<?php
/**
 * Vista: Gestión de Contratos/Proyectos (Admin)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

// Verificar si estamos en modo edición/nuevo
$accion = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'listar';
$proyecto_id = isset($_GET['proyecto_id']) ? absint($_GET['proyecto_id']) : 0;

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
    $condiciones_where .= ' AND (titulo LIKE %s OR cliente_nombre LIKE %s OR descripcion LIKE %s)';
    $valores_parametros = array_merge($valores_parametros, [$patron_busqueda, $patron_busqueda, $patron_busqueda]);
}

// Contar total
$consulta_total = "SELECT COUNT(*) FROM $tabla_proyectos WHERE $condiciones_where";
if (!empty($valores_parametros)) {
    $total_proyectos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
} else {
    $total_proyectos = (int) $wpdb->get_var($consulta_total);
}

// Obtener proyectos
$consulta_proyectos = "SELECT * FROM $tabla_proyectos WHERE $condiciones_where ORDER BY created_at DESC LIMIT %d OFFSET %d";
$parametros_finales = array_merge($valores_parametros, [$limite_por_pagina, $offset_consulta]);
$lista_proyectos = $wpdb->get_results($wpdb->prepare($consulta_proyectos, $parametros_finales), ARRAY_A);

// Calcular paginación
$total_paginas = ceil($total_proyectos / $limite_por_pagina);

// Contar por estado
$conteo_estados = $wpdb->get_results(
    "SELECT estado, COUNT(*) as cantidad FROM $tabla_proyectos GROUP BY estado",
    OBJECT_K
);

// Etiquetas de estado
$etiquetas_estado = [
    'propuesta'  => __('Propuesta', 'flavor-chat-ia'),
    'aprobado'   => __('Aprobado', 'flavor-chat-ia'),
    'en_curso'   => __('En Curso', 'flavor-chat-ia'),
    'completado' => __('Completado', 'flavor-chat-ia'),
    'cancelado'  => __('Cancelado', 'flavor-chat-ia'),
];
?>

<div class="wrap flavor-empresarial-contratos">
    <?php if ($accion === 'nuevo' || ($accion === 'editar' && $proyecto_id)): ?>
        <!-- Formulario de proyecto -->
        <?php
        $proyecto_editar = null;
        if ($accion === 'editar' && $proyecto_id) {
            $proyecto_editar = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $tabla_proyectos WHERE id = %d", $proyecto_id),
                ARRAY_A
            );
        }
        ?>
        <div class="flavor-form-container">
            <form id="form-proyecto" class="flavor-form" method="post">
                <?php wp_nonce_field('empresarial_proyecto_nonce', 'proyecto_nonce'); ?>
                <input type="hidden" name="proyecto_id" value="<?php echo esc_attr($proyecto_id); ?>">

                <div class="flavor-form-grid">
                    <div class="flavor-form-section">
                        <h3><?php esc_html_e('Información del Proyecto', 'flavor-chat-ia'); ?></h3>

                        <div class="flavor-form-row">
                            <label for="titulo"><?php esc_html_e('Título del Proyecto', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" id="titulo" name="titulo" required
                                   value="<?php echo esc_attr($proyecto_editar['titulo'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('Nombre del proyecto', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="flavor-form-row">
                            <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                            <textarea id="descripcion" name="descripcion" rows="4"
                                      placeholder="<?php esc_attr_e('Describe el alcance del proyecto', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($proyecto_editar['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="flavor-form-row-group">
                            <div class="flavor-form-row">
                                <label for="estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                                <select id="estado" name="estado">
                                    <?php foreach ($etiquetas_estado as $estado_valor => $estado_label): ?>
                                        <option value="<?php echo esc_attr($estado_valor); ?>"
                                            <?php selected($proyecto_editar['estado'] ?? 'propuesta', $estado_valor); ?>>
                                            <?php echo esc_html($estado_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flavor-form-row">
                                <label for="progreso"><?php esc_html_e('Progreso (%)', 'flavor-chat-ia'); ?></label>
                                <input type="number" id="progreso" name="progreso" min="0" max="100"
                                       value="<?php echo esc_attr($proyecto_editar['progreso'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="flavor-form-section">
                        <h3><?php esc_html_e('Cliente', 'flavor-chat-ia'); ?></h3>

                        <div class="flavor-form-row">
                            <label for="cliente_nombre"><?php esc_html_e('Nombre del Cliente', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" id="cliente_nombre" name="cliente_nombre" required
                                   value="<?php echo esc_attr($proyecto_editar['cliente_nombre'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('Nombre o razón social', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="flavor-form-row">
                            <label for="cliente_email"><?php esc_html_e('Email del Cliente', 'flavor-chat-ia'); ?></label>
                            <input type="email" id="cliente_email" name="cliente_email"
                                   value="<?php echo esc_attr($proyecto_editar['cliente_email'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('contacto@empresa.com', 'flavor-chat-ia'); ?>">
                        </div>
                    </div>

                    <div class="flavor-form-section">
                        <h3><?php esc_html_e('Presupuesto y Fechas', 'flavor-chat-ia'); ?></h3>

                        <div class="flavor-form-row">
                            <label for="presupuesto"><?php esc_html_e('Presupuesto (€)', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="presupuesto" name="presupuesto" min="0" step="0.01"
                                   value="<?php echo esc_attr($proyecto_editar['presupuesto'] ?? 0); ?>"
                                   placeholder="0.00">
                        </div>

                        <div class="flavor-form-row-group">
                            <div class="flavor-form-row">
                                <label for="fecha_inicio"><?php esc_html_e('Fecha de Inicio', 'flavor-chat-ia'); ?></label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio"
                                       value="<?php echo esc_attr($proyecto_editar['fecha_inicio'] ?? ''); ?>">
                            </div>

                            <div class="flavor-form-row">
                                <label for="fecha_entrega"><?php esc_html_e('Fecha de Entrega', 'flavor-chat-ia'); ?></label>
                                <input type="date" id="fecha_entrega" name="fecha_entrega"
                                       value="<?php echo esc_attr($proyecto_editar['fecha_entrega'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flavor-form-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-empresarial-contratos')); ?>" class="button">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <?php echo $proyecto_id ? esc_html__('Actualizar Proyecto', 'flavor-chat-ia') : esc_html__('Crear Proyecto', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- Lista de proyectos -->

        <!-- Filtros -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="flavor-empresarial-contratos">

                    <select name="estado">
                        <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($etiquetas_estado as $estado_valor => $estado_label): ?>
                            <option value="<?php echo esc_attr($estado_valor); ?>" <?php selected($estado_filtro, $estado_valor); ?>>
                                <?php echo esc_html($estado_label); ?>
                                (<?php echo esc_html($conteo_estados[$estado_valor]->cantidad ?? 0); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="submit" class="button" value="<?php esc_attr_e('Filtrar', 'flavor-chat-ia'); ?>">
                </form>
            </div>

            <div class="alignright">
                <form method="get" action="">
                    <input type="hidden" name="page" value="flavor-empresarial-contratos">
                    <input type="search" name="s" value="<?php echo esc_attr($busqueda_termino); ?>"
                           placeholder="<?php esc_attr_e('Buscar proyectos...', 'flavor-chat-ia'); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                </form>
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        esc_html(_n('%s proyecto', '%s proyectos', $total_proyectos, 'flavor-chat-ia')),
                        number_format_i18n($total_proyectos)
                    ); ?>
                </span>
            </div>
        </div>

        <!-- Tabla de proyectos -->
        <?php if (!empty($lista_proyectos)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-titulo"><?php esc_html_e('Proyecto', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-cliente"><?php esc_html_e('Cliente', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-presupuesto"><?php esc_html_e('Presupuesto', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-progreso"><?php esc_html_e('Progreso', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-fechas"><?php esc_html_e('Fechas', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="column-acciones"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_proyectos as $proyecto):
                        // Calcular días restantes
                        $dias_restantes = null;
                        $clase_urgencia = '';
                        if (!empty($proyecto['fecha_entrega']) && in_array($proyecto['estado'], ['aprobado', 'en_curso'], true)) {
                            $fecha_entrega = strtotime($proyecto['fecha_entrega']);
                            $hoy = strtotime(current_time('Y-m-d'));
                            $dias_restantes = ($fecha_entrega - $hoy) / 86400;
                            if ($dias_restantes < 0) {
                                $clase_urgencia = 'urgencia-vencido';
                            } elseif ($dias_restantes <= 7) {
                                $clase_urgencia = 'urgencia-pronto';
                            }
                        }
                    ?>
                        <tr class="proyecto-row estado-<?php echo esc_attr($proyecto['estado']); ?> <?php echo esc_attr($clase_urgencia); ?>"
                            data-proyecto-id="<?php echo esc_attr($proyecto['id']); ?>">
                            <td class="column-titulo">
                                <strong>
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'editar', 'proyecto_id' => $proyecto['id']], admin_url('admin.php?page=flavor-empresarial-contratos'))); ?>">
                                        <?php echo esc_html($proyecto['titulo']); ?>
                                    </a>
                                </strong>
                                <?php if (!empty($proyecto['descripcion'])): ?>
                                    <br><span class="descripcion"><?php echo esc_html(wp_trim_words($proyecto['descripcion'], 10)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-cliente">
                                <strong><?php echo esc_html($proyecto['cliente_nombre']); ?></strong>
                                <?php if (!empty($proyecto['cliente_email'])): ?>
                                    <br><span class="email"><?php echo esc_html($proyecto['cliente_email']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-presupuesto">
                                <strong><?php echo esc_html(number_format($proyecto['presupuesto'], 2, ',', '.')); ?> €</strong>
                            </td>
                            <td class="column-progreso">
                                <div class="progreso-wrapper">
                                    <div class="progreso-bar">
                                        <div class="progreso-fill" style="width: <?php echo esc_attr($proyecto['progreso']); ?>%;"></div>
                                    </div>
                                    <span class="progreso-texto"><?php echo esc_html($proyecto['progreso']); ?>%</span>
                                </div>
                            </td>
                            <td class="column-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($proyecto['estado']); ?>">
                                    <?php echo esc_html($etiquetas_estado[$proyecto['estado']] ?? $proyecto['estado']); ?>
                                </span>
                            </td>
                            <td class="column-fechas">
                                <?php if (!empty($proyecto['fecha_inicio'])): ?>
                                    <span class="fecha-inicio">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <?php echo esc_html(date_i18n('j M Y', strtotime($proyecto['fecha_inicio']))); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($proyecto['fecha_entrega'])): ?>
                                    <span class="fecha-entrega <?php echo esc_attr($clase_urgencia); ?>">
                                        <span class="dashicons dashicons-flag"></span>
                                        <?php echo esc_html(date_i18n('j M Y', strtotime($proyecto['fecha_entrega']))); ?>
                                        <?php if ($dias_restantes !== null && $dias_restantes < 0): ?>
                                            <span class="dias-vencido">(<?php printf(esc_html__('%d días vencido', 'flavor-chat-ia'), abs($dias_restantes)); ?>)</span>
                                        <?php elseif ($dias_restantes !== null && $dias_restantes <= 7): ?>
                                            <span class="dias-restantes">(<?php printf(esc_html(_n('%d día', '%d días', $dias_restantes, 'flavor-chat-ia')), $dias_restantes); ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-acciones">
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'editar', 'proyecto_id' => $proyecto['id']], admin_url('admin.php?page=flavor-empresarial-contratos'))); ?>"
                                   class="button button-small">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <?php if ($proyecto['estado'] !== 'completado'): ?>
                                    <button type="button" class="button button-small completar-proyecto"
                                            data-id="<?php echo esc_attr($proyecto['id']); ?>">
                                        <span class="dashicons dashicons-yes-alt"></span>
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
                            'page' => 'flavor-empresarial-contratos',
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
                <span class="dashicons dashicons-portfolio"></span>
                <h3><?php esc_html_e('No hay proyectos', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('No se encontraron proyectos con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-empresarial-contratos&action=nuevo')); ?>" class="button button-primary">
                    <?php esc_html_e('Crear Primer Proyecto', 'flavor-chat-ia'); ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.flavor-empresarial-contratos .column-titulo { width: 22%; }
.flavor-empresarial-contratos .column-cliente { width: 18%; }
.flavor-empresarial-contratos .column-presupuesto { width: 12%; }
.flavor-empresarial-contratos .column-progreso { width: 12%; }
.flavor-empresarial-contratos .column-estado { width: 10%; }
.flavor-empresarial-contratos .column-fechas { width: 16%; }
.flavor-empresarial-contratos .column-acciones { width: 10%; }

.proyecto-row .descripcion,
.proyecto-row .email {
    font-size: 12px;
    color: #646970;
}

.proyecto-row.urgencia-vencido {
    background-color: #fff5f5 !important;
}

.proyecto-row.urgencia-pronto {
    background-color: #fffbf0 !important;
}

.progreso-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.progreso-bar {
    flex: 1;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.progreso-fill {
    height: 100%;
    background: #2271b1;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progreso-texto {
    font-size: 12px;
    color: #646970;
    min-width: 35px;
}

.estado-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.estado-propuesta { background: #fff3cd; color: #856404; }
.estado-aprobado { background: #cce5ff; color: #004085; }
.estado-en_curso { background: #d4edda; color: #155724; }
.estado-completado { background: #d1ecf1; color: #0c5460; }
.estado-cancelado { background: #f8d7da; color: #721c24; }

.fecha-inicio,
.fecha-entrega {
    display: block;
    font-size: 12px;
    margin-bottom: 3px;
}

.fecha-inicio .dashicons,
.fecha-entrega .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
    vertical-align: middle;
    margin-right: 3px;
}

.fecha-entrega.urgencia-vencido {
    color: #d63638;
    font-weight: 600;
}

.fecha-entrega.urgencia-pronto {
    color: #dba617;
}

.dias-vencido,
.dias-restantes {
    font-size: 10px;
    font-weight: normal;
}

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

/* Formulario */
.flavor-form-container {
    background: #fff;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    max-width: 900px;
}

.flavor-form-grid {
    display: grid;
    gap: 30px;
}

.flavor-form-section h3 {
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 14px;
}

.flavor-form-row {
    margin-bottom: 20px;
}

.flavor-form-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.flavor-form-row input[type="text"],
.flavor-form-row input[type="email"],
.flavor-form-row input[type="number"],
.flavor-form-row input[type="date"],
.flavor-form-row textarea,
.flavor-form-row select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
}

.flavor-form-row textarea {
    resize: vertical;
}

.flavor-form-row-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.flavor-form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
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
    margin-bottom: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Completar proyecto
    $('.completar-proyecto').on('click', function() {
        var proyectoId = $(this).data('id');
        if (confirm('<?php echo esc_js(__('¿Marcar este proyecto como completado?', 'flavor-chat-ia')); ?>')) {
            // En producción, llamar a AJAX
            var $row = $('tr[data-proyecto-id="' + proyectoId + '"]');
            $row.find('.estado-badge')
                .removeClass('estado-propuesta estado-aprobado estado-en_curso')
                .addClass('estado-completado')
                .text('<?php echo esc_js(__('Completado', 'flavor-chat-ia')); ?>');
            $row.find('.progreso-fill').css('width', '100%');
            $row.find('.progreso-texto').text('100%');
            $(this).remove();
        }
    });

    // Auto-actualizar progreso cuando cambia el estado
    $('#estado').on('change', function() {
        if ($(this).val() === 'completado') {
            $('#progreso').val(100);
        }
    });
});
</script>
