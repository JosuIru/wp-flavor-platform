<?php
/**
 * Widget de Dashboard para Incidencias
 *
 * Muestra estadísticas del módulo de incidencias en el dashboard unificado:
 * - Mis incidencias abiertas
 * - Estado de seguimiento
 * - Incidencias recientes
 *
 * @package FlavorChatIA
 * @subpackage Modules\Incidencias
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Incidencias
 *
 * @since 4.1.0
 */
class Flavor_Incidencias_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'incidencias';

    /**
     * Título del widget
     *
     * @var string
     */
    protected $title;

    /**
     * Icono del widget
     *
     * @var string
     */
    protected $icon = 'dashicons-flag';

    /**
     * Tamaño del widget
     *
     * @var string
     */
    protected $size = 'medium';

    /**
     * Categoría del widget
     *
     * @var string
     */
    protected $category = 'gestion';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 22;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    private $prefix_tabla;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->prefix_tabla = $wpdb->prefix . 'flavor_incidencias_';
        $this->title = __('Incidencias', 'flavor-chat-ia');
        $this->description = __('Reporta y sigue problemas en tu comunidad', 'flavor-chat-ia');

        parent::__construct([
            'id' => $this->widget_id,
            'title' => $this->title,
            'icon' => $this->icon,
            'size' => $this->size,
            'category' => $this->category,
            'priority' => $this->priority,
            'refreshable' => true,
            'cache_time' => 180,
            'description' => $this->description,
        ]);
    }

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        return $this->get_cached_data(function() use ($user_id) {
            return $this->fetch_widget_data($user_id);
        });
    }

    public function get_widget_config(): array {
        $config = parent::get_widget_config();
        $severity = $this->get_native_severity_payload($this->get_widget_data());

        if (!empty($severity['slug'])) {
            $config['severity_slug'] = $severity['slug'];
            $config['severity_label'] = $severity['label'];
            $config['severity_reason'] = $severity['reason'];
        }

        return $config;
    }

    /**
     * Obtiene los datos frescos del widget
     *
     * @param int $user_id ID del usuario
     * @return array
     */
    private function fetch_widget_data(int $user_id): array {
        global $wpdb;

        $tabla_incidencias = $this->prefix_tabla . 'incidencias';
        $tabla_seguimiento = $this->prefix_tabla . 'seguimiento';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Mis incidencias abiertas
        $mis_abiertas = 0;
        if ($user_id && $this->table_exists($tabla_incidencias)) {
            $mis_abiertas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_incidencias}
                 WHERE usuario_id = %d
                 AND estado NOT IN ('resuelta', 'cerrada', 'rechazada')",
                $user_id
            ));
        }

        // Mis incidencias resueltas
        $mis_resueltas = 0;
        if ($user_id && $this->table_exists($tabla_incidencias)) {
            $mis_resueltas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_incidencias}
                 WHERE usuario_id = %d
                 AND estado = 'resuelta'",
                $user_id
            ));
        }

        // Total incidencias en la comunidad
        $total_abiertas = 0;
        if ($this->table_exists($tabla_incidencias)) {
            $total_abiertas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_incidencias}
                 WHERE estado NOT IN ('resuelta', 'cerrada', 'rechazada')"
            );
        }

        // Actualizaciones recientes en mis incidencias
        $actualizaciones_nuevas = 0;
        if ($user_id && $this->table_exists($tabla_seguimiento) && $this->table_exists($tabla_incidencias)) {
            $actualizaciones_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimiento} s
                 INNER JOIN {$tabla_incidencias} i ON s.incidencia_id = i.id
                 WHERE i.usuario_id = %d
                 AND s.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 AND s.autor_id != %d",
                $user_id,
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        // Stat 1: Mis incidencias abiertas
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-flag',
                'valor' => $mis_abiertas,
                'label' => __('Mis incidencias', 'flavor-chat-ia'),
                'color' => $mis_abiertas > 0 ? 'warning' : 'success',
                'url' => $es_admin ? admin_url('admin.php?page=incidencias&filter=mias') : home_url('/mi-portal/incidencias/mis-incidencias/'),
            ];
        }

        // Stat 2: Actualizaciones
        if ($user_id && $actualizaciones_nuevas > 0) {
            $stats[] = [
                'icon' => 'dashicons-bell',
                'valor' => $actualizaciones_nuevas,
                'label' => __('Actualizaciones', 'flavor-chat-ia'),
                'color' => 'info',
                'url' => $es_admin ? admin_url('admin.php?page=incidencias') : home_url('/mi-portal/incidencias/mis-incidencias/'),
            ];
        }

        // Stat 3: En la comunidad
        $stats[] = [
            'icon' => 'dashicons-location',
            'valor' => $total_abiertas,
            'label' => __('En la comunidad', 'flavor-chat-ia'),
            'color' => 'primary',
            'url' => $es_admin ? admin_url('admin.php?page=incidencias') : home_url('/mi-portal/incidencias/'),
        ];

        // Stat 4: Reportar nueva
        if ($user_id) {
            $stats[] = [
                'icon' => 'dashicons-plus-alt2',
                'valor' => __('Nueva', 'flavor-chat-ia'),
                'label' => __('Reportar', 'flavor-chat-ia'),
                'color' => 'danger',
                'url' => $es_admin ? admin_url('admin.php?page=incidencias&action=nueva') : home_url('/mi-portal/incidencias/reportar/'),
            ];
        }

        // Items: incidencias recientes de la comunidad
        $items = $this->get_incidencias_recientes(5);

        return [
            'stats' => $stats,
            'items' => $items,
            'summary' => [
                'mis_abiertas' => $mis_abiertas,
                'mis_resueltas' => $mis_resueltas,
                'total_abiertas' => $total_abiertas,
                'actualizaciones_nuevas' => $actualizaciones_nuevas,
            ],
            'empty_state' => __('No hay incidencias reportadas recientemente', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver mapa de incidencias', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=incidencias&view=mapa') : home_url('/mi-portal/incidencias/mapa/'),
                    'icon' => 'dashicons-location-alt',
                ],
            ],
        ];
    }

    /**
     * Obtiene las incidencias más recientes
     *
     * @param int $limite Número máximo de incidencias
     * @return array
     */
    private function get_incidencias_recientes(int $limite = 5): array {
        global $wpdb;

        $tabla_incidencias = $this->prefix_tabla . 'incidencias';

        if (!$this->table_exists($tabla_incidencias)) {
            return [];
        }

        $incidencias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, categoria, prioridad, estado, fecha_creacion, direccion
             FROM {$tabla_incidencias}
             WHERE estado NOT IN ('rechazada')
             ORDER BY fecha_creacion DESC
             LIMIT %d",
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($incidencias as $incidencia) {
            // Icono según categoría
            $icono = 'dashicons-flag';
            if (stripos($incidencia->categoria, 'iluminacion') !== false || stripos($incidencia->categoria, 'luz') !== false) {
                $icono = 'dashicons-lightbulb';
            } elseif (stripos($incidencia->categoria, 'basura') !== false || stripos($incidencia->categoria, 'limpieza') !== false) {
                $icono = 'dashicons-trash';
            } elseif (stripos($incidencia->categoria, 'via') !== false || stripos($incidencia->categoria, 'calle') !== false) {
                $icono = 'dashicons-admin-site';
            } elseif (stripos($incidencia->categoria, 'ruido') !== false) {
                $icono = 'dashicons-megaphone';
            }

            // Estado para badge
            $badge = '';
            $estado_map = [
                'pendiente' => __('Pendiente', 'flavor-chat-ia'),
                'en_revision' => __('En revisión', 'flavor-chat-ia'),
                'en_proceso' => __('En proceso', 'flavor-chat-ia'),
                'resuelta' => __('Resuelta', 'flavor-chat-ia'),
            ];
            $badge = $estado_map[$incidencia->estado] ?? ucfirst($incidencia->estado);

            $items[] = [
                'icon' => $icono,
                'title' => wp_trim_words($incidencia->titulo, 5, '...'),
                'meta' => $incidencia->direccion ? wp_trim_words($incidencia->direccion, 4, '...') : human_time_diff(strtotime($incidencia->fecha_creacion)),
                'url' => $es_admin ? admin_url('admin.php?page=incidencias&id=' . $incidencia->id) : home_url('/mi-portal/incidencias/ver/' . $incidencia->id . '/'),
                'badge' => $badge,
            ];
        }

        return $items;
    }

    /**
     * Verifica si una tabla existe
     *
     * @param string $nombre_tabla Nombre de la tabla
     * @return bool
     */
    private function table_exists(string $nombre_tabla): bool {
        global $wpdb;
        static $cache = [];

        if (isset($cache[$nombre_tabla])) {
            return $cache[$nombre_tabla];
        }

        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $nombre_tabla
        ));

        $cache[$nombre_tabla] = ($resultado === $nombre_tabla);
        return $cache[$nombre_tabla];
    }

    /**
     * Devuelve severidad nativa segun el estado real de incidencias.
     *
     * @param array $data
     * @return array{slug:string,label:string,reason:string}
     */
    private function get_native_severity_payload(array $data): array {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $mis_abiertas = (int) ($summary['mis_abiertas'] ?? 0);
        $total_abiertas = (int) ($summary['total_abiertas'] ?? 0);
        $actualizaciones_nuevas = (int) ($summary['actualizaciones_nuevas'] ?? 0);

        if ($mis_abiertas > 0 || $actualizaciones_nuevas > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('attention');
            $severity['reason'] = __('Tienes incidencias abiertas o actualizaciones recientes que requieren atención.', 'flavor-chat-ia');
            return $severity;
        }

        if ($total_abiertas > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('followup');
            $severity['reason'] = __('Hay incidencias comunitarias activas que conviene seguir aunque no te afecten directamente.', 'flavor-chat-ia');
            return $severity;
        }

        $severity = Flavor_Dashboard_Severity::get_payload('stable');
        $severity['reason'] = __('No hay incidencias activas relevantes en este momento.', 'flavor-chat-ia');
        return $severity;
    }

    /**
     * Renderiza el contenido del widget
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }
}
