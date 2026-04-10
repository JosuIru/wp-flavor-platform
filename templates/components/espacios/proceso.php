<?php
/**
 * Template: Pasos del Proceso de Reserva
 *
 * Muestra los pasos del proceso de reserva: seleccionar, confirmar, pagar
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Proceso de Reserva';
$subtitulo_seccion = isset($args['subtitulo_seccion']) ? $args['subtitulo_seccion'] : 'Reserva tu espacio en 3 sencillos pasos';
$paso_actual = isset($args['paso_actual']) ? intval($args['paso_actual']) : 0;
$pasos = isset($args['pasos']) ? $args['pasos'] : array();
$mostrar_como_seccion = isset($args['mostrar_como_seccion']) ? $args['mostrar_como_seccion'] : true;
$estilo = isset($args['estilo']) ? $args['estilo'] : 'horizontal'; // horizontal, vertical, cards
$mostrar_tiempo_estimado = isset($args['mostrar_tiempo_estimado']) ? $args['mostrar_tiempo_estimado'] : true;
$tiempo_total = isset($args['tiempo_total']) ? $args['tiempo_total'] : '5 minutos';

// Datos de demostración
if (empty($pasos)) {
    $pasos = array(
        array(
            'numero' => 1,
            'titulo' => 'Selecciona tu Espacio',
            'descripcion' => 'Explora nuestra variedad de espacios y elige el que mejor se adapte a tus necesidades. Filtra por capacidad, equipamiento y precio.',
            'icono' => 'search',
            'tiempo' => '1 min',
            'completado' => false,
            'activo' => true,
            'detalles' => array(
                'Más de 15 espacios disponibles',
                'Filtros avanzados de búsqueda',
                'Fotos y tours virtuales',
                'Comparador de espacios'
            )
        ),
        array(
            'numero' => 2,
            'titulo' => 'Confirma Fecha y Hora',
            'descripcion' => 'Consulta la disponibilidad en tiempo real y selecciona el horario que mejor te convenga. Puedes reservar por horas o jornadas completas.',
            'icono' => 'calendar',
            'tiempo' => '2 min',
            'completado' => false,
            'activo' => false,
            'detalles' => array(
                'Calendario interactivo',
                'Disponibilidad en tiempo real',
                'Reservas recurrentes',
                'Flexibilidad horaria'
            )
        ),
        array(
            'numero' => 3,
            'titulo' => 'Realiza el Pago',
            'descripcion' => 'Completa tu reserva con nuestro sistema de pago seguro. Aceptamos múltiples métodos de pago y recibirás confirmación instantánea.',
            'icono' => 'credit-card',
            'tiempo' => '2 min',
            'completado' => false,
            'activo' => false,
            'detalles' => array(
                'Pago 100% seguro',
                'Múltiples métodos de pago',
                'Facturación automática',
                'Política de cancelación flexible'
            )
        )
    );
}

// Actualizar estado de pasos según paso_actual
if ($paso_actual > 0) {
    foreach ($pasos as $indice => &$paso) {
        if (($indice + 1) < $paso_actual) {
            $paso['completado'] = true;
            $paso['activo'] = false;
        } elseif (($indice + 1) === $paso_actual) {
            $paso['completado'] = false;
            $paso['activo'] = true;
        } else {
            $paso['completado'] = false;
            $paso['activo'] = false;
        }
    }
    unset($paso);
}

// Función para renderizar icono SVG
function renderizar_icono_proceso($tipo_icono) {
    $iconos = array(
        'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
        'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
        'credit-card' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
        'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
    );

    return isset($iconos[$tipo_icono]) ? $iconos[$tipo_icono] : $iconos['check'];
}
?>

<section class="flavor-proceso <?php echo $mostrar_como_seccion ? '' : 'flavor-proceso--inline'; ?> flavor-proceso--<?php echo esc_attr($estilo); ?>">
    <div class="flavor-proceso__container">
        <?php if ($mostrar_como_seccion) : ?>
        <header class="flavor-proceso__header">
            <h2 class="flavor-proceso__titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-proceso__subtitulo"><?php echo esc_html($subtitulo_seccion); ?></p>
            <?php if ($mostrar_tiempo_estimado) : ?>
            <div class="flavor-proceso__tiempo-total">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Tiempo estimado: <?php echo esc_html($tiempo_total); ?></span>
            </div>
            <?php endif; ?>
        </header>
        <?php endif; ?>

        <div class="flavor-proceso__pasos">
            <?php foreach ($pasos as $indice => $paso) :
                $clases_paso = 'flavor-proceso__paso';
                if ($paso['completado']) $clases_paso .= ' flavor-proceso__paso--completado';
                if ($paso['activo']) $clases_paso .= ' flavor-proceso__paso--activo';
                $es_ultimo = ($indice === count($pasos) - 1);
            ?>
            <div class="<?php echo esc_attr($clases_paso); ?>">
                <div class="flavor-proceso__paso-indicador">
                    <div class="flavor-proceso__paso-icono">
                        <?php if ($paso['completado']) : ?>
                            <?php echo renderizar_icono_proceso('check'); ?>
                        <?php else : ?>
                            <?php echo renderizar_icono_proceso($paso['icono']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!$es_ultimo) : ?>
                    <div class="flavor-proceso__paso-linea">
                        <div class="flavor-proceso__paso-linea-progreso"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-proceso__paso-contenido">
                    <div class="flavor-proceso__paso-header">
                        <span class="flavor-proceso__paso-numero">Paso <?php echo esc_html($paso['numero']); ?></span>
                        <?php if ($mostrar_tiempo_estimado && isset($paso['tiempo'])) : ?>
                        <span class="flavor-proceso__paso-tiempo">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($paso['tiempo']); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="flavor-proceso__paso-titulo"><?php echo esc_html($paso['titulo']); ?></h3>
                    <p class="flavor-proceso__paso-descripcion"><?php echo esc_html($paso['descripcion']); ?></p>

                    <?php if (!empty($paso['detalles'])) : ?>
                    <ul class="flavor-proceso__paso-detalles">
                        <?php foreach ($paso['detalles'] as $detalle) : ?>
                        <li class="flavor-proceso__paso-detalle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <?php echo esc_html($detalle); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($paso['activo']) : ?>
                    <a href="#paso-<?php echo esc_attr($paso['numero']); ?>" class="flavor-proceso__paso-boton">
                        Comenzar
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Barra de progreso móvil -->
        <div class="flavor-proceso__progreso-movil">
            <div class="flavor-proceso__progreso-barra">
                <div class="flavor-proceso__progreso-completado" style="width: <?php echo esc_attr((($paso_actual > 0 ? $paso_actual : 1) / count($pasos)) * 100); ?>%;"></div>
            </div>
            <div class="flavor-proceso__progreso-texto">
                Paso <?php echo esc_html($paso_actual > 0 ? $paso_actual : 1); ?> de <?php echo esc_html(count($pasos)); ?>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-proceso {
    padding: 80px 0;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.flavor-proceso--inline {
    padding: 40px 0;
}

.flavor-proceso__container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

.flavor-proceso__header {
    text-align: center;
    margin-bottom: 60px;
}

.flavor-proceso__titulo {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px;
}

.flavor-proceso__subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0 0 20px;
}

.flavor-proceso__tiempo-total {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #eef2ff;
    color: #667eea;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Estilo Horizontal */
.flavor-proceso--horizontal .flavor-proceso__pasos {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.flavor-proceso--horizontal .flavor-proceso__paso {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.flavor-proceso--horizontal .flavor-proceso__paso-indicador {
    display: flex;
    align-items: center;
    margin-bottom: 24px;
}

.flavor-proceso--horizontal .flavor-proceso__paso-linea {
    flex: 1;
    height: 3px;
    background: #e2e8f0;
    margin-left: 16px;
    border-radius: 2px;
    overflow: hidden;
}

.flavor-proceso--horizontal .flavor-proceso__paso-linea-progreso {
    width: 0;
    height: 100%;
    background: #667eea;
    transition: width 0.5s ease;
}

.flavor-proceso--horizontal .flavor-proceso__paso--completado .flavor-proceso__paso-linea-progreso {
    width: 100%;
}

/* Estilo Vertical */
.flavor-proceso--vertical .flavor-proceso__pasos {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.flavor-proceso--vertical .flavor-proceso__paso {
    display: flex;
    gap: 24px;
}

.flavor-proceso--vertical .flavor-proceso__paso-indicador {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.flavor-proceso--vertical .flavor-proceso__paso-linea {
    width: 3px;
    flex: 1;
    min-height: 60px;
    background: #e2e8f0;
    margin-top: 16px;
    border-radius: 2px;
    overflow: hidden;
}

.flavor-proceso--vertical .flavor-proceso__paso-linea-progreso {
    width: 100%;
    height: 0;
    background: #667eea;
    transition: height 0.5s ease;
}

.flavor-proceso--vertical .flavor-proceso__paso--completado .flavor-proceso__paso-linea-progreso {
    height: 100%;
}

.flavor-proceso--vertical .flavor-proceso__paso-contenido {
    flex: 1;
    padding-bottom: 40px;
}

/* Estilo Cards */
.flavor-proceso--cards .flavor-proceso__pasos {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.flavor-proceso--cards .flavor-proceso__paso {
    background: #ffffff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.flavor-proceso--cards .flavor-proceso__paso:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.flavor-proceso--cards .flavor-proceso__paso--activo {
    border-color: #667eea;
}

.flavor-proceso--cards .flavor-proceso__paso-indicador {
    margin-bottom: 20px;
}

.flavor-proceso--cards .flavor-proceso__paso-linea {
    display: none;
}

/* Icono del paso */
.flavor-proceso__paso-icono {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 16px;
    color: #64748b;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.flavor-proceso__paso--activo .flavor-proceso__paso-icono {
    background: #667eea;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.flavor-proceso__paso--completado .flavor-proceso__paso-icono {
    background: #10b981;
    color: #ffffff;
}

/* Contenido del paso */
.flavor-proceso__paso-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.flavor-proceso__paso-numero {
    font-size: 0.75rem;
    font-weight: 600;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-proceso__paso-tiempo {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: #94a3b8;
}

.flavor-proceso__paso-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px;
}

.flavor-proceso__paso--activo .flavor-proceso__paso-titulo {
    color: #667eea;
}

.flavor-proceso__paso-descripcion {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 16px;
}

.flavor-proceso__paso-detalles {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
}

.flavor-proceso__paso-detalle {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #475569;
    padding: 6px 0;
}

.flavor-proceso__paso-detalle svg {
    color: #10b981;
    flex-shrink: 0;
}

.flavor-proceso__paso-boton {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #667eea;
    color: #ffffff;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.flavor-proceso__paso-boton:hover {
    background: #5a67d8;
    transform: translateX(4px);
}

/* Progreso móvil */
.flavor-proceso__progreso-movil {
    display: none;
    margin-top: 40px;
    text-align: center;
}

.flavor-proceso__progreso-barra {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 12px;
}

.flavor-proceso__progreso-completado {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 3px;
    transition: width 0.5s ease;
}

.flavor-proceso__progreso-texto {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-proceso--horizontal .flavor-proceso__pasos,
    .flavor-proceso--cards .flavor-proceso__pasos {
        flex-direction: column;
        grid-template-columns: 1fr;
    }

    .flavor-proceso--horizontal .flavor-proceso__paso {
        flex-direction: row;
        gap: 24px;
    }

    .flavor-proceso--horizontal .flavor-proceso__paso-indicador {
        flex-direction: column;
        margin-bottom: 0;
    }

    .flavor-proceso--horizontal .flavor-proceso__paso-linea {
        width: 3px;
        height: auto;
        flex: 1;
        min-height: 60px;
        margin-left: 0;
        margin-top: 16px;
    }

    .flavor-proceso--horizontal .flavor-proceso__paso-contenido {
        padding-bottom: 30px;
    }
}

@media (max-width: 576px) {
    .flavor-proceso {
        padding: 60px 0;
    }

    .flavor-proceso__titulo {
        font-size: 1.75rem;
    }

    .flavor-proceso__header {
        margin-bottom: 40px;
    }

    .flavor-proceso__paso-icono {
        width: 52px;
        height: 52px;
    }

    .flavor-proceso__paso-icono svg {
        width: 22px;
        height: 22px;
    }

    .flavor-proceso__paso-titulo {
        font-size: 1.125rem;
    }

    .flavor-proceso__paso-detalles {
        display: none;
    }

    .flavor-proceso__progreso-movil {
        display: block;
    }

    .flavor-proceso--cards .flavor-proceso__paso {
        padding: 24px;
    }
}
</style>
