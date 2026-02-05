<?php
/**
 * Frontend: Single Comunidad
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$comunidad = $comunidad ?? [];
$miembros = $miembros ?? [];
$publicaciones = $publicaciones ?? [];
$eventos = $eventos ?? [];
$es_miembro = $es_miembro ?? false;
$es_admin = $es_admin ?? false;
?>

<div class="flavor-frontend flavor-comunidades-single">
    <!-- Header de la comunidad -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-2xl overflow-hidden mb-8 shadow-lg">
        <?php if (!empty($comunidad['banner'])): ?>
        <div class="h-48 overflow-hidden">
            <img src="<?php echo esc_url($comunidad['banner']); ?>" alt="" class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <div class="p-8">
            <div class="flex items-start gap-6">
                <div class="w-24 h-24 bg-white rounded-2xl flex items-center justify-center text-5xl shadow-lg flex-shrink-0">
                    <?php echo esc_html($comunidad['emoji'] ?? '🏘️'); ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold"><?php echo esc_html($comunidad['nombre']); ?></h1>
                        <?php if (!empty($comunidad['verificada'])): ?>
                        <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm">✓ Verificada</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-rose-100 mb-4"><?php echo esc_html($comunidad['descripcion']); ?></p>
                    <div class="flex items-center gap-6 text-sm text-rose-100">
                        <span>👥 <?php echo esc_html($comunidad['total_miembros'] ?? 0); ?> miembros</span>
                        <span>📍 <?php echo esc_html($comunidad['ubicacion'] ?? 'Local'); ?></span>
                        <span>📅 Creada <?php echo esc_html($comunidad['fecha_creacion'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <?php if (!$es_miembro): ?>
                    <button class="bg-white text-rose-600 px-6 py-3 rounded-xl font-semibold hover:bg-rose-50 transition-colors"
                            onclick="flavorComunidades.unirse(<?php echo esc_attr($comunidad['id']); ?>)">
                        Unirse
                    </button>
                    <?php else: ?>
                    <div class="flex gap-2">
                        <button class="bg-white/20 text-white px-4 py-2 rounded-xl font-medium hover:bg-white/30 transition-colors">
                            ✓ Miembro
                        </button>
                        <?php if ($es_admin): ?>
                        <button class="bg-white text-rose-600 px-4 py-2 rounded-xl font-medium hover:bg-rose-50 transition-colors"
                                onclick="flavorComunidades.administrar(<?php echo esc_attr($comunidad['id']); ?>)">
                            ⚙️ Administrar
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tabs de navegación -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2">
                <nav class="flex gap-2">
                    <button class="flex-1 px-4 py-2 rounded-lg text-sm font-medium bg-rose-100 text-rose-700" data-tab="publicaciones">
                        📝 Publicaciones
                    </button>
                    <button class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100" data-tab="eventos">
                        📅 Eventos
                    </button>
                    <button class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100" data-tab="miembros">
                        👥 Miembros
                    </button>
                    <button class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100" data-tab="archivos">
                        📁 Archivos
                    </button>
                </nav>
            </div>

            <!-- Nueva publicación -->
            <?php if ($es_miembro): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex gap-4">
                    <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center text-xl">
                        👤
                    </div>
                    <div class="flex-1">
                        <textarea placeholder="Comparte algo con la comunidad..."
                                  class="w-full p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 resize-none"
                                  rows="3"></textarea>
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex gap-2">
                                <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg" title="Añadir imagen">📷</button>
                                <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg" title="Añadir archivo">📎</button>
                                <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg" title="Crear evento">📅</button>
                                <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg" title="Encuesta">📊</button>
                            </div>
                            <button class="bg-rose-500 text-white px-6 py-2 rounded-xl font-medium hover:bg-rose-600 transition-colors">
                                Publicar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Publicaciones -->
            <div id="tab-publicaciones" class="space-y-4">
                <?php if (empty($publicaciones)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-2xl">
                    <div class="text-4xl mb-3">📝</div>
                    <p class="text-gray-500">No hay publicaciones todavía</p>
                </div>
                <?php else: ?>
                <?php foreach ($publicaciones as $publicacion): ?>
                <article class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center text-xl flex-shrink-0">
                            <?php echo esc_html(substr($publicacion['autor_nombre'] ?? '?', 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-semibold text-gray-800"><?php echo esc_html($publicacion['autor_nombre']); ?></span>
                                <?php if (!empty($publicacion['autor_rol'])): ?>
                                <span class="bg-rose-100 text-rose-600 text-xs px-2 py-0.5 rounded-full"><?php echo esc_html($publicacion['autor_rol']); ?></span>
                                <?php endif; ?>
                                <span class="text-gray-400 text-sm">· <?php echo esc_html($publicacion['fecha']); ?></span>
                            </div>
                            <div class="prose prose-sm max-w-none text-gray-700 mb-4">
                                <?php echo wp_kses_post($publicacion['contenido']); ?>
                            </div>
                            <?php if (!empty($publicacion['imagen'])): ?>
                            <div class="rounded-xl overflow-hidden mb-4">
                                <img src="<?php echo esc_url($publicacion['imagen']); ?>" alt="" class="w-full">
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-6 text-sm text-gray-500">
                                <button class="flex items-center gap-1 hover:text-rose-600 transition-colors">
                                    ❤️ <?php echo esc_html($publicacion['likes'] ?? 0); ?>
                                </button>
                                <button class="flex items-center gap-1 hover:text-rose-600 transition-colors">
                                    💬 <?php echo esc_html($publicacion['comentarios'] ?? 0); ?>
                                </button>
                                <button class="hover:text-rose-600 transition-colors">
                                    🔗 Compartir
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Eventos (oculto por defecto) -->
            <div id="tab-eventos" class="space-y-4 hidden">
                <?php if (empty($eventos)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-2xl">
                    <div class="text-4xl mb-3">📅</div>
                    <p class="text-gray-500">No hay eventos programados</p>
                </div>
                <?php else: ?>
                <?php foreach ($eventos as $evento): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex gap-4">
                    <div class="w-16 text-center flex-shrink-0">
                        <div class="bg-rose-100 text-rose-600 rounded-t-lg py-1 text-xs font-medium">
                            <?php echo esc_html($evento['mes']); ?>
                        </div>
                        <div class="bg-white border border-rose-100 rounded-b-lg py-2 text-2xl font-bold text-gray-800">
                            <?php echo esc_html($evento['dia']); ?>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 mb-1"><?php echo esc_html($evento['titulo']); ?></h4>
                        <p class="text-gray-600 text-sm mb-2"><?php echo esc_html($evento['descripcion']); ?></p>
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span>🕐 <?php echo esc_html($evento['hora']); ?></span>
                            <span>📍 <?php echo esc_html($evento['lugar']); ?></span>
                            <span>👥 <?php echo esc_html($evento['asistentes'] ?? 0); ?> asistirán</span>
                        </div>
                    </div>
                    <button class="bg-rose-100 text-rose-600 px-4 py-2 rounded-xl font-medium hover:bg-rose-200 transition-colors self-center">
                        Asistir
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Información -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">ℹ️ Información</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Tipo</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($comunidad['tipo'] ?? 'Vecinal'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Privacidad</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($comunidad['privacidad'] ?? 'Pública'); ?></dd>
                    </div>
                    <?php if (!empty($comunidad['reglas'])): ?>
                    <div>
                        <dt class="text-gray-500 mb-2">Reglas</dt>
                        <dd class="text-gray-600 text-xs bg-gray-50 p-3 rounded-lg"><?php echo esc_html($comunidad['reglas']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Administradores -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">👑 Administradores</h3>
                <div class="space-y-3">
                    <?php
                    $admins = array_filter($miembros, function($miembro) { return !empty($miembro['es_admin']); });
                    if (empty($admins)) $admins = array_slice($miembros, 0, 2);
                    foreach ($admins as $admin):
                    ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center text-lg">
                            <?php echo esc_html(substr($admin['nombre'] ?? '?', 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($admin['nombre']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($admin['rol'] ?? 'Admin'); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Miembros recientes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">👥 Miembros</h3>
                    <span class="text-sm text-gray-500"><?php echo count($miembros); ?></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_slice($miembros, 0, 12) as $miembro): ?>
                    <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center text-sm"
                         title="<?php echo esc_attr($miembro['nombre']); ?>">
                        <?php echo esc_html(substr($miembro['nombre'] ?? '?', 0, 1)); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($miembros) > 12): ?>
                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-xs text-gray-600">
                        +<?php echo count($miembros) - 12; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enlaces -->
            <?php if (!empty($comunidad['enlaces'])): ?>
            <div class="bg-rose-50 rounded-2xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">🔗 Enlaces</h3>
                <div class="space-y-2">
                    <?php foreach ($comunidad['enlaces'] as $enlace): ?>
                    <a href="<?php echo esc_url($enlace['url']); ?>" target="_blank"
                       class="flex items-center gap-2 text-sm text-rose-600 hover:text-rose-700">
                        <?php echo esc_html($enlace['icono'] ?? '🔗'); ?>
                        <?php echo esc_html($enlace['titulo']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
