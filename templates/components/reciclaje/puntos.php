<?php
/**
 * Template: Puntos de Reciclaje
 * Mapa/grid de puntos de reciclaje con filtros por tipo de residuo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo']) ? esc_html($args['titulo']) : 'Puntos de Reciclaje';
$mostrar_mapa = isset($args['mostrar_mapa']) ? (bool) $args['mostrar_mapa'] : true;
$mostrar_filtros = isset($args['mostrar_filtros']) ? (bool) $args['mostrar_filtros'] : true;
$columnas_grid = isset($args['columnas']) ? absint($args['columnas']) : 3;
$clase_adicional = isset($args['clase']) ? esc_attr($args['clase']) : '';

// Tipos de contenedores/puntos de reciclaje
$tipos_contenedores = array(
    'amarillo' => array(
        'nombre' => 'Envases',
        'icono' => '🟡',
        'color' => '#FFD700',
        'descripcion' => 'Plásticos, latas, briks'
    ),
    'azul' => array(
        'nombre' => 'Papel y Cartón',
        'icono' => '🔵',
        'color' => '#2196F3',
        'descripcion' => 'Papel, cartón, revistas'
    ),
    'verde' => array(
        'nombre' => 'Vidrio',
        'icono' => '🟢',
        'color' => '#4CAF50',
        'descripcion' => 'Botellas, tarros de vidrio'
    ),
    'marron' => array(
        'nombre' => 'Orgánico',
        'icono' => '🟤',
        'color' => '#795548',
        'descripcion' => 'Restos de comida, plantas'
    ),
    'gris' => array(
        'nombre' => 'Resto',
        'icono' => '⚫',
        'color' => '#607D8B',
        'descripcion' => 'Residuos no reciclables'
    ),
    'punto_limpio' => array(
        'nombre' => 'Punto Limpio',
        'icono' => '♻️',
        'color' => '#9C27B0',
        'descripcion' => 'Residuos especiales, RAEE, aceite'
    )
);

// Datos de demostración de puntos de reciclaje
$puntos_reciclaje_demo = array(
    array(
        'id' => 1,
        'nombre' => 'Plaza Mayor - Contenedores',
        'direccion' => 'Plaza Mayor, 1',
        'tipos' => array('amarillo', 'azul', 'verde', 'marron', 'gris'),
        'horario' => '24 horas',
        'latitud' => 40.4168,
        'longitud' => -3.7038,
        'distancia' => '150m'
    ),
    array(
        'id' => 2,
        'nombre' => 'Punto Limpio Municipal',
        'direccion' => 'Polígono Industrial, Nave 25',
        'tipos' => array('punto_limpio'),
        'horario' => 'Lun-Sáb: 9:00-20:00',
        'latitud' => 40.4200,
        'longitud' => -3.7100,
        'distancia' => '1.2km'
    ),
    array(
        'id' => 3,
        'nombre' => 'Calle del Comercio',
        'direccion' => 'Calle del Comercio, 45',
        'tipos' => array('amarillo', 'azul', 'verde'),
        'horario' => '24 horas',
        'latitud' => 40.4150,
        'longitud' => -3.7020,
        'distancia' => '300m'
    ),
    array(
        'id' => 4,
        'nombre' => 'Parque Central',
        'direccion' => 'Avenida del Parque, s/n',
        'tipos' => array('amarillo', 'azul', 'verde', 'marron'),
        'horario' => '24 horas',
        'latitud' => 40.4180,
        'longitud' => -3.7050,
        'distancia' => '450m'
    ),
    array(
        'id' => 5,
        'nombre' => 'Centro Cívico Norte',
        'direccion' => 'Calle Norte, 12',
        'tipos' => array('amarillo', 'azul', 'verde', 'gris'),
        'horario' => '24 horas',
        'latitud' => 40.4220,
        'longitud' => -3.7080,
        'distancia' => '800m'
    ),
    array(
        'id' => 6,
        'nombre' => 'Mercado Municipal',
        'direccion' => 'Plaza del Mercado, 3',
        'tipos' => array('marron', 'amarillo'),
        'horario' => 'Lun-Sáb: 8:00-14:00',
        'latitud' => 40.4160,
        'longitud' => -3.7045,
        'distancia' => '200m'
    )
);

// Usar puntos personalizados si se proporcionan
$puntos_reciclaje = isset($args['puntos']) && is_array($args['puntos']) ? $args['puntos'] : $puntos_reciclaje_demo;
?>

<div class="flavor-puntos-reciclaje <?php echo $clase_adicional; ?>">

    <!-- Encabezado -->
    <div class="flavor-puntos-header">
        <h2 class="flavor-puntos-titulo"><?php echo $titulo_seccion; ?></h2>
        <p class="flavor-puntos-subtitulo">Encuentra el contenedor más cercano para reciclar correctamente</p>
    </div>

    <?php if ($mostrar_filtros) : ?>
    <!-- Filtros por tipo de residuo -->
    <div class="flavor-puntos-filtros">
        <span class="flavor-filtros-label">Filtrar por tipo:</span>
        <div class="flavor-filtros-botones">
            <button type="button" class="flavor-filtro-btn flavor-filtro-activo" data-filtro="todos">
                <span class="flavor-filtro-icono">📍</span>
                <span class="flavor-filtro-texto">Todos</span>
            </button>
            <?php foreach ($tipos_contenedores as $tipo_clave => $tipo_datos) : ?>
            <button type="button" class="flavor-filtro-btn" data-filtro="<?php echo esc_attr($tipo_clave); ?>">
                <span class="flavor-filtro-icono"><?php echo $tipo_datos['icono']; ?></span>
                <span class="flavor-filtro-texto"><?php echo esc_html($tipo_datos['nombre']); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($mostrar_mapa) : ?>
    <!-- Contenedor del mapa -->
    <div class="flavor-puntos-mapa-container">
        <div class="flavor-puntos-mapa" id="flavor-mapa-reciclaje">
            <div class="flavor-mapa-placeholder">
                <span class="flavor-mapa-icono">🗺️</span>
                <p>Mapa interactivo de puntos de reciclaje</p>
                <small>Integra con Google Maps, OpenStreetMap o similar</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de puntos de reciclaje -->
    <div class="flavor-puntos-grid flavor-grid-cols-<?php echo $columnas_grid; ?>">
        <?php foreach ($puntos_reciclaje as $punto) : ?>
        <article class="flavor-punto-card" data-tipos="<?php echo esc_attr(implode(',', $punto['tipos'])); ?>">
            <div class="flavor-punto-header">
                <h3 class="flavor-punto-nombre"><?php echo esc_html($punto['nombre']); ?></h3>
                <?php if (!empty($punto['distancia'])) : ?>
                <span class="flavor-punto-distancia">
                    <span class="flavor-distancia-icono">📍</span>
                    <?php echo esc_html($punto['distancia']); ?>
                </span>
                <?php endif; ?>
            </div>

            <div class="flavor-punto-contenido">
                <p class="flavor-punto-direccion">
                    <span class="flavor-direccion-icono">🏠</span>
                    <?php echo esc_html($punto['direccion']); ?>
                </p>

                <p class="flavor-punto-horario">
                    <span class="flavor-horario-icono">🕐</span>
                    <?php echo esc_html($punto['horario']); ?>
                </p>

                <!-- Tipos de contenedores disponibles -->
                <div class="flavor-punto-tipos">
                    <?php foreach ($punto['tipos'] as $tipo_punto) : ?>
                        <?php if (isset($tipos_contenedores[$tipo_punto])) : ?>
                        <span class="flavor-tipo-badge"
                              style="background-color: <?php echo esc_attr($tipos_contenedores[$tipo_punto]['color']); ?>20; border-color: <?php echo esc_attr($tipos_contenedores[$tipo_punto]['color']); ?>;"
                              title="<?php echo esc_attr($tipos_contenedores[$tipo_punto]['descripcion']); ?>">
                            <span class="flavor-tipo-icono"><?php echo $tipos_contenedores[$tipo_punto]['icono']; ?></span>
                            <span class="flavor-tipo-nombre"><?php echo esc_html($tipos_contenedores[$tipo_punto]['nombre']); ?></span>
                        </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flavor-punto-acciones">
                <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-direcciones"
                        data-lat="<?php echo esc_attr($punto['latitud']); ?>"
                        data-lng="<?php echo esc_attr($punto['longitud']); ?>">
                    <span>🧭</span> Cómo llegar
                </button>
                <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-detalles"
                        data-punto-id="<?php echo esc_attr($punto['id']); ?>">
                    <span>ℹ️</span> Detalles
                </button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Leyenda de tipos -->
    <div class="flavor-puntos-leyenda">
        <h4 class="flavor-leyenda-titulo">Leyenda de contenedores</h4>
        <div class="flavor-leyenda-items">
            <?php foreach ($tipos_contenedores as $tipo_clave => $tipo_datos) : ?>
            <div class="flavor-leyenda-item">
                <span class="flavor-leyenda-color" style="background-color: <?php echo esc_attr($tipo_datos['color']); ?>;"></span>
                <span class="flavor-leyenda-icono"><?php echo $tipo_datos['icono']; ?></span>
                <div class="flavor-leyenda-texto">
                    <strong><?php echo esc_html($tipo_datos['nombre']); ?></strong>
                    <small><?php echo esc_html($tipo_datos['descripcion']); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<style>
/* Estilos base para puntos de reciclaje */
.flavor-puntos-reciclaje {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-puntos-header {
    text-align: center;
    margin-bottom: 30px;
}

.flavor-puntos-titulo {
    font-size: 2rem;
    color: #2e7d32;
    margin: 0 0 10px 0;
}

.flavor-puntos-subtitulo {
    color: #666;
    font-size: 1.1rem;
    margin: 0;
}

/* Filtros */
.flavor-puntos-filtros {
    background: #f5f5f5;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.flavor-filtros-label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.flavor-filtros-botones {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-filtro-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 2px solid #ddd;
    border-radius: 25px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.flavor-filtro-btn:hover {
    border-color: #4CAF50;
    background: #e8f5e9;
}

.flavor-filtro-btn.flavor-filtro-activo {
    border-color: #4CAF50;
    background: #4CAF50;
    color: white;
}

.flavor-filtro-icono {
    font-size: 1.1rem;
}

/* Mapa */
.flavor-puntos-mapa-container {
    margin-bottom: 30px;
}

.flavor-puntos-mapa {
    height: 350px;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #c8e6c9;
}

.flavor-mapa-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #2e7d32;
}

.flavor-mapa-icono {
    font-size: 4rem;
    margin-bottom: 15px;
}

.flavor-mapa-placeholder p {
    font-size: 1.2rem;
    margin: 0 0 5px 0;
}

.flavor-mapa-placeholder small {
    color: #666;
}

/* Grid de puntos */
.flavor-puntos-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-grid-cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.flavor-grid-cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

/* Tarjeta de punto */
.flavor-punto-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
}

.flavor-punto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.flavor-punto-card.flavor-oculto {
    display: none;
}

.flavor-punto-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.flavor-punto-nombre {
    font-size: 1.1rem;
    color: #333;
    margin: 0;
    flex: 1;
}

.flavor-punto-distancia {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    margin-left: 10px;
}

.flavor-punto-contenido {
    flex: 1;
}

.flavor-punto-direccion,
.flavor-punto-horario {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.95rem;
    margin: 0 0 10px 0;
}

.flavor-punto-tipos {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.flavor-tipo-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    border: 1px solid;
}

.flavor-tipo-icono {
    font-size: 0.9rem;
}

.flavor-tipo-nombre {
    font-weight: 500;
}

/* Acciones */
.flavor-punto-acciones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.flavor-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.flavor-btn-primary {
    background: #4CAF50;
    color: white;
}

.flavor-btn-primary:hover {
    background: #388E3C;
}

.flavor-btn-secondary {
    background: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.flavor-btn-secondary:hover {
    background: #e0e0e0;
}

/* Leyenda */
.flavor-puntos-leyenda {
    background: #fafafa;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e0e0e0;
}

.flavor-leyenda-titulo {
    font-size: 1rem;
    color: #333;
    margin: 0 0 15px 0;
}

.flavor-leyenda-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-leyenda-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    flex-shrink: 0;
}

.flavor-leyenda-icono {
    font-size: 1.2rem;
}

.flavor-leyenda-texto {
    display: flex;
    flex-direction: column;
}

.flavor-leyenda-texto strong {
    font-size: 0.9rem;
    color: #333;
}

.flavor-leyenda-texto small {
    font-size: 0.8rem;
    color: #666;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-grid-cols-3,
    .flavor-grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-puntos-reciclaje {
        padding: 15px;
    }

    .flavor-puntos-titulo {
        font-size: 1.5rem;
    }

    .flavor-filtros-botones {
        justify-content: center;
    }

    .flavor-filtro-btn {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    .flavor-filtro-texto {
        display: none;
    }

    .flavor-puntos-mapa {
        height: 250px;
    }

    .flavor-grid-cols-2,
    .flavor-grid-cols-3,
    .flavor-grid-cols-4 {
        grid-template-columns: 1fr;
    }

    .flavor-punto-acciones {
        flex-direction: column;
    }

    .flavor-leyenda-items {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .flavor-punto-header {
        flex-direction: column;
        gap: 10px;
    }

    .flavor-punto-distancia {
        margin-left: 0;
        align-self: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad de filtros
    const botonesDelFiltro = document.querySelectorAll('.flavor-filtro-btn');
    const tarjetasDePuntos = document.querySelectorAll('.flavor-punto-card');

    botonesDelFiltro.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const filtroSeleccionado = this.dataset.filtro;

            // Actualizar estado activo
            botonesDelFiltro.forEach(function(btn) {
                btn.classList.remove('flavor-filtro-activo');
            });
            this.classList.add('flavor-filtro-activo');

            // Filtrar tarjetas
            tarjetasDePuntos.forEach(function(tarjeta) {
                const tiposDeLaTarjeta = tarjeta.dataset.tipos.split(',');

                if (filtroSeleccionado === 'todos' || tiposDeLaTarjeta.includes(filtroSeleccionado)) {
                    tarjeta.classList.remove('flavor-oculto');
                } else {
                    tarjeta.classList.add('flavor-oculto');
                }
            });
        });
    });

    // Funcionalidad de botón "Cómo llegar"
    const botonesDirecciones = document.querySelectorAll('.flavor-btn-direcciones');
    botonesDirecciones.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const latitud = this.dataset.lat;
            const longitud = this.dataset.lng;
            const urlGoogleMaps = 'https://www.google.com/maps/dir/?api=1&destination=' + latitud + ',' + longitud;
            window.open(urlGoogleMaps, '_blank');
        });
    });

    // Funcionalidad de botón "Detalles"
    const botonesDetalles = document.querySelectorAll('.flavor-btn-detalles');
    botonesDetalles.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const idDelPunto = this.dataset.puntoId;
            // Aquí se puede implementar un modal o expandir la información
            console.log('Mostrar detalles del punto:', idDelPunto);
        });
    });
});
</script>
