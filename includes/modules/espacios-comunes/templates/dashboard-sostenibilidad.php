<?php
/**
 * Template: Dashboard de Sostenibilidad de Espacios Comunes
 *
 * @package FlavorPlatform
 * @subpackage EspaciosComunes
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $metricas Métricas globales del sistema
 * @var array $alertas Alertas activas
 */

if (!defined('ABSPATH')) {
    exit;
}

$periodo_texto = date_i18n('F Y', strtotime($metricas['periodo'] . '-01'));
?>

<div class="ec-dashboard-sost">
    <div class="ec-dashboard-sost__header">
        <span class="ec-dashboard-sost__icono">
            <span class="dashicons dashicons-analytics"></span>
        </span>
        <div>
            <h3 class="ec-dashboard-sost__titulo"><?php esc_html_e('Sostenibilidad de Espacios', 'flavor-platform'); ?></h3>
            <span class="ec-dashboard-sost__periodo"><?php echo esc_html($periodo_texto); ?></span>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($alertas)): ?>
        <div class="ec-dashboard-sost__alertas">
            <?php foreach ($alertas as $alerta): ?>
                <div class="ec-dashboard-sost__alerta ec-dashboard-sost__alerta--<?php echo esc_attr($alerta['tipo']); ?>">
                    <span class="dashicons <?php echo esc_attr($alerta['icono']); ?>"></span>
                    <div>
                        <strong><?php echo esc_html($alerta['titulo']); ?></strong>
                        <p><?php echo esc_html($alerta['descripcion']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Métricas principales -->
    <div class="ec-dashboard-sost__metricas">
        <div class="ec-dashboard-sost__metrica">
            <span class="ec-dashboard-sost__metrica-icono" style="background: #2196f3;">
                <span class="dashicons dashicons-calendar-alt"></span>
            </span>
            <span class="ec-dashboard-sost__metrica-valor"><?php echo esc_html($metricas['total_reservas']); ?></span>
            <span class="ec-dashboard-sost__metrica-label"><?php esc_html_e('Reservas', 'flavor-platform'); ?></span>
        </div>

        <div class="ec-dashboard-sost__metrica">
            <span class="ec-dashboard-sost__metrica-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-groups"></span>
            </span>
            <span class="ec-dashboard-sost__metrica-valor"><?php echo esc_html($metricas['usuarios_unicos']); ?></span>
            <span class="ec-dashboard-sost__metrica-label"><?php esc_html_e('Usuarios', 'flavor-platform'); ?></span>
        </div>

        <div class="ec-dashboard-sost__metrica">
            <span class="ec-dashboard-sost__metrica-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <span class="ec-dashboard-sost__metrica-valor"><?php echo esc_html($metricas['cesiones_solidarias']); ?></span>
            <span class="ec-dashboard-sost__metrica-label"><?php esc_html_e('Cesiones', 'flavor-platform'); ?></span>
        </div>

        <div class="ec-dashboard-sost__metrica">
            <span class="ec-dashboard-sost__metrica-icono" style="background: #9c27b0;">
                <span class="dashicons dashicons-hammer"></span>
            </span>
            <span class="ec-dashboard-sost__metrica-valor"><?php echo esc_html($metricas['horas_voluntariado']); ?>h</span>
            <span class="ec-dashboard-sost__metrica-label"><?php esc_html_e('Voluntariado', 'flavor-platform'); ?></span>
        </div>
    </div>

    <!-- Índices -->
    <div class="ec-dashboard-sost__indices">
        <!-- Índice de Equidad -->
        <div class="ec-dashboard-sost__indice">
            <div class="ec-dashboard-sost__indice-header">
                <span class="dashicons dashicons-universal-access-alt"></span>
                <span><?php esc_html_e('Índice de Equidad', 'flavor-platform'); ?></span>
            </div>
            <div class="ec-dashboard-sost__gauge">
                <?php
                $equidad = $metricas['indice_equidad'];
                $clase_equidad = $equidad >= 70 ? 'bueno' : ($equidad >= 40 ? 'medio' : 'bajo');
                ?>
                <div class="ec-dashboard-sost__gauge-bar">
                    <div class="ec-dashboard-sost__gauge-fill ec-dashboard-sost__gauge-fill--<?php echo esc_attr($clase_equidad); ?>" style="width: <?php echo esc_attr($equidad); ?>%"></div>
                </div>
                <span class="ec-dashboard-sost__gauge-valor"><?php echo esc_html($equidad); ?>%</span>
            </div>
            <p class="ec-dashboard-sost__indice-desc">
                <?php if ($equidad >= 70): ?>
                    <?php esc_html_e('Excelente distribución del uso entre vecinos.', 'flavor-platform'); ?>
                <?php elseif ($equidad >= 40): ?>
                    <?php esc_html_e('Distribución moderada. Algunos usuarios usan más que otros.', 'flavor-platform'); ?>
                <?php else: ?>
                    <?php esc_html_e('Distribución desigual. Pocos usuarios concentran el uso.', 'flavor-platform'); ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Índice de Rotación -->
        <div class="ec-dashboard-sost__indice">
            <div class="ec-dashboard-sost__indice-header">
                <span class="dashicons dashicons-update"></span>
                <span><?php esc_html_e('Índice de Rotación', 'flavor-platform'); ?></span>
            </div>
            <div class="ec-dashboard-sost__rotacion">
                <span class="ec-dashboard-sost__rotacion-valor"><?php echo esc_html($metricas['indice_rotacion']); ?></span>
                <span class="ec-dashboard-sost__rotacion-label"><?php esc_html_e('reservas/usuario', 'flavor-platform'); ?></span>
            </div>
            <p class="ec-dashboard-sost__indice-desc">
                <?php esc_html_e('Promedio de reservas por usuario este mes.', 'flavor-platform'); ?>
            </p>
        </div>
    </div>

    <!-- Huella de Carbono -->
    <div class="ec-dashboard-sost__co2">
        <div class="ec-dashboard-sost__co2-header">
            <span class="dashicons dashicons-cloud"></span>
            <span><?php esc_html_e('Huella de Carbono', 'flavor-platform'); ?></span>
        </div>
        <div class="ec-dashboard-sost__co2-valor">
            <span class="ec-dashboard-sost__co2-numero"><?php echo esc_html(number_format($metricas['co2_total_kg'], 1)); ?></span>
            <span class="ec-dashboard-sost__co2-unidad">kg CO<sub>2</sub></span>
        </div>
        <div class="ec-dashboard-sost__co2-equivalencia">
            <?php
            $arboles = $metricas['co2_total_kg'] / 21; // Un árbol absorbe ~21kg CO2/año
            ?>
            <span class="dashicons dashicons-palmtree"></span>
            <?php printf(
                esc_html__('Equivale a %.1f árboles necesarios para absorber', 'flavor-platform'),
                $arboles
            ); ?>
        </div>
    </div>

    <!-- Llamada a la acción -->
    <div class="ec-dashboard-sost__cta">
        <h4><?php esc_html_e('¿Cómo mejorar estos índices?', 'flavor-platform'); ?></h4>
        <ul>
            <li>
                <span class="dashicons dashicons-share-alt"></span>
                <?php esc_html_e('Cede tus reservas cuando no puedas usarlas', 'flavor-platform'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Participa en tareas de voluntariado', 'flavor-platform'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e('Apaga luces y climatización al salir', 'flavor-platform'); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.ec-dashboard-sost {
    --ec-primary: #00897b;
    --ec-primary-light: #e0f2f1;
    --ec-text: #333;
    --ec-text-light: #666;
    --ec-border: #e0e0e0;
    --ec-radius: 12px;
    background: #fff;
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 1.5rem;
}

.ec-dashboard-sost__header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ec-dashboard-sost__icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--ec-primary);
    border-radius: 50%;
}

.ec-dashboard-sost__icono .dashicons {
    color: #fff;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.ec-dashboard-sost__titulo {
    margin: 0;
    font-size: 1.1rem;
}

.ec-dashboard-sost__periodo {
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__alertas {
    margin-bottom: 1.5rem;
}

.ec-dashboard-sost__alerta {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.ec-dashboard-sost__alerta--warning {
    background: #fff3e0;
}

.ec-dashboard-sost__alerta--warning .dashicons {
    color: #f57c00;
}

.ec-dashboard-sost__alerta--error {
    background: #ffebee;
}

.ec-dashboard-sost__alerta--error .dashicons {
    color: #c62828;
}

.ec-dashboard-sost__alerta strong {
    display: block;
    font-size: 0.9rem;
}

.ec-dashboard-sost__alerta p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__metricas {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.ec-dashboard-sost__metrica {
    text-align: center;
    padding: 1rem 0.5rem;
    background: #f5f5f5;
    border-radius: 10px;
}

.ec-dashboard-sost__metrica-icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.ec-dashboard-sost__metrica-icono .dashicons {
    color: #fff;
    font-size: 1.1rem;
    width: 1.1rem;
    height: 1.1rem;
}

.ec-dashboard-sost__metrica-valor {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.ec-dashboard-sost__metrica-label {
    font-size: 0.75rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__indices {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ec-dashboard-sost__indice {
    padding: 1rem;
    background: var(--ec-primary-light);
    border-radius: 10px;
}

.ec-dashboard-sost__indice-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.ec-dashboard-sost__indice-header .dashicons {
    color: var(--ec-primary);
}

.ec-dashboard-sost__gauge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ec-dashboard-sost__gauge-bar {
    flex: 1;
    height: 12px;
    background: #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}

.ec-dashboard-sost__gauge-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
}

.ec-dashboard-sost__gauge-fill--bueno { background: #4caf50; }
.ec-dashboard-sost__gauge-fill--medio { background: #ff9800; }
.ec-dashboard-sost__gauge-fill--bajo { background: #f44336; }

.ec-dashboard-sost__gauge-valor {
    font-size: 1.1rem;
    font-weight: 700;
    min-width: 45px;
    text-align: right;
}

.ec-dashboard-sost__rotacion {
    text-align: center;
    padding: 0.5rem 0;
}

.ec-dashboard-sost__rotacion-valor {
    font-size: 2rem;
    font-weight: 700;
    color: var(--ec-primary);
}

.ec-dashboard-sost__rotacion-label {
    display: block;
    font-size: 0.8rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__indice-desc {
    margin: 0.5rem 0 0;
    font-size: 0.8rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__co2 {
    padding: 1rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border-radius: 10px;
    text-align: center;
    margin-bottom: 1.5rem;
}

.ec-dashboard-sost__co2-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.ec-dashboard-sost__co2-header .dashicons {
    color: #2e7d32;
}

.ec-dashboard-sost__co2-numero {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2e7d32;
}

.ec-dashboard-sost__co2-unidad {
    font-size: 1rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__co2-equivalencia {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--ec-text-light);
    margin-top: 0.5rem;
}

.ec-dashboard-sost__co2-equivalencia .dashicons {
    color: #4caf50;
}

.ec-dashboard-sost__cta {
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 10px;
}

.ec-dashboard-sost__cta h4 {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
}

.ec-dashboard-sost__cta ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.ec-dashboard-sost__cta li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: var(--ec-text-light);
}

.ec-dashboard-sost__cta li .dashicons {
    color: var(--ec-primary);
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

@media (max-width: 600px) {
    .ec-dashboard-sost__metricas {
        grid-template-columns: repeat(2, 1fr);
    }

    .ec-dashboard-sost__indices {
        grid-template-columns: 1fr;
    }
}
</style>
