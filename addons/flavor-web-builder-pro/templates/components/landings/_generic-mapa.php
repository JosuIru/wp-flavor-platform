<?php
/**
 * Template Genérico: Mapa
 *
 * Variables disponibles:
 * - $titulo (string): Título de la sección
 * - $subtitulo (string): Subtítulo
 * - $lat (float): Latitud central
 * - $lng (float): Longitud central
 * - $zoom (int): Nivel de zoom
 * - $marcadores (array): Array de marcadores
 * - $altura (string): Altura del mapa
 * - $color_primario (string): Color primario
 * - $mostrar_listado (bool): Mostrar listado lateral
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$titulo = $titulo ?? '';
$subtitulo = $subtitulo ?? '';
$lat = floatval($lat ?? 40.4168);
$lng = floatval($lng ?? -3.7038);
$zoom = intval($zoom ?? 13);
$marcadores = $marcadores ?? [];
$altura = $altura ?? '500px';
$color_primario = $color_primario ?? '#4f46e5';
$mostrar_listado = $mostrar_listado ?? false;

// Generar ID único
$unique_id = 'fgm-' . wp_unique_id();
$map_id = 'map-' . wp_unique_id();
?>

<section class="flavor-generic-mapa <?php echo esc_attr($unique_id); ?>"
         style="--fgm-primary: <?php echo esc_attr($color_primario); ?>;">

    <?php if (!empty($titulo) || !empty($subtitulo)) : ?>
    <header class="fgm-header">
        <?php if (!empty($titulo)) : ?>
            <h2 class="fgm-title"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>
        <?php if (!empty($subtitulo)) : ?>
            <p class="fgm-subtitle"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <div class="fgm-wrapper <?php echo $mostrar_listado ? 'fgm-wrapper--with-sidebar' : ''; ?>">
        <div id="<?php echo esc_attr($map_id); ?>" class="fgm-map" style="height: <?php echo esc_attr($altura); ?>;"></div>

        <?php if ($mostrar_listado && !empty($marcadores)) : ?>
        <aside class="fgm-sidebar">
            <h3 class="fgm-sidebar__title"><?php esc_html_e('Ubicaciones', 'flavor-chat-ia'); ?></h3>
            <ul class="fgm-list">
                <?php foreach ($marcadores as $index => $marcador) :
                    $nombre = $marcador['nombre'] ?? $marcador['titulo'] ?? '';
                    $direccion = $marcador['direccion'] ?? $marcador['descripcion'] ?? '';
                ?>
                <li class="fgm-list__item" data-marker="<?php echo esc_attr($index); ?>">
                    <span class="fgm-list__number"><?php echo esc_html($index + 1); ?></span>
                    <div class="fgm-list__content">
                        <strong class="fgm-list__name"><?php echo esc_html($nombre); ?></strong>
                        <?php if (!empty($direccion)) : ?>
                            <span class="fgm-list__address"><?php echo esc_html($direccion); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-generic-mapa {
    padding: 40px 20px;
}

.fgm-header {
    text-align: center;
    margin-bottom: 32px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.fgm-title {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.fgm-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
}

.fgm-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.fgm-wrapper--with-sidebar {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

.fgm-map {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.fgm-sidebar {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.fgm-sidebar__title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    padding: 16px 20px;
    margin: 0;
    border-bottom: 1px solid #f3f4f6;
}

.fgm-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

.fgm-list__item {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background 0.2s;
}

.fgm-list__item:hover {
    background: #f9fafb;
}

.fgm-list__number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--fgm-primary);
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.fgm-list__content {
    flex: 1;
    min-width: 0;
}

.fgm-list__name {
    display: block;
    font-size: 0.9375rem;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fgm-list__address {
    display: block;
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 1024px) {
    .fgm-wrapper--with-sidebar {
        grid-template-columns: 1fr;
    }

    .fgm-sidebar {
        order: -1;
    }

    .fgm-list {
        max-height: 200px;
    }
}

@media (max-width: 640px) {
    .fgm-title {
        font-size: 1.5rem;
    }
}
</style>

<?php if (!empty($marcadores)) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapContainer = document.getElementById('<?php echo esc_js($map_id); ?>');
    if (!mapContainer) return;

    // Placeholder para cuando no hay librería de mapas
    mapContainer.innerHTML = '<div style="text-align: center; padding: 40px;"><span class="dashicons dashicons-location" style="font-size: 48px; opacity: 0.5;"></span><p style="margin: 16px 0 0;">Mapa: <?php echo count($marcadores); ?> ubicaciones</p></div>';

    // Si hay Leaflet disponible
    if (typeof L !== 'undefined') {
        mapContainer.innerHTML = '';
        var map = L.map('<?php echo esc_js($map_id); ?>').setView([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], <?php echo esc_js($zoom); ?>);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marcadores = <?php echo json_encode($marcadores); ?>;
        marcadores.forEach(function(m, i) {
            if (m.lat && m.lng) {
                L.marker([m.lat, m.lng]).addTo(map)
                    .bindPopup('<strong>' + (m.nombre || m.titulo || '') + '</strong><br>' + (m.direccion || m.descripcion || ''));
            }
        });
    }
});
</script>
<?php endif; ?>
