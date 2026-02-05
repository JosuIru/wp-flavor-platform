<?php
/**
 * Template Landing - Gimnasio / Centro Fitness
 *
 * Variables: $nombre, $eslogan, $telefono, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$nombre = $nombre ?? __('Fitness Center', 'flavor-chat-ia');
$eslogan = $eslogan ?? __('Transforma tu cuerpo, cambia tu vida', 'flavor-chat-ia');
$telefono = $telefono ?? '900 000 000';
$color_primario = $color_primario ?? '#ea580c';

$planes = $planes ?? [
    ['nombre' => __('Básico', 'flavor-chat-ia'), 'precio' => '29€', 'periodo' => __('/mes', 'flavor-chat-ia'), 'caracteristicas' => [__('Acceso a sala de musculación', 'flavor-chat-ia'), __('Horario de mañanas', 'flavor-chat-ia'), __('Casillero incluido', 'flavor-chat-ia')], 'destacado' => false],
    ['nombre' => __('Premium', 'flavor-chat-ia'), 'precio' => '49€', 'periodo' => __('/mes', 'flavor-chat-ia'), 'caracteristicas' => [__('Acceso ilimitado 24/7', 'flavor-chat-ia'), __('Clases grupales incluidas', 'flavor-chat-ia'), __('Zona de spa', 'flavor-chat-ia'), __('App de seguimiento', 'flavor-chat-ia')], 'destacado' => true],
    ['nombre' => __('VIP', 'flavor-chat-ia'), 'precio' => '79€', 'periodo' => __('/mes', 'flavor-chat-ia'), 'caracteristicas' => [__('Todo lo de Premium', 'flavor-chat-ia'), __('Entrenador personal', 'flavor-chat-ia'), __('Plan nutricional', 'flavor-chat-ia'), __('Invitados gratis', 'flavor-chat-ia')], 'destacado' => false],
];

$clases = $clases ?? [
    ['nombre' => 'Spinning', 'icono' => 'performance', 'horario' => __('Lun-Vie 7:00, 19:00', 'flavor-chat-ia')],
    ['nombre' => 'Yoga', 'icono' => 'heart', 'horario' => __('Mar-Jue 10:00, 18:00', 'flavor-chat-ia')],
    ['nombre' => 'CrossFit', 'icono' => 'superhero', 'horario' => __('Lun-Vie 8:00, 20:00', 'flavor-chat-ia')],
    ['nombre' => 'Pilates', 'icono' => 'universal-access', 'horario' => __('Mié-Vie 9:00, 17:00', 'flavor-chat-ia')],
];

$stats = $stats ?? [
    ['numero' => '500+', 'texto' => __('Miembros activos', 'flavor-chat-ia')],
    ['numero' => '50+', 'texto' => __('Clases semanales', 'flavor-chat-ia')],
    ['numero' => '15', 'texto' => __('Entrenadores', 'flavor-chat-ia')],
    ['numero' => '24/7', 'texto' => __('Horario', 'flavor-chat-ia')],
];
?>

<!-- Hero -->
<section class="flavor-gym-hero" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-hero-overlay">
        <div class="flavor-hero-content">
            <h1><?php echo esc_html($nombre); ?></h1>
            <p><?php echo esc_html($eslogan); ?></p>
            <div class="flavor-hero-actions">
                <a href="#planes" class="flavor-btn-primary"><?php esc_html_e('Ver Planes', 'flavor-chat-ia'); ?></a>
                <a href="#clases" class="flavor-btn-ghost"><?php esc_html_e('Ver Clases', 'flavor-chat-ia'); ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="flavor-gym-stats">
    <div class="flavor-container">
        <div class="flavor-stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="flavor-stat">
                    <span class="flavor-stat-numero"><?php echo esc_html($stat['numero']); ?></span>
                    <span class="flavor-stat-texto"><?php echo esc_html($stat['texto']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Planes -->
<section class="flavor-gym-planes" id="planes">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Elige tu Plan', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Sin permanencia, cancela cuando quieras', 'flavor-chat-ia'); ?></p>

        <div class="flavor-planes-grid">
            <?php foreach ($planes as $plan): ?>
                <div class="flavor-plan-card <?php echo $plan['destacado'] ? 'is-destacado' : ''; ?>">
                    <?php if ($plan['destacado']): ?>
                        <span class="flavor-plan-badge"><?php esc_html_e('Más Popular', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                    <h3><?php echo esc_html($plan['nombre']); ?></h3>
                    <div class="flavor-plan-precio">
                        <span class="precio"><?php echo esc_html($plan['precio']); ?></span>
                        <span class="periodo"><?php echo esc_html($plan['periodo']); ?></span>
                    </div>
                    <ul class="flavor-plan-features">
                        <?php foreach ($plan['caracteristicas'] as $feat): ?>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($feat); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="#planes" class="flavor-plan-btn <?php echo $plan['destacado'] ? 'is-primary' : ''; ?>">
                        <?php esc_html_e('Empezar Ahora', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Clases -->
<section class="flavor-gym-clases" id="clases">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php esc_html_e('Clases Grupales', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-section-subtitle"><?php esc_html_e('Entrena en grupo con nuestros instructores certificados', 'flavor-chat-ia'); ?></p>

        <div class="flavor-clases-grid">
            <?php foreach ($clases as $clase): ?>
                <div class="flavor-clase-card">
                    <div class="flavor-clase-icono">
                        <span class="dashicons dashicons-<?php echo esc_attr($clase['icono']); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($clase['nombre']); ?></h3>
                    <p><?php echo esc_html($clase['horario']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flavor-clases-cta">
            <a href="#clases" class="flavor-btn-outline"><?php esc_html_e('Ver Horario Completo', 'flavor-chat-ia'); ?></a>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="flavor-gym-cta" style="background: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <h2><?php esc_html_e('¡Tu primera semana GRATIS!', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Prueba nuestras instalaciones sin compromiso', 'flavor-chat-ia'); ?></p>
        <a href="#planes" class="flavor-btn-white"><?php esc_html_e('Solicitar Prueba Gratuita', 'flavor-chat-ia'); ?></a>
    </div>
</section>

<style>
.flavor-gym-hero {
    min-height: 80vh;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;
}
.flavor-gym-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="%23ffffff10"/></svg>');
    background-size: 50px 50px;
}
.flavor-gym-hero .flavor-hero-overlay {
    position: relative;
    z-index: 1;
    padding: 2rem;
}
.flavor-gym-hero h1 {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 800;
    color: white;
    margin: 0 0 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.flavor-gym-hero p {
    font-size: 1.25rem;
    color: rgba(255,255,255,0.8);
    margin: 0 0 2rem;
}
.flavor-hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
.flavor-btn-primary {
    padding: 1rem 2.5rem;
    background: var(--color-primario);
    color: white;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: all 0.2s;
}
.flavor-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(234, 88, 12, 0.4);
}
.flavor-btn-ghost {
    padding: 1rem 2.5rem;
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-ghost:hover {
    border-color: white;
    background: rgba(255,255,255,0.1);
}

/* Stats */
.flavor-gym-stats {
    background: var(--color-primario);
    padding: 3rem 0;
}
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    text-align: center;
    color: white;
}
.flavor-stat-numero {
    display: block;
    font-size: 2.5rem;
    font-weight: 800;
}
.flavor-stat-texto {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Planes */
.flavor-gym-planes {
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
.flavor-planes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}
.flavor-plan-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    position: relative;
    transition: all 0.3s;
}
.flavor-plan-card.is-destacado {
    border: 2px solid var(--color-primario);
    transform: scale(1.05);
}
.flavor-plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-primario);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.flavor-plan-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: #1f2937;
}
.flavor-plan-precio {
    margin-bottom: 1.5rem;
}
.flavor-plan-precio .precio {
    font-size: 3rem;
    font-weight: 800;
    color: var(--color-primario);
}
.flavor-plan-precio .periodo {
    color: #6b7280;
}
.flavor-plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem;
    text-align: left;
}
.flavor-plan-features li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    color: #4b5563;
    border-bottom: 1px solid #f3f4f6;
}
.flavor-plan-features .dashicons {
    color: var(--color-primario);
    font-size: 18px;
    width: 18px;
    height: 18px;
}
.flavor-plan-btn {
    display: block;
    padding: 1rem;
    background: #f3f4f6;
    color: #1f2937;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-plan-btn.is-primary {
    background: var(--color-primario);
    color: white;
}
.flavor-plan-btn:hover {
    transform: translateY(-2px);
}

/* Clases */
.flavor-gym-clases {
    padding: 5rem 0;
    background: white;
}
.flavor-clases-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.flavor-clase-card {
    background: #f9fafb;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s;
}
.flavor-clase-card:hover {
    background: var(--color-primario);
    color: white;
    transform: translateY(-4px);
}
.flavor-clase-icono {
    width: 60px;
    height: 60px;
    background: var(--color-primario);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    transition: all 0.3s;
}
.flavor-clase-card:hover .flavor-clase-icono {
    background: white;
}
.flavor-clase-icono .dashicons {
    color: white;
    font-size: 24px;
    width: 24px;
    height: 24px;
    transition: all 0.3s;
}
.flavor-clase-card:hover .flavor-clase-icono .dashicons {
    color: var(--color-primario);
}
.flavor-clase-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
}
.flavor-clase-card p {
    font-size: 0.875rem;
    margin: 0;
    opacity: 0.8;
}
.flavor-clases-cta {
    text-align: center;
}
.flavor-btn-outline {
    display: inline-block;
    padding: 1rem 2rem;
    border: 2px solid var(--color-primario);
    color: var(--color-primario);
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-outline:hover {
    background: var(--color-primario);
    color: white;
}

/* CTA Final */
.flavor-gym-cta {
    padding: 5rem 0;
    text-align: center;
    color: white;
}
.flavor-gym-cta h2 {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0 0 1rem;
}
.flavor-gym-cta p {
    font-size: 1.125rem;
    margin: 0 0 2rem;
    opacity: 0.9;
}
.flavor-btn-white {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: white;
    color: var(--color-primario);
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
}
.flavor-btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .flavor-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .flavor-plan-card.is-destacado {
        transform: none;
    }
}
</style>
