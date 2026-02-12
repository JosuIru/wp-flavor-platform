<?php
/**
 * Gestor de Datos de Demostración
 *
 * Permite poblar y limpiar datos de ejemplo en los módulos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar datos de demostración
 */
class Flavor_Demo_Data_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Demo_Data_Manager
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
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        add_action('admin_post_flavor_populate_demo_data', [$this, 'handle_populate_demo_data']);
        add_action('admin_post_flavor_clear_demo_data', [$this, 'handle_clear_demo_data']);
        add_action('admin_post_flavor_create_demo_pages', [$this, 'handle_create_demo_pages']);
        add_action('admin_post_flavor_delete_demo_pages', [$this, 'handle_delete_demo_pages']);
    }

    /**
     * Maneja la acción de poblar datos demo
     */
    public function handle_populate_demo_data() {
        check_admin_referer('flavor_demo_data_action');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulo_id = sanitize_text_field($_POST['modulo_id'] ?? 'all');
        $modulos_activos = isset($_POST['modulos_activos']) ? array_map('sanitize_text_field', (array) $_POST['modulos_activos']) : null;
        $resultados = [];

        if ($modulo_id === 'all') {
            // Si se proporciona lista de módulos activos, usarla; si no, poblar todos
            $resultados = $this->populate_all_modules($modulos_activos);
        } else {
            $resultado = $this->populate_module($modulo_id);
            $resultados[$modulo_id] = $resultado;
        }

        $exitos = array_filter($resultados, function($r) { return $r['success']; });
        $mensaje = count($exitos) > 0 ? 'demo_data_populated' : 'demo_data_error';

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => $mensaje, 'count' => count($exitos)],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Maneja la acción de limpiar datos demo
     */
    public function handle_clear_demo_data() {
        check_admin_referer('flavor_demo_data_action');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulo_id = sanitize_text_field($_POST['modulo_id'] ?? 'all');
        $modulos_activos = isset($_POST['modulos_activos']) ? array_map('sanitize_text_field', (array) $_POST['modulos_activos']) : null;
        $resultados = [];

        if ($modulo_id === 'all') {
            // Si se proporciona lista de módulos activos, usarla; si no, limpiar todos
            $resultados = $this->clear_all_modules($modulos_activos);
        } else {
            $resultado = $this->clear_module($modulo_id);
            $resultados[$modulo_id] = $resultado;
        }

        $exitos = array_filter($resultados, function($r) { return $r['success']; });
        $mensaje = count($exitos) > 0 ? 'demo_data_cleared' : 'demo_data_clear_error';

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => $mensaje, 'count' => count($exitos)],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Pobla todos los módulos activos
     *
     * @param array $modulos_filtrados (opcional) Lista de módulos específicos a poblar
     * @return array
     */
    public function populate_all_modules($modulos_filtrados = null) {
        $resultados = [];

        // Crear usuarios demo compartidos ANTES de poblar cualquier módulo
        // Estos usuarios se reutilizarán en todos los módulos que los necesiten
        $this->get_or_create_demo_users();

        // Si no se especifican módulos, usar todos los disponibles
        $modulos_disponibles = $modulos_filtrados ?? $this->get_modulos_con_demo_disponibles();

        foreach ($modulos_disponibles as $modulo_id) {
            // Solo poblar si el método existe
            if (method_exists($this, 'populate_' . $modulo_id)) {
                $resultados[$modulo_id] = $this->populate_module($modulo_id);
            }
        }

        return $resultados;
    }

    /**
     * Limpia todos los módulos
     *
     * @param array $modulos_filtrados (opcional) Lista de módulos específicos a limpiar
     * @return array
     */
    public function clear_all_modules($modulos_filtrados = null) {
        $resultados = [];

        // Si no se especifican módulos, usar todos los disponibles
        $modulos_disponibles = $modulos_filtrados ?? $this->get_modulos_con_demo_disponibles();

        foreach ($modulos_disponibles as $modulo_id) {
            // Solo limpiar si el método existe
            if (method_exists($this, 'clear_' . $modulo_id)) {
                $resultados[$modulo_id] = $this->clear_module($modulo_id);
            }
        }

        // Limpiar usuarios demo compartidos DESPUÉS de limpiar todos los módulos
        $usuarios_eliminados = $this->clear_demo_users();
        if ($usuarios_eliminados > 0) {
            $resultados['usuarios_compartidos'] = [
                'success' => true,
                'count' => $usuarios_eliminados,
                'message' => sprintf('Se eliminaron %d usuarios demo compartidos', $usuarios_eliminados),
            ];
        }

        return $resultados;
    }

    /**
     * Obtiene la lista de módulos que tienen implementación de datos demo
     *
     * @return array
     */
    private function get_modulos_con_demo_disponibles() {
        return [
            'advertising',
            'avisos_municipales',
            'ayuda_vecinal',
            'banco_tiempo',
            'bares',
            'biblioteca',
            'bicicletas_compartidas',
            'carpooling',
            'chat_grupos',
            'chat_interno',
            'clientes',
            'colectivos',
            'compostaje',
            'comunidades',
            'cursos',
            'empresarial',
            'espacios_comunes',
            'eventos',
            'facturas',
            'fichaje_empleados',
            'foros',
            'grupos_consumo',
            'huertos_urbanos',
            'incidencias',
            'marketplace',
            'multimedia',
            'network',
            'parkings',
            'participacion',
            'podcast',
            'presupuestos_participativos',
            'radio',
            'reciclaje',
            'red_social',
            'reservas',
            'socios',
            'talleres',
            'tramites',
            'transparencia',
        ];
    }

    /**
     * Pobla un módulo específico
     *
     * @param string $modulo_id
     * @return array
     */
    public function populate_module($modulo_id) {
        $metodo = 'populate_' . $modulo_id;

        if (method_exists($this, $metodo)) {
            try {
                return $this->$metodo();
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    /**
     * Limpia un módulo específico
     *
     * @param string $modulo_id
     * @return array
     */
    public function clear_module($modulo_id) {
        $metodo = 'clear_' . $modulo_id;

        if (method_exists($this, $metodo)) {
            try {
                return $this->$metodo();
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    /**
     * Obtiene un usuario administrador para asignar datos
     *
     * @return int
     */
    private function get_demo_user_id() {
        $usuarios_admin = get_users(['role' => 'administrator', 'number' => 1]);
        return !empty($usuarios_admin) ? $usuarios_admin[0]->ID : 1;
    }

    /**
     * Crea o obtiene usuarios demo compartidos para todos los módulos
     *
     * @return array Array de IDs de usuarios creados/obtenidos
     */
    private function get_or_create_demo_users() {
        // Intentar obtener usuarios demo existentes
        $existing_users = get_users([
            'meta_key' => '_flavor_demo_data',
            'meta_value' => '1',
            'fields' => 'ID',
        ]);

        // Si ya existen usuarios demo, devolverlos
        if (!empty($existing_users)) {
            return $existing_users;
        }

        // Si no existen, crearlos
        $usuarios_demo = [
            [
                'nombre' => 'María García López',
                'email' => 'demo-maria@example.com',
                'telefono' => '611222333',
                'direccion' => 'C/ San Pedro 15, 2º A',
            ],
            [
                'nombre' => 'Juan Martínez Ruiz',
                'email' => 'demo-juan@example.com',
                'telefono' => '622333444',
                'direccion' => 'Av. Navarra 45, 3º B',
            ],
            [
                'nombre' => 'Ana Sánchez Torres',
                'email' => 'demo-ana@example.com',
                'telefono' => '633444555',
                'direccion' => 'C/ Mayor 8, 1º',
            ],
            [
                'nombre' => 'Carlos Fernández Gil',
                'email' => 'demo-carlos@example.com',
                'telefono' => '644555666',
                'direccion' => 'Plaza del Castillo 12',
            ],
            [
                'nombre' => 'Laura Pérez Muñoz',
                'email' => 'demo-laura@example.com',
                'telefono' => '655666777',
                'direccion' => 'C/ Estafeta 22, 4º D',
            ],
            [
                'nombre' => 'Pedro González Vega',
                'email' => 'demo-pedro@example.com',
                'telefono' => '666777888',
                'direccion' => 'C/ Comercio 33, bajo',
            ],
            [
                'nombre' => 'Isabel Torres Mora',
                'email' => 'demo-isabel@example.com',
                'telefono' => '677888999',
                'direccion' => 'Av. Libertad 18, 5º C',
            ],
            [
                'nombre' => 'David Gil Romero',
                'email' => 'demo-david@example.com',
                'telefono' => '688999000',
                'direccion' => 'Plaza Nueva 5, 2º',
            ],
        ];

        $user_ids = [];

        foreach ($usuarios_demo as $usuario_data) {
            // Verificar si el email ya existe
            $existing = get_user_by('email', $usuario_data['email']);
            if ($existing) {
                $user_ids[] = $existing->ID;
                continue;
            }

            $usuario_login = sanitize_user(str_replace(' ', '_', strtolower($usuario_data['nombre'])));
            $usuario_password = wp_generate_password(12, false);

            $usuario_id = wp_create_user(
                $usuario_login,
                $usuario_password,
                $usuario_data['email']
            );

            if (!is_wp_error($usuario_id)) {
                $user_ids[] = $usuario_id;

                // Marcar como usuario demo
                update_user_meta($usuario_id, '_flavor_demo_data', true);

                // Datos personales
                $nombres = explode(' ', $usuario_data['nombre']);
                update_user_meta($usuario_id, 'first_name', $nombres[0]);
                update_user_meta($usuario_id, 'last_name', trim(str_replace($nombres[0], '', $usuario_data['nombre'])));

                // Datos de contacto compartidos
                update_user_meta($usuario_id, 'billing_phone', $usuario_data['telefono']);
                update_user_meta($usuario_id, 'billing_address_1', $usuario_data['direccion']);
                update_user_meta($usuario_id, 'telefono', $usuario_data['telefono']);
                update_user_meta($usuario_id, 'direccion', $usuario_data['direccion']);

                // Asignar rol subscriber
                $user = new WP_User($usuario_id);
                $user->set_role('subscriber');
            }
        }

        return $user_ids;
    }

    /**
     * Limpia todos los usuarios demo compartidos
     */
    private function clear_demo_users() {
        $usuarios_demo = get_users([
            'meta_key' => '_flavor_demo_data',
            'meta_value' => '1',
            'fields' => 'ID',
        ]);

        $eliminados = 0;
        if (!empty($usuarios_demo)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($usuarios_demo as $usuario_id) {
                if (wp_delete_user($usuario_id)) {
                    $eliminados++;
                }
            }
        }

        return $eliminados;
    }

    /**
     * Marca los registros como demo para poder limpiarlos
     *
     * @param string $modulo_id
     * @param array $ids
     */
    private function mark_as_demo($modulo_id, $ids) {
        $demo_ids = get_option('flavor_demo_data_ids', []);
        $demo_ids[$modulo_id] = array_merge($demo_ids[$modulo_id] ?? [], $ids);
        update_option('flavor_demo_data_ids', $demo_ids);
    }

    /**
     * Obtiene los IDs de demo de un módulo
     *
     * @param string $modulo_id
     * @return array
     */
    private function get_demo_ids($modulo_id) {
        $demo_ids = get_option('flavor_demo_data_ids', []);
        return $demo_ids[$modulo_id] ?? [];
    }

    /**
     * Limpia los IDs de demo de un módulo
     *
     * @param string $modulo_id
     */
    private function clear_demo_ids($modulo_id) {
        $demo_ids = get_option('flavor_demo_data_ids', []);
        unset($demo_ids[$modulo_id]);
        update_option('flavor_demo_data_ids', $demo_ids);
    }

    // =========================================================
    // BANCO DE TIEMPO
    // =========================================================

    /**
     * Pobla datos demo de Banco de Tiempo
     */
    private function populate_banco_tiempo() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Verificar que las tablas existen
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
        }

        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $servicios_demo = [
            [
                'titulo' => 'Clases de guitarra española',
                'descripcion' => 'Ofrezco clases de guitarra para principiantes. Tengo 10 años de experiencia tocando y 5 enseñando. Incluyo material básico.',
                'categoria' => 'educacion',
                'horas_estimadas' => 1.5,
            ],
            [
                'titulo' => 'Reparación de ordenadores',
                'descripcion' => 'Arreglo problemas de software, virus, instalación de programas, optimización del sistema. También puedo hacer pequeñas reparaciones de hardware.',
                'categoria' => 'tecnologia',
                'horas_estimadas' => 2,
            ],
            [
                'titulo' => 'Cuidado de mascotas',
                'descripcion' => 'Paseo perros, cuido gatos y otros animales pequeños mientras estás de viaje. Tengo experiencia con todo tipo de mascotas.',
                'categoria' => 'cuidados',
                'horas_estimadas' => 1,
            ],
            [
                'titulo' => 'Pequeñas reparaciones del hogar',
                'descripcion' => 'Ayudo con montaje de muebles IKEA, colgar cuadros, pequeños arreglos de fontanería básica, cambio de enchufes.',
                'categoria' => 'bricolaje',
                'horas_estimadas' => 2,
            ],
            [
                'titulo' => 'Clases de conversación en inglés',
                'descripcion' => 'Nivel C1 certificado. Ofrezco práctica de conversación en inglés para mejorar fluidez. Adaptado a tu nivel.',
                'categoria' => 'educacion',
                'horas_estimadas' => 1,
            ],
            [
                'titulo' => 'Acompañamiento a mayores',
                'descripcion' => 'Ofrezco compañía a personas mayores: paseos, conversación, lectura, acompañamiento a citas médicas.',
                'categoria' => 'cuidados',
                'horas_estimadas' => 2,
            ],
            [
                'titulo' => 'Transporte al aeropuerto',
                'descripcion' => 'Puedo llevarte al aeropuerto o estación de tren en mi coche. Disponibilidad de madrugada también.',
                'categoria' => 'transporte',
                'horas_estimadas' => 1.5,
            ],
            [
                'titulo' => 'Ayuda con jardín y plantas',
                'descripcion' => 'Puedo ayudarte con el mantenimiento del jardín, poda, plantación, riego durante vacaciones.',
                'categoria' => 'otros',
                'horas_estimadas' => 2,
            ],
        ];

        foreach ($servicios_demo as $servicio) {
            $resultado = $wpdb->insert(
                $tabla_servicios,
                [
                    'usuario_id' => $usuario_id,
                    'titulo' => $servicio['titulo'],
                    'descripcion' => $servicio['descripcion'],
                    'categoria' => $servicio['categoria'],
                    'horas_estimadas' => $servicio['horas_estimadas'],
                    'estado' => 'activo',
                    'fecha_publicacion' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%f', '%s', '%s']
            );

            if ($resultado) {
                $ids_insertados[] = $wpdb->insert_id;
            }
        }

        $this->mark_as_demo('banco_tiempo', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d servicios de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Banco de Tiempo
     */
    private function clear_banco_tiempo() {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $ids = $this->get_demo_ids('banco_tiempo');

        if (empty($ids)) {
            return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_servicios WHERE id IN ($placeholders)",
            ...$ids
        ));

        $this->clear_demo_ids('banco_tiempo');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d servicios de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // EVENTOS
    // =========================================================

    /**
     * Pobla datos demo de Eventos
     */
    private function populate_eventos() {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Verificar que la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            return ['success' => false, 'error' => __('categoria', 'flavor-chat-ia')];
        }

        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $eventos_demo = [
            [
                'titulo' => 'Asamblea General de Vecinos',
                'descripcion' => 'Reunión mensual de la comunidad para tratar temas importantes: presupuestos, actividades del próximo trimestre, y propuestas de los vecinos. Se ruega puntualidad.',
                'categoria' => 'asamblea',
                'fecha_inicio' => date('Y-m-d 18:00:00', strtotime('+7 days')),
                'fecha_fin' => date('Y-m-d 20:00:00', strtotime('+7 days')),
                'ubicacion' => 'Salón de Actos - Centro Cívico',
                'maximo_asistentes' => 100,
            ],
            [
                'titulo' => 'Taller de Huerto Urbano para Principiantes',
                'descripcion' => 'Aprende a crear tu propio huerto en casa. Incluye: preparación del sustrato, selección de plantas, calendario de siembra y técnicas de riego. Material incluido.',
                'categoria' => 'taller',
                'fecha_inicio' => date('Y-m-d 10:00:00', strtotime('+10 days')),
                'fecha_fin' => date('Y-m-d 13:00:00', strtotime('+10 days')),
                'ubicacion' => 'Huerto Comunitario - Parcela Central',
                'maximo_asistentes' => 15,
            ],
            [
                'titulo' => 'Fiesta de Primavera',
                'descripcion' => '¡Celebramos la llegada del buen tiempo! Música en vivo, paella popular, actividades para niños, y sorteos. Trae tu silla y tu buen humor.',
                'categoria' => 'fiesta',
                'fecha_inicio' => date('Y-m-d 12:00:00', strtotime('+21 days')),
                'fecha_fin' => date('Y-m-d 20:00:00', strtotime('+21 days')),
                'ubicacion' => 'Plaza del Barrio',
                'maximo_asistentes' => 0,
            ],
            [
                'titulo' => 'Curso de Primeros Auxilios',
                'descripcion' => 'Formación básica en primeros auxilios impartida por Cruz Roja. Aprenderás RCP, actuación ante atragantamientos, y uso del desfibrilador.',
                'categoria' => 'taller',
                'fecha_inicio' => date('Y-m-d 16:00:00', strtotime('+14 days')),
                'fecha_fin' => date('Y-m-d 19:00:00', strtotime('+14 days')),
                'ubicacion' => 'Aula 3 - Centro Cívico',
                'aforo_maximo' => 20,
                'tipo' => 'taller',
            ],
            [
                'titulo' => 'Club de Lectura: Reunión Mensual',
                'descripcion' => 'Este mes comentamos "Cien años de soledad" de García Márquez. Ven a compartir tus impresiones y descubrir nuevas perspectivas.',
                'tipo' => 'reunion',
                'fecha_inicio' => date('Y-m-d 19:00:00', strtotime('+5 days')),
                'fecha_fin' => date('Y-m-d 21:00:00', strtotime('+5 days')),
                'ubicacion' => 'Biblioteca Municipal - Sala de Lectura',
                'aforo_maximo' => 12,
            ],
            [
                'titulo' => 'Yoga al Aire Libre',
                'descripcion' => 'Sesión gratuita de yoga para todos los niveles. Trae tu esterilla y ropa cómoda. En caso de lluvia se suspende.',
                'tipo' => 'actividad',
                'fecha_inicio' => date('Y-m-d 09:00:00', strtotime('+3 days')),
                'fecha_fin' => date('Y-m-d 10:00:00', strtotime('+3 days')),
                'ubicacion' => 'Parque Central - Zona de Césped',
                'aforo_maximo' => 25,
            ],
        ];

        foreach ($eventos_demo as $evento) {
            $resultado = $wpdb->insert(
                $tabla_eventos,
                [
                    'titulo' => $evento['titulo'],
                    'descripcion' => $evento['descripcion'],
                    'tipo' => $evento['tipo'],
                    'fecha_inicio' => $evento['fecha_inicio'],
                    'fecha_fin' => $evento['fecha_fin'],
                    'ubicacion' => $evento['ubicacion'],
                    'aforo_maximo' => $evento['aforo_maximo'],
                    'organizador_id' => $usuario_id,
                    'estado' => 'publicado',
                    'fecha_creacion' => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
            );

            if ($resultado) {
                $ids_insertados[] = $wpdb->insert_id;
            }
        }

        $this->mark_as_demo('eventos', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d eventos de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Eventos
     */
    private function clear_eventos() {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_asistentes = $wpdb->prefix . 'flavor_eventos_asistentes';

        $ids = $this->get_demo_ids('eventos');

        if (empty($ids)) {
            return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
        }

        // Primero eliminar asistentes de esos eventos
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_asistentes WHERE evento_id IN ($placeholders)",
            ...$ids
        ));

        // Luego eliminar eventos
        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_eventos WHERE id IN ($placeholders)",
            ...$ids
        ));

        $this->clear_demo_ids('eventos');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d eventos de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // MARKETPLACE
    // =========================================================

    /**
     * Pobla datos demo de Marketplace
     */
    private function populate_marketplace() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $anuncios_demo = [
            [
                'titulo' => 'Bicicleta de montaña - Poco uso',
                'descripcion' => 'Bicicleta de montaña marca Orbea, talla M. Comprada hace 2 años pero apenas usada. Incluye casco y candado. Perfecto estado, solo pequeños arañazos estéticos.',
                'tipo' => 'venta',
                'categoria' => 'Deportes',
                'precio' => 180,
                'estado' => 'buen_estado',
                'ubicacion' => 'Centro',
            ],
            [
                'titulo' => 'Sofá 3 plazas - Regalo por mudanza',
                'descripcion' => 'Sofá de 3 plazas color gris. Está en buen estado pero tiene algunas manchas que se pueden limpiar. Lo regalo porque me mudo y no me cabe. Hay que recogerlo.',
                'tipo' => 'regalo',
                'categoria' => 'Muebles',
                'precio' => 0,
                'estado' => 'usado',
                'ubicacion' => 'Ensanche',
            ],
            [
                'titulo' => 'Nintendo Switch con juegos',
                'descripcion' => 'Consola Nintendo Switch con 3 juegos: Zelda BOTW, Mario Kart 8 y Animal Crossing. Incluye base dock, 2 mandos y funda de transporte.',
                'tipo' => 'venta',
                'categoria' => 'Electrónica',
                'precio' => 220,
                'estado' => 'como_nuevo',
                'ubicacion' => 'San Juan',
            ],
            [
                'titulo' => 'Colección de libros de cocina',
                'descripcion' => 'Lote de 15 libros de cocina: mediterránea, asiática, repostería, cocina rápida. Algunos nuevos, otros consultados. Intercambio por libros de jardinería o bricolaje.',
                'tipo' => 'cambio',
                'categoria' => 'Libros',
                'precio' => 0,
                'estado' => 'buen_estado',
                'ubicacion' => 'Casco Antiguo',
            ],
            [
                'titulo' => 'Taladro percutor Bosch profesional',
                'descripcion' => 'Alquilo mi taladro percutor Bosch Professional para trabajos de bricolaje. Incluye set de brocas para madera, metal y pared. 5€/día o 15€/semana.',
                'tipo' => 'alquiler',
                'categoria' => 'Herramientas',
                'precio' => 5,
                'estado' => 'buen_estado',
                'ubicacion' => 'Centro',
            ],
            [
                'titulo' => 'Ropa de bebé 0-12 meses',
                'descripcion' => 'Lote de ropa de bebé niño: bodies, pijamas, conjuntos. Todo de marca y en muy buen estado. Mi hijo ya no lo usa. Regalo a quien lo necesite.',
                'tipo' => 'regalo',
                'categoria' => 'Ropa',
                'precio' => 0,
                'estado' => 'buen_estado',
                'ubicacion' => 'Ensanche',
            ],
            [
                'titulo' => 'Mesa de estudio IKEA',
                'descripcion' => 'Mesa de estudio modelo MICKE de IKEA, color blanco. 105x50cm. Incluye cajonera. Perfecta para estudiantes o teletrabajo.',
                'tipo' => 'venta',
                'categoria' => 'Muebles',
                'precio' => 45,
                'estado' => 'como_nuevo',
                'ubicacion' => 'Universidad',
            ],
            [
                'titulo' => 'Patines en línea talla 40',
                'descripcion' => 'Patines en línea marca Fila, talla 40 (unisex). Poco uso, ruedas en buen estado. Incluyo protecciones de rodilla y muñeca.',
                'tipo' => 'venta',
                'categoria' => 'Deportes',
                'precio' => 35,
                'estado' => 'buen_estado',
                'ubicacion' => 'San Juan',
            ],
        ];

        foreach ($anuncios_demo as $anuncio) {
            // Crear el post
            $post_data = [
                'post_title' => $anuncio['titulo'],
                'post_content' => $anuncio['descripcion'],
                'post_status' => 'publish',
                'post_type' => 'marketplace_item',
                'post_author' => $usuario_id,
            ];

            $post_id = wp_insert_post($post_data);

            if ($post_id && !is_wp_error($post_id)) {
                $ids_insertados[] = $post_id;

                // Asignar tipo de transacción
                wp_set_object_terms($post_id, $anuncio['tipo'], 'marketplace_tipo');

                // Asignar categoría
                wp_set_object_terms($post_id, $anuncio['categoria'], 'marketplace_categoria');

                // Guardar meta fields
                update_post_meta($post_id, '_marketplace_precio', $anuncio['precio']);
                update_post_meta($post_id, '_marketplace_estado', $anuncio['estado']);
                update_post_meta($post_id, '_marketplace_ubicacion', $anuncio['ubicacion']);
                update_post_meta($post_id, '_marketplace_contacto', 'chat');
                update_post_meta($post_id, '_marketplace_fecha_expiracion', date('Y-m-d', strtotime('+30 days')));
                update_post_meta($post_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('marketplace', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d anuncios de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Marketplace
     */
    private function clear_marketplace() {
        $ids = $this->get_demo_ids('marketplace');

        if (empty($ids)) {
            return ['success' => true, 'count' => 0, 'message' => __('_flavor_demo_data', 'flavor-chat-ia')];
        }

        $eliminados = 0;
        foreach ($ids as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $eliminados++;
            }
        }

        $this->clear_demo_ids('marketplace');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d anuncios de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // GRUPOS DE CONSUMO
    // =========================================================

    /**
     * Pobla datos demo de Grupos de Consumo
     * Usa Custom Post Types: gc_productor, gc_producto, gc_ciclo
     */
    private function populate_grupos_consumo() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [
            'productores' => [],
            'productos' => [],
            'ciclos' => [],
            'pedidos' => [],
            'entregas' => [],
            'consolidados' => [],
        ];

        // Productores demo
        $productores_demo = [
            [
                'nombre' => 'Huerta El Manantial',
                'descripcion' => 'Producción ecológica de hortalizas de temporada. Certificación oficial. Entrega semanal los miércoles. Trabajamos con variedades locales y semillas tradicionales.',
                'ubicacion' => 'Valle de Egüés',
                'email' => 'demo-huerta@example.com',
                'telefono' => '600123456',
                'certificacion' => 'ecologico',
            ],
            [
                'nombre' => 'Granja Los Olivos',
                'descripcion' => 'Huevos de gallinas en libertad, quesos artesanos de cabra y leche fresca. Producción familiar con más de 30 años de tradición.',
                'ubicacion' => 'Tierra Estella',
                'email' => 'demo-granja@example.com',
                'telefono' => '600789012',
                'certificacion' => 'artesano',
            ],
            [
                'nombre' => 'Panadería Artesana El Horno',
                'descripcion' => 'Pan de masa madre, elaboración tradicional con harinas ecológicas molidas a la piedra. Horneamos cada día a las 6am.',
                'ubicacion' => 'Casco Antiguo',
                'email' => 'demo-pan@example.com',
                'telefono' => '600345678',
                'certificacion' => 'artesano',
            ],
        ];

        // Crear productores como CPT
        foreach ($productores_demo as $productor_data) {
            $productor_id = wp_insert_post([
                'post_title'   => $productor_data['nombre'],
                'post_content' => $productor_data['descripcion'],
                'post_status'  => 'publish',
                'post_type'    => 'gc_productor',
                'post_author'  => $usuario_id,
            ]);

            if ($productor_id && !is_wp_error($productor_id)) {
                $ids_insertados['productores'][] = $productor_id;

                // Guardar meta fields del productor
                update_post_meta($productor_id, '_gc_ubicacion', $productor_data['ubicacion']);
                update_post_meta($productor_id, '_gc_email', $productor_data['email']);
                update_post_meta($productor_id, '_gc_telefono', $productor_data['telefono']);
                update_post_meta($productor_id, '_gc_certificacion', $productor_data['certificacion']);
                update_post_meta($productor_id, '_gc_estado', 'activo');
                update_post_meta($productor_id, '_flavor_demo_data', true);

                // Crear productos para este productor
                $productos = $this->get_productos_demo_para_productor($productor_data['nombre']);
                foreach ($productos as $producto_data) {
                    $producto_id = wp_insert_post([
                        'post_title'   => $producto_data['nombre'],
                        'post_content' => $producto_data['descripcion'],
                        'post_status'  => 'publish',
                        'post_type'    => 'gc_producto',
                        'post_author'  => $usuario_id,
                    ]);

                    if ($producto_id && !is_wp_error($producto_id)) {
                        $ids_insertados['productos'][] = $producto_id;

                        // Guardar meta fields del producto
                        update_post_meta($producto_id, '_gc_productor_id', $productor_id);
                        update_post_meta($producto_id, '_gc_precio', $producto_data['precio']);
                        update_post_meta($producto_id, '_gc_unidad', $producto_data['unidad']);
                        update_post_meta($producto_id, '_gc_stock', $producto_data['stock']);
                        update_post_meta($producto_id, '_gc_estado', 'disponible');
                        update_post_meta($producto_id, '_flavor_demo_data', true);
                    }
                }
            }
        }

        // Crear un ciclo de pedido abierto
        $ciclo_id = wp_insert_post([
            'post_title'  => 'Pedido Semana ' . date('W') . ' - ' . date('F Y'),
            'post_status' => 'gc_abierto', // Estado personalizado
            'post_type'   => 'gc_ciclo',
            'post_author' => $usuario_id,
        ]);

        if ($ciclo_id && !is_wp_error($ciclo_id)) {
            $ids_insertados['ciclos'][] = $ciclo_id;

            // Meta fields del ciclo
            $fecha_cierre = date('Y-m-d 23:59:59', strtotime('+5 days'));
            $fecha_entrega = date('Y-m-d 10:00:00', strtotime('+7 days'));

            update_post_meta($ciclo_id, '_gc_fecha_cierre', $fecha_cierre);
            update_post_meta($ciclo_id, '_gc_fecha_entrega', $fecha_entrega);
            update_post_meta($ciclo_id, '_gc_lugar_entrega', 'Centro Cívico - Plaza Mayor, 1');
            update_post_meta($ciclo_id, '_gc_notas', 'Recogida de pedidos de 10:00 a 13:00. Traer bolsas propias.');
            update_post_meta($ciclo_id, '_flavor_demo_data', true);
        }

        // Usar usuarios demo compartidos - solo marcar como consumidores con meta
        $usuarios_compartidos = $this->get_or_create_demo_users();
        $ids_insertados['usuarios'] = $usuarios_compartidos;
        $ids_insertados['consumidores'] = [];
        $ids_insertados['pedidos'] = [];

        // Usar solo los primeros 5 usuarios para este módulo
        $usuarios_para_gc = array_slice($usuarios_compartidos, 0, 5);

        foreach ($usuarios_para_gc as $usuario_id_compartido) {
            $user = get_userdata($usuario_id_compartido);
            if (!$user) continue;

            // Marcar usuario como consumidor de grupo de consumo (usando meta en lugar de tabla)
            update_user_meta($usuario_id_compartido, '_gc_consumidor', true);
            update_user_meta($usuario_id_compartido, '_gc_grupo_id', 1);
            $ids_insertados['consumidores'][] = $usuario_id_compartido;
        }

        // Crear pedidos de prueba si hay ciclo y productos
        if ($ciclo_id && !empty($ids_insertados['productos']) && !empty($usuarios_para_gc)) {
            global $wpdb;
            $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

            // Crear entre 3-8 pedidos por usuario
            foreach ($usuarios_para_gc as $usuario_id_compartido) {
                $num_pedidos = rand(3, 8);
                $productos_disponibles = $ids_insertados['productos'];
                shuffle($productos_disponibles);
                $productos_elegidos = array_slice($productos_disponibles, 0, $num_pedidos);

                foreach ($productos_elegidos as $producto_id) {
                    $precio = get_post_meta($producto_id, '_gc_precio', true);
                    $cantidad = rand(1, 5) * 0.5; // 0.5, 1, 1.5, 2, 2.5 kg

                    $pedido_id = $wpdb->insert(
                        $tabla_pedidos,
                        [
                            'ciclo_id' => $ciclo_id,
                            'usuario_id' => $usuario_id_compartido,
                            'producto_id' => $producto_id,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precio,
                            'estado' => 'confirmado',
                            'fecha_pedido' => current_time('mysql'),
                            'fecha_modificacion' => current_time('mysql'),
                        ],
                        ['%d', '%d', '%d', '%f', '%f', '%s', '%s', '%s']
                    );

                    if ($pedido_id) {
                        $ids_insertados['pedidos'][] = $wpdb->insert_id;
                    }
                }
            }

            // Crear entregas para los usuarios con pedidos
            if (!empty($ids_insertados['pedidos'])) {
                $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';

                foreach ($usuarios_para_gc as $usuario_id_compartido) {
                    // Calcular total de pedidos del usuario
                    $pedidos_usuario = $wpdb->get_results($wpdb->prepare(
                        "SELECT SUM(cantidad * precio_unitario) as total
                         FROM $tabla_pedidos
                         WHERE ciclo_id = %d AND usuario_id = %d",
                        $ciclo_id,
                        $usuario_id_compartido
                    ));

                    if ($pedidos_usuario && $pedidos_usuario[0]->total > 0) {
                        $total_pedido = $pedidos_usuario[0]->total;
                        $gastos_gestion = $total_pedido * 0.05; // 5% de gastos
                        $total_final = $total_pedido + $gastos_gestion;

                        $entrega_id = $wpdb->insert(
                            $tabla_entregas,
                            [
                                'ciclo_id' => $ciclo_id,
                                'usuario_id' => $usuario_id_compartido,
                                'total_pedido' => $total_pedido,
                                'gastos_gestion' => $gastos_gestion,
                                'total_final' => $total_final,
                                'estado_pago' => rand(0, 1) ? 'pagado' : 'pendiente',
                                'fecha_pago' => rand(0, 1) ? current_time('mysql') : null,
                                'metodo_pago' => rand(0, 1) ? 'transferencia' : null,
                                'estado_recogida' => 'pendiente',
                                'fecha_creacion' => current_time('mysql'),
                            ],
                            ['%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s']
                        );

                        if ($entrega_id) {
                            $ids_insertados['entregas'][] = $wpdb->insert_id;
                        }
                    }
                }
            }

            // Crear consolidado por producto
            if (!empty($ids_insertados['productos'])) {
                $tabla_consolidado = $wpdb->prefix . 'flavor_gc_consolidado';

                foreach ($ids_insertados['productos'] as $producto_id) {
                    $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);

                    // Calcular cantidad total pedida
                    $consolidado = $wpdb->get_results($wpdb->prepare(
                        "SELECT SUM(cantidad) as total, COUNT(*) as num_pedidos
                         FROM $tabla_pedidos
                         WHERE ciclo_id = %d AND producto_id = %d",
                        $ciclo_id,
                        $producto_id
                    ));

                    if ($consolidado && $consolidado[0]->total > 0) {
                        $wpdb->insert(
                            $tabla_consolidado,
                            [
                                'ciclo_id' => $ciclo_id,
                                'productor_id' => $productor_id,
                                'producto_id' => $producto_id,
                                'cantidad_total' => $consolidado[0]->total,
                                'numero_pedidos' => $consolidado[0]->num_pedidos,
                                'estado' => 'pendiente',
                                'fecha_solicitud' => current_time('mysql'),
                            ],
                            ['%d', '%d', '%d', '%f', '%d', '%s', '%s']
                        );

                        if ($wpdb->insert_id) {
                            $ids_insertados['consolidados'][] = $wpdb->insert_id;
                        }
                    }
                }
            }
        }

        $this->mark_as_demo('grupos_consumo', $ids_insertados);

        $total_productores = count($ids_insertados['productores']);
        $total_productos = count($ids_insertados['productos']);
        $total_ciclos = count($ids_insertados['ciclos']);
        $total_usuarios = count($ids_insertados['consumidores']);
        $total_pedidos = count($ids_insertados['pedidos']);
        $total_entregas = isset($ids_insertados['entregas']) ? count($ids_insertados['entregas']) : 0;

        return [
            'success' => true,
            'count' => $total_productores + $total_productos + $total_ciclos + $total_usuarios + $total_pedidos + $total_entregas,
            'message' => sprintf(
                'Se insertaron %d productores, %d productos, %d ciclo, %d consumidores, %d pedidos y %d entregas',
                $total_productores,
                $total_productos,
                $total_ciclos,
                $total_usuarios,
                $total_pedidos,
                $total_entregas
            ),
        ];
    }

    /**
     * Obtiene productos demo para un productor
     */
    private function get_productos_demo_para_productor($nombre_productor) {
        $productos_por_tipo = [
            'Huerta El Manantial' => [
                ['nombre' => 'Tomates de temporada', 'descripcion' => 'Tomates maduros en planta, variedades tradicionales. Sabor intenso, perfectos para ensalada o gazpacho.', 'precio' => 2.50, 'unidad' => 'kg', 'stock' => 50],
                ['nombre' => 'Lechugas variadas', 'descripcion' => 'Mix de lechugas frescas: romana, batavia y hoja de roble. Recogidas el mismo día del reparto.', 'precio' => 1.20, 'unidad' => 'unidad', 'stock' => 30],
                ['nombre' => 'Calabacines ecológicos', 'descripcion' => 'Calabacines tiernos de cultivo ecológico. Ideales para tortilla, crema o a la plancha.', 'precio' => 1.80, 'unidad' => 'kg', 'stock' => 40],
                ['nombre' => 'Pimientos verdes italianos', 'descripcion' => 'Pimientos verdes de freír, variedad italiana. Finos y muy sabrosos.', 'precio' => 2.00, 'unidad' => 'kg', 'stock' => 25],
                ['nombre' => 'Zanahorias', 'descripcion' => 'Zanahorias frescas con hojas. Dulces y crujientes.', 'precio' => 1.50, 'unidad' => 'kg', 'stock' => 35],
            ],
            'Granja Los Olivos' => [
                ['nombre' => 'Huevos camperos', 'descripcion' => 'Docena de huevos de gallinas criadas en libertad. Yemas naranjas y sabor auténtico.', 'precio' => 3.50, 'unidad' => 'docena', 'stock' => 40],
                ['nombre' => 'Queso fresco de cabra', 'descripcion' => 'Queso fresco artesano de cabra, pieza de 250g. Suave y cremoso.', 'precio' => 4.50, 'unidad' => 'unidad', 'stock' => 20],
                ['nombre' => 'Queso curado de cabra', 'descripcion' => 'Queso curado 6 meses, pieza de 350g. Sabor intenso y textura firme.', 'precio' => 7.50, 'unidad' => 'unidad', 'stock' => 15],
                ['nombre' => 'Leche fresca de cabra', 'descripcion' => 'Leche de cabra pasteurizada, botella de 1L. Recogida del día.', 'precio' => 1.80, 'unidad' => 'litro', 'stock' => 30],
                ['nombre' => 'Yogur natural de cabra', 'descripcion' => 'Pack de 4 yogures naturales artesanos. Sin azúcar añadido.', 'precio' => 3.20, 'unidad' => 'pack', 'stock' => 25],
            ],
            'Panadería Artesana El Horno' => [
                ['nombre' => 'Pan de masa madre', 'descripcion' => 'Hogaza de 500g con fermentación lenta de 24h. Corteza crujiente, miga esponjosa.', 'precio' => 3.00, 'unidad' => 'unidad', 'stock' => 25],
                ['nombre' => 'Pan integral con semillas', 'descripcion' => 'Pan integral 400g con semillas de girasol, lino y sésamo. Rico en fibra.', 'precio' => 2.80, 'unidad' => 'unidad', 'stock' => 20],
                ['nombre' => 'Pan de centeno', 'descripcion' => 'Pan de centeno alemán, 450g. Denso y aromático, perfecto con queso.', 'precio' => 3.20, 'unidad' => 'unidad', 'stock' => 15],
                ['nombre' => 'Bizcocho casero de limón', 'descripcion' => 'Bizcocho tradicional de limón, elaborado con huevos camperos y ralladura fresca.', 'precio' => 5.50, 'unidad' => 'unidad', 'stock' => 10],
                ['nombre' => 'Empanada gallega', 'descripcion' => 'Empanada de atún tradicional, tamaño familiar (4-6 raciones).', 'precio' => 8.00, 'unidad' => 'unidad', 'stock' => 8],
            ],
        ];

        return $productos_por_tipo[$nombre_productor] ?? [];
    }

    /**
     * Limpia datos demo de Grupos de Consumo
     * Elimina los CPTs creados como demo
     */
    private function clear_grupos_consumo() {
        $ids = $this->get_demo_ids('grupos_consumo');

        if (empty($ids)) {
            // También buscar por meta _flavor_demo_data
            $posts_demo = get_posts([
                'post_type' => ['gc_productor', 'gc_producto', 'gc_ciclo'],
                'posts_per_page' => -1,
                'meta_key' => '_flavor_demo_data',
                'meta_value' => '1',
                'fields' => 'ids',
            ]);

            if (empty($posts_demo)) {
                return ['success' => true, 'count' => 0, 'message' => __('manage_options', 'flavor-chat-ia')];
            }

            $ids = [
                'productores' => [],
                'productos' => [],
                'ciclos' => [],
            ];

            foreach ($posts_demo as $post_id) {
                $post_type = get_post_type($post_id);
                if ($post_type === 'gc_productor') {
                    $ids['productores'][] = $post_id;
                } elseif ($post_type === 'gc_producto') {
                    $ids['productos'][] = $post_id;
                } elseif ($post_type === 'gc_ciclo') {
                    $ids['ciclos'][] = $post_id;
                }
            }
        }

        $eliminados = 0;

        // Eliminar productos primero (dependen de productores)
        if (!empty($ids['productos'])) {
            foreach ($ids['productos'] as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        // Eliminar ciclos
        if (!empty($ids['ciclos'])) {
            foreach ($ids['ciclos'] as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        // Eliminar productores
        if (!empty($ids['productores'])) {
            foreach ($ids['productores'] as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        // Eliminar consolidados primero (dependen de productos)
        global $wpdb;
        if (!empty($ids['consolidados'])) {
            $tabla_consolidado = $wpdb->prefix . 'flavor_gc_consolidado';
            foreach ($ids['consolidados'] as $consolidado_id) {
                $wpdb->delete($tabla_consolidado, ['id' => $consolidado_id], ['%d']);
                $eliminados++;
            }
        }

        // Eliminar entregas
        if (!empty($ids['entregas'])) {
            $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
            foreach ($ids['entregas'] as $entrega_id) {
                $wpdb->delete($tabla_entregas, ['id' => $entrega_id], ['%d']);
                $eliminados++;
            }
        }

        // Eliminar pedidos de la tabla
        if (!empty($ids['pedidos'])) {
            $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
            foreach ($ids['pedidos'] as $pedido_id) {
                $wpdb->delete($tabla_pedidos, ['id' => $pedido_id], ['%d']);
                $eliminados++;
            }
        }

        // Eliminar consumidores de la tabla
        if (!empty($ids['consumidores'])) {
            $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
            foreach ($ids['consumidores'] as $consumidor_id) {
                $wpdb->delete($tabla_consumidores, ['id' => $consumidor_id], ['%d']);
                $eliminados++;
            }
        }

        // NOTA: No eliminamos los usuarios WordPress porque son compartidos con otros módulos
        // Los usuarios demo se eliminan solo cuando se ejecuta "Limpiar todo"

        $this->clear_demo_ids('grupos_consumo');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d registros de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // AYUDA VECINAL
    // =========================================================

    /**
     * Pobla datos demo de Ayuda Vecinal
     */
    private function populate_ayuda_vecinal() {
        global $wpdb;
        // Nombre correcto según el módulo
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        // Verificar que la tabla existe - Si no, intentar crearla
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            // Intentar crear las tablas del módulo
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/ayuda-vecinal/class-ayuda-vecinal-module.php';
            if (class_exists('Flavor_Chat_Ayuda_Vecinal_Module')) {
                $modulo = new Flavor_Chat_Ayuda_Vecinal_Module();
                // Ejecutar el init directamente
                if (method_exists($modulo, 'init')) {
                    $modulo->init();
                }

                // Verificar de nuevo
                if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
                    return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
                }
            } else {
                return ['success' => false, 'error' => __('cursos', 'flavor-chat-ia')];
            }
        }

        $usuarios_compartidos = $this->get_or_create_demo_users();
        $ids_insertados = [];

        $solicitudes_demo = [
            [
                'titulo' => 'Necesito ayuda con la compra semanal',
                'descripcion' => 'Soy una persona mayor y me cuesta cargar las bolsas. Busco alguien que pueda acompañarme al supermercado una vez por semana.',
                'categoria' => 'compras',
                'urgencia' => 'media',
            ],
            [
                'titulo' => 'Busco quien pasee a mi perro',
                'descripcion' => 'Me han operado y no puedo caminar mucho. Necesito alguien que pasee a mi perro (es pequeño y tranquilo) 2 veces al día durante 2 semanas.',
                'categoria' => 'mascotas',
                'urgencia' => 'alta',
            ],
            [
                'titulo' => 'Ayuda con tecnología - configurar móvil',
                'descripcion' => 'Acabo de comprar un smartphone y no sé configurarlo. Necesito ayuda para instalar WhatsApp y aprender lo básico.',
                'categoria' => 'tecnologia',
                'urgencia' => 'baja',
            ],
            [
                'titulo' => 'Acompañamiento a cita médica',
                'descripcion' => 'Tengo una cita importante en el hospital el próximo martes a las 10h. Busco alguien que pueda acompañarme.',
                'categoria' => 'transporte',
                'urgencia' => 'alta',
            ],
            [
                'titulo' => 'Pequeña reparación en casa',
                'descripcion' => 'Se me ha estropeado el grifo de la cocina y gotea constantemente. Busco alguien que sepa de fontanería básica.',
                'categoria' => 'reparaciones',
                'urgencia' => 'media',
            ],
        ];

        foreach ($solicitudes_demo as $index => $solicitud) {
            $solicitante_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];

            $resultado = $wpdb->insert(
                $tabla_solicitudes,
                [
                    'solicitante_id' => $solicitante_id,
                    'categoria' => $solicitud['categoria'],
                    'titulo' => $solicitud['titulo'],
                    'descripcion' => $solicitud['descripcion'],
                    'urgencia' => $solicitud['urgencia'],
                    'estado' => 'abierta',
                    'fecha_solicitud' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($resultado) {
                $ids_insertados[] = $wpdb->insert_id;
            }
        }

        $this->mark_as_demo('ayuda_vecinal', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d solicitudes de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Ayuda Vecinal
     */
    private function clear_ayuda_vecinal() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $ids = $this->get_demo_ids('ayuda_vecinal');

        if (empty($ids)) {
            return ['success' => true, 'count' => 0, 'message' => __('Hotel Demo', 'flavor-chat-ia')];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_solicitudes WHERE id IN ($placeholders)",
            ...$ids
        ));

        $this->clear_demo_ids('ayuda_vecinal');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d solicitudes de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // UTILIDADES
    // =========================================================

    /**
     * Verifica si un módulo tiene datos demo
     *
     * @param string $modulo_id
     * @return bool
     */
    public function has_demo_data($modulo_id) {
        $ids = $this->get_demo_ids($modulo_id);
        return !empty($ids);
    }

    /**
     * Obtiene el conteo de datos demo de un módulo
     *
     * @param string $modulo_id
     * @return int
     */
    public function get_demo_data_count($modulo_id) {
        $ids = $this->get_demo_ids($modulo_id);

        if (is_array($ids) && isset($ids[0]) && is_array($ids[0])) {
            // Es un array multidimensional (grupos_consumo)
            return array_sum(array_map('count', $ids));
        }

        return count($ids);
    }

    // =========================================================
    // RESERVAS
    // =========================================================

    /**
     * Pobla datos demo de Reservas
     */
    private function populate_reservas() {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        // Verificar que la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
            return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
        }

        // Usar usuarios demo compartidos
        $usuarios_compartidos = $this->get_or_create_demo_users();
        $ids_insertados = [];

        // Configuración de reservas (próximos 14 días)
        $reservas_config = [
            ['personas' => 4, 'dias' => 1, 'hora' => '20:30', 'notas' => 'Cumpleaños, necesitamos una tarta'],
            ['personas' => 2, 'dias' => 1, 'hora' => '21:00', 'notas' => ''],
            ['personas' => 6, 'dias' => 2, 'hora' => '13:30', 'notas' => 'Menú infantil para 2 niños'],
            ['personas' => 3, 'dias' => 3, 'hora' => '21:30', 'notas' => ''],
            ['personas' => 8, 'dias' => 5, 'hora' => '14:00', 'notas' => 'Comida de empresa'],
            ['personas' => 2, 'dias' => 7, 'hora' => '20:00', 'notas' => 'Aniversario'],
            ['personas' => 5, 'dias' => 10, 'hora' => '13:00', 'notas' => 'Terraza si es posible'],
            ['personas' => 4, 'dias' => 12, 'hora' => '21:00', 'notas' => ''],
        ];

        // Crear reservas con datos de usuarios reales
        foreach ($reservas_config as $index => $config) {
            $usuario_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];
            $user = get_userdata($usuario_id);
            if (!$user) continue;

            $fecha_reserva = date('Y-m-d', strtotime("+{$config['dias']} days")) . ' ' . $config['hora'] . ':00';

            $result = $wpdb->insert(
                $tabla_reservas,
                [
                    'nombre' => $user->first_name . ' ' . $user->last_name,
                    'telefono' => get_user_meta($usuario_id, 'telefono', true),
                    'email' => $user->user_email,
                    'fecha_reserva' => $fecha_reserva,
                    'num_personas' => $config['personas'],
                    'notas' => $config['notas'],
                    'estado' => 'confirmada',
                    'usuario_id' => $usuario_id,
                    'fecha_creacion' => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s']
            );

            if ($result) {
                $ids_insertados[] = $wpdb->insert_id;
            }
        }

        $this->mark_as_demo('reservas', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d reservas de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Reservas
     */
    private function clear_reservas() {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $ids = $this->get_demo_ids('reservas');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $wpdb->delete($tabla_reservas, ['id' => $id], ['%d']);
                $eliminados++;
            }
        }

        $this->clear_demo_ids('reservas');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d reservas de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // SOCIOS
    // =========================================================

    /**
     * Pobla datos demo de Socios
     */
    private function populate_socios() {
        // Usar usuarios demo compartidos
        $usuarios_compartidos = $this->get_or_create_demo_users();
        $admin_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $tipos_socios = ['familiar', 'individual', 'familiar', 'juvenil', 'individual', 'familiar', 'individual', 'senior'];
        $cuotas = [45.00, 30.00, 45.00, 15.00, 30.00, 45.00, 30.00, 20.00];

        foreach ($usuarios_compartidos as $index => $usuario_id) {
            $user = get_userdata($usuario_id);
            if (!$user) continue;

            $numero = str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            $socio_id = wp_insert_post([
                'post_title'   => $user->first_name . ' ' . $user->last_name,
                'post_content' => 'Socio de ejemplo vinculado a usuario real.',
                'post_status'  => 'publish',
                'post_type'    => 'socio',
                'post_author'  => $admin_id,
            ]);

            if ($socio_id && !is_wp_error($socio_id)) {
                $ids_insertados[] = $socio_id;

                update_post_meta($socio_id, '_socio_usuario_id', $usuario_id);
                update_post_meta($socio_id, '_socio_numero', $numero);
                update_post_meta($socio_id, '_socio_tipo', $tipos_socios[$index % count($tipos_socios)]);
                update_post_meta($socio_id, '_socio_cuota_mensual', $cuotas[$index % count($cuotas)]);
                update_post_meta($socio_id, '_socio_estado', 'activo');
                update_post_meta($socio_id, '_socio_fecha_alta', date('Y-m-d'));
                update_post_meta($socio_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('socios', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d socios de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Socios
     */
    private function clear_socios() {
        $ids = $this->get_demo_ids('socios');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('socios');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d socios de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // FICHAJE EMPLEADOS
    // =========================================================

    /**
     * Pobla datos demo de Fichaje de Empleados
     */
    private function populate_fichaje_empleados() {
        global $wpdb;

        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        // Verificar que la tabla de fichajes existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            // Intentar crear las tablas del módulo
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/fichaje-empleados/class-fichaje-empleados-module.php';
            if (class_exists('Flavor_Chat_Fichaje_Empleados_Module')) {
                $modulo = new Flavor_Chat_Fichaje_Empleados_Module();
                // El módulo crea tablas en __construct vía init hook
                do_action('init');

                // Verificar de nuevo
                if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
                    return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
                }
            }
        }

        $usuarios_compartidos = $this->get_or_create_demo_users();
        $ids_insertados = [];

        // Crear fichajes directamente para los usuarios demo (sin tabla empleados)
        foreach ($usuarios_compartidos as $usuario_id) {
            // Marcar al usuario con meta de empleado demo
            update_user_meta($usuario_id, '_flavor_empleado_demo', true);
            update_user_meta($usuario_id, '_flavor_departamento', ['Administración', 'Operaciones', 'Servicios'][array_rand(['Administración', 'Operaciones', 'Servicios'])]);

            // Crear fichajes de los últimos 30 días
            for ($dia = 30; $dia >= 1; $dia--) {
                $fecha_hora = date('Y-m-d', strtotime("-{$dia} days"));
                $dia_semana = date('N', strtotime($fecha_hora)); // 1=lunes, 7=domingo

                // Saltar fines de semana
                if ($dia_semana >= 6) continue;

                // Fichaje de ENTRADA (por la mañana)
                $minutos_var_entrada = rand(-15, 15);
                $hora_entrada = date('Y-m-d H:i:s', strtotime($fecha_hora . " 08:00:00 +{$minutos_var_entrada} minutes"));

                $entrada_result = $wpdb->insert(
                    $tabla_fichajes,
                    [
                        'usuario_id' => $usuario_id,
                        'tipo' => 'entrada',
                        'fecha_hora' => $hora_entrada,
                        'latitud' => null,
                        'longitud' => null,
                        'dispositivo' => 'web',
                        'ip_address' => '127.0.0.1',
                        'notas' => 'Fichaje demo automático',
                        'validado' => 1,
                        'fecha_creacion' => $hora_entrada,
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
                );

                if ($entrada_result) {
                    $ids_insertados[] = $wpdb->insert_id;
                }

                // Fichaje de SALIDA (por la tarde)
                $minutos_var_salida = rand(-15, 15);
                $hora_salida = date('Y-m-d H:i:s', strtotime($fecha_hora . " 17:00:00 +{$minutos_var_salida} minutes"));

                $salida_result = $wpdb->insert(
                    $tabla_fichajes,
                    [
                        'usuario_id' => $usuario_id,
                        'tipo' => 'salida',
                        'fecha_hora' => $hora_salida,
                        'latitud' => null,
                        'longitud' => null,
                        'dispositivo' => 'web',
                        'ip_address' => '127.0.0.1',
                        'notas' => 'Fichaje demo automático',
                        'validado' => 1,
                        'fecha_creacion' => $hora_salida,
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
                );

                if ($salida_result) {
                    $ids_insertados[] = $wpdb->insert_id;
                }
            }
        }

        $this->mark_as_demo('fichaje_empleados', $ids_insertados);

        return [
            'success' => true,
            'counts' => [
                'empleados' => count($usuarios_compartidos),
                'fichajes' => count($ids_insertados)
            ],
            'message' => sprintf('Se crearon fichajes para %d empleados (%d registros totales)',
                count($usuarios_compartidos),
                count($ids_insertados)
            ),
        ];
    }

    /**
     * Limpia datos demo de Fichaje Empleados
     */
    private function clear_fichaje_empleados() {
        global $wpdb;

        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $fichaje_ids = $this->get_demo_ids('fichaje_empleados');

        $eliminados = 0;

        // Eliminar fichajes
        if (!empty($fichaje_ids)) {
            foreach ($fichaje_ids as $fichaje_id) {
                $result = $wpdb->delete($tabla_fichajes, ['id' => $fichaje_id], ['%d']);
                if ($result) {
                    $eliminados++;
                }
            }
        }

        // Limpiar meta de empleados en usuarios
        $usuarios_demo = get_users([
            'meta_key' => '_flavor_empleado_demo',
            'meta_value' => true,
        ]);

        foreach ($usuarios_demo as $user) {
            delete_user_meta($user->ID, '_flavor_empleado_demo');
            delete_user_meta($user->ID, '_flavor_departamento');
        }

        $this->clear_demo_ids('fichaje_empleados');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d fichajes demo', $eliminados),
        ];
    }

    // =========================================================
    // FACTURAS
    // =========================================================

    /**
     * Pobla datos demo de Facturas
     */
    private function populate_facturas() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $facturas_demo = [
            ['numero' => '2026-001', 'cliente' => 'Restaurante El Jardín', 'importe' => 450.00, 'dias' => -15],
            ['numero' => '2026-002', 'cliente' => 'Bar La Plaza', 'importe' => 320.50, 'dias' => -10],
            ['numero' => '2026-003', 'cliente' => 'Cafetería Central', 'importe' => 275.00, 'dias' => -5],
            ['numero' => '2026-004', 'cliente' => 'Hotel Vista Mar', 'importe' => 890.00, 'dias' => -3],
            ['numero' => '2026-005', 'cliente' => 'Panadería San José', 'importe' => 180.00, 'dias' => -1],
        ];

        foreach ($facturas_demo as $factura) {
            $factura_id = wp_insert_post([
                'post_title'   => 'Factura ' . $factura['numero'] . ' - ' . $factura['cliente'],
                'post_content' => 'Factura de ejemplo creada automáticamente.',
                'post_status'  => 'publish',
                'post_type'    => 'factura',
                'post_author'  => $usuario_id,
            ]);

            if ($factura_id && !is_wp_error($factura_id)) {
                $ids_insertados[] = $factura_id;

                $fecha_emision = date('Y-m-d', strtotime("{$factura['dias']} days"));
                $fecha_vencimiento = date('Y-m-d', strtotime("{$factura['dias']} days +30 days"));

                update_post_meta($factura_id, '_factura_numero', $factura['numero']);
                update_post_meta($factura_id, '_factura_cliente', $factura['cliente']);
                update_post_meta($factura_id, '_factura_importe_total', $factura['importe']);
                update_post_meta($factura_id, '_factura_fecha_emision', $fecha_emision);
                update_post_meta($factura_id, '_factura_fecha_vencimiento', $fecha_vencimiento);
                update_post_meta($factura_id, '_factura_estado', 'emitida');
                update_post_meta($factura_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('facturas', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d facturas de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Facturas
     */
    private function clear_facturas() {
        $ids = $this->get_demo_ids('facturas');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('facturas');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d facturas de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // INCIDENCIAS
    // =========================================================

    /**
     * Pobla datos demo de Incidencias
     */
    private function populate_incidencias() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $incidencias_demo = [
            ['titulo' => 'Papelera rota en Plaza Mayor', 'categoria' => 'mobiliario_urbano', 'prioridad' => 'media', 'ubicacion' => 'Plaza Mayor, junto al quiosco'],
            ['titulo' => 'Bache peligroso en C/ San Pedro', 'categoria' => 'calzada', 'prioridad' => 'alta', 'ubicacion' => 'C/ San Pedro, altura nº 15'],
            ['titulo' => 'Farola fundida en Parque Central', 'categoria' => 'alumbrado', 'prioridad' => 'alta', 'ubicacion' => 'Parque Central, zona infantil'],
            ['titulo' => 'Graffiti en fachada municipal', 'categoria' => 'limpieza', 'prioridad' => 'baja', 'ubicacion' => 'Edificio Ayuntamiento, pared lateral'],
            ['titulo' => 'Alcantarilla atascada', 'categoria' => 'saneamiento', 'prioridad' => 'alta', 'ubicacion' => 'Av. Libertad esquina C/ Mayor'],
            ['titulo' => 'Banco roto en parada de bus', 'categoria' => 'mobiliario_urbano', 'prioridad' => 'media', 'ubicacion' => 'Parada bus línea 3'],
            ['titulo' => 'Contenedor de basura desbordado', 'categoria' => 'residuos', 'prioridad' => 'alta', 'ubicacion' => 'C/ Nueva, nº 22'],
            ['titulo' => 'Señal de tráfico vandalizad', 'categoria' => 'senalizacion', 'prioridad' => 'media', 'ubicacion' => 'Cruce Av. Paz con C/ Sol'],
        ];

        foreach ($incidencias_demo as $incidencia) {
            $incidencia_id = wp_insert_post([
                'post_title'   => $incidencia['titulo'],
                'post_content' => 'Incidencia reportada por vecino. Requiere revisión del servicio correspondiente.',
                'post_status'  => 'publish',
                'post_type'    => 'incidencia',
                'post_author'  => $usuario_id,
            ]);

            if ($incidencia_id && !is_wp_error($incidencia_id)) {
                $ids_insertados[] = $incidencia_id;

                update_post_meta($incidencia_id, '_incidencia_categoria', $incidencia['categoria']);
                update_post_meta($incidencia_id, '_incidencia_prioridad', $incidencia['prioridad']);
                update_post_meta($incidencia_id, '_incidencia_ubicacion', $incidencia['ubicacion']);
                update_post_meta($incidencia_id, '_incidencia_estado', 'pendiente');
                update_post_meta($incidencia_id, '_incidencia_fecha_reporte', current_time('mysql'));
                update_post_meta($incidencia_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('incidencias', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d incidencias de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Incidencias
     */
    private function clear_incidencias() {
        $ids = $this->get_demo_ids('incidencias');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('incidencias');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d incidencias de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // TALLERES
    // =========================================================

    /**
     * Pobla datos demo de Talleres
     */
    private function populate_talleres() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $talleres_demo = [
            ['titulo' => 'Iniciación a la Fotografía Digital', 'plazas' => 15, 'precio' => 45.00, 'duracion' => '4 semanas'],
            ['titulo' => 'Yoga para Principiantes', 'plazas' => 20, 'precio' => 35.00, 'duracion' => '8 semanas'],
            ['titulo' => 'Cocina Mediterránea Saludable', 'plazas' => 12, 'precio' => 60.00, 'duracion' => '6 sesiones'],
            ['titulo' => 'Taller de Cerámica', 'plazas' => 10, 'precio' => 75.00, 'duracion' => '10 semanas'],
            ['titulo' => 'Informática Básica para Seniors', 'plazas' => 15, 'precio' => 0.00, 'duracion' => '6 semanas'],
            ['titulo' => 'Pintura al Óleo', 'plazas' => 12, 'precio' => 55.00, 'duracion' => '8 semanas'],
        ];

        foreach ($talleres_demo as $taller) {
            $taller_id = wp_insert_post([
                'post_title'   => $taller['titulo'],
                'post_content' => 'Taller impartido por profesionales. Incluye material didáctico y certificado de asistencia.',
                'post_status'  => 'publish',
                'post_type'    => 'taller',
                'post_author'  => $usuario_id,
            ]);

            if ($taller_id && !is_wp_error($taller_id)) {
                $ids_insertados[] = $taller_id;

                $fecha_inicio = date('Y-m-d', strtotime('+' . rand(7, 30) . ' days'));

                update_post_meta($taller_id, '_taller_plazas_totales', $taller['plazas']);
                update_post_meta($taller_id, '_taller_plazas_disponibles', rand(0, $taller['plazas']));
                update_post_meta($taller_id, '_taller_precio', $taller['precio']);
                update_post_meta($taller_id, '_taller_duracion', $taller['duracion']);
                update_post_meta($taller_id, '_taller_fecha_inicio', $fecha_inicio);
                update_post_meta($taller_id, '_taller_estado', 'abierto');
                update_post_meta($taller_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('talleres', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d talleres de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Talleres
     */
    private function clear_talleres() {
        $ids = $this->get_demo_ids('talleres');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('talleres');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d talleres de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // TRAMITES
    // =========================================================

    /**
     * Pobla datos demo de Trámites
     */
    private function populate_tramites() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $tramites_demo = [
            ['titulo' => 'Solicitud de Empadronamiento', 'area' => 'registro', 'plazo' => '10 días'],
            ['titulo' => 'Licencia de Apertura Comercio', 'area' => 'urbanismo', 'plazo' => '2 meses'],
            ['titulo' => 'Certificado de Residencia', 'area' => 'registro', 'plazo' => '5 días'],
            ['titulo' => 'Solicitud de Licencia de Obras', 'area' => 'urbanismo', 'plazo' => '3 meses'],
            ['titulo' => 'Inscripción Actividades Deportivas', 'area' => 'deportes', 'plazo' => '15 días'],
            ['titulo' => 'Baja Padrón Municipal', 'area' => 'registro', 'plazo' => '10 días'],
        ];

        foreach ($tramites_demo as $tramite) {
            $tramite_id = wp_insert_post([
                'post_title'   => $tramite['titulo'],
                'post_content' => 'Documentación necesaria: DNI, justificante de domicilio. Se puede realizar presencialmente o por sede electrónica.',
                'post_status'  => 'publish',
                'post_type'    => 'tramite',
                'post_author'  => $usuario_id,
            ]);

            if ($tramite_id && !is_wp_error($tramite_id)) {
                $ids_insertados[] = $tramite_id;

                update_post_meta($tramite_id, '_tramite_area', $tramite['area']);
                update_post_meta($tramite_id, '_tramite_plazo_resolucion', $tramite['plazo']);
                update_post_meta($tramite_id, '_tramite_tipo', 'presencial_online');
                update_post_meta($tramite_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('tramites', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d trámites de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Trámites
     */
    private function clear_tramites() {
        $ids = $this->get_demo_ids('tramites');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('tramites');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d trámites de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // AVISOS MUNICIPALES
    // =========================================================

    /**
     * Pobla datos demo de Avisos Municipales
     */
    private function populate_avisos_municipales() {
        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $avisos_demo = [
            ['titulo' => 'Corte de agua programado', 'tipo' => 'servicio', 'prioridad' => 'alta', 'fecha' => '+2 days'],
            ['titulo' => 'Fiestas patronales 2026', 'tipo' => 'evento', 'prioridad' => 'media', 'fecha' => '+30 days'],
            ['titulo' => 'Campaña vacunación infantil', 'tipo' => 'salud', 'prioridad' => 'alta', 'fecha' => '+7 days'],
            ['titulo' => 'Obras en Avenida Principal', 'tipo' => 'obras', 'prioridad' => 'media', 'fecha' => '+1 day'],
            ['titulo' => 'Apertura piscina municipal', 'tipo' => 'servicio', 'prioridad' => 'baja', 'fecha' => '+45 days'],
        ];

        foreach ($avisos_demo as $aviso) {
            $aviso_id = wp_insert_post([
                'post_title'   => $aviso['titulo'],
                'post_content' => 'Información importante para todos los vecinos. Consultar en la web municipal o llamar al teléfono de información.',
                'post_status'  => 'publish',
                'post_type'    => 'aviso_municipal',
                'post_author'  => $usuario_id,
            ]);

            if ($aviso_id && !is_wp_error($aviso_id)) {
                $ids_insertados[] = $aviso_id;

                $fecha_publicacion = date('Y-m-d', strtotime($aviso['fecha']));

                update_post_meta($aviso_id, '_aviso_tipo', $aviso['tipo']);
                update_post_meta($aviso_id, '_aviso_prioridad', $aviso['prioridad']);
                update_post_meta($aviso_id, '_aviso_fecha_publicacion', $fecha_publicacion);
                update_post_meta($aviso_id, '_aviso_activo', 1);
                update_post_meta($aviso_id, '_flavor_demo_data', true);
            }
        }

        $this->mark_as_demo('avisos_municipales', $ids_insertados);

        return [
            'success' => true,
            'count' => count($ids_insertados),
            'message' => sprintf('Se insertaron %d avisos municipales de ejemplo', count($ids_insertados)),
        ];
    }

    /**
     * Limpia datos demo de Avisos Municipales
     */
    private function clear_avisos_municipales() {
        $ids = $this->get_demo_ids('avisos_municipales');

        $eliminados = 0;
        if (!empty($ids)) {
            foreach ($ids as $post_id) {
                if (wp_delete_post($post_id, true)) {
                    $eliminados++;
                }
            }
        }

        $this->clear_demo_ids('avisos_municipales');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d avisos municipales de ejemplo', $eliminados),
        ];
    }

    // =========================================================
    // RED SOCIAL
    // =========================================================

    /**
     * Pobla datos demo de Red Social
     */
    private function populate_red_social() {
        global $wpdb;

        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_comentarios = $wpdb->prefix . 'flavor_social_comentarios';
        $tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
        $tabla_hashtags_posts = $wpdb->prefix . 'flavor_social_hashtags_posts';
        $tabla_historias = $wpdb->prefix . 'flavor_social_historias';

        // Verificar que las tablas existen - Si no, intentar crearlas
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            // Intentar crear las tablas con maybe_create_tables del módulo
            require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/class-red-social-module.php';
            if (class_exists('Flavor_Chat_Red_Social_Module')) {
                $modulo = new Flavor_Chat_Red_Social_Module();
                if (method_exists($modulo, 'maybe_create_tables')) {
                    $modulo->maybe_create_tables();
                }

                // Verificar de nuevo
                if (!Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
                    return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
                }
            } else {
                return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
            }
        }

        $usuarios_compartidos = $this->get_or_create_demo_users();
        $ids_insertados = [
            'perfiles' => [],
            'publicaciones' => [],
            'comentarios' => [],
            'reacciones' => [],
            'seguimientos' => [],
            'hashtags' => [],
            'historias' => [],
        ];

        // Datos de biografía para cada usuario
        $bios_demo = [
            'Amante de la naturaleza y la vida sostenible 🌱 | Huerto urbano en casa',
            'Fotógrafo aficionado 📷 | Compartiendo mi visión del barrio',
            'Cocinera por pasión 👩‍🍳 | Recetas caseras y tradicionales',
            'Ciclista urbano 🚴‍♂️ | Promoviendo movilidad sostenible',
            'Lectora empedernida 📚 | Amante del café y los libros',
            'Padre de familia 👨‍👩‍👧‍👦 | Activista comunitario',
            'Artista local 🎨 | Murales y arte callejero',
            'Maestra jubilada 👵 | Voluntaria en la biblioteca',
        ];

        $ubicaciones = [
            'Centro histórico',
            'Barrio San Juan',
            'Ensanche',
            'Plaza Mayor',
            'Casco Antiguo',
            'Zona universitaria',
            'Parque Central',
            'Barrio Norte',
        ];

        // 1. Crear perfiles para cada usuario compartido
        foreach ($usuarios_compartidos as $index => $usuario_id) {
            $user = get_userdata($usuario_id);
            if (!$user) continue;

            $perfil_id = $wpdb->insert(
                $tabla_perfiles,
                [
                    'usuario_id' => $usuario_id,
                    'bio' => $bios_demo[$index],
                    'ubicacion' => $ubicaciones[$index],
                    'sitio_web' => '',
                    'fecha_nacimiento' => date('Y-m-d', strtotime('-' . (25 + $index * 5) . ' years')),
                    'es_verificado' => $index < 2 ? 1 : 0,
                    'es_privado' => 0,
                    'total_publicaciones' => 0,
                    'total_seguidores' => 0,
                    'total_siguiendo' => 0,
                    'fecha_creacion' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s']
            );

            if ($perfil_id) {
                $ids_insertados['perfiles'][] = $wpdb->insert_id;
            }
        }

        // 2. Crear hashtags populares
        $hashtags_demo = ['sostenibilidad', 'comunidad', 'vecinos', 'ecologia', 'reciclar', 'compartir', 'apoyo', 'eventos', 'barrio', 'colaboracion'];

        foreach ($hashtags_demo as $tag) {
            $wpdb->insert(
                $tabla_hashtags,
                [
                    'hashtag' => $tag,
                    'total_usos' => 0,
                    'fecha_creacion' => current_time('mysql'),
                    'fecha_ultimo_uso' => current_time('mysql'),
                ],
                ['%s', '%d', '%s', '%s']
            );

            if ($wpdb->insert_id) {
                $ids_insertados['hashtags'][] = $wpdb->insert_id;
            }
        }

        // 3. Crear publicaciones con variedad de contenido
        $publicaciones_demo = [
            ['autor_idx' => 0, 'contenido' => '¡Hoy cosechamos los primeros tomates del huerto comunitario! 🍅 Nada como el sabor de lo cultivado con tus propias manos. #sostenibilidad #huerto', 'hashtags' => ['sostenibilidad']],
            ['autor_idx' => 1, 'contenido' => 'Atardecer increíble desde el mirador. Les comparto esta foto que tomé ayer 📸 #barrio #fotografia', 'hashtags' => ['barrio']],
            ['autor_idx' => 2, 'contenido' => 'Nueva receta en el blog: paella de verduras ecológicas. ¿Quién se anima a prepararla este fin de semana? 🥘 #cocina #comunidad', 'hashtags' => ['comunidad']],
            ['autor_idx' => 3, 'contenido' => 'Recordatorio: mañana salida en bici a las 8am desde la plaza. Ruta fácil de 15km. ¡Todos bienvenidos! 🚴‍♀️ #movilidad #vecinos', 'hashtags' => ['vecinos']],
            ['autor_idx' => 4, 'contenido' => 'Terminé de leer "El jardín de las mariposas". Recomendadísimo para los amantes de la naturaleza 📖 #lectura #recomendaciones', 'hashtags' => []],
            ['autor_idx' => 5, 'contenido' => 'Gracias a todos los que vinieron a la asamblea de ayer. Juntos conseguiremos mejorar nuestro barrio 💪 #comunidad #participacion', 'hashtags' => ['comunidad']],
            ['autor_idx' => 0, 'contenido' => 'Cambio plantones de tomate por semillas de albahaca. Alguien interesado? #trueque #compartir', 'hashtags' => ['compartir']],
            ['autor_idx' => 6, 'contenido' => 'Nuevo mural terminado en la calle Mayor! Pasaos a verlo 🎨 Representa la historia de nuestro barrio. #arte #barrio', 'hashtags' => ['barrio']],
            ['autor_idx' => 7, 'contenido' => 'La biblioteca tiene nuevos libros infantiles. Traed a los peques el sábado que haremos cuentacuentos 📚👶 #eventos #comunidad', 'hashtags' => ['eventos', 'comunidad']],
            ['autor_idx' => 2, 'contenido' => 'Clase de cocina gratuita este miércoles en el centro cívico. Aprenderemos a hacer pan de masa madre 🍞 #eventos #compartir', 'hashtags' => ['eventos', 'compartir']],
            ['autor_idx' => 1, 'contenido' => '¿Alguien más ha visto las estrellas esta noche? Cielo despejado perfecto para observar ✨ #barrio', 'hashtags' => ['barrio']],
            ['autor_idx' => 4, 'contenido' => 'Organizamos club de lectura mensual. Primera reunión el día 20. Interesados enviar mensaje! 📚 #lectura #comunidad', 'hashtags' => ['comunidad']],
            ['autor_idx' => 3, 'contenido' => 'Punto de reciclaje nuevo instalado en la plaza. Por fin podemos reciclar pilas y pequeños electrodomésticos ♻️ #ecologia #sostenibilidad', 'hashtags' => ['ecologia', 'sostenibilidad']],
            ['autor_idx' => 5, 'contenido' => 'Necesitamos voluntarios para la limpieza del parque el domingo. Quien pueda ayudar que me escriba 🧹 #apoyo #vecinos', 'hashtags' => ['apoyo', 'vecinos']],
            ['autor_idx' => 6, 'contenido' => 'Taller de graffiti para jóvenes, sábados de 10 a 12h. Materiales incluidos, plazas limitadas! 🎨 #arte #eventos', 'hashtags' => ['eventos']],
            ['autor_idx' => 0, 'contenido' => 'Los girasoles del huerto ya miden más de 2 metros! 🌻 La naturaleza es increíble. #sostenibilidad #huerto', 'hashtags' => ['sostenibilidad']],
            ['autor_idx' => 7, 'contenido' => 'Buscamos libros usados para donar a la biblioteca. Si tenéis alguno que no leáis, será bienvenido 📖 #compartir #colaboracion', 'hashtags' => ['compartir', 'colaboracion']],
        ];

        foreach ($publicaciones_demo as $pub_data) {
            $autor_id = $usuarios_compartidos[$pub_data['autor_idx']];
            $dias_atras = rand(1, 15);

            $pub_id = $wpdb->insert(
                $tabla_publicaciones,
                [
                    'autor_id' => $autor_id,
                    'contenido' => $pub_data['contenido'],
                    'tipo' => 'texto',
                    'visibilidad' => 'comunidad',
                    'estado' => 'publicado',
                    'me_gusta' => 0,
                    'comentarios' => 0,
                    'compartidos' => 0,
                    'vistas' => rand(10, 80),
                    'fecha_publicacion' => date('Y-m-d H:i:s', strtotime("-{$dias_atras} days")),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s']
            );

            if ($pub_id) {
                $publicacion_id = $wpdb->insert_id;
                $ids_insertados['publicaciones'][] = $publicacion_id;

                // Asociar hashtags a la publicación
                foreach ($pub_data['hashtags'] as $hashtag_nombre) {
                    $hashtag = $wpdb->get_row($wpdb->prepare(
                        "SELECT id FROM $tabla_hashtags WHERE hashtag = %s",
                        $hashtag_nombre
                    ));

                    if ($hashtag) {
                        $wpdb->insert(
                            $tabla_hashtags_posts,
                            [
                                'hashtag_id' => $hashtag->id,
                                'publicacion_id' => $publicacion_id,
                                'fecha_creacion' => current_time('mysql'),
                            ],
                            ['%d', '%d', '%s']
                        );

                        // Actualizar contador de usos del hashtag
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $tabla_hashtags SET total_usos = total_usos + 1, fecha_ultimo_uso = %s WHERE id = %d",
                            current_time('mysql'),
                            $hashtag->id
                        ));
                    }
                }
            }
        }

        // 4. Crear comentarios en algunas publicaciones
        $comentarios_demo = [
            'Me encanta esta iniciativa! 👏',
            'Cuenta conmigo para el próximo',
            'Qué bonito! Gracias por compartir',
            'Yo también quiero apuntarme',
            'Excelente idea, necesitamos más cosas así',
            'Podría ir el sábado, me confirmas horario?',
            'Quedó precioso! Enhorabuena',
            '¿Dónde puedo conseguir información?',
            'Comparto! Que más gente se entere',
            'Muy interesante, gracias!',
        ];

        // Comentar en las primeras 10 publicaciones
        if (!empty($ids_insertados['publicaciones'])) {
            foreach (array_slice($ids_insertados['publicaciones'], 0, 10) as $pub_id) {
                $num_comentarios = rand(1, 3);

                for ($i = 0; $i < $num_comentarios; $i++) {
                    $comentarista_id = $usuarios_compartidos[array_rand($usuarios_compartidos)];

                    $wpdb->insert(
                        $tabla_comentarios,
                        [
                            'publicacion_id' => $pub_id,
                            'autor_id' => $comentarista_id,
                            'contenido' => $comentarios_demo[array_rand($comentarios_demo)],
                            'me_gusta' => rand(0, 5),
                            'estado' => 'publicado',
                            'fecha_creacion' => current_time('mysql'),
                        ],
                        ['%d', '%d', '%s', '%d', '%s', '%s']
                    );

                    if ($wpdb->insert_id) {
                        $ids_insertados['comentarios'][] = $wpdb->insert_id;

                        // Actualizar contador de comentarios en la publicación
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $tabla_publicaciones SET comentarios = comentarios + 1 WHERE id = %d",
                            $pub_id
                        ));
                    }
                }
            }
        }

        // 5. Crear reacciones (likes) en publicaciones
        if (!empty($ids_insertados['publicaciones'])) {
            foreach ($ids_insertados['publicaciones'] as $pub_id) {
                $num_likes = rand(2, 6);
                $usuarios_reaccionan = array_rand(array_flip($usuarios_compartidos), min($num_likes, count($usuarios_compartidos)));

                if (!is_array($usuarios_reaccionan)) {
                    $usuarios_reaccionan = [$usuarios_reaccionan];
                }

                foreach ($usuarios_reaccionan as $usuario_id) {
                    $tipos_reaccion = ['me_gusta', 'me_gusta', 'me_gusta', 'me_encanta', 'me_divierte'];

                    $wpdb->insert(
                        $tabla_reacciones,
                        [
                            'publicacion_id' => $pub_id,
                            'usuario_id' => $usuario_id,
                            'tipo' => $tipos_reaccion[array_rand($tipos_reaccion)],
                            'fecha_creacion' => current_time('mysql'),
                        ],
                        ['%d', '%d', '%s', '%s']
                    );

                    if ($wpdb->insert_id) {
                        $ids_insertados['reacciones'][] = $wpdb->insert_id;

                        // Actualizar contador de me_gusta
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $tabla_publicaciones SET me_gusta = me_gusta + 1 WHERE id = %d",
                            $pub_id
                        ));
                    }
                }
            }
        }

        // 6. Crear red de seguimientos (cada usuario sigue a 3-5 otros)
        foreach ($usuarios_compartidos as $seguidor_id) {
            $num_seguir = rand(3, 5);
            $usuarios_a_seguir = array_diff($usuarios_compartidos, [$seguidor_id]);
            $usuarios_seleccionados = array_rand(array_flip($usuarios_a_seguir), min($num_seguir, count($usuarios_a_seguir)));

            if (!is_array($usuarios_seleccionados)) {
                $usuarios_seleccionados = [$usuarios_seleccionados];
            }

            foreach ($usuarios_seleccionados as $seguido_id) {
                $wpdb->insert(
                    $tabla_seguimientos,
                    [
                        'seguidor_id' => $seguidor_id,
                        'seguido_id' => $seguido_id,
                        'notificaciones_activas' => 1,
                        'fecha_seguimiento' => current_time('mysql'),
                    ],
                    ['%d', '%d', '%d', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['seguimientos'][] = $wpdb->insert_id;

                    // Actualizar contadores en perfiles
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $tabla_perfiles SET total_siguiendo = total_siguiendo + 1 WHERE usuario_id = %d",
                        $seguidor_id
                    ));
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $tabla_perfiles SET total_seguidores = total_seguidores + 1 WHERE usuario_id = %d",
                        $seguido_id
                    ));
                }
            }
        }

        // 7. Crear algunas historias activas (expiran en 24h)
        $historias_demo = [
            ['autor_idx' => 0, 'texto' => '☀️ Buenos días! Hoy plantamos lechugas 🥬', 'color' => '#10b981'],
            ['autor_idx' => 1, 'texto' => '📸 Nueva foto del día en mi perfil', 'color' => '#3b82f6'],
            ['autor_idx' => 3, 'texto' => '🚴‍♂️ Ruta completada! 20km', 'color' => '#f59e0b'],
        ];

        foreach ($historias_demo as $historia) {
            $autor_id = $usuarios_compartidos[$historia['autor_idx']];

            $wpdb->insert(
                $tabla_historias,
                [
                    'autor_id' => $autor_id,
                    'tipo' => 'texto',
                    'texto' => $historia['texto'],
                    'color_fondo' => $historia['color'],
                    'vistas' => rand(5, 25),
                    'fecha_creacion' => current_time('mysql'),
                    'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
            );

            if ($wpdb->insert_id) {
                $ids_insertados['historias'][] = $wpdb->insert_id;
            }
        }

        // Actualizar contador de publicaciones en perfiles
        $wpdb->query("
            UPDATE $tabla_perfiles p
            SET total_publicaciones = (
                SELECT COUNT(*)
                FROM $tabla_publicaciones pub
                WHERE pub.autor_id = p.usuario_id AND pub.estado = 'publicado'
            )
        ");

        $this->mark_as_demo('red_social', $ids_insertados);

        $total = array_sum(array_map('count', $ids_insertados));

        return [
            'success' => true,
            'count' => $total,
            'message' => sprintf(
                'Red social: %d perfiles, %d publicaciones, %d comentarios, %d reacciones, %d seguimientos, %d hashtags, %d historias',
                count($ids_insertados['perfiles']),
                count($ids_insertados['publicaciones']),
                count($ids_insertados['comentarios']),
                count($ids_insertados['reacciones']),
                count($ids_insertados['seguimientos']),
                count($ids_insertados['hashtags']),
                count($ids_insertados['historias'])
            ),
        ];
    }

    /**
     * Limpia datos demo de Red Social
     */
    private function clear_red_social() {
        global $wpdb;

        $tabla_perfiles = $wpdb->prefix . 'flavor_red_social_perfiles';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_red_social_publicaciones';
        $tabla_comentarios = $wpdb->prefix . 'flavor_red_social_comentarios';
        $tabla_reacciones = $wpdb->prefix . 'flavor_red_social_reacciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_red_social_seguimientos';
        $tabla_hashtags = $wpdb->prefix . 'flavor_red_social_hashtags';
        $tabla_hashtags_posts = $wpdb->prefix . 'flavor_red_social_hashtags_posts';
        $tabla_historias = $wpdb->prefix . 'flavor_red_social_historias';
        $tabla_notificaciones = $wpdb->prefix . 'flavor_red_social_notificaciones';
        $tabla_guardados = $wpdb->prefix . 'flavor_red_social_guardados';

        $ids = $this->get_demo_ids('red_social');
        $eliminados = 0;

        if (!empty($ids)) {
            // Eliminar en orden inverso por dependencias

            // 1. Hashtags_posts
            if (!empty($ids['hashtags_posts'])) {
                $placeholders = implode(',', array_fill(0, count($ids['hashtags_posts']), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_hashtags_posts WHERE id IN ($placeholders)",
                    ...$ids['hashtags_posts']
                ));
            }

            // 2. Historias
            if (!empty($ids['historias'])) {
                $placeholders = implode(',', array_fill(0, count($ids['historias']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_historias WHERE id IN ($placeholders)",
                    ...$ids['historias']
                ));
            }

            // 3. Guardados relacionados con publicaciones demo
            if (!empty($ids['publicaciones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['publicaciones']), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_guardados WHERE publicacion_id IN ($placeholders)",
                    ...$ids['publicaciones']
                ));
            }

            // 4. Notificaciones
            if (!empty($ids['publicaciones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['publicaciones']), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_notificaciones WHERE referencia_id IN ($placeholders) AND referencia_tipo = 'publicacion'",
                    ...$ids['publicaciones']
                ));
            }

            // 5. Reacciones
            if (!empty($ids['reacciones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['reacciones']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_reacciones WHERE id IN ($placeholders)",
                    ...$ids['reacciones']
                ));
            }

            // 6. Comentarios
            if (!empty($ids['comentarios'])) {
                $placeholders = implode(',', array_fill(0, count($ids['comentarios']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_comentarios WHERE id IN ($placeholders)",
                    ...$ids['comentarios']
                ));
            }

            // 7. Seguimientos
            if (!empty($ids['seguimientos'])) {
                $placeholders = implode(',', array_fill(0, count($ids['seguimientos']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_seguimientos WHERE id IN ($placeholders)",
                    ...$ids['seguimientos']
                ));
            }

            // 8. Publicaciones
            if (!empty($ids['publicaciones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['publicaciones']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_publicaciones WHERE id IN ($placeholders)",
                    ...$ids['publicaciones']
                ));
            }

            // 9. Hashtags
            if (!empty($ids['hashtags'])) {
                $placeholders = implode(',', array_fill(0, count($ids['hashtags']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_hashtags WHERE id IN ($placeholders)",
                    ...$ids['hashtags']
                ));
            }

            // 10. Perfiles (NO eliminamos usuarios, solo sus perfiles en red social)
            if (!empty($ids['perfiles'])) {
                $placeholders = implode(',', array_fill(0, count($ids['perfiles']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_perfiles WHERE id IN ($placeholders)",
                    ...$ids['perfiles']
                ));
            }
        }

        $this->clear_demo_ids('red_social');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d registros de red social', $eliminados),
        ];
    }

    // =========================================================
    // NETWORK (RED DE COMUNIDADES)
    // =========================================================

    /**
     * Pobla datos demo de Red de Comunidades
     */
    private function populate_network() {
        global $wpdb;

        $prefix = $wpdb->prefix . 'flavor_network_';
        $tabla_nodos = $prefix . 'nodes';
        $tabla_conexiones = $prefix . 'connections';
        $tabla_mensajes = $prefix . 'messages';
        $tabla_favoritos = $prefix . 'favorites';
        $tabla_tablon = $prefix . 'board';
        $tabla_contenido = $prefix . 'shared_content';
        $tabla_eventos = $prefix . 'events';
        $tabla_colaboraciones = $prefix . 'collaborations';

        // Verificar que las tablas existen
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_nodos)) {
            return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
        }

        $admin_id = $this->get_demo_user_id();
        $ids_insertados = [
            'nodos' => [],
            'conexiones' => [],
            'mensajes' => [],
            'favoritos' => [],
            'tablon' => [],
            'contenido' => [],
            'eventos' => [],
            'colaboraciones' => [],
        ];

        // 1. Crear nodos demo de diferentes tipos
        $nodos_demo = [
            [
                'nombre' => 'Ecoaldea Valle Verde',
                'slug' => 'ecoaldea-valle-verde',
                'descripcion' => 'Comunidad de vida sostenible en el medio rural. Compartimos recursos, conocimientos y un modelo de vida basado en la permacultura y el respeto mutuo.',
                'descripcion_corta' => 'Vida sostenible en comunidad',
                'tipo_entidad' => 'ecoaldea',
                'sector' => 'vivienda_colectiva',
                'nivel_consciencia' => 'integrado',
                'ciudad' => 'Huesca',
                'provincia' => 'Huesca',
                'telefono' => '974123456',
                'email' => 'contacto@valleverde.coop',
                'miembros' => 45,
            ],
            [
                'nombre' => 'Cooperativa Integral Barcelona',
                'slug' => 'coop-integral-bcn',
                'descripcion' => 'Red de cooperativas, colectivos y personas comprometidas con la transformación social desde la autogestión y el apoyo mutuo.',
                'descripcion_corta' => 'Transformación social desde la autogestión',
                'tipo_entidad' => 'cooperativa',
                'sector' => 'multiprop​osito',
                'nivel_consciencia' => 'consciente',
                'ciudad' => 'Barcelona',
                'provincia' => 'Barcelona',
                'telefono' => '934567890',
                'email' => 'info@cooperativaintegral.cat',
                'miembros' => 320,
            ],
            [
                'nombre' => 'Grupo de Consumo Agroecológico Madrid',
                'slug' => 'gc-agroeco-madrid',
                'descripcion' => 'Consumo responsable y local. Conectamos productores ecológicos con familias comprometidas con la alimentación sostenible.',
                'descripcion_corta' => 'Consumo agroecológico local',
                'tipo_entidad' => 'grupo_consumo',
                'sector' => 'alimentacion',
                'nivel_consciencia' => 'basico',
                'ciudad' => 'Madrid',
                'provincia' => 'Madrid',
                'telefono' => '915678901',
                'email' => 'hola@gcmadrid.org',
                'miembros' => 67,
            ],
            [
                'nombre' => 'La Colmena Que Dice Sí Pamplona',
                'slug' => 'colmena-pamplona',
                'descripcion' => 'Mercado local de productos frescos y artesanales. Venta directa de productores a consumidores comprometidos.',
                'descripcion_corta' => 'Mercado local de productos frescos',
                'tipo_entidad' => 'mercado_local',
                'sector' => 'alimentacion',
                'nivel_consciencia' => 'basico',
                'ciudad' => 'Pamplona',
                'provincia' => 'Navarra',
                'telefono' => '948234567',
                'email' => 'pamplona@colmenaquedicesí.es',
                'miembros' => 89,
            ],
            [
                'nombre' => 'Banco de Tiempo Zaragoza',
                'slug' => 'bdt-zaragoza',
                'descripcion' => 'Red de intercambio de servicios sin dinero. Cada hora de trabajo vale lo mismo independientemente del servicio prestado.',
                'descripcion_corta' => 'Intercambio de tiempo y servicios',
                'tipo_entidad' => 'banco_tiempo',
                'sector' => 'servicios',
                'nivel_consciencia' => 'consciente',
                'ciudad' => 'Zaragoza',
                'provincia' => 'Zaragoza',
                'telefono' => '976345678',
                'email' => 'info@bdtzaragoza.org',
                'miembros' => 156,
            ],
            [
                'nombre' => 'Cohousing Senior Bilbao',
                'slug' => 'cohousing-bilbao',
                'descripcion' => 'Vivienda colaborativa para mayores de 50 años. Compartimos espacios comunes y nos apoyamos mutuamente manteniendo nuestra independencia.',
                'descripcion_corta' => 'Vivienda colaborativa senior',
                'tipo_entidad' => 'cohousing',
                'sector' => 'vivienda_colectiva',
                'nivel_consciencia' => 'integrado',
                'ciudad' => 'Bilbao',
                'provincia' => 'Vizcaya',
                'telefono' => '944456789',
                'email' => 'contacto@cohousingbilbao.eus',
                'miembros' => 34,
            ],
            [
                'nombre' => 'Huertos Urbanos Comunitarios Sevilla',
                'slug' => 'huertos-sevilla',
                'descripcion' => 'Red de huertos urbanos ecológicos. Cultivamos alimentos saludables, biodiversidad y comunidad en la ciudad.',
                'descripcion_corta' => 'Cultivo ecológico urbano',
                'tipo_entidad' => 'huertos_urbanos',
                'sector' => 'agroecologia',
                'nivel_consciencia' => 'basico',
                'ciudad' => 'Sevilla',
                'provincia' => 'Sevilla',
                'telefono' => '954567890',
                'email' => 'huertos@sevilla.org',
                'miembros' => 78,
            ],
            [
                'nombre' => 'Espacio de Trabajo Cooperativo Valencia',
                'slug' => 'cowork-valencia',
                'descripcion' => 'Coworking autogestionado por sus miembros. Fomentamos la economía social y colaborativa entre profesionales y emprendedores.',
                'descripcion_corta' => 'Coworking autogestionado',
                'tipo_entidad' => 'coworking',
                'sector' => 'espacio_trabajo',
                'nivel_consciencia' => 'consciente',
                'ciudad' => 'Valencia',
                'provincia' => 'Valencia',
                'telefono' => '963678901',
                'email' => 'info@coworkvalencia.coop',
                'miembros' => 52,
            ],
            [
                'nombre' => 'Red de Apoyo Mutuo Comunitario Granada',
                'slug' => 'ram-granada',
                'descripcion' => 'Cuidamos entre todxs. Red de apoyo para situaciones de vulnerabilidad: cuidados, vivienda, alimentación y acompañamiento emocional.',
                'descripcion_corta' => 'Cuidados y apoyo mutuo',
                'tipo_entidad' => 'red_apoyo',
                'sector' => 'servicios',
                'nivel_consciencia' => 'consciente',
                'ciudad' => 'Granada',
                'provincia' => 'Granada',
                'telefono' => '958789012',
                'email' => 'apoyo@ramgranada.org',
                'miembros' => 124,
            ],
            [
                'nombre' => 'Energía Cooperativa Renovable Aragón',
                'slug' => 'enercoop-aragon',
                'descripcion' => 'Cooperativa de generación y consumo de energía 100% renovable. Soberanía energética al servicio de las personas y el planeta.',
                'descripcion_corta' => 'Energía renovable cooperativa',
                'tipo_entidad' => 'cooperativa_energia',
                'sector' => 'energia',
                'nivel_consciencia' => 'integrado',
                'ciudad' => 'Zaragoza',
                'provincia' => 'Zaragoza',
                'telefono' => '976890123',
                'email' => 'info@enercooparagón.coop',
                'miembros' => 267,
            ],
        ];

        foreach ($nodos_demo as $nodo_data) {
            $wpdb->insert(
                $tabla_nodos,
                [
                    'site_url' => 'https://' . $nodo_data['slug'] . '.example.com',
                    'nombre' => $nodo_data['nombre'],
                    'slug' => $nodo_data['slug'],
                    'descripcion' => $nodo_data['descripcion'],
                    'descripcion_corta' => $nodo_data['descripcion_corta'],
                    'tipo_entidad' => $nodo_data['tipo_entidad'],
                    'sector' => $nodo_data['sector'],
                    'nivel_consciencia' => $nodo_data['nivel_consciencia'],
                    'ciudad' => $nodo_data['ciudad'],
                    'provincia' => $nodo_data['provincia'],
                    'pais' => 'ES',
                    'telefono' => $nodo_data['telefono'],
                    'email' => $nodo_data['email'],
                    'verificado' => 1,
                    'estado' => 'activo',
                    'miembros_count' => $nodo_data['miembros'],
                    'fecha_registro' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 365) . ' days')),
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s']
            );

            if ($wpdb->insert_id) {
                $ids_insertados['nodos'][] = $wpdb->insert_id;
            }
        }

        // 2. Crear nodo local (este sitio)
        $wpdb->insert(
            $tabla_nodos,
            [
                'site_url' => home_url(),
                'nombre' => get_bloginfo('name') . ' (Nodo Local)',
                'slug' => 'nodo-local-' . sanitize_title(get_bloginfo('name')),
                'descripcion' => 'Este es nuestro nodo local. Conectamos con otras comunidades para compartir recursos, conocimientos y experiencias.',
                'descripcion_corta' => 'Nuestro nodo local',
                'tipo_entidad' => 'comunidad',
                'sector' => 'multiproposito',
                'nivel_consciencia' => 'basico',
                'ciudad' => 'Pamplona',
                'provincia' => 'Navarra',
                'pais' => 'ES',
                'email' => get_option('admin_email'),
                'es_nodo_local' => 1,
                'verificado' => 1,
                'estado' => 'activo',
                'miembros_count' => rand(20, 50),
                'fecha_registro' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s']
        );

        $nodo_local_id = $wpdb->insert_id;
        if ($nodo_local_id) {
            $ids_insertados['nodos'][] = $nodo_local_id;
        }

        // 3. Crear conexiones entre nodos (el nodo local conectado con varios)
        if (!empty($ids_insertados['nodos']) && $nodo_local_id) {
            // Conectar nodo local con 5 nodos aleatorios
            $nodos_externos = array_diff($ids_insertados['nodos'], [$nodo_local_id]);
            $nodos_a_conectar = array_rand(array_flip($nodos_externos), min(5, count($nodos_externos)));

            if (!is_array($nodos_a_conectar)) {
                $nodos_a_conectar = [$nodos_a_conectar];
            }

            foreach ($nodos_a_conectar as $nodo_destino_id) {
                $wpdb->insert(
                    $tabla_conexiones,
                    [
                        'nodo_origen_id' => $nodo_local_id,
                        'nodo_destino_id' => $nodo_destino_id,
                        'tipo_conexion' => 'visible',
                        'nivel' => 'visible',
                        'estado' => 'aceptada',
                        'mensaje_solicitud' => '¡Hola! Nos gustaría conectar con vuestra comunidad para compartir experiencias y recursos.',
                        'solicitado_por' => $admin_id,
                        'aprobado_por' => $admin_id,
                        'fecha_solicitud' => date('Y-m-d H:i:s', strtotime('-' . rand(10, 60) . ' days')),
                        'fecha_aprobacion' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 50) . ' days')),
                    ],
                    ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['conexiones'][] = $wpdb->insert_id;
                }
            }

            // Crear algunas conexiones entre otros nodos
            for ($i = 0; $i < 5; $i++) {
                $nodos_disponibles = array_diff($ids_insertados['nodos'], [$nodo_local_id]);
                if (count($nodos_disponibles) >= 2) {
                    $dos_nodos = array_rand(array_flip($nodos_disponibles), 2);

                    $wpdb->insert(
                        $tabla_conexiones,
                        [
                            'nodo_origen_id' => $dos_nodos[0],
                            'nodo_destino_id' => $dos_nodos[1],
                            'tipo_conexion' => 'visible',
                            'nivel' => 'visible',
                            'estado' => 'aceptada',
                            'solicitado_por' => $admin_id,
                            'aprobado_por' => $admin_id,
                            'fecha_solicitud' => date('Y-m-d H:i:s', strtotime('-' . rand(20, 100) . ' days')),
                            'fecha_aprobacion' => date('Y-m-d H:i:s', strtotime('-' . rand(10, 90) . ' days')),
                        ],
                        ['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
                    );

                    if ($wpdb->insert_id) {
                        $ids_insertados['conexiones'][] = $wpdb->insert_id;
                    }
                }
            }
        }

        // 4. Crear mensajes entre nodos conectados
        if (!empty($ids_insertados['conexiones']) && $nodo_local_id) {
            $mensajes_demo = [
                [
                    'de' => $nodo_local_id,
                    'asunto' => 'Propuesta de colaboración',
                    'contenido' => 'Hola! Estamos organizando unas jornadas sobre consumo responsable. ¿Os gustaría participar compartiendo vuestra experiencia?',
                ],
                [
                    'a' => $nodo_local_id,
                    'asunto' => 'Re: Propuesta de colaboración',
                    'contenido' => 'Nos encantaría! Podríamos hacer una presentación sobre nuestro modelo de grupos de consumo. ¿Qué fechas barajáis?',
                ],
                [
                    'de' => $nodo_local_id,
                    'asunto' => 'Consulta sobre gestión energética',
                    'contenido' => 'Estamos valorando instalar paneles solares. ¿Podríais compartir vuestra experiencia con la cooperativa energética?',
                ],
            ];

            foreach ($mensajes_demo as $mensaje_data) {
                if (isset($mensaje_data['de'])) {
                    $de_nodo = $mensaje_data['de'];
                    $a_nodo = $nodos_a_conectar[array_rand($nodos_a_conectar)];
                } else {
                    $de_nodo = $nodos_a_conectar[array_rand($nodos_a_conectar)];
                    $a_nodo = $nodo_local_id;
                }

                $wpdb->insert(
                    $tabla_mensajes,
                    [
                        'de_nodo_id' => $de_nodo,
                        'a_nodo_id' => $a_nodo,
                        'tipo' => 'mensaje',
                        'asunto' => $mensaje_data['asunto'],
                        'contenido' => $mensaje_data['contenido'],
                        'leido' => rand(0, 1),
                        'fecha_envio' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    ],
                    ['%d', '%d', '%s', '%s', '%s', '%d', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['mensajes'][] = $wpdb->insert_id;
                }
            }
        }

        // 5. Crear anuncios en el tablón de red
        if (!empty($ids_insertados['nodos'])) {
            $anuncios_tablon = [
                ['titulo' => 'Jornadas de Agroecología', 'tipo' => 'evento', 'contenido' => 'Los días 15 y 16 de marzo celebramos las jornadas anuales de agroecología. Talleres, ponencias y mercado de productores. ¡Bienvenidos!', 'dias' => 15],
                ['titulo' => 'Busco vivienda en proyecto comunitario', 'tipo' => 'demanda', 'contenido' => 'Familia de 4 personas busca vivienda en proyecto de cohousing o ecoaldea en Aragón. Interés en permacultura y educación alternativa.', 'dias' => -5],
                ['titulo' => 'Ofrecemos formación en energías renovables', 'tipo' => 'oferta', 'contenido' => 'Nuestra cooperativa ofrece talleres sobre instalación de energía solar fotovoltaica. Subvencionado para socios de redes colaborativas.', 'dias' => -2],
                ['titulo' => 'Compra colectiva de semillas ecológicas', 'tipo' => 'colaboracion', 'contenido' => 'Organizamos compra conjunta de semillas tradicionales y ecológicas. Precios reducidos. Pedidos hasta el 20 de febrero.', 'dias' => 10],
            ];

            foreach ($anuncios_tablon as $anuncio) {
                $nodo_aleatorio = $ids_insertados['nodos'][array_rand($ids_insertados['nodos'])];

                $wpdb->insert(
                    $tabla_tablon,
                    [
                        'nodo_id' => $nodo_aleatorio,
                        'tipo' => $anuncio['tipo'],
                        'titulo' => $anuncio['titulo'],
                        'contenido' => $anuncio['contenido'],
                        'ambito' => 'red',
                        'prioridad' => rand(0, 1) ? 'normal' : 'alta',
                        'activo' => 1,
                        'vistas' => rand(20, 150),
                        'fecha_publicacion' => date('Y-m-d H:i:s', strtotime($anuncio['dias'] . ' days')),
                        'fecha_fin' => date('Y-m-d H:i:s', strtotime('+' . (abs($anuncio['dias']) + 30) . ' days')),
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['tablon'][] = $wpdb->insert_id;
                }
            }
        }

        // 6. Crear contenido compartido
        if (!empty($ids_insertados['nodos'])) {
            $contenido_demo = [
                ['tipo' => 'producto', 'titulo' => 'Cestas de verdura ecológica', 'descripcion' => 'Cestas semanales de verdura de temporada certificada ecológica. 8-10 variedades según disponibilidad.', 'precio' => 15.00],
                ['tipo' => 'servicio', 'titulo' => 'Taller de reparación de bicicletas', 'descripcion' => 'Todos los sábados de 10 a 14h. Aprende a reparar tu bici con herramientas compartidas. Donativo voluntario.', 'precio' => 0.00],
                ['tipo' => 'espacio', 'titulo' => 'Sala multiusos disponible', 'descripcion' => 'Espacio de 80m² con cocina, baño y proyector. Ideal para talleres, reuniones o eventos. 50€/día.', 'precio' => 50.00],
                ['tipo' => 'recurso', 'titulo' => 'Biblioteca de herramientas', 'descripcion' => 'Préstamo gratuito de herramientas para socios: taladros, sierras, escaleras, carretillas, etc.', 'precio' => 0.00],
            ];

            foreach ($contenido_demo as $contenido) {
                $nodo_aleatorio = $ids_insertados['nodos'][array_rand($ids_insertados['nodos'])];

                $wpdb->insert(
                    $tabla_contenido,
                    [
                        'nodo_id' => $nodo_aleatorio,
                        'tipo_contenido' => $contenido['tipo'],
                        'titulo' => $contenido['titulo'],
                        'descripcion' => $contenido['descripcion'],
                        'precio' => $contenido['precio'],
                        'moneda' => 'EUR',
                        'disponibilidad' => 'disponible',
                        'visible_red' => 1,
                        'estado' => 'activo',
                        'vistas' => rand(10, 80),
                        'fecha_publicacion' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 60) . ' days')),
                    ],
                    ['%d', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s', '%d', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['contenido'][] = $wpdb->insert_id;
                }
            }
        }

        // 7. Crear eventos de red
        if (!empty($ids_insertados['nodos'])) {
            $eventos_demo = [
                ['titulo' => 'Encuentro de Economía Solidaria', 'tipo' => 'presencial', 'dias' => 20, 'plazas' => 100],
                ['titulo' => 'Webinar: Gestión de Comunidades', 'tipo' => 'online', 'dias' => 8, 'plazas' => 50],
                ['titulo' => 'Mercado de Intercambio', 'tipo' => 'presencial', 'dias' => 12, 'plazas' => 0],
            ];

            foreach ($eventos_demo as $evento) {
                $nodo_aleatorio = $ids_insertados['nodos'][array_rand($ids_insertados['nodos'])];

                $wpdb->insert(
                    $tabla_eventos,
                    [
                        'nodo_id' => $nodo_aleatorio,
                        'titulo' => $evento['titulo'],
                        'descripcion' => 'Evento abierto a toda la red. Espacio de encuentro, aprendizaje e intercambio entre comunidades.',
                        'tipo_evento' => $evento['tipo'],
                        'ubicacion' => $evento['tipo'] === 'presencial' ? 'Por confirmar' : '',
                        'url_online' => $evento['tipo'] === 'online' ? 'https://meet.example.com/evento' : '',
                        'fecha_inicio' => date('Y-m-d 10:00:00', strtotime('+' . $evento['dias'] . ' days')),
                        'fecha_fin' => date('Y-m-d 18:00:00', strtotime('+' . $evento['dias'] . ' days')),
                        'plazas' => $evento['plazas'],
                        'inscritos' => $evento['plazas'] > 0 ? rand(5, min(25, $evento['plazas'])) : 0,
                        'precio' => 0.00,
                        'visible_red' => 1,
                        'estado' => 'activo',
                        'fecha_publicacion' => current_time('mysql'),
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%s', '%s']
                );

                if ($wpdb->insert_id) {
                    $ids_insertados['eventos'][] = $wpdb->insert_id;
                }
            }
        }

        // 8. Crear colaboraciones
        if (!empty($ids_insertados['nodos']) && $nodo_local_id) {
            $wpdb->insert(
                $tabla_colaboraciones,
                [
                    'nodo_creador_id' => $nodo_local_id,
                    'tipo' => 'compra_colectiva',
                    'titulo' => 'Compra colectiva de paneles solares',
                    'descripcion' => 'Agrupamos pedidos para conseguir mejor precio en instalación de placas fotovoltaicas. Ya somos 12 familias interesadas.',
                    'objetivo' => 'Conseguir 20 instalaciones para reducir costes un 30%',
                    'requisitos' => 'Disponer de tejado o terreno adecuado',
                    'beneficios' => 'Ahorro económico, energía limpia, soberanía energética',
                    'estado' => 'abierta',
                    'max_participantes' => 20,
                    'fecha_limite' => date('Y-m-d', strtotime('+45 days')),
                    'ambito' => 'red',
                    'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-15 days')),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
            );

            if ($wpdb->insert_id) {
                $ids_insertados['colaboraciones'][] = $wpdb->insert_id;
            }
        }

        $this->mark_as_demo('network', $ids_insertados);

        $total = array_sum(array_map('count', $ids_insertados));

        return [
            'success' => true,
            'count' => $total,
            'message' => sprintf(
                'Network: %d nodos, %d conexiones, %d mensajes, %d anuncios, %d contenidos, %d eventos, %d colaboraciones',
                count($ids_insertados['nodos']),
                count($ids_insertados['conexiones']),
                count($ids_insertados['mensajes']),
                count($ids_insertados['tablon']),
                count($ids_insertados['contenido']),
                count($ids_insertados['eventos']),
                count($ids_insertados['colaboraciones'])
            ),
        ];
    }

    /**
     * Limpia datos demo de Network
     */
    private function clear_network() {
        global $wpdb;

        $prefix = $wpdb->prefix . 'flavor_network_';
        $tabla_nodos = $prefix . 'nodes';
        $tabla_conexiones = $prefix . 'connections';
        $tabla_mensajes = $prefix . 'messages';
        $tabla_favoritos = $prefix . 'favorites';
        $tabla_recomendaciones = $prefix . 'recommendations';
        $tabla_tablon = $prefix . 'board';
        $tabla_contenido = $prefix . 'shared_content';
        $tabla_eventos = $prefix . 'events';
        $tabla_colaboraciones = $prefix . 'collaborations';

        $ids = $this->get_demo_ids('network');
        $eliminados = 0;

        if (!empty($ids)) {
            // Eliminar en orden por dependencias

            // 1. Colaboraciones
            if (!empty($ids['colaboraciones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['colaboraciones']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_colaboraciones WHERE id IN ($placeholders)",
                    ...$ids['colaboraciones']
                ));
            }

            // 2. Eventos
            if (!empty($ids['eventos'])) {
                $placeholders = implode(',', array_fill(0, count($ids['eventos']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_eventos WHERE id IN ($placeholders)",
                    ...$ids['eventos']
                ));
            }

            // 3. Contenido compartido
            if (!empty($ids['contenido'])) {
                $placeholders = implode(',', array_fill(0, count($ids['contenido']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_contenido WHERE id IN ($placeholders)",
                    ...$ids['contenido']
                ));
            }

            // 4. Tablón
            if (!empty($ids['tablon'])) {
                $placeholders = implode(',', array_fill(0, count($ids['tablon']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_tablon WHERE id IN ($placeholders)",
                    ...$ids['tablon']
                ));
            }

            // 5. Favoritos relacionados con nodos demo
            if (!empty($ids['nodos'])) {
                $placeholders = implode(',', array_fill(0, count($ids['nodos']), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_favoritos WHERE nodo_id IN ($placeholders) OR nodo_favorito_id IN ($placeholders)",
                    ...array_merge($ids['nodos'], $ids['nodos'])
                ));
            }

            // 6. Recomendaciones
            if (!empty($ids['nodos'])) {
                $placeholders = implode(',', array_fill(0, count($ids['nodos']), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_recomendaciones WHERE de_nodo_id IN ($placeholders) OR a_nodo_id IN ($placeholders) OR nodo_recomendado_id IN ($placeholders)",
                    ...array_merge($ids['nodos'], $ids['nodos'], $ids['nodos'])
                ));
            }

            // 7. Mensajes
            if (!empty($ids['mensajes'])) {
                $placeholders = implode(',', array_fill(0, count($ids['mensajes']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_mensajes WHERE id IN ($placeholders)",
                    ...$ids['mensajes']
                ));
            }

            // 8. Conexiones
            if (!empty($ids['conexiones'])) {
                $placeholders = implode(',', array_fill(0, count($ids['conexiones']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_conexiones WHERE id IN ($placeholders)",
                    ...$ids['conexiones']
                ));
            }

            // 9. Nodos (excepto el nodo local)
            if (!empty($ids['nodos'])) {
                $placeholders = implode(',', array_fill(0, count($ids['nodos']), '%d'));
                $eliminados += $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla_nodos WHERE id IN ($placeholders) AND es_nodo_local = 0",
                    ...$ids['nodos']
                ));
            }
        }

        $this->clear_demo_ids('network');

        return [
            'success' => true,
            'count' => $eliminados,
            'message' => sprintf('Se eliminaron %d registros de network', $eliminados),
        ];
    }

    // =========================================================
    // PÁGINAS DE DEMOSTRACIÓN (LANDINGS)
    // =========================================================

    /**
     * Maneja la creación de páginas demo
     */
    public function handle_create_demo_pages() {
        check_admin_referer('flavor_demo_pages_action');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $scope = sanitize_text_field($_POST['demo_scope'] ?? 'all');
        $resultado = $this->create_demo_pages($scope === 'active');

        $mensaje = $resultado['success'] ? 'demo_pages_created' : 'demo_pages_error';

        wp_safe_redirect(add_query_arg(
            [
                'page' => 'flavor-app-composer',
                'mensaje' => $mensaje,
                'count' => $resultado['count'] ?? 0,
                'demo_scope' => $scope
            ],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Maneja la eliminación de páginas demo
     */
    public function handle_delete_demo_pages() {
        check_admin_referer('flavor_demo_pages_action');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $resultado = $this->delete_demo_pages();

        $mensaje = $resultado['success'] ? 'demo_pages_deleted' : 'demo_pages_delete_error';

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => $mensaje, 'count' => $resultado['count'] ?? 0],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Obtiene la definición de páginas a crear
     *
     * @return array
     */
    private function get_demo_pages_definition() {
        return [
            'grupos-consumo' => [
                'title' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'slug' => 'grupos-consumo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="grupos-consumo"]',
                'icon' => 'dashicons-carrot',
                'color' => '#84cc16',
            ],
            'banco-tiempo' => [
                'title' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'slug' => 'banco-tiempo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="banco-tiempo"]',
                'icon' => 'dashicons-clock',
                'color' => '#8b5cf6',
            ],
            'ayuntamiento' => [
                'title' => __('Ayuntamiento', 'flavor-chat-ia'),
                'slug' => 'ayuntamiento',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="ayuntamiento"]',
                'icon' => 'dashicons-building',
                'color' => '#1d4ed8',
            ],
            'comunidades' => [
                'title' => __('Comunidades', 'flavor-chat-ia'),
                'slug' => 'comunidades',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="comunidades"]',
                'icon' => 'dashicons-groups',
                'color' => '#f43f5e',
            ],
            'espacios-comunes' => [
                'title' => __('Espacios Comunes', 'flavor-chat-ia'),
                'slug' => 'espacios-comunes',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="espacios-comunes"]',
                'icon' => 'dashicons-admin-multisite',
                'color' => '#06b6d4',
            ],
            'ayuda-vecinal' => [
                'title' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'slug' => 'ayuda-vecinal',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="ayuda-vecinal"]',
                'icon' => 'dashicons-heart',
                'color' => '#f97316',
            ],
            'huertos-urbanos' => [
                'title' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'slug' => 'huertos-urbanos',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="huertos-urbanos"]',
                'icon' => 'dashicons-palmtree',
                'color' => '#22c55e',
            ],
            'biblioteca' => [
                'title' => __('Biblioteca', 'flavor-chat-ia'),
                'slug' => 'biblioteca',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="biblioteca"]',
                'icon' => 'dashicons-book',
                'color' => '#6366f1',
            ],
            'cursos' => [
                'title' => __('Cursos y Talleres', 'flavor-chat-ia'),
                'slug' => 'cursos',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="cursos"]',
                'icon' => 'dashicons-welcome-learn-more',
                'color' => '#a855f7',
            ],
            'eventos' => [
                'title' => __('Eventos', 'flavor-chat-ia'),
                'slug' => 'eventos',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="eventos"]',
                'icon' => 'dashicons-calendar',
                'color' => '#3b82f6',
            ],
            'marketplace' => [
                'title' => __('Marketplace', 'flavor-chat-ia'),
                'slug' => 'mercadillo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="marketplace"]',
                'icon' => 'dashicons-megaphone',
                'color' => '#f59e0b',
            ],
            'incidencias' => [
                'title' => __('Incidencias', 'flavor-chat-ia'),
                'slug' => 'incidencias',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="incidencias"]',
                'icon' => 'dashicons-warning',
                'color' => '#e11d48',
            ],
            'bicicletas' => [
                'title' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                'slug' => 'bicicletas',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="bicicletas"]',
                'icon' => 'dashicons-location-alt',
                'color' => '#a3e635',
            ],
            'reciclaje' => [
                'title' => __('Reciclaje', 'flavor-chat-ia'),
                'slug' => 'reciclaje',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="reciclaje"]',
                'icon' => 'dashicons-update',
                'color' => '#10b981',
            ],

            // Sectores Empresariales
            'restaurante' => [
                'title' => __('Restaurante Demo', 'flavor-chat-ia'),
                'slug' => 'restaurante-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="restaurante"]',
                'icon' => 'dashicons-food',
                'color' => '#b91c1c',
            ],
            'peluqueria' => [
                'title' => __('Peluquería Demo', 'flavor-chat-ia'),
                'slug' => 'peluqueria-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="peluqueria"]',
                'icon' => 'dashicons-art',
                'color' => '#be185d',
            ],
            'gimnasio' => [
                'title' => __('Gimnasio Demo', 'flavor-chat-ia'),
                'slug' => 'gimnasio-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="gimnasio"]',
                'icon' => 'dashicons-superhero',
                'color' => '#ea580c',
            ],
            'clinica' => [
                'title' => __('Clínica Demo', 'flavor-chat-ia'),
                'slug' => 'clinica-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="clinica"]',
                'icon' => 'dashicons-heart',
                'color' => '#0891b2',
            ],
            'hotel' => [
                'title' => __('Hotel Demo', 'flavor-chat-ia'),
                'slug' => 'hotel-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="hotel"]',
                'icon' => 'dashicons-admin-home',
                'color' => '#7c3aed',
            ],
            'inmobiliaria' => [
                'title' => __('Inmobiliaria Demo', 'flavor-chat-ia'),
                'slug' => 'inmobiliaria-demo',
                'template' => 'flavor-landing',
                'content' => '[flavor_landing module="inmobiliaria"]',
                'icon' => 'dashicons-building',
                'color' => '#059669',
            ],
        ];
    }

    /**
     * Crea las páginas de demostración
     *
     * @return array
     */
    public function create_demo_pages($only_active = false) {
        $paginas = $this->get_demo_pages_definition_filtered($only_active);
        $paginas_creadas = [];
        $usuario_id = $this->get_demo_user_id();

        foreach ($paginas as $id => $pagina) {
            // Verificar si ya existe una página con ese slug
            $pagina_existente = get_page_by_path($pagina['slug']);

            if ($pagina_existente) {
                // Si existe pero no es demo, no la tocamos
                if (!get_post_meta($pagina_existente->ID, '_flavor_demo_page', true)) {
                    continue;
                }
                // Si es demo, la actualizamos
                $post_id = wp_update_post([
                    'ID' => $pagina_existente->ID,
                    'post_title' => $pagina['title'],
                    'post_content' => $pagina['content'],
                    'post_status' => 'publish',
                ]);
            } else {
                // Crear nueva página
                $post_id = wp_insert_post([
                    'post_title' => $pagina['title'],
                    'post_name' => $pagina['slug'],
                    'post_content' => $pagina['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => $usuario_id,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                ]);
            }

            if ($post_id && !is_wp_error($post_id)) {
                // Marcar como página demo
                update_post_meta($post_id, '_flavor_demo_page', true);
                update_post_meta($post_id, '_flavor_landing_module', $id);

                // Asignar template si existe
                if (!empty($pagina['template'])) {
                    update_post_meta($post_id, '_wp_page_template', $pagina['template']);
                }

                $paginas_creadas[] = $post_id;
            }
        }

        // Guardar IDs de páginas creadas
        update_option('flavor_demo_pages_ids', $paginas_creadas);

        // Flush rewrite rules para que las nuevas URLs funcionen
        flush_rewrite_rules();

        return [
            'success' => true,
            'count' => count($paginas_creadas),
            'message' => sprintf(__('Se crearon %d páginas de demostración', 'flavor-chat-ia'), count($paginas_creadas)),
            'pages' => $paginas_creadas,
        ];
    }

    /**
     * Elimina las páginas de demostración
     *
     * @return array
     */
    public function delete_demo_pages() {
        $paginas_ids = get_option('flavor_demo_pages_ids', []);
        $eliminadas = 0;

        // También buscar páginas con el meta _flavor_demo_page
        $paginas_demo = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_key' => '_flavor_demo_page',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

        $todos_ids = array_unique(array_merge($paginas_ids, $paginas_demo));

        foreach ($todos_ids as $page_id) {
            // Verificar que realmente es una página demo
            if (get_post_meta($page_id, '_flavor_demo_page', true)) {
                if (wp_delete_post($page_id, true)) {
                    $eliminadas++;
                }
            }
        }

        // Limpiar la opción
        delete_option('flavor_demo_pages_ids');

        // Flush rewrite rules
        flush_rewrite_rules();

        return [
            'success' => true,
            'count' => $eliminadas,
            'message' => sprintf(__('Se eliminaron %d páginas de demostración', 'flavor-chat-ia'), $eliminadas),
        ];
    }

    /**
     * Verifica si existen páginas demo
     *
     * @return bool
     */
    public function has_demo_pages() {
        $paginas_ids = get_option('flavor_demo_pages_ids', []);

        if (!empty($paginas_ids)) {
            return true;
        }

        // También verificar por meta
        $paginas_demo = get_posts([
            'post_type' => 'page',
            'posts_per_page' => 1,
            'meta_key' => '_flavor_demo_page',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

        return !empty($paginas_demo);
    }

    /**
     * Obtiene el conteo de páginas demo
     *
     * @return int
     */
    public function get_demo_pages_count($scope = 'all') {
        if ($scope === 'active') {
            $definiciones = $this->get_demo_pages_definition_filtered(true);
            $count = 0;
            foreach ($definiciones as $definicion) {
                $pagina_existente = get_page_by_path($definicion['slug']);
                $es_demo = $pagina_existente && get_post_meta($pagina_existente->ID, '_flavor_demo_page', true);
                if ($es_demo) {
                    $count++;
                }
            }
            return $count;
        }

        $paginas_demo = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_key' => '_flavor_demo_page',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

        return count($paginas_demo);
    }

    /**
     * Obtiene las páginas demo existentes con sus datos
     *
     * @return array
     */
    public function get_demo_pages_list($scope = 'all') {
        $definiciones = $this->get_demo_pages_definition_filtered($scope === 'active');
        $paginas_resultado = [];

        foreach ($definiciones as $id => $definicion) {
            $pagina_existente = get_page_by_path($definicion['slug']);
            $es_demo = $pagina_existente && get_post_meta($pagina_existente->ID, '_flavor_demo_page', true);

            $paginas_resultado[$id] = [
                'title' => $definicion['title'],
                'slug' => $definicion['slug'],
                'icon' => $definicion['icon'],
                'color' => $definicion['color'],
                'exists' => (bool) $pagina_existente,
                'is_demo' => $es_demo,
                'url' => $pagina_existente ? get_permalink($pagina_existente->ID) : home_url('/' . $definicion['slug'] . '/'),
                'edit_url' => $pagina_existente ? get_edit_post_link($pagina_existente->ID) : null,
                'page_id' => $pagina_existente ? $pagina_existente->ID : null,
            ];
        }

        return $paginas_resultado;
    }

    /**
     * Devuelve definiciones filtradas por módulos activos (opcional).
     *
     * @param bool $only_active
     * @return array
     */
    private function get_demo_pages_definition_filtered($only_active = false) {
        $definiciones = $this->get_demo_pages_definition();
        if (!$only_active) {
            return $definiciones;
        }

        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = array_map('sanitize_key', $settings['active_modules'] ?? []);
        $active_frontend = array_map('sanitize_key', $settings['active_frontend_modules'] ?? []);

        if (empty($active_modules) && empty($active_frontend)) {
            return $definiciones;
        }

        $filtered = [];
        foreach ($definiciones as $id => $definicion) {
            $module_id = $this->normalizar_modulo_desde_definicion($id, $definicion);
            $module_slug = str_replace('_', '-', $module_id);

            if (
                in_array($module_id, $active_modules, true) ||
                in_array($module_slug, $active_frontend, true)
            ) {
                $filtered[$id] = $definicion;
            }
        }

        return $filtered;
    }

    /**
     * Normaliza el id de modulo asociado a una definicion de landing.
     *
     * @param string $id
     * @param array $definicion
     * @return string
     */
    private function normalizar_modulo_desde_definicion($id, $definicion) {
        $module_id = $id;

        if (!empty($definicion['content']) && preg_match('/module=\"([a-zA-Z0-9_-]+)\"/', $definicion['content'], $matches)) {
            $module_id = $matches[1];
        }

        $module_id = sanitize_key($module_id);
        $module_id = str_replace('-', '_', $module_id);
        return $module_id;
    }

    // =========================================================
    // MÉTODOS POPULATE ADICIONALES
    // =========================================================

// =========================================================
// PARTICIPACIÓN
// =========================================================

private function populate_participacion() {
    global $wpdb;
    $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $propuestas_demo = [
        ['titulo' => 'Crear zona de juegos infantiles en Plaza Mayor', 'categoria' => 'espacios_publicos', 'votos' => 45],
        ['titulo' => 'Ampliar horario de biblioteca municipal', 'categoria' => 'cultura', 'votos' => 32],
        ['titulo' => 'Instalar más papeleras de reciclaje', 'categoria' => 'medioambiente', 'votos' => 28],
        ['titulo' => 'Mejorar iluminación en Calle Oscura', 'categoria' => 'seguridad', 'votos' => 56],
        ['titulo' => 'Organizar mercado de productores locales', 'categoria' => 'comercio', 'votos' => 41],
    ];

    foreach ($propuestas_demo as $index => $prop) {
        $autor_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];

        $resultado = $wpdb->insert(
            $tabla_propuestas,
            [
                'titulo' => $prop['titulo'],
                'descripcion' => 'Propuesta ciudadana para mejorar nuestro municipio.',
                'resumen' => substr($prop['titulo'], 0, 100),
                'categoria' => $prop['categoria'],
                'proponente_id' => $autor_id,
                'estado' => 'activa',
                'tipo' => 'propuesta',
                'votos_favor' => $prop['votos'],
                'votos_contra' => rand(5, 15),
                'total_apoyos' => $prop['votos'],
                'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 30) . ' days')),
                'fecha_publicacion' => date('Y-m-d H:i:s', strtotime('-' . rand(4, 29) . ' days')),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('participacion', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d propuestas ciudadanas', count($ids_insertados)),
    ];
}

private function clear_participacion() {
    global $wpdb;
    $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
    $ids = $this->get_demo_ids('participacion');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_propuestas WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('participacion');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// BIBLIOTECA
// =========================================================

private function populate_biblioteca() {
    global $wpdb;
    $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $libros_demo = [
        ['titulo' => 'El jardín de las mariposas', 'autor' => 'Dot Hutchison', 'isbn' => '9788491291787', 'genero' => 'Thriller'],
        ['titulo' => 'La casa de los espíritus', 'autor' => 'Isabel Allende', 'isbn' => '9788497592000', 'genero' => 'Novela'],
        ['titulo' => 'Cien años de soledad', 'autor' => 'Gabriel García Márquez', 'isbn' => '9788497592000', 'genero' => 'Realismo mágico'],
        ['titulo' => 'El principito', 'autor' => 'Antoine de Saint-Exupéry', 'isbn' => '9788478887194', 'genero' => 'Infantil'],
        ['titulo' => '1984', 'autor' => 'George Orwell', 'isbn' => '9788499890944', 'genero' => 'Distopía'],
        ['titulo' => 'Don Quijote de la Mancha', 'autor' => 'Miguel de Cervantes', 'isbn' => '9788467019230', 'genero' => 'Clásico'],
        ['titulo' => 'La sombra del viento', 'autor' => 'Carlos Ruiz Zafón', 'isbn' => '9788408163381', 'genero' => 'Misterio'],
        ['titulo' => 'Los pilares de la Tierra', 'autor' => 'Ken Follett', 'isbn' => '9788497595049', 'genero' => 'Histórica'],
    ];

    foreach ($libros_demo as $index => $libro) {
        $propietario_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];

        $resultado = $wpdb->insert(
            $tabla_libros,
            [
                'titulo' => $libro['titulo'],
                'autor' => $libro['autor'],
                'isbn' => $libro['isbn'],
                'genero' => $libro['genero'],
                'editorial' => 'Editorial Demo',
                'ano_publicacion' => rand(1990, 2024),
                'propietario_id' => $propietario_id,
                'estado' => 'disponible',
                'condicion' => 'bueno',
                'ubicacion' => 'Estantería ' . chr(65 + rand(0, 10)),
                'fecha_registro' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 365) . ' days')),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('biblioteca', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d libros', count($ids_insertados)),
    ];
}

private function clear_biblioteca() {
    global $wpdb;
    $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
    $ids = $this->get_demo_ids('biblioteca');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_libros WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('biblioteca');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// BICICLETAS COMPARTIDAS
// =========================================================

private function populate_bicicletas_compartidas() {
    global $wpdb;
    $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
    $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $ids_insertados = ['bicicletas' => [], 'estaciones' => []];

    // Crear estaciones
    $estaciones_demo = [
        ['nombre' => 'Estación Plaza Mayor', 'ubicacion' => 'Plaza Mayor, 1', 'capacidad' => 12],
        ['nombre' => 'Estación Parque Central', 'ubicacion' => 'Parque Central', 'capacidad' => 10],
        ['nombre' => 'Estación Universidad', 'ubicacion' => 'Campus Universitario', 'capacidad' => 15],
        ['nombre' => 'Estación Centro Cívico', 'ubicacion' => 'C/ Centro Cívico 5', 'capacidad' => 8],
    ];

    foreach ($estaciones_demo as $est) {
        $resultado = $wpdb->insert(
            $tabla_estaciones,
            [
                'nombre' => $est['nombre'],
                'capacidad_total' => $est['capacidad'],
                'bicicletas_disponibles' => rand(3, $est['capacidad'] - 2),
                'estado' => 'activo',
            ],
            ['%s', '%d', '%d', '%s']
        );

        if ($resultado) {
            $ids_insertados['estaciones'][] = $wpdb->insert_id;
        }
    }

    // Crear bicicletas solo si hay estaciones
    if (empty($ids_insertados['estaciones'])) {
        return [
            'success' => true,
            'count' => 0,
            'message' => __('No se pudieron crear estaciones (columnas de tabla no coinciden)', 'flavor-chat-ia'),
        ];
    }

    $estaciones_ids = $ids_insertados['estaciones'];
    for ($i = 1; $i <= 20; $i++) {
        $estacion_id = $estaciones_ids[array_rand($estaciones_ids)];

        $resultado = $wpdb->insert(
            $tabla_bicicletas,
            [
                'codigo' => 'BIC' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'modelo' => ['Urbana', 'Montaña', 'Eléctrica'][array_rand(['Urbana', 'Montaña', 'Eléctrica'])],
                'estado' => 'disponible',
                'estacion_actual_id' => $estacion_id,
                'ultima_revision' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );

        if ($resultado) {
            $ids_insertados['bicicletas'][] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('bicicletas_compartidas', array_merge($ids_insertados['bicicletas'], $ids_insertados['estaciones']));

    return [
        'success' => true,
        'counts' => [
            'bicicletas' => count($ids_insertados['bicicletas']),
            'estaciones' => count($ids_insertados['estaciones']),
        ],
        'message' => sprintf('Se crearon %d bicicletas en %d estaciones',
            count($ids_insertados['bicicletas']),
            count($ids_insertados['estaciones'])
        ),
    ];
}

private function clear_bicicletas_compartidas() {
    global $wpdb;
    $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
    $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
    $ids = $this->get_demo_ids('bicicletas_compartidas');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_bicicletas WHERE id IN ($placeholders)", ...$ids));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_estaciones WHERE id IN ($placeholders)", ...$ids));

    $this->clear_demo_ids('bicicletas_compartidas');

    return ['success' => true, 'count' => count($ids)];
}

// =========================================================
// CARPOOLING
// =========================================================

private function populate_carpooling() {
    global $wpdb;
    $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_viajes)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $viajes_demo = [
        ['origen' => 'Centro', 'destino' => 'Polígono Industrial', 'hora' => '08:00', 'plazas' => 3],
        ['origen' => 'Universidad', 'destino' => 'Centro Comercial', 'hora' => '18:00', 'plazas' => 2],
        ['origen' => 'Estación Tren', 'destino' => 'Hospital', 'hora' => '09:30', 'plazas' => 3],
        ['origen' => 'Barrio Norte', 'destino' => 'Aeropuerto', 'hora' => '06:00', 'plazas' => 2],
        ['origen' => 'Plaza Mayor', 'destino' => 'Universidad', 'hora' => '07:45', 'plazas' => 4],
    ];

    foreach ($viajes_demo as $index => $viaje) {
        $conductor_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];

        $fecha_salida = date('Y-m-d ' . $viaje['hora'] . ':00', strtotime('+' . rand(1, 7) . ' days'));

        $resultado = $wpdb->insert(
            $tabla_viajes,
            [
                'conductor_id' => $conductor_id,
                'origen' => $viaje['origen'],
                'destino' => $viaje['destino'],
                'fecha_salida' => $fecha_salida,
                'plazas_disponibles' => $viaje['plazas'],
                'precio_por_plaza' => rand(3, 10),
                'estado' => 'activo',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('carpooling', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d viajes compartidos', count($ids_insertados)),
    ];
}

private function clear_carpooling() {
    global $wpdb;
    $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
    $ids = $this->get_demo_ids('carpooling');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_viajes WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('carpooling');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// COMPOSTAJE
// =========================================================

private function populate_compostaje() {
    global $wpdb;
    $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $ids_insertados = [];

    $puntos_demo = [
        ['nombre' => 'Punto Compostaje Parque Central', 'direccion' => 'Parque Central s/n', 'capacidad' => 1000],
        ['nombre' => 'Compostador Comunitario Norte', 'direccion' => 'C/ Barrio Norte, 15', 'capacidad' => 800],
        ['nombre' => 'Punto Huertos Urbanos', 'direccion' => 'Av. Huertos Urbanos, 3', 'capacidad' => 1200],
        ['nombre' => 'Compostador Plaza Mayor', 'direccion' => 'Plaza Mayor, 1', 'capacidad' => 1000],
    ];

    foreach ($puntos_demo as $punto) {
        $resultado = $wpdb->insert(
            $tabla_puntos,
            [
                'nombre' => $punto['nombre'],
                'direccion' => $punto['direccion'],
                'capacidad_litros' => $punto['capacidad'],
                'nivel_llenado_pct' => rand(20, 80),
                'tipo' => 'comunitario',
                'num_composteras' => rand(2, 5),
                'fase_actual' => 'activo',
                'estado' => 'activo',
                'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 365) . ' days')),
            ],
            ['%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('compostaje', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d puntos de compostaje', count($ids_insertados)),
    ];
}

private function clear_compostaje() {
    global $wpdb;
    $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
    $ids = $this->get_demo_ids('compostaje');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_puntos WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('compostaje');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// HUERTOS URBANOS
// =========================================================

private function populate_huertos_urbanos() {
    global $wpdb;
    $tabla_huertos = $wpdb->prefix . 'flavor_huertos';
    $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = ['huertos' => [], 'parcelas' => []];

    // Crear huertos
    $huertos_demo = [
        ['nombre' => 'Huerto Comunitario Norte', 'ubicacion' => 'Barrio Norte', 'parcelas' => 15],
        ['nombre' => 'Huerto Urbano Centro', 'ubicacion' => 'Centro Ciudad', 'parcelas' => 12],
        ['nombre' => 'Huerto Parque Sur', 'ubicacion' => 'Parque Sur', 'parcelas' => 20],
    ];

    foreach ($huertos_demo as $huerto) {
        $resultado = $wpdb->insert(
            $tabla_huertos,
            [
                'nombre' => $huerto['nombre'],
                'ubicacion' => $huerto['ubicacion'],
                'superficie_m2' => $huerto['parcelas'] * 25,
                'num_parcelas' => $huerto['parcelas'],
                'activo' => 1,
                'fecha_apertura' => date('Y-m-d', strtotime('-2 years')),
            ],
            ['%s', '%s', '%d', '%d', '%d', '%s']
        );

        if ($resultado) {
            $huerto_id = $wpdb->insert_id;
            $ids_insertados['huertos'][] = $huerto_id;

            // Crear parcelas para este huerto
            for ($i = 1; $i <= $huerto['parcelas']; $i++) {
                $asignado = $i <= count($usuarios_compartidos);
                $usuario_asignado = $asignado ? $usuarios_compartidos[($i - 1) % count($usuarios_compartidos)] : null;

                $resultado_parcela = $wpdb->insert(
                    $tabla_parcelas,
                    [
                        'huerto_id' => $huerto_id,
                        'numero' => $i,
                        'superficie_m2' => 25,
                        'estado' => $asignado ? 'ocupada' : 'disponible',
                        'usuario_asignado_id' => $usuario_asignado,
                        'fecha_asignacion' => $asignado ? date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')) : null,
                    ],
                    ['%d', '%d', '%d', '%s', '%d', '%s']
                );

                if ($resultado_parcela) {
                    $ids_insertados['parcelas'][] = $wpdb->insert_id;
                }
            }
        }
    }

    $this->mark_as_demo('huertos_urbanos', array_merge($ids_insertados['huertos'], $ids_insertados['parcelas']));

    return [
        'success' => true,
        'counts' => [
            'huertos' => count($ids_insertados['huertos']),
            'parcelas' => count($ids_insertados['parcelas']),
        ],
        'message' => sprintf('Se crearon %d huertos con %d parcelas',
            count($ids_insertados['huertos']),
            count($ids_insertados['parcelas'])
        ),
    ];
}

private function clear_huertos_urbanos() {
    global $wpdb;
    $tabla_huertos = $wpdb->prefix . 'flavor_huertos';
    $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
    $ids = $this->get_demo_ids('huertos_urbanos');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_huertos WHERE id IN ($placeholders)", ...$ids));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_parcelas WHERE id IN ($placeholders)", ...$ids));

    $this->clear_demo_ids('huertos_urbanos');

    return ['success' => true, 'count' => count($ids)];
}

// =========================================================
// PARKINGS
// =========================================================

private function populate_parkings() {
    global $wpdb;
    $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
    $tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_parkings)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = ['parkings' => [], 'plazas' => []];

    // Crear parkings
    $parkings_demo = [
        ['nombre' => 'Parking Plaza Mayor', 'direccion' => 'Plaza Mayor, s/n', 'plazas' => 50],
        ['nombre' => 'Parking Centro Cívico', 'direccion' => 'Av. Centro Cívico, 3', 'plazas' => 35],
    ];

    foreach ($parkings_demo as $parking) {
        $resultado = $wpdb->insert(
            $tabla_parkings,
            [
                'nombre' => $parking['nombre'],
                'descripcion' => 'Parking comunitario',
                'direccion' => $parking['direccion'],
                'total_plazas' => $parking['plazas'],
                'plazas_residentes' => (int)($parking['plazas'] * 0.7),
                'plazas_visitantes' => (int)($parking['plazas'] * 0.3),
                'tipo_parking' => 'subterraneo',
                'acceso_24h' => 1,
                'tipo_acceso' => 'tarjeta',
                'precio_hora_visitante' => 1.50,
                'cuota_mensual_residente' => 50.00,
            ],
            ['%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%f', '%f']
        );

        if ($resultado) {
            $parking_id = $wpdb->insert_id;
            $ids_insertados['parkings'][] = $parking_id;

            // Crear algunas plazas
            for ($i = 1; $i <= min(10, $parking['plazas']); $i++) {
                $ocupada = $i <= 5;

                $resultado_plaza = $wpdb->insert(
                    $tabla_plazas,
                    [
                        'parking_id' => $parking_id,
                        'numero' => 'P' . $i,
                        'tipo' => ['normal', 'PMR', 'electrico'][array_rand(['normal', 'PMR', 'electrico'])],
                        'estado' => $ocupada ? 'ocupada' : 'libre',
                    ],
                    ['%d', '%s', '%s', '%s']
                );

                if ($resultado_plaza) {
                    $ids_insertados['plazas'][] = $wpdb->insert_id;
                }
            }
        }
    }

    $this->mark_as_demo('parkings', array_merge($ids_insertados['parkings'], $ids_insertados['plazas']));

    return [
        'success' => true,
        'counts' => [
            'parkings' => count($ids_insertados['parkings']),
            'plazas' => count($ids_insertados['plazas']),
        ],
        'message' => sprintf('Se crearon %d parkings con %d plazas',
            count($ids_insertados['parkings']),
            count($ids_insertados['plazas'])
        ),
    ];
}

private function clear_parkings() {
    global $wpdb;
    $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
    $tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
    $ids = $this->get_demo_ids('parkings');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_parkings WHERE id IN ($placeholders)", ...$ids));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_plazas WHERE id IN ($placeholders)", ...$ids));

    $this->clear_demo_ids('parkings');

    return ['success' => true, 'count' => count($ids)];
}

// =========================================================
// PODCAST
// =========================================================

private function populate_podcast() {
    global $wpdb;
    $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
    $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_series)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = ['series' => [], 'episodios' => []];

    // Crear series
    $series_demo = [
        ['nombre' => 'Historias de Nuestro Barrio', 'descripcion' => 'Historias y anécdotas de vecinos', 'categoria' => 'Sociedad'],
        ['nombre' => 'Medio Ambiente Local', 'descripcion' => 'Actualidad ambiental municipal', 'categoria' => 'Ecología'],
    ];

    foreach ($series_demo as $index => $serie) {
        $creador_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];
        $slug = sanitize_title($serie['nombre']);

        $resultado = $wpdb->insert(
            $tabla_series,
            [
                'nombre' => $serie['nombre'],
                'slug' => $slug,
                'descripcion' => $serie['descripcion'],
                'creador_id' => $creador_id,
                'tipo' => 'serie',
                'estado' => 'activo',
                'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-6 months')),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($resultado) {
            $serie_id = $wpdb->insert_id;
            $ids_insertados['series'][] = $serie_id;

            // Crear episodios para esta serie
            for ($ep = 1; $ep <= 5; $ep++) {
                $resultado_ep = $wpdb->insert(
                    $tabla_episodios,
                    [
                        'serie_id' => $serie_id,
                        'numero_episodio' => $ep,
                        'titulo' => "Episodio {$ep}: " . ['Primavera', 'Verano', 'Otoño', 'Invierno', 'Especial'][$ep - 1],
                        'descripcion' => "Episodio número {$ep} de la serie.",
                        'duracion_segundos' => rand(600, 3600),
                        'fecha_publicacion' => date('Y-m-d H:i:s', strtotime('-' . (6 - $ep) . ' weeks')),
                        'reproducciones' => rand(20, 150),
                    ],
                    ['%d', '%d', '%s', '%s', '%d', '%s', '%d']
                );

                if ($resultado_ep) {
                    $ids_insertados['episodios'][] = $wpdb->insert_id;
                }
            }
        }
    }

    $this->mark_as_demo('podcast', array_merge($ids_insertados['series'], $ids_insertados['episodios']));

    return [
        'success' => true,
        'counts' => [
            'series' => count($ids_insertados['series']),
            'episodios' => count($ids_insertados['episodios']),
        ],
        'message' => sprintf('Se crearon %d series con %d episodios',
            count($ids_insertados['series']),
            count($ids_insertados['episodios'])
        ),
    ];
}

private function clear_podcast() {
    global $wpdb;
    $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
    $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
    $ids = $this->get_demo_ids('podcast');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_series WHERE id IN ($placeholders)", ...$ids));
    $wpdb->query($wpdb->prepare("DELETE FROM $tabla_episodios WHERE id IN ($placeholders)", ...$ids));

    $this->clear_demo_ids('podcast');

    return ['success' => true, 'count' => count($ids)];
}

// =========================================================
// RECICLAJE
// =========================================================

private function populate_reciclaje() {
    global $wpdb;
    $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $ids_insertados = [];

    $puntos_demo = [
        ['nombre' => 'Punto Limpio Norte', 'direccion' => 'Polígono Norte, Parcela 14', 'tipo' => 'punto_limpio'],
        ['nombre' => 'Contenedor Amarillo Plaza Mayor', 'direccion' => 'Plaza Mayor, s/n', 'tipo' => 'contenedor'],
        ['nombre' => 'Punto Verde Parque Central', 'direccion' => 'Parque Central, zona sur', 'tipo' => 'punto_verde'],
        ['nombre' => 'Contenedor Vidrio Centro', 'direccion' => 'C/ Centro, 5', 'tipo' => 'contenedor'],
    ];

    foreach ($puntos_demo as $punto) {
        $resultado = $wpdb->insert(
            $tabla_puntos,
            [
                'nombre' => $punto['nombre'],
                'direccion' => $punto['direccion'],
                'latitud' => 40.4168 + (rand(-100, 100) / 1000),
                'longitud' => -3.7038 + (rand(-100, 100) / 1000),
                'estado' => 'activo',
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%s', '%s', '%f', '%f', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('reciclaje', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d puntos de reciclaje', count($ids_insertados)),
    ];
}

private function clear_reciclaje() {
    global $wpdb;
    $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
    $ids = $this->get_demo_ids('reciclaje');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_puntos WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('reciclaje');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// TRANSPARENCIA
// =========================================================

private function populate_transparencia() {
    global $wpdb;
    $tabla_documentos = $wpdb->prefix . 'flavor_transparencia_documentos_publicos';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $ids_insertados = [];

    $documentos_demo = [
        ['titulo' => 'Presupuesto Municipal 2026', 'categoria' => 'economico'],
        ['titulo' => 'Acta Pleno 15 Enero 2026', 'categoria' => 'actas'],
        ['titulo' => 'Plan Movilidad Urbana', 'categoria' => 'urbanismo'],
        ['titulo' => 'Memoria Anual 2025', 'categoria' => 'informes'],
        ['titulo' => 'Contratos Adjudicados Q1 2026', 'categoria' => 'contratacion'],
    ];

    foreach ($documentos_demo as $doc) {
        $resultado = $wpdb->insert(
            $tabla_documentos,
            [
                'titulo' => $doc['titulo'],
                'descripcion' => 'Documento público disponible para consulta.',
                'categoria' => $doc['categoria'],
                'estado' => 'publicado',
                'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 30) . ' days')),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('transparencia', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d documentos de transparencia', count($ids_insertados)),
    ];
}

private function clear_transparencia() {
    global $wpdb;
    $tabla_documentos = $wpdb->prefix . 'flavor_transparencia_documentos_publicos';
    $ids = $this->get_demo_ids('transparencia');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_documentos WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('transparencia');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// PRESUPUESTOS PARTICIPATIVOS
// =========================================================

private function populate_presupuestos_participativos() {
    $admin_id = $this->get_demo_user_id();
    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $proyectos_demo = [
        ['titulo' => 'Renovación Parque Infantil', 'presupuesto' => 15000, 'votos' => 234],
        ['titulo' => 'Nuevos Carriles Bici', 'presupuesto' => 45000, 'votos' => 189],
        ['titulo' => 'Biblioteca 24h Digital', 'presupuesto' => 8000, 'votos' => 156],
        ['titulo' => 'Programa Compostaje Domiciliario', 'presupuesto' => 12000, 'votos' => 201],
    ];

    foreach ($proyectos_demo as $index => $proyecto) {
        $proponente_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];

        $post_id = wp_insert_post([
            'post_title' => $proyecto['titulo'],
            'post_content' => 'Proyecto propuesto por la ciudadanía para presupuestos participativos.',
            'post_status' => 'publish',
            'post_type' => 'presupuesto_part',
            'post_author' => $proponente_id,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            $ids_insertados[] = $post_id;
            update_post_meta($post_id, '_presupuesto_estimado', $proyecto['presupuesto']);
            update_post_meta($post_id, '_votos_totales', $proyecto['votos']);
            update_post_meta($post_id, '_estado', 'votacion');
            update_post_meta($post_id, '_flavor_demo_data', true);
        }
    }

    $this->mark_as_demo('presupuestos_participativos', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d proyectos presupuestarios', count($ids_insertados)),
    ];
}

private function clear_presupuestos_participativos() {
    $ids = $this->get_demo_ids('presupuestos_participativos');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $eliminados = 0;
    foreach ($ids as $post_id) {
        if (wp_delete_post($post_id, true)) {
            $eliminados++;
        }
    }

    $this->clear_demo_ids('presupuestos_participativos');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// CHAT GRUPOS
// =========================================================

private function populate_chat_grupos() {
    global $wpdb;
    $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $grupos_demo = [
        ['nombre' => 'Comunidad de Vecinos', 'descripcion' => 'Grupo general del vecindario', 'tipo' => 'publico'],
        ['nombre' => 'Huertos Urbanos', 'descripcion' => 'Grupo de usuarios del huerto', 'tipo' => 'publico'],
        ['nombre' => 'Ciclistas Urbanos', 'descripcion' => 'Aficionados a la bici', 'tipo' => 'publico'],
        ['nombre' => 'Club de Lectura', 'descripcion' => 'Lectores empedernidos', 'tipo' => 'privado'],
    ];

    foreach ($grupos_demo as $index => $grupo) {
        $creador_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];
        $slug = sanitize_title($grupo['nombre']);

        $resultado = $wpdb->insert(
            $tabla_grupos,
            [
                'nombre' => $grupo['nombre'],
                'slug' => $slug,
                'descripcion' => $grupo['descripcion'],
                'tipo' => $grupo['tipo'],
                'creador_id' => $creador_id,
                'categoria' => 'general',
                'miembros_count' => rand(5, 15),
                'mensajes_count' => rand(10, 100),
                'estado' => 'activo',
                'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 180) . ' days')),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('chat_grupos', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d grupos de chat', count($ids_insertados)),
    ];
}

private function clear_chat_grupos() {
    global $wpdb;
    $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
    $ids = $this->get_demo_ids('chat_grupos');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_grupos WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('chat_grupos');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// CURSOS
// =========================================================

private function populate_cursos() {
    global $wpdb;
    $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_cursos)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $cursos_demo = [
        ['titulo' => 'Cocina Vegetariana Básica', 'categoria' => 'gastronomia', 'duracion' => 8, 'plazas' => 12],
        ['titulo' => 'Introducción a la Permacultura', 'categoria' => 'ecologia', 'duracion' => 12, 'plazas' => 15],
        ['titulo' => 'Fotografía con Móvil', 'categoria' => 'tecnologia', 'duracion' => 6, 'plazas' => 10],
        ['titulo' => 'Primeros Auxilios', 'categoria' => 'salud', 'duracion' => 4, 'plazas' => 20],
    ];

    foreach ($cursos_demo as $index => $curso) {
        $instructor_id = $usuarios_compartidos[$index % count($usuarios_compartidos)];
        $slug = sanitize_title($curso['titulo']);

        $resultado = $wpdb->insert(
            $tabla_cursos,
            [
                'instructor_id' => $instructor_id,
                'titulo' => $curso['titulo'],
                'slug' => $slug,
                'descripcion' => 'Curso organizado por el centro cívico municipal.',
                'descripcion_corta' => substr($curso['titulo'], 0, 100),
                'categoria' => $curso['categoria'],
                'nivel' => 'todos',
                'modalidad' => 'presencial',
                'duracion_horas' => $curso['duracion'],
                'max_alumnos' => $curso['plazas'],
                'alumnos_inscritos' => rand(2, $curso['plazas'] - 5),
                'precio' => 0,
                'es_gratuito' => 1,
                'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+' . rand(7, 45) . ' days')),
                'estado' => 'publicado',
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('cursos', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d cursos', count($ids_insertados)),
    ];
}

private function clear_cursos() {
    global $wpdb;
    $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
    $ids = $this->get_demo_ids('cursos');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_cursos WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('cursos');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// ESPACIOS COMUNES
// =========================================================

private function populate_espacios_comunes() {
    global $wpdb;
    $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $ids_insertados = [];

    $espacios_demo = [
        ['nombre' => 'Sala Multiusos Centro Cívico', 'capacidad' => 50, 'equipamiento' => 'proyector,sillas,mesas'],
        ['nombre' => 'Salón de Actos Plaza Mayor', 'capacidad' => 100, 'equipamiento' => 'escenario,sonido,proyector'],
        ['nombre' => 'Aula de Formación', 'capacidad' => 25, 'equipamiento' => 'pizarra,ordenadores,proyector'],
        ['nombre' => 'Cocina Comunitaria', 'capacidad' => 15, 'equipamiento' => 'cocina_industrial,nevera,horno'],
    ];

    foreach ($espacios_demo as $espacio) {
        $resultado = $wpdb->insert(
            $tabla_espacios,
            [
                'nombre' => $espacio['nombre'],
                'descripcion' => 'Espacio disponible para reservas por parte de vecinos y asociaciones.',
                'capacidad_personas' => $espacio['capacidad'],
                'equipamiento' => $espacio['equipamiento'],
                'requiere_autorizacion' => 0,
                'activo' => 1,
                'horario_apertura' => '08:00',
                'horario_cierre' => '22:00',
            ],
            ['%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('espacios_comunes', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d espacios comunes', count($ids_insertados)),
    ];
}

private function clear_espacios_comunes() {
    global $wpdb;
    $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
    $ids = $this->get_demo_ids('espacios_comunes');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_espacios WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('espacios_comunes');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// FOROS
// =========================================================

private function populate_foros() {
    global $wpdb;
    $tabla_foros = $wpdb->prefix . 'flavor_foros';

    if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
        return ['success' => false, 'error' => __('Módulo no soportado', 'flavor-chat-ia')];
    }

    $usuarios_compartidos = $this->get_or_create_demo_users();
    $ids_insertados = [];

    $foros_demo = [
        ['nombre' => 'Urbanismo y Mobiliario', 'descripcion' => 'Propuestas sobre urbanismo', 'icono' => 'location_city'],
        ['nombre' => 'Cultura y Ocio', 'descripcion' => 'Actividades culturales y de ocio', 'icono' => 'theater_comedy'],
        ['nombre' => 'Medio Ambiente', 'descripcion' => 'Temas medioambientales', 'icono' => 'eco'],
        ['nombre' => 'Participación Ciudadana', 'descripcion' => 'Participación y voluntariado', 'icono' => 'people'],
        ['nombre' => 'General', 'descripcion' => 'Temas generales del municipio', 'icono' => 'forum'],
    ];

    foreach ($foros_demo as $index => $foro) {
        $resultado = $wpdb->insert(
            $tabla_foros,
            [
                'nombre' => $foro['nombre'],
                'descripcion' => $foro['descripcion'],
                'icono' => $foro['icono'],
                'orden' => $index + 1,
                'estado' => 'activo',
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(60, 180) . ' days')),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($resultado) {
            $ids_insertados[] = $wpdb->insert_id;
        }
    }

    $this->mark_as_demo('foros', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d temas de foro', count($ids_insertados)),
    ];
}

private function clear_foros() {
    global $wpdb;
    $tabla_foros = $wpdb->prefix . 'flavor_foros';
    $ids = $this->get_demo_ids('foros');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $eliminados = $wpdb->query($wpdb->prepare(
        "DELETE FROM $tabla_foros WHERE id IN ($placeholders)",
        ...$ids
    ));

    $this->clear_demo_ids('foros');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// COLECTIVOS
// =========================================================

private function populate_colectivos() {
    $admin_id = $this->get_demo_user_id();
    $ids_insertados = [];

    $colectivos_demo = [
        ['nombre' => 'Asociación de Vecinos Centro', 'tipo' => 'asociacion'],
        ['nombre' => 'Colectivo Ciclista', 'tipo' => 'deportivo'],
        ['nombre' => 'Grupo Ecologista Local', 'tipo' => 'medioambiental'],
        ['nombre' => 'Asociación Cultural Tradiciones', 'tipo' => 'cultural'],
    ];

    foreach ($colectivos_demo as $colectivo) {
        $post_id = wp_insert_post([
            'post_title' => $colectivo['nombre'],
            'post_content' => 'Colectivo activo en el municipio.',
            'post_status' => 'publish',
            'post_type' => 'colectivo',
            'post_author' => $admin_id,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            $ids_insertados[] = $post_id;
            update_post_meta($post_id, '_tipo_colectivo', $colectivo['tipo']);
            update_post_meta($post_id, '_num_miembros', rand(15, 60));
            update_post_meta($post_id, '_activo', 1);
            update_post_meta($post_id, '_flavor_demo_data', true);
        }
    }

    $this->mark_as_demo('colectivos', $ids_insertados);

    return [
        'success' => true,
        'count' => count($ids_insertados),
        'message' => sprintf('Se crearon %d colectivos', count($ids_insertados)),
    ];
}

private function clear_colectivos() {
    $ids = $this->get_demo_ids('colectivos');

    if (empty($ids)) {
        return ['success' => true, 'count' => 0];
    }

    $eliminados = 0;
    foreach ($ids as $post_id) {
        if (wp_delete_post($post_id, true)) {
            $eliminados++;
        }
    }

    $this->clear_demo_ids('colectivos');

    return ['success' => true, 'count' => $eliminados];
}

// =========================================================
// MÓDULOS SIN DATOS DEMO (Retornan mensaje informativo)
// =========================================================

private function populate_advertising() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_advertising() {
    return ['success' => true, 'count' => 0];
}

private function populate_chat_interno() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_chat_interno() {
    return ['success' => true, 'count' => 0];
}

private function populate_empresarial() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_empresarial() {
    return ['success' => true, 'count' => 0];
}

private function populate_multimedia() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_multimedia() {
    return ['success' => true, 'count' => 0];
}

private function populate_radio() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_radio() {
    return ['success' => true, 'count' => 0];
}

private function populate_clientes() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_clientes() {
    return ['success' => true, 'count' => 0];
}

private function populate_comunidades() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_comunidades() {
    return ['success' => true, 'count' => 0];
}

private function populate_bares() {
    return ['success' => true, 'count' => 0, 'message' => __('No hay datos demo que limpiar', 'flavor-chat-ia')];
}

private function clear_bares() {
    return ['success' => true, 'count' => 0];
}
}
