<?php
/**
 * Trait: Componentes UI para Admin de Módulos
 *
 * Proporciona componentes visuales reutilizables para páginas de administración:
 * - render_ui_page_header() - Header con título, botones y estadísticas
 * - render_ui_nav_tabs() - Navegación con tabs y badges
 * - render_info_card() - Tarjetas informativas con diferentes tipos
 * - render_stats_grid() - Grid responsive de estadísticas
 * - render_data_table() - Tablas con estilo mejorado
 * - render_section() - Secciones con título y contenido
 * - render_quick_actions() - Acciones rápidas en grid
 * - render_alert() - Alertas dismissibles
 *
 * Uso:
 *   class Mi_Modulo extends Flavor_Chat_Module_Base {
 *       use Flavor_Module_Admin_UI_Trait;
 *
 *       public function render_admin_dashboard() {
 *           $this->render_ui_page_header('Mi Módulo', $botones, $stats);
 *           $this->render_stats_grid($estadisticas);
 *       }
 *   }
 *
 * @package FlavorPlatform
 * @subpackage Modules
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Module_Admin_UI_Trait {

    /**
     * Renderiza el header de página admin
     *
     * @param string $titulo Título de la página
     * @param array $botones Botones de acción [['label' => '', 'url' => '', 'class' => 'button-primary']]
     * @param array $stats Estadísticas rápidas [['valor' => '', 'label' => '', 'icon' => 'dashicons-xxx']]
     */
    protected function render_ui_page_header(string $titulo, array $botones = [], array $stats = []): void {
        ?>
        <div class="fmod-page-header">
            <div class="fmod-page-header__main">
                <h1 class="fmod-page-header__title">
                    <?php if (!empty($this->icon)): ?>
                        <span class="dashicons <?php echo esc_attr($this->icon ?? 'dashicons-admin-generic'); ?>"></span>
                    <?php endif; ?>
                    <?php echo esc_html($titulo); ?>
                </h1>

                <?php if (!empty($botones)): ?>
                    <div class="fmod-page-header__actions">
                        <?php foreach ($botones as $btn): ?>
                            <a href="<?php echo esc_url($btn['url']); ?>"
                               class="button <?php echo esc_attr($btn['class'] ?? ''); ?>">
                                <?php if (!empty($btn['icon'])): ?>
                                    <span class="dashicons <?php echo esc_attr($btn['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($btn['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($stats)): ?>
                <div class="fmod-page-header__stats">
                    <?php foreach ($stats as $stat): ?>
                        <div class="fmod-stat-card <?php echo !empty($stat['color']) ? 'fmod-stat-card--' . esc_attr($stat['color']) : ''; ?>">
                            <?php if (!empty($stat['icon'])): ?>
                                <span class="fmod-stat-card__icon dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                            <?php endif; ?>
                            <span class="fmod-stat-card__valor"><?php echo esc_html($stat['valor']); ?></span>
                            <span class="fmod-stat-card__label"><?php echo esc_html($stat['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .fmod-page-header{margin-bottom:1.5rem;padding:1.5rem;background:#fff;border:1px solid #ddd;border-radius:8px}
        .fmod-page-header__main{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
        .fmod-page-header__title{margin:0;font-size:1.5rem;display:flex;align-items:center;gap:.5rem}
        .fmod-page-header__title .dashicons{font-size:1.75rem;width:1.75rem;height:1.75rem;color:#2271b1}
        .fmod-page-header__actions{display:flex;gap:.5rem}
        .fmod-page-header__stats{display:flex;gap:1rem;flex-wrap:wrap}
        .fmod-stat-card{display:flex;flex-direction:column;align-items:center;padding:1rem 1.5rem;background:#f9f9f9;border-radius:8px;min-width:100px}
        .fmod-stat-card--green{background:#e8f5e9;color:#2e7d32}
        .fmod-stat-card--blue{background:#e3f2fd;color:#1565c0}
        .fmod-stat-card--orange{background:#fff3e0;color:#e65100}
        .fmod-stat-card--red{background:#ffebee;color:#c62828}
        .fmod-stat-card--purple{background:#f3e5f5;color:#7b1fa2}
        .fmod-stat-card__icon{font-size:1.5rem;width:1.5rem;height:1.5rem;margin-bottom:.25rem}
        .fmod-stat-card__valor{font-size:1.5rem;font-weight:700;line-height:1}
        .fmod-stat-card__label{font-size:.75rem;color:#666;margin-top:.25rem}
        </style>
        <?php
    }

    /**
     * Renderiza tabs de navegación
     *
     * @param array $tabs Tabs [['id' => '', 'label' => '', 'icon' => '']]
     * @param string $current_tab Tab activo
     * @param string $base_url URL base para los tabs
     */
    protected function render_ui_nav_tabs(array $tabs, string $current_tab, string $base_url): void {
        ?>
        <nav class="fmod-nav-tabs">
            <?php foreach ($tabs as $tab): ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab['id'], $base_url)); ?>"
                   class="fmod-nav-tab <?php echo $current_tab === $tab['id'] ? 'fmod-nav-tab--active' : ''; ?>">
                    <?php if (!empty($tab['icon'])): ?>
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                    <?php endif; ?>
                    <?php echo esc_html($tab['label']); ?>
                    <?php if (!empty($tab['badge'])): ?>
                        <span class="fmod-nav-tab__badge"><?php echo esc_html($tab['badge']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <style>
        .fmod-nav-tabs{display:flex;gap:.25rem;margin-bottom:1.5rem;border-bottom:2px solid #ddd;padding-bottom:0}
        .fmod-nav-tab{display:flex;align-items:center;gap:.35rem;padding:.75rem 1rem;color:#555;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
        .fmod-nav-tab:hover{color:#2271b1;background:#f5f5f5}
        .fmod-nav-tab--active{color:#2271b1;border-bottom-color:#2271b1;font-weight:500}
        .fmod-nav-tab .dashicons{font-size:1rem;width:1rem;height:1rem}
        .fmod-nav-tab__badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 .4rem;background:#2271b1;color:#fff;font-size:.7rem;font-weight:600;border-radius:10px}
        </style>
        <?php
    }

    /**
     * Renderiza una tarjeta informativa
     *
     * @param array $config Configuración de la tarjeta
     */
    protected function render_info_card(array $config): void {
        $defaults = [
            'title' => '',
            'description' => '',
            'icon' => 'dashicons-info-outline',
            'type' => 'info', // info, success, warning, error
            'actions' => [],
            'footer' => '',
        ];
        $config = wp_parse_args($config, $defaults);
        ?>
        <div class="fmod-info-card fmod-info-card--<?php echo esc_attr($config['type']); ?>">
            <div class="fmod-info-card__header">
                <span class="fmod-info-card__icon dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                <h3 class="fmod-info-card__title"><?php echo esc_html($config['title']); ?></h3>
            </div>
            <?php if (!empty($config['description'])): ?>
                <p class="fmod-info-card__description"><?php echo esc_html($config['description']); ?></p>
            <?php endif; ?>
            <?php if (!empty($config['content'])): ?>
                <div class="fmod-info-card__content"><?php echo wp_kses_post($config['content']); ?></div>
            <?php endif; ?>
            <?php if (!empty($config['actions'])): ?>
                <div class="fmod-info-card__actions">
                    <?php foreach ($config['actions'] as $action): ?>
                        <a href="<?php echo esc_url($action['url']); ?>"
                           class="button <?php echo esc_attr($action['class'] ?? 'button-secondary'); ?>">
                            <?php echo esc_html($action['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($config['footer'])): ?>
                <div class="fmod-info-card__footer"><?php echo wp_kses_post($config['footer']); ?></div>
            <?php endif; ?>
        </div>

        <style>
        .fmod-info-card{padding:1.25rem;border-radius:8px;margin-bottom:1rem}
        .fmod-info-card--info{background:#e3f2fd;border:1px solid #90caf9}
        .fmod-info-card--success{background:#e8f5e9;border:1px solid #a5d6a7}
        .fmod-info-card--warning{background:#fff3e0;border:1px solid #ffcc80}
        .fmod-info-card--error{background:#ffebee;border:1px solid #ef9a9a}
        .fmod-info-card__header{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
        .fmod-info-card__icon{font-size:1.25rem;width:1.25rem;height:1.25rem}
        .fmod-info-card__title{margin:0;font-size:1rem;font-weight:600}
        .fmod-info-card__description{margin:0 0 .75rem;font-size:.9rem;color:#555}
        .fmod-info-card__content{margin-bottom:.75rem}
        .fmod-info-card__actions{display:flex;gap:.5rem}
        .fmod-info-card__footer{margin-top:.75rem;padding-top:.75rem;border-top:1px solid rgba(0,0,0,.1);font-size:.85rem;color:#666}
        </style>
        <?php
    }

    /**
     * Renderiza una grid de estadísticas
     *
     * @param array $stats Array de estadísticas
     * @param int $columns Número de columnas (2-6)
     */
    protected function render_stats_grid(array $stats, int $columns = 4): void {
        ?>
        <div class="fmod-stats-grid fmod-stats-grid--cols-<?php echo esc_attr(min(6, max(2, $columns))); ?>">
            <?php foreach ($stats as $stat): ?>
                <div class="fmod-stat-box <?php echo !empty($stat['color']) ? 'fmod-stat-box--' . esc_attr($stat['color']) : ''; ?>">
                    <?php if (!empty($stat['icon'])): ?>
                        <span class="fmod-stat-box__icon dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                    <?php endif; ?>
                    <div class="fmod-stat-box__content">
                        <span class="fmod-stat-box__valor"><?php echo esc_html($stat['valor']); ?></span>
                        <span class="fmod-stat-box__label"><?php echo esc_html($stat['label']); ?></span>
                        <?php if (!empty($stat['sublabel'])): ?>
                            <span class="fmod-stat-box__sublabel"><?php echo esc_html($stat['sublabel']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($stat['trend'])): ?>
                        <span class="fmod-stat-box__trend fmod-stat-box__trend--<?php echo $stat['trend'] > 0 ? 'up' : 'down'; ?>">
                            <span class="dashicons dashicons-arrow-<?php echo $stat['trend'] > 0 ? 'up' : 'down'; ?>-alt"></span>
                            <?php echo esc_html(abs($stat['trend'])); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .fmod-stats-grid{display:grid;gap:1rem;margin-bottom:1.5rem}
        .fmod-stats-grid--cols-2{grid-template-columns:repeat(2,1fr)}
        .fmod-stats-grid--cols-3{grid-template-columns:repeat(3,1fr)}
        .fmod-stats-grid--cols-4{grid-template-columns:repeat(4,1fr)}
        .fmod-stats-grid--cols-5{grid-template-columns:repeat(5,1fr)}
        .fmod-stats-grid--cols-6{grid-template-columns:repeat(6,1fr)}
        @media(max-width:1200px){.fmod-stats-grid--cols-5,.fmod-stats-grid--cols-6{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.fmod-stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:600px){.fmod-stats-grid{grid-template-columns:1fr}}
        .fmod-stat-box{display:flex;align-items:center;gap:1rem;padding:1.25rem;background:#fff;border:1px solid #ddd;border-radius:8px}
        .fmod-stat-box--green{border-left:4px solid #4caf50}
        .fmod-stat-box--blue{border-left:4px solid #2196f3}
        .fmod-stat-box--orange{border-left:4px solid #ff9800}
        .fmod-stat-box--red{border-left:4px solid #f44336}
        .fmod-stat-box--purple{border-left:4px solid #9c27b0}
        .fmod-stat-box__icon{font-size:2rem;width:2rem;height:2rem;color:#666}
        .fmod-stat-box--green .fmod-stat-box__icon{color:#4caf50}
        .fmod-stat-box--blue .fmod-stat-box__icon{color:#2196f3}
        .fmod-stat-box--orange .fmod-stat-box__icon{color:#ff9800}
        .fmod-stat-box--red .fmod-stat-box__icon{color:#f44336}
        .fmod-stat-box--purple .fmod-stat-box__icon{color:#9c27b0}
        .fmod-stat-box__content{flex:1}
        .fmod-stat-box__valor{display:block;font-size:1.75rem;font-weight:700;line-height:1.2}
        .fmod-stat-box__label{display:block;font-size:.85rem;color:#666}
        .fmod-stat-box__sublabel{display:block;font-size:.75rem;color:#999}
        .fmod-stat-box__trend{display:flex;align-items:center;gap:.25rem;font-size:.85rem;font-weight:500}
        .fmod-stat-box__trend--up{color:#4caf50}
        .fmod-stat-box__trend--down{color:#f44336}
        .fmod-stat-box__trend .dashicons{font-size:1rem;width:1rem;height:1rem}
        </style>
        <?php
    }

    /**
     * Renderiza tabla con estilo mejorado
     *
     * @param array $headers Cabeceras de la tabla
     * @param array $rows Filas de la tabla
     * @param array $config Configuración adicional
     */
    protected function render_data_table(array $headers, array $rows, array $config = []): void {
        $defaults = [
            'striped' => true,
            'hover' => true,
            'empty_message' => __('No hay datos disponibles', 'flavor-platform'),
            'class' => '',
        ];
        $config = wp_parse_args($config, $defaults);

        $table_class = 'wp-list-table widefat fmod-data-table';
        if ($config['striped']) $table_class .= ' striped';
        if ($config['hover']) $table_class .= ' fmod-data-table--hover';
        if ($config['class']) $table_class .= ' ' . $config['class'];
        ?>
        <table class="<?php echo esc_attr($table_class); ?>">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th <?php echo !empty($header['width']) ? 'style="width:' . esc_attr($header['width']) . '"' : ''; ?>>
                            <?php echo esc_html(is_array($header) ? $header['label'] : $header); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?php echo count($headers); ?>" class="fmod-data-table__empty">
                            <?php echo esc_html($config['empty_message']); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?php echo is_array($cell) ? wp_kses_post($cell['html']) : esc_html($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <style>
        .fmod-data-table{margin-top:0}
        .fmod-data-table--hover tbody tr:hover{background:#f5f5f5}
        .fmod-data-table__empty{text-align:center;padding:2rem!important;color:#666;font-style:italic}
        </style>
        <?php
    }

    /**
     * Renderiza sección con título
     *
     * @param string $title Título de la sección
     * @param string $description Descripción opcional
     * @param callable|null $content_callback Callback para renderizar contenido
     */
    protected function render_section(string $title, string $description = '', ?callable $content_callback = null): void {
        ?>
        <div class="fmod-section">
            <div class="fmod-section__header">
                <h2 class="fmod-section__title"><?php echo esc_html($title); ?></h2>
                <?php if ($description): ?>
                    <p class="fmod-section__description"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
            </div>
            <div class="fmod-section__content">
                <?php
                if ($content_callback && is_callable($content_callback)) {
                    call_user_func($content_callback);
                }
                ?>
            </div>
        </div>

        <style>
        .fmod-section{margin-bottom:2rem}
        .fmod-section__header{margin-bottom:1rem}
        .fmod-section__title{margin:0 0 .25rem;font-size:1.15rem;font-weight:600}
        .fmod-section__description{margin:0;color:#666;font-size:.9rem}
        .fmod-section__content{background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.25rem}
        </style>
        <?php
    }

    /**
     * Renderiza acciones rápidas
     *
     * @param array $actions Lista de acciones
     */
    protected function render_quick_actions(array $actions): void {
        ?>
        <div class="fmod-quick-actions">
            <h3 class="fmod-quick-actions__title"><?php esc_html_e('Acciones rápidas', 'flavor-platform'); ?></h3>
            <div class="fmod-quick-actions__grid">
                <?php foreach ($actions as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>"
                       class="fmod-quick-action <?php echo !empty($action['primary']) ? 'fmod-quick-action--primary' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($action['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                        <span class="fmod-quick-action__label"><?php echo esc_html($action['label']); ?></span>
                        <?php if (!empty($action['description'])): ?>
                            <span class="fmod-quick-action__desc"><?php echo esc_html($action['description']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .fmod-quick-actions{margin-bottom:1.5rem}
        .fmod-quick-actions__title{margin:0 0 .75rem;font-size:1rem;font-weight:600}
        .fmod-quick-actions__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem}
        .fmod-quick-action{display:flex;flex-direction:column;align-items:center;text-align:center;padding:1.25rem;background:#fff;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#333;transition:all .2s}
        .fmod-quick-action:hover{border-color:#2271b1;box-shadow:0 2px 8px rgba(0,0,0,.08)}
        .fmod-quick-action--primary{background:#2271b1;color:#fff;border-color:#2271b1}
        .fmod-quick-action--primary:hover{background:#135e96}
        .fmod-quick-action .dashicons{font-size:1.5rem;width:1.5rem;height:1.5rem;margin-bottom:.5rem}
        .fmod-quick-action__label{font-weight:500;font-size:.9rem}
        .fmod-quick-action__desc{font-size:.75rem;color:#666;margin-top:.25rem}
        .fmod-quick-action--primary .fmod-quick-action__desc{color:rgba(255,255,255,.8)}
        </style>
        <?php
    }

    /**
     * Renderiza una alerta
     *
     * @param string $message Mensaje de la alerta
     * @param string $type Tipo: info, success, warning, error
     * @param bool $dismissible Si es descartable
     */
    protected function render_alert(string $message, string $type = 'info', bool $dismissible = true): void {
        $icons = [
            'info' => 'dashicons-info-outline',
            'success' => 'dashicons-yes-alt',
            'warning' => 'dashicons-warning',
            'error' => 'dashicons-dismiss',
        ];
        ?>
        <div class="fmod-alert fmod-alert--<?php echo esc_attr($type); ?> <?php echo $dismissible ? 'is-dismissible' : ''; ?>">
            <span class="dashicons <?php echo esc_attr($icons[$type] ?? $icons['info']); ?>"></span>
            <p><?php echo wp_kses_post($message); ?></p>
            <?php if ($dismissible): ?>
                <button type="button" class="fmod-alert__dismiss" onclick="this.parentElement.remove()">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
        </div>

        <style>
        .fmod-alert{display:flex;align-items:flex-start;gap:.75rem;padding:1rem;border-radius:8px;margin-bottom:1rem;position:relative}
        .fmod-alert--info{background:#e3f2fd;border:1px solid #90caf9}
        .fmod-alert--success{background:#e8f5e9;border:1px solid #a5d6a7}
        .fmod-alert--warning{background:#fff3e0;border:1px solid #ffcc80}
        .fmod-alert--error{background:#ffebee;border:1px solid #ef9a9a}
        .fmod-alert .dashicons{font-size:1.25rem;width:1.25rem;height:1.25rem;flex-shrink:0}
        .fmod-alert p{margin:0;flex:1;font-size:.9rem}
        .fmod-alert__dismiss{position:absolute;top:.5rem;right:.5rem;background:none;border:none;cursor:pointer;padding:.25rem;opacity:.6}
        .fmod-alert__dismiss:hover{opacity:1}
        .fmod-alert__dismiss .dashicons{font-size:1rem;width:1rem;height:1rem}
        </style>
        <?php
    }
}
