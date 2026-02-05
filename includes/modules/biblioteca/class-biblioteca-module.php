<?php
/**
 * Módulo de Biblioteca Comunitaria para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Biblioteca - Sistema de préstamo de libros entre vecinos
 */
class Flavor_Chat_Biblioteca_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'biblioteca';
        $this->name = __('Biblioteca Comunitaria', 'flavor-chat-ia');
        $this->description = __('Sistema de préstamo e intercambio de libros entre vecinos de la comunidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        return Flavor_Chat_Helpers::tabla_existe($tabla_libros);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Biblioteca no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_donaciones' => true,
            'permite_intercambios' => true,
            'permite_prestamos' => true,
            'duracion_prestamo_dias' => 30,
            'renovaciones_maximas' => 2,
            'permite_reservas' => true,
            'sistema_puntos' => true,
            'puntos_por_prestamo' => 1,
            'requiere_verificacion_isbn' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        $sql_libros = "CREATE TABLE IF NOT EXISTS $tabla_libros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propietario_id bigint(20) unsigned NOT NULL,
            isbn varchar(20) DEFAULT NULL,
            titulo varchar(500) NOT NULL,
            autor varchar(255) NOT NULL,
            editorial varchar(255) DEFAULT NULL,
            ano_publicacion int(11) DEFAULT NULL,
            idioma varchar(50) DEFAULT 'Español',
            genero varchar(100) DEFAULT NULL,
            num_paginas int(11) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            portada_url varchar(500) DEFAULT NULL,
            estado_fisico enum('excelente','bueno','aceptable','desgastado') DEFAULT 'bueno',
            disponibilidad enum('disponible','prestado','reservado','no_disponible') DEFAULT 'disponible',
            tipo enum('donado','prestamo','intercambio') DEFAULT 'prestamo',
            ubicacion varchar(255) DEFAULT NULL COMMENT 'Casa del propietario o punto recogida',
            valoracion_media decimal(3,2) DEFAULT 0,
            veces_prestado int(11) DEFAULT 0,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propietario_id (propietario_id),
            KEY isbn (isbn),
            KEY disponibilidad (disponibilidad),
            KEY genero (genero),
            FULLTEXT KEY busqueda (titulo, autor, descripcion)
        ) $charset_collate;";

        $sql_prestamos = "CREATE TABLE IF NOT EXISTS $tabla_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            prestamista_id bigint(20) unsigned NOT NULL,
            prestatario_id bigint(20) unsigned NOT NULL,
            fecha_prestamo datetime NOT NULL,
            fecha_devolucion_prevista datetime NOT NULL,
            fecha_devolucion_real datetime DEFAULT NULL,
            renovaciones int(11) DEFAULT 0,
            estado enum('activo','devuelto','retrasado','perdido') DEFAULT 'activo',
            notas_prestamista text DEFAULT NULL,
            notas_prestatario text DEFAULT NULL,
            valoracion_libro int(11) DEFAULT NULL,
            valoracion_prestatario int(11) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY libro_id (libro_id),
            KEY prestatario_id (prestatario_id),
            KEY estado (estado),
            KEY fecha_devolucion_prevista (fecha_devolucion_prevista)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime NOT NULL,
            estado enum('pendiente','confirmada','cancelada','expirada') DEFAULT 'pendiente',
            notificado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY libro_id (libro_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_resenas = "CREATE TABLE IF NOT EXISTS $tabla_resenas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            valoracion int(11) NOT NULL,
            resena text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY libro_usuario (libro_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_libros);
        dbDelta($sql_prestamos);
        dbDelta($sql_reservas);
        dbDelta($sql_resenas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_libros' => [
                'description' => 'Buscar libros disponibles',
                'params' => ['query', 'genero', 'autor'],
            ],
            'detalle_libro' => [
                'description' => 'Ver detalles del libro',
                'params' => ['libro_id'],
            ],
            'solicitar_prestamo' => [
                'description' => 'Solicitar préstamo',
                'params' => ['libro_id'],
            ],
            'mis_libros' => [
                'description' => 'Mis libros en la biblioteca',
                'params' => [],
            ],
            'mis_prestamos' => [
                'description' => 'Libros que tengo prestados',
                'params' => [],
            ],
            'agregar_libro' => [
                'description' => 'Agregar libro a la biblioteca',
                'params' => ['titulo', 'autor', 'isbn', 'tipo'],
            ],
            'devolver_libro' => [
                'description' => 'Marcar libro como devuelto',
                'params' => ['prestamo_id'],
            ],
            'renovar_prestamo' => [
                'description' => 'Renovar préstamo',
                'params' => ['prestamo_id'],
            ],
            'reservar_libro' => [
                'description' => 'Reservar libro prestado',
                'params' => ['libro_id'],
            ],
            'valorar_libro' => [
                'description' => 'Valorar y reseñar libro',
                'params' => ['libro_id', 'valoracion', 'resena'],
            ],
            'recomendaciones' => [
                'description' => 'Libros recomendados',
                'params' => [],
            ],
            // Admin actions
            'estadisticas_biblioteca' => [
                'description' => 'Estadísticas de uso (admin)',
                'params' => ['periodo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Buscar libros
     */
    private function action_buscar_libros($params) {
        global $wpdb;
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $where = ["disponibilidad IN ('disponible', 'prestado')"];
        $prepare_values = [];

        if (!empty($params['query'])) {
            $where[] = 'MATCH(titulo, autor, descripcion) AGAINST(%s IN BOOLEAN MODE)';
            $prepare_values[] = sanitize_text_field($params['query']);
        }

        if (!empty($params['genero'])) {
            $where[] = 'genero = %s';
            $prepare_values[] = sanitize_text_field($params['genero']);
        }

        if (!empty($params['autor'])) {
            $where[] = 'autor LIKE %s';
            $prepare_values[] = '%' . $wpdb->esc_like(sanitize_text_field($params['autor'])) . '%';
        }

        $sql = "SELECT * FROM $tabla_libros WHERE " . implode(' AND ', $where) . " ORDER BY fecha_agregado DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $libros = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $libros = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'libros' => array_map(function($l) {
                $propietario = get_userdata($l->propietario_id);
                return [
                    'id' => $l->id,
                    'titulo' => $l->titulo,
                    'autor' => $l->autor,
                    'genero' => $l->genero,
                    'isbn' => $l->isbn,
                    'editorial' => $l->editorial,
                    'ano' => $l->ano_publicacion,
                    'descripcion' => wp_trim_words($l->descripcion, 50),
                    'portada' => $l->portada_url,
                    'disponibilidad' => $l->disponibilidad,
                    'tipo' => $l->tipo,
                    'propietario' => $propietario ? $propietario->display_name : 'Vecino',
                    'valoracion' => floatval($l->valoracion_media),
                    'veces_prestado' => $l->veces_prestado,
                ];
            }, $libros),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'biblioteca_buscar',
                'description' => 'Buscar libros en la biblioteca comunitaria',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Título, autor o tema'],
                        'genero' => ['type' => 'string', 'description' => 'Género literario'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_biblioteca' => [
                'label' => __('Hero Biblioteca', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Biblioteca Comunitaria', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Miles de libros compartidos entre vecinos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_buscador' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'biblioteca/hero',
            ],
            'libros_grid' => [
                'label' => __('Grid de Libros', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Libros Disponibles', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [3, 4, 5, 6], 'default' => 5],
                    'limite' => ['type' => 'number', 'default' => 12],
                    'genero' => ['type' => 'text', 'default' => ''],
                    'mostrar_propietario' => ['type' => 'toggle', 'default' => false],
                ],
                'template' => 'biblioteca/grid',
            ],
            'generos_nav' => [
                'label' => __('Navegación por Géneros', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Explora por Género', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'carrusel'], 'default' => 'grid'],
                ],
                'template' => 'biblioteca/generos',
            ],
            'stats_biblioteca' => [
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'mostrar_total_libros' => ['type' => 'toggle', 'default' => true],
                    'mostrar_prestamos' => ['type' => 'toggle', 'default' => true],
                    'mostrar_lectores' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'biblioteca/stats',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Biblioteca Comunitaria**

Comparte, presta e intercambia libros con tus vecinos.

**Cómo funciona:**
1. Agrega tus libros que quieras compartir
2. Busca libros que te interesen
3. Solicita préstamo al propietario
4. Acuerda punto de entrega
5. Lee y devuelve en el plazo acordado

**Tipos de libros:**
- Donados: Son de la comunidad, gratis
- Préstamo: Debes devolverlos
- Intercambio: Cambio por otro libro tuyo

**Géneros disponibles:**
- Novela, Ensayo, Poesía
- Ciencia ficción, Fantasía
- Historia, Biografía
- Técnico, Académico
- Infantil, Juvenil
- Y muchos más...

**Sistema de puntos:**
- Gana puntos prestando libros
- Usa puntos para solicitar préstamos
- Fomenta la reciprocidad

**Ventajas:**
- Acceso gratis a miles de libros
- Conoce gustos de tus vecinos
- Reduce consumo, reutiliza
- Crea comunidad lectora
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cuánto tiempo puedo tener un libro?',
                'respuesta' => 'Normalmente 30 días, pero puedes renovar hasta 2 veces si nadie lo ha reservado.',
            ],
            [
                'pregunta' => '¿Qué pasa si pierdo un libro?',
                'respuesta' => 'Debes reponerlo o acordar compensación con el propietario.',
            ],
            [
                'pregunta' => '¿Puedo donar libros?',
                'respuesta' => 'Sí, los libros donados pasan a ser de la comunidad y cualquiera puede tomarlos.',
            ],
        ];
    }
}
