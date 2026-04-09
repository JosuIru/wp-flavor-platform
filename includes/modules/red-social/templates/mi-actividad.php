<?php
/**
 * Template: Mi Actividad - Actividad reciente del usuario
 *
 * Muestra un historial de la actividad del usuario: publicaciones, likes, comentarios, etc.
 *
 * @package FlavorChatIA
 * @subpackage RedSocial/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
if (!$usuario_id) {
    echo '<div class="rs-login-required">';
    echo '<p>' . esc_html__('Debes iniciar sesion para ver tu actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('red_social', 'mi-actividad'))) . '" class="rs-btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

global $wpdb;

$tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
$tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';
$tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
$tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
$tabla_guardados = $wpdb->prefix . 'flavor_social_guardados';
$tabla_historial_puntos = $wpdb->prefix . 'flavor_social_historial_puntos';
$tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';

// Parametros de filtro
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'todo';
$pagina_actual = isset($_GET['pag']) ? max(1, absint($_GET['pag'])) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener estadisticas del usuario
$total_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_publicaciones WHERE autor_id = %d AND estado = 'publicado'",
    $usuario_id
));

$total_comentarios = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_comentarios WHERE autor_id = %d AND estado = 'publicado'",
    $usuario_id
));

$total_likes_dados = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_reacciones WHERE usuario_id = %d",
    $usuario_id
));

$total_likes_recibidos = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(me_gusta) FROM $tabla_publicaciones WHERE autor_id = %d AND estado = 'publicado'",
    $usuario_id
));

$total_guardados = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_guardados WHERE usuario_id = %d",
    $usuario_id
));

// Obtener reputacion
$reputacion = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_reputacion WHERE usuario_id = %d",
    $usuario_id
));

// Construir timeline de actividad
$actividades = [];

switch ($filtro_tipo) {
    case 'publicaciones':
        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT 'publicacion' as tipo_actividad, id as referencia_id, contenido as descripcion, fecha_publicacion as fecha
             FROM $tabla_publicaciones
             WHERE autor_id = %d AND estado = 'publicado'
             ORDER BY fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $usuario_id, $por_pagina, $offset
        ));
        break;

    case 'comentarios':
        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT 'comentario' as tipo_actividad, c.id as referencia_id, c.contenido as descripcion, c.fecha_creacion as fecha,
                    p.id as publicacion_id, p.contenido as publicacion_contenido
             FROM $tabla_comentarios c
             INNER JOIN $tabla_publicaciones p ON c.publicacion_id = p.id
             WHERE c.autor_id = %d AND c.estado = 'publicado'
             ORDER BY c.fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $usuario_id, $por_pagina, $offset
        ));
        break;

    case 'likes':
        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT 'like' as tipo_actividad, r.publicacion_id as referencia_id, p.contenido as descripcion, r.fecha_creacion as fecha,
                    u.display_name as autor_nombre, p.autor_id
             FROM $tabla_reacciones r
             INNER JOIN $tabla_publicaciones p ON r.publicacion_id = p.id
             INNER JOIN {$wpdb->users} u ON p.autor_id = u.ID
             WHERE r.usuario_id = %d AND r.publicacion_id IS NOT NULL
             ORDER BY r.fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $usuario_id, $por_pagina, $offset
        ));
        break;

    case 'guardados':
        $actividades = $wpdb->get_results($wpdb->prepare(
            "SELECT 'guardado' as tipo_actividad, g.publicacion_id as referencia_id, p.contenido as descripcion, g.fecha_guardado as fecha,
                    u.display_name as autor_nombre, p.autor_id
             FROM $tabla_guardados g
             INNER JOIN $tabla_publicaciones p ON g.publicacion_id = p.id
             INNER JOIN {$wpdb->users} u ON p.autor_id = u.ID
             WHERE g.usuario_id = %d
             ORDER BY g.fecha_guardado DESC
             LIMIT %d OFFSET %d",
            $usuario_id, $por_pagina, $offset
        ));
        break;

    default: // 'todo' - Timeline combinado
        $actividades = $wpdb->get_results($wpdb->prepare(
            "(SELECT 'publicacion' as tipo_actividad, id as referencia_id, contenido as descripcion, fecha_publicacion as fecha, NULL as extra_id, NULL as extra_nombre
              FROM $tabla_publicaciones WHERE autor_id = %d AND estado = 'publicado')
             UNION ALL
             (SELECT 'comentario' as tipo_actividad, c.id as referencia_id, c.contenido as descripcion, c.fecha_creacion as fecha, c.publicacion_id as extra_id, NULL as extra_nombre
              FROM $tabla_comentarios c WHERE c.autor_id = %d AND c.estado = 'publicado')
             UNION ALL
             (SELECT 'like' as tipo_actividad, r.publicacion_id as referencia_id, p.contenido as descripcion, r.fecha_creacion as fecha, p.autor_id as extra_id, u.display_name as extra_nombre
              FROM $tabla_reacciones r
              INNER JOIN $tabla_publicaciones p ON r.publicacion_id = p.id
              INNER JOIN {$wpdb->users} u ON p.autor_id = u.ID
              WHERE r.usuario_id = %d AND r.publicacion_id IS NOT NULL)
             UNION ALL
             (SELECT 'seguir' as tipo_actividad, s.seguido_id as referencia_id, NULL as descripcion, s.fecha_seguimiento as fecha, s.seguido_id as extra_id, u.display_name as extra_nombre
              FROM $tabla_seguimientos s
              INNER JOIN {$wpdb->users} u ON s.seguido_id = u.ID
              WHERE s.seguidor_id = %d)
             ORDER BY fecha DESC
             LIMIT %d OFFSET %d",
            $usuario_id, $usuario_id, $usuario_id, $usuario_id, $por_pagina, $offset
        ));
        break;
}

// Obtener historial de puntos reciente
$historial_puntos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_historial_puntos
     WHERE usuario_id = %d
     ORDER BY fecha_creacion DESC
     LIMIT 10",
    $usuario_id
));
?>

<div class="rs-container">
    <div class="rs-mi-actividad">
        <!-- Cabecera con estadisticas -->
        <div class="rs-actividad-header">
            <div class="rs-actividad-usuario">
                <img class="rs-actividad-avatar"
                     src="<?php echo esc_url(get_avatar_url($usuario_id, ['size' => 80])); ?>"
                     alt="">
                <div class="rs-actividad-info">
                    <h1><?php echo esc_html__('Tu actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
                    <p><?php echo esc_html__('Historial de tu participacion en la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <!-- Estadisticas rapidas -->
            <div class="rs-actividad-stats">
                <div class="rs-actividad-stat">
                    <span class="rs-stat-numero"><?php echo number_format($total_publicaciones); ?></span>
                    <span class="rs-stat-label"><?php echo esc_html__('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="rs-actividad-stat">
                    <span class="rs-stat-numero"><?php echo number_format($total_comentarios); ?></span>
                    <span class="rs-stat-label"><?php echo esc_html__('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="rs-actividad-stat">
                    <span class="rs-stat-numero"><?php echo number_format($total_likes_recibidos); ?></span>
                    <span class="rs-stat-label"><?php echo esc_html__('Likes recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="rs-actividad-stat">
                    <span class="rs-stat-numero"><?php echo number_format($total_guardados); ?></span>
                    <span class="rs-stat-label"><?php echo esc_html__('Guardados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <div class="rs-actividad-layout">
            <!-- Columna principal -->
            <div class="rs-actividad-main">
                <!-- Filtros -->
                <div class="rs-actividad-filtros">
                    <a href="?tipo=todo" class="rs-filtro-btn <?php echo $filtro_tipo === 'todo' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php echo esc_html__('Todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="?tipo=publicaciones" class="rs-filtro-btn <?php echo $filtro_tipo === 'publicaciones' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <?php echo esc_html__('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="?tipo=comentarios" class="rs-filtro-btn <?php echo $filtro_tipo === 'comentarios' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        </svg>
                        <?php echo esc_html__('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="?tipo=likes" class="rs-filtro-btn <?php echo $filtro_tipo === 'likes' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <?php echo esc_html__('Me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="?tipo=guardados" class="rs-filtro-btn <?php echo $filtro_tipo === 'guardados' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                        <?php echo esc_html__('Guardados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>

                <!-- Timeline de actividad -->
                <div class="rs-actividad-timeline">
                    <?php if (empty($actividades)): ?>
                        <div class="rs-actividad-vacio">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <p><?php echo esc_html__('No hay actividad en esta categoria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <span><?php echo esc_html__('Tu actividad en la red social aparecera aqui.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($actividades as $actividad): ?>
                            <div class="rs-actividad-item rs-actividad-<?php echo esc_attr($actividad->tipo_actividad); ?>">
                                <div class="rs-actividad-icono">
                                    <?php
                                    switch ($actividad->tipo_actividad) {
                                        case 'publicacion':
                                            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
                                            break;
                                        case 'comentario':
                                            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
                                            break;
                                        case 'like':
                                            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
                                            break;
                                        case 'seguir':
                                            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>';
                                            break;
                                        case 'guardado':
                                            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
                                            break;
                                    }
                                    ?>
                                </div>
                                <div class="rs-actividad-contenido">
                                    <div class="rs-actividad-titulo">
                                        <?php
                                        switch ($actividad->tipo_actividad) {
                                            case 'publicacion':
                                                echo esc_html__('Publicaste', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                                break;
                                            case 'comentario':
                                                echo esc_html__('Comentaste en una publicacion', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                                break;
                                            case 'like':
                                                printf(esc_html__('Te gusto una publicacion de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), '<strong>' . esc_html($actividad->extra_nombre) . '</strong>');
                                                break;
                                            case 'seguir':
                                                printf(esc_html__('Empezaste a seguir a %s', FLAVOR_PLATFORM_TEXT_DOMAIN), '<strong>' . esc_html($actividad->extra_nombre) . '</strong>');
                                                break;
                                            case 'guardado':
                                                printf(esc_html__('Guardaste una publicacion de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), '<strong>' . esc_html($actividad->autor_nombre) . '</strong>');
                                                break;
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($actividad->descripcion)): ?>
                                        <p class="rs-actividad-texto"><?php echo esc_html(wp_trim_words($actividad->descripcion, 20)); ?></p>
                                    <?php endif; ?>
                                    <span class="rs-actividad-tiempo"><?php echo human_time_diff(strtotime($actividad->fecha), current_time('timestamp')); ?></span>
                                </div>
                                <?php if ($actividad->tipo_actividad !== 'seguir'): ?>
                                    <a href="<?php echo esc_url(add_query_arg('publicacion_id', intval($actividad->referencia_id), Flavor_Chat_Helpers::get_action_url('red_social', ''))); ?>" class="rs-actividad-ver">
                                        <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($actividad->referencia_id), Flavor_Chat_Helpers::get_action_url('red_social', 'perfil'))); ?>" class="rs-actividad-ver">
                                        <?php echo esc_html__('Ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Paginacion -->
                <?php
                $total_items = 0;
                switch ($filtro_tipo) {
                    case 'publicaciones':
                        $total_items = $total_publicaciones;
                        break;
                    case 'comentarios':
                        $total_items = $total_comentarios;
                        break;
                    case 'likes':
                        $total_items = $total_likes_dados;
                        break;
                    case 'guardados':
                        $total_items = $total_guardados;
                        break;
                    default:
                        $total_items = $total_publicaciones + $total_comentarios + $total_likes_dados;
                }
                $total_paginas = ceil($total_items / $por_pagina);

                if ($total_paginas > 1):
                ?>
                    <div class="rs-paginacion">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?tipo=<?php echo esc_attr($filtro_tipo); ?>&pag=<?php echo $pagina_actual - 1; ?>" class="rs-paginacion-btn">
                                <?php echo esc_html__('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                        <span class="rs-paginacion-info"><?php printf(esc_html__('Pagina %d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $pagina_actual, $total_paginas); ?></span>
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?tipo=<?php echo esc_attr($filtro_tipo); ?>&pag=<?php echo $pagina_actual + 1; ?>" class="rs-paginacion-btn">
                                <?php echo esc_html__('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Reputacion -->
            <div class="rs-actividad-sidebar">
                <!-- Widget de reputacion -->
                <?php if ($reputacion): ?>
                    <div class="rs-widget rs-widget-reputacion">
                        <h3 class="rs-widget-titulo"><?php echo esc_html__('Tu reputacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="rs-reputacion-nivel">
                            <?php
                            $niveles = [
                                'nuevo' => ['min' => 0, 'label' => 'Nuevo', 'icono' => '<span>Nv</span>', 'color' => '#9ca3af'],
                                'activo' => ['min' => 50, 'label' => 'Activo', 'icono' => '<span>Ac</span>', 'color' => '#3b82f6'],
                                'contribuidor' => ['min' => 200, 'label' => 'Contribuidor', 'icono' => '<span>Co</span>', 'color' => '#8b5cf6'],
                                'experto' => ['min' => 500, 'label' => 'Experto', 'icono' => '<span>Ex</span>', 'color' => '#f59e0b'],
                                'lider' => ['min' => 1000, 'label' => 'Lider', 'icono' => '<span>Li</span>', 'color' => '#ef4444'],
                                'embajador' => ['min' => 2500, 'label' => 'Embajador', 'icono' => '<span>Em</span>', 'color' => '#10b981'],
                                'leyenda' => ['min' => 5000, 'label' => 'Leyenda', 'icono' => '<span>Le</span>', 'color' => '#ec4899'],
                            ];
                            $nivel_actual = $niveles[$reputacion->nivel] ?? $niveles['nuevo'];
                            $siguiente_nivel = null;
                            foreach ($niveles as $nivel_key => $nivel_data) {
                                if ($nivel_data['min'] > $reputacion->puntos_totales) {
                                    $siguiente_nivel = $nivel_data;
                                    break;
                                }
                            }
                            ?>
                            <div class="rs-nivel-badge" style="background: <?php echo esc_attr($nivel_actual['color']); ?>;">
                                <?php echo $nivel_actual['icono']; ?>
                            </div>
                            <div class="rs-nivel-info">
                                <span class="rs-nivel-nombre"><?php echo esc_html($nivel_actual['label']); ?></span>
                                <span class="rs-nivel-puntos"><?php echo number_format($reputacion->puntos_totales); ?> <?php echo esc_html__('puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <?php if ($siguiente_nivel): ?>
                            <div class="rs-nivel-progreso">
                                <?php
                                $puntos_para_siguiente = $siguiente_nivel['min'] - $reputacion->puntos_totales;
                                $progreso = (($reputacion->puntos_totales - $nivel_actual['min']) / ($siguiente_nivel['min'] - $nivel_actual['min'])) * 100;
                                ?>
                                <div class="rs-progreso-bar">
                                    <div class="rs-progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%;"></div>
                                </div>
                                <span class="rs-progreso-texto">
                                    <?php printf(esc_html__('%s puntos para %s', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($puntos_para_siguiente), $siguiente_nivel['label']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="rs-reputacion-stats">
                            <div class="rs-rep-stat">
                                <span class="rs-rep-stat-valor"><?php echo number_format($reputacion->puntos_semana); ?></span>
                                <span class="rs-rep-stat-label"><?php echo esc_html__('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="rs-rep-stat">
                                <span class="rs-rep-stat-valor"><?php echo number_format($reputacion->racha_dias); ?></span>
                                <span class="rs-rep-stat-label"><?php echo esc_html__('Dias de racha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Historial de puntos -->
                <?php if (!empty($historial_puntos)): ?>
                    <div class="rs-widget">
                        <h3 class="rs-widget-titulo"><?php echo esc_html__('Puntos recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="rs-puntos-lista">
                            <?php foreach ($historial_puntos as $punto): ?>
                                <div class="rs-punto-item">
                                    <span class="rs-punto-accion"><?php echo esc_html(ucfirst(str_replace('_', ' ', $punto->tipo_accion))); ?></span>
                                    <span class="rs-punto-valor <?php echo $punto->puntos >= 0 ? 'rs-positivo' : 'rs-negativo'; ?>">
                                        <?php echo $punto->puntos >= 0 ? '+' : ''; ?><?php echo esc_html($punto->puntos); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.rs-mi-actividad {
    padding: 24px 0;
}

.rs-actividad-header {
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    padding: 24px;
    margin-bottom: 24px;
}

.rs-actividad-usuario {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.rs-actividad-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-actividad-info h1 {
    margin: 0 0 4px;
    font-size: 24px;
}

.rs-actividad-info p {
    margin: 0;
    color: var(--rs-text-muted);
}

.rs-actividad-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.rs-actividad-stat {
    text-align: center;
    padding: 16px;
    background: var(--rs-bg-light);
    border-radius: var(--rs-radius-sm);
}

.rs-stat-numero {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: var(--rs-primary);
}

.rs-stat-label {
    font-size: 13px;
    color: var(--rs-text-muted);
}

.rs-actividad-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
}

.rs-actividad-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.rs-filtro-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: var(--rs-bg-card);
    border: 1px solid var(--rs-border);
    border-radius: var(--rs-radius-sm);
    color: var(--rs-text-muted);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: var(--rs-transition);
}

.rs-filtro-btn:hover,
.rs-filtro-btn.active {
    background: var(--rs-primary);
    border-color: var(--rs-primary);
    color: white;
}

.rs-actividad-timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rs-actividad-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    transition: var(--rs-transition);
}

.rs-actividad-item:hover {
    box-shadow: var(--rs-shadow-lg);
}

.rs-actividad-icono {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rs-actividad-publicacion .rs-actividad-icono {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.rs-actividad-comentario .rs-actividad-icono {
    background: rgba(139, 92, 246, 0.1);
    color: #8b5cf6;
}

.rs-actividad-like .rs-actividad-icono {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.rs-actividad-seguir .rs-actividad-icono {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.rs-actividad-guardado .rs-actividad-icono {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.rs-actividad-contenido {
    flex: 1;
    min-width: 0;
}

.rs-actividad-titulo {
    font-size: 15px;
    margin-bottom: 4px;
}

.rs-actividad-texto {
    margin: 0 0 8px;
    color: var(--rs-text-muted);
    font-size: 14px;
}

.rs-actividad-tiempo {
    font-size: 12px;
    color: var(--rs-text-muted);
}

.rs-actividad-ver {
    padding: 8px 16px;
    background: var(--rs-bg-light);
    color: var(--rs-primary);
    border-radius: var(--rs-radius-sm);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    flex-shrink: 0;
    transition: var(--rs-transition);
}

.rs-actividad-ver:hover {
    background: var(--rs-primary);
    color: white;
}

.rs-actividad-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
}

.rs-actividad-vacio svg {
    color: var(--rs-text-muted);
    margin-bottom: 16px;
    opacity: 0.5;
}

.rs-actividad-vacio p {
    margin: 0 0 8px;
    font-size: 16px;
}

.rs-actividad-vacio span {
    color: var(--rs-text-muted);
    font-size: 14px;
}

/* Sidebar reputacion */
.rs-widget-reputacion {
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    padding: 20px;
    margin-bottom: 20px;
}

.rs-reputacion-nivel {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.rs-nivel-badge {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.rs-nivel-nombre {
    display: block;
    font-size: 18px;
    font-weight: 600;
}

.rs-nivel-puntos {
    font-size: 14px;
    color: var(--rs-text-muted);
}

.rs-nivel-progreso {
    margin-bottom: 16px;
}

.rs-progreso-bar {
    height: 8px;
    background: var(--rs-bg-light);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.rs-progreso-fill {
    height: 100%;
    background: var(--rs-primary);
    border-radius: 4px;
    transition: width 0.3s;
}

.rs-progreso-texto {
    font-size: 12px;
    color: var(--rs-text-muted);
}

.rs-reputacion-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid var(--rs-border);
}

.rs-rep-stat {
    text-align: center;
}

.rs-rep-stat-valor {
    display: block;
    font-size: 20px;
    font-weight: 600;
    color: var(--rs-primary);
}

.rs-rep-stat-label {
    font-size: 12px;
    color: var(--rs-text-muted);
}

.rs-puntos-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rs-punto-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--rs-border);
}

.rs-punto-item:last-child {
    border-bottom: none;
}

.rs-punto-accion {
    font-size: 13px;
    color: var(--rs-text);
}

.rs-punto-valor {
    font-weight: 600;
    font-size: 14px;
}

.rs-punto-valor.rs-positivo {
    color: var(--rs-success);
}

.rs-punto-valor.rs-negativo {
    color: var(--rs-danger);
}

@media (max-width: 900px) {
    .rs-actividad-layout {
        grid-template-columns: 1fr;
    }

    .rs-actividad-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .rs-actividad-usuario {
        flex-direction: column;
        text-align: center;
    }

    .rs-filtro-btn span {
        display: none;
    }

    .rs-actividad-ver {
        display: none;
    }
}
</style>
