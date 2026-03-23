<?php
/**
 * Componentes Reutilizables para Dashboards de Módulos
 *
 * Sistema de componentes visuales para crear dashboards
 * atractivos y consistentes en todos los módulos.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dashboard_Components {

    /**
     * Renderizar stat card (tarjeta de estadística)
     *
     * @param array $args Argumentos de configuración
     * @return string HTML
     */
    public static function stat_card($args = []) {
        $defaults = [
            'value' => '0',
            'label' => '',
            'icon' => 'dashicons-chart-line',
            'color' => 'primary', // primary, success, warning, error, info, purple, pink, eco
            'trend' => null, // 'up', 'down', null
            'trend_value' => '',
            'meta' => '',
            'link' => '',
            'highlight' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $class = 'dm-stat-card';
        $class .= ' dm-stat-card--' . esc_attr($args['color']);
        if ($args['highlight']) {
            $class .= ' dm-stat-card--highlight';
        }

        ob_start();
        ?>
        <div class="<?php echo $class; ?>">
            <?php if ($args['icon']): ?>
                <span class="dashicons <?php echo esc_attr($args['icon']); ?> dm-stat-card__icon"></span>
            <?php endif; ?>

            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html($args['value']); ?></div>
                <div class="dm-stat-card__label"><?php echo esc_html($args['label']); ?></div>

                <?php if ($args['trend']): ?>
                    <div class="dm-stat-card__trend dm-stat-card__trend--<?php echo esc_attr($args['trend']); ?>">
                        <span class="dashicons dashicons-arrow-<?php echo $args['trend'] === 'up' ? 'up' : 'down'; ?>-alt"></span>
                        <?php echo esc_html($args['trend_value']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($args['meta']): ?>
                    <div class="dm-stat-card__meta"><?php echo esc_html($args['meta']); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($args['link']): ?>
                <a href="<?php echo esc_url($args['link']); ?>" class="dm-stat-card__link">
                    <span class="screen-reader-text"><?php echo esc_html($args['label']); ?></span>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar grid de stat cards
     *
     * @param array $cards Array de configuraciones de stat_card
     * @param int $columns Número de columnas (2, 3, 4, 5)
     * @return string HTML
     */
    public static function stats_grid($cards, $columns = null) {
        $class = 'dm-stats-grid';
        if ($columns) {
            $class .= ' dm-stats-grid--' . intval($columns);
        }

        ob_start();
        ?>
        <div class="<?php echo $class; ?>">
            <?php foreach ($cards as $card): ?>
                <?php echo self::stat_card($card); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar tabla de datos
     *
     * @param array $args Configuración de tabla
     * @return string HTML
     */
    public static function data_table($args = []) {
        $defaults = [
            'title' => '',
            'icon' => 'dashicons-list-view',
            'columns' => [], // ['key' => 'Label']
            'data' => [],
            'empty_message' => __('No hay datos disponibles', 'flavor-chat-ia'),
            'striped' => true,
            'hoverable' => true,
            'compact' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $table_class = 'dm-table';
        if ($args['striped']) $table_class .= ' dm-table--striped';
        if ($args['hoverable']) $table_class .= ' dm-table--hover';
        if ($args['compact']) $table_class .= ' dm-table--compact';

        ob_start();
        ?>
        <div class="dm-section">
            <?php if ($args['title']): ?>
                <div class="dm-section__header">
                    <?php if ($args['icon']): ?>
                        <span class="dashicons <?php echo esc_attr($args['icon']); ?>"></span>
                    <?php endif; ?>
                    <h3 class="dm-section__title"><?php echo esc_html($args['title']); ?></h3>
                </div>
            <?php endif; ?>

            <?php if (empty($args['data'])): ?>
                <div class="dm-empty-state">
                    <span class="dashicons dashicons-info dm-empty-state__icon"></span>
                    <p class="dm-empty-state__message"><?php echo esc_html($args['empty_message']); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-table-wrapper">
                    <table class="<?php echo $table_class; ?>">
                        <thead>
                            <tr>
                                <?php foreach ($args['columns'] as $key => $label): ?>
                                    <th><?php echo esc_html($label); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($args['data'] as $row): ?>
                                <tr>
                                    <?php foreach (array_keys($args['columns']) as $key): ?>
                                        <td><?php echo isset($row[$key]) ? wp_kses_post($row[$key]) : '—'; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar badge de estado
     *
     * @param string $label Texto del badge
     * @param string $type Tipo: success, warning, error, info, primary
     * @return string HTML
     */
    public static function badge($label, $type = 'primary') {
        return sprintf(
            '<span class="dm-badge dm-badge--%s">%s</span>',
            esc_attr($type),
            esc_html($label)
        );
    }

    /**
     * Renderizar alerta
     *
     * @param string $message Mensaje de la alerta
     * @param string $type Tipo: success, warning, error, info
     * @param bool $dismissible Puede cerrarse
     * @return string HTML
     */
    public static function alert($message, $type = 'info', $dismissible = false) {
        $icons = [
            'success' => 'dashicons-yes-alt',
            'warning' => 'dashicons-warning',
            'error' => 'dashicons-dismiss',
            'info' => 'dashicons-info',
        ];

        $class = 'dm-alert dm-alert--' . esc_attr($type);
        if ($dismissible) {
            $class .= ' dm-alert--dismissible';
        }

        ob_start();
        ?>
        <div class="<?php echo $class; ?>" role="alert">
            <span class="dashicons <?php echo esc_attr($icons[$type] ?? $icons['info']); ?> dm-alert__icon"></span>
            <div class="dm-alert__message"><?php echo wp_kses_post($message); ?></div>
            <?php if ($dismissible): ?>
                <button type="button" class="dm-alert__close" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar progreso
     *
     * @param int $value Valor actual
     * @param int $max Valor máximo
     * @param string $label Etiqueta
     * @param string $color Color: primary, success, warning, error
     * @return string HTML
     */
    public static function progress_bar($value, $max, $label = '', $color = 'primary') {
        $percentage = $max > 0 ? min(100, round(($value / $max) * 100)) : 0;

        ob_start();
        ?>
        <div class="dm-progress">
            <?php if ($label): ?>
                <div class="dm-progress__label">
                    <span><?php echo esc_html($label); ?></span>
                    <span><?php echo esc_html($value . ' / ' . $max); ?></span>
                </div>
            <?php endif; ?>
            <div class="dm-progress__track">
                <div class="dm-progress__bar dm-progress__bar--<?php echo esc_attr($color); ?>"
                     style="width: <?php echo esc_attr($percentage); ?>%"
                     role="progressbar"
                     aria-valuenow="<?php echo esc_attr($value); ?>"
                     aria-valuemin="0"
                     aria-valuemax="<?php echo esc_attr($max); ?>">
                </div>
            </div>
            <div class="dm-progress__percentage"><?php echo esc_html($percentage); ?>%</div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección con header
     *
     * @param string $title Título de la sección
     * @param string $content Contenido HTML
     * @param array $args Argumentos adicionales
     * @return string HTML
     */
    public static function section($title, $content, $args = []) {
        $defaults = [
            'icon' => '',
            'actions' => '', // HTML de botones/acciones
            'collapsible' => false,
            'collapsed' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $section_class = 'dm-section';
        if ($args['collapsible']) {
            $section_class .= ' dm-section--collapsible';
            if ($args['collapsed']) {
                $section_class .= ' dm-section--collapsed';
            }
        }

        ob_start();
        ?>
        <div class="<?php echo $section_class; ?>">
            <div class="dm-section__header">
                <div class="dm-section__header-left">
                    <?php if ($args['icon']): ?>
                        <span class="dashicons <?php echo esc_attr($args['icon']); ?> dm-section__icon"></span>
                    <?php endif; ?>
                    <h3 class="dm-section__title"><?php echo esc_html($title); ?></h3>
                </div>
                <?php if ($args['actions'] || $args['collapsible']): ?>
                    <div class="dm-section__header-right">
                        <?php echo $args['actions']; ?>
                        <?php if ($args['collapsible']): ?>
                            <button type="button" class="dm-section__toggle" aria-label="<?php esc_attr_e('Expandir/Contraer', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="dm-section__content">
                <?php echo $content; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar estado vacío
     *
     * @param string $message Mensaje
     * @param string $icon Icono dashicons
     * @param string $action HTML de botón de acción
     * @return string HTML
     */
    public static function empty_state($message, $icon = 'dashicons-admin-generic', $action = '') {
        ob_start();
        ?>
        <div class="dm-empty-state">
            <span class="dashicons <?php echo esc_attr($icon); ?> dm-empty-state__icon"></span>
            <p class="dm-empty-state__message"><?php echo esc_html($message); ?></p>
            <?php if ($action): ?>
                <div class="dm-empty-state__action"><?php echo $action; ?></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mini chart (sparkline simple con CSS)
     *
     * @param array $values Array de valores numéricos
     * @param string $color Color del gráfico
     * @return string HTML
     */
    public static function mini_chart($values, $color = 'primary') {
        if (empty($values)) {
            return '';
        }

        $max = max($values);
        $min = min($values);
        $range = $max - $min;

        ob_start();
        ?>
        <div class="dm-mini-chart">
            <?php foreach ($values as $value): ?>
                <?php
                $height = $range > 0 ? (($value - $min) / $range) * 100 : 50;
                ?>
                <div class="dm-mini-chart__bar dm-mini-chart__bar--<?php echo esc_attr($color); ?>"
                     style="height: <?php echo esc_attr($height); ?>%"
                     data-value="<?php echo esc_attr($value); ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
