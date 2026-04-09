<?php
/**
 * Template Landing - Peluquería / Salón de Belleza
 *
 * Variables: $nombre, $eslogan, $telefono, $direccion, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Salón de Belleza', FLAVOR_PLATFORM_TEXT_DOMAIN);
$eslogan = $eslogan ?? __('Tu imagen, nuestra pasión', FLAVOR_PLATFORM_TEXT_DOMAIN);
$telefono = $telefono ?? '900 000 000';
$color_primario = $color_primario ?? '#be185d';

$servicios = $servicios ?? [
    ['nombre' => __('Corte de Cabello', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 15€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'art'],
    ['nombre' => __('Coloración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 35€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'color-picker'],
    ['nombre' => __('Peinados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 25€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'heart'],
    ['nombre' => __('Tratamientos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 20€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'star-filled'],
    ['nombre' => __('Manicura', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 12€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'admin-customizer'],
    ['nombre' => __('Maquillaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => __('Desde 30€', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'visibility'],
];

$equipo = $equipo ?? [
    ['nombre' => 'María García', 'cargo' => __('Directora & Estilista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['nombre' => 'Laura Martínez', 'cargo' => __('Colorista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['nombre' => 'Ana López', 'cargo' => __('Estilista', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
];
?>

<!-- Hero -->
<section class="flavor-peluqueria-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-hero-content">
        <span class="flavor-hero-badge"><?php esc_html_e('Belleza & Estilo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <h1><?php echo esc_html($nombre); ?></h1>
        <p><?php echo esc_html($eslogan); ?></p>
        <div class="flavor-hero-actions">
            <a href="#cita" class="flavor-btn-primary"><?php esc_html_e('Reservar Cita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>" class="flavor-btn-secondary">
                <span class="dashicons dashicons-phone"></span>
                <?php echo esc_html($telefono); ?>
            </a>
        </div>
    </div>
</section>

<!-- Servicios -->
<section class="flavor-peluqueria-servicios" id="servicios">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestros Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Tratamientos personalizados para realzar tu belleza natural', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-servicios-grid">
            <?php foreach ($servicios as $servicio): ?>
                <div class="flavor-servicio-card">
                    <div class="flavor-servicio-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($servicio['nombre']); ?></h3>
                    <span class="flavor-servicio-precio"><?php echo esc_html($servicio['precio']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Equipo -->
<section class="flavor-peluqueria-equipo">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestro Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Profesionales apasionados por la belleza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-equipo-grid">
            <?php foreach ($equipo as $miembro): ?>
                <div class="flavor-miembro-card">
                    <div class="flavor-miembro-imagen">
                        <?php if (!empty($miembro['imagen'])): ?>
                            <img src="<?php echo esc_url($miembro['imagen']); ?>" alt="<?php echo esc_attr($miembro['nombre']); ?>">
                        <?php else: ?>
                            <div class="flavor-imagen-placeholder">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo esc_html($miembro['nombre']); ?></h3>
                    <p><?php echo esc_html($miembro['cargo']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Reserva -->
<section class="flavor-peluqueria-cta" id="cita" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-cta-wrapper">
            <div class="flavor-cta-text">
                <h2><?php esc_html_e('¿Lista para un cambio?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p><?php esc_html_e('Reserva tu cita y déjate mimar por nuestros expertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="flavor-cta-actions">
                <a href="#cita" class="flavor-btn-white"><?php esc_html_e('Reservar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Galería -->
<section class="flavor-peluqueria-galeria">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestros Trabajos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div class="flavor-galeria-grid">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="flavor-galeria-item">
                    <div class="flavor-galeria-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<style>
.flavor-peluqueria-hero {
    min-height: 70vh;
    background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 2rem;
}
.flavor-peluqueria-hero .flavor-hero-content {
    max-width: 700px;
}
.flavor-hero-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--color-primario);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}
.flavor-peluqueria-hero h1 {
    font-size: clamp(2.5rem, 5vw, 3.5rem);
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1rem;
}
.flavor-peluqueria-hero p {
    font-size: 1.25rem;
    color: #6b7280;
    margin: 0 0 2rem;
}
.flavor-hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
.flavor-btn-primary {
    padding: 1rem 2rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.flavor-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: white;
    color: #1f2937;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s;
}
.flavor-btn-secondary:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* Servicios */
.flavor-peluqueria-servicios {
    padding: 5rem 0;
    background: white;
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
.flavor-servicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
}
.flavor-servicio-card {
    background: #fdf2f8;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    transition: all 0.3s;
}
.flavor-servicio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.flavor-servicio-icono {
    width: 60px;
    height: 60px;
    background: var(--color-primario);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}
.flavor-servicio-icono .dashicons {
    color: white;
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.flavor-servicio-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-servicio-precio {
    color: var(--color-primario);
    font-weight: 600;
}

/* Equipo */
.flavor-peluqueria-equipo {
    padding: 5rem 0;
    background: #f9fafb;
}
.flavor-equipo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
}
.flavor-miembro-card {
    text-align: center;
}
.flavor-miembro-imagen {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 1.5rem;
    border: 4px solid var(--color-primario);
}
.flavor-miembro-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-imagen-placeholder {
    width: 100%;
    height: 100%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-imagen-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
}
.flavor-miembro-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}
.flavor-miembro-card p {
    color: var(--color-primario);
    margin: 0;
    font-size: 0.9rem;
}

/* CTA */
.flavor-peluqueria-cta {
    padding: 4rem 0;
    color: white;
}
.flavor-cta-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}
.flavor-cta-text h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
}
.flavor-cta-text p {
    margin: 0;
    opacity: 0.9;
}
.flavor-btn-white {
    padding: 1rem 2rem;
    background: white;
    color: var(--color-primario);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Galería */
.flavor-peluqueria-galeria {
    padding: 5rem 0;
    background: white;
}
.flavor-galeria-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.flavor-galeria-item {
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
}
.flavor-galeria-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-galeria-placeholder .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #9ca3af;
}

@media (max-width: 768px) {
    .flavor-cta-wrapper {
        text-align: center;
        justify-content: center;
    }
    .flavor-galeria-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
