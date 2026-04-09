<?php
/**
 * Vista: Gestión de Empresas/Contactos (Admin)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

// Parámetros de filtrado
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$busqueda_termino = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
$limite_por_pagina = 20;
$offset_consulta = ($pagina_actual - 1) * $limite_por_pagina;

// Construir consulta
$condiciones_where = '1=1';
$valores_parametros = [];

if (!empty($estado_filtro)) {
    $condiciones_where .= ' AND estado = %s';
    $valores_parametros[] = $estado_filtro;
}

if (!empty($busqueda_termino)) {
    $patron_busqueda = '%' . $wpdb->esc_like($busqueda_termino) . '%';
    $condiciones_where .= ' AND (nombre LIKE %s OR email LIKE %s OR empresa LIKE %s OR asunto LIKE %s)';
    $valores_parametros = array_merge($valores_parametros, [$patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda]);
}

// Contar total
$consulta_total = "SELECT COUNT(*) FROM $tabla_contactos WHERE $condiciones_where";
if (!empty($valores_parametros)) {
    $total_contactos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
} else {
    $total_contactos = (int) $wpdb->get_var($consulta_total);
}

// Obtener contactos
$consulta_contactos = "SELECT * FROM $tabla_contactos WHERE $condiciones_where ORDER BY created_at DESC LIMIT %d OFFSET %d";
$parametros_finales = array_merge($valores_parametros, [$limite_por_pagina, $offset_consulta]);
$lista_contactos = $wpdb->get_results($wpdb->prepare($consulta_contactos, $parametros_finales), ARRAY_A);

// Calcular paginación
$total_paginas = ceil($total_contactos / $limite_por_pagina);

// Contar por estado
$conteo_estados = $wpdb->get_results(
    "SELECT estado, COUNT(*) as cantidad FROM $tabla_contactos GROUP BY estado",
    OBJECT_K
);
?>

<div class="wrap flavor-empresarial-contactos">
    <!-- Filtros -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="flavor-empresarial-empresas">

                <select name="estado">
                    <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="nuevo" <?php selected($estado_filtro, 'nuevo'); ?>>
                        <?php esc_html_e('Nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        (<?php echo esc_html($conteo_estados['nuevo']->cantidad ?? 0); ?>)
                    </option>
                    <option value="leido" <?php selected($estado_filtro, 'leido'); ?>>
                        <?php esc_html_e('Leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        (<?php echo esc_html($conteo_estados['leido']->cantidad ?? 0); ?>)
                    </option>
                    <option value="respondido" <?php selected($estado_filtro, 'respondido'); ?>>
                        <?php esc_html_e('Respondidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        (<?php echo esc_html($conteo_estados['respondido']->cantidad ?? 0); ?>)
                    </option>
                    <option value="archivado" <?php selected($estado_filtro, 'archivado'); ?>>
                        <?php esc_html_e('Archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        (<?php echo esc_html($conteo_estados['archivado']->cantidad ?? 0); ?>)
                    </option>
                </select>

                <input type="submit" class="button" value="<?php esc_attr_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </form>
        </div>

        <div class="alignright">
            <form method="get" action="">
                <input type="hidden" name="page" value="flavor-empresarial-empresas">
                <input type="search" name="s" value="<?php echo esc_attr($busqueda_termino); ?>"
                       placeholder="<?php esc_attr_e('Buscar contactos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <input type="submit" class="button" value="<?php esc_attr_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </form>
        </div>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(
                    esc_html(_n('%s contacto', '%s contactos', $total_contactos, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                    number_format_i18n($total_contactos)
                ); ?>
            </span>
        </div>
    </div>

    <!-- Tabla de contactos -->
    <?php if (!empty($lista_contactos)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-nombre"><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-empresa"><?php esc_html_e('Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-asunto"><?php esc_html_e('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-origen"><?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-fecha"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th scope="col" class="column-acciones"><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista_contactos as $contacto): ?>
                    <tr class="contacto-row estado-<?php echo esc_attr($contacto['estado']); ?>"
                        data-contacto-id="<?php echo esc_attr($contacto['id']); ?>">
                        <td class="column-nombre">
                            <strong>
                                <a href="#" class="ver-contacto" data-id="<?php echo esc_attr($contacto['id']); ?>">
                                    <?php echo esc_html($contacto['nombre']); ?>
                                </a>
                            </strong>
                            <br>
                            <span class="email"><?php echo esc_html($contacto['email']); ?></span>
                            <?php if (!empty($contacto['telefono'])): ?>
                                <br><span class="telefono"><?php echo esc_html($contacto['telefono']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-empresa">
                            <?php echo esc_html($contacto['empresa'] ?: '-'); ?>
                        </td>
                        <td class="column-asunto">
                            <?php echo esc_html($contacto['asunto'] ?: __('Sin asunto', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </td>
                        <td class="column-origen">
                            <span class="origen-badge origen-<?php echo esc_attr($contacto['origen']); ?>">
                                <?php echo esc_html(ucfirst($contacto['origen'])); ?>
                            </span>
                        </td>
                        <td class="column-estado">
                            <span class="estado-badge estado-<?php echo esc_attr($contacto['estado']); ?>">
                                <?php echo esc_html(ucfirst($contacto['estado'])); ?>
                            </span>
                        </td>
                        <td class="column-fecha">
                            <span title="<?php echo esc_attr($contacto['created_at']); ?>">
                                <?php echo esc_html(date_i18n('j M Y H:i', strtotime($contacto['created_at']))); ?>
                            </span>
                        </td>
                        <td class="column-acciones">
                            <button type="button" class="button button-small ver-contacto"
                                    data-id="<?php echo esc_attr($contacto['id']); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <?php if ($contacto['estado'] !== 'respondido'): ?>
                                <button type="button" class="button button-small marcar-respondido"
                                        data-id="<?php echo esc_attr($contacto['id']); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                </button>
                            <?php endif; ?>
                            <?php if ($contacto['estado'] !== 'archivado'): ?>
                                <button type="button" class="button button-small archivar-contacto"
                                        data-id="<?php echo esc_attr($contacto['id']); ?>">
                                    <span class="dashicons dashicons-archive"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $url_base = add_query_arg([
                        'page' => 'flavor-empresarial-empresas',
                        'estado' => $estado_filtro,
                        's' => $busqueda_termino,
                    ], admin_url('admin.php'));

                    echo paginate_links([
                        'base' => $url_base . '%_%',
                        'format' => '&paged=%#%',
                        'current' => $pagina_actual,
                        'total' => $total_paginas,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="flavor-empty-state">
            <span class="dashicons dashicons-email-alt"></span>
            <h3><?php esc_html_e('No hay contactos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('No se encontraron contactos con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de detalle de contacto -->
<div id="modal-contacto" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2><?php esc_html_e('Detalle del Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="flavor-modal-close">&times;</button>
        </div>
        <div class="flavor-modal-body" id="contacto-detalle">
            <div class="flavor-loading">
                <span class="spinner is-active"></span>
            </div>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cerrar-modal">
                <?php esc_html_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button button-primary" id="btn-responder-contacto" style="display: none;">
                <?php esc_html_e('Marcar como Respondido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>
</div>

<style>
.flavor-empresarial-contactos .column-nombre { width: 20%; }
.flavor-empresarial-contactos .column-empresa { width: 15%; }
.flavor-empresarial-contactos .column-asunto { width: 20%; }
.flavor-empresarial-contactos .column-origen { width: 10%; }
.flavor-empresarial-contactos .column-estado { width: 10%; }
.flavor-empresarial-contactos .column-fecha { width: 12%; }
.flavor-empresarial-contactos .column-acciones { width: 13%; }

.contacto-row.estado-nuevo td:first-child {
    border-left: 3px solid #dba617;
}

.contacto-row .email,
.contacto-row .telefono {
    font-size: 12px;
    color: #646970;
}

.origen-badge,
.estado-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}

.origen-web { background: #e8f4fd; color: #2271b1; }
.origen-landing { background: #e7f5e7; color: #00a32a; }
.origen-popup { background: #fef3e7; color: #d63638; }

.estado-nuevo { background: #fff3cd; color: #856404; }
.estado-leido { background: #cce5ff; color: #004085; }
.estado-respondido { background: #d4edda; color: #155724; }
.estado-archivado { background: #f0f0f1; color: #646970; }

.column-acciones .button {
    padding: 2px 6px;
    min-height: 26px;
}

.column-acciones .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: middle;
}

.flavor-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
    margin-top: 20px;
}

.flavor-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #c3c4c7;
}

.flavor-empty-state h3 {
    margin: 15px 0 5px;
}

.flavor-empty-state p {
    color: #646970;
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
}

.flavor-modal-content {
    position: relative;
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.flavor-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-modal-header h2 {
    margin: 0;
    font-size: 16px;
}

.flavor-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    padding: 0;
    line-height: 1;
}

.flavor-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.flavor-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.flavor-loading {
    text-align: center;
    padding: 40px;
}

.contacto-detalle-info {
    display: grid;
    gap: 15px;
}

.contacto-detalle-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 10px;
}

.contacto-detalle-label {
    font-weight: 600;
    color: #1d2327;
}

.contacto-detalle-mensaje {
    margin-top: 15px;
    padding: 15px;
    background: #f7f7f7;
    border-radius: 6px;
}

.contacto-detalle-mensaje h4 {
    margin: 0 0 10px;
    font-size: 13px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ver contacto
    $('.ver-contacto').on('click', function(e) {
        e.preventDefault();
        var contactoId = $(this).data('id');

        $('#modal-contacto').show();
        $('#contacto-detalle').html('<div class="flavor-loading"><span class="spinner is-active"></span></div>');

        // Simular carga (en producción, llamar a AJAX)
        var $row = $('tr[data-contacto-id="' + contactoId + '"]');
        var nombre = $row.find('.column-nombre strong a').text();
        var email = $row.find('.email').text();
        var telefono = $row.find('.telefono').text() || '-';
        var empresa = $row.find('.column-empresa').text().trim() || '-';
        var asunto = $row.find('.column-asunto').text().trim();
        var estado = $row.find('.estado-badge').text().trim();
        var fecha = $row.find('.column-fecha span').attr('title');

        setTimeout(function() {
            var html = '<div class="contacto-detalle-info">';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Nombre:</span><span>' + nombre + '</span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Email:</span><span><a href="mailto:' + email + '">' + email + '</a></span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Teléfono:</span><span>' + telefono + '</span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Empresa:</span><span>' + empresa + '</span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Asunto:</span><span>' + asunto + '</span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Estado:</span><span class="estado-badge estado-' + estado.toLowerCase() + '">' + estado + '</span></div>';
            html += '<div class="contacto-detalle-row"><span class="contacto-detalle-label">Fecha:</span><span>' + fecha + '</span></div>';
            html += '</div>';

            $('#contacto-detalle').html(html);

            if (estado.toLowerCase() !== 'respondido') {
                $('#btn-responder-contacto').show().data('id', contactoId);
            } else {
                $('#btn-responder-contacto').hide();
            }
        }, 300);
    });

    // Cerrar modal
    $('.flavor-modal-close, #btn-cerrar-modal, .flavor-modal-overlay').on('click', function() {
        $('#modal-contacto').hide();
    });

    // Marcar como respondido
    $('.marcar-respondido, #btn-responder-contacto').on('click', function() {
        var contactoId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Marcar este contacto como respondido?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
            // En producción, llamar a AJAX
            $('tr[data-contacto-id="' + contactoId + '"] .estado-badge')
                .removeClass('estado-nuevo estado-leido')
                .addClass('estado-respondido')
                .text('Respondido');
            $('#modal-contacto').hide();
        }
    });

    // Archivar contacto
    $('.archivar-contacto').on('click', function() {
        var contactoId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Archivar este contacto?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
            // En producción, llamar a AJAX
            $('tr[data-contacto-id="' + contactoId + '"]').fadeOut();
        }
    });

    // ESC para cerrar modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#modal-contacto').hide();
        }
    });
});
</script>
