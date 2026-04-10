<?php
/**
 * Vista: Métricas de Colaboración Inter-Comunidades
 *
 * Dashboard con estadísticas de actividad y colaboración entre comunidades
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '30';
?>

<div class="flavor-metricas-colaboracion" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">

    <!-- Cabecera -->
    <header class="flavor-metricas-header">
        <div class="flavor-metricas-titulo-wrapper">
            <h2 class="flavor-metricas-titulo">
                <span class="dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Métricas de Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <span class="flavor-metricas-subtitle">
                <?php esc_html_e('Estadísticas de actividad entre comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>

        <!-- Selector de período -->
        <div class="flavor-periodo-selector">
            <label><?php esc_html_e('Período:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="selector-periodo">
                <option value="7" <?php selected($periodo, '7'); ?>><?php esc_html_e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="30" <?php selected($periodo, '30'); ?>><?php esc_html_e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="90" <?php selected($periodo, '90'); ?>><?php esc_html_e('Últimos 3 meses', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="365" <?php selected($periodo, '365'); ?>><?php esc_html_e('Último año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
    </header>

    <!-- Tarjetas de resumen -->
    <div class="flavor-metricas-resumen" id="resumen-metricas">
        <div class="flavor-metrica-card">
            <div class="flavor-metrica-icono" style="background: #3b82f6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-metrica-info">
                <span class="flavor-metrica-valor" id="total-comunidades">-</span>
                <span class="flavor-metrica-label"><?php esc_html_e('Comunidades activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-metrica-card">
            <div class="flavor-metrica-icono" style="background: #10b981;">
                <span class="dashicons dashicons-share-alt2"></span>
            </div>
            <div class="flavor-metrica-info">
                <span class="flavor-metrica-valor" id="total-colaboraciones">-</span>
                <span class="flavor-metrica-label"><?php esc_html_e('Colaboraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-metrica-card">
            <div class="flavor-metrica-icono" style="background: #8b5cf6;">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="flavor-metrica-info">
                <span class="flavor-metrica-valor" id="total-publicaciones">-</span>
                <span class="flavor-metrica-label"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-metrica-card">
            <div class="flavor-metrica-icono" style="background: #f59e0b;">
                <span class="dashicons dashicons-networking"></span>
            </div>
            <div class="flavor-metrica-info">
                <span class="flavor-metrica-valor" id="total-federado">-</span>
                <span class="flavor-metrica-label"><?php esc_html_e('Contenido federado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Grid de métricas detalladas -->
    <div class="flavor-metricas-grid">

        <!-- Top comunidades más activas -->
        <div class="flavor-metricas-panel">
            <h3 class="flavor-panel-titulo">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Comunidades más activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="flavor-panel-contenido" id="top-comunidades">
                <div class="flavor-cargando-mini">
                    <span class="flavor-spinner-mini"></span>
                </div>
            </div>
        </div>

        <!-- Tipos de colaboración -->
        <div class="flavor-metricas-panel">
            <h3 class="flavor-panel-titulo">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php esc_html_e('Tipos de colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="flavor-panel-contenido" id="tipos-colaboracion">
                <div class="flavor-cargando-mini">
                    <span class="flavor-spinner-mini"></span>
                </div>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="flavor-metricas-panel flavor-panel-wide">
            <h3 class="flavor-panel-titulo">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Actividad de colaboración reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="flavor-panel-contenido" id="actividad-reciente">
                <div class="flavor-cargando-mini">
                    <span class="flavor-spinner-mini"></span>
                </div>
            </div>
        </div>

        <!-- Recursos compartidos -->
        <div class="flavor-metricas-panel">
            <h3 class="flavor-panel-titulo">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Recursos más compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="flavor-panel-contenido" id="recursos-compartidos">
                <div class="flavor-cargando-mini">
                    <span class="flavor-spinner-mini"></span>
                </div>
            </div>
        </div>

        <!-- Conexiones entre comunidades -->
        <div class="flavor-metricas-panel">
            <h3 class="flavor-panel-titulo">
                <span class="dashicons dashicons-admin-links"></span>
                <?php esc_html_e('Conexiones más fuertes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <div class="flavor-panel-contenido" id="conexiones-comunidades">
                <div class="flavor-cargando-mini">
                    <span class="flavor-spinner-mini"></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Resumen de la red federada -->
    <div class="flavor-metricas-federado">
        <h3 class="flavor-seccion-titulo">
            <span class="dashicons dashicons-networking"></span>
            <?php esc_html_e('Red Federada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>

        <div class="flavor-federado-grid" id="metricas-federado">
            <div class="flavor-cargando-mini">
                <span class="flavor-spinner-mini"></span>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-metricas-colaboracion {
    max-width: 1200px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-metricas-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-metricas-titulo-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-metricas-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-metricas-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-metricas-subtitle {
    font-size: 0.9em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-periodo-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-periodo-selector label {
    font-size: 0.9em;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-periodo-selector select {
    padding: 8px 12px;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 6px;
    background: white;
    font-size: 0.9em;
}

/* Tarjetas de resumen */
.flavor-metricas-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-metrica-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-metrica-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    color: white;
}

.flavor-metrica-icono .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-metrica-info {
    display: flex;
    flex-direction: column;
}

.flavor-metrica-valor {
    font-size: 1.8em;
    font-weight: 700;
    color: var(--gc-gray-900, #111827);
    line-height: 1;
}

.flavor-metrica-label {
    font-size: 0.85em;
    color: var(--gc-gray-500, #6b7280);
    margin-top: 4px;
}

/* Grid de paneles */
.flavor-metricas-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.flavor-metricas-panel {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    overflow: hidden;
}

.flavor-metricas-panel.flavor-panel-wide {
    grid-column: span 2;
}

.flavor-panel-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 16px 20px;
    font-size: 1em;
    color: var(--gc-gray-800, #1f2937);
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-panel-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-panel-contenido {
    padding: 16px 20px;
    min-height: 150px;
}

.flavor-cargando-mini {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
}

.flavor-spinner-mini {
    width: 20px;
    height: 20px;
    border: 2px solid var(--gc-gray-200, #e5e7eb);
    border-top-color: var(--gc-primary, #2e7d32);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Lista de ranking */
.flavor-ranking-lista {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-ranking-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-ranking-item:last-child {
    border-bottom: none;
}

.flavor-ranking-pos {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gc-gray-100, #f3f4f6);
    border-radius: 50%;
    font-size: 0.85em;
    font-weight: 600;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-ranking-item:nth-child(1) .flavor-ranking-pos {
    background: #fef3c7;
    color: #92400e;
}

.flavor-ranking-item:nth-child(2) .flavor-ranking-pos {
    background: #e5e7eb;
    color: #374151;
}

.flavor-ranking-item:nth-child(3) .flavor-ranking-pos {
    background: #fed7aa;
    color: #9a3412;
}

.flavor-ranking-info {
    flex: 1;
    min-width: 0;
}

.flavor-ranking-nombre {
    font-weight: 500;
    color: var(--gc-gray-800, #1f2937);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-ranking-meta {
    font-size: 0.8em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-ranking-valor {
    font-weight: 600;
    color: var(--gc-primary, #2e7d32);
    font-size: 0.95em;
}

/* Barras de progreso */
.flavor-barra-wrapper {
    margin-bottom: 14px;
}

.flavor-barra-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    font-size: 0.9em;
}

.flavor-barra-nombre {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gc-gray-700, #374151);
}

.flavor-barra-valor {
    font-weight: 600;
    color: var(--gc-gray-800, #1f2937);
}

.flavor-barra {
    height: 8px;
    background: var(--gc-gray-100, #f3f4f6);
    border-radius: 4px;
    overflow: hidden;
}

.flavor-barra-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

/* Timeline de actividad */
.flavor-timeline {
    position: relative;
    padding-left: 24px;
}

.flavor-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: var(--gc-gray-200, #e5e7eb);
}

.flavor-timeline-item {
    position: relative;
    padding-bottom: 16px;
}

.flavor-timeline-item:last-child {
    padding-bottom: 0;
}

.flavor-timeline-item::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 4px;
    width: 10px;
    height: 10px;
    background: var(--gc-primary, #2e7d32);
    border-radius: 50%;
    border: 2px solid white;
}

.flavor-timeline-contenido {
    font-size: 0.9em;
    color: var(--gc-gray-700, #374151);
}

.flavor-timeline-fecha {
    font-size: 0.8em;
    color: var(--gc-gray-400, #9ca3af);
    margin-top: 4px;
}

/* Conexiones */
.flavor-conexion-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-conexion-item:last-child {
    border-bottom: none;
}

.flavor-conexion-comunidades {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.flavor-conexion-nombre {
    font-size: 0.9em;
    color: var(--gc-gray-700, #374151);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}

.flavor-conexion-enlace {
    color: var(--gc-primary, #2e7d32);
}

.flavor-conexion-valor {
    font-size: 0.85em;
    padding: 4px 10px;
    background: var(--gc-gray-100, #f3f4f6);
    border-radius: 12px;
    color: var(--gc-gray-600, #4b5563);
}

/* Sección federado */
.flavor-metricas-federado {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    padding: 20px;
}

.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px;
    font-size: 1.1em;
    color: var(--gc-gray-800, #1f2937);
}

.flavor-seccion-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-federado-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.flavor-federado-stat {
    padding: 16px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: 8px;
    text-align: center;
}

.flavor-federado-stat-valor {
    font-size: 1.6em;
    font-weight: 700;
    color: var(--gc-gray-900, #111827);
}

.flavor-federado-stat-label {
    font-size: 0.85em;
    color: var(--gc-gray-500, #6b7280);
    margin-top: 4px;
}

/* Sin datos */
.flavor-sin-datos {
    text-align: center;
    padding: 30px;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-sin-datos .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: var(--gc-gray-300, #d1d5db);
    margin-bottom: 8px;
}

@media (max-width: 900px) {
    .flavor-metricas-grid {
        grid-template-columns: 1fr;
    }

    .flavor-metricas-panel.flavor-panel-wide {
        grid-column: span 1;
    }
}

@media (max-width: 600px) {
    .flavor-metricas-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-metricas-resumen {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-metricas-colaboracion');
        if (!contenedor) return;

        var nonce = contenedor.dataset.nonce;
        var selectorPeriodo = document.getElementById('selector-periodo');
        var periodo = selectorPeriodo.value;

        // Cargar métricas al inicio
        cargarMetricas();

        // Cambio de período
        selectorPeriodo.addEventListener('change', function() {
            periodo = this.value;
            cargarMetricas();
        });

        function cargarMetricas() {
            var formData = new FormData();
            formData.append('action', 'comunidades_obtener_metricas');
            formData.append('nonce', nonce);
            formData.append('periodo', periodo);

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    renderizarMetricas(data.data);
                }
            });
        }

        function renderizarMetricas(data) {
            // Resumen
            document.getElementById('total-comunidades').textContent = data.resumen.comunidades_activas || 0;
            document.getElementById('total-colaboraciones').textContent = data.resumen.colaboraciones || 0;
            document.getElementById('total-publicaciones').textContent = data.resumen.publicaciones || 0;
            document.getElementById('total-federado').textContent = data.resumen.contenido_federado || 0;

            // Top comunidades
            renderizarTopComunidades(data.top_comunidades || []);

            // Tipos de colaboración
            renderizarTiposColaboracion(data.tipos_colaboracion || []);

            // Actividad reciente
            renderizarActividadReciente(data.actividad_reciente || []);

            // Recursos compartidos
            renderizarRecursosCompartidos(data.recursos_compartidos || []);

            // Conexiones
            renderizarConexiones(data.conexiones || []);

            // Métricas federadas
            renderizarMetricasFederadas(data.federado || {});
        }

        function renderizarTopComunidades(comunidades) {
            var contenedor = document.getElementById('top-comunidades');

            if (!comunidades.length) {
                contenedor.innerHTML = '<div class="flavor-sin-datos"><span class="dashicons dashicons-groups"></span><p><?php echo esc_js(__('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>';
                return;
            }

            var html = '<ul class="flavor-ranking-lista">';
            comunidades.forEach(function(com, i) {
                html += '<li class="flavor-ranking-item">' +
                    '<span class="flavor-ranking-pos">' + (i + 1) + '</span>' +
                    '<div class="flavor-ranking-info">' +
                        '<div class="flavor-ranking-nombre">' + escapeHtml(com.nombre) + '</div>' +
                        '<div class="flavor-ranking-meta">' + com.miembros + ' <?php echo esc_js(__('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></div>' +
                    '</div>' +
                    '<span class="flavor-ranking-valor">' + com.actividad + '</span>' +
                '</li>';
            });
            html += '</ul>';

            contenedor.innerHTML = html;
        }

        function renderizarTiposColaboracion(tipos) {
            var contenedor = document.getElementById('tipos-colaboracion');

            if (!tipos.length) {
                contenedor.innerHTML = '<div class="flavor-sin-datos"><span class="dashicons dashicons-chart-pie"></span><p><?php echo esc_js(__('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>';
                return;
            }

            var total = tipos.reduce(function(sum, t) { return sum + t.cantidad; }, 0);
            var colores = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#ec4899'];

            var html = '';
            tipos.forEach(function(tipo, i) {
                var porcentaje = total > 0 ? Math.round((tipo.cantidad / total) * 100) : 0;
                html += '<div class="flavor-barra-wrapper">' +
                    '<div class="flavor-barra-label">' +
                        '<span class="flavor-barra-nombre">' + tipo.icono + ' ' + escapeHtml(tipo.label) + '</span>' +
                        '<span class="flavor-barra-valor">' + tipo.cantidad + '</span>' +
                    '</div>' +
                    '<div class="flavor-barra">' +
                        '<div class="flavor-barra-fill" style="width: ' + porcentaje + '%; background: ' + colores[i % colores.length] + ';"></div>' +
                    '</div>' +
                '</div>';
            });

            contenedor.innerHTML = html;
        }

        function renderizarActividadReciente(actividades) {
            var contenedor = document.getElementById('actividad-reciente');

            if (!actividades.length) {
                contenedor.innerHTML = '<div class="flavor-sin-datos"><span class="dashicons dashicons-clock"></span><p><?php echo esc_js(__('No hay actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>';
                return;
            }

            var html = '<div class="flavor-timeline">';
            actividades.forEach(function(act) {
                html += '<div class="flavor-timeline-item">' +
                    '<div class="flavor-timeline-contenido">' + escapeHtml(act.descripcion) + '</div>' +
                    '<div class="flavor-timeline-fecha">' + act.fecha + '</div>' +
                '</div>';
            });
            html += '</div>';

            contenedor.innerHTML = html;
        }

        function renderizarRecursosCompartidos(recursos) {
            var contenedor = document.getElementById('recursos-compartidos');

            if (!recursos.length) {
                contenedor.innerHTML = '<div class="flavor-sin-datos"><span class="dashicons dashicons-portfolio"></span><p><?php echo esc_js(__('No hay recursos compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>';
                return;
            }

            var html = '<ul class="flavor-ranking-lista">';
            recursos.forEach(function(rec, i) {
                html += '<li class="flavor-ranking-item">' +
                    '<span class="flavor-ranking-pos">' + (i + 1) + '</span>' +
                    '<div class="flavor-ranking-info">' +
                        '<div class="flavor-ranking-nombre">' + escapeHtml(rec.titulo) + '</div>' +
                        '<div class="flavor-ranking-meta">' + rec.tipo + '</div>' +
                    '</div>' +
                    '<span class="flavor-ranking-valor">' + rec.compartidos + '×</span>' +
                '</li>';
            });
            html += '</ul>';

            contenedor.innerHTML = html;
        }

        function renderizarConexiones(conexiones) {
            var contenedor = document.getElementById('conexiones-comunidades');

            if (!conexiones.length) {
                contenedor.innerHTML = '<div class="flavor-sin-datos"><span class="dashicons dashicons-admin-links"></span><p><?php echo esc_js(__('No hay conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>';
                return;
            }

            var html = '';
            conexiones.forEach(function(con) {
                html += '<div class="flavor-conexion-item">' +
                    '<div class="flavor-conexion-comunidades">' +
                        '<span class="flavor-conexion-nombre">' + escapeHtml(con.comunidad_a) + '</span>' +
                        '<span class="dashicons dashicons-leftright flavor-conexion-enlace"></span>' +
                        '<span class="flavor-conexion-nombre">' + escapeHtml(con.comunidad_b) + '</span>' +
                    '</div>' +
                    '<span class="flavor-conexion-valor">' + con.interacciones + '</span>' +
                '</div>';
            });

            contenedor.innerHTML = html;
        }

        function renderizarMetricasFederadas(federado) {
            var contenedor = document.getElementById('metricas-federado');

            var html = '<div class="flavor-federado-stat">' +
                '<div class="flavor-federado-stat-valor">' + (federado.nodos_conectados || 0) + '</div>' +
                '<div class="flavor-federado-stat-label"><?php echo esc_js(__('Nodos conectados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></div>' +
            '</div>' +
            '<div class="flavor-federado-stat">' +
                '<div class="flavor-federado-stat-valor">' + (federado.contenido_recibido || 0) + '</div>' +
                '<div class="flavor-federado-stat-label"><?php echo esc_js(__('Contenido recibido', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></div>' +
            '</div>' +
            '<div class="flavor-federado-stat">' +
                '<div class="flavor-federado-stat-valor">' + (federado.contenido_compartido || 0) + '</div>' +
                '<div class="flavor-federado-stat-label"><?php echo esc_js(__('Contenido compartido', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></div>' +
            '</div>' +
            '<div class="flavor-federado-stat">' +
                '<div class="flavor-federado-stat-valor">' + (federado.puntuacion_nodo || 0) + '</div>' +
                '<div class="flavor-federado-stat-label"><?php echo esc_js(__('Puntuación del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></div>' +
            '</div>';

            contenedor.innerHTML = html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
})();
</script>
