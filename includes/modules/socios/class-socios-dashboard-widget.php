<?php
/**
 * Widget de Dashboard para Socios
 *
 * Muestra estadísticas del módulo de socios en el dashboard unificado:
 * - Estado de membresía
 * - Cuotas pendientes
 * - Próximos vencimientos
 *
 * @package FlavorChatIA
 * @subpackage Modules\Socios
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Socios
 *
 * @since 4.1.0
 */
class Flavor_Socios_Dashboard_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id = 'socios';

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
    protected $icon = 'dashicons-id-alt';

    /**
     * Tamaño del widget
     *
     * @var string
     */
    protected $size = 'small';

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
    protected $priority = 5;

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
        $this->prefix_tabla = $wpdb->prefix . 'flavor_socios_';
        $this->title = __('Mi Membresía', 'flavor-chat-ia');
        $this->description = __('Estado de tu membresía y cuotas', 'flavor-chat-ia');

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

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return [
                'stats' => [],
                'items' => [],
                'empty_state' => __('Inicia sesión para ver tu membresía', 'flavor-chat-ia'),
                'footer' => [],
            ];
        }

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

        $tabla_socios = $this->prefix_tabla . 'socios';
        $tabla_cuotas = $this->prefix_tabla . 'cuotas';
        $tabla_tipos = $this->prefix_tabla . 'tipos';

        $es_admin = is_admin() && !wp_doing_ajax();

        // Obtener datos del socio
        $socio = null;
        if ($this->table_exists($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, t.nombre as tipo_nombre, t.precio as cuota_mensual
                 FROM {$tabla_socios} s
                 LEFT JOIN {$tabla_tipos} t ON s.tipo_socio_id = t.id
                 WHERE s.usuario_id = %d
                 LIMIT 1",
                $user_id
            ));
        }

        // Construir estadísticas
        $stats = [];

        if ($socio) {
            $estado_socio = (string) ($socio->estado ?? '');
            // Stat 1: Estado de membresía
            $estado_color = 'gray';
            $estado_texto = __('Inactivo', 'flavor-chat-ia');
            if ($socio->estado === 'activo') {
                $estado_color = 'success';
                $estado_texto = __('Activo', 'flavor-chat-ia');
            } elseif ($socio->estado === 'pendiente') {
                $estado_color = 'warning';
                $estado_texto = __('Pendiente', 'flavor-chat-ia');
            } elseif ($socio->estado === 'suspendido') {
                $estado_color = 'danger';
                $estado_texto = __('Suspendido', 'flavor-chat-ia');
            }

            $stats[] = [
                'icon' => 'dashicons-yes-alt',
                'valor' => $estado_texto,
                'label' => $socio->tipo_nombre ?: __('Socio', 'flavor-chat-ia'),
                'color' => $estado_color,
                'url' => $es_admin ? admin_url('admin.php?page=socios') : home_url('/mi-portal/socios/mi-perfil/'),
            ];

            // Stat 2: Número de socio
            if (!empty($socio->numero_socio)) {
                $stats[] = [
                    'icon' => 'dashicons-id',
                    'valor' => '#' . $socio->numero_socio,
                    'label' => __('Nº Socio', 'flavor-chat-ia'),
                    'color' => 'primary',
                ];
            }

            // Cuotas pendientes
            $cuotas_pendientes = 0;
            $importe_pendiente = 0.0;
            if ($this->table_exists($tabla_cuotas)) {
                $pendientes = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as total, COALESCE(SUM(importe), 0) as importe
                     FROM {$tabla_cuotas}
                     WHERE socio_id = %d AND estado IN ('pendiente', 'vencida')",
                    $socio->id
                ));

                $cuotas_pendientes = (int) $pendientes->total;
                $importe_pendiente = (float) $pendientes->importe;
            }

            // Stat 3: Cuotas pendientes
            if ($cuotas_pendientes > 0) {
                $stats[] = [
                    'icon' => 'dashicons-warning',
                    'valor' => number_format($importe_pendiente, 2, ',', '.') . ' €',
                    'label' => sprintf(_n('%d cuota', '%d cuotas', $cuotas_pendientes, 'flavor-chat-ia'), $cuotas_pendientes),
                    'color' => 'danger',
                    'url' => $es_admin ? admin_url('admin.php?page=socios&tab=cuotas') : home_url('/mi-portal/socios/mis-cuotas/'),
                ];
            } else {
                $stats[] = [
                    'icon' => 'dashicons-saved',
                    'valor' => __('Al día', 'flavor-chat-ia'),
                    'label' => __('Cuotas', 'flavor-chat-ia'),
                    'color' => 'success',
                ];
            }

            // Stat 4: Fecha alta
            if (!empty($socio->fecha_alta)) {
                $antiguedad = human_time_diff(strtotime($socio->fecha_alta));
                $stats[] = [
                    'icon' => 'dashicons-calendar-alt',
                    'valor' => $antiguedad,
                    'label' => __('Antigüedad', 'flavor-chat-ia'),
                    'color' => 'info',
                ];
            }

            // Items: últimas cuotas
            $items = $this->get_ultimas_cuotas($socio->id, 3);

        } else {
            // Usuario no es socio
            $estado_socio = 'no_registrado';
            $cuotas_pendientes = 0;
            $stats[] = [
                'icon' => 'dashicons-info',
                'valor' => __('No registrado', 'flavor-chat-ia'),
                'label' => __('Membresía', 'flavor-chat-ia'),
                'color' => 'gray',
            ];

            $items = [];
        }

        return [
            'stats' => $stats,
            'items' => $items,
            'summary' => [
                'estado_socio' => $estado_socio,
                'cuotas_pendientes' => (int) ($cuotas_pendientes ?? 0),
            ],
            'empty_state' => $socio ? __('No hay cuotas registradas', 'flavor-chat-ia') : __('Hazte socio para disfrutar de todos los beneficios', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $socio ? __('Ver mi cuenta', 'flavor-chat-ia') : __('Hacerse socio', 'flavor-chat-ia'),
                    'url' => $es_admin ? admin_url('admin.php?page=socios') : home_url('/mi-portal/socios/' . ($socio ? 'mi-perfil/' : 'unirse/')),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene las últimas cuotas del socio
     *
     * @param int $socio_id ID del socio
     * @param int $limite Número máximo de cuotas
     * @return array
     */
    private function get_ultimas_cuotas(int $socio_id, int $limite = 3): array {
        global $wpdb;

        $tabla_cuotas = $this->prefix_tabla . 'cuotas';

        if (!$this->table_exists($tabla_cuotas)) {
            return [];
        }

        $cuotas = $wpdb->get_results($wpdb->prepare(
            "SELECT id, concepto, periodo, importe, estado, fecha_vencimiento
             FROM {$tabla_cuotas}
             WHERE socio_id = %d
             ORDER BY fecha_vencimiento DESC
             LIMIT %d",
            $socio_id,
            $limite
        ));

        $es_admin = is_admin() && !wp_doing_ajax();
        $items = [];

        foreach ($cuotas as $cuota) {
            $icono = 'dashicons-money-alt';
            if ($cuota->estado === 'pagada') {
                $icono = 'dashicons-yes';
            } elseif ($cuota->estado === 'vencida') {
                $icono = 'dashicons-warning';
            }

            $periodo = $cuota->periodo ?: date_i18n('M Y', strtotime($cuota->fecha_vencimiento));

            $items[] = [
                'icon' => $icono,
                'title' => $cuota->concepto ?: $periodo,
                'meta' => number_format($cuota->importe, 2, ',', '.') . ' €',
                'url' => $es_admin ? admin_url('admin.php?page=socios&tab=cuotas&id=' . $cuota->id) : home_url('/mi-portal/socios/mis-cuotas/'),
                'badge' => ucfirst($cuota->estado),
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
     * Devuelve severidad nativa segun el estado del vinculo y cuotas.
     *
     * @param array $data
     * @return array{slug:string,label:string,reason:string}
     */
    private function get_native_severity_payload(array $data): array {
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $estado_socio = (string) ($summary['estado_socio'] ?? '');
        $cuotas_pendientes = (int) ($summary['cuotas_pendientes'] ?? 0);

        if ($estado_socio === 'suspendido' || $cuotas_pendientes > 0) {
            $severity = Flavor_Dashboard_Severity::get_payload('attention');
            $severity['reason'] = __('Tu vínculo de socio requiere atención por suspensión o cuotas pendientes.', 'flavor-chat-ia');
            return $severity;
        }

        if ($estado_socio === 'pendiente') {
            $severity = Flavor_Dashboard_Severity::get_payload('followup');
            $severity['reason'] = __('Tu membresía sigue pendiente de consolidación o revisión.', 'flavor-chat-ia');
            return $severity;
        }

        $severity = Flavor_Dashboard_Severity::get_payload('stable');
        $severity['reason'] = __('Tu membresía está estable y sin alertas económicas inmediatas.', 'flavor-chat-ia');
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
