<?php
/**
 * Template: Feed de Actividad Social
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Actividad del Barrio';
$descripcion = $descripcion ?? 'Mantente conectado con lo que pasa en tu comunidad';

$publicaciones = [
    ['autor' => 'Maria Garcia', 'avatar' => 'https://i.pravatar.cc/150?img=32', 'tiempo' => 'Hace 15 min', 'contenido' => 'Hoy estreno la cosecha de tomates de mi parcela en el huerto. Quien quiera, que pase por casa! 🍅', 'likes' => 24, 'comentarios' => 8, 'tipo' => 'texto', 'imagen' => null],
    ['autor' => 'Asociacion de Vecinos', 'avatar' => 'https://i.pravatar.cc/150?img=70', 'tiempo' => 'Hace 2 horas', 'contenido' => 'Recordad que manana es la asamblea de vecinos a las 19:00 en el Centro Civico. Temas importantes a tratar sobre el nuevo parking.', 'likes' => 45, 'comentarios' => 12, 'tipo' => 'evento', 'imagen' => null],
    ['autor' => 'Pedro Lopez', 'avatar' => 'https://i.pravatar.cc/150?img=53', 'tiempo' => 'Hace 4 horas', 'contenido' => 'Alguien ha visto un gato atigrado por la zona de la Plaza? Se ha perdido desde ayer. Se llama Michi. 🐱', 'likes' => 67, 'comentarios' => 23, 'tipo' => 'busqueda', 'imagen' => 'https://picsum.photos/seed/gato/400/300'],
    ['autor' => 'Panaderia La Espiga', 'avatar' => 'https://i.pravatar.cc/150?img=60', 'tiempo' => 'Hace 6 horas', 'contenido' => 'Esta semana 20% de descuento en pan de masa madre para vecinos del barrio. Solo con el codigo VECINO20', 'likes' => 89, 'comentarios' => 15, 'tipo' => 'promocion', 'imagen' => null],
];

$tendencias = [
    '#FiestasDelBarrio',
    '#HuertoComunitario',
    '#ComercioLocal',
    '#LimpiezaParque',
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-sky-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <?php echo esc_html__('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Feed principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Crear publicacion -->
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-400 to-blue-500 flex items-center justify-center text-white font-bold">
                            <?php echo esc_html__('TU', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                        <div class="flex-1">
                            <textarea placeholder="<?php echo esc_attr__('Que esta pasando en tu barrio?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 resize-none" rows="2"></textarea>
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center gap-2">
                                    <button class="p-2 rounded-lg text-gray-500 hover:bg-sky-50 hover:text-sky-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 rounded-lg text-gray-500 hover:bg-sky-50 hover:text-sky-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <button class="px-4 py-2 rounded-xl text-white font-semibold transition-all hover:scale-105" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                                    <?php echo esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Publicaciones -->
                <?php foreach ($publicaciones as $post): ?>
                    <article class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                        <div class="flex items-start gap-4">
                            <img src="<?php echo esc_url($post['avatar']); ?>" alt="<?php echo esc_attr($post['autor']); ?>" class="w-12 h-12 rounded-full object-cover">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900"><?php echo esc_html($post['autor']); ?></span>
                                    <?php if ($post['tipo'] === 'evento'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700"><?php echo esc_html__('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php elseif ($post['tipo'] === 'busqueda'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700"><?php echo esc_html__('Busqueda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php elseif ($post['tipo'] === 'promocion'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700"><?php echo esc_html__('Promocion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm text-gray-500"><?php echo esc_html($post['tiempo']); ?></span>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-700"><?php echo esc_html($post['contenido']); ?></p>
                        <?php if ($post['imagen']): ?>
                            <img src="<?php echo esc_url($post['imagen']); ?>" alt="" class="mt-4 rounded-xl w-full object-cover max-h-80">
                        <?php endif; ?>
                        <div class="flex items-center gap-6 mt-4 pt-4 border-t border-gray-100">
                            <button class="flex items-center gap-2 text-gray-500 hover:text-red-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="text-sm font-medium"><?php echo esc_html($post['likes']); ?></span>
                            </button>
                            <button class="flex items-center gap-2 text-gray-500 hover:text-sky-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span class="text-sm font-medium"><?php echo esc_html($post['comentarios']); ?></span>
                            </button>
                            <button class="flex items-center gap-2 text-gray-500 hover:text-sky-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                <span class="text-sm font-medium"><?php echo esc_html__('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Tendencias -->
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4"><?php echo esc_html__('Tendencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="space-y-3">
                        <?php foreach ($tendencias as $tendencia): ?>
                            <a href="#<?php echo sanitize_title($tendencia); ?>" class="block p-3 rounded-xl hover:bg-sky-50 transition-colors">
                                <span class="text-sky-600 font-semibold"><?php echo esc_html($tendencia); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sugerencias -->
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4"><?php echo esc_html__('Conecta con vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="space-y-4">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <div class="flex items-center gap-3">
                                <img src="https://i.pravatar.cc/150?img=<?php echo $i + 10; ?>" alt="" class="w-10 h-10 rounded-full object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">Vecino <?php echo $i; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo esc_html__('5 amigos en comun', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                                <button class="px-3 py-1 rounded-full text-xs font-semibold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-colors">
                                    <?php echo esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
