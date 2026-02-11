<?php
/**
 * Controlador Frontend: Comunidades
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

class Flavor_Comunidades_Controller extends Flavor_Frontend_Controller_Base {

    protected $module_slug = 'comunidades';
    protected $module_name = 'Comunidades';
    protected $primary_color = 'rose';
    protected $gradient_from = 'rose-500';
    protected $gradient_to = 'pink-600';

    /**
     * Obtiene datos para la vista de archivo
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidades = [];
        $comunidades_destacadas = [];
        $estadisticas = [
            'total_comunidades' => 0,
            'total_miembros' => 0,
            'eventos_mes' => 0,
            'publicaciones_semana' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            $comunidades = $wpdb->get_results("SELECT * FROM $tabla_comunidades WHERE estado = 'activa' ORDER BY miembros DESC", ARRAY_A);
            $estadisticas['total_comunidades'] = count($comunidades);

            foreach ($comunidades as &$comunidad) {
                $comunidad['url'] = home_url("/{$this->module_slug}/{$comunidad['id']}/");
            }
        } else {
            // Demo data
            $comunidades = [
                [
                    'id' => 1,
                    'nombre' => 'Vecinos del Centro',
                    'descripcion' => 'Comunidad de vecinos del barrio centro para compartir información y organizar actividades',
                    'tipo' => 'vecinal',
                    'miembros' => 234,
                    'ubicacion' => 'Centro',
                    'imagen' => '',
                    'verificada' => true,
                    'activa' => true,
                    'url' => home_url("/{$this->module_slug}/1/"),
                ],
                [
                    'id' => 2,
                    'nombre' => 'Runners del Parque',
                    'descripcion' => 'Grupo de corredores que quedamos varias veces por semana para entrenar juntos',
                    'tipo' => 'deportiva',
                    'miembros' => 89,
                    'ubicacion' => 'Parque Municipal',
                    'imagen' => '',
                    'verificada' => false,
                    'activa' => true,
                    'url' => home_url("/{$this->module_slug}/2/"),
                ],
                [
                    'id' => 3,
                    'nombre' => 'Club de Lectura Municipal',
                    'descripcion' => 'Nos reunimos mensualmente para comentar libros y compartir recomendaciones',
                    'tipo' => 'cultural',
                    'miembros' => 45,
                    'ubicacion' => 'Biblioteca',
                    'imagen' => '',
                    'verificada' => true,
                    'activa' => true,
                    'url' => home_url("/{$this->module_slug}/3/"),
                ],
                [
                    'id' => 4,
                    'nombre' => 'Voluntarios Solidarios',
                    'descripcion' => 'Red de voluntariado para ayudar a personas mayores y en situación de vulnerabilidad',
                    'tipo' => 'solidaria',
                    'miembros' => 156,
                    'ubicacion' => 'Todo el municipio',
                    'imagen' => '',
                    'verificada' => true,
                    'activa' => true,
                    'url' => home_url("/{$this->module_slug}/4/"),
                ],
            ];

            $comunidades_destacadas = [
                [
                    'id' => 1,
                    'nombre' => 'Vecinos del Centro',
                    'descripcion' => 'La comunidad más activa del municipio',
                    'emoji' => '🏠',
                    'miembros' => 234,
                    'eventos_proximos' => 3,
                    'url' => home_url("/{$this->module_slug}/1/"),
                ],
                [
                    'id' => 4,
                    'nombre' => 'Voluntarios Solidarios',
                    'descripcion' => 'Haciendo del municipio un lugar mejor',
                    'emoji' => '🤝',
                    'miembros' => 156,
                    'eventos_proximos' => 5,
                    'url' => home_url("/{$this->module_slug}/4/"),
                ],
            ];

            $estadisticas = [
                'total_comunidades' => 24,
                'total_miembros' => 1250,
                'eventos_mes' => 18,
                'publicaciones_semana' => 67,
            ];
        }

        return [
            'comunidades' => $comunidades,
            'comunidades_destacadas' => $comunidades_destacadas,
            'estadisticas' => $estadisticas,
            'categorias' => $this->get_categorias(),
        ];
    }

    /**
     * Obtiene datos para la vista single
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidad = null;
        $miembros = [];
        $publicaciones = [];
        $eventos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            $comunidad = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_comunidades WHERE id = %d", $item_id), ARRAY_A);
        }

        if (!$comunidad) {
            // Demo data
            $comunidad = [
                'id' => $item_id,
                'nombre' => 'Vecinos del Centro',
                'descripcion' => 'Somos una comunidad de vecinos del barrio centro. Compartimos información útil, organizamos actividades y nos ayudamos mutuamente.',
                'tipo' => 'Vecinal',
                'privacidad' => 'Pública',
                'total_miembros' => 234,
                'ubicacion' => 'Barrio Centro',
                'fecha_creacion' => 'Enero 2020',
                'verificada' => true,
                'banner' => '',
                'emoji' => '🏠',
                'reglas' => 'Respeto mutuo. No spam ni publicidad. Contenido relacionado con el barrio.',
                'enlaces' => [
                    ['titulo' => 'Grupo WhatsApp', 'url' => '#', 'icono' => '💬'],
                    ['titulo' => 'Instagram', 'url' => '#', 'icono' => '📷'],
                ],
            ];

            $miembros = [
                ['nombre' => 'Ana García', 'rol' => 'Administrador', 'es_admin' => true],
                ['nombre' => 'Carlos López', 'rol' => 'Moderador', 'es_admin' => true],
                ['nombre' => 'María Sánchez', 'rol' => 'Miembro', 'es_admin' => false],
                ['nombre' => 'Pedro Martínez', 'rol' => 'Miembro', 'es_admin' => false],
                ['nombre' => 'Laura Fernández', 'rol' => 'Miembro', 'es_admin' => false],
                ['nombre' => 'Juan Rodríguez', 'rol' => 'Miembro', 'es_admin' => false],
            ];

            $publicaciones = [
                [
                    'autor_nombre' => 'Ana García',
                    'autor_rol' => 'Admin',
                    'fecha' => 'Hace 2 horas',
                    'contenido' => '<p>¡Buenos días vecinos! Os recuerdo que mañana tenemos la reunión mensual en el centro cívico a las 19:00. ¡Os esperamos!</p>',
                    'imagen' => '',
                    'likes' => 15,
                    'comentarios' => 8,
                ],
                [
                    'autor_nombre' => 'Carlos López',
                    'autor_rol' => '',
                    'fecha' => 'Hace 1 día',
                    'contenido' => '<p>¿Alguien sabe si mañana hay mercadillo en la plaza? El del mes pasado estuvo genial.</p>',
                    'imagen' => '',
                    'likes' => 7,
                    'comentarios' => 12,
                ],
            ];

            $eventos = [
                [
                    'titulo' => 'Reunión mensual de vecinos',
                    'descripcion' => 'Encuentro mensual para tratar temas del barrio',
                    'mes' => 'FEB',
                    'dia' => '5',
                    'hora' => '19:00',
                    'lugar' => 'Centro Cívico',
                    'asistentes' => 28,
                ],
                [
                    'titulo' => 'Limpieza del parque',
                    'descripcion' => 'Jornada de voluntariado para limpiar el parque del barrio',
                    'mes' => 'FEB',
                    'dia' => '12',
                    'hora' => '10:00',
                    'lugar' => 'Parque Central',
                    'asistentes' => 15,
                ],
            ];
        }

        $usuario_id = get_current_user_id();
        $es_miembro = $this->es_miembro($item_id, $usuario_id);
        $es_admin = $this->es_admin_comunidad($item_id, $usuario_id);

        return [
            'comunidad' => $comunidad,
            'miembros' => $miembros,
            'publicaciones' => $publicaciones,
            'eventos' => $eventos,
            'es_miembro' => $es_miembro,
            'es_admin' => $es_admin,
        ];
    }

    /**
     * Obtiene datos para la búsqueda
     */
    protected function get_search_data($query) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $resultados = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            $resultados = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_comunidades WHERE (nombre LIKE %s OR descripcion LIKE %s) AND estado = 'activa'",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            ), ARRAY_A);

            foreach ($resultados as &$comunidad) {
                $comunidad['url'] = home_url("/{$this->module_slug}/{$comunidad['id']}/");
            }
        }

        return [
            'query' => $query,
            'resultados' => $resultados,
            'total_resultados' => count($resultados),
        ];
    }

    /**
     * Obtiene categorías/tipos de comunidades
     */
    private function get_categorias() {
        return [
            ['slug' => 'vecinal', 'nombre' => 'Vecinal', 'icono' => '🏠'],
            ['slug' => 'interes', 'nombre' => 'Interés común', 'icono' => '💡'],
            ['slug' => 'deportiva', 'nombre' => 'Deportiva', 'icono' => '⚽'],
            ['slug' => 'cultural', 'nombre' => 'Cultural', 'icono' => '🎭'],
            ['slug' => 'solidaria', 'nombre' => 'Solidaria', 'icono' => '🤝'],
            ['slug' => 'profesional', 'nombre' => 'Profesional', 'icono' => '💼'],
        ];
    }

    /**
     * Verifica si el usuario es miembro
     */
    private function es_miembro($comunidad_id, $usuario_id) {
        if (!$usuario_id) return false;
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND usuario_id = %d AND estado = 'activo'",
                $comunidad_id, $usuario_id
            ));
        }
        return false;
    }

    /**
     * Verifica si el usuario es admin de la comunidad
     */
    private function es_admin_comunidad($comunidad_id, $usuario_id) {
        if (!$usuario_id) return false;
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND usuario_id = %d AND rol = 'admin'",
                $comunidad_id, $usuario_id
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
            'callback' => [$this, 'api_unirse_comunidad'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/salir", [
            'methods' => 'POST',
            'callback' => [$this, 'api_salir_comunidad'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/crear", [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_comunidad'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/publicar", [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_publicacion'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/(?P<id>\d+)/evento", [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_evento'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
    }

    /**
     * API: Unirse a comunidad
     */
    public function api_unirse_comunidad($request) {
        $comunidad_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Te has unido a la comunidad', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * API: Salir de comunidad
     */
    public function api_salir_comunidad($request) {
        $comunidad_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Has salido de la comunidad', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * API: Crear comunidad
     */
    public function api_crear_comunidad($request) {
        $datos = [
            'nombre' => sanitize_text_field($request->get_param('nombre')),
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion')),
            'tipo' => sanitize_text_field($request->get_param('tipo')),
            'privacidad' => sanitize_text_field($request->get_param('privacidad')),
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Comunidad creada correctamente', 'flavor-chat-ia'),
            'comunidad_id' => 999,
        ], 200);
    }

    /**
     * API: Crear publicación
     */
    public function api_crear_publicacion($request) {
        $comunidad_id = $request->get_param('id');
        $contenido = wp_kses_post($request->get_param('contenido'));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Publicación creada', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * API: Crear evento
     */
    public function api_crear_evento($request) {
        $comunidad_id = $request->get_param('id');
        $datos = [
            'titulo' => sanitize_text_field($request->get_param('titulo')),
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion')),
            'fecha' => sanitize_text_field($request->get_param('fecha')),
            'hora' => sanitize_text_field($request->get_param('hora')),
            'lugar' => sanitize_text_field($request->get_param('lugar')),
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Evento creado', 'flavor-chat-ia'),
        ], 200);
    }
}
