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
                'nombre' => 'Tienda Online',
                'descripcion' => 'Tienda online con carrito, productos y chat de atención al cliente.',
                'icono' => 'dashicons-store',
                'modulos_requeridos' => ['woocommerce'],
                'modulos_opcionales' => ['marketplace', 'facturas', 'advertising', 'chat_interno'],
                'tipo_organizacion' => ['empresa'],
                'impacto_social' => [],
                'color' => '#00a0d2',
            ],
            'grupo_consumo' => [
                'nombre' => 'Grupo de Consumo',
                'descripcion' => 'Gestión de pedidos colectivos, productores locales y repartos.',
                'icono' => 'dashicons-carrot',
                'modulos_requeridos' => ['grupos_consumo'],
                'modulos_opcionales' => ['eventos', 'socios', 'marketplace', 'chat_grupos'],
                'tipo_organizacion' => ['comunidad', 'cooperativa'],
                'impacto_social' => ['consumo_local', 'economia_circular'],
                'color' => '#46b450',
            ],
            'restaurante' => [
                'nombre' => 'Restaurante',
                'descripcion' => 'Menús, reservas de mesas, pedidos online y gestión de comensales.',
                'icono' => 'dashicons-food',
                'modulos_requeridos' => ['woocommerce', 'reservas'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'facturas', 'fichaje_empleados'],
                'tipo_organizacion' => ['empresa', 'hosteleria'],
                'impacto_social' => [],
                'color' => '#f56e28',
            ],
            'banco_tiempo' => [
                'nombre' => 'Banco de Tiempo',
                'descripcion' => 'Intercambio de servicios por horas entre miembros de la comunidad.',
                'icono' => 'dashicons-clock',
                'modulos_requeridos' => ['banco_tiempo', 'socios'],
                'modulos_opcionales' => ['eventos', 'talleres', 'ayuda_vecinal', 'chat_grupos'],
                'tipo_organizacion' => ['comunidad', 'asociacion'],
                'impacto_social' => ['solidaridad', 'intercambio'],
                'color' => '#9b59b6',
            ],
            'comunidad' => [
                'nombre' => 'Comunidad/Asociación',
                'descripcion' => 'Gestión de socios, eventos, foro y recursos compartidos.',
                'icono' => 'dashicons-groups',
                'modulos_requeridos' => ['socios', 'eventos'],
                'modulos_opcionales' => ['talleres', 'marketplace', 'participacion', 'chat_grupos', 'chat_interno', 'multimedia', 'cursos', 'red_social', 'reservas'],
                'tipo_organizacion' => ['comunidad', 'asociacion'],
                'impacto_social' => ['cohesion_social'],
                'color' => '#e91e63',
            ],
            'coworking' => [
                'nombre' => 'Coworking',
                'descripcion' => 'Reservas de espacios, control de acceso, membresías y comunidad.',
                'icono' => 'dashicons-building',
                'modulos_requeridos' => ['espacios_comunes', 'socios', 'fichaje_empleados', 'reservas'],
                'modulos_opcionales' => ['eventos', 'chat_interno', 'facturas', 'chat_grupos'],
                'tipo_organizacion' => ['empresa', 'espacios'],
                'impacto_social' => ['economia_colaborativa'],
                'color' => '#00bcd4',
            ],
            'marketplace' => [
                'nombre' => 'Marketplace Comunitario',
                'descripcion' => 'Plataforma para regalo, venta, cambio y alquiler entre usuarios.',
                'icono' => 'dashicons-megaphone',
                'modulos_requeridos' => ['marketplace'],
                'modulos_opcionales' => ['advertising', 'chat_interno', 'multimedia'],
                'tipo_organizacion' => ['empresa', 'comunidad'],
                'impacto_social' => ['economia_circular'],
                'color' => '#ff9800',
            ],
            'ayuntamiento' => [
                'nombre' => 'Ayuntamiento / Portal Ciudadano',
                'descripcion' => 'Portal de servicios municipales: trámites, noticias, cita previa y atención ciudadana.',
                'icono' => 'dashicons-building',
                'modulos_requeridos' => ['avisos_municipales', 'incidencias'],
                'modulos_opcionales' => ['participacion', 'presupuestos_participativos', 'eventos', 'espacios_comunes', 'biblioteca', 'tramites', 'transparencia'],
                'tipo_organizacion' => ['publico', 'administracion'],
                'impacto_social' => ['transparencia', 'participacion'],
                'color' => '#1d4ed8',
            ],
            'barrio' => [
                'nombre' => 'Barrio / Vecindario',
                'descripcion' => 'Plataforma vecinal completa con ayuda mutua, huertos, bicicletas y recursos compartidos.',
                'icono' => 'dashicons-location',
                'modulos_requeridos' => ['ayuda_vecinal'],
                'modulos_opcionales' => ['huertos_urbanos', 'bicicletas_compartidas', 'espacios_comunes', 'banco_tiempo', 'incidencias', 'reciclaje', 'compostaje', 'carpooling', 'talleres', 'eventos', 'chat_grupos', 'reservas'],
                'tipo_organizacion' => ['comunidad'],
                'impacto_social' => ['cohesion_social', 'sostenibilidad'],
                'color' => '#22c55e',
            ],
            'academia' => [
                'nombre' => 'Academia Online',
                'descripcion' => 'Plataforma de formación online con cursos, talleres y certificados.',
                'icono' => 'dashicons-welcome-learn-more',
                'modulos_requeridos' => ['cursos', 'talleres'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'socios', 'chat_grupos', 'biblioteca'],
                'tipo_organizacion' => ['educacion', 'empresa'],
                'impacto_social' => ['educacion'],
                'color' => '#7c3aed',
            ],
            'radio_comunitaria' => [
                'nombre' => 'Radio Comunitaria',
                'descripcion' => 'Emisora de radio y podcast comunitaria con programación y participación vecinal.',
                'icono' => 'dashicons-controls-volumeon',
                'modulos_requeridos' => ['radio', 'podcast'],
                'modulos_opcionales' => ['eventos', 'multimedia', 'socios', 'chat_grupos'],
                'tipo_organizacion' => ['comunidad', 'medios'],
                'impacto_social' => ['cultura'],
                'color' => '#dc2626',
            ],
            'cooperativa' => [
                'nombre' => 'Cooperativa',
                'descripcion' => 'Gestión cooperativa con gobernanza democrática, transparencia y participación.',
                'icono' => 'dashicons-admin-users',
                'modulos_requeridos' => ['socios', 'transparencia', 'participacion'],
                'modulos_opcionales' => ['eventos', 'presupuestos_participativos', 'chat_grupos', 'chat_interno', 'talleres'],
                'tipo_organizacion' => ['cooperativa', 'empresa'],
                'impacto_social' => ['gobernanza', 'transparencia'],
                'color' => '#059669',
            ],
            'hosteleria' => [
                'nombre' => 'Hostelería',
                'descripcion' => 'Bar, cafetería o restaurante con carta digital, pedidos y reservas.',
                'icono' => 'dashicons-food',
                'modulos_requeridos' => ['bares', 'woocommerce', 'reservas'],
                'modulos_opcionales' => ['eventos', 'facturas', 'fichaje_empleados', 'multimedia'],
                'tipo_organizacion' => ['empresa', 'hosteleria'],
                'impacto_social' => [],
                'color' => '#ea580c',
            ],
            'empresa_profesional' => [
                'nombre' => 'Empresa Profesional',
                'descripcion' => 'Empresa con CRM, facturación, clientes y presencia corporativa.',
                'icono' => 'dashicons-briefcase',
                'modulos_requeridos' => ['empresarial', 'clientes', 'facturas'],
                'modulos_opcionales' => ['marketplace', 'woocommerce', 'reservas', 'eventos', 'chat_interno', 'multimedia'],
                'tipo_organizacion' => ['empresa'],
                'impacto_social' => [],
                'color' => '#1f2937',
            ],
            'empresa_saas' => [
                'nombre' => 'Empresa SaaS',
                'descripcion' => 'Producto digital con ventas online, soporte y marketing.',
                'icono' => 'dashicons-cloud',
                'modulos_requeridos' => ['empresarial', 'clientes', 'woocommerce'],
                'modulos_opcionales' => ['facturas', 'chat_interno', 'eventos', 'multimedia', 'marketplace'],
                'tipo_organizacion' => ['empresa', 'tecnologia'],
                'impacto_social' => [],
                'color' => '#0ea5e9',
            ],
            'empresa_operaciones' => [
                'nombre' => 'Empresa Operativa',
                'descripcion' => 'Equipos, turnos, reservas y espacios para operaciones diarias.',
                'icono' => 'dashicons-businessperson',
                'modulos_requeridos' => ['fichaje_empleados', 'espacios_comunes', 'reservas'],
                'modulos_opcionales' => ['facturas', 'incidencias', 'eventos', 'chat_interno', 'clientes'],
                'tipo_organizacion' => ['empresa', 'operaciones'],
                'impacto_social' => [],
                'color' => '#2563eb',
            ],
            'empresa_etica' => [
                'nombre' => 'Empresa Ética y Consciente',
                'descripcion' => 'Empresa con impacto social, transparencia y participación.',
                'icono' => 'dashicons-heart',
                'modulos_requeridos' => ['empresarial', 'transparencia', 'participacion'],
                'modulos_opcionales' => ['presupuestos_participativos', 'ayuda_vecinal', 'grupos_consumo', 'reciclaje', 'compostaje', 'eventos', 'chat_grupos'],
                'tipo_organizacion' => ['empresa', 'cooperativa'],
                'impacto_social' => ['impacto_social', 'transparencia', 'sostenibilidad'],
                'color' => '#16a34a',
            ],
            'red_empresas' => [
                'nombre' => 'Red de Empresas y Cooperativas',
                'descripcion' => 'Empresas interconectadas con marketplace, comunidad y nodos.',
                'icono' => 'dashicons-networking',
                'modulos_requeridos' => ['comunidades', 'marketplace', 'clientes'],
                'modulos_opcionales' => ['empresarial', 'transparencia', 'participacion', 'chat_grupos', 'eventos', 'grupos_consumo'],
                'tipo_organizacion' => ['empresa', 'cooperativa', 'red'],
                'impacto_social' => ['intercooperacion', 'economia_circular'],
                'color' => '#7c3aed',
            ],
            'smart_village' => [
                'nombre' => 'Smart Village',
                'descripcion' => 'Pueblo inteligente con avisos, ayuda vecinal, huertos y movilidad sostenible.',
                'icono' => 'dashicons-location',
                'modulos_requeridos' => ['avisos_municipales', 'ayuda_vecinal'],
                'modulos_opcionales' => ['huertos_urbanos', 'bicicletas_compartidas', 'incidencias', 'espacios_comunes', 'eventos', 'chat_grupos', 'carpooling'],
                'tipo_organizacion' => ['comunidad', 'publico'],
                'impacto_social' => ['sostenibilidad', 'movilidad'],
                'color' => '#16a34a',
            ],
            'reciclaje_comunitario' => [
                'nombre' => 'Reciclaje Comunitario',
                'descripcion' => 'Sistema completo de reciclaje con puntos limpios, gamificación, recompensas y economía circular.',
                'icono' => 'dashicons-admin-site',
                'modulos_requeridos' => ['reciclaje'],
                'modulos_opcionales' => ['socios', 'eventos', 'compostaje', 'huertos_urbanos', 'marketplace', 'chat_grupos', 'talleres', 'transparencia'],
                'tipo_organizacion' => ['comunidad', 'cooperativa'],
                'impacto_social' => ['sostenibilidad', 'economia_circular'],
                'color' => '#10b981',
            ],
            'club_deportivo' => [
                'nombre' => 'Club Deportivo',
                'descripcion' => 'Gestión de un club deportivo con socios, eventos, cuotas y comunicación.',
                'icono' => 'dashicons-universal-access',
                'modulos_requeridos' => ['socios', 'eventos', 'reservas'],
                'modulos_opcionales' => ['talleres', 'chat_grupos', 'multimedia', 'espacios_comunes', 'fichaje_empleados'],
                'tipo_organizacion' => ['deporte', 'asociacion'],
                'impacto_social' => ['salud'],
                'color' => '#2563eb',
            ],
            'ong' => [
                'nombre' => 'ONG / Fundación',
                'descripcion' => 'Organización sin ánimo de lucro con transparencia, socios y proyectos.',
                'icono' => 'dashicons-heart',
                'modulos_requeridos' => ['socios', 'transparencia'],
                'modulos_opcionales' => ['eventos', 'participacion', 'chat_grupos', 'multimedia', 'cursos'],
                'tipo_organizacion' => ['ong', 'fundacion'],
                'impacto_social' => ['impacto_social', 'transparencia'],
                'color' => '#be185d',
            ],
            'coordinadora_territorial' => [
                'nombre' => 'Coordinadora Territorial',
                'descripcion' => 'Plataforma para coordinar valles, pueblos y colectivos. Organización ciudadana, denuncia, participación y dinamización del territorio.',
                'icono' => 'dashicons-location-alt',
                'modulos_requeridos' => ['comunidades', 'colectivos', 'incidencias', 'participacion', 'campanias'],
                'modulos_opcionales' => [
                    'foros',
                    'chat_grupos',
                    'avisos_municipales',
                    'eventos',
                    'presupuestos_participativos',
                    'multimedia',
                    'podcast',
                    'ayuda_vecinal',
                    'banco_tiempo',
                    'grupos_consumo',
                    'carpooling',
                    'biodiversidad_local',
                    'transparencia',
                    'saberes_ancestrales',
                    'huertos_urbanos',
                    'justicia_restaurativa',
                    'circulos_cuidados',
                    'trabajo_digno',
                    'documentacion_legal',
                    'seguimiento_denuncias',
                    'mapa_actores',
                ],
                'tipo_organizacion' => ['comunidad', 'movimiento', 'plataforma'],
                'impacto_social' => ['participacion', 'cohesion_social', 'territorio', 'denuncia'],
                'color' => '#dc2626',
            ],
            'cooperativa_empresas' => [
                'nombre' => 'Cooperativa de Empresas',
                'descripcion' => 'Cluster de pequeños negocios locales que colaboran: directorio compartido, marketplace conjunto, facturación cruzada y banco de tiempo empresarial.',
                'icono' => 'dashicons-store',
                'modulos_requeridos' => ['socios', 'clientes', 'marketplace'],
                'modulos_opcionales' => [
                    'facturas',
                    'banco_tiempo',
                    'bares',
                    'grupos_consumo',
                    'crowdfunding',
                    'eventos',
                    'chat_grupos',
                    'chat_interno',
                    'talleres',
                    'transparencia',
                    'participacion',
                    'trabajo_digno',
                ],
                'tipo_organizacion' => ['cooperativa', 'empresa', 'asociacion'],
                'impacto_social' => ['economia_local', 'intercooperacion', 'economia_circular'],
                'color' => '#0d9488',
            ],
            'personalizado' => [
                'nombre' => 'Personalizado',
                'descripcion' => 'Selecciona manualmente los módulos que necesitas.',
                'icono' => 'dashicons-admin-generic',
                'modulos_requeridos' => [],
                'modulos_opcionales' => [],
                'tipo_organizacion' => [],
                'impacto_social' => [],
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

        flavor_update_main_settings($configuracion);

        // Limpiar cache de opciones para asegurar que se lee el valor actualizado
        wp_cache_delete('alloptions', 'options');

        // Crear tablas de los módulos del perfil
        if ($perfil_id !== 'personalizado' && !empty($modulos_activos)) {
            $this->crear_tablas_modulos($modulos_activos);
        }

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

        flavor_log_debug( 'obtener_modulos_requeridos() - Perfil activo: ' . $perfil_id, 'AppProfiles' );
        flavor_log_debug( 'Módulos requeridos: ' . implode(', ', $perfil['modulos_requeridos'] ?? []), 'AppProfiles' );

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
     * Obtiene los perfiles activos (soporta múltiples)
     *
     * @return array
     */
    public function obtener_perfiles_activos() {
        $configuracion = flavor_get_main_settings();

        // Verificar si hay múltiples perfiles activos
        if (isset($configuracion['app_profiles']) && is_array($configuracion['app_profiles'])) {
            return $configuracion['app_profiles'];
        }

        // Compatibilidad con perfil único
        $perfil_unico = $configuracion['app_profile'] ?? 'personalizado';
        return [$perfil_unico];
    }

    /**
     * Establece múltiples perfiles activos
     *
     * @param array $perfiles_ids Array de IDs de perfiles
     * @return bool
     */
    public function establecer_perfiles($perfiles_ids) {
        if (!is_array($perfiles_ids) || empty($perfiles_ids)) {
            return false;
        }

        // Validar que todos los perfiles existen
        foreach ($perfiles_ids as $perfil_id) {
            if (!isset($this->perfiles_disponibles[$perfil_id])) {
                return false;
            }
        }

        $configuracion = flavor_get_main_settings();

        // Guardar perfiles activos
        $configuracion['app_profiles'] = $perfiles_ids;
        $configuracion['app_profile'] = $perfiles_ids[0]; // Mantener compatibilidad

        // Combinar módulos de todos los perfiles
        $modulos_combinados = $this->combinar_modulos_de_perfiles($perfiles_ids);
        $configuracion['active_modules'] = $modulos_combinados;

        flavor_update_main_settings($configuracion);

        // Limpiar cache
        wp_cache_delete('alloptions', 'options');

        // Crear tablas de los módulos combinados
        if (!empty($modulos_combinados)) {
            $this->crear_tablas_modulos($modulos_combinados);
        }

        return true;
    }

    /**
     * Combina módulos de múltiples perfiles
     *
     * @param array $perfiles_ids
     * @return array
     */
    private function combinar_modulos_de_perfiles($perfiles_ids) {
        $modulos_combinados = [];

        foreach ($perfiles_ids as $perfil_id) {
            if ($perfil_id === 'personalizado') {
                continue; // Personalizado no tiene módulos predefinidos
            }

            $perfil = $this->obtener_perfil($perfil_id);
            if ($perfil) {
                $modulos_combinados = array_merge(
                    $modulos_combinados,
                    $perfil['modulos_requeridos'] ?? [],
                    $perfil['modulos_opcionales'] ?? []
                );
            }
        }

        // Eliminar duplicados y reindexar
        return array_values(array_unique($modulos_combinados));
    }

    /**
     * Verifica si un perfil específico está activo
     *
     * @param string $perfil_id
     * @return bool
     */
    public function es_perfil_activo($perfil_id) {
        $perfiles_activos = $this->obtener_perfiles_activos();
        return in_array($perfil_id, $perfiles_activos, true);
    }

    /**
     * Añade un perfil a los activos sin eliminar los existentes
     *
     * @param string $perfil_id
     * @return bool
     */
    public function anadir_perfil($perfil_id) {
        $perfiles_activos = $this->obtener_perfiles_activos();

        if (in_array($perfil_id, $perfiles_activos, true)) {
            return true; // Ya está activo
        }

        $perfiles_activos[] = $perfil_id;
        return $this->establecer_perfiles($perfiles_activos);
    }

    /**
     * Quita un perfil de los activos
     *
     * @param string $perfil_id
     * @return bool
     */
    public function quitar_perfil($perfil_id) {
        $perfiles_activos = $this->obtener_perfiles_activos();

        $perfiles_activos = array_filter($perfiles_activos, function($id) use ($perfil_id) {
            return $id !== $perfil_id;
        });

        if (empty($perfiles_activos)) {
            $perfiles_activos = ['personalizado']; // Al menos uno debe estar activo
        }

        return $this->establecer_perfiles(array_values($perfiles_activos));
    }

    /**
     * Activa un módulo opcional
     *
     * @param string $modulo_id
     * @return bool
     */
    public function activar_modulo_opcional($modulo_id) {
        // Verificar que el módulo esté registrado en el sistema (no solo en el perfil)
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();

        if (!isset($modulos_registrados[$modulo_id])) {
            flavor_log_debug( "Módulo '{$modulo_id}' no está registrado en el sistema", 'AppProfiles' );
            return false;
        }

        flavor_log_debug( "Intentando activar módulo: {$modulo_id}", 'AppProfiles' );

        // Hook para validar dependencias antes de activar
        $can_activate = apply_filters('flavor_before_activate_module', true, $modulo_id);

        if (is_wp_error($can_activate)) {
            flavor_log_debug( "No se puede activar '{$modulo_id}': " . $can_activate->get_error_message(), 'AppProfiles' );
            return false;
        }

        if (!$can_activate) {
            flavor_log_debug( "No se puede activar '{$modulo_id}': validación falló", 'AppProfiles' );
            return false;
        }

        global $wpdb;

        // LEER DIRECTO DE BD para evitar cualquier caché
        $config_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $configuracion = $config_raw ? maybe_unserialize($config_raw) : [];
        $modulos_activos = isset($configuracion['active_modules']) && is_array($configuracion['active_modules'])
            ? $configuracion['active_modules']
            : [];

        flavor_log_debug( "Módulos activos ANTES (directo BD): " . implode(', ', $modulos_activos), 'AppProfiles' );

        if (!in_array($modulo_id, $modulos_activos)) {
            $modulos_activos[] = $modulo_id;
            $configuracion['active_modules'] = array_values(array_unique($modulos_activos));

            flavor_log_debug( "Módulos activos DESPUÉS: " . implode(', ', $configuracion['active_modules']), 'AppProfiles' );

            // Serializar configuración
            $value = maybe_serialize($configuracion);

            // ESCRIBIR DIRECTO EN BD - Siempre usar wpdb para evitar problemas de caché/hooks
            $existe = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                    'flavor_chat_ia_settings'
                )
            );

            if ($existe) {
                // UPDATE directo con query raw para evitar cualquier interferencia
                $sql = $wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s",
                    $value,
                    'flavor_chat_ia_settings'
                );
                $resultado = $wpdb->query($sql);
                flavor_log_debug( "UPDATE directo - SQL ejecutado, filas afectadas: {$resultado}", 'AppProfiles' );
            } else {
                // INSERT si no existe
                $resultado = $wpdb->insert(
                    $wpdb->options,
                    [
                        'option_name'  => 'flavor_chat_ia_settings',
                        'option_value' => $value,
                        'autoload'     => 'yes'
                    ],
                    ['%s', '%s', '%s']
                );
                flavor_log_debug( "INSERT nuevo - resultado: " . ($resultado ? 'OK' : 'FAIL'), 'AppProfiles' );
            }

            // VERIFICAR que se guardó correctamente
            $verificacion_raw = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                    'flavor_chat_ia_settings'
                )
            );
            $verificacion = maybe_unserialize($verificacion_raw);
            $modulos_verificados = $verificacion['active_modules'] ?? [];

            $modulo_guardado = in_array($modulo_id, $modulos_verificados);
            flavor_log_debug( "VERIFICACIÓN BD - Módulo {$modulo_id} guardado: " . ($modulo_guardado ? 'SÍ' : 'NO'), 'AppProfiles' );
            flavor_log_debug( "VERIFICACIÓN BD - Módulos: " . implode(', ', $modulos_verificados), 'AppProfiles' );

            if (!$modulo_guardado) {
                flavor_log_error( "ERROR CRÍTICO: El módulo {$modulo_id} NO se guardó en la BD", 'AppProfiles' );
                return false;
            }

            // Limpiar TODO el caché DESPUÉS de verificar
            wp_cache_delete(FLAVOR_CHAT_IA_SETTINGS_OPTION, 'options');
            wp_cache_delete(FLAVOR_PLATFORM_SETTINGS_OPTION, 'options');
            wp_cache_delete('alloptions', 'options');
            wp_cache_flush();

            // Forzar que WordPress recargue la opción
            wp_load_alloptions(true);

            // Crear tablas del módulo automáticamente
            $this->crear_tablas_modulo($modulo_id);
        } else {
            flavor_log_debug( "Módulo ya estaba activo: {$modulo_id}", 'AppProfiles' );
        }

        return true;
    }

    /**
     * Crea las tablas de un módulo
     *
     * @param string $modulo_id
     * @return bool
     */
    private function crear_tablas_modulo($modulo_id) {
        return $this->crear_tablas_modulos([$modulo_id]);
    }

    /**
     * Crea las tablas de múltiples módulos
     *
     * @param array $modulos_ids
     * @return bool
     */
    private function crear_tablas_modulos($modulos_ids) {
        if (empty($modulos_ids)) {
            return false;
        }

        // Verificar si existe el instalador de tablas
        $installer_path = FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/components/class-table-installer.php';

        if (!file_exists($installer_path)) {
            return false;
        }

        require_once $installer_path;

        if (!class_exists('Flavor_Table_Installer')) {
            return false;
        }

        try {
            $installer = new Flavor_Table_Installer();
            $resultado = $installer->instalar('auto_activacion', [
                'modulos' => $modulos_ids
            ]);

            return $resultado['success'] ?? false;
        } catch (Exception $e) {
            flavor_log_error( 'Error creando tablas: ' . $e->getMessage(), 'AppProfiles' );
            return false;
        }
    }

    /**
     * Obtiene los módulos organizados por categorías
     *
     * @return array Categorías con sus módulos
     */
    public function obtener_categorias_modulos() {
        return [
            'comercio' => [
                'nombre' => 'Comercio',
                'icono'  => 'dashicons-store',
                'modulos' => ['woocommerce', 'marketplace', 'facturas', 'advertising', 'reservas'],
            ],
            'comunidad' => [
                'nombre' => 'Comunidad',
                'icono'  => 'dashicons-groups',
                'modulos' => ['socios', 'foros', 'red_social', 'chat_grupos', 'chat_interno', 'comunidades', 'colectivos', 'circulos_cuidados', 'justicia_restaurativa'],
            ],
            'gobernanza' => [
                'nombre' => 'Gobernanza',
                'icono'  => 'dashicons-building',
                'modulos' => ['participacion', 'presupuestos_participativos', 'transparencia', 'avisos_municipales', 'tramites'],
            ],
            'sostenibilidad' => [
                'nombre' => 'Sostenibilidad',
                'icono'  => 'dashicons-palmtree',
                'modulos' => ['huertos_urbanos', 'bicicletas_compartidas', 'compostaje', 'energia_comunitaria', 'reciclaje', 'carpooling', 'huella_ecologica', 'biodiversidad_local'],
            ],
            'economia_social' => [
                'nombre' => 'Economía Social',
                'icono'  => 'dashicons-heart',
                'modulos' => ['economia_suficiencia', 'economia_don', 'trabajo_digno', 'saberes_ancestrales', 'sello_conciencia'],
            ],
            'contenido' => [
                'nombre' => 'Contenido',
                'icono'  => 'dashicons-media-interactive',
                'modulos' => ['cursos', 'podcast', 'radio', 'multimedia', 'biblioteca', 'talleres', 'eventos', 'email_marketing'],
            ],
            'operaciones' => [
                'nombre' => 'Operaciones',
                'icono'  => 'dashicons-admin-tools',
                'modulos' => ['fichaje_empleados', 'incidencias', 'espacios_comunes', 'parkings', 'banco_tiempo', 'ayuda_vecinal', 'empresarial', 'clientes', 'bares', 'grupos_consumo'],
            ],
            'finanzas' => [
                'nombre' => 'Finanzas',
                'icono'  => 'dashicons-chart-line',
                'modulos' => ['trading_ia', 'dex_solana', 'themacle'],
            ],
        ];
    }

    /**
     * Obtiene los tipos de organizacion disponibles para filtros.
     *
     * @return array
     */
    public function obtener_tipos_organizacion() {
        return [
            'empresa' => 'Empresa',
            'cooperativa' => 'Cooperativa',
            'comunidad' => 'Comunidad',
            'asociacion' => 'Asociación',
            'ong' => 'ONG',
            'fundacion' => 'Fundación',
            'publico' => 'Administración Pública',
            'administracion' => 'Administración',
            'hosteleria' => 'Hostelería',
            'educacion' => 'Educación',
            'medios' => 'Medios',
            'deporte' => 'Deporte',
            'tecnologia' => 'Tecnología',
            'espacios' => 'Espacios',
            'operaciones' => 'Operaciones',
            'red' => 'Red',
        ];
    }

    /**
     * Obtiene etiquetas de impacto social disponibles para filtros.
     *
     * @return array
     */
    public function obtener_impactos_sociales() {
        return [
            'impacto_social' => 'Impacto Social',
            'transparencia' => 'Transparencia',
            'participacion' => 'Participación',
            'gobernanza' => 'Gobernanza',
            'sostenibilidad' => 'Sostenibilidad',
            'economia_colaborativa' => 'Economía Colaborativa',
            'economia_circular' => 'Economía Circular',
            'consumo_local' => 'Consumo Local',
            'cohesion_social' => 'Cohesión Social',
            'intercooperacion' => 'Intercooperación',
            'solidaridad' => 'Solidaridad',
            'intercambio' => 'Intercambio',
            'educacion' => 'Educación',
            'cultura' => 'Cultura',
            'movilidad' => 'Movilidad',
            'salud' => 'Salud',
        ];
    }

    /**
     * Desactiva un módulo opcional (solo si no es requerido)
     *
     * @param string $modulo_id
     * @return bool
     */
    public function desactivar_modulo_opcional($modulo_id) {
        // Hook para validar si hay módulos dependientes antes de desactivar
        $can_deactivate = apply_filters('flavor_before_deactivate_module', true, $modulo_id);

        if (is_wp_error($can_deactivate)) {
            flavor_log_debug( "No se puede desactivar '{$modulo_id}': " . $can_deactivate->get_error_message(), 'AppProfiles' );
            return false;
        }

        if (!$can_deactivate) {
            flavor_log_debug( "No se puede desactivar '{$modulo_id}': validación falló", 'AppProfiles' );
            return false;
        }

        $modulos_requeridos = $this->obtener_modulos_requeridos();

        flavor_log_debug( "desactivar_modulo_opcional('{$modulo_id}')", 'AppProfiles' );
        flavor_log_debug( 'Módulos requeridos: ' . implode(', ', $modulos_requeridos), 'AppProfiles' );

        if (in_array($modulo_id, $modulos_requeridos)) {
            flavor_log_debug( "'{$modulo_id}' ES REQUERIDO - Retornando false", 'AppProfiles' );
            return false; // No se puede desactivar un módulo requerido
        }

        global $wpdb;

        // LEER DIRECTO DE BD para evitar cualquier caché
        $config_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $configuracion = $config_raw ? maybe_unserialize($config_raw) : [];
        $modulos_activos = isset($configuracion['active_modules']) && is_array($configuracion['active_modules'])
            ? $configuracion['active_modules']
            : [];

        flavor_log_debug( 'Módulos activos ANTES (directo BD): ' . implode(', ', $modulos_activos), 'AppProfiles' );

        $clave = array_search($modulo_id, $modulos_activos);
        if ($clave !== false) {
            flavor_log_debug( "Encontrado en posición {$clave} - Eliminando...", 'AppProfiles' );
            unset($modulos_activos[$clave]);
            $configuracion['active_modules'] = array_values($modulos_activos);

            // Serializar configuración
            $value = maybe_serialize($configuracion);

            // ESCRIBIR DIRECTO EN BD
            $sql = $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s",
                $value,
                'flavor_chat_ia_settings'
            );
            $resultado = $wpdb->query($sql);
            flavor_log_debug( "UPDATE directo - SQL ejecutado, filas afectadas: {$resultado}", 'AppProfiles' );

            // VERIFICAR que se guardó correctamente
            $verificacion_raw = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                    'flavor_chat_ia_settings'
                )
            );
            $verificacion = maybe_unserialize($verificacion_raw);
            $modulos_verificados = $verificacion['active_modules'] ?? [];

            $modulo_eliminado = !in_array($modulo_id, $modulos_verificados);
            flavor_log_debug( "VERIFICACIÓN BD - Módulo {$modulo_id} eliminado: " . ($modulo_eliminado ? 'SÍ' : 'NO'), 'AppProfiles' );
            flavor_log_debug( 'VERIFICACIÓN BD - Módulos restantes: ' . implode(', ', $modulos_verificados), 'AppProfiles' );

            if (!$modulo_eliminado) {
                flavor_log_error( "ERROR CRÍTICO: El módulo {$modulo_id} NO se eliminó de la BD", 'AppProfiles' );
                return false;
            }

            // Limpiar TODO el caché DESPUÉS de verificar
            wp_cache_delete(FLAVOR_CHAT_IA_SETTINGS_OPTION, 'options');
            wp_cache_delete(FLAVOR_PLATFORM_SETTINGS_OPTION, 'options');
            wp_cache_delete('alloptions', 'options');
            wp_cache_flush();

            // Forzar que WordPress recargue la opción
            wp_load_alloptions(true);
        } else {
            flavor_log_debug( "'{$modulo_id}' NO encontrado en módulos activos", 'AppProfiles' );
        }

        return true;
    }
}
