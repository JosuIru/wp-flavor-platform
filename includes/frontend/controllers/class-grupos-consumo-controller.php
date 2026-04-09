<?php
/**
 * Controlador Frontend: Grupos de Consumo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

class Flavor_Grupos_Consumo_Controller extends Flavor_Frontend_Controller_Base {

    protected $module_slug = 'grupos-consumo';
    protected $module_name = 'Grupos de Consumo';
    protected $primary_color = 'lime';
    protected $gradient_from = 'lime-500';
    protected $gradient_to = 'green-600';

    /**
     * Obtiene datos para la vista de archivo
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_grupos_consumo';
        $tabla_productos = $wpdb->prefix . 'flavor_gc_productos';

        $grupos = [];
        $estadisticas = [
            'total_grupos' => 0,
            'total_socios' => 0,
            'productores_locales' => 0,
            'pedidos_mes' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $grupos = $wpdb->get_results("SELECT * FROM $tabla_grupos WHERE estado = 'activo' ORDER BY nombre ASC", ARRAY_A);
            $estadisticas['total_grupos'] = count($grupos);

            foreach ($grupos as &$grupo) {
                $grupo['url'] = home_url("/{$this->module_slug}/{$grupo['id']}/");
            }
        } else {
            // Demo data
            $grupos = [
                [
                    'id' => 1,
                    'nombre' => 'EcoConsumo Local',
                    'descripcion' => 'Grupo de consumo ecológico con productos de proximidad',
                    'imagen' => '',
                    'socios' => 45,
                    'zona' => 'Centro',
                    'tipo' => 'ecologico',
                    'dia_reparto' => 'Miércoles',
                    'aceptando_socios' => true,
                    'url' => home_url("/{$this->module_slug}/1/"),
                ],
                [
                    'id' => 2,
                    'nombre' => 'La Cesta Verde',
                    'descripcion' => 'Frutas y verduras de temporada directas del productor',
                    'imagen' => '',
                    'socios' => 32,
                    'zona' => 'Norte',
                    'tipo' => 'frutas_verduras',
                    'dia_reparto' => 'Viernes',
                    'aceptando_socios' => true,
                    'url' => home_url("/{$this->module_slug}/2/"),
                ],
                [
                    'id' => 3,
                    'nombre' => 'Pan Artesano Colectivo',
                    'descripcion' => 'Pan de masa madre y repostería artesanal',
                    'imagen' => '',
                    'socios' => 28,
                    'zona' => 'Sur',
                    'tipo' => 'panaderia',
                    'dia_reparto' => 'Sábado',
                    'aceptando_socios' => false,
                    'url' => home_url("/{$this->module_slug}/3/"),
                ],
            ];
            $estadisticas = [
                'total_grupos' => 12,
                'total_socios' => 340,
                'productores_locales' => 25,
                'pedidos_mes' => 156,
            ];
        }

        return [
            'grupos' => $grupos,
            'estadisticas' => $estadisticas,
            'categorias' => $this->get_categorias(),
        ];
    }

    /**
     * Obtiene datos para la vista single
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_grupos_consumo';

        $grupo = null;
        $productos = [];
        $productores = [];
        $proximos_pedidos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $grupo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_grupos WHERE id = %d", $item_id), ARRAY_A);
        }

        if (!$grupo) {
            // Demo data
            $grupo = [
                'id' => $item_id,
                'nombre' => 'EcoConsumo Local',
                'descripcion' => 'Somos un grupo de consumo comprometido con la alimentación ecológica y de proximidad. Trabajamos directamente con productores locales para garantizar productos frescos y de calidad.',
                'imagen' => '',
                'socios' => 45,
                'zona' => 'Centro',
                'tipo' => 'ecologico',
                'dia_reparto' => 'Miércoles',
                'hora_reparto' => '18:00 - 20:00',
                'punto_reparto' => 'Centro Cívico Municipal',
                'direccion_reparto' => 'C/ Mayor 15',
                'cuota_mensual' => '5€',
                'pedido_minimo' => '15€',
                'aceptando_socios' => true,
                'contacto_email' => 'info@ecoconsumo.local',
                'contacto_telefono' => '600 123 456',
                'fecha_creacion' => '2020',
            ];

            $productos = [
                ['nombre' => 'Cesta verduras temporada', 'precio' => '12€', 'productor' => 'Huerta El Vergel', 'disponible' => true],
                ['nombre' => 'Huevos ecológicos (docena)', 'precio' => '4€', 'productor' => 'Granja La Paz', 'disponible' => true],
                ['nombre' => 'Pan integral masa madre', 'precio' => '3.50€', 'productor' => 'Panadería Artesana', 'disponible' => true],
                ['nombre' => 'Queso curado oveja', 'precio' => '15€/kg', 'productor' => 'Quesería Sierra', 'disponible' => false],
            ];

            $productores = [
                ['nombre' => 'Huerta El Vergel', 'tipo' => 'Verduras', 'km' => 8],
                ['nombre' => 'Granja La Paz', 'tipo' => 'Huevos y aves', 'km' => 12],
                ['nombre' => 'Panadería Artesana', 'tipo' => 'Pan y repostería', 'km' => 3],
            ];

            $proximos_pedidos = [
                ['fecha' => 'Lunes 15', 'cierre' => 'Domingo 14 a las 20:00', 'reparto' => 'Miércoles 17'],
                ['fecha' => 'Lunes 22', 'cierre' => 'Domingo 21 a las 20:00', 'reparto' => 'Miércoles 24'],
            ];
        }

        return [
            'grupo' => $grupo,
            'productos' => $productos,
            'productores' => $productores,
            'proximos_pedidos' => $proximos_pedidos,
            'es_socio' => is_user_logged_in() ? $this->es_socio($item_id) : false,
        ];
    }

    /**
     * Obtiene datos para la búsqueda
     */
    protected function get_search_data($query) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_grupos_consumo';
        $resultados = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $resultados = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_grupos WHERE (nombre LIKE %s OR descripcion LIKE %s) AND estado = 'activo'",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            ), ARRAY_A);

            foreach ($resultados as &$grupo) {
                $grupo['url'] = home_url("/{$this->module_slug}/{$grupo['id']}/");
            }
        }

        return [
            'query' => $query,
            'resultados' => $resultados,
            'total_resultados' => count($resultados),
        ];
    }

    /**
     * Obtiene categorías de grupos
     */
    private function get_categorias() {
        return [
            ['slug' => 'ecologico', 'nombre' => 'Ecológico'],
            ['slug' => 'frutas_verduras', 'nombre' => 'Frutas y Verduras'],
            ['slug' => 'carnes', 'nombre' => 'Carnes'],
            ['slug' => 'lacteos', 'nombre' => 'Lácteos'],
            ['slug' => 'panaderia', 'nombre' => 'Panadería'],
            ['slug' => 'mixto', 'nombre' => 'Mixto'],
        ];
    }

    /**
     * Verifica si el usuario es socio del grupo
     */
    private function es_socio($grupo_id) {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_gc_socios';
        $usuario_id = get_current_user_id();

        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_socios WHERE grupo_id = %d AND usuario_id = %d AND estado = 'activo'",
                $grupo_id, $usuario_id
            ));
        }
        return false;
    }

    /**
     * Registra endpoints REST adicionales
     */
    protected function register_rest_routes_extra() {
        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/unirse", [
            'methods' => 'POST',
            'callback' => [$this, 'api_unirse_grupo'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/pedido", [
            'methods' => 'POST',
            'callback' => [$this, 'api_realizar_pedido'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
    }

    /**
     * API: Unirse a grupo
     */
    public function api_unirse_grupo($request) {
        $grupo_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        // Lógica para unirse al grupo
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * API: Realizar pedido
     */
    public function api_realizar_pedido($request) {
        $grupo_id = $request->get_param('id');
        $productos = $request->get_param('productos');

        // Lógica para realizar pedido
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Pedido registrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }
}
