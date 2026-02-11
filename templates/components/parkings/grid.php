<?php
/**
 * Template: Grid de parkings disponibles
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Parkings
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Parkings Disponibles';
$columnas = isset($args['columnas']) ? $args['columnas'] : 3;
$mostrar_ordenar = isset($args['mostrar_ordenar']) ? $args['mostrar_ordenar'] : true;
$vista_compacta = isset($args['vista_compacta']) ? $args['vista_compacta'] : false;

// Datos de demostración de parkings
$parkings_demo = isset($args['parkings']) ? $args['parkings'] : array(
    array(
        'id' => 1,
        'nombre' => 'Parking Residencial Las Flores',
        'imagen' => 'https://images.unsplash.com/photo-1590674899484-d5640e854abe?w=400&h=250&fit=crop',
        'direccion' => 'Calle Mayor 45, Madrid',
        'barrio' => 'Centro',
        'plazas_totales' => 50,
        'plazas_disponibles' => 12,
        'precio_hora' => 1.50,
        'precio_dia' => 15.00,
        'precio_mes' => 120.00,
        'horario_apertura' => '00:00',
        'horario_cierre' => '23:59',
        'abierto_24h' => true,
        'tipo' => 'cubierto',
        'altura_maxima' => 2.10,
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h'),
        'valoracion' => 4.5,
        'num_resenas' => 127,
        'destacado' => true
    ),
    array(
        'id' => 2,
        'nombre' => 'Parking Comunitario Sol',
        'imagen' => 'https://images.unsplash.com/photo-1573348722427-f1d6819fdf98?w=400&h=250&fit=crop',
        'direccion' => 'Plaza del Sol 10, Madrid',
        'barrio' => 'Sol',
        'plazas_totales' => 30,
        'plazas_disponibles' => 5,
        'precio_hora' => 2.00,
        'precio_dia' => 18.00,
        'precio_mes' => 150.00,
        'horario_apertura' => '06:00',
        'horario_cierre' => '23:00',
        'abierto_24h' => false,
        'tipo' => 'exterior',
        'altura_maxima' => null,
        'servicios' => array('vigilancia', 'lavado'),
        'valoracion' => 4.2,
        'num_resenas' => 89,
        'destacado' => false
    ),
    array(
        'id' => 3,
        'nombre' => 'Parking Vecinal Centro',
        'imagen' => 'https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=400&h=250&fit=crop',
        'direccion' => 'Calle Gran Vía 78, Madrid',
        'barrio' => 'Gran Vía',
        'plazas_totales' => 80,
        'plazas_disponibles' => 25,
        'precio_hora' => 1.80,
        'precio_dia' => 16.00,
        'precio_mes' => 135.00,
        'horario_apertura' => '00:00',
        'horario_cierre' => '23:59',
        'abierto_24h' => true,
        'tipo' => 'cubierto',
        'altura_maxima' => 2.20,
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h', 'motos'),
        'valoracion' => 4.8,
        'num_resenas' => 234,
        'destacado' => true
    ),
    array(
        'id' => 4,
        'nombre' => 'Parking Barrio Norte',
        'imagen' => 'https://images.unsplash.com/photo-1470224114660-3f6686c562eb?w=400&h=250&fit=crop',
        'direccion' => 'Avenida de la Paz 23, Madrid',
        'barrio' => 'Chamberí',
        'plazas_totales' => 40,
        'plazas_disponibles' => 0,
        'precio_hora' => 1.20,
        'precio_dia' => 12.00,
        'precio_mes' => 95.00,
        'horario_apertura' => '07:00',
        'horario_cierre' => '22:00',
        'abierto_24h' => false,
        'tipo' => 'exterior',
        'altura_maxima' => null,
        'servicios' => array('motos'),
        'valoracion' => 3.9,
        'num_resenas' => 45,
        'destacado' => false
    ),
    array(
        'id' => 5,
        'nombre' => 'Parking Comunidad Verde',
        'imagen' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=250&fit=crop',
        'direccion' => 'Calle del Prado 56, Madrid',
        'barrio' => 'Retiro',
        'plazas_totales' => 60,
        'plazas_disponibles' => 18,
        'precio_hora' => 1.60,
        'precio_dia' => 14.50,
        'precio_mes' => 110.00,
        'horario_apertura' => '00:00',
        'horario_cierre' => '23:59',
        'abierto_24h' => true,
        'tipo' => 'cubierto',
        'altura_maxima' => 2.00,
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h', 'lavado'),
        'valoracion' => 4.6,
        'num_resenas' => 178,
        'destacado' => false
    ),
    array(
        'id' => 6,
        'nombre' => 'Parking Plaza Mayor',
        'imagen' => 'https://images.unsplash.com/photo-1545179605-1296651e9da9?w=400&h=250&fit=crop',
        'direccion' => 'Plaza Mayor 2, Madrid',
        'barrio' => 'Centro',
        'plazas_totales' => 100,
        'plazas_disponibles' => 35,
        'precio_hora' => 2.50,
        'precio_dia' => 22.00,
        'precio_mes' => 180.00,
        'horario_apertura' => '00:00',
        'horario_cierre' => '23:59',
        'abierto_24h' => true,
        'tipo' => 'cubierto',
        'altura_maxima' => 2.30,
        'servicios' => array('vigilancia', 'carga_electrica', 'acceso_24h', 'lavado', 'motos'),
        'valoracion' => 4.7,
        'num_resenas' => 312,
        'destacado' => true
    )
);

// Iconos de servicios
$iconos_servicios = array(
    'vigilancia' => array('icono' => '&#128249;', 'nombre' => 'Vigilancia 24h'),
    'carga_electrica' => array('icono' => '&#9889;', 'nombre' => 'Carga eléctrica'),
    'acceso_24h' => array('icono' => '&#128275;', 'nombre' => 'Acceso 24h'),
    'lavado' => array('icono' => '&#128703;', 'nombre' => 'Lavado'),
    'motos' => array('icono' => '&#127949;', 'nombre' => 'Motos')
);
?>

<div class="flavor-parkings-grid-container">
    <div class="flavor-parkings-grid-header">
        <?php if (!empty($titulo)) : ?>
            <h2 class="flavor-parkings-grid-titulo"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>

        <?php if ($mostrar_ordenar) : ?>
            <div class="flavor-parkings-grid-controles">
                <div class="flavor-parkings-ordenar">
                    <label for="flavor-ordenar-por" class="flavor-parkings-ordenar-label">Ordenar por:</label>
                    <select id="flavor-ordenar-por" class="flavor-parkings-ordenar-select">
                        <option value="relevancia">Relevancia</option>
                        <option value="precio_asc">Precio: menor a mayor</option>
                        <option value="precio_desc">Precio: mayor a menor</option>
                        <option value="disponibilidad">Disponibilidad</option>
                        <option value="valoracion">Mejor valorados</option>
                        <option value="distancia">Distancia</option>
                    </select>
                </div>

                <div class="flavor-parkings-vista-toggle">
                    <button type="button" class="flavor-parkings-vista-btn active" data-vista="grid" title="Vista cuadrícula">
                        <span>&#9783;</span>
                    </button>
                    <button type="button" class="flavor-parkings-vista-btn" data-vista="lista" title="Vista lista">
                        <span>&#9776;</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="flavor-parkings-resultados-info">
        <span class="flavor-parkings-total-resultados"><?php echo count($parkings_demo); ?> parkings encontrados</span>
    </div>

    <div class="flavor-parkings-grid" style="--columnas: <?php echo esc_attr($columnas); ?>;">
        <?php foreach ($parkings_demo as $parking) :
            $porcentaje_ocupacion = (($parking['plazas_totales'] - $parking['plazas_disponibles']) / $parking['plazas_totales']) * 100;
            $clase_disponibilidad = $parking['plazas_disponibles'] > 10 ? 'alta' : ($parking['plazas_disponibles'] > 0 ? 'media' : 'agotada');
            $esta_completo = $parking['plazas_disponibles'] === 0;
        ?>
            <article class="flavor-parkings-card <?php echo $parking['destacado'] ? 'flavor-parkings-card-destacado' : ''; ?> <?php echo $esta_completo ? 'flavor-parkings-card-completo' : ''; ?>">
                <?php if ($parking['destacado']) : ?>
                    <span class="flavor-parkings-badge-destacado">Destacado</span>
                <?php endif; ?>

                <div class="flavor-parkings-card-imagen">
                    <img src="<?php echo esc_url($parking['imagen']); ?>"
                         alt="<?php echo esc_attr($parking['nombre']); ?>"
                         loading="lazy">

                    <div class="flavor-parkings-card-overlay">
                        <span class="flavor-parkings-tipo-badge flavor-parkings-tipo-<?php echo esc_attr($parking['tipo']); ?>">
                            <?php echo esc_html(ucfirst($parking['tipo'])); ?>
                        </span>

                        <?php if ($parking['abierto_24h']) : ?>
                            <span class="flavor-parkings-horario-badge">24h</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($esta_completo) : ?>
                        <div class="flavor-parkings-completo-overlay">
                            <span>COMPLETO</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-parkings-card-content">
                    <div class="flavor-parkings-card-header">
                        <h3 class="flavor-parkings-card-nombre"><?php echo esc_html($parking['nombre']); ?></h3>
                        <div class="flavor-parkings-card-valoracion">
                            <span class="flavor-parkings-estrella">&#9733;</span>
                            <span class="flavor-parkings-valoracion-num"><?php echo number_format($parking['valoracion'], 1); ?></span>
                            <span class="flavor-parkings-resenas">(<?php echo esc_html($parking['num_resenas']); ?>)</span>
                        </div>
                    </div>

                    <div class="flavor-parkings-card-ubicacion">
                        <span class="flavor-parkings-icon">&#128205;</span>
                        <span class="flavor-parkings-direccion"><?php echo esc_html($parking['direccion']); ?></span>
                        <span class="flavor-parkings-barrio"><?php echo esc_html($parking['barrio']); ?></span>
                    </div>

                    <div class="flavor-parkings-card-disponibilidad flavor-parkings-disponibilidad-<?php echo esc_attr($clase_disponibilidad); ?>">
                        <div class="flavor-parkings-disponibilidad-header">
                            <span class="flavor-parkings-disponibilidad-icon">&#128663;</span>
                            <span class="flavor-parkings-disponibilidad-texto">
                                <?php if ($esta_completo) : ?>
                                    Sin plazas disponibles
                                <?php else : ?>
                                    <strong><?php echo esc_html($parking['plazas_disponibles']); ?></strong> plazas libres de <?php echo esc_html($parking['plazas_totales']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flavor-parkings-barra-progreso">
                            <div class="flavor-parkings-barra-llena" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%;"></div>
                        </div>
                    </div>

                    <div class="flavor-parkings-card-horario">
                        <span class="flavor-parkings-icon">&#128339;</span>
                        <?php if ($parking['abierto_24h']) : ?>
                            <span class="flavor-parkings-horario-abierto">Abierto 24 horas</span>
                        <?php else : ?>
                            <span><?php echo esc_html($parking['horario_apertura']); ?> - <?php echo esc_html($parking['horario_cierre']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($parking['altura_maxima']) : ?>
                        <div class="flavor-parkings-card-altura">
                            <span class="flavor-parkings-icon">&#8597;</span>
                            <span>Altura máx: <?php echo number_format($parking['altura_maxima'], 2); ?>m</span>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-parkings-card-servicios">
                        <?php foreach ($parking['servicios'] as $servicio) :
                            if (isset($iconos_servicios[$servicio])) : ?>
                                <span class="flavor-parkings-servicio" title="<?php echo esc_attr($iconos_servicios[$servicio]['nombre']); ?>">
                                    <?php echo $iconos_servicios[$servicio]['icono']; ?>
                                </span>
                            <?php endif;
                        endforeach; ?>
                    </div>

                    <div class="flavor-parkings-card-precios">
                        <div class="flavor-parkings-precio-principal">
                            <span class="flavor-parkings-precio-cantidad"><?php echo number_format($parking['precio_hora'], 2); ?>€</span>
                            <span class="flavor-parkings-precio-periodo">/hora</span>
                        </div>
                        <div class="flavor-parkings-precios-secundarios">
                            <span class="flavor-parkings-precio-item">
                                <span class="flavor-parkings-precio-label">Día:</span>
                                <span class="flavor-parkings-precio-valor"><?php echo number_format($parking['precio_dia'], 2); ?>€</span>
                            </span>
                            <span class="flavor-parkings-precio-item">
                                <span class="flavor-parkings-precio-label">Mes:</span>
                                <span class="flavor-parkings-precio-valor"><?php echo number_format($parking['precio_mes'], 2); ?>€</span>
                            </span>
                        </div>
                    </div>

                    <div class="flavor-parkings-card-acciones">
                        <button type="button" class="flavor-parkings-btn-ver" data-parking-id="<?php echo esc_attr($parking['id']); ?>">
                            Ver detalles
                        </button>
                        <button type="button"
                                class="flavor-parkings-btn-reservar"
                                data-parking-id="<?php echo esc_attr($parking['id']); ?>"
                                <?php echo $esta_completo ? 'disabled' : ''; ?>>
                            <?php echo $esta_completo ? 'Lista de espera' : 'Reservar'; ?>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="flavor-parkings-grid-paginacion">
        <button type="button" class="flavor-parkings-pag-btn" disabled>
            <span>&#8592;</span> Anterior
        </button>
        <div class="flavor-parkings-pag-numeros">
            <button type="button" class="flavor-parkings-pag-num active">1</button>
            <button type="button" class="flavor-parkings-pag-num">2</button>
            <button type="button" class="flavor-parkings-pag-num">3</button>
            <span class="flavor-parkings-pag-dots">...</span>
            <button type="button" class="flavor-parkings-pag-num">8</button>
        </div>
        <button type="button" class="flavor-parkings-pag-btn">
            Siguiente <span>&#8594;</span>
        </button>
    </div>
</div>

<style>
.flavor-parkings-grid-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-parkings-grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.flavor-parkings-grid-titulo {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0;
}

.flavor-parkings-grid-controles {
    display: flex;
    align-items: center;
    gap: 20px;
}

.flavor-parkings-ordenar {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-parkings-ordenar-label {
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-parkings-ordenar-select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
}

.flavor-parkings-vista-toggle {
    display: flex;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 4px;
}

.flavor-parkings-vista-btn {
    padding: 8px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 6px;
    font-size: 1.1rem;
    color: #64748b;
    transition: all 0.2s;
}

.flavor-parkings-vista-btn.active {
    background: white;
    color: #4f46e5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-parkings-resultados-info {
    margin-bottom: 20px;
}

.flavor-parkings-total-resultados {
    font-size: 0.95rem;
    color: #64748b;
}

/* Grid de cards */
.flavor-parkings-grid {
    display: grid;
    grid-template-columns: repeat(var(--columnas, 3), 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

/* Card */
.flavor-parkings-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
}

.flavor-parkings-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.flavor-parkings-card-destacado {
    border: 2px solid #f59e0b;
}

.flavor-parkings-card-completo {
    opacity: 0.85;
}

.flavor-parkings-badge-destacado {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 10;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-parkings-card-imagen {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.flavor-parkings-card-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.flavor-parkings-card:hover .flavor-parkings-card-imagen img {
    transform: scale(1.05);
}

.flavor-parkings-card-overlay {
    position: absolute;
    bottom: 10px;
    right: 10px;
    display: flex;
    gap: 8px;
}

.flavor-parkings-tipo-badge,
.flavor-parkings-horario-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.flavor-parkings-tipo-cubierto {
    background: rgba(59, 130, 246, 0.9);
    color: white;
}

.flavor-parkings-tipo-exterior {
    background: rgba(34, 197, 94, 0.9);
    color: white;
}

.flavor-parkings-horario-badge {
    background: rgba(139, 92, 246, 0.9);
    color: white;
}

.flavor-parkings-completo-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-parkings-completo-overlay span {
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: 2px;
}

/* Card content */
.flavor-parkings-card-content {
    padding: 20px;
}

.flavor-parkings-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.flavor-parkings-card-nombre {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a2e;
    flex: 1;
    line-height: 1.3;
}

.flavor-parkings-card-valoracion {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    margin-left: 10px;
}

.flavor-parkings-estrella {
    color: #f59e0b;
    font-size: 1rem;
}

.flavor-parkings-valoracion-num {
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-parkings-resenas {
    font-size: 0.8rem;
    color: #94a3b8;
}

.flavor-parkings-card-ubicacion {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-parkings-barrio {
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

/* Disponibilidad */
.flavor-parkings-card-disponibilidad {
    margin-bottom: 12px;
    padding: 10px;
    border-radius: 8px;
    background: #f8fafc;
}

.flavor-parkings-disponibilidad-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.flavor-parkings-disponibilidad-texto {
    font-size: 0.85rem;
    color: #475569;
}

.flavor-parkings-barra-progreso {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}

.flavor-parkings-barra-llena {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.flavor-parkings-disponibilidad-alta .flavor-parkings-barra-llena {
    background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
}

.flavor-parkings-disponibilidad-media .flavor-parkings-barra-llena {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.flavor-parkings-disponibilidad-agotada .flavor-parkings-barra-llena {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.flavor-parkings-card-horario,
.flavor-parkings-card-altura {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 10px;
}

.flavor-parkings-horario-abierto {
    color: #22c55e;
    font-weight: 500;
}

.flavor-parkings-icon {
    font-size: 0.9rem;
}

/* Servicios */
.flavor-parkings-card-servicios {
    display: flex;
    gap: 8px;
    margin-bottom: 15px;
}

.flavor-parkings-servicio {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 1rem;
    cursor: help;
}

/* Precios */
.flavor-parkings-card-precios {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 15px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 15px;
}

.flavor-parkings-precio-principal {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.flavor-parkings-precio-cantidad {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4f46e5;
}

.flavor-parkings-precio-periodo {
    font-size: 0.85rem;
    color: #94a3b8;
}

.flavor-parkings-precios-secundarios {
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: right;
}

.flavor-parkings-precio-item {
    font-size: 0.8rem;
    color: #64748b;
}

.flavor-parkings-precio-valor {
    font-weight: 600;
    color: #475569;
}

/* Acciones */
.flavor-parkings-card-acciones {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.flavor-parkings-btn-ver,
.flavor-parkings-btn-reservar {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.flavor-parkings-btn-ver {
    background: #f1f5f9;
    color: #475569;
}

.flavor-parkings-btn-ver:hover {
    background: #e2e8f0;
}

.flavor-parkings-btn-reservar {
    background: #4f46e5;
    color: white;
}

.flavor-parkings-btn-reservar:hover:not(:disabled) {
    background: #4338ca;
}

.flavor-parkings-btn-reservar:disabled {
    background: #94a3b8;
    cursor: not-allowed;
}

/* Paginacion */
.flavor-parkings-grid-paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    padding-top: 20px;
}

.flavor-parkings-pag-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-parkings-pag-btn:hover:not(:disabled) {
    border-color: #4f46e5;
    color: #4f46e5;
}

.flavor-parkings-pag-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.flavor-parkings-pag-numeros {
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-parkings-pag-num {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-parkings-pag-num:hover {
    border-color: #4f46e5;
    color: #4f46e5;
}

.flavor-parkings-pag-num.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: white;
}

.flavor-parkings-pag-dots {
    color: #94a3b8;
    padding: 0 5px;
}

/* Responsive */
@media (max-width: 1200px) {
    .flavor-parkings-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-parkings-grid {
        grid-template-columns: 1fr;
    }

    .flavor-parkings-grid-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-parkings-grid-controles {
        width: 100%;
        justify-content: space-between;
    }

    .flavor-parkings-card-precios {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .flavor-parkings-precios-secundarios {
        flex-direction: row;
        gap: 15px;
    }

    .flavor-parkings-grid-paginacion {
        flex-wrap: wrap;
    }

    .flavor-parkings-pag-numeros {
        order: -1;
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}

@media (max-width: 480px) {
    .flavor-parkings-card-acciones {
        grid-template-columns: 1fr;
    }

    .flavor-parkings-ordenar {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    .flavor-parkings-ordenar-select {
        width: 100%;
    }
}
</style>
