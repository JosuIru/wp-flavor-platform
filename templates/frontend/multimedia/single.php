<?php
/**
 * Frontend: Single Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$medio = $medio ?? [];
$titulo_medio = $medio['titulo'] ?? 'Contenido multimedia';
$descripcion_medio = $medio['descripcion'] ?? '';
$tipo_medio = $medio['tipo'] ?? 'video';
$autor_medio = $medio['autor'] ?? 'Autor';
$autor_avatar = $medio['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=1';
$fecha_medio = $medio['fecha'] ?? 'hace 3 dias';
$vistas_medio = $medio['vistas'] ?? 0;
$duracion_medio = $medio['duracion'] ?? '12:34';
$etiquetas_medio = $medio['etiquetas'] ?? [];
$comentarios_medio = $medio['comentarios'] ?? [];
$medios_relacionados = $medio['relacionados'] ?? [];
?>

<div class="flavor-single multimedia">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-indigo-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-indigo-600">Multimedia</a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($titulo_medio); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Reproductor placeholder -->
                <div class="relative aspect-video rounded-2xl overflow-hidden mb-6 bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center">
                    <?php if ($tipo_medio === 'video'): ?>
                        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm cursor-pointer hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <span class="absolute bottom-4 right-4 px-3 py-1 rounded-lg text-sm font-bold bg-black/70 text-white">
                            <?php echo esc_html($duracion_medio); ?>
                        </span>
                    <?php elseif ($tipo_medio === 'foto'): ?>
                        <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    <?php else: ?>
                        <div class="text-center">
                            <svg class="w-16 h-16 text-white/50 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                            <div class="w-64 h-2 bg-white/20 rounded-full mx-auto">
                                <div class="w-1/3 h-2 bg-white/60 rounded-full"></div>
                            </div>
                            <span class="text-white/70 text-sm mt-2 block"><?php echo esc_html($duracion_medio); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info del medio -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                            <?php echo esc_html(ucfirst($tipo_medio)); ?>
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo_medio); ?></h1>

                    <!-- Autor y stats -->
                    <div class="flex items-center gap-4 mb-6">
                        <img src="<?php echo esc_url($autor_avatar); ?>"
                             alt="<?php echo esc_attr($autor_medio); ?>"
                             class="w-10 h-10 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor_medio); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($fecha_medio); ?> - <?php echo esc_html($vistas_medio); ?> visualizaciones</p>
                        </div>
                    </div>

                    <!-- Descripcion -->
                    <div class="prose max-w-none text-gray-700 mb-6">
                        <?php echo wp_kses_post($descripcion_medio ?: '<p>Descripcion del contenido multimedia. Aqui se puede detallar el contexto, los participantes y la informacion relevante sobre este recurso compartido por la comunidad.</p>'); ?>
                    </div>

                    <!-- Etiquetas -->
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $etiquetas_defecto = !empty($etiquetas_medio) ? $etiquetas_medio : ['comunidad', 'barrio', 'eventos', 'cultura'];
                        foreach ($etiquetas_defecto as $etiqueta):
                        ?>
                            <span class="px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors cursor-pointer">
                                #<?php echo esc_html($etiqueta); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Comentarios -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Comentarios</h2>

                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=18" alt="Luis Herrera" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Luis Herrera</span>
                                    <span class="text-sm text-gray-500">hace 5 horas</span>
                                </div>
                                <p class="text-gray-700">Excelente contenido, gracias por compartirlo con la comunidad.</p>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <div class="flex gap-4">
                            <img src="https://i.pravatar.cc/150?img=35" alt="Marta Gomez" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-gray-900">Marta Gomez</span>
                                    <span class="text-sm text-gray-500">hace 2 horas</span>
                                </div>
                                <p class="text-gray-700">Me encanta! Seria genial hacer mas contenido como este.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <textarea rows="3" placeholder="Escribe un comentario..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                        <div class="flex justify-end mt-3">
                            <button class="px-6 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105"
                                    style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
                                Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Info del medio -->
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4 mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Informacion</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Tipo</span>
                            <span class="font-semibold text-indigo-600"><?php echo esc_html(ucfirst($tipo_medio)); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Vistas</span>
                            <span class="font-semibold"><?php echo esc_html($vistas_medio); ?></span>
                        </div>
                        <?php if ($tipo_medio === 'video' || $tipo_medio === 'audio'): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                                <span class="text-gray-600 text-sm">Duracion</span>
                                <span class="font-semibold"><?php echo esc_html($duracion_medio); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Publicado</span>
                            <span class="font-semibold text-sm"><?php echo esc_html($fecha_medio); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Perfil del autor -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Autor</h3>
                    <div class="flex items-center gap-3 mb-3">
                        <img src="<?php echo esc_url($autor_avatar); ?>" alt="<?php echo esc_attr($autor_medio); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor_medio); ?></p>
                            <p class="text-sm text-gray-500">14 publicaciones</p>
                        </div>
                    </div>
                    <button class="w-full py-2 rounded-xl text-indigo-600 font-semibold text-sm bg-indigo-50 hover:bg-indigo-100 transition-colors">
                        Ver perfil
                    </button>
                </div>

                <!-- Contenido relacionado -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Relacionado</h3>
                    <div class="space-y-4">
                        <a href="#" class="flex gap-3 group">
                            <div class="w-24 h-16 rounded-lg bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">Reportaje comunitario</p>
                                <p class="text-xs text-gray-500">320 vistas</p>
                            </div>
                        </a>
                        <a href="#" class="flex gap-3 group">
                            <div class="w-24 h-16 rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">Galeria de fotos del evento</p>
                                <p class="text-xs text-gray-500">180 vistas</p>
                            </div>
                        </a>
                        <a href="#" class="flex gap-3 group">
                            <div class="w-24 h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">Podcast semanal</p>
                                <p class="text-xs text-gray-500">95 vistas</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
