<?php
/**
 * Controlador Frontend - Módulo Empresas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Empresas_Frontend_Controller {

    private $module;

    public function __construct($module) {
        $this->module = $module;
    }

    /**
     * Renderiza la vista apropiada
     */
    public function render($vista = 'dashboard', $atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $user_id = get_current_user_id();
        $empresas_usuario = $this->module->get_empresas_usuario($user_id);

        // Si no pertenece a ninguna empresa
        if (empty($empresas_usuario)) {
            return $this->render_view('sin-empresa', [
                'puede_crear' => $this->module->get_setting('permitir_crear_frontend', true),
            ]);
        }

        // Empresa actual (primera o seleccionada)
        $empresa_id = isset($_GET['empresa']) ? absint($_GET['empresa']) : $empresas_usuario[0]->empresa_id;
        $empresa = $this->module->get_empresa($empresa_id);

        if (!$empresa) {
            return '<div class="flavor-alert flavor-alert-error">' . esc_html__('Empresa no encontrada.', 'flavor-platform') . '</div>';
        }

        // Verificar que el usuario pertenece a esta empresa
        $miembro = $this->module->get_miembro($empresa_id, $user_id);
        if (!$miembro) {
            return '<div class="flavor-alert flavor-alert-error">' . esc_html__('No tienes acceso a esta empresa.', 'flavor-platform') . '</div>';
        }

        $data = [
            'empresa' => $empresa,
            'miembro' => $miembro,
            'empresas_usuario' => $empresas_usuario,
            'es_admin' => $miembro->rol === 'admin',
            'atts' => $atts,
        ];

        switch ($vista) {
            case 'dashboard':
                return $this->render_dashboard($data);
            case 'perfil':
                return $this->render_perfil($data);
            case 'miembros':
                return $this->render_miembros($data);
            case 'documentos':
                return $this->render_documentos($data);
            case 'actividad':
                return $this->render_actividad($data);
            default:
                return $this->render_dashboard($data);
        }
    }

    /**
     * Renderiza el listado público de empresas
     */
    public function render_listado($atts = []) {
        global $wpdb;

        $tabla_empresas = $wpdb->prefix . 'flavor_empresas';

        $empresas = $wpdb->get_results(
            "SELECT * FROM {$tabla_empresas}
             WHERE estado = 'activa' AND visibilidad = 'publica'
             ORDER BY nombre ASC"
        );

        return $this->render_view('listado', [
            'empresas' => $empresas,
            'atts' => $atts,
        ]);
    }

    /**
     * Dashboard de empresa
     */
    private function render_dashboard($data) {
        global $wpdb;

        $empresa_id = $data['empresa']->id;
        $tabla_miembros = $wpdb->prefix . 'flavor_empresas_miembros';
        $tabla_documentos = $wpdb->prefix . 'flavor_empresas_documentos';
        $tabla_actividad = $wpdb->prefix . 'flavor_empresas_actividad';

        // Stats
        $data['stats'] = [
            'miembros_activos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_miembros} WHERE empresa_id = %d AND estado = 'activo'",
                $empresa_id
            )),
            'documentos' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_documentos} WHERE empresa_id = %d",
                $empresa_id
            )),
        ];

        // Actividad reciente
        $data['actividad'] = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name
             FROM {$tabla_actividad} a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.empresa_id = %d
             ORDER BY a.created_at DESC
             LIMIT 10",
            $empresa_id
        ));

        // Miembros recientes
        $data['miembros_recientes'] = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM {$tabla_miembros} m
             JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.empresa_id = %d AND m.estado = 'activo'
             ORDER BY m.fecha_alta DESC
             LIMIT 5",
            $empresa_id
        ));

        return $this->render_view('dashboard', $data);
    }

    /**
     * Perfil de la empresa
     */
    private function render_perfil($data) {
        return $this->render_view('perfil', $data);
    }

    /**
     * Lista de miembros
     */
    private function render_miembros($data) {
        global $wpdb;

        $tabla_miembros = $wpdb->prefix . 'flavor_empresas_miembros';

        $data['miembros'] = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM {$tabla_miembros} m
             JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.empresa_id = %d AND m.estado != 'baja'
             ORDER BY m.rol ASC, u.display_name ASC",
            $data['empresa']->id
        ));

        return $this->render_view('miembros', $data);
    }

    /**
     * Documentos de la empresa
     */
    private function render_documentos($data) {
        global $wpdb;

        $tabla_documentos = $wpdb->prefix . 'flavor_empresas_documentos';

        $data['documentos'] = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as subido_por_nombre
             FROM {$tabla_documentos} d
             LEFT JOIN {$wpdb->users} u ON d.subido_por = u.ID
             WHERE d.empresa_id = %d
             ORDER BY d.created_at DESC",
            $data['empresa']->id
        ));

        return $this->render_view('documentos', $data);
    }

    /**
     * Actividad de la empresa
     */
    private function render_actividad($data) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_empresas_actividad';

        $data['actividad'] = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name
             FROM {$tabla_actividad} a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.empresa_id = %d
             ORDER BY a.created_at DESC
             LIMIT 50",
            $data['empresa']->id
        ));

        return $this->render_view('actividad', $data);
    }

    /**
     * Renderiza una vista
     */
    private function render_view($view, $data = []) {
        extract($data);

        $view_file = dirname(__FILE__) . '/views/' . $view . '.php';

        if (!file_exists($view_file)) {
            return '<div class="flavor-alert flavor-alert-error">' .
                   sprintf(esc_html__('Vista no encontrada: %s', 'flavor-platform'), esc_html($view)) .
                   '</div>';
        }

        ob_start();
        include $view_file;
        return ob_get_clean();
    }

    /**
     * Mensaje de login requerido
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="flavor-card" style="text-align:center;padding:40px;">
            <span class="dashicons dashicons-lock" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
            <h3><?php esc_html_e('Acceso restringido', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('Debes iniciar sesión para acceder a esta sección.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn-primary">
                <?php esc_html_e('Iniciar sesión', 'flavor-platform'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}
