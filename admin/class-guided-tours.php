<?php
/**
 * Sistema de Tours Guiados Interactivos
 *
 * Tours paso a paso para ayudar a usuarios nuevos a familiarizarse
 * con las funcionalidades de Flavor Platform
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para tours guiados
 *
 * @since 3.0.0
 */
class Flavor_Guided_Tours {

    /**
     * Instancia singleton
     *
     * @var Flavor_Guided_Tours
     */
    private static $instancia = null;

    /**
     * Tours disponibles
     *
     * @var array
     */
    private $tours = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Guided_Tours
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_tours();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Cargar assets solo en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_tour_assets']);

        // AJAX para marcar tour como completado
        add_action('wp_ajax_flavor_complete_tour', [$this, 'ajax_complete_tour']);

        // AJAX para reiniciar tour
        add_action('wp_ajax_flavor_reset_tour', [$this, 'ajax_reset_tour']);

        // Mostrar tours disponibles en cada página
        add_action('admin_footer', [$this, 'render_tour_launcher']);
    }

    /**
     * Registra todos los tours disponibles
     *
     * @return void
     */
    private function registrar_tours() {
        // Tour de bienvenida general
        $this->tours['welcome'] = [
            'titulo' => __('Bienvenida a Flavor Platform', 'flavor-chat-ia'),
            'descripcion' => __('Descubre las funcionalidades principales', 'flavor-chat-ia'),
            'paginas' => ['flavor-dashboard', 'toplevel_page_flavor-dashboard'],
            'pasos' => [
                [
                    'elemento' => '.flavor-welcome-panel',
                    'titulo' => __('¡Bienvenido!', 'flavor-chat-ia'),
                    'contenido' => __('Esta es tu plataforma modular lista para usar. Desde aquí puedes gestionar todo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-stat-grid',
                    'titulo' => __('Estadísticas en tiempo real', 'flavor-chat-ia'),
                    'contenido' => __('Ve aquí las estadísticas más importantes de tu plataforma: addons activos, módulos, conversaciones y mensajes.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-quick-actions',
                    'titulo' => __('Acciones rápidas', 'flavor-chat-ia'),
                    'contenido' => __('Accede rápidamente a las secciones más utilizadas desde estos botones.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '#menu-posts-flavor_landing',
                    'titulo' => __('Landing Pages', 'flavor-chat-ia'),
                    'contenido' => __('Crea landing pages personalizadas para tus aplicaciones móviles.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
            ],
        ];

        // Tour de gestión de addons
        $this->tours['addons'] = [
            'titulo' => __('Gestión de Addons', 'flavor-chat-ia'),
            'descripcion' => __('Aprende a activar y configurar addons', 'flavor-chat-ia'),
            'paginas' => ['flavor-platform_page_flavor-addons'],
            'pasos' => [
                [
                    'elemento' => '.flavor-addon-card:first',
                    'titulo' => __('Tarjetas de Addon', 'flavor-chat-ia'),
                    'contenido' => __('Cada addon tiene su propia tarjeta con información sobre su estado, versión y requisitos.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-addon-badge',
                    'titulo' => __('Estado del Addon', 'flavor-chat-ia'),
                    'contenido' => __('Este badge muestra si el addon está activo o inactivo.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-addon-actions button:first',
                    'titulo' => __('Activar/Desactivar', 'flavor-chat-ia'),
                    'contenido' => __('Usa este botón para activar o desactivar el addon. Los requisitos se verifican automáticamente.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour de módulos
        $this->tours['modules'] = [
            'titulo' => __('Módulos del Chat', 'flavor-chat-ia'),
            'descripcion' => __('Entiende cómo funcionan los módulos', 'flavor-chat-ia'),
            'paginas' => ['flavor-platform_page_flavor-modules'],
            'pasos' => [
                [
                    'elemento' => '.flavor-module-card:first',
                    'titulo' => __('Módulos Especializados', 'flavor-chat-ia'),
                    'contenido' => __('Cada módulo añade funcionalidades específicas al chat IA.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-module-dependencies',
                    'titulo' => __('Dependencias', 'flavor-chat-ia'),
                    'contenido' => __('Algunos módulos requieren otros módulos o addons para funcionar.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour de configuración de IA
        $this->tours['ai-setup'] = [
            'titulo' => __('Configurar Motor de IA', 'flavor-chat-ia'),
            'descripcion' => __('Conecta tu motor de IA preferido', 'flavor-chat-ia'),
            'paginas' => ['flavor-platform_page_flavor-chat-ia'],
            'pasos' => [
                [
                    'elemento' => 'select[name="flavor_chat_settings[engine]"]',
                    'titulo' => __('Selecciona el Motor', 'flavor-chat-ia'),
                    'contenido' => __('Elige entre Claude, OpenAI, DeepSeek o Mistral como tu motor de IA.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => 'input[name="flavor_chat_settings[api_key]"]',
                    'titulo' => __('API Key', 'flavor-chat-ia'),
                    'contenido' => __('Ingresa tu API key. Esta se guarda de forma segura y encriptada.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => 'select[name="flavor_chat_settings[model]"]',
                    'titulo' => __('Modelo de IA', 'flavor-chat-ia'),
                    'contenido' => __('Selecciona qué versión del modelo usar. Los modelos más recientes suelen dar mejores respuestas.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Permitir que addons registren sus propios tours
        $this->tours = apply_filters('flavor_guided_tours', $this->tours);
    }

    /**
     * Carga assets del sistema de tours
     *
     * @param string $hook_suffix Sufijo del hook
     * @return void
     */
    public function enqueue_tour_assets($hook_suffix) {
        // Shepherd.js - biblioteca para tours guiados
        wp_enqueue_style(
            'shepherdjs',
            'https://cdn.jsdelivr.net/npm/shepherd.js@11.1.1/dist/css/shepherd.css',
            [],
            '11.1.1'
        );

        wp_enqueue_script(
            'shepherdjs',
            'https://cdn.jsdelivr.net/npm/shepherd.js@11.1.1/dist/js/shepherd.min.js',
            [],
            '11.1.1',
            true
        );

        // Script personalizado para tours
        $js_tours = "
            (function($) {
                'use strict';

                window.FlavorTours = {
                    tours: " . json_encode($this->tours) . ",
                    currentTour: null,
                    completedTours: " . json_encode($this->get_completed_tours()) . ",

                    init: function() {
                        this.bindEvents();
                        this.checkAutoStart();
                    },

                    bindEvents: function() {
                        $(document).on('click', '.flavor-tour-start', function(e) {
                            e.preventDefault();
                            var tourId = $(this).data('tour-id');
                            FlavorTours.startTour(tourId);
                        });

                        $(document).on('click', '.flavor-tour-reset', function(e) {
                            e.preventDefault();
                            var tourId = $(this).data('tour-id');
                            FlavorTours.resetTour(tourId);
                        });
                    },

                    checkAutoStart: function() {
                        var currentPage = '" . $hook_suffix . "';
                        var self = this;

                        $.each(this.tours, function(tourId, tour) {
                            if (tour.paginas.indexOf(currentPage) !== -1 &&
                                self.completedTours.indexOf(tourId) === -1 &&
                                !localStorage.getItem('flavor_tour_dismissed_' + tourId)) {
                                self.showTourPromo(tourId, tour);
                            }
                        });
                    },

                    showTourPromo: function(tourId, tour) {
                        // Verificar que al menos un elemento del tour existe y es visible
                        var hasValidElements = false;
                        $.each(tour.pasos || [], function(i, paso) {
                            try {
                                var el = $(paso.elemento);
                                if (el.length > 0 && el.is(':visible') && el.get(0) &&
                                    typeof el.get(0).getBoundingClientRect === 'function') {
                                    var rect = el.get(0).getBoundingClientRect();
                                    if (rect.width > 0 || rect.height > 0) {
                                        hasValidElements = true;
                                        return false; // break
                                    }
                                }
                            } catch (e) {}
                        });
                        if (!hasValidElements) return;

                        var html = '<div class=\"notice notice-info is-dismissible flavor-tour-promo\">';
                        html += '<p><strong>' + tour.titulo + ':</strong> ' + tour.descripcion + '</p>';
                        html += '<p><button class=\"button button-primary flavor-tour-start\" data-tour-id=\"' + tourId + '\">';
                        html += '" . esc_js(__('Iniciar Tour', 'flavor-chat-ia')) . "</button> ';
                        html += '<button class=\"button flavor-tour-dismiss\" data-tour-id=\"' + tourId + '\">';
                        html += '" . esc_js(__('No mostrar de nuevo', 'flavor-chat-ia')) . "</button></p>';
                        html += '</div>';

                        $('.wrap h1').first().after(html);

                        $(document).on('click', '.flavor-tour-dismiss', function(e) {
                            e.preventDefault();
                            var id = $(this).data('tour-id');
                            localStorage.setItem('flavor_tour_dismissed_' + id, '1');
                            $(this).closest('.flavor-tour-promo').remove();
                        });
                    },

                    startTour: function(tourId) {
                        var tourConfig = this.tours[tourId];
                        if (!tourConfig) return;

                        var steps = [];
                        var validSteps = [];

                        // Filtrar solo los pasos cuyos elementos existen en el DOM y son visibles
                        $.each(tourConfig.pasos, function(index, paso) {
                            try {
                                var elemento = $(paso.elemento);
                                // Verificar que existe, es visible y tiene getBoundingClientRect
                                if (elemento.length > 0 &&
                                    elemento.is(':visible') &&
                                    elemento.get(0) &&
                                    typeof elemento.get(0).getBoundingClientRect === 'function') {
                                    var rect = elemento.get(0).getBoundingClientRect();
                                    // Verificar que tiene dimensiones
                                    if (rect.width > 0 || rect.height > 0) {
                                        validSteps.push(paso);
                                    }
                                }
                            } catch (e) {
                                console.log('FlavorTours: Elemento no válido', paso.elemento, e);
                            }
                        });

                        // Si no hay pasos válidos, no iniciar el tour
                        if (validSteps.length === 0) {
                            console.log('FlavorTours: No hay elementos visibles para el tour ' + tourId);
                            return;
                        }

                        $.each(validSteps, function(index, paso) {
                            // Obtener el elemento DOM real, no solo el selector
                            var elementoDOM = $(paso.elemento).get(0);

                            var step = {
                                title: paso.titulo,
                                text: paso.contenido,
                                buttons: []
                            };

                            // Solo adjuntar si el elemento existe realmente
                            if (elementoDOM) {
                                step.attachTo = {
                                    element: elementoDOM,
                                    on: paso.posicion || 'bottom'
                                };
                            }

                            if (index > 0) {
                                step.buttons.push({
                                    text: '" . esc_js(__('Anterior', 'flavor-chat-ia')) . "',
                                    action: function() {
                                        this.back();
                                    }
                                });
                            }

                            if (index < validSteps.length - 1) {
                                step.buttons.push({
                                    text: '" . esc_js(__('Siguiente', 'flavor-chat-ia')) . "',
                                    action: function() {
                                        this.next();
                                    }
                                });
                            } else {
                                step.buttons.push({
                                    text: '" . esc_js(__('Finalizar', 'flavor-chat-ia')) . "',
                                    action: function() {
                                        FlavorTours.completeTour(tourId);
                                        this.complete();
                                    }
                                });
                            }

                            steps.push(step);
                        });

                        try {
                            this.currentTour = new Shepherd.Tour({
                                useModalOverlay: true,
                                defaultStepOptions: {
                                    cancelIcon: {
                                        enabled: true
                                    },
                                    classes: 'flavor-tour-step',
                                    scrollTo: { behavior: 'smooth', block: 'center' },
                                    // Si el elemento no existe, mostrar como modal centrado
                                    when: {
                                        show: function() {
                                            // Silenciar errores de posicionamiento
                                        }
                                    }
                                },
                                exitOnEsc: true,
                                keyboardNavigation: true
                            });

                            steps.forEach(step => this.currentTour.addStep(step));

                            // Capturar errores durante el tour
                            this.currentTour.on('error', function(e) {
                                console.warn('FlavorTours: Error en tour', e);
                            });

                            this.currentTour.start();
                        } catch (e) {
                            console.warn('FlavorTours: No se pudo iniciar el tour', e);
                        }
                    },

                    completeTour: function(tourId) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_complete_tour',
                                nonce: '" . wp_create_nonce('flavor_tour_nonce') . "',
                                tour_id: tourId
                            },
                            success: function() {
                                FlavorTours.completedTours.push(tourId);
                                $('.flavor-tour-promo').remove();
                            }
                        });
                    },

                    resetTour: function(tourId) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flavor_reset_tour',
                                nonce: '" . wp_create_nonce('flavor_tour_nonce') . "',
                                tour_id: tourId
                            },
                            success: function() {
                                var index = FlavorTours.completedTours.indexOf(tourId);
                                if (index > -1) {
                                    FlavorTours.completedTours.splice(index, 1);
                                }
                                localStorage.removeItem('flavor_tour_dismissed_' + tourId);
                                location.reload();
                            }
                        });
                    }
                };

                $(document).ready(function() {
                    if (typeof Shepherd !== 'undefined') {
                        FlavorTours.init();
                    }
                });

            })(jQuery);
        ";

        wp_add_inline_script('shepherdjs', $js_tours);

        // Estilos personalizados
        $css_tours = "
            .flavor-tour-step {
                max-width: 400px;
            }
            .flavor-tour-step .shepherd-content {
                padding: 20px;
            }
            .flavor-tour-step .shepherd-header {
                padding-bottom: 15px;
                border-bottom: 1px solid #e5e5e5;
                margin-bottom: 15px;
            }
            .flavor-tour-step .shepherd-title {
                font-size: 18px;
                font-weight: 600;
            }
            .flavor-tour-step .shepherd-text {
                font-size: 14px;
                line-height: 1.6;
            }
            .flavor-tour-step .shepherd-footer {
                padding-top: 15px;
                border-top: 1px solid #e5e5e5;
                margin-top: 15px;
                text-align: right;
            }
            .flavor-tour-step button {
                margin-left: 10px;
            }
            .flavor-tour-launcher {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
            }
            .flavor-tour-launcher-btn {
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                transition: all 0.3s;
            }
            .flavor-tour-launcher-btn:hover {
                background: #135e96;
                transform: scale(1.1);
            }
            .flavor-tour-menu {
                display: none;
                position: absolute;
                bottom: 60px;
                right: 0;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                min-width: 250px;
                max-height: 400px;
                overflow-y: auto;
            }
            .flavor-tour-menu.active {
                display: block;
            }
            .flavor-tour-menu-item {
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f1;
                cursor: pointer;
                transition: background 0.2s;
            }
            .flavor-tour-menu-item:hover {
                background: #f6f7f7;
            }
            .flavor-tour-menu-item:last-child {
                border-bottom: none;
            }
            .flavor-tour-menu-item h4 {
                margin: 0 0 5px 0;
                font-size: 14px;
            }
            .flavor-tour-menu-item p {
                margin: 0;
                font-size: 12px;
                color: #646970;
            }
            .flavor-tour-menu-item.completed {
                opacity: 0.6;
            }
            .flavor-tour-menu-item .dashicons-yes {
                color: #00a32a;
                float: right;
            }
        ";

        wp_add_inline_style('shepherdjs', $css_tours);
    }

    /**
     * Renderiza el lanzador de tours
     *
     * @return void
     */
    public function render_tour_launcher() {
        $screen = get_current_screen();
        $tours_disponibles = [];

        foreach ($this->tours as $tour_id => $tour) {
            if (!empty($tour['paginas']) && in_array($screen->id, $tour['paginas'])) {
                $tours_disponibles[$tour_id] = $tour;
            }
        }

        if (empty($tours_disponibles)) {
            return;
        }

        $completados = $this->get_completed_tours();

        ?>
        <div class="flavor-tour-launcher">
            <button class="flavor-tour-launcher-btn" title="<?php esc_attr_e('Tours Guiados', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-info"></span>
            </button>
            <div class="flavor-tour-menu">
                <?php foreach ($tours_disponibles as $tour_id => $tour): ?>
                    <div class="flavor-tour-menu-item <?php echo in_array($tour_id, $completados) ? 'completed' : ''; ?>"
                         data-tour-id="<?php echo esc_attr($tour_id); ?>">
                        <?php if (in_array($tour_id, $completados)): ?>
                            <span class="dashicons dashicons-yes"></span>
                        <?php endif; ?>
                        <h4><?php echo esc_html($tour['titulo']); ?></h4>
                        <p><?php echo esc_html($tour['descripcion']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.flavor-tour-launcher-btn').on('click', function(e) {
                e.stopPropagation();
                $('.flavor-tour-menu').toggleClass('active');
            });

            $('.flavor-tour-menu-item').on('click', function() {
                var tourId = $(this).data('tour-id');
                $('.flavor-tour-menu').removeClass('active');
                FlavorTours.startTour(tourId);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-tour-launcher').length) {
                    $('.flavor-tour-menu').removeClass('active');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Marca un tour como completado
     *
     * @return void
     */
    public function ajax_complete_tour() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $tour_id = sanitize_text_field($_POST['tour_id']);
        $completados = $this->get_completed_tours();

        if (!in_array($tour_id, $completados)) {
            $completados[] = $tour_id;
            update_user_meta(get_current_user_id(), 'flavor_completed_tours', $completados);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Reinicia un tour
     *
     * @return void
     */
    public function ajax_reset_tour() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos');
        }

        $tour_id = sanitize_text_field($_POST['tour_id']);
        $completados = $this->get_completed_tours();

        $key = array_search($tour_id, $completados);
        if ($key !== false) {
            unset($completados[$key]);
            update_user_meta(get_current_user_id(), 'flavor_completed_tours', array_values($completados));
        }

        wp_send_json_success();
    }

    /**
     * Obtiene tours completados del usuario actual
     *
     * @return array
     */
    private function get_completed_tours() {
        $completados = get_user_meta(get_current_user_id(), 'flavor_completed_tours', true);
        return is_array($completados) ? $completados : [];
    }
}
