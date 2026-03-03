<?php
/**
 * Template: Catalogo de Productos para Grupos de Consumo
 *
 * Variables disponibles:
 * - $productos: Array de productos
 * - $ciclo_activo: Datos del ciclo activo actual
 * - $productores: Lista de productores para filtro
 * - $categorias: Lista de categorias para filtro
 * - $lista_compra: Items en la lista del usuario
 * - $atts: Atributos del shortcode
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

$productos = $args['productos'] ?? [];
$ciclo_activo = $args['ciclo_activo'] ?? null;
$productores = $args['productores'] ?? [];
$categorias = $args['categorias'] ?? [];
$lista_compra = $args['lista_compra'] ?? [];
$atributos_shortcode = $args['atts'] ?? [];
$mostrar_filtros = ($atributos_shortcode['mostrar_filtros'] ?? 'si') === 'si';
$columnas = absint($atributos_shortcode['columnas'] ?? 3);
$porcentaje_gestion = $args['porcentaje_gestion'] ?? 0;
$producto_destacado_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

// Obtener precios min/max para el filtro de rango
$precios = array_map(function($producto) {
    return floatval(get_post_meta($producto->ID, '_gc_precio', true));
}, $productos);
$precio_minimo_catalogo = !empty($precios) ? floor(min($precios)) : 0;
$precio_maximo_catalogo = !empty($precios) ? ceil(max($precios)) : 100;
?>

<div class="flavor-gc-catalogo"
     data-columnas="<?php echo esc_attr($columnas); ?>"
     data-grupo-id="<?php echo esc_attr($atributos_shortcode['grupo_id'] ?? ''); ?>"
     data-producto-destacado="<?php echo esc_attr($producto_destacado_id); ?>">

    <!-- Header del Catalogo -->
    <header class="flavor-gc-catalogo-header">
        <div class="flavor-gc-catalogo-titulo">
            <h2><?php _e('Productos disponibles', 'flavor-chat-ia'); ?></h2>
            <?php if (!empty($productos)): ?>
                <span class="flavor-gc-total-productos">
                    <?php printf(
                        _n('%d producto', '%d productos', count($productos), 'flavor-chat-ia'),
                        count($productos)
                    ); ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($ciclo_activo): ?>
            <div class="flavor-gc-ciclo-banner">
                <div class="flavor-gc-ciclo-estado">
                    <span class="flavor-gc-estado-badge flavor-gc-estado-abierto">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Ciclo abierto', 'flavor-chat-ia'); ?>
                    </span>
                </div>
                <div class="flavor-gc-ciclo-info">
                    <div class="flavor-gc-ciclo-fechas">
                        <div class="flavor-gc-fecha-item">
                            <span class="dashicons dashicons-clock"></span>
                            <div>
                                <span class="flavor-gc-fecha-label"><?php _e('Cierre de pedidos', 'flavor-chat-ia'); ?></span>
                                <strong class="flavor-gc-fecha-valor">
                                    <?php echo date_i18n('l j \d\e F, H:i', strtotime($ciclo_activo['fecha_cierre'])); ?>
                                </strong>
                            </div>
                        </div>
                        <div class="flavor-gc-fecha-item">
                            <span class="dashicons dashicons-location"></span>
                            <div>
                                <span class="flavor-gc-fecha-label"><?php _e('Entrega', 'flavor-chat-ia'); ?></span>
                                <strong class="flavor-gc-fecha-valor">
                                    <?php echo date_i18n('l j \d\e F', strtotime($ciclo_activo['fecha_entrega'])); ?>
                                    <?php if (!empty($ciclo_activo['hora_entrega'])): ?>
                                        - <?php echo esc_html($ciclo_activo['hora_entrega']); ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                        <?php if (!empty($ciclo_activo['lugar_entrega'])): ?>
                            <div class="flavor-gc-fecha-item">
                                <span class="dashicons dashicons-admin-home"></span>
                                <div>
                                    <span class="flavor-gc-fecha-label"><?php _e('Lugar de entrega', 'flavor-chat-ia'); ?></span>
                                    <strong class="flavor-gc-fecha-valor"><?php echo esc_html($ciclo_activo['lugar_entrega']); ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-gc-tiempo-restante" data-cierre="<?php echo esc_attr($ciclo_activo['fecha_cierre']); ?>">
                        <span class="flavor-gc-countdown-label"><?php _e('Tiempo restante:', 'flavor-chat-ia'); ?></span>
                        <span class="flavor-gc-countdown-valor" id="gc-countdown"></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="flavor-gc-ciclo-banner flavor-gc-ciclo-cerrado">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php _e('No hay ciclo de pedidos abierto actualmente. Los productos mostrados son a modo informativo.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Buscador -->
        <div class="flavor-gc-buscador">
            <span class="dashicons dashicons-search"></span>
            <input type="search"
                   id="gc-buscar-producto"
                   placeholder="<?php esc_attr_e('Buscar productos...', 'flavor-chat-ia'); ?>"
                   autocomplete="off">
            <button type="button" class="flavor-gc-buscar-limpiar" id="gc-limpiar-busqueda" style="display:none;">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </header>

    <div class="flavor-gc-catalogo-contenido">
        <?php if ($mostrar_filtros): ?>
        <!-- Sidebar con Filtros -->
        <aside class="flavor-gc-filtros-sidebar">
            <div class="flavor-gc-filtros-header">
                <h3>
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Filtros', 'flavor-chat-ia'); ?>
                </h3>
                <button type="button" class="flavor-gc-filtros-limpiar" id="gc-limpiar-filtros">
                    <?php _e('Limpiar', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Filtro por Categoria -->
            <?php if (!empty($categorias)): ?>
            <div class="flavor-gc-filtro-grupo">
                <h4 class="flavor-gc-filtro-titulo">
                    <?php _e('Categorias', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div class="flavor-gc-filtro-opciones">
                    <?php foreach ($categorias as $categoria): ?>
                        <label class="flavor-gc-filtro-opcion">
                            <input type="checkbox"
                                   name="gc_categoria[]"
                                   value="<?php echo esc_attr($categoria->slug); ?>"
                                   class="gc-filtro-categoria">
                            <span class="flavor-gc-checkbox"></span>
                            <span class="flavor-gc-opcion-texto"><?php echo esc_html($categoria->name); ?></span>
                            <span class="flavor-gc-opcion-count">(<?php echo esc_html($categoria->count); ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filtro por Productor -->
            <?php if (!empty($productores)): ?>
            <div class="flavor-gc-filtro-grupo">
                <h4 class="flavor-gc-filtro-titulo">
                    <?php _e('Productores', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div class="flavor-gc-filtro-opciones">
                    <?php foreach ($productores as $productor): ?>
                        <?php
                        $es_ecologico = get_post_meta($productor->ID, '_gc_certificacion_eco', true);
                        ?>
                        <label class="flavor-gc-filtro-opcion">
                            <input type="checkbox"
                                   name="gc_productor[]"
                                   value="<?php echo esc_attr($productor->ID); ?>"
                                   class="gc-filtro-productor">
                            <span class="flavor-gc-checkbox"></span>
                            <span class="flavor-gc-opcion-texto">
                                <?php echo esc_html($productor->post_title); ?>
                                <?php if ($es_ecologico): ?>
                                    <span class="flavor-gc-badge-eco-mini" title="<?php esc_attr_e('Productor ecologico', 'flavor-chat-ia'); ?>"><?php echo esc_html__('ECO', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filtro por Precio -->
            <div class="flavor-gc-filtro-grupo">
                <h4 class="flavor-gc-filtro-titulo">
                    <?php _e('Rango de precio', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div class="flavor-gc-filtro-precio">
                    <div class="flavor-gc-precio-rango">
                        <input type="range"
                               id="gc-precio-min"
                               min="<?php echo esc_attr($precio_minimo_catalogo); ?>"
                               max="<?php echo esc_attr($precio_maximo_catalogo); ?>"
                               value="<?php echo esc_attr($precio_minimo_catalogo); ?>"
                               class="gc-filtro-precio-slider">
                        <input type="range"
                               id="gc-precio-max"
                               min="<?php echo esc_attr($precio_minimo_catalogo); ?>"
                               max="<?php echo esc_attr($precio_maximo_catalogo); ?>"
                               value="<?php echo esc_attr($precio_maximo_catalogo); ?>"
                               class="gc-filtro-precio-slider">
                    </div>
                    <div class="flavor-gc-precio-valores">
                        <span id="gc-precio-min-valor"><?php echo esc_html($precio_minimo_catalogo); ?></span>
                        <span class="flavor-gc-precio-separador">-</span>
                        <span id="gc-precio-max-valor"><?php echo esc_html($precio_maximo_catalogo); ?></span>
                    </div>
                </div>
            </div>

            <!-- Filtro Solo Disponibles -->
            <div class="flavor-gc-filtro-grupo">
                <label class="flavor-gc-filtro-toggle">
                    <input type="checkbox" id="gc-solo-disponibles" class="gc-filtro-disponibles">
                    <span class="flavor-gc-toggle"></span>
                    <span class="flavor-gc-toggle-texto"><?php _e('Solo productos con stock', 'flavor-chat-ia'); ?></span>
                </label>
            </div>

            <!-- Filtro Solo Ecologicos -->
            <div class="flavor-gc-filtro-grupo">
                <label class="flavor-gc-filtro-toggle">
                    <input type="checkbox" id="gc-solo-ecologicos" class="gc-filtro-ecologicos">
                    <span class="flavor-gc-toggle"></span>
                    <span class="flavor-gc-toggle-texto"><?php _e('Solo productos ecologicos', 'flavor-chat-ia'); ?></span>
                </label>
            </div>

            <!-- Filtros activos en movil -->
            <div class="flavor-gc-filtros-aplicados" id="gc-filtros-aplicados" style="display:none;">
                <!-- Se llenan dinamicamente -->
            </div>
        </aside>

        <!-- Boton para mostrar filtros en movil -->
        <button type="button" class="flavor-gc-filtros-toggle-movil" id="gc-toggle-filtros">
            <span class="dashicons dashicons-filter"></span>
            <?php _e('Filtros', 'flavor-chat-ia'); ?>
            <span class="flavor-gc-filtros-count" id="gc-filtros-count" style="display:none;">0</span>
        </button>
        <?php endif; ?>

        <!-- Area principal con productos -->
        <main class="flavor-gc-catalogo-main">
            <!-- Barra de ordenacion -->
            <div class="flavor-gc-ordenar-barra">
                <div class="flavor-gc-vista-opciones">
                    <button type="button" class="flavor-gc-vista-btn active" data-vista="grid" title="<?php esc_attr_e('Vista cuadricula', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-grid-view"></span>
                    </button>
                    <button type="button" class="flavor-gc-vista-btn" data-vista="lista" title="<?php esc_attr_e('Vista lista', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-list-view"></span>
                    </button>
                </div>
                <div class="flavor-gc-ordenar">
                    <label for="gc-ordenar"><?php _e('Ordenar por:', 'flavor-chat-ia'); ?></label>
                    <select id="gc-ordenar" class="flavor-gc-select">
                        <option value="nombre-asc"><?php _e('Nombre A-Z', 'flavor-chat-ia'); ?></option>
                        <option value="nombre-desc"><?php _e('Nombre Z-A', 'flavor-chat-ia'); ?></option>
                        <option value="precio-asc"><?php _e('Precio: menor a mayor', 'flavor-chat-ia'); ?></option>
                        <option value="precio-desc"><?php _e('Precio: mayor a menor', 'flavor-chat-ia'); ?></option>
                        <option value="productor"><?php _e('Por productor', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Grid de productos -->
            <div class="flavor-gc-productos-grid flavor-gc-columnas-<?php echo esc_attr($columnas); ?>" id="gc-productos-grid">
                <?php if (empty($productos)): ?>
                    <div class="flavor-gc-sin-productos">
                        <span class="dashicons dashicons-carrot"></span>
                        <p><?php _e('No hay productos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $producto_id = $producto->ID;
                        $precio = floatval(get_post_meta($producto_id, '_gc_precio', true));
                        $unidad = get_post_meta($producto_id, '_gc_unidad', true) ?: 'ud';
                        $stock = get_post_meta($producto_id, '_gc_stock', true);
                        $cantidad_minima = get_post_meta($producto_id, '_gc_cantidad_minima', true) ?: 1;
                        $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
                        $productor = $productor_id ? get_post($productor_id) : null;
                        $imagen_url = get_the_post_thumbnail_url($producto_id, 'medium');
                        $en_lista = isset($lista_compra[$producto_id]);
                        $cantidad_en_lista = $en_lista ? $lista_compra[$producto_id] : $cantidad_minima;
                        $es_ecologico = $productor_id ? get_post_meta($productor_id, '_gc_certificacion_eco', true) : false;
                        $tiene_stock = empty($stock) || floatval($stock) > 0;
                        $stock_bajo = !empty($stock) && floatval($stock) <= 5;
                        $categorias_producto = get_the_terms($producto_id, 'gc_categoria');
                        $categoria_slugs = $categorias_producto ? implode(' ', wp_list_pluck($categorias_producto, 'slug')) : '';
                        ?>
                        <article class="flavor-gc-producto-card <?php echo $en_lista ? 'en-lista' : ''; ?> <?php echo !$tiene_stock ? 'sin-stock' : ''; ?> <?php echo $producto_destacado_id === $producto_id ? 'producto-destacado' : ''; ?>"
                                 data-producto-id="<?php echo esc_attr($producto_id); ?>"
                                 data-precio="<?php echo esc_attr($precio); ?>"
                                 data-nombre="<?php echo esc_attr(strtolower($producto->post_title)); ?>"
                                 data-productor-id="<?php echo esc_attr($productor_id); ?>"
                                 data-categorias="<?php echo esc_attr($categoria_slugs); ?>"
                                 data-stock="<?php echo esc_attr($stock); ?>"
                                 data-ecologico="<?php echo $es_ecologico ? '1' : '0'; ?>">

                            <div class="flavor-gc-producto-imagen">
                                <?php if ($imagen_url): ?>
                                    <img src="<?php echo esc_url($imagen_url); ?>"
                                         alt="<?php echo esc_attr($producto->post_title); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="flavor-gc-imagen-placeholder">
                                        <span class="dashicons dashicons-carrot"></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($es_ecologico): ?>
                                    <span class="flavor-gc-badge-eco" title="<?php esc_attr_e('Producto ecologico certificado', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-awards"></span>
                                        <?php _e('ECO', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($en_lista): ?>
                                    <span class="flavor-gc-badge-en-lista">
                                        <span class="dashicons dashicons-yes"></span>
                                    </span>
                                <?php endif; ?>

                                <?php if (!$tiene_stock): ?>
                                    <div class="flavor-gc-overlay-agotado">
                                        <span><?php _e('Agotado', 'flavor-chat-ia'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-gc-producto-contenido">
                                <h3 class="flavor-gc-producto-nombre">
                                    <a href="<?php echo esc_url(add_query_arg('product', intval($producto_id), home_url('/mi-portal/grupos-consumo/productos/'))); ?>">
                                        <?php echo esc_html($producto->post_title); ?>
                                    </a>
                                </h3>

                                <?php if ($productor): ?>
                                    <p class="flavor-gc-producto-productor">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <a href="<?php echo esc_url(add_query_arg('productor', intval($productor_id), home_url('/mi-portal/grupos-consumo/productores-cercanos/'))); ?>">
                                            <?php echo esc_html($productor->post_title); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>

                                <div class="flavor-gc-producto-precio-stock">
                                    <div class="flavor-gc-producto-precio">
                                        <span class="flavor-gc-precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?></span>
                                        <span class="flavor-gc-precio-moneda"><?php echo esc_html__('EUR', 'flavor-chat-ia'); ?></span>
                                        <span class="flavor-gc-precio-unidad">/ <?php echo esc_html($unidad); ?></span>
                                    </div>

                                    <?php if ($tiene_stock && !empty($stock)): ?>
                                        <div class="flavor-gc-producto-stock <?php echo $stock_bajo ? 'stock-bajo' : ''; ?>">
                                            <?php if ($stock_bajo): ?>
                                                <span class="dashicons dashicons-warning"></span>
                                                <?php printf(__('Quedan %s', 'flavor-chat-ia'), number_format($stock, 0, ',', '.')); ?>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-yes"></span>
                                                <?php _e('En stock', 'flavor-chat-ia'); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($tiene_stock && $ciclo_activo): ?>
                                    <div class="flavor-gc-producto-acciones">
                                        <?php if (is_user_logged_in()): ?>
                                            <div class="flavor-gc-cantidad-control">
                                                <button type="button" class="flavor-gc-cantidad-btn flavor-gc-cantidad-menos" data-action="decrementar">
                                                    <span class="dashicons dashicons-minus"></span>
                                                </button>
                                                <input type="number"
                                                       class="flavor-gc-cantidad-input"
                                                       value="<?php echo esc_attr($cantidad_en_lista); ?>"
                                                       min="<?php echo esc_attr($cantidad_minima); ?>"
                                                       max="<?php echo esc_attr($stock ?: 999); ?>"
                                                       step="1"
                                                       data-min="<?php echo esc_attr($cantidad_minima); ?>">
                                                <button type="button" class="flavor-gc-cantidad-btn flavor-gc-cantidad-mas" data-action="incrementar">
                                                    <span class="dashicons dashicons-plus"></span>
                                                </button>
                                            </div>
                                            <button type="button" class="flavor-gc-btn-agregar <?php echo $en_lista ? 'en-lista' : ''; ?>">
                                                <span class="dashicons <?php echo $en_lista ? 'dashicons-yes' : 'dashicons-cart'; ?>"></span>
                                                <span class="flavor-gc-btn-texto">
                                                    <?php echo $en_lista ? __('En pedido', 'flavor-chat-ia') : __('Anadir', 'flavor-chat-ia'); ?>
                                                </span>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/productos/'))); ?>" class="flavor-gc-btn-login">
                                                <span class="dashicons dashicons-lock"></span>
                                                <?php _e('Inicia sesion para pedir', 'flavor-chat-ia'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!$ciclo_activo): ?>
                                    <div class="flavor-gc-producto-sin-ciclo">
                                        <span class="dashicons dashicons-info"></span>
                                        <?php _e('Pedidos cerrados', 'flavor-chat-ia'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Mensaje cuando no hay resultados de filtro -->
            <div class="flavor-gc-sin-resultados" id="gc-sin-resultados" style="display:none;">
                <span class="dashicons dashicons-search"></span>
                <h3><?php _e('No se encontraron productos', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Prueba a cambiar los filtros o el termino de busqueda.', 'flavor-chat-ia'); ?></p>
                <button type="button" class="flavor-gc-btn flavor-gc-btn-secondary" id="gc-reiniciar-filtros">
                    <?php _e('Limpiar filtros', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Paginacion AJAX (Load More) -->
            <?php if (count($productos) >= 12): ?>
            <div class="flavor-gc-cargar-mas" id="gc-cargar-mas">
                <button type="button" class="flavor-gc-btn flavor-gc-btn-secondary" id="gc-btn-cargar-mas" data-pagina="1">
                    <span class="flavor-gc-btn-texto"><?php _e('Cargar mas productos', 'flavor-chat-ia'); ?></span>
                    <span class="flavor-gc-btn-loading" style="display:none;">
                        <span class="dashicons dashicons-update-alt flavor-spin"></span>
                        <?php _e('Cargando...', 'flavor-chat-ia'); ?>
                    </span>
                </button>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Overlay para filtros en movil -->
<div class="flavor-gc-filtros-overlay" id="gc-filtros-overlay"></div>
