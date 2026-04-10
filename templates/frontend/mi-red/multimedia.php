<?php
/**
 * Multimedia - Mi Red Social
 *
 * Galería personal de contenido multimedia.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$galeria = $datos_vista['galeria'] ?? [];
$albumes = $datos_vista['albumes'] ?? [];
?>

<div class="mi-red-multimedia">
    <header class="mi-red-multimedia__header">
        <h1><?php esc_html_e('Mi Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <a href="<?php echo esc_url(home_url('/multimedia/subir/')); ?>" class="mi-red-btn mi-red-btn--primary">
            <?php esc_html_e('Subir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </header>

    <!-- Tabs -->
    <div class="mi-red-tabs">
        <button class="mi-red-tab mi-red-tab--active" data-tab="galeria"><?php esc_html_e('Galería', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button class="mi-red-tab" data-tab="albumes"><?php esc_html_e('Álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>

    <!-- Galería -->
    <div class="mi-red-tab-content" id="tab-galeria">
        <?php if (empty($galeria)) : ?>
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">📸</div>
                <h3><?php esc_html_e('No tienes contenido multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Sube fotos, videos o audios para verlos aquí.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(home_url('/multimedia/subir/')); ?>" class="mi-red-btn mi-red-btn--primary">
                    <?php esc_html_e('Subir contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="mi-red-galeria-grid">
                <?php foreach ($galeria as $item) : ?>
                    <a href="<?php echo esc_url(home_url('/multimedia/ver/' . $item['id'] . '/')); ?>" class="mi-red-galeria-item">
                        <?php if ($item['tipo'] === 'imagen') : ?>
                            <img src="<?php echo esc_url($item['thumbnail_url'] ?? $item['url']); ?>" alt="">
                        <?php elseif ($item['tipo'] === 'video') : ?>
                            <div class="mi-red-galeria-item__video">
                                <img src="<?php echo esc_url($item['thumbnail_url'] ?? ''); ?>" alt="">
                                <span class="mi-red-galeria-item__play">▶️</span>
                            </div>
                        <?php else : ?>
                            <div class="mi-red-galeria-item__audio">
                                <span>🎵</span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Álbumes -->
    <div class="mi-red-tab-content" id="tab-albumes" hidden>
        <?php if (empty($albumes)) : ?>
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">📁</div>
                <p><?php esc_html_e('No tienes álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button class="mi-red-btn mi-red-btn--primary"><?php esc_html_e('Crear álbum', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        <?php else : ?>
            <div class="mi-red-albumes-grid">
                <?php foreach ($albumes as $album) : ?>
                    <a href="#" class="mi-red-album-card">
                        <div class="mi-red-album-card__cover"></div>
                        <h3 class="mi-red-album-card__nombre"><?php echo esc_html($album['nombre'] ?? ''); ?></h3>
                        <span class="mi-red-album-card__count"><?php echo esc_html($album['total'] ?? 0); ?> <?php esc_html_e('elementos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.mi-red-multimedia__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--mir-spacing-4);
}

.mi-red-multimedia__header h1 {
    font-size: var(--mir-font-size-2xl);
    margin: 0;
}

.mi-red-galeria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: var(--mir-spacing-2);
}

.mi-red-galeria-item {
    aspect-ratio: 1;
    border-radius: var(--mir-radius-lg);
    overflow: hidden;
    background: var(--mir-gray-100);
}

.mi-red-galeria-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s;
}

.mi-red-galeria-item:hover img {
    transform: scale(1.05);
}

.mi-red-galeria-item__video,
.mi-red-galeria-item__audio {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--mir-gray-200);
}

.mi-red-galeria-item__play {
    position: absolute;
    font-size: 2rem;
}

.mi-red-galeria-item__audio span {
    font-size: 3rem;
}

.mi-red-albumes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--mir-spacing-4);
}

.mi-red-album-card {
    background: white;
    border-radius: var(--mir-radius-xl);
    overflow: hidden;
    text-decoration: none;
    box-shadow: var(--mir-shadow-sm);
    transition: all 0.2s;
}

.mi-red-album-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--mir-shadow-lg);
}

.mi-red-album-card__cover {
    height: 120px;
    background: linear-gradient(135deg, var(--mir-primary), var(--mir-secondary));
}

.mi-red-album-card__nombre {
    margin: var(--mir-spacing-3) var(--mir-spacing-4) var(--mir-spacing-1);
    font-size: var(--mir-font-size-base);
    color: var(--mir-gray-900);
}

.mi-red-album-card__count {
    display: block;
    margin: 0 var(--mir-spacing-4) var(--mir-spacing-3);
    font-size: var(--mir-font-size-sm);
    color: var(--mir-gray-500);
}
</style>

<script>
document.querySelectorAll('.mi-red-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.mi-red-tab').forEach(t => t.classList.remove('mi-red-tab--active'));
        document.querySelectorAll('.mi-red-tab-content').forEach(c => c.hidden = true);
        this.classList.add('mi-red-tab--active');
        document.getElementById('tab-' + this.dataset.tab).hidden = false;
    });
});
</script>
