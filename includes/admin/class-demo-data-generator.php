<?php
/**
 * Generador de datos de demostración - Legacy Admin UI
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 * @deprecated 2.3.0 Use Flavor_Demo_Data_Manager instead
 *
 * Esta clase se mantiene por compatibilidad hacia atrás.
 * Para generación de datos demo, usar:
 * - Flavor_Demo_Data_Manager: Gestor completo con UI y acciones
 * - Flavor_Demo_Data_Generator: Generación programática de datos
 *
 * La funcionalidad de esta clase ha sido integrada en Flavor_Demo_Data_Manager
 * como el preset "tejido_empresarial".
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar el generador principal si existe
$main_generator_path = dirname(__DIR__) . '/class-demo-data-generator.php';
if (file_exists($main_generator_path) && !class_exists('Flavor_Demo_Data_Generator', false)) {
    require_once $main_generator_path;
}

// Cargar el Manager si existe (implementación principal)
$manager_path = __DIR__ . '/class-demo-data-manager.php';
if (file_exists($manager_path) && !class_exists('Flavor_Demo_Data_Manager', false)) {
    require_once $manager_path;
}

if (class_exists('Flavor_Demo_Data_Admin', false)) {
    return;
}

/**
 * Clase legacy para UI de administración del generador de datos demo
 *
 * @deprecated 2.3.0 Use Flavor_Demo_Data_Manager instead
 *
 * Esta clase se mantiene para:
 * - Compatibilidad con código existente que instancie esta clase
 * - El preset específico "Tejido Empresarial" (usuarios emprendedores locales)
 *
 * Para nuevas implementaciones, usar Flavor_Demo_Data_Manager directamente.
 */
class Flavor_Demo_Data_Admin {

    /**
     * Instancia única
     *
     * @var Flavor_Demo_Data_Admin|null
     */
    private static $instance = null;

    /**
     * Instancia del Manager (delegación)
     *
     * @var Flavor_Demo_Data_Manager|null
     */
    private $manager = null;

    /**
     * Datos de usuarios demo para preset "Tejido Empresarial"
     *
     * @var array
     */
    private $demo_users = [
        [
            'username'     => 'maria_cosmetica',
            'email'        => 'maria@demo.local',
            'display_name' => 'María García',
            'business'     => 'Cosmética Natural Estella',
            'category'     => 'Belleza y Cuidado Personal',
            'location'     => 'Estella-Lizarra',
            'description'  => 'Elaboración artesanal de jabones, cremas y productos de cosmética natural con ingredientes locales.',
        ],
        [
            'username'     => 'carlos_carpintero',
            'email'        => 'carlos@demo.local',
            'display_name' => 'Carlos Martínez',
            'business'     => 'Carpintería Artesana Ayegui',
            'category'     => 'Artesanía y Oficios',
            'location'     => 'Ayegui',
            'description'  => 'Carpintería tradicional especializada en muebles a medida y restauración de mobiliario antiguo.',
        ],
        [
            'username'     => 'ana_diseno',
            'email'        => 'ana@demo.local',
            'display_name' => 'Ana López',
            'business'     => 'Diseño Gráfico Local',
            'category'     => 'Servicios Profesionales',
            'location'     => 'Estella-Lizarra',
            'description'  => 'Diseño gráfico, branding e identidad visual para pequeños negocios y emprendedores de la comarca.',
        ],
        [
            'username'     => 'pedro_huerta',
            'email'        => 'pedro@demo.local',
            'display_name' => 'Pedro Sánchez',
            'business'     => 'Huerta Ecológica Villatuerta',
            'category'     => 'Alimentación y Agricultura',
            'location'     => 'Villatuerta',
            'description'  => 'Producción ecológica de verduras y hortalizas de temporada. Venta directa y grupos de consumo.',
        ],
        [
            'username'     => 'laura_asesora',
            'email'        => 'laura@demo.local',
            'display_name' => 'Laura Fernández',
            'business'     => 'Asesoría Fiscal Rural',
            'category'     => 'Servicios Profesionales',
            'location'     => 'Estella-Lizarra',
            'description'  => 'Asesoría fiscal y contable especializada en autónomos, cooperativas y pequeñas empresas rurales.',
        ],
    ];

    /**
     * Productos de marketplace para preset "Tejido Empresarial"
     *
     * @var array
     */
    private $marketplace_items = [
        [
            'title'       => 'Jabón artesanal de lavanda',
            'price'       => 8,
            'category'    => 'Cosmética Natural',
            'user_key'    => 'maria_cosmetica',
            'description' => 'Jabón elaborado con aceite de oliva virgen extra de la zona y esencia de lavanda del Pirineo. 100% natural, sin químicos.',
            'type'        => 'producto',
        ],
        [
            'title'       => 'Tabla de cortar madera de nogal',
            'price'       => 45,
            'category'    => 'Artesanía',
            'user_key'    => 'carlos_carpintero',
            'description' => 'Tabla de cortar artesanal fabricada con madera de nogal local. Tratada con aceite alimentario. Medidas: 40x25cm.',
            'type'        => 'producto',
        ],
        [
            'title'       => 'Pack diseño logo + tarjetas',
            'price'       => 150,
            'category'    => 'Servicios',
            'user_key'    => 'ana_diseno',
            'description' => 'Diseño de logotipo profesional con 3 propuestas + diseño de tarjetas de visita. Incluye archivos en todos los formatos.',
            'type'        => 'servicio',
        ],
        [
            'title'       => 'Cesta verduras de temporada',
            'price'       => 25,
            'category'    => 'Alimentación',
            'user_key'    => 'pedro_huerta',
            'description' => 'Cesta semanal con 5-6 kg de verduras ecológicas de temporada. Recogida en Villatuerta o puntos de la comarca.',
            'type'        => 'producto',
        ],
        [
            'title'       => 'Asesoría fiscal inicial autónomos',
            'price'       => 50,
            'category'    => 'Servicios',
            'user_key'    => 'laura_asesora',
            'description' => 'Sesión de asesoría de 1 hora para nuevos autónomos. Revisamos tu situación, obligaciones fiscales y optimización.',
            'type'        => 'servicio',
        ],
    ];

    /**
     * Servicios banco de tiempo para preset "Tejido Empresarial"
     *
     * @var array
     */
    private $banco_tiempo_services = [
        [
            'title'       => 'Taller de cosmética natural',
            'hours'       => 2,
            'user_key'    => 'maria_cosmetica',
            'description' => 'Taller práctico donde aprenderás a hacer tu propio jabón o crema. Incluye materiales.',
            'category'    => 'Formación',
        ],
        [
            'title'       => 'Reparación de muebles',
            'hours'       => 1,
            'user_key'    => 'carlos_carpintero',
            'description' => 'Reparaciones pequeñas de muebles: patas, cajones, bisagras, etc. Precio por hora.',
            'category'    => 'Hogar',
        ],
        [
            'title'       => 'Diseño de logotipo básico',
            'hours'       => 3,
            'user_key'    => 'ana_diseno',
            'description' => 'Diseño de un logotipo sencillo para tu negocio o proyecto. Incluye 2 propuestas y archivos finales.',
            'category'    => 'Diseño',
        ],
        [
            'title'       => 'Asesoría fiscal básica',
            'hours'       => 1,
            'user_key'    => 'laura_asesora',
            'description' => 'Consulta sobre dudas fiscales, declaraciones, facturas, etc. Orientación general.',
            'category'    => 'Asesoría',
        ],
        [
            'title'       => 'Clase de huerto urbano',
            'hours'       => 2,
            'user_key'    => 'pedro_huerta',
            'description' => 'Aprende a cultivar tus propias verduras en casa o en un pequeño espacio. Teoría y práctica.',
            'category'    => 'Formación',
        ],
    ];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Demo_Data_Admin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Obtener referencia al Manager si existe
        if (class_exists('Flavor_Demo_Data_Manager')) {
            $this->manager = Flavor_Demo_Data_Manager::get_instance();
        }

        // Solo registrar hooks si el Manager no maneja ya el menú
        if (!$this->manager || !has_action('admin_menu', [$this->manager, 'register_demo_menu'])) {
            add_action('admin_menu', [$this, 'add_admin_menu'], 100);
        }

        // Acciones propias (legacy)
        add_action('admin_post_flavor_generate_demo_data', [$this, 'handle_generate_demo']);
        add_action('admin_post_flavor_cleanup_demo_data', [$this, 'handle_cleanup_demo']);
    }

    /**
     * Añadir menú de admin (legacy)
     *
     * @deprecated 2.3.0 El Manager maneja su propio menú
     */
    public function add_admin_menu() {
        // Solo añadir si no existe ya un menú de demo del Manager
        global $submenu;
        $menu_exists = false;

        if (isset($submenu['flavor-chat-ia'])) {
            foreach ($submenu['flavor-chat-ia'] as $item) {
                if (strpos($item[2] ?? '', 'demo') !== false) {
                    $menu_exists = true;
                    break;
                }
            }
        }

        if (!$menu_exists) {
            add_submenu_page(
                'flavor-chat-ia',
                'Demo Tejido Empresarial',
                '🎯 Demo Local',
                'manage_options',
                'flavor-demo-data-legacy',
                [$this, 'render_admin_page']
            );
        }
    }

    /**
     * Renderizar página de admin
     */
    public function render_admin_page() {
        $demo_exists = $this->check_demo_exists();
        ?>
        <div class="wrap">
            <h1>Datos Demo - Tejido Empresarial Local</h1>

            <div class="notice notice-info">
                <p>
                    <strong>Nota:</strong> Esta es la interfaz legacy para el preset "Tejido Empresarial".
                    Para gestión completa de datos demo, usa
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-demo-data')); ?>">
                        Flavor Platform → Datos Demo
                    </a>
                </p>
            </div>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2>Preset: Emprendimiento Local</h2>
                <p>Este preset crea datos de ejemplo para demostrar el ecosistema de emprendimiento:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong>5 usuarios</strong> emprendedores ficticios</li>
                    <li><strong>5 productos</strong> en el marketplace</li>
                    <li><strong>5 servicios</strong> en el banco de tiempo</li>
                    <li><strong>1 grupo de consumo</strong> con productores</li>
                    <li><strong>1 taller</strong> de formación</li>
                </ul>

                <?php if ($demo_exists) : ?>
                    <div class="notice notice-success inline" style="margin: 15px 0;">
                        <p><strong>✓ Datos de demo ya generados</strong></p>
                    </div>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="flavor_cleanup_demo_data">
                        <?php wp_nonce_field('flavor_demo_data', 'demo_nonce'); ?>
                        <button type="submit" class="button" onclick="return confirm('¿Eliminar todos los datos de demo?');">
                            🗑️ Limpiar datos de demo
                        </button>
                    </form>
                <?php else : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="flavor_generate_demo_data">
                        <?php wp_nonce_field('flavor_demo_data', 'demo_nonce'); ?>
                        <button type="submit" class="button button-primary" style="margin-top: 15px;">
                            🚀 Generar Datos de Demo
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h3>Usuarios de Demo</h3>
                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Negocio</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->demo_users as $user) : ?>
                        <tr>
                            <td><code><?php echo esc_html($user['username']); ?></code></td>
                            <td><?php echo esc_html($user['business']); ?></td>
                            <td><?php echo esc_html($user['location']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 10px;"><small>Contraseña para todos: <code>demo2026</code></small></p>
            </div>
        </div>
        <?php
    }

    /**
     * Verificar si existen datos de demo
     *
     * @return bool
     */
    private function check_demo_exists() {
        $user = get_user_by('login', 'maria_cosmetica');
        return $user !== false;
    }

    /**
     * Obtener datos del preset "Tejido Empresarial"
     *
     * Útil para que el Manager pueda acceder a estos datos como preset.
     *
     * @return array
     */
    public function get_preset_data() {
        return [
            'id'          => 'tejido_empresarial',
            'name'        => 'Tejido Empresarial Local',
            'description' => 'Emprendedores locales con marketplace, banco de tiempo y grupos de consumo',
            'users'       => $this->demo_users,
            'marketplace' => $this->marketplace_items,
            'banco_tiempo'=> $this->banco_tiempo_services,
        ];
    }

    /**
     * Generar datos de demo
     */
    public function handle_generate_demo() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('flavor_demo_data', 'demo_nonce');

        $results = [];

        // 1. Crear usuarios
        $user_ids = [];
        foreach ($this->demo_users as $user_data) {
            $user_id = $this->create_demo_user($user_data);
            if ($user_id) {
                $user_ids[$user_data['username']] = $user_id;
                $results[] = "✓ Usuario creado: {$user_data['username']}";
            }
        }

        // 2. Crear productos en marketplace
        foreach ($this->marketplace_items as $item) {
            if (isset($user_ids[$item['user_key']])) {
                $this->create_marketplace_item($item, $user_ids[$item['user_key']]);
                $results[] = "✓ Producto marketplace: {$item['title']}";
            }
        }

        // 3. Crear servicios banco de tiempo
        foreach ($this->banco_tiempo_services as $service) {
            if (isset($user_ids[$service['user_key']])) {
                $this->create_banco_tiempo_service($service, $user_ids[$service['user_key']]);
                $results[] = "✓ Servicio banco tiempo: {$service['title']}";
            }
        }

        // 4. Crear grupo de consumo
        $this->create_grupo_consumo($user_ids);
        $results[] = '✓ Grupo de consumo: Cesta de la Tierra Estella';

        // 5. Crear taller
        if (isset($user_ids['ana_diseno'])) {
            $this->create_taller($user_ids['ana_diseno']);
            $results[] = '✓ Taller: Marketing Digital para Pequeños Negocios';
        }

        // Guardar log
        update_option('flavor_demo_data_log', $results);

        Flavor_Chat_Helpers::safe_redirect(admin_url('admin.php?page=flavor-demo-data-legacy&generated=1'));
        exit;
    }

    /**
     * Crear usuario de demo
     *
     * @param array $data Datos del usuario.
     * @return int|false ID del usuario o false si falla.
     */
    private function create_demo_user($data) {
        $existing = get_user_by('login', $data['username']);
        if ($existing) {
            return $existing->ID;
        }

        $user_id = wp_create_user(
            $data['username'],
            'demo2026',
            $data['email']
        );

        if (is_wp_error($user_id)) {
            return false;
        }

        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $data['display_name'],
            'description'  => $data['description'],
        ]);

        // Metadatos de emprendedor
        update_user_meta($user_id, 'business_name', $data['business']);
        update_user_meta($user_id, 'business_category', $data['category']);
        update_user_meta($user_id, 'location', $data['location']);
        update_user_meta($user_id, 'is_demo_user', true);
        update_user_meta($user_id, 'banco_tiempo_balance', 5);

        return $user_id;
    }

    /**
     * Crear item de marketplace
     *
     * @param array $item    Datos del item.
     * @param int   $user_id ID del usuario propietario.
     * @return int|false
     */
    private function create_marketplace_item($item, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_marketplace_items';

        // Verificar si la tabla existe
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            // Usar post type si no hay tabla
            $post_id = wp_insert_post([
                'post_title'   => $item['title'],
                'post_content' => $item['description'],
                'post_status'  => 'publish',
                'post_type'    => 'marketplace_item',
                'post_author'  => $user_id,
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'price', $item['price']);
                update_post_meta($post_id, 'category', $item['category']);
                update_post_meta($post_id, 'type', $item['type']);
                update_post_meta($post_id, 'is_demo', true);
            }
            return $post_id;
        }

        $wpdb->insert($table, [
            'user_id'     => $user_id,
            'title'       => $item['title'],
            'description' => $item['description'],
            'price'       => $item['price'],
            'category'    => $item['category'],
            'type'        => $item['type'],
            'status'      => 'active',
            'is_demo'     => 1,
            'created_at'  => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Crear servicio de banco de tiempo
     *
     * @param array $service Datos del servicio.
     * @param int   $user_id ID del usuario proveedor.
     * @return int|false
     */
    private function create_banco_tiempo_service($service, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_banco_tiempo_services';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            $post_id = wp_insert_post([
                'post_title'   => $service['title'],
                'post_content' => $service['description'],
                'post_status'  => 'publish',
                'post_type'    => 'banco_tiempo_service',
                'post_author'  => $user_id,
            ]);

            if ($post_id) {
                update_post_meta($post_id, 'hours', $service['hours']);
                update_post_meta($post_id, 'category', $service['category']);
                update_post_meta($post_id, 'is_demo', true);
            }
            return $post_id;
        }

        $wpdb->insert($table, [
            'user_id'     => $user_id,
            'title'       => $service['title'],
            'description' => $service['description'],
            'hours'       => $service['hours'],
            'category'    => $service['category'],
            'status'      => 'active',
            'is_demo'     => 1,
            'created_at'  => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Crear grupo de consumo
     *
     * @param array $user_ids IDs de usuarios.
     * @return bool
     */
    private function create_grupo_consumo($user_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_grupos_consumo';

        $group_data = [
            'name'            => 'Cesta de la Tierra Estella',
            'description'     => 'Grupo de consumo de productos ecológicos locales. Recogida semanal los jueves en Plaza de los Fueros.',
            'pickup_location' => 'Plaza de los Fueros, Estella',
            'cycle'           => 'semanal',
            'cycle_day'       => 'jueves',
            'is_demo'         => 1,
        ];

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            update_option('flavor_demo_grupo_consumo', $group_data);
            return true;
        }

        $wpdb->insert($table, array_merge($group_data, [
            'created_at' => current_time('mysql'),
        ]));

        return (bool) $wpdb->insert_id;
    }

    /**
     * Crear taller de formación
     *
     * @param int $instructor_id ID del instructor.
     * @return int|false
     */
    private function create_taller($instructor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_talleres';

        $taller_data = [
            'title'                => 'Marketing Digital para Pequeños Negocios',
            'description'          => 'Aprende a promocionar tu negocio en redes sociales y Google. Taller práctico con ordenador.',
            'instructor_id'        => $instructor_id,
            'location'             => 'Biblioteca de Estella',
            'date'                 => gmdate('Y-m-d', strtotime('next Saturday')),
            'time'                 => '10:00',
            'duration'             => 3,
            'max_participants'     => 15,
            'current_participants' => 8,
            'price'                => 0,
            'banco_tiempo_price'   => 2,
            'is_demo'              => 1,
        ];

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            $post_id = wp_insert_post([
                'post_title'   => $taller_data['title'],
                'post_content' => $taller_data['description'],
                'post_status'  => 'publish',
                'post_type'    => 'taller',
                'post_author'  => $instructor_id,
            ]);

            if ($post_id) {
                foreach ($taller_data as $key => $value) {
                    if (!in_array($key, ['title', 'description'], true)) {
                        update_post_meta($post_id, $key, $value);
                    }
                }
            }
            return $post_id;
        }

        $wpdb->insert($table, array_merge($taller_data, [
            'created_at' => current_time('mysql'),
        ]));

        return $wpdb->insert_id;
    }

    /**
     * Limpiar datos de demo
     */
    public function handle_cleanup_demo() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('flavor_demo_data', 'demo_nonce');

        global $wpdb;

        // Eliminar usuarios de demo
        foreach ($this->demo_users as $user_data) {
            $user = get_user_by('login', $user_data['username']);
            if ($user) {
                wp_delete_user($user->ID);
            }
        }

        // Eliminar posts de demo
        $post_types = ['marketplace_item', 'banco_tiempo_service', 'taller'];
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type'      => $post_type,
                'meta_key'       => 'is_demo',
                'meta_value'     => true,
                'posts_per_page' => -1,
            ]);
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }

        // Limpiar tablas personalizadas (si existen)
        $tables = [
            'flavor_marketplace_items',
            'flavor_banco_tiempo_services',
            'flavor_grupos_consumo',
            'flavor_talleres',
        ];
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query($wpdb->prepare("DELETE FROM `{$full_table}` WHERE is_demo = %d", 1));
        }

        // Limpiar options
        delete_option('flavor_demo_grupo_consumo');
        delete_option('flavor_demo_data_log');

        Flavor_Chat_Helpers::safe_redirect(admin_url('admin.php?page=flavor-demo-data-legacy&cleaned=1'));
        exit;
    }
}

// Inicializar
Flavor_Demo_Data_Admin::get_instance();
