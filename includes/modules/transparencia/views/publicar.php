<?php
/**
 * Vista: Publicar Datos - Módulo Transparencia
 *
 * Formulario para crear/editar documentos públicos.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_datos = $wpdb->prefix . 'flavor_transparencia_documentos_publicos';
$tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_datos)) === $tabla_datos;

// Edición o nuevo
$documento_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$documento = null;
$es_edicion = false;

if ($documento_id && $tabla_existe) {
    $documento = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_datos WHERE id = %d",
        $documento_id
    ));
    $es_edicion = (bool) $documento;
}

// Categorías predefinidas
$categorias = [
    'presupuestos' => __('Presupuestos', 'flavor-chat-ia'),
    'contratos' => __('Contratos', 'flavor-chat-ia'),
    'subvenciones' => __('Subvenciones', 'flavor-chat-ia'),
    'normativa' => __('Normativa', 'flavor-chat-ia'),
    'actas' => __('Actas', 'flavor-chat-ia'),
    'personal' => __('Personal', 'flavor-chat-ia'),
    'indicadores' => __('Indicadores', 'flavor-chat-ia'),
    'patrimonio' => __('Patrimonio', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];

// Estados
$estados = [
    'borrador' => __('Borrador', 'flavor-chat-ia'),
    'pendiente' => __('Pendiente de Aprobación', 'flavor-chat-ia'),
    'publicado' => __('Publicado', 'flavor-chat-ia'),
    'archivado' => __('Archivado', 'flavor-chat-ia'),
];

// Procesar formulario
$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transparencia_publicar_nonce'])) {
    if (!wp_verify_nonce($_POST['transparencia_publicar_nonce'], 'transparencia_publicar')) {
        $mensaje = __('Error de seguridad. Recarga la página e inténtalo de nuevo.', 'flavor-chat-ia');
        $mensaje_tipo = 'error';
    } else {
        $datos_guardar = [
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'contenido' => wp_kses_post($_POST['contenido'] ?? ''),
            'categoria' => sanitize_key($_POST['categoria'] ?? ''),
            'subcategoria' => sanitize_text_field($_POST['subcategoria'] ?? ''),
            'estado' => sanitize_key($_POST['estado'] ?? 'borrador'),
            'periodo' => sanitize_text_field($_POST['periodo'] ?? ''),
            'fecha_documento' => sanitize_text_field($_POST['fecha_documento'] ?? ''),
            'entidad' => sanitize_text_field($_POST['entidad'] ?? ''),
            'departamento' => sanitize_text_field($_POST['departamento'] ?? ''),
            'importe' => !empty($_POST['importe']) ? floatval($_POST['importe']) : null,
            'autor_id' => get_current_user_id(),
        ];

        // Si se publica, establecer fecha de publicación
        if ($datos_guardar['estado'] === 'publicado' && (!$es_edicion || $documento->estado !== 'publicado')) {
            $datos_guardar['fecha_publicacion'] = current_time('mysql');
            $datos_guardar['aprobado_por'] = get_current_user_id();
        }

        // Manejar archivo subido
        if (!empty($_FILES['archivo']['name'])) {
            $archivo_permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'];
            $archivo_extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

            if (in_array($archivo_extension, $archivo_permitidos)) {
                $upload = wp_handle_upload($_FILES['archivo'], ['test_form' => false]);

                if (!isset($upload['error'])) {
                    $datos_guardar['archivo_url'] = $upload['url'];
                    $datos_guardar['archivo_nombre'] = sanitize_file_name($_FILES['archivo']['name']);
                    $datos_guardar['archivo_tipo'] = $upload['type'];
                    $datos_guardar['archivo_tamano'] = $_FILES['archivo']['size'];
                }
            }
        }

        if ($es_edicion) {
            $wpdb->update($tabla_datos, $datos_guardar, ['id' => $documento_id]);
            $mensaje = __('Documento actualizado correctamente.', 'flavor-chat-ia');
            $mensaje_tipo = 'success';
            // Recargar documento
            $documento = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_datos WHERE id = %d", $documento_id));
        } else {
            $wpdb->insert($tabla_datos, $datos_guardar);
            $nuevo_id = $wpdb->insert_id;
            if ($nuevo_id) {
                $mensaje = __('Documento creado correctamente.', 'flavor-chat-ia');
                $mensaje_tipo = 'success';
                // Redirigir a edición
                wp_redirect(admin_url('admin.php?page=transparencia-publicar&id=' . $nuevo_id . '&created=1'));
                exit;
            }
        }
    }
}

// Mensaje de creación
if (isset($_GET['created'])) {
    $mensaje = __('Documento creado correctamente.', 'flavor-chat-ia');
    $mensaje_tipo = 'success';
}
?>

<div class="wrap flavor-transparencia-publicar">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-upload"></span>
        <?php echo $es_edicion ? esc_html__('Editar Documento', 'flavor-chat-ia') : esc_html__('Publicar Documento', 'flavor-chat-ia'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-datos')); ?>" class="page-title-action">
        <?php esc_html_e('Volver a Datos', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (!$tabla_existe): ?>
        <div class="dm-alert dm-alert--warning">
            <span class="dashicons dashicons-warning"></span>
            <p><?php esc_html_e('Las tablas del módulo Transparencia no están creadas.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>

    <?php if ($mensaje): ?>
        <div class="dm-alert dm-alert--<?php echo $mensaje_tipo === 'success' ? 'success' : 'error'; ?>">
            <span class="dashicons dashicons-<?php echo $mensaje_tipo === 'success' ? 'yes-alt' : 'warning'; ?>"></span>
            <p><?php echo esc_html($mensaje); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="transparencia-form">
        <?php wp_nonce_field('transparencia_publicar', 'transparencia_publicar_nonce'); ?>

        <div class="dm-grid dm-grid--sidebar">
            <!-- Contenido principal -->
            <div class="dm-form-main">
                <div class="dm-card">
                    <div class="dm-card__header">
                        <h3><?php esc_html_e('Información del Documento', 'flavor-chat-ia'); ?></h3>
                    </div>
                    <div class="dm-card__body">
                        <div class="dm-form-group">
                            <label for="titulo"><?php esc_html_e('Título', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <input type="text" id="titulo" name="titulo" required class="regular-text widefat"
                                   value="<?php echo esc_attr($documento->titulo ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('Ej: Presupuesto General 2024', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="dm-form-group">
                            <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                            <textarea id="descripcion" name="descripcion" rows="3" class="widefat"
                                      placeholder="<?php esc_attr_e('Breve descripción del documento...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($documento->descripcion ?? ''); ?></textarea>
                        </div>

                        <div class="dm-form-group">
                            <label for="contenido"><?php esc_html_e('Contenido (opcional)', 'flavor-chat-ia'); ?></label>
                            <?php
                            wp_editor(
                                $documento->contenido ?? '',
                                'contenido',
                                [
                                    'textarea_name' => 'contenido',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                ]
                            );
                            ?>
                        </div>

                        <div class="dm-form-row">
                            <div class="dm-form-group">
                                <label for="archivo"><?php esc_html_e('Archivo adjunto', 'flavor-chat-ia'); ?></label>
                                <input type="file" id="archivo" name="archivo"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods">
                                <p class="description"><?php esc_html_e('Formatos permitidos: PDF, Word, Excel, ODF. Máx. 10MB', 'flavor-chat-ia'); ?></p>

                                <?php if ($es_edicion && $documento->archivo_url): ?>
                                    <div class="archivo-actual" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <a href="<?php echo esc_url($documento->archivo_url); ?>" target="_blank">
                                            <?php echo esc_html($documento->archivo_nombre); ?>
                                        </a>
                                        <span class="description">(<?php echo esc_html(size_format($documento->archivo_tamano)); ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dm-card">
                    <div class="dm-card__header">
                        <h3><?php esc_html_e('Detalles Adicionales', 'flavor-chat-ia'); ?></h3>
                    </div>
                    <div class="dm-card__body">
                        <div class="dm-form-row dm-form-row--3">
                            <div class="dm-form-group">
                                <label for="periodo"><?php esc_html_e('Período', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="periodo" name="periodo" class="regular-text"
                                       value="<?php echo esc_attr($documento->periodo ?? ''); ?>"
                                       placeholder="<?php esc_attr_e('Ej: 2024, Q1 2024, Enero 2024', 'flavor-chat-ia'); ?>">
                            </div>

                            <div class="dm-form-group">
                                <label for="fecha_documento"><?php esc_html_e('Fecha del documento', 'flavor-chat-ia'); ?></label>
                                <input type="date" id="fecha_documento" name="fecha_documento"
                                       value="<?php echo esc_attr($documento->fecha_documento ?? ''); ?>">
                            </div>

                            <div class="dm-form-group">
                                <label for="importe"><?php esc_html_e('Importe (si aplica)', 'flavor-chat-ia'); ?></label>
                                <input type="number" id="importe" name="importe" step="0.01" class="regular-text"
                                       value="<?php echo esc_attr($documento->importe ?? ''); ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="dm-form-row dm-form-row--2">
                            <div class="dm-form-group">
                                <label for="entidad"><?php esc_html_e('Entidad', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="entidad" name="entidad" class="regular-text"
                                       value="<?php echo esc_attr($documento->entidad ?? ''); ?>"
                                       placeholder="<?php esc_attr_e('Nombre de la entidad', 'flavor-chat-ia'); ?>">
                            </div>

                            <div class="dm-form-group">
                                <label for="departamento"><?php esc_html_e('Departamento', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="departamento" name="departamento" class="regular-text"
                                       value="<?php echo esc_attr($documento->departamento ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="dm-form-sidebar">
                <div class="dm-card">
                    <div class="dm-card__header">
                        <h3><?php esc_html_e('Publicación', 'flavor-chat-ia'); ?></h3>
                    </div>
                    <div class="dm-card__body">
                        <div class="dm-form-group">
                            <label for="estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                            <select id="estado" name="estado" class="widefat">
                                <?php foreach ($estados as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($documento->estado ?? 'borrador', $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="dm-form-group">
                            <label for="categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <select id="categoria" name="categoria" required class="widefat">
                                <option value=""><?php esc_html_e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($categorias as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($documento->categoria ?? '', $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="dm-form-group">
                            <label for="subcategoria"><?php esc_html_e('Subcategoría', 'flavor-chat-ia'); ?></label>
                            <input type="text" id="subcategoria" name="subcategoria" class="widefat"
                                   value="<?php echo esc_attr($documento->subcategoria ?? ''); ?>">
                        </div>

                        <?php if ($es_edicion && $documento->fecha_publicacion): ?>
                            <div class="dm-form-info" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <p><strong><?php esc_html_e('Publicado:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($documento->fecha_publicacion))); ?>
                                </p>
                                <p><strong><?php esc_html_e('Visitas:', 'flavor-chat-ia'); ?></strong> <?php echo number_format_i18n($documento->visitas ?? 0); ?></p>
                                <p><strong><?php esc_html_e('Descargas:', 'flavor-chat-ia'); ?></strong> <?php echo number_format_i18n($documento->descargas ?? 0); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="dm-card__footer" style="padding: 15px 20px; border-top: 1px solid #ddd; background: #f9f9f9;">
                        <button type="submit" class="button button-primary button-large widefat">
                            <span class="dashicons dashicons-<?php echo $es_edicion ? 'saved' : 'upload'; ?>"></span>
                            <?php echo $es_edicion ? esc_html__('Guardar Cambios', 'flavor-chat-ia') : esc_html__('Publicar Documento', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<style>
.flavor-transparencia-publicar .dm-grid--sidebar {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
}
.flavor-transparencia-publicar .dm-form-group {
    margin-bottom: 18px;
}
.flavor-transparencia-publicar .dm-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}
.flavor-transparencia-publicar .dm-form-group .required {
    color: #dc2626;
}
.flavor-transparencia-publicar .dm-form-row {
    display: grid;
    gap: 15px;
}
.flavor-transparencia-publicar .dm-form-row--2 {
    grid-template-columns: 1fr 1fr;
}
.flavor-transparencia-publicar .dm-form-row--3 {
    grid-template-columns: 1fr 1fr 1fr;
}
.flavor-transparencia-publicar .dm-card__footer .button .dashicons {
    margin-right: 6px;
    vertical-align: middle;
    margin-top: -2px;
}
@media (max-width: 960px) {
    .flavor-transparencia-publicar .dm-grid--sidebar {
        grid-template-columns: 1fr;
    }
    .flavor-transparencia-publicar .dm-form-row--3 {
        grid-template-columns: 1fr;
    }
}
</style>
