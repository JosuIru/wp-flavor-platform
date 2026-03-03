<?php
/**
 * Banco de Tiempo - Servicios (Frontend Dashboard)
 *
 * Template para mostrar los servicios disponibles y del usuario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$mostrar_propios = !empty($atts['mostrar_propios']);

global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

// Categorías disponibles
$categorias = [
    'cuidados' => __('Cuidados', 'flavor-chat-ia'),
    'educacion' => __('Educación', 'flavor-chat-ia'),
    'bricolaje' => __('Bricolaje', 'flavor-chat-ia'),
    'tecnologia' => __('Tecnología', 'flavor-chat-ia'),
    'transporte' => __('Transporte', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];

$iconos_categoria = [
    'cuidados' => 'dashicons-heart',
    'educacion' => 'dashicons-welcome-learn-more',
    'bricolaje' => 'dashicons-admin-tools',
    'tecnologia' => 'dashicons-laptop',
    'transporte' => 'dashicons-car',
    'otros' => 'dashicons-admin-generic',
];

// Mis servicios activos
$mis_servicios = [];
if ($usuario_id) {
    $mis_servicios = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_servicios
         WHERE usuario_id = %d AND estado IN ('activo', 'pausado')
         ORDER BY fecha_publicacion DESC",
        $usuario_id
    ));
}

// Servicios de otros usuarios
$servicios_disponibles = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, u.display_name as usuario_nombre
     FROM $tabla_servicios s
     LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
     WHERE s.estado = 'activo' AND s.usuario_id != %d
     ORDER BY s.fecha_publicacion DESC
     LIMIT 12",
    $usuario_id ?: 0
));

// Estadísticas
$total_servicios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios WHERE estado = 'activo'");
$total_categorias = $wpdb->get_var("SELECT COUNT(DISTINCT categoria) FROM $tabla_servicios WHERE estado = 'activo'");
?>

<div class="fl-banco-tiempo-servicios">
    <!-- Mis Servicios -->
    <?php if ($mostrar_propios && $usuario_id && !empty($mis_servicios)) : ?>
    <div class="fl-section">
        <div class="fl-section-header">
            <h3 class="fl-section-title">
                <span class="dashicons dashicons-businessman"></span>
                <?php esc_html_e('Mis Servicios', 'flavor-chat-ia'); ?>
            </h3>
            <button type="button" class="fl-btn fl-btn-primary fl-btn-sm bt-btn-nuevo-servicio">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Ofrecer servicio', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <div class="fl-services-grid fl-services-mine">
            <?php foreach ($mis_servicios as $servicio) : ?>
            <div class="fl-service-card fl-service-mine <?php echo $servicio->estado === 'pausado' ? 'fl-service-paused' : ''; ?>">
                <div class="fl-service-header">
                    <span class="fl-service-category">
                        <span class="dashicons <?php echo esc_attr($iconos_categoria[$servicio->categoria] ?? 'dashicons-tag'); ?>"></span>
                        <?php echo esc_html($categorias[$servicio->categoria] ?? $servicio->categoria); ?>
                    </span>
                    <span class="fl-service-status fl-status-<?php echo esc_attr($servicio->estado); ?>">
                        <?php echo $servicio->estado === 'activo' ? esc_html__('Activo', 'flavor-chat-ia') : esc_html__('Pausado', 'flavor-chat-ia'); ?>
                    </span>
                </div>
                <h4 class="fl-service-title"><?php echo esc_html($servicio->titulo); ?></h4>
                <p class="fl-service-desc"><?php echo esc_html(wp_trim_words($servicio->descripcion, 15)); ?></p>
                <div class="fl-service-footer">
                    <span class="fl-service-hours">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo number_format($servicio->horas_estimadas, 1); ?>h
                    </span>
                    <div class="fl-service-actions">
                        <button type="button" class="fl-btn-icon bt-btn-editar" data-servicio-id="<?php echo esc_attr($servicio->id); ?>" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($mostrar_propios && $usuario_id) : ?>
    <div class="fl-empty-cta">
        <span class="dashicons dashicons-admin-tools"></span>
        <h4><?php esc_html_e('¿Qué sabes hacer?', 'flavor-chat-ia'); ?></h4>
        <p><?php esc_html_e('Comparte tus habilidades con la comunidad y empieza a acumular horas.', 'flavor-chat-ia'); ?></p>
        <button type="button" class="fl-btn fl-btn-primary bt-btn-nuevo-servicio">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Ofrecer mi primer servicio', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Servicios Disponibles -->
    <div class="fl-section">
        <div class="fl-section-header">
            <h3 class="fl-section-title">
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('Servicios Disponibles', 'flavor-chat-ia'); ?>
            </h3>
            <span class="fl-section-count"><?php echo intval($total_servicios); ?> <?php esc_html_e('servicios', 'flavor-chat-ia'); ?></span>
        </div>

        <!-- Filtros por categoría -->
        <div class="fl-category-filters">
            <button class="fl-filter-btn active" data-categoria="all">
                <span class="dashicons dashicons-screenoptions"></span>
                <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
            </button>
            <?php foreach ($categorias as $cat_id => $cat_nombre) : ?>
            <button class="fl-filter-btn" data-categoria="<?php echo esc_attr($cat_id); ?>">
                <span class="dashicons <?php echo esc_attr($iconos_categoria[$cat_id]); ?>"></span>
                <?php echo esc_html($cat_nombre); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($servicios_disponibles)) : ?>
        <div class="fl-services-grid">
            <?php foreach ($servicios_disponibles as $servicio) : ?>
            <div class="fl-service-card" data-categoria="<?php echo esc_attr($servicio->categoria); ?>">
                <div class="fl-service-header">
                    <span class="fl-service-category">
                        <span class="dashicons <?php echo esc_attr($iconos_categoria[$servicio->categoria] ?? 'dashicons-tag'); ?>"></span>
                        <?php echo esc_html($categorias[$servicio->categoria] ?? $servicio->categoria); ?>
                    </span>
                </div>
                <h4 class="fl-service-title"><?php echo esc_html($servicio->titulo); ?></h4>
                <p class="fl-service-desc"><?php echo esc_html(wp_trim_words($servicio->descripcion, 20)); ?></p>
                <div class="fl-service-provider">
                    <?php echo get_avatar($servicio->usuario_id, 24); ?>
                    <span><?php echo esc_html($servicio->usuario_nombre ?: __('Usuario', 'flavor-chat-ia')); ?></span>
                </div>
                <div class="fl-service-footer">
                    <span class="fl-service-hours">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo number_format($servicio->horas_estimadas, 1); ?>h
                    </span>
                    <?php if ($usuario_id) : ?>
                    <button type="button" class="fl-btn fl-btn-primary fl-btn-sm bt-btn-solicitar" data-servicio-id="<?php echo esc_attr($servicio->id); ?>">
                        <?php esc_html_e('Solicitar', 'flavor-chat-ia'); ?>
                    </button>
                    <?php else : ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="fl-btn fl-btn-outline fl-btn-sm">
                        <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="fl-empty-state">
            <span class="dashicons dashicons-admin-tools"></span>
            <p><?php esc_html_e('No hay servicios disponibles en este momento.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.fl-banco-tiempo-servicios {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.fl-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fl-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.fl-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.fl-section-title .dashicons {
    color: #6366f1;
}

.fl-section-count {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Filtros de categoría */
.fl-category-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.fl-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    background: white;
    font-size: 0.8125rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.15s;
}

.fl-filter-btn:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.fl-filter-btn.active {
    background: #6366f1;
    border-color: #6366f1;
    color: white;
}

.fl-filter-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Grid de servicios */
.fl-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.fl-service-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
}

.fl-service-card:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}

.fl-service-mine {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border-color: #c4b5fd;
}

.fl-service-paused {
    opacity: 0.7;
}

.fl-service-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.fl-service-category {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.fl-service-category .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.fl-service-status {
    font-size: 0.6875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    font-weight: 500;
}

.fl-status-activo {
    background: #d1fae5;
    color: #059669;
}

.fl-status-pausado {
    background: #fef3c7;
    color: #d97706;
}

.fl-service-title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.5rem;
    line-height: 1.3;
}

.fl-service-desc {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 0.75rem;
    line-height: 1.5;
}

.fl-service-provider {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #4b5563;
    margin-bottom: 0.75rem;
}

.fl-service-provider img {
    border-radius: 50%;
}

.fl-service-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.fl-service-hours {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6366f1;
}

.fl-service-hours .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.fl-service-actions {
    display: flex;
    gap: 0.25rem;
}

/* Botones */
.fl-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
}

.fl-btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.fl-btn-primary {
    background: #6366f1;
    color: white;
}

.fl-btn-primary:hover {
    background: #4f46e5;
    color: white;
}

.fl-btn-outline {
    background: white;
    border: 1px solid #e5e7eb;
    color: #4b5563;
}

.fl-btn-outline:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.fl-btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: transparent;
    color: #6b7280;
    text-decoration: none;
}

.fl-btn-icon:hover {
    background: #e5e7eb;
    color: #4f46e5;
}

/* Estado vacío CTA */
.fl-empty-cta {
    text-align: center;
    padding: 2.5rem 1.5rem;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 2px dashed #c4b5fd;
    border-radius: 16px;
}

.fl-empty-cta .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #6366f1;
    margin-bottom: 1rem;
}

.fl-empty-cta h4 {
    font-size: 1.125rem;
    color: #1f2937;
    margin: 0 0 0.5rem;
}

.fl-empty-cta p {
    color: #6b7280;
    margin: 0 0 1.25rem;
    font-size: 0.9375rem;
}

/* Estado vacío simple */
.fl-empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #f9fafb;
    border-radius: 12px;
}

.fl-empty-state .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #9ca3af;
    margin-bottom: 0.5rem;
}

.fl-empty-state p {
    margin: 0;
    color: #6b7280;
}

@media (max-width: 640px) {
    .fl-services-grid {
        grid-template-columns: 1fr;
    }

    .fl-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .fl-category-filters {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.fl-filter-btn');
    const serviceCards = document.querySelectorAll('.fl-service-card:not(.fl-service-mine)');
    const params = new URLSearchParams(window.location.search);
    const servicioId = params.get('servicio_id');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const categoria = this.dataset.categoria;

            // Actualizar botones activos
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filtrar tarjetas
            serviceCards.forEach(card => {
                if (categoria === 'all' || card.dataset.categoria === categoria) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    if (servicioId && window.BancoTiempo && BancoTiempo.Servicios && typeof BancoTiempo.Servicios.verDetalle === 'function') {
        setTimeout(function() {
            BancoTiempo.Servicios.verDetalle(servicioId);
        }, 150);
    }
});
</script>
