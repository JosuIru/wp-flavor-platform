<?php
/**
 * Vista: Feed Unificado de Comunidades
 *
 * Muestra actividad de todas las comunidades del usuario (locales + federadas)
 *
 * Variables disponibles:
 * - $feed_combinado: Array de actividades combinadas
 * - $comunidades_usuario: Comunidades del usuario para filtrar
 * - $atributos: Atributos del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$mostrar_origen = $atributos['mostrar_origen'] === 'true';
?>

<div class="flavor-feed-unificado" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">

    <!-- Cabecera con filtros -->
    <div class="flavor-feed-header">
        <h2 class="flavor-feed-titulo">
            <span class="dashicons dashicons-networking"></span>
            <?php esc_html_e('Actividad de mis Comunidades', 'flavor-chat-ia'); ?>
        </h2>

        <div class="flavor-feed-filtros">
            <!-- Filtro por comunidad -->
            <?php if (!empty($comunidades_usuario)): ?>
            <select class="flavor-feed-filtro-comunidad" id="filtro-comunidad">
                <option value=""><?php esc_html_e('Todas las comunidades', 'flavor-chat-ia'); ?></option>
                <?php foreach ($comunidades_usuario as $comunidad): ?>
                <option value="<?php echo esc_attr($comunidad->id); ?>">
                    <?php echo esc_html($comunidad->nombre); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <!-- Filtro por origen -->
            <div class="flavor-feed-filtro-origen">
                <label class="flavor-filtro-checkbox">
                    <input type="checkbox" name="origen_local" value="local" checked>
                    <span class="dashicons dashicons-admin-home"></span>
                    <?php esc_html_e('Local', 'flavor-chat-ia'); ?>
                </label>
                <label class="flavor-filtro-checkbox">
                    <input type="checkbox" name="origen_federado" value="federado" checked>
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e('Red', 'flavor-chat-ia'); ?>
                </label>
            </div>
        </div>
    </div>

    <!-- Contenido del feed -->
    <div class="flavor-feed-contenido">
        <?php if (empty($feed_combinado)): ?>
            <div class="flavor-feed-vacio">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php esc_html_e('No hay actividad todavía', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Únete a comunidades para ver su actividad aquí.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="flavor-btn-primary">
                    <?php esc_html_e('Explorar comunidades', 'flavor-chat-ia'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="flavor-feed-lista" id="feed-lista">
                <?php foreach ($feed_combinado as $item): ?>
                <article class="flavor-feed-item"
                         data-origen="<?php echo esc_attr($item->origen_tipo); ?>"
                         data-comunidad="<?php echo esc_attr($item->comunidad_id); ?>">

                    <!-- Cabecera del item -->
                    <header class="flavor-feed-item-header">
                        <div class="flavor-feed-item-comunidad">
                            <?php if ($item->comunidad_imagen): ?>
                                <img src="<?php echo esc_url($item->comunidad_imagen); ?>"
                                     alt="<?php echo esc_attr($item->comunidad_nombre); ?>"
                                     class="flavor-feed-comunidad-avatar">
                            <?php else: ?>
                                <div class="flavor-feed-comunidad-avatar flavor-feed-comunidad-avatar-default">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                            <?php endif; ?>

                            <div class="flavor-feed-item-meta">
                                <span class="flavor-feed-comunidad-nombre">
                                    <?php echo esc_html($item->comunidad_nombre); ?>
                                </span>

                                <?php if ($mostrar_origen && $item->origen_tipo === 'federado'): ?>
                                    <span class="flavor-feed-origen-badge federado" title="<?php esc_attr_e('Contenido de la red federada', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-networking"></span>
                                        <?php echo esc_html($item->nodo_nombre); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="flavor-feed-autor">
                                    <?php
                                    printf(
                                        esc_html__('por %s', 'flavor-chat-ia'),
                                        esc_html($item->autor_nombre ?: __('Usuario', 'flavor-chat-ia'))
                                    );
                                    ?>
                                </span>

                                <time class="flavor-feed-fecha" datetime="<?php echo esc_attr($item->fecha); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($item->fecha), current_time('timestamp'))); ?>
                                </time>
                            </div>
                        </div>

                        <?php if ($item->origen_tipo === 'local'): ?>
                        <div class="flavor-feed-item-acciones-menu">
                            <button type="button" class="flavor-btn-icon" aria-label="<?php esc_attr_e('Más opciones', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-ellipsis"></span>
                            </button>
                            <div class="flavor-dropdown-menu">
                                <button type="button" class="flavor-compartir-btn" data-actividad="<?php echo esc_attr($item->id); ?>">
                                    <span class="dashicons dashicons-share"></span>
                                    <?php esc_html_e('Compartir en otra comunidad', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </header>

                    <!-- Contenido -->
                    <div class="flavor-feed-item-contenido">
                        <?php if ($item->titulo): ?>
                            <h3 class="flavor-feed-item-titulo"><?php echo esc_html($item->titulo); ?></h3>
                        <?php endif; ?>

                        <div class="flavor-feed-item-texto">
                            <?php echo wp_kses_post(wpautop($item->contenido)); ?>
                        </div>

                        <?php if ($item->imagen): ?>
                            <div class="flavor-feed-item-imagen">
                                <img src="<?php echo esc_url($item->imagen); ?>" alt="" loading="lazy">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <footer class="flavor-feed-item-footer">
                        <?php if ($item->origen_tipo === 'local'): ?>
                            <button type="button"
                                    class="flavor-feed-btn-like <?php echo $item->usuario_dio_like ? 'liked' : ''; ?>"
                                    data-actividad="<?php echo esc_attr($item->id); ?>">
                                <span class="dashicons dashicons-heart<?php echo $item->usuario_dio_like ? '' : '-empty'; ?>"></span>
                                <span class="likes-count"><?php echo esc_html($item->likes_count); ?></span>
                            </button>

                            <a href="<?php echo esc_url(add_query_arg('comunidad', $item->comunidad_id, home_url('/comunidades/'))); ?>"
                               class="flavor-feed-btn">
                                <span class="dashicons dashicons-admin-comments"></span>
                                <?php esc_html_e('Comentar', 'flavor-chat-ia'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($item->url_externa); ?>"
                               target="_blank"
                               rel="noopener"
                               class="flavor-feed-btn flavor-feed-btn-external">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e('Ver en el nodo', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Cargar más -->
            <div class="flavor-feed-cargar-mas">
                <button type="button" class="flavor-btn-secondary" id="cargar-mas-feed">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Cargar más', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para compartir -->
    <div class="flavor-modal" id="modal-compartir" aria-hidden="true">
        <div class="flavor-modal-overlay"></div>
        <div class="flavor-modal-contenido">
            <header class="flavor-modal-header">
                <h3><?php esc_html_e('Compartir en otra comunidad', 'flavor-chat-ia'); ?></h3>
                <button type="button" class="flavor-modal-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </header>
            <form id="form-compartir" class="flavor-modal-body">
                <input type="hidden" name="actividad_id" id="compartir-actividad-id">

                <div class="flavor-form-group">
                    <label for="comunidad-destino"><?php esc_html_e('Comunidad destino', 'flavor-chat-ia'); ?></label>
                    <select name="comunidad_destino" id="comunidad-destino" required>
                        <option value=""><?php esc_html_e('Selecciona una comunidad', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($comunidades_usuario as $comunidad): ?>
                        <option value="<?php echo esc_attr($comunidad->id); ?>">
                            <?php echo esc_html($comunidad->nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="comentario-compartir"><?php esc_html_e('Añadir comentario (opcional)', 'flavor-chat-ia'); ?></label>
                    <textarea name="comentario" id="comentario-compartir" rows="3"
                              placeholder="<?php esc_attr_e('Escribe algo sobre esta publicación...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-modal-acciones">
                    <button type="button" class="flavor-btn-secondary flavor-modal-cerrar">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" class="flavor-btn-primary">
                        <span class="dashicons dashicons-share"></span>
                        <?php esc_html_e('Compartir', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.flavor-feed-unificado {
    max-width: 800px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-feed-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-feed-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-feed-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-feed-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.flavor-feed-filtro-comunidad {
    padding: 8px 12px;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: var(--gc-button-radius, 6px);
    font-size: 0.9em;
    background: white;
}

.flavor-feed-filtro-origen {
    display: flex;
    gap: 8px;
}

.flavor-filtro-checkbox {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 10px;
    background: var(--gc-gray-100, #f3f4f6);
    border-radius: var(--gc-button-radius, 6px);
    cursor: pointer;
    font-size: 0.85em;
    transition: all 0.2s;
}

.flavor-filtro-checkbox:hover {
    background: var(--gc-gray-200, #e5e7eb);
}

.flavor-filtro-checkbox input {
    accent-color: var(--gc-primary, #2e7d32);
}

.flavor-filtro-checkbox .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Feed vacío */
.flavor-feed-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-feed-vacio .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--gc-gray-400, #9ca3af);
    margin-bottom: 16px;
}

.flavor-feed-vacio h3 {
    margin: 0 0 8px;
    color: var(--gc-gray-700, #374151);
}

.flavor-feed-vacio p {
    margin: 0 0 20px;
    color: var(--gc-gray-500, #6b7280);
}

/* Items del feed */
.flavor-feed-lista {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.flavor-feed-item {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.flavor-feed-item:hover {
    box-shadow: var(--gc-shadow, 0 4px 6px -1px rgba(0,0,0,0.1));
}

.flavor-feed-item.oculto {
    display: none;
}

.flavor-feed-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px;
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-feed-item-comunidad {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.flavor-feed-comunidad-avatar {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
}

.flavor-feed-comunidad-avatar-default {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--gc-primary, #2e7d32), var(--gc-primary-dark, #1b5e20));
}

.flavor-feed-comunidad-avatar-default .dashicons {
    color: white;
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-feed-item-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-feed-comunidad-nombre {
    font-weight: 600;
    color: var(--gc-gray-900, #111827);
}

.flavor-feed-origen-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: var(--gc-primary-light, #e8f5e9);
    color: var(--gc-primary-dark, #1b5e20);
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: 500;
}

.flavor-feed-origen-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.flavor-feed-autor {
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-feed-fecha {
    font-size: 0.8em;
    color: var(--gc-gray-400, #9ca3af);
}

/* Contenido del item */
.flavor-feed-item-contenido {
    padding: 16px;
}

.flavor-feed-item-titulo {
    margin: 0 0 8px;
    font-size: 1.1em;
    color: var(--gc-gray-900, #111827);
}

.flavor-feed-item-texto {
    color: var(--gc-gray-700, #374151);
    line-height: 1.6;
}

.flavor-feed-item-texto p {
    margin: 0 0 12px;
}

.flavor-feed-item-texto p:last-child {
    margin-bottom: 0;
}

.flavor-feed-item-imagen {
    margin-top: 12px;
    border-radius: 8px;
    overflow: hidden;
}

.flavor-feed-item-imagen img {
    width: 100%;
    display: block;
}

/* Footer del item */
.flavor-feed-item-footer {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-feed-btn,
.flavor-feed-btn-like {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: transparent;
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    color: var(--gc-gray-600, #4b5563);
    font-size: 0.9em;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-feed-btn:hover,
.flavor-feed-btn-like:hover {
    background: var(--gc-gray-100, #f3f4f6);
    color: var(--gc-gray-900, #111827);
}

.flavor-feed-btn-like.liked {
    color: #ef4444;
}

.flavor-feed-btn-like.liked .dashicons-heart-empty::before {
    content: "\f487";
}

.flavor-feed-btn-external {
    color: var(--gc-primary, #2e7d32);
}

/* Menú de acciones */
.flavor-feed-item-acciones-menu {
    position: relative;
}

.flavor-btn-icon {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: var(--gc-gray-400, #9ca3af);
    border-radius: 50%;
    transition: all 0.2s;
}

.flavor-btn-icon:hover {
    background: var(--gc-gray-100, #f3f4f6);
    color: var(--gc-gray-700, #374151);
}

.flavor-dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    min-width: 200px;
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: 8px;
    box-shadow: var(--gc-shadow-lg, 0 10px 15px -3px rgba(0,0,0,0.1));
    display: none;
    z-index: 100;
}

.flavor-feed-item-acciones-menu:focus-within .flavor-dropdown-menu,
.flavor-feed-item-acciones-menu:hover .flavor-dropdown-menu {
    display: block;
}

.flavor-dropdown-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    color: var(--gc-gray-700, #374151);
    font-size: 0.9em;
}

.flavor-dropdown-menu button:hover {
    background: var(--gc-gray-50, #f9fafb);
}

/* Cargar más */
.flavor-feed-cargar-mas {
    text-align: center;
    margin-top: 24px;
}

/* Modal */
.flavor-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.flavor-modal[aria-hidden="true"] {
    display: none;
}

.flavor-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
}

.flavor-modal-contenido {
    position: relative;
    background: white;
    border-radius: var(--gc-border-radius, 12px);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: auto;
}

.flavor-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-modal-header h3 {
    margin: 0;
    font-size: 1.1em;
}

.flavor-modal-cerrar {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-modal-body {
    padding: 20px;
}

.flavor-form-group {
    margin-bottom: 16px;
}

.flavor-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--gc-gray-700, #374151);
}

.flavor-form-group select,
.flavor-form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: var(--gc-button-radius, 6px);
    font-size: 1em;
    font-family: inherit;
}

.flavor-form-group textarea {
    resize: vertical;
}

.flavor-modal-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--gc-gray-200, #e5e7eb);
}

/* Botones */
.flavor-btn-primary,
.flavor-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    font-size: 0.95em;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-btn-primary {
    background: var(--gc-primary, #2e7d32);
    color: white;
}

.flavor-btn-primary:hover {
    background: var(--gc-primary-dark, #1b5e20);
}

.flavor-btn-secondary {
    background: var(--gc-gray-100, #f3f4f6);
    color: var(--gc-gray-700, #374151);
}

.flavor-btn-secondary:hover {
    background: var(--gc-gray-200, #e5e7eb);
}

@media (max-width: 600px) {
    .flavor-feed-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-feed-filtros {
        flex-direction: column;
    }

    .flavor-feed-filtro-comunidad {
        width: 100%;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-feed-unificado');
        if (!contenedor) return;

        var nonce = contenedor.dataset.nonce;
        var feedLista = document.getElementById('feed-lista');

        // Filtros
        var filtroComunidad = document.getElementById('filtro-comunidad');
        var filtrosOrigen = contenedor.querySelectorAll('.flavor-filtro-checkbox input');

        function filtrarFeed() {
            var comunidadSeleccionada = filtroComunidad ? filtroComunidad.value : '';
            var origenesActivos = [];

            filtrosOrigen.forEach(function(cb) {
                if (cb.checked) origenesActivos.push(cb.value);
            });

            var items = feedLista.querySelectorAll('.flavor-feed-item');
            items.forEach(function(item) {
                var origen = item.dataset.origen;
                var comunidad = item.dataset.comunidad;

                var coincideOrigen = origenesActivos.includes(origen);
                var coincideComunidad = !comunidadSeleccionada || comunidad === comunidadSeleccionada;

                if (coincideOrigen && coincideComunidad) {
                    item.classList.remove('oculto');
                } else {
                    item.classList.add('oculto');
                }
            });
        }

        if (filtroComunidad) {
            filtroComunidad.addEventListener('change', filtrarFeed);
        }
        filtrosOrigen.forEach(function(cb) {
            cb.addEventListener('change', filtrarFeed);
        });

        // Modal compartir
        var modal = document.getElementById('modal-compartir');
        var formCompartir = document.getElementById('form-compartir');
        var inputActividadId = document.getElementById('compartir-actividad-id');

        contenedor.addEventListener('click', function(e) {
            var btnCompartir = e.target.closest('.flavor-compartir-btn');
            if (btnCompartir) {
                var actividadId = btnCompartir.dataset.actividad;
                inputActividadId.value = actividadId;
                modal.setAttribute('aria-hidden', 'false');
            }

            var btnCerrar = e.target.closest('.flavor-modal-cerrar');
            var overlay = e.target.closest('.flavor-modal-overlay');
            if (btnCerrar || overlay) {
                modal.setAttribute('aria-hidden', 'true');
            }
        });

        // Enviar compartir
        if (formCompartir) {
            formCompartir.addEventListener('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(formCompartir);
                formData.append('action', 'comunidades_compartir_publicacion');
                formData.append('nonce', nonce);

                fetch(flavorComunidadesConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        alert(data.data.message);
                        modal.setAttribute('aria-hidden', 'true');
                        formCompartir.reset();
                    } else {
                        alert(data.data.message || 'Error al compartir');
                    }
                })
                .catch(function() {
                    alert('Error de conexión');
                });
            });
        }

        // Likes
        contenedor.addEventListener('click', function(e) {
            var btnLike = e.target.closest('.flavor-feed-btn-like');
            if (!btnLike) return;

            var actividadId = btnLike.dataset.actividad;
            var formData = new FormData();
            formData.append('action', 'comunidades_like');
            formData.append('nonce', nonce);
            formData.append('actividad_id', actividadId);

            fetch(flavorComunidadesConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    btnLike.classList.toggle('liked', data.data.liked);
                    btnLike.querySelector('.likes-count').textContent = data.data.likes;
                }
            });
        });
    });
})();
</script>
