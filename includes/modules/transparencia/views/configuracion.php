<?php
/**
 * Vista Admin: Configuración del módulo Transparencia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion_actual = is_object($this) && method_exists($this, 'get_settings')
    ? (array) $this->get_settings()
    : [];

$defaults = [
    'disponible_app' => 'cliente',
    'permite_solicitudes_anonimas' => false,
    'dias_plazo_respuesta' => 30,
    'publicacion_automatica' => false,
    'requiere_aprobacion_publicacion' => true,
    'notificar_nuevas_solicitudes' => true,
    'email_notificaciones' => get_option('admin_email'),
    'categorias_habilitadas' => [
        'presupuestos', 'contratos', 'subvenciones', 'normativa',
        'actas', 'personal', 'indicadores', 'patrimonio',
    ],
    'mostrar_graficos' => true,
    'limite_documentos_por_pagina' => 12,
    'formatos_permitidos' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'],
    'tamano_maximo_archivo' => 10485760,
];

$configuracion_actual = wp_parse_args($configuracion_actual, $defaults);

$categorias_disponibles = [
    'presupuestos' => __('Presupuestos', 'flavor-chat-ia'),
    'contratos' => __('Contratos', 'flavor-chat-ia'),
    'subvenciones' => __('Subvenciones', 'flavor-chat-ia'),
    'normativa' => __('Normativa', 'flavor-chat-ia'),
    'actas' => __('Actas', 'flavor-chat-ia'),
    'personal' => __('Personal', 'flavor-chat-ia'),
    'indicadores' => __('Indicadores', 'flavor-chat-ia'),
    'patrimonio' => __('Patrimonio', 'flavor-chat-ia'),
];

$formatos_disponibles = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'csv', 'txt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transparencia_guardar_config'])) {
    check_admin_referer('transparencia_config', 'transparencia_nonce');

    $categorias = array_values(array_intersect(
        array_map('sanitize_key', (array) ($_POST['categorias_habilitadas'] ?? [])),
        array_keys($categorias_disponibles)
    ));

    $formatos = array_values(array_intersect(
        array_map('sanitize_key', (array) ($_POST['formatos_permitidos'] ?? [])),
        $formatos_disponibles
    ));

    if (empty($categorias)) {
        $categorias = $defaults['categorias_habilitadas'];
    }
    if (empty($formatos)) {
        $formatos = $defaults['formatos_permitidos'];
    }

    $limite_docs = absint($_POST['limite_documentos_por_pagina'] ?? $defaults['limite_documentos_por_pagina']);
    if ($limite_docs < 6) {
        $limite_docs = 6;
    }
    if ($limite_docs > 100) {
        $limite_docs = 100;
    }

    $tamano_mb = absint($_POST['tamano_maximo_archivo_mb'] ?? (int) floor(((int) $configuracion_actual['tamano_maximo_archivo']) / 1048576));
    if ($tamano_mb < 1) {
        $tamano_mb = 1;
    }
    if ($tamano_mb > 200) {
        $tamano_mb = 200;
    }

    $nueva_configuracion = [
        'disponible_app' => sanitize_key($_POST['disponible_app'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente',
        'permite_solicitudes_anonimas' => !empty($_POST['permite_solicitudes_anonimas']),
        'dias_plazo_respuesta' => max(1, min(90, absint($_POST['dias_plazo_respuesta'] ?? $defaults['dias_plazo_respuesta']))),
        'publicacion_automatica' => !empty($_POST['publicacion_automatica']),
        'requiere_aprobacion_publicacion' => !empty($_POST['requiere_aprobacion_publicacion']),
        'notificar_nuevas_solicitudes' => !empty($_POST['notificar_nuevas_solicitudes']),
        'email_notificaciones' => sanitize_email($_POST['email_notificaciones'] ?? $defaults['email_notificaciones']),
        'categorias_habilitadas' => $categorias,
        'mostrar_graficos' => !empty($_POST['mostrar_graficos']),
        'limite_documentos_por_pagina' => $limite_docs,
        'formatos_permitidos' => $formatos,
        'tamano_maximo_archivo' => $tamano_mb * 1048576,
    ];

    if (empty($nueva_configuracion['email_notificaciones'])) {
        $nueva_configuracion['email_notificaciones'] = get_option('admin_email');
    }

    update_option('flavor_chat_ia_module_transparencia', $nueva_configuracion);
    if (is_object($this) && method_exists($this, 'get_settings') && method_exists($this, 'init')) {
        // Recargar configuración en memoria del módulo sin depender de nueva request.
        $this->settings = $nueva_configuracion;
    }
    $configuracion_actual = $nueva_configuracion;

    echo '<div class="notice notice-success is-dismissible"><p>' .
        esc_html__('Configuración guardada correctamente.', 'flavor-chat-ia') .
        '</p></div>';
}

$tamano_actual_mb = max(1, (int) floor(((int) $configuracion_actual['tamano_maximo_archivo']) / 1048576));
?>

<div class="wrap flavor-admin-page">
    <h1><?php esc_html_e('Configuración de Transparencia', 'flavor-chat-ia'); ?></h1>
    <p class="description">
        <?php esc_html_e('Ajusta cómo se publican los datos, cómo se reciben solicitudes y qué formatos se permiten en el portal.', 'flavor-chat-ia'); ?>
    </p>

    <form method="post">
        <?php wp_nonce_field('transparencia_config', 'transparencia_nonce'); ?>
        <input type="hidden" name="transparencia_guardar_config" value="1">

        <h2><?php esc_html_e('Solicitudes de Información', 'flavor-chat-ia'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Plazo de respuesta (días hábiles)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" min="1" max="90" class="small-text" name="dias_plazo_respuesta" value="<?php echo esc_attr((int) $configuracion_actual['dias_plazo_respuesta']); ?>">
                    <p class="description"><?php esc_html_e('Se usa para calcular la fecha límite de cada solicitud.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Solicitudes anónimas', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="permite_solicitudes_anonimas" value="1" <?php checked(!empty($configuracion_actual['permite_solicitudes_anonimas'])); ?>>
                        <?php esc_html_e('Permitir solicitudes sin usuario autenticado.', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Notificaciones', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="notificar_nuevas_solicitudes" value="1" <?php checked(!empty($configuracion_actual['notificar_nuevas_solicitudes'])); ?>>
                        <?php esc_html_e('Enviar aviso al recibir nuevas solicitudes.', 'flavor-chat-ia'); ?>
                    </label>
                    <p style="margin-top:8px;">
                        <input type="email" class="regular-text" name="email_notificaciones" value="<?php echo esc_attr((string) $configuracion_actual['email_notificaciones']); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Publicación y Moderación', 'flavor-chat-ia'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Publicación automática', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="publicacion_automatica" value="1" <?php checked(!empty($configuracion_actual['publicacion_automatica'])); ?>>
                        <?php esc_html_e('Publicar documentos sin revisión manual.', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Aprobación de publicación', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="requiere_aprobacion_publicacion" value="1" <?php checked(!empty($configuracion_actual['requiere_aprobacion_publicacion'])); ?>>
                        <?php esc_html_e('Requerir aprobación antes de publicar.', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Contexto de disponibilidad', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="disponible_app">
                        <option value="cliente" <?php selected((string) $configuracion_actual['disponible_app'], 'cliente'); ?>><?php esc_html_e('Cliente', 'flavor-chat-ia'); ?></option>
                        <option value="admin" <?php selected((string) $configuracion_actual['disponible_app'], 'admin'); ?>><?php esc_html_e('Administración', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Documentos y Visualización', 'flavor-chat-ia'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Documentos por página', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" min="6" max="100" class="small-text" name="limite_documentos_por_pagina" value="<?php echo esc_attr((int) $configuracion_actual['limite_documentos_por_pagina']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Mostrar gráficos', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="mostrar_graficos" value="1" <?php checked(!empty($configuracion_actual['mostrar_graficos'])); ?>>
                        <?php esc_html_e('Activar gráficas de presupuesto y actividad.', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Tamaño máximo por archivo (MB)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" min="1" max="200" class="small-text" name="tamano_maximo_archivo_mb" value="<?php echo esc_attr($tamano_actual_mb); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Categorías habilitadas', 'flavor-chat-ia'); ?></th>
                <td>
                    <?php foreach ($categorias_disponibles as $categoria_key => $categoria_label) : ?>
                        <label style="display:inline-block; margin: 0 14px 8px 0;">
                            <input type="checkbox" name="categorias_habilitadas[]" value="<?php echo esc_attr($categoria_key); ?>" <?php checked(in_array($categoria_key, (array) $configuracion_actual['categorias_habilitadas'], true)); ?>>
                            <?php echo esc_html($categoria_label); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Formatos permitidos', 'flavor-chat-ia'); ?></th>
                <td>
                    <?php foreach ($formatos_disponibles as $formato) : ?>
                        <label style="display:inline-block; margin: 0 10px 8px 0; text-transform: uppercase;">
                            <input type="checkbox" name="formatos_permitidos[]" value="<?php echo esc_attr($formato); ?>" <?php checked(in_array($formato, (array) $configuracion_actual['formatos_permitidos'], true)); ?>>
                            <?php echo esc_html($formato); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Guardar configuración', 'flavor-chat-ia'); ?></button>
        </p>
    </form>
</div>
