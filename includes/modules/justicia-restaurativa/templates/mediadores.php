<?php
/**
 * Template: Mediadores
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$justicia_restaurativa_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Justicia_Restaurativa_Module')
    : 'Flavor_Chat_Justicia_Restaurativa_Module';
$modulo = new $justicia_restaurativa_module_class();
$mediadores = $modulo->get_mediadores();
$user_id = get_current_user_id();

// Verificar si el usuario ya solicitó ser mediador
$solicitud_pendiente = false;
if ($user_id) {
    $solicitud = get_user_meta($user_id, '_jr_solicitud_mediador', true);
    $solicitud_pendiente = $solicitud && ($solicitud['estado'] ?? '') === 'pendiente';
}

// Verificar si ya es mediador
$es_mediador = $user_id && get_user_meta($user_id, '_jr_es_mediador', true) === '1';
?>

<div class="jr-mediadores">
    <header class="jr-mediadores__header">
        <h2><?php esc_html_e('Mediadores Voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Personas formadas en facilitación de diálogos y resolución de conflictos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <?php if ($mediadores) : ?>
    <div class="jr-mediadores__grid">
        <?php foreach ($mediadores as $mediador) :
            // Contar procesos facilitados
            global $wpdb;
            $procesos_facilitados = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                 WHERE meta_key = '_jr_mediador_id' AND meta_value = %d",
                $mediador->ID
            ));

            $acuerdos_alcanzados = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                 WHERE pm.meta_key = '_jr_mediador_id' AND pm.meta_value = %d
                   AND pm2.meta_key = '_jr_estado' AND pm2.meta_value IN ('acuerdo', 'cumplido')",
                $mediador->ID
            ));

            $bio = get_user_meta($mediador->ID, '_jr_mediador_bio', true);
        ?>
        <article class="jr-mediador-card">
            <?php echo get_avatar($mediador->ID, 80, '', '', ['class' => 'jr-mediador-card__avatar']); ?>
            <h3 class="jr-mediador-card__nombre"><?php echo esc_html($mediador->display_name); ?></h3>
            <?php if ($bio) : ?>
            <p class="jr-mediador-card__bio"><?php echo esc_html($bio); ?></p>
            <?php endif; ?>
            <div class="jr-mediador-card__stats">
                <div class="jr-mediador-card__stat">
                    <div class="jr-mediador-card__stat-valor"><?php echo esc_html($procesos_facilitados); ?></div>
                    <div class="jr-mediador-card__stat-label"><?php esc_html_e('Procesos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="jr-mediador-card__stat">
                    <div class="jr-mediador-card__stat-valor"><?php echo esc_html($acuerdos_alcanzados); ?></div>
                    <div class="jr-mediador-card__stat-label"><?php esc_html_e('Acuerdos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="jr-empty-state">
        <span class="dashicons dashicons-groups"></span>
        <p><?php esc_html_e('Aún no hay mediadores registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <?php if (is_user_logged_in() && !$es_mediador && !$solicitud_pendiente) : ?>
    <div class="jr-cta-mediador">
        <h3><?php esc_html_e('¿Te gustaría ser mediador/a?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('Si tienes formación o experiencia en mediación, resolución de conflictos o facilitación, únete a nuestro equipo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <button class="jr-btn" onclick="document.getElementById('jr-form-mediador').scrollIntoView({behavior: 'smooth'})">
            <?php esc_html_e('Quiero ser mediador/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>

    <section id="jr-form-mediador" class="jr-solicitar" style="margin-top: 3rem;">
        <form class="jr-solicitar__form jr-form-mediador">
            <header class="jr-solicitar__header">
                <h2><?php esc_html_e('Solicitar ser mediador/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </header>

            <div class="jr-form-grupo">
                <label for="jr-formacion"><?php esc_html_e('Formación y experiencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea name="formacion" id="jr-formacion" rows="4" required
                          placeholder="<?php esc_attr_e('Describe tu formación en mediación, facilitación o resolución de conflictos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="jr-form-grupo">
                <label for="jr-motivacion"><?php esc_html_e('¿Por qué quieres ser mediador/a?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea name="motivacion" id="jr-motivacion" rows="3"
                          placeholder="<?php esc_attr_e('Cuéntanos tu motivación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="jr-solicitar__submit">
                <button type="submit" class="jr-btn jr-btn--primary">
                    <?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </section>
    <?php elseif ($solicitud_pendiente) : ?>
    <div class="jr-aviso-confidencial" style="max-width: 600px; margin: 2rem auto;">
        <span class="dashicons dashicons-clock"></span>
        <p><?php esc_html_e('Tu solicitud para ser mediador/a está siendo revisada. Nos pondremos en contacto contigo pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php elseif ($es_mediador) : ?>
    <div class="jr-aviso-confidencial" style="max-width: 600px; margin: 2rem auto; border-color: var(--jr-success); background: color-mix(in srgb, var(--jr-success) 10%, #fff);">
        <span class="dashicons dashicons-yes-alt" style="color: var(--jr-success);"></span>
        <p><?php esc_html_e('Eres parte del equipo de mediadores. ¡Gracias por tu contribución!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>
