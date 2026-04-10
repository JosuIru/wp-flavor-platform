<?php
/**
 * Perfil - Mi Red Social
 *
 * Vista del perfil de usuario con sus publicaciones y estadísticas.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$perfil = $datos_vista['perfil'] ?? [];
$publicaciones = $datos_vista['publicaciones'] ?? [];
$estadisticas = $datos_vista['estadisticas'] ?? [];
$es_propio = ($perfil['id'] ?? 0) === ($usuario['id'] ?? 0);
?>

<div class="mi-red-perfil">
    <!-- Header del perfil -->
    <header class="mi-red-perfil__header">
        <div class="mi-red-perfil__cover"></div>
        <div class="mi-red-perfil__info">
            <img src="<?php echo esc_url($perfil['avatar'] ?? ''); ?>"
                 alt="<?php echo esc_attr($perfil['nombre'] ?? ''); ?>"
                 class="mi-red-perfil__avatar">
            <div class="mi-red-perfil__meta">
                <h1 class="mi-red-perfil__nombre"><?php echo esc_html($perfil['nombre'] ?? ''); ?></h1>
                <?php if (!empty($perfil['bio'])) : ?>
                    <p class="mi-red-perfil__bio"><?php echo esc_html($perfil['bio']); ?></p>
                <?php endif; ?>
                <p class="mi-red-perfil__fecha">
                    <?php esc_html_e('Miembro desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($perfil['fecha_registro'] ?? ''))); ?>
                </p>
            </div>
            <div class="mi-red-perfil__actions">
                <?php if ($es_propio) : ?>
                    <button class="mi-red-btn mi-red-btn--outline"><?php esc_html_e('Editar perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php else : ?>
                    <button class="mi-red-btn mi-red-btn--primary" data-action="seguir" data-usuario="<?php echo esc_attr($perfil['id']); ?>">
                        <?php esc_html_e('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="mi-red-btn mi-red-btn--outline"><?php esc_html_e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="mi-red-perfil__stats">
        <div class="mi-red-stat-item">
            <span class="mi-red-stat-item__value"><?php echo esc_html(number_format_i18n($estadisticas['publicaciones'] ?? 0)); ?></span>
            <span class="mi-red-stat-item__label"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="mi-red-stat-item">
            <span class="mi-red-stat-item__value"><?php echo esc_html(number_format_i18n($estadisticas['seguidores'] ?? 0)); ?></span>
            <span class="mi-red-stat-item__label"><?php esc_html_e('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="mi-red-stat-item">
            <span class="mi-red-stat-item__value"><?php echo esc_html(number_format_i18n($estadisticas['siguiendo'] ?? 0)); ?></span>
            <span class="mi-red-stat-item__label"><?php esc_html_e('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="mi-red-stat-item">
            <span class="mi-red-stat-item__value"><?php echo esc_html(number_format_i18n($estadisticas['me_gusta_recibidos'] ?? 0)); ?></span>
            <span class="mi-red-stat-item__label"><?php esc_html_e('Me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Publicaciones -->
    <div class="mi-red-perfil__content">
        <h2 class="mi-red-perfil__section-title"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <?php if (empty($publicaciones)) : ?>
            <div class="mi-red-empty-state">
                <p><?php esc_html_e('No hay publicaciones todavía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php else : ?>
            <div class="mi-red-feed__list">
                <?php foreach ($publicaciones as $item) : ?>
                    <?php include FLAVOR_PLATFORM_PATH . 'templates/frontend/mi-red/partials/feed-item.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
