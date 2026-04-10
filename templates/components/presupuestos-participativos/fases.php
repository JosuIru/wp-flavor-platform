<?php
/**
 * Template: Timeline de Fases - Presupuestos Participativos
 *
 * Muestra las fases del proceso de presupuestos participativos
 * (propuesta, debate, votación, ejecución)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Fases del Proceso';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Conoce cómo funciona el proceso de presupuestos participativos';
$fase_actual = isset($args['fase_actual']) ? (int) $args['fase_actual'] : 1;
$mostrar_fechas = isset($args['mostrar_fechas']) ? (bool) $args['mostrar_fechas'] : true;
$fases = isset($args['fases']) ? $args['fases'] : array();

// Datos de demostración si no hay fases definidas
if (empty($fases)) {
    $fases = array(
        array(
            'numero' => 1,
            'titulo' => 'Propuesta',
            'descripcion' => 'Presenta tu idea de proyecto para mejorar tu comunidad. Cualquier ciudadano puede proponer iniciativas.',
            'icono' => 'lightbulb',
            'fecha_inicio' => '1 de Enero',
            'fecha_fin' => '31 de Marzo',
            'estado' => 'completada'
        ),
        array(
            'numero' => 2,
            'titulo' => 'Debate',
            'descripcion' => 'Las propuestas se discuten y refinan con la participación de la comunidad y expertos técnicos.',
            'icono' => 'comments',
            'fecha_inicio' => '1 de Abril',
            'fecha_fin' => '30 de Mayo',
            'estado' => 'activa'
        ),
        array(
            'numero' => 3,
            'titulo' => 'Votación',
            'descripcion' => 'Los ciudadanos votan por las propuestas que consideran más beneficiosas para la comunidad.',
            'icono' => 'vote-yea',
            'fecha_inicio' => '1 de Junio',
            'fecha_fin' => '30 de Junio',
            'estado' => 'pendiente'
        ),
        array(
            'numero' => 4,
            'titulo' => 'Ejecución',
            'descripcion' => 'Los proyectos ganadores se implementan con seguimiento ciudadano y rendición de cuentas.',
            'icono' => 'hammer',
            'fecha_inicio' => '1 de Julio',
            'fecha_fin' => '31 de Diciembre',
            'estado' => 'pendiente'
        )
    );
}

$clase_contenedor = isset($args['clase_extra']) ? 'flavor-fases-timeline ' . esc_attr($args['clase_extra']) : 'flavor-fases-timeline';
?>

<section class="<?php echo esc_attr($clase_contenedor); ?>">
    <div class="flavor-fases-header">
        <?php if (!empty($titulo)) : ?>
            <h2 class="flavor-fases-titulo"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>

        <?php if (!empty($subtitulo)) : ?>
            <p class="flavor-fases-subtitulo"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
    </div>

    <div class="flavor-fases-contenedor">
        <div class="flavor-fases-linea-progreso">
            <div class="flavor-fases-linea-completada" style="width: <?php echo esc_attr(($fase_actual / count($fases)) * 100); ?>%;"></div>
        </div>

        <div class="flavor-fases-lista">
            <?php foreach ($fases as $indice => $fase) :
                $numero_fase = isset($fase['numero']) ? $fase['numero'] : $indice + 1;
                $estado_fase = isset($fase['estado']) ? $fase['estado'] : 'pendiente';
                $clase_estado = 'flavor-fase-' . esc_attr($estado_fase);
                $es_fase_actual = ($numero_fase === $fase_actual);
            ?>
                <div class="flavor-fase-item <?php echo esc_attr($clase_estado); ?> <?php echo $es_fase_actual ? 'flavor-fase-actual' : ''; ?>">
                    <div class="flavor-fase-indicador">
                        <div class="flavor-fase-numero">
                            <?php if ($estado_fase === 'completada') : ?>
                                <svg class="flavor-fase-icono-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            <?php else : ?>
                                <span><?php echo esc_html($numero_fase); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-fase-contenido">
                        <div class="flavor-fase-cabecera">
                            <?php if (!empty($fase['icono'])) : ?>
                                <span class="flavor-fase-icono">
                                    <i class="fas fa-<?php echo esc_attr($fase['icono']); ?>" aria-hidden="true"></i>
                                </span>
                            <?php endif; ?>

                            <h3 class="flavor-fase-titulo">
                                <?php echo esc_html($fase['titulo']); ?>
                                <?php if ($es_fase_actual) : ?>
                                    <span class="flavor-fase-badge-actual">En curso</span>
                                <?php endif; ?>
                            </h3>
                        </div>

                        <?php if (!empty($fase['descripcion'])) : ?>
                            <p class="flavor-fase-descripcion"><?php echo esc_html($fase['descripcion']); ?></p>
                        <?php endif; ?>

                        <?php if ($mostrar_fechas && (!empty($fase['fecha_inicio']) || !empty($fase['fecha_fin']))) : ?>
                            <div class="flavor-fase-fechas">
                                <svg class="flavor-fase-icono-calendario" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>
                                    <?php
                                    if (!empty($fase['fecha_inicio']) && !empty($fase['fecha_fin'])) {
                                        echo esc_html($fase['fecha_inicio'] . ' - ' . $fase['fecha_fin']);
                                    } elseif (!empty($fase['fecha_inicio'])) {
                                        echo esc_html('Desde ' . $fase['fecha_inicio']);
                                    } else {
                                        echo esc_html('Hasta ' . $fase['fecha_fin']);
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.flavor-fases-timeline {
    padding: 3rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 1rem;
    margin: 2rem 0;
}

.flavor-fases-header {
    text-align: center;
    margin-bottom: 3rem;
}

.flavor-fases-titulo {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.75rem 0;
}

.flavor-fases-subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.flavor-fases-contenedor {
    position: relative;
    max-width: 900px;
    margin: 0 auto;
}

.flavor-fases-linea-progreso {
    position: absolute;
    top: 2rem;
    left: 2rem;
    right: 2rem;
    height: 4px;
    background: #cbd5e1;
    border-radius: 2px;
    z-index: 1;
    display: none;
}

.flavor-fases-linea-completada {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 2px;
    transition: width 0.5s ease;
}

.flavor-fases-lista {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.flavor-fase-item {
    display: flex;
    gap: 1.25rem;
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #cbd5e1;
}

.flavor-fase-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
}

.flavor-fase-completada {
    border-left-color: #10b981;
}

.flavor-fase-activa {
    border-left-color: #3b82f6;
}

.flavor-fase-actual {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-left-color: #3b82f6;
}

.flavor-fase-pendiente {
    border-left-color: #94a3b8;
}

.flavor-fase-indicador {
    flex-shrink: 0;
}

.flavor-fase-numero {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    font-weight: 700;
    background: #e2e8f0;
    color: #64748b;
    transition: all 0.3s ease;
}

.flavor-fase-completada .flavor-fase-numero {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
}

.flavor-fase-activa .flavor-fase-numero,
.flavor-fase-actual .flavor-fase-numero {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #ffffff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
}

.flavor-fase-icono-check {
    width: 1.25rem;
    height: 1.25rem;
}

.flavor-fase-contenido {
    flex: 1;
    min-width: 0;
}

.flavor-fase-cabecera {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.flavor-fase-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: #f1f5f9;
    border-radius: 0.5rem;
    color: #64748b;
    font-size: 0.875rem;
}

.flavor-fase-activa .flavor-fase-icono,
.flavor-fase-actual .flavor-fase-icono {
    background: #dbeafe;
    color: #3b82f6;
}

.flavor-fase-completada .flavor-fase-icono {
    background: #d1fae5;
    color: #10b981;
}

.flavor-fase-titulo {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.flavor-fase-badge-actual {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.flavor-fase-descripcion {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 0.75rem 0;
}

.flavor-fase-fechas {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: #f1f5f9;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: #475569;
}

.flavor-fase-icono-calendario {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

/* Responsive Design */
@media (min-width: 768px) {
    .flavor-fases-timeline {
        padding: 4rem 2rem;
    }

    .flavor-fases-titulo {
        font-size: 2.5rem;
    }

    .flavor-fases-lista {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .flavor-fase-item {
        flex-direction: column;
        text-align: center;
        border-left: none;
        border-top: 4px solid #cbd5e1;
    }

    .flavor-fase-completada {
        border-top-color: #10b981;
    }

    .flavor-fase-activa,
    .flavor-fase-actual {
        border-top-color: #3b82f6;
    }

    .flavor-fase-pendiente {
        border-top-color: #94a3b8;
    }

    .flavor-fase-indicador {
        display: flex;
        justify-content: center;
    }

    .flavor-fase-cabecera {
        justify-content: center;
    }

    .flavor-fase-titulo {
        justify-content: center;
    }

    .flavor-fase-fechas {
        justify-content: center;
    }
}

@media (min-width: 1024px) {
    .flavor-fases-lista {
        grid-template-columns: repeat(4, 1fr);
    }

    .flavor-fases-linea-progreso {
        display: block;
        top: calc(1.5rem + 1.5rem);
        left: calc(12.5% + 1.5rem);
        right: calc(12.5% + 1.5rem);
    }
}
</style>
