<?php
/**
 * Frontend: Single Publicacion Red Social
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$publicacion = $publicacion ?? [];
$titulo_publicacion = $publicacion['titulo'] ?? 'Publicacion';
$contenido_publicacion = $publicacion['contenido'] ?? '';
$autor_nombre = $publicacion['autor'] ?? 'Usuario';
$autor_avatar = $publicacion['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=1';
$fecha_publicacion = $publicacion['fecha'] ?? 'hace 2 horas';
$tiene_imagen = $publicacion['tiene_imagen'] ?? false;
$comentarios_publicacion = $publicacion['comentarios'] ?? [];
?>

<div class="flavor-single red-social">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-pink-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-pink-600">Red Social</a>
                <span>/</span>
                <span class="text-gray-900 font-medium">Publicacion</span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Publicacion -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <!-- Autor -->
                    <div class="flex items-center gap-4 mb-6">
                        <img src="<?php echo esc_url($autor_avatar); ?>"
                             alt="<?php echo esc_attr($autor_nombre); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor_nombre); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($fecha_publicacion); ?></p>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="prose max-w-none text-gray-700 mb-6">
                        <?php echo wp_kses_post($contenido_publicacion ?: '<p>Esta es una publicacion de ejemplo en la red social de la comunidad. Aqui los miembros comparten experiencias, noticias y eventos relevantes para todos.</p><p>La participacion activa de los vecinos fortalece los lazos comunitarios y mejora la convivencia.</p>'); ?>
                    </div>

                    <!-- Imagen -->
                    <div class="aspect-video rounded-xl bg-gradient-to-br from-pink-100 to-rose-100 flex items-center justify-center mb-6 overflow-hidden">
                        <svg class="w-16 h-16 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <!-- Interacciones -->
                    <div class="flex items-center gap-6 pt-4 border-t border-gray-100">
                        <button class="flex items-center gap-2 text-gray-500 hover:text-pink-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="font-medium">24 Me gusta</span>
                        </button>
                        <button class="flex items-center gap-2 text-gray-500 hover:text-pink-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span class="font-medium">Compartir</span>
                        </button>
                    </div>
                </div>

                <!-- Comentarios -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">3 Comentarios</h2>

                    <div class="space-y-6">
                        <!-- Comentario 1 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=15" alt="Laura Fernandez" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Laura Fernandez</span>
                                    <span class="text-sm text-gray-500">hace 1 hora</span>
                                </div>
                                <p class="text-gray-700 mb-2">Que buena iniciativa! Me encanta ver como la comunidad se organiza para mejorar el barrio.</p>
                                <button class="text-sm text-gray-500 hover:text-pink-600 transition-colors">6 Me gusta</button>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Comentario 2 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=28" alt="Javier Moreno" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Javier Moreno</span>
                                    <span class="text-sm text-gray-500">hace 45 min</span>
                                </div>
                                <p class="text-gray-700 mb-2">Totalmente de acuerdo. Cuenten conmigo para lo que haga falta. Podemos coordinarnos por aqui mismo.</p>
                                <button class="text-sm text-gray-500 hover:text-pink-600 transition-colors">3 Me gusta</button>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Comentario 3 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=42" alt="Sofia Navarro" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Sofia Navarro</span>
                                    <span class="text-sm text-gray-500">hace 20 min</span>
                                </div>
                                <p class="text-gray-700 mb-2">Comparto! He visto propuestas parecidas en otros barrios y funcionan genial.</p>
                                <button class="text-sm text-gray-500 hover:text-pink-600 transition-colors">1 Me gusta</button>
                            </div>
                        </div>
                    </div>

                    <!-- Escribir comentario -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="flex gap-3">
                            <img src="https://i.pravatar.cc/150?img=5" alt="Tu" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <textarea rows="2" placeholder="Escribe un comentario..."
                                          class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none text-sm"></textarea>
                                <div class="flex justify-end mt-2">
                                    <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold transition-all hover:scale-105"
                                            style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
                                        Comentar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Perfil del autor -->
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4 mb-6">
                    <div class="text-center mb-4">
                        <img src="<?php echo esc_url($autor_avatar); ?>" alt="<?php echo esc_attr($autor_nombre); ?>"
                             class="w-20 h-20 rounded-full object-cover mx-auto mb-3">
                        <h3 class="font-bold text-gray-900 text-lg"><?php echo esc_html($autor_nombre); ?></h3>
                        <p class="text-sm text-gray-500">Miembro activo</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center mb-4">
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-pink-600">45</span>
                            <span class="text-xs text-gray-500">Posts</span>
                        </div>
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-pink-600">128</span>
                            <span class="text-xs text-gray-500">Seguidores</span>
                        </div>
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-pink-600">67</span>
                            <span class="text-xs text-gray-500">Siguiendo</span>
                        </div>
                    </div>
                    <button class="w-full py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
                        Seguir
                    </button>
                </div>

                <!-- Conexiones en comun -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Conexiones en comun</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=20" alt="" class="w-8 h-8 rounded-full object-cover">
                            <span class="text-sm text-gray-700">Elena Rodriguez</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=31" alt="" class="w-8 h-8 rounded-full object-cover">
                            <span class="text-sm text-gray-700">Miguel Santos</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=48" alt="" class="w-8 h-8 rounded-full object-cover">
                            <span class="text-sm text-gray-700">Carmen Diaz</span>
                        </div>
                    </div>
                </div>

                <!-- Temas tendencia -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Tendencias</h3>
                    <div class="space-y-3">
                        <a href="#" class="block p-3 rounded-xl hover:bg-pink-50 transition-colors">
                            <p class="font-medium text-pink-600 text-sm">#FiestaDelBarrio</p>
                            <p class="text-xs text-gray-500">24 publicaciones</p>
                        </a>
                        <a href="#" class="block p-3 rounded-xl hover:bg-pink-50 transition-colors">
                            <p class="font-medium text-pink-600 text-sm">#MejoraComunidad</p>
                            <p class="text-xs text-gray-500">18 publicaciones</p>
                        </a>
                        <a href="#" class="block p-3 rounded-xl hover:bg-pink-50 transition-colors">
                            <p class="font-medium text-pink-600 text-sm">#DeporteVecinal</p>
                            <p class="text-xs text-gray-500">11 publicaciones</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
