<?php
/**
 * Template para Barrio - Sección de Servicios Vecinales
 *
 * Variables: $titulo, $servicios, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Servicios del Barrio', 'flavor-chat-ia');
$color_primario = $color_primario ?? '#22c55e';

$servicios_default = [
    [
        'nombre' => __('Ayuda Vecinal', 'flavor-chat-ia'),
        'descripcion' => __('Pide o ofrece ayuda a tus vecinos: compras, pasear mascotas, acompañamiento...', 'flavor-chat-ia'),
        'icono' => 'heart',
        'color' => '#ec4899',
        'url' => '#ayuda',
    ],
    [
        'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
        'descripcion' => __('Intercambia horas de servicios: clases, reparaciones, cuidados, cocina...', 'flavor-chat-ia'),
        'icono' => 'clock',
        'color' => '#8b5cf6',
        'url' => '#banco-tiempo',
    ],
    [
        'nombre' => __('Huertos Urbanos', 'flavor-chat-ia'),
        'descripcion' => __('Gestiona tu parcela, comparte cosechas y aprende de agricultura urbana.', 'flavor-chat-ia'),
        'icono' => 'carrot',
        'color' => '#22c55e',
        'url' => '#huertos',
    ],
    [
        'nombre' => __('Bicis Compartidas', 'flavor-chat-ia'),
        'descripcion' => __('Préstamo de bicicletas entre vecinos. Movilidad sostenible y gratuita.', 'flavor-chat-ia'),
        'icono' => 'bike',
        'color' => '#06b6d4',
        'url' => '#bicis',
    ],
    [
        'nombre' => __('Espacios Comunes', 'flavor-chat-ia'),
        'descripcion' => __('Reserva salas, patios y espacios para reuniones, fiestas o actividades.', 'flavor-chat-ia'),
        'icono' => 'building',
        'color' => '#f59e0b',
        'url' => '#espacios',
    ],
    [
        'nombre' => __('Incidencias', 'flavor-chat-ia'),
        'descripcion' => __('Reporta problemas del barrio: baches, farolas, limpieza, ruidos...', 'flavor-chat-ia'),
        'icono' => 'warning',
        'color' => '#ef4444',
        'url' => '#incidencias',
    ],
];

$servicios = $servicios ?? $servicios_default;

$stats = [
    ['numero' => '1.250', 'label' => __('Vecinos activos', 'flavor-chat-ia')],
    ['numero' => '340', 'label' => __('Ayudas realizadas', 'flavor-chat-ia')],
    ['numero' => '89', 'label' => __('Horas intercambiadas', 'flavor-chat-ia')],
    ['numero' => '12', 'label' => __('Huertos gestionados', 'flavor-chat-ia')],
];
?>

<section class="flavor-barrio-servicios" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-barrio-header">
            <span class="flavor-barrio-badge">
                <span class="dashicons dashicons-location"></span>
                <?php esc_html_e('Tu Barrio', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
            <p class="flavor-barrio-subtitulo"><?php esc_html_e('Todo lo que necesitas, cerca de ti y entre vecinos', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="flavor-servicios-grid">
            <?php foreach ($servicios as $servicio): ?>
                <a href="<?php echo esc_url($servicio['url']); ?>" class="flavor-servicio-card" style="--servicio-color: <?php echo esc_attr($servicio['color']); ?>;">
                    <div class="flavor-servicio-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($servicio['icono']); ?>"></span>
                    </div>
                    <h3 class="flavor-servicio-nombre"><?php echo esc_html($servicio['nombre']); ?></h3>
                    <p class="flavor-servicio-descripcion"><?php echo esc_html($servicio['descripcion']); ?></p>
                    <span class="flavor-servicio-enlace">
                        <?php esc_html_e('Acceder', 'flavor-chat-ia'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="flavor-barrio-stats">
            <?php foreach ($stats as $stat): ?>
                <div class="flavor-stat-item">
                    <span class="flavor-stat-numero"><?php echo esc_html($stat['numero']); ?></span>
                    <span class="flavor-stat-label"><?php echo esc_html($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flavor-barrio-cta">
            <div class="flavor-cta-texto">
                <h3><?php esc_html_e('¿Aún no eres parte del barrio digital?', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Únete gratis y conecta con tus vecinos', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="#registro" class="flavor-cta-boton">
                <?php esc_html_e('Unirme al barrio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-barrio-servicios {
    padding: 5rem 0;
    background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
}
.flavor-barrio-header {
    text-align: center;
    margin-bottom: 3rem;
}
.flavor-barrio-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--color-primario);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
.flavor-barrio-header .flavor-section-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-barrio-subtitulo {
    color: #6b7280;
    font-size: 1.125rem;
    margin: 0;
}
.flavor-servicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}
.flavor-servicio-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}
.flavor-servicio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.08);
}
.flavor-servicio-icono {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--servicio-color) 20%, white), color-mix(in srgb, var(--servicio-color) 30%, white));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}
.flavor-servicio-icono .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: var(--servicio-color);
}
.flavor-servicio-nombre {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem;
}
.flavor-servicio-descripcion {
    font-size: 0.9375rem;
    color: #6b7280;
    margin: 0 0 1rem;
    flex: 1;
    line-height: 1.5;
}
.flavor-servicio-enlace {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--servicio-color);
    font-weight: 600;
    font-size: 0.9375rem;
    transition: gap 0.2s;
}
.flavor-servicio-card:hover .flavor-servicio-enlace {
    gap: 0.5rem;
}
.flavor-barrio-stats {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    margin-bottom: 3rem;
}
.flavor-stat-item {
    text-align: center;
    padding: 0 1.5rem;
}
.flavor-stat-numero {
    display: block;
    font-size: 2rem;
    font-weight: 800;
    color: var(--color-primario);
    line-height: 1;
}
.flavor-stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}
.flavor-barrio-cta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    background: var(--color-primario);
    border-radius: 16px;
    padding: 2rem 2.5rem;
    color: white;
}
.flavor-cta-texto h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}
.flavor-cta-texto p {
    margin: 0;
    opacity: 0.9;
}
.flavor-cta-boton {
    flex-shrink: 0;
    padding: 1rem 2rem;
    background: white;
    color: var(--color-primario);
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-cta-boton:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
@media (max-width: 768px) {
    .flavor-barrio-servicios {
        padding: 3rem 0;
    }
    .flavor-barrio-header .flavor-section-title {
        font-size: 1.75rem;
    }
    .flavor-barrio-stats {
        gap: 1.5rem;
    }
    .flavor-stat-item {
        padding: 0 1rem;
        min-width: 120px;
    }
    .flavor-barrio-cta {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
}
</style>
