<?php
/**
 * Template: Feed de Publicaciones
 *
 * @package FlavorPlatform
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Feed Comunitario';
$descripcion = $args['descripcion'] ?? 'Conecta con tu comunidad y comparte experiencias';
$mostrar_sidebar = $args['mostrar_sidebar'] ?? true;
$limite_publicaciones = $args['limite_publicaciones'] ?? 10;

// Datos de ejemplo de publicaciones
$publicaciones_feed = [
    [
        'id' => 1,
        'autor' => 'Rosa Martínez',
        'avatar' => 'https://i.pravatar.cc/150?img=12',
        'tiempo' => 'Hace 2 horas',
        'contenido' => 'Acabo de terminar el taller de compostaje. Muy motivada para empezar mi compostador en casa. Gracias a todos por los consejos!',
        'imagen' => 'https://picsum.photos/seed/compostaje/600/400',
        'likes' => 42,
        'comentarios' => 15,
        'comparticiones' => 3,
        'tipo' => 'logro',
        'verificado' => false,
    ],
    [
        'id' => 2,
        'autor' => 'Ayuntamiento Local',
        'avatar' => 'https://i.pravatar.cc/150?img=67',
        'tiempo' => 'Hace 4 horas',
        'contenido' => 'Convocatoria abierta para nuevos voluntarios en los huertos comunitarios. Interesados contactar a través del formulario en nuestra web.',
        'imagen' => null,
        'likes' => 78,
        'comentarios' => 31,
        'comparticiones' => 12,
        'tipo' => 'oportunidad',
        'verificado' => true,
    ],
    [
        'id' => 3,
        'autor' => 'Juan Carlos Rivera',
        'avatar' => 'https://i.pravatar.cc/150?img=45',
        'tiempo' => 'Hace 6 horas',
        'contenido' => 'Quien se anima a organizar una salida en bicicleta el próximo domingo? Ruta fácil por los alrededores del parque.',
        'imagen' => 'https://picsum.photos/seed/bicicletas/600/400',
        'likes' => 56,
        'comentarios' => 23,
        'comparticiones' => 8,
        'tipo' => 'evento',
        'verificado' => false,
    ],
    [
        'id' => 4,
        'autor' => 'Eco Mercado Barrial',
        'avatar' => 'https://i.pravatar.cc/150?img=55',
        'tiempo' => 'Hace 8 horas',
        'contenido' => 'Nuevos productos locales disponibles esta semana: mermeladas artesanales, verduras de km0 y productos de granja ecológica.',
        'imagen' => 'https://picsum.photos/seed/mercado/600/400',
        'likes' => 94,
        'comentarios' => 28,
        'comparticiones' => 15,
        'tipo' => 'negocio',
        'verificado' => true,
    ],
    [
        'id' => 5,
        'autor' => 'Marta López Sánchez',
        'avatar' => 'https://i.pravatar.cc/150?img=33',
        'tiempo' => 'Hace 1 día',
        'contenido' => 'Buscamos casa para adopción. Adorable perrita de 3 años, cariñosa y dócil. Requiere hogar tranquilo. Interesados mandar privado.',
        'imagen' => 'https://picsum.photos/seed/perro/600/400',
        'likes' => 112,
        'comentarios' => 45,
        'comparticiones' => 22,
        'tipo' => 'busqueda',
        'verificado' => false,
    ],
];

// Filtrar por límite
$publicaciones_mostradas = array_slice($publicaciones_feed, 0, $limite_publicaciones);
?>

<section class="flavor-component py-16 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    <?php echo esc_html($descripcion); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 <?php echo $mostrar_sidebar ? 'lg:grid-cols-3' : ''; ?> gap-8">
                <!-- Feed Principal -->
                <div class="<?php echo $mostrar_sidebar ? 'lg:col-span-2' : ''; ?> space-y-6">
                    <!-- Crear Publicacion -->
                    <div class="flavor-feed-composer bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                TU
                            </div>
                            <div class="flex-1 w-full">
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
                                    <textarea
                                        placeholder="<?php echo esc_attr__('Comparte tu experiencia con la comunidad...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                        class="w-full bg-transparent resize-none focus:outline-none text-gray-900 placeholder-gray-500"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center gap-2">
                                        <button class="p-2 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="<?php echo esc_attr__('Agregar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <button class="p-2 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="<?php echo esc_attr__('Agregar ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            </svg>
                                        </button>
                                        <button class="p-2 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="<?php echo esc_attr__('Agregar etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button class="px-6 py-2 rounded-lg text-white font-semibold transition-all hover:shadow-lg" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                                        <?php echo esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Publicaciones -->
                    <?php foreach ($publicaciones_mostradas as $publicacion): ?>
                        <article class="flavor-feed-post bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
                            <!-- Header de la publicacion -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start gap-4 flex-1">
                                    <img src="<?php echo esc_url($publicacion['avatar']); ?>" alt="<?php echo esc_attr($publicacion['autor']); ?>" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <a href="#" class="font-bold text-gray-900 hover:text-blue-600 transition-colors">
                                                <?php echo esc_html($publicacion['autor']); ?>
                                            </a>
                                            <?php if ($publicacion['verificado']): ?>
                                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" title="<?php echo esc_attr__('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm text-gray-500"><?php echo esc_html($publicacion['tiempo']); ?></span>
                                    </div>
                                </div>
                                <button class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors" title="<?php echo esc_attr__('Más opciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.5 1.5H9.5V3h1V1.5zM10.5 17v1.5H9.5V17h1zM17 10.5V9.5H15.5v1H17zM3 10.5V9.5H1.5v1H3z"/>
                                        <path d="M5.5 5.5a1 1 0 11-2 0 1 1 0 012 0zM16.5 5.5a1 1 0 11-2 0 1 1 0 012 0zM5.5 16.5a1 1 0 11-2 0 1 1 0 012 0zM16.5 16.5a1 1 0 11-2 0 1 1 0 012 0z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Badge de tipo -->
                            <?php
                            $tipo_badge_config = [
                                'logro' => ['color' => 'green', 'icono' => '⭐', 'texto' => 'Logro'],
                                'oportunidad' => ['color' => 'purple', 'icono' => '🎯', 'texto' => 'Oportunidad'],
                                'evento' => ['color' => 'orange', 'icono' => '📅', 'texto' => 'Evento'],
                                'negocio' => ['color' => 'pink', 'icono' => '💼', 'texto' => 'Negocio'],
                                'busqueda' => ['color' => 'red', 'icono' => '🔍', 'texto' => 'Búsqueda'],
                            ];
                            $config = $tipo_badge_config[$publicacion['tipo']] ?? $tipo_badge_config['logro'];
                            ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold mb-3" style="background-color: var(--color-<?php echo esc_attr($config['color']); ?>-100); color: var(--color-<?php echo esc_attr($config['color']); ?>-700);">
                                <span><?php echo esc_html($config['icono']); ?></span>
                                <?php echo esc_html($config['texto']); ?>
                            </span>

                            <!-- Contenido -->
                            <p class="text-gray-800 text-base mb-4 leading-relaxed">
                                <?php echo wp_kses_post($publicacion['contenido']); ?>
                            </p>

                            <!-- Imagen si existe -->
                            <?php if ($publicacion['imagen']): ?>
                                <img src="<?php echo esc_url($publicacion['imagen']); ?>" alt="" class="w-full rounded-xl mb-4 object-cover max-h-80">
                            <?php endif; ?>

                            <!-- Acciones -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100 text-sm text-gray-600">
                                <div class="flex items-center gap-6">
                                    <button class="flavor-like-btn flex items-center gap-2 hover:text-red-500 transition-colors group" title="<?php echo esc_attr__('Me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <svg class="w-5 h-5 group-hover:fill-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span class="font-medium"><?php echo esc_html($publicacion['likes']); ?></span>
                                    </button>
                                    <button class="flavor-comment-btn flex items-center gap-2 hover:text-blue-500 transition-colors" title="<?php echo esc_attr__('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span class="font-medium"><?php echo esc_html($publicacion['comentarios']); ?></span>
                                    </button>
                                    <button class="flavor-share-btn flex items-center gap-2 hover:text-green-500 transition-colors" title="<?php echo esc_attr__('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                        </svg>
                                        <span class="font-medium"><?php echo esc_html($publicacion['comparticiones']); ?></span>
                                    </button>
                                </div>
                                <button class="hover:text-blue-500 transition-colors" title="<?php echo esc_attr__('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h6a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <!-- Botón cargar más -->
                    <div class="text-center pt-6">
                        <button class="px-8 py-3 rounded-lg font-semibold transition-all hover:shadow-lg" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white;">
                            <?php echo esc_html__('Cargar más publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>

                <!-- Sidebar -->
                <?php if ($mostrar_sidebar): ?>
                    <div class="space-y-6 lg:sticky lg:top-8 lg:h-fit">
                        <!-- Tarjeta de bienvenida -->
                        <div class="flavor-welcome-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <div class="text-center mb-4">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">
                                    <?php echo esc_html__('Bienvenido a la Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo esc_html__('Conecta, comparte y aprende con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Estadísticas rápidas -->
                        <div class="flavor-stats-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">
                                <?php echo esc_html__('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                    <span class="text-gray-600"><?php echo esc_html__('Miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="font-bold text-blue-600">1,234</span>
                                </div>
                                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                    <span class="text-gray-600"><?php echo esc_html__('Publicaciones hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="font-bold text-green-600">48</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600"><?php echo esc_html__('Interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="font-bold text-purple-600">892</span>
                                </div>
                            </div>
                        </div>

                        <!-- Temas populares -->
                        <div class="flavor-topics-card bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">
                                <?php echo esc_html__('Temas Populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="space-y-3">
                                <a href="#" class="block p-3 rounded-lg hover:bg-blue-50 transition-colors">
                                    <span class="text-blue-600 font-semibold text-sm">#ComunidadSolidaria</span>
                                </a>
                                <a href="#" class="block p-3 rounded-lg hover:bg-blue-50 transition-colors">
                                    <span class="text-blue-600 font-semibold text-sm">#VecindarioActivo</span>
                                </a>
                                <a href="#" class="block p-3 rounded-lg hover:bg-blue-50 transition-colors">
                                    <span class="text-blue-600 font-semibold text-sm">#DesdeMiBarrio</span>
                                </a>
                                <a href="#" class="block p-3 rounded-lg hover:bg-blue-50 transition-colors">
                                    <span class="text-blue-600 font-semibold text-sm">#EconomiaCircular</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
