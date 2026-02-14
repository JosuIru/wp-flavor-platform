<?php
/**
 * Vista: Listado de comunidades
 *
 * @package FlavorChatIA
 * @var array  $comunidades            Lista de comunidades
 * @var array  $atributos              Atributos del shortcode
 * @var array  $categorias             Categorías disponibles
 * @var int    $identificador_usuario  ID del usuario actual
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas_grid = intval($atributos['columnas'] ?? 3);
$mostrar_filtros = ($atributos['mostrar_filtros'] ?? 'si') === 'si';
?>

<div class="flavor-com-listado" data-columnas="<?php echo esc_attr($columnas_grid); ?>">

    <?php if ($mostrar_filtros): ?>
    <div class="flavor-com-filtros">
        <div class="flavor-com-filtros-grupo">
            <label for="com-filtro-categoria"><?php esc_html_e('Categoría:', 'flavor-chat-ia'); ?></label>
            <select id="com-filtro-categoria" class="flavor-com-select">
                <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $clave => $etiqueta): ?>
                    <option value="<?php echo esc_attr($clave); ?>"
                        <?php selected($atributos['categoria'] ?? '', $clave); ?>>
                        <?php echo esc_html($etiqueta); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flavor-com-filtros-grupo">
            <label for="com-filtro-tipo"><?php esc_html_e('Tipo:', 'flavor-chat-ia'); ?></label>
            <select id="com-filtro-tipo" class="flavor-com-select">
                <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="abierta"><?php esc_html_e('Abiertas', 'flavor-chat-ia'); ?></option>
                <option value="cerrada"><?php esc_html_e('Cerradas', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <div class="flavor-com-filtros-grupo">
            <input type="text" id="com-busqueda" class="flavor-com-input"
                   placeholder="<?php esc_attr_e('Buscar comunidades...', 'flavor-chat-ia'); ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_user_logged_in()): ?>
    <div class="flavor-com-acciones-top">
        <a href="<?php echo esc_url(home_url('/comunidades/crear/')); ?>" class="flavor-com-btn flavor-com-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if (empty($comunidades)): ?>
        <div class="flavor-com-vacio">
            <span class="dashicons dashicons-groups"></span>
            <h3><?php esc_html_e('No hay comunidades disponibles', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Sé el primero en crear una comunidad.', 'flavor-chat-ia'); ?></p>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url('/comunidades/crear/')); ?>" class="flavor-com-btn flavor-com-btn-primary">
                    <?php esc_html_e('Crear la primera comunidad', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flavor-com-grid flavor-com-grid-<?php echo esc_attr($columnas_grid); ?>">
            <?php foreach ($comunidades as $comunidad):
                $etiqueta_categoria = $categorias[$comunidad['categoria']] ?? ucfirst($comunidad['categoria']);
            ?>
                <article class="flavor-com-card" data-id="<?php echo esc_attr($comunidad['id']); ?>">
                    <?php if (!empty($comunidad['imagen'])): ?>
                        <div class="flavor-com-card-imagen">
                            <img src="<?php echo esc_url($comunidad['imagen']); ?>" alt="">
                        </div>
                    <?php else: ?>
                        <div class="flavor-com-card-imagen flavor-com-placeholder">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                    <?php endif; ?>

                    <div class="flavor-com-card-content">
                        <div class="flavor-com-card-header">
                            <span class="flavor-com-categoria flavor-com-cat-<?php echo esc_attr($comunidad['categoria']); ?>">
                                <?php echo esc_html($etiqueta_categoria); ?>
                            </span>
                            <?php if ($comunidad['tipo'] === 'cerrada'): ?>
                                <span class="flavor-com-tipo-badge" title="<?php esc_attr_e('Comunidad cerrada', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-lock"></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="flavor-com-titulo">
                            <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad['id'], home_url('/comunidades/'))); ?>">
                                <?php echo esc_html($comunidad['nombre']); ?>
                            </a>
                        </h3>

                        <p class="flavor-com-descripcion">
                            <?php echo esc_html(wp_trim_words($comunidad['descripcion'] ?? '', 20)); ?>
                        </p>

                        <div class="flavor-com-meta">
                            <div class="flavor-com-meta-item">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span>
                                    <?php printf(
                                        esc_html(_n('%d miembro', '%d miembros', $comunidad['miembros_count'], 'flavor-chat-ia')),
                                        $comunidad['miembros_count']
                                    ); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flavor-com-card-footer">
                            <a href="<?php echo esc_url(add_query_arg('comunidad', $comunidad['id'], home_url('/comunidades/'))); ?>"
                               class="flavor-com-btn flavor-com-btn-ver">
                                <?php esc_html_e('Ver comunidad', 'flavor-chat-ia'); ?>
                            </a>

                            <?php if ($identificador_usuario && $comunidad['tipo'] === 'abierta'): ?>
                                <button type="button" class="flavor-com-btn flavor-com-btn-unirse"
                                        data-comunidad="<?php echo esc_attr($comunidad['id']); ?>">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php esc_html_e('Unirse', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-com-cargar-mas" id="com-cargar-mas" style="display: none;">
            <button type="button" class="flavor-com-btn flavor-com-btn-secondary">
                <?php esc_html_e('Cargar más comunidades', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
