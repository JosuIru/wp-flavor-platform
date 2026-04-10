<?php
/**
 * Template: Resultados de encuesta
 *
 * Variables disponibles:
 * - $resultados: array - Datos de resultados
 * - $formato: string - barras, pastel, texto
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($resultados) || empty($resultados)) {
    ?>
    <div class="flavor-encuesta-resultados__empty">
        <?php esc_html_e('No hay resultados disponibles', 'flavor-platform'); ?>
    </div>
    <?php
    return;
}

$formato = $formato ?? 'barras';
?>

<div class="flavor-encuesta-resultados" data-formato="<?php echo esc_attr($formato); ?>">
    <header class="flavor-encuesta-resultados__header">
        <h3><?php esc_html_e('Resultados', 'flavor-platform'); ?></h3>
        <div class="flavor-encuesta-resultados__meta">
            <span class="flavor-encuesta-resultados__total">
                <?php
                printf(
                    esc_html(_n('%d participante', '%d participantes', $resultados['total_participantes'], 'flavor-platform')),
                    $resultados['total_participantes']
                );
                ?>
            </span>

            <?php if ($resultados['estado'] === 'activa'): ?>
                <span class="flavor-encuesta-resultados__live">
                    <?php esc_html_e('En vivo', 'flavor-platform'); ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="flavor-encuesta-resultados__campos">
        <?php foreach ($resultados['campos'] as $campo): ?>
            <div class="flavor-encuesta-resultados__campo"
                 data-campo-tipo="<?php echo esc_attr($campo['tipo']); ?>">

                <h4 class="flavor-encuesta-resultados__pregunta">
                    <?php echo esc_html($campo['etiqueta']); ?>
                </h4>

                <?php
                // Renderizar según tipo de campo
                switch ($campo['tipo']):
                    case 'seleccion_unica':
                    case 'seleccion_multiple':
                        if (!empty($campo['conteos']) && !empty($campo['opciones'])):
                            $total_votos = array_sum($campo['conteos']);
                            ?>
                            <div class="flavor-encuesta-resultados__bars">
                                <?php
                                foreach ($campo['opciones'] as $indice => $opcion):
                                    $conteo = $campo['conteos'][$indice] ?? 0;
                                    $porcentaje = $total_votos > 0 ? round(($conteo / $total_votos) * 100) : 0;
                                    ?>
                                    <div class="flavor-encuesta-resultados__bar">
                                        <div class="flavor-encuesta-resultados__bar-header">
                                            <span class="flavor-encuesta-resultados__bar-label">
                                                <?php echo esc_html($opcion); ?>
                                            </span>
                                            <span class="flavor-encuesta-resultados__bar-stats">
                                                <?php echo esc_html($conteo); ?>
                                                (<?php echo esc_html($porcentaje); ?>%)
                                            </span>
                                        </div>
                                        <div class="flavor-encuesta-resultados__bar-track">
                                            <div class="flavor-encuesta-resultados__bar-fill"
                                                 style="width: <?php echo esc_attr($porcentaje); ?>%"
                                                 data-porcentaje="<?php echo esc_attr($porcentaje); ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php
                        else:
                            ?>
                            <p class="flavor-encuesta-resultados__no-data">
                                <?php esc_html_e('Sin respuestas aún', 'flavor-platform'); ?>
                            </p>
                            <?php
                        endif;
                        break;

                    case 'si_no':
                        if (!empty($campo['conteos'])):
                            $si = $campo['conteos'][1] ?? 0;
                            $no = $campo['conteos'][0] ?? 0;
                            $total_votos = $si + $no;
                            $porcentaje_si = $total_votos > 0 ? round(($si / $total_votos) * 100) : 0;
                            $porcentaje_no = $total_votos > 0 ? round(($no / $total_votos) * 100) : 0;
                            ?>
                            <div class="flavor-encuesta-resultados__binary">
                                <div class="flavor-encuesta-resultados__binary-option flavor-encuesta-resultados__binary-option--si">
                                    <span class="flavor-encuesta-resultados__binary-label"><?php esc_html_e('Sí', 'flavor-platform'); ?></span>
                                    <span class="flavor-encuesta-resultados__binary-value"><?php echo esc_html($si); ?></span>
                                    <span class="flavor-encuesta-resultados__binary-percent"><?php echo esc_html($porcentaje_si); ?>%</span>
                                </div>
                                <div class="flavor-encuesta-resultados__binary-option flavor-encuesta-resultados__binary-option--no">
                                    <span class="flavor-encuesta-resultados__binary-label"><?php esc_html_e('No', 'flavor-platform'); ?></span>
                                    <span class="flavor-encuesta-resultados__binary-value"><?php echo esc_html($no); ?></span>
                                    <span class="flavor-encuesta-resultados__binary-percent"><?php echo esc_html($porcentaje_no); ?>%</span>
                                </div>
                            </div>
                            <?php
                        endif;
                        break;

                    case 'escala':
                    case 'estrellas':
                        if (!empty($campo['conteos'])):
                            $suma = 0;
                            $total_votos = 0;
                            foreach ($campo['conteos'] as $valor => $cantidad) {
                                $suma += $valor * $cantidad;
                                $total_votos += $cantidad;
                            }
                            $promedio = $total_votos > 0 ? round($suma / $total_votos, 1) : 0;
                            ?>
                            <div class="flavor-encuesta-resultados__rating">
                                <div class="flavor-encuesta-resultados__rating-value">
                                    <?php echo esc_html($promedio); ?>
                                </div>

                                <?php if ($campo['tipo'] === 'estrellas'): ?>
                                    <div class="flavor-encuesta-resultados__stars-display">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            $clase = $i <= round($promedio) ? 'filled' : '';
                                            echo '<span class="flavor-encuesta-resultados__star ' . esc_attr($clase) . '">★</span>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flavor-encuesta-resultados__rating-count">
                                    <?php
                                    printf(
                                        esc_html(_n('%d voto', '%d votos', $total_votos, 'flavor-platform')),
                                        $total_votos
                                    );
                                    ?>
                                </div>
                            </div>
                            <?php
                        endif;
                        break;

                    case 'numero':
                        if (!empty($campo['estadisticas'])):
                            $stats = $campo['estadisticas'];
                            ?>
                            <div class="flavor-encuesta-resultados__stats flavor-encuesta-resultados__stats--numeric">
                                <div class="flavor-encuesta-resultados__stat">
                                    <span class="flavor-encuesta-resultados__stat-value">
                                        <?php echo esc_html(number_format($stats['promedio'], 2)); ?>
                                    </span>
                                    <span class="flavor-encuesta-resultados__stat-label">
                                        <?php esc_html_e('Promedio', 'flavor-platform'); ?>
                                    </span>
                                </div>
                                <div class="flavor-encuesta-resultados__stat">
                                    <span class="flavor-encuesta-resultados__stat-value">
                                        <?php echo esc_html($stats['minimo']); ?>
                                    </span>
                                    <span class="flavor-encuesta-resultados__stat-label">
                                        <?php esc_html_e('Mínimo', 'flavor-platform'); ?>
                                    </span>
                                </div>
                                <div class="flavor-encuesta-resultados__stat">
                                    <span class="flavor-encuesta-resultados__stat-value">
                                        <?php echo esc_html($stats['maximo']); ?>
                                    </span>
                                    <span class="flavor-encuesta-resultados__stat-label">
                                        <?php esc_html_e('Máximo', 'flavor-platform'); ?>
                                    </span>
                                </div>
                                <div class="flavor-encuesta-resultados__stat">
                                    <span class="flavor-encuesta-resultados__stat-value">
                                        <?php echo esc_html($stats['total']); ?>
                                    </span>
                                    <span class="flavor-encuesta-resultados__stat-label">
                                        <?php esc_html_e('Respuestas', 'flavor-platform'); ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        endif;
                        break;

                    case 'texto':
                    case 'textarea':
                        if (!empty($campo['respuestas_texto'])):
                            ?>
                            <div class="flavor-encuesta-resultados__text-responses">
                                <p class="flavor-encuesta-resultados__text-count">
                                    <?php
                                    printf(
                                        esc_html__('%d respuestas de texto', 'flavor-platform'),
                                        count($campo['respuestas_texto'])
                                    );
                                    ?>
                                </p>
                                <div class="flavor-encuesta-resultados__text-list">
                                    <?php foreach (array_slice($campo['respuestas_texto'], 0, 10) as $respuesta): ?>
                                        <div class="flavor-encuesta-resultados__text-item">
                                            <?php echo esc_html($respuesta); ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (count($campo['respuestas_texto']) > 10): ?>
                                        <p class="flavor-encuesta-resultados__text-more">
                                            <?php
                                            printf(
                                                esc_html__('Y %d respuestas más...', 'flavor-platform'),
                                                count($campo['respuestas_texto']) - 10
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        else:
                            ?>
                            <p class="flavor-encuesta-resultados__no-data">
                                <?php esc_html_e('Sin respuestas de texto', 'flavor-platform'); ?>
                            </p>
                            <?php
                        endif;
                        break;

                    default:
                        ?>
                        <p class="flavor-encuesta-resultados__no-data">
                            <?php esc_html_e('Tipo de campo no soportado', 'flavor-platform'); ?>
                        </p>
                        <?php
                endswitch;
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (current_user_can('manage_options')): ?>
        <footer class="flavor-encuesta-resultados__footer">
            <a href="#" class="flavor-encuesta-resultados__export" data-encuesta-id="<?php echo esc_attr($resultados['encuesta_id']); ?>">
                <?php esc_html_e('Exportar resultados', 'flavor-platform'); ?>
            </a>
        </footer>
    <?php endif; ?>
</div>
