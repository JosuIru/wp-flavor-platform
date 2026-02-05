<?php
/**
 * Frontend: Archive de Incidencias
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$incidencias = $incidencias ?? [];
$total_incidencias = $total_incidencias ?? 0;
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-frontend flavor-incidencias-archive">
    <!-- Header con gradiente rojo -->
    <div class="bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">⚠️ Incidencias del Barrio</h1>
                <p class="text-red-100">Reporta y consulta problemas en espacios públicos</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_incidencias); ?> incidencias registradas
                </span>
                <button class="bg-white text-red-600 px-6 py-3 rounded-xl font-semibold hover:bg-red-50 transition-all shadow-md"
                        onclick="flavorIncidencias.nuevaIncidencia()">
                    📝 Reportar incidencia
                </button>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <span class="text-red-600">🔴</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['pendientes'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Pendientes</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <span class="text-yellow-600">🟡</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['en_proceso'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">En proceso</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-green-600">🟢</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['resueltas'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Resueltas</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-blue-600">📊</span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['tiempo_medio'] ?? '—'); ?></p>
                    <p class="text-xs text-gray-500">Días promedio</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rápidos por estado -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-red-100 text-red-700 font-medium hover:bg-red-200 transition-colors filter-active"
                data-estado="todos">
            Todas
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-estado="pendiente">
            🔴 Pendientes
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-estado="en_proceso">
            🟡 En proceso
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-estado="resuelto">
            🟢 Resueltas
        </button>
    </div>

    <!-- Lista de incidencias -->
    <div class="space-y-4">
        <?php if (empty($incidencias)): ?>
        <div class="text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay incidencias</h3>
            <p class="text-gray-500 mb-6">El barrio está en perfecto estado</p>
            <button class="bg-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-600 transition-colors"
                    onclick="flavorIncidencias.nuevaIncidencia()">
                📝 Reportar nueva incidencia
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($incidencias as $incidencia): ?>
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

                        <!-- Prioridad -->
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
                        <span class="flex items-center gap-1">
                            🏷️ <?php echo esc_html($incidencia['categoria'] ?? 'General'); ?>
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
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_incidencias > 10): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                ← Anterior
            </button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_incidencias / 10); ?></span>
            <button class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors">
                Siguiente →
            </button>
        </nav>
    </div>
    <?php endif; ?>
</div>
