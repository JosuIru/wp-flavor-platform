<?php
/**
 * Sistema de Busqueda Global Cross-Module
 *
 * Busca simultaneamente en todos los modulos activos del sistema,
 * agregando resultados de multiples tablas con una API unificada.
 *
 * @package FlavorPlatform
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Global_Search {

    private static $instancia = null;

    private $entidades_registradas = [];

    private $cache_tablas_existentes = null;

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public static function registrar_entidad($clave_entidad, $configuracion) {
        $instancia = self::get_instance();

        $configuracion_normalizada = wp_parse_args($configuracion, [
            'tabla'               => '',
            'campos_busqueda'     => [],
            'campo_titulo'        => '',
            'campo_descripcion'   => '',
            'campo_estado'        => 'estado',
            'valor_estado_activo' => null,
            'icono'               => '',
            'etiqueta'            => ucfirst($clave_entidad),
            'url_base'            => '',
        ]);

        $instancia->entidades_registradas[$clave_entidad] = $configuracion_normalizada;
    }

    private function __construct() {
        $this->registrar_entidades_internas();
        $this->registrar_hooks();
    }

    private function registrar_hooks() {
        add_action('wp_ajax_flavor_global_search', [$this, 'ajax_buscar']);
        add_filter('flavor_chat_global_tools', [$this, 'registrar_herramientas_globales']);
        add_filter('flavor_chat_global_actions', [$this, 'registrar_acciones_globales']);
    }

    private function registrar_entidades_internas() {
        $entidades_predefinidas = [
            'eventos' => [
                'tabla'               => 'flavor_eventos',
                'campos_busqueda'     => ['titulo', 'descripcion', 'ubicacion', 'organizador_nombre'],
                'campo_titulo'        => 'titulo',
                'campo_descripcion'   => 'descripcion',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => 'publicado',
                'etiqueta'            => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'bares' => [
                'tabla'               => 'flavor_bares',
                'campos_busqueda'     => ['nombre', 'descripcion', 'direccion', 'tipo'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'descripcion',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => 'activo',
                'etiqueta'            => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'foros_hilos' => [
                'tabla'               => 'flavor_foros_hilos',
                'campos_busqueda'     => ['titulo', 'contenido'],
                'campo_titulo'        => 'titulo',
                'campo_descripcion'   => 'contenido',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => 'abierto',
                'etiqueta'            => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'comunidades' => [
                'tabla'               => 'flavor_comunidades',
                'campos_busqueda'     => ['nombre', 'descripcion'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'descripcion',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => 'activa',
                'etiqueta'            => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'colectivos' => [
                'tabla'               => 'flavor_colectivos',
                'campos_busqueda'     => ['nombre', 'descripcion'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'descripcion',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => 'activo',
                'etiqueta'            => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'clientes' => [
                'tabla'               => 'flavor_clientes_crm',
                'campos_busqueda'     => ['nombre', 'email', 'empresa', 'telefono', 'notas'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'notas',
                'etiqueta'            => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'contactos_empresarial' => [
                'tabla'               => 'flavor_contactos_empresarial',
                'campos_busqueda'     => ['nombre', 'email', 'asunto', 'mensaje'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'asunto',
                'etiqueta'            => __('Contactos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'proyectos_empresarial' => [
                'tabla'               => 'flavor_proyectos_empresarial',
                'campos_busqueda'     => ['nombre', 'descripcion', 'cliente'],
                'campo_titulo'        => 'nombre',
                'campo_descripcion'   => 'descripcion',
                'campo_estado'        => 'estado',
                'valor_estado_activo' => null,
                'etiqueta'            => __('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];

        foreach ($entidades_predefinidas as $clave => $configuracion) {
            $this->entidades_registradas[$clave] = $configuracion;
        }
    }

    // =========================================================================
    // BUSQUEDA PRINCIPAL
    // =========================================================================

    public function buscar($termino_busqueda, $opciones = []) {
        global $wpdb;

        $termino_busqueda = sanitize_text_field($termino_busqueda);
        if (strlen($termino_busqueda) < 2) {
            return ['success' => false, 'error' => __('El termino de busqueda debe tener al menos 2 caracteres', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $limite_por_entidad   = isset($opciones['limite']) ? absint($opciones['limite']) : 5;
        $pagina_actual        = isset($opciones['pagina']) ? max(1, absint($opciones['pagina'])) : 1;
        $offset_resultados    = ($pagina_actual - 1) * $limite_por_entidad;
        $limite_por_entidad   = min($limite_por_entidad, 50);
        $entidades_filtradas = $opciones['entidades'] ?? [];
        $resultados_agrupados = [];
        $total_resultados = 0;

        $tablas_existentes_en_bd = $this->obtener_tablas_existentes();

        foreach ($this->entidades_registradas as $clave_entidad => $configuracion_entidad) {
            if (!empty($entidades_filtradas) && !in_array($clave_entidad, $entidades_filtradas, true)) {
                continue;
            }

            $nombre_tabla_completo = $wpdb->prefix . $configuracion_entidad['tabla'];
            if (!in_array($nombre_tabla_completo, $tablas_existentes_en_bd, true)) {
                continue;
            }

            if (empty($configuracion_entidad['campos_busqueda'])) {
                continue;
            }

            $resultados_entidad = $this->buscar_en_entidad(
                $nombre_tabla_completo,
                $configuracion_entidad,
                $termino_busqueda,
                $limite_por_entidad,
                $offset_resultados
            );

            if (!empty($resultados_entidad)) {
                $resultados_agrupados[$clave_entidad] = [
                    'etiqueta'    => $configuracion_entidad['etiqueta'],
                    'total'       => count($resultados_entidad),
                    'resultados'  => $resultados_entidad,
                ];
                $total_resultados += count($resultados_entidad);
            }
        }

        return [
            'success'     => true,
            'termino'     => $termino_busqueda,
            'total'       => $total_resultados,
            'entidades'   => count($resultados_agrupados),
            'resultados'  => $resultados_agrupados,
        ];
    }

    private function buscar_en_entidad($nombre_tabla, $configuracion, $termino, $limite, $offset = 0) {
        global $wpdb;

        $clausulas_like = [];
        $valores_like = [];
        foreach ($configuracion['campos_busqueda'] as $nombre_campo) {
            // FIX: Validar que el nombre de campo sea alfanumérico con guiones bajos
            $nombre_campo_sanitizado = preg_replace('/[^a-z0-9_]/i', '', $nombre_campo);
            if (empty($nombre_campo_sanitizado) || strlen($nombre_campo_sanitizado) > 64) {
                continue;
            }
            $clausulas_like[] = "`{$nombre_campo_sanitizado}` LIKE %s";
            $valores_like[] = '%' . $wpdb->esc_like($termino) . '%';
        }

        // FIX: Si no hay campos válidos, retornar vacío
        if (empty($clausulas_like)) {
            return [];
        }

        $clausula_busqueda = implode(' OR ', $clausulas_like);

        // Filtro de estado si aplica
        $clausula_estado = '';
        if (!empty($configuracion['campo_estado']) && $configuracion['valor_estado_activo'] !== null) {
            // FIX: Sanitizar nombre de campo de estado
            $campo_estado_sanitizado = preg_replace('/[^a-z0-9_]/i', '', $configuracion['campo_estado']);
            if (!empty($campo_estado_sanitizado) && strlen($campo_estado_sanitizado) <= 64) {
                $clausula_estado = $wpdb->prepare(
                    " AND `{$campo_estado_sanitizado}` = %s",
                    $configuracion['valor_estado_activo']
                );
            }
        }

        // FIX: Sanitizar campos de selección
        $campo_titulo_raw = $configuracion['campo_titulo'] ?: 'id';
        $campo_titulo = preg_replace('/[^a-z0-9_]/i', '', $campo_titulo_raw);
        if (empty($campo_titulo) || strlen($campo_titulo) > 64) {
            $campo_titulo = 'id';
        }

        $campo_descripcion_raw = $configuracion['campo_descripcion'] ?: '';
        $campo_descripcion = preg_replace('/[^a-z0-9_]/i', '', $campo_descripcion_raw);
        if (empty($campo_descripcion) || strlen($campo_descripcion) > 64) {
            $campo_descripcion = "''";
        } else {
            $campo_descripcion = "`{$campo_descripcion}`";
        }

        $consulta_sql = $wpdb->prepare(
            "SELECT id, `{$campo_titulo}` AS titulo, {$campo_descripcion} AS descripcion
             FROM {$nombre_tabla}
             WHERE ({$clausula_busqueda}){$clausula_estado}
             ORDER BY id DESC
             LIMIT %d OFFSET %d",
            array_merge($valores_like, [$limite, $offset])
        );

        $filas_encontradas = $wpdb->get_results($consulta_sql);

        $resultados_formateados = [];
        foreach ($filas_encontradas as $fila) {
            $texto_descripcion = $fila->descripcion ?? '';
            if (strlen($texto_descripcion) > 150) {
                $texto_descripcion = mb_substr($texto_descripcion, 0, 150) . '...';
            }

            $resultados_formateados[] = [
                'id'          => $fila->id,
                'titulo'      => $fila->titulo,
                'descripcion' => $texto_descripcion,
            ];
        }

        return $resultados_formateados;
    }

    private function obtener_tablas_existentes() {
        if ($this->cache_tablas_existentes !== null) {
            return $this->cache_tablas_existentes;
        }

        global $wpdb;
        $tablas_flavor = $wpdb->get_col(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->esc_like($wpdb->prefix . 'flavor_') . '%'
            )
        );

        $this->cache_tablas_existentes = $tablas_flavor ?: [];
        return $this->cache_tablas_existentes;
    }

    /**
     * Invalidar cache de tablas existentes
     */
    public function invalidar_cache_tablas() {
        $this->cache_tablas_existentes = null;
    }

    // =========================================================================
    // AJAX
    // =========================================================================

    public function ajax_buscar() {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (
            !wp_verify_nonce($nonce, 'flavor_platform_nonce')
            && !wp_verify_nonce($nonce, 'flavor_platform_nonce')
        ) {
            wp_send_json_error(__('Nonce inválido', FLAVOR_PLATFORM_TEXT_DOMAIN), 403);
        }

        if (!current_user_can('read')) {
            wp_send_json_error(__('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $termino = sanitize_text_field($_POST['termino'] ?? '');
        $limite = intval($_POST['limite'] ?? 5);
        $entidades = isset($_POST['entidades']) ? array_map('sanitize_key', (array) $_POST['entidades']) : [];

        $resultado = $this->buscar($termino, [
            'limite'    => $limite,
            'entidades' => $entidades,
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    // =========================================================================
    // INTEGRACION CON CHAT IA
    // =========================================================================

    public function registrar_herramientas_globales($herramientas) {
        $herramientas[] = [
            'name'         => 'busqueda_global',
            'description'  => 'Busca informacion en todos los modulos de la plataforma (eventos, bares, foros, comunidades, colectivos, clientes, contactos, proyectos). Util cuando no se sabe en que modulo buscar.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'termino' => [
                        'type'        => 'string',
                        'description' => 'Termino de busqueda (minimo 2 caracteres)',
                    ],
                    'limite' => [
                        'type'        => 'integer',
                        'description' => 'Maximo resultados por entidad (defecto: 5)',
                    ],
                ],
                'required' => ['termino'],
            ],
        ];

        return $herramientas;
    }

    public function registrar_acciones_globales($acciones) {
        $acciones['busqueda_global'] = [
            'label'       => __('Busqueda global', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Busca en todos los modulos de la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'callback'    => [$this, 'ejecutar_busqueda_desde_chat'],
        ];

        return $acciones;
    }

    public function ejecutar_busqueda_desde_chat($parametros) {
        $termino = $parametros['termino'] ?? '';
        $limite = intval($parametros['limite'] ?? 5);

        return $this->buscar($termino, ['limite' => $limite]);
    }

    public function obtener_entidades_disponibles() {
        $tablas_existentes = $this->obtener_tablas_existentes();
        $entidades_disponibles = [];

        foreach ($this->entidades_registradas as $clave => $configuracion) {
            global $wpdb;
            $nombre_tabla_completo = $wpdb->prefix . $configuracion['tabla'];
            if (in_array($nombre_tabla_completo, $tablas_existentes, true)) {
                $entidades_disponibles[$clave] = $configuracion['etiqueta'];
            }
        }

        return $entidades_disponibles;
    }

    public function buscar_para_chat($parametros_busqueda) {
        $termino = sanitize_text_field($parametros_busqueda['termino'] ?? '');
        $limite  = absint($parametros_busqueda['limite'] ?? 5);

        $resultado = $this->buscar($termino, ['limite' => $limite]);

        if (!$resultado['success']) {
            return $resultado;
        }

        $texto_respuesta = '';
        foreach ($resultado['resultados'] as $clave => $datos) {
            $texto_respuesta .= '--- ' . $datos['etiqueta'] . ' (' . $datos['total'] . ') ---' . "\n";
            foreach ($datos['resultados'] as $item) {
                $texto_respuesta .= '- [' . $item['id'] . '] ' . $item['titulo'];
                if (!empty($item['descripcion'])) {
                    $texto_respuesta .= ': ' . $item['descripcion'];
                }
                $texto_respuesta .= "\n";
            }
            $texto_respuesta .= "\n";
        }

        $resultado['texto_chat'] = trim($texto_respuesta);
        return $resultado;
    }

    public function get_tool_definition() {
        return [
            'name'         => 'busqueda_global',
            'description'  => 'Busca informacion en todos los modulos de la plataforma (eventos, bares, foros, comunidades, colectivos, clientes, contactos, proyectos). Util cuando no se sabe en que modulo buscar.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'termino' => [
                        'type'        => 'string',
                        'description' => 'Termino de busqueda (minimo 2 caracteres)',
                    ],
                    'limite' => [
                        'type'        => 'integer',
                        'description' => 'Maximo resultados por entidad (defecto: 5, maximo: 50)',
                    ],
                ],
                'required' => ['termino'],
            ],
        ];
    }

    public function get_knowledge_base_text() {
        $entidades_disponibles = $this->obtener_entidades_disponibles();
        if (empty($entidades_disponibles)) {
            return '';
        }

        $lista_modulos = implode(', ', array_values($entidades_disponibles));
        return sprintf(
            __('Dispones de una herramienta de busqueda global que busca en: %s. Usa busqueda_global cuando el usuario quiera buscar algo y no sepas en que modulo esta.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $lista_modulos
        );
    }

    public function registrar_modulo_busqueda($knowledge_text) {
        $busqueda_knowledge = $this->get_knowledge_base_text();
        if (!empty($busqueda_knowledge)) {
            $knowledge_text .= '
' . $busqueda_knowledge;
        }
        return $knowledge_text;
    }

    public function get_modulos_disponibles() {
        $tablas_existentes = $this->obtener_tablas_existentes();
        $modulos_info = [];

        foreach ($this->entidades_registradas as $clave => $config) {
            global $wpdb;
            $nombre_tabla = $wpdb->prefix . $config['tabla'];
            $disponible = in_array($nombre_tabla, $tablas_existentes, true);
            $modulos_info[$clave] = [
                'etiqueta'   => $config['etiqueta'],
                'tabla'      => $config['tabla'],
                'disponible' => $disponible,
                'campos'     => count($config['campos_busqueda']),
            ];
        }

        return $modulos_info;
    }

    public function ejecutar_accion_herramienta($nombre_accion, $parametros) {
        if ($nombre_accion === 'busqueda_global') {
            return $this->buscar_para_chat($parametros);
        }

        return [
            'success' => false,
            'error'   => __('Accion no reconocida', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

}
