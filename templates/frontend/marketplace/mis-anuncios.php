<?php
/**
 * Frontend: Mis Anuncios de Marketplace
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$usuario_id = get_current_user_id();

if (!$usuario_id) {
    echo '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">';
    echo '<p class="text-yellow-800">' . esc_html__('Debes iniciar sesión para ver tus anuncios.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . esc_html__('Iniciar Sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

// Query de anuncios del usuario actual
$query_args = [
    'post_type'      => 'marketplace_item',
    'post_status'    => ['publish', 'pending', 'draft'],
    'author'         => $usuario_id,
    'posts_per_page' => 20,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$query = new WP_Query($query_args);
$mis_anuncios = [];

// Preparar consulta de comunidades
global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        // Obtener comunidad asociada si existe
        $comunidad_id = get_post_meta($post_id, '_marketplace_comunidad_id', true);
        $comunidad_nombre = null;
        if ($comunidad_id) {
            $comunidad_nombre = $wpdb->get_var($wpdb->prepare(
                "SELECT nombre FROM {$tabla_comunidades} WHERE id = %d",
                absint($comunidad_id)
            ));
        }

        $mis_anuncios[] = [
            'id'              => $post_id,
            'titulo'          => get_the_title(),
            'descripcion'     => wp_trim_words(get_the_excerpt(), 15),
            'precio'          => get_post_meta($post_id, '_marketplace_precio', true) ?: get_post_meta($post_id, '_precio', true) ?: '0',
            'imagen'          => get_the_post_thumbnail_url($post_id, 'medium') ?: '',
            'url'             => home_url('/mi-portal/marketplace/detalle/?anuncio_id=' . $post_id),
            'edit_url'        => get_edit_post_link($post_id),
            'estado'          => get_post_status($post_id),
            'fecha'           => get_the_date(),
            'vistas'          => get_post_meta($post_id, '_marketplace_vistas', true) ?: get_post_meta($post_id, '_vistas', true) ?: 0,
            'comunidad_id'    => $comunidad_id,
            'comunidad_nombre'=> $comunidad_nombre,
        ];
    }
    wp_reset_postdata();
}

$total_anuncios = $query->found_posts;
?>

<div class="flavor-frontend flavor-marketplace-mis-anuncios">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-2xl p-6 mb-6 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-2xl font-bold mb-1"><?php echo esc_html__('📋 Mis Anuncios', 'flavor-chat-ia'); ?></h2>
                <p class="text-indigo-100"><?php echo esc_html__('Gestiona tus productos publicados', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_anuncios); ?> <?php echo esc_html__('anuncios', 'flavor-chat-ia'); ?>
                </span>
                <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>"
                   class="bg-white text-indigo-600 px-5 py-2 rounded-xl font-semibold hover:bg-indigo-50 transition-all shadow-md">
                    <?php echo esc_html__('+ Nuevo Anuncio', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($mis_anuncios)): ?>
    <!-- Estado vacío -->
    <div class="text-center py-12 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">📦</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No tienes anuncios publicados', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Publica tu primer producto y empieza a vender!', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>"
           class="inline-block bg-indigo-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-indigo-600 transition-colors">
            <?php echo esc_html__('Publicar Anuncio', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else: ?>
    <!-- Lista de anuncios -->
    <div class="space-y-4">
        <?php foreach ($mis_anuncios as $anuncio): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
            <div class="flex gap-4">
                <!-- Imagen -->
                <div class="w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                    <?php if (!empty($anuncio['imagen'])): ?>
                    <img src="<?php echo esc_url($anuncio['imagen']); ?>" alt="<?php echo esc_attr($anuncio['titulo']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-3xl">📷</div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-semibold text-gray-800 truncate">
                            <a href="<?php echo esc_url($anuncio['url']); ?>" class="hover:text-indigo-600">
                                <?php echo esc_html($anuncio['titulo']); ?>
                            </a>
                        </h3>
                        <span class="text-lg font-bold text-green-600 whitespace-nowrap">
                            <?php echo esc_html($anuncio['precio']); ?> €
                        </span>
                    </div>

                    <p class="text-sm text-gray-500 mb-3 line-clamp-1"><?php echo esc_html($anuncio['descripcion']); ?></p>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <!-- Estado -->
                            <?php
                            $estado_class = 'bg-gray-100 text-gray-600';
                            $estado_label = __('Borrador', 'flavor-chat-ia');
                            if ($anuncio['estado'] === 'publish') {
                                $estado_class = 'bg-green-100 text-green-700';
                                $estado_label = __('Publicado', 'flavor-chat-ia');
                            } elseif ($anuncio['estado'] === 'pending') {
                                $estado_class = 'bg-yellow-100 text-yellow-700';
                                $estado_label = __('Pendiente', 'flavor-chat-ia');
                            }
                            ?>
                            <span class="px-2 py-1 rounded-full <?php echo esc_attr($estado_class); ?>">
                                <?php echo esc_html($estado_label); ?>
                            </span>
                            <?php if (!empty($anuncio['comunidad_nombre'])): ?>
                            <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $anuncio['comunidad_id'] . '/')); ?>"
                               class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors">
                                👥 <?php echo esc_html($anuncio['comunidad_nombre']); ?>
                            </a>
                            <?php endif; ?>
                            <span>📅 <?php echo esc_html($anuncio['fecha']); ?></span>
                            <span>👁 <?php echo esc_html($anuncio['vistas']); ?> <?php echo esc_html__('vistas', 'flavor-chat-ia'); ?></span>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center gap-2">
                            <a href="<?php echo esc_url($anuncio['url']); ?>"
                               class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                                <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                            </a>
                            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_item_url('marketplace', $anuncio['id'], 'editar')); ?>"
                               class="px-3 py-1 text-sm bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-colors">
                                <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                            </a>
                            <button type="button"
                                    class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                                    onclick="if(confirm('<?php echo esc_js(__('¿Eliminar este anuncio?', 'flavor-chat-ia')); ?>')) { flavorMarketplace.eliminarAnuncio(<?php echo esc_js($anuncio['id']); ?>); }">
                                <?php echo esc_html__('Eliminar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
