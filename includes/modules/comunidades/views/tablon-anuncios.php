<?php
/**
 * Vista: Tablón de Anuncios Inter-Comunidades
 *
 * Muestra anuncios importantes de todas las comunidades del usuario y la red federada
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$puede_crear = is_user_logged_in();
$solo_destacados = isset($atributos['destacados']) && $atributos['destacados'] === 'true';
$incluir_red = !isset($atributos['incluir_red']) || $atributos['incluir_red'] !== 'false';
$limite = intval($atributos['limite'] ?? 20);

// Categorías de anuncios
$categorias = [
    'general'     => ['label' => __('General', 'flavor-chat-ia'), 'icon' => '📢', 'color' => '#3b82f6'],
    'urgente'     => ['label' => __('Urgente', 'flavor-chat-ia'), 'icon' => '🚨', 'color' => '#ef4444'],
    'evento'      => ['label' => __('Evento', 'flavor-chat-ia'), 'icon' => '📅', 'color' => '#8b5cf6'],
    'convocatoria' => ['label' => __('Convocatoria', 'flavor-chat-ia'), 'icon' => '📋', 'color' => '#f59e0b'],
    'recurso'     => ['label' => __('Recurso', 'flavor-chat-ia'), 'icon' => '📦', 'color' => '#10b981'],
    'colaboracion' => ['label' => __('Colaboración', 'flavor-chat-ia'), 'icon' => '🤝', 'color' => '#ec4899'],
];
?>

<div class="flavor-tablon-anuncios" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">

    <!-- Cabecera -->
    <header class="flavor-tablon-header">
        <div class="flavor-tablon-titulo-wrapper">
            <h2 class="flavor-tablon-titulo">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Tablón de Anuncios', 'flavor-chat-ia'); ?>
            </h2>
            <span class="flavor-tablon-subtitle">
                <?php esc_html_e('Anuncios de tus comunidades y la red', 'flavor-chat-ia'); ?>
            </span>
        </div>

        <?php if ($puede_crear): ?>
        <button type="button" class="flavor-btn-nuevo-anuncio" id="btn-nuevo-anuncio">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nuevo anuncio', 'flavor-chat-ia'); ?>
        </button>
        <?php endif; ?>
    </header>

    <!-- Filtros por categoría -->
    <nav class="flavor-tablon-filtros">
        <button type="button" class="flavor-filtro-cat activo" data-categoria="todos">
            <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($categorias as $key => $config): ?>
        <button type="button" class="flavor-filtro-cat" data-categoria="<?php echo esc_attr($key); ?>">
            <span><?php echo $config['icon']; ?></span>
            <?php echo esc_html($config['label']); ?>
        </button>
        <?php endforeach; ?>
    </nav>

    <!-- Lista de anuncios -->
    <div class="flavor-tablon-contenido" id="lista-anuncios">
        <div class="flavor-tablon-cargando">
            <span class="flavor-spinner"></span>
            <?php esc_html_e('Cargando anuncios...', 'flavor-chat-ia'); ?>
        </div>
    </div>

    <!-- Modal crear anuncio -->
    <?php if ($puede_crear): ?>
    <div class="flavor-modal" id="modal-nuevo-anuncio" style="display:none;">
        <div class="flavor-modal-overlay"></div>
        <div class="flavor-modal-contenido flavor-modal-lg">
            <header class="flavor-modal-header">
                <h3>
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Crear nuevo anuncio', 'flavor-chat-ia'); ?>
                </h3>
                <button type="button" class="flavor-modal-cerrar">&times;</button>
            </header>

            <form id="form-nuevo-anuncio" class="flavor-modal-body">
                <!-- Selección de comunidad -->
                <div class="flavor-form-grupo">
                    <label for="anuncio-comunidad"><?php esc_html_e('Publicar en comunidad:', 'flavor-chat-ia'); ?></label>
                    <select id="anuncio-comunidad" name="comunidad_id" required>
                        <option value=""><?php esc_html_e('Selecciona una comunidad', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <!-- Título -->
                <div class="flavor-form-grupo">
                    <label for="anuncio-titulo"><?php esc_html_e('Título del anuncio:', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="anuncio-titulo" name="titulo" maxlength="200" required
                           placeholder="<?php esc_attr_e('Escribe un título claro y conciso', 'flavor-chat-ia'); ?>">
                </div>

                <!-- Contenido -->
                <div class="flavor-form-grupo">
                    <label for="anuncio-contenido"><?php esc_html_e('Contenido:', 'flavor-chat-ia'); ?></label>
                    <textarea id="anuncio-contenido" name="contenido" rows="4" required
                              placeholder="<?php esc_attr_e('Describe el anuncio con todo el detalle necesario', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <!-- Categoría -->
                <div class="flavor-form-grupo">
                    <label><?php esc_html_e('Categoría:', 'flavor-chat-ia'); ?></label>
                    <div class="flavor-categorias-grid">
                        <?php foreach ($categorias as $key => $config): ?>
                        <label class="flavor-categoria-opcion">
                            <input type="radio" name="categoria" value="<?php echo esc_attr($key); ?>" <?php echo $key === 'general' ? 'checked' : ''; ?>>
                            <span class="flavor-categoria-chip" style="--cat-color: <?php echo esc_attr($config['color']); ?>">
                                <?php echo $config['icon']; ?>
                                <?php echo esc_html($config['label']); ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Opciones adicionales -->
                <div class="flavor-form-grupo flavor-opciones-adicionales">
                    <label class="flavor-checkbox-label">
                        <input type="checkbox" name="destacado" value="1">
                        <span class="flavor-checkbox-custom"></span>
                        <span>
                            <strong><?php esc_html_e('Marcar como destacado', 'flavor-chat-ia'); ?></strong>
                            <small><?php esc_html_e('El anuncio aparecerá primero', 'flavor-chat-ia'); ?></small>
                        </span>
                    </label>

                    <label class="flavor-checkbox-label">
                        <input type="checkbox" name="compartir_red" value="1">
                        <span class="flavor-checkbox-custom"></span>
                        <span>
                            <strong><?php esc_html_e('Compartir en la red federada', 'flavor-chat-ia'); ?></strong>
                            <small><?php esc_html_e('Visible para otras comunidades de la red', 'flavor-chat-ia'); ?></small>
                        </span>
                    </label>
                </div>

                <!-- Fecha de expiración opcional -->
                <div class="flavor-form-grupo">
                    <label for="anuncio-expiracion"><?php esc_html_e('Fecha de expiración (opcional):', 'flavor-chat-ia'); ?></label>
                    <input type="date" id="anuncio-expiracion" name="fecha_expiracion"
                           min="<?php echo esc_attr(date('Y-m-d')); ?>">
                    <small><?php esc_html_e('El anuncio se ocultará automáticamente después de esta fecha', 'flavor-chat-ia'); ?></small>
                </div>
            </form>

            <footer class="flavor-modal-footer">
                <button type="button" class="flavor-btn-secundario" id="cancelar-anuncio">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="flavor-btn-primario" id="publicar-anuncio">
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Publicar anuncio', 'flavor-chat-ia'); ?>
                </button>
            </footer>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-tablon-anuncios {
    max-width: 900px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-tablon-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-tablon-titulo-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-tablon-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.5em;
    color: var(--gc-gray-900, #111827);
}

.flavor-tablon-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-tablon-subtitle {
    font-size: 0.9em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-btn-nuevo-anuncio {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: var(--gc-primary, #2e7d32);
    color: white;
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    font-size: 0.95em;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-btn-nuevo-anuncio:hover {
    background: var(--gc-primary-dark, #1b5e20);
}

.flavor-btn-nuevo-anuncio .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Filtros */
.flavor-tablon-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-filtro-cat {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 14px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 20px;
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-filtro-cat:hover {
    border-color: var(--gc-primary, #2e7d32);
    color: var(--gc-primary, #2e7d32);
}

.flavor-filtro-cat.activo {
    background: var(--gc-primary, #2e7d32);
    border-color: var(--gc-primary, #2e7d32);
    color: white;
}

/* Contenido */
.flavor-tablon-cargando {
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

/* Anuncios */
.flavor-anuncio-card {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    margin-bottom: 16px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.flavor-anuncio-card:hover {
    box-shadow: var(--gc-shadow, 0 4px 6px -1px rgba(0,0,0,0.1));
}

.flavor-anuncio-card.destacado {
    border-left: 4px solid var(--gc-primary, #2e7d32);
}

.flavor-anuncio-card.urgente {
    border-left-color: #ef4444;
    background: linear-gradient(to right, rgba(239, 68, 68, 0.05), transparent);
}

.flavor-anuncio-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px 0;
}

.flavor-anuncio-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-anuncio-categoria {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--cat-color, #3b82f6);
    color: white;
    border-radius: 4px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-anuncio-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--gc-gray-100, #f3f4f6);
    color: var(--gc-gray-600, #4b5563);
    border-radius: 4px;
    font-size: 0.75em;
}

.flavor-anuncio-badge.federado {
    background: #d1fae5;
    color: #065f46;
}

.flavor-anuncio-badge.destacado {
    background: #fef3c7;
    color: #92400e;
}

.flavor-anuncio-fecha {
    font-size: 0.8em;
    color: var(--gc-gray-400, #9ca3af);
}

.flavor-anuncio-body {
    padding: 16px 20px;
}

.flavor-anuncio-titulo {
    margin: 0 0 8px;
    font-size: 1.1em;
    color: var(--gc-gray-900, #111827);
}

.flavor-anuncio-contenido {
    margin: 0;
    font-size: 0.95em;
    color: var(--gc-gray-600, #4b5563);
    line-height: 1.5;
}

.flavor-anuncio-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-top: 1px solid var(--gc-gray-100, #f3f4f6);
}

.flavor-anuncio-comunidad {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85em;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-anuncio-comunidad-img {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    object-fit: cover;
}

.flavor-anuncio-acciones {
    display: flex;
    gap: 8px;
}

.flavor-anuncio-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 6px;
    font-size: 0.85em;
    color: var(--gc-gray-700, #374151);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-anuncio-btn:hover {
    background: var(--gc-gray-100, #f3f4f6);
}

.flavor-anuncio-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Vacío */
.flavor-tablon-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--gc-gray-50, #f9fafb);
    border-radius: var(--gc-border-radius, 12px);
}

.flavor-tablon-vacio .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--gc-gray-400, #9ca3af);
    margin-bottom: 16px;
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.flavor-modal-contenido {
    position: relative;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    background: white;
    border-radius: var(--gc-border-radius, 12px);
    display: flex;
    flex-direction: column;
    box-shadow: var(--gc-shadow-xl, 0 25px 50px -12px rgba(0, 0, 0, 0.25));
}

.flavor-modal-lg {
    max-width: 600px;
}

.flavor-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1em;
    color: var(--gc-gray-900, #111827);
}

.flavor-modal-header .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-modal-cerrar {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--gc-gray-400, #9ca3af);
    cursor: pointer;
    line-height: 1;
}

.flavor-modal-cerrar:hover {
    color: var(--gc-gray-600, #4b5563);
}

.flavor-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

.flavor-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid var(--gc-gray-200, #e5e7eb);
}

/* Formulario */
.flavor-form-grupo {
    margin-bottom: 20px;
}

.flavor-form-grupo > label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--gc-gray-700, #374151);
}

.flavor-form-grupo input[type="text"],
.flavor-form-grupo input[type="date"],
.flavor-form-grupo select,
.flavor-form-grupo textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: 6px;
    font-size: 0.95em;
    transition: border-color 0.2s;
}

.flavor-form-grupo input:focus,
.flavor-form-grupo select:focus,
.flavor-form-grupo textarea:focus {
    border-color: var(--gc-primary, #2e7d32);
    outline: none;
}

.flavor-form-grupo small {
    display: block;
    margin-top: 4px;
    font-size: 0.8em;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-categorias-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-categoria-opcion {
    cursor: pointer;
}

.flavor-categoria-opcion input {
    position: absolute;
    opacity: 0;
}

.flavor-categoria-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 14px;
    background: var(--gc-gray-100, #f3f4f6);
    border: 2px solid transparent;
    border-radius: 20px;
    font-size: 0.85em;
    color: var(--gc-gray-700, #374151);
    transition: all 0.2s;
}

.flavor-categoria-opcion input:checked + .flavor-categoria-chip {
    background: var(--cat-color);
    color: white;
    border-color: var(--cat-color);
}

.flavor-opciones-adicionales {
    background: var(--gc-gray-50, #f9fafb);
    padding: 16px;
    border-radius: 8px;
}

.flavor-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    cursor: pointer;
}

.flavor-checkbox-label + .flavor-checkbox-label {
    border-top: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-checkbox-label input {
    position: absolute;
    opacity: 0;
}

.flavor-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--gc-gray-300, #d1d5db);
    border-radius: 4px;
    flex-shrink: 0;
    position: relative;
    transition: all 0.2s;
}

.flavor-checkbox-label input:checked + .flavor-checkbox-custom {
    background: var(--gc-primary, #2e7d32);
    border-color: var(--gc-primary, #2e7d32);
}

.flavor-checkbox-label input:checked + .flavor-checkbox-custom::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
}

.flavor-checkbox-label span:last-child {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-checkbox-label small {
    color: var(--gc-gray-500, #6b7280);
}

.flavor-btn-secundario {
    padding: 10px 20px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: var(--gc-button-radius, 6px);
    color: var(--gc-gray-700, #374151);
    font-size: 0.95em;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-secundario:hover {
    background: var(--gc-gray-50, #f9fafb);
}

.flavor-btn-primario {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: var(--gc-primary, #2e7d32);
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    color: white;
    font-size: 0.95em;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-btn-primario:hover {
    background: var(--gc-primary-dark, #1b5e20);
}

.flavor-btn-primario .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

@media (max-width: 600px) {
    .flavor-tablon-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-btn-nuevo-anuncio {
        width: 100%;
        justify-content: center;
    }

    .flavor-modal-contenido {
        width: 95%;
        max-height: 90vh;
    }

    .flavor-anuncio-footer {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-tablon-anuncios');
        if (!contenedor) return;

        var nonce = contenedor.dataset.nonce;
        var listaAnuncios = document.getElementById('lista-anuncios');
        var modalNuevoAnuncio = document.getElementById('modal-nuevo-anuncio');
        var formNuevoAnuncio = document.getElementById('form-nuevo-anuncio');
        var categoriaActual = 'todos';

        // Categorías con colores
        var categorias = <?php echo json_encode($categorias); ?>;

        // Cargar anuncios al inicio
        cargarAnuncios();

        // Filtros por categoría
        document.querySelectorAll('.flavor-filtro-cat').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.flavor-filtro-cat').forEach(function(b) {
                    b.classList.remove('activo');
                });
                btn.classList.add('activo');
                categoriaActual = btn.dataset.categoria;
                cargarAnuncios();
            });
        });

        // Modal nuevo anuncio
        var btnNuevoAnuncio = document.getElementById('btn-nuevo-anuncio');
        if (btnNuevoAnuncio) {
            btnNuevoAnuncio.addEventListener('click', function() {
                cargarComunidadesUsuario();
                modalNuevoAnuncio.style.display = 'flex';
            });
        }

        // Cerrar modal
        if (modalNuevoAnuncio) {
            document.querySelector('.flavor-modal-cerrar').addEventListener('click', cerrarModal);
            document.querySelector('.flavor-modal-overlay').addEventListener('click', cerrarModal);
            document.getElementById('cancelar-anuncio').addEventListener('click', cerrarModal);
            document.getElementById('publicar-anuncio').addEventListener('click', publicarAnuncio);
        }

        function cerrarModal() {
            modalNuevoAnuncio.style.display = 'none';
            formNuevoAnuncio.reset();
        }

        function cargarAnuncios() {
            listaAnuncios.innerHTML = '<div class="flavor-tablon-cargando"><span class="flavor-spinner"></span><?php echo esc_js(__('Cargando anuncios...', 'flavor-chat-ia')); ?></div>';

            var formData = new FormData();
            formData.append('action', 'comunidades_obtener_anuncios');
            formData.append('nonce', nonce);
            formData.append('categoria', categoriaActual);
            formData.append('incluir_red', 'true');

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    renderizarAnuncios(data.data.anuncios);
                } else {
                    listaAnuncios.innerHTML = '<div class="flavor-tablon-vacio">' +
                        '<span class="dashicons dashicons-warning"></span>' +
                        '<h3>Error</h3>' +
                        '<p>' + (data.data?.message || '<?php echo esc_js(__('Error al cargar anuncios', 'flavor-chat-ia')); ?>') + '</p>' +
                        '</div>';
                }
            });
        }

        function renderizarAnuncios(anuncios) {
            if (!anuncios || anuncios.length === 0) {
                listaAnuncios.innerHTML = '<div class="flavor-tablon-vacio">' +
                    '<span class="dashicons dashicons-megaphone"></span>' +
                    '<h3><?php echo esc_js(__('No hay anuncios', 'flavor-chat-ia')); ?></h3>' +
                    '<p><?php echo esc_js(__('Los anuncios de tus comunidades aparecerán aquí.', 'flavor-chat-ia')); ?></p>' +
                    '</div>';
                return;
            }

            var html = '';
            anuncios.forEach(function(anuncio) {
                var catConfig = categorias[anuncio.categoria] || categorias.general;
                var clases = ['flavor-anuncio-card'];
                if (anuncio.destacado) clases.push('destacado');
                if (anuncio.categoria === 'urgente') clases.push('urgente');

                html += '<article class="' + clases.join(' ') + '" data-id="' + anuncio.id + '">' +
                    '<div class="flavor-anuncio-header">' +
                        '<div class="flavor-anuncio-meta">' +
                            '<span class="flavor-anuncio-categoria" style="--cat-color: ' + catConfig.color + '">' +
                                catConfig.icon + ' ' + catConfig.label +
                            '</span>';

                if (anuncio.destacado) {
                    html += '<span class="flavor-anuncio-badge destacado">⭐ <?php echo esc_js(__('Destacado', 'flavor-chat-ia')); ?></span>';
                }
                if (anuncio.origen === 'federado') {
                    html += '<span class="flavor-anuncio-badge federado">🌐 <?php echo esc_js(__('Red federada', 'flavor-chat-ia')); ?></span>';
                }

                html += '</div>' +
                        '<span class="flavor-anuncio-fecha">' + anuncio.fecha + '</span>' +
                    '</div>' +
                    '<div class="flavor-anuncio-body">' +
                        '<h3 class="flavor-anuncio-titulo">' + escapeHtml(anuncio.titulo) + '</h3>' +
                        '<p class="flavor-anuncio-contenido">' + escapeHtml(anuncio.contenido) + '</p>' +
                    '</div>' +
                    '<div class="flavor-anuncio-footer">' +
                        '<div class="flavor-anuncio-comunidad">';

                if (anuncio.comunidad_imagen) {
                    html += '<img src="' + anuncio.comunidad_imagen + '" alt="" class="flavor-anuncio-comunidad-img">';
                }
                html += '<span>' + escapeHtml(anuncio.comunidad_nombre) + '</span>' +
                        '</div>' +
                        '<div class="flavor-anuncio-acciones">';

                if (anuncio.url) {
                    html += '<a href="' + anuncio.url + '" class="flavor-anuncio-btn"' +
                            (anuncio.origen === 'federado' ? ' target="_blank" rel="noopener"' : '') + '>' +
                            '<span class="dashicons dashicons-visibility"></span><?php echo esc_js(__('Ver más', 'flavor-chat-ia')); ?>' +
                            '</a>';
                }

                html += '</div></div></article>';
            });

            listaAnuncios.innerHTML = html;
        }

        function cargarComunidadesUsuario() {
            var select = document.getElementById('anuncio-comunidad');
            select.innerHTML = '<option value=""><?php echo esc_js(__('Cargando...', 'flavor-chat-ia')); ?></option>';

            var formData = new FormData();
            formData.append('action', 'comunidades_mis_comunidades_admin');
            formData.append('nonce', nonce);

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                select.innerHTML = '<option value=""><?php echo esc_js(__('Selecciona una comunidad', 'flavor-chat-ia')); ?></option>';
                if (data.success && data.data.comunidades) {
                    data.data.comunidades.forEach(function(comunidad) {
                        select.innerHTML += '<option value="' + comunidad.id + '">' + escapeHtml(comunidad.nombre) + '</option>';
                    });
                }
            });
        }

        function publicarAnuncio() {
            var formData = new FormData(formNuevoAnuncio);
            formData.append('action', 'comunidades_crear_anuncio');
            formData.append('nonce', nonce);

            // Validación básica
            if (!formData.get('comunidad_id') || !formData.get('titulo') || !formData.get('contenido')) {
                alert('<?php echo esc_js(__('Por favor completa todos los campos obligatorios', 'flavor-chat-ia')); ?>');
                return;
            }

            var btnPublicar = document.getElementById('publicar-anuncio');
            btnPublicar.disabled = true;
            btnPublicar.innerHTML = '<span class="flavor-spinner" style="width:16px;height:16px;border-width:2px;"></span><?php echo esc_js(__('Publicando...', 'flavor-chat-ia')); ?>';

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btnPublicar.disabled = false;
                btnPublicar.innerHTML = '<span class="dashicons dashicons-megaphone"></span><?php echo esc_js(__('Publicar anuncio', 'flavor-chat-ia')); ?>';

                if (data.success) {
                    cerrarModal();
                    cargarAnuncios();
                } else {
                    alert(data.data?.message || '<?php echo esc_js(__('Error al publicar', 'flavor-chat-ia')); ?>');
                }
            });
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
