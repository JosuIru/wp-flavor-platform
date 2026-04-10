<?php
/**
 * Template genérico de Archive
 *
 * Puede ser usado por cualquier módulo. Detecta el módulo automáticamente
 * desde las variables que pasa Dynamic Pages ($module_id, $module).
 *
 * Uso en configuración de tabs:
 *   'listado' => ['label' => 'Todos', 'icon' => '...', 'content' => 'template:_archive.php']
 *
 * O desde un template específico del módulo:
 *   include FLAVOR_PLATFORM_PATH . 'templates/frontend/_archive.php';
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar el Archive Renderer
if (!class_exists('Flavor_Archive_Renderer')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/class-archive-renderer.php';
}

// Detectar el módulo desde variables de Dynamic Pages o parámetro directo
$detected_module = '';

// Prioridad 1: Variable $module_slug si existe (parámetro directo)
if (!empty($module_slug)) {
    $detected_module = $module_slug;
}
// Prioridad 2: Variable $module_id de Dynamic Pages
elseif (!empty($module_id)) {
    $detected_module = str_replace('_', '-', $module_id);
}
// Prioridad 3: Objeto $module de Dynamic Pages
elseif (!empty($module) && is_object($module) && isset($module->id)) {
    $detected_module = str_replace('_', '-', $module->id);
}

if (empty($detected_module)) {
    echo '<div class="flavor-notice flavor-notice-error">';
    echo '<p>' . esc_html__('No se pudo detectar el módulo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '</div>';
    return;
}

$renderer = new Flavor_Archive_Renderer();

// Renderizar automáticamente - obtiene datos de BD y configuración del módulo
echo $renderer->render_auto($detected_module);
