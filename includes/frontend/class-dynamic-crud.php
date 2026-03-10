<?php
/**
 * Sistema de CRUD Dinámico para Módulos
 *
 * Genera automáticamente formularios y listados para cualquier módulo,
 * permitiendo a los usuarios crear, ver, editar y eliminar sus registros.
 *
 * @package FlavorChatIA
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dynamic_CRUD {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Cache de esquemas de módulos
     */
    private $schemas = [];

    /**
     * Configuración de módulos
     */
    private $module_config = [];

    /**
     * Obtiene la URL actual para redirects de login en formularios dinámicos.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_module_config();

        // AJAX handlers
        add_action('wp_ajax_flavor_crud_create', [$this, 'ajax_create']);
        add_action('wp_ajax_flavor_crud_update', [$this, 'ajax_update']);
        add_action('wp_ajax_flavor_crud_delete', [$this, 'ajax_delete']);
        add_action('wp_ajax_flavor_crud_get', [$this, 'ajax_get']);
        add_action('wp_ajax_flavor_crud_list', [$this, 'ajax_list']);
        add_action('wp_ajax_flavor_crud_get_form', [$this, 'ajax_get_form']);

        // Shortcodes
        add_shortcode('flavor_crud_form', [$this, 'render_form_shortcode']);
        add_shortcode('flavor_crud_list', [$this, 'render_list_shortcode']);
        add_shortcode('flavor_mis_registros', [$this, 'render_my_records_shortcode']);
    }

    /**
     * Inicializa la configuración de módulos
     */
    private function init_module_config() {
        $this->module_config = [
            'eventos' => [
                'tabla' => 'flavor_eventos',
                'titulo_singular' => __('Evento', 'flavor-chat-ia'),
                'titulo_plural' => __('Eventos', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar-alt',
                'color' => '#4f46e5',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                    'fecha_inicio' => ['tipo' => 'datetime', 'label' => __('Fecha inicio', 'flavor-chat-ia'), 'required' => true],
                    'fecha_fin' => ['tipo' => 'datetime', 'label' => __('Fecha fin', 'flavor-chat-ia')],
                    'ubicacion' => ['tipo' => 'text', 'label' => __('Ubicación', 'flavor-chat-ia')],
                    'capacidad' => ['tipo' => 'number', 'label' => __('Capacidad', 'flavor-chat-ia')],
                    'precio' => ['tipo' => 'price', 'label' => __('Precio', 'flavor-chat-ia')],
                    'imagen' => ['tipo' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['titulo', 'fecha_inicio', 'ubicacion', 'estado'],
                'filtros' => ['estado', 'fecha_inicio'],
                'acciones_usuario' => ['crear', 'editar', 'ver', 'inscribirse'],
            ],

            'reservas' => [
                'tabla' => 'flavor_reservas',
                'titulo_singular' => __('Reserva', 'flavor-chat-ia'),
                'titulo_plural' => __('Reservas', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar',
                'color' => '#0891b2',
                'campos' => [
                    'recurso_id' => ['tipo' => 'select_recurso', 'label' => __('Recurso', 'flavor-chat-ia'), 'required' => true],
                    'fecha' => ['tipo' => 'date', 'label' => __('Fecha', 'flavor-chat-ia'), 'required' => true],
                    'hora_inicio' => ['tipo' => 'time', 'label' => __('Hora inicio', 'flavor-chat-ia'), 'required' => true],
                    'hora_fin' => ['tipo' => 'time', 'label' => __('Hora fin', 'flavor-chat-ia'), 'required' => true],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['recurso_id', 'fecha', 'hora_inicio', 'estado'],
                'filtros' => ['estado', 'fecha'],
                'estados' => ['pendiente', 'confirmada', 'cancelada', 'completada'],
            ],

            'incidencias' => [
                'tabla' => 'flavor_incidencias',
                'titulo_singular' => __('Incidencia', 'flavor-chat-ia'),
                'titulo_plural' => __('Incidencias', 'flavor-chat-ia'),
                'icono' => 'dashicons-warning',
                'color' => '#dc2626',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['via_publica' => 'Vía pública', 'alumbrado' => 'Alumbrado', 'limpieza' => 'Limpieza', 'otros' => 'Otros']],
                    'ubicacion' => ['tipo' => 'location', 'label' => __('Ubicación', 'flavor-chat-ia')],
                    'imagen' => ['tipo' => 'image', 'label' => __('Foto', 'flavor-chat-ia')],
                    'prioridad' => ['tipo' => 'select', 'label' => __('Prioridad', 'flavor-chat-ia'), 'options' => ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'urgente' => 'Urgente']],
                ],
                'campos_listado' => ['titulo', 'categoria', 'estado', 'fecha_creacion'],
                'filtros' => ['estado', 'categoria', 'prioridad'],
            ],

            'marketplace' => [
                'tabla' => 'flavor_marketplace',
                'titulo_singular' => __('Anuncio', 'flavor-chat-ia'),
                'titulo_plural' => __('Anuncios', 'flavor-chat-ia'),
                'icono' => 'dashicons-cart',
                'color' => '#ea580c',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                    'precio' => ['tipo' => 'price', 'label' => __('Precio', 'flavor-chat-ia')],
                    'tipo' => ['tipo' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['venta' => 'Venta', 'alquiler' => 'Alquiler', 'regalo' => 'Regalo', 'intercambio' => 'Intercambio']],
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['electronica' => 'Electrónica', 'hogar' => 'Hogar', 'ropa' => 'Ropa', 'vehiculos' => 'Vehículos', 'otros' => 'Otros']],
                    'imagenes' => ['tipo' => 'gallery', 'label' => __('Fotos', 'flavor-chat-ia')],
                    'contacto' => ['tipo' => 'text', 'label' => __('Contacto', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['titulo', 'precio', 'tipo', 'estado'],
                'filtros' => ['tipo', 'categoria', 'precio_min', 'precio_max'],
            ],

            'banco_tiempo' => [
                'tabla' => 'flavor_banco_tiempo_servicios',
                'titulo_singular' => __('Servicio', 'flavor-chat-ia'),
                'titulo_plural' => __('Servicios', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'color' => '#f59e0b',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['cuidados' => 'Cuidados', 'educacion' => 'Educación', 'bricolaje' => 'Bricolaje', 'tecnologia' => 'Tecnología', 'transporte' => 'Transporte', 'otros' => 'Otros']],
                    'horas_estimadas' => ['tipo' => 'number', 'label' => __('Horas estimadas', 'flavor-chat-ia'), 'step' => '0.5', 'min' => '0.5', 'max' => '8'],
                ],
                'campos_listado' => ['titulo', 'categoria', 'horas_estimadas', 'estado'],
                'filtros' => ['categoria', 'estado'],
            ],

            'bicicletas_compartidas' => [
                'tabla' => 'flavor_bicicletas_prestamos',
                'titulo_singular' => __('Préstamo de bici', 'flavor-chat-ia'),
                'titulo_plural' => __('Préstamos de bicis', 'flavor-chat-ia'),
                'icono' => 'dashicons-dashboard',
                'color' => '#0284c7',
                'campos' => [
                    'bicicleta_id' => ['tipo' => 'select_bicicleta', 'label' => __('Bicicleta', 'flavor-chat-ia'), 'required' => true],
                    'fecha_inicio' => ['tipo' => 'datetime', 'label' => __('Fecha recogida', 'flavor-chat-ia'), 'required' => true],
                    'fecha_fin' => ['tipo' => 'datetime', 'label' => __('Fecha devolución', 'flavor-chat-ia')],
                    'punto_recogida' => ['tipo' => 'select_punto', 'label' => __('Punto de recogida', 'flavor-chat-ia')],
                    'punto_devolucion' => ['tipo' => 'select_punto', 'label' => __('Punto de devolución', 'flavor-chat-ia')],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['bicicleta_id', 'fecha_inicio', 'fecha_fin', 'estado'],
                'filtros' => ['estado'],
            ],

            'carpooling' => [
                'tabla' => 'flavor_carpooling',
                'titulo_singular' => __('Viaje compartido', 'flavor-chat-ia'),
                'titulo_plural' => __('Viajes compartidos', 'flavor-chat-ia'),
                'icono' => 'dashicons-car',
                'color' => '#7c3aed',
                'campos' => [
                    'origen' => ['tipo' => 'location', 'label' => __('Origen', 'flavor-chat-ia'), 'required' => true],
                    'destino' => ['tipo' => 'location', 'label' => __('Destino', 'flavor-chat-ia'), 'required' => true],
                    'fecha' => ['tipo' => 'date', 'label' => __('Fecha', 'flavor-chat-ia'), 'required' => true],
                    'hora' => ['tipo' => 'time', 'label' => __('Hora salida', 'flavor-chat-ia'), 'required' => true],
                    'plazas' => ['tipo' => 'number', 'label' => __('Plazas disponibles', 'flavor-chat-ia'), 'required' => true, 'min' => 1, 'max' => 8],
                    'precio_plaza' => ['tipo' => 'price', 'label' => __('Precio por plaza', 'flavor-chat-ia')],
                    'tipo' => ['tipo' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['conductor' => 'Ofrezco viaje', 'pasajero' => 'Busco viaje']],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Detalles', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['origen', 'destino', 'fecha', 'hora', 'plazas'],
                'filtros' => ['tipo', 'fecha'],
            ],

            'huertos_urbanos' => [
                'tabla' => 'flavor_huertos',
                'titulo_singular' => __('Parcela', 'flavor-chat-ia'),
                'titulo_plural' => __('Parcelas', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'color' => '#16a34a',
                'campos' => [
                    'parcela_id' => ['tipo' => 'select_parcela', 'label' => __('Parcela', 'flavor-chat-ia'), 'required' => true],
                    'fecha_inicio' => ['tipo' => 'date', 'label' => __('Fecha inicio', 'flavor-chat-ia'), 'required' => true],
                    'duracion_meses' => ['tipo' => 'number', 'label' => __('Duración (meses)', 'flavor-chat-ia'), 'min' => 1],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['parcela_id', 'fecha_inicio', 'estado'],
                'filtros' => ['estado'],
            ],

            'biblioteca' => [
                'tabla' => 'flavor_biblioteca_prestamos',
                'titulo_singular' => __('Préstamo', 'flavor-chat-ia'),
                'titulo_plural' => __('Préstamos', 'flavor-chat-ia'),
                'icono' => 'dashicons-book',
                'color' => '#65a30d',
                'campos' => [
                    'libro_id' => ['tipo' => 'select_libro', 'label' => __('Libro', 'flavor-chat-ia'), 'required' => true],
                    'fecha_prestamo' => ['tipo' => 'date', 'label' => __('Fecha préstamo', 'flavor-chat-ia')],
                    'fecha_devolucion' => ['tipo' => 'date', 'label' => __('Fecha devolución prevista', 'flavor-chat-ia')],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['libro_id', 'fecha_prestamo', 'fecha_devolucion', 'estado'],
                'filtros' => ['estado'],
            ],

            'participacion' => [
                'tabla' => 'flavor_participacion',
                'titulo_singular' => __('Propuesta', 'flavor-chat-ia'),
                'titulo_plural' => __('Propuestas', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
                'color' => '#8b5cf6',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'wysiwyg', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['urbanismo' => 'Urbanismo', 'cultura' => 'Cultura', 'medio_ambiente' => 'Medio ambiente', 'movilidad' => 'Movilidad', 'servicios' => 'Servicios', 'otros' => 'Otros']],
                    'presupuesto_estimado' => ['tipo' => 'price', 'label' => __('Presupuesto estimado', 'flavor-chat-ia')],
                    'imagen' => ['tipo' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                    'ubicacion' => ['tipo' => 'location', 'label' => __('Ubicación', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['titulo', 'categoria', 'votos', 'estado'],
                'filtros' => ['categoria', 'estado'],
                'acciones_extra' => ['votar', 'comentar'],
            ],

            'cursos' => [
                'tabla' => 'flavor_cursos_inscripciones',
                'titulo_singular' => __('Inscripción', 'flavor-chat-ia'),
                'titulo_plural' => __('Inscripciones', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'color' => '#2563eb',
                'campos' => [
                    'curso_id' => ['tipo' => 'select_curso', 'label' => __('Curso', 'flavor-chat-ia'), 'required' => true],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Comentarios', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['curso_id', 'fecha_inscripcion', 'estado'],
                'filtros' => ['estado'],
            ],

            'talleres' => [
                'tabla' => 'flavor_talleres_inscripciones',
                'titulo_singular' => __('Inscripción a taller', 'flavor-chat-ia'),
                'titulo_plural' => __('Inscripciones a talleres', 'flavor-chat-ia'),
                'icono' => 'dashicons-hammer',
                'color' => '#7c3aed',
                'campos' => [
                    'taller_id' => ['tipo' => 'select_taller', 'label' => __('Taller', 'flavor-chat-ia'), 'required' => true],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Comentarios', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['taller_id', 'fecha_inscripcion', 'estado'],
                'filtros' => ['estado'],
            ],

            'grupos_consumo' => [
                'tabla' => 'flavor_grupos_consumo_pedidos',
                'titulo_singular' => __('Pedido', 'flavor-chat-ia'),
                'titulo_plural' => __('Pedidos', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'color' => '#22c55e',
                'campos' => [
                    'grupo_id' => ['tipo' => 'select_grupo', 'label' => __('Grupo', 'flavor-chat-ia'), 'required' => true],
                    'productos' => ['tipo' => 'productos_pedido', 'label' => __('Productos', 'flavor-chat-ia'), 'required' => true],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas para el pedido', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['grupo_id', 'total', 'fecha_pedido', 'estado'],
                'filtros' => ['estado', 'grupo_id'],
            ],

            'espacios_comunes' => [
                'tabla' => 'flavor_espacios_reservas',
                'titulo_singular' => __('Reserva de espacio', 'flavor-chat-ia'),
                'titulo_plural' => __('Reservas de espacios', 'flavor-chat-ia'),
                'icono' => 'dashicons-building',
                'color' => '#6366f1',
                'campos' => [
                    'espacio_id' => ['tipo' => 'select_espacio', 'label' => __('Espacio', 'flavor-chat-ia'), 'required' => true],
                    'fecha' => ['tipo' => 'date', 'label' => __('Fecha', 'flavor-chat-ia'), 'required' => true],
                    'hora_inicio' => ['tipo' => 'time', 'label' => __('Hora inicio', 'flavor-chat-ia'), 'required' => true],
                    'hora_fin' => ['tipo' => 'time', 'label' => __('Hora fin', 'flavor-chat-ia'), 'required' => true],
                    'motivo' => ['tipo' => 'text', 'label' => __('Motivo', 'flavor-chat-ia'), 'required' => true],
                    'asistentes' => ['tipo' => 'number', 'label' => __('Número de asistentes', 'flavor-chat-ia')],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas adicionales', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['espacio_id', 'fecha', 'hora_inicio', 'estado'],
                'filtros' => ['estado', 'espacio_id'],
            ],

            'comunidades' => [
                'tabla' => 'flavor_comunidades_miembros',
                'titulo_singular' => __('Membresía', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis comunidades', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'color' => '#0d9488',
                'campos' => [
                    'comunidad_id' => ['tipo' => 'select_comunidad', 'label' => __('Comunidad', 'flavor-chat-ia'), 'required' => true],
                    'rol' => ['tipo' => 'select', 'label' => __('Rol', 'flavor-chat-ia'), 'options' => ['miembro' => 'Miembro', 'moderador' => 'Moderador', 'admin' => 'Administrador']],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Presentación', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['comunidad_id', 'rol', 'fecha_union', 'estado'],
                'filtros' => ['estado', 'rol'],
            ],

            'podcast' => [
                'tabla' => 'flavor_podcast_suscripciones',
                'titulo_singular' => __('Suscripción', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis podcasts', 'flavor-chat-ia'),
                'icono' => 'dashicons-microphone',
                'color' => '#db2777',
                'campos' => [
                    'podcast_id' => ['tipo' => 'select_podcast', 'label' => __('Podcast', 'flavor-chat-ia'), 'required' => true],
                    'notificaciones' => ['tipo' => 'checkbox', 'label' => __('Notificaciones', 'flavor-chat-ia'), 'checkbox_label' => __('Recibir notificaciones de nuevos episodios', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['podcast_id', 'fecha_suscripcion'],
                'filtros' => [],
            ],

            'radio' => [
                'tabla' => 'flavor_radio_favoritos',
                'titulo_singular' => __('Favorito', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis programas favoritos', 'flavor-chat-ia'),
                'icono' => 'dashicons-format-audio',
                'color' => '#e11d48',
                'campos' => [
                    'programa_id' => ['tipo' => 'select_programa', 'label' => __('Programa', 'flavor-chat-ia'), 'required' => true],
                ],
                'campos_listado' => ['programa_id', 'fecha_agregado'],
                'filtros' => [],
            ],

            'reciclaje' => [
                'tabla' => 'flavor_reciclaje_depositos',
                'titulo_singular' => __('Depósito de reciclaje', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis depósitos de reciclaje', 'flavor-chat-ia'),
                'icono' => 'dashicons-update',
                'color' => '#22c55e',
                'campos' => [
                    'tipo_material' => ['tipo' => 'select', 'label' => __('Tipo de material', 'flavor-chat-ia'), 'options' => ['papel' => 'Papel/Cartón', 'plastico' => 'Plástico', 'vidrio' => 'Vidrio', 'organico' => 'Orgánico', 'electronico' => 'Electrónico', 'aceite' => 'Aceite', 'textil' => 'Textil', 'otros' => 'Otros'], 'required' => true],
                    'cantidad_kg' => ['tipo' => 'number', 'label' => __('Cantidad (kg)', 'flavor-chat-ia'), 'step' => '0.1'],
                    'punto_reciclaje_id' => ['tipo' => 'number', 'label' => __('Punto de reciclaje', 'flavor-chat-ia')],
                    'fecha_deposito' => ['tipo' => 'date', 'label' => __('Fecha', 'flavor-chat-ia')],
                    'foto_url' => ['tipo' => 'text', 'label' => __('Foto', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['tipo_material', 'cantidad_kg', 'fecha_deposito'],
                'filtros' => ['tipo_material'],
            ],

            'tramites' => [
                'tabla' => 'flavor_tramites_solicitudes',
                'titulo_singular' => __('Solicitud', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis trámites', 'flavor-chat-ia'),
                'icono' => 'dashicons-clipboard',
                'color' => '#3b82f6',
                'campos' => [
                    'tipo_tramite' => ['tipo' => 'select', 'label' => __('Tipo de trámite', 'flavor-chat-ia'), 'options' => ['certificado' => 'Certificado', 'licencia' => 'Licencia', 'permiso' => 'Permiso', 'solicitud' => 'Solicitud general', 'queja' => 'Queja/Reclamación'], 'required' => true],
                    'titulo' => ['tipo' => 'text', 'label' => __('Asunto', 'flavor-chat-ia'), 'required' => true],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                    'documentos' => ['tipo' => 'gallery', 'label' => __('Documentos adjuntos', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['titulo', 'tipo_tramite', 'fecha_solicitud', 'estado'],
                'filtros' => ['tipo_tramite', 'estado'],
            ],

            'avisos_municipales' => [
                'tabla' => 'flavor_avisos_suscripciones',
                'titulo_singular' => __('Suscripción a avisos', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis suscripciones a avisos', 'flavor-chat-ia'),
                'icono' => 'dashicons-bell',
                'color' => '#f59e0b',
                'campos' => [
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => ['general' => 'General', 'trafico' => 'Tráfico', 'obras' => 'Obras', 'eventos' => 'Eventos', 'emergencias' => 'Emergencias'], 'required' => true],
                    'zona' => ['tipo' => 'text', 'label' => __('Zona/Barrio', 'flavor-chat-ia')],
                    'email' => ['tipo' => 'checkbox', 'label' => __('Email', 'flavor-chat-ia'), 'checkbox_label' => __('Recibir por email', 'flavor-chat-ia')],
                    'push' => ['tipo' => 'checkbox', 'label' => __('Push', 'flavor-chat-ia'), 'checkbox_label' => __('Recibir notificaciones push', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['categoria', 'zona', 'fecha_suscripcion'],
                'filtros' => ['categoria'],
            ],

            'fichaje_empleados' => [
                'tabla' => 'flavor_fichajes',
                'titulo_singular' => __('Fichaje', 'flavor-chat-ia'),
                'titulo_plural' => __('Mis fichajes', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'color' => '#059669',
                'campos' => [
                    'tipo' => ['tipo' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['entrada' => 'Entrada', 'salida' => 'Salida', 'pausa_inicio' => 'Inicio pausa', 'pausa_fin' => 'Fin pausa'], 'required' => true],
                    'fecha_hora' => ['tipo' => 'datetime', 'label' => __('Fecha y hora', 'flavor-chat-ia'), 'required' => true],
                    'ubicacion' => ['tipo' => 'location', 'label' => __('Ubicación', 'flavor-chat-ia')],
                    'notas' => ['tipo' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['tipo', 'fecha_hora', 'ubicacion'],
                'filtros' => ['tipo'],
            ],

            'presupuestos_participativos' => [
                'tabla' => 'flavor_pp_proyectos',
                'titulo_singular' => __('Propuesta', 'flavor-chat-ia'),
                'titulo_plural' => __('Propuestas', 'flavor-chat-ia'),
                'icono' => 'dashicons-portfolio',
                'color' => '#7c3aed',
                'campos' => [
                    'titulo' => ['tipo' => 'text', 'label' => __('Título del proyecto', 'flavor-chat-ia'), 'required' => true, 'placeholder' => 'Nombre descriptivo de tu propuesta'],
                    'descripcion' => ['tipo' => 'textarea', 'label' => __('Descripción detallada', 'flavor-chat-ia'), 'required' => true, 'rows' => 6],
                    'categoria' => ['tipo' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true, 'options' => [
                        'infraestructura' => 'Infraestructura',
                        'medio_ambiente' => 'Medio Ambiente',
                        'cultura' => 'Cultura y Ocio',
                        'deporte' => 'Deporte',
                        'social' => 'Social',
                        'educacion' => 'Educación',
                        'accesibilidad' => 'Accesibilidad',
                    ]],
                    'presupuesto_solicitado' => ['tipo' => 'price', 'label' => __('Presupuesto estimado (€)', 'flavor-chat-ia'), 'required' => true],
                    'ubicacion' => ['tipo' => 'location', 'label' => __('Ubicación', 'flavor-chat-ia')],
                    'imagen' => ['tipo' => 'image', 'label' => __('Imagen', 'flavor-chat-ia')],
                ],
                'campos_listado' => ['titulo', 'categoria', 'presupuesto_solicitado', 'votos_recibidos', 'estado'],
                'filtros' => ['categoria', 'estado'],
                'estados' => ['borrador', 'pendiente_validacion', 'validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado', 'rechazado'],
                'estado_inicial' => 'pendiente_validacion',
                'campo_usuario' => 'proponente_id',
            ],
        ];

        // Permitir que los módulos añadan/modifiquen su configuración
        $this->module_config = apply_filters('flavor_crud_module_config', $this->module_config);
    }

    /**
     * Obtiene la configuración de un módulo
     */
    public function get_module_config($module_id) {
        $id_normalizado = str_replace('-', '_', $module_id);
        return $this->module_config[$id_normalizado] ?? null;
    }

    /**
     * Renderiza el formulario de creación/edición
     */
    public function render_form($module_id, $item_id = 0, $args = []) {
        $config = $this->get_module_config($module_id);

        if (!$config) {
            return '<div class="fcrud-error">' . __('Módulo no configurado', 'flavor-chat-ia') . '</div>';
        }

        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $item = $item_id > 0 ? $this->get_item($module_id, $item_id) : null;
        $is_edit = $item !== null;

        // Verificar permisos de edición
        if ($is_edit && !$this->can_edit($module_id, $item)) {
            return '<div class="fcrud-error">' . __('No tienes permiso para editar este elemento', 'flavor-chat-ia') . '</div>';
        }

        $form_id = 'fcrud-form-' . wp_unique_id();
        $color = $config['color'] ?? '#4f46e5';

        ob_start();
        ?>
        <div class="fcrud-form-container" style="--fcrud-color: <?php echo esc_attr($color); ?>;">
            <form id="<?php echo esc_attr($form_id); ?>" class="fcrud-form" data-module="<?php echo esc_attr($module_id); ?>" data-item-id="<?php echo esc_attr($item_id); ?>">
                <?php wp_nonce_field('flavor_crud_' . $module_id, 'fcrud_nonce'); ?>

                <div class="fcrud-form-header">
                    <h3>
                        <span class="dashicons <?php echo esc_attr($config['icono']); ?>"></span>
                        <?php
                        if ($is_edit) {
                            printf(__('Editar %s', 'flavor-chat-ia'), $config['titulo_singular']);
                        } else {
                            printf(__('Nuevo/a %s', 'flavor-chat-ia'), $config['titulo_singular']);
                        }
                        ?>
                    </h3>
                </div>

                <div class="fcrud-form-fields">
                    <?php foreach ($config['campos'] as $field_id => $field): ?>
                        <?php $this->render_field($field_id, $field, $item[$field_id] ?? '', $module_id); ?>
                    <?php endforeach; ?>
                </div>

                <div class="fcrud-form-actions">
                    <button type="button" class="fcrud-btn fcrud-btn-secondary fcrud-cancel">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" class="fcrud-btn fcrud-btn-primary">
                        <span class="fcrud-btn-text">
                            <?php echo $is_edit ? esc_html__('Guardar cambios', 'flavor-chat-ia') : esc_html__('Crear', 'flavor-chat-ia'); ?>
                        </span>
                        <span class="fcrud-btn-loading" style="display:none;">
                            <span class="fcrud-spinner"></span>
                            <?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?>
                        </span>
                    </button>
                </div>

                <div class="fcrud-form-message" style="display:none;"></div>
            </form>
        </div>
        <?php

        $this->enqueue_crud_assets();

        return ob_get_clean();
    }

    /**
     * Renderiza un campo del formulario
     */
    private function render_field($field_id, $field, $value = '', $module_id = '') {
        $type = $field['tipo'] ?? 'text';
        $label = $field['label'] ?? ucfirst($field_id);
        $required = $field['required'] ?? false;
        $placeholder = $field['placeholder'] ?? '';
        $help = $field['help'] ?? '';

        $field_class = 'fcrud-field fcrud-field--' . $type;
        if ($required) $field_class .= ' fcrud-field--required';

        ?>
        <div class="<?php echo esc_attr($field_class); ?>">
            <label for="fcrud-<?php echo esc_attr($field_id); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?><span class="required">*</span><?php endif; ?>
            </label>

            <?php
            switch ($type) {
                case 'text':
                case 'email':
                case 'url':
                case 'tel':
                    ?>
                    <input type="<?php echo esc_attr($type); ?>"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           placeholder="<?php echo esc_attr($placeholder); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'number':
                    $min = $field['min'] ?? '';
                    $max = $field['max'] ?? '';
                    $step = $field['step'] ?? 1;
                    ?>
                    <input type="number"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           min="<?php echo esc_attr($min); ?>"
                           max="<?php echo esc_attr($max); ?>"
                           step="<?php echo esc_attr($step); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'price':
                    ?>
                    <div class="fcrud-price-input">
                        <input type="number"
                               id="fcrud-<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($field_id); ?>"
                               value="<?php echo esc_attr($value); ?>"
                               step="0.01"
                               min="0"
                               <?php echo $required ? 'required' : ''; ?>>
                        <span class="fcrud-price-currency">€</span>
                    </div>
                    <?php
                    break;

                case 'textarea':
                    $rows = $field['rows'] ?? 4;
                    ?>
                    <textarea id="fcrud-<?php echo esc_attr($field_id); ?>"
                              name="<?php echo esc_attr($field_id); ?>"
                              rows="<?php echo esc_attr($rows); ?>"
                              placeholder="<?php echo esc_attr($placeholder); ?>"
                              <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
                    <?php
                    break;

                case 'wysiwyg':
                    wp_editor($value, 'fcrud-' . $field_id, [
                        'textarea_name' => $field_id,
                        'textarea_rows' => 8,
                        'media_buttons' => true,
                        'teeny' => true,
                        'quicktags' => false,
                    ]);
                    break;

                case 'select':
                    $options = $field['options'] ?? [];
                    ?>
                    <select id="fcrud-<?php echo esc_attr($field_id); ?>"
                            name="<?php echo esc_attr($field_id); ?>"
                            <?php echo $required ? 'required' : ''; ?>>
                        <option value=""><?php esc_html_e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($options as $opt_value => $opt_label): ?>
                            <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                                <?php echo esc_html($opt_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;

                case 'date':
                    ?>
                    <input type="date"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'time':
                    ?>
                    <input type="time"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'datetime':
                    ?>
                    <input type="datetime-local"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
                    break;

                case 'image':
                    ?>
                    <div class="fcrud-image-upload" data-field="<?php echo esc_attr($field_id); ?>">
                        <input type="hidden" name="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr($value); ?>">
                        <div class="fcrud-image-preview">
                            <?php if ($value): ?>
                                <img src="<?php echo esc_url($value); ?>" alt="">
                                <button type="button" class="fcrud-image-remove"><span class="dashicons dashicons-no-alt"></span></button>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="fcrud-image-select fcrud-btn fcrud-btn-secondary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Subir imagen', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <?php
                    break;

                case 'gallery':
                    ?>
                    <div class="fcrud-gallery-upload" data-field="<?php echo esc_attr($field_id); ?>">
                        <input type="hidden" name="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr($value); ?>">
                        <div class="fcrud-gallery-preview">
                            <?php
                            $images = $value ? explode(',', $value) : [];
                            foreach ($images as $img): ?>
                                <div class="fcrud-gallery-item">
                                    <img src="<?php echo esc_url($img); ?>" alt="">
                                    <button type="button" class="fcrud-gallery-remove"><span class="dashicons dashicons-no-alt"></span></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="fcrud-gallery-add fcrud-btn fcrud-btn-secondary">
                            <span class="dashicons dashicons-images-alt2"></span>
                            <?php esc_html_e('Añadir fotos', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <?php
                    break;

                case 'location':
                    ?>
                    <div class="fcrud-location-field">
                        <input type="text"
                               id="fcrud-<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($field_id); ?>"
                               value="<?php echo esc_attr($value); ?>"
                               placeholder="<?php esc_attr_e('Dirección o coordenadas', 'flavor-chat-ia'); ?>"
                               class="fcrud-location-input"
                               <?php echo $required ? 'required' : ''; ?>>
                        <button type="button" class="fcrud-location-detect" title="<?php esc_attr_e('Usar mi ubicación', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-location"></span>
                        </button>
                    </div>
                    <?php
                    break;

                case 'checkbox':
                    ?>
                    <label class="fcrud-checkbox">
                        <input type="checkbox"
                               id="fcrud-<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($field_id); ?>"
                               value="1"
                               <?php checked($value, '1'); ?>>
                        <span><?php echo esc_html($field['checkbox_label'] ?? $label); ?></span>
                    </label>
                    <?php
                    break;

                // Selects dinámicos para recursos relacionados
                case 'select_recurso':
                case 'select_bicicleta':
                case 'select_parcela':
                case 'select_libro':
                case 'select_curso':
                case 'select_taller':
                case 'select_grupo':
                case 'select_espacio':
                case 'select_punto':
                    $resource_type = str_replace('select_', '', $type);
                    $options = $this->get_resource_options($resource_type, $module_id);
                    ?>
                    <select id="fcrud-<?php echo esc_attr($field_id); ?>"
                            name="<?php echo esc_attr($field_id); ?>"
                            class="fcrud-select-resource"
                            data-resource="<?php echo esc_attr($resource_type); ?>"
                            <?php echo $required ? 'required' : ''; ?>>
                        <option value=""><?php esc_html_e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?php echo esc_attr($opt['id']); ?>" <?php selected($value, $opt['id']); ?>>
                                <?php echo esc_html($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;

                default:
                    ?>
                    <input type="text"
                           id="fcrud-<?php echo esc_attr($field_id); ?>"
                           name="<?php echo esc_attr($field_id); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php
            }

            if ($help): ?>
                <p class="fcrud-field-help"><?php echo esc_html($help); ?></p>
            <?php endif;
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza el listado de registros del usuario
     */
    public function render_list($module_id, $args = []) {
        $config = $this->get_module_config($module_id);

        if (!$config) {
            return '<div class="fcrud-error">' . __('Módulo no configurado para CRUD', 'flavor-chat-ia') . '</div>';
        }

        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        // Verificar que la tabla existe
        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];
        $info_tabla = $this->verificar_tabla($tabla);

        if (!$info_tabla['existe']) {
            return '<div class="fcrud-empty">
                <span class="dashicons ' . esc_attr($config['icono']) . '"></span>
                <p>' . sprintf(__('El módulo %s no tiene datos configurados todavía.', 'flavor-chat-ia'), $config['titulo_plural']) . '</p>
                <p class="fcrud-help">' . __('Contacta con el administrador para activar este módulo.', 'flavor-chat-ia') . '</p>
            </div>';
        }

        $defaults = [
            'limite' => 10,
            'pagina' => 1,
            'solo_mios' => true,
            'mostrar_crear' => true,
            'mostrar_filtros' => true,
        ];

        $args = wp_parse_args($args, $defaults);
        $items = $this->get_user_items($module_id, $args);
        $total = $this->count_user_items($module_id, $args);
        $total_pages = ceil($total / $args['limite']);

        $color = $config['color'] ?? '#4f46e5';
        $list_id = 'fcrud-list-' . wp_unique_id();

        ob_start();
        ?>
        <div class="fcrud-list-container" style="--fcrud-color: <?php echo esc_attr($color); ?>;" data-module="<?php echo esc_attr($module_id); ?>">

            <!-- Header -->
            <div class="fcrud-list-header">
                <div class="fcrud-list-title">
                    <span class="dashicons <?php echo esc_attr($config['icono']); ?>"></span>
                    <h3><?php printf(__('Mis %s', 'flavor-chat-ia'), $config['titulo_plural']); ?></h3>
                    <span class="fcrud-list-count">(<?php echo number_format_i18n($total); ?>)</span>
                </div>

                <?php if ($args['mostrar_crear']): ?>
                <button type="button" class="fcrud-btn fcrud-btn-primary fcrud-create-new">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php printf(__('Nuevo/a %s', 'flavor-chat-ia'), $config['titulo_singular']); ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Filtros -->
            <?php if ($args['mostrar_filtros'] && !empty($config['filtros'])): ?>
            <div class="fcrud-list-filters">
                <?php foreach ($config['filtros'] as $filtro): ?>
                    <?php $this->render_filter($filtro, $module_id); ?>
                <?php endforeach; ?>
                <button type="button" class="fcrud-btn fcrud-btn-secondary fcrud-filter-apply">
                    <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- Lista -->
            <div class="fcrud-list-items" id="<?php echo esc_attr($list_id); ?>">
                <?php if (empty($items)): ?>
                    <div class="fcrud-empty">
                        <span class="dashicons <?php echo esc_attr($config['icono']); ?>"></span>
                        <p><?php printf(__('No tienes %s todavía', 'flavor-chat-ia'), strtolower($config['titulo_plural'])); ?></p>
                        <?php if ($args['mostrar_crear']): ?>
                        <button type="button" class="fcrud-btn fcrud-btn-primary fcrud-create-new">
                            <?php printf(__('Crear mi primer %s', 'flavor-chat-ia'), strtolower($config['titulo_singular'])); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="fcrud-items-grid">
                        <?php foreach ($items as $item): ?>
                            <?php $this->render_list_item($item, $config, $module_id); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="fcrud-pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button type="button" class="fcrud-page <?php echo $i === (int)$args['pagina'] ? 'active' : ''; ?>" data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Modal para formulario -->
        <div class="fcrud-modal" id="fcrud-modal-<?php echo esc_attr($module_id); ?>" style="display:none;">
            <div class="fcrud-modal-overlay"></div>
            <div class="fcrud-modal-content">
                <button type="button" class="fcrud-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                <div class="fcrud-modal-body"></div>
            </div>
        </div>

        <?php
        $this->enqueue_crud_assets();

        return ob_get_clean();
    }

    /**
     * Renderiza un item del listado
     */
    private function render_list_item($item, $config, $module_id) {
        $campos_listado = $config['campos_listado'] ?? array_keys($config['campos']);
        $estado = $item['estado'] ?? '';
        $estado_class = $this->get_estado_class($estado);

        ?>
        <div class="fcrud-item" data-id="<?php echo esc_attr($item['id']); ?>">
            <div class="fcrud-item-content">
                <?php
                $first = true;
                foreach ($campos_listado as $campo):
                    $valor = $item[$campo] ?? '';
                    $label = $config['campos'][$campo]['label'] ?? ucfirst($campo);

                    if ($campo === 'estado' && $valor): ?>
                        <span class="fcrud-item-status <?php echo esc_attr($estado_class); ?>">
                            <?php echo esc_html(ucfirst($valor)); ?>
                        </span>
                    <?php elseif ($first): ?>
                        <h4 class="fcrud-item-title"><?php echo esc_html($valor ?: __('Sin título', 'flavor-chat-ia')); ?></h4>
                        <?php $first = false; ?>
                    <?php else: ?>
                        <div class="fcrud-item-field">
                            <span class="fcrud-item-label"><?php echo esc_html($label); ?>:</span>
                            <span class="fcrud-item-value"><?php echo esc_html($this->format_field_value($valor, $config['campos'][$campo] ?? [])); ?></span>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>

            <div class="fcrud-item-actions">
                <button type="button" class="fcrud-action-view" title="<?php esc_attr_e('Ver', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </button>
                <?php if ($this->can_edit($module_id, $item)): ?>
                <button type="button" class="fcrud-action-edit" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <?php endif; ?>
                <?php if ($this->can_delete($module_id, $item)): ?>
                <button type="button" class="fcrud-action-delete" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza un filtro
     */
    private function render_filter($filtro, $module_id) {
        $config = $this->get_module_config($module_id);
        $campo = $config['campos'][$filtro] ?? null;

        if ($filtro === 'estado') {
            $estados = $config['estados'] ?? ['pendiente', 'confirmado', 'completado', 'cancelado'];
            ?>
            <select name="filter_estado" class="fcrud-filter-select">
                <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?php echo esc_attr($estado); ?>"><?php echo esc_html(ucfirst($estado)); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        } elseif ($campo && $campo['tipo'] === 'select') {
            ?>
            <select name="filter_<?php echo esc_attr($filtro); ?>" class="fcrud-filter-select">
                <option value=""><?php printf(__('Todos: %s', 'flavor-chat-ia'), $campo['label']); ?></option>
                <?php foreach ($campo['options'] as $val => $label): ?>
                    <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        } elseif ($filtro === 'fecha' || ($campo && $campo['tipo'] === 'date')) {
            ?>
            <input type="date" name="filter_fecha_desde" class="fcrud-filter-date" placeholder="<?php esc_attr_e('Desde', 'flavor-chat-ia'); ?>">
            <input type="date" name="filter_fecha_hasta" class="fcrud-filter-date" placeholder="<?php esc_attr_e('Hasta', 'flavor-chat-ia'); ?>">
            <?php
        }
    }

    // =====================================================
    // AJAX Handlers
    // =====================================================

    /**
     * AJAX: Crear registro
     */
    public function ajax_create() {
        check_ajax_referer('flavor_crud_' . sanitize_key($_POST['module']), 'fcrud_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $module_id = sanitize_key($_POST['module']);
        $config = $this->get_module_config($module_id);

        if (!$config) {
            wp_send_json_error(['message' => __('Módulo no válido', 'flavor-chat-ia')]);
        }

        // Validar y sanitizar campos
        $data = $this->sanitize_form_data($_POST, $config['campos']);

        // Añadir campos automáticos
        $campo_usuario = $config['campo_usuario'] ?? 'user_id';
        $data[$campo_usuario] = get_current_user_id();
        $data['fecha_creacion'] = current_time('mysql');
        $data['estado'] = $config['estado_inicial'] ?? 'pendiente';

        // Campos especiales por módulo
        global $wpdb;
        if ($module_id === 'presupuestos_participativos' || $module_id === 'presupuestos-participativos') {
            // Obtener edición activa
            $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
            $edicion = $wpdb->get_row("SELECT id FROM {$tabla_ediciones} WHERE fase IN ('propuestas', 'activo') ORDER BY anio DESC LIMIT 1");
            if ($edicion) {
                $data['edicion_id'] = $edicion->id;
            } else {
                wp_send_json_error(['message' => __('No hay una edición activa de presupuestos participativos', 'flavor-chat-ia')]);
            }
        }

        // Hook para que módulos añadan campos adicionales
        $data = apply_filters('flavor_crud_before_create', $data, $module_id, $config);

        // Insertar en base de datos
        $tabla = $wpdb->prefix . $config['tabla'];

        $result = $wpdb->insert($tabla, $data);

        if ($result === false) {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-chat-ia')]);
        }

        $new_id = $wpdb->insert_id;

        // Hook para acciones post-creación
        do_action('flavor_crud_after_create', $module_id, $new_id, $data);

        wp_send_json_success([
            'message' => sprintf(__('%s creado/a correctamente', 'flavor-chat-ia'), $config['titulo_singular']),
            'id' => $new_id,
        ]);
    }

    /**
     * AJAX: Actualizar registro
     */
    public function ajax_update() {
        check_ajax_referer('flavor_crud_' . sanitize_key($_POST['module']), 'fcrud_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $module_id = sanitize_key($_POST['module']);
        $item_id = absint($_POST['item_id']);
        $config = $this->get_module_config($module_id);

        if (!$config || !$item_id) {
            wp_send_json_error(['message' => __('Datos no válidos', 'flavor-chat-ia')]);
        }

        // Verificar permisos
        $item = $this->get_item($module_id, $item_id);
        if (!$this->can_edit($module_id, $item)) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        // Sanitizar datos
        $data = $this->sanitize_form_data($_POST, $config['campos']);
        $data['fecha_modificacion'] = current_time('mysql');

        // Actualizar
        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];

        $result = $wpdb->update($tabla, $data, ['id' => $item_id]);

        if ($result === false) {
            wp_send_json_error(['message' => __('Error al actualizar', 'flavor-chat-ia')]);
        }

        do_action('flavor_crud_after_update', $module_id, $item_id, $data);

        wp_send_json_success([
            'message' => sprintf(__('%s actualizado/a correctamente', 'flavor-chat-ia'), $config['titulo_singular']),
        ]);
    }

    /**
     * AJAX: Eliminar registro
     */
    public function ajax_delete() {
        check_ajax_referer('flavor_crud_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $module_id = sanitize_key($_POST['module']);
        $item_id = absint($_POST['item_id']);
        $config = $this->get_module_config($module_id);

        if (!$config || !$item_id) {
            wp_send_json_error(['message' => __('Datos no válidos', 'flavor-chat-ia')]);
        }

        // Verificar permisos
        $item = $this->get_item($module_id, $item_id);
        if (!$this->can_delete($module_id, $item)) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];

        // Soft delete (cambiar estado) o hard delete según configuración
        if (isset($config['soft_delete']) && $config['soft_delete']) {
            $result = $wpdb->update($tabla, ['estado' => 'eliminado', 'fecha_eliminacion' => current_time('mysql')], ['id' => $item_id]);
        } else {
            $result = $wpdb->delete($tabla, ['id' => $item_id]);
        }

        if ($result === false) {
            wp_send_json_error(['message' => __('Error al eliminar', 'flavor-chat-ia')]);
        }

        do_action('flavor_crud_after_delete', $module_id, $item_id);

        wp_send_json_success([
            'message' => sprintf(__('%s eliminado/a correctamente', 'flavor-chat-ia'), $config['titulo_singular']),
        ]);
    }

    /**
     * AJAX: Obtener un registro
     */
    public function ajax_get() {
        check_ajax_referer('flavor_crud_action', 'nonce');

        $module_id = sanitize_key($_POST['module']);
        $item_id = absint($_POST['item_id']);

        $item = $this->get_item($module_id, $item_id);

        if (!$item) {
            wp_send_json_error(['message' => __('No encontrado', 'flavor-chat-ia')]);
        }

        // Verificar permisos de lectura
        if (!$this->can_view($module_id, $item)) {
            wp_send_json_error(['message' => __('No tienes permiso', 'flavor-chat-ia')]);
        }

        wp_send_json_success(['item' => $item]);
    }

    /**
     * AJAX: Obtener formulario
     */
    public function ajax_get_form() {
        check_ajax_referer('flavor_crud_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $module_id = sanitize_key($_POST['module']);
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

        $html = $this->render_form($module_id, $item_id);

        wp_send_json_success(['html' => $html]);
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * Obtiene un item
     */
    public function get_item($module_id, $item_id) {
        $config = $this->get_module_config($module_id);
        if (!$config) return null;

        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla} WHERE id = %d", $item_id), ARRAY_A);
    }

    /**
     * Verifica si una tabla existe y tiene las columnas necesarias
     */
    private function verificar_tabla($tabla) {
        global $wpdb;

        // Verificar si la tabla existe
        $existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla));
        if (!$existe) {
            return ['existe' => false, 'mensaje' => __('La tabla no existe. El módulo necesita ser configurado.', 'flavor-chat-ia')];
        }

        // Obtener columnas
        $columnas = $wpdb->get_col("DESCRIBE {$tabla}");

        return [
            'existe' => true,
            'columnas' => $columnas,
            'tiene_user_id' => in_array('user_id', $columnas),
            'tiene_estado' => in_array('estado', $columnas),
        ];
    }

    /**
     * Obtiene items del usuario
     */
    public function get_user_items($module_id, $args = []) {
        $config = $this->get_module_config($module_id);
        if (!$config) return [];

        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];

        // Verificar tabla
        $info_tabla = $this->verificar_tabla($tabla);
        if (!$info_tabla['existe']) {
            return [];
        }

        $user_id = get_current_user_id();
        $limite = absint($args['limite'] ?? 10);
        $offset = (absint($args['pagina'] ?? 1) - 1) * $limite;
        $campo_usuario = $config['campo_usuario'] ?? 'user_id';

        // Construir WHERE según columnas disponibles
        $where_parts = [];

        if (!empty($args['solo_mios']) && in_array($campo_usuario, $info_tabla['columnas'])) {
            $where_parts[] = $wpdb->prepare("{$campo_usuario} = %d", $user_id);
        }

        if (!empty($args['estado']) && $info_tabla['tiene_estado']) {
            $where_parts[] = $wpdb->prepare("estado = %s", $args['estado']);
        }

        $where = !empty($where_parts) ? "WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT * FROM {$tabla} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limite, $offset), ARRAY_A);
    }

    /**
     * Cuenta items del usuario
     */
    public function count_user_items($module_id, $args = []) {
        $config = $this->get_module_config($module_id);
        if (!$config) return 0;

        global $wpdb;
        $tabla = $wpdb->prefix . $config['tabla'];

        // Verificar tabla
        $info_tabla = $this->verificar_tabla($tabla);
        if (!$info_tabla['existe']) {
            return 0;
        }

        $user_id = get_current_user_id();
        $campo_usuario = $config['campo_usuario'] ?? 'user_id';

        // Construir WHERE según columnas disponibles
        $where_parts = [];

        if (!empty($args['solo_mios']) && in_array($campo_usuario, $info_tabla['columnas'])) {
            $where_parts[] = $wpdb->prepare("{$campo_usuario} = %d", $user_id);
        }

        $where = !empty($where_parts) ? "WHERE " . implode(" AND ", $where_parts) : "";

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} {$where}");
    }

    /**
     * Verifica si el usuario puede editar
     */
    public function can_edit($module_id, $item) {
        if (!is_user_logged_in()) return false;

        $user_id = get_current_user_id();

        // Admin puede todo
        if (current_user_can('manage_options')) return true;

        // Obtener campo de usuario del config
        $config = $this->get_module_config($module_id);
        $campo_usuario = $config['campo_usuario'] ?? 'user_id';

        // Propietario puede editar
        $item_user_id = $item[$campo_usuario] ?? ($item['user_id'] ?? null);
        if ($item_user_id !== null && (int)$item_user_id === $user_id) {
            // Solo si no está en estado final
            $estados_finales = ['completado', 'cancelado', 'cerrado', 'ejecutado', 'rechazado'];
            if (isset($item['estado']) && in_array($item['estado'], $estados_finales)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Verifica si el usuario puede eliminar
     */
    public function can_delete($module_id, $item) {
        if (!is_user_logged_in()) return false;

        // Admin puede todo
        if (current_user_can('manage_options')) return true;

        // Propietario puede eliminar solo si está pendiente
        if (isset($item['user_id']) && (int)$item['user_id'] === get_current_user_id()) {
            $estados_eliminables = ['pendiente', 'borrador'];
            return isset($item['estado']) && in_array($item['estado'], $estados_eliminables);
        }

        return false;
    }

    /**
     * Verifica si el usuario puede ver
     */
    public function can_view($module_id, $item) {
        if (!is_user_logged_in()) return false;

        // Admin puede todo
        if (current_user_can('manage_options')) return true;

        // Propietario puede ver
        if (isset($item['user_id']) && (int)$item['user_id'] === get_current_user_id()) {
            return true;
        }

        return false;
    }

    /**
     * Sanitiza datos del formulario
     */
    private function sanitize_form_data($post_data, $campos) {
        $data = [];

        foreach ($campos as $field_id => $field) {
            if (!isset($post_data[$field_id])) continue;

            $value = $post_data[$field_id];
            $tipo = $field['tipo'] ?? 'text';

            switch ($tipo) {
                case 'text':
                case 'email':
                case 'url':
                case 'tel':
                case 'location':
                    $data[$field_id] = sanitize_text_field($value);
                    break;

                case 'textarea':
                    $data[$field_id] = sanitize_textarea_field($value);
                    break;

                case 'wysiwyg':
                    $data[$field_id] = wp_kses_post($value);
                    break;

                case 'number':
                case 'price':
                    $data[$field_id] = floatval($value);
                    break;

                case 'date':
                case 'time':
                case 'datetime':
                    $data[$field_id] = sanitize_text_field($value);
                    break;

                case 'select':
                case 'checkbox':
                    $data[$field_id] = sanitize_key($value);
                    break;

                case 'image':
                case 'gallery':
                    $data[$field_id] = esc_url_raw($value);
                    break;

                default:
                    $data[$field_id] = sanitize_text_field($value);
            }
        }

        return $data;
    }

    /**
     * Formatea valor de campo para mostrar
     */
    private function format_field_value($value, $field) {
        if (empty($value)) return '-';

        $tipo = $field['tipo'] ?? 'text';

        switch ($tipo) {
            case 'price':
                return number_format_i18n(floatval($value), 2) . ' €';

            case 'date':
                return date_i18n(get_option('date_format'), strtotime($value));

            case 'datetime':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($value));

            case 'select':
                return $field['options'][$value] ?? $value;

            default:
                return $value;
        }
    }

    /**
     * Obtiene opciones de recursos relacionados
     */
    private function get_resource_options($resource_type, $module_id) {
        global $wpdb;
        $options = [];

        // Mapeo de recursos a tablas
        $resource_tables = [
            'recurso' => 'flavor_recursos',
            'bicicleta' => 'flavor_bicicletas_catalogo',
            'parcela' => 'flavor_huertos_parcelas',
            'libro' => 'flavor_biblioteca',
            'curso' => 'flavor_cursos',
            'taller' => 'flavor_talleres',
            'grupo' => 'flavor_grupos_consumo',
            'espacio' => 'flavor_espacios',
            'punto' => 'flavor_bicicletas_puntos',
        ];

        $tabla = $resource_tables[$resource_type] ?? null;

        if ($tabla) {
            $tabla_full = $wpdb->prefix . $tabla;
            $existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_full}'");

            if ($existe) {
                $col_nombre = $wpdb->get_var("SHOW COLUMNS FROM {$tabla_full} LIKE 'nombre'") ? 'nombre' :
                             ($wpdb->get_var("SHOW COLUMNS FROM {$tabla_full} LIKE 'titulo'") ? 'titulo' : 'id');

                $results = $wpdb->get_results("SELECT id, {$col_nombre} as label FROM {$tabla_full} WHERE 1=1 ORDER BY {$col_nombre}");

                foreach ($results as $row) {
                    $options[] = ['id' => $row->id, 'label' => $row->label];
                }
            }
        }

        return $options;
    }

    /**
     * Obtiene clase CSS para estado
     */
    private function get_estado_class($estado) {
        $clases = [
            'pendiente' => 'status-warning',
            'confirmado' => 'status-info',
            'confirmada' => 'status-info',
            'activo' => 'status-success',
            'completado' => 'status-success',
            'completada' => 'status-success',
            'cancelado' => 'status-danger',
            'cancelada' => 'status-danger',
            'rechazado' => 'status-danger',
            'rechazada' => 'status-danger',
        ];

        return $clases[$estado] ?? 'status-default';
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="fcrud-login-required">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php esc_html_e('Acceso restringido', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Debes iniciar sesión para acceder a esta sección.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="fcrud-btn fcrud-btn-primary">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // =====================================================
    // Shortcodes
    // =====================================================

    /**
     * Shortcode [flavor_crud_form]
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'id' => 0,
        ], $atts);

        return $this->render_form($atts['module'], absint($atts['id']));
    }

    /**
     * Shortcode [flavor_crud_list]
     */
    public function render_list_shortcode($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'limite' => 10,
            'solo_mios' => true,
        ], $atts);

        return $this->render_list($atts['module'], $atts);
    }

    /**
     * Shortcode [flavor_mis_registros]
     */
    public function render_my_records_shortcode($atts) {
        $atts = shortcode_atts([
            'module' => '',
        ], $atts);

        return $this->render_list($atts['module'], ['solo_mios' => true]);
    }

    /**
     * Encola assets del CRUD
     */
    private function enqueue_crud_assets() {
        static $enqueued = false;

        if ($enqueued) return;
        $enqueued = true;

        wp_enqueue_style('dashicons');
        wp_enqueue_media();

        // CSS inline
        wp_register_style('flavor-crud', false);
        wp_enqueue_style('flavor-crud');
        wp_add_inline_style('flavor-crud', $this->get_crud_styles());

        // JS inline
        wp_register_script('flavor-crud', false, ['jquery'], FLAVOR_CHAT_IA_VERSION, true);
        wp_enqueue_script('flavor-crud');
        wp_add_inline_script('flavor-crud', $this->get_crud_scripts());

        wp_localize_script('flavor-crud', 'flavorCRUD', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_crud_action'),
            'strings' => [
                'confirm_delete' => __('¿Estás seguro de que quieres eliminar este elemento?', 'flavor-chat-ia'),
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Estilos CSS del CRUD
     */
    private function get_crud_styles() {
        return '
        /* Variables */
        :root {
            --fcrud-color: #4f46e5;
            --fcrud-success: #10b981;
            --fcrud-warning: #f59e0b;
            --fcrud-danger: #ef4444;
            --fcrud-info: #3b82f6;
            --fcrud-bg: #f8fafc;
            --fcrud-surface: #ffffff;
            --fcrud-border: #e5e7eb;
            --fcrud-text: #111827;
            --fcrud-text-muted: #6b7280;
            --fcrud-radius: 12px;
        }

        /* Form Container */
        .fcrud-form-container {
            background: var(--fcrud-surface);
            border-radius: var(--fcrud-radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .fcrud-form-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--fcrud-border);
        }

        .fcrud-form-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
        }

        .fcrud-form-header .dashicons {
            color: var(--fcrud-color);
        }

        /* Form Fields */
        .fcrud-form-fields {
            display: grid;
            gap: 20px;
        }

        .fcrud-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fcrud-field label {
            font-weight: 500;
            font-size: 0.9375rem;
            color: var(--fcrud-text);
        }

        .fcrud-field label .required {
            color: var(--fcrud-danger);
            margin-left: 2px;
        }

        .fcrud-field input:not([type="checkbox"]),
        .fcrud-field textarea,
        .fcrud-field select {
            padding: 12px 16px;
            border: 1px solid var(--fcrud-border);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .fcrud-field input:focus,
        .fcrud-field textarea:focus,
        .fcrud-field select:focus {
            outline: none;
            border-color: var(--fcrud-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .fcrud-field-help {
            font-size: 0.8125rem;
            color: var(--fcrud-text-muted);
            margin: 4px 0 0;
        }

        /* Price Input */
        .fcrud-price-input {
            position: relative;
            display: flex;
        }

        .fcrud-price-input input {
            flex: 1;
            padding-right: 40px !important;
        }

        .fcrud-price-currency {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--fcrud-text-muted);
            font-weight: 500;
        }

        /* Location Field */
        .fcrud-location-field {
            display: flex;
            gap: 8px;
        }

        .fcrud-location-input {
            flex: 1;
        }

        .fcrud-location-detect {
            padding: 12px;
            border: 1px solid var(--fcrud-border);
            border-radius: 8px;
            background: var(--fcrud-bg);
            cursor: pointer;
            transition: all 0.2s;
        }

        .fcrud-location-detect:hover {
            background: var(--fcrud-color);
            border-color: var(--fcrud-color);
            color: white;
        }

        /* Image Upload */
        .fcrud-image-upload,
        .fcrud-gallery-upload {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fcrud-image-preview {
            position: relative;
            display: inline-block;
        }

        .fcrud-image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            object-fit: cover;
        }

        .fcrud-image-remove,
        .fcrud-gallery-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--fcrud-danger);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fcrud-gallery-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .fcrud-gallery-item {
            position: relative;
        }

        .fcrud-gallery-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Checkbox */
        .fcrud-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .fcrud-checkbox input {
            width: 20px;
            height: 20px;
            accent-color: var(--fcrud-color);
        }

        /* Form Actions */
        .fcrud-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--fcrud-border);
        }

        /* Buttons */
        .fcrud-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .fcrud-btn-primary {
            background: var(--fcrud-color);
            color: white;
        }

        .fcrud-btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .fcrud-btn-secondary {
            background: var(--fcrud-bg);
            color: var(--fcrud-text);
            border: 1px solid var(--fcrud-border);
        }

        .fcrud-btn-secondary:hover {
            background: var(--fcrud-surface);
            border-color: var(--fcrud-color);
        }

        .fcrud-btn-loading {
            display: none;
        }

        .fcrud-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: fcrud-spin 0.8s linear infinite;
        }

        @keyframes fcrud-spin {
            to { transform: rotate(360deg); }
        }

        /* Form Message */
        .fcrud-form-message {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9375rem;
        }

        .fcrud-form-message.success {
            background: #d1fae5;
            color: #065f46;
        }

        .fcrud-form-message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* List Container */
        .fcrud-list-container {
            background: var(--fcrud-surface);
            border-radius: var(--fcrud-radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .fcrud-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .fcrud-list-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fcrud-list-title .dashicons {
            color: var(--fcrud-color);
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .fcrud-list-title h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .fcrud-list-count {
            color: var(--fcrud-text-muted);
            font-size: 0.875rem;
        }

        /* Filters */
        .fcrud-list-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--fcrud-bg);
            border-radius: 8px;
        }

        .fcrud-filter-select,
        .fcrud-filter-date {
            padding: 10px 14px;
            border: 1px solid var(--fcrud-border);
            border-radius: 6px;
            font-size: 0.875rem;
            background: white;
        }

        /* Items Grid */
        .fcrud-items-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fcrud-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: var(--fcrud-bg);
            border-radius: 10px;
            transition: all 0.2s;
        }

        .fcrud-item:hover {
            background: #f1f5f9;
        }

        .fcrud-item-content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .fcrud-item-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .fcrud-item-field {
            font-size: 0.875rem;
            color: var(--fcrud-text-muted);
        }

        .fcrud-item-label {
            margin-right: 4px;
        }

        .fcrud-item-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-success { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-danger { background: #fee2e2; color: #991b1b; }
        .status-info { background: #dbeafe; color: #1e40af; }
        .status-default { background: #f3f4f6; color: #374151; }

        .fcrud-item-actions {
            display: flex;
            gap: 8px;
        }

        .fcrud-item-actions button {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: var(--fcrud-surface);
            color: var(--fcrud-text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fcrud-item-actions button:hover {
            background: var(--fcrud-color);
            color: white;
        }

        .fcrud-action-delete:hover {
            background: var(--fcrud-danger) !important;
        }

        /* Empty State */
        .fcrud-empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--fcrud-text-muted);
        }

        .fcrud-empty .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .fcrud-empty p {
            margin: 0 0 20px;
            font-size: 1rem;
        }

        /* Pagination */
        .fcrud-pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--fcrud-border);
        }

        .fcrud-page {
            width: 36px;
            height: 36px;
            border: 1px solid var(--fcrud-border);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .fcrud-page:hover,
        .fcrud-page.active {
            background: var(--fcrud-color);
            border-color: var(--fcrud-color);
            color: white;
        }

        /* Modal */
        .fcrud-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fcrud-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }

        .fcrud-modal-content {
            position: relative;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            background: var(--fcrud-surface);
            border-radius: var(--fcrud-radius);
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }

        .fcrud-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: var(--fcrud-bg);
            cursor: pointer;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fcrud-modal-close:hover {
            background: var(--fcrud-danger);
            color: white;
        }

        .fcrud-modal-body {
            padding: 24px;
        }

        /* Login Required */
        .fcrud-login-required {
            text-align: center;
            padding: 48px 20px;
            background: var(--fcrud-surface);
            border-radius: var(--fcrud-radius);
        }

        .fcrud-login-required .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: var(--fcrud-text-muted);
            margin-bottom: 16px;
        }

        .fcrud-login-required h3 {
            margin: 0 0 8px;
        }

        .fcrud-login-required p {
            color: var(--fcrud-text-muted);
            margin: 0 0 20px;
        }

        /* Error */
        .fcrud-error {
            padding: 20px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fcrud-list-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .fcrud-list-filters {
                flex-direction: column;
            }

            .fcrud-item {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .fcrud-item-actions {
                justify-content: flex-end;
            }

            .fcrud-form-actions {
                flex-direction: column;
            }

            .fcrud-btn {
                width: 100%;
            }
        }
        ';
    }

    /**
     * Scripts JS del CRUD
     */
    private function get_crud_scripts() {
        return '
        jQuery(document).ready(function($) {

            // Crear nuevo
            $(document).on("click", ".fcrud-create-new", function() {
                var $container = $(this).closest(".fcrud-list-container");
                var module = $container.data("module");
                var $modal = $("#fcrud-modal-" + module);

                // Cargar formulario vacío via AJAX
                $.post(flavorCRUD.ajax_url, {
                    action: "flavor_crud_get_form",
                    module: module,
                    nonce: flavorCRUD.nonce
                }, function(response) {
                    if (response.success) {
                        $modal.find(".fcrud-modal-body").html(response.data.html);
                        $modal.fadeIn(200);
                    }
                });
            });

            // Editar
            $(document).on("click", ".fcrud-action-edit", function() {
                var $item = $(this).closest(".fcrud-item");
                var itemId = $item.data("id");
                var $container = $(this).closest(".fcrud-list-container");
                var module = $container.data("module");
                var $modal = $("#fcrud-modal-" + module);

                $.post(flavorCRUD.ajax_url, {
                    action: "flavor_crud_get_form",
                    module: module,
                    item_id: itemId,
                    nonce: flavorCRUD.nonce
                }, function(response) {
                    if (response.success) {
                        $modal.find(".fcrud-modal-body").html(response.data.html);
                        $modal.fadeIn(200);
                    }
                });
            });

            // Eliminar
            $(document).on("click", ".fcrud-action-delete", function() {
                if (!confirm(flavorCRUD.strings.confirm_delete)) return;

                var $item = $(this).closest(".fcrud-item");
                var itemId = $item.data("id");
                var $container = $(this).closest(".fcrud-list-container");
                var module = $container.data("module");

                $.post(flavorCRUD.ajax_url, {
                    action: "flavor_crud_delete",
                    module: module,
                    item_id: itemId,
                    nonce: flavorCRUD.nonce
                }, function(response) {
                    if (response.success) {
                        $item.slideUp(300, function() { $(this).remove(); });
                    } else {
                        alert(response.data.message || flavorCRUD.strings.error);
                    }
                });
            });

            // Submit formulario
            $(document).on("submit", ".fcrud-form", function(e) {
                e.preventDefault();

                var $form = $(this);
                var $btn = $form.find("button[type=submit]");
                var $msg = $form.find(".fcrud-form-message");
                var module = $form.data("module");
                var itemId = $form.data("item-id");
                var action = itemId > 0 ? "flavor_crud_update" : "flavor_crud_create";

                // Mostrar loading
                $btn.find(".fcrud-btn-text").hide();
                $btn.find(".fcrud-btn-loading").show();
                $btn.prop("disabled", true);

                var formData = new FormData($form[0]);
                formData.append("action", action);
                formData.append("module", module);
                formData.append("item_id", itemId);

                $.ajax({
                    url: flavorCRUD.ajax_url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $msg.html(response.data.message).removeClass("error").addClass("success").show();
                            setTimeout(function() {
                                $form.closest(".fcrud-modal").fadeOut(200);
                                location.reload();
                            }, 1000);
                        } else {
                            $msg.html(response.data.message || flavorCRUD.strings.error).removeClass("success").addClass("error").show();
                        }
                    },
                    error: function() {
                        $msg.html(flavorCRUD.strings.error).removeClass("success").addClass("error").show();
                    },
                    complete: function() {
                        $btn.find(".fcrud-btn-text").show();
                        $btn.find(".fcrud-btn-loading").hide();
                        $btn.prop("disabled", false);
                    }
                });
            });

            // Cerrar modal
            $(document).on("click", ".fcrud-modal-close, .fcrud-modal-overlay, .fcrud-cancel", function() {
                $(this).closest(".fcrud-modal").fadeOut(200);
            });

            // Media uploader
            $(document).on("click", ".fcrud-image-select, .fcrud-gallery-add", function(e) {
                e.preventDefault();
                var $container = $(this).closest(".fcrud-image-upload, .fcrud-gallery-upload");
                var $input = $container.find("input[type=hidden]");
                var isGallery = $container.hasClass("fcrud-gallery-upload");

                var frame = wp.media({
                    title: "Seleccionar imagen",
                    multiple: isGallery,
                    library: { type: "image" }
                });

                frame.on("select", function() {
                    var attachments = frame.state().get("selection").toJSON();

                    if (isGallery) {
                        var urls = $input.val() ? $input.val().split(",") : [];
                        var $preview = $container.find(".fcrud-gallery-preview");

                        attachments.forEach(function(att) {
                            urls.push(att.url);
                            $preview.append(\'<div class="fcrud-gallery-item"><img src="\' + att.url + \'" alt=""><button type="button" class="fcrud-gallery-remove"><span class="dashicons dashicons-no-alt"></span></button></div>\');
                        });

                        $input.val(urls.join(","));
                    } else {
                        var url = attachments[0].url;
                        $input.val(url);
                        $container.find(".fcrud-image-preview").html(\'<img src="\' + url + \'" alt=""><button type="button" class="fcrud-image-remove"><span class="dashicons dashicons-no-alt"></span></button>\');
                    }
                });

                frame.open();
            });

            // Remove image
            $(document).on("click", ".fcrud-image-remove", function() {
                var $container = $(this).closest(".fcrud-image-upload");
                $container.find("input[type=hidden]").val("");
                $container.find(".fcrud-image-preview").empty();
            });

            // Remove gallery item
            $(document).on("click", ".fcrud-gallery-remove", function() {
                var $item = $(this).closest(".fcrud-gallery-item");
                var $container = $item.closest(".fcrud-gallery-upload");
                var $input = $container.find("input[type=hidden]");
                var url = $item.find("img").attr("src");
                var urls = $input.val().split(",").filter(function(u) { return u !== url; });

                $input.val(urls.join(","));
                $item.remove();
            });

            // Location detect
            $(document).on("click", ".fcrud-location-detect", function() {
                var $input = $(this).siblings(".fcrud-location-input");

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        $input.val(pos.coords.latitude + ", " + pos.coords.longitude);
                    });
                }
            });

            // Paginación
            $(document).on("click", ".fcrud-page", function() {
                var page = $(this).data("page");
                var $container = $(this).closest(".fcrud-list-container");
                // Implementar carga de página via AJAX
            });
        });
        ';
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Dynamic_CRUD::get_instance();
});
