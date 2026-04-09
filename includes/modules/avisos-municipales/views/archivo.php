<?php
/**
 * Vista de Archivo de Avisos Municipales
 *
 * Variables disponibles:
 *   $avisos - Array de avisos archivados
 *   $stats - Estadísticas del archivo
 *   $filtros - Filtros aplicados
 *   $paginacion - Datos de paginación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$prioridad_classes = [
    'urgente' => 'dm-badge--error',
    'alta'    => 'dm-badge--warning',
    'media'   => 'dm-badge--info',
    'baja'    => 'dm-badge--success',
];
?>

<div class="wrap dm-dashboard">

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-archive"></span>
                <?php esc_html_e('Archivo de Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Historial de avisos expirados y archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-dashboard')); ?>" class="dm-btn dm-btn--secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Estadísticas del archivo -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--compact">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-archive"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['total_archivados']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--compact">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['este_mes']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Expirados este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--compact">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['total_visualizaciones']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Visualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--compact">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['total_confirmaciones']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Confirmaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="dm-card" style="margin-bottom: 24px;">
        <div class="dm-card__body">
            <form method="get" action="" class="dm-filters">
                <input type="hidden" name="page" value="avisos-archivo" />

                <div class="dm-filters__group">
                    <label for="filtro_categoria"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="categoria" id="filtro_categoria">
                        <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat->nombre); ?>" <?php selected($filtros['categoria'], $cat->nombre); ?>>
                            <?php echo esc_html($cat->nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dm-filters__group">
                    <label for="filtro_anio"><?php esc_html_e('Año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="anio" id="filtro_anio">
                        <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php
                        $anio_actual = (int) date('Y');
                        for ($a = $anio_actual; $a >= $anio_actual - 5; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php selected($filtros['anio'], $a); ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="dm-filters__group">
                    <label for="filtro_buscar"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="buscar" id="filtro_buscar" value="<?php echo esc_attr($filtros['buscar']); ?>" placeholder="<?php esc_attr_e('Título...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" />
                </div>

                <div class="dm-filters__actions">
                    <button type="submit" class="dm-btn dm-btn--primary dm-btn--sm">
                        <span class="dashicons dashicons-search"></span> <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-archivo')); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                        <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de avisos archivados -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h2 class="dm-card__title">
                <?php esc_html_e('Avisos Archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <?php if ($paginacion['total'] > 0): ?>
                <span class="dm-badge dm-badge--muted"><?php echo number_format_i18n($paginacion['total']); ?></span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="dm-card__body dm-card__body--no-padding">
            <?php if (!empty($avisos)): ?>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Expirado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th class="dm-table__actions"><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avisos as $aviso): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($aviso->titulo); ?></strong>
                        </td>
                        <td>
                            <?php if ($aviso->categoria): ?>
                            <span class="dm-badge dm-badge--muted"><?php echo esc_html($aviso->categoria); ?></span>
                            <?php else: ?>
                            <span class="dm-text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dm-badge <?php echo esc_attr($prioridad_classes[$aviso->prioridad] ?? 'dm-badge--info'); ?>">
                                <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="dm-text-muted"><?php echo esc_html(date_i18n('d/m/Y', strtotime($aviso->fecha_publicacion ?: $aviso->created_at))); ?></span>
                        </td>
                        <td>
                            <?php if ($aviso->fecha_expiracion): ?>
                            <span class="dm-text-muted"><?php echo esc_html(date_i18n('d/m/Y', strtotime($aviso->fecha_expiracion))); ?></span>
                            <?php else: ?>
                            <span class="dm-text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dm-text-muted"><?php echo number_format_i18n($aviso->total_visualizaciones); ?></span>
                        </td>
                        <td class="dm-table__actions">
                            <button type="button" class="dm-btn dm-btn--ghost dm-btn--sm am-ver-aviso"
                                    data-id="<?php echo esc_attr($aviso->id); ?>"
                                    data-titulo="<?php echo esc_attr($aviso->titulo); ?>"
                                    data-contenido="<?php echo esc_attr(wp_trim_words($aviso->contenido, 50)); ?>"
                                    data-categoria="<?php echo esc_attr($aviso->categoria); ?>"
                                    data-prioridad="<?php echo esc_attr($aviso->prioridad); ?>"
                                    data-fecha="<?php echo esc_attr(date_i18n('d/m/Y H:i', strtotime($aviso->created_at))); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo&republicar=' . $aviso->id)); ?>"
                               class="dm-btn dm-btn--primary dm-btn--sm"
                               title="<?php esc_attr_e('Republicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-controls-repeat"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($paginacion['total_paginas'] > 1): ?>
            <div class="dm-pagination">
                <?php if ($paginacion['pagina'] > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $paginacion['pagina'] - 1)); ?>" class="dm-pagination__btn">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <?php endif; ?>

                <span class="dm-pagination__info">
                    <?php printf(
                        __('Página %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $paginacion['pagina'],
                        $paginacion['total_paginas']
                    ); ?>
                </span>

                <?php if ($paginacion['pagina'] < $paginacion['total_paginas']): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $paginacion['pagina'] + 1)); ?>" class="dm-pagination__btn">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="dm-empty-state">
                <span class="dashicons dashicons-archive"></span>
                <p><?php esc_html_e('No hay avisos archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php if (!empty($filtros['categoria']) || !empty($filtros['anio']) || !empty($filtros['buscar'])): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-archivo')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Quitar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modal ver aviso -->
<div id="modal-ver-aviso" class="dm-modal" style="display:none;">
    <div class="dm-modal__overlay"></div>
    <div class="dm-modal__content">
        <div class="dm-modal__header">
            <h3 class="dm-modal__title"><?php esc_html_e('Detalle del Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button type="button" class="dm-modal__close" id="cerrar-modal-aviso">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="dm-modal__body">
            <div id="contenido-aviso"></div>
        </div>
        <div class="dm-modal__footer">
            <button type="button" class="dm-btn dm-btn--secondary" id="btn-cerrar-modal"><?php esc_html_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <a href="#" id="btn-republicar-aviso" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-controls-repeat"></span> <?php esc_html_e('Republicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para esta vista */
.dm-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}

.dm-filters__group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dm-filters__group label {
    font-size: 12px;
    font-weight: 500;
    color: var(--dm-text-muted);
}

.dm-filters__group select,
.dm-filters__group input {
    min-width: 150px;
    padding: 8px 12px;
    border: 1px solid var(--dm-border);
    border-radius: var(--dm-radius-sm);
    font-size: 13px;
}

.dm-filters__actions {
    display: flex;
    gap: 8px;
}

.dm-table {
    width: 100%;
    border-collapse: collapse;
}

.dm-table th,
.dm-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--dm-border);
    font-size: 13px;
}

.dm-table th {
    background: var(--dm-bg);
    font-weight: 600;
    color: var(--dm-text-secondary);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.dm-table__actions {
    width: 100px;
    text-align: right;
}

.dm-table__actions .dm-btn {
    margin-left: 4px;
}

.dm-text-muted {
    color: var(--dm-text-muted);
}

.dm-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-top: 1px solid var(--dm-border);
}

.dm-pagination__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--dm-bg);
    border: 1px solid var(--dm-border);
    border-radius: var(--dm-radius-sm);
    color: var(--dm-text);
    text-decoration: none;
}

.dm-pagination__btn:hover {
    background: var(--dm-primary);
    border-color: var(--dm-primary);
    color: #fff;
}

.dm-pagination__info {
    font-size: 13px;
    color: var(--dm-text-muted);
}

/* Modal */
.dm-modal__overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
}

.dm-modal__content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    background: #fff;
    border-radius: var(--dm-radius);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    z-index: 100001;
    display: flex;
    flex-direction: column;
}

.dm-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--dm-border);
}

.dm-modal__title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.dm-modal__close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--dm-text-muted);
    padding: 4px;
}

.dm-modal__close:hover {
    color: var(--dm-text);
}

.dm-modal__body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.dm-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid var(--dm-border);
}

.dm-stats-grid--4 {
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 1024px) {
    .dm-stats-grid--4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .dm-stats-grid--4 {
        grid-template-columns: 1fr;
    }

    .dm-filters {
        flex-direction: column;
    }

    .dm-filters__group {
        width: 100%;
    }

    .dm-filters__group select,
    .dm-filters__group input {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Modal ver aviso
    $('.am-ver-aviso').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var html = '<table class="form-table">';
        html += '<tr><th>Título:</th><td><strong>' + $btn.data('titulo') + '</strong></td></tr>';
        html += '<tr><th>Categoría:</th><td>' + ($btn.data('categoria') || '-') + '</td></tr>';
        html += '<tr><th>Prioridad:</th><td>' + $btn.data('prioridad') + '</td></tr>';
        html += '<tr><th>Fecha:</th><td>' + $btn.data('fecha') + '</td></tr>';
        html += '<tr><th>Contenido:</th><td>' + ($btn.data('contenido') || '-') + '</td></tr>';
        html += '</table>';
        $('#contenido-aviso').html(html);
        $('#btn-republicar-aviso').attr('href', '<?php echo admin_url('admin.php?page=avisos-nuevo&republicar='); ?>' + $btn.data('id'));
        $('#modal-ver-aviso').fadeIn(200);
    });

    $('#cerrar-modal-aviso, #btn-cerrar-modal, .dm-modal__overlay').on('click', function(e) {
        if (e.target === this) {
            $('#modal-ver-aviso').fadeOut(200);
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#modal-ver-aviso').fadeOut(200);
        }
    });
});
</script>
