<?php
/**
 * Vista: Búsqueda Federada Unificada
 *
 * Permite buscar contenido en comunidades locales y en la red federada
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$termino_busqueda = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'todos';
$filtro_origen = isset($_GET['origen']) ? sanitize_text_field($_GET['origen']) : 'todos';
$pagina = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Tipos de contenido buscables
$tipos_contenido = [
    'todos'       => ['label' => __('Todo', 'flavor-chat-ia'), 'icon' => 'dashicons-search'],
    'comunidades' => ['label' => __('Comunidades', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
    'publicaciones' => ['label' => __('Publicaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-post'],
    'eventos'     => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
    'recetas'     => ['label' => __('Recetas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
    'biblioteca'  => ['label' => __('Biblioteca', 'flavor-chat-ia'), 'icon' => 'dashicons-book'],
    'multimedia'  => ['label' => __('Multimedia', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
];

$origenes = [
    'todos' => __('Todos los orígenes', 'flavor-chat-ia'),
    'local' => __('Solo local', 'flavor-chat-ia'),
    'federado' => __('Solo red federada', 'flavor-chat-ia'),
];
?>

<div class="flavor-busqueda-federada" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">

    <!-- Cabecera de búsqueda -->
    <header class="flavor-busqueda-header">
        <h2 class="flavor-busqueda-titulo">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e('Búsqueda en la Red', 'flavor-chat-ia'); ?>
        </h2>
    </header>

    <!-- Formulario de búsqueda -->
    <form class="flavor-busqueda-form" method="get" action="">
        <div class="flavor-busqueda-input-wrapper">
            <span class="dashicons dashicons-search"></span>
            <input type="text"
                   name="q"
                   id="termino-busqueda"
                   class="flavor-busqueda-input"
                   placeholder="<?php esc_attr_e('Buscar comunidades, eventos, recursos...', 'flavor-chat-ia'); ?>"
                   value="<?php echo esc_attr($termino_busqueda); ?>"
                   autocomplete="off">
            <button type="submit" class="flavor-busqueda-submit">
                <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <!-- Filtros -->
        <div class="flavor-busqueda-filtros">
            <div class="flavor-filtro-grupo">
                <label><?php esc_html_e('Tipo:', 'flavor-chat-ia'); ?></label>
                <div class="flavor-filtro-chips">
                    <?php foreach ($tipos_contenido as $tipo => $config): ?>
                    <label class="flavor-chip <?php echo $filtro_tipo === $tipo ? 'activo' : ''; ?>">
                        <input type="radio" name="tipo" value="<?php echo esc_attr($tipo); ?>" <?php checked($filtro_tipo, $tipo); ?>>
                        <span class="<?php echo esc_attr($config['icon']); ?>"></span>
                        <?php echo esc_html($config['label']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flavor-filtro-grupo">
                <label><?php esc_html_e('Origen:', 'flavor-chat-ia'); ?></label>
                <select name="origen" class="flavor-select-origen">
                    <?php foreach ($origenes as $valor => $label): ?>
                    <option value="<?php echo esc_attr($valor); ?>" <?php selected($filtro_origen, $valor); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Resultados -->
    <div class="flavor-busqueda-resultados" id="resultados-busqueda">
        <?php if (empty($termino_busqueda)): ?>
            <div class="flavor-busqueda-inicial">
                <span class="dashicons dashicons-networking"></span>
                <h3><?php esc_html_e('Busca en toda la red', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Encuentra comunidades, eventos, recetas y más recursos tanto locales como de la red federada.', 'flavor-chat-ia'); ?></p>

                <div class="flavor-busqueda-sugerencias">
                    <h4><?php esc_html_e('Búsquedas populares:', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-sugerencias-tags">
                        <a href="?q=huerto" class="flavor-tag">huerto</a>
                        <a href="?q=recetas" class="flavor-tag">recetas</a>
                        <a href="?q=taller" class="flavor-tag">taller</a>
                        <a href="?q=intercambio" class="flavor-tag">intercambio</a>
                        <a href="?q=consumo" class="flavor-tag">consumo</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="flavor-resultados-cargando" id="cargando-resultados">
                <span class="flavor-spinner"></span>
                <?php esc_html_e('Buscando...', 'flavor-chat-ia'); ?>
            </div>
            <div class="flavor-resultados-contenido" id="contenido-resultados" style="display:none;"></div>
        <?php endif; ?>
    </div>
</div>

<style>
.flavor-busqueda-federada {
    max-width: 900px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-busqueda-header {
    margin-bottom: 24px;
}

.flavor-busqueda-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-busqueda-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

/* Formulario */
.flavor-busqueda-form {
    background: white;
    padding: 24px;
    border-radius: var(--gc-border-radius, 12px);
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    margin-bottom: 24px;
}

.flavor-busqueda-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--gc-gray-50, #f9fafb);
    border: 2px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    transition: border-color 0.2s;
}

.flavor-busqueda-input-wrapper:focus-within {
    border-color: var(--gc-primary, #2e7d32);
    background: white;
}

.flavor-busqueda-input-wrapper .dashicons {
    color: var(--gc-gray-400, #9ca3af);
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.flavor-busqueda-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 1.1em;
    color: var(--gc-gray-900, #111827);
    outline: none;
}

.flavor-busqueda-input::placeholder {
    color: var(--gc-gray-400, #9ca3af);
}

.flavor-busqueda-submit {
    padding: 10px 24px;
    background: var(--gc-primary, #2e7d32);
    color: white;
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    font-size: 0.95em;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-busqueda-submit:hover {
    background: var(--gc-primary-dark, #1b5e20);
}

/* Filtros */
.flavor-busqueda-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-filtro-grupo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-filtro-grupo > label {
    font-size: 0.9em;
    color: var(--gc-gray-600, #4b5563);
    font-weight: 500;
}

.flavor-filtro-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.flavor-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: var(--gc-gray-100, #f3f4f6);
    border-radius: 20px;
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-chip input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.flavor-chip:hover {
    background: var(--gc-gray-200, #e5e7eb);
}

.flavor-chip.activo {
    background: var(--gc-primary, #2e7d32);
    color: white;
}

.flavor-chip .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-select-origen {
    padding: 8px 12px;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 6px;
    font-size: 0.9em;
    background: white;
    color: var(--gc-gray-700, #374151);
}

/* Resultados */
.flavor-busqueda-resultados {
    min-height: 300px;
}

.flavor-busqueda-inicial {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-busqueda-inicial .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--gc-primary, #2e7d32);
    margin-bottom: 16px;
}

.flavor-busqueda-inicial h3 {
    margin: 0 0 8px;
    color: var(--gc-gray-700, #374151);
}

.flavor-busqueda-inicial p {
    margin: 0 0 24px;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-busqueda-sugerencias h4 {
    font-size: 0.9em;
    color: var(--gc-gray-600, #4b5563);
    margin: 0 0 12px;
}

.flavor-sugerencias-tags {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}

.flavor-tag {
    padding: 6px 14px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 20px;
    color: var(--gc-gray-700, #374151);
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.2s;
}

.flavor-tag:hover {
    background: var(--gc-primary, #2e7d32);
    border-color: var(--gc-primary, #2e7d32);
    color: white;
}

.flavor-resultados-cargando {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--gc-gray-200, #e5e7eb);
    border-top-color: var(--gc-primary, #2e7d32);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Cards de resultados */
.flavor-resultado-card {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    margin-bottom: 12px;
    transition: box-shadow 0.2s;
}

.flavor-resultado-card:hover {
    box-shadow: var(--gc-shadow, 0 4px 6px -1px rgba(0,0,0,0.1));
}

.flavor-resultado-imagen {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.flavor-resultado-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-resultado-imagen-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gc-gray-100, #f3f4f6);
}

.flavor-resultado-imagen-placeholder .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: var(--gc-gray-400, #9ca3af);
}

.flavor-resultado-contenido {
    flex: 1;
    min-width: 0;
}

.flavor-resultado-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.flavor-resultado-tipo {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: var(--gc-primary, #2e7d32);
    color: white;
    border-radius: 4px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-resultado-origen {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: var(--gc-gray-100, #f3f4f6);
    color: var(--gc-gray-600, #4b5563);
    border-radius: 4px;
    font-size: 0.75em;
}

.flavor-resultado-origen.federado {
    background: #d1fae5;
    color: #065f46;
}

.flavor-resultado-titulo {
    margin: 0 0 6px;
    font-size: 1.1em;
}

.flavor-resultado-titulo a {
    color: var(--gc-gray-900, #111827);
    text-decoration: none;
}

.flavor-resultado-titulo a:hover {
    color: var(--gc-primary, #2e7d32);
}

.flavor-resultado-descripcion {
    margin: 0 0 10px;
    font-size: 0.9em;
    color: var(--gc-gray-600, #4b5563);
    line-height: 1.4;
}

.flavor-resultado-info {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.85em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-resultado-info span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.flavor-resultado-info .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Sin resultados */
.flavor-sin-resultados {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-sin-resultados .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--gc-gray-400, #9ca3af);
    margin-bottom: 16px;
}

/* Paginación */
.flavor-paginacion {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.flavor-paginacion a,
.flavor-paginacion span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9em;
}

.flavor-paginacion a {
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    color: var(--gc-gray-700, #374151);
}

.flavor-paginacion a:hover {
    background: var(--gc-gray-50, #f9fafb);
}

.flavor-paginacion span.actual {
    background: var(--gc-primary, #2e7d32);
    color: white;
}

.flavor-resultados-resumen {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-resultados-count {
    font-size: 0.95em;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-resultados-count strong {
    color: var(--gc-gray-900, #111827);
}

@media (max-width: 600px) {
    .flavor-busqueda-input-wrapper {
        flex-wrap: wrap;
    }

    .flavor-busqueda-submit {
        width: 100%;
        margin-top: 10px;
    }

    .flavor-resultado-card {
        flex-direction: column;
    }

    .flavor-resultado-imagen {
        width: 100%;
        height: 160px;
    }

    .flavor-filtro-grupo {
        width: 100%;
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-busqueda-federada');
        if (!contenedor) return;

        var nonce = contenedor.dataset.nonce;
        var terminoInput = document.getElementById('termino-busqueda');
        var cargando = document.getElementById('cargando-resultados');
        var contenidoResultados = document.getElementById('contenido-resultados');

        // Si hay término de búsqueda, ejecutar búsqueda
        if (terminoInput && terminoInput.value.trim()) {
            ejecutarBusqueda();
        }

        // Auto-submit al cambiar filtros
        document.querySelectorAll('.flavor-chip input, .flavor-select-origen').forEach(function(input) {
            input.addEventListener('change', function() {
                if (terminoInput.value.trim()) {
                    document.querySelector('.flavor-busqueda-form').submit();
                }
            });
        });

        function ejecutarBusqueda() {
            var termino = terminoInput.value.trim();
            if (!termino) return;

            var tipo = document.querySelector('.flavor-chip.activo input')?.value || 'todos';
            var origen = document.querySelector('.flavor-select-origen')?.value || 'todos';

            if (cargando) cargando.style.display = 'flex';
            if (contenidoResultados) contenidoResultados.style.display = 'none';

            var formData = new FormData();
            formData.append('action', 'comunidades_busqueda_federada');
            formData.append('nonce', nonce);
            formData.append('termino', termino);
            formData.append('tipo', tipo);
            formData.append('origen', origen);
            formData.append('pagina', <?php echo intval($pagina); ?>);

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (cargando) cargando.style.display = 'none';
                if (contenidoResultados) {
                    contenidoResultados.style.display = 'block';
                    if (data.success) {
                        renderizarResultados(data.data);
                    } else {
                        contenidoResultados.innerHTML = '<div class="flavor-sin-resultados">' +
                            '<span class="dashicons dashicons-warning"></span>' +
                            '<h3><?php echo esc_js(__('Error en la búsqueda', 'flavor-chat-ia')); ?></h3>' +
                            '<p>' + (data.data?.message || '<?php echo esc_js(__('Inténtalo de nuevo', 'flavor-chat-ia')); ?>') + '</p>' +
                            '</div>';
                    }
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                if (cargando) cargando.style.display = 'none';
            });
        }

        function renderizarResultados(data) {
            var resultados = data.resultados || [];
            var total = data.total || 0;

            if (resultados.length === 0) {
                contenidoResultados.innerHTML = '<div class="flavor-sin-resultados">' +
                    '<span class="dashicons dashicons-search"></span>' +
                    '<h3><?php echo esc_js(__('No se encontraron resultados', 'flavor-chat-ia')); ?></h3>' +
                    '<p><?php echo esc_js(__('Prueba con otros términos o filtros', 'flavor-chat-ia')); ?></p>' +
                    '</div>';
                return;
            }

            var html = '<div class="flavor-resultados-resumen">' +
                '<span class="flavor-resultados-count"><?php echo esc_js(__('Se encontraron', 'flavor-chat-ia')); ?> <strong>' + total + '</strong> <?php echo esc_js(__('resultados', 'flavor-chat-ia')); ?></span>' +
                '</div>';

            resultados.forEach(function(item) {
                var origenClase = item.origen === 'federado' ? 'federado' : '';
                var origenLabel = item.origen === 'federado' ? '<?php echo esc_js(__('Red federada', 'flavor-chat-ia')); ?>' : '<?php echo esc_js(__('Local', 'flavor-chat-ia')); ?>';

                html += '<article class="flavor-resultado-card">' +
                    '<div class="flavor-resultado-imagen">';

                if (item.imagen) {
                    html += '<img src="' + escapeHtml(item.imagen) + '" alt="" loading="lazy">';
                } else {
                    html += '<div class="flavor-resultado-imagen-placeholder">' +
                            '<span class="dashicons dashicons-' + (item.icono || 'admin-site') + '"></span>' +
                            '</div>';
                }

                html += '</div>' +
                    '<div class="flavor-resultado-contenido">' +
                        '<div class="flavor-resultado-meta">' +
                            '<span class="flavor-resultado-tipo">' + escapeHtml(item.tipo_label || item.tipo) + '</span>' +
                            '<span class="flavor-resultado-origen ' + origenClase + '">' +
                                (item.origen === 'federado' ? '<span class="dashicons dashicons-networking"></span>' : '') +
                                origenLabel +
                            '</span>' +
                        '</div>' +
                        '<h3 class="flavor-resultado-titulo">' +
                            '<a href="' + escapeHtml(item.url || '#') + '"' + (item.origen === 'federado' ? ' target="_blank" rel="noopener"' : '') + '>' +
                                escapeHtml(item.titulo) +
                            '</a>' +
                        '</h3>';

                if (item.descripcion) {
                    html += '<p class="flavor-resultado-descripcion">' + escapeHtml(truncar(item.descripcion, 150)) + '</p>';
                }

                html += '<div class="flavor-resultado-info">';

                if (item.autor) {
                    html += '<span><span class="dashicons dashicons-admin-users"></span> ' + escapeHtml(item.autor) + '</span>';
                }
                if (item.fecha) {
                    html += '<span><span class="dashicons dashicons-calendar"></span> ' + escapeHtml(item.fecha) + '</span>';
                }
                if (item.nodo_nombre && item.origen === 'federado') {
                    html += '<span><span class="dashicons dashicons-admin-site"></span> ' + escapeHtml(item.nodo_nombre) + '</span>';
                }

                html += '</div></div></article>';
            });

            // Paginación
            if (data.paginas > 1) {
                html += '<div class="flavor-paginacion">';
                for (var i = 1; i <= data.paginas; i++) {
                    var params = new URLSearchParams(window.location.search);
                    params.set('pg', i);
                    if (i === <?php echo intval($pagina); ?>) {
                        html += '<span class="actual">' + i + '</span>';
                    } else {
                        html += '<a href="?' + params.toString() + '">' + i + '</a>';
                    }
                }
                html += '</div>';
            }

            contenidoResultados.innerHTML = html;
        }

        function truncar(texto, longitud) {
            if (!texto) return '';
            if (texto.length <= longitud) return texto;
            return texto.substring(0, longitud).trim() + '...';
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
