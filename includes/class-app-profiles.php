<?php
/**
 * Gestión de Perfiles de Aplicación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona los perfiles de aplicación
 */
class Flavor_App_Profiles {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Perfiles disponibles
     */
    private $perfiles_disponibles = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_Profiles
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
        $this->definir_perfiles();
    }

    /**
     * Define los perfiles de aplicación disponibles
     */
    private function definir_perfiles() {
        $this->perfiles_disponibles = [
            'tienda' => [
                'nombre' => __('Tienda Online', 'flavor-chat-ia'),
                'descripcion' => __('Tienda online con carrito, productos y chat de atención al cliente.', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'modulos_requeridos' => ['woocommerce'],
                'modulos_opcionales' => ['marketplace', 'facturas', 'advertising', 'chat_interno'],
                'color' => '#00a0d2',
            ],
            'grupo_consumo' => [
                'nombre' => __('Grupo de Consumo', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de pedidos colectivos, productores locales y repartos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'modulos_requeridos' => ['grupos_consumo'],
                'modulos_opcionales' => ['eventos', 'socios', 'marketplace', 'chat_grupos'],
                'color' => '#46b450',
            ],
            'restaurante' => [
                'nombre' => __('Restaurante', 'flavor-chat-ia'),
                'descripcion' => __('Menús, reservas de mesas, pedidos online y gestión de comensales.', 'flavor-chat-ia'),
                'icono' => 'dashicons-food',
                'modulos_requeridos' => ['woocommerce', 'reservas'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'facturas', 'fichaje_empleados'],
                'color' => '#f56e28',
            ],
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'descripcion' => __('Intercambio de servicios por horas entre miembros de la comunidad.', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'modulos_requeridos' => ['banco_tiempo', 'socios'],
                'modulos_opcionales' => ['eventos', 'talleres', 'ayuda_vecinal', 'chat_grupos'],
                'color' => '#9b59b6',
            ],
            'comunidad' => [
                'nombre' => __('Comunidad/Asociación', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de socios, eventos, foro y recursos compartidos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'modulos_requeridos' => ['socios', 'eventos'],
                'modulos_opcionales' => ['talleres', 'marketplace', 'participacion', 'chat_grupos', 'chat_interno', 'multimedia', 'cursos', 'red_social', 'reservas'],
                'color' => '#e91e63',
            ],
            'coworking' => [
                'nombre' => __('Coworking', 'flavor-chat-ia'),
                'descripcion' => __('Reservas de espacios, control de acceso, membresías y comunidad.', 'flavor-chat-ia'),
                'icono' => 'dashicons-building',
                'modulos_requeridos' => ['espacios_comunes', 'socios', 'fichaje_empleados', 'reservas'],
                'modulos_opcionales' => ['eventos', 'chat_interno', 'facturas', 'chat_grupos'],
                'color' => '#00bcd4',
            ],
            'marketplace' => [
                'nombre' => __('Marketplace Comunitario', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma para regalo, venta, cambio y alquiler entre usuarios.', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
                'modulos_requeridos' => ['marketplace'],
                'modulos_opcionales' => ['advertising', 'chat_interno', 'multimedia'],
                'color' => '#ff9800',
            ],
            'ayuntamiento' => [
                'nombre' => __('Ayuntamiento / Portal Ciudadano', 'flavor-chat-ia'),
                'descripcion' => __('Portal de servicios municipales: trámites, noticias, cita previa y atención ciudadana.', 'flavor-chat-ia'),
                'icono' => 'dashicons-building',
                'modulos_requeridos' => ['avisos_municipales', 'incidencias'],
                'modulos_opcionales' => ['participacion', 'presupuestos_participativos', 'eventos', 'espacios_comunes', 'biblioteca', 'tramites', 'transparencia'],
                'color' => '#1d4ed8',
            ],
            'barrio' => [
                'nombre' => __('Barrio / Vecindario', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma vecinal completa con ayuda mutua, huertos, bicicletas y recursos compartidos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-location',
                'modulos_requeridos' => ['ayuda_vecinal'],
                'modulos_opcionales' => ['huertos_urbanos', 'bicicletas_compartidas', 'espacios_comunes', 'banco_tiempo', 'incidencias', 'reciclaje', 'compostaje', 'carpooling', 'talleres', 'eventos', 'chat_grupos', 'reservas'],
                'color' => '#22c55e',
            ],
            'academia' => [
                'nombre' => __('Academia Online', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma de formación online con cursos, talleres y certificados.', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'modulos_requeridos' => ['cursos', 'talleres'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'socios', 'chat_grupos', 'biblioteca'],
                'color' => '#7c3aed',
            ],
            'radio_comunitaria' => [
                'nombre' => __('Radio Comunitaria', 'flavor-chat-ia'),
                'descripcion' => __('Emisora de radio y podcast comunitaria con programación y participación vecinal.', 'flavor-chat-ia'),
                'icono' => 'dashicons-controls-volumeon',
                'modulos_requeridos' => ['radio', 'podcast'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'socios', 'chat_grupos'],
                'color' => '#dc2626',
            ],
            'cooperativa' => [
                'nombre' => __('Cooperativa', 'flavor-chat-ia'),
                'descripcion' => __('Gestión cooperativa con gobernanza democrática, transparencia y participación.', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-users',
                'modulos_requeridos' => ['socios', 'transparencia', 'participacion'],
                'modulos_opcionales' => ['eventos', 'presupuestos_participativos', 'chat_grupos', 'chat_interno', 'talleres'],
                'color' => '#059669',
            ],
            'hosteleria' => [
                'nombre' => __('Hostelería', 'flavor-chat-ia'),
                'descripcion' => __('Bar, cafetería o restaurante con carta digital, pedidos y reservas.', 'flavor-chat-ia'),
                'icono' => 'dashicons-food',
                'modulos_requeridos' => ['bares', 'woocommerce', 'reservas'],
                'modulos_opcionales' => ['eventos', 'facturas', 'fichaje_empleados', 'multimedia'],
                'color' => '#ea580c',
            ],
            'smart_village' => [
                'nombre' => __('Smart Village', 'flavor-chat-ia'),
                'descripcion' => __('Pueblo inteligente con avisos, ayuda vecinal, huertos y movilidad sostenible.', 'flavor-chat-ia'),
                'icono' => 'dashicons-location',
                'modulos_requeridos' => ['avisos_municipales', 'ayuda_vecinal'],
                'modulos_opcionales' => ['huertos_urbanos', 'bicicletas_compartidas', 'incidencias', 'espacios_comunes', 'eventos', 'chat_grupos', 'carpooling'],
                'color' => '#16a34a',
            ],
            'club_deportivo' => [
                'nombre' => __('Club Deportivo', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de un club deportivo con socios, eventos, cuotas y comunicación.', 'flavor-chat-ia'),
                'icono' => 'dashicons-universal-access',
                'modulos_requeridos' => ['socios', 'eventos', 'reservas'],
                'modulos_opcionales' => ['talleres', 'chat_grupos', 'multimedia', 'espacios_comunes', 'fichaje_empleados'],
                'color' => '#2563eb',
            ],
            'ong' => [
                'nombre' => __('ONG / Fundación', 'flavor-chat-ia'),
                'descripcion' => __('Organización sin ánimo de lucro con transparencia, socios y proyectos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-heart',
                'modulos_requeridos' => ['socios', 'transparencia'],
                'modulos_opcionales' => ['eventos', 'participacion', 'chat_grupos', 'multimedia', 'cursos'],
                'color' => '#be185d',
            ],
            'personalizado' => [
                'nombre' => __('Personalizado', 'flavor-chat-ia'),
                'descripcion' => __('Selecciona manualmente los módulos que necesitas.', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-generic',
                'modulos_requeridos' => [],
                'modulos_opcionales' => [],
                'color' => '#95a5a6',
            ],
        ];

        // Permitir que otros plugins/temas añadan perfiles
        $this->perfiles_disponibles = apply_filters('flavor_chat_ia_app_profiles', $this->perfiles_disponibles);
    }

    /**
     * Obtiene todos los perfiles disponibles
     *
     * @return array
     */
    public function obtener_perfiles() {
        return $this->perfiles_disponibles;
    }

    /**
     * Obtiene un perfil específico
     *
     * @param string $perfil_id
     * @return array|null
     */
    public function obtener_perfil($perfil_id) {
        return $this->perfiles_disponibles[$perfil_id] ?? null;
    }

    /**
     * Obtiene el perfil activo actualmente
     *
     * @return string
     */
    public function obtener_perfil_activo() {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        return $configuracion['app_profile'] ?? 'personalizado';
    }

    /**
     * Establece el perfil activo
     *
     * @param string $perfil_id
     * @return bool
     */
    public function establecer_perfil($perfil_id) {
        if (!isset($this->perfiles_disponibles[$perfil_id])) {
            return false;
        }

        $perfil = $this->perfiles_disponibles[$perfil_id];
        $configuracion = get_option('flavor_chat_ia_settings', []);

        // Actualizar perfil activo
        $configuracion['app_profile'] = $perfil_id;

        // Si no es personalizado, activar módulos del perfil
        if ($perfil_id !== 'personalizado') {
            $modulos_activos = array_merge(
                $perfil['modulos_requeridos'],
                $configuracion['modulos_opcionales_activos'] ?? []
            );
            $configuracion['active_modules'] = $modulos_activos;
        }

        update_option('flavor_chat_ia_settings', $configuracion);

        return true;
    }

    /**
     * Obtiene los módulos requeridos por el perfil activo
     *
     * @return array
     */
    public function obtener_modulos_requeridos() {
        $perfil_id = $this->obtener_perfil_activo();
        $perfil = $this->obtener_perfil($perfil_id);

        return $perfil['modulos_requeridos'] ?? [];
    }

    /**
     * Obtiene los módulos opcionales del perfil activo
     *
     * @return array
     */
    public function obtener_modulos_opcionales() {
        $perfil_id = $this->obtener_perfil_activo();
        $perfil = $this->obtener_perfil($perfil_id);

        return $perfil['modulos_opcionales'] ?? [];
    }

    /**
     * Verifica si un módulo está disponible en el perfil activo
     *
     * @param string $modulo_id
     * @return bool
     */
    public function es_modulo_disponible($modulo_id) {
        $perfil_id = $this->obtener_perfil_activo();

        if ($perfil_id === 'personalizado') {
            return true; // En modo personalizado todo está disponible
        }

        $perfil = $this->obtener_perfil($perfil_id);
        $todos_modulos = array_merge(
            $perfil['modulos_requeridos'] ?? [],
            $perfil['modulos_opcionales'] ?? []
        );

        return in_array($modulo_id, $todos_modulos);
    }

    /**
     * Activa un módulo opcional
     *
     * @param string $modulo_id
     * @return bool
     */
    public function activar_modulo_opcional($modulo_id) {
        if (!$this->es_modulo_disponible($modulo_id)) {
            return false;
        }

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        if (!in_array($modulo_id, $modulos_activos)) {
            $modulos_activos[] = $modulo_id;
            $configuracion['active_modules'] = $modulos_activos;
            update_option('flavor_chat_ia_settings', $configuracion);
        }

        return true;
    }

    /**
     * Obtiene los módulos organizados por categorías
     *
     * @return array Categorías con sus módulos
     */
    public function obtener_categorias_modulos() {
        return [
            'comercio' => [
                'nombre' => __('Comercio', 'flavor-chat-ia'),
                'icono'  => 'dashicons-store',
                'modulos' => ['woocommerce', 'marketplace', 'facturas', 'advertising'],
            ],
            'comunidad' => [
                'nombre' => __('Comunidad', 'flavor-chat-ia'),
                'icono'  => 'dashicons-groups',
                'modulos' => ['socios', 'foros', 'red_social', 'chat_grupos', 'chat_interno', 'comunidades', 'colectivos'],
            ],
            'gobernanza' => [
                'nombre' => __('Gobernanza', 'flavor-chat-ia'),
                'icono'  => 'dashicons-building',
                'modulos' => ['participacion', 'presupuestos_participativos', 'transparencia', 'avisos_municipales', 'tramites'],
            ],
            'sostenibilidad' => [
                'nombre' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icono'  => 'dashicons-palmtree',
                'modulos' => ['huertos_urbanos', 'bicicletas_compartidas', 'compostaje', 'reciclaje', 'carpooling'],
            ],
            'contenido' => [
                'nombre' => __('Contenido', 'flavor-chat-ia'),
                'icono'  => 'dashicons-media-interactive',
                'modulos' => ['cursos', 'podcast', 'radio', 'multimedia', 'biblioteca', 'talleres', 'eventos'],
            ],
            'operaciones' => [
                'nombre' => __('Operaciones', 'flavor-chat-ia'),
                'icono'  => 'dashicons-admin-tools',
                'modulos' => ['fichaje_empleados', 'incidencias', 'espacios_comunes', 'parkings', 'banco_tiempo', 'ayuda_vecinal', 'empresarial', 'clientes', 'bares', 'grupos_consumo'],
            ],
        ];
    }

    /**
     * Desactiva un módulo opcional (solo si no es requerido)
     *
     * @param string $modulo_id
     * @return bool
     */
    public function desactivar_modulo_opcional($modulo_id) {
        $modulos_requeridos = $this->obtener_modulos_requeridos();

        if (in_array($modulo_id, $modulos_requeridos)) {
            return false; // No se puede desactivar un módulo requerido
        }

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $clave = array_search($modulo_id, $modulos_activos);
        if ($clave !== false) {
            unset($modulos_activos[$clave]);
            $configuracion['active_modules'] = array_values($modulos_activos);
            update_option('flavor_chat_ia_settings', $configuracion);
        }

        return true;
    }
}
