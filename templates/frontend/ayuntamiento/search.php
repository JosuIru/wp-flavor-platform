<?php
/**
 * Frontend: Búsqueda Ayuntamiento
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['empadronamiento', 'licencia obra', 'certificado', 'impuestos', 'subvenciones'];
?>

<div class="flavor-frontend flavor-ayuntamiento-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscador del Ayuntamiento</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="Buscar trámites, noticias, servicios..."
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-blue-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-blue-200 text-sm">Búsquedas frecuentes:</span>
            <?php foreach ($sugerencias as $sug): ?>
            <a href="?q=<?php echo esc_attr($sug); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sug); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-blue-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-blue-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos resultados</h3>
        <p class="text-gray-500 mb-6">Prueba con otros términos o contacta con atención ciudadana</p>
        <a href="<?php echo esc_url(home_url('/ayuntamiento/contacto/')); ?>" class="bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-800 transition-colors inline-block">
            Contactar
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($resultados as $resultado): ?>
        <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">
                    <?php
                    $iconos = ['tramite' => '📋', 'noticia' => '📢', 'servicio' => '📍'];
                    echo esc_html($iconos[$resultado['tipo']] ?? '📄');
                    ?>
                </div>
                <div class="flex-1">
                    <span class="text-xs text-blue-600 font-medium uppercase"><?php echo esc_html($resultado['tipo'] ?? 'General'); ?></span>
                    <h3 class="text-lg font-semibold text-gray-800 mt-1">
                        <a href="<?php echo esc_url($resultado['url']); ?>" class="hover:text-blue-600 transition-colors">
                            <?php echo esc_html($resultado['titulo']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mt-2 line-clamp-2"><?php echo esc_html($resultado['extracto']); ?></p>
                    <?php if (!empty($resultado['departamento'])): ?>
                    <p class="text-xs text-gray-400 mt-2">📁 <?php echo esc_html($resultado['departamento']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
