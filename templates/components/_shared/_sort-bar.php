<?php
/**
 * Componente Compartido: Barra de Ordenación y Filtros
 *
 * @package FlavorChatIA
 * @var int $total_items
 * @var string $etiqueta_items Ej: "servicios", "productos"
 * @var string $ordenacion_actual
 * @var array $opciones_orden Array de ['value' => '', 'label' => '']
 * @var string $vista_actual 'grid' o 'list'
 * @var bool $mostrar_vista
 */
if (!defined('ABSPATH')) exit;

$total_items = $total_items ?? 0;
$etiqueta_items = $etiqueta_items ?? 'elementos';
$ordenacion_actual = $ordenacion_actual ?? 'recientes';
$opciones_orden = $opciones_orden ?? [
    ['value' => 'recientes', 'label' => 'Más recientes'],
    ['value' => 'populares', 'label' => 'Más populares'],
    ['value' => 'nombre', 'label' => 'Nombre A-Z'],
    ['value' => 'nombre_desc', 'label' => 'Nombre Z-A'],
];
$vista_actual = $vista_actual ?? 'grid';
$mostrar_vista = $mostrar_vista ?? true;
?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-6 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
    <p class="text-sm text-gray-600">
        <span class="font-semibold text-gray-800"><?php echo esc_html($total_items); ?></span>
        <?php echo esc_html($etiqueta_items); ?> encontrados
    </p>

    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="flavor-sort" class="text-sm text-gray-500">Ordenar:</label>
            <select id="flavor-sort" name="orden"
                    class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent bg-white">
                <?php foreach ($opciones_orden as $opcion): ?>
                    <option value="<?php echo esc_attr($opcion['value']); ?>"
                            <?php echo $ordenacion_actual === $opcion['value'] ? 'selected' : ''; ?>>
                        <?php echo esc_html($opcion['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($mostrar_vista): ?>
        <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1">
            <button class="p-2 rounded <?php echo $vista_actual === 'grid' ? 'bg-gray-100 text-gray-800' : 'text-gray-400 hover:text-gray-600'; ?> transition-colors"
                    data-vista="grid" aria-label="Vista cuadrícula" title="Cuadrícula">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </button>
            <button class="p-2 rounded <?php echo $vista_actual === 'list' ? 'bg-gray-100 text-gray-800' : 'text-gray-400 hover:text-gray-600'; ?> transition-colors"
                    data-vista="list" aria-label="Vista lista" title="Lista">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
