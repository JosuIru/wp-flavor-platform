<?php
/**
 * Registros de navegación del Shell para módulos
 *
 * Registra las subpáginas y badges de cada módulo en el Shell Navigation Registry.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que registra las navegaciones de módulos en el shell
 */
class Flavor_Shell_Module_Registrations {

    /**
     * Instancia singleton
     *
     * @var Flavor_Shell_Module_Registrations|null
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Shell_Module_Registrations
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
        add_action('flavor_shell_register_navigation', [$this, 'register_all_modules']);
    }

    /**
     * Comprueba si una tabla existe.
     *
     * @param string $table Nombre completo de tabla.
     * @return bool
     */
    private function table_exists($table) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    /**
     * Devuelve la primera columna existente de una lista.
     *
     * @param string $table Nombre completo de tabla.
     * @param array  $candidates Columnas candidatas.
     * @return string|null
     */
    private function first_existing_column($table, array $candidates) {
        global $wpdb;

        foreach ($candidates as $column) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
            if ($exists) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Registrar navegaciones de todos los módulos
     *
     * @param Flavor_Shell_Navigation_Registry $registry Instancia del registry
     */
    public function register_all_modules($registry) {
        $this->register_eventos($registry);
        $this->register_tramites($registry);
        $this->register_incidencias($registry);
        $this->register_socios($registry);
        $this->register_marketplace($registry);
        $this->register_reservas($registry);
        $this->register_foros($registry);
        $this->register_participacion($registry);
        $this->register_huertos($registry);
        $this->register_comunidades($registry);
        $this->register_colectivos($registry);
        $this->register_banco_tiempo($registry);
        $this->register_biblioteca($registry);
        $this->register_cursos($registry);
        $this->register_talleres($registry);
        $this->register_radio($registry);
        $this->register_podcast($registry);
        $this->register_campanias($registry);
        $this->register_contabilidad($registry);
        $this->register_chat_ia($registry);
    }

    /**
     * Registrar módulo Eventos
     */
    private function register_eventos($registry) {
        $registry->register_module_subpages('eventos-dashboard', [
            [
                'slug' => 'eventos-proximos',
                'label' => __('Próximos', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar-alt',
            ],
            [
                'slug' => 'eventos-calendario',
                'label' => __('Calendario', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar',
            ],
            [
                'slug' => 'eventos-asistentes',
                'label' => __('Asistentes', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'eventos-nuevo',
                'label' => __('Nuevo evento', 'flavor-chat-ia'),
                'icon' => 'dashicons-plus-alt2',
            ],
            [
                'slug' => 'eventos-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: eventos esta semana
        $registry->register_badge_callback('eventos-dashboard', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_eventos';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            $ahora = current_time('mysql');
            $en_7_dias = gmdate('Y-m-d H:i:s', strtotime('+7 days', current_time('timestamp', true)));
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'publicado' AND fecha_inicio BETWEEN %s AND %s",
                $ahora,
                $en_7_dias
            ));
        }, 'info');

        // Badge: inscripciones pendientes
        $registry->register_badge_callback('eventos-asistentes', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_eventos_inscripciones';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'");
        }, 'warning');
    }

    /**
     * Registrar módulo Trámites
     */
    private function register_tramites($registry) {
        $registry->register_module_subpages('tramites-dashboard', [
            [
                'slug' => 'tramites-pendientes',
                'label' => __('Pendientes', 'flavor-chat-ia'),
                'icon' => 'dashicons-clock',
            ],
            [
                'slug' => 'tramites-historial',
                'label' => __('Historial', 'flavor-chat-ia'),
                'icon' => 'dashicons-backup',
            ],
            [
                'slug' => 'tramites-tipos',
                'label' => __('Tipos', 'flavor-chat-ia'),
                'icon' => 'dashicons-category',
            ],
            [
                'slug' => 'tramites-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: solicitudes en curso
        $registry->register_badge_callback('tramites-dashboard', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('pendiente', 'en_revision', 'en_proceso')"
            );
        }, 'warning');

        // Badge: urgentes
        $registry->register_badge_callback('tramites-pendientes', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_tramites_solicitudes';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE prioridad = 'alta' AND estado IN ('pendiente', 'en_revision', 'en_proceso')"
            );
        }, 'danger');
    }

    /**
     * Registrar módulo Incidencias
     */
    private function register_incidencias($registry) {
        $registry->register_module_subpages('incidencias-dashboard', [
            [
                'slug' => 'incidencias-abiertas',
                'label' => __('Abiertas', 'flavor-chat-ia'),
                'icon' => 'dashicons-warning',
            ],
            [
                'slug' => 'incidencias-todas',
                'label' => __('Todas', 'flavor-chat-ia'),
                'icon' => 'dashicons-list-view',
            ],
            [
                'slug' => 'incidencias-mapa',
                'label' => __('Mapa', 'flavor-chat-ia'),
                'icon' => 'dashicons-location-alt',
            ],
            [
                'slug' => 'incidencias-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: incidencias abiertas
        $registry->register_badge_callback('incidencias-dashboard', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_incidencias';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('pendiente', 'en_proceso')"
            );
        }, 'warning');

        // Badge: sin asignar
        $registry->register_badge_callback('incidencias-abiertas', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_incidencias';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente' AND asignado_a IS NULL"
            );
        }, 'danger');
    }

    /**
     * Registrar módulo Socios
     */
    private function register_socios($registry) {
        $registry->register_module_subpages('socios-dashboard', [
            [
                'slug' => 'socios-listado',
                'label' => __('Listado', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'socios-solicitudes',
                'label' => __('Solicitudes', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-users',
            ],
            [
                'slug' => 'socios-cuotas',
                'label' => __('Cuotas', 'flavor-chat-ia'),
                'icon' => 'dashicons-money-alt',
            ],
            [
                'slug' => 'socios-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: solicitudes pendientes
        $registry->register_badge_callback('socios-solicitudes', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_socios';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Marketplace
     */
    private function register_marketplace($registry) {
        $registry->register_module_subpages('marketplace-dashboard', [
            [
                'slug' => 'marketplace-anuncios',
                'label' => __('Anuncios', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
            ],
            [
                'slug' => 'marketplace-vendedores',
                'label' => __('Vendedores', 'flavor-chat-ia'),
                'icon' => 'dashicons-businessman',
            ],
            [
                'slug' => 'marketplace-ventas',
                'label' => __('Ventas', 'flavor-chat-ia'),
                'icon' => 'dashicons-cart',
            ],
            [
                'slug' => 'marketplace-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: anuncios pendientes de aprobar
        $registry->register_badge_callback('marketplace-anuncios', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Reservas
     */
    private function register_reservas($registry) {
        $registry->register_module_subpages('reservas-dashboard', [
            [
                'slug' => 'reservas-calendario',
                'label' => __('Calendario', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar',
            ],
            [
                'slug' => 'reservas-espacios',
                'label' => __('Espacios', 'flavor-chat-ia'),
                'icon' => 'dashicons-building',
            ],
            [
                'slug' => 'reservas-pendientes',
                'label' => __('Pendientes', 'flavor-chat-ia'),
                'icon' => 'dashicons-clock',
            ],
            [
                'slug' => 'reservas-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: reservas pendientes
        $registry->register_badge_callback('reservas-pendientes', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_reservas';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Foros
     */
    private function register_foros($registry) {
        $registry->register_module_subpages('foros-dashboard', [
            [
                'slug' => 'foros-temas',
                'label' => __('Temas', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-chat',
            ],
            [
                'slug' => 'foros-categorias',
                'label' => __('Categorías', 'flavor-chat-ia'),
                'icon' => 'dashicons-category',
            ],
            [
                'slug' => 'foros-moderacion',
                'label' => __('Moderación', 'flavor-chat-ia'),
                'icon' => 'dashicons-shield',
            ],
            [
                'slug' => 'foros-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: temas sin respuesta
        $registry->register_badge_callback('foros-temas', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_foros_temas';
            $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

            if ($this->table_exists($tabla)) {
                $col_respuestas = $this->first_existing_column($tabla, ['respuestas_count', 'respuestas']);
                $col_estado = $this->first_existing_column($tabla, ['estado', 'status']);

                if ($col_respuestas && $col_estado) {
                    return (int) $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$tabla} WHERE {$col_respuestas} = 0 AND {$col_estado} = 'abierto'"
                    );
                }
            }

            // Fallback a estructura nueva de foros_hilos.
            if ($this->table_exists($tabla_hilos)) {
                $col_respuestas = $this->first_existing_column($tabla_hilos, ['respuestas_count', 'respuestas']);
                $col_estado = $this->first_existing_column($tabla_hilos, ['estado', 'status']);

                if ($col_respuestas && $col_estado) {
                    return (int) $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$tabla_hilos} WHERE {$col_respuestas} = 0 AND {$col_estado} = 'abierto'"
                    );
                }
            }

            return 0;
        }, 'info');
    }

    /**
     * Registrar módulo Participación
     */
    private function register_participacion($registry) {
        $registry->register_module_subpages('participacion-dashboard', [
            [
                'slug' => 'participacion-propuestas',
                'label' => __('Propuestas', 'flavor-chat-ia'),
                'icon' => 'dashicons-lightbulb',
            ],
            [
                'slug' => 'participacion-votaciones',
                'label' => __('Votaciones', 'flavor-chat-ia'),
                'icon' => 'dashicons-yes',
            ],
            [
                'slug' => 'participacion-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: propuestas en votación
        $registry->register_badge_callback('participacion-votaciones', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_participacion_propuestas';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'votacion'"
            );
        }, 'info');
    }

    /**
     * Registrar módulo Huertos
     */
    private function register_huertos($registry) {
        $registry->register_module_subpages('huertos-dashboard', [
            [
                'slug' => 'huertos-parcelas',
                'label' => __('Parcelas', 'flavor-chat-ia'),
                'icon' => 'dashicons-layout',
            ],
            [
                'slug' => 'huertos-huertanos',
                'label' => __('Huertanos', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'huertos-cosechas',
                'label' => __('Cosechas', 'flavor-chat-ia'),
                'icon' => 'dashicons-carrot',
            ],
            [
                'slug' => 'huertos-recursos',
                'label' => __('Recursos', 'flavor-chat-ia'),
                'icon' => 'dashicons-archive',
            ],
            [
                'slug' => 'huertos-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: solicitudes de parcela
        $registry->register_badge_callback('huertos-parcelas', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_huertos_solicitudes';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Comunidades
     */
    private function register_comunidades($registry) {
        $registry->register_module_subpages('comunidades-dashboard', [
            [
                'slug' => 'comunidades-listado',
                'label' => __('Listado', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-multisite',
            ],
            [
                'slug' => 'comunidades-miembros',
                'label' => __('Miembros', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'comunidades-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Colectivos
     */
    private function register_colectivos($registry) {
        $registry->register_module_subpages('flavor-colectivos-dashboard', [
            [
                'slug' => 'colectivos-listado',
                'label' => __('Listado', 'flavor-chat-ia'),
                'icon' => 'dashicons-networking',
            ],
            [
                'slug' => 'colectivos-solicitudes',
                'label' => __('Solicitudes', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-users',
            ],
            [
                'slug' => 'colectivos-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: solicitudes de unión pendientes
        $registry->register_badge_callback('colectivos-solicitudes', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_colectivos_miembros';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) !== $tabla) {
                return 0;
            }
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'pendiente'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Banco de Tiempo
     */
    private function register_banco_tiempo($registry) {
        $registry->register_module_subpages('banco-tiempo-dashboard', [
            [
                'slug' => 'banco-tiempo-servicios',
                'label' => __('Servicios', 'flavor-chat-ia'),
                'icon' => 'dashicons-hammer',
            ],
            [
                'slug' => 'banco-tiempo-intercambios',
                'label' => __('Intercambios', 'flavor-chat-ia'),
                'icon' => 'dashicons-randomize',
            ],
            [
                'slug' => 'banco-tiempo-miembros',
                'label' => __('Miembros', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'banco-tiempo-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: intercambios pendientes
        $registry->register_badge_callback('banco-tiempo-intercambios', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_banco_tiempo_intercambios';
            if (!$this->table_exists($tabla)) {
                return 0;
            }

            $col_estado = $this->first_existing_column($tabla, ['estado', 'status']);
            if (!$col_estado) {
                return 0;
            }

            $valor_pendiente = ('status' === $col_estado) ? 'pending' : 'pendiente';
            return (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$tabla} WHERE {$col_estado} = %s", $valor_pendiente)
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Biblioteca
     */
    private function register_biblioteca($registry) {
        $registry->register_module_subpages('biblioteca-dashboard', [
            [
                'slug' => 'biblioteca-catalogo',
                'label' => __('Catálogo', 'flavor-chat-ia'),
                'icon' => 'dashicons-book-alt',
            ],
            [
                'slug' => 'biblioteca-prestamos',
                'label' => __('Préstamos', 'flavor-chat-ia'),
                'icon' => 'dashicons-migrate',
            ],
            [
                'slug' => 'biblioteca-reservas',
                'label' => __('Reservas', 'flavor-chat-ia'),
                'icon' => 'dashicons-calendar',
            ],
            [
                'slug' => 'biblioteca-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: préstamos vencidos
        $registry->register_badge_callback('biblioteca-prestamos', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_biblioteca_prestamos';
            if (!$this->table_exists($tabla)) {
                return 0;
            }

            $col_estado = $this->first_existing_column($tabla, ['estado', 'status']);
            $col_fecha = $this->first_existing_column($tabla, ['fecha_devolucion_prevista', 'fecha_devolucion', 'fecha_fin', 'due_date']);

            if (!$col_estado || !$col_fecha) {
                return 0;
            }

            $valor_activo = ('status' === $col_estado) ? 'active' : 'activo';
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE {$col_estado} = %s AND {$col_fecha} < %s",
                $valor_activo,
                current_time('Y-m-d')
            ));
        }, 'danger');
    }

    /**
     * Registrar módulo Cursos
     */
    private function register_cursos($registry) {
        $registry->register_module_subpages('cursos-dashboard', [
            [
                'slug' => 'cursos-listado',
                'label' => __('Listado', 'flavor-chat-ia'),
                'icon' => 'dashicons-welcome-learn-more',
            ],
            [
                'slug' => 'cursos-inscripciones',
                'label' => __('Inscripciones', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'cursos-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Talleres
     */
    private function register_talleres($registry) {
        $registry->register_module_subpages('talleres-dashboard', [
            [
                'slug' => 'talleres-listado',
                'label' => __('Listado', 'flavor-chat-ia'),
                'icon' => 'dashicons-hammer',
            ],
            [
                'slug' => 'talleres-inscripciones',
                'label' => __('Inscripciones', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
            ],
            [
                'slug' => 'talleres-materiales',
                'label' => __('Materiales', 'flavor-chat-ia'),
                'icon' => 'dashicons-archive',
            ],
            [
                'slug' => 'talleres-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Radio
     */
    private function register_radio($registry) {
        $registry->register_module_subpages('flavor-radio-dashboard', [
            [
                'slug' => 'radio-programas',
                'label' => __('Programas', 'flavor-chat-ia'),
                'icon' => 'dashicons-playlist-audio',
            ],
            [
                'slug' => 'radio-locutores',
                'label' => __('Locutores', 'flavor-chat-ia'),
                'icon' => 'dashicons-microphone',
            ],
            [
                'slug' => 'radio-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Podcast
     */
    private function register_podcast($registry) {
        $registry->register_module_subpages('podcast-dashboard', [
            [
                'slug' => 'podcast-episodios',
                'label' => __('Episodios', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-audio',
            ],
            [
                'slug' => 'podcast-series',
                'label' => __('Series', 'flavor-chat-ia'),
                'icon' => 'dashicons-playlist-audio',
            ],
            [
                'slug' => 'podcast-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Campañas
     */
    private function register_campanias($registry) {
        $registry->register_module_subpages('campanias-dashboard', [
            [
                'slug' => 'campanias-listado',
                'label' => __('Campañas', 'flavor-chat-ia'),
                'icon' => 'dashicons-megaphone',
            ],
            [
                'slug' => 'campanias-estadisticas',
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'icon' => 'dashicons-chart-bar',
            ],
            [
                'slug' => 'campanias-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);
    }

    /**
     * Registrar módulo Contabilidad
     */
    private function register_contabilidad($registry) {
        $registry->register_module_subpages('contabilidad-dashboard', [
            [
                'slug' => 'contabilidad-dashboard',
                'label' => __('Resumen', 'flavor-chat-ia'),
                'icon' => 'dashicons-chart-pie',
            ],
            [
                'slug' => 'contabilidad-movimientos',
                'label' => __('Movimientos', 'flavor-chat-ia'),
                'icon' => 'dashicons-list-view',
            ],
            [
                'slug' => 'contabilidad-config',
                'label' => __('Configuración', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-settings',
            ],
        ]);

        // Badge: asientos en borrador pendientes de confirmar.
        $registry->register_badge_callback('contabilidad-dashboard', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_contabilidad_movimientos';
            if (!$this->table_exists($tabla)) {
                return 0;
            }

            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'borrador'"
            );
        }, 'warning');
    }

    /**
     * Registrar módulo Chat IA
     */
    private function register_chat_ia($registry) {
        $registry->register_module_subpages('flavor-chat-config', [
            [
                'slug' => 'flavor-chat-ia-escalations',
                'label' => __('Escalados', 'flavor-chat-ia'),
                'icon' => 'dashicons-warning',
            ],
            [
                'slug' => 'flavor-chat-ia-analytics',
                'label' => __('Analíticas', 'flavor-chat-ia'),
                'icon' => 'dashicons-chart-area',
            ],
        ]);

        // Badge: escalados pendientes
        $registry->register_badge_callback('flavor-chat-ia-escalations', function() {
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_chat_escalations';
            if (!$this->table_exists($tabla)) {
                return 0;
            }

            $col_estado = $this->first_existing_column($tabla, ['estado', 'status']);
            if (!$col_estado) {
                return 0;
            }

            $valor_pendiente = ('status' === $col_estado) ? 'pending' : 'pendiente';
            return (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$tabla} WHERE {$col_estado} = %s", $valor_pendiente)
            );
        }, 'danger');
    }
}

// Inicializar
Flavor_Shell_Module_Registrations::get_instance();
