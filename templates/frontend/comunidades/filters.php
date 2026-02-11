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
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('🔍 Filtrar comunidades', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-rose-600 hover:text-rose-700 font-medium" onclick="flavorComunidades.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-comunidades" class="space-y-6">
            <!-- Búsqueda -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtros_activos['busqueda'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('Nombre de comunidad...', 'flavor-chat-ia'); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
            </div>

            <!-- Tipo de comunidad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Tipo de comunidad', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="tipo" value="" <?php echo empty($filtros_activos['tipo']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700"><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></span>
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
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Privacidad', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="" <?php echo empty($filtros_activos['privacidad']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700"><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="<?php echo esc_attr__('publica', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['privacidad'] ?? '') === 'publica' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700"><?php echo esc_html__('🌍 Públicas', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="privacidad" value="<?php echo esc_attr__('privada', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['privacidad'] ?? '') === 'privada' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-rose-500 border-gray-300 focus:ring-rose-500">
                        <span class="text-gray-700"><?php echo esc_html__('🔒 Privadas', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Ubicación -->
            <?php if (!empty($ubicaciones)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></label>
                <select name="ubicacion" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value=""><?php echo esc_html__('Todas las ubicaciones', 'flavor-chat-ia'); ?></option>
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
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Tamaño', 'flavor-chat-ia'); ?></label>
                <select name="tamano" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value=""><?php echo esc_html__('Cualquier tamaño', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('pequena', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tamano'] ?? '') === 'pequena' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Pequeña (menos de 20 miembros)', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('mediana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tamano'] ?? '') === 'mediana' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Mediana (20-100 miembros)', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('grande', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['tamano'] ?? '') === 'grande' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('Grande (más de 100 miembros)', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </div>

            <!-- Actividad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Actividad', 'flavor-chat-ia'); ?></label>
                <select name="actividad" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value=""><?php echo esc_html__('Cualquier nivel', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('muy_activa', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['actividad'] ?? '') === 'muy_activa' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('🔥 Muy activa', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('activa', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['actividad'] ?? '') === 'activa' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('✨ Activa', 'flavor-chat-ia'); ?>
                    </option>
                    <option value="<?php echo esc_attr__('moderada', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['actividad'] ?? '') === 'moderada' ? 'selected' : ''; ?>>
                        <?php echo esc_html__('💤 Moderada', 'flavor-chat-ia'); ?>
                    </option>
                </select>
            </div>

            <!-- Opciones adicionales -->
            <div class="space-y-3">
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="verificada" value="1"
                           <?php echo !empty($filtros_activos['verificada']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700"><?php echo esc_html__('✓ Solo comunidades verificadas', 'flavor-chat-ia'); ?></span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="con_eventos" value="1"
                           <?php echo !empty($filtros_activos['con_eventos']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700"><?php echo esc_html__('📅 Con eventos próximos', 'flavor-chat-ia'); ?></span>
                </label>
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="aceptando_miembros" value="1"
                           <?php echo !empty($filtros_activos['aceptando_miembros']) ? 'checked' : ''; ?>
                           class="w-4 h-4 text-rose-500 border-gray-300 rounded focus:ring-rose-500">
                    <span class="text-gray-700"><?php echo esc_html__('🚪 Aceptando nuevos miembros', 'flavor-chat-ia'); ?></span>
                </label>
            </div>

            <button type="submit" class="w-full bg-rose-500 text-white py-3 px-6 rounded-xl font-semibold hover:bg-rose-600 transition-colors">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
