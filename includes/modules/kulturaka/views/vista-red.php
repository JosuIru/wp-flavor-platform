<?php
/**
 * Vista Red - Kulturaka
 * Mapa de nodos de la red cultural descentralizada
 *
 * @package FlavorPlatform
 * @subpackage Modules\Kulturaka
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas
$tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';
$tabla_conexiones = $wpdb->prefix . 'flavor_kulturaka_conexiones';

// Obtener todos los nodos activos
$nodos = $wpdb->get_results(
    "SELECT * FROM $tabla_nodos
     WHERE estado = 'activo'
     ORDER BY destacado DESC, nombre ASC"
);

// Obtener conexiones entre nodos
$conexiones = $wpdb->get_results(
    "SELECT c.*,
            n1.nombre as origen_nombre, n1.latitud as origen_lat, n1.longitud as origen_lng,
            n2.nombre as destino_nombre, n2.latitud as destino_lat, n2.longitud as destino_lng
     FROM $tabla_conexiones c
     JOIN $tabla_nodos n1 ON c.nodo_origen_id = n1.id
     JOIN $tabla_nodos n2 ON c.nodo_destino_id = n2.id
     WHERE c.estado = 'activa'"
) ?: [];

// Estadísticas de la red
$stats = [
    'total_nodos' => count($nodos),
    'espacios' => count(array_filter($nodos, fn($n) => $n->tipo === 'espacio')),
    'comunidades' => count(array_filter($nodos, fn($n) => $n->tipo === 'comunidad')),
    'colectivos' => count(array_filter($nodos, fn($n) => $n->tipo === 'colectivo')),
    'conexiones' => count($conexiones),
    'ciudades' => count(array_unique(array_filter(array_column($nodos, 'ciudad')))),
];

// Agrupar nodos por ciudad para lista
$nodos_por_ciudad = [];
foreach ($nodos as $nodo) {
    $ciudad = $nodo->ciudad ?: 'Sin ubicación';
    if (!isset($nodos_por_ciudad[$ciudad])) {
        $nodos_por_ciudad[$ciudad] = [];
    }
    $nodos_por_ciudad[$ciudad][] = $nodo;
}
ksort($nodos_por_ciudad);

// Preparar datos para el mapa JS
$nodos_mapa = array_map(function($nodo) {
    return [
        'id' => $nodo->id,
        'nombre' => $nodo->nombre,
        'tipo' => $nodo->tipo,
        'ciudad' => $nodo->ciudad,
        'lat' => (float)$nodo->latitud,
        'lng' => (float)$nodo->longitud,
        'imagen' => $nodo->imagen_principal,
        'color' => $nodo->color_marca ?: '#ec4899',
        'verificado' => (bool)$nodo->verificado,
        'destacado' => (bool)$nodo->destacado,
        'eventos' => (int)$nodo->eventos_realizados,
        'artistas' => (int)$nodo->artistas_apoyados,
        'indice' => (float)$nodo->indice_cooperacion,
        'acepta_propuestas' => (bool)$nodo->acepta_propuestas,
    ];
}, array_filter($nodos, fn($n) => $n->latitud && $n->longitud));

$conexiones_mapa = array_map(function($c) {
    return [
        'origen' => ['lat' => (float)$c->origen_lat, 'lng' => (float)$c->origen_lng],
        'destino' => ['lat' => (float)$c->destino_lat, 'lng' => (float)$c->destino_lng],
        'tipo' => $c->tipo,
    ];
}, $conexiones);
?>

<div class="vista-red">
    <!-- Header con stats -->
    <div class="red-header">
        <div class="red-title">
            <span class="dashicons dashicons-networking"></span>
            <div>
                <h2>Red Cultural Descentralizada</h2>
                <p>Conectando espacios, artistas y comunidades</p>
            </div>
        </div>

        <div class="red-stats">
            <div class="stat">
                <span class="number"><?php echo $stats['total_nodos']; ?></span>
                <span class="label">Nodos</span>
            </div>
            <div class="stat">
                <span class="number"><?php echo $stats['conexiones']; ?></span>
                <span class="label">Conexiones</span>
            </div>
            <div class="stat">
                <span class="number"><?php echo $stats['ciudades']; ?></span>
                <span class="label">Ciudades</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="red-filtros">
        <div class="filtro-grupo">
            <label>Tipo:</label>
            <button class="filtro-btn active" data-tipo="todos">Todos</button>
            <button class="filtro-btn" data-tipo="espacio">
                <span class="dot espacio"></span> Espacios (<?php echo $stats['espacios']; ?>)
            </button>
            <button class="filtro-btn" data-tipo="comunidad">
                <span class="dot comunidad"></span> Comunidades (<?php echo $stats['comunidades']; ?>)
            </button>
            <button class="filtro-btn" data-tipo="colectivo">
                <span class="dot colectivo"></span> Colectivos (<?php echo $stats['colectivos']; ?>)
            </button>
        </div>

        <div class="filtro-grupo">
            <label>Ciudad:</label>
            <select id="filtro-ciudad">
                <option value="">Todas las ciudades</option>
                <?php foreach (array_keys($nodos_por_ciudad) as $ciudad): ?>
                    <option value="<?php echo esc_attr($ciudad); ?>"><?php echo esc_html($ciudad); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-grupo">
            <label>
                <input type="checkbox" id="filtro-propuestas"> Solo los que aceptan propuestas
            </label>
        </div>
    </div>

    <div class="red-content">
        <!-- Mapa -->
        <div class="red-mapa-container">
            <div id="red-mapa" class="red-mapa">
                <!-- Placeholder si no hay Leaflet -->
                <div class="mapa-placeholder">
                    <span class="dashicons dashicons-location-alt"></span>
                    <p>Mapa de la red cultural</p>
                    <small>Visualización geográfica de nodos y conexiones</small>
                </div>
            </div>

            <div class="mapa-leyenda">
                <h5>Leyenda</h5>
                <div class="leyenda-items">
                    <span><span class="dot espacio"></span> Espacio cultural</span>
                    <span><span class="dot comunidad"></span> Comunidad</span>
                    <span><span class="dot colectivo"></span> Colectivo</span>
                    <span><span class="line conexion"></span> Conexión activa</span>
                </div>
            </div>
        </div>

        <!-- Lista de nodos -->
        <div class="red-lista">
            <div class="lista-header">
                <h4>Directorio de nodos</h4>
                <div class="lista-vista">
                    <button class="vista-btn active" data-vista="ciudad" title="Por ciudad">
                        <span class="dashicons dashicons-location"></span>
                    </button>
                    <button class="vista-btn" data-vista="tipo" title="Por tipo">
                        <span class="dashicons dashicons-category"></span>
                    </button>
                    <button class="vista-btn" data-vista="ranking" title="Por índice">
                        <span class="dashicons dashicons-star-filled"></span>
                    </button>
                </div>
            </div>

            <div class="lista-content" id="lista-nodos">
                <?php foreach ($nodos_por_ciudad as $ciudad => $nodos_ciudad): ?>
                    <div class="ciudad-grupo" data-ciudad="<?php echo esc_attr($ciudad); ?>">
                        <h5 class="ciudad-nombre">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($ciudad); ?>
                            <span class="count">(<?php echo count($nodos_ciudad); ?>)</span>
                        </h5>
                        <div class="nodos-lista">
                            <?php foreach ($nodos_ciudad as $nodo): ?>
                                <div class="nodo-item tipo-<?php echo esc_attr($nodo->tipo); ?>"
                                     data-id="<?php echo esc_attr($nodo->id); ?>"
                                     data-tipo="<?php echo esc_attr($nodo->tipo); ?>"
                                     data-propuestas="<?php echo $nodo->acepta_propuestas ? '1' : '0'; ?>">
                                    <div class="nodo-avatar" style="background-color: <?php echo esc_attr($nodo->color_marca ?: '#ec4899'); ?>">
                                        <?php if (!empty($nodo->imagen_principal)): ?>
                                            <img src="<?php echo esc_url($nodo->imagen_principal); ?>" alt="">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-<?php echo $nodo->tipo === 'espacio' ? 'building' : ($nodo->tipo === 'comunidad' ? 'groups' : 'megaphone'); ?>"></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="nodo-info">
                                        <strong>
                                            <?php echo esc_html($nodo->nombre); ?>
                                            <?php if ($nodo->verificado): ?>
                                                <span class="verified">✓</span>
                                            <?php endif; ?>
                                            <?php if ($nodo->destacado): ?>
                                                <span class="destacado">⭐</span>
                                            <?php endif; ?>
                                        </strong>
                                        <span class="nodo-tipo"><?php echo esc_html(ucfirst($nodo->tipo)); ?></span>

                                        <div class="nodo-stats">
                                            <span title="Eventos realizados">📅 <?php echo $nodo->eventos_realizados; ?></span>
                                            <span title="Artistas apoyados">🎤 <?php echo $nodo->artistas_apoyados; ?></span>
                                            <span title="Índice de cooperación">⭐ <?php echo number_format($nodo->indice_cooperacion, 1); ?></span>
                                        </div>

                                        <div class="nodo-badges">
                                            <?php if ($nodo->acepta_propuestas): ?>
                                                <span class="badge propuestas">Acepta propuestas</span>
                                            <?php endif; ?>
                                            <?php if ($nodo->acepta_semilla): ?>
                                                <span class="badge semilla">🌱</span>
                                            <?php endif; ?>
                                            <?php if ($nodo->acepta_hours): ?>
                                                <span class="badge hours">⏰</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="nodo-actions">
                                        <button type="button" class="btn-ver-nodo" data-id="<?php echo esc_attr($nodo->id); ?>" title="Ver detalles">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                        <?php if ($nodo->acepta_propuestas): ?>
                                            <button type="button" class="btn-proponer-nodo" data-id="<?php echo esc_attr($nodo->id); ?>" title="Enviar propuesta">
                                                <span class="dashicons dashicons-email-alt"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel de detalle del nodo (oculto por defecto) -->
    <div id="panel-nodo" class="panel-nodo" style="display: none;">
        <div class="panel-header">
            <button type="button" class="panel-close">&times;</button>
        </div>
        <div class="panel-content">
            <!-- Se llena dinámicamente -->
        </div>
    </div>
</div>

<style>
.vista-red {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.red-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
    padding: 24px;
    border-radius: 16px;
    color: white;
}

.red-title {
    display: flex;
    align-items: center;
    gap: 16px;
}

.red-title .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
}

.red-title h2 {
    margin: 0;
    font-size: 22px;
}

.red-title p {
    margin: 4px 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.red-stats {
    display: flex;
    gap: 32px;
}

.red-stats .stat {
    text-align: center;
}

.red-stats .number {
    display: block;
    font-size: 32px;
    font-weight: 700;
}

.red-stats .label {
    font-size: 13px;
    opacity: 0.9;
}

/* Filtros */
.red-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    padding: 16px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filtro-grupo {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filtro-grupo label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.filtro-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.filtro-btn:hover {
    border-color: #8b5cf6;
}

.filtro-btn.active {
    background: #8b5cf6;
    border-color: #8b5cf6;
    color: white;
}

.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.dot.espacio { background: #ec4899; }
.dot.comunidad { background: #3b82f6; }
.dot.colectivo { background: #10b981; }

.filtro-grupo select {
    padding: 6px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
}

/* Content grid */
.red-content {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    min-height: 600px;
}

/* Mapa */
.red-mapa-container {
    position: relative;
}

.red-mapa {
    height: 600px;
    background: #f1f5f9;
    border-radius: 16px;
    overflow: hidden;
}

.mapa-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9ca3af;
}

.mapa-placeholder .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
}

.mapa-placeholder p {
    margin: 0;
    font-size: 18px;
    color: #6b7280;
}

.mapa-placeholder small {
    margin-top: 8px;
    font-size: 13px;
}

.mapa-leyenda {
    position: absolute;
    bottom: 16px;
    left: 16px;
    background: white;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.mapa-leyenda h5 {
    margin: 0 0 8px;
    font-size: 12px;
    color: #6b7280;
}

.leyenda-items {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 11px;
    color: #374151;
}

.leyenda-items span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.line.conexion {
    width: 20px;
    height: 2px;
    background: #8b5cf6;
}

/* Lista de nodos */
.red-lista {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.lista-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.lista-header h4 {
    margin: 0;
    font-size: 15px;
    color: #374151;
}

.lista-vista {
    display: flex;
    gap: 4px;
}

.vista-btn {
    padding: 6px 10px;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
}

.vista-btn.active {
    background: #f5f3ff;
    border-color: #8b5cf6;
}

.vista-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #6b7280;
}

.vista-btn.active .dashicons {
    color: #8b5cf6;
}

.lista-content {
    flex: 1;
    overflow-y: auto;
    max-height: 540px;
}

.ciudad-grupo {
    border-bottom: 1px solid #f3f4f6;
}

.ciudad-nombre {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 12px 16px;
    background: #f9fafb;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
}

.ciudad-nombre .dashicons {
    font-size: 14px;
    color: #9ca3af;
}

.ciudad-nombre .count {
    font-weight: 400;
    color: #9ca3af;
}

.nodos-lista {
    padding: 0 8px 8px;
}

.nodo-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
}

.nodo-item:hover {
    background: #f5f3ff;
}

.nodo-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.nodo-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nodo-avatar .dashicons {
    font-size: 24px;
    color: white;
}

.nodo-info {
    flex: 1;
    min-width: 0;
}

.nodo-info strong {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #1f2937;
}

.nodo-info .verified {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    font-size: 9px;
}

.nodo-info .destacado {
    font-size: 12px;
}

.nodo-tipo {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}

.nodo-stats {
    display: flex;
    gap: 10px;
    margin-top: 4px;
    font-size: 11px;
    color: #6b7280;
}

.nodo-badges {
    display: flex;
    gap: 4px;
    margin-top: 6px;
}

.nodo-badges .badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
}

.nodo-badges .badge.propuestas {
    background: #dbeafe;
    color: #1e40af;
}

.nodo-badges .badge.semilla {
    background: #dcfce7;
}

.nodo-badges .badge.hours {
    background: #fef3c7;
}

.nodo-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nodo-actions button {
    padding: 6px;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
}

.nodo-actions button:hover {
    background: #8b5cf6;
    border-color: #8b5cf6;
}

.nodo-actions button:hover .dashicons {
    color: white;
}

.nodo-actions .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #6b7280;
}

/* Panel de detalle */
.panel-nodo {
    position: fixed;
    right: 0;
    top: 0;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 24px rgba(0,0,0,0.15);
    z-index: 1000;
    overflow-y: auto;
}

.panel-header {
    display: flex;
    justify-content: flex-end;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.panel-close {
    padding: 8px 12px;
    background: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
}

.panel-content {
    padding: 20px;
}

@media (max-width: 1024px) {
    .red-content {
        grid-template-columns: 1fr;
    }

    .red-mapa {
        height: 400px;
    }

    .red-lista {
        max-height: 500px;
    }
}

@media (max-width: 768px) {
    .red-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .red-filtros {
        flex-direction: column;
        align-items: flex-start;
    }

    .panel-nodo {
        width: 100%;
    }
}
</style>

<script>
// Datos para el mapa
const nodosData = <?php echo json_encode($nodos_mapa); ?>;
const conexionesData = <?php echo json_encode($conexiones_mapa); ?>;

// Filtros
document.querySelectorAll('.filtro-btn[data-tipo]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filtro-btn[data-tipo]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filtrarNodos();
    });
});

document.getElementById('filtro-ciudad')?.addEventListener('change', filtrarNodos);
document.getElementById('filtro-propuestas')?.addEventListener('change', filtrarNodos);

function filtrarNodos() {
    const tipo = document.querySelector('.filtro-btn[data-tipo].active')?.dataset.tipo || 'todos';
    const ciudad = document.getElementById('filtro-ciudad')?.value || '';
    const soloPropuestas = document.getElementById('filtro-propuestas')?.checked || false;

    document.querySelectorAll('.ciudad-grupo').forEach(grupo => {
        const ciudadGrupo = grupo.dataset.ciudad;
        let mostrarGrupo = !ciudad || ciudadGrupo === ciudad;

        if (mostrarGrupo) {
            let hayVisibles = false;
            grupo.querySelectorAll('.nodo-item').forEach(nodo => {
                let visible = true;

                if (tipo !== 'todos' && nodo.dataset.tipo !== tipo) {
                    visible = false;
                }

                if (soloPropuestas && nodo.dataset.propuestas !== '1') {
                    visible = false;
                }

                nodo.style.display = visible ? '' : 'none';
                if (visible) hayVisibles = true;
            });

            grupo.style.display = hayVisibles ? '' : 'none';
        } else {
            grupo.style.display = 'none';
        }
    });
}

// Cambio de vista
document.querySelectorAll('.vista-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.vista-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Implementar cambio de vista (por ciudad, tipo, ranking)
    });
});

// Ver detalles de nodo
document.querySelectorAll('.btn-ver-nodo').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const nodoId = this.dataset.id;
        abrirPanelNodo(nodoId);
    });
});

function abrirPanelNodo(id) {
    const panel = document.getElementById('panel-nodo');
    panel.style.display = 'block';
    // Aquí cargarías los datos del nodo vía AJAX
    panel.querySelector('.panel-content').innerHTML = `<p>Cargando nodo #${id}...</p>`;
}

document.querySelector('.panel-close')?.addEventListener('click', function() {
    document.getElementById('panel-nodo').style.display = 'none';
});

// Inicializar mapa si Leaflet está disponible
if (typeof L !== 'undefined' && nodosData.length > 0) {
    // Crear mapa
    const map = L.map('red-mapa').setView([40.4168, -3.7038], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Colores por tipo
    const colores = {
        espacio: '#ec4899',
        comunidad: '#3b82f6',
        colectivo: '#10b981'
    };

    // Añadir marcadores
    nodosData.forEach(nodo => {
        if (nodo.lat && nodo.lng) {
            const marker = L.circleMarker([nodo.lat, nodo.lng], {
                radius: nodo.destacado ? 12 : 8,
                fillColor: colores[nodo.tipo] || '#8b5cf6',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);

            marker.bindPopup(`
                <strong>${nodo.nombre}</strong><br>
                <em>${nodo.tipo}</em> - ${nodo.ciudad}<br>
                ⭐ ${nodo.indice.toFixed(1)} | 🎤 ${nodo.artistas} artistas
            `);
        }
    });

    // Añadir conexiones
    conexionesData.forEach(con => {
        L.polyline([
            [con.origen.lat, con.origen.lng],
            [con.destino.lat, con.destino.lng]
        ], {
            color: '#8b5cf6',
            weight: 2,
            opacity: 0.6,
            dashArray: '5, 10'
        }).addTo(map);
    });
}
</script>
