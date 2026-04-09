<?php
/**
 * Template: Servicios Empresariales
 *
 * @package FlavorChatIA
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = esc_html($atts['titulo']);
$descripcion_seccion = esc_html($atts['descripcion']);
$numero_columnas = absint($atts['columnas']);
$estilo_grid = sanitize_key($atts['estilo']);

// Obtener servicios desde opciones o definir por defecto
$servicios_guardados = get_option('flavor_empresarial_servicios', []);

if (empty($servicios_guardados)) {
    $servicios_guardados = [
        [
            'icono'       => 'dashicons-admin-tools',
            'titulo'      => __('Consultoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Asesoramiento profesional para optimizar tus procesos de negocio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'icono'       => 'dashicons-chart-line',
            'titulo'      => __('Desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Soluciones tecnológicas a medida para impulsar tu crecimiento.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'icono'       => 'dashicons-megaphone',
            'titulo'      => __('Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Estrategias de marketing digital para aumentar tu visibilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'icono'       => 'dashicons-groups',
            'titulo'      => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Programas de capacitación para tu equipo de trabajo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'icono'       => 'dashicons-admin-network',
            'titulo'      => __('Soporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Asistencia técnica continua para mantener tu operación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
        [
            'icono'       => 'dashicons-analytics',
            'titulo'      => __('Análisis', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Análisis de datos para tomar decisiones informadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ],
    ];
}
?>

<section class="flavor-emp-servicios flavor-emp-servicios-<?php echo esc_attr($estilo_grid); ?>">
    <div class="flavor-emp-servicios-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="flavor-emp-seccion-titulo"><?php echo $titulo_seccion; ?></h2>
        <?php endif; ?>
        <?php if ($descripcion_seccion): ?>
            <p class="flavor-emp-seccion-descripcion"><?php echo $descripcion_seccion; ?></p>
        <?php endif; ?>
    </div>

    <div class="flavor-emp-servicios-grid columnas-<?php echo esc_attr($numero_columnas); ?>">
        <?php foreach ($servicios_guardados as $servicio): ?>
            <article class="flavor-emp-servicio-card">
                <?php if (!empty($servicio['icono'])): ?>
                    <div class="servicio-icono">
                        <span class="dashicons <?php echo esc_attr($servicio['icono']); ?>"></span>
                    </div>
                <?php endif; ?>

                <h3 class="servicio-titulo"><?php echo esc_html($servicio['titulo']); ?></h3>

                <?php if (!empty($servicio['descripcion'])): ?>
                    <p class="servicio-descripcion"><?php echo esc_html($servicio['descripcion']); ?></p>
                <?php endif; ?>

                <?php if (!empty($servicio['enlace'])): ?>
                    <a href="<?php echo esc_url($servicio['enlace']); ?>" class="servicio-enlace">
                        <?php esc_html_e('Saber más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
