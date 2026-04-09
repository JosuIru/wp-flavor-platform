<?php
/**
 * Componente: Map Viewer
 *
 * Mapa interactivo con Leaflet para mostrar ubicaciones y marcadores.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $markers     Array de marcadores: [['lat' => x, 'lng' => y, 'title' => '', 'popup' => '', 'icon' => '', 'color' => '']]
 * @param array  $center      Centro del mapa: ['lat' => x, 'lng' => y]
 * @param int    $zoom        Nivel de zoom (1-18)
 * @param string $height      Altura del mapa (CSS)
 * @param bool   $show_search Mostrar buscador de ubicaciones
 * @param bool   $show_locate Mostrar botón "Mi ubicación"
 * @param bool   $clustered   Agrupar marcadores cercanos
 * @param bool   $draggable   Marcadores arrastrables (para formularios)
 * @param string $module      Módulo para cargar marcadores via AJAX
 * @param string $id          ID único del mapa
 */

if (!defined('ABSPATH')) {
    exit;
}

$markers = $markers ?? [];
$center = $center ?? ['lat' => 40.4168, 'lng' => -3.7038]; // Madrid por defecto
$zoom = intval($zoom ?? 13);
$height = $height ?? '400px';
$show_search = $show_search ?? false;
$show_locate = $show_locate ?? true;
$clustered = $clustered ?? true;
$draggable = $draggable ?? false;
$module = $module ?? '';
$map_id = $id ?? 'flavor-map-' . wp_rand(1000, 9999);

// Colores para marcadores
$marker_colors = [
    'blue'   => '#3B82F6',
    'red'    => '#EF4444',
    'green'  => '#22C55E',
    'yellow' => '#EAB308',
    'purple' => '#A855F7',
    'orange' => '#F97316',
    'gray'   => '#6B7280',
];
?>

<div class="flavor-map-viewer relative rounded-xl overflow-hidden shadow-lg" id="<?php echo esc_attr($map_id); ?>-wrapper">

    <!-- Controles superiores -->
    <div class="absolute top-3 left-3 right-3 z-[1000] flex items-center gap-2">
        <?php if ($show_search): ?>
            <div class="flex-1 max-w-sm">
                <div class="relative">
                    <input type="text"
                           id="<?php echo esc_attr($map_id); ?>-search"
                           class="w-full pl-10 pr-4 py-2 bg-white rounded-lg shadow-md border-0 text-sm focus:ring-2 focus:ring-blue-500"
                           placeholder="<?php esc_attr_e('Buscar ubicación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                </div>
                <div id="<?php echo esc_attr($map_id); ?>-search-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
            </div>
        <?php endif; ?>

        <?php if ($show_locate): ?>
            <button type="button"
                    id="<?php echo esc_attr($map_id); ?>-locate"
                    class="p-2 bg-white rounded-lg shadow-md hover:bg-gray-50 transition-colors"
                    title="<?php esc_attr_e('Mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        <?php endif; ?>
    </div>

    <!-- Contenedor del mapa -->
    <div id="<?php echo esc_attr($map_id); ?>"
         class="flavor-leaflet-map w-full"
         style="height: <?php echo esc_attr($height); ?>;"
         data-center='<?php echo json_encode($center); ?>'
         data-zoom="<?php echo esc_attr($zoom); ?>"
         data-module="<?php echo esc_attr($module); ?>"
         data-clustered="<?php echo $clustered ? 'true' : 'false'; ?>"
         data-draggable="<?php echo $draggable ? 'true' : 'false'; ?>">
    </div>

    <!-- Leyenda (si hay marcadores con categorías) -->
    <?php
    $categories = [];
    foreach ($markers as $marker) {
        if (!empty($marker['category'])) {
            $categories[$marker['category']] = $marker['color'] ?? 'blue';
        }
    }
    if (!empty($categories)):
    ?>
        <div class="absolute bottom-3 left-3 z-[1000] bg-white rounded-lg shadow-md p-3">
            <p class="text-xs font-medium text-gray-500 mb-2"><?php esc_html_e('Leyenda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="space-y-1">
                <?php foreach ($categories as $cat => $color): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-3 h-3 rounded-full" style="background-color: <?php echo esc_attr($marker_colors[$color] ?? $color); ?>;"></span>
                        <span class="text-gray-700"><?php echo esc_html($cat); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loading overlay -->
    <div id="<?php echo esc_attr($map_id); ?>-loading" class="absolute inset-0 bg-gray-100 flex items-center justify-center z-[999]">
        <div class="flex items-center gap-2 text-gray-500">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span><?php esc_html_e('Cargando mapa...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>
</div>

<!-- Input oculto para formularios (coordenadas seleccionadas) -->
<?php if ($draggable): ?>
    <input type="hidden" id="<?php echo esc_attr($map_id); ?>-lat" name="latitude" value="">
    <input type="hidden" id="<?php echo esc_attr($map_id); ?>-lng" name="longitude" value="">
    <input type="hidden" id="<?php echo esc_attr($map_id); ?>-address" name="address" value="">
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapId = '<?php echo esc_js($map_id); ?>';
    const mapEl = document.getElementById(mapId);
    const loadingEl = document.getElementById(mapId + '-loading');

    if (!mapEl || typeof L === 'undefined') {
        if (loadingEl) loadingEl.innerHTML = '<p class="text-red-500">Error: Leaflet no está cargado</p>';
        return;
    }

    // Configuración inicial
    const center = JSON.parse(mapEl.dataset.center);
    const zoom = parseInt(mapEl.dataset.zoom);
    const module = mapEl.dataset.module;
    const clustered = mapEl.dataset.clustered === 'true';
    const draggable = mapEl.dataset.draggable === 'true';

    // Inicializar mapa
    const map = L.map(mapId).setView([center.lat, center.lng], zoom);

    // Capa de tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Ocultar loading
    if (loadingEl) loadingEl.style.display = 'none';

    // Colores de marcadores
    const markerColors = <?php echo json_encode($marker_colors); ?>;

    // Función para crear icono personalizado
    function createIcon(color) {
        const hexColor = markerColors[color] || color || markerColors.blue;
        return L.divIcon({
            html: `<svg width="25" height="41" viewBox="0 0 25 41" fill="${hexColor}" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.5 0C5.6 0 0 5.6 0 12.5c0 8.8 12.5 28.5 12.5 28.5S25 21.3 25 12.5C25 5.6 19.4 0 12.5 0zm0 17c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"/>
            </svg>`,
            className: 'flavor-map-marker',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [0, -41]
        });
    }

    // Grupo de marcadores
    let markerGroup = clustered && typeof L.markerClusterGroup !== 'undefined'
        ? L.markerClusterGroup()
        : L.layerGroup();

    // Marcadores iniciales
    const initialMarkers = <?php echo json_encode($markers); ?>;

    function addMarkers(markers) {
        markerGroup.clearLayers();
        markers.forEach(m => {
            if (!m.lat || !m.lng) return;

            const marker = L.marker([m.lat, m.lng], {
                icon: createIcon(m.color),
                draggable: draggable && m.draggable !== false
            });

            if (m.popup || m.title) {
                marker.bindPopup(`
                    <div class="flavor-map-popup p-2">
                        ${m.title ? `<strong class="block text-gray-900">${m.title}</strong>` : ''}
                        ${m.popup ? `<p class="text-sm text-gray-600 mt-1">${m.popup}</p>` : ''}
                        ${m.url ? `<a href="${m.url}" class="text-sm text-blue-600 hover:underline mt-2 inline-block">Ver más →</a>` : ''}
                    </div>
                `);
            }

            if (draggable) {
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    document.getElementById(mapId + '-lat').value = pos.lat;
                    document.getElementById(mapId + '-lng').value = pos.lng;
                });
            }

            markerGroup.addLayer(marker);
        });

        map.addLayer(markerGroup);

        // Ajustar vista si hay marcadores
        if (markers.length > 0) {
            const bounds = markerGroup.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
            }
        }
    }

    // Cargar marcadores iniciales o via AJAX
    if (initialMarkers.length > 0) {
        addMarkers(initialMarkers);
    } else if (module) {
        fetch(flavorAjax.url + '?action=flavor_get_map_markers&module=' + module + '&_wpnonce=' + flavorAjax.nonce)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.markers) {
                    addMarkers(data.data.markers);
                }
            });
    }

    // Botón de ubicación
    const locateBtn = document.getElementById(mapId + '-locate');
    if (locateBtn) {
        locateBtn.addEventListener('click', function() {
            map.locate({ setView: true, maxZoom: 16 });
        });

        map.on('locationfound', function(e) {
            L.marker(e.latlng, { icon: createIcon('blue') })
                .addTo(map)
                .bindPopup('<?php esc_html_e('Tu ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')
                .openPopup();

            if (draggable) {
                document.getElementById(mapId + '-lat').value = e.latlng.lat;
                document.getElementById(mapId + '-lng').value = e.latlng.lng;
            }
        });
    }

    // Buscador de ubicaciones
    const searchInput = document.getElementById(mapId + '-search');
    const searchResults = document.getElementById(mapId + '-search-results');

    if (searchInput && searchResults) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 3) {
                searchResults.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
                    .then(r => r.json())
                    .then(results => {
                        if (results.length === 0) {
                            searchResults.innerHTML = '<p class="p-3 text-sm text-gray-500"><?php esc_html_e('Sin resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>';
                        } else {
                            searchResults.innerHTML = results.map(r => `
                                <button type="button" class="w-full text-left p-3 hover:bg-gray-50 border-b last:border-0 text-sm"
                                        data-lat="${r.lat}" data-lng="${r.lon}" data-name="${r.display_name}">
                                    ${r.display_name}
                                </button>
                            `).join('');

                            searchResults.querySelectorAll('button').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const lat = parseFloat(this.dataset.lat);
                                    const lng = parseFloat(this.dataset.lng);
                                    map.setView([lat, lng], 15);
                                    searchResults.classList.add('hidden');
                                    searchInput.value = this.dataset.name;

                                    if (draggable) {
                                        document.getElementById(mapId + '-lat').value = lat;
                                        document.getElementById(mapId + '-lng').value = lng;
                                        document.getElementById(mapId + '-address').value = this.dataset.name;

                                        // Añadir marcador arrastrable
                                        markerGroup.clearLayers();
                                        const marker = L.marker([lat, lng], { icon: createIcon('blue'), draggable: true }).addTo(markerGroup);
                                        marker.on('dragend', function(e) {
                                            const pos = e.target.getLatLng();
                                            document.getElementById(mapId + '-lat').value = pos.lat;
                                            document.getElementById(mapId + '-lng').value = pos.lng;
                                        });
                                    }
                                });
                            });
                        }
                        searchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        // Cerrar resultados al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }

    // Exponer el mapa para uso externo
    window['flavorMap_' + mapId] = map;
});
</script>
