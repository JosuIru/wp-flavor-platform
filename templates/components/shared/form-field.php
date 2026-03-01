<?php
/**
 * Componente: Form Field
 *
 * Campo de formulario genérico reutilizable.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $type        Tipo: text, email, password, textarea, select, checkbox, radio, file, date, number, tel, url, hidden
 * @param string $name        Nombre del campo
 * @param string $id          ID del campo (default: name)
 * @param string $label       Etiqueta
 * @param string $value       Valor actual
 * @param string $placeholder Placeholder
 * @param string $help        Texto de ayuda
 * @param string $error       Mensaje de error
 * @param bool   $required    Campo requerido
 * @param bool   $disabled    Campo deshabilitado
 * @param array  $options     Opciones para select/radio: [['value' => '1', 'label' => 'Opción 1']]
 * @param array  $attrs       Atributos adicionales
 * @param string $color       Color del tema
 * @param string $icon        Icono al inicio del campo
 * @param string $suffix      Texto o icono al final
 * @param int    $rows        Filas para textarea
 * @param string $accept      Accept para file input
 */

if (!defined('ABSPATH')) {
    exit;
}

$type = $type ?? 'text';
$name = $name ?? '';
$field_id = $id ?? $name;
$label = $label ?? '';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$error = $error ?? '';
$required = $required ?? false;
$disabled = $disabled ?? false;
$options = $options ?? [];
$attrs = $attrs ?? [];
$color = $color ?? 'blue';
$icon = $icon ?? '';
$suffix = $suffix ?? '';
$rows = $rows ?? 4;
$accept = $accept ?? '';
$min = $min ?? '';
$max = $max ?? '';
$step = $step ?? '';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Clases base del input
$input_base = 'w-full px-4 py-3 rounded-xl border transition-colors focus:outline-none focus:ring-2';
$input_normal = 'border-gray-200 focus:border-' . $color . '-500 focus:ring-' . $color . '-500/20';
$input_error = 'border-red-500 focus:border-red-500 focus:ring-red-500/20';
$input_disabled = 'bg-gray-100 cursor-not-allowed opacity-60';

$input_classes = $input_base . ' ' . ($error ? $input_error : $input_normal) . ' ' . ($disabled ? $input_disabled : '');

// Construir atributos extra
$attrs_html = '';
foreach ($attrs as $attr_name => $attr_value) {
    $attrs_html .= ' ' . esc_attr($attr_name) . '="' . esc_attr($attr_value) . '"';
}

// Atributos comunes
$common_attrs = '';
if ($required) $common_attrs .= ' required';
if ($disabled) $common_attrs .= ' disabled';
if ($placeholder) $common_attrs .= ' placeholder="' . esc_attr($placeholder) . '"';
?>

<div class="form-field mb-4">
    <?php if ($label && $type !== 'hidden'): ?>
        <label for="<?php echo esc_attr($field_id); ?>" class="block text-sm font-medium text-gray-700 mb-2">
            <?php echo esc_html($label); ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if ($type === 'hidden'): ?>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr($value); ?>">

    <?php elseif ($type === 'textarea'): ?>
        <textarea
            name="<?php echo esc_attr($name); ?>"
            id="<?php echo esc_attr($field_id); ?>"
            rows="<?php echo esc_attr($rows); ?>"
            class="<?php echo esc_attr($input_classes); ?>"
            <?php echo $common_attrs; ?>
            <?php echo $attrs_html; ?>
        ><?php echo esc_textarea($value); ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <div class="relative">
            <select
                name="<?php echo esc_attr($name); ?>"
                id="<?php echo esc_attr($field_id); ?>"
                class="<?php echo esc_attr($input_classes); ?> appearance-none pr-10"
                <?php echo $common_attrs; ?>
                <?php echo $attrs_html; ?>
            >
                <?php if ($placeholder): ?>
                    <option value=""><?php echo esc_html($placeholder); ?></option>
                <?php endif; ?>
                <?php foreach ($options as $option): ?>
                    <option value="<?php echo esc_attr($option['value'] ?? ''); ?>"
                            <?php selected($value, $option['value'] ?? ''); ?>>
                        <?php echo esc_html($option['label'] ?? $option['value'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <span class="text-gray-400">▼</span>
            </div>
        </div>

    <?php elseif ($type === 'checkbox'): ?>
        <label class="flex items-center gap-3 cursor-pointer">
            <input
                type="checkbox"
                name="<?php echo esc_attr($name); ?>"
                id="<?php echo esc_attr($field_id); ?>"
                value="1"
                class="w-5 h-5 rounded border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500"
                <?php checked($value, '1'); ?>
                <?php echo $common_attrs; ?>
                <?php echo $attrs_html; ?>
            >
            <span class="text-gray-700"><?php echo esc_html($placeholder ?: $label); ?></span>
        </label>

    <?php elseif ($type === 'radio'): ?>
        <div class="space-y-2">
            <?php foreach ($options as $index => $option): ?>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input
                        type="radio"
                        name="<?php echo esc_attr($name); ?>"
                        id="<?php echo esc_attr($field_id . '_' . $index); ?>"
                        value="<?php echo esc_attr($option['value'] ?? ''); ?>"
                        class="w-5 h-5 border-gray-300 text-<?php echo esc_attr($color); ?>-600 focus:ring-<?php echo esc_attr($color); ?>-500"
                        <?php checked($value, $option['value'] ?? ''); ?>
                        <?php echo $common_attrs; ?>
                    >
                    <span class="text-gray-700"><?php echo esc_html($option['label'] ?? ''); ?></span>
                </label>
            <?php endforeach; ?>
        </div>

    <?php elseif ($type === 'file'): ?>
        <div class="relative">
            <input
                type="file"
                name="<?php echo esc_attr($name); ?>"
                id="<?php echo esc_attr($field_id); ?>"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:<?php echo esc_attr($color_classes['bg']); ?> file:<?php echo esc_attr($color_classes['text']); ?> hover:file:opacity-80 cursor-pointer"
                <?php if ($accept): ?>accept="<?php echo esc_attr($accept); ?>"<?php endif; ?>
                <?php echo $common_attrs; ?>
                <?php echo $attrs_html; ?>
            >
        </div>

    <?php else: ?>
        <div class="relative">
            <?php if ($icon): ?>
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <?php echo esc_html($icon); ?>
                </span>
            <?php endif; ?>

            <input
                type="<?php echo esc_attr($type); ?>"
                name="<?php echo esc_attr($name); ?>"
                id="<?php echo esc_attr($field_id); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($input_classes); ?> <?php echo $icon ? 'pl-12' : ''; ?> <?php echo $suffix ? 'pr-12' : ''; ?>"
                <?php if ($min !== ''): ?>min="<?php echo esc_attr($min); ?>"<?php endif; ?>
                <?php if ($max !== ''): ?>max="<?php echo esc_attr($max); ?>"<?php endif; ?>
                <?php if ($step): ?>step="<?php echo esc_attr($step); ?>"<?php endif; ?>
                <?php echo $common_attrs; ?>
                <?php echo $attrs_html; ?>
            >

            <?php if ($suffix): ?>
                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                    <?php echo esc_html($suffix); ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($help && !$error): ?>
        <p class="mt-2 text-sm text-gray-500"><?php echo esc_html($help); ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="mt-2 text-sm text-red-600"><?php echo esc_html($error); ?></p>
    <?php endif; ?>
</div>
