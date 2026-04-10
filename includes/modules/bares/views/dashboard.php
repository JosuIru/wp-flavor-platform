<?php
/**
 * Dashboard de Bares y Hostelería
 *
 * Panel administrativo para gestión de establecimientos, reservas,
 * valoraciones y cartas de menú.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_bares = $wpdb->prefix . 'flavor_bares';
$tabla_reservas = $wpdb->prefix . 'flavor_bares_reservas';
$tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';
$tabla_carta = $wpdb->prefix . 'flavor_bares_carta';

// Verificar existencia de tablas
$tabla_bares_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_bares'") === $tabla_bares;
$tabla_reservas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas;
$tabla_valoraciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_valoraciones'") === $tabla_valoraciones;

// Estadísticas generales
$total_bares = 0;
$bares_activos = 0;
$bares_por_tipo = [];
$valoracion_media = 0;
$total_reservas_hoy = 0;
$total_reservas_semana = 0;
$total_valoraciones = 0;

if ($tabla_bares_existe) {
    $total_bares = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bares");
    $bares_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bares WHERE estado = 'activo'");

    $bares_por_tipo = $wpdb->get_results(
        "SELECT tipo, COUNT(*) as cantidad FROM $tabla_bares GROUP BY tipo ORDER BY cantidad DESC",
        ARRAY_A
    );
}

if ($tabla_valoraciones_existe) {
    $valoracion_media = (float) $wpdb->get_var("SELECT AVG(puntuacion) FROM $tabla_valoraciones");
    $total_valoraciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_valoraciones");
}

if ($tabla_reservas_existe) {
    $hoy = date('Y-m-d');
    $total_reservas_hoy = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_reservas WHERE DATE(fecha_reserva) = %s AND estado IN ('confirmada', 'pendiente')",
        $hoy
    ));

    $total_reservas_semana = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_reservas
         WHERE fecha_reserva >= CURDATE() AND fecha_reserva < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         AND estado IN ('confirmada', 'pendiente')"
    );

    $reservas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'pendiente'");
}

// Últimos bares añadidos
$ultimos_bares = [];
if ($tabla_bares_existe) {
    $ultimos_bares = $wpdb->get_results(
        "SELECT id, nombre, tipo, estado, direccion, valoracion_media, created_at
         FROM $tabla_bares
         ORDER BY created_at DESC
         LIMIT 5"
    );
}

// Top bares por valoración
$top_bares = [];
if ($tabla_bares_existe) {
    $top_bares = $wpdb->get_results(
        "SELECT id, nombre, tipo, valoracion_media, total_valoraciones
         FROM $tabla_bares
         WHERE valoracion_media > 0
         ORDER BY valoracion_media DESC, total_valoraciones DESC
         LIMIT 5"
    );
}

// Próximas reservas
$proximas_reservas = [];
if ($tabla_reservas_existe && $tabla_bares_existe) {
    $proximas_reservas = $wpdb->get_results(
        "SELECT r.id, r.nombre_cliente, r.fecha_reserva, r.hora_reserva, r.personas, r.estado, b.nombre as bar_nombre
         FROM $tabla_reservas r
         LEFT JOIN $tabla_bares b ON r.bar_id = b.id
         WHERE r.fecha_reserva >= CURDATE()
         AND r.estado IN ('confirmada', 'pendiente')
         ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC
         LIMIT 10"
    );
}

// Distribución por tipo de establecimiento
$tipos_labels = [
    'bar' => __('Bar', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'restaurante' => __('Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cafeteria' => __('Cafetería', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pub' => __('Pub', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'terraza' => __('Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cocteleria' => __('Coctelería', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('bares'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-store" style="color: #8b5cf6;"></span>
            <h1><?php esc_html_e('Bares y Hostelería', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-nuevo')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Establecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-listado')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php esc_html_e('Ver Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-reservas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php esc_html_e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-valoraciones')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-star-filled"></span>
                <span><?php esc_html_e('Valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-config')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-store"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($bares_activos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Establecimientos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($total_bares > $bares_activos): ?>
                <span class="dm-stat-card__meta"><?php echo esc_html($total_bares); ?> <?php esc_html_e('en total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($total_reservas_hoy); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Reservas Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta"><?php echo esc_html($total_reservas_semana); ?> <?php esc_html_e('esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($valoracion_media, 1)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Valoración Media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta"><?php echo esc_html($total_valoraciones); ?> <?php esc_html_e('reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-food"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(count($bares_por_tipo)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Tipos de Local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($reservas_pendientes) && $reservas_pendientes > 0): ?>
    <!-- Alerta de reservas pendientes -->
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-clock"></span>
        <div class="dm-alert__content">
            <strong><?php echo esc_html($reservas_pendientes); ?> <?php esc_html_e('reservas pendientes de confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <p><?php esc_html_e('Revisa y confirma las reservas pendientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-reservas&estado=pendiente')); ?>" class="button">
                <?php esc_html_e('Ver Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <!-- Últimos Establecimientos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Últimos Añadidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bares-listado')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimos_bares)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_bares as $bar):
                            $estado_class = $bar->estado === 'activo' ? 'success' : 'secondary';
                            $tipo_label = isset($tipos_labels[$bar->tipo]) ? $tipos_labels[$bar->tipo] : ucfirst($bar->tipo);
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bares-editar&id=' . $bar->id)); ?>">
                                    <strong><?php echo esc_html($bar->nombre); ?></strong>
                                </a>
                                <?php if ($bar->valoracion_media > 0): ?>
                                <br><small style="color: #f59e0b;">★ <?php echo esc_html(number_format($bar->valoracion_media, 1)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($tipo_label); ?></td>
                            <td>
                                <span class="dm-badge dm-badge--<?php echo esc_attr($estado_class); ?>">
                                    <?php echo esc_html(ucfirst($bar->estado)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay establecimientos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Valorados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Mejor Valorados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($top_bares)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Establecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_bares as $bar): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($bar->nombre); ?></strong>
                                <br><small style="color: #64748b;"><?php echo esc_html(isset($tipos_labels[$bar->tipo]) ? $tipos_labels[$bar->tipo] : ucfirst($bar->tipo)); ?></small>
                            </td>
                            <td>
                                <span style="color: #f59e0b; font-weight: bold;">
                                    ★ <?php echo esc_html(number_format($bar->valoracion_media, 1)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($bar->total_valoraciones); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin valoraciones disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Próximas Reservas -->
    <?php if (!empty($proximas_reservas)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Próximas Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bares-reservas')); ?>" class="dm-card__link">
                <?php esc_html_e('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <div class="dm-card__body">
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha/Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Establecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximas_reservas as $reserva):
                        $estado_class = $reserva->estado === 'confirmada' ? 'success' : 'warning';
                        $es_hoy = date('Y-m-d', strtotime($reserva->fecha_reserva)) === date('Y-m-d');
                    ?>
                    <tr>
                        <td>
                            <?php if ($es_hoy): ?>
                            <span class="dm-badge dm-badge--info"><?php esc_html_e('HOY', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php else: ?>
                            <?php echo esc_html(date_i18n('d/m', strtotime($reserva->fecha_reserva))); ?>
                            <?php endif; ?>
                            <strong><?php echo esc_html($reserva->hora_reserva); ?></strong>
                        </td>
                        <td><?php echo esc_html($reserva->bar_nombre); ?></td>
                        <td><?php echo esc_html($reserva->nombre_cliente); ?></td>
                        <td>
                            <span class="dashicons dashicons-groups"></span>
                            <?php echo esc_html($reserva->personas); ?>
                        </td>
                        <td>
                            <span class="dm-badge dm-badge--<?php echo esc_attr($estado_class); ?>">
                                <?php echo esc_html(ucfirst($reserva->estado)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Distribución por Tipo -->
    <?php if (!empty($bares_por_tipo)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Distribución por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-distribution">
                <?php
                $colores = ['primary', 'success', 'warning', 'info', 'secondary', 'danger'];
                $indice_color = 0;
                foreach ($bares_por_tipo as $tipo_data):
                    $porcentaje = $total_bares > 0 ? ($tipo_data['cantidad'] / $total_bares) * 100 : 0;
                    $tipo_label = isset($tipos_labels[$tipo_data['tipo']]) ? $tipos_labels[$tipo_data['tipo']] : ucfirst($tipo_data['tipo']);
                    $color = $colores[$indice_color % count($colores)];
                    $indice_color++;
                ?>
                <div class="dm-distribution__item">
                    <div class="dm-distribution__label">
                        <span class="dm-badge dm-badge--<?php echo esc_attr($color); ?>"><?php echo esc_html($tipo_label); ?></span>
                        <span><?php echo esc_html($tipo_data['cantidad']); ?></span>
                    </div>
                    <div class="dm-progress">
                        <div class="dm-progress__bar dm-progress__bar--<?php echo esc_attr($color); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resumen Rápido -->
    <div class="dm-grid dm-grid--3">
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-menu-alt3"></span> <?php esc_html_e('Cartas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php
                $total_platos = 0;
                if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_carta'") === $tabla_carta) {
                    $total_platos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_carta WHERE activo = 1");
                }
                ?>
                <div style="font-size: 2em; font-weight: bold; color: #8b5cf6;">
                    <?php echo esc_html($total_platos); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Platos/productos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php
                $total_favoritos = 0;
                $tabla_favoritos = $wpdb->prefix . 'flavor_bares_favoritos';
                if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_favoritos'") === $tabla_favoritos) {
                    $total_favoritos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT bar_id) FROM $tabla_favoritos");
                }
                ?>
                <div style="font-size: 2em; font-weight: bold; color: #ec4899;">
                    <?php echo esc_html($total_favoritos); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Locales marcados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-location"></span> <?php esc_html_e('Cobertura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php
                $localidades = 0;
                if ($tabla_bares_existe) {
                    $localidades = (int) $wpdb->get_var("SELECT COUNT(DISTINCT ciudad) FROM $tabla_bares WHERE ciudad IS NOT NULL AND ciudad != ''");
                }
                ?>
                <div style="font-size: 2em; font-weight: bold; color: #3b82f6;">
                    <?php echo esc_html($localidades); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Localidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
</div>
