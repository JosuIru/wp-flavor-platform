<?php
/**
 * Template Landing - Hotel / Alojamiento
 *
 * Variables: $nombre, $eslogan, $telefono, $direccion, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Hotel Boutique', FLAVOR_PLATFORM_TEXT_DOMAIN);
$eslogan = $eslogan ?? __('Tu hogar lejos de casa', FLAVOR_PLATFORM_TEXT_DOMAIN);
$telefono = $telefono ?? '900 000 000';
$direccion = $direccion ?? __('Calle Principal, 123', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color_primario = $color_primario ?? '#7c3aed';
$estrellas = $estrellas ?? 4;

$habitaciones = $habitaciones ?? [
    ['nombre' => __('Individual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Perfecta para viajeros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '59€', 'capacidad' => 1, 'imagen' => ''],
    ['nombre' => __('Doble', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Ideal para parejas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '89€', 'capacidad' => 2, 'imagen' => ''],
    ['nombre' => __('Suite', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Máximo confort y espacio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'precio' => '149€', 'capacidad' => 2, 'imagen' => ''],
];

$servicios = $servicios ?? [
    ['icono' => 'food', 'nombre' => __('Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'car', 'nombre' => __('Parking', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'tide', 'nombre' => __('Piscina', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'desktop', 'nombre' => __('WiFi Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'superhero', 'nombre' => __('Gimnasio', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'coffee', 'nombre' => __('Desayuno', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
?>

<!-- Hero -->
<section class="flavor-hotel-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-hero-overlay">
        <div class="flavor-hero-content">
            <div class="flavor-hotel-estrellas">
                <?php for ($i = 0; $i < $estrellas; $i++): ?>
                    <span class="dashicons dashicons-star-filled"></span>
                <?php endfor; ?>
            </div>
            <h1><?php echo esc_html($nombre); ?></h1>
            <p><?php echo esc_html($eslogan); ?></p>
            <div class="flavor-hero-location">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($direccion); ?>
            </div>
            <a href="#reservar" class="flavor-btn-primary"><?php esc_html_e('Reservar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        </div>
    </div>
</section>

<!-- Servicios -->
<section class="flavor-hotel-servicios">
    <div class="flavor-container">
        <div class="flavor-servicios-bar">
            <?php foreach ($servicios as $servicio): ?>
                <div class="flavor-servicio-item">
                    <span class="dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    <span><?php echo esc_html($servicio['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Habitaciones -->
<section class="flavor-hotel-habitaciones" id="habitaciones">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestras Habitaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Diseñadas para tu descanso y comodidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-habitaciones-grid">
            <?php foreach ($habitaciones as $hab): ?>
                <div class="flavor-habitacion-card">
                    <div class="flavor-habitacion-imagen">
                        <?php if (!empty($hab['imagen'])): ?>
                            <img src="<?php echo esc_url($hab['imagen']); ?>" alt="<?php echo esc_attr($hab['nombre']); ?>">
                        <?php else: ?>
                            <div class="flavor-imagen-placeholder">
                                <span class="dashicons dashicons-admin-home"></span>
                            </div>
                        <?php endif; ?>
                        <span class="flavor-habitacion-capacidad">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($hab['capacidad']); ?>
                        </span>
                    </div>
                    <div class="flavor-habitacion-info">
                        <h3><?php echo esc_html($hab['nombre']); ?></h3>
                        <p><?php echo esc_html($hab['descripcion']); ?></p>
                        <div class="flavor-habitacion-precio">
                            <span class="precio"><?php echo esc_html($hab['precio']); ?></span>
                            <span class="noche"><?php esc_html_e('/noche', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <a href="#reservar" class="flavor-habitacion-btn"><?php esc_html_e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Buscador -->
<section class="flavor-hotel-buscador" id="reservar" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <h2><?php esc_html_e('Comprueba disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div class="flavor-buscador-form">
            <div class="flavor-form-group">
                <label><?php esc_html_e('Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="date" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="flavor-form-group">
                <label><?php esc_html_e('Salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="date" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="flavor-form-group">
                <label><?php esc_html_e('Huéspedes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select>
                    <option>1 <?php esc_html_e('adulto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option selected>2 <?php esc_html_e('adultos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option>3 <?php esc_html_e('adultos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option>4 <?php esc_html_e('adultos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>
            <button type="submit" class="flavor-btn-buscar"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </div>
</section>

<!-- Galería -->
<section class="flavor-hotel-galeria">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Descubre el Hotel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div class="flavor-galeria-grid">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="flavor-galeria-item">
                    <div class="flavor-galeria-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- Ubicación -->
<section class="flavor-hotel-ubicacion">
    <div class="flavor-container">
        <div class="flavor-ubicacion-wrapper">
            <div class="flavor-ubicacion-info">
                <h2><?php esc_html_e('Cómo llegar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-direccion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($direccion); ?>
                </p>
                <div class="flavor-contacto-items">
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>">
                        <span class="dashicons dashicons-phone"></span>
                        <?php echo esc_html($telefono); ?>
                    </a>
                    <a href="mailto:info@hotel.com">
                        <span class="dashicons dashicons-email"></span>
                        info@hotel.com
                    </a>
                </div>
            </div>
            <div class="flavor-ubicacion-mapa">
                <div class="flavor-mapa-placeholder">
                    <span class="dashicons dashicons-location-alt"></span>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-hotel-hero {
    min-height: 85vh;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;
}
.flavor-hotel-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 20%, rgba(124, 58, 237, 0.3) 0%, transparent 50%);
}
.flavor-hero-overlay {
    position: relative;
    z-index: 1;
    padding: 2rem;
    color: white;
}
.flavor-hotel-estrellas {
    margin-bottom: 1rem;
}
.flavor-hotel-estrellas .dashicons {
    color: #fbbf24;
    font-size: 20px;
    width: 20px;
    height: 20px;
}
.flavor-hotel-hero h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 700;
    margin: 0 0 1rem;
    letter-spacing: -0.02em;
}
.flavor-hotel-hero p {
    font-size: 1.25rem;
    opacity: 0.9;
    margin: 0 0 1.5rem;
}
.flavor-hero-location {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 2rem;
}
.flavor-btn-primary {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(124, 58, 237, 0.4);
}

/* Servicios */
.flavor-hotel-servicios {
    background: white;
    padding: 2rem 0;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.flavor-servicios-bar {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2rem;
}
.flavor-servicio-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #4b5563;
    font-size: 0.9rem;
}
.flavor-servicio-item .dashicons {
    color: var(--color-primario);
}

/* Habitaciones */
.flavor-hotel-habitaciones {
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
.flavor-habitaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}
.flavor-habitacion-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transition: all 0.3s;
}
.flavor-habitacion-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}
.flavor-habitacion-imagen {
    height: 200px;
    background: #f3f4f6;
    position: relative;
}
.flavor-habitacion-imagen img {
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
.flavor-habitacion-capacidad {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: white;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.flavor-habitacion-capacidad .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.flavor-habitacion-info {
    padding: 1.5rem;
}
.flavor-habitacion-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-habitacion-info p {
    color: #6b7280;
    margin: 0 0 1rem;
    font-size: 0.9rem;
}
.flavor-habitacion-precio {
    margin-bottom: 1rem;
}
.flavor-habitacion-precio .precio {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-primario);
}
.flavor-habitacion-precio .noche {
    color: #6b7280;
    font-size: 0.9rem;
}
.flavor-habitacion-btn {
    display: block;
    text-align: center;
    padding: 0.75rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-habitacion-btn:hover {
    filter: brightness(1.1);
}

/* Buscador */
.flavor-hotel-buscador {
    padding: 3rem 0;
    color: white;
}
.flavor-hotel-buscador h2 {
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 2rem;
}
.flavor-buscador-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    align-items: flex-end;
}
.flavor-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.flavor-form-group label {
    font-size: 0.875rem;
    font-weight: 500;
}
.flavor-form-group input,
.flavor-form-group select {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    min-width: 150px;
}
.flavor-btn-buscar {
    padding: 0.75rem 2rem;
    background: white;
    color: var(--color-primario);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-btn-buscar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Galería */
.flavor-hotel-galeria {
    padding: 5rem 0;
    background: white;
}
.flavor-galeria-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.flavor-galeria-item {
    aspect-ratio: 4/3;
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

/* Ubicación */
.flavor-hotel-ubicacion {
    padding: 5rem 0;
    background: #f9fafb;
}
.flavor-ubicacion-wrapper {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 3rem;
    align-items: center;
}
.flavor-ubicacion-info h2 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 1.5rem;
    color: #1f2937;
}
.flavor-direccion {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #4b5563;
    margin-bottom: 1.5rem;
}
.flavor-direccion .dashicons {
    color: var(--color-primario);
}
.flavor-contacto-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.flavor-contacto-items a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #4b5563;
    text-decoration: none;
    transition: color 0.2s;
}
.flavor-contacto-items a:hover {
    color: var(--color-primario);
}
.flavor-contacto-items .dashicons {
    color: var(--color-primario);
}
.flavor-ubicacion-mapa {
    height: 300px;
    border-radius: 16px;
    overflow: hidden;
}
.flavor-mapa-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-mapa-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
}

@media (max-width: 768px) {
    .flavor-galeria-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .flavor-ubicacion-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>
