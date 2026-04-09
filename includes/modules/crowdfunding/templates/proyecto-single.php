<?php
/**
 * Template: Proyecto de Crowdfunding (Single)
 *
 * @package FlavorChatIA
 * @subpackage Modules\Crowdfunding
 *
 * Variables disponibles:
 * @var object $proyecto Datos del proyecto
 * @var bool $es_creador Si el usuario actual es el creador
 */

if (!defined('ABSPATH')) exit;

$porcentaje = $proyecto->porcentaje_recaudado;
$progreso_clase = $porcentaje >= 100 ? 'flavor-cf-progreso--exito' : ($porcentaje >= 75 ? 'flavor-cf-progreso--alto' : ($porcentaje >= 50 ? 'flavor-cf-progreso--medio' : ''));

$moneda_iconos = [
    'eur' => '€',
    'semilla' => '🌱',
    'hours' => '⏱️',
];
?>

<div class="flavor-cf-proyecto" data-proyecto-id="<?php echo esc_attr($proyecto->id); ?>">

    <!-- Cabecera -->
    <header class="flavor-cf-proyecto__header">
        <?php if (!empty($proyecto->imagen_principal)): ?>
            <div class="flavor-cf-proyecto__imagen">
                <img src="<?php echo esc_url($proyecto->imagen_principal); ?>" alt="<?php echo esc_attr($proyecto->titulo); ?>">
                <?php if ($proyecto->estado !== 'activo'): ?>
                    <span class="flavor-cf-proyecto__badge flavor-cf-proyecto__badge--<?php echo esc_attr($proyecto->estado); ?>">
                        <?php echo esc_html($proyecto->estado_label); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="flavor-cf-proyecto__info">
            <div class="flavor-cf-proyecto__meta-top">
                <span class="flavor-cf-proyecto__tipo">
                    <?php echo esc_html($proyecto->tipo_label); ?>
                </span>
                <span class="flavor-cf-proyecto__modalidad">
                    <?php echo esc_html($proyecto->modalidad_label); ?>
                </span>
            </div>

            <h1 class="flavor-cf-proyecto__titulo"><?php echo esc_html($proyecto->titulo); ?></h1>

            <div class="flavor-cf-proyecto__creador">
                <img src="<?php echo esc_url($proyecto->creador_avatar); ?>" alt="">
                <span>
                    <?php printf(
                        esc_html__('Por %s', 'flavor-platform'),
                        '<strong>' . esc_html($proyecto->creador_nombre) . '</strong>'
                    ); ?>
                </span>
                <?php if ($proyecto->verificado): ?>
                    <span class="flavor-cf-verificado" title="<?php esc_attr_e('Proyecto verificado', 'flavor-platform'); ?>">✓</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($proyecto->extracto)): ?>
                <p class="flavor-cf-proyecto__extracto"><?php echo esc_html($proyecto->extracto); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="flavor-cf-proyecto__contenido">

        <!-- Columna principal -->
        <main class="flavor-cf-proyecto__main">

            <!-- Video destacado -->
            <?php if (!empty($proyecto->video_principal)): ?>
            <section class="flavor-cf-seccion">
                <div class="flavor-cf-video">
                    <?php echo wp_oembed_get($proyecto->video_principal); ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Descripción -->
            <section class="flavor-cf-seccion">
                <h2><?php esc_html_e('Sobre el proyecto', 'flavor-platform'); ?></h2>
                <div class="flavor-cf-descripcion">
                    <?php echo wp_kses_post(wpautop($proyecto->contenido ?: $proyecto->descripcion)); ?>
                </div>
            </section>

            <!-- Galería -->
            <?php if (!empty($proyecto->galeria)): ?>
            <section class="flavor-cf-seccion">
                <h2><?php esc_html_e('Galería', 'flavor-platform'); ?></h2>
                <div class="flavor-cf-galeria">
                    <?php foreach ($proyecto->galeria as $imagen): ?>
                        <a href="<?php echo esc_url($imagen); ?>" class="flavor-cf-galeria__item">
                            <img src="<?php echo esc_url($imagen); ?>" alt="">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Desglose del presupuesto -->
            <?php if (!empty($proyecto->desglose_presupuesto)): ?>
            <section class="flavor-cf-seccion">
                <h2><?php esc_html_e('Desglose del presupuesto', 'flavor-platform'); ?></h2>
                <div class="flavor-cf-presupuesto">
                    <?php foreach ($proyecto->desglose_presupuesto as $linea): ?>
                        <div class="flavor-cf-presupuesto__linea">
                            <span class="flavor-cf-presupuesto__concepto"><?php echo esc_html($linea['concepto']); ?></span>
                            <span class="flavor-cf-presupuesto__importe"><?php echo number_format_i18n($linea['importe'], 2); ?>€</span>
                        </div>
                    <?php endforeach; ?>
                    <div class="flavor-cf-presupuesto__total">
                        <span><?php esc_html_e('Total', 'flavor-platform'); ?></span>
                        <span><?php echo number_format_i18n($proyecto->objetivo_eur, 2); ?>€</span>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Distribución de fondos (estilo Kulturaka) -->
            <section class="flavor-cf-seccion">
                <h2><?php esc_html_e('Distribución de los fondos', 'flavor-platform'); ?></h2>
                <div class="flavor-cf-distribucion">
                    <div class="flavor-cf-distribucion__item">
                        <span class="flavor-cf-distribucion__barra" style="width: <?php echo $proyecto->porcentaje_creador; ?>%; background: #10b981;"></span>
                        <span class="flavor-cf-distribucion__label">
                            <?php echo $proyecto->porcentaje_creador; ?>% <?php esc_html_e('Creador', 'flavor-platform'); ?>
                        </span>
                    </div>
                    <?php if ($proyecto->porcentaje_espacio > 0): ?>
                    <div class="flavor-cf-distribucion__item">
                        <span class="flavor-cf-distribucion__barra" style="width: <?php echo $proyecto->porcentaje_espacio; ?>%; background: #3b82f6;"></span>
                        <span class="flavor-cf-distribucion__label">
                            <?php echo $proyecto->porcentaje_espacio; ?>% <?php esc_html_e('Espacio', 'flavor-platform'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="flavor-cf-distribucion__item">
                        <span class="flavor-cf-distribucion__barra" style="width: <?php echo $proyecto->porcentaje_comunidad; ?>%; background: #f59e0b;"></span>
                        <span class="flavor-cf-distribucion__label">
                            <?php echo $proyecto->porcentaje_comunidad; ?>% <?php esc_html_e('Fondo comunitario', 'flavor-platform'); ?>
                        </span>
                    </div>
                    <div class="flavor-cf-distribucion__item">
                        <span class="flavor-cf-distribucion__barra" style="width: <?php echo $proyecto->porcentaje_plataforma + $proyecto->porcentaje_emergencia; ?>%; background: #6b7280;"></span>
                        <span class="flavor-cf-distribucion__label">
                            <?php echo $proyecto->porcentaje_plataforma + $proyecto->porcentaje_emergencia; ?>% <?php esc_html_e('Infraestructura + Fondo emergencia', 'flavor-platform'); ?>
                        </span>
                    </div>
                </div>
            </section>

        </main>

        <!-- Sidebar -->
        <aside class="flavor-cf-proyecto__sidebar">

            <!-- Progreso de financiación -->
            <div class="flavor-cf-card flavor-cf-card--progreso">
                <div class="flavor-cf-progreso <?php echo esc_attr($progreso_clase); ?>">
                    <div class="flavor-cf-progreso__barra">
                        <div class="flavor-cf-progreso__fill" style="width: <?php echo min($porcentaje, 100); ?>%;"></div>
                    </div>
                    <div class="flavor-cf-progreso__porcentaje"><?php echo number_format_i18n($porcentaje, 1); ?>%</div>
                </div>

                <div class="flavor-cf-recaudado">
                    <div class="flavor-cf-recaudado__principal">
                        <span class="flavor-cf-recaudado__cantidad"><?php echo number_format_i18n($proyecto->recaudado_eur, 0); ?>€</span>
                        <span class="flavor-cf-recaudado__objetivo">
                            <?php printf(esc_html__('de %s€', 'flavor-platform'), number_format_i18n($proyecto->objetivo_eur, 0)); ?>
                        </span>
                    </div>
                    <?php if ($proyecto->recaudado_semilla > 0): ?>
                    <div class="flavor-cf-recaudado__secundario">
                        + <?php echo number_format_i18n($proyecto->recaudado_semilla, 0); ?> 🌱 SEMILLA
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-cf-stats">
                    <div class="flavor-cf-stat">
                        <span class="flavor-cf-stat__valor"><?php echo number_format_i18n($proyecto->aportantes_count); ?></span>
                        <span class="flavor-cf-stat__label"><?php esc_html_e('mecenas', 'flavor-platform'); ?></span>
                    </div>
                    <?php if ($proyecto->dias_restantes !== null): ?>
                    <div class="flavor-cf-stat">
                        <span class="flavor-cf-stat__valor"><?php echo number_format_i18n($proyecto->dias_restantes); ?></span>
                        <span class="flavor-cf-stat__label"><?php esc_html_e('días restantes', 'flavor-platform'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($proyecto->estado === 'activo' && !$proyecto->finalizado): ?>
                    <a href="#aportar" class="flavor-cf-btn flavor-cf-btn--primary flavor-cf-btn--block flavor-cf-btn--lg">
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Apoyar este proyecto', 'flavor-platform'); ?>
                    </a>
                <?php elseif ($proyecto->estado === 'exitoso'): ?>
                    <div class="flavor-cf-mensaje flavor-cf-mensaje--exito">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('¡Proyecto financiado con éxito!', 'flavor-platform'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Monedas aceptadas -->
            <div class="flavor-cf-card">
                <h3><?php esc_html_e('Formas de aportar', 'flavor-platform'); ?></h3>
                <div class="flavor-cf-monedas">
                    <?php if ($proyecto->acepta_eur): ?>
                        <span class="flavor-cf-moneda flavor-cf-moneda--eur">€ Euros</span>
                    <?php endif; ?>
                    <?php if ($proyecto->acepta_semilla): ?>
                        <span class="flavor-cf-moneda flavor-cf-moneda--semilla">🌱 SEMILLA</span>
                    <?php endif; ?>
                    <?php if ($proyecto->acepta_hours): ?>
                        <span class="flavor-cf-moneda flavor-cf-moneda--hours">⏱️ HOURS</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recompensas/Tiers -->
            <?php if (!empty($proyecto->tiers)): ?>
            <div class="flavor-cf-card flavor-cf-card--tiers" id="aportar">
                <h3><?php esc_html_e('Elige tu recompensa', 'flavor-platform'); ?></h3>

                <?php if ($proyecto->permite_aportacion_libre): ?>
                <div class="flavor-cf-tier flavor-cf-tier--libre">
                    <div class="flavor-cf-tier__header">
                        <span class="flavor-cf-tier__nombre"><?php esc_html_e('Aportación libre', 'flavor-platform'); ?></span>
                    </div>
                    <p class="flavor-cf-tier__descripcion"><?php esc_html_e('Aporta la cantidad que desees sin recompensa específica.', 'flavor-platform'); ?></p>
                    <form class="flavor-cf-form-libre">
                        <input type="number" name="importe" min="<?php echo esc_attr($proyecto->minimo_aportacion); ?>" placeholder="<?php echo esc_attr($proyecto->minimo_aportacion); ?>€" class="flavor-cf-input">
                        <button type="submit" class="flavor-cf-btn flavor-cf-btn--secondary"><?php esc_html_e('Aportar', 'flavor-platform'); ?></button>
                    </form>
                </div>
                <?php endif; ?>

                <?php foreach ($proyecto->tiers as $tier): ?>
                <div class="flavor-cf-tier <?php echo !$tier->disponible ? 'flavor-cf-tier--agotado' : ''; ?> <?php echo $tier->destacado ? 'flavor-cf-tier--destacado' : ''; ?>">
                    <?php if ($tier->destacado): ?>
                        <span class="flavor-cf-tier__badge-destacado"><?php esc_html_e('Popular', 'flavor-platform'); ?></span>
                    <?php endif; ?>

                    <div class="flavor-cf-tier__header">
                        <span class="flavor-cf-tier__importe">
                            <?php
                            if ($tier->importe_eur) {
                                echo number_format_i18n($tier->importe_eur, 0) . '€';
                            } elseif ($tier->importe_semilla) {
                                echo number_format_i18n($tier->importe_semilla, 0) . ' 🌱';
                            } elseif ($tier->importe_hours) {
                                echo number_format_i18n($tier->importe_hours, 1) . ' ⏱️';
                            }
                            ?>
                        </span>
                        <span class="flavor-cf-tier__nombre"><?php echo esc_html($tier->nombre); ?></span>
                    </div>

                    <?php if (!empty($tier->descripcion)): ?>
                        <p class="flavor-cf-tier__descripcion"><?php echo esc_html($tier->descripcion); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($tier->recompensas)): ?>
                    <ul class="flavor-cf-tier__recompensas">
                        <?php foreach ($tier->recompensas as $recompensa): ?>
                            <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($recompensa); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="flavor-cf-tier__footer">
                        <?php if ($tier->cantidad_limitada): ?>
                            <span class="flavor-cf-tier__stock">
                                <?php
                                if ($tier->disponible) {
                                    printf(esc_html__('%d de %d disponibles', 'flavor-platform'), $tier->restantes, $tier->cantidad_limitada);
                                } else {
                                    esc_html_e('Agotado', 'flavor-platform');
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                        <span class="flavor-cf-tier__mecenas">
                            <?php printf(esc_html__('%d mecenas', 'flavor-platform'), $tier->cantidad_vendida); ?>
                        </span>
                    </div>

                    <?php if ($tier->disponible && $proyecto->estado === 'activo'): ?>
                        <button class="flavor-cf-btn flavor-cf-btn--primary flavor-cf-btn--block flavor-cf-seleccionar-tier" data-tier-id="<?php echo esc_attr($tier->id); ?>">
                            <?php esc_html_e('Seleccionar', 'flavor-platform'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Compartir -->
            <div class="flavor-cf-card">
                <h3><?php esc_html_e('Comparte este proyecto', 'flavor-platform'); ?></h3>
                <div class="flavor-cf-compartir">
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($proyecto->titulo); ?>" target="_blank" class="flavor-cf-compartir__btn flavor-cf-compartir__btn--twitter">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="flavor-cf-compartir__btn flavor-cf-compartir__btn--facebook">
                        <span class="dashicons dashicons-facebook"></span>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode($proyecto->titulo . ' ' . get_permalink()); ?>" target="_blank" class="flavor-cf-compartir__btn flavor-cf-compartir__btn--whatsapp">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </a>
                    <button class="flavor-cf-compartir__btn flavor-cf-compartir__btn--copiar" data-url="<?php echo esc_url(get_permalink()); ?>">
                        <span class="dashicons dashicons-admin-links"></span>
                    </button>
                </div>
            </div>

        </aside>
    </div>
</div>

<style>
.flavor-cf-proyecto {
    --cf-primary: #ec4899;
    --cf-success: #10b981;
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-cf-proyecto__header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .flavor-cf-proyecto__header {
        grid-template-columns: 1fr;
    }
}

.flavor-cf-proyecto__imagen {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
}

.flavor-cf-proyecto__imagen img {
    width: 100%;
    height: 350px;
    object-fit: cover;
}

.flavor-cf-proyecto__badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}

.flavor-cf-proyecto__badge--exitoso {
    background: var(--cf-success);
    color: white;
}

.flavor-cf-proyecto__badge--fallido {
    background: #ef4444;
    color: white;
}

.flavor-cf-proyecto__info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.flavor-cf-proyecto__meta-top {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.flavor-cf-proyecto__tipo,
.flavor-cf-proyecto__modalidad {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.flavor-cf-proyecto__tipo {
    background: var(--cf-primary);
    color: white;
}

.flavor-cf-proyecto__modalidad {
    background: #f3f4f6;
    color: #6b7280;
}

.flavor-cf-proyecto__titulo {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem;
    color: #1f2937;
}

.flavor-cf-proyecto__creador {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.flavor-cf-proyecto__creador img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.flavor-cf-verificado {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: var(--cf-success);
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
}

.flavor-cf-proyecto__extracto {
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

.flavor-cf-proyecto__contenido {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
}

@media (max-width: 900px) {
    .flavor-cf-proyecto__contenido {
        grid-template-columns: 1fr;
    }
}

.flavor-cf-proyecto__main {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.flavor-cf-seccion h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: #1f2937;
}

.flavor-cf-descripcion {
    line-height: 1.8;
    color: #374151;
}

.flavor-cf-proyecto__sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flavor-cf-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-cf-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: #374151;
}

.flavor-cf-card--progreso {
    border: 2px solid var(--cf-primary);
}

.flavor-cf-progreso__barra {
    height: 12px;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.flavor-cf-progreso__fill {
    height: 100%;
    background: linear-gradient(90deg, var(--cf-primary), #f472b6);
    border-radius: 999px;
    transition: width 0.5s ease;
}

.flavor-cf-progreso--exito .flavor-cf-progreso__fill {
    background: linear-gradient(90deg, var(--cf-success), #6ee7b7);
}

.flavor-cf-progreso__porcentaje {
    text-align: right;
    font-weight: 700;
    color: var(--cf-primary);
    font-size: 1.25rem;
}

.flavor-cf-recaudado {
    text-align: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.flavor-cf-recaudado__cantidad {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-cf-recaudado__objetivo {
    color: #6b7280;
    font-size: 0.9rem;
}

.flavor-cf-recaudado__secundario {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.flavor-cf-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.flavor-cf-stat {
    text-align: center;
}

.flavor-cf-stat__valor {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-cf-stat__label {
    font-size: 0.8rem;
    color: #6b7280;
}

.flavor-cf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.flavor-cf-btn--primary {
    background: var(--cf-primary);
    color: white;
}

.flavor-cf-btn--primary:hover {
    background: #db2777;
}

.flavor-cf-btn--block {
    width: 100%;
}

.flavor-cf-btn--lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.flavor-cf-monedas {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.flavor-cf-moneda {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    background: #f3f4f6;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
}

.flavor-cf-tier {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    position: relative;
    transition: border-color 0.2s;
}

.flavor-cf-tier:hover {
    border-color: var(--cf-primary);
}

.flavor-cf-tier--destacado {
    border-color: var(--cf-primary);
}

.flavor-cf-tier--agotado {
    opacity: 0.6;
}

.flavor-cf-tier__badge-destacado {
    position: absolute;
    top: -10px;
    right: 1rem;
    background: var(--cf-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-cf-tier__header {
    margin-bottom: 0.75rem;
}

.flavor-cf-tier__importe {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.flavor-cf-tier__nombre {
    display: block;
    font-weight: 600;
    color: #374151;
}

.flavor-cf-tier__descripcion {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

.flavor-cf-tier__recompensas {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem;
}

.flavor-cf-tier__recompensas li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    margin-bottom: 0.5rem;
}

.flavor-cf-tier__recompensas .dashicons {
    color: var(--cf-success);
    font-size: 1rem;
}

.flavor-cf-tier__footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

.flavor-cf-compartir {
    display: flex;
    gap: 0.5rem;
}

.flavor-cf-compartir__btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.flavor-cf-compartir__btn--twitter { background: #1da1f2; }
.flavor-cf-compartir__btn--facebook { background: #1877f2; }
.flavor-cf-compartir__btn--whatsapp { background: #25d366; }
.flavor-cf-compartir__btn--copiar { background: #6b7280; }

.flavor-cf-distribucion {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-cf-distribucion__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-cf-distribucion__barra {
    height: 12px;
    border-radius: 999px;
    min-width: 20px;
}

.flavor-cf-distribucion__label {
    font-size: 0.875rem;
    color: #374151;
}

.flavor-cf-mensaje--exito {
    background: #d1fae5;
    color: #065f46;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
}

.flavor-cf-mensaje--exito .dashicons {
    margin-right: 0.5rem;
}

.flavor-cf-galeria {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
}

.flavor-cf-galeria__item {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
}

.flavor-cf-galeria__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.flavor-cf-galeria__item:hover img {
    transform: scale(1.05);
}

.flavor-cf-presupuesto__linea {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-cf-presupuesto__total {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0 0;
    font-weight: 700;
    font-size: 1.1rem;
}
</style>
