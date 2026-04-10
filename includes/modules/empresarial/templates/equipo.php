<?php
/**
 * Template: Equipo Empresarial
 *
 * @package FlavorPlatform
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = esc_html($atts['titulo']);
$descripcion_seccion = esc_html($atts['descripcion']);
$tipo_layout = sanitize_key($atts['layout']);
$numero_columnas = absint($atts['columnas']);

// Obtener miembros del equipo desde opciones o definir por defecto
$miembros_equipo = get_option('flavor_empresarial_equipo', []);

if (empty($miembros_equipo)) {
    $miembros_equipo = [
        [
            'nombre'   => 'Ana García',
            'puesto'   => __('Directora General', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bio'      => __('Más de 15 años de experiencia en gestión empresarial.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foto'     => '',
            'linkedin' => '#',
            'twitter'  => '#',
        ],
        [
            'nombre'   => 'Carlos Martín',
            'puesto'   => __('Director Técnico', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bio'      => __('Experto en desarrollo de soluciones tecnológicas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foto'     => '',
            'linkedin' => '#',
            'twitter'  => '#',
        ],
        [
            'nombre'   => 'María López',
            'puesto'   => __('Directora de Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bio'      => __('Especialista en estrategias de marketing digital.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foto'     => '',
            'linkedin' => '#',
            'twitter'  => '#',
        ],
        [
            'nombre'   => 'Pedro Sánchez',
            'puesto'   => __('Director Financiero', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bio'      => __('Amplia experiencia en gestión financiera corporativa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foto'     => '',
            'linkedin' => '#',
            'twitter'  => '#',
        ],
    ];
}
?>

<section class="flavor-emp-equipo flavor-emp-equipo-<?php echo esc_attr($tipo_layout); ?>">
    <div class="flavor-emp-equipo-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="flavor-emp-seccion-titulo"><?php echo $titulo_seccion; ?></h2>
        <?php endif; ?>
        <?php if ($descripcion_seccion): ?>
            <p class="flavor-emp-seccion-descripcion"><?php echo $descripcion_seccion; ?></p>
        <?php endif; ?>
    </div>

    <div class="flavor-emp-equipo-grid columnas-<?php echo esc_attr($numero_columnas); ?>">
        <?php foreach ($miembros_equipo as $miembro): ?>
            <article class="flavor-emp-miembro-card">
                <div class="miembro-foto">
                    <?php if (!empty($miembro['foto'])): ?>
                        <img src="<?php echo esc_url($miembro['foto']); ?>" alt="<?php echo esc_attr($miembro['nombre']); ?>">
                    <?php else: ?>
                        <div class="miembro-foto-placeholder">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="miembro-info">
                    <h3 class="miembro-nombre"><?php echo esc_html($miembro['nombre']); ?></h3>
                    <span class="miembro-puesto"><?php echo esc_html($miembro['puesto']); ?></span>

                    <?php if (!empty($miembro['bio'])): ?>
                        <p class="miembro-bio"><?php echo esc_html($miembro['bio']); ?></p>
                    <?php endif; ?>

                    <div class="miembro-redes">
                        <?php if (!empty($miembro['linkedin'])): ?>
                            <a href="<?php echo esc_url($miembro['linkedin']); ?>" class="red-social linkedin" target="_blank" rel="noopener">
                                <span class="dashicons dashicons-linkedin"></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($miembro['twitter'])): ?>
                            <a href="<?php echo esc_url($miembro['twitter']); ?>" class="red-social twitter" target="_blank" rel="noopener">
                                <span class="dashicons dashicons-twitter"></span>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($miembro['email'])): ?>
                            <a href="mailto:<?php echo esc_attr($miembro['email']); ?>" class="red-social email">
                                <span class="dashicons dashicons-email"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
