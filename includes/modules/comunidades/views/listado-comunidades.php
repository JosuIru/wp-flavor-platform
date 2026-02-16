<?php
/**
 * Vista: Listado de Comunidades
 *
 * Variables disponibles:
 * - $comunidades: array de comunidades
 * - $categorias: array de categorias
 * - $identificador_usuario: int ID del usuario actual
 * - $atributos: array con configuracion del shortcode
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atributos['columnas'] ?? 3);
$mostrar_filtros = ($atributos['mostrar_filtros'] ?? 'si') === 'si';
?>

<div class="flavor-com-contenedor">
    <?php if ($mostrar_filtros && !empty($categorias)): ?>
    <div class="flavor-com-filtros">
        <div class="flavor-com-filtros-grupo">
            <label for="flavor-com-filtro-categoria"><?php esc_html_e('Categoria:', 'flavor-chat-ia'); ?></label>
            <select id="flavor-com-filtro-categoria" class="flavor-com-select">
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-com-filtros-grupo">
            <label for="flavor-com-filtro-tipo"><?php esc_html_e('Tipo:', 'flavor-chat-ia'); ?></label>
            <select id="flavor-com-filtro-tipo" class="flavor-com-select">
                <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="publica"><?php esc_html_e('Publicas', 'flavor-chat-ia'); ?></option>
                <option value="privada"><?php esc_html_e('Privadas', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <div class="flavor-com-filtros-grupo">
            <input type="text" id="flavor-com-buscar" class="flavor-com-input" placeholder="<?php esc_attr_e('Buscar comunidades...', 'flavor-chat-ia'); ?>">
        </div>
    </div>
    <?php endif; ?>

    <div class="flavor-com-acciones-header">
        <?php if (is_user_logged_in()): ?>
        <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="flavor-com-boton flavor-com-boton-primario">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($comunidades)): ?>
        <div class="flavor-com-vacio">
            <span class="dashicons dashicons-groups"></span>
            <p><?php esc_html_e('No hay comunidades disponibles.', 'flavor-chat-ia'); ?></p>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="flavor-com-boton flavor-com-boton-primario">
                    <?php esc_html_e('Crea la primera comunidad', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-com-grid flavor-com-columnas-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($comunidades as $comunidad):
                $categoria_nombre = $categorias[$comunidad->categoria] ?? ucfirst($comunidad->categoria ?? 'otros');
                $es_miembro = isset($comunidad->es_miembro) && $comunidad->es_miembro;
            ?>
            <article class="flavor-com-card" data-id="<?php echo esc_attr($comunidad->id); ?>" data-categoria="<?php echo esc_attr($comunidad->categoria ?? 'otros'); ?>">
                <?php if (!empty($comunidad->imagen_portada)): ?>
                <div class="flavor-com-card-imagen">
                    <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="<?php echo esc_attr($comunidad->nombre); ?>" loading="lazy">
                </div>
                <?php else: ?>
                <div class="flavor-com-card-imagen flavor-com-imagen-placeholder">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <?php endif; ?>

                <div class="flavor-com-card-contenido">
                    <span class="flavor-com-categoria flavor-com-categoria-<?php echo esc_attr($comunidad->categoria ?? 'otros'); ?>">
                        <?php echo esc_html($categoria_nombre); ?>
                    </span>

                    <?php if (($comunidad->tipo ?? 'publica') === 'privada'): ?>
                    <span class="flavor-com-badge-privada">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e('Privada', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>

                    <h3 class="flavor-com-card-titulo">
                        <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad->id, home_url('/comunidad/'))); ?>">
                            <?php echo esc_html($comunidad->nombre); ?>
                        </a>
                    </h3>

                    <p class="flavor-com-card-descripcion">
                        <?php echo esc_html(wp_trim_words($comunidad->descripcion ?? '', 20, '...')); ?>
                    </p>

                    <div class="flavor-com-card-meta">
                        <span class="flavor-com-meta-item">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($comunidad->total_miembros ?? 0); ?> <?php esc_html_e('miembros', 'flavor-chat-ia'); ?>
                        </span>
                    </div>

                    <div class="flavor-com-card-acciones">
                        <?php if ($es_miembro): ?>
                            <span class="flavor-com-badge-miembro">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e('Eres miembro', 'flavor-chat-ia'); ?>
                            </span>
                        <?php elseif (is_user_logged_in()): ?>
                            <button type="button" class="flavor-com-boton flavor-com-boton-primario flavor-com-btn-unirse" data-comunidad-id="<?php echo esc_attr($comunidad->id); ?>">
                                <?php esc_html_e('Unirse', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>

                        <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad->id, home_url('/comunidad/'))); ?>" class="flavor-com-boton flavor-com-boton-texto">
                            <?php esc_html_e('Ver mas', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-com-cargar-mas" id="flavor-com-cargar-mas" style="display: none;">
            <button type="button" class="flavor-com-boton flavor-com-boton-secundario">
                <?php esc_html_e('Cargar mas comunidades', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
