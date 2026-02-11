<?php
/**
 * Template: Grid de parcelas disponibles para solicitar
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Huertos_Urbanos
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? $args['titulo'] : 'Parcelas Disponibles';
$huerto_nombre = isset($args['huerto_nombre']) ? $args['huerto_nombre'] : 'Huerto Comunitario La Esperanza';
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$columnas_grid = isset($args['columnas']) ? $args['columnas'] : 4;

// Datos de demostración de parcelas
$parcelas_demo = isset($args['parcelas']) ? $args['parcelas'] : [
    [
        'id' => 'A1',
        'numero' => 'A-01',
        'tamano' => 25,
        'unidad' => 'm²',
        'estado' => 'disponible',
        'orientacion' => 'norte',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 15,
        'caracteristicas' => ['Suelo fértil', 'Buena exposición solar']
    ],
    [
        'id' => 'A2',
        'numero' => 'A-02',
        'tamano' => 30,
        'unidad' => 'm²',
        'estado' => 'ocupada',
        'orientacion' => 'norte',
        'tiene_riego' => true,
        'tiene_caseta' => true,
        'precio_mensual' => 20,
        'hortelano' => 'María García',
        'caracteristicas' => ['Con caseta', 'Riego automático']
    ],
    [
        'id' => 'A3',
        'numero' => 'A-03',
        'tamano' => 25,
        'unidad' => 'm²',
        'estado' => 'disponible',
        'orientacion' => 'este',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 15,
        'caracteristicas' => ['Sol matutino', 'Cerca de la fuente']
    ],
    [
        'id' => 'A4',
        'numero' => 'A-04',
        'tamano' => 20,
        'unidad' => 'm²',
        'estado' => 'reservada',
        'orientacion' => 'este',
        'tiene_riego' => false,
        'tiene_caseta' => false,
        'precio_mensual' => 12,
        'caracteristicas' => ['Ideal principiantes', 'Tamaño compacto']
    ],
    [
        'id' => 'B1',
        'numero' => 'B-01',
        'tamano' => 35,
        'unidad' => 'm²',
        'estado' => 'ocupada',
        'orientacion' => 'sur',
        'tiene_riego' => true,
        'tiene_caseta' => true,
        'precio_mensual' => 25,
        'hortelano' => 'Carlos López',
        'caracteristicas' => ['Parcela grande', 'Máxima exposición solar']
    ],
    [
        'id' => 'B2',
        'numero' => 'B-02',
        'tamano' => 35,
        'unidad' => 'm²',
        'estado' => 'disponible',
        'orientacion' => 'sur',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 22,
        'caracteristicas' => ['Parcela grande', 'Ideal frutales']
    ],
    [
        'id' => 'B3',
        'numero' => 'B-03',
        'tamano' => 25,
        'unidad' => 'm²',
        'estado' => 'mantenimiento',
        'orientacion' => 'sur',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 15,
        'caracteristicas' => ['En preparación', 'Disponible próximamente']
    ],
    [
        'id' => 'B4',
        'numero' => 'B-04',
        'tamano' => 30,
        'unidad' => 'm²',
        'estado' => 'ocupada',
        'orientacion' => 'oeste',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 18,
        'hortelano' => 'Ana Martínez',
        'caracteristicas' => ['Sol vespertino', 'Protegida del viento']
    ],
    [
        'id' => 'C1',
        'numero' => 'C-01',
        'tamano' => 40,
        'unidad' => 'm²',
        'estado' => 'disponible',
        'orientacion' => 'oeste',
        'tiene_riego' => true,
        'tiene_caseta' => true,
        'precio_mensual' => 30,
        'caracteristicas' => ['Parcela premium', 'Caseta con herramientas']
    ],
    [
        'id' => 'C2',
        'numero' => 'C-02',
        'tamano' => 25,
        'unidad' => 'm²',
        'estado' => 'lista_espera',
        'orientacion' => 'norte',
        'tiene_riego' => false,
        'tiene_caseta' => false,
        'precio_mensual' => 14,
        'personas_espera' => 3,
        'caracteristicas' => ['Económica', 'Buena ubicación']
    ],
    [
        'id' => 'C3',
        'numero' => 'C-03',
        'tamano' => 30,
        'unidad' => 'm²',
        'estado' => 'disponible',
        'orientacion' => 'este',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 18,
        'caracteristicas' => ['Esquinera', 'Mayor privacidad']
    ],
    [
        'id' => 'C4',
        'numero' => 'C-04',
        'tamano' => 25,
        'unidad' => 'm²',
        'estado' => 'ocupada',
        'orientacion' => 'sur',
        'tiene_riego' => true,
        'tiene_caseta' => false,
        'precio_mensual' => 16,
        'hortelano' => 'Pedro Sánchez',
        'caracteristicas' => ['Céntrica', 'Fácil acceso']
    ]
];

// Función para obtener el color según el estado
function obtener_color_estado_parcela($estado) {
    $colores = [
        'disponible' => '#4CAF50',
        'ocupada' => '#9E9E9E',
        'reservada' => '#FF9800',
        'mantenimiento' => '#2196F3',
        'lista_espera' => '#9C27B0'
    ];
    return isset($colores[$estado]) ? $colores[$estado] : '#757575';
}

// Función para obtener el texto del estado
function obtener_texto_estado_parcela($estado) {
    $textos = [
        'disponible' => 'Disponible',
        'ocupada' => 'Ocupada',
        'reservada' => 'Reservada',
        'mantenimiento' => 'En mantenimiento',
        'lista_espera' => 'Lista de espera'
    ];
    return isset($textos[$estado]) ? $textos[$estado] : 'Desconocido';
}

// Función para obtener el icono de orientación
function obtener_icono_orientacion($orientacion) {
    $iconos = [
        'norte' => '⬆️',
        'sur' => '⬇️',
        'este' => '➡️',
        'oeste' => '⬅️'
    ];
    return isset($iconos[$orientacion]) ? $iconos[$orientacion] : '📍';
}

// Calcular estadísticas
$total_parcelas = count($parcelas_demo);
$parcelas_disponibles = count(array_filter($parcelas_demo, fn($parcela) => $parcela['estado'] === 'disponible'));
$parcelas_ocupadas = count(array_filter($parcelas_demo, fn($parcela) => $parcela['estado'] === 'ocupada'));
?>

<div class="flavor-parcelas-container">
    <header class="flavor-parcelas-header">
        <div class="flavor-parcelas-header-content">
            <h2 class="flavor-parcelas-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-parcelas-subtitulo"><?php echo esc_html($huerto_nombre); ?></p>
        </div>
        <div class="flavor-parcelas-estadisticas">
            <div class="flavor-estadistica">
                <span class="flavor-estadistica-numero"><?php echo esc_html($total_parcelas); ?></span>
                <span class="flavor-estadistica-texto">Total</span>
            </div>
            <div class="flavor-estadistica flavor-estadistica-disponible">
                <span class="flavor-estadistica-numero"><?php echo esc_html($parcelas_disponibles); ?></span>
                <span class="flavor-estadistica-texto">Disponibles</span>
            </div>
            <div class="flavor-estadistica">
                <span class="flavor-estadistica-numero"><?php echo esc_html($parcelas_ocupadas); ?></span>
                <span class="flavor-estadistica-texto">Ocupadas</span>
            </div>
        </div>
    </header>

    <?php if ($mostrar_filtros) : ?>
    <!-- Filtros -->
    <div class="flavor-parcelas-filtros">
        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label">Estado:</label>
            <div class="flavor-filtros-botones">
                <button type="button" class="flavor-filtro-btn active" data-filtro="todos">Todos</button>
                <button type="button" class="flavor-filtro-btn" data-filtro="disponible">Disponibles</button>
                <button type="button" class="flavor-filtro-btn" data-filtro="ocupada">Ocupadas</button>
                <button type="button" class="flavor-filtro-btn" data-filtro="reservada">Reservadas</button>
            </div>
        </div>
        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label">Tamaño:</label>
            <select class="flavor-filtro-select" id="flavor-filtro-tamano">
                <option value="todos">Todos los tamaños</option>
                <option value="pequeno">Pequeño (< 25 m²)</option>
                <option value="mediano">Mediano (25-30 m²)</option>
                <option value="grande">Grande (> 30 m²)</option>
            </select>
        </div>
        <div class="flavor-filtros-grupo">
            <label class="flavor-filtro-label">Precio máx:</label>
            <input type="range" class="flavor-filtro-range" id="flavor-filtro-precio" min="10" max="35" value="35">
            <span class="flavor-filtro-precio-valor">35€/mes</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Leyenda de estados -->
    <div class="flavor-parcelas-leyenda">
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-dot" style="background: #4CAF50;"></span> Disponible
        </span>
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-dot" style="background: #9E9E9E;"></span> Ocupada
        </span>
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-dot" style="background: #FF9800;"></span> Reservada
        </span>
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-dot" style="background: #2196F3;"></span> Mantenimiento
        </span>
        <span class="flavor-leyenda-item">
            <span class="flavor-leyenda-dot" style="background: #9C27B0;"></span> Lista de espera
        </span>
    </div>

    <!-- Grid de parcelas -->
    <div class="flavor-parcelas-grid" style="--columnas: <?php echo esc_attr($columnas_grid); ?>;">
        <?php foreach ($parcelas_demo as $parcela) :
            $color_estado = obtener_color_estado_parcela($parcela['estado']);
            $texto_estado = obtener_texto_estado_parcela($parcela['estado']);
            $icono_orientacion = obtener_icono_orientacion($parcela['orientacion']);
            $es_disponible = $parcela['estado'] === 'disponible';
        ?>
        <article class="flavor-parcela-card <?php echo $es_disponible ? 'flavor-parcela-disponible' : ''; ?>"
                 data-estado="<?php echo esc_attr($parcela['estado']); ?>"
                 data-tamano="<?php echo esc_attr($parcela['tamano']); ?>"
                 data-precio="<?php echo esc_attr($parcela['precio_mensual']); ?>">

            <div class="flavor-parcela-header" style="background-color: <?php echo esc_attr($color_estado); ?>;">
                <span class="flavor-parcela-numero"><?php echo esc_html($parcela['numero']); ?></span>
                <span class="flavor-parcela-estado-badge"><?php echo esc_html($texto_estado); ?></span>
            </div>

            <div class="flavor-parcela-body">
                <div class="flavor-parcela-info-principal">
                    <div class="flavor-parcela-tamano">
                        <span class="flavor-parcela-tamano-valor"><?php echo esc_html($parcela['tamano']); ?></span>
                        <span class="flavor-parcela-tamano-unidad"><?php echo esc_html($parcela['unidad']); ?></span>
                    </div>
                    <div class="flavor-parcela-precio">
                        <span class="flavor-parcela-precio-valor"><?php echo esc_html($parcela['precio_mensual']); ?>€</span>
                        <span class="flavor-parcela-precio-periodo">/mes</span>
                    </div>
                </div>

                <div class="flavor-parcela-detalles">
                    <div class="flavor-parcela-detalle">
                        <span class="flavor-detalle-icono"><?php echo $icono_orientacion; ?></span>
                        <span class="flavor-detalle-texto"><?php echo esc_html(ucfirst($parcela['orientacion'])); ?></span>
                    </div>
                    <div class="flavor-parcela-detalle">
                        <span class="flavor-detalle-icono"><?php echo $parcela['tiene_riego'] ? '💧' : '🚫'; ?></span>
                        <span class="flavor-detalle-texto"><?php echo $parcela['tiene_riego'] ? 'Con riego' : 'Sin riego'; ?></span>
                    </div>
                    <div class="flavor-parcela-detalle">
                        <span class="flavor-detalle-icono"><?php echo $parcela['tiene_caseta'] ? '🏠' : ''; ?></span>
                        <span class="flavor-detalle-texto"><?php echo $parcela['tiene_caseta'] ? 'Con caseta' : ''; ?></span>
                    </div>
                </div>

                <?php if (!empty($parcela['caracteristicas'])) : ?>
                <div class="flavor-parcela-caracteristicas">
                    <?php foreach ($parcela['caracteristicas'] as $caracteristica) : ?>
                    <span class="flavor-caracteristica-tag"><?php echo esc_html($caracteristica); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($parcela['estado'] === 'ocupada' && isset($parcela['hortelano'])) : ?>
                <div class="flavor-parcela-hortelano">
                    <span class="flavor-hortelano-icono">👤</span>
                    <span class="flavor-hortelano-nombre"><?php echo esc_html($parcela['hortelano']); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($parcela['estado'] === 'lista_espera' && isset($parcela['personas_espera'])) : ?>
                <div class="flavor-parcela-espera">
                    <span class="flavor-espera-icono">👥</span>
                    <span class="flavor-espera-texto"><?php echo esc_html($parcela['personas_espera']); ?> personas en espera</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="flavor-parcela-footer">
                <?php if ($es_disponible) : ?>
                <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-solicitar-parcela" data-parcela="<?php echo esc_attr($parcela['id']); ?>">
                    <span class="flavor-btn-icono">✋</span>
                    Solicitar esta parcela
                </button>
                <?php elseif ($parcela['estado'] === 'lista_espera') : ?>
                <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-unirse-espera" data-parcela="<?php echo esc_attr($parcela['id']); ?>">
                    <span class="flavor-btn-icono">📝</span>
                    Unirse a la lista
                </button>
                <?php elseif ($parcela['estado'] === 'reservada') : ?>
                <span class="flavor-parcela-info-reserva">
                    <span class="flavor-info-icono">ℹ️</span>
                    Reservada temporalmente
                </span>
                <?php elseif ($parcela['estado'] === 'mantenimiento') : ?>
                <span class="flavor-parcela-info-mantenimiento">
                    <span class="flavor-info-icono">🔧</span>
                    Disponible próximamente
                </span>
                <?php else : ?>
                <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-ver-perfil" data-parcela="<?php echo esc_attr($parcela['id']); ?>">
                    <span class="flavor-btn-icono">👁️</span>
                    Ver cultivos actuales
                </button>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Información adicional -->
    <div class="flavor-parcelas-info-adicional">
        <div class="flavor-info-card">
            <h4 class="flavor-info-titulo">📋 ¿Cómo solicitar una parcela?</h4>
            <ol class="flavor-info-lista">
                <li>Selecciona una parcela disponible</li>
                <li>Rellena el formulario de solicitud</li>
                <li>Espera la confirmación (máx. 5 días)</li>
                <li>Firma el contrato y comienza a cultivar</li>
            </ol>
        </div>
        <div class="flavor-info-card">
            <h4 class="flavor-info-titulo">📞 ¿Necesitas ayuda?</h4>
            <p class="flavor-info-texto">Contacta con la coordinación del huerto:</p>
            <p class="flavor-info-contacto">
                <span>📧 huertos@ejemplo.com</span>
                <span>📱 91 123 45 67</span>
            </p>
        </div>
    </div>
</div>

<style>
.flavor-parcelas-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-parcelas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e8f5e9;
}

.flavor-parcelas-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0;
}

.flavor-parcelas-subtitulo {
    color: #666;
    margin: 5px 0 0 0;
}

.flavor-parcelas-estadisticas {
    display: flex;
    gap: 20px;
}

.flavor-estadistica {
    text-align: center;
    padding: 10px 20px;
    background: #f5f5f5;
    border-radius: 8px;
}

.flavor-estadistica-disponible {
    background: #e8f5e9;
}

.flavor-estadistica-numero {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: #333;
}

.flavor-estadistica-disponible .flavor-estadistica-numero {
    color: #2e7d32;
}

.flavor-estadistica-texto {
    font-size: 0.875rem;
    color: #666;
}

.flavor-parcelas-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-end;
    padding: 20px;
    background: #fafafa;
    border-radius: 12px;
    margin-bottom: 20px;
}

.flavor-filtros-grupo {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-filtro-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #333;
}

.flavor-filtros-botones {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.flavor-filtro-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-filtro-btn:hover {
    border-color: #4CAF50;
    color: #4CAF50;
}

.flavor-filtro-btn.active {
    background: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.flavor-filtro-select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    min-width: 180px;
}

.flavor-filtro-range {
    width: 150px;
    cursor: pointer;
}

.flavor-filtro-precio-valor {
    font-weight: 600;
    color: #4CAF50;
    margin-left: 10px;
}

.flavor-parcelas-leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: #555;
}

.flavor-leyenda-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.flavor-parcelas-grid {
    display: grid;
    grid-template-columns: repeat(var(--columnas, 4), 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.flavor-parcela-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-parcela-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.flavor-parcela-disponible {
    border: 2px solid #4CAF50;
}

.flavor-parcela-header {
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.flavor-parcela-numero {
    font-size: 1.25rem;
    font-weight: 700;
}

.flavor-parcela-estado-badge {
    font-size: 0.7rem;
    text-transform: uppercase;
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 4px;
}

.flavor-parcela-body {
    padding: 15px;
}

.flavor-parcela-info-principal {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.flavor-parcela-tamano {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.flavor-parcela-tamano-valor {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
}

.flavor-parcela-tamano-unidad {
    font-size: 1rem;
    color: #666;
}

.flavor-parcela-precio {
    text-align: right;
}

.flavor-parcela-precio-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #4CAF50;
}

.flavor-parcela-precio-periodo {
    font-size: 0.875rem;
    color: #999;
}

.flavor-parcela-detalles {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}

.flavor-parcela-detalle {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    color: #666;
}

.flavor-detalle-icono {
    font-size: 1rem;
}

.flavor-parcela-caracteristicas {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.flavor-caracteristica-tag {
    padding: 4px 8px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 4px;
    font-size: 0.75rem;
}

.flavor-parcela-hortelano,
.flavor-parcela-espera {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #f5f5f5;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #555;
}

.flavor-parcela-footer {
    padding: 15px;
    background: #fafafa;
    border-top: 1px solid #eee;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-primary {
    background: #4CAF50;
    color: white;
}

.flavor-btn-primary:hover {
    background: #388E3C;
}

.flavor-btn-secondary {
    background: #9C27B0;
    color: white;
}

.flavor-btn-secondary:hover {
    background: #7B1FA2;
}

.flavor-btn-outline {
    background: transparent;
    border: 1px solid #999;
    color: #666;
}

.flavor-btn-outline:hover {
    border-color: #4CAF50;
    color: #4CAF50;
}

.flavor-parcela-info-reserva,
.flavor-parcela-info-mantenimiento {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    font-size: 0.875rem;
    color: #666;
}

.flavor-parcelas-info-adicional {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.flavor-info-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.flavor-info-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 15px 0;
}

.flavor-info-lista {
    margin: 0;
    padding-left: 20px;
    color: #555;
}

.flavor-info-lista li {
    margin-bottom: 8px;
}

.flavor-info-texto {
    color: #666;
    margin: 0 0 10px 0;
}

.flavor-info-contacto {
    display: flex;
    flex-direction: column;
    gap: 5px;
    color: #4CAF50;
    font-weight: 500;
    margin: 0;
}

/* Responsive */
@media (max-width: 1200px) {
    .flavor-parcelas-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 900px) {
    .flavor-parcelas-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-parcelas-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-parcelas-estadisticas {
        width: 100%;
        justify-content: space-around;
    }

    .flavor-parcelas-filtros {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-filtros-botones {
        justify-content: flex-start;
    }

    .flavor-parcelas-leyenda {
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .flavor-parcelas-container {
        padding: 15px;
    }

    .flavor-parcelas-titulo {
        font-size: 1.5rem;
    }

    .flavor-parcelas-grid {
        grid-template-columns: 1fr;
    }

    .flavor-estadistica {
        padding: 8px 12px;
    }

    .flavor-estadistica-numero {
        font-size: 1.5rem;
    }
}
</style>
