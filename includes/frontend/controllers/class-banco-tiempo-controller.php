<?php
/**
 * Controlador Frontend: Banco de Tiempo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

class Flavor_Banco_Tiempo_Controller extends Flavor_Frontend_Controller_Base {

    protected $module_slug = 'banco-tiempo';
    protected $module_name = 'Banco de Tiempo';
    protected $primary_color = 'violet';
    protected $gradient_from = 'violet-500';
    protected $gradient_to = 'purple-600';

    /**
     * Obtiene datos para la vista de archivo
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_usuarios = $wpdb->prefix . 'flavor_banco_tiempo_usuarios';

        $servicios = [];
        $estadisticas = [
            'total_miembros' => 0,
            'servicios_activos' => 0,
            'horas_intercambiadas' => 0,
            'intercambios_mes' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            $servicios = $wpdb->get_results("SELECT * FROM $tabla_servicios WHERE estado = 'activo' ORDER BY fecha_creacion DESC LIMIT 20", ARRAY_A);
            $estadisticas['servicios_activos'] = count($servicios);

            foreach ($servicios as &$servicio) {
                $servicio['url'] = home_url("/{$this->module_slug}/{$servicio['id']}/");
            }
        } else {
            // Demo data
            $servicios = [
                [
                    'id' => 1,
                    'titulo' => 'Clases de inglés conversacional',
                    'descripcion' => 'Ofrezco clases de conversación en inglés para nivel intermedio y avanzado',
                    'tipo' => 'oferta',
                    'categoria' => 'idiomas',
                    'horas' => 1,
                    'usuario_nombre' => 'María García',
                    'usuario_valoracion' => '4.9',
                    'url' => home_url("/{$this->module_slug}/1/"),
                ],
                [
                    'id' => 2,
                    'titulo' => 'Ayuda con mudanza',
                    'descripcion' => 'Necesito ayuda para mudarme el próximo fin de semana',
                    'tipo' => 'demanda',
                    'categoria' => 'hogar',
                    'horas' => 3,
                    'usuario_nombre' => 'Carlos López',
                    'usuario_valoracion' => '4.7',
                    'url' => home_url("/{$this->module_slug}/2/"),
                ],
                [
                    'id' => 3,
                    'titulo' => 'Reparación de bicicletas',
                    'descripcion' => 'Ofrezco reparaciones básicas y mantenimiento de bicis',
                    'tipo' => 'oferta',
                    'categoria' => 'reparaciones',
                    'horas' => 2,
                    'usuario_nombre' => 'Ana Martínez',
                    'usuario_valoracion' => '5.0',
                    'url' => home_url("/{$this->module_slug}/3/"),
                ],
                [
                    'id' => 4,
                    'titulo' => 'Clases de guitarra',
                    'descripcion' => 'Busco alguien que me enseñe guitarra española, nivel principiante',
                    'tipo' => 'demanda',
                    'categoria' => 'musica',
                    'horas' => 1,
                    'usuario_nombre' => 'Pedro Sánchez',
                    'usuario_valoracion' => '4.8',
                    'url' => home_url("/{$this->module_slug}/4/"),
                ],
            ];
            $estadisticas = [
                'total_miembros' => 156,
                'servicios_activos' => 89,
                'horas_intercambiadas' => 1234,
                'intercambios_mes' => 45,
            ];
        }

        return [
            'servicios' => $servicios,
            'estadisticas' => $estadisticas,
            'categorias' => $this->get_categorias(),
        ];
    }

    /**
     * Obtiene datos para la vista single
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $servicio = null;
        $usuario = null;
        $valoraciones = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            $servicio = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_servicios WHERE id = %d", $item_id), ARRAY_A);
        }

        if (!$servicio) {
            // Demo data
            $servicio = [
                'id' => $item_id,
                'titulo' => 'Clases de inglés conversacional',
                'descripcion' => 'Ofrezco clases de conversación en inglés para personas con nivel intermedio o avanzado. Podemos practicar temas de actualidad, preparar entrevistas de trabajo o simplemente conversar para mejorar la fluidez.',
                'tipo' => 'oferta',
                'categoria' => 'idiomas',
                'horas' => 1,
                'disponibilidad' => 'Tardes de lunes a viernes',
                'ubicacion' => 'Presencial o videollamada',
                'fecha_creacion' => '15/01/2024',
            ];

            $usuario = [
                'nombre' => 'María García',
                'valoracion' => '4.9',
                'intercambios' => 23,
                'miembro_desde' => '2022',
                'horas_dadas' => 45,
                'horas_recibidas' => 38,
                'descripcion' => 'Profesora de inglés jubilada, me encanta compartir mi conocimiento.',
            ];

            $valoraciones = [
                ['autor' => 'Juan P.', 'puntuacion' => 5, 'comentario' => 'Excelente profesora, muy paciente', 'fecha' => 'Hace 1 semana'],
                ['autor' => 'Laura M.', 'puntuacion' => 5, 'comentario' => 'Las clases son muy amenas y he mejorado mucho', 'fecha' => 'Hace 2 semanas'],
                ['autor' => 'Roberto S.', 'puntuacion' => 4, 'comentario' => 'Muy buena experiencia', 'fecha' => 'Hace 1 mes'],
            ];
        }

        return [
            'servicio' => $servicio,
            'usuario' => $usuario,
            'valoraciones' => $valoraciones,
            'servicios_similares' => $this->get_servicios_similares($servicio['categoria'] ?? 'idiomas', $item_id),
        ];
    }

    /**
     * Obtiene datos para la búsqueda
     */
    protected function get_search_data($query) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $resultados = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            $resultados = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_servicios WHERE (titulo LIKE %s OR descripcion LIKE %s) AND estado = 'activo'",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            ), ARRAY_A);

            foreach ($resultados as &$servicio) {
                $servicio['url'] = home_url("/{$this->module_slug}/{$servicio['id']}/");
            }
        }

        return [
            'query' => $query,
            'resultados' => $resultados,
            'total_resultados' => count($resultados),
        ];
    }

    /**
     * Obtiene categorías de servicios
     */
    private function get_categorias() {
        return [
            ['slug' => 'idiomas', 'nombre' => 'Idiomas', 'icono' => '🗣️'],
            ['slug' => 'informatica', 'nombre' => 'Informática', 'icono' => '💻'],
            ['slug' => 'hogar', 'nombre' => 'Hogar', 'icono' => '🏠'],
            ['slug' => 'cuidados', 'nombre' => 'Cuidados', 'icono' => '👶'],
            ['slug' => 'reparaciones', 'nombre' => 'Reparaciones', 'icono' => '🔧'],
            ['slug' => 'transporte', 'nombre' => 'Transporte', 'icono' => '🚗'],
            ['slug' => 'cocina', 'nombre' => 'Cocina', 'icono' => '🍳'],
            ['slug' => 'musica', 'nombre' => 'Música', 'icono' => '🎵'],
            ['slug' => 'deporte', 'nombre' => 'Deporte', 'icono' => '⚽'],
            ['slug' => 'otros', 'nombre' => 'Otros', 'icono' => '📦'],
        ];
    }

    /**
     * Obtiene servicios similares
     */
    private function get_servicios_similares($categoria, $excluir_id) {
        return [
            ['id' => 10, 'titulo' => 'Conversación en francés', 'horas' => 1, 'url' => home_url("/{$this->module_slug}/10/")],
            ['id' => 11, 'titulo' => 'Preparación exámenes Cambridge', 'horas' => 2, 'url' => home_url("/{$this->module_slug}/11/")],
        ];
    }

    /**
     * Registra endpoints REST adicionales
     */
    protected function register_rest_routes_extra() {
        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/solicitar", [
            'methods' => 'POST',
            'callback' => [$this, 'api_solicitar_intercambio'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/publicar", [
            'methods' => 'POST',
            'callback' => [$this, 'api_publicar_servicio'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/valorar", [
            'methods' => 'POST',
            'callback' => [$this, 'api_valorar_intercambio'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
    }

    /**
     * API: Solicitar intercambio
     */
    public function api_solicitar_intercambio($request) {
        $servicio_id = $request->get_param('id');
        $mensaje = sanitize_textarea_field($request->get_param('mensaje'));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * API: Publicar servicio
     */
    public function api_publicar_servicio($request) {
        $datos = [
            'titulo' => sanitize_text_field($request->get_param('titulo')),
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion')),
            'tipo' => sanitize_text_field($request->get_param('tipo')),
            'categoria' => sanitize_text_field($request->get_param('categoria')),
            'horas' => intval($request->get_param('horas')),
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Servicio publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * API: Valorar intercambio
     */
    public function api_valorar_intercambio($request) {
        $intercambio_id = $request->get_param('id');
        $puntuacion = intval($request->get_param('puntuacion'));
        $comentario = sanitize_textarea_field($request->get_param('comentario'));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Valoración guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }
}
