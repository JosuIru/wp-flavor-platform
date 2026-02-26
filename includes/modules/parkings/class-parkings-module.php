<?php
/**
 * Modulo de Parkings Comunitarios para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Parkings - Gestion completa de plazas de parking comunitarias
 */
class Flavor_Chat_Parkings_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Nombre de tablas
     */
    private $tabla_parkings;
    private $tabla_plazas;
    private $tabla_reservas;
    private $tabla_asignaciones;
    private $tabla_lista_espera;

    /**
     * Estados de plaza
     */
    const ESTADO_PLAZA_LIBRE = 'libre';
    const ESTADO_PLAZA_OCUPADA = 'ocupada';
    const ESTADO_PLAZA_RESERVADA = 'reservada';
    const ESTADO_PLAZA_MANTENIMIENTO = 'mantenimiento';
    const ESTADO_PLAZA_BLOQUEADA = 'bloqueada';

    /**
     * Tipos de reserva
     */
    const TIPO_RESERVA_TEMPORAL = 'temporal';
    const TIPO_RESERVA_ROTACION = 'rotacion';
    const TIPO_RESERVA_EVENTO = 'evento';

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_parkings_obtener_plaza', [$this, 'ajax_obtener_plaza']);
        add_action('wp_ajax_nopriv_parkings_obtener_plaza', [$this, 'ajax_obtener_plaza']);
        add_action('wp_ajax_parkings_reservar_plaza', [$this, 'ajax_reservar_plaza']);
        add_action('wp_ajax_nopriv_parkings_reservar_plaza', [$this, 'ajax_reservar_plaza']);
        add_action('wp_ajax_parkings_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_nopriv_parkings_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_parkings_solicitar_plaza', [$this, 'ajax_solicitar_plaza']);
        add_action('wp_ajax_nopriv_parkings_solicitar_plaza', [$this, 'ajax_solicitar_plaza']);
        add_action('wp_ajax_parkings_cancelar_solicitud', [$this, 'ajax_cancelar_solicitud']);
        add_action('wp_ajax_nopriv_parkings_cancelar_solicitud', [$this, 'ajax_cancelar_solicitud']);
        add_action('wp_ajax_parkings_obtener_disponibilidad', [$this, 'ajax_obtener_disponibilidad']);
        add_action('wp_ajax_nopriv_parkings_obtener_disponibilidad', [$this, 'ajax_obtener_disponibilidad']);
        add_action('wp_ajax_parkings_liberar_plaza', [$this, 'ajax_liberar_plaza']);
        add_action('wp_ajax_nopriv_parkings_liberar_plaza', [$this, 'ajax_liberar_plaza']);
        add_action('wp_ajax_parkings_registrar_entrada', [$this, 'ajax_registrar_entrada']);
        add_action('wp_ajax_nopriv_parkings_registrar_entrada', [$this, 'ajax_registrar_entrada']);
        add_action('wp_ajax_parkings_registrar_salida', [$this, 'ajax_registrar_salida']);
        add_action('wp_ajax_nopriv_parkings_registrar_salida', [$this, 'ajax_registrar_salida']);

        global $wpdb;

        $this->id = 'parkings';
        $this->name = 'Parkings Comunitarios'; // Translation loaded on init
        $this->description = 'Sistema completo de gestion de parkings comunitarios con reservas, asignaciones y lista de espera.'; // Translation loaded on init

        $this->tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        $this->tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
        $this->tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
        $this->tabla_asignaciones = $wpdb->prefix . 'flavor_parkings_asignaciones';
        $this->tabla_lista_espera = $wpdb->prefix . 'flavor_parkings_lista_espera';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_parkings);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Parkings no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_reserva_temporal' => true,
            'permite_rotacion' => true,
            'duracion_minima_reserva_horas' => 1,
            'duracion_maxima_reserva_dias' => 30,
            'anticipacion_maxima_dias' => 90,
            'precio_hora' => 1.50,
            'precio_dia' => 10.00,
            'precio_mes' => 80.00,
            'comision_plataforma' => 10,
            'max_reservas_activas_usuario' => 3,
            'tiempo_gracia_minutos' => 15,
            'penalizacion_no_show' => 5.00,
            'notificar_liberacion' => true,
            'notificar_lista_espera' => true,
            'rotacion_activa' => true,
            'periodo_rotacion_dias' => 30,
            'max_lista_espera' => 50,
            'requiere_matricula' => true,
            'permite_invitados' => false,
            'horario_apertura' => '00:00',
            'horario_cierre' => '23:59',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'registrar_shortcodes']);
        add_action('init', [$this, 'registrar_shortcodes_adicionales']);
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('wp_ajax_flavor_parkings', [$this, 'manejar_ajax']);
        add_action('wp_ajax_nopriv_flavor_parkings', [$this, 'manejar_ajax_publico']);
        add_action('rest_api_init', [$this, 'registrar_rutas_api']);
        add_action('rest_api_init', [$this, 'registrar_endpoint_ocupacion_tiempo_real']);
        add_action('flavor_parkings_check_rotacion', [$this, 'procesar_rotacion_programada']);
        add_action('flavor_parkings_check_reservas_expiradas', [$this, 'procesar_reservas_expiradas']);

        // Registrar eventos de notificacion
        add_action('init', [$this, 'registrar_eventos_notificacion']);

        // Registrar acciones de gamificacion
        add_action('init', [$this, 'registrar_acciones_gamificacion']);

        // Programar tareas cron adicionales
        $this->programar_tareas_cron();

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        if (!wp_next_scheduled('flavor_parkings_check_rotacion')) {
            wp_schedule_event(time(), 'daily', 'flavor_parkings_check_rotacion');
        }
        if (!wp_next_scheduled('flavor_parkings_check_reservas_expiradas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_parkings_check_reservas_expiradas');
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_parkings)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_parkings = "CREATE TABLE IF NOT EXISTS {$this->tabla_parkings} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            direccion varchar(500) NOT NULL,
            codigo_postal varchar(10) DEFAULT NULL,
            latitud decimal(10,7) DEFAULT NULL,
            longitud decimal(10,7) DEFAULT NULL,
            tipo_parking enum('subterraneo','superficie','cubierto','mixto') DEFAULT 'subterraneo',
            total_plazas int(11) DEFAULT 0,
            plazas_residentes int(11) DEFAULT 0,
            plazas_visitantes int(11) DEFAULT 0,
            plazas_movilidad_reducida int(11) DEFAULT 0,
            plazas_carga_electrica int(11) DEFAULT 0,
            horario_apertura time DEFAULT '00:00:00',
            horario_cierre time DEFAULT '23:59:59',
            acceso_24h tinyint(1) DEFAULT 1,
            tipo_acceso enum('mando','tarjeta','codigo','app','mixto') DEFAULT 'mando',
            codigo_acceso_visitantes varchar(50) DEFAULT NULL,
            precio_hora_visitante decimal(10,2) DEFAULT NULL,
            precio_dia_visitante decimal(10,2) DEFAULT NULL,
            cuota_mensual_residente decimal(10,2) DEFAULT NULL,
            permite_rotacion tinyint(1) DEFAULT 1,
            periodo_rotacion_dias int(11) DEFAULT 30,
            permite_subarrendamiento tinyint(1) DEFAULT 0,
            normas text DEFAULT NULL,
            contacto_emergencia varchar(255) DEFAULT NULL,
            telefono_emergencia varchar(20) DEFAULT NULL,
            plano_url varchar(500) DEFAULT NULL,
            fotos text DEFAULT NULL COMMENT 'JSON array URLs',
            configuracion text DEFAULT NULL COMMENT 'JSON config',
            estado enum('activo','inactivo','mantenimiento') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            creado_por bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY estado (estado),
            KEY ubicacion (latitud, longitud)
        ) $charset_collate;";

        $sql_plazas = "CREATE TABLE IF NOT EXISTS {$this->tabla_plazas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) unsigned NOT NULL,
            numero_plaza varchar(20) NOT NULL,
            planta varchar(10) DEFAULT NULL,
            zona varchar(50) DEFAULT NULL,
            fila varchar(10) DEFAULT NULL,
            tipo_plaza enum('normal','grande','pequena','moto','bici','carga_electrica','movilidad_reducida') DEFAULT 'normal',
            propietario_id bigint(20) unsigned DEFAULT NULL,
            asignada_a bigint(20) unsigned DEFAULT NULL,
            ancho_cm int(11) DEFAULT NULL,
            largo_cm int(11) DEFAULT NULL,
            alto_maximo_cm int(11) DEFAULT NULL,
            tiene_columna tinyint(1) DEFAULT 0,
            lado_columna enum('izquierda','derecha','ambos') DEFAULT NULL,
            tiene_cargador tinyint(1) DEFAULT 0,
            tipo_cargador varchar(50) DEFAULT NULL,
            potencia_cargador_kw decimal(5,2) DEFAULT NULL,
            posicion_x int(11) DEFAULT NULL COMMENT 'Coordenada X en plano',
            posicion_y int(11) DEFAULT NULL COMMENT 'Coordenada Y en plano',
            rotacion int(11) DEFAULT 0 COMMENT 'Grados rotacion en plano',
            estado enum('libre','ocupada','reservada','mantenimiento','bloqueada') DEFAULT 'libre',
            disponible_alquiler tinyint(1) DEFAULT 0,
            precio_hora decimal(10,2) DEFAULT NULL,
            precio_dia decimal(10,2) DEFAULT NULL,
            precio_mes decimal(10,2) DEFAULT NULL,
            notas text DEFAULT NULL,
            ultima_ocupacion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY parking_numero (parking_id, numero_plaza),
            KEY parking_id (parking_id),
            KEY propietario_id (propietario_id),
            KEY asignada_a (asignada_a),
            KEY estado (estado),
            KEY tipo_plaza (tipo_plaza)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS {$this->tabla_reservas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            plaza_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_reserva enum('temporal','rotacion','evento','mantenimiento') DEFAULT 'temporal',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            hora_entrada datetime DEFAULT NULL,
            hora_salida datetime DEFAULT NULL,
            matricula_vehiculo varchar(20) DEFAULT NULL,
            marca_vehiculo varchar(100) DEFAULT NULL,
            modelo_vehiculo varchar(100) DEFAULT NULL,
            color_vehiculo varchar(50) DEFAULT NULL,
            precio_total decimal(10,2) DEFAULT 0,
            precio_pagado decimal(10,2) DEFAULT 0,
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_pago varchar(100) DEFAULT NULL,
            codigo_acceso varchar(20) DEFAULT NULL,
            codigo_qr varchar(255) DEFAULT NULL,
            estado enum('pendiente','confirmada','activa','completada','cancelada','no_show') DEFAULT 'pendiente',
            cancelada_por bigint(20) unsigned DEFAULT NULL,
            motivo_cancelacion text DEFAULT NULL,
            fecha_cancelacion datetime DEFAULT NULL,
            penalizacion decimal(10,2) DEFAULT 0,
            notas text DEFAULT NULL,
            recordatorio_enviado tinyint(1) DEFAULT 0,
            valoracion int(11) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            ip_creacion varchar(45) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY plaza_id (plaza_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio),
            KEY fecha_fin (fecha_fin),
            KEY tipo_reserva (tipo_reserva)
        ) $charset_collate;";

        $sql_asignaciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_asignaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            plaza_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_asignacion enum('propietario','inquilino','rotacion','temporal') DEFAULT 'inquilino',
            fecha_inicio date NOT NULL,
            fecha_fin date DEFAULT NULL,
            es_indefinida tinyint(1) DEFAULT 0,
            cuota_mensual decimal(10,2) DEFAULT NULL,
            dia_cobro int(11) DEFAULT 1,
            vehiculos_autorizados text DEFAULT NULL COMMENT 'JSON array matriculas',
            codigo_acceso varchar(50) DEFAULT NULL,
            mando_entregado tinyint(1) DEFAULT 0,
            numero_mando varchar(50) DEFAULT NULL,
            tarjeta_entregada tinyint(1) DEFAULT 0,
            numero_tarjeta varchar(50) DEFAULT NULL,
            contrato_url varchar(500) DEFAULT NULL,
            notas text DEFAULT NULL,
            estado enum('activa','pausada','finalizada','cancelada') DEFAULT 'activa',
            motivo_finalizacion text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            creado_por bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY plaza_id (plaza_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY tipo_asignacion (tipo_asignacion),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_lista_espera = "CREATE TABLE IF NOT EXISTS {$this->tabla_lista_espera} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_plaza_preferida enum('normal','grande','pequena','moto','bici','carga_electrica','movilidad_reducida','cualquiera') DEFAULT 'cualquiera',
            planta_preferida varchar(10) DEFAULT NULL,
            zona_preferida varchar(50) DEFAULT NULL,
            tiene_vehiculo_electrico tinyint(1) DEFAULT 0,
            necesita_movilidad_reducida tinyint(1) DEFAULT 0,
            matricula varchar(20) DEFAULT NULL,
            marca_vehiculo varchar(100) DEFAULT NULL,
            modelo_vehiculo varchar(100) DEFAULT NULL,
            presupuesto_maximo decimal(10,2) DEFAULT NULL,
            urgencia enum('baja','media','alta','urgente') DEFAULT 'media',
            motivo text DEFAULT NULL,
            posicion int(11) DEFAULT NULL,
            notificaciones_enviadas int(11) DEFAULT 0,
            ultima_notificacion datetime DEFAULT NULL,
            ofertas_rechazadas int(11) DEFAULT 0,
            fecha_limite_interes date DEFAULT NULL,
            estado enum('activo','pausado','atendido','cancelado','expirado') DEFAULT 'activo',
            atendido_con_plaza_id bigint(20) unsigned DEFAULT NULL,
            fecha_atencion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY parking_usuario (parking_id, usuario_id),
            KEY parking_id (parking_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY posicion (posicion),
            KEY urgencia (urgencia)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_parkings);
        dbDelta($sql_plazas);
        dbDelta($sql_reservas);
        dbDelta($sql_asignaciones);
        dbDelta($sql_lista_espera);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        add_shortcode('flavor_mapa_parkings', [$this, 'shortcode_mapa_parkings']);
        add_shortcode('flavor_disponibilidad_parking', [$this, 'shortcode_disponibilidad']);
        add_shortcode('flavor_mis_reservas_parking', [$this, 'shortcode_mis_reservas']);
        add_shortcode('flavor_solicitar_plaza', [$this, 'shortcode_solicitar_plaza']);
        add_shortcode('flavor_parking_grid', [$this, 'shortcode_parking_grid']);
        add_shortcode('flavor_parking_stats', [$this, 'shortcode_estadisticas']);
    }

    /**
     * Registrar shortcodes (alias para hooks de WordPress)
     */
    public function registrar_shortcodes() {
        $this->register_shortcodes();
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $plugin_url = plugin_dir_url(dirname(dirname(dirname(__FILE__))));
        $modulo_url = $plugin_url . 'includes/modules/parkings/assets/';

        wp_register_style(
            'flavor-parkings',
            $modulo_url . 'css/parkings.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-parkings',
            $modulo_url . 'js/parkings.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-parkings', 'flavorParkings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor-chat/v1/parkings/'),
            'nonce' => wp_create_nonce('flavor_parkings_nonce'),
            'strings' => [
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'confirmReserva' => __('Confirmar reserva?', 'flavor-chat-ia'),
                'confirmCancelar' => __('Cancelar esta reserva?', 'flavor-chat-ia'),
                'plazaLibre' => __('Libre', 'flavor-chat-ia'),
                'plazaOcupada' => __('Ocupada', 'flavor-chat-ia'),
                'plazaReservada' => __('Reservada', 'flavor-chat-ia'),
                'plazaMantenimiento' => __('Mantenimiento', 'flavor-chat-ia'),
                'reservaExito' => __('Reserva realizada correctamente', 'flavor-chat-ia'),
                'cancelacionExito' => __('Reserva cancelada correctamente', 'flavor-chat-ia'),
            ],
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
        ]);
    }

    /**
     * Shortcode: Mapa de parkings
     */
    public function shortcode_mapa_parkings($atributos) {
        $atributos = shortcode_atts([
            'parking_id' => 0,
            'altura' => 500,
            'mostrar_leyenda' => true,
            'interactivo' => true,
            'mostrar_precios' => true,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $parking = $this->obtener_parking($parking_id);

        if (!$parking) {
            return '<div class="flavor-parkings-error">' . __('Parking no encontrado', 'flavor-chat-ia') . '</div>';
        }

        $plazas = $this->obtener_plazas_parking($parking_id);

        ob_start();
        ?>
        <div class="flavor-parkings-mapa-container"
             data-parking-id="<?php echo esc_attr($parking_id); ?>"
             data-interactivo="<?php echo esc_attr($atributos['interactivo'] ? '1' : '0'); ?>">

            <div class="flavor-parkings-mapa-header">
                <h3><?php echo esc_html($parking->nombre); ?></h3>
                <div class="flavor-parkings-stats-rapidas">
                    <span class="stat libre">
                        <span class="numero"><?php echo $this->contar_plazas_por_estado($parking_id, 'libre'); ?></span>
                        <span class="etiqueta"><?php _e('Libres', 'flavor-chat-ia'); ?></span>
                    </span>
                    <span class="stat ocupada">
                        <span class="numero"><?php echo $this->contar_plazas_por_estado($parking_id, 'ocupada'); ?></span>
                        <span class="etiqueta"><?php _e('Ocupadas', 'flavor-chat-ia'); ?></span>
                    </span>
                    <span class="stat reservada">
                        <span class="numero"><?php echo $this->contar_plazas_por_estado($parking_id, 'reservada'); ?></span>
                        <span class="etiqueta"><?php _e('Reservadas', 'flavor-chat-ia'); ?></span>
                    </span>
                </div>
            </div>

            <div class="flavor-parkings-mapa-visual" style="height: <?php echo intval($atributos['altura']); ?>px;">
                <div class="flavor-parkings-plano">
                    <?php foreach ($plazas as $plaza): ?>
                        <?php
                        $clases_plaza = ['flavor-plaza'];
                        $clases_plaza[] = 'estado-' . $plaza->estado;
                        $clases_plaza[] = 'tipo-' . $plaza->tipo_plaza;

                        $estilo_plaza = '';
                        if ($plaza->posicion_x !== null && $plaza->posicion_y !== null) {
                            $estilo_plaza = sprintf(
                                'left: %dpx; top: %dpx; transform: rotate(%ddeg);',
                                $plaza->posicion_x,
                                $plaza->posicion_y,
                                $plaza->rotacion ?: 0
                            );
                        }
                        ?>
                        <div class="<?php echo esc_attr(implode(' ', $clases_plaza)); ?>"
                             data-plaza-id="<?php echo esc_attr($plaza->id); ?>"
                             data-numero="<?php echo esc_attr($plaza->numero_plaza); ?>"
                             data-estado="<?php echo esc_attr($plaza->estado); ?>"
                             data-tipo="<?php echo esc_attr($plaza->tipo_plaza); ?>"
                             style="<?php echo esc_attr($estilo_plaza); ?>"
                             title="<?php echo esc_attr(sprintf(__('Plaza %s - %s', 'flavor-chat-ia'), $plaza->numero_plaza, ucfirst($plaza->estado))); ?>">
                            <span class="numero-plaza"><?php echo esc_html($plaza->numero_plaza); ?></span>
                            <?php if ($plaza->tiene_cargador): ?>
                                <span class="icono-cargador" title="<?php _e('Cargador electrico', 'flavor-chat-ia'); ?>"></span>
                            <?php endif; ?>
                            <?php if ($plaza->tipo_plaza === 'movilidad_reducida'): ?>
                                <span class="icono-accesible" title="<?php _e('Movilidad reducida', 'flavor-chat-ia'); ?>"></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($atributos['mostrar_leyenda']): ?>
            <div class="flavor-parkings-leyenda">
                <span class="leyenda-item libre"><span class="color"></span> <?php _e('Libre', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item ocupada"><span class="color"></span> <?php _e('Ocupada', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item reservada"><span class="color"></span> <?php _e('Reservada', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item mantenimiento"><span class="color"></span> <?php _e('Mantenimiento', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item cargador"><span class="icono"></span> <?php _e('Cargador', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item accesible"><span class="icono"></span> <?php _e('Accesible', 'flavor-chat-ia'); ?></span>
            </div>
            <?php endif; ?>

            <div class="flavor-parkings-detalle-plaza" style="display: none;">
                <div class="detalle-contenido"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Disponibilidad de parking
     */
    public function shortcode_disponibilidad($atributos) {
        $atributos = shortcode_atts([
            'parking_id' => 0,
            'fecha' => date('Y-m-d'),
            'mostrar_calendario' => true,
            'dias_vista' => 7,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $parking = $this->obtener_parking($parking_id);

        if (!$parking) {
            return '<div class="flavor-parkings-error">' . __('Parking no encontrado', 'flavor-chat-ia') . '</div>';
        }

        $fecha_inicio = $atributos['fecha'];
        $dias_vista = intval($atributos['dias_vista']);
        $disponibilidad = $this->obtener_disponibilidad_rango($parking_id, $fecha_inicio, $dias_vista);

        ob_start();
        ?>
        <div class="flavor-parkings-disponibilidad" data-parking-id="<?php echo esc_attr($parking_id); ?>">
            <div class="disponibilidad-header">
                <h3><?php echo esc_html($parking->nombre); ?> - <?php _e('Disponibilidad', 'flavor-chat-ia'); ?></h3>
                <div class="navegacion-fechas">
                    <button class="btn-fecha-anterior" data-direccion="anterior"><?php echo esc_html__('&larr;', 'flavor-chat-ia'); ?></button>
                    <span class="fecha-actual"><?php echo esc_html(date_i18n('F Y', strtotime($fecha_inicio))); ?></span>
                    <button class="btn-fecha-siguiente" data-direccion="siguiente"><?php echo esc_html__('&rarr;', 'flavor-chat-ia'); ?></button>
                </div>
            </div>

            <?php if ($atributos['mostrar_calendario']): ?>
            <div class="disponibilidad-calendario">
                <div class="calendario-dias">
                    <?php
                    for ($dia_offset = 0; $dia_offset < $dias_vista; $dia_offset++):
                        $fecha_dia = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $dia_offset . ' days'));
                        $datos_dia = $disponibilidad[$fecha_dia] ?? ['libres' => 0, 'total' => 0];
                        $porcentaje_ocupacion = $datos_dia['total'] > 0 ? (($datos_dia['total'] - $datos_dia['libres']) / $datos_dia['total']) * 100 : 0;
                        $clase_ocupacion = $porcentaje_ocupacion >= 90 ? 'alta' : ($porcentaje_ocupacion >= 50 ? 'media' : 'baja');
                    ?>
                    <div class="dia-calendario ocupacion-<?php echo esc_attr($clase_ocupacion); ?>"
                         data-fecha="<?php echo esc_attr($fecha_dia); ?>">
                        <span class="dia-semana"><?php echo esc_html(date_i18n('D', strtotime($fecha_dia))); ?></span>
                        <span class="dia-numero"><?php echo esc_html(date('d', strtotime($fecha_dia))); ?></span>
                        <span class="plazas-libres"><?php echo intval($datos_dia['libres']); ?> <?php _e('libres', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="disponibilidad-resumen">
                <div class="resumen-item">
                    <span class="valor"><?php echo $parking->total_plazas; ?></span>
                    <span class="etiqueta"><?php _e('Total plazas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="valor"><?php echo $this->contar_plazas_por_estado($parking_id, 'libre'); ?></span>
                    <span class="etiqueta"><?php _e('Disponibles ahora', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="valor"><?php echo $this->contar_lista_espera($parking_id); ?></span>
                    <span class="etiqueta"><?php _e('En lista de espera', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="disponibilidad-acciones">
                <?php if (is_user_logged_in()): ?>
                    <button class="btn-reservar-plaza" data-parking-id="<?php echo esc_attr($parking_id); ?>">
                        <?php _e('Reservar plaza', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn-login-reservar">
                        <?php _e('Iniciar sesion para reservar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas
     */
    public function shortcode_mis_reservas($atributos) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-parkings-login-requerido">' .
                   '<p>' . __('Debes iniciar sesion para ver tus reservas.', 'flavor-chat-ia') . '</p>' .
                   '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn-login">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a>' .
                   '</div>';
        }

        $atributos = shortcode_atts([
            'mostrar_historico' => false,
            'limite' => 10,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $usuario_id = get_current_user_id();
        $reservas_activas = $this->obtener_reservas_usuario($usuario_id, ['pendiente', 'confirmada', 'activa']);
        $reservas_pasadas = $atributos['mostrar_historico'] ?
            $this->obtener_reservas_usuario($usuario_id, ['completada', 'cancelada'], intval($atributos['limite'])) : [];

        ob_start();
        ?>
        <div class="flavor-parkings-mis-reservas">
            <h3><?php _e('Mis Reservas de Parking', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($reservas_activas) && empty($reservas_pasadas)): ?>
                <div class="sin-reservas">
                    <p><?php _e('No tienes reservas de parking.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/parkings/')); ?>" class="btn-buscar-parking"><?php _e('Buscar plaza disponible', 'flavor-chat-ia'); ?></a>
                </div>
            <?php else: ?>

                <?php if (!empty($reservas_activas)): ?>
                <div class="reservas-seccion reservas-activas">
                    <h4><?php _e('Reservas activas', 'flavor-chat-ia'); ?></h4>
                    <div class="lista-reservas">
                        <?php foreach ($reservas_activas as $reserva): ?>
                            <?php echo $this->renderizar_tarjeta_reserva($reserva, true); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($reservas_pasadas)): ?>
                <div class="reservas-seccion reservas-historico">
                    <h4><?php _e('Historico', 'flavor-chat-ia'); ?></h4>
                    <div class="lista-reservas">
                        <?php foreach ($reservas_pasadas as $reserva): ?>
                            <?php echo $this->renderizar_tarjeta_reserva($reserva, false); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Solicitar plaza (lista de espera)
     */
    public function shortcode_solicitar_plaza($atributos) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-parkings-login-requerido">' .
                   '<p>' . __('Debes iniciar sesion para solicitar una plaza.', 'flavor-chat-ia') . '</p>' .
                   '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn-login">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a>' .
                   '</div>';
        }

        $atributos = shortcode_atts([
            'parking_id' => 0,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $usuario_id = get_current_user_id();
        $solicitud_existente = $this->obtener_solicitud_espera($parking_id, $usuario_id);

        ob_start();
        ?>
        <div class="flavor-parkings-solicitar-plaza" data-parking-id="<?php echo esc_attr($parking_id); ?>">
            <?php if ($solicitud_existente): ?>
                <div class="solicitud-existente">
                    <h4><?php _e('Ya estas en la lista de espera', 'flavor-chat-ia'); ?></h4>
                    <div class="info-solicitud">
                        <p><strong><?php _e('Posicion:', 'flavor-chat-ia'); ?></strong> #<?php echo intval($solicitud_existente->posicion); ?></p>
                        <p><strong><?php _e('Fecha solicitud:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud_existente->fecha_creacion))); ?></p>
                        <p><strong><?php _e('Estado:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(ucfirst($solicitud_existente->estado)); ?></p>
                    </div>
                    <button class="btn-cancelar-solicitud" data-solicitud-id="<?php echo esc_attr($solicitud_existente->id); ?>">
                        <?php _e('Cancelar solicitud', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="formulario-solicitud">
                    <h4><?php _e('Solicitar plaza de parking', 'flavor-chat-ia'); ?></h4>
                    <form id="form-solicitar-plaza" class="flavor-form">
                        <input type="hidden" name="parking_id" value="<?php echo esc_attr($parking_id); ?>">
                        <input type="hidden" name="action" value="<?php echo esc_attr__('flavor_parkings', 'flavor-chat-ia'); ?>">
                        <input type="hidden" name="subaction" value="<?php echo esc_attr__('solicitar_plaza', 'flavor-chat-ia'); ?>">
                        <?php wp_nonce_field('flavor_parkings_nonce', 'nonce'); ?>

                        <div class="campo-form">
                            <label for="tipo_plaza_preferida"><?php _e('Tipo de plaza preferida', 'flavor-chat-ia'); ?></label>
                            <select name="tipo_plaza_preferida" id="tipo_plaza_preferida">
                                <option value="<?php echo esc_attr__('cualquiera', 'flavor-chat-ia'); ?>"><?php _e('Cualquiera', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('normal', 'flavor-chat-ia'); ?>"><?php _e('Normal', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('grande', 'flavor-chat-ia'); ?>"><?php _e('Grande', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('pequena', 'flavor-chat-ia'); ?>"><?php _e('Pequena', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('moto', 'flavor-chat-ia'); ?>"><?php _e('Moto', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('carga_electrica', 'flavor-chat-ia'); ?>"><?php _e('Con cargador electrico', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('movilidad_reducida', 'flavor-chat-ia'); ?>"><?php _e('Movilidad reducida', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="campo-form">
                            <label for="matricula"><?php _e('Matricula del vehiculo', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="matricula" id="matricula" placeholder="<?php echo esc_attr__('0000 ABC', 'flavor-chat-ia'); ?>" required>
                        </div>

                        <div class="campo-form fila-doble">
                            <div>
                                <label for="marca_vehiculo"><?php _e('Marca', 'flavor-chat-ia'); ?></label>
                                <input type="text" name="marca_vehiculo" id="marca_vehiculo" placeholder="<?php echo esc_attr__('Ej: Toyota', 'flavor-chat-ia'); ?>">
                            </div>
                            <div>
                                <label for="modelo_vehiculo"><?php _e('Modelo', 'flavor-chat-ia'); ?></label>
                                <input type="text" name="modelo_vehiculo" id="modelo_vehiculo" placeholder="<?php echo esc_attr__('Ej: Corolla', 'flavor-chat-ia'); ?>">
                            </div>
                        </div>

                        <div class="campo-form">
                            <label for="urgencia"><?php _e('Urgencia', 'flavor-chat-ia'); ?></label>
                            <select name="urgencia" id="urgencia">
                                <option value="<?php echo esc_attr__('baja', 'flavor-chat-ia'); ?>"><?php _e('Baja - Puedo esperar', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('media', 'flavor-chat-ia'); ?>" selected><?php _e('Media - Normal', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('alta', 'flavor-chat-ia'); ?>"><?php _e('Alta - Lo necesito pronto', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('urgente', 'flavor-chat-ia'); ?>"><?php _e('Urgente - Es prioritario', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="campo-form">
                            <label for="presupuesto_maximo"><?php _e('Presupuesto maximo mensual', 'flavor-chat-ia'); ?></label>
                            <input type="number" name="presupuesto_maximo" id="presupuesto_maximo" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="campo-form">
                            <label for="motivo"><?php _e('Motivo de la solicitud (opcional)', 'flavor-chat-ia'); ?></label>
                            <textarea name="motivo" id="motivo" rows="3" placeholder="<?php _e('Explica brevemente por que necesitas la plaza...', 'flavor-chat-ia'); ?>"></textarea>
                        </div>

                        <div class="campo-form checkbox">
                            <label>
                                <input type="checkbox" name="tiene_vehiculo_electrico" value="1">
                                <?php _e('Tengo vehiculo electrico', 'flavor-chat-ia'); ?>
                            </label>
                        </div>

                        <div class="campo-form checkbox">
                            <label>
                                <input type="checkbox" name="necesita_movilidad_reducida" value="1">
                                <?php _e('Necesito plaza de movilidad reducida', 'flavor-chat-ia'); ?>
                            </label>
                        </div>

                        <div class="acciones-form">
                            <button type="submit" class="btn-enviar-solicitud">
                                <?php _e('Enviar solicitud', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="info-lista-espera">
                <p><?php _e('Actualmente hay', 'flavor-chat-ia'); ?> <strong><?php echo $this->contar_lista_espera($parking_id); ?></strong> <?php _e('personas en lista de espera.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Grid de parkings
     */
    public function shortcode_parking_grid($atributos) {
        $atributos = shortcode_atts([
            'limite' => 6,
            'columnas' => 3,
            'mostrar_disponibilidad' => true,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $parkings = $this->obtener_parkings_activos(intval($atributos['limite']));

        ob_start();
        ?>
        <div class="flavor-parkings-grid columnas-<?php echo intval($atributos['columnas']); ?>">
            <?php foreach ($parkings as $parking): ?>
                <div class="parking-card">
                    <div class="parking-imagen">
                        <?php
                        $fotos = json_decode($parking->fotos, true) ?: [];
                        $foto_principal = !empty($fotos) ? $fotos[0] : '';
                        if ($foto_principal):
                        ?>
                            <img src="<?php echo esc_url($foto_principal); ?>" alt="<?php echo esc_attr($parking->nombre); ?>">
                        <?php else: ?>
                            <div class="sin-imagen">
                                <span class="dashicons dashicons-car"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="parking-info">
                        <h4><?php echo esc_html($parking->nombre); ?></h4>
                        <p class="direccion"><?php echo esc_html($parking->direccion); ?></p>
                        <?php if ($atributos['mostrar_disponibilidad']): ?>
                            <div class="disponibilidad-rapida">
                                <span class="plazas-libres">
                                    <?php echo $this->contar_plazas_por_estado($parking->id, 'libre'); ?>
                                    <?php _e('plazas libres', 'flavor-chat-ia'); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="parking-precios">
                            <?php if ($parking->precio_hora_visitante): ?>
                                <span class="precio"><?php echo number_format($parking->precio_hora_visitante, 2); ?>eur/h</span>
                            <?php endif; ?>
                            <?php if ($parking->cuota_mensual_residente): ?>
                                <span class="precio"><?php echo number_format($parking->cuota_mensual_residente, 2); ?>eur/mes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="parking-acciones">
                        <button type="button" class="btn-ver-parking" data-parking-id="<?php echo esc_attr($parking->id); ?>">
                            <?php _e('Ver disponibilidad', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadisticas
     */
    public function shortcode_estadisticas($atributos) {
        $atributos = shortcode_atts([
            'parking_id' => 0,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $estadisticas = $this->obtener_estadisticas($parking_id);

        ob_start();
        ?>
        <div class="flavor-parkings-estadisticas">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-valor"><?php echo intval($estadisticas['total_plazas']); ?></span>
                    <span class="stat-etiqueta"><?php _e('Total Plazas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo intval($estadisticas['plazas_libres']); ?></span>
                    <span class="stat-etiqueta"><?php _e('Disponibles', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['ocupacion_porcentaje'], 1); ?>%</span>
                    <span class="stat-etiqueta"><?php _e('Ocupacion', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo intval($estadisticas['reservas_mes']); ?></span>
                    <span class="stat-etiqueta"><?php _e('Reservas este mes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo intval($estadisticas['lista_espera']); ?></span>
                    <span class="stat-etiqueta"><?php _e('En lista espera', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['ingresos_mes'], 2); ?>eur</span>
                    <span class="stat-etiqueta"><?php _e('Ingresos mes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Manejar peticiones AJAX (usuarios autenticados)
     */
    public function manejar_ajax() {
        check_ajax_referer('flavor_parkings_nonce', 'nonce');

        $subaccion = sanitize_text_field($_POST['subaction'] ?? '');
        $resultado = ['success' => false, 'message' => __('Accion no valida', 'flavor-chat-ia')];

        switch ($subaccion) {
            case 'obtener_plaza':
                $resultado = $this->ajax_obtener_plaza();
                break;
            case 'reservar_plaza':
                $resultado = $this->ajax_reservar_plaza();
                break;
            case 'cancelar_reserva':
                $resultado = $this->ajax_cancelar_reserva();
                break;
            case 'solicitar_plaza':
                $resultado = $this->ajax_solicitar_plaza();
                break;
            case 'cancelar_solicitud':
                $resultado = $this->ajax_cancelar_solicitud();
                break;
            case 'obtener_disponibilidad':
                $resultado = $this->ajax_obtener_disponibilidad();
                break;
            case 'liberar_plaza':
                $resultado = $this->ajax_liberar_plaza();
                break;
            case 'registrar_entrada':
                $resultado = $this->ajax_registrar_entrada();
                break;
            case 'registrar_salida':
                $resultado = $this->ajax_registrar_salida();
                break;
        }

        wp_send_json($resultado);
    }

    /**
     * Manejar AJAX publico (usuarios no autenticados)
     */
    public function manejar_ajax_publico() {
        $subaccion = sanitize_text_field($_POST['subaction'] ?? '');

        if (in_array($subaccion, ['obtener_plaza', 'obtener_disponibilidad'])) {
            $this->manejar_ajax();
        } else {
            wp_send_json(['success' => false, 'message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener detalle de plaza
     */
    private function ajax_obtener_plaza() {
        $plaza_id = intval($_POST['plaza_id'] ?? 0);
        $plaza = $this->obtener_plaza($plaza_id);

        if (!$plaza) {
            return ['success' => false, 'message' => __('Plaza no encontrada', 'flavor-chat-ia')];
        }

        $parking = $this->obtener_parking($plaza->parking_id);
        $reserva_activa = $this->obtener_reserva_activa_plaza($plaza_id);
        $asignacion = $this->obtener_asignacion_activa_plaza($plaza_id);

        return [
            'success' => true,
            'plaza' => [
                'id' => $plaza->id,
                'numero' => $plaza->numero_plaza,
                'planta' => $plaza->planta,
                'zona' => $plaza->zona,
                'tipo' => $plaza->tipo_plaza,
                'estado' => $plaza->estado,
                'ancho' => $plaza->ancho_cm,
                'largo' => $plaza->largo_cm,
                'alto_maximo' => $plaza->alto_maximo_cm,
                'tiene_cargador' => (bool) $plaza->tiene_cargador,
                'tipo_cargador' => $plaza->tipo_cargador,
                'potencia_cargador' => $plaza->potencia_cargador_kw,
                'tiene_columna' => (bool) $plaza->tiene_columna,
                'lado_columna' => $plaza->lado_columna,
                'disponible_alquiler' => (bool) $plaza->disponible_alquiler,
                'precio_hora' => floatval($plaza->precio_hora),
                'precio_dia' => floatval($plaza->precio_dia),
                'precio_mes' => floatval($plaza->precio_mes),
            ],
            'parking' => [
                'id' => $parking->id,
                'nombre' => $parking->nombre,
                'direccion' => $parking->direccion,
            ],
            'reserva_activa' => $reserva_activa ? [
                'id' => $reserva_activa->id,
                'fecha_inicio' => $reserva_activa->fecha_inicio,
                'fecha_fin' => $reserva_activa->fecha_fin,
                'estado' => $reserva_activa->estado,
            ] : null,
            'tiene_asignacion' => !empty($asignacion),
        ];
    }

    /**
     * AJAX: Reservar plaza
     */
    private function ajax_reservar_plaza() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'message' => __('Debes iniciar sesion', 'flavor-chat-ia')];
        }

        $plaza_id = intval($_POST['plaza_id'] ?? 0);
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($_POST['fecha_fin'] ?? '');
        $matricula = sanitize_text_field($_POST['matricula'] ?? '');

        if (!$plaza_id || !$fecha_inicio || !$fecha_fin) {
            return ['success' => false, 'message' => __('Datos incompletos', 'flavor-chat-ia')];
        }

        $plaza = $this->obtener_plaza($plaza_id);
        if (!$plaza || $plaza->estado !== 'libre') {
            return ['success' => false, 'message' => __('Plaza no disponible', 'flavor-chat-ia')];
        }

        if (!$this->plaza_disponible_rango($plaza_id, $fecha_inicio, $fecha_fin)) {
            return ['success' => false, 'message' => __('La plaza no esta disponible en esas fechas', 'flavor-chat-ia')];
        }

        $configuracion = $this->get_settings();
        $reservas_activas_usuario = $this->contar_reservas_activas_usuario($usuario_id);
        if ($reservas_activas_usuario >= $configuracion['max_reservas_activas_usuario']) {
            return ['success' => false, 'message' => __('Has alcanzado el limite de reservas activas', 'flavor-chat-ia')];
        }

        $precio_total = $this->calcular_precio_reserva($plaza, $fecha_inicio, $fecha_fin);
        $codigo_acceso = $this->generar_codigo_acceso();

        global $wpdb;
        $resultado_insercion = $wpdb->insert($this->tabla_reservas, [
            'plaza_id' => $plaza_id,
            'usuario_id' => $usuario_id,
            'tipo_reserva' => self::TIPO_RESERVA_TEMPORAL,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'matricula_vehiculo' => $matricula,
            'precio_total' => $precio_total,
            'codigo_acceso' => $codigo_acceso,
            'estado' => 'confirmada',
            'ip_creacion' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        if (!$resultado_insercion) {
            return ['success' => false, 'message' => __('Error al crear la reserva', 'flavor-chat-ia')];
        }

        $reserva_id = $wpdb->insert_id;
        $this->actualizar_estado_plaza($plaza_id, 'reservada');
        $this->enviar_notificacion_reserva($reserva_id);

        return [
            'success' => true,
            'message' => __('Reserva realizada correctamente', 'flavor-chat-ia'),
            'reserva_id' => $reserva_id,
            'codigo_acceso' => $codigo_acceso,
            'precio_total' => $precio_total,
        ];
    }

    /**
     * AJAX: Cancelar reserva
     */
    private function ajax_cancelar_reserva() {
        $usuario_id = get_current_user_id();
        $reserva_id = intval($_POST['reserva_id'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva || $reserva->usuario_id != $usuario_id) {
            return ['success' => false, 'message' => __('Reserva no encontrada', 'flavor-chat-ia')];
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            return ['success' => false, 'message' => __('Esta reserva no se puede cancelar', 'flavor-chat-ia')];
        }

        $penalizacion = 0;
        $horas_hasta_inicio = (strtotime($reserva->fecha_inicio) - time()) / 3600;
        if ($horas_hasta_inicio < 24) {
            $configuracion = $this->get_settings();
            $penalizacion = $configuracion['penalizacion_no_show'];
        }

        global $wpdb;
        $wpdb->update($this->tabla_reservas, [
            'estado' => 'cancelada',
            'cancelada_por' => $usuario_id,
            'motivo_cancelacion' => $motivo,
            'fecha_cancelacion' => current_time('mysql'),
            'penalizacion' => $penalizacion,
        ], ['id' => $reserva_id]);

        $this->actualizar_estado_plaza($reserva->plaza_id, 'libre');
        $this->notificar_lista_espera_plaza_disponible($reserva->plaza_id);

        return [
            'success' => true,
            'message' => __('Reserva cancelada', 'flavor-chat-ia'),
            'penalizacion' => $penalizacion,
        ];
    }

    /**
     * AJAX: Solicitar plaza (lista de espera)
     */
    private function ajax_solicitar_plaza() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'message' => __('Debes iniciar sesion', 'flavor-chat-ia')];
        }

        $parking_id = intval($_POST['parking_id'] ?? 0);

        $solicitud_existente = $this->obtener_solicitud_espera($parking_id, $usuario_id);
        if ($solicitud_existente) {
            return ['success' => false, 'message' => __('Ya tienes una solicitud activa', 'flavor-chat-ia')];
        }

        $posicion = $this->contar_lista_espera($parking_id) + 1;

        global $wpdb;
        $resultado_insercion = $wpdb->insert($this->tabla_lista_espera, [
            'parking_id' => $parking_id,
            'usuario_id' => $usuario_id,
            'tipo_plaza_preferida' => sanitize_text_field($_POST['tipo_plaza_preferida'] ?? 'cualquiera'),
            'matricula' => sanitize_text_field($_POST['matricula'] ?? ''),
            'marca_vehiculo' => sanitize_text_field($_POST['marca_vehiculo'] ?? ''),
            'modelo_vehiculo' => sanitize_text_field($_POST['modelo_vehiculo'] ?? ''),
            'urgencia' => sanitize_text_field($_POST['urgencia'] ?? 'media'),
            'presupuesto_maximo' => floatval($_POST['presupuesto_maximo'] ?? 0),
            'motivo' => sanitize_textarea_field($_POST['motivo'] ?? ''),
            'tiene_vehiculo_electrico' => isset($_POST['tiene_vehiculo_electrico']) ? 1 : 0,
            'necesita_movilidad_reducida' => isset($_POST['necesita_movilidad_reducida']) ? 1 : 0,
            'posicion' => $posicion,
            'estado' => 'activo',
        ]);

        if (!$resultado_insercion) {
            return ['success' => false, 'message' => __('Error al registrar la solicitud', 'flavor-chat-ia')];
        }

        return [
            'success' => true,
            'message' => __('Solicitud registrada correctamente', 'flavor-chat-ia'),
            'posicion' => $posicion,
        ];
    }

    /**
     * AJAX: Cancelar solicitud de lista de espera
     */
    private function ajax_cancelar_solicitud() {
        $usuario_id = get_current_user_id();
        $solicitud_id = intval($_POST['solicitud_id'] ?? 0);

        global $wpdb;
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_lista_espera} WHERE id = %d AND usuario_id = %d",
            $solicitud_id,
            $usuario_id
        ));

        if (!$solicitud) {
            return ['success' => false, 'message' => __('Solicitud no encontrada', 'flavor-chat-ia')];
        }

        $wpdb->update($this->tabla_lista_espera, [
            'estado' => 'cancelado',
        ], ['id' => $solicitud_id]);

        $this->reordenar_lista_espera($solicitud->parking_id);

        return ['success' => true, 'message' => __('Solicitud cancelada', 'flavor-chat-ia')];
    }

    /**
     * AJAX: Obtener disponibilidad
     */
    private function ajax_obtener_disponibilidad() {
        $parking_id = intval($_POST['parking_id'] ?? 0);
        $fecha = sanitize_text_field($_POST['fecha'] ?? date('Y-m-d'));
        $dias = intval($_POST['dias'] ?? 7);

        $disponibilidad = $this->obtener_disponibilidad_rango($parking_id, $fecha, $dias);

        return [
            'success' => true,
            'disponibilidad' => $disponibilidad,
        ];
    }

    /**
     * AJAX: Liberar plaza
     */
    private function ajax_liberar_plaza() {
        $usuario_id = get_current_user_id();
        $reserva_id = intval($_POST['reserva_id'] ?? 0);

        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva || $reserva->usuario_id != $usuario_id) {
            return ['success' => false, 'message' => __('Reserva no encontrada', 'flavor-chat-ia')];
        }

        if ($reserva->estado !== 'activa') {
            return ['success' => false, 'message' => __('La reserva no esta activa', 'flavor-chat-ia')];
        }

        global $wpdb;
        $wpdb->update($this->tabla_reservas, [
            'estado' => 'completada',
            'hora_salida' => current_time('mysql'),
        ], ['id' => $reserva_id]);

        $this->actualizar_estado_plaza($reserva->plaza_id, 'libre');
        $this->notificar_lista_espera_plaza_disponible($reserva->plaza_id);

        return ['success' => true, 'message' => __('Plaza liberada correctamente', 'flavor-chat-ia')];
    }

    /**
     * AJAX: Registrar entrada
     */
    private function ajax_registrar_entrada() {
        $usuario_id = get_current_user_id();
        $reserva_id = intval($_POST['reserva_id'] ?? 0);

        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva || $reserva->usuario_id != $usuario_id) {
            return ['success' => false, 'message' => __('Reserva no encontrada', 'flavor-chat-ia')];
        }

        if ($reserva->estado !== 'confirmada') {
            return ['success' => false, 'message' => __('La reserva no esta confirmada', 'flavor-chat-ia')];
        }

        global $wpdb;
        $wpdb->update($this->tabla_reservas, [
            'estado' => 'activa',
            'hora_entrada' => current_time('mysql'),
        ], ['id' => $reserva_id]);

        $this->actualizar_estado_plaza($reserva->plaza_id, 'ocupada');

        return ['success' => true, 'message' => __('Entrada registrada', 'flavor-chat-ia')];
    }

    /**
     * AJAX: Registrar salida
     */
    private function ajax_registrar_salida() {
        return $this->ajax_liberar_plaza();
    }

    /**
     * Registrar rutas REST API
     */
    public function registrar_rutas_api() {
        register_rest_route('flavor-chat/v1', '/parkings', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_parkings'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_parking'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)/plazas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_plazas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)/disponibilidad', [
            'methods' => 'GET',
            'callback' => [$this, 'api_disponibilidad'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/reservas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_reserva'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/reservas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_reserva'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/reservas/(?P<id>\d+)/cancelar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_cancelar_reserva'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/lista-espera', [
            'methods' => 'POST',
            'callback' => [$this, 'api_apuntarse_espera'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/mis-reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_reservas'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);
    }

    /**
     * Verificar autenticacion API
     */
    public function verificar_autenticacion_api() {
        return is_user_logged_in();
    }

    /**
     * API: Listar parkings
     */
    public function api_listar_parkings($request) {
        $limite = intval($request->get_param('limite') ?? 20);
        $pagina = intval($request->get_param('pagina') ?? 1);
        $offset = ($pagina - 1) * $limite;

        global $wpdb;
        $parkings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_parkings} WHERE estado = 'activo' ORDER BY nombre ASC LIMIT %d OFFSET %d",
            $limite,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parkings} WHERE estado = 'activo'");

        return new WP_REST_Response([
            'parkings' => array_map([$this, 'formatear_parking_api'], $parkings),
            'total' => intval($total),
            'pagina' => $pagina,
            'paginas_totales' => ceil($total / $limite),
        ], 200);
    }

    /**
     * API: Obtener parking
     */
    public function api_obtener_parking($request) {
        $parking_id = intval($request['id']);
        $parking = $this->obtener_parking($parking_id);

        if (!$parking) {
            return new WP_REST_Response(['error' => __('Parking no encontrado', 'flavor-chat-ia')], 404);
        }

        return new WP_REST_Response($this->formatear_parking_api($parking, true), 200);
    }

    /**
     * API: Listar plazas de un parking
     */
    public function api_listar_plazas($request) {
        $parking_id = intval($request['id']);
        $plazas = $this->obtener_plazas_parking($parking_id);

        return new WP_REST_Response([
            'plazas' => array_map([$this, 'formatear_plaza_api'], $plazas),
        ], 200);
    }

    /**
     * API: Disponibilidad
     */
    public function api_disponibilidad($request) {
        $parking_id = intval($request['id']);
        $fecha = sanitize_text_field($request->get_param('fecha') ?? date('Y-m-d'));
        $dias = intval($request->get_param('dias') ?? 7);

        $disponibilidad = $this->obtener_disponibilidad_rango($parking_id, $fecha, $dias);

        return new WP_REST_Response([
            'parking_id' => $parking_id,
            'fecha_inicio' => $fecha,
            'dias' => $dias,
            'disponibilidad' => $disponibilidad,
        ], 200);
    }

    /**
     * API: Crear reserva
     */
    public function api_crear_reserva($request) {
        $datos = $request->get_json_params();

        $_POST = array_merge($_POST, $datos);
        $_POST['subaction'] = 'reservar_plaza';

        $resultado = $this->ajax_reservar_plaza();

        $codigo_estado = $resultado['success'] ? 201 : 400;
        return new WP_REST_Response($resultado, $codigo_estado);
    }

    /**
     * API: Obtener reserva
     */
    public function api_obtener_reserva($request) {
        $reserva_id = intval($request['id']);
        $reserva = $this->obtener_reserva($reserva_id);

        if (!$reserva || $reserva->usuario_id != get_current_user_id()) {
            return new WP_REST_Response(['error' => __('Reserva no encontrada', 'flavor-chat-ia')], 404);
        }

        return new WP_REST_Response($this->formatear_reserva_api($reserva), 200);
    }

    /**
     * API: Cancelar reserva
     */
    public function api_cancelar_reserva($request) {
        $_POST['reserva_id'] = intval($request['id']);
        $_POST['motivo'] = sanitize_textarea_field($request->get_param('motivo') ?? '');

        $resultado = $this->ajax_cancelar_reserva();

        $codigo_estado = $resultado['success'] ? 200 : 400;
        return new WP_REST_Response($resultado, $codigo_estado);
    }

    /**
     * API: Apuntarse a lista de espera
     */
    public function api_apuntarse_espera($request) {
        $datos = $request->get_json_params();

        $_POST = array_merge($_POST, $datos);
        $_POST['subaction'] = 'solicitar_plaza';

        $resultado = $this->ajax_solicitar_plaza();

        $codigo_estado = $resultado['success'] ? 201 : 400;
        return new WP_REST_Response($resultado, $codigo_estado);
    }

    /**
     * API: Mis reservas
     */
    public function api_mis_reservas($request) {
        $usuario_id = get_current_user_id();
        $estado = sanitize_text_field($request->get_param('estado') ?? '');

        $estados = $estado ? [$estado] : ['pendiente', 'confirmada', 'activa', 'completada'];
        $reservas = $this->obtener_reservas_usuario($usuario_id, $estados, 50);

        return new WP_REST_Response([
            'reservas' => array_map([$this, 'formatear_reserva_api'], $reservas),
        ], 200);
    }

    /**
     * Obtener parking por ID
     */
    private function obtener_parking($parking_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_parkings} WHERE id = %d",
            $parking_id
        ));
    }

    /**
     * Obtener parkings activos
     */
    private function obtener_parkings_activos($limite = 20) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_parkings} WHERE estado = 'activo' ORDER BY nombre ASC LIMIT %d",
            $limite
        ));
    }

    /**
     * Obtener plaza por ID
     */
    private function obtener_plaza($plaza_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_plazas} WHERE id = %d",
            $plaza_id
        ));
    }

    /**
     * Obtener plazas de un parking
     */
    private function obtener_plazas_parking($parking_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_plazas} WHERE parking_id = %d ORDER BY planta, zona, numero_plaza",
            $parking_id
        ));
    }

    /**
     * Obtener reserva por ID
     */
    private function obtener_reserva($reserva_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_reservas} WHERE id = %d",
            $reserva_id
        ));
    }

    /**
     * Obtener reservas de usuario
     */
    private function obtener_reservas_usuario($usuario_id, $estados = [], $limite = 20) {
        global $wpdb;

        $estados_sql = !empty($estados) ? "AND r.estado IN ('" . implode("','", array_map('esc_sql', $estados)) . "')" : '';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.numero_plaza, p.planta, p.zona, pk.nombre as parking_nombre, pk.direccion as parking_direccion
             FROM {$this->tabla_reservas} r
             LEFT JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             LEFT JOIN {$this->tabla_parkings} pk ON p.parking_id = pk.id
             WHERE r.usuario_id = %d {$estados_sql}
             ORDER BY r.fecha_inicio DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));
    }

    /**
     * Obtener reserva activa de una plaza
     */
    private function obtener_reserva_activa_plaza($plaza_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_reservas}
             WHERE plaza_id = %d AND estado IN ('confirmada', 'activa')
             ORDER BY fecha_inicio ASC LIMIT 1",
            $plaza_id
        ));
    }

    /**
     * Obtener asignacion activa de una plaza
     */
    private function obtener_asignacion_activa_plaza($plaza_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_asignaciones}
             WHERE plaza_id = %d AND estado = 'activa'
             AND (es_indefinida = 1 OR fecha_fin >= CURDATE())",
            $plaza_id
        ));
    }

    /**
     * Obtener solicitud de espera
     */
    private function obtener_solicitud_espera($parking_id, $usuario_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_lista_espera}
             WHERE parking_id = %d AND usuario_id = %d AND estado = 'activo'",
            $parking_id,
            $usuario_id
        ));
    }

    /**
     * Contar plazas por estado
     */
    private function contar_plazas_por_estado($parking_id, $estado) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_plazas} WHERE parking_id = %d AND estado = %s",
            $parking_id,
            $estado
        ));
    }

    /**
     * Contar lista de espera
     */
    private function contar_lista_espera($parking_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_lista_espera} WHERE parking_id = %d AND estado = 'activo'",
            $parking_id
        ));
    }

    /**
     * Contar reservas activas de usuario
     */
    private function contar_reservas_activas_usuario($usuario_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE usuario_id = %d AND estado IN ('pendiente', 'confirmada', 'activa')",
            $usuario_id
        ));
    }

    /**
     * Verificar disponibilidad de plaza en rango
     */
    private function plaza_disponible_rango($plaza_id, $fecha_inicio, $fecha_fin) {
        global $wpdb;
        $conflictos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE plaza_id = %d
             AND estado IN ('confirmada', 'activa')
             AND (
                 (fecha_inicio <= %s AND fecha_fin >= %s)
                 OR (fecha_inicio <= %s AND fecha_fin >= %s)
                 OR (fecha_inicio >= %s AND fecha_fin <= %s)
             )",
            $plaza_id,
            $fecha_inicio, $fecha_inicio,
            $fecha_fin, $fecha_fin,
            $fecha_inicio, $fecha_fin
        ));

        return $conflictos == 0;
    }

    /**
     * Obtener disponibilidad en rango de fechas
     */
    private function obtener_disponibilidad_rango($parking_id, $fecha_inicio, $dias) {
        global $wpdb;

        $disponibilidad = [];
        $total_plazas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_plazas} WHERE parking_id = %d AND estado != 'bloqueada'",
            $parking_id
        ));

        for ($desplazamiento_dia = 0; $desplazamiento_dia < $dias; $desplazamiento_dia++) {
            $fecha = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $desplazamiento_dia . ' days'));

            $ocupadas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT r.plaza_id)
                 FROM {$this->tabla_reservas} r
                 INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
                 WHERE p.parking_id = %d
                 AND r.estado IN ('confirmada', 'activa')
                 AND DATE(r.fecha_inicio) <= %s
                 AND DATE(r.fecha_fin) >= %s",
                $parking_id,
                $fecha,
                $fecha
            ));

            $disponibilidad[$fecha] = [
                'total' => intval($total_plazas),
                'ocupadas' => intval($ocupadas),
                'libres' => intval($total_plazas) - intval($ocupadas),
            ];
        }

        return $disponibilidad;
    }

    /**
     * Actualizar estado de plaza
     */
    private function actualizar_estado_plaza($plaza_id, $nuevo_estado) {
        global $wpdb;
        $wpdb->update($this->tabla_plazas, [
            'estado' => $nuevo_estado,
            'ultima_ocupacion' => $nuevo_estado === 'ocupada' ? current_time('mysql') : null,
        ], ['id' => $plaza_id]);
    }

    /**
     * Calcular precio de reserva
     */
    private function calcular_precio_reserva($plaza, $fecha_inicio, $fecha_fin) {
        $diferencia_segundos = strtotime($fecha_fin) - strtotime($fecha_inicio);
        $horas = ceil($diferencia_segundos / 3600);
        $dias = ceil($diferencia_segundos / 86400);

        if ($dias >= 30 && $plaza->precio_mes) {
            $meses = ceil($dias / 30);
            return $meses * floatval($plaza->precio_mes);
        } elseif ($dias >= 1 && $plaza->precio_dia) {
            return $dias * floatval($plaza->precio_dia);
        } elseif ($plaza->precio_hora) {
            return $horas * floatval($plaza->precio_hora);
        }

        $configuracion = $this->get_settings();
        return $horas * floatval($configuracion['precio_hora']);
    }

    /**
     * Generar codigo de acceso
     */
    private function generar_codigo_acceso() {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    /**
     * Reordenar lista de espera
     */
    private function reordenar_lista_espera($parking_id) {
        global $wpdb;

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->tabla_lista_espera}
             WHERE parking_id = %d AND estado = 'activo'
             ORDER BY urgencia DESC, fecha_creacion ASC",
            $parking_id
        ));

        $posicion = 1;
        foreach ($solicitudes as $solicitud) {
            $wpdb->update($this->tabla_lista_espera, [
                'posicion' => $posicion,
            ], ['id' => $solicitud->id]);
            $posicion++;
        }
    }

    /**
     * Obtener estadisticas
     */
    private function obtener_estadisticas($parking_id = 0) {
        global $wpdb;

        $condicion_parking = $parking_id ? $wpdb->prepare("WHERE p.parking_id = %d", $parking_id) : '';
        $condicion_parking_reservas = $parking_id ? $wpdb->prepare("AND p.parking_id = %d", $parking_id) : '';
        $condicion_parking_espera = $parking_id ? $wpdb->prepare("WHERE parking_id = %d", $parking_id) : '';

        $total_plazas = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_plazas} p {$condicion_parking}");
        $plazas_libres = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_plazas} p {$condicion_parking} " . ($condicion_parking ? "AND" : "WHERE") . " estado = 'libre'");

        $ocupacion = $total_plazas > 0 ? (($total_plazas - $plazas_libres) / $total_plazas) * 100 : 0;

        $reservas_mes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE MONTH(r.fecha_creacion) = MONTH(CURDATE())
             AND YEAR(r.fecha_creacion) = YEAR(CURDATE())
             {$condicion_parking_reservas}"
        );

        $ingresos_mes = $wpdb->get_var(
            "SELECT COALESCE(SUM(r.precio_pagado), 0) FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE r.estado IN ('completada', 'activa')
             AND MONTH(r.fecha_creacion) = MONTH(CURDATE())
             AND YEAR(r.fecha_creacion) = YEAR(CURDATE())
             {$condicion_parking_reservas}"
        );

        $lista_espera = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_lista_espera} {$condicion_parking_espera} " . ($condicion_parking_espera ? "AND" : "WHERE") . " estado = 'activo'");

        return [
            'total_plazas' => intval($total_plazas),
            'plazas_libres' => intval($plazas_libres),
            'ocupacion_porcentaje' => floatval($ocupacion),
            'reservas_mes' => intval($reservas_mes),
            'ingresos_mes' => floatval($ingresos_mes),
            'lista_espera' => intval($lista_espera),
        ];
    }

    /**
     * Renderizar tarjeta de reserva
     */
    private function renderizar_tarjeta_reserva($reserva, $mostrar_acciones = true) {
        $clases_estado = [
            'pendiente' => 'estado-pendiente',
            'confirmada' => 'estado-confirmada',
            'activa' => 'estado-activa',
            'completada' => 'estado-completada',
            'cancelada' => 'estado-cancelada',
        ];

        ob_start();
        ?>
        <div class="reserva-tarjeta <?php echo esc_attr($clases_estado[$reserva->estado] ?? ''); ?>" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
            <div class="reserva-header">
                <span class="plaza-numero">Plaza <?php echo esc_html($reserva->numero_plaza); ?></span>
                <span class="reserva-estado"><?php echo esc_html(ucfirst($reserva->estado)); ?></span>
            </div>
            <div class="reserva-detalles">
                <p class="parking-nombre"><?php echo esc_html($reserva->parking_nombre); ?></p>
                <p class="fechas">
                    <span class="fecha-inicio"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_inicio))); ?></span>
                    <span class="separador">-</span>
                    <span class="fecha-fin"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_fin))); ?></span>
                </p>
                <?php if ($reserva->codigo_acceso && in_array($reserva->estado, ['confirmada', 'activa'])): ?>
                    <p class="codigo-acceso">
                        <strong><?php _e('Codigo:', 'flavor-chat-ia'); ?></strong>
                        <code><?php echo esc_html($reserva->codigo_acceso); ?></code>
                    </p>
                <?php endif; ?>
                <p class="precio">
                    <strong><?php _e('Precio:', 'flavor-chat-ia'); ?></strong>
                    <?php echo number_format($reserva->precio_total, 2); ?>eur
                </p>
            </div>
            <?php if ($mostrar_acciones && in_array($reserva->estado, ['pendiente', 'confirmada', 'activa'])): ?>
                <div class="reserva-acciones">
                    <?php if ($reserva->estado === 'confirmada'): ?>
                        <button class="btn-entrada" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                            <?php _e('Registrar entrada', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($reserva->estado === 'activa'): ?>
                        <button class="btn-salida" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                            <?php _e('Registrar salida', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if (in_array($reserva->estado, ['pendiente', 'confirmada'])): ?>
                        <button class="btn-cancelar" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                            <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Formatear parking para API
     */
    private function formatear_parking_api($parking, $detallado = false) {
        $datos = [
            'id' => intval($parking->id),
            'nombre' => $parking->nombre,
            'direccion' => $parking->direccion,
            'tipo' => $parking->tipo_parking,
            'total_plazas' => intval($parking->total_plazas),
            'plazas_libres' => $this->contar_plazas_por_estado($parking->id, 'libre'),
            'coordenadas' => [
                'lat' => floatval($parking->latitud),
                'lng' => floatval($parking->longitud),
            ],
            'precios' => [
                'hora' => floatval($parking->precio_hora_visitante),
                'dia' => floatval($parking->precio_dia_visitante),
                'mes' => floatval($parking->cuota_mensual_residente),
            ],
        ];

        if ($detallado) {
            $datos['descripcion'] = $parking->descripcion;
            $datos['horario'] = [
                'apertura' => $parking->horario_apertura,
                'cierre' => $parking->horario_cierre,
                'acceso_24h' => (bool) $parking->acceso_24h,
            ];
            $datos['tipo_acceso'] = $parking->tipo_acceso;
            $datos['permite_rotacion'] = (bool) $parking->permite_rotacion;
            $datos['normas'] = $parking->normas;
            $datos['contacto_emergencia'] = $parking->contacto_emergencia;
            $datos['telefono_emergencia'] = $parking->telefono_emergencia;
            $datos['fotos'] = json_decode($parking->fotos, true) ?: [];
            $datos['plazas_detalle'] = [
                'residentes' => intval($parking->plazas_residentes),
                'visitantes' => intval($parking->plazas_visitantes),
                'movilidad_reducida' => intval($parking->plazas_movilidad_reducida),
                'carga_electrica' => intval($parking->plazas_carga_electrica),
            ];
        }

        return $datos;
    }

    /**
     * Formatear plaza para API
     */
    private function formatear_plaza_api($plaza) {
        return [
            'id' => intval($plaza->id),
            'numero' => $plaza->numero_plaza,
            'planta' => $plaza->planta,
            'zona' => $plaza->zona,
            'tipo' => $plaza->tipo_plaza,
            'estado' => $plaza->estado,
            'dimensiones' => [
                'ancho' => intval($plaza->ancho_cm),
                'largo' => intval($plaza->largo_cm),
                'alto_maximo' => intval($plaza->alto_maximo_cm),
            ],
            'caracteristicas' => [
                'tiene_columna' => (bool) $plaza->tiene_columna,
                'lado_columna' => $plaza->lado_columna,
                'tiene_cargador' => (bool) $plaza->tiene_cargador,
                'tipo_cargador' => $plaza->tipo_cargador,
                'potencia_cargador_kw' => floatval($plaza->potencia_cargador_kw),
            ],
            'precios' => [
                'hora' => floatval($plaza->precio_hora),
                'dia' => floatval($plaza->precio_dia),
                'mes' => floatval($plaza->precio_mes),
            ],
            'disponible_alquiler' => (bool) $plaza->disponible_alquiler,
            'posicion' => [
                'x' => intval($plaza->posicion_x),
                'y' => intval($plaza->posicion_y),
                'rotacion' => intval($plaza->rotacion),
            ],
        ];
    }

    /**
     * Formatear reserva para API
     */
    private function formatear_reserva_api($reserva) {
        return [
            'id' => intval($reserva->id),
            'plaza_id' => intval($reserva->plaza_id),
            'tipo' => $reserva->tipo_reserva,
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'estado' => $reserva->estado,
            'vehiculo' => [
                'matricula' => $reserva->matricula_vehiculo,
                'marca' => $reserva->marca_vehiculo,
                'modelo' => $reserva->modelo_vehiculo,
                'color' => $reserva->color_vehiculo,
            ],
            'precio_total' => floatval($reserva->precio_total),
            'precio_pagado' => floatval($reserva->precio_pagado),
            'codigo_acceso' => $reserva->codigo_acceso,
            'hora_entrada' => $reserva->hora_entrada,
            'hora_salida' => $reserva->hora_salida,
        ];
    }

    /**
     * Enviar notificacion de reserva
     */
    private function enviar_notificacion_reserva($reserva_id) {
        // Usar el nuevo sistema de notificaciones integrado
        $this->notificar_reserva_confirmada($reserva_id);

        // Enviar tambien email tradicional como respaldo
        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva) {
            return;
        }

        $usuario = get_userdata($reserva->usuario_id);
        if (!$usuario || !$usuario->user_email) {
            return;
        }

        $plaza = $this->obtener_plaza($reserva->plaza_id);
        $parking = $this->obtener_parking($plaza->parking_id);

        $asunto = sprintf(__('[%s] Confirmacion de reserva de parking', 'flavor-chat-ia'), get_bloginfo('name'));

        $mensaje = sprintf(
            __("Hola %s,\n\nTu reserva ha sido confirmada:\n\nParking: %s\nPlaza: %s\nFecha: %s - %s\nCodigo de acceso: %s\nPrecio: %.2f EUR\n\nGracias por usar nuestro servicio.", 'flavor-chat-ia'),
            $usuario->display_name,
            $parking->nombre,
            $plaza->numero_plaza,
            date_i18n('d/m/Y H:i', strtotime($reserva->fecha_inicio)),
            date_i18n('d/m/Y H:i', strtotime($reserva->fecha_fin)),
            $reserva->codigo_acceso,
            $reserva->precio_total
        );

        wp_mail($usuario->user_email, $asunto, $mensaje);
    }

    /**
     * Notificar lista de espera cuando hay plaza disponible
     */
    private function notificar_lista_espera_plaza_disponible($plaza_id) {
        $configuracion = $this->get_settings();
        if (!$configuracion['notificar_lista_espera']) {
            return;
        }

        $plaza = $this->obtener_plaza($plaza_id);
        if (!$plaza) {
            return;
        }

        global $wpdb;
        $siguiente_en_espera = $wpdb->get_row($wpdb->prepare(
            "SELECT le.*, u.user_email, u.display_name
             FROM {$this->tabla_lista_espera} le
             INNER JOIN {$wpdb->users} u ON le.usuario_id = u.ID
             WHERE le.parking_id = %d
             AND le.estado = 'activo'
             AND (le.tipo_plaza_preferida = 'cualquiera' OR le.tipo_plaza_preferida = %s)
             ORDER BY le.posicion ASC
             LIMIT 1",
            $plaza->parking_id,
            $plaza->tipo_plaza
        ));

        if (!$siguiente_en_espera) {
            return;
        }

        // Usar el nuevo sistema de notificaciones integrado
        $this->notificar_plaza_disponible_espera($siguiente_en_espera, $plaza);

        $parking = $this->obtener_parking($plaza->parking_id);

        // Email tradicional como respaldo
        $asunto = sprintf(__('[%s] Plaza de parking disponible!', 'flavor-chat-ia'), get_bloginfo('name'));

        $mensaje = sprintf(
            __("Hola %s,\n\nBuenas noticias! Hay una plaza de parking disponible que coincide con tu solicitud:\n\nParking: %s\nPlaza: %s\nTipo: %s\n\nAccede a tu cuenta para reservarla antes de que la ocupe otra persona.\n\nGracias.", 'flavor-chat-ia'),
            $siguiente_en_espera->display_name,
            $parking->nombre,
            $plaza->numero_plaza,
            ucfirst($plaza->tipo_plaza)
        );

        wp_mail($siguiente_en_espera->user_email, $asunto, $mensaje);

        $wpdb->update($this->tabla_lista_espera, [
            'notificaciones_enviadas' => $siguiente_en_espera->notificaciones_enviadas + 1,
            'ultima_notificacion' => current_time('mysql'),
        ], ['id' => $siguiente_en_espera->id]);
    }

    /**
     * Procesar rotacion programada
     */
    public function procesar_rotacion_programada() {
        $configuracion = $this->get_settings();
        if (!$configuracion['rotacion_activa']) {
            return;
        }

        global $wpdb;

        $asignaciones_expirar = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.parking_id
             FROM {$this->tabla_asignaciones} a
             INNER JOIN {$this->tabla_plazas} p ON a.plaza_id = p.id
             INNER JOIN {$this->tabla_parkings} pk ON p.parking_id = pk.id
             WHERE a.tipo_asignacion = 'rotacion'
             AND a.estado = 'activa'
             AND a.fecha_fin <= CURDATE()
             AND pk.permite_rotacion = 1"
        ));

        foreach ($asignaciones_expirar as $asignacion) {
            $wpdb->update($this->tabla_asignaciones, [
                'estado' => 'finalizada',
                'motivo_finalizacion' => 'Fin de periodo de rotacion',
            ], ['id' => $asignacion->id]);

            $this->actualizar_estado_plaza($asignacion->plaza_id, 'libre');
            $this->notificar_lista_espera_plaza_disponible($asignacion->plaza_id);
        }
    }

    /**
     * Procesar reservas expiradas
     */
    public function procesar_reservas_expiradas() {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_reservas}
             SET estado = 'no_show', penalizacion = %f
             WHERE estado = 'confirmada'
             AND fecha_inicio < DATE_SUB(NOW(), INTERVAL %d MINUTE)",
            $this->get_settings()['penalizacion_no_show'],
            $this->get_settings()['tiempo_gracia_minutos']
        ));

        $reservas_expiradas = $wpdb->get_results(
            "SELECT plaza_id FROM {$this->tabla_reservas}
             WHERE estado = 'no_show'
             AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );

        foreach ($reservas_expiradas as $reserva) {
            $this->actualizar_estado_plaza($reserva->plaza_id, 'libre');
            $this->notificar_lista_espera_plaza_disponible($reserva->plaza_id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_parkings' => [
                'description' => 'Buscar parkings disponibles',
                'params' => ['lat', 'lng', 'fecha', 'tipo_vehiculo'],
            ],
            'ver_parking' => [
                'description' => 'Ver detalles de un parking',
                'params' => ['parking_id'],
            ],
            'ver_disponibilidad' => [
                'description' => 'Ver disponibilidad de plazas',
                'params' => ['parking_id', 'fecha'],
            ],
            'reservar_plaza' => [
                'description' => 'Reservar una plaza de parking',
                'params' => ['plaza_id', 'fecha_inicio', 'fecha_fin', 'matricula'],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar una reserva',
                'params' => ['reserva_id'],
            ],
            'mis_reservas' => [
                'description' => 'Ver mis reservas activas',
                'params' => [],
            ],
            'apuntarse_espera' => [
                'description' => 'Apuntarse a la lista de espera',
                'params' => ['parking_id', 'tipo_plaza', 'matricula'],
            ],
            'liberar_plaza' => [
                'description' => 'Liberar plaza (registrar salida)',
                'params' => ['reserva_id'],
            ],
            'estadisticas_parking' => [
                'description' => 'Ver estadisticas del parking (admin)',
                'params' => ['parking_id'],
            ],
            'ver_tarifas' => [
                'description' => 'Ver tarifas del parking',
                'params' => ['parking_id'],
            ],
            'ocupacion_tiempo_real' => [
                'description' => 'Ver ocupacion en tiempo real',
                'params' => ['parking_id'],
            ],
            'valorar_reserva' => [
                'description' => 'Valorar una reserva completada',
                'params' => ['reserva_id', 'valoracion', 'comentario'],
            ],
            'compartir_plaza' => [
                'description' => 'Poner mi plaza disponible para alquiler',
                'params' => ['plaza_id', 'fecha_desde', 'fecha_hasta'],
            ],
            'calcular_precio' => [
                'description' => 'Calcular precio con descuentos',
                'params' => ['plaza_id', 'fecha_inicio', 'fecha_fin'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $params) {
        $metodo = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    /**
     * Accion: Buscar parkings
     */
    private function action_buscar_parkings($params) {
        global $wpdb;

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $radio_km = floatval($params['radio'] ?? 5);

        $sql = "SELECT p.*,
                (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                FROM {$this->tabla_parkings} p
                WHERE p.estado = 'activo'
                HAVING distancia <= %f
                ORDER BY distancia ASC
                LIMIT 20";

        $parkings = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat, $radio_km));

        return [
            'success' => true,
            'parkings' => array_map(function($parking) {
                return [
                    'id' => $parking->id,
                    'nombre' => $parking->nombre,
                    'direccion' => $parking->direccion,
                    'distancia_km' => round($parking->distancia, 2),
                    'plazas_libres' => $this->contar_plazas_por_estado($parking->id, 'libre'),
                    'total_plazas' => $parking->total_plazas,
                ];
            }, $parkings),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'hero_parkings' => [
                'label' => __('Hero Parkings', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-multisite',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Parkings Comunitarios', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Gestion inteligente de plazas de parking', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'parkings/hero',
            ],
            'parkings_mapa' => [
                'label' => __('Mapa de Parkings', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'parking_id' => ['type' => 'number', 'default' => 0],
                    'altura' => ['type' => 'number', 'default' => 500],
                    'mostrar_leyenda' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'parkings/mapa',
            ],
            'parkings_grid' => [
                'label' => __('Grid de Parkings', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'limite' => ['type' => 'number', 'default' => 6],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                ],
                'template' => 'parkings/grid',
            ],
            'parkings_disponibilidad' => [
                'label' => __('Widget Disponibilidad', 'flavor-chat-ia'),
                'category' => 'widgets',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'parking_id' => ['type' => 'number', 'default' => 0],
                    'dias_vista' => ['type' => 'number', 'default' => 7],
                ],
                'template' => 'parkings/disponibilidad',
            ],
            'parkings_mis_reservas' => [
                'label' => __('Mis Reservas', 'flavor-chat-ia'),
                'category' => 'user',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'mostrar_historico' => ['type' => 'toggle', 'default' => false],
                ],
                'template' => 'parkings/mis-reservas',
            ],
            'parkings_solicitar' => [
                'label' => __('Solicitar Plaza', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'parking_id' => ['type' => 'number', 'default' => 0],
                ],
                'template' => 'parkings/solicitar',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'parkings_buscar',
                'description' => 'Buscar parkings disponibles cerca de una ubicacion',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'radio' => ['type' => 'number', 'description' => 'Radio de busqueda en km'],
                    ],
                    'required' => ['lat', 'lng'],
                ],
            ],
            [
                'name' => 'parkings_disponibilidad',
                'description' => 'Consultar disponibilidad de plazas en un parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'parking_id' => ['type' => 'integer', 'description' => 'ID del parking'],
                        'fecha' => ['type' => 'string', 'description' => 'Fecha YYYY-MM-DD'],
                    ],
                    'required' => ['parking_id'],
                ],
            ],
            [
                'name' => 'parkings_reservar',
                'description' => 'Reservar una plaza de parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'plaza_id' => ['type' => 'integer', 'description' => 'ID de la plaza'],
                        'fecha_inicio' => ['type' => 'string', 'description' => 'Fecha y hora inicio'],
                        'fecha_fin' => ['type' => 'string', 'description' => 'Fecha y hora fin'],
                        'matricula' => ['type' => 'string', 'description' => 'Matricula del vehiculo'],
                    ],
                    'required' => ['plaza_id', 'fecha_inicio', 'fecha_fin'],
                ],
            ],
            [
                'name' => 'parkings_lista_espera',
                'description' => 'Apuntarse a la lista de espera de un parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'parking_id' => ['type' => 'integer', 'description' => 'ID del parking'],
                        'tipo_plaza' => ['type' => 'string', 'description' => 'Tipo de plaza preferida'],
                        'matricula' => ['type' => 'string', 'description' => 'Matricula del vehiculo'],
                    ],
                    'required' => ['parking_id'],
                ],
            ],
            [
                'name' => 'parkings_ocupacion_tiempo_real',
                'description' => 'Consultar ocupacion en tiempo real de un parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'parking_id' => ['type' => 'integer', 'description' => 'ID del parking'],
                    ],
                    'required' => ['parking_id'],
                ],
            ],
            [
                'name' => 'parkings_tarifas',
                'description' => 'Consultar tarifas y precios de un parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'parking_id' => ['type' => 'integer', 'description' => 'ID del parking'],
                    ],
                    'required' => ['parking_id'],
                ],
            ],
            [
                'name' => 'parkings_calcular_precio',
                'description' => 'Calcular precio de reserva con descuentos aplicables',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'plaza_id' => ['type' => 'integer', 'description' => 'ID de la plaza'],
                        'fecha_inicio' => ['type' => 'string', 'description' => 'Fecha y hora inicio'],
                        'fecha_fin' => ['type' => 'string', 'description' => 'Fecha y hora fin'],
                    ],
                    'required' => ['plaza_id', 'fecha_inicio', 'fecha_fin'],
                ],
            ],
            [
                'name' => 'parkings_valorar',
                'description' => 'Valorar una reserva de parking completada',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'reserva_id' => ['type' => 'integer', 'description' => 'ID de la reserva'],
                        'valoracion' => ['type' => 'integer', 'description' => 'Puntuacion de 1 a 5'],
                        'comentario' => ['type' => 'string', 'description' => 'Comentario opcional'],
                    ],
                    'required' => ['reserva_id', 'valoracion'],
                ],
            ],
            [
                'name' => 'parkings_compartir_plaza',
                'description' => 'Ofrecer tu plaza para alquiler temporal',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'plaza_id' => ['type' => 'integer', 'description' => 'ID de tu plaza'],
                        'fecha_desde' => ['type' => 'string', 'description' => 'Desde cuando disponible'],
                        'fecha_hasta' => ['type' => 'string', 'description' => 'Hasta cuando disponible'],
                    ],
                    'required' => ['plaza_id'],
                ],
            ],
            [
                'name' => 'parkings_mis_reservas',
                'description' => 'Ver mis reservas de parking activas e historico',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'description' => 'Filtrar por estado', 'enum' => ['activa', 'completada', 'cancelada', 'todas']],
                    ],
                ],
            ],
            [
                'name' => 'parkings_cancelar_reserva',
                'description' => 'Cancelar una reserva de parking',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'reserva_id' => ['type' => 'integer', 'description' => 'ID de la reserva a cancelar'],
                        'motivo' => ['type' => 'string', 'description' => 'Motivo de cancelacion'],
                    ],
                    'required' => ['reserva_id'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Parkings Comunitarios**

Gestion completa de plazas de parking para comunidades de vecinos.

**Funcionalidades principales:**
- Visualizacion de mapa interactivo de plazas
- Reserva temporal de plazas
- Sistema de rotacion automatica
- Lista de espera inteligente
- Asignaciones permanentes

**Tipos de plaza:**
- Normal: Plaza estandar
- Grande: Para vehiculos grandes
- Pequena: Para coches pequenos
- Moto: Exclusiva motos
- Bici: Para bicicletas
- Carga electrica: Con punto de carga
- Movilidad reducida: Accesible

**Estados de plaza:**
- Libre: Disponible para reservar
- Ocupada: En uso actualmente
- Reservada: Con reserva pendiente
- Mantenimiento: No disponible temporalmente
- Bloqueada: Fuera de servicio

**Como reservar:**
1. Consulta disponibilidad en el mapa
2. Selecciona plaza y fechas
3. Introduce datos del vehiculo
4. Confirma la reserva
5. Recibe codigo de acceso

**Lista de espera:**
- Sistema de prioridad por urgencia
- Notificacion automatica cuando hay plaza
- Preferencias de tipo de plaza
- Presupuesto maximo configurable

**Rotacion:**
- Cambio periodico de plazas asignadas
- Equidad entre vecinos
- Configurable por comunidad

**Tarifas:**
- Por hora, dia o mes
- Diferentes segun tipo de plaza
- Penalizacion por no-show
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo reservar una plaza de parking?',
                'respuesta' => 'Accede al mapa de plazas, selecciona una plaza libre, elige las fechas y confirma tu reserva. Recibiras un codigo de acceso por email.',
            ],
            [
                'pregunta' => 'Que pasa si no llego a mi reserva?',
                'respuesta' => 'Dispones de un tiempo de gracia configurado. Pasado ese tiempo, la reserva se marca como no-show y puede aplicarse una penalizacion.',
            ],
            [
                'pregunta' => 'Puedo cancelar una reserva?',
                'respuesta' => 'Si, puedes cancelar reservas pendientes o confirmadas. Las cancelaciones con menos de 24h de antelacion pueden tener penalizacion.',
            ],
            [
                'pregunta' => 'Como funciona la lista de espera?',
                'respuesta' => 'Registra tu solicitud indicando preferencias. Cuando haya una plaza disponible que coincida, recibiras una notificacion automatica.',
            ],
            [
                'pregunta' => 'Que es el sistema de rotacion?',
                'respuesta' => 'Es un sistema que permite cambiar periodicamente las asignaciones de plazas para garantizar equidad entre todos los vecinos.',
            ],
            [
                'pregunta' => 'Puedo ver el estado de mi plaza en tiempo real?',
                'respuesta' => 'Si, el mapa de plazas se actualiza en tiempo real mostrando el estado actual de cada plaza.',
            ],
        ];
    }

    // =========================================================================
    // INTEGRACION CON SISTEMA DE NOTIFICACIONES
    // =========================================================================

    /**
     * Registrar tipos de eventos de notificacion para parkings
     */
    public function registrar_eventos_notificacion() {
        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();

            $gestor_notificaciones->register_event_type('parking_reserva_confirmada', [
                'label' => __('Reserva de parking confirmada', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['email', 'push', 'inapp'],
                'icon' => 'dashicons-car',
            ]);

            $gestor_notificaciones->register_event_type('parking_reserva_cancelada', [
                'label' => __('Reserva de parking cancelada', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-no',
            ]);

            $gestor_notificaciones->register_event_type('parking_plaza_disponible', [
                'label' => __('Plaza de parking disponible', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['email', 'push', 'inapp'],
                'icon' => 'dashicons-yes-alt',
                'priority' => 'high',
            ]);

            $gestor_notificaciones->register_event_type('parking_recordatorio_reserva', [
                'label' => __('Recordatorio de reserva', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['push', 'inapp'],
                'icon' => 'dashicons-clock',
            ]);

            $gestor_notificaciones->register_event_type('parking_reserva_expirando', [
                'label' => __('Reserva a punto de expirar', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['push', 'inapp'],
                'icon' => 'dashicons-warning',
            ]);

            $gestor_notificaciones->register_event_type('parking_rotacion_proxima', [
                'label' => __('Rotacion de plaza proxima', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-update',
            ]);

            $gestor_notificaciones->register_event_type('parking_penalizacion', [
                'label' => __('Penalizacion aplicada', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-warning',
                'priority' => 'high',
            ]);

            $gestor_notificaciones->register_event_type('parking_lista_espera_avance', [
                'label' => __('Avance en lista de espera', 'flavor-chat-ia'),
                'category' => 'parkings',
                'default_channels' => ['inapp'],
                'icon' => 'dashicons-arrow-up-alt',
            ]);
        }
    }

    /**
     * Enviar notificacion via sistema Flavor
     */
    private function enviar_notificacion_flavor($usuario_id, $tipo_evento, $datos_notificacion) {
        if (function_exists('flavor_notify')) {
            flavor_notify($usuario_id, $tipo_evento, $datos_notificacion);
        }
    }

    /**
     * Notificacion: Reserva confirmada
     */
    private function notificar_reserva_confirmada($reserva_id) {
        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva) {
            return;
        }

        $plaza = $this->obtener_plaza($reserva->plaza_id);
        $parking = $this->obtener_parking($plaza->parking_id);

        $this->enviar_notificacion_flavor($reserva->usuario_id, 'parking_reserva_confirmada', [
            'title' => __('Reserva de parking confirmada', 'flavor-chat-ia'),
            'message' => sprintf(
                __('Tu reserva en %s (Plaza %s) del %s al %s ha sido confirmada. Codigo de acceso: %s', 'flavor-chat-ia'),
                $parking->nombre,
                $plaza->numero_plaza,
                date_i18n('d/m/Y H:i', strtotime($reserva->fecha_inicio)),
                date_i18n('d/m/Y H:i', strtotime($reserva->fecha_fin)),
                $reserva->codigo_acceso
            ),
            'icon' => 'dashicons-car',
            'color' => '#10b981',
            'link' => home_url('/mis-reservas-parking/'),
            'data' => [
                'reserva_id' => $reserva_id,
                'parking_id' => $parking->id,
                'plaza_id' => $plaza->id,
                'codigo_acceso' => $reserva->codigo_acceso,
            ],
        ]);

        $this->otorgar_puntos_gamificacion($reserva->usuario_id, 'parking_reserva', 10);
    }

    /**
     * Notificacion: Reserva cancelada
     */
    private function notificar_reserva_cancelada($reserva_id, $penalizacion = 0) {
        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva) {
            return;
        }

        $plaza = $this->obtener_plaza($reserva->plaza_id);
        $parking = $this->obtener_parking($plaza->parking_id);

        $mensaje = sprintf(
            __('Tu reserva en %s (Plaza %s) ha sido cancelada.', 'flavor-chat-ia'),
            $parking->nombre,
            $plaza->numero_plaza
        );

        if ($penalizacion > 0) {
            $mensaje .= ' ' . sprintf(__('Se ha aplicado una penalizacion de %.2f EUR.', 'flavor-chat-ia'), $penalizacion);
        }

        $this->enviar_notificacion_flavor($reserva->usuario_id, 'parking_reserva_cancelada', [
            'title' => __('Reserva de parking cancelada', 'flavor-chat-ia'),
            'message' => $mensaje,
            'icon' => 'dashicons-no',
            'color' => '#ef4444',
            'link' => home_url('/mis-reservas-parking/'),
            'data' => [
                'reserva_id' => $reserva_id,
                'penalizacion' => $penalizacion,
            ],
        ]);
    }

    /**
     * Notificacion: Plaza disponible para lista de espera
     */
    private function notificar_plaza_disponible_espera($solicitud_espera, $plaza) {
        $parking = $this->obtener_parking($plaza->parking_id);

        $this->enviar_notificacion_flavor($solicitud_espera->usuario_id, 'parking_plaza_disponible', [
            'title' => __('Plaza de parking disponible!', 'flavor-chat-ia'),
            'message' => sprintf(
                __('Hay una plaza disponible en %s que coincide con tu solicitud. Plaza %s (%s). Reservala antes de que la ocupe otra persona!', 'flavor-chat-ia'),
                $parking->nombre,
                $plaza->numero_plaza,
                ucfirst($plaza->tipo_plaza)
            ),
            'icon' => 'dashicons-yes-alt',
            'color' => '#22c55e',
            'link' => home_url('/parkings/?reservar=' . $plaza->id),
            'priority' => 'high',
            'data' => [
                'plaza_id' => $plaza->id,
                'parking_id' => $parking->id,
                'solicitud_id' => $solicitud_espera->id,
            ],
        ]);
    }

    /**
     * Notificacion: Recordatorio de reserva
     */
    public function enviar_recordatorios_reservas() {
        global $wpdb;

        $reservas_proximas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.numero_plaza, pk.nombre as parking_nombre
             FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             INNER JOIN {$this->tabla_parkings} pk ON p.parking_id = pk.id
             WHERE r.estado = 'confirmada'
             AND r.recordatorio_enviado = 0
             AND r.fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)"
        ));

        foreach ($reservas_proximas as $reserva) {
            $this->enviar_notificacion_flavor($reserva->usuario_id, 'parking_recordatorio_reserva', [
                'title' => __('Tu reserva de parking comienza pronto', 'flavor-chat-ia'),
                'message' => sprintf(
                    __('Tu reserva en %s (Plaza %s) comienza en menos de 2 horas. Codigo: %s', 'flavor-chat-ia'),
                    $reserva->parking_nombre,
                    $reserva->numero_plaza,
                    $reserva->codigo_acceso
                ),
                'icon' => 'dashicons-clock',
                'color' => '#f59e0b',
                'link' => home_url('/mis-reservas-parking/'),
                'data' => [
                    'reserva_id' => $reserva->id,
                ],
            ]);

            $wpdb->update($this->tabla_reservas, [
                'recordatorio_enviado' => 1,
            ], ['id' => $reserva->id]);
        }
    }

    /**
     * Notificacion: Reserva expirando
     */
    public function notificar_reservas_expirando() {
        global $wpdb;

        $reservas_expirando = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.numero_plaza, pk.nombre as parking_nombre
             FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             INNER JOIN {$this->tabla_parkings} pk ON p.parking_id = pk.id
             WHERE r.estado = 'activa'
             AND r.fecha_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)"
        ));

        foreach ($reservas_expirando as $reserva) {
            $this->enviar_notificacion_flavor($reserva->usuario_id, 'parking_reserva_expirando', [
                'title' => __('Tu reserva de parking termina pronto', 'flavor-chat-ia'),
                'message' => sprintf(
                    __('Tu reserva en %s (Plaza %s) termina en 30 minutos. Recuerda liberar la plaza a tiempo.', 'flavor-chat-ia'),
                    $reserva->parking_nombre,
                    $reserva->numero_plaza
                ),
                'icon' => 'dashicons-warning',
                'color' => '#f59e0b',
                'link' => home_url('/mis-reservas-parking/'),
                'data' => [
                    'reserva_id' => $reserva->id,
                ],
            ]);
        }
    }

    // =========================================================================
    // INTEGRACION CON SISTEMA DE GAMIFICACION
    // =========================================================================

    /**
     * Registrar acciones de gamificacion para parkings
     */
    public function registrar_acciones_gamificacion() {
        add_filter('flavor_gamification_actions', [$this, 'anadir_acciones_gamificacion']);
        add_filter('flavor_gamification_badges', [$this, 'anadir_insignias_gamificacion']);
    }

    /**
     * Anadir acciones de gamificacion
     */
    public function anadir_acciones_gamificacion($acciones) {
        $acciones['parking_reserva'] = [
            'label' => __('Reservar plaza de parking', 'flavor-chat-ia'),
            'points' => 10,
            'description' => __('Puntos por realizar una reserva de parking', 'flavor-chat-ia'),
        ];

        $acciones['parking_completar_reserva'] = [
            'label' => __('Completar reserva de parking', 'flavor-chat-ia'),
            'points' => 15,
            'description' => __('Puntos por completar una reserva sin incidencias', 'flavor-chat-ia'),
        ];

        $acciones['parking_liberar_anticipado'] = [
            'label' => __('Liberar plaza anticipadamente', 'flavor-chat-ia'),
            'points' => 20,
            'description' => __('Puntos extra por liberar la plaza antes de tiempo', 'flavor-chat-ia'),
        ];

        $acciones['parking_valorar'] = [
            'label' => __('Valorar experiencia parking', 'flavor-chat-ia'),
            'points' => 5,
            'description' => __('Puntos por valorar la experiencia de parking', 'flavor-chat-ia'),
        ];

        $acciones['parking_compartir_disponibilidad'] = [
            'label' => __('Compartir disponibilidad de plaza', 'flavor-chat-ia'),
            'points' => 25,
            'description' => __('Puntos por ofrecer tu plaza cuando no la uses', 'flavor-chat-ia'),
        ];

        $acciones['parking_referir_vecino'] = [
            'label' => __('Referir vecino al sistema parking', 'flavor-chat-ia'),
            'points' => 50,
            'description' => __('Puntos por traer un nuevo vecino al sistema', 'flavor-chat-ia'),
        ];

        return $acciones;
    }

    /**
     * Anadir insignias de gamificacion
     */
    public function anadir_insignias_gamificacion($insignias) {
        $insignias['parking_novato'] = [
            'label' => __('Conductor Novato', 'flavor-chat-ia'),
            'description' => __('Primera reserva de parking realizada', 'flavor-chat-ia'),
            'icon' => 'car',
            'condition' => 'parking_reservas >= 1',
        ];

        $insignias['parking_habitual'] = [
            'label' => __('Conductor Habitual', 'flavor-chat-ia'),
            'description' => __('10 reservas de parking completadas', 'flavor-chat-ia'),
            'icon' => 'car-side',
            'condition' => 'parking_reservas >= 10',
        ];

        $insignias['parking_experto'] = [
            'label' => __('Experto en Parking', 'flavor-chat-ia'),
            'description' => __('50 reservas de parking completadas', 'flavor-chat-ia'),
            'icon' => 'parking',
            'condition' => 'parking_reservas >= 50',
        ];

        $insignias['parking_puntual'] = [
            'label' => __('Conductor Puntual', 'flavor-chat-ia'),
            'description' => __('10 reservas sin retrasos ni no-shows', 'flavor-chat-ia'),
            'icon' => 'clock',
            'condition' => 'parking_puntuales >= 10',
        ];

        $insignias['parking_solidario'] = [
            'label' => __('Vecino Solidario', 'flavor-chat-ia'),
            'description' => __('Ha compartido su plaza 5 veces', 'flavor-chat-ia'),
            'icon' => 'handshake',
            'condition' => 'parking_compartidas >= 5',
        ];

        $insignias['parking_ecologico'] = [
            'label' => __('Conductor Ecologico', 'flavor-chat-ia'),
            'description' => __('10 reservas en plazas con cargador electrico', 'flavor-chat-ia'),
            'icon' => 'leaf',
            'condition' => 'parking_electrico >= 10',
        ];

        return $insignias;
    }

    /**
     * Otorgar puntos de gamificacion
     */
    private function otorgar_puntos_gamificacion($usuario_id, $accion, $puntos_base = 0) {
        if (class_exists('Flavor_Gamification_Manager')) {
            $gestor_gamificacion = Flavor_Gamification_Manager::get_instance();
            $gestor_gamificacion->award_points($usuario_id, $accion, $puntos_base);
        }

        do_action('flavor_gamification_action', $usuario_id, $accion, [
            'module' => 'parkings',
            'points' => $puntos_base,
        ]);
    }

    /**
     * Incrementar contador de gamificacion
     */
    private function incrementar_contador_gamificacion($usuario_id, $contador, $incremento = 1) {
        $clave_meta = 'flavor_parking_' . $contador;
        $valor_actual = intval(get_user_meta($usuario_id, $clave_meta, true));
        update_user_meta($usuario_id, $clave_meta, $valor_actual + $incremento);

        $this->verificar_insignias_usuario($usuario_id);
    }

    /**
     * Verificar insignias del usuario
     */
    private function verificar_insignias_usuario($usuario_id) {
        if (class_exists('Flavor_Gamification_Manager')) {
            $gestor_gamificacion = Flavor_Gamification_Manager::get_instance();
            $gestor_gamificacion->check_badges($usuario_id);
        }
    }

    // =========================================================================
    // SISTEMA DE TARIFAS
    // =========================================================================

    /**
     * Obtener tarifas de un parking
     */
    public function obtener_tarifas_parking($parking_id) {
        $parking = $this->obtener_parking($parking_id);
        if (!$parking) {
            return null;
        }

        $configuracion = $this->get_settings();

        return [
            'hora_visitante' => floatval($parking->precio_hora_visitante ?: $configuracion['precio_hora']),
            'dia_visitante' => floatval($parking->precio_dia_visitante ?: $configuracion['precio_dia']),
            'mes_residente' => floatval($parking->cuota_mensual_residente ?: $configuracion['precio_mes']),
            'comision_plataforma' => floatval($configuracion['comision_plataforma']),
            'penalizacion_no_show' => floatval($configuracion['penalizacion_no_show']),
            'moneda' => 'EUR',
            'iva_incluido' => true,
        ];
    }

    /**
     * Calcular precio con descuentos
     */
    public function calcular_precio_con_descuentos($plaza, $fecha_inicio, $fecha_fin, $usuario_id) {
        $precio_base = $this->calcular_precio_reserva($plaza, $fecha_inicio, $fecha_fin);

        $descuento_total = 0;

        $reservas_mes = $this->contar_reservas_usuario_mes($usuario_id);
        if ($reservas_mes >= 5) {
            $descuento_total += 0.10;
        } elseif ($reservas_mes >= 3) {
            $descuento_total += 0.05;
        }

        $dias_reserva = ceil((strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400);
        if ($dias_reserva >= 7) {
            $descuento_total += 0.15;
        } elseif ($dias_reserva >= 3) {
            $descuento_total += 0.08;
        }

        if (class_exists('Flavor_Gamification_Manager')) {
            $nivel_usuario = $this->obtener_nivel_gamificacion($usuario_id);
            if ($nivel_usuario >= 5) {
                $descuento_total += 0.05;
            }
        }

        $descuento_maximo = 0.25;
        $descuento_aplicado = min($descuento_total, $descuento_maximo);

        $precio_final = $precio_base * (1 - $descuento_aplicado);

        return [
            'precio_base' => $precio_base,
            'descuento_porcentaje' => $descuento_aplicado * 100,
            'descuento_importe' => $precio_base - $precio_final,
            'precio_final' => round($precio_final, 2),
            'detalles_descuento' => [
                'frecuencia' => $reservas_mes >= 3,
                'larga_duracion' => $dias_reserva >= 3,
                'fidelidad' => isset($nivel_usuario) && $nivel_usuario >= 5,
            ],
        ];
    }

    /**
     * Contar reservas del usuario en el mes actual
     */
    private function contar_reservas_usuario_mes($usuario_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE usuario_id = %d
             AND estado IN ('completada', 'activa', 'confirmada')
             AND MONTH(fecha_creacion) = MONTH(CURDATE())
             AND YEAR(fecha_creacion) = YEAR(CURDATE())",
            $usuario_id
        ));
    }

    /**
     * Obtener nivel de gamificacion del usuario
     */
    private function obtener_nivel_gamificacion($usuario_id) {
        if (class_exists('Flavor_Gamification_Manager')) {
            $gestor = Flavor_Gamification_Manager::get_instance();
            return $gestor->get_user_level($usuario_id);
        }
        return 1;
    }

    // =========================================================================
    // OCUPACION EN TIEMPO REAL
    // =========================================================================

    /**
     * Obtener ocupacion en tiempo real
     */
    public function obtener_ocupacion_tiempo_real($parking_id) {
        global $wpdb;

        $total_plazas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_plazas}
             WHERE parking_id = %d AND estado != 'bloqueada'",
            $parking_id
        ));

        $plazas_por_estado = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as cantidad
             FROM {$this->tabla_plazas}
             WHERE parking_id = %d
             GROUP BY estado",
            $parking_id
        ), OBJECT_K);

        $ocupadas = intval($plazas_por_estado['ocupada']->cantidad ?? 0);
        $reservadas = intval($plazas_por_estado['reservada']->cantidad ?? 0);
        $mantenimiento = intval($plazas_por_estado['mantenimiento']->cantidad ?? 0);
        $libres = intval($plazas_por_estado['libre']->cantidad ?? 0);

        $porcentaje_ocupacion = $total_plazas > 0 ? (($ocupadas + $reservadas) / $total_plazas) * 100 : 0;

        $tendencia = $this->calcular_tendencia_ocupacion($parking_id);

        return [
            'parking_id' => $parking_id,
            'timestamp' => current_time('mysql'),
            'total' => intval($total_plazas),
            'libres' => $libres,
            'ocupadas' => $ocupadas,
            'reservadas' => $reservadas,
            'mantenimiento' => $mantenimiento,
            'porcentaje_ocupacion' => round($porcentaje_ocupacion, 1),
            'tendencia' => $tendencia,
            'estado_general' => $this->determinar_estado_general($porcentaje_ocupacion),
            'proximas_reservas' => $this->obtener_proximas_reservas_parking($parking_id, 5),
            'tiempo_medio_estancia' => $this->calcular_tiempo_medio_estancia($parking_id),
        ];
    }

    /**
     * Calcular tendencia de ocupacion
     */
    private function calcular_tendencia_ocupacion($parking_id) {
        global $wpdb;

        $hace_una_hora = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE p.parking_id = %d
             AND r.estado = 'activa'
             AND r.hora_entrada <= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $parking_id
        ));

        $ahora = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_plazas}
             WHERE parking_id = %d AND estado = 'ocupada'",
            $parking_id
        ));

        if ($ahora > $hace_una_hora) {
            return 'subiendo';
        } elseif ($ahora < $hace_una_hora) {
            return 'bajando';
        }
        return 'estable';
    }

    /**
     * Determinar estado general del parking
     */
    private function determinar_estado_general($porcentaje) {
        if ($porcentaje >= 95) {
            return 'lleno';
        } elseif ($porcentaje >= 80) {
            return 'casi_lleno';
        } elseif ($porcentaje >= 50) {
            return 'ocupado';
        } elseif ($porcentaje >= 20) {
            return 'disponible';
        }
        return 'vacio';
    }

    /**
     * Obtener proximas reservas del parking
     */
    private function obtener_proximas_reservas_parking($parking_id, $limite = 5) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.fecha_inicio, r.fecha_fin, p.numero_plaza
             FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE p.parking_id = %d
             AND r.estado = 'confirmada'
             AND r.fecha_inicio > NOW()
             ORDER BY r.fecha_inicio ASC
             LIMIT %d",
            $parking_id,
            $limite
        ));
    }

    /**
     * Calcular tiempo medio de estancia
     */
    private function calcular_tiempo_medio_estancia($parking_id) {
        global $wpdb;

        $tiempo_medio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida))
             FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE p.parking_id = %d
             AND r.estado = 'completada'
             AND r.hora_entrada IS NOT NULL
             AND r.hora_salida IS NOT NULL
             AND r.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $parking_id
        ));

        return intval($tiempo_medio);
    }

    // =========================================================================
    // API ADICIONAL PARA TIEMPO REAL
    // =========================================================================

    /**
     * Registrar endpoint de ocupacion en tiempo real
     */
    public function registrar_endpoint_ocupacion_tiempo_real() {
        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)/ocupacion-tiempo-real', [
            'methods' => 'GET',
            'callback' => [$this, 'api_ocupacion_tiempo_real'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)/tarifas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_tarifas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/calcular-precio', [
            'methods' => 'POST',
            'callback' => [$this, 'api_calcular_precio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/parkings/(?P<id>\d+)/historico-ocupacion', [
            'methods' => 'GET',
            'callback' => [$this, 'api_historico_ocupacion'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * API: Ocupacion en tiempo real
     */
    public function api_ocupacion_tiempo_real($request) {
        $parking_id = intval($request['id']);
        $ocupacion = $this->obtener_ocupacion_tiempo_real($parking_id);

        if (!$ocupacion) {
            return new WP_REST_Response(['error' => __('Parking no encontrado', 'flavor-chat-ia')], 404);
        }

        return new WP_REST_Response($ocupacion, 200);
    }

    /**
     * API: Obtener tarifas
     */
    public function api_obtener_tarifas($request) {
        $parking_id = intval($request['id']);
        $tarifas = $this->obtener_tarifas_parking($parking_id);

        if (!$tarifas) {
            return new WP_REST_Response(['error' => __('Parking no encontrado', 'flavor-chat-ia')], 404);
        }

        return new WP_REST_Response($tarifas, 200);
    }

    /**
     * API: Calcular precio con descuentos
     */
    public function api_calcular_precio($request) {
        $datos = $request->get_json_params();

        $plaza_id = intval($datos['plaza_id'] ?? 0);
        $fecha_inicio = sanitize_text_field($datos['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($datos['fecha_fin'] ?? '');
        $usuario_id = get_current_user_id();

        $plaza = $this->obtener_plaza($plaza_id);
        if (!$plaza) {
            return new WP_REST_Response(['error' => __('Plaza no encontrada', 'flavor-chat-ia')], 404);
        }

        $precio = $this->calcular_precio_con_descuentos($plaza, $fecha_inicio, $fecha_fin, $usuario_id);

        return new WP_REST_Response($precio, 200);
    }

    /**
     * API: Historico de ocupacion
     */
    public function api_historico_ocupacion($request) {
        $parking_id = intval($request['id']);
        $dias = intval($request->get_param('dias') ?? 7);

        $historico = $this->obtener_historico_ocupacion($parking_id, $dias);

        return new WP_REST_Response([
            'parking_id' => $parking_id,
            'dias' => $dias,
            'historico' => $historico,
        ], 200);
    }

    /**
     * Obtener historico de ocupacion
     */
    private function obtener_historico_ocupacion($parking_id, $dias = 7) {
        global $wpdb;

        $historico = [];

        for ($desplazamiento = 0; $desplazamiento < $dias; $desplazamiento++) {
            $fecha = date('Y-m-d', strtotime("-{$desplazamiento} days"));

            $reservas_dia = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} r
                 INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
                 WHERE p.parking_id = %d
                 AND DATE(r.fecha_inicio) = %s",
                $parking_id,
                $fecha
            ));

            $historico[$fecha] = [
                'reservas' => intval($reservas_dia),
            ];
        }

        return $historico;
    }

    // =========================================================================
    // ACCIONES ADICIONALES DEL MODULO
    // =========================================================================

    /**
     * Accion: Ver tarifas del parking
     */
    private function action_ver_tarifas($params) {
        $parking_id = intval($params['parking_id'] ?? 0);
        $tarifas = $this->obtener_tarifas_parking($parking_id);

        if (!$tarifas) {
            return ['success' => false, 'error' => __('Parking no encontrado', 'flavor-chat-ia')];
        }

        return [
            'success' => true,
            'tarifas' => $tarifas,
        ];
    }

    /**
     * Accion: Ver ocupacion tiempo real
     */
    private function action_ocupacion_tiempo_real($params) {
        $parking_id = intval($params['parking_id'] ?? 0);
        $ocupacion = $this->obtener_ocupacion_tiempo_real($parking_id);

        if (!$ocupacion) {
            return ['success' => false, 'error' => __('Parking no encontrado', 'flavor-chat-ia')];
        }

        return [
            'success' => true,
            'ocupacion' => $ocupacion,
        ];
    }

    /**
     * Accion: Valorar reserva
     */
    private function action_valorar_reserva($params) {
        $reserva_id = intval($params['reserva_id'] ?? 0);
        $valoracion = intval($params['valoracion'] ?? 0);
        $comentario = sanitize_textarea_field($params['comentario'] ?? '');
        $usuario_id = get_current_user_id();

        if ($valoracion < 1 || $valoracion > 5) {
            return ['success' => false, 'error' => __('La valoracion debe estar entre 1 y 5', 'flavor-chat-ia')];
        }

        $reserva = $this->obtener_reserva($reserva_id);
        if (!$reserva || $reserva->usuario_id != $usuario_id) {
            return ['success' => false, 'error' => __('Reserva no encontrada', 'flavor-chat-ia')];
        }

        if ($reserva->estado !== 'completada') {
            return ['success' => false, 'error' => __('Solo puedes valorar reservas completadas', 'flavor-chat-ia')];
        }

        if ($reserva->valoracion) {
            return ['success' => false, 'error' => __('Esta reserva ya ha sido valorada', 'flavor-chat-ia')];
        }

        global $wpdb;
        $wpdb->update($this->tabla_reservas, [
            'valoracion' => $valoracion,
            'comentario_valoracion' => $comentario,
        ], ['id' => $reserva_id]);

        $plaza = $this->obtener_plaza($reserva->plaza_id);
        $this->actualizar_valoracion_parking($plaza->parking_id);

        $this->otorgar_puntos_gamificacion($usuario_id, 'parking_valorar', 5);
        $this->incrementar_contador_gamificacion($usuario_id, 'valoraciones');

        return [
            'success' => true,
            'message' => __('Valoracion registrada correctamente. Gracias!', 'flavor-chat-ia'),
        ];
    }

    /**
     * Actualizar valoracion media del parking
     */
    private function actualizar_valoracion_parking($parking_id) {
        global $wpdb;

        $valoracion_media = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(r.valoracion)
             FROM {$this->tabla_reservas} r
             INNER JOIN {$this->tabla_plazas} p ON r.plaza_id = p.id
             WHERE p.parking_id = %d
             AND r.valoracion IS NOT NULL",
            $parking_id
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_parkings}
             SET configuracion = JSON_SET(COALESCE(configuracion, '{}'), '$.valoracion_media', %f)
             WHERE id = %d",
            floatval($valoracion_media),
            $parking_id
        ));
    }

    /**
     * Accion: Compartir plaza
     */
    private function action_compartir_plaza($params) {
        $plaza_id = intval($params['plaza_id'] ?? 0);
        $fecha_desde = sanitize_text_field($params['fecha_desde'] ?? '');
        $fecha_hasta = sanitize_text_field($params['fecha_hasta'] ?? '');
        $usuario_id = get_current_user_id();

        $plaza = $this->obtener_plaza($plaza_id);
        if (!$plaza || $plaza->propietario_id != $usuario_id) {
            return ['success' => false, 'error' => __('No eres el propietario de esta plaza', 'flavor-chat-ia')];
        }

        global $wpdb;
        $wpdb->update($this->tabla_plazas, [
            'disponible_alquiler' => 1,
        ], ['id' => $plaza_id]);

        $this->otorgar_puntos_gamificacion($usuario_id, 'parking_compartir_disponibilidad', 25);
        $this->incrementar_contador_gamificacion($usuario_id, 'compartidas');

        $this->notificar_lista_espera_plaza_disponible($plaza_id);

        return [
            'success' => true,
            'message' => __('Tu plaza esta ahora disponible para alquiler. Gracias por compartir!', 'flavor-chat-ia'),
        ];
    }

    // =========================================================================
    // CRON JOBS ADICIONALES
    // =========================================================================

    /**
     * Programar tareas cron adicionales
     */
    public function programar_tareas_cron() {
        if (!wp_next_scheduled('flavor_parkings_enviar_recordatorios')) {
            wp_schedule_event(time(), 'hourly', 'flavor_parkings_enviar_recordatorios');
        }
        add_action('flavor_parkings_enviar_recordatorios', [$this, 'enviar_recordatorios_reservas']);

        if (!wp_next_scheduled('flavor_parkings_notificar_expirando')) {
            wp_schedule_event(time(), 'every_fifteen_minutes', 'flavor_parkings_notificar_expirando');
        }
        add_action('flavor_parkings_notificar_expirando', [$this, 'notificar_reservas_expirando']);

        if (!wp_next_scheduled('flavor_parkings_limpiar_expiradas')) {
            wp_schedule_event(time(), 'daily', 'flavor_parkings_limpiar_expiradas');
        }
        add_action('flavor_parkings_limpiar_expiradas', [$this, 'limpiar_solicitudes_expiradas']);
    }

    /**
     * Limpiar solicitudes de lista de espera expiradas
     */
    public function limpiar_solicitudes_expiradas() {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_lista_espera}
             SET estado = 'expirado'
             WHERE estado = 'activo'
             AND fecha_limite_interes IS NOT NULL
             AND fecha_limite_interes < %s",
            current_time('Y-m-d')
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_lista_espera}
             SET estado = 'expirado'
             WHERE estado = 'activo'
             AND ofertas_rechazadas >= %d",
            3
        ));
    }

    // =========================================================================
    // SHORTCODES ADICIONALES
    // =========================================================================

    /**
     * Shortcode: Tarifas del parking
     */
    public function shortcode_tarifas_parking($atributos) {
        $atributos = shortcode_atts([
            'parking_id' => 0,
            'mostrar_descuentos' => true,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $tarifas = $this->obtener_tarifas_parking($parking_id);
        $parking = $this->obtener_parking($parking_id);

        if (!$tarifas || !$parking) {
            return '<div class="flavor-parkings-error">' . __('Parking no encontrado', 'flavor-chat-ia') . '</div>';
        }

        ob_start();
        ?>
        <div class="flavor-parkings-tarifas">
            <h4><?php echo esc_html($parking->nombre); ?> - <?php _e('Tarifas', 'flavor-chat-ia'); ?></h4>
            <div class="tarifas-grid">
                <div class="tarifa-item">
                    <span class="tarifa-tipo"><?php _e('Por hora', 'flavor-chat-ia'); ?></span>
                    <span class="tarifa-precio"><?php echo number_format($tarifas['hora_visitante'], 2); ?> EUR</span>
                </div>
                <div class="tarifa-item">
                    <span class="tarifa-tipo"><?php _e('Por dia', 'flavor-chat-ia'); ?></span>
                    <span class="tarifa-precio"><?php echo number_format($tarifas['dia_visitante'], 2); ?> EUR</span>
                </div>
                <div class="tarifa-item">
                    <span class="tarifa-tipo"><?php _e('Mensual (residente)', 'flavor-chat-ia'); ?></span>
                    <span class="tarifa-precio"><?php echo number_format($tarifas['mes_residente'], 2); ?> EUR</span>
                </div>
            </div>
            <?php if ($atributos['mostrar_descuentos']): ?>
            <div class="descuentos-info">
                <h5><?php _e('Descuentos disponibles', 'flavor-chat-ia'); ?></h5>
                <ul>
                    <li><?php _e('5% si haces 3+ reservas al mes', 'flavor-chat-ia'); ?></li>
                    <li><?php _e('10% si haces 5+ reservas al mes', 'flavor-chat-ia'); ?></li>
                    <li><?php _e('8% para reservas de 3+ dias', 'flavor-chat-ia'); ?></li>
                    <li><?php _e('15% para reservas de 7+ dias', 'flavor-chat-ia'); ?></li>
                    <li><?php _e('5% extra para usuarios nivel 5+', 'flavor-chat-ia'); ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ocupacion en tiempo real
     */
    public function shortcode_ocupacion_tiempo_real($atributos) {
        $atributos = shortcode_atts([
            'parking_id' => 0,
            'auto_refresh' => true,
            'intervalo' => 30,
        ], $atributos);

        wp_enqueue_style('flavor-parkings');
        wp_enqueue_script('flavor-parkings');

        $parking_id = intval($atributos['parking_id']);
        $ocupacion = $this->obtener_ocupacion_tiempo_real($parking_id);

        if (!$ocupacion) {
            return '<div class="flavor-parkings-error">' . __('Parking no encontrado', 'flavor-chat-ia') . '</div>';
        }

        $clases_estado = [
            'lleno' => 'estado-rojo',
            'casi_lleno' => 'estado-naranja',
            'ocupado' => 'estado-amarillo',
            'disponible' => 'estado-verde',
            'vacio' => 'estado-verde',
        ];

        ob_start();
        ?>
        <div class="flavor-parkings-ocupacion-tiempo-real <?php echo esc_attr($clases_estado[$ocupacion['estado_general']] ?? ''); ?>"
             data-parking-id="<?php echo esc_attr($parking_id); ?>"
             data-auto-refresh="<?php echo esc_attr($atributos['auto_refresh'] ? '1' : '0'); ?>"
             data-intervalo="<?php echo esc_attr($atributos['intervalo']); ?>">

            <div class="ocupacion-header">
                <span class="estado-badge"><?php echo esc_html(ucfirst(str_replace('_', ' ', $ocupacion['estado_general']))); ?></span>
                <span class="ultima-actualizacion"><?php echo esc_html(date_i18n('H:i:s', strtotime($ocupacion['timestamp']))); ?></span>
            </div>

            <div class="ocupacion-grafico">
                <div class="barra-ocupacion">
                    <div class="barra-llena" style="width: <?php echo esc_attr($ocupacion['porcentaje_ocupacion']); ?>%;"></div>
                </div>
                <span class="porcentaje"><?php echo esc_html($ocupacion['porcentaje_ocupacion']); ?>%</span>
            </div>

            <div class="ocupacion-detalles">
                <div class="detalle-item">
                    <span class="valor"><?php echo intval($ocupacion['libres']); ?></span>
                    <span class="etiqueta"><?php _e('Libres', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="detalle-item">
                    <span class="valor"><?php echo intval($ocupacion['ocupadas']); ?></span>
                    <span class="etiqueta"><?php _e('Ocupadas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="detalle-item">
                    <span class="valor"><?php echo intval($ocupacion['reservadas']); ?></span>
                    <span class="etiqueta"><?php _e('Reservadas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="ocupacion-tendencia tendencia-<?php echo esc_attr($ocupacion['tendencia']); ?>">
                <?php if ($ocupacion['tendencia'] === 'subiendo'): ?>
                    <span class="icono">&#8593;</span> <?php _e('La ocupacion esta subiendo', 'flavor-chat-ia'); ?>
                <?php elseif ($ocupacion['tendencia'] === 'bajando'): ?>
                    <span class="icono">&#8595;</span> <?php _e('La ocupacion esta bajando', 'flavor-chat-ia'); ?>
                <?php else: ?>
                    <span class="icono">&#8596;</span> <?php _e('Ocupacion estable', 'flavor-chat-ia'); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registrar shortcodes adicionales
     */
    public function registrar_shortcodes_adicionales() {
        add_shortcode('flavor_tarifas_parking', [$this, 'shortcode_tarifas_parking']);
        add_shortcode('flavor_ocupacion_tiempo_real', [$this, 'shortcode_ocupacion_tiempo_real']);
    }

    /**
     * Configuración para el panel unificado de gestión
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'parkings',
            'label' => __('Parkings', 'flavor-chat-ia'),
            'icon' => 'dashicons-building',
            'capability' => 'manage_options',
            'categoria' => 'servicios',
            'paginas' => [
                [
                    'slug' => 'flavor-parkings-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-parkings-plazas',
                    'titulo' => __('Plazas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_plazas'],
                    'badge' => [$this, 'contar_plazas_disponibles'],
                ],
                [
                    'slug' => 'flavor-parkings-configuracion',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Renderiza el dashboard de administración de parkings
     */
    public function render_admin_dashboard() {
        $this->render_page_header(
            __('Dashboard de Parkings', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Plaza', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=flavor-parkings-plazas&action=nueva'),
                    'class' => 'button-primary',
                ],
            ]
        );

        $estadisticas = $this->get_estadisticas_dashboard();
        ?>
        <div class="wrap flavor-admin-dashboard">
            <div class="flavor-stats-grid">
                <?php foreach ($estadisticas as $estadistica): ?>
                    <div class="flavor-stat-card" style="border-left-color: <?php echo esc_attr($estadistica['color'] ?? '#0073aa'); ?>">
                        <span class="dashicons <?php echo esc_attr($estadistica['icon']); ?>"></span>
                        <div class="stat-content">
                            <span class="stat-value"><?php echo esc_html($estadistica['valor']); ?></span>
                            <span class="stat-label"><?php echo esc_html($estadistica['label']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de plazas
     */
    public function render_admin_plazas() {
        global $wpdb;

        $this->render_page_header(
            __('Gestión de Plazas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Plaza', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=flavor-parkings-plazas&action=nueva'),
                    'class' => 'button-primary',
                ],
            ]
        );

        $plazas = [];
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_plazas)) {
            $plazas = $wpdb->get_results(
                "SELECT p.*, pk.nombre as parking_nombre
                 FROM {$this->tabla_plazas} p
                 LEFT JOIN {$this->tabla_parkings} pk ON p.parking_id = pk.id
                 ORDER BY pk.nombre, p.numero"
            );
        }
        ?>
        <div class="wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Número', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Parking', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plazas)): ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No hay plazas registradas.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plazas as $plaza): ?>
                            <tr>
                                <td><?php echo esc_html($plaza->numero); ?></td>
                                <td><?php echo esc_html($plaza->parking_nombre); ?></td>
                                <td><?php echo esc_html(ucfirst($plaza->tipo ?? 'normal')); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo esc_attr($plaza->estado); ?>">
                                        <?php echo esc_html(ucfirst($plaza->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&action=editar&id=' . $plaza->id)); ?>" class="button button-small">
                                        <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza la página de configuración del módulo
     */
    public function render_admin_configuracion() {
        $this->render_page_header(__('Configuración de Parkings', 'flavor-chat-ia'));

        $configuracion = $this->get_settings();
        ?>
        <div class="wrap">
            <form method="post" action="">
                <?php wp_nonce_field('flavor_parkings_config', 'parkings_config_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="precio_hora"><?php esc_html_e('Precio por hora', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="precio_hora" id="precio_hora"
                                   value="<?php echo esc_attr($configuracion['precio_hora'] ?? '1.50'); ?>" class="small-text"> <?php echo esc_html__('&euro;', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="precio_dia"><?php esc_html_e('Precio por día', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="precio_dia" id="precio_dia"
                                   value="<?php echo esc_attr($configuracion['precio_dia'] ?? '10.00'); ?>" class="small-text"> <?php echo esc_html__('&euro;', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="precio_mes"><?php esc_html_e('Precio mensual', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="precio_mes" id="precio_mes"
                                   value="<?php echo esc_attr($configuracion['precio_mes'] ?? '80.00'); ?>" class="small-text"> <?php echo esc_html__('&euro;', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="permite_reserva_temporal"><?php esc_html_e('Permitir reserva temporal', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="permite_reserva_temporal" id="permite_reserva_temporal" value="1"
                                   <?php checked($configuracion['permite_reserva_temporal'] ?? true); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="permite_rotacion"><?php esc_html_e('Permitir rotación', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="permite_rotacion" id="permite_rotacion" value="1"
                                   <?php checked($configuracion['permite_rotacion'] ?? true); ?>>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Guardar configuración', 'flavor-chat-ia')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Cuenta plazas disponibles para el badge
     *
     * @return int
     */
    public function contar_plazas_disponibles() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_plazas)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_plazas} WHERE estado = %s",
                self::ESTADO_PLAZA_LIBRE
            )
        );
    }

    /**
     * Obtiene estadísticas para el dashboard del panel unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;

        $total_plazas = 0;
        $plazas_libres = 0;
        $plazas_ocupadas = 0;
        $reservas_activas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_plazas)) {
            $total_plazas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_plazas}");
            $plazas_libres = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$this->tabla_plazas} WHERE estado = %s", self::ESTADO_PLAZA_LIBRE)
            );
            $plazas_ocupadas = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$this->tabla_plazas} WHERE estado = %s", self::ESTADO_PLAZA_OCUPADA)
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_reservas)) {
            $reservas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE estado = 'activa'"
            );
        }

        return [
            [
                'icon' => 'dashicons-building',
                'valor' => $total_plazas,
                'label' => __('Total plazas', 'flavor-chat-ia'),
                'color' => '#0073aa',
                'enlace' => admin_url('admin.php?page=flavor-parkings-plazas'),
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $plazas_libres,
                'label' => __('Plazas libres', 'flavor-chat-ia'),
                'color' => '#46b450',
                'enlace' => admin_url('admin.php?page=flavor-parkings-plazas&estado=libre'),
            ],
            [
                'icon' => 'dashicons-marker',
                'valor' => $plazas_ocupadas,
                'label' => __('Plazas ocupadas', 'flavor-chat-ia'),
                'color' => '#dc3232',
                'enlace' => admin_url('admin.php?page=flavor-parkings-plazas&estado=ocupada'),
            ],
            [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $reservas_activas,
                'label' => __('Reservas activas', 'flavor-chat-ia'),
                'color' => '#ffb900',
                'enlace' => admin_url('admin.php?page=flavor-parkings-plazas&tab=reservas'),
            ],
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('parkings');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('parkings');
        if (!$pagina && !get_option('flavor_parkings_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['parkings']);
            update_option('flavor_parkings_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Parkings Comunitarios', 'flavor-chat-ia'),
                'slug' => 'parkings',
                'content' => '<h1>' . __('Parkings Comunitarios', 'flavor-chat-ia') . '</h1>
<p>' . __('Encuentra y reserva plazas de aparcamiento en tu barrio. Consulta disponibilidad en tiempo real y gestiona tus reservas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="parkings" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Reservar Plaza', 'flavor-chat-ia'),
                'slug' => 'parkings/reservar',
                'content' => '<h1>' . __('Reservar Plaza de Parking', 'flavor-chat-ia') . '</h1>
<p>' . __('Selecciona el parking, la fecha y hora para reservar tu plaza de aparcamiento.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="parkings" action="reservar"]',
                'parent' => 'parkings',
            ],
            [
                'title' => __('Mis Reservas', 'flavor-chat-ia'),
                'slug' => 'parkings/mis-reservas',
                'content' => '<h1>' . __('Mis Reservas de Parking', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta y gestiona tus reservas de plazas de aparcamiento activas e históricas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="parkings" action="mis_reservas" columnas="2" limite="20"]',
                'parent' => 'parkings',
            ],
        ];
    }
}
