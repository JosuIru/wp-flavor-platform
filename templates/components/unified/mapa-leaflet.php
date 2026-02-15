<?php
/**
 * Template Unificado: Mapa Interactivo con Leaflet/OpenStreetMap
 *
 * Reutilizable para cualquier módulo que necesite mostrar ubicaciones.
 * No requiere API key - usa OpenStreetMap gratuito.
 *
 * Variables esperadas:
 *   $titulo           (string) Título de la sección
 *   $subtitulo        (string) Subtítulo/descripción
 *   $marcadores       (array)  Lista de marcadores con estructura:
 *                              - id: ID único
 *                              - nombre: Nombre/título del marcador
 *                              - direccion: Dirección o descripción
 *                              - lat: Latitud
 *                              - lng: Longitud
 *                              - estado: 'disponible'|'limitado'|'vacio'|'activo'|'inactivo' (opcional)
 *                              - valor: Número a mostrar en el marcador (opcional)
 *                              - valor_total: Total para barra de progreso (opcional)
 *                              - icono: Clase de icono dashicons (opcional)
 *                              - color: Color personalizado hex (opcional)
 *                              - datos_extra: Array de datos adicionales para el popup (opcional)
 *   $centro_lat       (float)  Latitud del centro (opcional, calcula automático)
 *   $centro_lng       (float)  Longitud del centro (opcional, calcula automático)
 *   $zoom             (int)    Nivel de zoom inicial (default: 13)
 *   $altura_mapa      (string) Altura del mapa (default: '400px')
 *   $mostrar_lista    (bool)   Mostrar lista de elementos debajo (default: true)
 *   $mostrar_leyenda  (bool)   Mostrar leyenda de colores (default: true)
 *   $mostrar_buscar   (bool)   Mostrar botón de ubicación (default: true)
 *   $color_primario   (string) Color primario del tema (default: '#4f46e5')
 *   $texto_boton      (string) Texto del botón de acción (default: 'Cómo llegar')
 *   $modulo_id        (string) ID del módulo para clases CSS específicas
 *   $etiquetas        (array)  Etiquetas personalizadas para estados:
 *                              - disponible: 'Disponible'
 *                              - limitado: 'Pocas unidades'
 *                              - vacio: 'Sin disponibilidad'
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo_seccion = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$lista_marcadores = $marcadores ?? [];
$zoom_inicial = $zoom ?? 13;
$altura_contenedor = $altura_mapa ?? '400px';
$mostrar_lista_elementos = $mostrar_lista ?? true;
$mostrar_leyenda_colores = $mostrar_leyenda ?? true;
$mostrar_boton_ubicacion = $mostrar_buscar ?? true;
$color_principal = $color_primario ?? '#4f46e5';
$texto_btn_accion = $texto_boton ?? __('Cómo llegar', 'flavor-chat-ia');
$id_modulo = $modulo_id ?? 'generico';

// Etiquetas de estado personalizables
$etiquetas_estado = wp_parse_args($etiquetas ?? [], [
    'disponible' => __('Disponible', 'flavor-chat-ia'),
    'limitado' => __('Limitado', 'flavor-chat-ia'),
    'vacio' => __('Sin disponibilidad', 'flavor-chat-ia'),
]);

// Calcular centro del mapa si no se proporciona
$latitud_centro = $centro_lat ?? null;
$longitud_centro = $centro_lng ?? null;

if (($latitud_centro === null || $longitud_centro === null) && !empty($lista_marcadores)) {
    // Usar la primera ubicación válida como centro
    foreach ($lista_marcadores as $marcador) {
        if (!empty($marcador['lat']) && !empty($marcador['lng'])) {
            $latitud_centro = (float) $marcador['lat'];
            $longitud_centro = (float) $marcador['lng'];
            break;
        }
    }
}

// Valores por defecto si no hay marcadores (Madrid)
$latitud_centro = $latitud_centro ?? 40.416775;
$longitud_centro = $longitud_centro ?? -3.703790;

// Preparar marcadores para JavaScript
$marcadores_json = [];
foreach ($lista_marcadores as $marcador) {
    if (empty($marcador['lat']) || empty($marcador['lng'])) continue;

    $marcadores_json[] = [
        'id' => $marcador['id'] ?? uniqid(),
        'nombre' => $marcador['nombre'] ?? '',
        'direccion' => $marcador['direccion'] ?? '',
        'lat' => (float) $marcador['lat'],
        'lng' => (float) $marcador['lng'],
        'estado' => $marcador['estado'] ?? 'disponible',
        'valor' => $marcador['valor'] ?? null,
        'valor_total' => $marcador['valor_total'] ?? null,
        'icono' => $marcador['icono'] ?? 'dashicons-location-alt',
        'color' => $marcador['color'] ?? null,
        'datos_extra' => $marcador['datos_extra'] ?? [],
    ];
}

// ID único para este mapa
$id_mapa = 'flavor-mapa-' . $id_modulo . '-' . uniqid();
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div class="flavor-mapa-unificado flavor-mapa-<?php echo esc_attr($id_modulo); ?>" style="--fmu-color: <?php echo esc_attr($color_principal); ?>;">

    <?php if ($titulo_seccion || $subtitulo_seccion): ?>
    <div class="fmu-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="fmu-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
        <?php endif; ?>
        <?php if ($subtitulo_seccion): ?>
            <p class="fmu-subtitulo"><?php echo esc_html($subtitulo_seccion); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Contenedor del mapa -->
    <div class="fmu-mapa-contenedor">
        <div id="<?php echo esc_attr($id_mapa); ?>" class="fmu-mapa-canvas" style="height: <?php echo esc_attr($altura_contenedor); ?>;"></div>

        <?php if (empty($lista_marcadores)): ?>
        <div class="fmu-mapa-overlay">
            <span class="dashicons dashicons-location"></span>
            <p><?php esc_html_e('No hay ubicaciones disponibles', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($mostrar_boton_ubicacion): ?>
        <button class="fmu-btn-ubicacion" id="<?php echo esc_attr($id_mapa); ?>-ubicacion" title="<?php esc_attr_e('Usar mi ubicación', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-location"></span>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($mostrar_leyenda_colores): ?>
    <div class="fmu-leyenda">
        <div class="fmu-leyenda-item">
            <span class="fmu-leyenda-marker fmu-estado-disponible"></span>
            <span><?php echo esc_html($etiquetas_estado['disponible']); ?></span>
        </div>
        <div class="fmu-leyenda-item">
            <span class="fmu-leyenda-marker fmu-estado-limitado"></span>
            <span><?php echo esc_html($etiquetas_estado['limitado']); ?></span>
        </div>
        <div class="fmu-leyenda-item">
            <span class="fmu-leyenda-marker fmu-estado-vacio"></span>
            <span><?php echo esc_html($etiquetas_estado['vacio']); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($mostrar_lista_elementos && !empty($lista_marcadores)): ?>
    <div class="fmu-lista">
        <div class="fmu-lista-grid">
            <?php foreach ($lista_marcadores as $marcador):
                if (empty($marcador['lat']) || empty($marcador['lng'])) continue;

                $estado = $marcador['estado'] ?? 'disponible';
                $valor = $marcador['valor'] ?? null;
                $valor_total = $marcador['valor_total'] ?? null;
                $porcentaje = ($valor !== null && $valor_total > 0) ? round(($valor / $valor_total) * 100) : null;
            ?>
            <div class="fmu-item"
                 data-id="<?php echo esc_attr($marcador['id'] ?? ''); ?>"
                 data-lat="<?php echo esc_attr($marcador['lat']); ?>"
                 data-lng="<?php echo esc_attr($marcador['lng']); ?>">

                <div class="fmu-item-icono fmu-estado-<?php echo esc_attr($estado); ?>">
                    <span class="dashicons <?php echo esc_attr($marcador['icono'] ?? 'dashicons-location-alt'); ?>"></span>
                </div>

                <div class="fmu-item-info">
                    <h4 class="fmu-item-nombre"><?php echo esc_html($marcador['nombre'] ?? ''); ?></h4>

                    <?php if (!empty($marcador['direccion'])): ?>
                    <div class="fmu-item-direccion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($marcador['direccion']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($valor !== null): ?>
                    <div class="fmu-item-stats">
                        <span class="fmu-item-valor"><?php echo esc_html($valor); ?></span>
                        <?php if ($valor_total !== null): ?>
                        <span class="fmu-item-total">/ <?php echo esc_html($valor_total); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($porcentaje !== null): ?>
                    <div class="fmu-item-barra">
                        <div class="fmu-item-progreso fmu-estado-<?php echo esc_attr($estado); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="fmu-item-acciones">
                    <button class="fmu-btn-direcciones"
                            data-lat="<?php echo esc_attr($marcador['lat']); ?>"
                            data-lng="<?php echo esc_attr($marcador['lng']); ?>">
                        <span class="dashicons dashicons-location-alt"></span>
                        <?php echo esc_html($texto_btn_accion); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-mapa-unificado {
    --fmu-disponible: #10b981;
    --fmu-limitado: #f59e0b;
    --fmu-vacio: #ef4444;
    max-width: 1000px;
    margin: 0 auto;
    padding: 1rem;
}

.fmu-header {
    text-align: center;
    margin-bottom: 1.5rem;
}
.fmu-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}
.fmu-subtitulo {
    margin: 0;
    color: #6b7280;
}

.fmu-mapa-contenedor {
    position: relative;
    margin-bottom: 1rem;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.fmu-mapa-canvas {
    width: 100%;
    background: #e5e7eb;
    z-index: 1;
}
.fmu-mapa-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
.fmu-mapa-overlay .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
    margin-bottom: 1rem;
}
.fmu-mapa-overlay p {
    margin: 0;
    color: #6b7280;
}

.fmu-btn-ubicacion {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000;
    width: 40px;
    height: 40px;
    background: white;
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.fmu-btn-ubicacion:hover {
    background: var(--fmu-color);
    color: white;
}
.fmu-btn-ubicacion .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.fmu-leyenda {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}
.fmu-leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #4b5563;
}
.fmu-leyenda-marker {
    width: 14px;
    height: 14px;
    border-radius: 50%;
}
.fmu-estado-disponible, .fmu-leyenda-marker.fmu-estado-disponible { background: var(--fmu-disponible); }
.fmu-estado-limitado, .fmu-leyenda-marker.fmu-estado-limitado { background: var(--fmu-limitado); }
.fmu-estado-vacio, .fmu-leyenda-marker.fmu-estado-vacio { background: var(--fmu-vacio); }
.fmu-estado-activo { background: var(--fmu-disponible); }
.fmu-estado-inactivo { background: #9ca3af; }

.fmu-lista-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.fmu-item {
    display: flex;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
}
.fmu-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.fmu-item.active {
    border-color: var(--fmu-color);
}

.fmu-item-icono {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.fmu-item-icono .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: white;
}

.fmu-item-info {
    flex: 1;
    min-width: 0;
}
.fmu-item-nombre {
    margin: 0 0 0.35rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
}
.fmu-item-direccion {
    font-size: 0.8rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}
.fmu-item-direccion .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}
.fmu-item-stats {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
    margin-bottom: 0.35rem;
}
.fmu-item-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}
.fmu-item-total {
    font-size: 0.8rem;
    color: #9ca3af;
}
.fmu-item-barra {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}
.fmu-item-progreso {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s;
}

.fmu-item-acciones {
    display: flex;
    align-items: center;
}
.fmu-btn-direcciones {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}
.fmu-btn-direcciones:hover {
    background: #f3f4f6;
}
.fmu-btn-direcciones .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Popups de Leaflet */
.leaflet-popup-content-wrapper {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.leaflet-popup-content {
    margin: 12px 16px;
    min-width: 180px;
}
.fmu-popup h4 {
    margin: 0 0 8px;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}
.fmu-popup-direccion {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 8px;
}
.fmu-popup-stats {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin-bottom: 10px;
}
.fmu-popup-valor {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}
.fmu-popup-total {
    font-size: 0.75rem;
    color: #6b7280;
}
.fmu-popup-btn {
    display: block;
    width: 100%;
    padding: 8px 12px;
    background: var(--fmu-color, #4f46e5);
    color: white;
    text-align: center;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
}
.fmu-popup-btn:hover {
    filter: brightness(0.9);
}

@media (max-width: 640px) {
    .fmu-leyenda {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .fmu-lista-grid {
        grid-template-columns: 1fr;
    }
    .fmu-item {
        flex-direction: column;
        text-align: center;
    }
    .fmu-item-icono {
        margin: 0 auto;
    }
    .fmu-item-direccion {
        justify-content: center;
    }
    .fmu-item-stats {
        justify-content: center;
    }
    .fmu-item-acciones {
        justify-content: center;
        margin-top: 0.5rem;
    }
}
</style>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
(function() {
    'use strict';

    var CONFIG = {
        idMapa: '<?php echo esc_js($id_mapa); ?>',
        marcadores: <?php echo wp_json_encode($marcadores_json); ?>,
        centroLat: <?php echo (float) $latitud_centro; ?>,
        centroLng: <?php echo (float) $longitud_centro; ?>,
        zoom: <?php echo (int) $zoom_inicial; ?>,
        colorPrimario: '<?php echo esc_js($color_principal); ?>',
        textoBoton: '<?php echo esc_js($texto_btn_accion); ?>',
        colores: {
            disponible: '#10b981',
            limitado: '#f59e0b',
            vacio: '#ef4444',
            activo: '#10b981',
            inactivo: '#9ca3af'
        }
    };

    var mapa = null;
    var marcadoresMap = {};

    document.addEventListener('DOMContentLoaded', initMapa);

    function initMapa() {
        var contenedor = document.getElementById(CONFIG.idMapa);
        if (!contenedor) return;

        // Inicializar mapa
        mapa = L.map(CONFIG.idMapa).setView([CONFIG.centroLat, CONFIG.centroLng], CONFIG.zoom);

        // Capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mapa);

        // Agregar marcadores
        if (CONFIG.marcadores && CONFIG.marcadores.length > 0) {
            CONFIG.marcadores.forEach(agregarMarcador);

            // Ajustar vista para mostrar todos
            if (CONFIG.marcadores.length > 1) {
                var grupo = L.featureGroup(Object.values(marcadoresMap));
                mapa.fitBounds(grupo.getBounds().pad(0.1));
            }
        }

        // Eventos de items en la lista
        document.querySelectorAll('.fmu-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.fmu-btn-direcciones')) return;

                var id = this.dataset.id;
                var lat = parseFloat(this.dataset.lat);
                var lng = parseFloat(this.dataset.lng);

                mapa.setView([lat, lng], 16);

                if (marcadoresMap[id]) {
                    marcadoresMap[id].openPopup();
                }

                document.querySelectorAll('.fmu-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Botones de direcciones
        document.querySelectorAll('.fmu-btn-direcciones').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                abrirDirecciones(this.dataset.lat, this.dataset.lng);
            });
        });

        // Botón de ubicación
        var btnUbicacion = document.getElementById(CONFIG.idMapa + '-ubicacion');
        if (btnUbicacion) {
            btnUbicacion.addEventListener('click', centrarEnUsuario);
        }
    }

    function agregarMarcador(datos) {
        if (!datos.lat || !datos.lng) return;

        var color = datos.color || CONFIG.colores[datos.estado] || CONFIG.colores.disponible;

        // Icono personalizado
        var valorMostrar = datos.valor !== null ? datos.valor : '';
        var iconoHtml = '<div style="' +
            'background:' + color + ';' +
            'width:36px;height:36px;' +
            'border-radius:50% 50% 50% 0;' +
            'transform:rotate(-45deg);' +
            'border:3px solid white;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.3);' +
            'display:flex;align-items:center;justify-content:center;' +
            '">' +
            '<span style="transform:rotate(45deg);color:white;font-weight:bold;font-size:12px;">' +
            valorMostrar + '</span></div>';

        var icono = L.divIcon({
            html: iconoHtml,
            className: 'fmu-marcador',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        });

        // Popup
        var statsHtml = '';
        if (datos.valor !== null) {
            statsHtml = '<div class="fmu-popup-stats">' +
                '<span class="fmu-popup-valor">' + datos.valor + '</span>' +
                (datos.valor_total ? '<span class="fmu-popup-total">/ ' + datos.valor_total + '</span>' : '') +
                '</div>';
        }

        var popupHtml = '<div class="fmu-popup">' +
            '<h4>' + escapeHtml(datos.nombre) + '</h4>' +
            (datos.direccion ? '<div class="fmu-popup-direccion">' + escapeHtml(datos.direccion) + '</div>' : '') +
            statsHtml +
            '<button class="fmu-popup-btn" onclick="window.flavorMapaAbrirDirecciones(' + datos.lat + ',' + datos.lng + ')">' +
            CONFIG.textoBoton + '</button></div>';

        var marcador = L.marker([datos.lat, datos.lng], { icon: icono })
            .addTo(mapa)
            .bindPopup(popupHtml);

        marcador.on('popupopen', function() {
            document.querySelectorAll('.fmu-item').forEach(function(item) {
                item.classList.remove('active');
                if (item.dataset.id == datos.id) {
                    item.classList.add('active');
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });

        marcadoresMap[datos.id] = marcador;
    }

    function centrarEnUsuario() {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                mapa.setView([lat, lng], 15);

                // Marcador temporal de ubicación
                L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: '<div style="background:#3b82f6;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 0 3px rgba(59,130,246,0.3);"></div>',
                        className: 'fmu-ubicacion-usuario',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(mapa);
            },
            function() {
                alert('No se pudo obtener tu ubicación');
            }
        );
    }

    function abrirDirecciones(lat, lng) {
        if (!lat || !lng) return;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var origen = pos.coords.latitude + ',' + pos.coords.longitude;
                    window.open('https://www.openstreetmap.org/directions?from=' + origen + '&to=' + lat + ',' + lng, '_blank');
                },
                function() {
                    window.open('https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=17/' + lat + '/' + lng, '_blank');
                }
            );
        } else {
            window.open('https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=17/' + lat + '/' + lng, '_blank');
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Exponer función global para popups
    window.flavorMapaAbrirDirecciones = abrirDirecciones;
})();
</script>
