<?php
/**
 * Database Setup - Creación de tablas base del plugin
 *
 * Esta clase maneja la creación de las tablas base del plugin
 * (conversaciones, mensajes, escalaciones) durante la activación.
 *
 * NOTA: Las tablas de módulos se gestionan en Flavor_Database_Installer.
 *
 * @package FlavorPlatform
 * @subpackage Bootstrap
 * @since 3.2.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona la creación de tablas base del plugin
 */
final class Flavor_Database_Setup {

    /**
     * Instancia singleton
     *
     * @var Flavor_Database_Setup|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Database_Setup
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Vacío
    }

    /**
     * Ejecuta toda la instalación de base de datos
     *
     * @return void
     */
    public function install() {
        // Usar nuevo sistema de migrations si está disponible
        if (class_exists('Flavor_Migration_Runner')) {
            $this->run_migrations();
        } else {
            // Fallback al método legacy
            $this->create_core_tables();
        }

        $this->create_module_tables();
        $this->install_via_database_installer();
    }

    /**
     * Ejecuta las migrations pendientes
     *
     * @return array Resultado de las migrations
     */
    public function run_migrations() {
        $runner = Flavor_Migration_Runner::get_instance();
        $runner->init();

        $results = $runner->run_pending();

        if (!empty($results['executed'])) {
            flavor_chat_ia_log(
                sprintf('Migrations ejecutadas: %d', count($results['executed'])),
                'info',
                'database'
            );
        }

        if (!empty($results['errors'])) {
            flavor_chat_ia_log(
                sprintf('Errores en migrations: %s', implode(', ', $results['errors'])),
                'error',
                'database'
            );
        }

        return $results;
    }

    /**
     * Crea las tablas core del plugin (chat, conversaciones, mensajes)
     *
     * @return void
     */
    public function create_core_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de conversaciones
        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';
        $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            language varchar(10) DEFAULT 'es',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            escalated tinyint(1) DEFAULT 0,
            escalation_reason text DEFAULT NULL,
            conversion_type varchar(50) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY started_at (started_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de mensajes
        $table_messages = $wpdb->prefix . 'flavor_chat_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            role enum('user','assistant','system') NOT NULL,
            content text NOT NULL,
            tool_calls text DEFAULT NULL,
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabla de escalaciones
        $table_escalations = $wpdb->prefix . 'flavor_chat_escalations';
        $sql_escalations = "CREATE TABLE IF NOT EXISTS $table_escalations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            reason text NOT NULL,
            summary text NOT NULL,
            contact_method varchar(20) DEFAULT NULL,
            status enum('pending','contacted','resolved') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_escalations);
    }

    /**
     * Crea las tablas de módulos específicos con instaladores propios
     *
     * @return void
     */
    public function create_module_tables() {
        // Módulos principales con install.php propio
        $modules_con_install = [
            'socios',
            'eventos',
            'reservas',
            'clientes',
            'facturas',
            'banco-tiempo',
            'grupos-consumo',
        ];

        foreach ($modules_con_install as $module_slug) {
            // Convertir guiones a guiones bajos para nombre de función
            $function_slug = str_replace('-', '_', $module_slug);

            // Determinar nombre de función
            $function_name = ($module_slug === 'reservas')
                ? "flavor_{$function_slug}_crear_tabla"  // Reservas usa singular
                : "flavor_{$function_slug}_crear_tablas"; // El resto usa plural

            // Banco-tiempo usa nombre especial
            if ($module_slug === 'banco-tiempo') {
                $function_name = 'flavor_banco_tiempo_install';
            }
            // Grupos-consumo usa nombre especial
            if ($module_slug === 'grupos-consumo') {
                $function_name = 'flavor_grupos_consumo_install';
            }

            $this->maybe_run_module_installer($module_slug, $function_name);
        }

        // Deep Link Manager
        if (class_exists('Flavor_Deep_Link_Manager')) {
            Flavor_Deep_Link_Manager::create_tables();
        }

        // Activar rewrite rules para Deep Links
        if (class_exists('Flavor_Deep_Link_Handler')) {
            Flavor_Deep_Link_Handler::activate();
        }

        // Sistema de Notificaciones
        if (class_exists('Flavor_Notification_Manager')) {
            $notifications = Flavor_Notification_Manager::get_instance();
            if (method_exists($notifications, 'create_tables')) {
                $notifications->create_tables();
            }
        }

        // Centro de Notificaciones
        if (class_exists('Flavor_Notification_Center')) {
            $notification_center = Flavor_Notification_Center::get_instance();
            if (method_exists($notification_center, 'maybe_create_table')) {
                $notification_center->maybe_create_table();
            }
        }

        // Sistema de Webhooks
        if (class_exists('Flavor_Webhook_Manager')) {
            $webhooks = Flavor_Webhook_Manager::get_instance();
            if (method_exists($webhooks, 'create_tables')) {
                $webhooks->create_tables();
            }
        }

        // Sistema de Formularios de Layouts
        if (class_exists('Flavor_Layout_Forms')) {
            $layout_forms = Flavor_Layout_Forms::get_instance();
            if (method_exists($layout_forms, 'create_tables')) {
                $layout_forms->create_tables();
            }
        }

        // Hook para instalaciones adicionales de módulos
        do_action('flavor_chat_ia_install_modules');
    }

    /**
     * Ejecuta un instalador de módulo si existe
     *
     * @param string $module_slug Slug del módulo
     * @param string $function_name Nombre de la función de instalación
     * @return void
     */
    private function maybe_run_module_installer($module_slug, $function_name) {
        $install_path = FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/install.php";

        if (file_exists($install_path)) {
            require_once $install_path;
            if (function_exists($function_name)) {
                call_user_func($function_name);
            }
        }
    }

    /**
     * Instala tablas mediante el instalador centralizado
     *
     * @return void
     */
    public function install_via_database_installer() {
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::install_tables();
            Flavor_Database_Installer::install_legal_pages();
        }
    }

    /**
     * Verifica e instala tablas de módulos si no existen
     *
     * Se ejecuta en init() para asegurar que las tablas estén disponibles
     *
     * @return void
     */
    public function maybe_install_tables() {
        // Solo ejecutar si no está instalado aún
        $db_version = get_option('flavor_db_version', '');
        if (!empty($db_version)) {
            return;
        }

        // Verificar si hay al menos una tabla crítica
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_existe = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_eventos
        ));

        if ($tabla_existe === $tabla_eventos) {
            // Ya existen las tablas, marcar como instalado
            update_option('flavor_db_version', '1.0.0');
            return;
        }

        // Instalar tablas
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::install_tables();
            flavor_chat_ia_log('Tablas de módulos instaladas automáticamente', 'info');
        }
    }

    /**
     * Verifica actualizaciones de BD (índices, migraciones)
     *
     * @return void
     */
    public function maybe_upgrade() {
        if (class_exists('Flavor_Database_Installer')) {
            Flavor_Database_Installer::maybe_upgrade();
        }
    }

    /**
     * Instala páginas legales si no existen
     *
     * @return void
     */
    public function maybe_install_legal_pages() {
        if (get_option('flavor_legal_pages_installed') !== '1') {
            if (class_exists('Flavor_Database_Installer')) {
                Flavor_Database_Installer::install_legal_pages();
                update_option('flavor_legal_pages_installed', '1');
            }
        }
    }

    /**
     * Corrige URLs de placeholder en la base de datos
     *
     * Convierte URLs del tipo "400x250?text=..." a SVG data URIs
     *
     * @return void
     */
    public function maybe_fix_placeholder_urls() {
        // Versión 2: Convertir a SVG
        if (get_option('flavor_placeholder_urls_fixed') === '2') {
            return;
        }

        global $wpdb;

        // Tablas y columnas que pueden contener URLs de imágenes
        $tablas_columnas = [
            $wpdb->prefix . 'flavor_incidencias' => ['imagen'],
            $wpdb->prefix . 'flavor_incidencias_categorias' => ['imagen', 'imagen_url'],
            $wpdb->prefix . 'flavor_eventos' => ['imagen'],
            $wpdb->prefix . 'flavor_espacios' => ['imagen'],
            $wpdb->prefix . 'flavor_cursos' => ['imagen'],
            $wpdb->prefix . 'flavor_talleres' => ['imagen'],
            $wpdb->prefix . 'flavor_marketplace' => ['imagen'],
            $wpdb->prefix . 'flavor_podcast_episodios' => ['imagen'],
        ];

        foreach ($tablas_columnas as $tabla => $columnas) {
            // Verificar si la tabla existe
            $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla));
            if (!$tabla_existe) {
                continue;
            }

            foreach ($columnas as $columna) {
                // Verificar si la columna existe
                $columna_existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $tabla,
                    $columna
                ));

                if (!$columna_existe) {
                    continue;
                }

                // Obtener registros con URLs de placeholder
                $registros = $wpdb->get_results(
                    "SELECT id, {$columna} as url FROM {$tabla} WHERE {$columna} LIKE '%placeholder%' OR {$columna} REGEXP '^[0-9]+x[0-9]+\\\\?text='"
                );

                foreach ($registros as $registro) {
                    $url_original = $registro->url;
                    $url_corregida = Flavor_Chat_Helpers::fix_placeholder_url($url_original);

                    if ($url_corregida !== $url_original) {
                        $wpdb->update(
                            $tabla,
                            [$columna => $url_corregida],
                            ['id' => $registro->id]
                        );
                    }
                }
            }
        }

        update_option('flavor_placeholder_urls_fixed', '2');
    }
}
