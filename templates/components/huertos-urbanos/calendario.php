<?php
/**
 * Template: Calendario de cultivos (qué plantar en cada época)
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Huertos_Urbanos
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_calendario = isset($args['titulo']) ? $args['titulo'] : 'Calendario de Cultivos';
$mes_actual = isset($args['mes_actual']) ? $args['mes_actual'] : date('n');
$mostrar_todos_meses = isset($args['mostrar_todos_meses']) ? $args['mostrar_todos_meses'] : false;
$zona_climatica = isset($args['zona_climatica']) ? $args['zona_climatica'] : 'mediterranea';

// Nombres de los meses
$nombres_meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

// Datos de demostración del calendario de cultivos
$calendario_cultivos = isset($args['cultivos']) ? $args['cultivos'] : [
    // Hortalizas
    [
        'nombre' => 'Tomate',
        'tipo' => 'hortaliza',
        'icono' => '🍅',
        'siembra' => [2, 3, 4],
        'trasplante' => [4, 5],
        'cosecha' => [6, 7, 8, 9],
        'dificultad' => 'media',
        'tiempo_cosecha' => '60-80 días',
        'consejos' => 'Necesita mucho sol y riego regular'
    ],
    [
        'nombre' => 'Lechuga',
        'tipo' => 'hortaliza',
        'icono' => '🥬',
        'siembra' => [1, 2, 3, 8, 9, 10],
        'trasplante' => [2, 3, 4, 9, 10, 11],
        'cosecha' => [3, 4, 5, 10, 11, 12],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '45-60 días',
        'consejos' => 'Evitar el calor extremo'
    ],
    [
        'nombre' => 'Pimiento',
        'tipo' => 'hortaliza',
        'icono' => '🫑',
        'siembra' => [2, 3],
        'trasplante' => [4, 5],
        'cosecha' => [7, 8, 9, 10],
        'dificultad' => 'media',
        'tiempo_cosecha' => '70-90 días',
        'consejos' => 'Proteger de heladas'
    ],
    [
        'nombre' => 'Zanahoria',
        'tipo' => 'hortaliza',
        'icono' => '🥕',
        'siembra' => [2, 3, 4, 7, 8],
        'trasplante' => [],
        'cosecha' => [5, 6, 7, 10, 11, 12],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '70-80 días',
        'consejos' => 'Siembra directa, no trasplantar'
    ],
    [
        'nombre' => 'Calabacín',
        'tipo' => 'hortaliza',
        'icono' => '🥒',
        'siembra' => [3, 4, 5],
        'trasplante' => [4, 5, 6],
        'cosecha' => [6, 7, 8, 9],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '50-60 días',
        'consejos' => 'Muy productivo, necesita espacio'
    ],
    [
        'nombre' => 'Berenjena',
        'tipo' => 'hortaliza',
        'icono' => '🍆',
        'siembra' => [2, 3],
        'trasplante' => [4, 5],
        'cosecha' => [7, 8, 9],
        'dificultad' => 'media',
        'tiempo_cosecha' => '80-90 días',
        'consejos' => 'Necesita calor constante'
    ],
    // Aromáticas
    [
        'nombre' => 'Albahaca',
        'tipo' => 'aromatica',
        'icono' => '🌿',
        'siembra' => [3, 4, 5],
        'trasplante' => [4, 5, 6],
        'cosecha' => [5, 6, 7, 8, 9],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '30-40 días',
        'consejos' => 'Pellizcar flores para prolongar cosecha'
    ],
    [
        'nombre' => 'Perejil',
        'tipo' => 'aromatica',
        'icono' => '🌱',
        'siembra' => [2, 3, 4, 8, 9],
        'trasplante' => [],
        'cosecha' => [4, 5, 6, 7, 8, 9, 10, 11],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '60-70 días',
        'consejos' => 'Germinación lenta, paciencia'
    ],
    [
        'nombre' => 'Romero',
        'tipo' => 'aromatica',
        'icono' => '🪻',
        'siembra' => [3, 4, 9, 10],
        'trasplante' => [4, 5, 10, 11],
        'cosecha' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        'dificultad' => 'facil',
        'tiempo_cosecha' => 'Perenne',
        'consejos' => 'Resiste sequía, poco riego'
    ],
    // Legumbres
    [
        'nombre' => 'Judías verdes',
        'tipo' => 'legumbre',
        'icono' => '🫘',
        'siembra' => [4, 5, 6],
        'trasplante' => [],
        'cosecha' => [6, 7, 8, 9],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '50-60 días',
        'consejos' => 'Siembra directa, necesita tutor'
    ],
    [
        'nombre' => 'Guisantes',
        'tipo' => 'legumbre',
        'icono' => '🟢',
        'siembra' => [10, 11, 2, 3],
        'trasplante' => [],
        'cosecha' => [4, 5, 6],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '60-70 días',
        'consejos' => 'Cultivo de invierno'
    ],
    // Raíces
    [
        'nombre' => 'Patata',
        'tipo' => 'raiz',
        'icono' => '🥔',
        'siembra' => [2, 3, 4],
        'trasplante' => [],
        'cosecha' => [6, 7, 8],
        'dificultad' => 'media',
        'tiempo_cosecha' => '90-120 días',
        'consejos' => 'Aporcar tierra conforme crece'
    ],
    [
        'nombre' => 'Cebolla',
        'tipo' => 'raiz',
        'icono' => '🧅',
        'siembra' => [9, 10, 11],
        'trasplante' => [11, 12, 1, 2],
        'cosecha' => [5, 6, 7],
        'dificultad' => 'media',
        'tiempo_cosecha' => '150-180 días',
        'consejos' => 'Ciclo largo, paciencia'
    ],
    [
        'nombre' => 'Ajo',
        'tipo' => 'raiz',
        'icono' => '🧄',
        'siembra' => [10, 11, 12],
        'trasplante' => [],
        'cosecha' => [5, 6, 7],
        'dificultad' => 'facil',
        'tiempo_cosecha' => '180-210 días',
        'consejos' => 'Plantar dientes con punta hacia arriba'
    ]
];

// Función para obtener el color según el tipo de cultivo
function obtener_color_tipo_cultivo($tipo) {
    $colores = [
        'hortaliza' => '#4CAF50',
        'aromatica' => '#9C27B0',
        'legumbre' => '#FF9800',
        'raiz' => '#795548',
        'fruta' => '#E91E63'
    ];
    return isset($colores[$tipo]) ? $colores[$tipo] : '#607D8B';
}

// Función para obtener el color de dificultad
function obtener_color_dificultad_cultivo($dificultad) {
    $colores = [
        'facil' => '#4CAF50',
        'media' => '#FF9800',
        'dificil' => '#F44336'
    ];
    return isset($colores[$dificultad]) ? $colores[$dificultad] : '#9E9E9E';
}

// Función para verificar si un mes está en un array
function esta_en_mes($mes, $meses_array) {
    return in_array($mes, $meses_array);
}

// Obtener cultivos del mes actual
$cultivos_siembra_mes = array_filter($calendario_cultivos, fn($cultivo) => in_array($mes_actual, $cultivo['siembra']));
$cultivos_cosecha_mes = array_filter($calendario_cultivos, fn($cultivo) => in_array($mes_actual, $cultivo['cosecha']));
?>

<div class="flavor-calendario-container">
    <header class="flavor-calendario-header">
        <h2 class="flavor-calendario-titulo"><?php echo esc_html($titulo_calendario); ?></h2>
        <p class="flavor-calendario-descripcion">Guía de siembra, trasplante y cosecha para tu huerto urbano</p>
        <div class="flavor-calendario-zona">
            <span class="flavor-zona-icono">🌍</span>
            <span class="flavor-zona-texto">Zona climática: <?php echo esc_html(ucfirst($zona_climatica)); ?></span>
        </div>
    </header>

    <!-- Selector de mes -->
    <div class="flavor-calendario-navegacion">
        <button type="button" class="flavor-btn flavor-btn-nav" data-direccion="anterior">
            <span>←</span> Mes anterior
        </button>
        <div class="flavor-mes-actual">
            <select id="flavor-selector-mes" class="flavor-selector-mes">
                <?php foreach ($nombres_meses as $num_mes => $nombre_mes) : ?>
                <option value="<?php echo esc_attr($num_mes); ?>" <?php selected($num_mes, $mes_actual); ?>>
                    <?php echo esc_html($nombre_mes); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="flavor-anio-actual"><?php echo date('Y'); ?></span>
        </div>
        <button type="button" class="flavor-btn flavor-btn-nav" data-direccion="siguiente">
            Mes siguiente <span>→</span>
        </button>
    </div>

    <!-- Resumen del mes -->
    <div class="flavor-calendario-resumen">
        <div class="flavor-resumen-card flavor-resumen-siembra">
            <div class="flavor-resumen-header">
                <span class="flavor-resumen-icono">🌱</span>
                <h3 class="flavor-resumen-titulo">Para sembrar en <?php echo esc_html($nombres_meses[$mes_actual]); ?></h3>
            </div>
            <div class="flavor-resumen-cultivos">
                <?php if (count($cultivos_siembra_mes) > 0) : ?>
                    <?php foreach ($cultivos_siembra_mes as $cultivo) : ?>
                    <span class="flavor-cultivo-chip">
                        <?php echo esc_html($cultivo['icono']); ?> <?php echo esc_html($cultivo['nombre']); ?>
                    </span>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="flavor-sin-cultivos">No hay siembras recomendadas este mes</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flavor-resumen-card flavor-resumen-cosecha">
            <div class="flavor-resumen-header">
                <span class="flavor-resumen-icono">🧺</span>
                <h3 class="flavor-resumen-titulo">Para cosechar en <?php echo esc_html($nombres_meses[$mes_actual]); ?></h3>
            </div>
            <div class="flavor-resumen-cultivos">
                <?php if (count($cultivos_cosecha_mes) > 0) : ?>
                    <?php foreach ($cultivos_cosecha_mes as $cultivo) : ?>
                    <span class="flavor-cultivo-chip">
                        <?php echo esc_html($cultivo['icono']); ?> <?php echo esc_html($cultivo['nombre']); ?>
                    </span>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="flavor-sin-cultivos">No hay cosechas previstas este mes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="flavor-calendario-leyenda">
        <h4 class="flavor-leyenda-titulo">Leyenda del calendario</h4>
        <div class="flavor-leyenda-items">
            <span class="flavor-leyenda-item">
                <span class="flavor-leyenda-celda flavor-celda-siembra"></span>
                Siembra
            </span>
            <span class="flavor-leyenda-item">
                <span class="flavor-leyenda-celda flavor-celda-trasplante"></span>
                Trasplante
            </span>
            <span class="flavor-leyenda-item">
                <span class="flavor-leyenda-celda flavor-celda-cosecha"></span>
                Cosecha
            </span>
        </div>
        <div class="flavor-leyenda-tipos">
            <span class="flavor-tipo-badge" style="background: #4CAF50;">Hortaliza</span>
            <span class="flavor-tipo-badge" style="background: #9C27B0;">Aromática</span>
            <span class="flavor-tipo-badge" style="background: #FF9800;">Legumbre</span>
            <span class="flavor-tipo-badge" style="background: #795548;">Raíz/Tubérculo</span>
        </div>
    </div>

    <!-- Tabla del calendario -->
    <div class="flavor-calendario-tabla-wrapper">
        <table class="flavor-calendario-tabla">
            <thead>
                <tr>
                    <th class="flavor-th-cultivo">Cultivo</th>
                    <?php foreach ($nombres_meses as $num_mes => $nombre_corto) : ?>
                    <th class="flavor-th-mes <?php echo $num_mes == $mes_actual ? 'flavor-mes-activo' : ''; ?>">
                        <?php echo esc_html(substr($nombre_corto, 0, 3)); ?>
                    </th>
                    <?php endforeach; ?>
                    <th class="flavor-th-info">Info</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendario_cultivos as $cultivo) :
                    $color_tipo = obtener_color_tipo_cultivo($cultivo['tipo']);
                    $color_dificultad = obtener_color_dificultad_cultivo($cultivo['dificultad']);
                ?>
                <tr class="flavor-fila-cultivo" data-tipo="<?php echo esc_attr($cultivo['tipo']); ?>">
                    <td class="flavor-td-cultivo">
                        <div class="flavor-cultivo-info">
                            <span class="flavor-cultivo-icono"><?php echo esc_html($cultivo['icono']); ?></span>
                            <div class="flavor-cultivo-datos">
                                <span class="flavor-cultivo-nombre"><?php echo esc_html($cultivo['nombre']); ?></span>
                                <span class="flavor-cultivo-tipo" style="color: <?php echo esc_attr($color_tipo); ?>;">
                                    <?php echo esc_html(ucfirst($cultivo['tipo'])); ?>
                                </span>
                            </div>
                        </div>
                    </td>
                    <?php for ($mes_iterador = 1; $mes_iterador <= 12; $mes_iterador++) :
                        $es_siembra = esta_en_mes($mes_iterador, $cultivo['siembra']);
                        $es_trasplante = esta_en_mes($mes_iterador, $cultivo['trasplante']);
                        $es_cosecha = esta_en_mes($mes_iterador, $cultivo['cosecha']);
                        $es_mes_actual = $mes_iterador == $mes_actual;

                        $clases_celda = 'flavor-td-mes';
                        if ($es_mes_actual) $clases_celda .= ' flavor-mes-activo';
                    ?>
                    <td class="<?php echo esc_attr($clases_celda); ?>">
                        <div class="flavor-celda-contenido">
                            <?php if ($es_siembra) : ?>
                            <span class="flavor-marca flavor-marca-siembra" title="Siembra">S</span>
                            <?php endif; ?>
                            <?php if ($es_trasplante) : ?>
                            <span class="flavor-marca flavor-marca-trasplante" title="Trasplante">T</span>
                            <?php endif; ?>
                            <?php if ($es_cosecha) : ?>
                            <span class="flavor-marca flavor-marca-cosecha" title="Cosecha">C</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endfor; ?>
                    <td class="flavor-td-info">
                        <button type="button" class="flavor-btn-info"
                                data-cultivo="<?php echo esc_attr($cultivo['nombre']); ?>"
                                data-tiempo="<?php echo esc_attr($cultivo['tiempo_cosecha']); ?>"
                                data-dificultad="<?php echo esc_attr($cultivo['dificultad']); ?>"
                                data-consejos="<?php echo esc_attr($cultivo['consejos']); ?>">
                            ℹ️
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Filtros por tipo -->
    <div class="flavor-calendario-filtros">
        <span class="flavor-filtros-label">Filtrar por tipo:</span>
        <div class="flavor-filtros-botones">
            <button type="button" class="flavor-filtro-tipo active" data-tipo="todos">Todos</button>
            <button type="button" class="flavor-filtro-tipo" data-tipo="hortaliza">🥬 Hortalizas</button>
            <button type="button" class="flavor-filtro-tipo" data-tipo="aromatica">🌿 Aromáticas</button>
            <button type="button" class="flavor-filtro-tipo" data-tipo="legumbre">🫘 Legumbres</button>
            <button type="button" class="flavor-filtro-tipo" data-tipo="raiz">🥔 Raíces</button>
        </div>
    </div>

    <!-- Tarjetas de cultivos destacados -->
    <div class="flavor-cultivos-destacados">
        <h3 class="flavor-destacados-titulo">Cultivos recomendados para <?php echo esc_html($nombres_meses[$mes_actual]); ?></h3>
        <div class="flavor-destacados-grid">
            <?php
            $cultivos_destacados = array_slice(array_filter($calendario_cultivos, fn($c) => in_array($mes_actual, $c['siembra'])), 0, 4);
            foreach ($cultivos_destacados as $cultivo) :
                $color_tipo = obtener_color_tipo_cultivo($cultivo['tipo']);
                $color_dificultad = obtener_color_dificultad_cultivo($cultivo['dificultad']);
            ?>
            <article class="flavor-cultivo-card">
                <div class="flavor-cultivo-card-header" style="background: <?php echo esc_attr($color_tipo); ?>;">
                    <span class="flavor-cultivo-card-icono"><?php echo esc_html($cultivo['icono']); ?></span>
                    <h4 class="flavor-cultivo-card-nombre"><?php echo esc_html($cultivo['nombre']); ?></h4>
                </div>
                <div class="flavor-cultivo-card-body">
                    <div class="flavor-cultivo-meta">
                        <span class="flavor-meta-item">
                            <span class="flavor-meta-icono">⏱️</span>
                            <?php echo esc_html($cultivo['tiempo_cosecha']); ?>
                        </span>
                        <span class="flavor-meta-item flavor-dificultad" style="color: <?php echo esc_attr($color_dificultad); ?>;">
                            <span class="flavor-meta-icono">📊</span>
                            <?php echo esc_html(ucfirst($cultivo['dificultad'])); ?>
                        </span>
                    </div>
                    <p class="flavor-cultivo-consejos"><?php echo esc_html($cultivo['consejos']); ?></p>
                </div>
                <div class="flavor-cultivo-card-footer">
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        Ver ficha completa
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Consejos del mes -->
    <div class="flavor-consejos-mes">
        <h3 class="flavor-consejos-titulo">💡 Consejos para <?php echo esc_html($nombres_meses[$mes_actual]); ?></h3>
        <div class="flavor-consejos-grid">
            <div class="flavor-consejo-card">
                <span class="flavor-consejo-icono">🌡️</span>
                <h4>Clima</h4>
                <p>Revisa la previsión meteorológica antes de sembrar. Protege los cultivos sensibles a las heladas.</p>
            </div>
            <div class="flavor-consejo-card">
                <span class="flavor-consejo-icono">💧</span>
                <h4>Riego</h4>
                <p>Ajusta la frecuencia de riego según la temperatura. Riega temprano por la mañana o al atardecer.</p>
            </div>
            <div class="flavor-consejo-card">
                <span class="flavor-consejo-icono">🐛</span>
                <h4>Plagas</h4>
                <p>Revisa las hojas regularmente en busca de plagas. Usa métodos ecológicos de control.</p>
            </div>
            <div class="flavor-consejo-card">
                <span class="flavor-consejo-icono">🌱</span>
                <h4>Abonado</h4>
                <p>Añade compost o humus de lombriz para nutrir el suelo antes de nuevas siembras.</p>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-calendario-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-calendario-header {
    text-align: center;
    margin-bottom: 30px;
}

.flavor-calendario-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0 0 10px 0;
}

.flavor-calendario-descripcion {
    color: #666;
    margin: 0 0 15px 0;
}

.flavor-calendario-zona {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #e8f5e9;
    border-radius: 20px;
    font-size: 0.9rem;
    color: #2e7d32;
}

.flavor-calendario-navegacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.flavor-btn-nav {
    padding: 10px 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-btn-nav:hover {
    border-color: #4CAF50;
    color: #4CAF50;
}

.flavor-mes-actual {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-selector-mes {
    padding: 12px 20px;
    font-size: 1.25rem;
    font-weight: 600;
    border: 2px solid #4CAF50;
    border-radius: 8px;
    background: white;
    color: #2e7d32;
    cursor: pointer;
}

.flavor-anio-actual {
    font-size: 1.25rem;
    color: #666;
}

.flavor-calendario-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-resumen-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.flavor-resumen-siembra {
    border-left: 4px solid #4CAF50;
}

.flavor-resumen-cosecha {
    border-left: 4px solid #FF9800;
}

.flavor-resumen-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.flavor-resumen-icono {
    font-size: 1.5rem;
}

.flavor-resumen-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0;
}

.flavor-resumen-cultivos {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-cultivo-chip {
    padding: 6px 12px;
    background: #f5f5f5;
    border-radius: 20px;
    font-size: 0.875rem;
}

.flavor-sin-cultivos {
    color: #999;
    font-style: italic;
    margin: 0;
}

.flavor-calendario-leyenda {
    background: #fafafa;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-leyenda-titulo {
    font-size: 0.875rem;
    color: #666;
    margin: 0 0 10px 0;
}

.flavor-leyenda-items {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 10px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}

.flavor-leyenda-celda {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.flavor-celda-siembra {
    background: #4CAF50;
}

.flavor-celda-trasplante {
    background: #2196F3;
}

.flavor-celda-cosecha {
    background: #FF9800;
}

.flavor-leyenda-tipos {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-tipo-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    color: white;
}

.flavor-calendario-tabla-wrapper {
    overflow-x: auto;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.flavor-calendario-tabla {
    width: 100%;
    border-collapse: collapse;
    background: white;
    min-width: 900px;
}

.flavor-calendario-tabla th,
.flavor-calendario-tabla td {
    padding: 10px 8px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.flavor-th-cultivo {
    text-align: left;
    padding-left: 15px !important;
    min-width: 180px;
    background: #f5f5f5;
}

.flavor-th-mes {
    width: 50px;
    font-size: 0.8rem;
    color: #666;
    background: #f5f5f5;
}

.flavor-th-info {
    width: 50px;
    background: #f5f5f5;
}

.flavor-mes-activo {
    background: #e8f5e9 !important;
    color: #2e7d32 !important;
    font-weight: 600;
}

.flavor-td-cultivo {
    text-align: left;
    padding-left: 15px !important;
}

.flavor-cultivo-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-cultivo-icono {
    font-size: 1.5rem;
}

.flavor-cultivo-datos {
    display: flex;
    flex-direction: column;
}

.flavor-cultivo-nombre {
    font-weight: 500;
    color: #333;
}

.flavor-cultivo-tipo {
    font-size: 0.75rem;
}

.flavor-td-mes {
    position: relative;
}

.flavor-celda-contenido {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    justify-content: center;
}

.flavor-marca {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 700;
    color: white;
}

.flavor-marca-siembra {
    background: #4CAF50;
}

.flavor-marca-trasplante {
    background: #2196F3;
}

.flavor-marca-cosecha {
    background: #FF9800;
}

.flavor-btn-info {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.2s;
}

.flavor-btn-info:hover {
    background: #e8f5e9;
}

.flavor-calendario-filtros {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
    padding: 15px;
    background: #fafafa;
    border-radius: 8px;
}

.flavor-filtros-label {
    font-weight: 500;
    color: #333;
}

.flavor-filtros-botones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-filtro-tipo {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.flavor-filtro-tipo:hover {
    border-color: #4CAF50;
}

.flavor-filtro-tipo.active {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-cultivos-destacados {
    margin-bottom: 40px;
}

.flavor-destacados-titulo {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 20px;
}

.flavor-destacados-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.flavor-cultivo-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}

.flavor-cultivo-card:hover {
    transform: translateY(-4px);
}

.flavor-cultivo-card-header {
    padding: 20px;
    color: white;
    text-align: center;
}

.flavor-cultivo-card-icono {
    font-size: 3rem;
    display: block;
    margin-bottom: 10px;
}

.flavor-cultivo-card-nombre {
    margin: 0;
    font-size: 1.25rem;
}

.flavor-cultivo-card-body {
    padding: 15px;
}

.flavor-cultivo-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.flavor-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.875rem;
    color: #666;
}

.flavor-cultivo-consejos {
    font-size: 0.875rem;
    color: #555;
    margin: 0;
    line-height: 1.5;
}

.flavor-cultivo-card-footer {
    padding: 15px;
    border-top: 1px solid #eee;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}

.flavor-btn-sm {
    padding: 8px 12px;
}

.flavor-btn-outline {
    background: transparent;
    border: 1px solid #4CAF50;
    color: #4CAF50;
}

.flavor-btn-outline:hover {
    background: #e8f5e9;
}

.flavor-consejos-mes {
    background: #e8f5e9;
    padding: 30px;
    border-radius: 12px;
}

.flavor-consejos-titulo {
    text-align: center;
    font-size: 1.5rem;
    color: #2e7d32;
    margin: 0 0 20px 0;
}

.flavor-consejos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.flavor-consejo-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.flavor-consejo-icono {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
}

.flavor-consejo-card h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.flavor-consejo-card p {
    margin: 0;
    font-size: 0.875rem;
    color: #666;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-calendario-container {
        padding: 15px;
    }

    .flavor-calendario-titulo {
        font-size: 1.5rem;
    }

    .flavor-calendario-navegacion {
        flex-direction: column;
    }

    .flavor-btn-nav {
        width: 100%;
        justify-content: center;
    }

    .flavor-calendario-filtros {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-filtros-botones {
        width: 100%;
    }

    .flavor-filtro-tipo {
        flex: 1;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .flavor-resumen-card {
        padding: 15px;
    }

    .flavor-leyenda-items {
        flex-direction: column;
        gap: 10px;
    }

    .flavor-consejos-mes {
        padding: 20px 15px;
    }
}
</style>
