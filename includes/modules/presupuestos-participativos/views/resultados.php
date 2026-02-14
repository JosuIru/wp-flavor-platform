<?php
/**
 * Vista: Resultados de Votacion
 *
 * Variables disponibles:
 * - $edicion: objeto con datos de la edicion
 * - $proyectos_ranking: array de proyectos ordenados por votos
 * - $total_votantes: int numero total de votantes
 * - $total_proyectos: int numero total de proyectos
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atributos['columnas'] ?? 2);
$presupuesto_total = floatval($edicion->presupuesto ?? 0);
$presupuesto_asignado = 0;
?>

<div class="flavor-pp-resultados-contenedor">
    <div class="flavor-pp-resultados-header">
        <h2><?php printf(esc_html__('Resultados Presupuestos Participativos %d', 'flavor-chat-ia'), intval($edicion->anio)); ?></h2>

        <div class="flavor-pp-estadisticas-resumen">
            <div class="flavor-pp-stat">
                <span class="flavor-pp-stat-valor"><?php echo esc_html(number_format($presupuesto_total, 0, ',', '.')); ?> EUR</span>
                <span class="flavor-pp-stat-label"><?php esc_html_e('Presupuesto total', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-pp-stat">
                <span class="flavor-pp-stat-valor"><?php echo esc_html($total_proyectos); ?></span>
                <span class="flavor-pp-stat-label"><?php esc_html_e('Proyectos presentados', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-pp-stat">
                <span class="flavor-pp-stat-valor"><?php echo esc_html($total_votantes); ?></span>
                <span class="flavor-pp-stat-label"><?php esc_html_e('Personas votaron', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <?php if (empty($proyectos_ranking)): ?>
        <div class="flavor-pp-vacio">
            <span class="dashicons dashicons-chart-bar"></span>
            <p><?php esc_html_e('Aun no hay resultados disponibles para esta edicion.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="flavor-pp-ranking">
            <h3><?php esc_html_e('Ranking de proyectos', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-pp-ranking-lista">
                <?php
                $posicion = 1;
                foreach ($proyectos_ranking as $proyecto):
                    $es_seleccionado = in_array($proyecto->estado, ['seleccionado', 'en_ejecucion', 'ejecutado']);
                    if ($es_seleccionado) {
                        $presupuesto_asignado += floatval($proyecto->presupuesto_estimado);
                    }
                ?>
                <div class="flavor-pp-ranking-item <?php echo $es_seleccionado ? 'seleccionado' : ''; ?>">
                    <div class="flavor-pp-ranking-posicion">
                        <?php if ($posicion <= 3): ?>
                            <span class="flavor-pp-medalla flavor-pp-medalla-<?php echo $posicion; ?>">
                                <?php echo $posicion; ?>
                            </span>
                        <?php else: ?>
                            <span class="flavor-pp-numero"><?php echo $posicion; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-pp-ranking-proyecto">
                        <h4 class="flavor-pp-ranking-titulo">
                            <?php echo esc_html($proyecto->titulo); ?>
                            <?php if ($es_seleccionado): ?>
                                <span class="flavor-pp-badge-seleccionado">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Seleccionado', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </h4>
                        <p class="flavor-pp-ranking-descripcion">
                            <?php echo esc_html(wp_trim_words($proyecto->descripcion, 20, '...')); ?>
                        </p>
                    </div>

                    <div class="flavor-pp-ranking-stats">
                        <div class="flavor-pp-ranking-votos">
                            <span class="dashicons dashicons-heart"></span>
                            <strong><?php echo esc_html($proyecto->total_votos); ?></strong>
                            <span><?php esc_html_e('votos', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flavor-pp-ranking-presupuesto">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php echo esc_html(number_format($proyecto->presupuesto_estimado, 0, ',', '.')); ?> EUR
                        </div>
                    </div>

                    <div class="flavor-pp-ranking-barra">
                        <?php
                        $max_votos = $proyectos_ranking[0]->total_votos ?: 1;
                        $porcentaje = ($proyecto->total_votos / $max_votos) * 100;
                        ?>
                        <div class="flavor-pp-barra-progreso" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                    </div>
                </div>
                <?php
                    $posicion++;
                endforeach;
                ?>
            </div>
        </div>

        <?php if ($presupuesto_asignado > 0): ?>
        <div class="flavor-pp-presupuesto-resumen">
            <h3><?php esc_html_e('Distribucion del presupuesto', 'flavor-chat-ia'); ?></h3>
            <div class="flavor-pp-presupuesto-barra-contenedor">
                <div class="flavor-pp-presupuesto-barra">
                    <div class="flavor-pp-presupuesto-asignado"
                         style="width: <?php echo esc_attr(min(100, ($presupuesto_asignado / $presupuesto_total) * 100)); ?>%">
                    </div>
                </div>
                <div class="flavor-pp-presupuesto-leyenda">
                    <span class="flavor-pp-leyenda-item asignado">
                        <span class="flavor-pp-leyenda-color"></span>
                        <?php printf(
                            esc_html__('Asignado: %s EUR (%d%%)', 'flavor-chat-ia'),
                            number_format($presupuesto_asignado, 0, ',', '.'),
                            round(($presupuesto_asignado / $presupuesto_total) * 100)
                        ); ?>
                    </span>
                    <span class="flavor-pp-leyenda-item disponible">
                        <span class="flavor-pp-leyenda-color"></span>
                        <?php printf(
                            esc_html__('Disponible: %s EUR', 'flavor-chat-ia'),
                            number_format($presupuesto_total - $presupuesto_asignado, 0, ',', '.')
                        ); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
