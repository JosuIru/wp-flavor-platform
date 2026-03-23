<?php
/**
 * Definición de Planes de Licencia
 *
 * Define los planes disponibles y qué módulos incluye cada uno
 *
 * @package FlavorChatIA
 * @subpackage Licensing
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que define los planes de licencia y sus módulos
 *
 * @since 3.2.0
 */
class Flavor_License_Plans {

    /**
     * Instancia singleton
     *
     * @var Flavor_License_Plans
     */
    private static $instance = null;

    /**
     * Definición de planes
     *
     * @var array
     */
    private $plans = [];

    /**
     * Módulos base incluidos en todos los planes
     *
     * @var array
     */
    private $base_modules = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_License_Plans
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
        $this->define_base_modules();
        $this->define_plans();
    }

    /**
     * Define módulos base incluidos en todos los planes
     *
     * @return void
     */
    private function define_base_modules() {
        $this->base_modules = [
            // Módulos de comunicación básica
            'chat_interno',
            'foros',

            // Módulos de gestión básica
            'incidencias',
            'eventos',

            // Módulos informativos
            'avisos_municipales',
        ];
    }

    /**
     * Define los planes de licencia disponibles
     *
     * @return void
     */
    private function define_plans() {
        $this->plans = [
            // Plan gratuito / sin licencia
            'free' => [
                'name'        => __('Plan Gratuito', 'flavor-chat-ia'),
                'slug'        => 'free',
                'price'       => 0,
                'period'      => 'unlimited',
                'sites'       => 1,
                'description' => __('Funcionalidades básicas para empezar', 'flavor-chat-ia'),
                'features'    => [
                    __('5 módulos básicos', 'flavor-chat-ia'),
                    __('Chat interno', 'flavor-chat-ia'),
                    __('Foros de comunidad', 'flavor-chat-ia'),
                    __('Gestión de eventos', 'flavor-chat-ia'),
                    __('Soporte por documentación', 'flavor-chat-ia'),
                ],
                'modules'     => [], // Solo módulos base
                'color'       => '#64748b',
            ],

            // Plan Starter
            'starter' => [
                'name'        => __('Plan Starter', 'flavor-chat-ia'),
                'slug'        => 'starter',
                'price'       => 79,
                'period'      => 'year',
                'sites'       => 1,
                'description' => __('Perfecto para comunidades pequeñas', 'flavor-chat-ia'),
                'features'    => [
                    __('20+ módulos', 'flavor-chat-ia'),
                    __('1 sitio web', 'flavor-chat-ia'),
                    __('Soporte por email', 'flavor-chat-ia'),
                    __('Actualizaciones durante 1 año', 'flavor-chat-ia'),
                ],
                'modules'     => [
                    // Comunicación
                    'chat_grupos',
                    'red_social',
                    'multimedia',

                    // Organización
                    'reservas',
                    'espacios_comunes',
                    'tramites',

                    // Comunidad
                    'socios',
                    'encuestas',
                    'participacion',

                    // Economía básica
                    'banco_tiempo',
                    'marketplace',

                    // Formación
                    'talleres',
                    'biblioteca',

                    // Transparencia
                    'transparencia',
                    'documentacion_legal',
                ],
                'color'       => '#3b82f6',
            ],

            // Plan Professional
            'professional' => [
                'name'        => __('Plan Professional', 'flavor-chat-ia'),
                'slug'        => 'professional',
                'price'       => 149,
                'period'      => 'year',
                'sites'       => 5,
                'description' => __('Para organizaciones en crecimiento', 'flavor-chat-ia'),
                'features'    => [
                    __('40+ módulos', 'flavor-chat-ia'),
                    __('5 sitios web', 'flavor-chat-ia'),
                    __('Soporte prioritario', 'flavor-chat-ia'),
                    __('Actualizaciones durante 1 año', 'flavor-chat-ia'),
                    __('Acceso a addons básicos', 'flavor-chat-ia'),
                ],
                'modules'     => [
                    // Todos los de Starter +
                    'chat_grupos',
                    'chat_estados',
                    'red_social',
                    'multimedia',
                    'reservas',
                    'espacios_comunes',
                    'tramites',
                    'socios',
                    'encuestas',
                    'participacion',
                    'banco_tiempo',
                    'marketplace',
                    'talleres',
                    'biblioteca',
                    'transparencia',
                    'documentacion_legal',

                    // Módulos adicionales Professional
                    'grupos_consumo',
                    'cursos',
                    'campanias',
                    'crowdfunding',
                    'colectivos',
                    'comunidades',
                    'huertos_urbanos',
                    'carpooling',
                    'bicicletas_compartidas',
                    'parkings',
                    'podcast',
                    'radio',
                    'kulturaka',
                    'ayuda_vecinal',
                    'circulos_cuidados',
                    'mapa_actores',
                    'seguimiento_denuncias',
                    'recetas',
                ],
                'color'       => '#8b5cf6',
            ],

            // Plan Agency
            'agency' => [
                'name'        => __('Plan Agency', 'flavor-chat-ia'),
                'slug'        => 'agency',
                'price'       => 299,
                'period'      => 'year',
                'sites'       => -1, // Ilimitado
                'description' => __('Para agencias y proyectos múltiples', 'flavor-chat-ia'),
                'features'    => [
                    __('Todos los módulos', 'flavor-chat-ia'),
                    __('Sitios ilimitados', 'flavor-chat-ia'),
                    __('Soporte premium 24/7', 'flavor-chat-ia'),
                    __('Actualizaciones de por vida', 'flavor-chat-ia'),
                    __('Todos los addons incluidos', 'flavor-chat-ia'),
                    __('White label disponible', 'flavor-chat-ia'),
                ],
                'modules'     => 'all', // Todos los módulos
                'color'       => '#f59e0b',
            ],
        ];

        // Permitir filtrar planes
        $this->plans = apply_filters('flavor_license_plans', $this->plans);
    }

    /**
     * Obtiene todos los planes
     *
     * @return array
     */
    public function get_plans() {
        return $this->plans;
    }

    /**
     * Obtiene un plan específico
     *
     * @param string $plan_slug Slug del plan
     * @return array|null
     */
    public function get_plan($plan_slug) {
        return $this->plans[$plan_slug] ?? null;
    }

    /**
     * Obtiene los módulos base
     *
     * @return array
     */
    public function get_base_modules() {
        return $this->base_modules;
    }

    /**
     * Obtiene los módulos disponibles para un plan
     *
     * @param string $plan_slug Slug del plan
     * @return array Lista de slugs de módulos
     */
    public function get_plan_modules($plan_slug) {
        $plan = $this->get_plan($plan_slug);

        if (!$plan) {
            return $this->base_modules;
        }

        // Plan Agency tiene todos los módulos
        if ($plan['modules'] === 'all') {
            return $this->get_all_available_modules();
        }

        // Combinar módulos base + módulos del plan
        return array_unique(array_merge(
            $this->base_modules,
            $plan['modules']
        ));
    }

    /**
     * Obtiene todos los módulos disponibles en el sistema
     *
     * @return array
     */
    public function get_all_available_modules() {
        $module_loader = Flavor_Chat_Module_Loader::get_instance();
        $all_modules = $module_loader->get_available_modules();

        return array_keys($all_modules);
    }

    /**
     * Verifica si un módulo está incluido en un plan
     *
     * @param string $module_slug Slug del módulo
     * @param string $plan_slug Slug del plan
     * @return bool
     */
    public function is_module_in_plan($module_slug, $plan_slug) {
        $plan_modules = $this->get_plan_modules($plan_slug);
        return in_array($module_slug, $plan_modules, true);
    }

    /**
     * Obtiene el plan mínimo requerido para un módulo
     *
     * @param string $module_slug Slug del módulo
     * @return string|null Slug del plan mínimo o null si está en base
     */
    public function get_minimum_plan_for_module($module_slug) {
        // Si está en base, no requiere plan
        if (in_array($module_slug, $this->base_modules, true)) {
            return 'free';
        }

        // Buscar en orden de precio
        $plan_order = ['starter', 'professional', 'agency'];

        foreach ($plan_order as $plan_slug) {
            if ($this->is_module_in_plan($module_slug, $plan_slug)) {
                return $plan_slug;
            }
        }

        return 'agency'; // Por defecto requiere agency
    }

    /**
     * Compara dos planes y devuelve cuál es superior
     *
     * @param string $plan_a Slug del plan A
     * @param string $plan_b Slug del plan B
     * @return int -1 si A < B, 0 si iguales, 1 si A > B
     */
    public function compare_plans($plan_a, $plan_b) {
        $order = ['free' => 0, 'starter' => 1, 'professional' => 2, 'agency' => 3];

        $level_a = $order[$plan_a] ?? 0;
        $level_b = $order[$plan_b] ?? 0;

        return $level_a <=> $level_b;
    }

    /**
     * Obtiene planes para mostrar en UI (sin el plan free)
     *
     * @return array
     */
    public function get_purchasable_plans() {
        $plans = $this->plans;
        unset($plans['free']);
        return $plans;
    }

    /**
     * Obtiene el precio formateado de un plan
     *
     * @param string $plan_slug Slug del plan
     * @return string
     */
    public function get_formatted_price($plan_slug) {
        $plan = $this->get_plan($plan_slug);

        if (!$plan || $plan['price'] === 0) {
            return __('Gratis', 'flavor-chat-ia');
        }

        $price = number_format($plan['price'], 0, ',', '.');
        $period = $plan['period'] === 'year' ? __('/año', 'flavor-chat-ia') : '';

        return $price . '€' . $period;
    }

    /**
     * Obtiene el texto de sitios permitidos
     *
     * @param string $plan_slug Slug del plan
     * @return string
     */
    public function get_sites_text($plan_slug) {
        $plan = $this->get_plan($plan_slug);

        if (!$plan) {
            return '1 ' . __('sitio', 'flavor-chat-ia');
        }

        if ($plan['sites'] === -1) {
            return __('Sitios ilimitados', 'flavor-chat-ia');
        }

        return sprintf(
            _n('%d sitio', '%d sitios', $plan['sites'], 'flavor-chat-ia'),
            $plan['sites']
        );
    }
}

/**
 * Función helper para obtener instancia
 *
 * @return Flavor_License_Plans
 */
function flavor_license_plans() {
    return Flavor_License_Plans::get_instance();
}
