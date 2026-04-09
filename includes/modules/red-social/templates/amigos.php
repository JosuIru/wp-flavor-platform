<?php
/**
 * Template: Amigos - Lista de amigos/seguidores
 *
 * Muestra la lista de seguidores y usuarios que sigue el usuario actual.
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
    echo '<p>' . esc_html__('Debes iniciar sesion para ver tus amigos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('red_social', 'amigos'))) . '" class="rs-btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

// Permitir ver perfil de otro usuario via parametro GET
$perfil_usuario_id = isset($_GET['rs_usuario']) ? absint($_GET['rs_usuario']) : $usuario_id;
$es_perfil_propio = ($perfil_usuario_id === $usuario_id);

global $wpdb;
$tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
$tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

// Obtener tipo de listado (seguidores o siguiendo)
$tipo_lista = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'seguidores';
$pagina_actual = isset($_GET['pag']) ? max(1, absint($_GET['pag'])) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener datos del usuario del perfil
$usuario_perfil = get_userdata($perfil_usuario_id);
if (!$usuario_perfil) {
    echo '<div class="rs-error">' . esc_html__('Usuario no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    return;
}

// Contar totales
$total_seguidores = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguido_id = %d",
    $perfil_usuario_id
));

$total_siguiendo = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_seguimientos WHERE seguidor_id = %d",
    $perfil_usuario_id
));

// Obtener lista segun el tipo
if ($tipo_lista === 'siguiendo') {
    $usuarios_lista = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_login, s.fecha_seguimiento
         FROM $tabla_seguimientos s
         INNER JOIN {$wpdb->users} u ON s.seguido_id = u.ID
         WHERE s.seguidor_id = %d
         ORDER BY s.fecha_seguimiento DESC
         LIMIT %d OFFSET %d",
        $perfil_usuario_id,
        $por_pagina,
        $offset
    ));
    $total_usuarios = $total_siguiendo;
} else {
    $usuarios_lista = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_login, s.fecha_seguimiento
         FROM $tabla_seguimientos s
         INNER JOIN {$wpdb->users} u ON s.seguidor_id = u.ID
         WHERE s.seguido_id = %d
         ORDER BY s.fecha_seguimiento DESC
         LIMIT %d OFFSET %d",
        $perfil_usuario_id,
        $por_pagina,
        $offset
    ));
    $total_usuarios = $total_seguidores;
}

$total_paginas = ceil($total_usuarios / $por_pagina);
?>

<div class="rs-container">
    <div class="rs-amigos">
        <!-- Cabecera del perfil -->
        <div class="rs-amigos-header">
            <div class="rs-amigos-usuario">
                <img class="rs-amigos-avatar"
                     src="<?php echo esc_url(get_avatar_url($perfil_usuario_id, ['size' => 60])); ?>"
                     alt="">
                <div class="rs-amigos-info">
                    <h1 class="rs-amigos-nombre"><?php echo esc_html($usuario_perfil->display_name); ?></h1>
                    <span class="rs-amigos-username">@<?php echo esc_html($usuario_perfil->user_login); ?></span>
                </div>
            </div>

            <?php if (!$es_perfil_propio): ?>
                <?php
                $ya_sigue = (bool) $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
                    $usuario_id,
                    $perfil_usuario_id
                ));
                ?>
                <button class="rs-btn-seguir <?php echo $ya_sigue ? 'rs-siguiendo' : ''; ?>"
                        data-usuario-id="<?php echo esc_attr($perfil_usuario_id); ?>">
                    <?php echo $ya_sigue ? esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>

        <!-- Tabs de navegacion -->
        <div class="rs-amigos-tabs">
            <a href="?tipo=seguidores<?php echo !$es_perfil_propio ? '&rs_usuario=' . $perfil_usuario_id : ''; ?>"
               class="rs-amigos-tab <?php echo $tipo_lista === 'seguidores' ? 'active' : ''; ?>">
                <span class="rs-tab-count"><?php echo number_format($total_seguidores); ?></span>
                <span class="rs-tab-label"><?php echo esc_html__('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="?tipo=siguiendo<?php echo !$es_perfil_propio ? '&rs_usuario=' . $perfil_usuario_id : ''; ?>"
               class="rs-amigos-tab <?php echo $tipo_lista === 'siguiendo' ? 'active' : ''; ?>">
                <span class="rs-tab-count"><?php echo number_format($total_siguiendo); ?></span>
                <span class="rs-tab-label"><?php echo esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>

        <!-- Buscador -->
        <div class="rs-amigos-buscar">
            <div class="rs-buscar-input-wrapper">
                <svg class="rs-buscar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                </svg>
                <input type="text"
                       class="rs-buscar-input"
                       id="rs-buscar-amigos"
                       placeholder="<?php echo esc_attr__('Buscar usuarios...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
        </div>

        <!-- Lista de usuarios -->
        <div class="rs-amigos-lista" id="rs-amigos-lista">
            <?php if (empty($usuarios_lista)): ?>
                <div class="rs-amigos-vacio">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <?php if ($tipo_lista === 'seguidores'): ?>
                        <p><?php echo esc_html__('Aun no hay seguidores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php if ($es_perfil_propio): ?>
                            <span class="rs-amigos-sugerencia"><?php echo esc_html__('Comparte tu perfil para conseguir seguidores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?php echo esc_html__('Aun no sigue a nadie.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php if ($es_perfil_propio): ?>
                            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('red_social', 'explorar')); ?>" class="rs-btn-primary"><?php echo esc_html__('Explorar usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($usuarios_lista as $usuario_item): ?>
                    <?php
                    // Verificar si el usuario actual sigue a este usuario
                    $sigo_usuario = false;
                    if ($usuario_id && $usuario_item->ID != $usuario_id) {
                        $sigo_usuario = (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
                            $usuario_id,
                            $usuario_item->ID
                        ));
                    }

                    // Obtener bio del usuario
                    $bio_usuario = $wpdb->get_var($wpdb->prepare(
                        "SELECT bio FROM $tabla_perfiles WHERE usuario_id = %d",
                        $usuario_item->ID
                    ));
                    ?>
                    <div class="rs-amigo-card" data-usuario-id="<?php echo esc_attr($usuario_item->ID); ?>">
                        <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($usuario_item->ID), Flavor_Chat_Helpers::get_action_url('red_social', 'perfil'))); ?>" class="rs-amigo-link">
                            <img class="rs-amigo-avatar"
                                 src="<?php echo esc_url(get_avatar_url($usuario_item->ID, ['size' => 56])); ?>"
                                 alt="">
                            <div class="rs-amigo-info">
                                <h3 class="rs-amigo-nombre"><?php echo esc_html($usuario_item->display_name); ?></h3>
                                <span class="rs-amigo-username">@<?php echo esc_html($usuario_item->user_login); ?></span>
                                <?php if ($bio_usuario): ?>
                                    <p class="rs-amigo-bio"><?php echo esc_html(wp_trim_words($bio_usuario, 15)); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php if ($usuario_item->ID != $usuario_id): ?>
                            <button class="rs-btn-seguir-mini <?php echo $sigo_usuario ? 'rs-siguiendo' : ''; ?>"
                                    data-usuario-id="<?php echo esc_attr($usuario_item->ID); ?>">
                                <?php echo $sigo_usuario ? esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginacion -->
        <?php if ($total_paginas > 1): ?>
            <div class="rs-paginacion">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?tipo=<?php echo esc_attr($tipo_lista); ?>&pag=<?php echo $pagina_actual - 1; ?><?php echo !$es_perfil_propio ? '&rs_usuario=' . $perfil_usuario_id : ''; ?>"
                       class="rs-paginacion-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <?php echo esc_html__('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>

                <span class="rs-paginacion-info">
                    <?php printf(
                        esc_html__('Pagina %d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $pagina_actual,
                        $total_paginas
                    ); ?>
                </span>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?tipo=<?php echo esc_attr($tipo_lista); ?>&pag=<?php echo $pagina_actual + 1; ?><?php echo !$es_perfil_propio ? '&rs_usuario=' . $perfil_usuario_id : ''; ?>"
                       class="rs-paginacion-btn">
                        <?php echo esc_html__('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Sugerencias de usuarios (solo en perfil propio) -->
        <?php if ($es_perfil_propio && $tipo_lista === 'siguiendo'): ?>
            <?php
            $sugerencias = $wpdb->get_results($wpdb->prepare(
                "SELECT u.ID, u.display_name, u.user_login
                 FROM {$wpdb->users} u
                 WHERE u.ID != %d
                 AND u.ID NOT IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                 ORDER BY RAND()
                 LIMIT 5",
                $usuario_id,
                $usuario_id
            ));

            if (!empty($sugerencias)):
            ?>
                <div class="rs-amigos-sugerencias">
                    <h3 class="rs-sugerencias-titulo"><?php echo esc_html__('Personas que quizas conozcas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="rs-sugerencias-lista">
                        <?php foreach ($sugerencias as $sugerencia): ?>
                            <div class="rs-sugerencia-card">
                                <img class="rs-sugerencia-avatar"
                                     src="<?php echo esc_url(get_avatar_url($sugerencia->ID, ['size' => 48])); ?>"
                                     alt="">
                                <div class="rs-sugerencia-info">
                                    <h4><?php echo esc_html($sugerencia->display_name); ?></h4>
                                    <span>@<?php echo esc_html($sugerencia->user_login); ?></span>
                                </div>
                                <button class="rs-btn-seguir-mini" data-usuario-id="<?php echo esc_attr($sugerencia->ID); ?>">
                                    <?php echo esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.rs-amigos {
    max-width: 680px;
    margin: 0 auto;
    padding: 24px 0;
}

.rs-amigos-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    margin-bottom: 16px;
}

.rs-amigos-usuario {
    display: flex;
    align-items: center;
    gap: 16px;
}

.rs-amigos-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-amigos-nombre {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 600;
}

.rs-amigos-username {
    color: var(--rs-text-muted);
}

.rs-amigos-tabs {
    display: flex;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    margin-bottom: 16px;
    overflow: hidden;
}

.rs-amigos-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    text-decoration: none;
    color: var(--rs-text-muted);
    border-bottom: 3px solid transparent;
    transition: var(--rs-transition);
}

.rs-amigos-tab:hover,
.rs-amigos-tab.active {
    color: var(--rs-primary);
    border-bottom-color: var(--rs-primary);
    background: rgba(99, 102, 241, 0.05);
}

.rs-tab-count {
    font-size: 20px;
    font-weight: 700;
}

.rs-tab-label {
    font-size: 14px;
}

.rs-amigos-buscar {
    margin-bottom: 16px;
}

.rs-buscar-input-wrapper {
    position: relative;
}

.rs-buscar-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rs-text-muted);
}

.rs-buscar-input {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 1px solid var(--rs-border);
    border-radius: var(--rs-radius);
    font-size: 15px;
    background: var(--rs-bg-card);
    transition: var(--rs-transition);
}

.rs-buscar-input:focus {
    outline: none;
    border-color: var(--rs-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.rs-amigos-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rs-amigo-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    transition: var(--rs-transition);
}

.rs-amigo-card:hover {
    box-shadow: var(--rs-shadow-lg);
}

.rs-amigo-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    flex: 1;
    min-width: 0;
}

.rs-amigo-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.rs-amigo-info {
    min-width: 0;
}

.rs-amigo-nombre {
    margin: 0 0 2px;
    font-size: 16px;
    font-weight: 600;
}

.rs-amigo-username {
    color: var(--rs-text-muted);
    font-size: 14px;
}

.rs-amigo-bio {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--rs-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rs-btn-seguir-mini {
    padding: 8px 20px;
    border: 1px solid var(--rs-primary);
    background: transparent;
    color: var(--rs-primary);
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: var(--rs-transition);
    flex-shrink: 0;
}

.rs-btn-seguir-mini:hover {
    background: var(--rs-primary);
    color: white;
}

.rs-btn-seguir-mini.rs-siguiendo {
    background: var(--rs-bg-light);
    border-color: var(--rs-border);
    color: var(--rs-text-muted);
}

.rs-btn-seguir-mini.rs-siguiendo:hover {
    background: var(--rs-danger);
    border-color: var(--rs-danger);
    color: white;
}

.rs-amigos-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
}

.rs-amigos-vacio svg {
    color: var(--rs-text-muted);
    margin-bottom: 16px;
}

.rs-amigos-vacio p {
    font-size: 18px;
    margin: 0 0 8px;
    color: var(--rs-text);
}

.rs-amigos-sugerencia {
    color: var(--rs-text-muted);
    font-size: 14px;
}

.rs-paginacion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    padding: 16px;
}

.rs-paginacion-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--rs-bg-card);
    border: 1px solid var(--rs-border);
    border-radius: var(--rs-radius-sm);
    color: var(--rs-text);
    text-decoration: none;
    font-weight: 500;
    transition: var(--rs-transition);
}

.rs-paginacion-btn:hover {
    background: var(--rs-primary);
    border-color: var(--rs-primary);
    color: white;
}

.rs-paginacion-info {
    color: var(--rs-text-muted);
}

.rs-amigos-sugerencias {
    margin-top: 32px;
    padding: 24px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
}

.rs-sugerencias-titulo {
    margin: 0 0 16px;
    font-size: 16px;
    font-weight: 600;
}

.rs-sugerencias-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rs-sugerencia-card {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rs-sugerencia-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-sugerencia-info {
    flex: 1;
}

.rs-sugerencia-info h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.rs-sugerencia-info span {
    color: var(--rs-text-muted);
    font-size: 13px;
}

@media (max-width: 640px) {
    .rs-amigos-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }

    .rs-amigos-usuario {
        flex-direction: column;
    }

    .rs-amigo-bio {
        display: none;
    }
}
</style>
