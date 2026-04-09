<?php
/**
 * Template Landing - Inmobiliaria
 *
 * Variables: $nombre, $eslogan, $telefono, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Inmobiliaria', FLAVOR_PLATFORM_TEXT_DOMAIN);
$eslogan = $eslogan ?? __('Encuentra tu hogar perfecto', FLAVOR_PLATFORM_TEXT_DOMAIN);
$telefono = $telefono ?? '900 000 000';
$color_primario = $color_primario ?? '#059669';

$propiedades = $propiedades ?? [
    ['titulo' => __('Piso en el Centro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'tipo' => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '185.000€', 'habitaciones' => 3, 'metros' => 95, 'ubicacion' => __('Centro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['titulo' => __('Chalet con Jardín', FLAVOR_PLATFORM_TEXT_DOMAIN), 'tipo' => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '320.000€', 'habitaciones' => 4, 'metros' => 180, 'ubicacion' => __('Urbanización', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['titulo' => __('Apartamento con Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN), 'tipo' => __('Alquiler', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '850€/mes', 'habitaciones' => 2, 'metros' => 70, 'ubicacion' => __('Ensanche', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
];

$stats = $stats ?? [
    ['numero' => '500+', 'texto' => __('Propiedades', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['numero' => '15', 'texto' => __('Años de experiencia', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['numero' => '2.000+', 'texto' => __('Clientes satisfechos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['numero' => '98%', 'texto' => __('Éxito en ventas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];

$servicios = $servicios ?? [
    ['icono' => 'admin-home', 'titulo' => __('Compra-Venta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Asesoramiento completo en la compra o venta de tu inmueble', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'admin-multisite', 'titulo' => __('Alquileres', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Gestión integral de alquileres para propietarios e inquilinos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'clipboard', 'titulo' => __('Valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Tasación profesional y gratuita de tu propiedad', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'money-alt', 'titulo' => __('Hipotecas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Te ayudamos a conseguir la mejor financiación', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
?>

<!-- Hero con Buscador -->
<section class="flavor-inmo-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-hero-overlay">
        <div class="flavor-container">
            <div class="flavor-hero-content">
                <h1><?php echo esc_html($eslogan); ?></h1>
                <p><?php esc_html_e('Miles de propiedades te esperan', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <!-- Buscador -->
            <div class="flavor-inmo-buscador">
                <div class="flavor-buscador-tabs">
                    <button class="is-active"><?php esc_html_e('Comprar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button><?php esc_html_e('Alquilar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
                <div class="flavor-buscador-form">
                    <div class="flavor-form-group flavor-form-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <input type="text" placeholder="<?php esc_attr_e('Ciudad, zona o código postal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="flavor-form-group">
                        <select>
                            <option value=""><?php esc_html_e('Tipo de inmueble', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php esc_html_e('Piso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php esc_html_e('Casa/Chalet', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option><?php esc_html_e('Local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div class="flavor-form-group">
                        <select>
                            <option value=""><?php esc_html_e('Precio máximo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option>100.000€</option>
                            <option>200.000€</option>
                            <option>300.000€</option>
                            <option>500.000€</option>
                        </select>
                    </div>
                    <button type="submit" class="flavor-btn-buscar">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="flavor-inmo-stats">
    <div class="flavor-container">
        <div class="flavor-stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="flavor-stat">
                    <span class="flavor-stat-numero"><?php echo esc_html($stat['numero']); ?></span>
                    <span class="flavor-stat-texto"><?php echo esc_html($stat['texto']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Propiedades Destacadas -->
<section class="flavor-inmo-propiedades" id="propiedades">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Propiedades Destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Las mejores oportunidades del mercado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-propiedades-grid">
            <?php foreach ($propiedades as $prop): ?>
                <article class="flavor-propiedad-card">
                    <div class="flavor-propiedad-imagen">
                        <?php if (!empty($prop['imagen'])): ?>
                            <img src="<?php echo esc_url($prop['imagen']); ?>" alt="<?php echo esc_attr($prop['titulo']); ?>">
                        <?php else: ?>
                            <div class="flavor-imagen-placeholder">
                                <span class="dashicons dashicons-admin-home"></span>
                            </div>
                        <?php endif; ?>
                        <span class="flavor-propiedad-tipo"><?php echo esc_html($prop['tipo']); ?></span>
                    </div>
                    <div class="flavor-propiedad-info">
                        <span class="flavor-propiedad-precio"><?php echo esc_html($prop['precio']); ?></span>
                        <h3><?php echo esc_html($prop['titulo']); ?></h3>
                        <p class="flavor-propiedad-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($prop['ubicacion']); ?>
                        </p>
                        <div class="flavor-propiedad-detalles">
                            <span>
                                <span class="dashicons dashicons-admin-home"></span>
                                <?php echo esc_html($prop['habitaciones']); ?> <?php esc_html_e('hab.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <span>
                                <span class="dashicons dashicons-editor-expand"></span>
                                <?php echo esc_html($prop['metros']); ?> m²
                            </span>
                        </div>
                    </div>
                    <a href="#propiedades" class="flavor-propiedad-link"></a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-ver-todas">
            <a href="#propiedades" class="flavor-btn-outline">
                <?php esc_html_e('Ver todas las propiedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    </div>
</section>

<!-- Servicios -->
<section class="flavor-inmo-servicios" id="servicios">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestros Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Todo lo que necesitas para tu operación inmobiliaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-servicios-grid">
            <?php foreach ($servicios as $servicio): ?>
                <div class="flavor-servicio-card">
                    <div class="flavor-servicio-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($servicio['titulo']); ?></h3>
                    <p><?php echo esc_html($servicio['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Valoración -->
<section class="flavor-inmo-cta" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-cta-wrapper">
            <div class="flavor-cta-text">
                <h2><?php esc_html_e('¿Quieres vender tu propiedad?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p><?php esc_html_e('Solicita una valoración gratuita y sin compromiso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="flavor-cta-actions">
                <a href="#servicios" class="flavor-btn-white"><?php esc_html_e('Valoración Gratuita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>" class="flavor-btn-white-outline">
                    <span class="dashicons dashicons-phone"></span>
                    <?php echo esc_html($telefono); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-inmo-hero {
    min-height: 70vh;
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    display: flex;
    align-items: center;
    padding: 4rem 0;
}
.flavor-inmo-hero .flavor-hero-overlay {
    width: 100%;
}
.flavor-inmo-hero .flavor-hero-content {
    text-align: center;
    color: white;
    margin-bottom: 3rem;
}
.flavor-inmo-hero h1 {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 700;
    margin: 0 0 0.5rem;
}
.flavor-inmo-hero p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0;
}

/* Buscador */
.flavor-inmo-buscador {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 900px;
    margin: 0 auto;
}
.flavor-buscador-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.flavor-buscador-tabs button {
    padding: 0.75rem 1.5rem;
    background: #f3f4f6;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-buscador-tabs button.is-active {
    background: var(--color-primario);
    color: white;
}
.flavor-buscador-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}
.flavor-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.flavor-form-ubicacion {
    position: relative;
}
.flavor-form-ubicacion .dashicons {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}
.flavor-form-ubicacion input {
    padding-left: 2.5rem;
}
.flavor-form-group input,
.flavor-form-group select {
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}
.flavor-form-group input:focus,
.flavor-form-group select:focus {
    outline: none;
    border-color: var(--color-primario);
}
.flavor-btn-buscar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: var(--color-primario);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-btn-buscar:hover {
    filter: brightness(1.1);
}

/* Stats */
.flavor-inmo-stats {
    padding: 3rem 0;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    text-align: center;
}
.flavor-stat-numero {
    display: block;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--color-primario);
}
.flavor-stat-texto {
    color: #6b7280;
    font-size: 0.9rem;
}

/* Propiedades */
.flavor-inmo-propiedades {
    padding: 5rem 0;
    background: #f9fafb;
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
.flavor-propiedades-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}
.flavor-propiedad-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transition: all 0.3s;
    position: relative;
}
.flavor-propiedad-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}
.flavor-propiedad-imagen {
    height: 200px;
    position: relative;
}
.flavor-propiedad-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-imagen-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}
.flavor-imagen-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
}
.flavor-propiedad-tipo {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--color-primario);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.flavor-propiedad-info {
    padding: 1.5rem;
}
.flavor-propiedad-precio {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primario);
    margin-bottom: 0.5rem;
}
.flavor-propiedad-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-propiedad-ubicacion {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0 0 1rem;
}
.flavor-propiedad-ubicacion .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.flavor-propiedad-detalles {
    display: flex;
    gap: 1.5rem;
    color: #4b5563;
    font-size: 0.875rem;
}
.flavor-propiedad-detalles span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.flavor-propiedad-detalles .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #9ca3af;
}
.flavor-propiedad-link {
    position: absolute;
    inset: 0;
}
.flavor-ver-todas {
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

/* Servicios */
.flavor-inmo-servicios {
    padding: 5rem 0;
    background: white;
}
.flavor-servicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}
.flavor-servicio-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: #f9fafb;
    transition: all 0.3s;
}
.flavor-servicio-card:hover {
    background: var(--color-primario);
    color: white;
    transform: translateY(-4px);
}
.flavor-servicio-icono {
    width: 70px;
    height: 70px;
    background: var(--color-primario);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: all 0.3s;
}
.flavor-servicio-card:hover .flavor-servicio-icono {
    background: white;
}
.flavor-servicio-icono .dashicons {
    color: white;
    font-size: 28px;
    width: 28px;
    height: 28px;
    transition: all 0.3s;
}
.flavor-servicio-card:hover .flavor-servicio-icono .dashicons {
    color: var(--color-primario);
}
.flavor-servicio-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
}
.flavor-servicio-card p {
    font-size: 0.9rem;
    margin: 0;
    opacity: 0.8;
}

/* CTA */
.flavor-inmo-cta {
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
.flavor-cta-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.flavor-btn-white {
    display: inline-block;
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
.flavor-btn-white-outline {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
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

@media (max-width: 768px) {
    .flavor-buscador-form {
        grid-template-columns: 1fr;
    }
    .flavor-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .flavor-cta-wrapper {
        flex-direction: column;
        text-align: center;
    }
}
</style>
