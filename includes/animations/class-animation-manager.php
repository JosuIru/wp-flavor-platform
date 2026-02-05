<?php
/**
 * Animation Manager - Sistema de Animaciones CSS
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Animation_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Animaciones predefinidas
     */
    private $animations = [];

    /**
     * Obtener instancia
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
        $this->init_animations();
        $this->init_hooks();
    }

    /**
     * Inicializar animaciones
     */
    private function init_animations() {
        $this->animations = [
            // Fade
            'fadeIn' => [
                'name' => 'Aparecer',
                'category' => 'fade',
                'keyframes' => [
                    'from' => ['opacity' => '0'],
                    'to' => ['opacity' => '1'],
                ],
            ],
            'fadeInUp' => [
                'name' => 'Aparecer desde abajo',
                'category' => 'fade',
                'keyframes' => [
                    'from' => ['opacity' => '0', 'transform' => 'translateY(30px)'],
                    'to' => ['opacity' => '1', 'transform' => 'translateY(0)'],
                ],
            ],
            'fadeInDown' => [
                'name' => 'Aparecer desde arriba',
                'category' => 'fade',
                'keyframes' => [
                    'from' => ['opacity' => '0', 'transform' => 'translateY(-30px)'],
                    'to' => ['opacity' => '1', 'transform' => 'translateY(0)'],
                ],
            ],
            'fadeInLeft' => [
                'name' => 'Aparecer desde izquierda',
                'category' => 'fade',
                'keyframes' => [
                    'from' => ['opacity' => '0', 'transform' => 'translateX(-30px)'],
                    'to' => ['opacity' => '1', 'transform' => 'translateX(0)'],
                ],
            ],
            'fadeInRight' => [
                'name' => 'Aparecer desde derecha',
                'category' => 'fade',
                'keyframes' => [
                    'from' => ['opacity' => '0', 'transform' => 'translateX(30px)'],
                    'to' => ['opacity' => '1', 'transform' => 'translateX(0)'],
                ],
            ],

            // Zoom
            'zoomIn' => [
                'name' => 'Zoom entrada',
                'category' => 'zoom',
                'keyframes' => [
                    'from' => ['opacity' => '0', 'transform' => 'scale(0.5)'],
                    'to' => ['opacity' => '1', 'transform' => 'scale(1)'],
                ],
            ],
            'zoomOut' => [
                'name' => 'Zoom salida',
                'category' => 'zoom',
                'keyframes' => [
                    'from' => ['opacity' => '1', 'transform' => 'scale(1)'],
                    'to' => ['opacity' => '0', 'transform' => 'scale(0.5)'],
                ],
            ],

            // Slide
            'slideInUp' => [
                'name' => 'Deslizar desde abajo',
                'category' => 'slide',
                'keyframes' => [
                    'from' => ['transform' => 'translateY(100%)', 'visibility' => 'visible'],
                    'to' => ['transform' => 'translateY(0)'],
                ],
            ],
            'slideInDown' => [
                'name' => 'Deslizar desde arriba',
                'category' => 'slide',
                'keyframes' => [
                    'from' => ['transform' => 'translateY(-100%)', 'visibility' => 'visible'],
                    'to' => ['transform' => 'translateY(0)'],
                ],
            ],
            'slideInLeft' => [
                'name' => 'Deslizar desde izquierda',
                'category' => 'slide',
                'keyframes' => [
                    'from' => ['transform' => 'translateX(-100%)', 'visibility' => 'visible'],
                    'to' => ['transform' => 'translateX(0)'],
                ],
            ],
            'slideInRight' => [
                'name' => 'Deslizar desde derecha',
                'category' => 'slide',
                'keyframes' => [
                    'from' => ['transform' => 'translateX(100%)', 'visibility' => 'visible'],
                    'to' => ['transform' => 'translateX(0)'],
                ],
            ],

            // Bounce
            'bounce' => [
                'name' => 'Rebote',
                'category' => 'attention',
                'keyframes' => [
                    '0%, 20%, 50%, 80%, 100%' => ['transform' => 'translateY(0)'],
                    '40%' => ['transform' => 'translateY(-20px)'],
                    '60%' => ['transform' => 'translateY(-10px)'],
                ],
            ],
            'bounceIn' => [
                'name' => 'Entrada con rebote',
                'category' => 'bounce',
                'keyframes' => [
                    '0%' => ['opacity' => '0', 'transform' => 'scale(0.3)'],
                    '50%' => ['transform' => 'scale(1.05)'],
                    '70%' => ['transform' => 'scale(0.9)'],
                    '100%' => ['opacity' => '1', 'transform' => 'scale(1)'],
                ],
            ],

            // Attention
            'pulse' => [
                'name' => 'Pulso',
                'category' => 'attention',
                'keyframes' => [
                    '0%' => ['transform' => 'scale(1)'],
                    '50%' => ['transform' => 'scale(1.05)'],
                    '100%' => ['transform' => 'scale(1)'],
                ],
            ],
            'shake' => [
                'name' => 'Sacudir',
                'category' => 'attention',
                'keyframes' => [
                    '0%, 100%' => ['transform' => 'translateX(0)'],
                    '10%, 30%, 50%, 70%, 90%' => ['transform' => 'translateX(-10px)'],
                    '20%, 40%, 60%, 80%' => ['transform' => 'translateX(10px)'],
                ],
            ],
            'swing' => [
                'name' => 'Balanceo',
                'category' => 'attention',
                'keyframes' => [
                    '20%' => ['transform' => 'rotate(15deg)'],
                    '40%' => ['transform' => 'rotate(-10deg)'],
                    '60%' => ['transform' => 'rotate(5deg)'],
                    '80%' => ['transform' => 'rotate(-5deg)'],
                    '100%' => ['transform' => 'rotate(0)'],
                ],
            ],
            'heartbeat' => [
                'name' => 'Latido',
                'category' => 'attention',
                'keyframes' => [
                    '0%' => ['transform' => 'scale(1)'],
                    '14%' => ['transform' => 'scale(1.3)'],
                    '28%' => ['transform' => 'scale(1)'],
                    '42%' => ['transform' => 'scale(1.3)'],
                    '70%' => ['transform' => 'scale(1)'],
                ],
            ],

            // Rotate
            'rotateIn' => [
                'name' => 'Rotación entrada',
                'category' => 'rotate',
                'keyframes' => [
                    'from' => ['transform' => 'rotate(-200deg)', 'opacity' => '0'],
                    'to' => ['transform' => 'rotate(0)', 'opacity' => '1'],
                ],
            ],
            'flip' => [
                'name' => 'Voltear',
                'category' => 'rotate',
                'keyframes' => [
                    '0%' => ['transform' => 'perspective(400px) rotateY(0)'],
                    '100%' => ['transform' => 'perspective(400px) rotateY(360deg)'],
                ],
            ],

            // Special
            'rubberBand' => [
                'name' => 'Banda elástica',
                'category' => 'special',
                'keyframes' => [
                    '0%' => ['transform' => 'scaleX(1)'],
                    '30%' => ['transform' => 'scaleX(1.25) scaleY(0.75)'],
                    '40%' => ['transform' => 'scaleX(0.75) scaleY(1.25)'],
                    '50%' => ['transform' => 'scaleX(1.15) scaleY(0.85)'],
                    '65%' => ['transform' => 'scaleX(0.95) scaleY(1.05)'],
                    '75%' => ['transform' => 'scaleX(1.05) scaleY(0.95)'],
                    '100%' => ['transform' => 'scaleX(1) scaleY(1)'],
                ],
            ],
            'jello' => [
                'name' => 'Gelatina',
                'category' => 'special',
                'keyframes' => [
                    '0%, 100%' => ['transform' => 'skewX(0) skewY(0)'],
                    '11.1%' => ['transform' => 'skewX(-12.5deg) skewY(-12.5deg)'],
                    '22.2%' => ['transform' => 'skewX(6.25deg) skewY(6.25deg)'],
                    '33.3%' => ['transform' => 'skewX(-3.125deg) skewY(-3.125deg)'],
                    '44.4%' => ['transform' => 'skewX(1.5625deg) skewY(1.5625deg)'],
                    '55.5%' => ['transform' => 'skewX(-0.78125deg) skewY(-0.78125deg)'],
                    '66.6%' => ['transform' => 'skewX(0.390625deg) skewY(0.390625deg)'],
                    '77.7%' => ['transform' => 'skewX(-0.1953125deg) skewY(-0.1953125deg)'],
                ],
            ],
        ];

        // Permitir extensión
        $this->animations = apply_filters('flavor_animations', $this->animations);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_flavor_get_animations', [$this, 'ajax_get_animations']);
        add_action('wp_ajax_nopriv_flavor_get_animations', [$this, 'ajax_get_animations']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_animation_styles']);
        add_action('wp_footer', [$this, 'output_animation_observer']);
    }

    /**
     * Obtener animaciones via AJAX
     */
    public function ajax_get_animations() {
        $grouped = [];

        foreach ($this->animations as $animation_id => $animation_data) {
            $category = $animation_data['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$animation_id] = [
                'name' => $animation_data['name'],
                'category' => $category,
            ];
        }

        wp_send_json_success([
            'animations' => $this->animations,
            'grouped' => $grouped,
            'categories' => [
                'fade' => 'Fade',
                'zoom' => 'Zoom',
                'slide' => 'Slide',
                'bounce' => 'Bounce',
                'attention' => 'Atención',
                'rotate' => 'Rotación',
                'special' => 'Especiales',
            ],
        ]);
    }

    /**
     * Encolar estilos de animación
     */
    public function enqueue_animation_styles() {
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-animations',
            FLAVOR_CHAT_IA_URL . "assets/css/animations{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Generar CSS de animaciones
     *
     * @return string
     */
    public function generate_css() {
        $css = "/* Flavor Animations */\n\n";

        foreach ($this->animations as $animation_id => $animation_data) {
            $css .= $this->generate_keyframes($animation_id, $animation_data['keyframes']);
            $css .= $this->generate_animation_class($animation_id);
        }

        // Clases de utilidad
        $css .= $this->generate_utility_classes();

        return $css;
    }

    /**
     * Generar keyframes CSS
     */
    private function generate_keyframes($name, $keyframes) {
        $css = "@keyframes flavor-{$name} {\n";

        foreach ($keyframes as $step => $properties) {
            $css .= "  {$step} {\n";
            foreach ($properties as $prop => $value) {
                $css .= "    {$prop}: {$value};\n";
            }
            $css .= "  }\n";
        }

        $css .= "}\n\n";

        return $css;
    }

    /**
     * Generar clase de animación
     */
    private function generate_animation_class($name) {
        return ".flavor-animate-{$name} {
  animation-name: flavor-{$name};
  animation-duration: var(--flavor-animation-duration, 0.6s);
  animation-timing-function: var(--flavor-animation-timing, ease);
  animation-fill-mode: both;
}

";
    }

    /**
     * Generar clases de utilidad
     */
    private function generate_utility_classes() {
        return "
/* Duraciones */
.flavor-duration-fast { --flavor-animation-duration: 0.3s; }
.flavor-duration-normal { --flavor-animation-duration: 0.6s; }
.flavor-duration-slow { --flavor-animation-duration: 1s; }
.flavor-duration-slower { --flavor-animation-duration: 1.5s; }

/* Delays */
.flavor-delay-100 { animation-delay: 0.1s; }
.flavor-delay-200 { animation-delay: 0.2s; }
.flavor-delay-300 { animation-delay: 0.3s; }
.flavor-delay-400 { animation-delay: 0.4s; }
.flavor-delay-500 { animation-delay: 0.5s; }
.flavor-delay-1000 { animation-delay: 1s; }

/* Timing functions */
.flavor-ease-linear { --flavor-animation-timing: linear; }
.flavor-ease-in { --flavor-animation-timing: ease-in; }
.flavor-ease-out { --flavor-animation-timing: ease-out; }
.flavor-ease-in-out { --flavor-animation-timing: ease-in-out; }
.flavor-ease-bounce { --flavor-animation-timing: cubic-bezier(0.68, -0.55, 0.265, 1.55); }

/* Repetición */
.flavor-repeat-1 { animation-iteration-count: 1; }
.flavor-repeat-2 { animation-iteration-count: 2; }
.flavor-repeat-3 { animation-iteration-count: 3; }
.flavor-repeat-infinite { animation-iteration-count: infinite; }

/* Estado inicial oculto para animaciones de entrada */
[data-flavor-animation] {
  opacity: 0;
}

[data-flavor-animation].flavor-animated {
  opacity: 1;
}

/* Reducir movimiento para accesibilidad */
@media (prefers-reduced-motion: reduce) {
  [data-flavor-animation],
  .flavor-animate-fadeIn,
  .flavor-animate-fadeInUp,
  .flavor-animate-fadeInDown,
  .flavor-animate-fadeInLeft,
  .flavor-animate-fadeInRight,
  .flavor-animate-zoomIn,
  .flavor-animate-slideInUp,
  .flavor-animate-slideInDown,
  .flavor-animate-slideInLeft,
  .flavor-animate-slideInRight,
  .flavor-animate-bounceIn,
  .flavor-animate-rotateIn {
    animation: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
";
    }

    /**
     * Output del observer de animaciones
     */
    public function output_animation_observer() {
        ?>
        <script>
        (function() {
            if (typeof IntersectionObserver === 'undefined') return;

            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const animation = el.dataset.flavorAnimation;
                        const delay = el.dataset.flavorDelay || 0;
                        const duration = el.dataset.flavorDuration || null;

                        setTimeout(() => {
                            if (duration) {
                                el.style.setProperty('--flavor-animation-duration', duration);
                            }
                            el.classList.add('flavor-animate-' + animation, 'flavor-animated');
                            animationObserver.unobserve(el);
                        }, parseInt(delay));
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('[data-flavor-animation]').forEach(el => {
                animationObserver.observe(el);
            });

            // Exponer para uso dinámico
            window.flavorAnimationObserver = animationObserver;
        })();
        </script>
        <?php
    }

    /**
     * Obtener animaciones
     */
    public function get_animations() {
        return $this->animations;
    }

    /**
     * Obtener atributos de animación para un elemento
     *
     * @param string $animation
     * @param array $options
     * @return string
     */
    public function get_animation_attributes($animation, $options = []) {
        $attrs = ['data-flavor-animation="' . esc_attr($animation) . '"'];

        if (!empty($options['delay'])) {
            $attrs[] = 'data-flavor-delay="' . intval($options['delay']) . '"';
        }

        if (!empty($options['duration'])) {
            $attrs[] = 'data-flavor-duration="' . esc_attr($options['duration']) . '"';
        }

        return implode(' ', $attrs);
    }
}

/**
 * Helper global
 */
function flavor_animation($animation, $options = []) {
    return Flavor_Animation_Manager::get_instance()->get_animation_attributes($animation, $options);
}
