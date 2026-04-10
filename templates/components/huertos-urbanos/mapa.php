<?php
/**
 * Template: Mapa interactivo de huertos urbanos
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Huertos_Urbanos
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_mapa = isset($args['titulo']) ? $args['titulo'] : 'Mapa de Huertos Urbanos';
$centro_latitud = isset($args['centro_latitud']) ? $args['centro_latitud'] : 40.4168;
$centro_longitud = isset($args['centro_longitud']) ? $args['centro_longitud'] : -3.7038;
$zoom_inicial = isset($args['zoom']) ? $args['zoom'] : 13;
$mostrar_leyenda = isset($args['mostrar_leyenda']) ? $args['mostrar_leyenda'] : true;
$altura_mapa = isset($args['altura']) ? $args['altura'] : '500px';

// Datos de demostración de huertos urbanos
$huertos_demo = isset($args['huertos']) ? $args['huertos'] : [
    [
        'id' => 1,
        'nombre' => 'Huerto Comunitario La Esperanza',
        'latitud' => 40.4200,
        'longitud' => -3.7100,
        'direccion' => 'Calle de la Huerta, 15',
        'parcelas_totales' => 40,
        'parcelas_disponibles' => 8,
        'estado' => 'activo',
        'tipo' => 'comunitario',
        'horario' => '8:00 - 20:00',
        'telefono' => '91 123 45 67'
    ],
    [
        'id' => 2,
        'nombre' => 'Huerto Escolar San Miguel',
        'latitud' => 40.4150,
        'longitud' => -3.6980,
        'direccion' => 'Av. del Jardín, 42',
        'parcelas_totales' => 25,
        'parcelas_disponibles' => 3,
        'estado' => 'activo',
        'tipo' => 'escolar',
        'horario' => '9:00 - 18:00',
        'telefono' => '91 234 56 78'
    ],
    [
        'id' => 3,
        'nombre' => 'Huerto Vecinal Los Pinos',
        'latitud' => 40.4180,
        'longitud' => -3.7200,
        'direccion' => 'Plaza del Olivo, 8',
        'parcelas_totales' => 30,
        'parcelas_disponibles' => 0,
        'estado' => 'completo',
        'tipo' => 'vecinal',
        'horario' => '7:00 - 21:00',
        'telefono' => '91 345 67 89'
    ],
    [
        'id' => 4,
        'nombre' => 'Huerto Terapéutico Bienestar',
        'latitud' => 40.4120,
        'longitud' => -3.6900,
        'direccion' => 'Calle Salud, 22',
        'parcelas_totales' => 15,
        'parcelas_disponibles' => 5,
        'estado' => 'activo',
        'tipo' => 'terapeutico',
        'horario' => '10:00 - 17:00',
        'telefono' => '91 456 78 90'
    ],
    [
        'id' => 5,
        'nombre' => 'Huerto Municipal Centro',
        'latitud' => 40.4168,
        'longitud' => -3.7038,
        'direccion' => 'Paseo Verde, 1',
        'parcelas_totales' => 60,
        'parcelas_disponibles' => 12,
        'estado' => 'activo',
        'tipo' => 'municipal',
        'horario' => '6:00 - 22:00',
        'telefono' => '91 567 89 01'
    ]
];

// Función para obtener el color del marcador según el tipo
function obtener_color_marcador_huerto($tipo) {
    $colores = [
        'comunitario' => '#4CAF50',
        'escolar' => '#2196F3',
        'vecinal' => '#FF9800',
        'terapeutico' => '#9C27B0',
        'municipal' => '#F44336'
    ];
    return isset($colores[$tipo]) ? $colores[$tipo] : '#757575';
}

// Función para obtener el icono según el estado
function obtener_icono_estado_huerto($estado) {
    return $estado === 'activo' ? '🌱' : '⏳';
}
?>

<div class="flavor-mapa-huertos-container">
    <header class="flavor-mapa-header">
        <h2 class="flavor-mapa-titulo"><?php echo esc_html($titulo_mapa); ?></h2>
        <p class="flavor-mapa-descripcion">Encuentra huertos urbanos cerca de ti y descubre parcelas disponibles</p>
    </header>

    <!-- Filtros del mapa -->
    <div class="flavor-mapa-filtros">
        <div class="flavor-filtro-grupo">
            <label for="flavor-filtro-tipo" class="flavor-filtro-label">Tipo de huerto:</label>
            <select id="flavor-filtro-tipo" class="flavor-filtro-select">
                <option value="todos">Todos los tipos</option>
                <option value="comunitario">Comunitario</option>
                <option value="escolar">Escolar</option>
                <option value="vecinal">Vecinal</option>
                <option value="terapeutico">Terapéutico</option>
                <option value="municipal">Municipal</option>
            </select>
        </div>
        <div class="flavor-filtro-grupo">
            <label for="flavor-filtro-disponibilidad" class="flavor-filtro-label">Disponibilidad:</label>
            <select id="flavor-filtro-disponibilidad" class="flavor-filtro-select">
                <option value="todos">Todos</option>
                <option value="disponible">Con parcelas disponibles</option>
                <option value="completo">Completo</option>
            </select>
        </div>
        <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-filtrar">
            <span class="flavor-btn-icon">🔍</span> Filtrar
        </button>
    </div>

    <!-- Contenedor del mapa -->
    <div class="flavor-mapa-wrapper">
        <div id="flavor-mapa-huertos" class="flavor-mapa" style="height: <?php echo esc_attr($altura_mapa); ?>;">
            <!-- El mapa se cargará aquí mediante JavaScript -->
            <div class="flavor-mapa-placeholder">
                <div class="flavor-mapa-loading">
                    <span class="flavor-spinner"></span>
                    <p>Cargando mapa...</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($mostrar_leyenda) : ?>
    <!-- Leyenda del mapa -->
    <div class="flavor-mapa-leyenda">
        <h4 class="flavor-leyenda-titulo">Leyenda</h4>
        <ul class="flavor-leyenda-lista">
            <li class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: #4CAF50;"></span>
                <span class="flavor-leyenda-texto">Comunitario</span>
            </li>
            <li class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: #2196F3;"></span>
                <span class="flavor-leyenda-texto">Escolar</span>
            </li>
            <li class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: #FF9800;"></span>
                <span class="flavor-leyenda-texto">Vecinal</span>
            </li>
            <li class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: #9C27B0;"></span>
                <span class="flavor-leyenda-texto">Terapéutico</span>
            </li>
            <li class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: #F44336;"></span>
                <span class="flavor-leyenda-texto">Municipal</span>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Lista de huertos -->
    <div class="flavor-huertos-lista">
        <h3 class="flavor-lista-titulo">Huertos disponibles</h3>
        <div class="flavor-huertos-grid">
            <?php foreach ($huertos_demo as $huerto) :
                $color_tipo = obtener_color_marcador_huerto($huerto['tipo']);
                $icono_estado = obtener_icono_estado_huerto($huerto['estado']);
                $porcentaje_ocupacion = (($huerto['parcelas_totales'] - $huerto['parcelas_disponibles']) / $huerto['parcelas_totales']) * 100;
            ?>
            <article class="flavor-huerto-card" data-huerto-id="<?php echo esc_attr($huerto['id']); ?>" data-tipo="<?php echo esc_attr($huerto['tipo']); ?>">
                <div class="flavor-huerto-card-header" style="border-left-color: <?php echo esc_attr($color_tipo); ?>;">
                    <h4 class="flavor-huerto-nombre"><?php echo esc_html($huerto['nombre']); ?></h4>
                    <span class="flavor-huerto-tipo-badge" style="background-color: <?php echo esc_attr($color_tipo); ?>;">
                        <?php echo esc_html(ucfirst($huerto['tipo'])); ?>
                    </span>
                </div>
                <div class="flavor-huerto-card-body">
                    <p class="flavor-huerto-direccion">
                        <span class="flavor-icon">📍</span>
                        <?php echo esc_html($huerto['direccion']); ?>
                    </p>
                    <p class="flavor-huerto-horario">
                        <span class="flavor-icon">🕐</span>
                        <?php echo esc_html($huerto['horario']); ?>
                    </p>
                    <p class="flavor-huerto-telefono">
                        <span class="flavor-icon">📞</span>
                        <?php echo esc_html($huerto['telefono']); ?>
                    </p>
                    <div class="flavor-huerto-parcelas">
                        <div class="flavor-parcelas-info">
                            <span class="flavor-parcelas-disponibles">
                                <?php echo esc_html($huerto['parcelas_disponibles']); ?> parcelas disponibles
                            </span>
                            <span class="flavor-parcelas-total">
                                de <?php echo esc_html($huerto['parcelas_totales']); ?> totales
                            </span>
                        </div>
                        <div class="flavor-parcelas-barra">
                            <div class="flavor-parcelas-progreso" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%;"></div>
                        </div>
                    </div>
                </div>
                <div class="flavor-huerto-card-footer">
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-ver-mapa" data-lat="<?php echo esc_attr($huerto['latitud']); ?>" data-lng="<?php echo esc_attr($huerto['longitud']); ?>">
                        <span class="flavor-btn-icon">🗺️</span> Ver en mapa
                    </button>
                    <?php if ($huerto['parcelas_disponibles'] > 0) : ?>
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-solicitar">
                        <span class="flavor-btn-icon">✋</span> Solicitar parcela
                    </button>
                    <?php else : ?>
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-disabled" disabled>
                        <span class="flavor-btn-icon">⏳</span> Lista de espera
                    </button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Datos para JavaScript -->
    <script type="application/json" id="flavor-huertos-data">
        <?php echo wp_json_encode([
            'centro' => ['lat' => $centro_latitud, 'lng' => $centro_longitud],
            'zoom' => $zoom_inicial,
            'huertos' => $huertos_demo
        ]); ?>
    </script>
</div>

<style>
.flavor-mapa-huertos-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-mapa-header {
    text-align: center;
    margin-bottom: 30px;
}

.flavor-mapa-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin-bottom: 10px;
}

.flavor-mapa-descripcion {
    color: #666;
    font-size: 1.1rem;
}

.flavor-mapa-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
    margin-bottom: 20px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.flavor-filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.flavor-filtro-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #333;
}

.flavor-filtro-select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    min-width: 180px;
    background: white;
}

.flavor-mapa-wrapper {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.flavor-mapa {
    width: 100%;
    background: #e8f5e9;
    position: relative;
}

.flavor-mapa-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.flavor-mapa-loading {
    text-align: center;
    color: #2e7d32;
}

.flavor-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #c8e6c9;
    border-top-color: #2e7d32;
    border-radius: 50%;
    animation: flavor-spin 1s linear infinite;
}

@keyframes flavor-spin {
    to { transform: rotate(360deg); }
}

.flavor-mapa-leyenda {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.flavor-leyenda-titulo {
    margin: 0 0 10px 0;
    font-size: 1rem;
    color: #333;
}

.flavor-leyenda-lista {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-leyenda-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.flavor-leyenda-texto {
    font-size: 0.875rem;
    color: #555;
}

.flavor-lista-titulo {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 20px;
}

.flavor-huertos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.flavor-huerto-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-huerto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.flavor-huerto-card-header {
    padding: 15px;
    background: #fafafa;
    border-left: 4px solid;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-huerto-nombre {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.flavor-huerto-tipo-badge {
    padding: 4px 10px;
    border-radius: 20px;
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.flavor-huerto-card-body {
    padding: 15px;
}

.flavor-huerto-card-body p {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #555;
    font-size: 0.9rem;
}

.flavor-icon {
    font-size: 1.1rem;
}

.flavor-huerto-parcelas {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.flavor-parcelas-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.875rem;
}

.flavor-parcelas-disponibles {
    color: #2e7d32;
    font-weight: 600;
}

.flavor-parcelas-total {
    color: #999;
}

.flavor-parcelas-barra {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-parcelas-progreso {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #81C784);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.flavor-huerto-card-footer {
    padding: 15px;
    background: #fafafa;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-sm {
    padding: 8px 12px;
    font-size: 0.8rem;
}

.flavor-btn-primary {
    background: #4CAF50;
    color: white;
}

.flavor-btn-primary:hover {
    background: #388E3C;
}

.flavor-btn-secondary {
    background: #607D8B;
    color: white;
}

.flavor-btn-secondary:hover {
    background: #455A64;
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

.flavor-btn-icon {
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-mapa-huertos-container {
        padding: 15px;
    }

    .flavor-mapa-titulo {
        font-size: 1.5rem;
    }

    .flavor-mapa-filtros {
        flex-direction: column;
    }

    .flavor-filtro-select {
        width: 100%;
    }

    .flavor-huertos-grid {
        grid-template-columns: 1fr;
    }

    .flavor-huerto-card-footer {
        flex-direction: column;
    }

    .flavor-btn {
        width: 100%;
        justify-content: center;
    }

    .flavor-leyenda-lista {
        flex-direction: column;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .flavor-mapa {
        height: 300px !important;
    }

    .flavor-huerto-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
