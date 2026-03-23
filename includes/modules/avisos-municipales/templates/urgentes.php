<?php
/**
 * Template: Lista de Avisos Urgentes
 *
 * Muestra los avisos con prioridad urgente con iconos de alerta.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
$tabla_categorias = $wpdb->prefix . 'flavor_avisos_categorias';
$tabla_zonas = $wpdb->prefix . 'flavor_avisos_zonas';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_avisos)) {
    echo '<div class="avisos-empty"><p>' . esc_html__('El modulo de avisos municipales no esta configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Parametros del template
$limite = isset($atts['limite']) ? intval($atts['limite']) : 10;
$mostrar_animacion = isset($atts['animacion']) ? ($atts['animacion'] === 'true') : true;

// Obtener avisos urgentes activos
$avisos_urgentes = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color, z.nombre AS zona_nombre
     FROM $tabla_avisos a
     LEFT JOIN $tabla_categorias c ON a.categoria_id = c.id
     LEFT JOIN $tabla_zonas z ON a.zona_id = z.id
     WHERE a.publicado = 1
       AND a.prioridad = 'urgente'
       AND a.fecha_inicio <= NOW()
       AND (a.fecha_fin IS NULL OR a.fecha_fin >= NOW())
     ORDER BY a.destacado DESC, a.fecha_inicio DESC
     LIMIT %d",
    $limite
));

// URL base para los detalles
$avisos_base_url = Flavor_Chat_Helpers::get_action_url('avisos_municipales', '');
?>

<div class="avisos-urgentes-wrapper">
    <header class="avisos-urgentes-header">
        <div class="avisos-urgentes-icon">
            <span class="dashicons dashicons-warning"></span>
        </div>
        <div class="avisos-urgentes-title-group">
            <h2><?php esc_html_e('Avisos Urgentes', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Comunicados que requieren atencion inmediata', 'flavor-chat-ia'); ?></p>
        </div>
        <?php if ($avisos_urgentes): ?>
        <span class="avisos-urgentes-contador"><?php echo count($avisos_urgentes); ?></span>
        <?php endif; ?>
    </header>

    <?php if ($avisos_urgentes): ?>
    <div class="avisos-urgentes-lista">
        <?php foreach ($avisos_urgentes as $aviso):
            $tiempo_restante = '';
            if ($aviso->fecha_fin) {
                $fecha_fin_timestamp = strtotime($aviso->fecha_fin);
                $diferencia = $fecha_fin_timestamp - time();
                if ($diferencia > 0 && $diferencia < 86400) {
                    $horas = floor($diferencia / 3600);
                    $tiempo_restante = sprintf(__('Expira en %d horas', 'flavor-chat-ia'), $horas);
                }
            }
        ?>
        <article class="aviso-urgente-card <?php echo $mostrar_animacion ? 'aviso-urgente-card--animado' : ''; ?>">
            <div class="aviso-urgente-alerta">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="aviso-urgente-contenido">
                <div class="aviso-urgente-badges">
                    <span class="aviso-badge aviso-badge--urgente">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Urgente', 'flavor-chat-ia'); ?>
                    </span>
                    <?php if ($aviso->categoria_nombre): ?>
                    <span class="aviso-badge aviso-badge--categoria" style="background: <?php echo esc_attr($aviso->categoria_color ?: '#6b7280'); ?>">
                        <?php echo esc_html($aviso->categoria_nombre); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($tiempo_restante): ?>
                    <span class="aviso-badge aviso-badge--expira">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($tiempo_restante); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <h3 class="aviso-urgente-titulo">
                    <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>">
                        <?php echo esc_html($aviso->titulo); ?>
                    </a>
                </h3>
                <p class="aviso-urgente-extracto">
                    <?php echo esc_html(wp_trim_words($aviso->extracto ?: $aviso->contenido, 25)); ?>
                </p>
                <div class="aviso-urgente-meta">
                    <span class="aviso-urgente-fecha">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php echo date_i18n('j M Y, H:i', strtotime($aviso->fecha_inicio)); ?>
                    </span>
                    <?php if ($aviso->zona_nombre): ?>
                    <span class="aviso-urgente-zona">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($aviso->zona_nombre); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($aviso->visualizaciones): ?>
                    <span class="aviso-urgente-vistas">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo number_format_i18n($aviso->visualizaciones); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aviso-urgente-accion">
                <a href="<?php echo esc_url($avisos_base_url . '?aviso=' . $aviso->id); ?>" class="btn btn-urgente">
                    <?php esc_html_e('Ver aviso', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="avisos-empty avisos-empty--success">
        <span class="dashicons dashicons-yes-alt"></span>
        <h3><?php esc_html_e('Sin avisos urgentes', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('No hay comunicados urgentes en este momento. Consulta los avisos activos para mas informacion.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url($avisos_base_url); ?>" class="btn btn-outline">
            <?php esc_html_e('Ver todos los avisos', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
.avisos-urgentes-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.avisos-urgentes-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    border-radius: 12px;
    color: white;
}

.avisos-urgentes-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
}

.avisos-urgentes-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.avisos-urgentes-title-group {
    flex: 1;
}

.avisos-urgentes-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.avisos-urgentes-header p {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.avisos-urgentes-contador {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    color: #dc2626;
    font-weight: 700;
    font-size: 1rem;
    border-radius: 50%;
}

.avisos-urgentes-lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.aviso-urgente-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border-radius: 12px;
    border-left: 4px solid #dc2626;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
    transition: all 0.2s ease;
}

.aviso-urgente-card:hover {
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);
    transform: translateY(-2px);
}

.aviso-urgente-card--animado {
    animation: pulso-urgente 2s ease-in-out infinite;
}

@keyframes pulso-urgente {
    0%, 100% { box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1); }
    50% { box-shadow: 0 4px 16px rgba(220, 38, 38, 0.25); }
}

.aviso-urgente-alerta {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 10px;
    flex-shrink: 0;
}

.aviso-urgente-alerta .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.aviso-urgente-contenido {
    flex: 1;
    min-width: 0;
}

.aviso-urgente-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.aviso-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.aviso-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.aviso-badge--urgente {
    background: #dc2626;
    color: white;
}

.aviso-badge--categoria {
    color: white;
}

.aviso-badge--expira {
    background: #fef3c7;
    color: #92400e;
}

.aviso-urgente-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.4;
}

.aviso-urgente-titulo a {
    color: #1f2937;
    text-decoration: none;
}

.aviso-urgente-titulo a:hover {
    color: #dc2626;
}

.aviso-urgente-extracto {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
}

.aviso-urgente-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.8rem;
    color: #9ca3af;
}

.aviso-urgente-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.aviso-urgente-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.aviso-urgente-accion {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-urgente {
    background: #dc2626;
    color: white;
}

.btn-urgente:hover {
    background: #b91c1c;
}

.btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.avisos-empty {
    text-align: center;
    padding: 3rem;
    background: #f9fafb;
    border-radius: 12px;
}

.avisos-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
    margin-bottom: 1rem;
}

.avisos-empty--success .dashicons {
    color: #10b981;
}

.avisos-empty h3 {
    margin: 0 0 0.5rem;
    color: #374151;
    font-size: 1.25rem;
}

.avisos-empty p {
    margin: 0 0 1.5rem;
    color: #6b7280;
    font-size: 0.9rem;
}

@media (max-width: 640px) {
    .aviso-urgente-card {
        flex-direction: column;
    }

    .aviso-urgente-alerta {
        width: 36px;
        height: 36px;
    }

    .aviso-urgente-accion {
        width: 100%;
    }

    .aviso-urgente-accion .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
