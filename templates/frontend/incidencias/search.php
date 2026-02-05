<?php
/**
 * Frontend: Búsqueda de Incidencias
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['farola', 'bache', 'grafiti', 'basura', 'ruido'];
?>

<div class="flavor-frontend flavor-incidencias-search">
    <!-- Buscador principal -->
    <div class="bg-gradient-to-r from-red-500 to-rose-500 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">🔍 Buscar incidencias</h2>

        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q"
                       value="<?php echo esc_attr($query); ?>"
                       placeholder="¿Qué incidencia buscas? (ej: farola rota, bache, grafiti...)"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-red-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-red-600 text-white p-3 rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Búsquedas frecuentes -->
        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-red-100 text-sm">Búsquedas frecuentes:</span>
            <?php foreach ($sugerencias as $sugerencia): ?>
            <a href="?q=<?php echo esc_attr($sugerencia); ?>"
               class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <!-- Resultados de búsqueda -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php if ($total_resultados > 0): ?>
                    <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                    para "<span class="text-red-600"><?php echo esc_html($query); ?></span>"
                <?php else: ?>
                    Sin resultados para "<span class="text-red-600"><?php echo esc_html($query); ?></span>"
                <?php endif; ?>
            </h3>

            <?php if ($total_resultados > 0): ?>
            <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500"
                    onchange="flavorIncidencias.ordenarResultados(this.value)">
                <option value="relevancia">Más relevantes</option>
                <option value="recientes">Más recientes</option>
                <option value="votos">Más apoyados</option>
                <option value="prioridad">Mayor prioridad</option>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($resultados)): ?>
    <!-- Sin resultados -->
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos incidencias</h3>
        <p class="text-gray-500 mb-6">Prueba con otros términos o reporta una nueva incidencia</p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button class="bg-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-600 transition-colors"
                    onclick="flavorIncidencias.nuevaIncidencia()">
                📝 Reportar incidencia
            </button>
            <a href="<?php echo esc_url(home_url('/incidencias/')); ?>"
               class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-300 transition-colors">
                Ver todas las incidencias
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Lista de resultados -->
    <div class="space-y-4">
        <?php foreach ($resultados as $incidencia): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden border border-gray-100">
            <div class="flex flex-col md:flex-row">
                <!-- Imagen -->
                <div class="md:w-48 flex-shrink-0">
                    <?php if (!empty($incidencia['imagen'])): ?>
                    <img src="<?php echo esc_url($incidencia['imagen']); ?>"
                         alt="<?php echo esc_attr($incidencia['titulo']); ?>"
                         class="w-full h-32 md:h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-32 md:h-full bg-red-50 flex items-center justify-center text-4xl">
                        ⚠️
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contenido -->
                <div class="flex-1 p-5">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <!-- Estado badge -->
                            <?php
                            $estado_config = [
                                'pendiente' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icono' => '🔴'],
                                'en_proceso' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icono' => '🟡'],
                                'resuelto' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icono' => '🟢'],
                            ];
                            $estado = $incidencia['estado'] ?? 'pendiente';
                            $config = $estado_config[$estado] ?? $estado_config['pendiente'];
                            ?>
                            <span class="inline-flex items-center gap-1 <?php echo esc_attr($config['bg']); ?> <?php echo esc_attr($config['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                                <?php echo esc_html($config['icono']); ?>
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $estado))); ?>
                            </span>

                            <h3 class="text-lg font-semibold text-gray-800 mt-2">
                                <a href="<?php echo esc_url($incidencia['url']); ?>" class="hover:text-red-600 transition-colors">
                                    <?php echo esc_html($incidencia['titulo']); ?>
                                </a>
                            </h3>
                        </div>

                        <?php if (!empty($incidencia['prioridad'])): ?>
                        <?php
                        $prioridad_config = [
                            'alta' => ['bg' => 'bg-red-500', 'label' => '🔥 Alta'],
                            'media' => ['bg' => 'bg-yellow-500', 'label' => '⚡ Media'],
                            'baja' => ['bg' => 'bg-blue-500', 'label' => '💧 Baja'],
                        ];
                        $prioridad = $incidencia['prioridad'];
                        $pconfig = $prioridad_config[$prioridad] ?? $prioridad_config['media'];
                        ?>
                        <span class="<?php echo esc_attr($pconfig['bg']); ?> text-white text-xs px-2 py-1 rounded">
                            <?php echo esc_html($pconfig['label']); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                        <?php echo esc_html($incidencia['descripcion']); ?>
                    </p>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            📍 <?php echo esc_html($incidencia['ubicacion'] ?? 'Sin ubicación'); ?>
                        </span>
                        <span class="flex items-center gap-1">
                            📅 <?php echo esc_html($incidencia['fecha'] ?? ''); ?>
                        </span>
                        <?php if (!empty($incidencia['votos'])): ?>
                        <span class="flex items-center gap-1 text-red-600 font-medium">
                            👍 <?php echo esc_html($incidencia['votos']); ?> apoyos
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex md:flex-col justify-end gap-2 p-4 bg-gray-50 md:bg-transparent">
                    <button class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                            onclick="flavorIncidencias.apoyar(<?php echo esc_attr($incidencia['id']); ?>)"
                            title="Apoyar incidencia">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                    </button>
                    <a href="<?php echo esc_url($incidencia['url']); ?>"
                       class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                       title="Ver detalles">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_resultados > 10): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                ← Anterior
            </button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_resultados / 10); ?></span>
            <button class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors">
                Siguiente →
            </button>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
