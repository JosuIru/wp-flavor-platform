<?php
/**
 * Panel de Administración Unificado para Módulos
 *
 * Sistema genérico que detecta módulos activos y muestra sus
 * opciones de administración en un menú dinámico.
 * Funciona para cualquier perfil: restaurantes, ayuntamientos,
 * comunidades, grupos de consumo, etc.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Unified_Admin_Panel {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú principal
     */
    const MENU_SLUG = 'flavor-gestion';

    /**
     * Módulos registrados con sus páginas de admin
     * @var array
     */
    private $modulos_registrados = [];

    /**
     * Categorías de módulos para organización
     * @var array
     */
    private $categorias = [];

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
        // Definir categorías de módulos
        $this->definir_categorias();

        // Recopilar módulos en init temprano (siempre, para REST API)
        add_action('init', [$this, 'recopilar_modulos_admin'], 5);

        // REST API endpoints para dashboard (siempre)
        add_action('rest_api_init', [$this, 'registrar_endpoints_rest']);

        // Solo en admin: menús, assets y AJAX
        if (is_admin()) {
            add_action('admin_menu', [$this, 'registrar_menu_unificado'], 20);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_ajax_flavor_gestion_stats', [$this, 'ajax_obtener_estadisticas']);
        }
    }

    /**
     * Registra endpoints REST para el dashboard
     */
    public function registrar_endpoints_rest() {
        // Endpoint para estadísticas del dashboard
        register_rest_route('flavor/v1', '/admin/dashboard-stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_estadisticas'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // Endpoint para datos de gráficos
        register_rest_route('flavor/v1', '/admin/dashboard-charts', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_graficos'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST: Obtiene estadísticas del dashboard
     */
    public function rest_obtener_estadisticas($request) {
        $estadisticas = $this->recopilar_estadisticas_modulos();

        return new WP_REST_Response([
            'success' => true,
            'data' => $estadisticas,
            'timestamp' => current_time('mysql'),
        ], 200);
    }

    /**
     * REST: Obtiene datos para gráficos del dashboard
     */
    public function rest_obtener_graficos($request) {
        $periodo = $request->get_param('periodo') ?: '7d';
        $graficos = $this->generar_datos_graficos($periodo);

        return new WP_REST_Response([
            'success' => true,
            'data' => $graficos,
            'periodo' => $periodo,
            'timestamp' => current_time('mysql'),
        ], 200);
    }

    /**
     * Recopila estadísticas de todos los módulos activos
     */
    private function recopilar_estadisticas_modulos() {
        $estadisticas = [
            'modulos_activos' => count($this->modulos_registrados),
            'resumen' => [],
            'por_categoria' => [],
        ];

        foreach ($this->modulos_registrados as $id_modulo => $config) {
            // Si el módulo tiene callback de estadísticas, usarlo
            if (!empty($config['estadisticas']) && is_callable($config['estadisticas'])) {
                $stats_modulo = call_user_func($config['estadisticas']);
                if (!empty($stats_modulo)) {
                    $estadisticas['resumen'][$id_modulo] = [
                        'label' => $config['label'] ?? $id_modulo,
                        'icon' => $config['icon'] ?? 'dashicons-admin-generic',
                        'datos' => $stats_modulo,
                    ];
                }
            }

            // Agrupar por categoría
            $categoria = $config['categoria'] ?? 'otros';
            if (!isset($estadisticas['por_categoria'][$categoria])) {
                $estadisticas['por_categoria'][$categoria] = [
                    'label' => $this->categorias[$categoria]['label'] ?? ucfirst($categoria),
                    'modulos' => 0,
                ];
            }
            $estadisticas['por_categoria'][$categoria]['modulos']++;
        }

        return $estadisticas;
    }

    /**
     * Genera datos para los gráficos del dashboard
     */
    private function generar_datos_graficos($periodo) {
        global $wpdb;

        // Determinar rango de fechas según período
        $dias = 7;
        switch ($periodo) {
            case '30d': $dias = 30; break;
            case '90d': $dias = 90; break;
            case '1y': $dias = 365; break;
            default: $dias = 7;
        }

        $fecha_inicio = date('Y-m-d', strtotime("-{$dias} days"));
        $labels = [];
        $datos_actividad = [];

        // Generar labels y datos base
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date_i18n('j M', strtotime($fecha));
            $datos_actividad[] = 0;
        }

        // Intentar obtener datos de actividad de posts
        $actividad = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(post_date) as fecha, COUNT(*) as total
             FROM {$wpdb->posts}
             WHERE post_date >= %s AND post_status IN ('publish', 'pending')
             GROUP BY DATE(post_date)
             ORDER BY fecha ASC",
            $fecha_inicio
        ), ARRAY_A);

        if ($actividad) {
            foreach ($actividad as $dia) {
                $indice = array_search(date_i18n('j M', strtotime($dia['fecha'])), $labels);
                if ($indice !== false) {
                    $datos_actividad[$indice] = (int) $dia['total'];
                }
            }
        }

        return [
            'actividad' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => __('Actividad', 'flavor-chat-ia'),
                        'data' => $datos_actividad,
                        'borderColor' => '#2271b1',
                        'backgroundColor' => 'rgba(34, 113, 177, 0.1)',
                        'fill' => true,
                    ],
                ],
            ],
            'distribucion_modulos' => $this->obtener_distribucion_modulos(),
        ];
    }

    /**
     * Obtiene distribución de módulos por categoría para gráfico
     */
    private function obtener_distribucion_modulos() {
        $distribucion = [];
        $colores = [
            '#2271b1', '#135e96', '#72aee6', '#c3c4c7',
            '#00a32a', '#d63638', '#dba617', '#3858e9',
            '#9b59b6',
        ];

        $indice_color = 0;
        foreach ($this->categorias as $id => $categoria) {
            $count = 0;
            foreach ($this->modulos_registrados as $modulo) {
                if (($modulo['categoria'] ?? '') === $id) {
                    $count++;
                }
            }
            if ($count > 0) {
                $distribucion['labels'][] = $categoria['label'];
                $distribucion['data'][] = $count;
                $distribucion['colors'][] = $colores[$indice_color % count($colores)];
                $indice_color++;
            }
        }

        return $distribucion;
    }

    /**
     * Define las categorías de módulos
     */
    private function definir_categorias() {
        $this->categorias = [
            'personas' => [
                'label' => __('Personas', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
                'orden' => 10,
                'modulos' => ['socios', 'consumidores', 'clientes', 'empleados', 'usuarios'],
            ],
            'economia' => [
                'label' => __('Economía', 'flavor-chat-ia'),
                'icon' => 'dashicons-money-alt',
                'orden' => 20,
                'modulos' => ['facturas', 'pagos', 'cuotas', 'presupuestos', 'tesoreria'],
            ],
            'operaciones' => [
                'label' => __('Operaciones', 'flavor-chat-ia'),
                'icon' => 'dashicons-clipboard',
                'orden' => 30,
                'modulos' => ['reservas', 'pedidos', 'entregas', 'turnos', 'citas'],
            ],
            'recursos' => [
                'label' => __('Recursos', 'flavor-chat-ia'),
                'icon' => 'dashicons-building',
                'orden' => 40,
                'modulos' => ['espacios', 'inventario', 'vehiculos', 'equipamiento'],
            ],
            'comunicacion' => [
                'label' => __('Comunicación', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
                'orden' => 50,
                'modulos' => ['avisos', 'newsletter', 'notificaciones', 'mensajes'],
            ],
            'actividades' => [
                'label' => __('Actividades', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar-alt',
                'orden' => 60,
                'modulos' => ['eventos', 'talleres', 'cursos', 'formacion'],
            ],
            'servicios' => [
                'label' => __('Servicios', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-tools',
                'orden' => 70,
                'modulos' => ['tramites', 'incidencias', 'solicitudes', 'tickets'],
            ],
            'comunidad' => [
                'label' => __('Comunidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-share',
                'orden' => 80,
                'modulos' => ['participacion', 'banco_tiempo', 'ayuda_vecinal', 'foros'],
            ],
            'sostenibilidad' => [
                'label' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon' => 'dashicons-palmtree',
                'orden' => 90,
                'modulos' => ['reciclaje', 'compostaje', 'huertos', 'bicicletas', 'carpooling'],
            ],
        ];
    }

    /**
     * Recopila módulos que tienen páginas de admin
     * Los módulos se registran mediante el filtro 'flavor_admin_panel_modules'
     */
    public function recopilar_modulos_admin() {
        /**
         * Filtro para que los módulos registren sus páginas de admin
         *
         * @param array $modulos Array de módulos con formato:
         *   [
         *     'id_modulo' => [
         *       'label' => 'Nombre visible',
         *       'icon' => 'dashicons-xxx',
         *       'capability' => 'manage_options',
         *       'categoria' => 'personas|economia|operaciones|...',
         *       'paginas' => [
         *         [
         *           'slug' => 'pagina-slug',
         *           'titulo' => 'Título de la página',
         *           'callback' => callable,
         *           'badge' => callable|int (opcional, para mostrar contador),
         *         ],
         *       ],
         *       'dashboard_widget' => callable (opcional),
         *       'estadisticas' => callable (opcional),
         *     ],
         *   ]
         */
        $this->modulos_registrados = apply_filters('flavor_admin_panel_modules', []);

        // Ordenar por categoría
        uasort($this->modulos_registrados, function($a, $b) {
            $cat_a = $a['categoria'] ?? 'otros';
            $cat_b = $b['categoria'] ?? 'otros';
            $orden_a = $this->categorias[$cat_a]['orden'] ?? 999;
            $orden_b = $this->categorias[$cat_b]['orden'] ?? 999;
            return $orden_a - $orden_b;
        });
    }

    /**
     * Registra el menú unificado de gestión
     */
    public function registrar_menu_unificado() {
        // Solo mostrar si hay módulos registrados
        if (empty($this->modulos_registrados)) {
            return;
        }

        // Calcular badge total (notificaciones pendientes)
        $badge_total = $this->calcular_badge_total();
        $menu_titulo = __('Gestión', 'flavor-chat-ia');
        if ($badge_total > 0) {
            $menu_titulo .= sprintf(' <span class="awaiting-mod">%d</span>', $badge_total);
        }

        // Menú principal
        add_menu_page(
            __('Panel de Gestión', 'flavor-chat-ia'),
            $menu_titulo,
            'read', // Capability mínima, cada página verifica la suya
            self::MENU_SLUG,
            [$this, 'render_dashboard'],
            'dashicons-analytics',
            26 // Justo después de Comentarios
        );

        // Dashboard como primer submenú
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'read',
            self::MENU_SLUG,
            [$this, 'render_dashboard']
        );

        // Registrar páginas de cada módulo
        $categoria_actual = '';
        foreach ($this->modulos_registrados as $modulo_id => $modulo) {
            $categoria = $modulo['categoria'] ?? 'otros';

            // Añadir separador de categoría si cambia
            if ($categoria !== $categoria_actual && isset($this->categorias[$categoria])) {
                $this->agregar_separador_categoria($categoria);
                $categoria_actual = $categoria;
            }

            // Registrar páginas del módulo
            $this->registrar_paginas_modulo($modulo_id, $modulo);
        }
    }

    /**
     * Agrega separador visual de categoría
     */
    private function agregar_separador_categoria($categoria) {
        if (!isset($this->categorias[$categoria])) {
            return;
        }

        $cat_info = $this->categorias[$categoria];
        add_submenu_page(
            self::MENU_SLUG,
            '',
            sprintf(
                '<span class="flavor-gestion-separator" data-icon="%s">%s</span>',
                esc_attr($cat_info['icon']),
                esc_html($cat_info['label'])
            ),
            'read',
            'flavor-gestion-sep-' . $categoria,
            '__return_empty_string'
        );
    }

    /**
     * Registra las páginas de un módulo
     */
    private function registrar_paginas_modulo($modulo_id, $modulo) {
        if (empty($modulo['paginas'])) {
            return;
        }

        $capability = $modulo['capability'] ?? 'manage_options';

        foreach ($modulo['paginas'] as $pagina) {
            $titulo_menu = $pagina['titulo'];

            // Añadir badge si existe
            if (!empty($pagina['badge'])) {
                $badge_count = is_callable($pagina['badge']) ? call_user_func($pagina['badge']) : intval($pagina['badge']);
                if ($badge_count > 0) {
                    $titulo_menu .= sprintf(' <span class="awaiting-mod">%d</span>', $badge_count);
                }
            }

            // Añadir icono del módulo
            $icon = $modulo['icon'] ?? 'dashicons-admin-generic';
            $titulo_menu = sprintf(
                '<span class="dashicons %s" style="font-size:16px;width:16px;height:16px;margin-right:4px;vertical-align:middle;opacity:0.7;"></span>%s',
                esc_attr($icon),
                $titulo_menu
            );

            add_submenu_page(
                self::MENU_SLUG,
                $pagina['titulo'],
                $titulo_menu,
                $capability,
                $pagina['slug'],
                $pagina['callback']
            );
        }
    }

    /**
     * Calcula el badge total de todos los módulos
     */
    private function calcular_badge_total() {
        $total = 0;
        foreach ($this->modulos_registrados as $modulo) {
            if (empty($modulo['paginas'])) continue;
            foreach ($modulo['paginas'] as $pagina) {
                if (!empty($pagina['badge'])) {
                    $count = is_callable($pagina['badge']) ? call_user_func($pagina['badge']) : intval($pagina['badge']);
                    $total += $count;
                }
            }
        }
        return $total;
    }

    /**
     * Renderiza el dashboard unificado
     */
    public function render_dashboard() {
        try {
            flavor_log_debug( 'Iniciando render_dashboard', 'UnifiedAdmin' );
            $modulos = $this->modulos_registrados;
            flavor_log_debug( 'Módulos registrados: ' . count($modulos), 'UnifiedAdmin' );
        } catch (Exception $e) {
            flavor_log_error( 'Error al obtener módulos: ' . $e->getMessage(), 'UnifiedAdmin' );
            $modulos = [];
        }
        ?>
        <div class="wrap flavor-gestion-dashboard">
            <h1>
                <span class="dashicons dashicons-analytics"></span>
                <?php _e('Panel de Gestión', 'flavor-chat-ia'); ?>
            </h1>

            <?php if (empty($modulos)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No hay módulos de gestión activos. Activa módulos desde el Compositor de Apps.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>

                <!-- Resumen rápido -->
                <div class="flavor-gestion-resumen">
                    <?php
                    try {
                        flavor_log_debug( 'Llamando a render_widgets_resumen', 'UnifiedAdmin' );
                        $this->render_widgets_resumen();
                        flavor_log_debug( 'render_widgets_resumen completado', 'UnifiedAdmin' );
                    } catch (Exception $e) {
                        flavor_log_error( 'Error en render_widgets_resumen: ' . $e->getMessage(), 'UnifiedAdmin' );
                        echo '<div class="notice notice-error"><p>Error al cargar estadísticas: ' . esc_html($e->getMessage()) . '</p></div>';
                    }
                    ?>
                </div>

                <!-- Accesos rápidos por categoría -->
                <div class="flavor-gestion-grid">
                    <?php
                    $categorias_con_modulos = [];
                    foreach ($modulos as $modulo_id => $modulo) {
                        $cat = $modulo['categoria'] ?? 'otros';
                        if (!isset($categorias_con_modulos[$cat])) {
                            $categorias_con_modulos[$cat] = [];
                        }
                        $categorias_con_modulos[$cat][$modulo_id] = $modulo;
                    }

                    foreach ($categorias_con_modulos as $categoria => $modulos_cat):
                        $cat_info = $this->categorias[$categoria] ?? [
                            'label' => ucfirst($categoria),
                            'icon' => 'dashicons-admin-generic',
                        ];
                    ?>
                        <div class="flavor-gestion-categoria">
                            <h2>
                                <span class="dashicons <?php echo esc_attr($cat_info['icon']); ?>"></span>
                                <?php echo esc_html($cat_info['label']); ?>
                            </h2>
                            <div class="flavor-gestion-modulos">
                                <?php foreach ($modulos_cat as $modulo_id => $modulo): ?>
                                    <div class="flavor-gestion-modulo-card">
                                        <h3>
                                            <span class="dashicons <?php echo esc_attr($modulo['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                                            <?php echo esc_html($modulo['label']); ?>
                                        </h3>
                                        <?php if (!empty($modulo['paginas'])): ?>
                                            <ul class="flavor-gestion-enlaces">
                                                <?php foreach ($modulo['paginas'] as $pagina): ?>
                                                    <li>
                                                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . $pagina['slug'])); ?>">
                                                            <?php echo esc_html($pagina['titulo']); ?>
                                                            <?php
                                                            if (!empty($pagina['badge'])) {
                                                                $badge = is_callable($pagina['badge']) ? call_user_func($pagina['badge']) : intval($pagina['badge']);
                                                                if ($badge > 0) {
                                                                    echo '<span class="flavor-badge">' . esc_html($badge) . '</span>';
                                                                }
                                                            }
                                                            ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>

                                        <?php
                                        // Widget personalizado del módulo
                                        if (!empty($modulo['dashboard_widget']) && is_callable($modulo['dashboard_widget'])) {
                                            echo '<div class="flavor-gestion-widget">';
                                            call_user_func($modulo['dashboard_widget']);
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza widgets de resumen en el dashboard
     */
    private function render_widgets_resumen() {
        $stats = $this->obtener_estadisticas_globales();
        ?>
        <div class="flavor-gestion-stats-grid">
            <?php foreach ($stats as $stat): ?>
                <?php
                // Validar que $stat es un array y tiene las claves necesarias
                if (!is_array($stat) || !isset($stat['icon']) || !isset($stat['valor']) || !isset($stat['label'])) {
                    flavor_log_debug( 'Estadística inválida: ' . print_r($stat, true), 'UnifiedAdmin' );
                    continue;
                }
                ?>
                <div class="flavor-gestion-stat-card <?php echo esc_attr($stat['color'] ?? ''); ?>">
                    <div class="stat-icon">
                        <span class="dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($stat['valor']); ?></span>
                        <span class="stat-label"><?php echo esc_html($stat['label']); ?></span>
                    </div>
                    <?php if (!empty($stat['enlace'])): ?>
                        <a href="<?php echo esc_url($stat['enlace']); ?>" class="stat-link">
                            <?php _e('Ver', 'flavor-chat-ia'); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Obtiene estadísticas globales de todos los módulos
     */
    private function obtener_estadisticas_globales() {
        $stats = [];

        foreach ($this->modulos_registrados as $modulo_id => $modulo) {
            if (!empty($modulo['estadisticas']) && is_callable($modulo['estadisticas'])) {
                try {
                    $modulo_stats = call_user_func($modulo['estadisticas']);

                    // Validar que es un array
                    if (!is_array($modulo_stats)) {
                        flavor_log_debug( "Módulo {$modulo_id} devolvió estadísticas no-array: " . gettype($modulo_stats), 'UnifiedAdmin' );
                        continue;
                    }

                    // Validar cada estadística individual
                    foreach ($modulo_stats as $stat) {
                        if (is_array($stat) && isset($stat['icon']) && isset($stat['valor']) && isset($stat['label'])) {
                            $stats[] = $stat;
                        } else {
                            flavor_log_debug( "Módulo {$modulo_id} devolvió estadística inválida: " . print_r($stat, true), 'UnifiedAdmin' );
                        }
                    }
                } catch (Exception $e) {
                    // Log error pero continuar con otros módulos
                    flavor_log_error( "Error en estadísticas del módulo {$modulo_id}: " . $e->getMessage(), 'UnifiedAdmin' );
                } catch (Error $e) {
                    // Capturar errores fatales también
                    flavor_log_error( "Error fatal en estadísticas del módulo {$modulo_id}: " . $e->getMessage(), 'UnifiedAdmin' );
                }
            }
        }

        // Si no hay estadísticas de módulos, mostrar genéricas
        if (empty($stats)) {
            $stats = [
                [
                    'icon' => 'dashicons-admin-plugins',
                    'valor' => count($this->modulos_registrados),
                    'label' => __('Módulos activos', 'flavor-chat-ia'),
                    'color' => 'blue',
                ],
            ];
        }

        return $stats;
    }

    /**
     * Encola assets del panel
     */
    public function enqueue_assets($hook) {
        // Solo en páginas del panel de gestión
        if (strpos($hook, self::MENU_SLUG) === false && strpos($hook, 'flavor-gestion') === false) {
            // Verificar si es una página de módulo registrado
            $es_pagina_modulo = false;
            foreach ($this->modulos_registrados as $modulo) {
                if (empty($modulo['paginas'])) continue;
                foreach ($modulo['paginas'] as $pagina) {
                    if (strpos($hook, $pagina['slug']) !== false) {
                        $es_pagina_modulo = true;
                        break 2;
                    }
                }
            }
            if (!$es_pagina_modulo) {
                return;
            }
        }

        wp_enqueue_style(
            'flavor-gestion-panel',
            FLAVOR_CHAT_IA_URL . 'includes/admin/assets/css/unified-admin-panel.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-gestion-panel',
            FLAVOR_CHAT_IA_URL . 'includes/admin/assets/js/unified-admin-panel.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-gestion-panel', 'flavorGestionData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_gestion_nonce'),
            'i18n' => [
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al cargar datos', 'flavor-chat-ia'),
            ],
        ]);

        // CSS inline para separadores del menú
        wp_add_inline_style('flavor-gestion-panel', '
            .flavor-gestion-separator {
                display: block;
                padding: 8px 0 4px;
                margin: 5px 0 0;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #a0a5aa;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
            .flavor-gestion-separator::before {
                content: attr(data-icon);
                font-family: dashicons;
                margin-right: 4px;
                font-size: 12px;
            }
            #adminmenu .wp-submenu a[href*="flavor-gestion-sep-"] {
                pointer-events: none !important;
                cursor: default !important;
            }
        ');
    }

    /**
     * AJAX: Obtiene estadísticas actualizadas
     */
    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_gestion_nonce', 'nonce');

        $stats = $this->obtener_estadisticas_globales();

        wp_send_json_success(['estadisticas' => $stats]);
    }

    /**
     * Helper: Obtiene los módulos registrados
     */
    public function get_modulos_registrados() {
        return $this->modulos_registrados;
    }

    /**
     * Helper: Verifica si un módulo está registrado
     */
    public function modulo_registrado($modulo_id) {
        return isset($this->modulos_registrados[$modulo_id]);
    }
}
