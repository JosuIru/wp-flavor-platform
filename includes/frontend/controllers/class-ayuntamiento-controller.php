<?php
/**
 * Controlador Frontend: Ayuntamiento
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

class Flavor_Ayuntamiento_Controller extends Flavor_Frontend_Controller_Base {

    protected $module_slug = 'ayuntamiento';
    protected $module_name = 'Ayuntamiento';
    protected $primary_color = 'blue';
    protected $gradient_from = 'blue-700';
    protected $gradient_to = 'blue-900';

    /**
     * Obtiene datos para la vista de archivo
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla_tramites = $wpdb->prefix . 'flavor_ayto_tramites';
        $tabla_noticias = $wpdb->prefix . 'flavor_ayto_noticias';

        $noticias = [];
        $tramites_destacados = [];
        $estadisticas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_noticias)) {
            $noticias = $wpdb->get_results("SELECT * FROM $tabla_noticias WHERE estado = 'publicado' ORDER BY fecha DESC LIMIT 10", ARRAY_A);
            foreach ($noticias as &$noticia) {
                $noticia['url'] = home_url("/{$this->module_slug}/noticia/{$noticia['id']}/");
            }
        } else {
            // Demo data
            $noticias = [
                [
                    'id' => 1,
                    'titulo' => 'Nuevas ayudas para familias',
                    'extracto' => 'El ayuntamiento aprueba un paquete de medidas de apoyo económico para familias vulnerables.',
                    'categoria' => 'Servicios Sociales',
                    'fecha' => '28 Ene 2024',
                    'imagen' => '',
                    'url' => home_url("/{$this->module_slug}/noticia/1/"),
                ],
                [
                    'id' => 2,
                    'titulo' => 'Obras de mejora en el parque central',
                    'extracto' => 'Comienzan las obras de remodelación del parque con nuevas zonas verdes y área infantil.',
                    'categoria' => 'Urbanismo',
                    'fecha' => '25 Ene 2024',
                    'imagen' => '',
                    'url' => home_url("/{$this->module_slug}/noticia/2/"),
                ],
                [
                    'id' => 3,
                    'titulo' => 'Campaña de vacunación antigripal',
                    'extracto' => 'Arranca la campaña de vacunación para mayores de 65 años y grupos de riesgo.',
                    'categoria' => 'Salud',
                    'fecha' => '22 Ene 2024',
                    'imagen' => '',
                    'url' => home_url("/{$this->module_slug}/noticia/3/"),
                ],
            ];
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_tramites)) {
            $tramites_destacados = $wpdb->get_results("SELECT * FROM $tabla_tramites WHERE destacado = 1 ORDER BY nombre LIMIT 5", ARRAY_A);
            foreach ($tramites_destacados as &$tramite) {
                $tramite['url'] = home_url("/{$this->module_slug}/tramite/{$tramite['id']}/");
            }
        } else {
            $tramites_destacados = [
                ['titulo' => 'Empadronamiento', 'url' => home_url("/{$this->module_slug}/tramite/empadronamiento/")],
                ['titulo' => 'Certificado de residencia', 'url' => home_url("/{$this->module_slug}/tramite/certificado-residencia/")],
                ['titulo' => 'Pago de tributos', 'url' => home_url("/{$this->module_slug}/tramite/pago-tributos/")],
                ['titulo' => 'Licencia de obras', 'url' => home_url("/{$this->module_slug}/tramite/licencia-obras/")],
                ['titulo' => 'Registro civil', 'url' => home_url("/{$this->module_slug}/tramite/registro-civil/")],
            ];
        }

        $estadisticas = [
            'direccion' => 'Plaza del Ayuntamiento, 1',
            'telefono' => '900 000 000',
            'email' => 'info@ayuntamiento.es',
            'horario' => 'L-V 9:00-14:00',
            'avisos' => [
                'Cierre por festivo el día 2 de febrero',
                'Nueva sede electrónica disponible',
            ],
        ];

        return [
            'noticias' => $noticias,
            'tramites_destacados' => $tramites_destacados,
            'estadisticas' => $estadisticas,
            'secciones' => $this->get_secciones(),
        ];
    }

    /**
     * Obtiene datos para la vista single
     */
    protected function get_single_data($item_id) {
        global $wpdb;

        // Determinar si es trámite, noticia o servicio
        $tipo = 'tramite';
        if (strpos($item_id, 'noticia-') === 0) {
            $tipo = 'noticia';
            $item_id = str_replace('noticia-', '', $item_id);
        } elseif (strpos($item_id, 'servicio-') === 0) {
            $tipo = 'servicio';
            $item_id = str_replace('servicio-', '', $item_id);
        }

        $item = null;
        $documentos = [];
        $relacionados = [];

        // Demo data según tipo
        if ($tipo === 'tramite') {
            $item = [
                'id' => $item_id,
                'titulo' => 'Empadronamiento',
                'categoria' => 'Padrón Municipal',
                'contenido' => '<p>El empadronamiento es la inscripción en el Padrón Municipal de Habitantes, que es el registro administrativo donde constan los vecinos del municipio.</p><p>Es obligatorio para todos los residentes y necesario para acceder a servicios públicos.</p>',
                'requisitos' => [
                    'DNI/NIE en vigor',
                    'Contrato de alquiler o escritura de propiedad',
                    'Recibo de suministros a nombre del solicitante',
                    'En caso de menores: libro de familia',
                ],
                'pasos' => [
                    'Recopilar la documentación necesaria',
                    'Solicitar cita previa online o por teléfono',
                    'Acudir a la cita con toda la documentación',
                    'Firmar la solicitud de alta en el padrón',
                    'Recibir el volante de empadronamiento',
                ],
                'plazo' => '1-3 días',
                'coste' => 'Gratuito',
                'departamento' => 'Atención Ciudadana',
                'tramite_online' => true,
                'url_tramite' => '#sede-electronica',
            ];

            $documentos = [
                ['nombre' => 'Solicitud de empadronamiento', 'url' => '#', 'formato' => 'PDF', 'tamaño' => '125 KB'],
                ['nombre' => 'Instrucciones del trámite', 'url' => '#', 'formato' => 'PDF', 'tamaño' => '89 KB'],
            ];

            $relacionados = [
                ['titulo' => 'Certificado de empadronamiento', 'url' => home_url("/{$this->module_slug}/tramite/certificado-empadronamiento/")],
                ['titulo' => 'Cambio de domicilio', 'url' => home_url("/{$this->module_slug}/tramite/cambio-domicilio/")],
            ];
        } elseif ($tipo === 'noticia') {
            $item = [
                'id' => $item_id,
                'titulo' => 'Nuevas ayudas para familias',
                'categoria' => 'Servicios Sociales',
                'fecha' => '28 Enero 2024',
                'contenido' => '<p>El Pleno del Ayuntamiento ha aprobado un nuevo paquete de medidas de apoyo económico destinadas a familias en situación de vulnerabilidad.</p><p>Las ayudas incluyen subvenciones para el pago de suministros básicos, becas de comedor escolar y apoyo al alquiler.</p>',
                'imagen' => '',
            ];
        }

        return [
            'item' => $item,
            'tipo' => $tipo,
            'documentos' => $documentos,
            'relacionados' => $relacionados,
        ];
    }

    /**
     * Obtiene datos para la búsqueda
     */
    protected function get_search_data($query) {
        global $wpdb;
        $resultados = [];

        // Buscar en trámites
        $tabla_tramites = $wpdb->prefix . 'flavor_ayto_tramites';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_tramites)) {
            $tramites = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nombre as titulo, descripcion as extracto, 'tramite' as tipo, departamento FROM $tabla_tramites WHERE nombre LIKE %s OR descripcion LIKE %s",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            ), ARRAY_A);

            foreach ($tramites as &$tramite) {
                $tramite['url'] = home_url("/{$this->module_slug}/tramite/{$tramite['id']}/");
                $resultados[] = $tramite;
            }
        }

        // Buscar en noticias
        $tabla_noticias = $wpdb->prefix . 'flavor_ayto_noticias';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_noticias)) {
            $noticias = $wpdb->get_results($wpdb->prepare(
                "SELECT id, titulo, extracto, 'noticia' as tipo FROM $tabla_noticias WHERE titulo LIKE %s OR contenido LIKE %s",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            ), ARRAY_A);

            foreach ($noticias as &$noticia) {
                $noticia['url'] = home_url("/{$this->module_slug}/noticia/{$noticia['id']}/");
                $resultados[] = $noticia;
            }
        }

        // Demo results si no hay datos
        if (empty($resultados) && !empty($query)) {
            $resultados = [
                [
                    'tipo' => 'tramite',
                    'titulo' => 'Empadronamiento',
                    'extracto' => 'Inscripción en el Padrón Municipal de Habitantes',
                    'departamento' => 'Atención Ciudadana',
                    'url' => home_url("/{$this->module_slug}/tramite/empadronamiento/"),
                ],
            ];
        }

        return [
            'query' => $query,
            'resultados' => $resultados,
            'total_resultados' => count($resultados),
        ];
    }

    /**
     * Obtiene secciones del ayuntamiento
     */
    private function get_secciones() {
        return [
            ['slug' => 'tramites', 'nombre' => 'Trámites', 'icono' => '📋'],
            ['slug' => 'noticias', 'nombre' => 'Noticias', 'icono' => '📢'],
            ['slug' => 'servicios', 'nombre' => 'Servicios', 'icono' => '📍'],
            ['slug' => 'agenda', 'nombre' => 'Agenda', 'icono' => '🗓️'],
            ['slug' => 'transparencia', 'nombre' => 'Transparencia', 'icono' => '🔍'],
            ['slug' => 'participacion', 'nombre' => 'Participación', 'icono' => '🗳️'],
        ];
    }

    /**
     * Registra endpoints REST adicionales
     */
    protected function register_rest_routes_extra() {
        register_rest_route('flavor/v1', "/{$this->module_slug}/cita-previa", [
            'methods' => 'POST',
            'callback' => [$this, 'api_solicitar_cita'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/tramites", [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_tramites'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', "/{$this->module_slug}/noticias", [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_noticias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * API: Solicitar cita previa
     */
    public function api_solicitar_cita($request) {
        $datos = [
            'nombre' => sanitize_text_field($request->get_param('nombre')),
            'email' => sanitize_email($request->get_param('email')),
            'telefono' => sanitize_text_field($request->get_param('telefono')),
            'tramite' => sanitize_text_field($request->get_param('tramite')),
            'fecha' => sanitize_text_field($request->get_param('fecha')),
            'hora' => sanitize_text_field($request->get_param('hora')),
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Cita solicitada correctamente', 'flavor-chat-ia'),
            'codigo' => 'CITA-' . wp_rand(10000, 99999),
        ], 200);
    }

    /**
     * API: Listar trámites
     */
    public function api_listar_tramites($request) {
        $categoria = $request->get_param('categoria');
        $tramites = [];

        // Demo trámites
        $tramites = [
            ['id' => 1, 'nombre' => 'Empadronamiento', 'categoria' => 'padron', 'online' => true],
            ['id' => 2, 'nombre' => 'Certificado de residencia', 'categoria' => 'padron', 'online' => true],
            ['id' => 3, 'nombre' => 'Licencia de obras menor', 'categoria' => 'urbanismo', 'online' => true],
            ['id' => 4, 'nombre' => 'Pago de tributos', 'categoria' => 'tributos', 'online' => true],
        ];

        if ($categoria) {
            $tramites = array_filter($tramites, function($tramite) use ($categoria) {
                return $tramite['categoria'] === $categoria;
            });
        }

        return new WP_REST_Response($tramites, 200);
    }

    /**
     * API: Listar noticias
     */
    public function api_listar_noticias($request) {
        $limite = intval($request->get_param('limite')) ?: 10;

        $noticias = [
            ['id' => 1, 'titulo' => 'Nuevas ayudas para familias', 'fecha' => '2024-01-28'],
            ['id' => 2, 'titulo' => 'Obras de mejora en el parque central', 'fecha' => '2024-01-25'],
        ];

        return new WP_REST_Response(array_slice($noticias, 0, $limite), 200);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
