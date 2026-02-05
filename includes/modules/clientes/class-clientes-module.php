<?php
/**
 * Modulo de Gestion de Clientes / CRM para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo: Gestion de Clientes
 * CRM basico para gestionar clientes, notas e interacciones
 */
class Flavor_Chat_Clientes_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'clientes';
        $this->name = __('Gestion de Clientes', 'flavor-chat-ia');
        $this->description = __('CRM basico para gestionar clientes, notas e interacciones', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_clientes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas del modulo de Clientes no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'limite_resultados_por_defecto' => 20,
            'tipos_cliente' => ['particular', 'empresa', 'autonomo', 'administracion'],
            'estados_cliente' => ['activo', 'inactivo', 'potencial', 'perdido'],
            'origenes_cliente' => ['web', 'referido', 'redes', 'directo', 'otro'],
            'tipos_nota' => ['nota', 'llamada', 'email', 'reunion', 'tarea', 'seguimiento'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_clientes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        $sql_clientes = "CREATE TABLE IF NOT EXISTS $tabla_clientes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            email varchar(200) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            empresa varchar(200) DEFAULT NULL,
            cargo varchar(200) DEFAULT NULL,
            direccion text DEFAULT NULL,
            tipo enum('particular','empresa','autonomo','administracion') DEFAULT 'particular',
            estado enum('activo','inactivo','potencial','perdido') DEFAULT 'potencial',
            etiquetas text DEFAULT NULL,
            valor_estimado decimal(10,2) DEFAULT 0.00,
            origen varchar(100) DEFAULT 'directo',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            notas_count int(11) DEFAULT 0,
            ultima_interaccion datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_tipo (tipo),
            KEY idx_estado (estado),
            KEY idx_asignado_a (asignado_a),
            KEY idx_created_by (created_by),
            KEY idx_origen (origen)
        ) $charset_collate;";

        $sql_notas = "CREATE TABLE IF NOT EXISTS $tabla_notas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cliente_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('nota','llamada','email','reunion','tarea','seguimiento') DEFAULT 'nota',
            contenido text NOT NULL,
            estado enum('pendiente','completada','cancelada') DEFAULT 'completada',
            fecha_seguimiento datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cliente_id (cliente_id),
            KEY idx_autor_id (autor_id),
            KEY idx_tipo (tipo),
            KEY idx_estado (estado),
            KEY idx_fecha_seguimiento (fecha_seguimiento)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_clientes);
        dbDelta($sql_notas);
    }

    // =====================================================================
    // ACCIONES (get_actions / execute_action)
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_clientes' => [
                'description' => 'Listar clientes con filtros opcionales por estado, tipo y etiquetas',
                'params' => ['estado', 'tipo', 'etiquetas', 'limite', 'pagina'],
            ],
            'ver_cliente' => [
                'description' => 'Ver detalle de un cliente con sus notas recientes',
                'params' => ['cliente_id'],
            ],
            'crear_cliente' => [
                'description' => 'Crear un nuevo cliente en el CRM',
                'params' => ['nombre', 'email', 'telefono', 'empresa', 'cargo', 'direccion', 'tipo', 'estado', 'etiquetas', 'valor_estimado', 'origen', 'asignado_a'],
            ],
            'actualizar_cliente' => [
                'description' => 'Actualizar informacion de un cliente existente',
                'params' => ['cliente_id', 'nombre', 'email', 'telefono', 'empresa', 'cargo', 'direccion', 'tipo', 'estado', 'etiquetas', 'valor_estimado', 'origen', 'asignado_a'],
            ],
            'agregar_nota' => [
                'description' => 'Agregar una nota o interaccion a un cliente',
                'params' => ['cliente_id', 'tipo', 'contenido', 'estado', 'fecha_seguimiento'],
            ],
            'buscar_clientes' => [
                'description' => 'Buscar clientes por nombre, email o empresa',
                'params' => ['busqueda', 'limite'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadisticas del CRM: totales, por tipo, por estado, pipeline de valor',
                'params' => [],
            ],
            'clientes_por_estado' => [
                'description' => 'Agrupar clientes por estado con conteos',
                'params' => [],
            ],
            'mis_clientes' => [
                'description' => 'Obtener los clientes asignados al usuario actual',
                'params' => ['estado', 'limite'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    // =====================================================================
    // IMPLEMENTACION DE ACCIONES
    // =====================================================================

    /**
     * Accion: Listar clientes con filtros
     */
    private function action_listar_clientes($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $estado_filtro = sanitize_text_field($parametros['estado'] ?? '');
        $tipo_filtro = sanitize_text_field($parametros['tipo'] ?? '');
        $etiquetas_filtro = $parametros['etiquetas'] ?? '';
        $limite = absint($parametros['limite'] ?? $this->get_setting('limite_resultados_por_defecto', 20));
        $pagina = absint($parametros['pagina'] ?? 1);
        $offset_valor = ($pagina - 1) * $limite;

        $condiciones_where = ['1=1'];
        $valores_preparar = [];

        if (!empty($estado_filtro)) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparar[] = $estado_filtro;
        }

        if (!empty($tipo_filtro)) {
            $condiciones_where[] = 'tipo = %s';
            $valores_preparar[] = $tipo_filtro;
        }

        if (!empty($etiquetas_filtro)) {
            $etiqueta_buscar = sanitize_text_field($etiquetas_filtro);
            $condiciones_where[] = 'etiquetas LIKE %s';
            $valores_preparar[] = '%' . $wpdb->esc_like($etiqueta_buscar) . '%';
        }

        $sql_where = implode(' AND ', $condiciones_where);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_clientes WHERE $sql_where";
        if (!empty($valores_preparar)) {
            $total_clientes = (int) $wpdb->get_var($wpdb->prepare($sql_count, ...$valores_preparar));
        } else {
            $total_clientes = (int) $wpdb->get_var($sql_count);
        }

        // Obtener resultados
        $sql_select = "SELECT * FROM $tabla_clientes WHERE $sql_where ORDER BY updated_at DESC LIMIT %d OFFSET %d";
        $valores_preparar[] = $limite;
        $valores_preparar[] = $offset_valor;

        $clientes_encontrados = $wpdb->get_results($wpdb->prepare($sql_select, ...$valores_preparar));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $clientes_encontrados);

        return [
            'success' => true,
            'total' => $total_clientes,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total_clientes / $limite),
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Se encontraron %d clientes.', 'flavor-chat-ia'),
                $total_clientes
            ),
        ];
    }

    /**
     * Accion: Ver detalle de un cliente
     */
    private function action_ver_cliente($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        $cliente_id = absint($parametros['cliente_id'] ?? 0);

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        $cliente_registro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_registro) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Obtener notas recientes
        $notas_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as autor_nombre
             FROM $tabla_notas n
             LEFT JOIN {$wpdb->users} u ON n.autor_id = u.ID
             WHERE n.cliente_id = %d
             ORDER BY n.created_at DESC
             LIMIT 10",
            $cliente_id
        ));

        $notas_formateadas = array_map(function ($nota) {
            return [
                'id' => (int) $nota->id,
                'tipo' => $nota->tipo,
                'contenido' => $nota->contenido,
                'estado' => $nota->estado,
                'fecha_seguimiento' => $nota->fecha_seguimiento,
                'autor' => $nota->autor_nombre ?? __('Sistema', 'flavor-chat-ia'),
                'created_at' => $nota->created_at,
            ];
        }, $notas_recientes);

        $cliente_detalle = $this->formatear_cliente($cliente_registro);
        $cliente_detalle['notas'] = $notas_formateadas;

        return [
            'success' => true,
            'cliente' => $cliente_detalle,
        ];
    }

    /**
     * Accion: Crear un nuevo cliente
     */
    private function action_crear_cliente($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para crear clientes.', 'flavor-chat-ia'),
            ];
        }

        $nombre_cliente = sanitize_text_field($parametros['nombre'] ?? '');

        if (empty($nombre_cliente)) {
            return [
                'success' => false,
                'error' => __('El nombre del cliente es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $etiquetas_json = null;
        if (!empty($parametros['etiquetas'])) {
            $etiquetas_valor = $parametros['etiquetas'];
            if (is_array($etiquetas_valor)) {
                $etiquetas_json = wp_json_encode(array_map('sanitize_text_field', $etiquetas_valor));
            } else {
                $etiquetas_json = wp_json_encode(array_map('trim', explode(',', sanitize_text_field($etiquetas_valor))));
            }
        }

        $datos_insertar = [
            'nombre' => $nombre_cliente,
            'email' => sanitize_email($parametros['email'] ?? ''),
            'telefono' => sanitize_text_field($parametros['telefono'] ?? ''),
            'empresa' => sanitize_text_field($parametros['empresa'] ?? ''),
            'cargo' => sanitize_text_field($parametros['cargo'] ?? ''),
            'direccion' => sanitize_textarea_field($parametros['direccion'] ?? ''),
            'tipo' => sanitize_text_field($parametros['tipo'] ?? 'particular'),
            'estado' => sanitize_text_field($parametros['estado'] ?? 'potencial'),
            'etiquetas' => $etiquetas_json,
            'valor_estimado' => floatval($parametros['valor_estimado'] ?? 0),
            'origen' => sanitize_text_field($parametros['origen'] ?? 'directo'),
            'asignado_a' => absint($parametros['asignado_a'] ?? 0) ?: null,
            'notas_count' => 0,
            'ultima_interaccion' => current_time('mysql'),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $formatos_columnas = [
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%d', '%s', '%s',
        ];

        // Si asignado_a es null, ajustar
        if ($datos_insertar['asignado_a'] === null) {
            $datos_insertar['asignado_a'] = 0;
        }

        $resultado_insercion = $wpdb->insert($tabla_clientes, $datos_insertar, $formatos_columnas);

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al crear el cliente.', 'flavor-chat-ia'),
            ];
        }

        $nuevo_cliente_id = $wpdb->insert_id;

        return [
            'success' => true,
            'cliente_id' => $nuevo_cliente_id,
            'mensaje' => sprintf(
                __('Cliente "%s" creado correctamente con ID %d.', 'flavor-chat-ia'),
                $nombre_cliente,
                $nuevo_cliente_id
            ),
        ];
    }

    /**
     * Accion: Actualizar un cliente existente
     */
    private function action_actualizar_cliente($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para actualizar clientes.', 'flavor-chat-ia'),
            ];
        }

        $cliente_id = absint($parametros['cliente_id'] ?? 0);

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        // Verificar que el cliente existe
        $cliente_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_existente) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $campos_actualizables = [
            'nombre', 'email', 'telefono', 'empresa', 'cargo',
            'direccion', 'tipo', 'estado', 'valor_estimado', 'origen', 'asignado_a',
        ];

        $datos_actualizar = [];
        $formatos_actualizar = [];

        foreach ($campos_actualizables as $campo_nombre) {
            if (isset($parametros[$campo_nombre])) {
                if ($campo_nombre === 'email') {
                    $datos_actualizar[$campo_nombre] = sanitize_email($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                } elseif ($campo_nombre === 'valor_estimado') {
                    $datos_actualizar[$campo_nombre] = floatval($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%f';
                } elseif ($campo_nombre === 'asignado_a') {
                    $datos_actualizar[$campo_nombre] = absint($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%d';
                } elseif ($campo_nombre === 'direccion') {
                    $datos_actualizar[$campo_nombre] = sanitize_textarea_field($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                } else {
                    $datos_actualizar[$campo_nombre] = sanitize_text_field($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                }
            }
        }

        // Manejar etiquetas por separado
        if (isset($parametros['etiquetas'])) {
            $etiquetas_valor = $parametros['etiquetas'];
            if (is_array($etiquetas_valor)) {
                $datos_actualizar['etiquetas'] = wp_json_encode(array_map('sanitize_text_field', $etiquetas_valor));
            } else {
                $datos_actualizar['etiquetas'] = wp_json_encode(array_map('trim', explode(',', sanitize_text_field($etiquetas_valor))));
            }
            $formatos_actualizar[] = '%s';
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => __('No se proporcionaron datos para actualizar.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizar['updated_at'] = current_time('mysql');
        $formatos_actualizar[] = '%s';

        $resultado_actualizacion = $wpdb->update(
            $tabla_clientes,
            $datos_actualizar,
            ['id' => $cliente_id],
            $formatos_actualizar,
            ['%d']
        );

        if ($resultado_actualizacion === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar el cliente.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'cliente_id' => $cliente_id,
            'campos_actualizados' => array_keys($datos_actualizar),
            'mensaje' => sprintf(
                __('Cliente #%d actualizado correctamente.', 'flavor-chat-ia'),
                $cliente_id
            ),
        ];
    }

    /**
     * Accion: Agregar nota/interaccion a un cliente
     */
    private function action_agregar_nota($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para agregar notas.', 'flavor-chat-ia'),
            ];
        }

        $cliente_id = absint($parametros['cliente_id'] ?? 0);
        $contenido_nota = sanitize_textarea_field($parametros['contenido'] ?? '');

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        if (empty($contenido_nota)) {
            return [
                'success' => false,
                'error' => __('El contenido de la nota es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        // Verificar que el cliente existe
        $cliente_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_existente) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $tipo_nota = sanitize_text_field($parametros['tipo'] ?? 'nota');
        $estado_nota = sanitize_text_field($parametros['estado'] ?? 'completada');
        $fecha_seguimiento_nota = null;

        if (!empty($parametros['fecha_seguimiento'])) {
            $fecha_seguimiento_nota = sanitize_text_field($parametros['fecha_seguimiento']);
        }

        $datos_nota = [
            'cliente_id' => $cliente_id,
            'autor_id' => get_current_user_id(),
            'tipo' => $tipo_nota,
            'contenido' => $contenido_nota,
            'estado' => $estado_nota,
            'fecha_seguimiento' => $fecha_seguimiento_nota,
            'created_at' => current_time('mysql'),
        ];

        $formatos_nota = ['%d', '%d', '%s', '%s', '%s', '%s', '%s'];

        $resultado_nota = $wpdb->insert($tabla_notas, $datos_nota, $formatos_nota);

        if ($resultado_nota === false) {
            return [
                'success' => false,
                'error' => __('Error al agregar la nota.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar conteo de notas y ultima interaccion en el cliente
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_clientes
             SET notas_count = notas_count + 1,
                 ultima_interaccion = %s,
                 updated_at = %s
             WHERE id = %d",
            current_time('mysql'),
            current_time('mysql'),
            $cliente_id
        ));

        $etiquetas_tipo = [
            'nota' => __('Nota', 'flavor-chat-ia'),
            'llamada' => __('Llamada', 'flavor-chat-ia'),
            'email' => __('Email', 'flavor-chat-ia'),
            'reunion' => __('Reunion', 'flavor-chat-ia'),
            'tarea' => __('Tarea', 'flavor-chat-ia'),
            'seguimiento' => __('Seguimiento', 'flavor-chat-ia'),
        ];

        $tipo_legible = $etiquetas_tipo[$tipo_nota] ?? $tipo_nota;

        return [
            'success' => true,
            'nota_id' => $wpdb->insert_id,
            'cliente_id' => $cliente_id,
            'mensaje' => sprintf(
                __('%s agregada al cliente #%d correctamente.', 'flavor-chat-ia'),
                $tipo_legible,
                $cliente_id
            ),
        ];
    }

    /**
     * Accion: Buscar clientes por nombre, email o empresa
     */
    private function action_buscar_clientes($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $termino_busqueda = sanitize_text_field($parametros['busqueda'] ?? '');
        $limite = absint($parametros['limite'] ?? 10);

        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error' => __('El termino de busqueda es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        $termino_like = '%' . $wpdb->esc_like($termino_busqueda) . '%';

        $clientes_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_clientes
             WHERE nombre LIKE %s
                OR email LIKE %s
                OR empresa LIKE %s
                OR telefono LIKE %s
             ORDER BY nombre ASC
             LIMIT %d",
            $termino_like,
            $termino_like,
            $termino_like,
            $termino_like,
            $limite
        ));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $clientes_encontrados);

        return [
            'success' => true,
            'total' => count($clientes_formateados),
            'busqueda' => $termino_busqueda,
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Se encontraron %d clientes para "%s".', 'flavor-chat-ia'),
                count($clientes_formateados),
                $termino_busqueda
            ),
        ];
    }

    /**
     * Accion: Estadisticas del CRM
     */
    private function action_estadisticas($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        // Total de clientes
        $total_clientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");

        // Por tipo
        $clientes_por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY tipo ORDER BY cantidad DESC"
        );

        $desglose_por_tipo = [];
        foreach ($clientes_por_tipo as $tipo_registro) {
            $desglose_por_tipo[$tipo_registro->tipo] = (int) $tipo_registro->cantidad;
        }

        // Por estado
        $clientes_por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY estado ORDER BY cantidad DESC"
        );

        $desglose_por_estado = [];
        foreach ($clientes_por_estado as $estado_registro) {
            $desglose_por_estado[$estado_registro->estado] = (int) $estado_registro->cantidad;
        }

        // Pipeline de valor (valor estimado por estado)
        $pipeline_valor = $wpdb->get_results(
            "SELECT estado, SUM(valor_estimado) as valor_total, COUNT(*) as cantidad
             FROM $tabla_clientes
             WHERE valor_estimado > 0
             GROUP BY estado
             ORDER BY valor_total DESC"
        );

        $desglose_pipeline = [];
        foreach ($pipeline_valor as $pipeline_registro) {
            $desglose_pipeline[$pipeline_registro->estado] = [
                'valor_total' => floatval($pipeline_registro->valor_total),
                'cantidad' => (int) $pipeline_registro->cantidad,
            ];
        }

        // Valor total del pipeline
        $valor_total_pipeline = (float) $wpdb->get_var(
            "SELECT IFNULL(SUM(valor_estimado), 0) FROM $tabla_clientes WHERE valor_estimado > 0"
        );

        // Por origen
        $clientes_por_origen = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY origen ORDER BY cantidad DESC"
        );

        $desglose_por_origen = [];
        foreach ($clientes_por_origen as $origen_registro) {
            $desglose_por_origen[$origen_registro->origen] = (int) $origen_registro->cantidad;
        }

        // Nuevos este mes
        $nuevos_este_mes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_clientes' => $total_clientes,
                'nuevos_este_mes' => $nuevos_este_mes,
                'por_tipo' => $desglose_por_tipo,
                'por_estado' => $desglose_por_estado,
                'por_origen' => $desglose_por_origen,
                'pipeline' => [
                    'valor_total' => $valor_total_pipeline,
                    'por_estado' => $desglose_pipeline,
                ],
            ],
            'mensaje' => sprintf(
                __('CRM: %d clientes totales, %d nuevos este mes, pipeline de %s.', 'flavor-chat-ia'),
                $total_clientes,
                $nuevos_este_mes,
                $this->format_price($valor_total_pipeline)
            ),
        ];
    }

    /**
     * Accion: Agrupar clientes por estado
     */
    private function action_clientes_por_estado($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $resultados_agrupados = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad, SUM(valor_estimado) as valor_total
             FROM $tabla_clientes
             GROUP BY estado
             ORDER BY FIELD(estado, 'activo', 'potencial', 'inactivo', 'perdido')"
        );

        $estados_desglose = [];
        foreach ($resultados_agrupados as $estado_registro) {
            $estados_desglose[] = [
                'estado' => $estado_registro->estado,
                'cantidad' => (int) $estado_registro->cantidad,
                'valor_total' => floatval($estado_registro->valor_total),
            ];
        }

        return [
            'success' => true,
            'estados' => $estados_desglose,
            'mensaje' => __('Desglose de clientes por estado obtenido correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Obtener clientes asignados al usuario actual
     */
    private function action_mis_clientes($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para ver tus clientes.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $estado_filtro = sanitize_text_field($parametros['estado'] ?? '');
        $limite = absint($parametros['limite'] ?? 20);

        $condiciones_where = ['asignado_a = %d'];
        $valores_preparar = [$usuario_actual_id];

        if (!empty($estado_filtro)) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparar[] = $estado_filtro;
        }

        $sql_where = implode(' AND ', $condiciones_where);
        $valores_preparar[] = $limite;

        $mis_clientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_clientes WHERE $sql_where ORDER BY ultima_interaccion DESC LIMIT %d",
            ...$valores_preparar
        ));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $mis_clientes);

        return [
            'success' => true,
            'total' => count($clientes_formateados),
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Tienes %d clientes asignados.', 'flavor-chat-ia'),
                count($clientes_formateados)
            ),
        ];
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Formatea un registro de cliente para la respuesta
     *
     * @param object $cliente_registro Registro de la base de datos
     * @return array
     */
    private function formatear_cliente($cliente_registro) {
        $usuario_asignado = null;
        if (!empty($cliente_registro->asignado_a)) {
            $datos_usuario = get_userdata($cliente_registro->asignado_a);
            $usuario_asignado = $datos_usuario ? $datos_usuario->display_name : null;
        }

        $usuario_creador = null;
        if (!empty($cliente_registro->created_by)) {
            $datos_creador = get_userdata($cliente_registro->created_by);
            $usuario_creador = $datos_creador ? $datos_creador->display_name : null;
        }

        $etiquetas_decodificadas = [];
        if (!empty($cliente_registro->etiquetas)) {
            $etiquetas_decodificadas = json_decode($cliente_registro->etiquetas, true);
            if (!is_array($etiquetas_decodificadas)) {
                $etiquetas_decodificadas = [];
            }
        }

        return [
            'id' => (int) $cliente_registro->id,
            'nombre' => $cliente_registro->nombre,
            'email' => $cliente_registro->email,
            'telefono' => $cliente_registro->telefono,
            'empresa' => $cliente_registro->empresa,
            'cargo' => $cliente_registro->cargo,
            'direccion' => $cliente_registro->direccion,
            'tipo' => $cliente_registro->tipo,
            'estado' => $cliente_registro->estado,
            'etiquetas' => $etiquetas_decodificadas,
            'valor_estimado' => floatval($cliente_registro->valor_estimado),
            'origen' => $cliente_registro->origen,
            'asignado_a' => [
                'id' => (int) $cliente_registro->asignado_a,
                'nombre' => $usuario_asignado,
            ],
            'notas_count' => (int) $cliente_registro->notas_count,
            'ultima_interaccion' => $cliente_registro->ultima_interaccion,
            'created_by' => [
                'id' => (int) $cliente_registro->created_by,
                'nombre' => $usuario_creador,
            ],
            'created_at' => $cliente_registro->created_at,
            'updated_at' => $cliente_registro->updated_at,
        ];
    }

    // =====================================================================
    // AI TOOL DEFINITIONS
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'clientes_listar',
                'description' => 'Lista clientes del CRM con filtros opcionales por estado, tipo o etiquetas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado del cliente',
                            'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Filtrar por tipo de cliente',
                            'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                        ],
                        'etiquetas' => [
                            'type' => 'string',
                            'description' => 'Filtrar por etiqueta (busqueda parcial)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Numero maximo de resultados (por defecto 20)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'clientes_buscar',
                'description' => 'Busca clientes por nombre, email, empresa o telefono',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Termino de busqueda (nombre, email, empresa o telefono)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default' => 10,
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name' => 'clientes_crear',
                'description' => 'Crea un nuevo cliente en el CRM',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre completo del cliente',
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente',
                        ],
                        'telefono' => [
                            'type' => 'string',
                            'description' => 'Telefono del cliente',
                        ],
                        'empresa' => [
                            'type' => 'string',
                            'description' => 'Empresa del cliente',
                        ],
                        'cargo' => [
                            'type' => 'string',
                            'description' => 'Cargo del cliente en su empresa',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de cliente',
                            'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado inicial del cliente',
                            'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                        ],
                        'origen' => [
                            'type' => 'string',
                            'description' => 'Origen del cliente',
                            'enum' => ['web', 'referido', 'redes', 'directo', 'otro'],
                        ],
                        'valor_estimado' => [
                            'type' => 'number',
                            'description' => 'Valor estimado del cliente en euros',
                        ],
                        'etiquetas' => [
                            'type' => 'string',
                            'description' => 'Etiquetas separadas por comas',
                        ],
                    ],
                    'required' => ['nombre'],
                ],
            ],
            [
                'name' => 'clientes_agregar_nota',
                'description' => 'Agrega una nota o interaccion a un cliente del CRM',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cliente_id' => [
                            'type' => 'integer',
                            'description' => 'ID del cliente',
                        ],
                        'contenido' => [
                            'type' => 'string',
                            'description' => 'Contenido de la nota o interaccion',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de interaccion',
                            'enum' => ['nota', 'llamada', 'email', 'reunion', 'tarea', 'seguimiento'],
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado de la nota/tarea',
                            'enum' => ['pendiente', 'completada', 'cancelada'],
                        ],
                        'fecha_seguimiento' => [
                            'type' => 'string',
                            'description' => 'Fecha de seguimiento en formato YYYY-MM-DD HH:MM:SS (opcional)',
                        ],
                    ],
                    'required' => ['cliente_id', 'contenido'],
                ],
            ],
        ];
    }

    // =====================================================================
    // KNOWLEDGE BASE & FAQs
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Gestion de Clientes / CRM**

El modulo de Clientes permite gestionar un CRM basico integrado en el sistema.
Incluye la gestion de contactos, notas de seguimiento e interacciones.

**Tipos de cliente:**
- Particular: persona fisica
- Empresa: persona juridica / empresa
- Autonomo: trabajador autonomo
- Administracion: entidad publica

**Estados del cliente:**
- Potencial: lead o contacto inicial
- Activo: cliente activo con relacion comercial
- Inactivo: cliente que ya no tiene actividad
- Perdido: oportunidad perdida

**Tipos de interaccion/nota:**
- Nota: nota general sobre el cliente
- Llamada: registro de llamada telefonica
- Email: registro de comunicacion por email
- Reunion: registro de reunion presencial o virtual
- Tarea: tarea pendiente relacionada con el cliente
- Seguimiento: recordatorio de seguimiento futuro

**Origenes de cliente:**
- Web: llego a traves de la web
- Referido: recomendado por otro cliente
- Redes: captado en redes sociales
- Directo: contacto directo
- Otro: otro origen

**Acciones disponibles:**
- "buscar clientes [termino]": busca por nombre, email o empresa
- "listar clientes": muestra todos los clientes con filtros
- "crear cliente [nombre]": crea un nuevo contacto
- "ver cliente [ID]": muestra detalle y notas de un cliente
- "agregar nota a cliente [ID]": registra una interaccion
- "mis clientes": muestra los clientes asignados al usuario
- "estadisticas clientes": muestra metricas del CRM
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo agregar un nuevo cliente al CRM?',
                'respuesta' => 'Puedes decirme "crear cliente" seguido del nombre. Tambien puedes proporcionar email, telefono, empresa y tipo de cliente.',
            ],
            [
                'pregunta' => 'Como registro una llamada con un cliente?',
                'respuesta' => 'Dime "agregar llamada al cliente [ID o nombre]" y el contenido de la nota. Quedara registrada como interaccion tipo llamada.',
            ],
            [
                'pregunta' => 'Como veo las estadisticas del CRM?',
                'respuesta' => 'Dime "estadisticas de clientes" y te mostrare el total de clientes, desglose por tipo y estado, y el pipeline de valor.',
            ],
            [
                'pregunta' => 'Puedo filtrar clientes por estado?',
                'respuesta' => 'Si, puedes pedirme "listar clientes activos", "listar clientes potenciales", etc. Tambien puedes filtrar por tipo o etiquetas.',
            ],
            [
                'pregunta' => 'Como se asigna un cliente a un comercial?',
                'respuesta' => 'Al crear o actualizar un cliente, puedes indicar el campo "asignado_a" con el ID del usuario de WordPress al que quieres asignarlo.',
            ],
        ];
    }

    // =====================================================================
    // WEB COMPONENTS
    // =====================================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'clientes_hero' => [
                'label' => __('Hero Clientes / CRM', 'flavor-chat-ia'),
                'description' => __('Seccion hero para la pagina de gestion de clientes', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Gestion de Clientes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('CRM integrado para gestionar tus contactos, notas e interacciones', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de Fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'clientes/hero',
            ],
            'clientes_grid' => [
                'label' => __('Grid de Clientes', 'flavor-chat-ia'),
                'description' => __('Listado de clientes del CRM en formato grid', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-id-alt',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de Seccion', 'flavor-chat-ia'),
                        'default' => __('Nuestros Clientes', 'flavor-chat-ia'),
                    ],
                    'estado_filtro' => [
                        'type' => 'select',
                        'label' => __('Filtrar por Estado', 'flavor-chat-ia'),
                        'options' => ['todos', 'activo', 'potencial', 'inactivo', 'perdido'],
                        'default' => 'todos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Numero maximo de clientes', 'flavor-chat-ia'),
                        'default' => 12,
                    ],
                ],
                'template' => 'clientes/clientes-grid',
            ],
            'clientes_estadisticas' => [
                'label' => __('Estadisticas CRM', 'flavor-chat-ia'),
                'description' => __('Dashboard de estadisticas del CRM', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de Seccion', 'flavor-chat-ia'),
                        'default' => __('Dashboard CRM', 'flavor-chat-ia'),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['cards', 'minimal'],
                        'default' => 'cards',
                    ],
                ],
                'template' => 'clientes/estadisticas',
            ],
        ];
    }
}
