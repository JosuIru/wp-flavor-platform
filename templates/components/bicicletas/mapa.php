<?php
/**
 * Mapa Interactivo - Estaciones de Bicicletas
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Bicicletas
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Encuentra tu estación más cercana';
$descripcion_seccion = isset($args['descripcion_seccion']) ? $args['descripcion_seccion'] : 'Más de 150 estaciones distribuidas por toda la ciudad para tu comodidad';
$latitud_centro = isset($args['latitud_centro']) ? $args['latitud_centro'] : 40.4168;
$longitud_centro = isset($args['longitud_centro']) ? $args['longitud_centro'] : -3.7038;
$zoom_inicial = isset($args['zoom_inicial']) ? $args['zoom_inicial'] : 13;
$altura_mapa = isset($args['altura_mapa']) ? $args['altura_mapa'] : '500px';
$estaciones = isset($args['estaciones']) ? $args['estaciones'] : array();
$mostrar_buscador = isset($args['mostrar_buscador']) ? $args['mostrar_buscador'] : true;
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$api_key_mapa = isset($args['api_key_mapa']) ? $args['api_key_mapa'] : '';

// Datos de demostración para estaciones si no hay datos reales
if (empty($estaciones)) {
    $estaciones = array(
        array(
            'id' => 1,
            'nombre' => 'Estación Plaza Mayor',
            'direccion' => 'Plaza Mayor, 1',
            'latitud' => 40.4155,
            'longitud' => -3.7074,
            'bicicletas_disponibles' => 12,
            'espacios_libres' => 8,
            'tipo' => 'grande',
            'estado' => 'activa'
        ),
        array(
            'id' => 2,
            'nombre' => 'Estación Parque del Retiro',
            'direccion' => 'Calle Alfonso XII, 28',
            'latitud' => 40.4153,
            'longitud' => -3.6845,
            'bicicletas_disponibles' => 8,
            'espacios_libres' => 12,
            'tipo' => 'mediana',
            'estado' => 'activa'
        ),
        array(
            'id' => 3,
            'nombre' => 'Estación Gran Vía',
            'direccion' => 'Gran Vía, 45',
            'latitud' => 40.4203,
            'longitud' => -3.7059,
            'bicicletas_disponibles' => 5,
            'espacios_libres' => 15,
            'tipo' => 'grande',
            'estado' => 'activa'
        ),
        array(
            'id' => 4,
            'nombre' => 'Estación Universidad',
            'direccion' => 'Calle Princesa, 70',
            'latitud' => 40.4341,
            'longitud' => -3.7178,
            'bicicletas_disponibles' => 0,
            'espacios_libres' => 20,
            'tipo' => 'grande',
            'estado' => 'sin_bicicletas'
        ),
        array(
            'id' => 5,
            'nombre' => 'Estación Atocha',
            'direccion' => 'Paseo del Prado, 1',
            'latitud' => 40.4069,
            'longitud' => -3.6910,
            'bicicletas_disponibles' => 15,
            'espacios_libres' => 5,
            'tipo' => 'grande',
            'estado' => 'activa'
        ),
        array(
            'id' => 6,
            'nombre' => 'Estación Malasaña',
            'direccion' => 'Plaza del Dos de Mayo, 3',
            'latitud' => 40.4267,
            'longitud' => -3.7031,
            'bicicletas_disponibles' => 7,
            'espacios_libres' => 3,
            'tipo' => 'pequena',
            'estado' => 'activa'
        )
    );
}

// ID único para el mapa
$id_mapa = 'flavor-mapa-' . uniqid();
?>

<section class="flavor-mapa-bicicletas" id="mapa">
    <div class="flavor-mapa-container">
        <div class="flavor-mapa-header">
            <h2 class="flavor-mapa-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <p class="flavor-mapa-descripcion"><?php echo esc_html($descripcion_seccion); ?></p>
        </div>

        <div class="flavor-mapa-contenido">
            <div class="flavor-mapa-sidebar">
                <?php if ($mostrar_buscador) : ?>
                <div class="flavor-mapa-buscador">
                    <div class="flavor-input-grupo">
                        <span class="flavor-input-icono">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" class="flavor-input-busqueda" placeholder="Buscar dirección o estación..." id="<?php echo esc_attr($id_mapa); ?>-busqueda">
                    </div>
                    <button class="flavor-boton-ubicacion" title="Usar mi ubicación">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($mostrar_filtros) : ?>
                <div class="flavor-mapa-filtros">
                    <span class="flavor-filtros-titulo">Filtrar por:</span>
                    <div class="flavor-filtros-opciones">
                        <label class="flavor-filtro-opcion">
                            <input type="checkbox" checked data-filtro="con_bicicletas">
                            <span class="flavor-filtro-check"></span>
                            <span class="flavor-filtro-texto">Con bicicletas</span>
                        </label>
                        <label class="flavor-filtro-opcion">
                            <input type="checkbox" checked data-filtro="con_espacios">
                            <span class="flavor-filtro-check"></span>
                            <span class="flavor-filtro-texto">Con espacios</span>
                        </label>
                        <label class="flavor-filtro-opcion">
                            <input type="checkbox" data-filtro="electricas">
                            <span class="flavor-filtro-check"></span>
                            <span class="flavor-filtro-texto">Eléctricas</span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flavor-estaciones-lista">
                    <div class="flavor-lista-header">
                        <span class="flavor-lista-titulo">Estaciones cercanas</span>
                        <span class="flavor-lista-contador"><?php echo count($estaciones); ?> estaciones</span>
                    </div>

                    <div class="flavor-lista-items">
                        <?php foreach ($estaciones as $estacion) :
                            $clase_estado = 'flavor-estacion-' . esc_attr($estacion['estado']);
                            $bicicletas_disponibles = intval($estacion['bicicletas_disponibles']);
                            $espacios_libres = intval($estacion['espacios_libres']);
                            $total_espacios = $bicicletas_disponibles + $espacios_libres;
                            $porcentaje_ocupacion = $total_espacios > 0 ? ($bicicletas_disponibles / $total_espacios) * 100 : 0;
                        ?>
                        <div class="flavor-estacion-item <?php echo $clase_estado; ?>"
                             data-lat="<?php echo esc_attr($estacion['latitud']); ?>"
                             data-lng="<?php echo esc_attr($estacion['longitud']); ?>"
                             data-id="<?php echo esc_attr($estacion['id']); ?>">
                            <div class="flavor-estacion-icono">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="18.5" cy="17.5" r="3.5"></circle>
                                    <circle cx="5.5" cy="17.5" r="3.5"></circle>
                                    <circle cx="15" cy="5" r="1"></circle>
                                    <path d="M12 17.5V14l-3-3 4-3 2 3h2"></path>
                                </svg>
                            </div>
                            <div class="flavor-estacion-info">
                                <h4 class="flavor-estacion-nombre"><?php echo esc_html($estacion['nombre']); ?></h4>
                                <p class="flavor-estacion-direccion"><?php echo esc_html($estacion['direccion']); ?></p>
                                <div class="flavor-estacion-disponibilidad">
                                    <div class="flavor-disponibilidad-barra">
                                        <div class="flavor-disponibilidad-progreso" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%;"></div>
                                    </div>
                                    <div class="flavor-disponibilidad-numeros">
                                        <span class="flavor-bicis-disponibles">
                                            <strong><?php echo esc_html($bicicletas_disponibles); ?></strong> bicis
                                        </span>
                                        <span class="flavor-espacios-libres">
                                            <strong><?php echo esc_html($espacios_libres); ?></strong> espacios
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button class="flavor-estacion-navegar" title="Cómo llegar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                                </svg>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flavor-mapa-wrapper">
                <div id="<?php echo esc_attr($id_mapa); ?>" class="flavor-mapa-elemento" style="height: <?php echo esc_attr($altura_mapa); ?>;"></div>

                <div class="flavor-mapa-leyenda">
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-marcador flavor-marcador-disponible"></span>
                        <span class="flavor-leyenda-texto">Disponible</span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-marcador flavor-marcador-limitado"></span>
                        <span class="flavor-leyenda-texto">Pocas bicis</span>
                    </div>
                    <div class="flavor-leyenda-item">
                        <span class="flavor-leyenda-marcador flavor-marcador-vacio"></span>
                        <span class="flavor-leyenda-texto">Sin bicis</span>
                    </div>
                </div>

                <div class="flavor-mapa-controles">
                    <button class="flavor-control-zoom flavor-zoom-in" title="Acercar">+</button>
                    <button class="flavor-control-zoom flavor-zoom-out" title="Alejar">-</button>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-mapa-bicicletas {
    padding: 4rem 0;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.flavor-mapa-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.flavor-mapa-header {
    text-align: center;
    margin-bottom: 3rem;
}

.flavor-mapa-titulo {
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 1rem;
}

.flavor-mapa-descripcion {
    font-size: 1.1rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

.flavor-mapa-contenido {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 1.5rem;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.flavor-mapa-sidebar {
    padding: 1.5rem;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    max-height: 600px;
}

.flavor-mapa-buscador {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.flavor-input-grupo {
    flex: 1;
    position: relative;
}

.flavor-input-icono {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.flavor-input-busqueda {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 2.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.flavor-input-busqueda:focus {
    outline: none;
    border-color: #00c853;
    box-shadow: 0 0 0 3px rgba(0, 200, 83, 0.1);
}

.flavor-boton-ubicacion {
    padding: 0.875rem;
    background: #00c853;
    color: #ffffff;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.flavor-boton-ubicacion:hover {
    background: #00b248;
    transform: scale(1.05);
}

.flavor-mapa-filtros {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.flavor-filtros-titulo {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.75rem;
}

.flavor-filtros-opciones {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.flavor-filtro-opcion {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: #374151;
}

.flavor-filtro-opcion input {
    display: none;
}

.flavor-filtro-check {
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.flavor-filtro-opcion input:checked + .flavor-filtro-check {
    background: #00c853;
    border-color: #00c853;
}

.flavor-filtro-opcion input:checked + .flavor-filtro-check::after {
    content: '✓';
    color: #ffffff;
    font-size: 12px;
    font-weight: bold;
}

.flavor-estaciones-lista {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.flavor-lista-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0.75rem;
}

.flavor-lista-titulo {
    font-weight: 600;
    color: #1f2937;
}

.flavor-lista-contador {
    font-size: 0.85rem;
    color: #6b7280;
}

.flavor-lista-items {
    flex: 1;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.flavor-estacion-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    margin-bottom: 0.5rem;
}

.flavor-estacion-item:hover {
    background: #f0fdf4;
    border-color: #00c853;
}

.flavor-estacion-icono {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #00c853 0%, #00e676 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    flex-shrink: 0;
}

.flavor-estacion-sin_bicicletas .flavor-estacion-icono {
    background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
}

.flavor-estacion-info {
    flex: 1;
    min-width: 0;
}

.flavor-estacion-nombre {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.flavor-estacion-direccion {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0 0 0.5rem 0;
}

.flavor-estacion-disponibilidad {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.flavor-disponibilidad-barra {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.flavor-disponibilidad-progreso {
    height: 100%;
    background: linear-gradient(90deg, #00c853 0%, #00e676 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.flavor-disponibilidad-numeros {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #6b7280;
}

.flavor-bicis-disponibles strong {
    color: #00c853;
}

.flavor-espacios-libres strong {
    color: #3b82f6;
}

.flavor-estacion-navegar {
    padding: 0.5rem;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.flavor-estacion-navegar:hover {
    background: #00c853;
    border-color: #00c853;
    color: #ffffff;
}

.flavor-mapa-wrapper {
    position: relative;
    min-height: 500px;
}

.flavor-mapa-elemento {
    width: 100%;
    height: 100%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 1rem;
}

.flavor-mapa-elemento::after {
    content: 'Mapa interactivo - Requiere API de mapas';
    text-align: center;
    padding: 2rem;
}

.flavor-mapa-leyenda {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.75rem 1rem;
    border-radius: 10px;
    display: flex;
    gap: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #374151;
}

.flavor-leyenda-marcador {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.flavor-marcador-disponible {
    background: #00c853;
}

.flavor-marcador-limitado {
    background: #fbbf24;
}

.flavor-marcador-vacio {
    background: #ef4444;
}

.flavor-mapa-controles {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.flavor-control-zoom {
    width: 36px;
    height: 36px;
    background: #ffffff;
    border: none;
    font-size: 1.25rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.flavor-zoom-in {
    border-radius: 8px 8px 0 0;
}

.flavor-zoom-out {
    border-radius: 0 0 8px 8px;
}

.flavor-control-zoom:hover {
    background: #f3f4f6;
}

@media (max-width: 1024px) {
    .flavor-mapa-contenido {
        grid-template-columns: 1fr;
    }

    .flavor-mapa-sidebar {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
        max-height: none;
    }

    .flavor-lista-items {
        max-height: 300px;
    }
}

@media (max-width: 640px) {
    .flavor-mapa-bicicletas {
        padding: 2rem 0;
    }

    .flavor-mapa-sidebar {
        padding: 1rem;
    }

    .flavor-filtros-opciones {
        flex-direction: column;
        gap: 0.5rem;
    }

    .flavor-mapa-leyenda {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const estacionesItems = document.querySelectorAll('.flavor-estacion-item');

    estacionesItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const latitud = this.dataset.lat;
            const longitud = this.dataset.lng;
            const nombreEstacion = this.querySelector('.flavor-estacion-nombre').textContent;

            // Remover clase activa de otros items
            estacionesItems.forEach(function(otroItem) {
                otroItem.classList.remove('flavor-estacion-activa');
            });

            // Agregar clase activa al item clickeado
            this.classList.add('flavor-estacion-activa');

            // Aquí se integraría con la API del mapa para centrar en la estación
            console.log('Estación seleccionada:', nombreEstacion, latitud, longitud);
        });
    });

    // Botón de navegación
    const botonesNavegar = document.querySelectorAll('.flavor-estacion-navegar');
    botonesNavegar.forEach(function(boton) {
        boton.addEventListener('click', function(evento) {
            evento.stopPropagation();
            const estacionItem = this.closest('.flavor-estacion-item');
            const latitud = estacionItem.dataset.lat;
            const longitud = estacionItem.dataset.lng;

            // Abrir Google Maps con direcciones
            const urlGoogleMaps = 'https://www.google.com/maps/dir/?api=1&destination=' + latitud + ',' + longitud;
            window.open(urlGoogleMaps, '_blank');
        });
    });
});
</script>
