<?php
/**
 * Cron Manager - Gestión centralizada de tareas programadas
 *
 * Esta clase maneja todos los crons del plugin: E2E, reputación,
 * newsletter, etc.
 *
 * @package FlavorPlatform
 * @subpackage Bootstrap
 * @since 3.2.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona las tareas programadas del plugin
 */
final class Flavor_Cron_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Cron_Manager|null
     */
    private static $instance = null;

    /**
     * Hooks de cron E2E
     *
     * @var array
     */
    private $crons_e2e = [
        'flavor_e2e_rotar_signed_prekeys',
        'flavor_e2e_limpiar_prekeys_usadas',
        'flavor_e2e_limpiar_dispositivos_inactivos',
    ];

    /**
     * Hooks de cron de reputación
     *
     * @var array
     */
    private $crons_reputacion = [
        'flavor_reset_puntos_semanales',
        'flavor_reset_puntos_mensuales',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Cron_Manager
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Vacío - los hooks se registran explícitamente
    }

    /**
     * Registra los hooks necesarios
     *
     * @return void
     */
    public function register_hooks() {
        // Añadir schedules personalizados
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        // Hooks de crons E2E
        add_action('flavor_e2e_rotar_signed_prekeys', [$this, 'cron_rotar_signed_prekeys']);
        add_action('flavor_e2e_limpiar_prekeys_usadas', [$this, 'cron_limpiar_prekeys_usadas']);
        add_action('flavor_e2e_limpiar_dispositivos_inactivos', [$this, 'cron_limpiar_dispositivos_inactivos']);
    }

    /**
     * Programa todos los crons durante la activación
     *
     * @return void
     */
    public function schedule_all() {
        $this->schedule_reputation_crons();
        $this->schedule_e2e_crons();
        $this->schedule_socios_cron();
        $this->schedule_newsletter_cron();
    }

    /**
     * Desprograma todos los crons durante la desactivación
     *
     * @return void
     */
    public function unschedule_all() {
        $this->unschedule_reputation_crons();
        $this->unschedule_e2e_crons();
        $this->unschedule_socios_cron();
        $this->unschedule_newsletter_cron();
        $this->unschedule_activity_log_cron();
        $this->unschedule_module_notifications_cron();
    }

    /**
     * Añade schedules personalizados para crons
     *
     * @param array $schedules Schedules existentes
     * @return array Schedules modificados
     */
    public function add_cron_schedules($schedules) {
        // Weekly (si no existe)
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 604800, // 7 días
                'display'  => __('Una vez a la semana', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Monthly
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = [
                'interval' => 2635200, // 30.5 días aproximadamente
                'display'  => __('Una vez al mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $schedules;
    }

    /**
     * Programa crons de reputación
     *
     * @return void
     */
    private function schedule_reputation_crons() {
        if (!wp_next_scheduled('flavor_reset_puntos_semanales')) {
            wp_schedule_event(strtotime('next monday'), 'weekly', 'flavor_reset_puntos_semanales');
        }

        if (!wp_next_scheduled('flavor_reset_puntos_mensuales')) {
            wp_schedule_event(strtotime('first day of next month'), 'monthly', 'flavor_reset_puntos_mensuales');
        }
    }

    /**
     * Programa crons de E2E (rotación y limpieza de prekeys)
     *
     * @return void
     */
    private function schedule_e2e_crons() {
        if (!wp_next_scheduled('flavor_e2e_rotar_signed_prekeys')) {
            wp_schedule_event(time(), 'daily', 'flavor_e2e_rotar_signed_prekeys');
        }

        if (!wp_next_scheduled('flavor_e2e_limpiar_prekeys_usadas')) {
            wp_schedule_event(time(), 'weekly', 'flavor_e2e_limpiar_prekeys_usadas');
        }

        if (!wp_next_scheduled('flavor_e2e_limpiar_dispositivos_inactivos')) {
            wp_schedule_event(time(), 'monthly', 'flavor_e2e_limpiar_dispositivos_inactivos');
        }
    }

    /**
     * Programa cron de cuotas periódicas de socios
     *
     * @return void
     */
    private function schedule_socios_cron() {
        $ruta_subscriptions = FLAVOR_PLATFORM_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if (file_exists($ruta_subscriptions)) {
            require_once $ruta_subscriptions;
            if (class_exists('Flavor_Socios_Subscriptions')) {
                Flavor_Socios_Subscriptions::programar_cron();
            }
        }
    }

    /**
     * Programa cron de newsletter
     *
     * @return void
     */
    private function schedule_newsletter_cron() {
        if (class_exists('Flavor_Newsletter_Manager')) {
            Flavor_Newsletter_Manager::instalar_tablas();
            Flavor_Newsletter_Manager::programar_cron();
        }
    }

    /**
     * Desprograma crons de reputación
     *
     * @return void
     */
    private function unschedule_reputation_crons() {
        foreach ($this->crons_reputacion as $cron_hook) {
            $timestamp = wp_next_scheduled($cron_hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $cron_hook);
            }
        }
    }

    /**
     * Desprograma crons de E2E
     *
     * @return void
     */
    private function unschedule_e2e_crons() {
        foreach ($this->crons_e2e as $cron_hook) {
            $timestamp = wp_next_scheduled($cron_hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $cron_hook);
            }
        }
    }

    /**
     * Desprograma cron de socios
     *
     * @return void
     */
    private function unschedule_socios_cron() {
        $ruta_subscriptions = FLAVOR_PLATFORM_PATH . 'includes/modules/socios/class-socios-subscriptions.php';
        if (file_exists($ruta_subscriptions)) {
            require_once $ruta_subscriptions;
            if (class_exists('Flavor_Socios_Subscriptions')) {
                Flavor_Socios_Subscriptions::desprogramar_cron();
            }
        }
    }

    /**
     * Desprograma cron de newsletter
     *
     * @return void
     */
    private function unschedule_newsletter_cron() {
        if (class_exists('Flavor_Newsletter_Manager')) {
            Flavor_Newsletter_Manager::desprogramar_cron();
        }
    }

    /**
     * Desprograma cron de registro de actividad
     *
     * @return void
     */
    private function unschedule_activity_log_cron() {
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::desactivar_cron();
        }
    }

    /**
     * Desprograma cron de notificaciones de módulos
     *
     * @return void
     */
    private function unschedule_module_notifications_cron() {
        if (class_exists('Flavor_Module_Notifications')) {
            Flavor_Module_Notifications::desactivar_cron();
        }
    }

    /**
     * Cron: Rotar signed prekeys expiradas
     *
     * Se ejecuta diariamente para renovar prekeys que expiran pronto
     *
     * @return void
     */
    public function cron_rotar_signed_prekeys() {
        $key_manager_path = FLAVOR_PLATFORM_PATH . 'includes/crypto/class-signal-key-manager.php';
        if (!file_exists($key_manager_path)) {
            return;
        }

        require_once $key_manager_path;

        if (!class_exists('Flavor_Signal_Key_Manager')) {
            return;
        }

        $key_manager = new Flavor_Signal_Key_Manager();
        $rotadas = $key_manager->rotar_signed_prekeys_expiradas();

        if ($rotadas > 0) {
            flavor_platform_log(
                sprintf('Cron E2E: Rotadas %d signed prekeys expiradas', $rotadas),
                'info',
                'e2e'
            );
        }
    }

    /**
     * Cron: Limpiar one-time prekeys usadas
     *
     * Se ejecuta semanalmente para eliminar prekeys consumidas
     *
     * @return void
     */
    public function cron_limpiar_prekeys_usadas() {
        $key_manager_path = FLAVOR_PLATFORM_PATH . 'includes/crypto/class-signal-key-manager.php';
        if (!file_exists($key_manager_path)) {
            return;
        }

        require_once $key_manager_path;

        if (!class_exists('Flavor_Signal_Key_Manager')) {
            return;
        }

        $key_manager = new Flavor_Signal_Key_Manager();
        $eliminadas = $key_manager->limpiar_prekeys_antiguas();

        if ($eliminadas > 0) {
            flavor_platform_log(
                sprintf('Cron E2E: Eliminadas %d one-time prekeys usadas', $eliminadas),
                'info',
                'e2e'
            );
        }
    }

    /**
     * Cron: Limpiar dispositivos inactivos
     *
     * Se ejecuta mensualmente para revocar dispositivos sin actividad en 90 días
     *
     * @return void
     */
    public function cron_limpiar_dispositivos_inactivos() {
        $device_manager_path = FLAVOR_PLATFORM_PATH . 'includes/crypto/class-device-manager.php';
        if (!file_exists($device_manager_path)) {
            return;
        }

        require_once $device_manager_path;

        if (!class_exists('Flavor_Device_Manager')) {
            return;
        }

        $device_manager = new Flavor_Device_Manager();
        $revocados = $device_manager->limpiar_dispositivos_inactivos();

        if ($revocados > 0) {
            flavor_platform_log(
                sprintf('Cron E2E: Revocados %d dispositivos inactivos (+90 días)', $revocados),
                'info',
                'e2e'
            );
        }
    }
}
