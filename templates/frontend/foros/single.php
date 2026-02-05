<?php
/**
 * Frontend: Single Tema de Foro
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$tema = $tema ?? [];
$titulo_tema = $tema['titulo'] ?? 'Tema de discusion';
$contenido_tema = $tema['contenido'] ?? '';
$autor_tema = $tema['autor'] ?? 'Usuario';
$autor_avatar = $tema['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=1';
$fecha_creacion = $tema['fecha'] ?? 'hace 3 dias';
$categoria_tema = $tema['categoria'] ?? 'General';
$respuestas_tema = $tema['respuestas'] ?? [];
$temas_relacionados = $tema['relacionados'] ?? [];
?>

<div class="flavor-single foros">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="/" class="hover:text-indigo-600">Inicio</a>
                <span>/</span>
                <a href="/foros/" class="hover:text-indigo-600">Foros</a>
                <span>/</span>
                <a href="/foros/?categoria=<?php echo esc_attr(sanitize_title($categoria_tema)); ?>" class="hover:text-indigo-600"><?php echo esc_html($categoria_tema); ?></a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($titulo_tema); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Tema original -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                            <?php echo esc_html($categoria_tema); ?>
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6"><?php echo esc_html($titulo_tema); ?></h1>

                    <!-- Autor del tema -->
                    <div class="flex items-center gap-4 pb-4 mb-4 border-b border-gray-100">
                        <img src="<?php echo esc_url($autor_avatar); ?>"
                             alt="<?php echo esc_attr($autor_tema); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor_tema); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($fecha_creacion); ?></p>
                        </div>
                    </div>

                    <!-- Contenido del post -->
                    <div class="prose max-w-none text-gray-700 mb-6">
                        <?php echo wp_kses_post($contenido_tema ?: '<p>Este es el contenido del tema de discusion. Aqui el autor expone su pregunta, idea o propuesta para que la comunidad pueda participar y aportar sus opiniones.</p><p>Se pueden incluir detalles adicionales, enlaces y cualquier informacion relevante para enriquecer la conversacion.</p>'); ?>
                    </div>

                    <!-- Acciones del post -->
                    <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                        <button class="flex items-center gap-2 text-gray-500 hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                            <span class="text-sm">12 Me gusta</span>
                        </button>
                        <button class="flex items-center gap-2 text-gray-500 hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            <span class="text-sm">Guardar</span>
                        </button>
                        <button class="flex items-center gap-2 text-gray-500 hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span class="text-sm">Compartir</span>
                        </button>
                    </div>
                </div>

                <!-- Respuestas -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">3 Respuestas</h2>

                    <div class="space-y-6">
                        <!-- Respuesta 1 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=12" alt="Maria Lopez" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Maria Lopez</span>
                                    <span class="text-sm text-gray-500">hace 2 dias</span>
                                </div>
                                <p class="text-gray-700 mb-3">Estoy totalmente de acuerdo con tu planteamiento. Creo que seria interesante organizar una reunion presencial para debatirlo con mas detalle.</p>
                                <div class="flex items-center gap-4">
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">5 Me gusta</button>
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Responder</button>
                                </div>

                                <!-- Respuesta anidada -->
                                <div class="flex gap-4 mt-4 pl-4 border-l-2 border-indigo-100">
                                    <img src="https://i.pravatar.cc/150?img=33" alt="Carlos Ruiz" class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-gray-900 text-sm">Carlos Ruiz</span>
                                            <span class="text-xs text-gray-500">hace 1 dia</span>
                                        </div>
                                        <p class="text-gray-700 text-sm">Buena idea Maria, yo me apunto a la reunion. Podemos usar el centro civico.</p>
                                        <button class="text-xs text-gray-500 hover:text-indigo-600 transition-colors mt-1">2 Me gusta</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Respuesta 2 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=22" alt="Pedro Garcia" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Pedro Garcia</span>
                                    <span class="text-sm text-gray-500">hace 1 dia</span>
                                </div>
                                <p class="text-gray-700 mb-3">Comparto un enlace que puede ser util para este tema. He estado investigando y hay experiencias similares en otras comunidades que han funcionado bien.</p>
                                <div class="flex items-center gap-4">
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">8 Me gusta</button>
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Responder</button>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Respuesta 3 -->
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=45" alt="Ana Martinez" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Ana Martinez</span>
                                    <span class="text-sm text-gray-500">hace 5 horas</span>
                                </div>
                                <p class="text-gray-700 mb-3">Gracias por abrir este tema. Es algo que nos afecta a todos y creo que entre todos podemos encontrar una buena solucion.</p>
                                <div class="flex items-center gap-4">
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">3 Me gusta</button>
                                    <button class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">Responder</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de respuesta -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Tu respuesta</h3>
                    <textarea rows="4" placeholder="Escribe tu respuesta..."
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    <div class="flex justify-end mt-4">
                        <button class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, #6366f1 0%, #9333ea 100%);">
                            Publicar Respuesta
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Info del tema -->
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4 mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Informacion del tema</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Respuestas</span>
                            <span class="font-semibold">3</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Vistas</span>
                            <span class="font-semibold">156</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Creado</span>
                            <span class="font-semibold text-sm"><?php echo esc_html($fecha_creacion); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50">
                            <span class="text-gray-600 text-sm">Categoria</span>
                            <span class="font-semibold text-indigo-600"><?php echo esc_html($categoria_tema); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Estadisticas del autor -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Sobre el autor</h3>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="<?php echo esc_url($autor_avatar); ?>" alt="<?php echo esc_attr($autor_tema); ?>" class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor_tema); ?></p>
                            <p class="text-sm text-gray-500">Miembro desde 2023</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-indigo-600">28</span>
                            <span class="text-xs text-gray-500">Temas</span>
                        </div>
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-indigo-600">142</span>
                            <span class="text-xs text-gray-500">Respuestas</span>
                        </div>
                        <div class="p-2 rounded-lg bg-gray-50">
                            <span class="block font-bold text-indigo-600">89</span>
                            <span class="text-xs text-gray-500">Me gusta</span>
                        </div>
                    </div>
                </div>

                <!-- Temas relacionados -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Temas relacionados</h3>
                    <div class="space-y-3">
                        <a href="/foros/tema/?id=1" class="block p-3 rounded-xl hover:bg-indigo-50 transition-colors">
                            <p class="font-medium text-gray-900 text-sm mb-1">Propuesta de mejora del parque central</p>
                            <p class="text-xs text-gray-500">12 respuestas - hace 1 dia</p>
                        </a>
                        <a href="/foros/tema/?id=2" class="block p-3 rounded-xl hover:bg-indigo-50 transition-colors">
                            <p class="font-medium text-gray-900 text-sm mb-1">Organizacion de actividades verano</p>
                            <p class="text-xs text-gray-500">8 respuestas - hace 3 dias</p>
                        </a>
                        <a href="/foros/tema/?id=3" class="block p-3 rounded-xl hover:bg-indigo-50 transition-colors">
                            <p class="font-medium text-gray-900 text-sm mb-1">Nuevo grupo de senderismo</p>
                            <p class="text-xs text-gray-500">5 respuestas - hace 5 dias</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
