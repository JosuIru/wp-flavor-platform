<?php
/**
 * Template para Ayuntamiento - Sección de Servicios Ciudadanos
 *
 * Variables: $titulo, $servicios, $noticias, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Servicios al Ciudadano', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color_primario = $color_primario ?? '#1d4ed8';
$id_seccion = $id_seccion ?? '';

$servicios_default = [
    [
        'nombre' => __('Cita Previa', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Solicita cita para trámites presenciales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'calendar-alt',
        'url' => '#cita',
        'destacado' => true,
    ],
    [
        'nombre' => __('Sede Electrónica', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Trámites y gestiones online', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'admin-site-alt3',
        'url' => '#sede',
        'destacado' => true,
    ],
    [
        'nombre' => __('Padrón Municipal', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Altas, bajas y certificados', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'id-alt',
        'url' => '#padron',
        'destacado' => false,
    ],
    [
        'nombre' => __('Urbanismo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Licencias y permisos de obra', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'building',
        'url' => '#urbanismo',
        'destacado' => false,
    ],
    [
        'nombre' => __('Tributos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('IBI, tasas y pagos municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'money-alt',
        'url' => '#tributos',
        'destacado' => false,
    ],
    [
        'nombre' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Reportar averías y problemas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'warning',
        'url' => '#incidencias',
        'destacado' => false,
    ],
];

$servicios = $servicios ?? $servicios_default;

$accesos_rapidos = [
    ['nombre' => __('Transportes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'car', 'url' => '#'],
    ['nombre' => __('Empleo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'businessperson', 'url' => '#'],
    ['nombre' => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'art', 'url' => '#'],
    ['nombre' => __('Deportes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'heart', 'url' => '#'],
    ['nombre' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'welcome-learn-more', 'url' => '#'],
    ['nombre' => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'palmtree', 'url' => '#'],
];
?>

<section<?php if ($id_seccion): ?> id="<?php echo esc_attr($id_seccion); ?>"<?php endif; ?> class="flavor-ayto-servicios" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-ayto-header">
            <div class="flavor-ayto-escudo">
                <?php
                $custom_logo_id = get_theme_mod('custom_logo');
                if ($custom_logo_id) {
                    echo wp_get_attachment_image($custom_logo_id, 'thumbnail', false, ['class' => 'flavor-escudo-img']);
                } else {
                    echo '<span class="dashicons dashicons-bank"></span>';
                }
                ?>
            </div>
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
        </div>

        <!-- Servicios destacados -->
        <div class="flavor-servicios-destacados">
            <?php foreach ($servicios as $servicio):
                if (!$servicio['destacado']) continue;
            ?>
                <a href="<?php echo esc_url($servicio['url']); ?>" class="flavor-servicio-destacado">
                    <div class="flavor-destacado-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    </div>
                    <div class="flavor-destacado-texto">
                        <h3><?php echo esc_html($servicio['nombre']); ?></h3>
                        <p><?php echo esc_html($servicio['descripcion']); ?></p>
                    </div>
                    <span class="flavor-destacado-flecha dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Grid de servicios -->
        <div class="flavor-servicios-grid">
            <?php foreach ($servicios as $servicio):
                if ($servicio['destacado']) continue;
            ?>
                <a href="<?php echo esc_url($servicio['url']); ?>" class="flavor-servicio-item">
                    <span class="flavor-servicio-icono dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    <span class="flavor-servicio-nombre"><?php echo esc_html($servicio['nombre']); ?></span>
                    <span class="flavor-servicio-desc"><?php echo esc_html($servicio['descripcion']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Accesos rápidos -->
        <div class="flavor-accesos-rapidos">
            <h3 class="flavor-accesos-titulo"><?php esc_html_e('Accesos rápidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="flavor-accesos-grid">
                <?php foreach ($accesos_rapidos as $acceso): ?>
                    <a href="<?php echo esc_url($acceso['url']); ?>" class="flavor-acceso-item">
                        <span class="dashicons dashicons-<?php echo esc_attr($acceso['icono']); ?>"></span>
                        <span><?php echo esc_html($acceso['nombre']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contacto -->
        <div class="flavor-ayto-contacto">
            <div class="flavor-contacto-item">
                <span class="dashicons dashicons-phone"></span>
                <div>
                    <strong><?php esc_html_e('Teléfono de atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span>010 / 900 000 000</span>
                </div>
            </div>
            <div class="flavor-contacto-item">
                <span class="dashicons dashicons-email-alt"></span>
                <div>
                    <strong><?php esc_html_e('Correo electrónico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span><?php echo esc_html__('atencion@ayuntamiento.es', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="flavor-contacto-item">
                <span class="dashicons dashicons-clock"></span>
                <div>
                    <strong><?php esc_html_e('Horario de atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span><?php echo esc_html__('L-V 9:00 - 14:00', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-ayto-servicios {
    padding: 4rem 0;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
}
.flavor-ayto-header {
    text-align: center;
    margin-bottom: 2.5rem;
}
.flavor-ayto-escudo {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    background: white;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.flavor-escudo-img {
    max-width: 60px;
    max-height: 60px;
}
.flavor-ayto-escudo .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: var(--color-primario);
}
.flavor-ayto-header .flavor-section-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.flavor-servicios-destacados {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.flavor-servicio-destacado {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--color-primario);
    color: white;
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-servicio-destacado:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(29, 78, 216, 0.3);
}
.flavor-destacado-icono {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.flavor-destacado-icono .dashicons {
    font-size: 24px;
}
.flavor-destacado-texto {
    flex: 1;
}
.flavor-destacado-texto h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
}
.flavor-destacado-texto p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.875rem;
}
.flavor-destacado-flecha {
    opacity: 0.7;
    transition: transform 0.2s;
}
.flavor-servicio-destacado:hover .flavor-destacado-flecha {
    transform: translateX(4px);
}
.flavor-servicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
}
.flavor-servicio-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-servicio-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.flavor-servicio-item .flavor-servicio-icono {
    width: 48px;
    height: 48px;
    background: #eff6ff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    font-size: 24px;
    color: var(--color-primario);
}
.flavor-servicio-item .flavor-servicio-nombre {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}
.flavor-servicio-item .flavor-servicio-desc {
    font-size: 0.8125rem;
    color: #6b7280;
}
.flavor-accesos-rapidos {
    margin-bottom: 2.5rem;
}
.flavor-accesos-titulo {
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 1rem;
    text-align: center;
}
.flavor-accesos-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
}
.flavor-acceso-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: #4b5563;
    font-size: 0.875rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: color 0.2s, box-shadow 0.2s;
}
.flavor-acceso-item:hover {
    color: var(--color-primario);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.flavor-acceso-item .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
.flavor-ayto-contacto {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}
.flavor-contacto-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.flavor-contacto-item > .dashicons {
    width: 40px;
    height: 40px;
    background: #eff6ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primario);
    font-size: 20px;
}
.flavor-contacto-item div {
    display: flex;
    flex-direction: column;
}
.flavor-contacto-item strong {
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 500;
}
.flavor-contacto-item span {
    font-weight: 600;
    color: #1f2937;
}
@media (max-width: 768px) {
    .flavor-ayto-servicios {
        padding: 3rem 0;
    }
    .flavor-ayto-header .flavor-section-title {
        font-size: 1.5rem;
    }
    .flavor-ayto-contacto {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
