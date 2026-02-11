<?php
/**
 * Template: Calendario Visual de Disponibilidad
 *
 * Muestra un calendario interactivo con la disponibilidad de un espacio
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Disponibilidad';
$espacio_nombre = isset($args['espacio_nombre']) ? $args['espacio_nombre'] : 'Sala de Reuniones Premium';
$espacio_id = isset($args['espacio_id']) ? intval($args['espacio_id']) : 1;
$mes_actual = isset($args['mes_actual']) ? intval($args['mes_actual']) : intval(date('n'));
$anio_actual = isset($args['anio_actual']) ? intval($args['anio_actual']) : intval(date('Y'));
$reservas = isset($args['reservas']) ? $args['reservas'] : array();
$hora_inicio = isset($args['hora_inicio']) ? intval($args['hora_inicio']) : 8;
$hora_fin = isset($args['hora_fin']) ? intval($args['hora_fin']) : 20;
$intervalo_minutos = isset($args['intervalo_minutos']) ? intval($args['intervalo_minutos']) : 60;
$mostrar_leyenda = isset($args['mostrar_leyenda']) ? $args['mostrar_leyenda'] : true;

// Nombres de meses y días en español
$nombres_meses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);

$nombres_dias = array('Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb');
$nombres_dias_completos = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

// Calcular datos del calendario
$primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
$dias_en_mes = intval(date('t', $primer_dia_mes));
$dia_semana_inicio = intval(date('w', $primer_dia_mes));
$hoy = date('Y-m-d');

// Datos de demostración de reservas
if (empty($reservas)) {
    $reservas = array(
        date('Y-m-d', strtotime('+1 day')) => array(
            array('hora_inicio' => '09:00', 'hora_fin' => '11:00', 'titulo' => 'Reunión de Equipo', 'usuario' => 'Carlos M.'),
            array('hora_inicio' => '14:00', 'hora_fin' => '16:00', 'titulo' => 'Presentación Cliente', 'usuario' => 'Ana G.')
        ),
        date('Y-m-d', strtotime('+2 days')) => array(
            array('hora_inicio' => '10:00', 'hora_fin' => '12:00', 'titulo' => 'Workshop Diseño', 'usuario' => 'María L.')
        ),
        date('Y-m-d', strtotime('+3 days')) => array(
            array('hora_inicio' => '08:00', 'hora_fin' => '10:00', 'titulo' => 'Stand-up Daily', 'usuario' => 'Pedro R.'),
            array('hora_inicio' => '11:00', 'hora_fin' => '13:00', 'titulo' => 'Entrevistas', 'usuario' => 'HR Team'),
            array('hora_inicio' => '15:00', 'hora_fin' => '17:00', 'titulo' => 'Capacitación', 'usuario' => 'Luis K.')
        ),
        date('Y-m-d', strtotime('+5 days')) => array(
            array('hora_inicio' => '09:00', 'hora_fin' => '18:00', 'titulo' => 'Evento Corporativo', 'usuario' => 'Eventos Inc.')
        ),
        date('Y-m-d', strtotime('+7 days')) => array(
            array('hora_inicio' => '13:00', 'hora_fin' => '15:00', 'titulo' => 'Comité Directivo', 'usuario' => 'Dirección')
        )
    );
}

// Función para obtener estado del día
function obtener_estado_dia($fecha, $reservas_lista) {
    if (!isset($reservas_lista[$fecha])) {
        return 'disponible';
    }

    $total_horas_reservadas = 0;
    foreach ($reservas_lista[$fecha] as $reserva) {
        $inicio = strtotime($reserva['hora_inicio']);
        $fin = strtotime($reserva['hora_fin']);
        $total_horas_reservadas += ($fin - $inicio) / 3600;
    }

    if ($total_horas_reservadas >= 8) {
        return 'completo';
    } elseif ($total_horas_reservadas >= 4) {
        return 'parcial';
    }
    return 'disponible';
}

// Generar slots de tiempo
$slots_tiempo = array();
for ($hora = $hora_inicio; $hora < $hora_fin; $hora++) {
    $slots_tiempo[] = sprintf('%02d:00', $hora);
}
?>

<section class="flavor-calendario" id="calendario">
    <div class="flavor-calendario__container">
        <header class="flavor-calendario__header">
            <div class="flavor-calendario__info">
                <h2 class="flavor-calendario__titulo"><?php echo esc_html($titulo_seccion); ?></h2>
                <p class="flavor-calendario__espacio"><?php echo esc_html($espacio_nombre); ?></p>
            </div>

            <div class="flavor-calendario__navegacion">
                <button class="flavor-calendario__nav-btn" data-accion="anterior" aria-label="Mes anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <span class="flavor-calendario__mes-actual">
                    <?php echo esc_html($nombres_meses[$mes_actual] . ' ' . $anio_actual); ?>
                </span>
                <button class="flavor-calendario__nav-btn" data-accion="siguiente" aria-label="Mes siguiente">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </header>

        <div class="flavor-calendario__contenido">
            <!-- Vista Mes -->
            <div class="flavor-calendario__mes">
                <div class="flavor-calendario__dias-semana">
                    <?php foreach ($nombres_dias as $dia_nombre) : ?>
                    <div class="flavor-calendario__dia-semana"><?php echo esc_html($dia_nombre); ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-calendario__dias">
                    <?php
                    // Espacios vacíos antes del primer día
                    for ($espacio_vacio = 0; $espacio_vacio < $dia_semana_inicio; $espacio_vacio++) {
                        echo '<div class="flavor-calendario__dia flavor-calendario__dia--vacio"></div>';
                    }

                    // Días del mes
                    for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
                        $fecha_dia = sprintf('%04d-%02d-%02d', $anio_actual, $mes_actual, $dia);
                        $es_hoy = ($fecha_dia === $hoy);
                        $es_pasado = (strtotime($fecha_dia) < strtotime($hoy));
                        $estado_disponibilidad = obtener_estado_dia($fecha_dia, $reservas);
                        $dia_semana_numero = intval(date('w', strtotime($fecha_dia)));
                        $es_fin_semana = ($dia_semana_numero === 0 || $dia_semana_numero === 6);

                        $clases_dia = 'flavor-calendario__dia';
                        if ($es_hoy) $clases_dia .= ' flavor-calendario__dia--hoy';
                        if ($es_pasado) $clases_dia .= ' flavor-calendario__dia--pasado';
                        if ($es_fin_semana) $clases_dia .= ' flavor-calendario__dia--finsemana';
                        $clases_dia .= ' flavor-calendario__dia--' . $estado_disponibilidad;

                        $reservas_dia = isset($reservas[$fecha_dia]) ? count($reservas[$fecha_dia]) : 0;
                        ?>
                        <div class="<?php echo esc_attr($clases_dia); ?>" data-fecha="<?php echo esc_attr($fecha_dia); ?>">
                            <span class="flavor-calendario__dia-numero"><?php echo esc_html($dia); ?></span>
                            <?php if ($reservas_dia > 0 && !$es_pasado) : ?>
                            <span class="flavor-calendario__dia-reservas"><?php echo esc_html($reservas_dia); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Panel de detalles del día seleccionado -->
            <div class="flavor-calendario__detalle" id="detalleCalendario">
                <div class="flavor-calendario__detalle-header">
                    <h3 class="flavor-calendario__detalle-fecha">Selecciona un día</h3>
                    <button class="flavor-calendario__detalle-cerrar" aria-label="Cerrar detalle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="flavor-calendario__horarios">
                    <?php foreach ($slots_tiempo as $slot) : ?>
                    <div class="flavor-calendario__slot" data-hora="<?php echo esc_attr($slot); ?>">
                        <span class="flavor-calendario__slot-hora"><?php echo esc_html($slot); ?></span>
                        <div class="flavor-calendario__slot-estado flavor-calendario__slot-estado--libre">
                            <span>Disponible</span>
                            <button class="flavor-calendario__slot-reservar">Reservar</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-calendario__detalle-vacio">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p>Selecciona un día para ver la disponibilidad horaria</p>
                </div>
            </div>
        </div>

        <?php if ($mostrar_leyenda) : ?>
        <div class="flavor-calendario__leyenda">
            <div class="flavor-calendario__leyenda-item">
                <span class="flavor-calendario__leyenda-color flavor-calendario__leyenda-color--disponible"></span>
                <span>Disponible</span>
            </div>
            <div class="flavor-calendario__leyenda-item">
                <span class="flavor-calendario__leyenda-color flavor-calendario__leyenda-color--parcial"></span>
                <span>Parcialmente ocupado</span>
            </div>
            <div class="flavor-calendario__leyenda-item">
                <span class="flavor-calendario__leyenda-color flavor-calendario__leyenda-color--completo"></span>
                <span>Completo</span>
            </div>
            <div class="flavor-calendario__leyenda-item">
                <span class="flavor-calendario__leyenda-color flavor-calendario__leyenda-color--hoy"></span>
                <span>Hoy</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-calendario {
    padding: 80px 0;
    background: #ffffff;
}

.flavor-calendario__container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

.flavor-calendario__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.flavor-calendario__titulo {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
}

.flavor-calendario__espacio {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
}

.flavor-calendario__navegacion {
    display: flex;
    align-items: center;
    gap: 16px;
}

.flavor-calendario__nav-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: #475569;
    transition: all 0.2s ease;
}

.flavor-calendario__nav-btn:hover {
    background: #667eea;
    color: #ffffff;
}

.flavor-calendario__mes-actual {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    min-width: 180px;
    text-align: center;
}

.flavor-calendario__contenido {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    background: #f8fafc;
    border-radius: 16px;
    padding: 24px;
}

.flavor-calendario__dias-semana {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-bottom: 8px;
}

.flavor-calendario__dia-semana {
    padding: 12px 8px;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
}

.flavor-calendario__dias {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.flavor-calendario__dia {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    background: #ffffff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    border: 2px solid transparent;
}

.flavor-calendario__dia:hover:not(.flavor-calendario__dia--vacio):not(.flavor-calendario__dia--pasado) {
    border-color: #667eea;
    transform: scale(1.05);
}

.flavor-calendario__dia--vacio {
    background: transparent;
    cursor: default;
}

.flavor-calendario__dia--pasado {
    opacity: 0.4;
    cursor: not-allowed;
}

.flavor-calendario__dia--finsemana {
    background: #f1f5f9;
}

.flavor-calendario__dia--hoy {
    background: #667eea;
    color: #ffffff;
}

.flavor-calendario__dia--hoy .flavor-calendario__dia-numero {
    color: #ffffff;
}

.flavor-calendario__dia--disponible {
    border-left: 3px solid #10b981;
}

.flavor-calendario__dia--parcial {
    border-left: 3px solid #f59e0b;
}

.flavor-calendario__dia--completo {
    border-left: 3px solid #ef4444;
}

.flavor-calendario__dia-numero {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #1e293b;
}

.flavor-calendario__dia-reservas {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 18px;
    height: 18px;
    background: #667eea;
    color: #ffffff;
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-calendario__detalle {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
}

.flavor-calendario__detalle-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-calendario__detalle-fecha {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.flavor-calendario__detalle-cerrar {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    display: none;
}

.flavor-calendario__horarios {
    display: none;
    flex-direction: column;
    gap: 8px;
}

.flavor-calendario__horarios--visible {
    display: flex;
}

.flavor-calendario__slot {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.flavor-calendario__slot:hover {
    background: #f1f5f9;
}

.flavor-calendario__slot-hora {
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    min-width: 50px;
}

.flavor-calendario__slot-estado {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.flavor-calendario__slot-estado--libre {
    color: #10b981;
}

.flavor-calendario__slot-estado--ocupado {
    color: #ef4444;
}

.flavor-calendario__slot-reservar {
    padding: 6px 14px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #667eea;
    background: #eef2ff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-calendario__slot-reservar:hover {
    background: #667eea;
    color: #ffffff;
}

.flavor-calendario__detalle-vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #94a3b8;
}

.flavor-calendario__detalle-vacio p {
    margin: 16px 0 0;
    font-size: 0.875rem;
}

.flavor-calendario__detalle-vacio--oculto {
    display: none;
}

.flavor-calendario__leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    justify-content: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}

.flavor-calendario__leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: #64748b;
}

.flavor-calendario__leyenda-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.flavor-calendario__leyenda-color--disponible {
    background: #10b981;
}

.flavor-calendario__leyenda-color--parcial {
    background: #f59e0b;
}

.flavor-calendario__leyenda-color--completo {
    background: #ef4444;
}

.flavor-calendario__leyenda-color--hoy {
    background: #667eea;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-calendario__contenido {
        grid-template-columns: 1fr;
    }

    .flavor-calendario__detalle {
        max-height: none;
    }

    .flavor-calendario__detalle-cerrar {
        display: block;
    }
}

@media (max-width: 576px) {
    .flavor-calendario {
        padding: 60px 0;
    }

    .flavor-calendario__header {
        flex-direction: column;
        text-align: center;
    }

    .flavor-calendario__titulo {
        font-size: 1.5rem;
    }

    .flavor-calendario__contenido {
        padding: 16px;
    }

    .flavor-calendario__dia-semana {
        padding: 8px 4px;
        font-size: 0.75rem;
    }

    .flavor-calendario__dia {
        border-radius: 4px;
    }

    .flavor-calendario__dia-numero {
        font-size: 0.8125rem;
    }

    .flavor-calendario__leyenda {
        gap: 16px;
    }

    .flavor-calendario__leyenda-item {
        font-size: 0.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const diasCalendario = document.querySelectorAll('.flavor-calendario__dia:not(.flavor-calendario__dia--vacio):not(.flavor-calendario__dia--pasado)');
    const detallePanel = document.getElementById('detalleCalendario');
    const detalleFecha = detallePanel.querySelector('.flavor-calendario__detalle-fecha');
    const horariosContenedor = detallePanel.querySelector('.flavor-calendario__horarios');
    const detalleVacio = detallePanel.querySelector('.flavor-calendario__detalle-vacio');
    const cerrarBtn = detallePanel.querySelector('.flavor-calendario__detalle-cerrar');

    const nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const nombresDias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    diasCalendario.forEach(function(diaElemento) {
        diaElemento.addEventListener('click', function() {
            const fechaSeleccionada = this.getAttribute('data-fecha');
            const fechaObj = new Date(fechaSeleccionada + 'T12:00:00');

            // Actualizar selección visual
            diasCalendario.forEach(function(dia) {
                dia.classList.remove('flavor-calendario__dia--seleccionado');
            });
            this.classList.add('flavor-calendario__dia--seleccionado');

            // Formatear fecha para mostrar
            const diaSemana = nombresDias[fechaObj.getDay()];
            const diaMes = fechaObj.getDate();
            const mes = nombresMeses[fechaObj.getMonth()];
            detalleFecha.textContent = diaSemana + ', ' + diaMes + ' de ' + mes;

            // Mostrar horarios
            horariosContenedor.classList.add('flavor-calendario__horarios--visible');
            detalleVacio.classList.add('flavor-calendario__detalle-vacio--oculto');
        });
    });

    if (cerrarBtn) {
        cerrarBtn.addEventListener('click', function() {
            horariosContenedor.classList.remove('flavor-calendario__horarios--visible');
            detalleVacio.classList.remove('flavor-calendario__detalle-vacio--oculto');
            detalleFecha.textContent = 'Selecciona un día';
            diasCalendario.forEach(function(dia) {
                dia.classList.remove('flavor-calendario__dia--seleccionado');
            });
        });
    }
});
</script>
