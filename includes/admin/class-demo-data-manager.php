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
        $resultados = [];

        if ($modulo_id === 'all') {
            $resultados = $this->populate_all_modules();
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
        $resultados = [];

        if ($modulo_id === 'all') {
            $resultados = $this->clear_all_modules();
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
     * @return array
     */
    public function populate_all_modules() {
        $resultados = [];
        $modulos_disponibles = ['banco_tiempo', 'eventos', 'marketplace', 'grupos_consumo', 'ayuda_vecinal'];

        foreach ($modulos_disponibles as $modulo_id) {
            $resultados[$modulo_id] = $this->populate_module($modulo_id);
        }

        return $resultados;
    }

    /**
     * Limpia todos los módulos
     *
     * @return array
     */
    public function clear_all_modules() {
        $resultados = [];
        $modulos_disponibles = ['banco_tiempo', 'eventos', 'marketplace', 'grupos_consumo', 'ayuda_vecinal'];

        foreach ($modulos_disponibles as $modulo_id) {
            $resultados[$modulo_id] = $this->clear_module($modulo_id);
        }

        return $resultados;
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

        return ['success' => false, 'error' => 'Módulo no soportado'];
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

        return ['success' => false, 'error' => 'Módulo no soportado'];
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
            return ['success' => false, 'error' => 'Tabla de servicios no existe'];
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
            return ['success' => true, 'count' => 0, 'message' => 'No hay datos demo que limpiar'];
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
            return ['success' => false, 'error' => 'Tabla de eventos no existe'];
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
                'maximo_asistentes' => 20,
            ],
            [
                'titulo' => 'Club de Lectura: Reunión Mensual',
                'descripcion' => 'Este mes comentamos "Cien años de soledad" de García Márquez. Ven a compartir tus impresiones y descubrir nuevas perspectivas.',
                'categoria' => 'reunion',
                'fecha_inicio' => date('Y-m-d 19:00:00', strtotime('+5 days')),
                'fecha_fin' => date('Y-m-d 21:00:00', strtotime('+5 days')),
                'ubicacion' => 'Biblioteca Municipal - Sala de Lectura',
                'maximo_asistentes' => 12,
            ],
            [
                'titulo' => 'Yoga al Aire Libre',
                'descripcion' => 'Sesión gratuita de yoga para todos los niveles. Trae tu esterilla y ropa cómoda. En caso de lluvia se suspende.',
                'categoria' => 'actividad',
                'fecha_inicio' => date('Y-m-d 09:00:00', strtotime('+3 days')),
                'fecha_fin' => date('Y-m-d 10:00:00', strtotime('+3 days')),
                'ubicacion' => 'Parque Central - Zona de Césped',
                'maximo_asistentes' => 25,
            ],
        ];

        foreach ($eventos_demo as $evento) {
            $resultado = $wpdb->insert(
                $tabla_eventos,
                [
                    'titulo' => $evento['titulo'],
                    'descripcion' => $evento['descripcion'],
                    'categoria' => $evento['categoria'],
                    'fecha_inicio' => $evento['fecha_inicio'],
                    'fecha_fin' => $evento['fecha_fin'],
                    'ubicacion' => $evento['ubicacion'],
                    'maximo_asistentes' => $evento['maximo_asistentes'],
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
            return ['success' => true, 'count' => 0, 'message' => 'No hay datos demo que limpiar'];
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
            return ['success' => true, 'count' => 0, 'message' => 'No hay datos demo que limpiar'];
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

        $this->mark_as_demo('grupos_consumo', $ids_insertados);

        $total_productores = count($ids_insertados['productores']);
        $total_productos = count($ids_insertados['productos']);
        $total_ciclos = count($ids_insertados['ciclos']);

        return [
            'success' => true,
            'count' => $total_productores + $total_productos + $total_ciclos,
            'message' => sprintf(
                'Se insertaron %d productores, %d productos y %d ciclo de pedidos',
                $total_productores,
                $total_productos,
                $total_ciclos
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
                return ['success' => true, 'count' => 0, 'message' => 'No hay datos demo que limpiar'];
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
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        // Verificar que la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return ['success' => false, 'error' => 'Tabla de ayuda vecinal no existe'];
        }

        $usuario_id = $this->get_demo_user_id();
        $ids_insertados = [];

        $solicitudes_demo = [
            [
                'titulo' => 'Necesito ayuda con la compra semanal',
                'descripcion' => 'Soy una persona mayor y me cuesta cargar las bolsas. Busco alguien que pueda acompañarme al supermercado una vez por semana.',
                'categoria' => 'compras',
                'urgencia' => 'normal',
                'ubicacion' => 'Calle Mayor, 15',
            ],
            [
                'titulo' => 'Busco quien pasee a mi perro',
                'descripcion' => 'Me han operado y no puedo caminar mucho. Necesito alguien que pasee a mi perro (es pequeño y tranquilo) 2 veces al día durante 2 semanas.',
                'categoria' => 'mascotas',
                'urgencia' => 'alta',
                'ubicacion' => 'Plaza Nueva, 8',
            ],
            [
                'titulo' => 'Ayuda con tecnología - configurar móvil',
                'descripcion' => 'Acabo de comprar un smartphone y no sé configurarlo. Necesito ayuda para instalar WhatsApp y aprender lo básico.',
                'categoria' => 'tecnologia',
                'urgencia' => 'normal',
                'ubicacion' => 'Av. de la Paz, 22',
            ],
            [
                'titulo' => 'Acompañamiento a cita médica',
                'descripcion' => 'Tengo una cita importante en el hospital el próximo martes a las 10h. Busco alguien que pueda acompañarme.',
                'categoria' => 'acompanamiento',
                'urgencia' => 'alta',
                'ubicacion' => 'Barrio San Jorge',
            ],
            [
                'titulo' => 'Pequeña reparación en casa',
                'descripcion' => 'Se me ha estropeado el grifo de la cocina y gotea constantemente. Busco alguien que sepa de fontanería básica.',
                'categoria' => 'bricolaje',
                'urgencia' => 'normal',
                'ubicacion' => 'Calle del Sol, 5',
            ],
        ];

        foreach ($solicitudes_demo as $solicitud) {
            $resultado = $wpdb->insert(
                $tabla_solicitudes,
                [
                    'usuario_id' => $usuario_id,
                    'titulo' => $solicitud['titulo'],
                    'descripcion' => $solicitud['descripcion'],
                    'categoria' => $solicitud['categoria'],
                    'urgencia' => $solicitud['urgencia'],
                    'ubicacion' => $solicitud['ubicacion'],
                    'estado' => 'abierta',
                    'fecha_creacion' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
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
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        $ids = $this->get_demo_ids('ayuda_vecinal');

        if (empty($ids)) {
            return ['success' => true, 'count' => 0, 'message' => 'No hay datos demo que limpiar'];
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

        $resultado = $this->create_demo_pages();

        $mensaje = $resultado['success'] ? 'demo_pages_created' : 'demo_pages_error';

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => $mensaje, 'count' => $resultado['count'] ?? 0],
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
    public function create_demo_pages() {
        $paginas = $this->get_demo_pages_definition();
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
    public function get_demo_pages_count() {
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
    public function get_demo_pages_list() {
        $definiciones = $this->get_demo_pages_definition();
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
}
