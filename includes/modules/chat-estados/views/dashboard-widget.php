<?php
/**
 * Widget de estados para el dashboard
 *
 * @package Flavor_Platform
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$mis_estados = $estados['mis_estados'] ?? null;
$contactos = $estados['contactos'] ?? [];
$tiene_estados = $mis_estados && !empty($mis_estados['estados']);
?>

<div class="estados-widget">
    <?php if ($tiene_estados): ?>
    <div class="mis-estados-resumen">
        <div class="estados-count">
            <span class="numero"><?php echo count($mis_estados['estados']); ?></span>
            <span class="label"><?php esc_html_e('estados activos', 'flavor-platform'); ?></span>
        </div>
        <div class="estados-vistas">
            <?php
            $total_vistas = array_sum(array_column($mis_estados['estados'], 'visualizaciones_count'));
            ?>
            <span class="dashicons dashicons-visibility"></span>
            <span><?php echo number_format_i18n($total_vistas); ?> <?php esc_html_e('vistas', 'flavor-platform'); ?></span>
        </div>
    </div>
    <?php else: ?>
    <div class="sin-estados">
        <span class="dashicons dashicons-format-status"></span>
        <p><?php esc_html_e('No tienes estados activos', 'flavor-platform'); ?></p>
        <button class="btn-crear-estado btn-small"><?php esc_html_e('Crear estado', 'flavor-platform'); ?></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($contactos)): ?>
    <div class="contactos-estados">
        <h4><?php esc_html_e('Estados de contactos', 'flavor-platform'); ?></h4>
        <div class="contactos-lista">
            <?php
            $mostrar = array_slice($contactos, 0, 5);
            foreach ($mostrar as $contacto):
                $sin_ver = $contacto['sin_ver'] ?? 0;
            ?>
            <div class="contacto-estado <?php echo $sin_ver > 0 ? 'sin-ver' : ''; ?>" data-user-id="<?php echo esc_attr($contacto['usuario_id']); ?>">
                <img src="<?php echo esc_url($contacto['autor_avatar']); ?>" alt="">
                <span class="nombre"><?php echo esc_html($contacto['autor_nombre']); ?></span>
                <?php if ($sin_ver > 0): ?>
                <span class="badge"><?php echo esc_html($sin_ver); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($contactos) > 5): ?>
        <a href="<?php echo esc_url(home_url('/estados/')); ?>" class="ver-todos">
            <?php printf(esc_html__('Ver todos (%d)', 'flavor-platform'), count($contactos)); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.estados-widget {
    padding: 16px 0;
}

.mis-estados-resumen {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--color-bg-light, #f5f5f5);
    border-radius: 8px;
    margin-bottom: 16px;
}

.estados-count .numero {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-primary, #128C7E);
}

.estados-count .label {
    display: block;
    font-size: 12px;
    color: var(--color-text-muted, #666);
}

.estados-vistas {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--color-text-muted, #666);
    font-size: 13px;
}

.sin-estados {
    text-align: center;
    padding: 24px;
    color: var(--color-text-muted, #666);
}

.sin-estados .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin-bottom: 8px;
    opacity: 0.5;
}

.sin-estados p {
    margin: 0 0 12px;
}

.contactos-estados h4 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--color-text-muted, #666);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contactos-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.contacto-estado {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.contacto-estado:hover {
    background: var(--color-bg-light, #f5f5f5);
}

.contacto-estado img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.contacto-estado.sin-ver img {
    border: 2px solid var(--color-primary, #25D366);
}

.contacto-estado .nombre {
    flex: 1;
    font-weight: 500;
}

.contacto-estado .badge {
    background: var(--color-primary, #25D366);
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
}

.ver-todos {
    display: block;
    text-align: center;
    margin-top: 12px;
    color: var(--color-primary, #128C7E);
    font-size: 13px;
    text-decoration: none;
}

.ver-todos:hover {
    text-decoration: underline;
}
</style>
