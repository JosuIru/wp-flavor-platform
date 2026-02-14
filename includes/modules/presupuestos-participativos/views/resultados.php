<?php
/**
 * Vista: Resultados de la votación
 *
 * @package FlavorChatIA
 * @var object $edicion            Edición
 * @var array  $proyectos_ranking  Proyectos ordenados por votos
 * @var int    $total_votantes     Total de personas que han votado
 * @var int    $total_proyectos    Total de proyectos presentados
 * @var array  $atributos          Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas_grid = intval($atributos['columnas'] ?? 2);
$presupuesto_total = floatval($edicion->presupuesto_total ?? 0);
$presupuesto_acumulado = 0;
?>

<div class="flavor-pp-resultados">

    <div class="flavor-pp-resultados-header">
        <h2>
            <?php printf(
                esc_html__('Resultados - Presupuestos Participativos %d', 'flavor-chat-ia'),
                intval($edicion->anio)
            ); ?>
        </h2>

        <div class="flavor-pp-estadisticas-generales">
            <div class="flavor-pp-stat">
                <span class="valor"><?php echo number_format($presupuesto_total, 0, ',', '.'); ?> €</span>
                <span class="label"><?php esc_html_e('Presupuesto total', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-pp-stat">
                <span class="valor"><?php echo intval($total_proyectos); ?></span>
                <span class="label"><?php esc_html_e('Proyectos presentados', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-pp-stat">
                <span class="valor"><?php echo intval($total_votantes); ?></span>
                <span class="label"><?php esc_html_e('Personas han votado', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-pp-stat">
                <span class="valor"><?php echo count($proyectos_ranking); ?></span>
                <span class="label"><?php esc_html_e('Proyectos validados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <?php if (empty($proyectos_ranking)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-chart-bar"></span>
            <p><?php esc_html_e('Aún no hay resultados disponibles.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-pp-ranking">
            <?php
            $posicion = 1;
            foreach ($proyectos_ranking as $proyecto):
                $presupuesto_proyecto = floatval($proyecto->presupuesto_solicitado);
                $presupuesto_acumulado += $presupuesto_proyecto;
                $dentro_presupuesto = $presupuesto_acumulado <= $presupuesto_total;
                $votos_total = intval($proyecto->total_votos);
                $estado_proyecto = $proyecto->estado;

                $etiquetas_estado = [
                    'validado' => __('Validado', 'flavor-chat-ia'),
                    'en_votacion' => __('En votación', 'flavor-chat-ia'),
                    'seleccionado' => __('Seleccionado', 'flavor-chat-ia'),
                    'en_ejecucion' => __('En ejecución', 'flavor-chat-ia'),
                    'ejecutado' => __('Ejecutado', 'flavor-chat-ia'),
                ];
            ?>
                <article class="flavor-pp-resultado-item <?php echo $dentro_presupuesto ? 'seleccionable' : 'fuera-presupuesto'; ?> <?php echo 'estado-' . esc_attr($estado_proyecto); ?>">

                    <div class="flavor-pp-posicion">
                        <span class="numero"><?php echo intval($posicion); ?></span>
                        <?php if ($posicion <= 3 && $dentro_presupuesto): ?>
                            <span class="medalla medalla-<?php echo intval($posicion); ?>">
                                <?php
                                $medallas = ['🥇', '🥈', '🥉'];
                                echo $medallas[$posicion - 1];
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-pp-resultado-content">
                        <div class="flavor-pp-resultado-header">
                            <h3 class="flavor-pp-titulo">
                                <?php echo esc_html($proyecto->titulo); ?>
                            </h3>
                            <span class="flavor-pp-estado flavor-pp-estado-<?php echo esc_attr($estado_proyecto); ?>">
                                <?php echo esc_html($etiquetas_estado[$estado_proyecto] ?? ucfirst($estado_proyecto)); ?>
                            </span>
                        </div>

                        <p class="flavor-pp-descripcion">
                            <?php echo esc_html(wp_trim_words($proyecto->descripcion, 25)); ?>
                        </p>

                        <div class="flavor-pp-resultado-meta">
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-money-alt"></span>
                                <span><?php echo number_format($presupuesto_proyecto, 0, ',', '.'); ?> €</span>
                            </div>
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-category"></span>
                                <span><?php echo esc_html(ucfirst($proyecto->categoria)); ?></span>
                            </div>
                            <?php if ($proyecto->porcentaje_ejecucion > 0): ?>
                            <div class="flavor-pp-meta-item">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <span><?php printf(esc_html__('%d%% ejecutado', 'flavor-chat-ia'), intval($proyecto->porcentaje_ejecucion)); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-pp-resultado-votos">
                        <div class="flavor-pp-votos-circulo <?php echo $votos_total > 50 ? 'alto' : ($votos_total > 20 ? 'medio' : 'bajo'); ?>">
                            <span class="numero"><?php echo intval($votos_total); ?></span>
                            <span class="label"><?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                        </div>

                        <?php if ($dentro_presupuesto): ?>
                            <span class="flavor-pp-badge-seleccionable">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e('Dentro del presupuesto', 'flavor-chat-ia'); ?>
                            </span>
                        <?php else: ?>
                            <span class="flavor-pp-badge-fuera">
                                <?php esc_html_e('Fuera del presupuesto', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($proyecto->porcentaje_ejecucion > 0): ?>
                    <div class="flavor-pp-barra-progreso">
                        <div class="flavor-pp-progreso-fill" style="width: <?php echo intval($proyecto->porcentaje_ejecucion); ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </article>
            <?php
                $posicion++;
            endforeach;
            ?>
        </div>

        <div class="flavor-pp-resumen-presupuesto">
            <div class="flavor-pp-barra-presupuesto">
                <div class="flavor-pp-presupuesto-usado"
                     style="width: <?php echo min(100, ($presupuesto_acumulado / max(1, $presupuesto_total)) * 100); ?>%;">
                </div>
            </div>
            <div class="flavor-pp-presupuesto-texto">
                <span>
                    <?php printf(
                        esc_html__('%s€ de %s€ asignados', 'flavor-chat-ia'),
                        number_format(min($presupuesto_acumulado, $presupuesto_total), 0, ',', '.'),
                        number_format($presupuesto_total, 0, ',', '.')
                    ); ?>
                </span>
                <span>
                    (<?php echo round(min(100, ($presupuesto_acumulado / max(1, $presupuesto_total)) * 100)); ?>%)
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>
