<?php
/**
 * Template Genérico: Grid de Items
 *
 * Variables disponibles:
 * - $titulo (string): Título de la sección
 * - $subtitulo (string): Subtítulo opcional
 * - $items (array): Elementos a mostrar
 * - $columnas (int): Número de columnas (2, 3, 4)
 * - $mostrar_imagen (bool): Mostrar imagen
 * - $mostrar_descripcion (bool): Mostrar descripción
 * - $mostrar_fecha (bool): Mostrar fecha
 * - $mostrar_precio (bool): Mostrar precio
 * - $color_primario (string): Color primario
 * - $btn_texto (string): Texto del botón
 * - $btn_url (string): URL del botón
 * - $estilo (string): grid, cards, minimal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$items = $items ?? [];
$columnas = intval($columnas ?? 3);
$mostrar_imagen = $mostrar_imagen ?? true;
$mostrar_descripcion = $mostrar_descripcion ?? true;
$mostrar_fecha = $mostrar_fecha ?? false;
$mostrar_precio = $mostrar_precio ?? false;
$color_primario = $color_primario ?? '#4f46e5';
$btn_texto = $btn_texto ?? '';
$btn_url = $btn_url ?? '';
$estilo = $estilo ?? 'cards';

// Generar ID único para estilos
$unique_id = 'fgg-' . wp_unique_id();
?>

<section class="flavor-generic-grid <?php echo esc_attr($unique_id); ?>" style="--fgg-primary: <?php echo esc_attr($color_primario); ?>;">
    <?php if (!empty($titulo) || !empty($subtitulo)) : ?>
    <header class="fgg-header">
        <?php if (!empty($titulo)) : ?>
            <h2 class="fgg-title"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>
        <?php if (!empty($subtitulo)) : ?>
            <p class="fgg-subtitle"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
    <div class="fgg-grid fgg-grid--<?php echo esc_attr($estilo); ?>" style="--fgg-columns: <?php echo esc_attr($columnas); ?>;">
        <?php foreach ($items as $item) :
            $item_titulo = $item['titulo'] ?? $item['nombre'] ?? $item['title'] ?? '';
            $item_desc = $item['descripcion'] ?? $item['description'] ?? $item['excerpt'] ?? '';
            $item_imagen = $item['imagen'] ?? $item['image'] ?? $item['thumbnail'] ?? '';
            $item_url = $item['url'] ?? $item['enlace'] ?? $item['link'] ?? '#';
            $item_fecha = $item['fecha'] ?? $item['date'] ?? $item['fecha_inicio'] ?? '';
            $item_precio = $item['precio'] ?? $item['price'] ?? '';
            $item_badge = $item['badge'] ?? $item['estado'] ?? $item['tipo'] ?? '';
            $item_icon = $item['icon'] ?? $item['icono'] ?? '';
        ?>
        <article class="fgg-item">
            <?php if ($mostrar_imagen && !empty($item_imagen)) : ?>
            <div class="fgg-item__image">
                <img src="<?php echo esc_url($item_imagen); ?>" alt="<?php echo esc_attr($item_titulo); ?>" loading="lazy">
                <?php if (!empty($item_badge)) : ?>
                    <span class="fgg-item__badge"><?php echo esc_html($item_badge); ?></span>
                <?php endif; ?>
            </div>
            <?php elseif (!empty($item_icon)) : ?>
            <div class="fgg-item__icon">
                <span class="dashicons <?php echo esc_attr($item_icon); ?>"></span>
            </div>
            <?php endif; ?>

            <div class="fgg-item__content">
                <?php if (!empty($item_titulo)) : ?>
                    <h3 class="fgg-item__title">
                        <?php if ($item_url !== '#') : ?>
                            <a href="<?php echo esc_url($item_url); ?>"><?php echo esc_html($item_titulo); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($item_titulo); ?>
                        <?php endif; ?>
                    </h3>
                <?php endif; ?>

                <?php if ($mostrar_descripcion && !empty($item_desc)) : ?>
                    <p class="fgg-item__desc"><?php echo esc_html(wp_trim_words($item_desc, 20)); ?></p>
                <?php endif; ?>

                <div class="fgg-item__meta">
                    <?php if ($mostrar_fecha && !empty($item_fecha)) : ?>
                        <span class="fgg-item__date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(is_string($item_fecha) ? date_i18n(get_option('date_format'), strtotime($item_fecha)) : $item_fecha); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($mostrar_precio && !empty($item_precio)) : ?>
                        <span class="fgg-item__price">
                            <?php
                            if (is_numeric($item_precio)) {
                                echo $item_precio > 0 ? esc_html(number_format($item_precio, 2)) . ' €' : esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            } else {
                                echo esc_html($item_precio);
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($item_url !== '#') : ?>
                <a href="<?php echo esc_url($item_url); ?>" class="fgg-item__link">
                    <?php esc_html_e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="fgg-empty">
        <span class="dashicons dashicons-info-outline"></span>
        <p><?php esc_html_e('No hay elementos para mostrar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($btn_texto) && !empty($btn_url)) : ?>
    <footer class="fgg-footer">
        <a href="<?php echo esc_url($btn_url); ?>" class="fgg-btn">
            <?php echo esc_html($btn_texto); ?>
        </a>
    </footer>
    <?php endif; ?>
</section>

<style>
.flavor-generic-grid {
    padding: 60px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.fgg-header {
    text-align: center;
    margin-bottom: 40px;
}

.fgg-title {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.fgg-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.fgg-grid {
    display: grid;
    grid-template-columns: repeat(var(--fgg-columns, 3), 1fr);
    gap: 24px;
}

.fgg-grid--cards .fgg-item {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.fgg-grid--cards .fgg-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

.fgg-grid--minimal .fgg-item {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.fgg-item__image {
    position: relative;
    aspect-ratio: 16/10;
    overflow: hidden;
}

.fgg-item__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.fgg-item__badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--fgg-primary);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.fgg-item__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--fgg-primary), color-mix(in srgb, var(--fgg-primary) 80%, white));
    border-radius: 12px;
    margin-bottom: 16px;
}

.fgg-item__icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: white;
}

.fgg-item__content {
    padding: 20px;
}

.fgg-item__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 8px;
    line-height: 1.4;
}

.fgg-item__title a {
    color: inherit;
    text-decoration: none;
}

.fgg-item__title a:hover {
    color: var(--fgg-primary);
}

.fgg-item__desc {
    font-size: 0.9375rem;
    color: #6b7280;
    margin: 0 0 12px;
    line-height: 1.6;
}

.fgg-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 12px;
}

.fgg-item__meta .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 4px;
}

.fgg-item__price {
    font-weight: 600;
    color: var(--fgg-primary);
}

.fgg-item__link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--fgg-primary);
    text-decoration: none;
}

.fgg-item__link:hover {
    text-decoration: underline;
}

.fgg-item__link .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.fgg-empty {
    text-align: center;
    padding: 60px 20px;
    background: #f9fafb;
    border-radius: 12px;
    color: #6b7280;
}

.fgg-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.fgg-footer {
    text-align: center;
    margin-top: 40px;
}

.fgg-btn {
    display: inline-block;
    padding: 14px 32px;
    background: var(--fgg-primary);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}

.fgg-btn:hover {
    background: color-mix(in srgb, var(--fgg-primary) 85%, black);
    transform: translateY(-2px);
}

@media (max-width: 1024px) {
    .fgg-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .flavor-generic-grid {
        padding: 40px 16px;
    }

    .fgg-title {
        font-size: 1.5rem;
    }

    .fgg-grid {
        grid-template-columns: 1fr;
    }
}
</style>
