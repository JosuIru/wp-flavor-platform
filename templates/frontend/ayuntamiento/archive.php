<?php
/**
 * Frontend: Archive Ayuntamiento
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$secciones = $secciones ?? [];
$noticias = $noticias ?? [];
$tramites_destacados = $tramites_destacados ?? [];
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-frontend flavor-ayuntamiento-archive">
    <!-- Header institucional -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 bg-white rounded-xl flex items-center justify-center text-4xl">
                    🏛️
                </div>
                <div>
                    <h1 class="text-3xl font-bold mb-1"><?php echo esc_html(get_bloginfo('name')); ?></h1>
                    <p class="text-blue-200">Portal ciudadano - Tu ayuntamiento a un clic</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="<?php echo esc_url(home_url('/ayuntamiento/tramites/')); ?>" class="bg-white text-blue-700 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-all shadow-md">
                    📋 Trámites
                </a>
                <a href="<?php echo esc_url(home_url('/ayuntamiento/cita-previa/')); ?>" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-500 transition-all border border-blue-500">
                    📅 Cita previa
                </a>
            </div>
        </div>
    </div>

    <!-- Buscador rápido -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <form action="<?php echo esc_url(home_url('/ayuntamiento/buscar/')); ?>" method="get" class="flex gap-4">
            <div class="flex-1 relative">
                <input type="text" name="q" placeholder="¿Qué trámite o información buscas?"
                       class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                <svg class="w-6 h-6 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit" class="bg-blue-700 text-white px-8 py-4 rounded-xl font-semibold hover:bg-blue-800 transition-colors">
                Buscar
            </button>
        </form>
    </div>

    <!-- Accesos rápidos -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
        <?php
        $accesos_rapidos = [
            ['icono' => '📋', 'titulo' => 'Trámites', 'url' => '/ayuntamiento/tramites/', 'color' => 'blue'],
            ['icono' => '📅', 'titulo' => 'Cita previa', 'url' => '/ayuntamiento/cita-previa/', 'color' => 'green'],
            ['icono' => '💰', 'titulo' => 'Pagos', 'url' => '/ayuntamiento/pagos/', 'color' => 'yellow'],
            ['icono' => '📢', 'titulo' => 'Noticias', 'url' => '/ayuntamiento/noticias/', 'color' => 'purple'],
            ['icono' => '🗓️', 'titulo' => 'Agenda', 'url' => '/ayuntamiento/agenda/', 'color' => 'red'],
            ['icono' => '📍', 'titulo' => 'Servicios', 'url' => '/ayuntamiento/servicios/', 'color' => 'teal'],
        ];
        foreach ($accesos_rapidos as $acceso):
        ?>
        <a href="<?php echo esc_url(home_url($acceso['url'])); ?>"
           class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-<?php echo $acceso['color']; ?>-200 transition-all text-center group">
            <div class="text-3xl mb-2"><?php echo esc_html($acceso['icono']); ?></div>
            <p class="text-sm font-medium text-gray-700 group-hover:text-<?php echo $acceso['color']; ?>-600"><?php echo esc_html($acceso['titulo']); ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Contenido principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Noticias -->
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">📢 Últimas noticias</h2>
                <a href="<?php echo esc_url(home_url('/ayuntamiento/noticias/')); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    Ver todas →
                </a>
            </div>
            <div class="space-y-4">
                <?php if (empty($noticias)): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center">
                    <p class="text-gray-500">No hay noticias recientes</p>
                </div>
                <?php else: ?>
                <?php foreach (array_slice($noticias, 0, 4) as $noticia): ?>
                <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
                    <div class="flex">
                        <?php if (!empty($noticia['imagen'])): ?>
                        <div class="w-40 flex-shrink-0">
                            <img src="<?php echo esc_url($noticia['imagen']); ?>" alt="" class="w-full h-full object-cover">
                        </div>
                        <?php endif; ?>
                        <div class="p-4 flex-1">
                            <span class="text-xs text-blue-600 font-medium"><?php echo esc_html($noticia['categoria'] ?? 'General'); ?></span>
                            <h3 class="font-semibold text-gray-800 mt-1 mb-2">
                                <a href="<?php echo esc_url($noticia['url']); ?>" class="hover:text-blue-600 transition-colors">
                                    <?php echo esc_html($noticia['titulo']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 line-clamp-2"><?php echo esc_html($noticia['extracto']); ?></p>
                            <p class="text-xs text-gray-400 mt-2"><?php echo esc_html($noticia['fecha']); ?></p>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Trámites destacados -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📋 Trámites más solicitados</h3>
                <ul class="space-y-3">
                    <?php
                    $tramites_default = [
                        ['titulo' => 'Empadronamiento', 'url' => '#'],
                        ['titulo' => 'Certificado de residencia', 'url' => '#'],
                        ['titulo' => 'Pago de tributos', 'url' => '#'],
                        ['titulo' => 'Licencia de obras', 'url' => '#'],
                        ['titulo' => 'Registro civil', 'url' => '#'],
                    ];
                    $tramites = !empty($tramites_destacados) ? $tramites_destacados : $tramites_default;
                    foreach ($tramites as $tramite):
                    ?>
                    <li>
                        <a href="<?php echo esc_url($tramite['url']); ?>" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 transition-colors">
                            <span class="text-blue-500">→</span>
                            <span><?php echo esc_html($tramite['titulo']); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="bg-blue-50 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📞 Contacto</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span>📍</span>
                        <span class="text-gray-600"><?php echo esc_html($estadisticas['direccion'] ?? 'Plaza del Ayuntamiento, 1'); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>📞</span>
                        <a href="tel:<?php echo esc_attr($estadisticas['telefono'] ?? ''); ?>" class="text-blue-600 hover:underline">
                            <?php echo esc_html($estadisticas['telefono'] ?? '900 000 000'); ?>
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>✉️</span>
                        <a href="mailto:<?php echo esc_attr($estadisticas['email'] ?? ''); ?>" class="text-blue-600 hover:underline">
                            <?php echo esc_html($estadisticas['email'] ?? 'info@ayuntamiento.es'); ?>
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>🕐</span>
                        <span class="text-gray-600"><?php echo esc_html($estadisticas['horario'] ?? 'L-V 9:00-14:00'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Alertas/Avisos -->
            <?php if (!empty($estadisticas['avisos'])): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h3 class="font-semibold text-yellow-800 mb-3">⚠️ Avisos importantes</h3>
                <ul class="space-y-2 text-sm text-yellow-700">
                    <?php foreach ($estadisticas['avisos'] as $aviso): ?>
                    <li>• <?php echo esc_html($aviso); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
