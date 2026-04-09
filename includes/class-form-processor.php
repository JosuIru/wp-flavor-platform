<?php
/**
 * Procesador de Formularios Universal
 *
 * Renderiza formularios, listados y dashboards para módulos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase procesadora de formularios
 */
class Flavor_Form_Processor {

    /**
     * Renderiza un formulario
     *
     * @param string $module_id ID del módulo
     * @param string $action_name Nombre de la acción
     * @param array $config Configuración del formulario
     * @param array $extra_attrs Atributos extra del shortcode
     * @return string HTML del formulario
     */
    public static function render_form($module_id, $action_name, $config, $extra_attrs = []) {
        ob_start();
        ?>
        <div class="flavor-form-container flavor-module-<?php echo esc_attr($module_id); ?>"
             x-data="flavorForm('<?php echo esc_attr($module_id); ?>', '<?php echo esc_attr($action_name); ?>')">

            <?php if (!empty($config['title'])): ?>
                <h2 class="flavor-form-title"><?php echo esc_html($config['title']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($config['description'])): ?>
                <p class="flavor-form-description"><?php echo esc_html($config['description']); ?></p>
            <?php endif; ?>

            <form @submit.prevent="submitForm" class="flavor-form">
                <?php wp_nonce_field('flavor_module_action', 'flavor_nonce'); ?>

                <?php foreach ($config['fields'] as $field_name => $field_config): ?>
                    <?php self::render_field($field_name, $field_config, $extra_attrs); ?>
                <?php endforeach; ?>

                <div class="flavor-form-actions">
                    <button type="submit"
                            class="flavor-btn flavor-btn-primary"
                            :disabled="loading">
                        <span x-show="!loading"><?php echo esc_html($config['submit_text'] ?? __('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                        <span x-show="loading" x-cloak>
                            <svg class="flavor-spinner" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php _e('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Mensajes de respuesta -->
            <div x-show="message"
                 x-cloak
                 :class="success ? 'flavor-message flavor-message-success' : 'flavor-message flavor-message-error'"
                 class="flavor-message"
                 role="alert">
                <p x-text="message"></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un campo individual
     *
     * @param string $name Nombre del campo
     * @param array $config Configuración del campo
     * @param array $extra_attrs Atributos extra
     */
    public static function render_field($name, $config, $extra_attrs = []) {
        $tipo = $config['type'] ?? 'text';
        $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $requerido = $config['required'] ?? false;
        $placeholder = $config['placeholder'] ?? '';
        $descripcion = $config['description'] ?? '';
        $valor_defecto = $extra_attrs[$name] ?? ($config['default'] ?? '');

        // Clases CSS
        $field_classes = 'flavor-form-field flavor-field-' . $tipo;
        if ($requerido) {
            $field_classes .= ' flavor-field-required';
        }

        ?>
        <div class="<?php echo esc_attr($field_classes); ?>">
            <label for="flavor_<?php echo esc_attr($name); ?>" class="flavor-field-label">
                <?php echo esc_html($label); ?>
                <?php if ($requerido): ?>
                    <span class="flavor-required">*</span>
                <?php endif; ?>
            </label>

            <?php
            switch ($tipo) {
                case 'textarea':
                    ?>
                    <textarea
                        id="flavor_<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="flavor-input flavor-textarea"
                        rows="<?php echo esc_attr($config['rows'] ?? 4); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $requerido ? 'required' : ''; ?>><?php echo esc_textarea($valor_defecto); ?></textarea>
                    <?php
                    break;

                case 'select':
                    ?>
                    <select
                        id="flavor_<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="flavor-input flavor-select"
                        <?php echo $requerido ? 'required' : ''; ?>>
                        <?php if (empty($requerido)): ?>
                            <option value=""><?php _e('Selecciona una opción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php endif; ?>
                        <?php foreach ($config['options'] as $valor => $etiqueta): ?>
                            <option value="<?php echo esc_attr($valor); ?>"
                                    <?php selected($valor_defecto, $valor); ?>>
                                <?php echo esc_html($etiqueta); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;

                case 'checkbox':
                    ?>
                    <label class="flavor-checkbox-label">
                        <input
                            type="checkbox"
                            id="flavor_<?php echo esc_attr($name); ?>"
                            name="<?php echo esc_attr($name); ?>"
                            class="flavor-checkbox"
                            value="1"
                            <?php checked($valor_defecto, 1); ?>
                            <?php echo $requerido ? 'required' : ''; ?>>
                        <span><?php echo esc_html($config['checkbox_label'] ?? $label); ?></span>
                    </label>
                    <?php
                    break;

                case 'radio':
                    ?>
                    <div class="flavor-radio-group">
                        <?php foreach ($config['options'] as $valor => $etiqueta): ?>
                            <label class="flavor-radio-label">
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr($name); ?>"
                                    class="flavor-radio"
                                    value="<?php echo esc_attr($valor); ?>"
                                    <?php checked($valor_defecto, $valor); ?>
                                    <?php echo $requerido ? 'required' : ''; ?>>
                                <span><?php echo esc_html($etiqueta); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    break;

                case 'file':
                    ?>
                    <input
                        type="file"
                        id="flavor_<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="flavor-input flavor-file"
                        accept="<?php echo esc_attr($config['accept'] ?? ''); ?>"
                        <?php echo $requerido ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'hidden':
                    ?>
                    <input
                        type="hidden"
                        id="flavor_<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        value="<?php echo esc_attr($valor_defecto); ?>">
                    <?php
                    return; // No mostrar label ni descripción

                default:
                    // text, email, number, date, datetime-local, time, tel, url, etc.
                    $attrs = '';
                    if ($tipo === 'number') {
                        $attrs .= isset($config['min']) ? ' min="' . esc_attr($config['min']) . '"' : '';
                        $attrs .= isset($config['max']) ? ' max="' . esc_attr($config['max']) . '"' : '';
                        $attrs .= isset($config['step']) ? ' step="' . esc_attr($config['step']) . '"' : '';
                    }
                    ?>
                    <input
                        type="<?php echo esc_attr($tipo); ?>"
                        id="flavor_<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="flavor-input"
                        value="<?php echo esc_attr($valor_defecto); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $attrs; ?>
                        <?php echo $requerido ? 'required' : ''; ?>>
                    <?php
                    break;
            }
            ?>

            <?php if (!empty($descripcion)): ?>
                <p class="flavor-field-description"><?php echo esc_html($descripcion); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza un listado de items
     *
     * @param string $module_id ID del módulo
     * @param array $resultado Resultado de la acción
     * @param int $columnas Número de columnas
     * @return string HTML del listado
     */
    public static function render_listing($module_id, $resultado, $columnas = 3) {
        // Detectar qué clave contiene los items (puede variar)
        $items = [];
        $claves_posibles = ['talleres', 'productos', 'eventos', 'items', 'data', 'results'];

        foreach ($claves_posibles as $clave) {
            if (isset($resultado[$clave]) && is_array($resultado[$clave])) {
                $items = $resultado[$clave];
                break;
            }
        }

        if (empty($items)) {
            return '<p class="flavor-no-items">' . __('No hay elementos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $grid_class = 'flavor-grid flavor-grid-cols-' . intval($columnas);

        ob_start();
        ?>
        <div class="flavor-listing flavor-module-<?php echo esc_attr($module_id); ?>">
            <div class="<?php echo esc_attr($grid_class); ?>">
                <?php foreach ($items as $item): ?>
                    <div class="flavor-card">
                        <?php if (!empty($item['imagen'])): ?>
                            <div class="flavor-card-image">
                                <img src="<?php echo esc_url($item['imagen']); ?>"
                                     alt="<?php echo esc_attr($item['titulo'] ?? $item['nombre'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>

                        <div class="flavor-card-content">
                            <?php if (!empty($item['titulo']) || !empty($item['nombre'])): ?>
                                <h3 class="flavor-card-title">
                                    <?php echo esc_html($item['titulo'] ?? $item['nombre']); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if (!empty($item['descripcion'])): ?>
                                <p class="flavor-card-description">
                                    <?php echo esc_html($item['descripcion']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($item['precio'])): ?>
                                <p class="flavor-card-price">
                                    <?php echo number_format($item['precio'], 2); ?> €
                                    <?php if (!empty($item['unidad'])): ?>
                                        / <?php echo esc_html($item['unidad']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($item['categoria'])): ?>
                                <span class="flavor-badge"><?php echo esc_html($item['categoria']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($item['id'])): ?>
                                <a href="?<?php echo esc_attr($module_id); ?>_id=<?php echo intval($item['id']); ?>"
                                   class="flavor-btn flavor-btn-secondary flavor-btn-sm">
                                    <?php _e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el detalle de un item
     *
     * @param string $module_id ID del módulo
     * @param array $resultado Resultado de la acción
     * @return string HTML del detalle
     */
    public static function render_detail($module_id, $resultado) {
        $item = $resultado['data'] ?? $resultado;

        ob_start();
        ?>
        <div class="flavor-detail flavor-module-<?php echo esc_attr($module_id); ?>">
            <?php if (!empty($item['imagen'])): ?>
                <div class="flavor-detail-image">
                    <img src="<?php echo esc_url($item['imagen']); ?>"
                         alt="<?php echo esc_attr($item['titulo'] ?? $item['nombre'] ?? ''); ?>">
                </div>
            <?php endif; ?>

            <div class="flavor-detail-content">
                <?php if (!empty($item['titulo']) || !empty($item['nombre'])): ?>
                    <h1 class="flavor-detail-title">
                        <?php echo esc_html($item['titulo'] ?? $item['nombre']); ?>
                    </h1>
                <?php endif; ?>

                <?php if (!empty($item['descripcion'])): ?>
                    <div class="flavor-detail-description">
                        <?php echo wp_kses_post($item['descripcion']); ?>
                    </div>
                <?php endif; ?>

                <div class="flavor-detail-meta">
                    <?php foreach ($item as $clave => $valor): ?>
                        <?php if (!in_array($clave, ['id', 'titulo', 'nombre', 'descripcion', 'imagen']) && !is_array($valor)): ?>
                            <div class="flavor-meta-item">
                                <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $clave))); ?>:</strong>
                                <?php echo esc_html($valor); ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el dashboard del usuario
     *
     * @param string $module_id ID del módulo
     * @param array $resultado Resultado de la acción
     * @return string HTML del dashboard
     */
    public static function render_dashboard($module_id, $resultado) {
        ob_start();
        ?>
        <div class="flavor-dashboard flavor-module-<?php echo esc_attr($module_id); ?>">
            <h2 class="flavor-dashboard-title">
                <?php printf(__('Mi %s', FLAVOR_PLATFORM_TEXT_DOMAIN), ucfirst($module_id)); ?>
            </h2>

            <?php
            // Renderizar según el tipo de datos
            foreach ($resultado as $seccion => $datos) {
                if ($seccion === 'success' || $seccion === 'error') {
                    continue;
                }

                if (is_array($datos) && !empty($datos)):
                    ?>
                    <div class="flavor-dashboard-section">
                        <h3><?php echo esc_html(ucfirst(str_replace('_', ' ', $seccion))); ?></h3>

                        <?php if (self::is_indexed_array($datos)): ?>
                            <div class="flavor-grid flavor-grid-cols-2">
                                <?php foreach ($datos as $item): ?>
                                    <div class="flavor-card flavor-card-sm">
                                        <?php foreach ($item as $campo => $valor): ?>
                                            <?php if (!is_array($valor)): ?>
                                                <p><strong><?php echo esc_html(ucfirst($campo)); ?>:</strong> <?php echo esc_html($valor); ?></p>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="flavor-stats">
                                <?php foreach ($datos as $stat_nombre => $stat_valor): ?>
                                    <?php if (!is_array($stat_valor)): ?>
                                        <div class="flavor-stat">
                                            <div class="flavor-stat-value"><?php echo esc_html($stat_valor); ?></div>
                                            <div class="flavor-stat-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $stat_nombre))); ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                endif;
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Verifica si un array es indexado (lista) vs asociativo (objeto)
     */
    private static function is_indexed_array($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }
}
