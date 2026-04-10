<?php
/**
 * Widget de Dashboard para Cursos
 *
 * @package FlavorPlatform
 * @subpackage Modules\Cursos
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_PLATFORM_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

class Flavor_Cursos_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    protected $widget_id = 'cursos';
    protected $icon = 'dashicons-welcome-learn-more';
    protected $size = 'medium';
    protected $category = 'actividades';
    protected $priority = 20;
    private $tabla_cursos;
    private $tabla_matriculas;

    public function __construct() {
        global $wpdb;
        $this->tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $this->tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';
        $this->title = __('Cursos', 'flavor-platform');
        $this->description = __('Formación y aprendizaje continuo', 'flavor-platform');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 300,
            'description' => $this->description,
        ]);
    }

    public function get_widget_data(): array {
        $user_id = get_current_user_id();
        return $this->get_cached_data(function() use ($user_id) {
            return $this->fetch_widget_data($user_id);
        });
    }

    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $es_admin = is_admin() && !wp_doing_ajax();

        $cursos_activos = 0;
        if ($this->table_exists($this->tabla_cursos)) {
            $cursos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tabla_cursos}
                 WHERE estado = 'publicado' AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())"
            );
        }

        $mis_cursos = 0;
        if ($user_id && $this->table_exists($this->tabla_matriculas)) {
            $mis_cursos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado IN ('activa', 'pausada')",
                $user_id
            ));
        }

        $stats = [
            [
                'icon' => 'dashicons-welcome-learn-more',
                'valor' => $cursos_activos,
                'label' => __('Cursos disponibles', 'flavor-platform'),
                'color' => 'primary',
                'url' => $es_admin ? admin_url('admin.php?page=cursos') : Flavor_Platform_Helpers::get_action_url('cursos', ''),
            ],
        ];

        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-book',
                'valor' => $mis_cursos,
                'label' => __('Mis cursos', 'flavor-platform'),
                'color' => $mis_cursos > 0 ? 'success' : 'gray',
                'url' => $es_admin ? admin_url('admin.php?page=cursos&tab=mis-cursos') : Flavor_Platform_Helpers::get_action_url('cursos', 'mis-cursos'),
            ];
        }

        $items = $this->get_cursos_disponibles(4);

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay cursos disponibles actualmente', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Ver catálogo', 'flavor-platform'),
                    'url' => $es_admin ? admin_url('admin.php?page=cursos') : Flavor_Platform_Helpers::get_action_url('cursos', ''),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    private function get_cursos_disponibles(int $limite): array {
        global $wpdb;

        if (!$this->table_exists($this->tabla_cursos)) {
            return [];
        }

        $cursos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, slug, fecha_inicio, plazas_maximas, inscritos_count FROM {$this->tabla_cursos}
             WHERE estado = 'publicado' AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
             ORDER BY fecha_inicio ASC LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($cursos as $curso) {
            $plazas_disponibles = null;
            if ($curso->plazas_maximas > 0) {
                $plazas_disponibles = max(0, $curso->plazas_maximas - ($curso->inscritos_count ?? 0));
            }

            $meta_text = $curso->fecha_inicio ? date_i18n('j M', strtotime($curso->fecha_inicio)) : __('Abierto', 'flavor-platform');
            if ($plazas_disponibles !== null) {
                $meta_text .= ' - ' . sprintf(__('%d plazas', 'flavor-platform'), $plazas_disponibles);
            }

            $items[] = [
                'icon' => 'dashicons-welcome-learn-more',
                'title' => wp_trim_words($curso->titulo, 5, '...'),
                'meta' => $meta_text,
                'url' => $es_admin ? admin_url('admin.php?page=cursos&id=' . $curso->id) : home_url('/cursos/' . $curso->slug . '/'),
            ];
        }

        return $items;
    }

    private function table_exists(string $nombre_tabla): bool {
        global $wpdb;
        static $cache = [];
        if (isset($cache[$nombre_tabla])) {
            return $cache[$nombre_tabla];
        }
        $resultado = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $nombre_tabla));
        $cache[$nombre_tabla] = ($resultado === $nombre_tabla);
        return $cache[$nombre_tabla];
    }

    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
