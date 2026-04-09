<?php
/**
 * Asistente de Configuración de Módulos
 *
 * Detecta módulos sin configurar y guía al usuario paso a paso.
 * Genera checklists de configuración y ofrece ayuda contextual.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Setup_Assistant {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuración requerida por módulo
     */
    private $module_requirements = [];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_module_requirements();
    }

    /**
     * Define los requisitos de configuración para cada módulo
     */
    private function define_module_requirements() {
        $this->module_requirements = [
            'socios' => [
                'nombre' => __('Gestión de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'pasos' => [
                    [
                        'id' => 'tipos_socio',
                        'titulo' => __('Definir tipos de socio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Configura los diferentes tipos de membresía (ej: consumidor, colaborador)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_socios_tipos',
                        'url' => 'admin.php?page=socios-dashboard&tab=configuracion',
                    ],
                    [
                        'id' => 'cuotas',
                        'titulo' => __('Configurar cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Establece los importes de cuota mensual/anual', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_socios_cuotas',
                        'url' => 'admin.php?page=socios-dashboard&tab=cuotas',
                    ],
                    [
                        'id' => 'campos_formulario',
                        'titulo' => __('Personalizar formulario de alta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Define qué campos pedir en el registro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_socios_formulario',
                        'url' => 'admin.php?page=socios-dashboard&tab=configuracion#formulario',
                        'opcional' => true,
                    ],
                ],
            ],

            'eventos' => [
                'nombre' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar-alt',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Organiza los eventos por tipo (talleres, charlas, fiestas...)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_eventos_categorias',
                        'url' => 'admin.php?page=eventos-dashboard&tab=categorias',
                    ],
                    [
                        'id' => 'ubicaciones',
                        'titulo' => __('Definir ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Añade las ubicaciones donde se realizan eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_eventos_ubicaciones',
                        'url' => 'admin.php?page=eventos-dashboard&tab=ubicaciones',
                        'opcional' => true,
                    ],
                    [
                        'id' => 'primer_evento',
                        'titulo' => __('Crear primer evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Publica tu primer evento de prueba', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_eventos_tiene_eventos',
                        'url' => 'admin.php?page=eventos-dashboard&action=nuevo',
                    ],
                ],
            ],

            'reservas' => [
                'nombre' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar',
                'pasos' => [
                    [
                        'id' => 'recursos',
                        'titulo' => __('Crear recursos reservables', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Define qué se puede reservar (salas, equipos, vehículos...)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_reservas_recursos',
                        'url' => 'admin.php?page=reservas-dashboard&tab=recursos',
                    ],
                    [
                        'id' => 'horarios',
                        'titulo' => __('Configurar horarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Define horarios de disponibilidad y duración de slots', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_reservas_horarios',
                        'url' => 'admin.php?page=reservas-dashboard&tab=configuracion',
                    ],
                    [
                        'id' => 'reglas',
                        'titulo' => __('Establecer reglas de reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Antelación mínima, máximo de reservas por usuario, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_reservas_reglas',
                        'url' => 'admin.php?page=reservas-dashboard&tab=configuracion#reglas',
                        'opcional' => true,
                    ],
                ],
            ],

            'grupos-consumo' => [
                'nombre' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-cart',
                'pasos' => [
                    [
                        'id' => 'productores',
                        'titulo' => __('Añadir productores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Registra los productores/proveedores del grupo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_gc_productores',
                        'url' => 'admin.php?page=grupos-consumo-dashboard&tab=productores',
                    ],
                    [
                        'id' => 'productos',
                        'titulo' => __('Cargar productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Añade los productos disponibles para pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_gc_productos',
                        'url' => 'admin.php?page=grupos-consumo-dashboard&tab=productos',
                    ],
                    [
                        'id' => 'ciclo',
                        'titulo' => __('Crear primer ciclo de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Abre el primer ciclo para que los miembros puedan pedir', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_gc_ciclos',
                        'url' => 'admin.php?page=grupos-consumo-dashboard&tab=ciclos&action=nuevo',
                    ],
                ],
            ],

            'incidencias' => [
                'nombre' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-warning',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Tipos de incidencias (averías, sugerencias, quejas...)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_incidencias_categorias',
                        'url' => 'admin.php?page=incidencias-dashboard&tab=categorias',
                    ],
                    [
                        'id' => 'responsables',
                        'titulo' => __('Asignar responsables', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Define quién puede gestionar incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_incidencias_responsables',
                        'url' => 'admin.php?page=incidencias-dashboard&tab=configuracion',
                        'opcional' => true,
                    ],
                ],
            ],

            'cursos' => [
                'nombre' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-welcome-learn-more',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Organiza los cursos por temática', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_cursos_categorias',
                        'url' => 'admin.php?page=cursos-dashboard&tab=categorias',
                    ],
                    [
                        'id' => 'primer_curso',
                        'titulo' => __('Crear primer curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Publica tu primer curso con lecciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_cursos_tiene_cursos',
                        'url' => 'admin.php?page=cursos-dashboard&action=nuevo',
                    ],
                ],
            ],

            'biblioteca' => [
                'nombre' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-book',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Organiza el catálogo por géneros o tipos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_biblioteca_categorias',
                        'url' => 'admin.php?page=biblioteca-dashboard&tab=categorias',
                    ],
                    [
                        'id' => 'libros',
                        'titulo' => __('Añadir libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Carga el catálogo de libros disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_biblioteca_libros',
                        'url' => 'admin.php?page=biblioteca-dashboard&action=nuevo',
                    ],
                    [
                        'id' => 'reglas_prestamo',
                        'titulo' => __('Configurar préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Días de préstamo, renovaciones, multas...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_biblioteca_reglas',
                        'url' => 'admin.php?page=biblioteca-dashboard&tab=configuracion',
                        'opcional' => true,
                    ],
                ],
            ],

            'foros' => [
                'nombre' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-format-chat',
                'pasos' => [
                    [
                        'id' => 'foros',
                        'titulo' => __('Crear foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Crea los foros de discusión principales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_foros_tiene_foros',
                        'url' => 'admin.php?page=foros-dashboard&action=nuevo',
                    ],
                ],
            ],

            'encuestas' => [
                'nombre' => __('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-chart-bar',
                'pasos' => [
                    [
                        'id' => 'primera_encuesta',
                        'titulo' => __('Crear primera encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Publica tu primera encuesta o votación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_encuestas_tiene_encuestas',
                        'url' => 'admin.php?page=encuestas-dashboard&action=nueva',
                    ],
                ],
            ],

            'banco-tiempo' => [
                'nombre' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clock',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Organiza los servicios por tipo (hogar, idiomas, informática...)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_bt_categorias',
                        'url' => 'admin.php?page=banco-tiempo-dashboard&tab=categorias',
                    ],
                ],
            ],

            'marketplace' => [
                'nombre' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-store',
                'pasos' => [
                    [
                        'id' => 'categorias',
                        'titulo' => __('Crear categorías', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Organiza los anuncios por tipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_marketplace_categorias',
                        'url' => 'admin.php?page=marketplace-dashboard&tab=categorias',
                    ],
                    [
                        'id' => 'reglas',
                        'titulo' => __('Configurar reglas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'descripcion' => __('Moderación, límites de anuncios, caducidad...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'check' => 'check_marketplace_reglas',
                        'url' => 'admin.php?page=marketplace-dashboard&tab=configuracion',
                        'opcional' => true,
                    ],
                ],
            ],
        ];

        // Permitir extensión por otros plugins
        $this->module_requirements = apply_filters('flavor_module_setup_requirements', $this->module_requirements);
    }

    /**
     * Obtiene el estado de configuración de todos los módulos activos
     *
     * @return array
     */
    public function get_all_modules_setup_status() {
        $active_modules = get_option('flavor_active_modules', []);
        $status = [];

        foreach ($active_modules as $module_id) {
            if (isset($this->module_requirements[$module_id])) {
                $status[$module_id] = $this->get_module_setup_status($module_id);
            }
        }

        return $status;
    }

    /**
     * Obtiene el estado de configuración de un módulo específico
     *
     * @param string $module_id
     * @return array
     */
    public function get_module_setup_status($module_id) {
        if (!isset($this->module_requirements[$module_id])) {
            return null;
        }

        $config = $this->module_requirements[$module_id];
        $pasos_completados = 0;
        $pasos_requeridos = 0;
        $checklist = [];

        foreach ($config['pasos'] as $paso) {
            $completado = $this->execute_check($paso['check']);
            $es_requerido = empty($paso['opcional']);

            $checklist[] = [
                'id' => $paso['id'],
                'titulo' => $paso['titulo'],
                'descripcion' => $paso['descripcion'],
                'completado' => $completado,
                'opcional' => !$es_requerido,
                'url' => admin_url($paso['url']),
            ];

            if ($es_requerido) {
                $pasos_requeridos++;
                if ($completado) {
                    $pasos_completados++;
                }
            }
        }

        $porcentaje = $pasos_requeridos > 0 ? round(($pasos_completados / $pasos_requeridos) * 100) : 100;

        return [
            'modulo_id' => $module_id,
            'nombre' => $config['nombre'],
            'icono' => $config['icono'],
            'completado' => $porcentaje === 100,
            'porcentaje' => $porcentaje,
            'pasos_completados' => $pasos_completados,
            'pasos_requeridos' => $pasos_requeridos,
            'checklist' => $checklist,
            'siguiente_paso' => $this->get_next_step($checklist),
        ];
    }

    /**
     * Obtiene el siguiente paso pendiente
     *
     * @param array $checklist
     * @return array|null
     */
    private function get_next_step($checklist) {
        foreach ($checklist as $paso) {
            if (!$paso['completado'] && !$paso['opcional']) {
                return $paso;
            }
        }
        // Si todos los requeridos están, buscar opcional pendiente
        foreach ($checklist as $paso) {
            if (!$paso['completado']) {
                return $paso;
            }
        }
        return null;
    }

    /**
     * Ejecuta una verificación de configuración
     *
     * @param string $check_method
     * @return bool
     */
    private function execute_check($check_method) {
        if (method_exists($this, $check_method)) {
            return $this->$check_method();
        }
        return false;
    }

    /**
     * Obtiene módulos que necesitan configuración
     *
     * @return array
     */
    public function get_modules_needing_setup() {
        $all_status = $this->get_all_modules_setup_status();
        $needs_setup = [];

        foreach ($all_status as $module_id => $status) {
            if (!$status['completado']) {
                $needs_setup[$module_id] = $status;
            }
        }

        // Ordenar por porcentaje (los más avanzados primero)
        uasort($needs_setup, function($a, $b) {
            return $b['porcentaje'] <=> $a['porcentaje'];
        });

        return $needs_setup;
    }

    /**
     * Genera texto de ayuda para el asistente IA
     *
     * @return string
     */
    public function get_setup_context_for_ai() {
        $needs_setup = $this->get_modules_needing_setup();

        if (empty($needs_setup)) {
            return "=== ESTADO DE CONFIGURACIÓN ===\nTodos los módulos activos están correctamente configurados.";
        }

        $output = ["=== MÓDULOS PENDIENTES DE CONFIGURAR ==="];

        foreach ($needs_setup as $module_id => $status) {
            $output[] = "\n## {$status['nombre']} ({$status['porcentaje']}% completado)";

            foreach ($status['checklist'] as $paso) {
                $estado = $paso['completado'] ? '✓' : '○';
                $opcional = $paso['opcional'] ? ' (opcional)' : '';
                $output[] = "  {$estado} {$paso['titulo']}{$opcional}";
            }

            if ($status['siguiente_paso']) {
                $output[] = "  → Siguiente: {$status['siguiente_paso']['titulo']}";
            }
        }

        return implode("\n", $output);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTODOS DE VERIFICACIÓN
    // ═══════════════════════════════════════════════════════════════════

    private function check_socios_tipos() {
        $settings = get_option('flavor_socios_settings', []);
        return !empty($settings['tipos_socio']);
    }

    private function check_socios_cuotas() {
        $settings = get_option('flavor_socios_settings', []);
        return isset($settings['cuota_mensual']) && $settings['cuota_mensual'] > 0;
    }

    private function check_socios_formulario() {
        $settings = get_option('flavor_socios_settings', []);
        return !empty($settings['campos_formulario']);
    }

    private function check_eventos_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_eventos_ubicaciones() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_ubicaciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return true; // Opcional, asumir OK si no existe
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_eventos_tiene_eventos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_reservas_recursos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas_recursos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_reservas_horarios() {
        $settings = get_option('flavor_reservas_settings', []);
        return !empty($settings['horario_inicio']);
    }

    private function check_reservas_reglas() {
        $settings = get_option('flavor_reservas_settings', []);
        return !empty($settings['reglas_configuradas']);
    }

    private function check_gc_productores() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_productores';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_gc_productos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_productos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_gc_ciclos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_ciclos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_incidencias_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_incidencias_responsables() {
        $settings = get_option('flavor_incidencias_settings', []);
        return !empty($settings['responsables']);
    }

    private function check_cursos_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_cursos_tiene_cursos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_biblioteca_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_biblioteca_libros() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca_libros';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_biblioteca_reglas() {
        $settings = get_option('flavor_biblioteca_settings', []);
        return !empty($settings['dias_prestamo']);
    }

    private function check_foros_tiene_foros() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_foros';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_encuestas_tiene_encuestas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_encuestas';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_bt_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_banco_tiempo_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_marketplace_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_marketplace_categorias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}") > 0;
    }

    private function check_marketplace_reglas() {
        $settings = get_option('flavor_marketplace_settings', []);
        return !empty($settings['reglas_configuradas']);
    }
}
