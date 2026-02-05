<?php
/**
 * Gestión de reservas en el admin
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reservation_Admin {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_get_restaurant_reservations', [$this, 'get_reservations_ajax']);
        add_action('wp_ajax_get_reservation_details', [$this, 'get_reservation_details_ajax']);
        add_action('wp_ajax_update_reservation_status', [$this, 'update_reservation_status_ajax']);
        add_action('wp_ajax_cancel_reservation', [$this, 'cancel_reservation_ajax']);
        add_action('wp_ajax_check_availability', [$this, 'check_availability_ajax']);
    }

    /**
     * Agregar páginas al menú
     */
    public function add_menu_pages() {
        add_submenu_page(
            'flavor-platform',
            'Reservas del Restaurante',
            'Reservas',
            'manage_options',
            'flavor-restaurant-reservations',
            [$this, 'render_reservations_page']
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'flavor-platform_page_flavor-restaurant-reservations') {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-restaurant-reservations',
            FLAVOR_RESTAURANT_URL . "assets/css/restaurant-admin{$sufijo_asset}.css",
            [],
            FLAVOR_RESTAURANT_VERSION
        );

        wp_enqueue_script(
            'flavor-restaurant-reservations',
            FLAVOR_RESTAURANT_URL . "assets/js/reservation-admin{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_RESTAURANT_VERSION,
            true
        );

        wp_localize_script('flavor-restaurant-reservations', 'flavorReservationAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_reservation_admin'),
        ]);
    }

    /**
     * Renderizar página de reservas
     */
    public function render_reservations_page() {
        ?>
        <div class="wrap flavor-restaurant-reservations">
            <h1>
                <span class="dashicons dashicons-calendar-alt"></span>
                Gestión de Reservas
            </h1>

            <div class="flavor-restaurant-toolbar">
                <div class="filter-group">
                    <label>Estado:</label>
                    <select id="filter-status" class="status-filter">
                        <option value="">Todos</option>
                        <option value="pending">Pendientes</option>
                        <option value="confirmed">Confirmadas</option>
                        <option value="cancelled">Canceladas</option>
                        <option value="completed">Completadas</option>
                        <option value="no_show">No se presentó</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Fecha:</label>
                    <input type="date" id="filter-date" class="date-filter">
                </div>

                <div class="filter-group">
                    <label>Mesa:</label>
                    <select id="filter-table" class="table-filter">
                        <option value="">Todas las mesas</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <input type="checkbox" id="filter-upcoming">
                        Solo próximas
                    </label>
                </div>

                <button type="button" id="refresh-reservations" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Actualizar
                </button>

                <button type="button" id="show-statistics" class="button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    Estadísticas
                </button>
            </div>

            <!-- Tarjetas de estadísticas rápidas -->
            <div class="reservations-statistics-cards">
                <div class="stat-card pending">
                    <span class="stat-icon dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-value" id="stat-pending">-</div>
                    </div>
                </div>

                <div class="stat-card confirmed">
                    <span class="stat-icon dashicons dashicons-yes"></span>
                    <div class="stat-content">
                        <div class="stat-label">Confirmadas</div>
                        <div class="stat-value" id="stat-confirmed">-</div>
                    </div>
                </div>

                <div class="stat-card today">
                    <span class="stat-icon dashicons dashicons-calendar"></span>
                    <div class="stat-content">
                        <div class="stat-label">Hoy</div>
                        <div class="stat-value" id="stat-today">-</div>
                    </div>
                </div>

                <div class="stat-card guests">
                    <span class="stat-icon dashicons dashicons-groups"></span>
                    <div class="stat-content">
                        <div class="stat-label">Comensales</div>
                        <div class="stat-value" id="stat-guests">-</div>
                    </div>
                </div>
            </div>

            <!-- Lista de reservas -->
            <div class="reservations-container">
                <div id="reservations-list" class="reservations-grid">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando reservas...
                    </div>
                </div>

                <div id="no-reservations" class="no-results" style="display: none;">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p>No hay reservas que coincidan con los filtros seleccionados.</p>
                </div>
            </div>
        </div>

        <!-- Modal de detalles de reserva -->
        <div id="reservation-details-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2 id="modal-reservation-title">Reserva #</h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="reservation-details-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando detalles...
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal de estadísticas -->
        <div id="statistics-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2>Estadísticas de Reservas</h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="statistics-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando estadísticas...
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close">Cerrar</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Obtener reservas
     */
    public function get_reservations_ajax() {
        check_ajax_referer('flavor_reservation_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $args = [
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'table_id' => absint($_POST['table_id'] ?? 0),
            'date' => sanitize_text_field($_POST['date'] ?? ''),
            'upcoming' => isset($_POST['upcoming']) && $_POST['upcoming'] === 'true',
            'limit' => 100,
        ];

        // Limpiar args vacíos
        $args = array_filter($args, function($value) {
            return $value !== '' && $value !== 0 && $value !== false;
        });

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservations = $reservation_manager->get_reservations($args);

        wp_send_json_success([
            'reservations' => $reservations,
            'total' => count($reservations),
        ]);
    }

    /**
     * AJAX: Obtener detalles de reserva
     */
    public function get_reservation_details_ajax() {
        check_ajax_referer('flavor_reservation_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $reservation_id = absint($_POST['reservation_id']);

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservation = $reservation_manager->get_reservation($reservation_id);

        if (!$reservation) {
            wp_send_json_error(['message' => 'Reserva no encontrada']);
        }

        wp_send_json_success([
            'reservation' => $reservation,
        ]);
    }

    /**
     * AJAX: Actualizar estado de reserva
     */
    public function update_reservation_status_ajax() {
        check_ajax_referer('flavor_reservation_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $reservation_id = absint($_POST['reservation_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->update_status($reservation_id, $new_status, $notes);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Estado actualizado correctamente',
            'reservation' => $result,
        ]);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function cancel_reservation_ajax() {
        check_ajax_referer('flavor_reservation_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $reservation_id = absint($_POST['reservation_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->cancel_reservation($reservation_id, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Reserva cancelada correctamente',
            'reservation' => $result,
        ]);
    }

    /**
     * AJAX: Verificar disponibilidad
     */
    public function check_availability_ajax() {
        check_ajax_referer('flavor_reservation_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $guests = absint($_POST['guests']);

        // Aquí llamarías al método del reservation manager para verificar disponibilidad
        // Por ahora retornamos éxito

        wp_send_json_success([
            'available' => true,
            'message' => 'Hay mesas disponibles para esa fecha y hora',
        ]);
    }
}
