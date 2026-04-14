<?php
/**
 * Trait de Componentes para Dashboards de Módulos
 *
 * Proporciona métodos reutilizables para construir dashboards consistentes
 * en todos los módulos de Flavor Platform.
 *
 * @package FlavorPlatform
 * @since 3.5.1
 *
 * USO:
 *
 * class Mi_Modulo_Dashboard_Tab {
 *     use Flavor_Dashboard_Components;
 *
 *     public function render_tab() {
 *         echo $this->dashboard_header('Mi Módulo', 'admin-site', [
 *             ['label' => 'Total', 'value' => 42, 'icon' => 'chart-bar'],
 *         ]);
 *
 *         echo $this->dashboard_section_start('Listado', 'list-view');
 *         // ... contenido
 *         echo $this->dashboard_section_end();
 *     }
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Dashboard_Components {

    /**
     * Renderizar header del dashboard con stats cards
     *
     * @param string $titulo Título del dashboard
     * @param string $icono Icono dashicons (sin "dashicons-")
     * @param array $stats Array de stats: [['label' => '', 'value' => '', 'icon' => '', 'color' => '']]
     * @param array $acciones Botones de acción: [['label' => '', 'url' => '', 'icon' => '', 'primary' => bool]]
     * @return string HTML
     */
    public function dashboard_header($titulo, $icono = 'admin-generic', $stats = [], $acciones = []) {
        ob_start();
        ?>
        <div class="fdc-dashboard">
            <div class="fdc-header">
                <div class="fdc-header__title">
                    <span class="dashicons dashicons-<?php echo esc_attr($icono); ?>"></span>
                    <h2><?php echo esc_html($titulo); ?></h2>
                </div>
                <?php if (!empty($acciones)): ?>
                    <div class="fdc-header__actions">
                        <?php foreach ($acciones as $accion): ?>
                            <a href="<?php echo esc_url($accion['url']); ?>"
                               class="fdc-btn <?php echo !empty($accion['primary']) ? 'fdc-btn--primary' : 'fdc-btn--outline'; ?>">
                                <?php if (!empty($accion['icon'])): ?>
                                    <span class="dashicons dashicons-<?php echo esc_attr($accion['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($accion['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($stats)): ?>
                <div class="fdc-stats-grid">
                    <?php foreach ($stats as $stat): ?>
                        <div class="fdc-stat-card" <?php echo !empty($stat['color']) ? 'style="--stat-color: ' . esc_attr($stat['color']) . '"' : ''; ?>>
                            <?php if (!empty($stat['icon'])): ?>
                                <div class="fdc-stat-card__icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($stat['icon']); ?>"></span>
                                </div>
                            <?php endif; ?>
                            <div class="fdc-stat-card__content">
                                <div class="fdc-stat-card__value"><?php echo esc_html($stat['value']); ?></div>
                                <div class="fdc-stat-card__label"><?php echo esc_html($stat['label']); ?></div>
                            </div>
                            <?php if (!empty($stat['trend'])): ?>
                                <div class="fdc-stat-card__trend fdc-stat-card__trend--<?php echo $stat['trend'] > 0 ? 'up' : 'down'; ?>">
                                    <span class="dashicons dashicons-arrow-<?php echo $stat['trend'] > 0 ? 'up' : 'down'; ?>-alt"></span>
                                    <?php echo esc_html(abs($stat['trend'])); ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Cerrar el dashboard
     *
     * @return string HTML
     */
    public function dashboard_footer() {
        return '</div><!-- .fdc-dashboard -->';
    }

    /**
     * Iniciar una sección del dashboard
     *
     * @param string $titulo Título de la sección
     * @param string $icono Icono dashicons
     * @param array $acciones Acciones de la sección
     * @return string HTML
     */
    public function dashboard_section_start($titulo, $icono = '', $acciones = []) {
        ob_start();
        ?>
        <div class="fdc-section">
            <div class="fdc-section__header">
                <h3 class="fdc-section__title">
                    <?php if ($icono): ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($icono); ?>"></span>
                    <?php endif; ?>
                    <?php echo esc_html($titulo); ?>
                </h3>
                <?php if (!empty($acciones)): ?>
                    <div class="fdc-section__actions">
                        <?php foreach ($acciones as $accion): ?>
                            <a href="<?php echo esc_url($accion['url']); ?>" class="fdc-link">
                                <?php echo esc_html($accion['label']); ?>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="fdc-section__content">
        <?php
        return ob_get_clean();
    }

    /**
     * Cerrar una sección
     *
     * @return string HTML
     */
    public function dashboard_section_end() {
        return '</div></div><!-- .fdc-section -->';
    }

    /**
     * Renderizar estado vacío
     *
     * @param string $titulo Título
     * @param string $mensaje Mensaje descriptivo
     * @param string $icono Icono dashicons
     * @param array $accion Botón de acción: ['label' => '', 'url' => '']
     * @return string HTML
     */
    public function dashboard_empty_state($titulo, $mensaje, $icono = 'marker', $accion = null) {
        ob_start();
        ?>
        <div class="fdc-empty-state">
            <div class="fdc-empty-state__icon">
                <span class="dashicons dashicons-<?php echo esc_attr($icono); ?>"></span>
            </div>
            <h4 class="fdc-empty-state__title"><?php echo esc_html($titulo); ?></h4>
            <p class="fdc-empty-state__message"><?php echo esc_html($mensaje); ?></p>
            <?php if ($accion): ?>
                <a href="<?php echo esc_url($accion['url']); ?>" class="fdc-btn fdc-btn--primary">
                    <?php if (!empty($accion['icon'])): ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($accion['icon']); ?>"></span>
                    <?php endif; ?>
                    <?php echo esc_html($accion['label']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar una lista de items con formato consistente
     *
     * @param array $items Array de items
     * @param callable $render_callback Función para renderizar cada item
     * @param string $empty_message Mensaje si la lista está vacía
     * @return string HTML
     */
    public function dashboard_item_list($items, $render_callback, $empty_message = '') {
        ob_start();

        if (empty($items)) {
            if ($empty_message) {
                echo '<p class="fdc-list-empty">' . esc_html($empty_message) . '</p>';
            }
            return ob_get_clean();
        }

        ?>
        <div class="fdc-item-list">
            <?php foreach ($items as $item): ?>
                <div class="fdc-item">
                    <?php call_user_func($render_callback, $item); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar una card de item genérica
     *
     * @param array $data Datos del item:
     *   - title: Título
     *   - subtitle: Subtítulo opcional
     *   - meta: Array de metadatos [['icon' => '', 'text' => '']]
     *   - badge: Badge opcional ['text' => '', 'type' => 'success|warning|error|info']
     *   - actions: Acciones [['label' => '', 'url' => '', 'ajax' => false]]
     *   - image: URL de imagen opcional
     * @return string HTML
     */
    public function dashboard_item_card($data) {
        ob_start();
        ?>
        <div class="fdc-card">
            <?php if (!empty($data['image'])): ?>
                <div class="fdc-card__image">
                    <img src="<?php echo esc_url($data['image']); ?>" alt="">
                </div>
            <?php endif; ?>

            <div class="fdc-card__body">
                <div class="fdc-card__header">
                    <h4 class="fdc-card__title"><?php echo esc_html($data['title']); ?></h4>
                    <?php if (!empty($data['badge'])): ?>
                        <span class="fdc-badge fdc-badge--<?php echo esc_attr($data['badge']['type'] ?? 'info'); ?>">
                            <?php echo esc_html($data['badge']['text']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($data['subtitle'])): ?>
                    <p class="fdc-card__subtitle"><?php echo esc_html($data['subtitle']); ?></p>
                <?php endif; ?>

                <?php if (!empty($data['meta'])): ?>
                    <div class="fdc-card__meta">
                        <?php foreach ($data['meta'] as $meta_item): ?>
                            <span class="fdc-meta-item">
                                <?php if (!empty($meta_item['icon'])): ?>
                                    <span class="dashicons dashicons-<?php echo esc_attr($meta_item['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($meta_item['text']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($data['actions'])): ?>
                <div class="fdc-card__actions">
                    <?php foreach ($data['actions'] as $action): ?>
                        <?php if (!empty($action['ajax'])): ?>
                            <button type="button"
                                    class="fdc-btn fdc-btn--small fdc-btn--outline"
                                    data-action="<?php echo esc_attr($action['ajax']); ?>"
                                    data-id="<?php echo esc_attr($action['id'] ?? ''); ?>">
                                <?php echo esc_html($action['label']); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($action['url']); ?>"
                               class="fdc-btn fdc-btn--small fdc-btn--outline">
                                <?php echo esc_html($action['label']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar tabs horizontales
     *
     * @param array $tabs Array de tabs: [['id' => '', 'label' => '', 'icon' => '', 'badge' => 0]]
     * @param string $active_tab ID del tab activo
     * @return string HTML
     */
    public function dashboard_tabs($tabs, $active_tab = '') {
        if (empty($active_tab) && !empty($tabs)) {
            $active_tab = $tabs[0]['id'];
        }

        ob_start();
        ?>
        <div class="fdc-tabs" x-data="{ activeTab: '<?php echo esc_attr($active_tab); ?>' }">
            <div class="fdc-tabs__nav" role="tablist">
                <?php foreach ($tabs as $tab): ?>
                    <button type="button"
                            class="fdc-tabs__tab"
                            :class="{ 'fdc-tabs__tab--active': activeTab === '<?php echo esc_attr($tab['id']); ?>' }"
                            @click="activeTab = '<?php echo esc_attr($tab['id']); ?>'"
                            role="tab"
                            :aria-selected="activeTab === '<?php echo esc_attr($tab['id']); ?>'">
                        <?php if (!empty($tab['icon'])): ?>
                            <span class="dashicons dashicons-<?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($tab['label']); ?>
                        <?php if (!empty($tab['badge'])): ?>
                            <span class="fdc-tabs__badge"><?php echo esc_html($tab['badge']); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar contenido de un tab
     *
     * @param string $tab_id ID del tab
     * @return string HTML de apertura
     */
    public function dashboard_tab_content_start($tab_id) {
        ob_start();
        ?>
            <div class="fdc-tabs__panel"
                 x-show="activeTab === '<?php echo esc_attr($tab_id); ?>'"
                 x-cloak
                 role="tabpanel">
        <?php
        return ob_get_clean();
    }

    /**
     * Cerrar contenido de tab
     *
     * @return string HTML
     */
    public function dashboard_tab_content_end() {
        return '</div>';
    }

    /**
     * Cerrar contenedor de tabs
     *
     * @return string HTML
     */
    public function dashboard_tabs_end() {
        return '</div><!-- .fdc-tabs -->';
    }

    /**
     * Renderizar tabla de datos
     *
     * @param array $columns Columnas: [['key' => '', 'label' => '', 'sortable' => false]]
     * @param array $rows Filas de datos
     * @param array $options Opciones: pagination, per_page, empty_message
     * @return string HTML
     */
    public function dashboard_table($columns, $rows, $options = []) {
        $empty_message = $options['empty_message'] ?? __('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN);

        ob_start();
        ?>
        <div class="fdc-table-container">
            <?php if (empty($rows)): ?>
                <p class="fdc-table-empty"><?php echo esc_html($empty_message); ?></p>
            <?php else: ?>
                <table class="fdc-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th class="<?php echo !empty($col['sortable']) ? 'fdc-table__sortable' : ''; ?>">
                                    <?php echo esc_html($col['label']); ?>
                                    <?php if (!empty($col['sortable'])): ?>
                                        <span class="dashicons dashicons-sort"></span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <td data-label="<?php echo esc_attr($col['label']); ?>">
                                        <?php
                                        if (isset($col['render']) && is_callable($col['render'])) {
                                            echo call_user_func($col['render'], $row);
                                        } elseif (isset($row[$col['key']])) {
                                            echo esc_html($row[$col['key']]);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar timeline/historial
     *
     * @param array $items Items: [['date' => '', 'title' => '', 'description' => '', 'icon' => '', 'type' => '']]
     * @return string HTML
     */
    public function dashboard_timeline($items) {
        if (empty($items)) {
            return '';
        }

        ob_start();
        ?>
        <div class="fdc-timeline">
            <?php foreach ($items as $item): ?>
                <div class="fdc-timeline__item fdc-timeline__item--<?php echo esc_attr($item['type'] ?? 'default'); ?>">
                    <div class="fdc-timeline__marker">
                        <span class="dashicons dashicons-<?php echo esc_attr($item['icon'] ?? 'marker'); ?>"></span>
                    </div>
                    <div class="fdc-timeline__content">
                        <div class="fdc-timeline__date">
                            <?php echo esc_html($item['date']); ?>
                        </div>
                        <h5 class="fdc-timeline__title"><?php echo esc_html($item['title']); ?></h5>
                        <?php if (!empty($item['description'])): ?>
                            <p class="fdc-timeline__desc"><?php echo esc_html($item['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar barra de progreso
     *
     * @param int $valor Valor actual
     * @param int $maximo Valor máximo
     * @param string $label Etiqueta
     * @param string $color Color (opcional)
     * @return string HTML
     */
    public function dashboard_progress_bar($valor, $maximo, $label = '', $color = '') {
        $porcentaje = $maximo > 0 ? min(100, ($valor / $maximo) * 100) : 0;

        ob_start();
        ?>
        <div class="fdc-progress" <?php echo $color ? 'style="--progress-color: ' . esc_attr($color) . '"' : ''; ?>>
            <?php if ($label): ?>
                <div class="fdc-progress__header">
                    <span class="fdc-progress__label"><?php echo esc_html($label); ?></span>
                    <span class="fdc-progress__value"><?php echo esc_html($valor); ?>/<?php echo esc_html($maximo); ?></span>
                </div>
            <?php endif; ?>
            <div class="fdc-progress__bar">
                <div class="fdc-progress__fill" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar alerta/notificación
     *
     * @param string $mensaje Mensaje
     * @param string $tipo Tipo: success, warning, error, info
     * @param bool $dismissible Si se puede cerrar
     * @return string HTML
     */
    public function dashboard_alert($mensaje, $tipo = 'info', $dismissible = false) {
        $iconos = [
            'success' => 'yes-alt',
            'warning' => 'warning',
            'error'   => 'dismiss',
            'info'    => 'info',
        ];

        ob_start();
        ?>
        <div class="fdc-alert fdc-alert--<?php echo esc_attr($tipo); ?>" <?php echo $dismissible ? 'x-data="{ show: true }" x-show="show"' : ''; ?>>
            <span class="dashicons dashicons-<?php echo esc_attr($iconos[$tipo] ?? 'info'); ?>"></span>
            <span class="fdc-alert__message"><?php echo esc_html($mensaje); ?></span>
            <?php if ($dismissible): ?>
                <button type="button" class="fdc-alert__close" @click="show = false">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar grid de acciones rápidas
     *
     * @param array $acciones Acciones: [['label' => '', 'url' => '', 'icon' => '', 'description' => '', 'color' => '']]
     * @return string HTML
     */
    public function dashboard_quick_actions($acciones) {
        if (empty($acciones)) {
            return '';
        }

        ob_start();
        ?>
        <div class="fdc-quick-actions">
            <?php foreach ($acciones as $accion): ?>
                <a href="<?php echo esc_url($accion['url']); ?>"
                   class="fdc-quick-action"
                   <?php echo !empty($accion['color']) ? 'style="--action-color: ' . esc_attr($accion['color']) . '"' : ''; ?>>
                    <div class="fdc-quick-action__icon">
                        <span class="dashicons dashicons-<?php echo esc_attr($accion['icon'] ?? 'admin-generic'); ?>"></span>
                    </div>
                    <div class="fdc-quick-action__content">
                        <span class="fdc-quick-action__label"><?php echo esc_html($accion['label']); ?></span>
                        <?php if (!empty($accion['description'])): ?>
                            <span class="fdc-quick-action__desc"><?php echo esc_html($accion['description']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Encolar estilos del sistema de componentes de dashboard
     *
     * Llamar este método desde el hook wp_enqueue_scripts o admin_enqueue_scripts
     *
     * @return void
     */
    protected function enqueue_dashboard_component_styles() {
        wp_enqueue_style(
            'flavor-dashboard-components',
            plugins_url('css/dashboard-components.css', __FILE__),
            [],
            defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '3.5.1'
        );
    }
}
