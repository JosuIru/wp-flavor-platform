<?php
/**
 * Template Landing Completa - Restaurante
 *
 * Variables: $nombre, $eslogan, $telefono, $direccion, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Nuestro Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN);
$eslogan = $eslogan ?? __('Cocina tradicional con un toque moderno', FLAVOR_PLATFORM_TEXT_DOMAIN);
$telefono = $telefono ?? '900 000 000';
$direccion = $direccion ?? __('Calle Principal, 123', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color_primario = $color_primario ?? '#b91c1c';
$horario = $horario ?? __('Lun-Dom: 13:00-16:00 / 20:00-23:30', FLAVOR_PLATFORM_TEXT_DOMAIN);

$platos_destacados = $platos_destacados ?? [
    ['nombre' => __('Paella Valenciana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Arroz con mariscos frescos del día', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '18€', 'imagen' => ''],
    ['nombre' => __('Chuletón a la Brasa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('500g de ternera gallega madurada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '24€', 'imagen' => ''],
    ['nombre' => __('Pulpo a la Gallega', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Con pimentón de la Vera y aceite de oliva', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '16€', 'imagen' => ''],
];

$caracteristicas = $caracteristicas ?? [
    ['icono' => 'carrot', 'titulo' => __('Productos Frescos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Ingredientes de mercado diario', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'groups', 'titulo' => __('Eventos Privados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Salones para celebraciones', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'car', 'titulo' => __('Parking Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Aparcamiento para clientes', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'superhero', 'titulo' => __('Menú Infantil', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Opciones para los pequeños', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
?>

<!-- Hero Section -->
<section class="flavor-restaurante-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-hero-overlay">
        <div class="flavor-hero-content">
            <h1 class="flavor-hero-title"><?php echo esc_html($nombre); ?></h1>
            <p class="flavor-hero-subtitle"><?php echo esc_html($eslogan); ?></p>
            <div class="flavor-hero-buttons">
                <a href="#reservar" class="flavor-btn-primary"><?php esc_html_e('Reservar Mesa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="#carta" class="flavor-btn-secondary"><?php esc_html_e('Ver Carta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>
            <div class="flavor-hero-info">
                <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($horario); ?></span>
                <span><span class="dashicons dashicons-phone"></span> <?php echo esc_html($telefono); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Platos Destacados -->
<section class="flavor-restaurante-platos" id="carta">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Platos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Descubre nuestras especialidades de la casa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-platos-grid">
            <?php foreach ($platos_destacados as $plato): ?>
                <div class="flavor-plato-card">
                    <div class="flavor-plato-imagen">
                        <?php if (!empty($plato['imagen'])): ?>
                            <img src="<?php echo esc_url($plato['imagen']); ?>" alt="<?php echo esc_attr($plato['nombre']); ?>">
                        <?php else: ?>
                            <div class="flavor-plato-placeholder">
                                <span class="dashicons dashicons-food"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-plato-info">
                        <h3><?php echo esc_html($plato['nombre']); ?></h3>
                        <p><?php echo esc_html($plato['descripcion']); ?></p>
                        <span class="flavor-plato-precio"><?php echo esc_html($plato['precio']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flavor-ver-carta">
            <a href="#carta-completa" class="flavor-btn-outline" data-scroll>
                <?php esc_html_e('Ver Carta Completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    </div>
</section>

<!-- Características -->
<section class="flavor-restaurante-caracteristicas">
    <div class="flavor-container">
        <div class="flavor-caracteristicas-grid">
            <?php foreach ($caracteristicas as $caract): ?>
                <div class="flavor-caracteristica">
                    <div class="flavor-caracteristica-icono" style="background: <?php echo esc_attr($color_primario); ?>15; color: <?php echo esc_attr($color_primario); ?>;">
                        <span class="dashicons dashicons-<?php echo esc_attr($caract['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($caract['titulo']); ?></h3>
                    <p><?php echo esc_html($caract['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Reservas CTA -->
<section class="flavor-restaurante-cta" id="reservar" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-cta-content">
            <h2><?php esc_html_e('¿Listo para una experiencia gastronómica?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Reserva tu mesa y disfruta de nuestra cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-cta-buttons">
                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>" class="flavor-btn-white">
                    <span class="dashicons dashicons-phone"></span>
                    <?php echo esc_html($telefono); ?>
                </a>
                <a href="#formulario-reserva" class="flavor-btn-white-outline">
                    <?php esc_html_e('Reservar Online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Ubicación -->
<section class="flavor-restaurante-ubicacion">
    <div class="flavor-container">
        <div class="flavor-ubicacion-wrapper">
            <div class="flavor-ubicacion-info">
                <h2><?php esc_html_e('Encuéntranos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="flavor-ubicacion-detalle">
                    <span class="dashicons dashicons-location"></span>
                    <p><?php echo esc_html($direccion); ?></p>
                </div>
                <div class="flavor-ubicacion-detalle">
                    <span class="dashicons dashicons-clock"></span>
                    <p><?php echo esc_html($horario); ?></p>
                </div>
                <div class="flavor-ubicacion-detalle">
                    <span class="dashicons dashicons-phone"></span>
                    <p><?php echo esc_html($telefono); ?></p>
                </div>
            </div>
            <div class="flavor-ubicacion-mapa">
                <div class="flavor-mapa-placeholder">
                    <span class="dashicons dashicons-location-alt"></span>
                    <p><?php esc_html_e('Mapa interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-restaurante-hero {
    min-height: 80vh;
    background: linear-gradient(135deg, #1f1f1f 0%, #2d2d2d 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-restaurante-hero .flavor-hero-overlay {
    text-align: center;
    padding: 2rem;
    color: white;
}
.flavor-restaurante-hero .flavor-hero-title {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 700;
    margin: 0 0 1rem;
    letter-spacing: -0.02em;
}
.flavor-restaurante-hero .flavor-hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    margin: 0 0 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}
.flavor-hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.flavor-btn-primary {
    padding: 1rem 2rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
}
.flavor-btn-secondary {
    padding: 1rem 2rem;
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-secondary:hover {
    border-color: white;
    background: rgba(255,255,255,0.1);
}
.flavor-hero-info {
    display: flex;
    gap: 2rem;
    justify-content: center;
    flex-wrap: wrap;
    font-size: 0.9rem;
    opacity: 0.8;
}
.flavor-hero-info span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.flavor-hero-info .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Platos */
.flavor-restaurante-platos {
    padding: 5rem 0;
    background: #fafafa;
}
.flavor-section-title {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-section-subtitle {
    text-align: center;
    color: #6b7280;
    margin: 0 0 3rem;
}
.flavor-platos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}
.flavor-plato-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}
.flavor-plato-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}
.flavor-plato-imagen {
    height: 200px;
    background: #f3f4f6;
}
.flavor-plato-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-plato-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}
.flavor-plato-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
}
.flavor-plato-info {
    padding: 1.5rem;
}
.flavor-plato-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-plato-info p {
    color: #6b7280;
    margin: 0 0 1rem;
    font-size: 0.9rem;
}
.flavor-plato-precio {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primario);
}
.flavor-ver-carta {
    text-align: center;
}
.flavor-btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    border: 2px solid var(--color-primario);
    color: var(--color-primario);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-outline:hover {
    background: var(--color-primario);
    color: white;
}

/* Características */
.flavor-restaurante-caracteristicas {
    padding: 5rem 0;
    background: white;
}
.flavor-caracteristicas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
}
.flavor-caracteristica {
    text-align: center;
    padding: 2rem;
}
.flavor-caracteristica-icono {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}
.flavor-caracteristica-icono .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}
.flavor-caracteristica h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-caracteristica p {
    color: #6b7280;
    margin: 0;
    font-size: 0.9rem;
}

/* CTA */
.flavor-restaurante-cta {
    padding: 5rem 0;
    color: white;
    text-align: center;
}
.flavor-cta-content h2 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem;
}
.flavor-cta-content p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0 0 2rem;
}
.flavor-cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
.flavor-btn-white {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: white;
    color: #1f2937;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.2s;
}
.flavor-btn-white:hover {
    transform: translateY(-2px);
}
.flavor-btn-white-outline {
    padding: 1rem 2rem;
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.5);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-white-outline:hover {
    border-color: white;
    background: rgba(255,255,255,0.1);
}

/* Ubicación */
.flavor-restaurante-ubicacion {
    padding: 5rem 0;
    background: #f9fafb;
}
.flavor-ubicacion-wrapper {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 3rem;
    align-items: center;
}
.flavor-ubicacion-info h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 2rem;
    color: #1f2937;
}
.flavor-ubicacion-detalle {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}
.flavor-ubicacion-detalle .dashicons {
    color: var(--color-primario);
    margin-top: 0.25rem;
}
.flavor-ubicacion-detalle p {
    margin: 0;
    color: #4b5563;
}
.flavor-ubicacion-mapa {
    border-radius: 16px;
    overflow: hidden;
    height: 300px;
}
.flavor-mapa-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}
.flavor-mapa-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .flavor-restaurante-hero {
        min-height: 70vh;
    }
    .flavor-ubicacion-wrapper {
        grid-template-columns: 1fr;
    }
    .flavor-hero-info {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
