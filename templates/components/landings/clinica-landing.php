<?php
/**
 * Template Landing - Clínica / Centro Médico
 *
 * Variables: $nombre, $eslogan, $telefono, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Clínica Salud', FLAVOR_PLATFORM_TEXT_DOMAIN);
$eslogan = $eslogan ?? __('Tu salud en las mejores manos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$telefono = $telefono ?? '900 000 000';
$color_primario = $color_primario ?? '#0891b2';

$especialidades = $especialidades ?? [
    ['nombre' => __('Medicina General', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'heart', 'descripcion' => __('Atención primaria y preventiva', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['nombre' => __('Pediatría', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'groups', 'descripcion' => __('Cuidado infantil especializado', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['nombre' => __('Cardiología', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'performance', 'descripcion' => __('Salud cardiovascular', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['nombre' => __('Dermatología', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'visibility', 'descripcion' => __('Cuidado de la piel', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['nombre' => __('Traumatología', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'universal-access', 'descripcion' => __('Lesiones y rehabilitación', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['nombre' => __('Ginecología', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'admin-users', 'descripcion' => __('Salud de la mujer', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];

$doctores = $doctores ?? [
    ['nombre' => 'Dr. Carlos Martínez', 'especialidad' => __('Director Médico', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['nombre' => 'Dra. Ana Rodríguez', 'especialidad' => __('Cardiología', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
    ['nombre' => 'Dr. Luis García', 'especialidad' => __('Pediatría', FLAVOR_PLATFORM_TEXT_DOMAIN), 'imagen' => ''],
];

$ventajas = $ventajas ?? [
    ['icono' => 'clock', 'titulo' => __('Cita en 24h', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Atención rápida y sin largas esperas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'shield', 'titulo' => __('Seguros Médicos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Trabajamos con las principales aseguradoras', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'admin-home', 'titulo' => __('Instalaciones Modernas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Equipamiento de última tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN)],
    ['icono' => 'phone', 'titulo' => __('Telemedicina', FLAVOR_PLATFORM_TEXT_DOMAIN), 'descripcion' => __('Consultas online desde casa', FLAVOR_PLATFORM_TEXT_DOMAIN)],
];
?>

<!-- Hero -->
<section class="flavor-clinica-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-hero-wrapper">
            <div class="flavor-hero-text">
                <span class="flavor-hero-badge"><?php esc_html_e('Centro Médico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <h1><?php echo esc_html($nombre); ?></h1>
                <p><?php echo esc_html($eslogan); ?></p>
                <div class="flavor-hero-actions">
                    <a href="#cita" class="flavor-btn-primary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Pedir Cita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>" class="flavor-btn-secondary">
                        <span class="dashicons dashicons-phone"></span>
                        <?php esc_html_e('Urgencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
            <div class="flavor-hero-image">
                <div class="flavor-hero-placeholder">
                    <span class="dashicons dashicons-plus-alt"></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Ventajas -->
<section class="flavor-clinica-ventajas">
    <div class="flavor-container">
        <div class="flavor-ventajas-grid">
            <?php foreach ($ventajas as $ventaja): ?>
                <div class="flavor-ventaja">
                    <div class="flavor-ventaja-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($ventaja['icono']); ?>"></span>
                    </div>
                    <div class="flavor-ventaja-texto">
                        <h3><?php echo esc_html($ventaja['titulo']); ?></h3>
                        <p><?php echo esc_html($ventaja['descripcion']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Especialidades -->
<section class="flavor-clinica-especialidades" id="especialidades">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Nuestras Especialidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Atención integral para toda la familia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-especialidades-grid">
            <?php foreach ($especialidades as $esp): ?>
                <a href="#cita" class="flavor-especialidad-card">
                    <div class="flavor-especialidad-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($esp['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($esp['nombre']); ?></h3>
                    <p><?php echo esc_html($esp['descripcion']); ?></p>
                    <span class="flavor-especialidad-arrow">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Equipo Médico -->
<section class="flavor-clinica-equipo">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Equipo Médico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Profesionales comprometidos con tu salud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="flavor-equipo-grid">
            <?php foreach ($doctores as $doctor): ?>
                <div class="flavor-doctor-card">
                    <div class="flavor-doctor-imagen">
                        <?php if (!empty($doctor['imagen'])): ?>
                            <img src="<?php echo esc_url($doctor['imagen']); ?>" alt="<?php echo esc_attr($doctor['nombre']); ?>">
                        <?php else: ?>
                            <div class="flavor-imagen-placeholder">
                                <span class="dashicons dashicons-businessman"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-doctor-info">
                        <h3><?php echo esc_html($doctor['nombre']); ?></h3>
                        <p><?php echo esc_html($doctor['especialidad']); ?></p>
                        <a href="#cita" class="flavor-doctor-cita"><?php esc_html_e('Pedir cita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Cita -->
<section class="flavor-clinica-cta" id="cita" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-cta-content">
            <h2><?php esc_html_e('Reserva tu cita online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Elige especialidad, día y hora que mejor te convenga', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-cta-actions">
                <a href="#cita" class="flavor-btn-white"><?php esc_html_e('Reservar Cita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $telefono)); ?>" class="flavor-btn-white-outline">
                    <span class="dashicons dashicons-phone"></span>
                    <?php echo esc_html($telefono); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-clinica-hero {
    padding: 5rem 0;
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
}
.flavor-hero-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
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
.flavor-hero-text h1 {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1rem;
}
.flavor-hero-text p {
    font-size: 1.125rem;
    color: #4b5563;
    margin: 0 0 2rem;
}
.flavor-hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.flavor-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
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
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
}
.flavor-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: white;
    color: #dc2626;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s;
}
.flavor-btn-secondary:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.flavor-hero-image {
    display: flex;
    justify-content: center;
}
.flavor-hero-placeholder {
    width: 300px;
    height: 300px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 20px 60px rgba(8, 145, 178, 0.2);
}
.flavor-hero-placeholder .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    color: var(--color-primario);
}

/* Ventajas */
.flavor-clinica-ventajas {
    padding: 4rem 0;
    background: white;
}
.flavor-ventajas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}
.flavor-ventaja {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.flavor-ventaja-icono {
    width: 50px;
    height: 50px;
    background: var(--color-primario)15;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.flavor-ventaja-icono .dashicons {
    color: var(--color-primario);
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.flavor-ventaja h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}
.flavor-ventaja p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Especialidades */
.flavor-clinica-especialidades {
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
.flavor-especialidades-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.flavor-especialidad-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
    position: relative;
}
.flavor-especialidad-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.flavor-especialidad-icono {
    width: 60px;
    height: 60px;
    background: var(--color-primario)15;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.flavor-especialidad-icono .dashicons {
    color: var(--color-primario);
    font-size: 28px;
    width: 28px;
    height: 28px;
}
.flavor-especialidad-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}
.flavor-especialidad-card p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}
.flavor-especialidad-arrow {
    position: absolute;
    right: 1.5rem;
    color: #d1d5db;
    transition: all 0.2s;
}
.flavor-especialidad-card:hover .flavor-especialidad-arrow {
    color: var(--color-primario);
    transform: translateX(4px);
}

/* Equipo */
.flavor-clinica-equipo {
    padding: 5rem 0;
    background: white;
}
.flavor-equipo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}
.flavor-doctor-card {
    text-align: center;
}
.flavor-doctor-imagen {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 1.5rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.flavor-doctor-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-imagen-placeholder {
    width: 100%;
    height: 100%;
    background: #f3f4f6;
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
.flavor-doctor-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}
.flavor-doctor-info p {
    color: var(--color-primario);
    margin: 0 0 1rem;
    font-size: 0.9rem;
}
.flavor-doctor-cita {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    border: 2px solid var(--color-primario);
    color: var(--color-primario);
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-doctor-cita:hover {
    background: var(--color-primario);
    color: white;
}

/* CTA */
.flavor-clinica-cta {
    padding: 5rem 0;
    text-align: center;
    color: white;
}
.flavor-cta-content h2 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem;
}
.flavor-cta-content p {
    font-size: 1.125rem;
    margin: 0 0 2rem;
    opacity: 0.9;
}
.flavor-cta-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
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
    .flavor-hero-wrapper {
        grid-template-columns: 1fr;
        text-align: center;
    }
    .flavor-hero-actions {
        justify-content: center;
    }
    .flavor-hero-image {
        order: -1;
    }
    .flavor-hero-placeholder {
        width: 200px;
        height: 200px;
    }
}
</style>
