<?php
/**
 * Vista: Campañas de Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
$campania_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

global $wpdb;

// Obtener listas para el formulario
$listas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1 ORDER BY nombre ASC"
);

// Obtener plantillas
$plantillas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_plantillas WHERE activa = 1 ORDER BY es_predefinida DESC, nombre ASC"
);

if ($action === 'new' || $action === 'edit'):
    $campania = null;
    if ($campania_id) {
        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias WHERE id = %d",
            $campania_id
        ));
    }
?>

<div class="wrap em-campanias-editor">
    <h1>
        <?php echo $campania ? __('Editar campaña', 'flavor-chat-ia') : __('Nueva campaña', 'flavor-chat-ia'); ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias'); ?>" class="page-title-action">
            <?php _e('Volver', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <form id="em-form-campania" class="em-form-campania">
        <input type="hidden" name="campania_id" value="<?php echo esc_attr($campania_id); ?>">

        <div class="em-editor-layout">
            <!-- Panel principal -->
            <div class="em-editor-main">
                <div class="em-form-section">
                    <label for="em-nombre"><?php _e('Nombre de la campaña', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="em-nombre" name="nombre" required
                           value="<?php echo esc_attr($campania->nombre ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Ej: Newsletter Marzo 2024', 'flavor-chat-ia'); ?>">
                </div>

                <div class="em-form-section">
                    <label for="em-asunto"><?php _e('Asunto del email', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="em-asunto" name="asunto" required
                           value="<?php echo esc_attr($campania->asunto ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Ej: Novedades de esta semana', 'flavor-chat-ia'); ?>">
                    <p class="description"><?php _e('Usa {{nombre}} para personalizar con el nombre del suscriptor.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="em-form-section">
                    <label for="em-preview-text"><?php _e('Texto de vista previa', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="em-preview-text" name="preview_text"
                           value="<?php echo esc_attr($campania->preview_text ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Texto que aparece después del asunto en la bandeja de entrada', 'flavor-chat-ia'); ?>">
                </div>

                <div class="em-form-section">
                    <label><?php _e('Contenido del email', 'flavor-chat-ia'); ?></label>

                    <div class="em-editor-tabs">
                        <button type="button" class="em-tab active" data-tab="visual"><?php _e('Visual', 'flavor-chat-ia'); ?></button>
                        <button type="button" class="em-tab" data-tab="html"><?php _e('HTML', 'flavor-chat-ia'); ?></button>
                    </div>

                    <div class="em-tab-content active" data-tab="visual">
                        <?php
                        wp_editor(
                            $campania->contenido_html ?? '',
                            'em_contenido_html',
                            [
                                'textarea_name' => 'contenido_html',
                                'textarea_rows' => 20,
                                'media_buttons' => true,
                                'teeny' => false,
                            ]
                        );
                        ?>
                    </div>

                    <div class="em-tab-content" data-tab="html" style="display:none;">
                        <textarea id="em-contenido-html-raw" name="contenido_html_raw" rows="20"><?php echo esc_textarea($campania->contenido_html ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="em-editor-sidebar">
                <div class="em-sidebar-section">
                    <h3><?php _e('Destinatarios', 'flavor-chat-ia'); ?></h3>
                    <p class="description"><?php _e('Selecciona las listas a las que enviar.', 'flavor-chat-ia'); ?></p>

                    <?php
                    $listas_seleccionadas = $campania ? json_decode($campania->listas_ids, true) : [];
                    ?>

                    <div class="em-listas-checkboxes">
                        <?php foreach ($listas as $lista): ?>
                            <label>
                                <input type="checkbox" name="listas_ids[]" value="<?php echo esc_attr($lista->id); ?>"
                                    <?php checked(in_array($lista->id, $listas_seleccionadas ?: [])); ?>>
                                <?php echo esc_html($lista->nombre); ?>
                                <span class="em-lista-count">(<?php echo number_format($lista->total_suscriptores); ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <p class="em-total-destinatarios">
                        <?php _e('Total estimado:', 'flavor-chat-ia'); ?>
                        <strong id="em-total-dest">0</strong>
                    </p>
                </div>

                <div class="em-sidebar-section">
                    <h3><?php _e('Plantilla', 'flavor-chat-ia'); ?></h3>
                    <select name="plantilla_id" id="em-plantilla">
                        <option value=""><?php _e('Sin plantilla', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($plantillas as $plantilla): ?>
                            <option value="<?php echo esc_attr($plantilla->id); ?>"
                                <?php selected($campania->plantilla_id ?? '', $plantilla->id); ?>>
                                <?php echo esc_html($plantilla->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button em-btn-cargar-plantilla"><?php _e('Cargar', 'flavor-chat-ia'); ?></button>
                </div>

                <div class="em-sidebar-section">
                    <h3><?php _e('Remitente', 'flavor-chat-ia'); ?></h3>

                    <label for="em-remitente-nombre"><?php _e('Nombre', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="em-remitente-nombre" name="remitente_nombre"
                           value="<?php echo esc_attr($campania->remitente_nombre ?? get_bloginfo('name')); ?>">

                    <label for="em-remitente-email"><?php _e('Email', 'flavor-chat-ia'); ?></label>
                    <input type="email" id="em-remitente-email" name="remitente_email"
                           value="<?php echo esc_attr($campania->remitente_email ?? get_option('admin_email')); ?>">
                </div>

                <div class="em-sidebar-section em-sidebar-actions">
                    <button type="submit" name="action" value="save" class="button button-large">
                        <?php _e('Guardar borrador', 'flavor-chat-ia'); ?>
                    </button>

                    <button type="button" class="button button-secondary em-btn-test">
                        <span class="dashicons dashicons-email"></span>
                        <?php _e('Enviar test', 'flavor-chat-ia'); ?>
                    </button>

                    <hr>

                    <button type="button" class="button button-primary button-large em-btn-enviar">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php _e('Enviar campaña', 'flavor-chat-ia'); ?>
                    </button>

                    <button type="button" class="button em-btn-programar">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e('Programar envío', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de programación -->
<div class="em-modal" id="em-modal-programar" style="display:none;">
    <div class="em-modal-content">
        <h3><?php _e('Programar envío', 'flavor-chat-ia'); ?></h3>
        <form id="em-form-programar">
            <label for="em-fecha-programada"><?php _e('Fecha y hora de envío', 'flavor-chat-ia'); ?></label>
            <input type="datetime-local" id="em-fecha-programada" name="fecha_programada" required
                   min="<?php echo date('Y-m-d\TH:i'); ?>">

            <div class="em-modal-actions">
                <button type="button" class="button em-modal-close"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Programar', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<?php else: // Lista de campañas ?>

<?php
$campanias = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_campanias ORDER BY creado_en DESC"
);
?>

<div class="wrap em-campanias">
    <h1>
        <?php _e('Campañas', 'flavor-chat-ia'); ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=new'); ?>" class="page-title-action">
            <?php _e('Nueva campaña', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <div class="em-tabs-filter">
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias'); ?>" class="<?php echo empty($_GET['estado']) ? 'active' : ''; ?>">
            <?php _e('Todas', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&estado=borrador'); ?>" class="<?php echo ($_GET['estado'] ?? '') === 'borrador' ? 'active' : ''; ?>">
            <?php _e('Borradores', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&estado=programada'); ?>" class="<?php echo ($_GET['estado'] ?? '') === 'programada' ? 'active' : ''; ?>">
            <?php _e('Programadas', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&estado=enviada'); ?>" class="<?php echo ($_GET['estado'] ?? '') === 'enviada' ? 'active' : ''; ?>">
            <?php _e('Enviadas', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php if (empty($campanias)): ?>
        <div class="em-empty-state">
            <span class="dashicons dashicons-email-alt"></span>
            <h2><?php _e('No hay campañas', 'flavor-chat-ia'); ?></h2>
            <p><?php _e('Crea tu primera campaña de email marketing.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=new'); ?>" class="button button-primary button-hero">
                <?php _e('Crear campaña', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped em-table-campanias">
            <thead>
                <tr>
                    <th class="column-nombre"><?php _e('Campaña', 'flavor-chat-ia'); ?></th>
                    <th class="column-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    <th class="column-enviados"><?php _e('Enviados', 'flavor-chat-ia'); ?></th>
                    <th class="column-apertura"><?php _e('Apertura', 'flavor-chat-ia'); ?></th>
                    <th class="column-clicks"><?php _e('Clicks', 'flavor-chat-ia'); ?></th>
                    <th class="column-fecha"><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campanias as $campania): ?>
                    <?php
                    $tasa_apertura = $campania->total_enviados > 0
                        ? round(($campania->total_abiertos / $campania->total_enviados) * 100, 1)
                        : 0;
                    $tasa_clicks = $campania->total_abiertos > 0
                        ? round(($campania->total_clicks / $campania->total_abiertos) * 100, 1)
                        : 0;
                    ?>
                    <tr data-id="<?php echo esc_attr($campania->id); ?>">
                        <td class="column-nombre">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=edit&id=' . $campania->id); ?>">
                                    <?php echo esc_html($campania->nombre); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=edit&id=' . $campania->id); ?>">
                                        <?php _e('Editar', 'flavor-chat-ia'); ?>
                                    </a> |
                                </span>
                                <?php if ($campania->estado === 'enviada'): ?>
                                    <span class="stats">
                                        <a href="<?php echo admin_url('admin.php?page=flavor-em-estadisticas&campania=' . $campania->id); ?>">
                                            <?php _e('Estadísticas', 'flavor-chat-ia'); ?>
                                        </a> |
                                    </span>
                                <?php endif; ?>
                                <span class="duplicate">
                                    <a href="#" class="em-duplicar" data-id="<?php echo esc_attr($campania->id); ?>">
                                        <?php _e('Duplicar', 'flavor-chat-ia'); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="em-eliminar" data-id="<?php echo esc_attr($campania->id); ?>">
                                        <?php _e('Eliminar', 'flavor-chat-ia'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-estado">
                            <span class="em-badge em-badge-<?php echo esc_attr($campania->estado); ?>">
                                <?php echo esc_html(ucfirst($campania->estado)); ?>
                            </span>
                        </td>
                        <td class="column-enviados">
                            <?php echo number_format($campania->total_enviados); ?>
                        </td>
                        <td class="column-apertura">
                            <div class="em-progress-bar">
                                <div class="em-progress-fill" style="width: <?php echo $tasa_apertura; ?>%;"></div>
                            </div>
                            <span><?php echo $tasa_apertura; ?>%</span>
                        </td>
                        <td class="column-clicks">
                            <div class="em-progress-bar">
                                <div class="em-progress-fill em-fill-green" style="width: <?php echo $tasa_clicks; ?>%;"></div>
                            </div>
                            <span><?php echo $tasa_clicks; ?>%</span>
                        </td>
                        <td class="column-fecha">
                            <?php
                            if ($campania->fecha_inicio_envio) {
                                echo date_i18n('d M Y H:i', strtotime($campania->fecha_inicio_envio));
                            } elseif ($campania->fecha_programada) {
                                echo '<span class="em-programada">' . date_i18n('d M Y H:i', strtotime($campania->fecha_programada)) . '</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php endif; ?>
