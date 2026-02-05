<?php
/**
 * Grupos de Consumo - Single Product Template
 */

if (!defined('ABSPATH')) exit;

get_header();
while (have_posts()) : the_post();
    $precio = get_post_meta(get_the_ID(), '_precio', true);
    $unidad = get_post_meta(get_the_ID(), '_unidad', true);
    $disponibilidad = get_post_meta(get_the_ID(), '_disponibilidad', true);
    $productor = get_post_meta(get_the_ID(), '_productor', true);
    $origen = get_post_meta(get_the_ID(), '_origen', true);
    $certificacion = get_post_meta(get_the_ID(), '_certificacion', true);
?>

<div class="flavor-container py-8">
    <nav class="flex mb-6 text-sm" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-2">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-600 hover:text-primary">Inicio</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li><a href="<?php echo esc_url(get_post_type_archive_link('grupo_consumo')); ?>" class="text-gray-600 hover:text-primary">Grupos de Consumo</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium line-clamp-1" aria-current="page"><?php the_title(); ?></li>
        </ol>
    </nav>

    <div class="mb-6">
        <a href="<?php echo esc_url(get_post_type_archive_link('grupo_consumo')); ?>" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Volver al catálogo
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <?php if (has_post_thumbnail()) : ?>
                <div class="aspect-square rounded-xl overflow-hidden shadow-lg mb-4">
                    <?php the_post_thumbnail('large', array('class' => 'w-full h-full object-cover')); ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php
            $categorias = get_the_terms(get_the_ID(), 'categoria_producto');
            if ($categorias && !is_wp_error($categorias)) :
            ?>
                <span class="inline-block px-4 py-2 text-sm font-semibold text-green-700 bg-green-100 rounded-full mb-4">
                    <?php echo esc_html($categorias[0]->name); ?>
                </span>
            <?php endif; ?>

            <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php the_title(); ?></h1>

            <?php if ($productor) : ?>
                <p class="text-lg text-gray-600 mb-4">Productor: <strong><?php echo esc_html($productor); ?></strong></p>
            <?php endif; ?>

            <?php if ($precio) : ?>
                <div class="text-5xl font-bold text-primary mb-6">
                    <?php echo esc_html($precio); ?>€
                    <?php if ($unidad) : ?>
                        <span class="text-xl text-gray-600 font-normal">/<?php echo esc_html($unidad); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mb-6">
                <?php if ($disponibilidad === 'disponible') : ?>
                    <span class="inline-flex items-center gap-2 text-green-700 bg-green-100 px-4 py-2 rounded-lg font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Disponible
                    </span>
                <?php else : ?>
                    <span class="inline-flex items-center gap-2 text-red-700 bg-red-100 px-4 py-2 rounded-lg font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Agotado
                    </span>
                <?php endif; ?>
            </div>

            <div class="prose max-w-none mb-6">
                <?php the_content(); ?>
            </div>

            <?php if ($origen || $certificacion) : ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold mb-3">Información adicional</h3>
                    <dl class="space-y-3">
                        <?php if ($origen) : ?>
                            <div><dt class="text-sm font-semibold text-gray-700">Origen:</dt><dd class="text-gray-600"><?php echo esc_html($origen); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($certificacion) : ?>
                            <div><dt class="text-sm font-semibold text-gray-700">Certificación:</dt><dd class="text-gray-600"><?php echo esc_html($certificacion); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <?php if (is_user_logged_in() && $disponibilidad === 'disponible') : ?>
                <button class="w-full px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-bold text-lg">
                    Solicitar Pedido
                </button>
            <?php elseif (!is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="block w-full text-center px-6 py-4 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-bold text-lg">
                    Inicia sesión para pedir
                </a>
            <?php endif; ?>

            <div class="mt-6 flex gap-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center">Compartir</a>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js(get_permalink()); ?>'); alert('Enlace copiado');" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Copiar enlace</button>
            </div>
        </div>
    </div>
</div>

<?php endwhile; get_footer(); ?>
