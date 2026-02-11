<?php
/**
 * Template: Calendario de Recogidas de Residuos
 * Calendario de recogidas de residuos (orgánico, envases, papel, vidrio) por zonas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? esc_html($args['titulo']) : 'Calendario de Recogida de Residuos';
$zona_actual = isset($args['zona']) ? esc_html($args['zona']) : 'Centro';
$mes_actual = isset($args['mes']) ? absint($args['mes']) : (int) date('n');
$anio_actual = isset($args['anio']) ? absint($args['anio']) : (int) date('Y');
$mostrar_selector_zona = isset($args['mostrar_selector_zona']) ? (bool) $args['mostrar_selector_zona'] : true;
$clase_adicional = isset($args['clase']) ? esc_attr($args['clase']) : '';

// Nombres de los meses en español
$nombres_meses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);

// Nombres de los días de la semana
$dias_semana = array('Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom');

// Tipos de residuos con sus colores y horarios
$tipos_residuos = array(
    'organico' => array(
        'nombre' => 'Orgánico',
        'icono' => '🟤',
        'color' => '#795548',
        'color_claro' => '#d7ccc8',
        'horario' => '20:00 - 22:00'
    ),
    'envases' => array(
        'nombre' => 'Envases',
        'icono' => '🟡',
        'color' => '#FFC107',
        'color_claro' => '#fff8e1',
        'horario' => '20:00 - 22:00'
    ),
    'papel' => array(
        'nombre' => 'Papel y Cartón',
        'icono' => '🔵',
        'color' => '#2196F3',
        'color_claro' => '#e3f2fd',
        'horario' => '20:00 - 22:00'
    ),
    'vidrio' => array(
        'nombre' => 'Vidrio',
        'icono' => '🟢',
        'color' => '#4CAF50',
        'color_claro' => '#e8f5e9',
        'horario' => '08:00 - 20:00'
    ),
    'resto' => array(
        'nombre' => 'Resto',
        'icono' => '⚫',
        'color' => '#607D8B',
        'color_claro' => '#eceff1',
        'horario' => '20:00 - 22:00'
    )
);

// Zonas disponibles
$zonas_disponibles = array(
    'Centro' => 'Centro Histórico',
    'Norte' => 'Zona Norte',
    'Sur' => 'Zona Sur',
    'Este' => 'Zona Este',
    'Oeste' => 'Zona Oeste',
    'Industrial' => 'Polígono Industrial'
);

// Calendario de recogidas por zona (demo)
// Formato: día de la semana (1=Lunes, 7=Domingo) => tipos de residuos
$calendarios_por_zona = array(
    'Centro' => array(
        1 => array('organico', 'resto'),           // Lunes
        2 => array('envases'),                      // Martes
        3 => array('organico', 'papel'),           // Miércoles
        4 => array('resto'),                        // Jueves
        5 => array('organico', 'vidrio'),          // Viernes
        6 => array('envases'),                      // Sábado
        7 => array()                                // Domingo
    ),
    'Norte' => array(
        1 => array('envases'),
        2 => array('organico', 'resto'),
        3 => array('papel'),
        4 => array('organico', 'envases'),
        5 => array('resto', 'vidrio'),
        6 => array('organico'),
        7 => array()
    ),
    'Sur' => array(
        1 => array('organico', 'papel'),
        2 => array('resto'),
        3 => array('envases', 'organico'),
        4 => array('vidrio'),
        5 => array('organico', 'resto'),
        6 => array('papel'),
        7 => array()
    ),
    'Este' => array(
        1 => array('resto'),
        2 => array('organico', 'envases'),
        3 => array('vidrio'),
        4 => array('organico', 'papel'),
        5 => array('envases'),
        6 => array('organico', 'resto'),
        7 => array()
    ),
    'Oeste' => array(
        1 => array('organico', 'vidrio'),
        2 => array('papel'),
        3 => array('resto', 'organico'),
        4 => array('envases'),
        5 => array('organico'),
        6 => array('resto', 'papel'),
        7 => array()
    ),
    'Industrial' => array(
        1 => array('envases', 'papel'),
        2 => array('resto'),
        3 => array('envases'),
        4 => array('papel', 'vidrio'),
        5 => array('resto', 'envases'),
        6 => array(),
        7 => array()
    )
);

// Obtener el calendario de la zona actual
$calendario_zona = isset($calendarios_por_zona[$zona_actual]) ? $calendarios_por_zona[$zona_actual] : $calendarios_por_zona['Centro'];

// Calcular datos del calendario
$primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
$dias_en_mes = (int) date('t', $primer_dia_mes);
$dia_semana_inicio = (int) date('N', $primer_dia_mes); // 1=Lunes, 7=Domingo
$dia_hoy = (int) date('j');
$mes_hoy = (int) date('n');
$anio_hoy = (int) date('Y');

// Generar estructura del calendario
$dias_calendario = array();
$contador_dia = 1;
$semanas_totales = ceil(($dias_en_mes + $dia_semana_inicio - 1) / 7);

for ($semana = 0; $semana < $semanas_totales; $semana++) {
    $dias_calendario[$semana] = array();
    for ($dia_semana = 1; $dia_semana <= 7; $dia_semana++) {
        if (($semana === 0 && $dia_semana < $dia_semana_inicio) || $contador_dia > $dias_en_mes) {
            $dias_calendario[$semana][$dia_semana] = null;
        } else {
            $dias_calendario[$semana][$dia_semana] = $contador_dia;
            $contador_dia++;
        }
    }
}
?>

<div class="flavor-calendario-reciclaje <?php echo $clase_adicional; ?>">

    <!-- Encabezado -->
    <div class="flavor-calendario-header">
        <h2 class="flavor-calendario-titulo"><?php echo $titulo_seccion; ?></h2>
        <p class="flavor-calendario-subtitulo">Consulta los días de recogida de cada tipo de residuo en tu zona</p>
    </div>

    <?php if ($mostrar_selector_zona) : ?>
    <!-- Selector de zona -->
    <div class="flavor-zona-selector">
        <label for="flavor-selector-zona" class="flavor-zona-label">
            <span class="flavor-zona-icono">📍</span>
            Selecciona tu zona:
        </label>
        <select id="flavor-selector-zona" class="flavor-zona-select">
            <?php foreach ($zonas_disponibles as $clave_zona => $nombre_zona) : ?>
            <option value="<?php echo esc_attr($clave_zona); ?>" <?php selected($zona_actual, $clave_zona); ?>>
                <?php echo esc_html($nombre_zona); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Información de zona actual -->
    <div class="flavor-zona-info">
        <div class="flavor-zona-actual">
            <span class="flavor-zona-badge">
                <span>📍</span> Zona: <strong><?php echo esc_html($zonas_disponibles[$zona_actual]); ?></strong>
            </span>
        </div>
    </div>

    <!-- Navegación del calendario -->
    <div class="flavor-calendario-nav">
        <button type="button" class="flavor-nav-btn flavor-nav-prev" data-mes="<?php echo $mes_actual > 1 ? $mes_actual - 1 : 12; ?>" data-anio="<?php echo $mes_actual > 1 ? $anio_actual : $anio_actual - 1; ?>">
            <span>◀</span> Anterior
        </button>
        <h3 class="flavor-calendario-mes-anio">
            <?php echo esc_html($nombres_meses[$mes_actual] . ' ' . $anio_actual); ?>
        </h3>
        <button type="button" class="flavor-nav-btn flavor-nav-next" data-mes="<?php echo $mes_actual < 12 ? $mes_actual + 1 : 1; ?>" data-anio="<?php echo $mes_actual < 12 ? $anio_actual : $anio_actual + 1; ?>">
            Siguiente <span>▶</span>
        </button>
    </div>

    <!-- Calendario -->
    <div class="flavor-calendario-container">
        <table class="flavor-calendario-tabla">
            <thead>
                <tr>
                    <?php foreach ($dias_semana as $dia_nombre) : ?>
                    <th class="flavor-calendario-dia-header"><?php echo esc_html($dia_nombre); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dias_calendario as $semana) : ?>
                <tr>
                    <?php foreach ($semana as $dia_semana => $numero_dia) : ?>
                    <?php
                    $es_hoy = ($numero_dia === $dia_hoy && $mes_actual === $mes_hoy && $anio_actual === $anio_hoy);
                    $residuos_del_dia = !is_null($numero_dia) ? $calendario_zona[$dia_semana] : array();
                    $clase_celda = 'flavor-calendario-celda';
                    if (is_null($numero_dia)) $clase_celda .= ' flavor-celda-vacia';
                    if ($es_hoy) $clase_celda .= ' flavor-celda-hoy';
                    if ($dia_semana === 7) $clase_celda .= ' flavor-celda-domingo';
                    ?>
                    <td class="<?php echo esc_attr($clase_celda); ?>">
                        <?php if (!is_null($numero_dia)) : ?>
                        <div class="flavor-celda-contenido">
                            <span class="flavor-celda-numero <?php echo $es_hoy ? 'flavor-numero-hoy' : ''; ?>">
                                <?php echo esc_html($numero_dia); ?>
                            </span>
                            <?php if (!empty($residuos_del_dia)) : ?>
                            <div class="flavor-celda-residuos">
                                <?php foreach ($residuos_del_dia as $tipo_residuo) : ?>
                                    <?php if (isset($tipos_residuos[$tipo_residuo])) : ?>
                                    <span class="flavor-residuo-indicador"
                                          style="background-color: <?php echo esc_attr($tipos_residuos[$tipo_residuo]['color']); ?>;"
                                          title="<?php echo esc_attr($tipos_residuos[$tipo_residuo]['nombre'] . ' - ' . $tipos_residuos[$tipo_residuo]['horario']); ?>">
                                        <?php echo $tipos_residuos[$tipo_residuo]['icono']; ?>
                                    </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Leyenda de tipos de residuos -->
    <div class="flavor-calendario-leyenda">
        <h4 class="flavor-leyenda-titulo">Tipos de residuos y horarios</h4>
        <div class="flavor-leyenda-grid">
            <?php foreach ($tipos_residuos as $tipo_clave => $tipo_datos) : ?>
            <div class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: <?php echo esc_attr($tipo_datos['color']); ?>;">
                    <?php echo $tipo_datos['icono']; ?>
                </span>
                <div class="flavor-leyenda-info">
                    <strong><?php echo esc_html($tipo_datos['nombre']); ?></strong>
                    <small>
                        <span class="flavor-horario-icono">🕐</span>
                        <?php echo esc_html($tipo_datos['horario']); ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Resumen semanal -->
    <div class="flavor-resumen-semanal">
        <h4 class="flavor-resumen-titulo">Resumen semanal - <?php echo esc_html($zonas_disponibles[$zona_actual]); ?></h4>
        <div class="flavor-resumen-grid">
            <?php
            $dias_semana_completos = array(
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
                5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            );
            foreach ($calendario_zona as $dia_num => $residuos) : ?>
            <div class="flavor-resumen-dia <?php echo empty($residuos) ? 'flavor-dia-sin-recogida' : ''; ?>">
                <span class="flavor-resumen-dia-nombre"><?php echo esc_html($dias_semana_completos[$dia_num]); ?></span>
                <div class="flavor-resumen-residuos">
                    <?php if (empty($residuos)) : ?>
                    <span class="flavor-sin-recogida">Sin recogida</span>
                    <?php else : ?>
                        <?php foreach ($residuos as $residuo) : ?>
                            <?php if (isset($tipos_residuos[$residuo])) : ?>
                            <span class="flavor-resumen-badge" style="background-color: <?php echo esc_attr($tipos_residuos[$residuo]['color_claro']); ?>; color: <?php echo esc_attr($tipos_residuos[$residuo]['color']); ?>; border-color: <?php echo esc_attr($tipos_residuos[$residuo]['color']); ?>;">
                                <?php echo $tipos_residuos[$residuo]['icono']; ?> <?php echo esc_html($tipos_residuos[$residuo]['nombre']); ?>
                            </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Avisos importantes -->
    <div class="flavor-avisos">
        <div class="flavor-aviso flavor-aviso-info">
            <span class="flavor-aviso-icono">ℹ️</span>
            <div class="flavor-aviso-contenido">
                <strong>Recuerda:</strong> Deposita las bolsas en los contenedores dentro del horario indicado para evitar malos olores y facilitar la recogida.
            </div>
        </div>
        <div class="flavor-aviso flavor-aviso-warning">
            <span class="flavor-aviso-icono">⚠️</span>
            <div class="flavor-aviso-contenido">
                <strong>Días festivos:</strong> Los días festivos puede haber cambios en el calendario de recogidas. Consulta los avisos municipales.
            </div>
        </div>
    </div>

</div>

<style>
/* Estilos base para calendario de reciclaje */
.flavor-calendario-reciclaje {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-calendario-header {
    text-align: center;
    margin-bottom: 25px;
}

.flavor-calendario-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0 0 10px 0;
}

.flavor-calendario-subtitulo {
    color: #666;
    font-size: 1.1rem;
    margin: 0;
}

/* Selector de zona */
.flavor-zona-selector {
    background: #f5f5f5;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.flavor-zona-label {
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-zona-icono {
    font-size: 1.2rem;
}

.flavor-zona-select {
    padding: 10px 20px;
    font-size: 1rem;
    border: 2px solid #4CAF50;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    min-width: 200px;
}

.flavor-zona-select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
}

/* Info de zona actual */
.flavor-zona-info {
    margin-bottom: 20px;
}

.flavor-zona-actual {
    display: flex;
    justify-content: center;
}

.flavor-zona-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 1rem;
}

/* Navegación del calendario */
.flavor-calendario-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #fafafa;
    border-radius: 12px;
}

.flavor-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.flavor-nav-btn:hover {
    border-color: #4CAF50;
    background: #e8f5e9;
}

.flavor-calendario-mes-anio {
    font-size: 1.4rem;
    color: #333;
    margin: 0;
}

/* Tabla del calendario */
.flavor-calendario-container {
    margin-bottom: 25px;
    overflow-x: auto;
}

.flavor-calendario-tabla {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.flavor-calendario-dia-header {
    background: #4CAF50;
    color: white;
    padding: 15px 10px;
    font-weight: 600;
    text-align: center;
    font-size: 0.9rem;
}

.flavor-calendario-celda {
    border: 1px solid #e0e0e0;
    padding: 8px;
    vertical-align: top;
    height: 90px;
    min-width: 100px;
}

.flavor-celda-vacia {
    background: #fafafa;
}

.flavor-celda-hoy {
    background: #fff8e1;
    border: 2px solid #FFC107;
}

.flavor-celda-domingo {
    background: #ffebee;
}

.flavor-celda-contenido {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.flavor-celda-numero {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.flavor-numero-hoy {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #FFC107;
    color: #333;
    border-radius: 50%;
}

.flavor-celda-residuos {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.flavor-residuo-indicador {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: help;
}

/* Leyenda */
.flavor-calendario-leyenda {
    background: #fafafa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e0e0e0;
}

.flavor-leyenda-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 15px 0;
}

.flavor-leyenda-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-leyenda-color {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.flavor-leyenda-info {
    display: flex;
    flex-direction: column;
}

.flavor-leyenda-info strong {
    color: #333;
    font-size: 0.95rem;
}

.flavor-leyenda-info small {
    color: #666;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Resumen semanal */
.flavor-resumen-semanal {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.flavor-resumen-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.flavor-resumen-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.flavor-resumen-dia {
    text-align: center;
    padding: 15px 10px;
    background: #f5f5f5;
    border-radius: 8px;
}

.flavor-dia-sin-recogida {
    opacity: 0.6;
}

.flavor-resumen-dia-nombre {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

.flavor-resumen-residuos {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.flavor-resumen-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid;
}

.flavor-sin-recogida {
    color: #999;
    font-size: 0.8rem;
    font-style: italic;
}

/* Avisos */
.flavor-avisos {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.flavor-aviso {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 20px;
    border-radius: 10px;
    border-left: 4px solid;
}

.flavor-aviso-info {
    background: #e3f2fd;
    border-left-color: #2196F3;
}

.flavor-aviso-warning {
    background: #fff8e1;
    border-left-color: #FFC107;
}

.flavor-aviso-icono {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.flavor-aviso-contenido {
    font-size: 0.95rem;
    color: #333;
}

.flavor-aviso-contenido strong {
    display: block;
    margin-bottom: 4px;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-resumen-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-calendario-reciclaje {
        padding: 15px;
    }

    .flavor-calendario-titulo {
        font-size: 1.5rem;
    }

    .flavor-zona-selector {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-zona-select {
        width: 100%;
    }

    .flavor-calendario-nav {
        flex-direction: column;
        gap: 15px;
    }

    .flavor-nav-btn {
        width: 100%;
        justify-content: center;
    }

    .flavor-calendario-celda {
        padding: 5px;
        height: 70px;
        min-width: 40px;
    }

    .flavor-celda-numero {
        font-size: 0.85rem;
    }

    .flavor-residuo-indicador {
        width: 22px;
        height: 22px;
        font-size: 0.7rem;
    }

    .flavor-resumen-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-leyenda-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .flavor-calendario-dia-header {
        padding: 10px 5px;
        font-size: 0.75rem;
    }

    .flavor-resumen-grid {
        grid-template-columns: 1fr;
    }

    .flavor-resumen-dia {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
    }

    .flavor-resumen-residuos {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad del selector de zona
    const selectorDeZona = document.getElementById('flavor-selector-zona');
    if (selectorDeZona) {
        selectorDeZona.addEventListener('change', function() {
            const zonaSeleccionada = this.value;
            // Aquí se puede implementar la actualización del calendario vía AJAX
            // o recargar la página con el nuevo parámetro de zona
            const urlActual = new URL(window.location.href);
            urlActual.searchParams.set('zona', zonaSeleccionada);
            window.location.href = urlActual.toString();
        });
    }

    // Funcionalidad de navegación entre meses
    const botonesDeNavegacion = document.querySelectorAll('.flavor-nav-btn');
    botonesDeNavegacion.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const mesDestino = this.dataset.mes;
            const anioDestino = this.dataset.anio;
            const urlActual = new URL(window.location.href);
            urlActual.searchParams.set('mes', mesDestino);
            urlActual.searchParams.set('anio', anioDestino);
            window.location.href = urlActual.toString();
        });
    });

    // Mostrar tooltips en dispositivos táctiles
    const indicadoresDeResiduo = document.querySelectorAll('.flavor-residuo-indicador');
    indicadoresDeResiduo.forEach(function(indicador) {
        indicador.addEventListener('click', function(evento) {
            evento.stopPropagation();
            const textoDelTooltip = this.getAttribute('title');
            if (textoDelTooltip) {
                alert(textoDelTooltip);
            }
        });
    });
});
</script>
