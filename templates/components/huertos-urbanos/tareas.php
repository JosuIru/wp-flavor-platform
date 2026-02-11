<?php
/**
 * Template: Lista de tareas comunitarias del huerto
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Huertos_Urbanos
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? $args['titulo'] : 'Tareas Comunitarias';
$huerto_nombre = isset($args['huerto_nombre']) ? $args['huerto_nombre'] : 'Huerto Comunitario La Esperanza';
$mostrar_calendario = isset($args['mostrar_calendario']) ? $args['mostrar_calendario'] : true;
$usuario_actual = isset($args['usuario_actual']) ? $args['usuario_actual'] : 'Visitante';

// Datos de demostración de tareas
$tareas_demo = isset($args['tareas']) ? $args['tareas'] : [
    [
        'id' => 1,
        'titulo' => 'Riego general de parcelas',
        'descripcion' => 'Riego de todas las parcelas del sector A y B. Incluye revisión de goteros y limpieza de filtros.',
        'tipo' => 'riego',
        'prioridad' => 'alta',
        'estado' => 'pendiente',
        'fecha_limite' => date('Y-m-d', strtotime('+1 day')),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'frecuencia' => 'diaria',
        'asignados' => [
            ['nombre' => 'María García', 'parcela' => 'A-02'],
            ['nombre' => 'Carlos López', 'parcela' => 'B-01']
        ],
        'max_voluntarios' => 3,
        'ubicacion' => 'Sector A y B',
        'herramientas' => ['Manguera', 'Llave de paso'],
        'puntos' => 10
    ],
    [
        'id' => 2,
        'titulo' => 'Limpieza de zonas comunes',
        'descripcion' => 'Barrer caminos, recoger hojas secas y mantener ordenada la caseta de herramientas.',
        'tipo' => 'limpieza',
        'prioridad' => 'media',
        'estado' => 'en_progreso',
        'fecha_limite' => date('Y-m-d'),
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
        'frecuencia' => 'semanal',
        'asignados' => [
            ['nombre' => 'Ana Martínez', 'parcela' => 'B-04']
        ],
        'max_voluntarios' => 4,
        'ubicacion' => 'Zonas comunes',
        'herramientas' => ['Escoba', 'Recogedor', 'Bolsas'],
        'puntos' => 15
    ],
    [
        'id' => 3,
        'titulo' => 'Mantenimiento del compostador',
        'descripcion' => 'Voltear el compost, añadir material seco si es necesario y comprobar temperatura y humedad.',
        'tipo' => 'mantenimiento',
        'prioridad' => 'media',
        'estado' => 'pendiente',
        'fecha_limite' => date('Y-m-d', strtotime('+3 days')),
        'hora_inicio' => '10:00',
        'hora_fin' => '11:30',
        'frecuencia' => 'semanal',
        'asignados' => [],
        'max_voluntarios' => 2,
        'ubicacion' => 'Zona de compostaje',
        'herramientas' => ['Horca', 'Termómetro', 'Guantes'],
        'puntos' => 20
    ],
    [
        'id' => 4,
        'titulo' => 'Taller de siembra para nuevos hortelanos',
        'descripcion' => 'Sesión práctica de siembra para los nuevos miembros. Incluye técnicas básicas y calendario de cultivos.',
        'tipo' => 'formacion',
        'prioridad' => 'baja',
        'estado' => 'programada',
        'fecha_limite' => date('Y-m-d', strtotime('+7 days')),
        'hora_inicio' => '11:00',
        'hora_fin' => '13:00',
        'frecuencia' => 'mensual',
        'asignados' => [
            ['nombre' => 'Pedro Sánchez', 'parcela' => 'C-04']
        ],
        'max_voluntarios' => 1,
        'ubicacion' => 'Parcela de demostración',
        'herramientas' => ['Semilleros', 'Sustrato', 'Regadera'],
        'puntos' => 25
    ],
    [
        'id' => 5,
        'titulo' => 'Reparación de vallas perimetrales',
        'descripcion' => 'Revisar y reparar las secciones dañadas de la valla en el lado norte del huerto.',
        'tipo' => 'mantenimiento',
        'prioridad' => 'alta',
        'estado' => 'pendiente',
        'fecha_limite' => date('Y-m-d', strtotime('+2 days')),
        'hora_inicio' => '16:00',
        'hora_fin' => '18:00',
        'frecuencia' => 'puntual',
        'asignados' => [
            ['nombre' => 'Miguel Ruiz', 'parcela' => 'D-02']
        ],
        'max_voluntarios' => 3,
        'ubicacion' => 'Perímetro norte',
        'herramientas' => ['Martillo', 'Alambre', 'Alicates', 'Guantes'],
        'puntos' => 30
    ],
    [
        'id' => 6,
        'titulo' => 'Control de plagas ecológico',
        'descripcion' => 'Revisión de parcelas para detectar plagas. Aplicar tratamientos preventivos ecológicos si es necesario.',
        'tipo' => 'fitosanitario',
        'prioridad' => 'alta',
        'estado' => 'completada',
        'fecha_limite' => date('Y-m-d', strtotime('-1 day')),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'frecuencia' => 'quincenal',
        'asignados' => [
            ['nombre' => 'Elena Gómez', 'parcela' => 'A-08'],
            ['nombre' => 'Rosa Díaz', 'parcela' => 'B-06']
        ],
        'max_voluntarios' => 2,
        'ubicacion' => 'Todas las parcelas',
        'herramientas' => ['Pulverizador', 'Jabón potásico', 'Lupa'],
        'puntos' => 20,
        'completada_por' => 'Elena Gómez'
    ],
    [
        'id' => 7,
        'titulo' => 'Asamblea mensual de hortelanos',
        'descripcion' => 'Reunión mensual para coordinar actividades, resolver dudas y planificar el próximo mes.',
        'tipo' => 'reunion',
        'prioridad' => 'media',
        'estado' => 'programada',
        'fecha_limite' => date('Y-m-d', strtotime('+10 days')),
        'hora_inicio' => '18:00',
        'hora_fin' => '19:30',
        'frecuencia' => 'mensual',
        'asignados' => [],
        'max_voluntarios' => 50,
        'ubicacion' => 'Caseta comunitaria',
        'herramientas' => [],
        'puntos' => 5
    ],
    [
        'id' => 8,
        'titulo' => 'Preparación de bancales nuevos',
        'descripcion' => 'Preparar el terreno para los nuevos bancales del sector D. Incluye cavado, abonado y nivelación.',
        'tipo' => 'preparacion',
        'prioridad' => 'media',
        'estado' => 'pendiente',
        'fecha_limite' => date('Y-m-d', strtotime('+5 days')),
        'hora_inicio' => '09:00',
        'hora_fin' => '13:00',
        'frecuencia' => 'puntual',
        'asignados' => [
            ['nombre' => 'Carlos López', 'parcela' => 'B-01']
        ],
        'max_voluntarios' => 5,
        'ubicacion' => 'Sector D',
        'herramientas' => ['Azada', 'Rastrillo', 'Carretilla', 'Compost'],
        'puntos' => 35
    ]
];

// Tipos de tareas con sus iconos y colores
$tipos_tareas = [
    'riego' => ['nombre' => 'Riego', 'icono' => '💧', 'color' => '#2196F3'],
    'limpieza' => ['nombre' => 'Limpieza', 'icono' => '🧹', 'color' => '#9C27B0'],
    'mantenimiento' => ['nombre' => 'Mantenimiento', 'icono' => '🔧', 'color' => '#FF9800'],
    'formacion' => ['nombre' => 'Formación', 'icono' => '📚', 'color' => '#4CAF50'],
    'fitosanitario' => ['nombre' => 'Fitosanitario', 'icono' => '🐛', 'color' => '#F44336'],
    'reunion' => ['nombre' => 'Reunión', 'icono' => '👥', 'color' => '#607D8B'],
    'preparacion' => ['nombre' => 'Preparación', 'icono' => '🌱', 'color' => '#795548']
];

// Función para obtener el color de prioridad
function obtener_color_prioridad_tarea($prioridad) {
    $colores = [
        'alta' => '#F44336',
        'media' => '#FF9800',
        'baja' => '#4CAF50'
    ];
    return isset($colores[$prioridad]) ? $colores[$prioridad] : '#9E9E9E';
}

// Función para obtener el color de estado
function obtener_color_estado_tarea($estado) {
    $colores = [
        'pendiente' => '#FF9800',
        'en_progreso' => '#2196F3',
        'programada' => '#9C27B0',
        'completada' => '#4CAF50'
    ];
    return isset($colores[$estado]) ? $colores[$estado] : '#9E9E9E';
}

// Función para formatear fecha
function formatear_fecha_tarea($fecha) {
    $timestamp = strtotime($fecha);
    $hoy = strtotime('today');
    $manana = strtotime('tomorrow');

    if ($timestamp == $hoy) {
        return 'Hoy';
    } elseif ($timestamp == $manana) {
        return 'Mañana';
    } elseif ($timestamp < $hoy) {
        return 'Vencida';
    } else {
        return date('d M', $timestamp);
    }
}

// Calcular estadísticas
$tareas_pendientes = count(array_filter($tareas_demo, fn($tarea) => $tarea['estado'] === 'pendiente'));
$tareas_en_progreso = count(array_filter($tareas_demo, fn($tarea) => $tarea['estado'] === 'en_progreso'));
$tareas_completadas = count(array_filter($tareas_demo, fn($tarea) => $tarea['estado'] === 'completada'));
$puntos_totales_disponibles = array_sum(array_map(fn($tarea) => $tarea['estado'] !== 'completada' ? $tarea['puntos'] : 0, $tareas_demo));
?>

<div class="flavor-tareas-container">
    <header class="flavor-tareas-header">
        <div class="flavor-header-content">
            <h2 class="flavor-tareas-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-tareas-subtitulo"><?php echo esc_html($huerto_nombre); ?></p>
        </div>
        <div class="flavor-header-acciones">
            <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-mis-tareas">
                <span>👤</span> Mis tareas
            </button>
            <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-nueva-tarea">
                <span>➕</span> Proponer tarea
            </button>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="flavor-tareas-stats">
        <div class="flavor-stat-card flavor-stat-pendientes">
            <div class="flavor-stat-icono">📋</div>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($tareas_pendientes); ?></span>
                <span class="flavor-stat-texto">Pendientes</span>
            </div>
        </div>
        <div class="flavor-stat-card flavor-stat-progreso">
            <div class="flavor-stat-icono">🔄</div>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($tareas_en_progreso); ?></span>
                <span class="flavor-stat-texto">En progreso</span>
            </div>
        </div>
        <div class="flavor-stat-card flavor-stat-completadas">
            <div class="flavor-stat-icono">✅</div>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($tareas_completadas); ?></span>
                <span class="flavor-stat-texto">Completadas</span>
            </div>
        </div>
        <div class="flavor-stat-card flavor-stat-puntos">
            <div class="flavor-stat-icono">⭐</div>
            <div class="flavor-stat-info">
                <span class="flavor-stat-numero"><?php echo esc_html($puntos_totales_disponibles); ?></span>
                <span class="flavor-stat-texto">Puntos disponibles</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-tareas-filtros">
        <div class="flavor-filtros-tipo">
            <span class="flavor-filtros-label">Filtrar por tipo:</span>
            <div class="flavor-filtros-botones">
                <button type="button" class="flavor-filtro-btn active" data-tipo="todos">Todos</button>
                <?php foreach ($tipos_tareas as $tipo_clave => $tipo_info) : ?>
                <button type="button" class="flavor-filtro-btn" data-tipo="<?php echo esc_attr($tipo_clave); ?>">
                    <?php echo esc_html($tipo_info['icono']); ?> <?php echo esc_html($tipo_info['nombre']); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flavor-filtros-estado">
            <span class="flavor-filtros-label">Estado:</span>
            <select class="flavor-select-estado" id="flavor-filtro-estado">
                <option value="todos">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_progreso">En progreso</option>
                <option value="programada">Programada</option>
                <option value="completada">Completada</option>
            </select>
        </div>
        <div class="flavor-filtros-vista">
            <button type="button" class="flavor-vista-btn active" data-vista="lista" title="Vista lista">
                <span>☰</span>
            </button>
            <button type="button" class="flavor-vista-btn" data-vista="tarjetas" title="Vista tarjetas">
                <span>▦</span>
            </button>
        </div>
    </div>

    <!-- Lista de tareas -->
    <div class="flavor-tareas-lista" id="flavor-tareas-lista">
        <?php foreach ($tareas_demo as $tarea) :
            $tipo_info = $tipos_tareas[$tarea['tipo']] ?? ['nombre' => 'Otro', 'icono' => '📌', 'color' => '#9E9E9E'];
            $color_prioridad = obtener_color_prioridad_tarea($tarea['prioridad']);
            $color_estado = obtener_color_estado_tarea($tarea['estado']);
            $fecha_formateada = formatear_fecha_tarea($tarea['fecha_limite']);
            $voluntarios_restantes = $tarea['max_voluntarios'] - count($tarea['asignados']);
            $es_urgente = $fecha_formateada === 'Hoy' || $fecha_formateada === 'Vencida';
        ?>
        <article class="flavor-tarea-item <?php echo $tarea['estado'] === 'completada' ? 'flavor-tarea-completada' : ''; ?>"
                 data-tipo="<?php echo esc_attr($tarea['tipo']); ?>"
                 data-estado="<?php echo esc_attr($tarea['estado']); ?>"
                 data-id="<?php echo esc_attr($tarea['id']); ?>">

            <div class="flavor-tarea-prioridad" style="background: <?php echo esc_attr($color_prioridad); ?>;" title="Prioridad <?php echo esc_attr($tarea['prioridad']); ?>"></div>

            <div class="flavor-tarea-contenido">
                <div class="flavor-tarea-header">
                    <div class="flavor-tarea-tipo" style="background: <?php echo esc_attr($tipo_info['color']); ?>;">
                        <?php echo esc_html($tipo_info['icono']); ?>
                    </div>
                    <div class="flavor-tarea-info-principal">
                        <h3 class="flavor-tarea-titulo"><?php echo esc_html($tarea['titulo']); ?></h3>
                        <div class="flavor-tarea-meta">
                            <span class="flavor-tarea-ubicacion">
                                <span class="flavor-meta-icono">📍</span>
                                <?php echo esc_html($tarea['ubicacion']); ?>
                            </span>
                            <span class="flavor-tarea-horario">
                                <span class="flavor-meta-icono">🕐</span>
                                <?php echo esc_html($tarea['hora_inicio']); ?> - <?php echo esc_html($tarea['hora_fin']); ?>
                            </span>
                            <span class="flavor-tarea-frecuencia">
                                <span class="flavor-meta-icono">🔁</span>
                                <?php echo esc_html(ucfirst($tarea['frecuencia'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flavor-tarea-fecha <?php echo $es_urgente ? 'flavor-fecha-urgente' : ''; ?>">
                        <span class="flavor-fecha-texto"><?php echo esc_html($fecha_formateada); ?></span>
                        <?php if ($tarea['estado'] !== 'completada') : ?>
                        <span class="flavor-fecha-completa"><?php echo date('d/m/Y', strtotime($tarea['fecha_limite'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="flavor-tarea-descripcion"><?php echo esc_html($tarea['descripcion']); ?></p>

                <div class="flavor-tarea-detalles">
                    <?php if (!empty($tarea['herramientas'])) : ?>
                    <div class="flavor-tarea-herramientas">
                        <span class="flavor-detalle-label">🔧 Herramientas:</span>
                        <div class="flavor-herramientas-lista">
                            <?php foreach ($tarea['herramientas'] as $herramienta) : ?>
                            <span class="flavor-herramienta-tag"><?php echo esc_html($herramienta); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-tarea-participantes">
                        <span class="flavor-detalle-label">👥 Voluntarios:</span>
                        <div class="flavor-participantes-info">
                            <?php if (count($tarea['asignados']) > 0) : ?>
                            <div class="flavor-participantes-avatares">
                                <?php foreach ($tarea['asignados'] as $asignado) : ?>
                                <span class="flavor-participante-avatar" title="<?php echo esc_attr($asignado['nombre']); ?> (<?php echo esc_attr($asignado['parcela']); ?>)">
                                    <?php echo esc_html(substr($asignado['nombre'], 0, 1)); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <span class="flavor-participantes-count">
                                <?php echo esc_html(count($tarea['asignados'])); ?>/<?php echo esc_html($tarea['max_voluntarios']); ?>
                                <?php if ($voluntarios_restantes > 0 && $tarea['estado'] !== 'completada') : ?>
                                <span class="flavor-vacantes">(<?php echo esc_html($voluntarios_restantes); ?> vacantes)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="flavor-tarea-puntos">
                        <span class="flavor-puntos-icono">⭐</span>
                        <span class="flavor-puntos-valor"><?php echo esc_html($tarea['puntos']); ?> puntos</span>
                    </div>
                </div>

                <div class="flavor-tarea-footer">
                    <span class="flavor-estado-badge" style="background: <?php echo esc_attr($color_estado); ?>;">
                        <?php
                        $textos_estado = [
                            'pendiente' => 'Pendiente',
                            'en_progreso' => 'En progreso',
                            'programada' => 'Programada',
                            'completada' => 'Completada'
                        ];
                        echo esc_html($textos_estado[$tarea['estado']] ?? $tarea['estado']);
                        ?>
                    </span>

                    <?php if ($tarea['estado'] === 'completada') : ?>
                    <span class="flavor-completada-por">
                        ✅ Completada por <?php echo esc_html($tarea['completada_por'] ?? 'el equipo'); ?>
                    </span>
                    <?php else : ?>
                    <div class="flavor-tarea-acciones">
                        <?php if ($voluntarios_restantes > 0) : ?>
                        <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-apuntarse" data-tarea="<?php echo esc_attr($tarea['id']); ?>">
                            <span>✋</span> Apuntarme
                        </button>
                        <?php else : ?>
                        <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-disabled" disabled>
                            Completo
                        </button>
                        <?php endif; ?>
                        <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-detalles" data-tarea="<?php echo esc_attr($tarea['id']); ?>">
                            Ver detalles
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($mostrar_calendario) : ?>
    <!-- Mini calendario de tareas -->
    <div class="flavor-tareas-calendario">
        <h3 class="flavor-calendario-titulo">📅 Próximas tareas programadas</h3>
        <div class="flavor-calendario-semana">
            <?php
            $dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            $fecha_inicio = strtotime('monday this week');
            for ($i = 0; $i < 7; $i++) :
                $fecha_dia = date('Y-m-d', strtotime("+$i days", $fecha_inicio));
                $es_hoy = $fecha_dia === date('Y-m-d');
                $tareas_dia = array_filter($tareas_demo, fn($tarea) => $tarea['fecha_limite'] === $fecha_dia && $tarea['estado'] !== 'completada');
            ?>
            <div class="flavor-calendario-dia <?php echo $es_hoy ? 'flavor-dia-hoy' : ''; ?>">
                <span class="flavor-dia-nombre"><?php echo esc_html($dias_semana[$i]); ?></span>
                <span class="flavor-dia-numero"><?php echo date('d', strtotime("+$i days", $fecha_inicio)); ?></span>
                <?php if (count($tareas_dia) > 0) : ?>
                <div class="flavor-dia-tareas">
                    <?php foreach (array_slice($tareas_dia, 0, 2) as $tarea_dia) :
                        $tipo_info_dia = $tipos_tareas[$tarea_dia['tipo']] ?? ['icono' => '📌', 'color' => '#9E9E9E'];
                    ?>
                    <span class="flavor-mini-tarea" style="background: <?php echo esc_attr($tipo_info_dia['color']); ?>;" title="<?php echo esc_attr($tarea_dia['titulo']); ?>">
                        <?php echo esc_html($tipo_info_dia['icono']); ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($tareas_dia) > 2) : ?>
                    <span class="flavor-mini-mas">+<?php echo count($tareas_dia) - 2; ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sistema de puntos -->
    <div class="flavor-sistema-puntos">
        <h3 class="flavor-puntos-titulo">⭐ Sistema de puntos comunitarios</h3>
        <div class="flavor-puntos-grid">
            <div class="flavor-puntos-info">
                <p>Gana puntos participando en las tareas comunitarias. Los puntos pueden canjearse por:</p>
                <ul class="flavor-puntos-beneficios">
                    <li>🌱 Semillas y plantones del huerto</li>
                    <li>🧰 Préstamo de herramientas especiales</li>
                    <li>📚 Acceso a talleres y formaciones</li>
                    <li>🎫 Prioridad en eventos especiales</li>
                </ul>
            </div>
            <div class="flavor-ranking">
                <h4 class="flavor-ranking-titulo">🏆 Top colaboradores del mes</h4>
                <ol class="flavor-ranking-lista">
                    <li class="flavor-ranking-item">
                        <span class="flavor-ranking-posicion">1</span>
                        <span class="flavor-ranking-nombre">Miguel Ruiz</span>
                        <span class="flavor-ranking-puntos">185 pts</span>
                    </li>
                    <li class="flavor-ranking-item">
                        <span class="flavor-ranking-posicion">2</span>
                        <span class="flavor-ranking-nombre">Elena Gómez</span>
                        <span class="flavor-ranking-puntos">142 pts</span>
                    </li>
                    <li class="flavor-ranking-item">
                        <span class="flavor-ranking-posicion">3</span>
                        <span class="flavor-ranking-nombre">Ana Martínez</span>
                        <span class="flavor-ranking-puntos">128 pts</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Reglas de participación -->
    <div class="flavor-tareas-reglas">
        <h4 class="flavor-reglas-titulo">📋 Normas de participación</h4>
        <div class="flavor-reglas-grid">
            <div class="flavor-regla">
                <span class="flavor-regla-icono">⏰</span>
                <p>Confirma tu asistencia con 24h de antelación</p>
            </div>
            <div class="flavor-regla">
                <span class="flavor-regla-icono">📱</span>
                <p>Si no puedes asistir, avisa lo antes posible</p>
            </div>
            <div class="flavor-regla">
                <span class="flavor-regla-icono">🧤</span>
                <p>Trae ropa adecuada y protección si es necesario</p>
            </div>
            <div class="flavor-regla">
                <span class="flavor-regla-icono">🤝</span>
                <p>Colabora con respeto y buen ambiente</p>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-tareas-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-tareas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-tareas-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0;
}

.flavor-tareas-subtitulo {
    color: #666;
    margin: 5px 0 0 0;
}

.flavor-header-acciones {
    display: flex;
    gap: 10px;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-sm {
    padding: 8px 14px;
    font-size: 0.85rem;
}

.flavor-btn-primary {
    background: #4CAF50;
    color: white;
}

.flavor-btn-primary:hover {
    background: #388E3C;
}

.flavor-btn-outline {
    background: transparent;
    border: 1px solid #4CAF50;
    color: #4CAF50;
}

.flavor-btn-outline:hover {
    background: #e8f5e9;
}

.flavor-btn-disabled {
    background: #e0e0e0;
    color: #999;
    cursor: not-allowed;
}

.flavor-tareas-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.flavor-stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.flavor-stat-pendientes {
    border-left: 4px solid #FF9800;
}

.flavor-stat-progreso {
    border-left: 4px solid #2196F3;
}

.flavor-stat-completadas {
    border-left: 4px solid #4CAF50;
}

.flavor-stat-puntos {
    border-left: 4px solid #FFC107;
}

.flavor-stat-icono {
    font-size: 1.75rem;
}

.flavor-stat-info {
    display: flex;
    flex-direction: column;
}

.flavor-stat-numero {
    font-size: 1.75rem;
    font-weight: 700;
    color: #333;
}

.flavor-stat-texto {
    font-size: 0.875rem;
    color: #666;
}

.flavor-tareas-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 12px;
    margin-bottom: 25px;
}

.flavor-filtros-tipo {
    flex: 1;
}

.flavor-filtros-label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 8px;
}

.flavor-filtros-botones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-filtro-btn {
    padding: 8px 14px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
}

.flavor-filtro-btn:hover {
    border-color: #4CAF50;
}

.flavor-filtro-btn.active {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-select-estado {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
}

.flavor-filtros-vista {
    display: flex;
    gap: 5px;
}

.flavor-vista-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
}

.flavor-vista-btn.active,
.flavor-vista-btn:hover {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-tareas-lista {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 40px;
}

.flavor-tarea-item {
    display: flex;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-tarea-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.flavor-tarea-completada {
    opacity: 0.7;
}

.flavor-tarea-prioridad {
    width: 6px;
    flex-shrink: 0;
}

.flavor-tarea-contenido {
    flex: 1;
    padding: 20px;
}

.flavor-tarea-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 12px;
}

.flavor-tarea-tipo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.flavor-tarea-info-principal {
    flex: 1;
}

.flavor-tarea-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 8px 0;
}

.flavor-tarea-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 0.85rem;
    color: #666;
}

.flavor-tarea-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.flavor-meta-icono {
    font-size: 0.9rem;
}

.flavor-tarea-fecha {
    text-align: right;
    flex-shrink: 0;
}

.flavor-fecha-texto {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.flavor-fecha-urgente .flavor-fecha-texto {
    color: #F44336;
}

.flavor-fecha-completa {
    display: block;
    font-size: 0.8rem;
    color: #999;
}

.flavor-tarea-descripcion {
    color: #555;
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0 0 15px 0;
}

.flavor-tarea-detalles {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin-bottom: 15px;
}

.flavor-detalle-label {
    font-size: 0.8rem;
    color: #666;
    margin-right: 8px;
}

.flavor-herramientas-lista {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.flavor-herramienta-tag {
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976D2;
    border-radius: 4px;
    font-size: 0.8rem;
}

.flavor-participantes-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-participantes-avatares {
    display: flex;
    margin-left: -5px;
}

.flavor-participante-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #4CAF50, #81C784);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    border: 2px solid white;
    margin-left: -8px;
}

.flavor-participante-avatar:first-child {
    margin-left: 0;
}

.flavor-participantes-count {
    font-size: 0.875rem;
    color: #333;
}

.flavor-vacantes {
    color: #4CAF50;
    font-weight: 500;
}

.flavor-tarea-puntos {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    padding: 6px 12px;
    background: #FFF8E1;
    border-radius: 20px;
}

.flavor-puntos-icono {
    font-size: 1rem;
}

.flavor-puntos-valor {
    font-weight: 600;
    color: #F57C00;
    font-size: 0.9rem;
}

.flavor-tarea-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-estado-badge {
    padding: 6px 14px;
    border-radius: 20px;
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
}

.flavor-completada-por {
    font-size: 0.85rem;
    color: #4CAF50;
}

.flavor-tarea-acciones {
    display: flex;
    gap: 10px;
}

/* Calendario */
.flavor-tareas-calendario {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.flavor-calendario-titulo {
    font-size: 1.25rem;
    color: #333;
    margin: 0 0 20px 0;
}

.flavor-calendario-semana {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.flavor-calendario-dia {
    text-align: center;
    padding: 15px 10px;
    background: #f5f5f5;
    border-radius: 8px;
    min-height: 100px;
}

.flavor-dia-hoy {
    background: #e8f5e9;
    border: 2px solid #4CAF50;
}

.flavor-dia-nombre {
    display: block;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 5px;
}

.flavor-dia-numero {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.flavor-dia-hoy .flavor-dia-numero {
    color: #4CAF50;
}

.flavor-dia-tareas {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 4px;
}

.flavor-mini-tarea {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.flavor-mini-mas {
    width: 28px;
    height: 28px;
    background: #ddd;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #666;
}

/* Sistema de puntos */
.flavor-sistema-puntos {
    background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.flavor-puntos-titulo {
    font-size: 1.25rem;
    color: #F57C00;
    margin: 0 0 20px 0;
    text-align: center;
}

.flavor-puntos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.flavor-puntos-info p {
    margin: 0 0 15px 0;
    color: #555;
}

.flavor-puntos-beneficios {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-puntos-beneficios li {
    padding: 8px 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-ranking {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.flavor-ranking-titulo {
    font-size: 1rem;
    color: #333;
    margin: 0 0 15px 0;
}

.flavor-ranking-lista {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: ranking;
}

.flavor-ranking-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.flavor-ranking-item:last-child {
    border-bottom: none;
}

.flavor-ranking-posicion {
    width: 28px;
    height: 28px;
    background: #FFC107;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.flavor-ranking-item:nth-child(2) .flavor-ranking-posicion {
    background: #9E9E9E;
}

.flavor-ranking-item:nth-child(3) .flavor-ranking-posicion {
    background: #CD7F32;
}

.flavor-ranking-nombre {
    flex: 1;
    font-weight: 500;
    color: #333;
}

.flavor-ranking-puntos {
    font-weight: 600;
    color: #F57C00;
}

/* Reglas */
.flavor-tareas-reglas {
    background: #f5f5f5;
    padding: 25px;
    border-radius: 12px;
}

.flavor-reglas-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 15px 0;
    text-align: center;
}

.flavor-reglas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.flavor-regla {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: white;
    padding: 15px;
    border-radius: 8px;
}

.flavor-regla-icono {
    font-size: 1.5rem;
}

.flavor-regla p {
    margin: 0;
    font-size: 0.9rem;
    color: #555;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 900px) {
    .flavor-calendario-semana {
        grid-template-columns: repeat(4, 1fr);
    }

    .flavor-calendario-dia:nth-child(n+5) {
        grid-column: span 1;
    }
}

@media (max-width: 768px) {
    .flavor-tareas-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-header-acciones {
        width: 100%;
    }

    .flavor-header-acciones .flavor-btn {
        flex: 1;
        justify-content: center;
    }

    .flavor-tareas-filtros {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-filtros-vista {
        align-self: flex-end;
    }

    .flavor-tarea-header {
        flex-direction: column;
    }

    .flavor-tarea-fecha {
        text-align: left;
    }

    .flavor-tarea-detalles {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-tarea-puntos {
        margin-left: 0;
    }

    .flavor-tarea-footer {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-tarea-acciones {
        width: 100%;
    }

    .flavor-tarea-acciones .flavor-btn {
        flex: 1;
        justify-content: center;
    }

    .flavor-calendario-semana {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .flavor-tareas-container {
        padding: 15px;
    }

    .flavor-tareas-titulo {
        font-size: 1.5rem;
    }

    .flavor-stat-card {
        padding: 15px;
    }

    .flavor-stat-numero {
        font-size: 1.5rem;
    }

    .flavor-tarea-item {
        flex-direction: column;
    }

    .flavor-tarea-prioridad {
        width: 100%;
        height: 4px;
    }

    .flavor-calendario-dia {
        min-height: 80px;
        padding: 10px 5px;
    }
}
</style>
