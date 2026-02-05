<?php
/**
 * Frontend: Filtros Comunidades
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$tipos = $tipos ?? [];
$ubicaciones = $ubicaciones ?? [];
?>

<div class="flavor-frontend flavor-comunidades-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">🔍 Filtrar comunidades</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-rose-600 hover:text-rose-700 font-medium" onclick="flavorComunidades.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-comunidades" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="Nombre de comunidad..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
            </div>

            <!-- Tipo de comunidad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de comunidad</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700">Todas</span>
                    </label>
                    <?php
                    $tipos_default = [
                        'vecinal' => '🏠 Vecinal',
                        'interes' => '💡 Interés común',
                        'deportiva' => '⚽ Deportiva',
                        'cultural' => '🎭 Cultural',
                        'solidaria' => '🤝 Solidaria',
                        'profesional' => '💼 Profesional',
                    ];
                    foreach ($tipos_default as $tipo_valor => $tipo_label):
                    ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="<?php echo esc_attr($tipo_valor); ?>"
                               <?php echo ($filtros_activos['tipo'] ?? '') === $tipo_valor ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700"><?php echo esc_html($tipo_label); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Privacidad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Privacidad</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="" <?php echo empty($filtros_activos['privacidad']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700">Todas</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="publica" <?php echo ($filtros_activos['privacidad'] ?? '') === 'publica' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700">🌍 Públicas</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="privada" <?php echo ($filtros_activos['privacidad'] ?? '') === 'privada' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700">🔒 Privadas</span>
                    </label>
                </div>
            </div>

            <!-- Ubicación -->
            <?php if (!empty($ubicaciones)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación</label>
                <select name="ubicacion" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value="">Todas las ubicaciones</option>
                    <?php foreach ($ubicaciones as $ubicacion): ?>
                    <option value="<?php echo esc_attr($ubicacion['id']); ?>" <?php echo ($filtros_activos['ubicacion'] ?? '') == $ubicacion['id'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($ubicacion['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Tamaño -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tamaño</label>
                <select name="tamano" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value="">Cualquier tamaño</option>
                    <option value="pequena" <?php echo ($filtros_activos['tamano'] ?? '') === 'pequena' ? 'selected' : ''; ?>>
                        Pequeña (menos de 20 miembros)
                    </option>
                    <option value="mediana" <?php echo ($filtros_activos['tamano'] ?? '') === 'mediana' ? 'selected' : ''; ?>>
                        Mediana (20-100 miembros)
                    </option>
                    <option value="grande" <?php echo ($filtros_activos['tamano'] ?? '') === 'grande' ? 'selected' : ''; ?>>
                        Grande (más de 100 miembros)
                    </option>
                </select>
            </div>

            <!-- Actividad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Actividad</label>
                <select name="actividad" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value="">Cualquier nivel</option>
                    <option value="muy_activa" <?php echo ($filtros_activos['actividad'] ?? '') === 'muy_activa' ? 'selected' : ''; ?>>
                        🔥 Muy activa
                    </option>
                    <option value="activa" <?php echo ($filtros_activos['actividad'] ?? '') === 'activa' ? 'selected' : ''; ?>>
                        ✨ Activa
                    </option>
                    <option value="moderada" <?php echo ($filtros_activos['actividad'] ?? '') === 'moderada' ? 'selected' : ''; ?>>
                        💤 Moderada
                    </option>
                </select>
            </div>

            <!-- Opciones adicionales -->
            <div class="space-y-3">
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="verificada" value="1"
                           <?php echo !empty($filtros_activos['verificada']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700">✓ Solo comunidades verificadas</span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="con_eventos" value="1"
                           <?php echo !empty($filtros_activos['con_eventos']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700">📅 Con eventos próximos</span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="aceptando_miembros" value="1"
                           <?php echo !empty($filtros_activos['aceptando_miembros']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700">🚪 Aceptando nuevos miembros</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-rose-500 text-white py-3 px-6 rounded-xl font-semibold hover:bg-rose-600 transition-colors">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
