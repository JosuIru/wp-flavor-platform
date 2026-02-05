<?php
/**
 * Frontend: Single Incidencia
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$incidencia = $incidencia ?? [];
$comentarios = $comentarios ?? [];
$historial = $historial ?? [];
?>

<div class="flavor-frontend flavor-incidencias-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/incidencias/')); ?>" class="hover:text-red-600 transition-colors">
            Incidencias
        </a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($incidencia['titulo'] ?? 'Incidencia'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Imagen principal -->
                <?php if (!empty($incidencia['imagen'])): ?>
                <div class="aspect-video">
                    <img src="<?php echo esc_url($incidencia['imagen']); ?>"
                         alt="<?php echo esc_attr($incidencia['titulo']); ?>"
                         class="w-full h-full object-cover">
                </div>
                <?php endif; ?>

                <div class="p-6">
                    <!-- Estado y prioridad -->
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <?php
                        $estado_config = [
                            'pendiente' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icono' => '🔴', 'label' => 'Pendiente'],
                            'en_proceso' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icono' => '🟡', 'label' => 'En proceso'],
                            'resuelto' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icono' => '🟢', 'label' => 'Resuelta'],
                        ];
                        $estado = $incidencia['estado'] ?? 'pendiente';
                        $config = $estado_config[$estado] ?? $estado_config['pendiente'];
                        ?>
                        <span class="<?php echo esc_attr($config['bg']); ?> <?php echo esc_attr($config['text']); ?> px-4 py-2 rounded-full font-medium">
                            <?php echo esc_html($config['icono'] . ' ' . $config['label']); ?>
                        </span>

                        <?php if (!empty($incidencia['prioridad'])): ?>
                        <?php
                        $prioridad_config = [
                            'alta' => ['bg' => 'bg-red-500', 'label' => '🔥 Prioridad Alta'],
                            'media' => ['bg' => 'bg-yellow-500', 'label' => '⚡ Prioridad Media'],
                            'baja' => ['bg' => 'bg-blue-500', 'label' => '💧 Prioridad Baja'],
                        ];
                        $prioridad = $incidencia['prioridad'];
                        $pconfig = $prioridad_config[$prioridad] ?? $prioridad_config['media'];
                        ?>
                        <span class="<?php echo esc_attr($pconfig['bg']); ?> text-white px-4 py-2 rounded-full font-medium">
                            <?php echo esc_html($pconfig['label']); ?>
                        </span>
                        <?php endif; ?>

                        <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full">
                            🏷️ <?php echo esc_html($incidencia['categoria'] ?? 'General'); ?>
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-800 mb-4">
                        <?php echo esc_html($incidencia['titulo']); ?>
                    </h1>

                    <div class="prose prose-red max-w-none">
                        <?php echo wp_kses_post($incidencia['descripcion'] ?? ''); ?>
                    </div>
                </div>
            </div>

            <!-- Galería de fotos adicionales -->
            <?php if (!empty($incidencia['galeria'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📷 Fotos adicionales</h2>
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach ($incidencia['galeria'] as $foto): ?>
                    <a href="<?php echo esc_url($foto); ?>" class="aspect-square rounded-lg overflow-hidden" data-lightbox="galeria">
                        <img src="<?php echo esc_url($foto); ?>" alt="Foto de la incidencia" class="w-full h-full object-cover hover:scale-105 transition-transform">
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Historial de cambios -->
            <?php if (!empty($historial)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Historial de la incidencia</h2>
                <div class="space-y-4">
                    <?php foreach ($historial as $evento): ?>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <?php echo esc_html($evento['icono'] ?? '📝'); ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-800"><?php echo esc_html($evento['descripcion']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($evento['fecha']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Comentarios -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    💬 Comentarios (<?php echo count($comentarios); ?>)
                </h2>

                <?php if (empty($comentarios)): ?>
                <p class="text-gray-500 text-center py-8">No hay comentarios todavía. ¡Sé el primero!</p>
                <?php else: ?>
                <div class="space-y-4 mb-6">
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="flex gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <?php echo esc_html(mb_substr($comentario['autor'] ?? 'A', 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="font-medium text-gray-800"><?php echo esc_html($comentario['autor'] ?? 'Anónimo'); ?></p>
                                <p class="text-gray-600 mt-1"><?php echo esc_html($comentario['texto']); ?></p>
                            </div>
                            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($comentario['fecha']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Formulario de comentario -->
                <form class="space-y-4" onsubmit="return flavorIncidencias.enviarComentario(event, <?php echo esc_attr($incidencia['id']); ?>)">
                    <textarea name="comentario" rows="3"
                              placeholder="Escribe tu comentario..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
                    <button type="submit"
                            class="bg-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-600 transition-colors">
                        Enviar comentario
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Info de la incidencia -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📍 Información</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500">Ubicación</dt>
                        <dd class="text-gray-800 font-medium"><?php echo esc_html($incidencia['ubicacion'] ?? 'No especificada'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Fecha de reporte</dt>
                        <dd class="text-gray-800"><?php echo esc_html($incidencia['fecha_creacion'] ?? ''); ?></dd>
                    </div>
                    <?php if (!empty($incidencia['fecha_resolucion'])): ?>
                    <div>
                        <dt class="text-sm text-gray-500">Fecha de resolución</dt>
                        <dd class="text-gray-800"><?php echo esc_html($incidencia['fecha_resolucion']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <div>
                        <dt class="text-sm text-gray-500">Reportado por</dt>
                        <dd class="text-gray-800"><?php echo esc_html($incidencia['autor'] ?? 'Anónimo'); ?></dd>
                    </div>
                    <?php if (!empty($incidencia['asignado_a'])): ?>
                    <div>
                        <dt class="text-sm text-gray-500">Asignado a</dt>
                        <dd class="text-gray-800"><?php echo esc_html($incidencia['asignado_a']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Mapa de ubicación -->
            <?php if (!empty($incidencia['coordenadas'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">🗺️ Ubicación en el mapa</h3>
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-100" id="mapa-incidencia"
                     data-lat="<?php echo esc_attr($incidencia['coordenadas']['lat']); ?>"
                     data-lng="<?php echo esc_attr($incidencia['coordenadas']['lng']); ?>">
                    <!-- Mapa se carga por JS -->
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-3">
                <h3 class="font-semibold text-gray-800 mb-4">⚡ Acciones</h3>

                <button class="w-full flex items-center justify-center gap-2 bg-red-100 text-red-700 py-3 px-4 rounded-xl font-medium hover:bg-red-200 transition-colors"
                        onclick="flavorIncidencias.apoyar(<?php echo esc_attr($incidencia['id']); ?>)">
                    👍 Apoyar incidencia
                    <span class="bg-red-200 px-2 py-0.5 rounded-full text-sm">
                        <?php echo esc_html($incidencia['votos'] ?? 0); ?>
                    </span>
                </button>

                <button class="w-full flex items-center justify-center gap-2 bg-gray-100 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-200 transition-colors"
                        onclick="flavorIncidencias.compartir(<?php echo esc_attr($incidencia['id']); ?>)">
                    📤 Compartir
                </button>

                <button class="w-full flex items-center justify-center gap-2 bg-gray-100 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-200 transition-colors"
                        onclick="flavorIncidencias.seguir(<?php echo esc_attr($incidencia['id']); ?>)">
                    🔔 Seguir actualizaciones
                </button>
            </div>

            <!-- Incidencias cercanas -->
            <?php if (!empty($incidencia['cercanas'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📍 Incidencias cercanas</h3>
                <div class="space-y-3">
                    <?php foreach ($incidencia['cercanas'] as $cercana): ?>
                    <a href="<?php echo esc_url($cercana['url']); ?>"
                       class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($cercana['titulo']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($cercana['distancia']); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
