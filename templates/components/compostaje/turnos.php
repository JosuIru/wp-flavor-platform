<?php
/**
 * Template: Calendario de turnos de mantenimiento de composteras
 *
 * @package FlavorPlatform
 * @since 1.0.0
 *
 * Variables esperadas en $args:
 * - turnos: array con los turnos programados
 * - composteras: array con las composteras disponibles
 * - usuario_actual_id: ID del usuario actual
 * - mes_actual: número del mes actual (1-12)
 * - año_actual: año actual
 * - puede_apuntarse: si el usuario puede apuntarse a turnos
 */

if (!defined('ABSPATH')) exit;

// Datos del calendario
$mesActual = $args['mes_actual'] ?? (int)date('n');
$añoActual = $args['año_actual'] ?? (int)date('Y');
$usuarioActualId = $args['usuario_actual_id'] ?? get_current_user_id();
$puedeApuntarse = $args['puede_apuntarse'] ?? true;

// Nombres de meses en español
$nombresMeses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Composteras disponibles
$composteras = $args['composteras'] ?? [
    ['id' => 1, 'nombre' => 'Plaza Mayor', 'color' => '#4caf50'],
    ['id' => 2, 'nombre' => 'Parque Central', 'color' => '#2196f3'],
    ['id' => 3, 'nombre' => 'Huerto Comunitario', 'color' => '#ff9800'],
];

// Turnos de demostración
$turnosDemostracion = $args['turnos'] ?? [
    [
        'id' => 1,
        'fecha' => date('Y-m-d', strtotime('+2 days')),
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
        'compostera_id' => 1,
        'compostera_nombre' => 'Plaza Mayor',
        'tipo' => 'volteo',
        'voluntarios_necesarios' => 3,
        'voluntarios' => [
            ['id' => 101, 'nombre' => 'María García'],
            ['id' => 102, 'nombre' => 'Carlos López'],
        ],
        'descripcion' => 'Volteo semanal del material en compostaje',
    ],
    [
        'id' => 2,
        'fecha' => date('Y-m-d', strtotime('+5 days')),
        'hora_inicio' => '10:00',
        'hora_fin' => '12:00',
        'compostera_id' => 2,
        'compostera_nombre' => 'Parque Central',
        'tipo' => 'cribado',
        'voluntarios_necesarios' => 4,
        'voluntarios' => [
            ['id' => 103, 'nombre' => 'Ana Martínez'],
        ],
        'descripcion' => 'Cribado del compost maduro para distribución',
    ],
    [
        'id' => 3,
        'fecha' => date('Y-m-d', strtotime('+7 days')),
        'hora_inicio' => '09:30',
        'hora_fin' => '11:30',
        'compostera_id' => 3,
        'compostera_nombre' => 'Huerto Comunitario',
        'tipo' => 'riego',
        'voluntarios_necesarios' => 2,
        'voluntarios' => [
            ['id' => 107, 'nombre' => 'Elena Ruiz'],
            ['id' => 108, 'nombre' => 'David Moreno'],
        ],
        'descripcion' => 'Control de humedad y riego si es necesario',
    ],
    [
        'id' => 4,
        'fecha' => date('Y-m-d', strtotime('+10 days')),
        'hora_inicio' => '10:00',
        'hora_fin' => '13:00',
        'compostera_id' => 1,
        'compostera_nombre' => 'Plaza Mayor',
        'tipo' => 'mantenimiento',
        'voluntarios_necesarios' => 5,
        'voluntarios' => [],
        'descripcion' => 'Mantenimiento general de la estructura',
    ],
    [
        'id' => 5,
        'fecha' => date('Y-m-d', strtotime('+14 days')),
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
        'compostera_id' => 2,
        'compostera_nombre' => 'Parque Central',
        'tipo' => 'volteo',
        'voluntarios_necesarios' => 3,
        'voluntarios' => [
            ['id' => 101, 'nombre' => 'María García'],
        ],
        'descripcion' => 'Volteo semanal del material',
    ],
];

// Tipos de tareas con iconos y colores
$tiposTareas = [
    'volteo' => ['icono' => '🔄', 'nombre' => 'Volteo', 'color' => '#4caf50'],
    'cribado' => ['icono' => '🧹', 'nombre' => 'Cribado', 'color' => '#9c27b0'],
    'riego' => ['icono' => '💧', 'nombre' => 'Riego', 'color' => '#2196f3'],
    'mantenimiento' => ['icono' => '🔧', 'nombre' => 'Mantenimiento', 'color' => '#ff9800'],
    'formacion' => ['icono' => '📚', 'nombre' => 'Formación', 'color' => '#e91e63'],
];

// Generar datos del calendario
$primerDiaMes = mktime(0, 0, 0, $mesActual, 1, $añoActual);
$diasEnMes = date('t', $primerDiaMes);
$diaSemanaInicio = date('N', $primerDiaMes); // 1 = Lunes, 7 = Domingo

// Organizar turnos por fecha
$turnosPorFecha = [];
foreach ($turnosDemostracion as $turno) {
    $fecha = $turno['fecha'];
    if (!isset($turnosPorFecha[$fecha])) {
        $turnosPorFecha[$fecha] = [];
    }
    $turnosPorFecha[$fecha][] = $turno;
}

// Mis turnos
$misTurnos = array_filter($turnosDemostracion, function($turno) use ($usuarioActualId) {
    foreach ($turno['voluntarios'] as $voluntario) {
        if ($voluntario['id'] === $usuarioActualId) {
            return true;
        }
    }
    return false;
});
?>

<div class="flavor-turnos-compostaje">
    <!-- Cabecera -->
    <div class="flavor-turnos-header">
        <div class="flavor-turnos-titulo-container">
            <h2 class="flavor-turnos-titulo">
                <span class="flavor-turnos-icono">📅</span>
                Turnos de Mantenimiento
            </h2>
            <p class="flavor-turnos-subtitulo">
                Apúntate a los turnos de mantenimiento de las composteras comunitarias
            </p>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="flavor-turnos-stats">
        <div class="flavor-turno-stat">
            <span class="flavor-stat-icono-pequeno">📋</span>
            <span class="flavor-stat-info">
                <strong><?php echo count($turnosDemostracion); ?></strong> turnos programados
            </span>
        </div>
        <div class="flavor-turno-stat">
            <span class="flavor-stat-icono-pequeno">✅</span>
            <span class="flavor-stat-info">
                <strong><?php echo count($misTurnos); ?></strong> mis turnos
            </span>
        </div>
        <div class="flavor-turno-stat">
            <span class="flavor-stat-icono-pequeno">🙋</span>
            <span class="flavor-stat-info">
                <strong><?php
                    $plazasDisponibles = 0;
                    foreach ($turnosDemostracion as $turno) {
                        $plazasDisponibles += $turno['voluntarios_necesarios'] - count($turno['voluntarios']);
                    }
                    echo $plazasDisponibles;
                ?></strong> plazas disponibles
            </span>
        </div>
    </div>

    <!-- Navegación del calendario -->
    <div class="flavor-calendario-nav">
        <button class="flavor-nav-btn" data-accion="anterior" aria-label="Mes anterior">
            ← Anterior
        </button>
        <h3 class="flavor-calendario-mes">
            <?php echo esc_html($nombresMeses[$mesActual] . ' ' . $añoActual); ?>
        </h3>
        <button class="flavor-nav-btn" data-accion="siguiente" aria-label="Mes siguiente">
            Siguiente →
        </button>
    </div>

    <!-- Leyenda de composteras -->
    <div class="flavor-calendario-leyenda">
        <?php foreach ($composteras as $compostera) : ?>
            <div class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: <?php echo esc_attr($compostera['color']); ?>"></span>
                <span class="flavor-leyenda-nombre"><?php echo esc_html($compostera['nombre']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Calendario -->
    <div class="flavor-calendario">
        <div class="flavor-calendario-header">
            <div class="flavor-dia-semana">Lun</div>
            <div class="flavor-dia-semana">Mar</div>
            <div class="flavor-dia-semana">Mié</div>
            <div class="flavor-dia-semana">Jue</div>
            <div class="flavor-dia-semana">Vie</div>
            <div class="flavor-dia-semana flavor-fin-semana">Sáb</div>
            <div class="flavor-dia-semana flavor-fin-semana">Dom</div>
        </div>
        <div class="flavor-calendario-grid">
            <?php
            // Días vacíos al inicio
            for ($i = 1; $i < $diaSemanaInicio; $i++) {
                echo '<div class="flavor-dia flavor-dia-vacio"></div>';
            }

            // Días del mes
            $hoy = date('Y-m-d');
            for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                $fechaDia = sprintf('%04d-%02d-%02d', $añoActual, $mesActual, $dia);
                $esHoy = $fechaDia === $hoy;
                $esPasado = $fechaDia < $hoy;
                $turnosDelDia = $turnosPorFecha[$fechaDia] ?? [];
                $tieneTurnos = !empty($turnosDelDia);

                $claseDia = 'flavor-dia';
                if ($esHoy) $claseDia .= ' flavor-dia-hoy';
                if ($esPasado) $claseDia .= ' flavor-dia-pasado';
                if ($tieneTurnos) $claseDia .= ' flavor-dia-con-turno';

                echo '<div class="' . esc_attr($claseDia) . '" data-fecha="' . esc_attr($fechaDia) . '">';
                echo '<span class="flavor-dia-numero">' . $dia . '</span>';

                if ($tieneTurnos) {
                    echo '<div class="flavor-dia-turnos">';
                    foreach (array_slice($turnosDelDia, 0, 2) as $turno) {
                        $colorCompostera = '#4caf50';
                        foreach ($composteras as $comp) {
                            if ($comp['id'] === $turno['compostera_id']) {
                                $colorCompostera = $comp['color'];
                                break;
                            }
                        }
                        $tipoInfo = $tiposTareas[$turno['tipo']] ?? ['icono' => '📌', 'nombre' => 'Tarea'];
                        echo '<div class="flavor-turno-mini" style="border-left-color: ' . esc_attr($colorCompostera) . '">';
                        echo '<span class="flavor-turno-mini-icono">' . esc_html($tipoInfo['icono']) . '</span>';
                        echo '<span class="flavor-turno-mini-hora">' . esc_html($turno['hora_inicio']) . '</span>';
                        echo '</div>';
                    }
                    if (count($turnosDelDia) > 2) {
                        echo '<span class="flavor-turnos-mas">+' . (count($turnosDelDia) - 2) . ' más</span>';
                    }
                    echo '</div>';
                }

                echo '</div>';
            }

            // Días vacíos al final
            $diasTotales = $diaSemanaInicio - 1 + $diasEnMes;
            $diasRestantes = 7 - ($diasTotales % 7);
            if ($diasRestantes < 7) {
                for ($i = 0; $i < $diasRestantes; $i++) {
                    echo '<div class="flavor-dia flavor-dia-vacio"></div>';
                }
            }
            ?>
        </div>
    </div>

    <!-- Lista de próximos turnos -->
    <div class="flavor-proximos-turnos">
        <h3 class="flavor-seccion-titulo">
            <span>📋</span> Próximos turnos
        </h3>
        <div class="flavor-turnos-lista">
            <?php foreach ($turnosDemostracion as $turno) :
                $tipoInfo = $tiposTareas[$turno['tipo']] ?? ['icono' => '📌', 'nombre' => 'Tarea', 'color' => '#666'];
                $plazasOcupadas = count($turno['voluntarios']);
                $plazasDisponibles = $turno['voluntarios_necesarios'] - $plazasOcupadas;
                $estaApuntado = false;
                foreach ($turno['voluntarios'] as $vol) {
                    if ($vol['id'] === $usuarioActualId) {
                        $estaApuntado = true;
                        break;
                    }
                }
                $colorCompostera = '#4caf50';
                foreach ($composteras as $comp) {
                    if ($comp['id'] === $turno['compostera_id']) {
                        $colorCompostera = $comp['color'];
                        break;
                    }
                }
            ?>
                <div class="flavor-turno-card <?php echo $estaApuntado ? 'flavor-turno-apuntado' : ''; ?>"
                     style="border-left-color: <?php echo esc_attr($colorCompostera); ?>">
                    <div class="flavor-turno-fecha">
                        <span class="flavor-fecha-dia"><?php echo esc_html(date('d', strtotime($turno['fecha']))); ?></span>
                        <span class="flavor-fecha-mes"><?php echo esc_html(date_i18n('M', strtotime($turno['fecha']))); ?></span>
                    </div>
                    <div class="flavor-turno-info">
                        <div class="flavor-turno-header-card">
                            <span class="flavor-turno-tipo" style="background-color: <?php echo esc_attr($tipoInfo['color']); ?>">
                                <?php echo esc_html($tipoInfo['icono'] . ' ' . $tipoInfo['nombre']); ?>
                            </span>
                            <?php if ($estaApuntado) : ?>
                                <span class="flavor-badge-apuntado">✓ Apuntado</span>
                            <?php endif; ?>
                        </div>
                        <h4 class="flavor-turno-titulo"><?php echo esc_html($turno['compostera_nombre']); ?></h4>
                        <p class="flavor-turno-descripcion"><?php echo esc_html($turno['descripcion']); ?></p>
                        <div class="flavor-turno-detalles">
                            <span class="flavor-detalle">
                                🕐 <?php echo esc_html($turno['hora_inicio'] . ' - ' . $turno['hora_fin']); ?>
                            </span>
                            <span class="flavor-detalle">
                                👥 <?php echo esc_html($plazasOcupadas . '/' . $turno['voluntarios_necesarios']); ?> voluntarios
                            </span>
                        </div>
                        <?php if (!empty($turno['voluntarios'])) : ?>
                            <div class="flavor-turno-voluntarios">
                                <span class="flavor-voluntarios-label">Apuntados:</span>
                                <?php foreach ($turno['voluntarios'] as $voluntario) : ?>
                                    <span class="flavor-voluntario-chip"><?php echo esc_html($voluntario['nombre']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-turno-acciones">
                        <?php if ($estaApuntado) : ?>
                            <button class="flavor-btn-turno flavor-btn-cancelar" data-turno-id="<?php echo esc_attr($turno['id']); ?>">
                                Cancelar inscripción
                            </button>
                        <?php elseif ($plazasDisponibles > 0 && $puedeApuntarse) : ?>
                            <button class="flavor-btn-turno flavor-btn-apuntarse" data-turno-id="<?php echo esc_attr($turno['id']); ?>">
                                Apuntarme
                            </button>
                            <span class="flavor-plazas-info"><?php echo esc_html($plazasDisponibles); ?> plazas libres</span>
                        <?php else : ?>
                            <span class="flavor-turno-completo">Turno completo</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Leyenda de tipos de tareas -->
    <div class="flavor-tipos-tareas">
        <h4>Tipos de tareas:</h4>
        <div class="flavor-tipos-grid">
            <?php foreach ($tiposTareas as $clave => $tipo) : ?>
                <div class="flavor-tipo-item">
                    <span class="flavor-tipo-icono"><?php echo esc_html($tipo['icono']); ?></span>
                    <span class="flavor-tipo-nombre"><?php echo esc_html($tipo['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="flavor-turnos-info">
        <h4>ℹ️ Información importante</h4>
        <ul>
            <li>Cada turno tiene una duración aproximada de 2 horas</li>
            <li>Se proporcionan herramientas y guantes de trabajo</li>
            <li>Avisa con 24h de antelación si no puedes asistir</li>
            <li>Recibirás un recordatorio por email el día anterior</li>
        </ul>
    </div>
</div>

<style>
.flavor-turnos-compostaje {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* Header */
.flavor-turnos-header {
    margin-bottom: 1.5rem;
}

.flavor-turnos-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    color: #1b5e20;
    margin: 0;
}

.flavor-turnos-icono {
    font-size: 1.8rem;
}

.flavor-turnos-subtitulo {
    color: #666;
    margin: 0.25rem 0 0;
    font-size: 0.95rem;
}

/* Estadísticas */
.flavor-turnos-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.flavor-turno-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f5f5f5;
    border-radius: 10px;
}

.flavor-stat-icono-pequeno {
    font-size: 1.25rem;
}

.flavor-stat-info strong {
    color: #2e7d32;
}

/* Navegación calendario */
.flavor-calendario-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.flavor-nav-btn {
    padding: 0.5rem 1rem;
    border: 2px solid #e0e0e0;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    color: #555;
    transition: all 0.2s;
}

.flavor-nav-btn:hover {
    border-color: #4caf50;
    color: #4caf50;
}

.flavor-calendario-mes {
    font-size: 1.25rem;
    color: #333;
    margin: 0;
}

/* Leyenda */
.flavor-calendario-leyenda {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.flavor-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

/* Calendario */
.flavor-calendario {
    margin-bottom: 2rem;
}

.flavor-calendario-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-bottom: 4px;
}

.flavor-dia-semana {
    text-align: center;
    padding: 0.75rem 0.25rem;
    font-weight: 600;
    font-size: 0.85rem;
    color: #555;
    background: #f5f5f5;
    border-radius: 6px;
}

.flavor-fin-semana {
    color: #e57373;
}

.flavor-calendario-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.flavor-dia {
    min-height: 90px;
    padding: 0.5rem;
    background: #fafafa;
    border-radius: 8px;
    position: relative;
    transition: all 0.2s;
}

.flavor-dia:hover:not(.flavor-dia-vacio) {
    background: #f0f7f0;
}

.flavor-dia-vacio {
    background: transparent;
}

.flavor-dia-numero {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

.flavor-dia-hoy {
    background: #e8f5e9;
    border: 2px solid #4caf50;
}

.flavor-dia-hoy .flavor-dia-numero {
    color: #2e7d32;
}

.flavor-dia-pasado {
    opacity: 0.5;
}

.flavor-dia-con-turno {
    cursor: pointer;
}

.flavor-dia-turnos {
    margin-top: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-turno-mini {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.35rem;
    background: #fff;
    border-radius: 4px;
    border-left: 3px solid;
    font-size: 0.75rem;
}

.flavor-turno-mini-icono {
    font-size: 0.8rem;
}

.flavor-turno-mini-hora {
    color: #666;
}

.flavor-turnos-mas {
    font-size: 0.7rem;
    color: #999;
    text-align: center;
}

/* Lista de turnos */
.flavor-proximos-turnos {
    margin-bottom: 2rem;
}

.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.15rem;
    color: #333;
    margin: 0 0 1rem;
}

.flavor-turnos-lista {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flavor-turno-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #fafafa;
    border-radius: 12px;
    border-left: 4px solid;
    transition: all 0.2s;
}

.flavor-turno-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.flavor-turno-apuntado {
    background: #e8f5e9;
}

.flavor-turno-fecha {
    min-width: 60px;
    text-align: center;
    padding: 0.5rem;
    background: #fff;
    border-radius: 8px;
}

.flavor-fecha-dia {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-fecha-mes {
    display: block;
    font-size: 0.8rem;
    color: #757575;
    text-transform: uppercase;
}

.flavor-turno-info {
    flex: 1;
}

.flavor-turno-header-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.flavor-turno-tipo {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    color: #fff;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.flavor-badge-apuntado {
    padding: 0.2rem 0.5rem;
    background: #4caf50;
    color: #fff;
    border-radius: 10px;
    font-size: 0.75rem;
}

.flavor-turno-titulo {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    color: #333;
}

.flavor-turno-descripcion {
    margin: 0 0 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.flavor-turno-detalles {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.flavor-detalle {
    font-size: 0.85rem;
    color: #555;
}

.flavor-turno-voluntarios {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.flavor-voluntarios-label {
    font-size: 0.85rem;
    color: #757575;
}

.flavor-voluntario-chip {
    padding: 0.2rem 0.5rem;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
    font-size: 0.8rem;
}

.flavor-turno-acciones {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 0.5rem;
    min-width: 150px;
}

.flavor-btn-turno {
    padding: 0.6rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-apuntarse {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    color: #fff;
}

.flavor-btn-apuntarse:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
}

.flavor-btn-cancelar {
    background: #fff;
    color: #c62828;
    border: 2px solid #ef9a9a;
}

.flavor-btn-cancelar:hover {
    background: #ffebee;
}

.flavor-plazas-info {
    font-size: 0.8rem;
    color: #757575;
}

.flavor-turno-completo {
    padding: 0.5rem 1rem;
    background: #eeeeee;
    color: #999;
    border-radius: 8px;
    font-size: 0.9rem;
}

/* Tipos de tareas */
.flavor-tipos-tareas {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 10px;
}

.flavor-tipos-tareas h4 {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    color: #555;
}

.flavor-tipos-grid {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.flavor-tipo-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.9rem;
}

.flavor-tipo-icono {
    font-size: 1.1rem;
}

/* Info adicional */
.flavor-turnos-info {
    padding: 1rem;
    background: #e3f2fd;
    border-radius: 10px;
    border-left: 4px solid #2196f3;
}

.flavor-turnos-info h4 {
    margin: 0 0 0.5rem;
    color: #1565c0;
    font-size: 1rem;
}

.flavor-turnos-info ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #555;
}

.flavor-turnos-info li {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-turnos-stats {
        flex-direction: column;
        gap: 0.75rem;
    }

    .flavor-dia {
        min-height: 70px;
        padding: 0.35rem;
    }

    .flavor-dia-numero {
        font-size: 0.85rem;
    }

    .flavor-turno-mini {
        font-size: 0.65rem;
    }

    .flavor-turno-card {
        flex-direction: column;
    }

    .flavor-turno-fecha {
        align-self: flex-start;
    }

    .flavor-turno-acciones {
        flex-direction: row;
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .flavor-turnos-compostaje {
        padding: 1rem;
    }

    .flavor-calendario-nav {
        flex-direction: column;
        gap: 0.75rem;
    }

    .flavor-dia {
        min-height: 50px;
    }

    .flavor-dia-turnos {
        display: none;
    }

    .flavor-dia-con-turno::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        width: 6px;
        height: 6px;
        background: #4caf50;
        border-radius: 50%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navegación del calendario (demostración)
    document.querySelectorAll('.flavor-nav-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // En producción, esto recargaría el calendario con AJAX
            console.log('Navegar: ' + this.dataset.accion);
        });
    });

    // Botones de apuntarse/cancelar
    document.querySelectorAll('.flavor-btn-apuntarse, .flavor-btn-cancelar').forEach(btn => {
        btn.addEventListener('click', function() {
            const turnoId = this.dataset.turnoId;
            const esApuntarse = this.classList.contains('flavor-btn-apuntarse');

            // En producción, esto enviaría la acción por AJAX
            console.log((esApuntarse ? 'Apuntarse a' : 'Cancelar') + ' turno: ' + turnoId);

            // Demostración visual
            const card = this.closest('.flavor-turno-card');
            if (esApuntarse) {
                card.classList.add('flavor-turno-apuntado');
                this.textContent = 'Cancelar inscripción';
                this.classList.remove('flavor-btn-apuntarse');
                this.classList.add('flavor-btn-cancelar');
            } else {
                card.classList.remove('flavor-turno-apuntado');
                this.textContent = 'Apuntarme';
                this.classList.remove('flavor-btn-cancelar');
                this.classList.add('flavor-btn-apuntarse');
            }
        });
    });
});
</script>
