<?php
/**
 * Dashboard de Avisos Municipales (MEJORADO con Widgets de Datos en Vivo)
 *
 * Vista principal del panel de administración del módulo.
 *
 * Variables disponibles:
 *   $stats - Array con estadísticas
 *   $avisos_recientes - Array de avisos recientes
 *   $avisos_urgentes - Array de avisos urgentes
 *   $categorias - Array de categorías
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Colores de prioridad
$prioridad_classes = [
    'urgente' => 'dm-badge--error',
    'alta'    => 'dm-badge--warning',
    'media'   => 'dm-badge--info',
    'baja'    => 'dm-badge--success',
];

$prioridad_icons = [
    'urgente' => 'warning',
    'alta'    => 'flag',
    'media'   => 'info',
    'baja'    => 'yes-alt',
];

// ==================== WIDGETS DE DATOS EN VIVO ====================
$modulos_relacionados = [];
$active_modules = get_option('flavor_active_modules', []);

// 1. Incidencias - Reportes ciudadanos
if (in_array('incidencias', $active_modules)) {
    $tabla_tickets = $wpdb->prefix . 'flavor_incidencias_tickets';
    $tabla_tickets_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_tickets'") === $tabla_tickets;

    if ($tabla_tickets_existe) {
        $incidencias_recientes = $wpdb->get_results(
            "SELECT id, asunto, estado, prioridad, fecha_creacion
             FROM $tabla_tickets
             WHERE (estado = 'abierto' OR estado = 'en_progreso')
             AND (prioridad = 'alta' OR prioridad = 'urgente')
             ORDER BY fecha_creacion DESC
             LIMIT 3"
        );

        if (!empty($incidencias_recientes)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($incidencias_recientes as $ticket) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🔧 %s</span>
                    </div>',
                    esc_html(wp_trim_words($ticket->asunto, 5)),
                    esc_html(ucfirst($ticket->estado))
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['incidencias'] = [
                'titulo' => sprintf(__('Incidencias Urgentes (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($incidencias_recientes)),
                'descripcion' => __('Reportes ciudadanos que requieren avisos públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-warning',
                'url' => admin_url('admin.php?page=flavor-incidencias'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 2. Eventos - Eventos municipales
if (in_array('eventos', $active_modules)) {
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    $tabla_eventos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos;

    if ($tabla_eventos_existe) {
        $eventos_proximos = $wpdb->get_results(
            "SELECT id, titulo, fecha, categoria
             FROM $tabla_eventos
             WHERE fecha >= CURDATE()
             AND (categoria = 'municipal' OR categoria = 'institucional')
             AND estado = 'publicado'
             ORDER BY fecha ASC
             LIMIT 3"
        );

        if (!empty($eventos_proximos)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($eventos_proximos as $evento) {
                $fecha = date_i18n('d M', strtotime($evento->fecha));
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">📅 %s</span>
                    </div>',
                    esc_html(wp_trim_words($evento->titulo, 5)),
                    esc_html($fecha)
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['eventos'] = [
                'titulo' => sprintf(__('Eventos Municipales (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($eventos_proximos)),
                'descripcion' => __('Eventos oficiales a comunicar a la ciudadanía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar-alt',
                'url' => admin_url('admin.php?page=flavor-eventos'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 3. Participación - Consultas públicas
if (in_array('participacion', $active_modules)) {
    $tabla_propuestas = $wpdb->prefix . 'flavor_participacion_propuestas';
    $tabla_propuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_propuestas'") === $tabla_propuestas;

    if ($tabla_propuestas_existe) {
        $consultas_activas = $wpdb->get_results(
            "SELECT id, titulo, tipo, votos_totales, fecha_fin
             FROM $tabla_propuestas
             WHERE estado = 'activa'
             AND tipo IN ('consulta_publica', 'referendum')
             ORDER BY fecha_fin DESC
             LIMIT 3"
        );

        if (!empty($consultas_activas)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($consultas_activas as $consulta) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🗳️ %d participantes</span>
                    </div>',
                    esc_html(wp_trim_words($consulta->titulo, 5)),
                    (int)$consulta->votos_totales
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['participacion'] = [
                'titulo' => sprintf(__('Consultas Públicas (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($consultas_activas)),
                'descripcion' => __('Procesos participativos que requieren difusión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-megaphone',
                'url' => admin_url('admin.php?page=flavor-participacion'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 4. Transparencia - Comunicados oficiales
if (in_array('transparencia', $active_modules)) {
    $tabla_documentos = $wpdb->prefix . 'flavor_transparencia_documentos';
    $tabla_documentos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_documentos'") === $tabla_documentos;

    if ($tabla_documentos_existe) {
        $documentos_recientes = $wpdb->get_results(
            "SELECT id, titulo, tipo, fecha_publicacion
             FROM $tabla_documentos
             WHERE tipo IN ('comunicado', 'resolucion', 'acta')
             AND estado = 'publicado'
             ORDER BY fecha_publicacion DESC
             LIMIT 3"
        );

        if (!empty($documentos_recientes)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($documentos_recientes as $doc) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">📄 %s</span>
                    </div>',
                    esc_html(wp_trim_words($doc->titulo, 5)),
                    esc_html(ucfirst($doc->tipo))
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['transparencia'] = [
                'titulo' => sprintf(__('Documentos Publicados (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($documentos_recientes)),
                'descripcion' => __('Resoluciones y actas de interés público', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-media-document',
                'url' => admin_url('admin.php?page=flavor-transparencia'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 5. Tramites - Procedimientos administrativos
if (in_array('tramites', $active_modules)) {
    $tabla_tramites = $wpdb->prefix . 'flavor_tramites';
    $tabla_tramites_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_tramites'") === $tabla_tramites;

    if ($tabla_tramites_existe) {
        $tramites_populares = $wpdb->get_results(
            "SELECT id, nombre, solicitudes_totales, requiere_aviso_publico, estado
             FROM $tabla_tramites
             WHERE estado = 'activo'
             AND requiere_aviso_publico = 1
             ORDER BY solicitudes_totales DESC
             LIMIT 3"
        );

        if (!empty($tramites_populares)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($tramites_populares as $tramite) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">📋 %d solicitudes</span>
                    </div>',
                    esc_html(wp_trim_words($tramite->nombre, 5)),
                    (int)$tramite->solicitudes_totales
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['tramites'] = [
                'titulo' => sprintf(__('Trámites Destacados (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($tramites_populares)),
                'descripcion' => __('Procedimientos con cambios a comunicar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clipboard',
                'url' => admin_url('admin.php?page=flavor-tramites'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 6. Comunidades - Grupos vecinales
if (in_array('comunidades', $active_modules)) {
    $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
    $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
    $tabla_comunidades_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_comunidades'") === $tabla_comunidades;
    $tabla_miembros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_miembros'") === $tabla_miembros;

    if ($tabla_comunidades_existe && $tabla_miembros_existe) {
        $comunidades_grandes = $wpdb->get_results(
            "SELECT c.id, c.nombre, c.categoria, COUNT(m.id) as num_miembros
             FROM $tabla_comunidades c
             LEFT JOIN $tabla_miembros m ON c.id = m.comunidad_id AND m.estado = 'activo'
             WHERE c.estado = 'activa'
             AND c.categoria = 'vecinal'
             GROUP BY c.id
             ORDER BY num_miembros DESC
             LIMIT 3"
        );

        if (!empty($comunidades_grandes)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($comunidades_grandes as $comunidad) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🤝 %d vecinos</span>
                    </div>',
                    esc_html(wp_trim_words($comunidad->nombre, 5)),
                    (int)$comunidad->num_miembros
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['comunidades'] = [
                'titulo' => sprintf(__('Comunidades Vecinales (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($comunidades_grandes)),
                'descripcion' => __('Grupos de vecinos para comunicación segmentada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-multisite',
                'url' => admin_url('admin.php?page=flavor-comunidades'),
                'datos' => $datos_html,
            ];
        }
    }
}
?>

<div class="wrap dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('avisos_municipales');
    }
    ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Comunicados oficiales, cortes de servicio y notificaciones a la ciudadanía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Alerta de avisos urgentes -->
    <?php if (!empty($avisos_urgentes)): ?>
    <div class="dm-alert dm-alert--error">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong><?php printf(
                _n('%d aviso urgente activo', '%d avisos urgentes activos', count($avisos_urgentes), FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($avisos_urgentes)
            ); ?></strong>
            <div style="margin-top: 4px;">
                <?php foreach (array_slice($avisos_urgentes, 0, 2) as $aviso_urgente): ?>
                    <span style="margin-right: 12px;"><?php echo esc_html($aviso_urgente->titulo); ?></span>
                <?php endforeach; ?>
                <?php if (count($avisos_urgentes) > 2): ?>
                    <span>+<?php echo count($avisos_urgentes) - 2; ?> <?php esc_html_e('más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['activos']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Avisos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos')); ?>" class="dm-stat-card__link">
                <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> &rarr;
            </a>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['urgentes']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['proximos_expirar']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Próximos a expirar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['visualizaciones_mes']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Visualizaciones (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- WIDGETS DE DATOS EN VIVO -->
    <?php if (!empty($modulos_relacionados)): ?>
    <div class="dm-card" style="margin: 24px 0;">
        <div class="dm-card__header">
            <h2 class="dm-card__title">
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('Datos en Vivo de Módulos Relacionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
        </div>
        <div class="dm-card__body">
            <div class="dm-widget-datos-vivo">
                <?php foreach ($modulos_relacionados as $widget): ?>
                <div class="dm-widget-relacionado">
                    <div class="dm-widget-relacionado__header">
                        <span class="dashicons <?php echo esc_attr($widget['icono']); ?>"></span>
                        <h4><?php echo esc_html($widget['titulo']); ?></h4>
                    </div>
                    <p class="dm-widget-relacionado__desc"><?php echo esc_html($widget['descripcion']); ?></p>
                    <?php echo $widget['datos']; ?>
                    <a href="<?php echo esc_url($widget['url']); ?>" class="dm-widget-relacionado__link">
                        <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de contenido principal -->
    <div class="dm-grid dm-grid--2">

        <!-- Últimos avisos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2 class="dm-card__title">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Últimos Avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos')); ?>" class="dm-card__action">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body dm-card__body--no-padding">
                <?php if (!empty($avisos_recientes)): ?>
                <ul class="dm-list">
                    <?php foreach ($avisos_recientes as $aviso): ?>
                    <li class="dm-list__item">
                        <div class="dm-list__icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($prioridad_icons[$aviso->prioridad] ?? 'info'); ?>"></span>
                        </div>
                        <div class="dm-list__content">
                            <div class="dm-list__title"><?php echo esc_html($aviso->titulo); ?></div>
                            <div class="dm-list__meta">
                                <span class="dm-badge <?php echo esc_attr($prioridad_classes[$aviso->prioridad] ?? 'dm-badge--info'); ?>">
                                    <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                                </span>
                                <span><?php echo esc_html($aviso->categoria ?: __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                                <span><?php echo esc_html(human_time_diff(strtotime($aviso->created_at), current_time('timestamp'))); ?></span>
                            </div>
                        </div>
                        <div class="dm-list__actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo&editar=' . $aviso->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="dm-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay avisos publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-btn dm-btn--primary dm-btn--sm">
                        <?php esc_html_e('Crear primer aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones rápidas y categorías -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2 class="dm-card__title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="dm-card__body">
                <div class="dm-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span><?php esc_html_e('Nuevo Aviso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo&prioridad=urgente')); ?>" class="dm-quick-action dm-quick-action--danger">
                        <span class="dashicons dashicons-warning"></span>
                        <span><?php esc_html_e('Aviso Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-archivo')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-archive"></span>
                        <span><?php esc_html_e('Ver Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-config')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('avisos_municipales', '')); ?>" class="dm-quick-action" target="_blank">
                        <span class="dashicons dashicons-external"></span>
                        <span><?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                </div>

                <?php if (!empty($categorias)): ?>
                <h3 class="dm-subtitle" style="margin-top: 24px;">
                    <?php esc_html_e('Por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <div class="dm-tags">
                    <?php foreach ($categorias as $categoria): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos&categoria=' . urlencode($categoria->slug ?? $categoria->nombre))); ?>"
                       class="dm-tag"
                       style="--tag-color: <?php echo esc_attr($categoria->color ?? '#6366f1'); ?>">
                        <?php if (!empty($categoria->icono)): ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($categoria->icono); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($categoria->nombre); ?>
                        <?php if (isset($categoria->count) && $categoria->count > 0): ?>
                        <span class="dm-tag__count"><?php echo number_format_i18n($categoria->count); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Estadísticas adicionales -->
    <div class="dm-grid dm-grid--3" style="margin-top: 24px;">
        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-archive"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['total']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Total histórico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['este_mes']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Publicados este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['confirmaciones']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Confirmaciones de lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
