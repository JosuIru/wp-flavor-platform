<?php
/**
 * Template: Mapa interactivo de parkings comunitarios
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Parkings
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Mapa de Parkings Comunitarios';
$altura_mapa = isset($args['altura_mapa']) ? $args['altura_mapa'] : '500px';
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$centro_latitud = isset($args['centro_latitud']) ? $args['centro_latitud'] : 40.4168;
$centro_longitud = isset($args['centro_longitud']) ? $args['centro_longitud'] : -3.7038;
$zoom_inicial = isset($args['zoom_inicial']) ? $args['zoom_inicial'] : 13;

// Datos de demostración de parkings
$parkings_demo = isset($args['parkings']) ? $args['parkings'] : array(
    array(
        'id' => 1,
        'nombre' => 'Parking Residencial Las Flores',
        'direccion' => 'Calle Mayor 45, Madrid',
        'latitud' => 40.4200,
        'longitud' => -3.7050,
        'plazas_totales' => 50,
        'plazas_disponibles' => 12,
        'precio_hora' => 1.50,
        'precio_dia' => 15.00,
        'horario' => '24 horas',
        'tipo' => 'cubierto',
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h'),
        'valoracion' => 4.5
    ),
    array(
        'id' => 2,
        'nombre' => 'Parking Comunitario Sol',
        'direccion' => 'Plaza del Sol 10, Madrid',
        'latitud' => 40.4165,
        'longitud' => -3.7025,
        'plazas_totales' => 30,
        'plazas_disponibles' => 5,
        'precio_hora' => 2.00,
        'precio_dia' => 18.00,
        'horario' => '06:00 - 23:00',
        'tipo' => 'exterior',
        'servicios' => array('vigilancia', 'lavado'),
        'valoracion' => 4.2
    ),
    array(
        'id' => 3,
        'nombre' => 'Parking Vecinal Centro',
        'direccion' => 'Calle Gran Vía 78, Madrid',
        'latitud' => 40.4195,
        'longitud' => -3.7005,
        'plazas_totales' => 80,
        'plazas_disponibles' => 25,
        'precio_hora' => 1.80,
        'precio_dia' => 16.00,
        'horario' => '24 horas',
        'tipo' => 'cubierto',
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h', 'motos'),
        'valoracion' => 4.8
    ),
    array(
        'id' => 4,
        'nombre' => 'Parking Barrio Norte',
        'direccion' => 'Avenida de la Paz 23, Madrid',
        'latitud' => 40.4230,
        'longitud' => -3.6980,
        'plazas_totales' => 40,
        'plazas_disponibles' => 0,
        'precio_hora' => 1.20,
        'precio_dia' => 12.00,
        'horario' => '07:00 - 22:00',
        'tipo' => 'exterior',
        'servicios' => array('motos'),
        'valoracion' => 3.9
    ),
    array(
        'id' => 5,
        'nombre' => 'Parking Comunidad Verde',
        'direccion' => 'Calle del Prado 56, Madrid',
        'latitud' => 40.4140,
        'longitud' => -3.6950,
        'plazas_totales' => 60,
        'plazas_disponibles' => 18,
        'precio_hora' => 1.60,
        'precio_dia' => 14.50,
        'horario' => '24 horas',
        'tipo' => 'cubierto',
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h', 'lavado'),
        'valoracion' => 4.6
    )
);

// Opciones de filtros
$tipos_parking = array(
    'todos' => 'Todos los tipos',
    'cubierto' => 'Cubierto',
    'exterior' => 'Exterior'
);

$servicios_disponibles = array(
    'vigilancia' => 'Vigilancia 24h',
    'carga_electrica' => 'Carga eléctrica',
    'acceso_24h' => 'Acceso 24 horas',
    'lavado' => 'Servicio de lavado',
    'motos' => 'Plazas para motos'
);
?>

<div class="flavor-parkings-mapa-container">
    <?php if (!empty($titulo)) : ?>
        <h2 class="flavor-parkings-mapa-titulo"><?php echo esc_html($titulo); ?></h2>
    <?php endif; ?>

    <?php if ($mostrar_filtros) : ?>
        <div class="flavor-parkings-filtros">
            <div class="flavor-parkings-filtros-row">
                <div class="flavor-parkings-filtro-grupo">
                    <label for="flavor-filtro-tipo" class="flavor-parkings-filtro-label">Tipo de parking</label>
                    <select id="flavor-filtro-tipo" class="flavor-parkings-filtro-select">
                        <?php foreach ($tipos_parking as $valor_tipo => $etiqueta_tipo) : ?>
                            <option value="<?php echo esc_attr($valor_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-parkings-filtro-grupo">
                    <label for="flavor-filtro-disponibilidad" class="flavor-parkings-filtro-label">Disponibilidad</label>
                    <select id="flavor-filtro-disponibilidad" class="flavor-parkings-filtro-select">
                        <option value="todos">Todos</option>
                        <option value="disponible">Con plazas disponibles</option>
                        <option value="completo">Completo</option>
                    </select>
                </div>

                <div class="flavor-parkings-filtro-grupo">
                    <label for="flavor-filtro-precio" class="flavor-parkings-filtro-label">Precio máximo/hora</label>
                    <input type="range" id="flavor-filtro-precio" class="flavor-parkings-filtro-range" min="0" max="5" step="0.25" value="5">
                    <span id="flavor-precio-valor" class="flavor-parkings-precio-valor">5.00€</span>
                </div>

                <div class="flavor-parkings-filtro-grupo flavor-parkings-filtro-servicios">
                    <label class="flavor-parkings-filtro-label">Servicios</label>
                    <div class="flavor-parkings-servicios-checks">
                        <?php foreach ($servicios_disponibles as $clave_servicio => $nombre_servicio) : ?>
                            <label class="flavor-parkings-servicio-check">
                                <input type="checkbox" name="servicios[]" value="<?php echo esc_attr($clave_servicio); ?>">
                                <span><?php echo esc_html($nombre_servicio); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="button" class="flavor-parkings-btn-filtrar">
                <span class="flavor-parkings-btn-icon">&#128269;</span>
                Aplicar filtros
            </button>
        </div>
    <?php endif; ?>

    <div class="flavor-parkings-mapa-wrapper">
        <div id="flavor-parkings-mapa"
             class="flavor-parkings-mapa"
             style="height: <?php echo esc_attr($altura_mapa); ?>;"
             data-centro-lat="<?php echo esc_attr($centro_latitud); ?>"
             data-centro-lng="<?php echo esc_attr($centro_longitud); ?>"
             data-zoom="<?php echo esc_attr($zoom_inicial); ?>">

            <!-- Placeholder visual del mapa -->
            <div class="flavor-parkings-mapa-placeholder">
                <div class="flavor-parkings-mapa-loading">
                    <div class="flavor-parkings-spinner"></div>
                    <p>Cargando mapa...</p>
                </div>
            </div>
        </div>

        <!-- Lista lateral de parkings -->
        <div class="flavor-parkings-lista-lateral">
            <h3 class="flavor-parkings-lista-titulo">Parkings encontrados</h3>
            <div class="flavor-parkings-lista-scroll">
                <?php foreach ($parkings_demo as $parking) :
                    $porcentaje_ocupacion = (($parking['plazas_totales'] - $parking['plazas_disponibles']) / $parking['plazas_totales']) * 100;
                    $clase_disponibilidad = $parking['plazas_disponibles'] > 10 ? 'alta' : ($parking['plazas_disponibles'] > 0 ? 'media' : 'agotada');
                ?>
                    <div class="flavor-parkings-lista-item"
                         data-parking-id="<?php echo esc_attr($parking['id']); ?>"
                         data-lat="<?php echo esc_attr($parking['latitud']); ?>"
                         data-lng="<?php echo esc_attr($parking['longitud']); ?>">

                        <div class="flavor-parkings-item-header">
                            <h4 class="flavor-parkings-item-nombre"><?php echo esc_html($parking['nombre']); ?></h4>
                            <span class="flavor-parkings-item-valoracion">
                                <span class="flavor-parkings-estrella">&#9733;</span>
                                <?php echo number_format($parking['valoracion'], 1); ?>
                            </span>
                        </div>

                        <p class="flavor-parkings-item-direccion">
                            <span class="flavor-parkings-icon-ubicacion">&#128205;</span>
                            <?php echo esc_html($parking['direccion']); ?>
                        </p>

                        <div class="flavor-parkings-item-info">
                            <span class="flavor-parkings-item-tipo flavor-parkings-tipo-<?php echo esc_attr($parking['tipo']); ?>">
                                <?php echo esc_html(ucfirst($parking['tipo'])); ?>
                            </span>
                            <span class="flavor-parkings-item-horario">
                                <span class="flavor-parkings-icon-reloj">&#128339;</span>
                                <?php echo esc_html($parking['horario']); ?>
                            </span>
                        </div>

                        <div class="flavor-parkings-item-disponibilidad flavor-parkings-disponibilidad-<?php echo esc_attr($clase_disponibilidad); ?>">
                            <div class="flavor-parkings-barra-ocupacion">
                                <div class="flavor-parkings-barra-llena" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%;"></div>
                            </div>
                            <span class="flavor-parkings-plazas-texto">
                                <?php echo esc_html($parking['plazas_disponibles']); ?> de <?php echo esc_html($parking['plazas_totales']); ?> plazas libres
                            </span>
                        </div>

                        <div class="flavor-parkings-item-precios">
                            <span class="flavor-parkings-precio-hora"><?php echo number_format($parking['precio_hora'], 2); ?>€/h</span>
                            <span class="flavor-parkings-precio-dia"><?php echo number_format($parking['precio_dia'], 2); ?>€/día</span>
                        </div>

                        <div class="flavor-parkings-item-servicios">
                            <?php foreach ($parking['servicios'] as $servicio) : ?>
                                <span class="flavor-parkings-servicio-badge" title="<?php echo esc_attr($servicios_disponibles[$servicio] ?? $servicio); ?>">
                                    <?php
                                    $iconos_servicios = array(
                                        'vigilancia' => '&#128249;',
                                        'carga_electrica' => '&#9889;',
                                        'acceso_24h' => '&#128275;',
                                        'lavado' => '&#128703;',
                                        'motos' => '&#127949;'
                                    );
                                    echo $iconos_servicios[$servicio] ?? '&#10003;';
                                    ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="flavor-parkings-btn-reservar" data-parking-id="<?php echo esc_attr($parking['id']); ?>">
                            <?php echo $parking['plazas_disponibles'] > 0 ? 'Reservar plaza' : 'Lista de espera'; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Leyenda del mapa -->
    <div class="flavor-parkings-leyenda">
        <h4 class="flavor-parkings-leyenda-titulo">Leyenda</h4>
        <div class="flavor-parkings-leyenda-items">
            <span class="flavor-parkings-leyenda-item">
                <span class="flavor-parkings-marcador flavor-parkings-marcador-disponible"></span>
                Alta disponibilidad
            </span>
            <span class="flavor-parkings-leyenda-item">
                <span class="flavor-parkings-marcador flavor-parkings-marcador-medio"></span>
                Pocas plazas
            </span>
            <span class="flavor-parkings-leyenda-item">
                <span class="flavor-parkings-marcador flavor-parkings-marcador-completo"></span>
                Completo
            </span>
        </div>
    </div>
</div>

<!-- Datos JSON para JavaScript -->
<script type="application/json" id="flavor-parkings-data">
<?php echo json_encode($parkings_demo); ?>
</script>

<style>
.flavor-parkings-mapa-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-parkings-mapa-titulo {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 20px;
}

/* Filtros */
.flavor-parkings-filtros {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.flavor-parkings-filtros-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.flavor-parkings-filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.flavor-parkings-filtro-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4a5568;
}

.flavor-parkings-filtro-select {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
}

.flavor-parkings-filtro-range {
    width: 100%;
    cursor: pointer;
}

.flavor-parkings-precio-valor {
    font-weight: 600;
    color: #2d3748;
}

.flavor-parkings-filtro-servicios {
    grid-column: span 2;
}

.flavor-parkings-servicios-checks {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.flavor-parkings-servicio-check {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    cursor: pointer;
}

.flavor-parkings-btn-filtrar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-parkings-btn-filtrar:hover {
    background: #4338ca;
}

/* Mapa y lista */
.flavor-parkings-mapa-wrapper {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
    margin-bottom: 20px;
}

.flavor-parkings-mapa {
    background: #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.flavor-parkings-mapa-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.flavor-parkings-mapa-loading {
    text-align: center;
    color: white;
}

.flavor-parkings-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: flavor-spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes flavor-spin {
    to { transform: rotate(360deg); }
}

/* Lista lateral */
.flavor-parkings-lista-lateral {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.flavor-parkings-lista-titulo {
    padding: 15px 20px;
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    background: #f8f9fa;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-parkings-lista-scroll {
    max-height: 460px;
    overflow-y: auto;
}

.flavor-parkings-lista-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-parkings-lista-item:hover {
    background: #f8f9fa;
}

.flavor-parkings-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.flavor-parkings-item-nombre {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-parkings-item-valoracion {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: #4a5568;
}

.flavor-parkings-estrella {
    color: #f59e0b;
}

.flavor-parkings-item-direccion {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.flavor-parkings-item-info {
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
}

.flavor-parkings-item-tipo {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.flavor-parkings-tipo-cubierto {
    background: #dbeafe;
    color: #1e40af;
}

.flavor-parkings-tipo-exterior {
    background: #dcfce7;
    color: #166534;
}

.flavor-parkings-item-horario {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Disponibilidad */
.flavor-parkings-item-disponibilidad {
    margin-bottom: 10px;
}

.flavor-parkings-barra-ocupacion {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 6px;
}

.flavor-parkings-barra-llena {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.flavor-parkings-disponibilidad-alta .flavor-parkings-barra-llena {
    background: #22c55e;
}

.flavor-parkings-disponibilidad-media .flavor-parkings-barra-llena {
    background: #f59e0b;
}

.flavor-parkings-disponibilidad-agotada .flavor-parkings-barra-llena {
    background: #ef4444;
}

.flavor-parkings-plazas-texto {
    font-size: 0.8rem;
    color: #64748b;
}

.flavor-parkings-item-precios {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
}

.flavor-parkings-precio-hora {
    font-size: 1rem;
    font-weight: 600;
    color: #4f46e5;
}

.flavor-parkings-precio-dia {
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-parkings-item-servicios {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.flavor-parkings-servicio-badge {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 0.9rem;
}

.flavor-parkings-btn-reservar {
    width: 100%;
    padding: 10px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-parkings-btn-reservar:hover {
    background: #4338ca;
}

/* Leyenda */
.flavor-parkings-leyenda {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px 20px;
}

.flavor-parkings-leyenda-titulo {
    margin: 0 0 10px;
    font-size: 0.9rem;
    font-weight: 600;
}

.flavor-parkings-leyenda-items {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
}

.flavor-parkings-leyenda-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #4a5568;
}

.flavor-parkings-marcador {
    width: 16px;
    height: 16px;
    border-radius: 50%;
}

.flavor-parkings-marcador-disponible {
    background: #22c55e;
}

.flavor-parkings-marcador-medio {
    background: #f59e0b;
}

.flavor-parkings-marcador-completo {
    background: #ef4444;
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-parkings-mapa-wrapper {
        grid-template-columns: 1fr;
    }

    .flavor-parkings-lista-lateral {
        max-height: 400px;
    }

    .flavor-parkings-lista-scroll {
        max-height: 340px;
    }
}

@media (max-width: 768px) {
    .flavor-parkings-filtros-row {
        grid-template-columns: 1fr;
    }

    .flavor-parkings-filtro-servicios {
        grid-column: span 1;
    }

    .flavor-parkings-mapa {
        height: 300px !important;
    }

    .flavor-parkings-leyenda-items {
        flex-direction: column;
        gap: 10px;
    }
}
</style>
