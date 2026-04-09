<?php
/**
 * Template parcial: Grupos de Consumo Activos
 *
 * Muestra los grupos de consumo disponibles para unirse
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$id_seccion = $id_seccion ?? 'grupos';

// Obtener grupos activos
$grupos = get_posts([
    'post_type' => 'gc_grupo',
    'post_status' => 'publish',
    'posts_per_page' => 6,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => '_gc_acepta_miembros',
            'value' => '1',
            'compare' => '=',
        ],
        [
            'key' => '_gc_acepta_miembros',
            'compare' => 'NOT EXISTS',
        ],
    ],
]);

// Función auxiliar para contar miembros
if (!function_exists('gc_contar_miembros')) {
    function gc_contar_miembros($grupo_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_consumidores';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE grupo_id = %d AND estado = 'activo'",
            $grupo_id
        ));
    }
}
?>

<section id="<?php echo esc_attr($id_seccion); ?>" class="flavor-landing__section flavor-gc-grupos-section">
    <div class="flavor-container">
        <header class="flavor-section-header">
            <h2 class="flavor-section-title"><?php _e('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-section-subtitle"><?php _e('Encuentra un grupo cerca de ti y empieza a consumir local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </header>

        <?php if (!empty($grupos)): ?>
            <div class="flavor-gc-grupos-grid">
                <?php foreach ($grupos as $grupo):
                    $imagen_url = get_the_post_thumbnail_url($grupo->ID, 'medium');
                    $ubicacion = get_post_meta($grupo->ID, '_gc_ubicacion', true);
                    $num_miembros = gc_contar_miembros($grupo->ID);
                    $descripcion = wp_trim_words($grupo->post_excerpt ?: $grupo->post_content, 15, '...');
                ?>
                    <article class="flavor-gc-grupo-card">
                        <div class="flavor-gc-grupo-imagen">
                            <?php if ($imagen_url): ?>
                                <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr($grupo->post_title); ?>">
                            <?php else: ?>
                                <div class="flavor-gc-grupo-placeholder">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                            <?php endif; ?>
                            <span class="flavor-gc-badge flavor-gc-badge--success"><?php _e('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-gc-grupo-content">
                            <h3 class="flavor-gc-grupo-titulo"><?php echo esc_html($grupo->post_title); ?></h3>
                            <?php if ($ubicacion): ?>
                                <p class="flavor-gc-grupo-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($ubicacion); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($descripcion): ?>
                                <p class="flavor-gc-grupo-descripcion"><?php echo esc_html($descripcion); ?></p>
                            <?php endif; ?>
                            <div class="flavor-gc-grupo-meta">
                                <span class="flavor-gc-grupo-miembros">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php printf(_n('%d miembro', '%d miembros', $num_miembros, FLAVOR_PLATFORM_TEXT_DOMAIN), $num_miembros); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flavor-gc-grupo-actions">
                            <a href="<?php echo get_permalink($grupo->ID); ?>" class="flavor-btn flavor-btn--outline flavor-btn--sm">
                                <?php _e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_item_url('grupos-consumo', $grupo->ID, 'unirme')); ?>" class="flavor-btn flavor-btn--primary flavor-btn--sm">
                                <?php _e('Unirme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="flavor-section-footer">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', '')); ?>" class="flavor-btn flavor-btn--outline">
                    <?php _e('Ver todos los grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </a>
            </div>
        <?php else: ?>
            <div class="flavor-gc-empty-state">
                <span class="dashicons dashicons-groups"></span>
                <p><?php _e('Pronto habrá grupos de consumo disponibles en tu zona.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(home_url('/contacto/')); ?>" class="flavor-btn flavor-btn--primary">
                    <?php _e('Avísame cuando haya grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-gc-grupos-section {
    padding: 4rem 0;
    background: #f8fafc;
}
.flavor-gc-grupos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}
.flavor-gc-grupo-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-gc-grupo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.flavor-gc-grupo-imagen {
    position: relative;
    height: 160px;
    background: #e2e8f0;
}
.flavor-gc-grupo-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-gc-grupo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
}
.flavor-gc-grupo-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
.flavor-gc-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.flavor-gc-badge--success {
    background: #84cc16;
    color: #fff;
}
.flavor-gc-grupo-content {
    padding: 1.25rem;
}
.flavor-gc-grupo-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    color: #1e293b;
}
.flavor-gc-grupo-ubicacion {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}
.flavor-gc-grupo-ubicacion .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.flavor-gc-grupo-descripcion {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
}
.flavor-gc-grupo-meta {
    font-size: 0.813rem;
    color: #94a3b8;
}
.flavor-gc-grupo-miembros {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.flavor-gc-grupo-actions {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #f1f5f9;
}
.flavor-gc-grupo-actions .flavor-btn {
    flex: 1;
    text-align: center;
    justify-content: center;
}
.flavor-section-footer {
    text-align: center;
    margin-top: 2rem;
}
.flavor-gc-empty-state {
    text-align: center;
    padding: 3rem;
    background: #fff;
    border-radius: 12px;
}
.flavor-gc-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #cbd5e1;
    margin-bottom: 1rem;
}
</style>
