<?php
/**
 * Configuracion Central de Integraciones entre Modulos
 *
 * Este archivo define que modulos pueden relacionarse entre si.
 * Se usa cuando los modulos no tienen los traits de integracion implementados
 * pero queremos habilitar las relaciones de forma centralizada.
 *
 * NOTA: Los modulos con traits implementados tienen prioridad sobre esta config.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar configuracion de integraciones
 */
add_action('init', 'flavor_register_integration_config', 5);

function flavor_register_integration_config() {

    /**
     * MODULOS POLIVALENTES (Proveedores)
     * Estos modulos ofrecen contenido que otros pueden vincular
     */
    $providers = [
        'recetas' => [
            'id'         => 'recetas',
            'label'      => __('Recetas', 'flavor-chat-ia'),
            'icon'       => 'dashicons-carrot',
            'post_type'  => 'flavor_receta',
            'capability' => 'edit_posts',
            'module_id'  => 'recetas',
        ],
        'multimedia' => [
            'id'         => 'multimedia',
            'label'      => __('Multimedia', 'flavor-chat-ia'),
            'icon'       => 'dashicons-format-gallery',
            'table'      => 'flavor_multimedia', // Usa tabla, no CPT
            'capability' => 'upload_files',
            'module_id'  => 'multimedia',
        ],
        'podcast' => [
            'id'         => 'podcast',
            'label'      => __('Episodios de Podcast', 'flavor-chat-ia'),
            'icon'       => 'dashicons-microphone',
            'table'      => 'flavor_podcast_episodios',
            'capability' => 'edit_posts',
            'module_id'  => 'podcast',
        ],
        'biblioteca' => [
            'id'         => 'biblioteca',
            'label'      => __('Recursos de Biblioteca', 'flavor-chat-ia'),
            'icon'       => 'dashicons-book',
            'table'      => 'flavor_biblioteca_libros',
            'capability' => 'edit_posts',
            'module_id'  => 'biblioteca',
        ],
        'videos' => [
            'id'         => 'videos',
            'label'      => __('Videos', 'flavor-chat-ia'),
            'icon'       => 'dashicons-video-alt3',
            'post_type'  => 'flavor_video',
            'capability' => 'upload_files',
            'module_id'  => 'multimedia',
        ],
        'articulos_social' => [
            'id'         => 'articulos_social',
            'label'      => __('Publicaciones', 'flavor-chat-ia'),
            'icon'       => 'dashicons-share',
            'table'      => 'flavor_social_publicaciones',
            'capability' => 'edit_posts',
            'module_id'  => 'red_social',
        ],
        'radio' => [
            'id'         => 'radio',
            'label'      => __('Programas de Radio', 'flavor-chat-ia'),
            'icon'       => 'dashicons-controls-volumeon',
            'table'      => 'flavor_radio_programas',
            'capability' => 'edit_posts',
            'module_id'  => 'radio',
        ],
        'eventos' => [
            'id'         => 'eventos',
            'label'      => __('Eventos', 'flavor-chat-ia'),
            'icon'       => 'dashicons-calendar-alt',
            'post_type'  => 'flavor_evento',
            'capability' => 'edit_posts',
            'module_id'  => 'eventos',
        ],
        'cursos' => [
            'id'         => 'cursos',
            'label'      => __('Cursos', 'flavor-chat-ia'),
            'icon'       => 'dashicons-welcome-learn-more',
            'post_type'  => 'flavor_curso',
            'capability' => 'edit_posts',
            'module_id'  => 'cursos',
        ],
        'talleres' => [
            'id'         => 'talleres',
            'label'      => __('Talleres', 'flavor-chat-ia'),
            'icon'       => 'dashicons-hammer',
            'post_type'  => 'flavor_taller',
            'capability' => 'edit_posts',
            'module_id'  => 'talleres',
        ],
        'foros' => [
            'id'         => 'foros',
            'label'      => __('Temas de Foro', 'flavor-chat-ia'),
            'icon'       => 'dashicons-format-chat',
            'table'      => 'flavor_foros_temas',
            'capability' => 'edit_posts',
            'module_id'  => 'foros',
        ],
    ];

    /**
     * MATRIZ DE INTEGRACIONES
     * Define que modulos base (consumers) aceptan que proveedores
     *
     * Formato: 'consumer_id' => ['provider_id_1', 'provider_id_2', ...]
     */
    $integration_matrix = [
        // Grupos de Consumo
        'grupos_consumo' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'gc_producto', 'context' => 'side'],
                ['type' => 'post', 'post_type' => 'gc_productor', 'context' => 'normal'],
            ],
            'accepts' => ['recetas', 'multimedia', 'podcast', 'videos'],
        ],

        // Eventos
        'eventos' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_evento', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'podcast', 'recetas', 'articulos_social'],
        ],

        // Talleres
        'talleres' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_taller', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'recetas', 'biblioteca'],
        ],

        // Cursos
        'cursos' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_curso', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'videos', 'biblioteca', 'podcast'],
        ],

        // Espacios Comunes
        'espacios_comunes' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_espacio', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Huertos Urbanos
        'huertos_urbanos' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_huerto', 'context' => 'side'],
            ],
            'accepts' => ['recetas', 'multimedia', 'videos'],
        ],

        // Comunidades
        'comunidades' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_comunidad', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'podcast', 'articulos_social'],
        ],

        // Incidencias
        'incidencias' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_incidencia', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'], // Solo fotos/videos de la incidencia
        ],

        // Marketplace
        'marketplace' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'marketplace_item', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Socios
        'socios' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_socio', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'], // Foto de perfil, documentos
        ],

        // Bares/Establecimientos
        'bares' => [
            'targets' => [
                ['type' => 'post', 'post_type' => 'flavor_bar', 'context' => 'side'],
            ],
            'accepts' => ['recetas', 'multimedia'],
        ],

        // Colectivos
        'colectivos' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_colectivos', 'context' => 'normal'],
            ],
            'accepts' => ['multimedia', 'articulos_social', 'eventos', 'podcast'],
        ],

        // Foros
        'foros' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_foros_temas', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'videos'],
        ],

        // Participacion ciudadana
        'participacion' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_participacion_propuestas', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'articulos_social'],
        ],

        // Presupuestos participativos
        'presupuestos_participativos' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_presupuestos_proyectos', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Avisos municipales
        'avisos_municipales' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_avisos', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Ayuda vecinal
        'ayuda_vecinal' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_ayuda_vecinal', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Circulos de cuidados
        'circulos_cuidados' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_circulos_cuidados', 'context' => 'normal'],
            ],
            'accepts' => ['multimedia', 'recetas', 'biblioteca'],
        ],

        // Compostaje
        'compostaje' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_composteras', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'recetas', 'biblioteca'],
        ],

        // Reciclaje
        'reciclaje' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_puntos_reciclaje', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Biodiversidad local
        'biodiversidad_local' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_especies', 'context' => 'normal'],
            ],
            'accepts' => ['multimedia', 'recetas', 'biblioteca'],
        ],

        // Saberes ancestrales
        'saberes_ancestrales' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_saberes', 'context' => 'normal'],
            ],
            'accepts' => ['recetas', 'biblioteca', 'multimedia', 'podcast', 'videos'],
        ],

        // Banco de tiempo
        'banco_tiempo' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_banco_tiempo_servicios', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Reservas
        'reservas' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_reservas_recursos', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Carpooling
        'carpooling' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_carpooling_viajes', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Bicicletas compartidas
        'bicicletas_compartidas' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_bicicletas', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Tramites
        'tramites' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_tramites', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'biblioteca'],
        ],

        // Transparencia
        'transparencia' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_transparencia_documentos', 'context' => 'side'],
            ],
            'accepts' => ['multimedia', 'biblioteca'],
        ],

        // Economia del don
        'economia_don' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_economia_don_ofertas', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],

        // Trabajo digno
        'trabajo_digno' => [
            'targets' => [
                ['type' => 'table', 'table' => 'flavor_ofertas_trabajo', 'context' => 'side'],
            ],
            'accepts' => ['multimedia'],
        ],
    ];

    /**
     * INTEGRACIONES CON USUARIOS
     * Algunos modulos permiten que los USUARIOS tengan contenido vinculado
     */
    $user_integrations = [
        // Productores (usuarios con rol productor)
        'gc_productor_user' => [
            'role' => 'gc_productor',
            'accepts' => ['recetas', 'multimedia', 'videos', 'articulos_social', 'podcast'],
            'context' => 'profile',
        ],

        // Consumidores
        'gc_consumidor_user' => [
            'role' => 'gc_consumidor',
            'accepts' => ['recetas', 'multimedia', 'articulos_social'],
            'context' => 'profile',
        ],

        // Socios
        'socio_user' => [
            'role' => 'flavor_socio',
            'accepts' => ['multimedia', 'articulos_social'],
            'context' => 'profile',
        ],

        // Miembros de comunidad
        'comunidad_miembro_user' => [
            'role' => 'comunidad_miembro',
            'accepts' => ['multimedia', 'articulos_social', 'recetas'],
            'context' => 'profile',
        ],

        // Voluntarios
        'voluntario_user' => [
            'role' => 'flavor_voluntario',
            'accepts' => ['multimedia', 'articulos_social'],
            'context' => 'profile',
        ],

        // Vecinos participantes
        'vecino_user' => [
            'role' => 'flavor_vecino',
            'accepts' => ['multimedia', 'articulos_social'],
            'context' => 'profile',
        ],

        // Instructores/Profesores
        'instructor_user' => [
            'role' => 'flavor_instructor',
            'accepts' => ['multimedia', 'videos', 'biblioteca', 'podcast', 'cursos', 'talleres'],
            'context' => 'profile',
        ],

        // Hortelanos (huertos urbanos)
        'hortelano_user' => [
            'role' => 'flavor_hortelano',
            'accepts' => ['multimedia', 'recetas', 'articulos_social'],
            'context' => 'profile',
        ],

        // Empleados
        'empleado_user' => [
            'role' => 'flavor_empleado',
            'accepts' => ['multimedia'],
            'context' => 'profile',
        ],
    ];

    // Registrar providers en el sistema
    add_filter('flavor_integration_providers', function($existing_providers) use ($providers) {
        // Solo agregar providers que no estan ya registrados (por el trait)
        foreach ($providers as $id => $provider) {
            if (!isset($existing_providers[$id])) {
                // Verificar que el modulo esta activo
                if (flavor_is_module_active($provider['module_id'])) {
                    $existing_providers[$id] = $provider;
                }
            }
        }
        return $existing_providers;
    }, 5);

    // Registrar consumers en el sistema
    add_filter('flavor_integration_consumers', function($existing_consumers) use ($integration_matrix) {
        foreach ($integration_matrix as $consumer_id => $config) {
            if (!isset($existing_consumers[$consumer_id])) {
                // Verificar que el modulo esta activo
                if (flavor_is_module_active($consumer_id)) {
                    $existing_consumers[$consumer_id] = [
                        'module_id' => $consumer_id,
                        'accepted' => $config['accepts'],
                        'targets' => $config['targets'],
                    ];
                }
            }
        }
        return $existing_consumers;
    }, 5);

    // Almacenar config para uso posterior
    update_option('flavor_integration_matrix', $integration_matrix, false);
    update_option('flavor_user_integrations', $user_integrations, false);
}

/**
 * Helper: Verificar si un modulo esta activo
 */
function flavor_is_module_active($module_id) {
    $settings = get_option('flavor_chat_ia_settings', []);
    $active_modules = $settings['active_modules'] ?? [];

    return in_array($module_id, $active_modules);
}

/**
 * Helper: Obtener providers activos para un consumer
 */
function flavor_get_active_providers_for($consumer_id) {
    $matrix = get_option('flavor_integration_matrix', []);

    if (!isset($matrix[$consumer_id])) {
        return [];
    }

    $accepted = $matrix[$consumer_id]['accepts'];
    $providers = apply_filters('flavor_integration_providers', []);

    return array_filter($providers, function($provider) use ($accepted) {
        return in_array($provider['id'], $accepted);
    });
}

/**
 * Helper: Verificar si un consumer acepta un provider
 */
function flavor_consumer_accepts($consumer_id, $provider_id) {
    $matrix = get_option('flavor_integration_matrix', []);

    if (!isset($matrix[$consumer_id])) {
        return false;
    }

    return in_array($provider_id, $matrix[$consumer_id]['accepts']);
}

/**
 * INTEGRACIONES FUNCIONALES
 *
 * Estas integraciones permiten acciones entre módulos (no solo vincular contenido).
 * Se implementan en Flavor_Functional_Integrations (trait-module-integrations.php)
 *
 * @since 1.8.0
 */
add_filter('flavor_functional_integrations_docs', function($docs) {
    return [
        'eventos_gc_catering' => [
            'origen'      => 'eventos',
            'destino'     => 'grupos_consumo',
            'descripcion' => 'Crear pedido grupal de productos para el catering de un evento',
            'ejemplo'     => 'Un organizador de evento puede solicitar productos del grupo de consumo para el catering',
        ],
        'cursos_banco_tiempo' => [
            'origen'      => 'cursos',
            'destino'     => 'banco_tiempo',
            'descripcion' => 'Permitir pagar la inscripción a cursos con horas del banco de tiempo',
            'ejemplo'     => 'Un curso puede configurarse para aceptar 5 horas de servicio como forma de pago',
        ],
        'carpooling_eventos' => [
            'origen'      => 'carpooling',
            'destino'     => 'eventos',
            'descripcion' => 'Ofrecer o buscar transporte compartido para asistir a un evento',
            'ejemplo'     => 'Los asistentes a un evento pueden coordinar viajes compartidos',
        ],
        'incidencias_huertos' => [
            'origen'      => 'incidencias',
            'destino'     => 'huertos_urbanos',
            'descripcion' => 'Reportar problemas específicos en parcelas de huertos',
            'ejemplo'     => 'Una incidencia de plagas puede vincularse a una parcela específica',
        ],
        'talleres_comunidades' => [
            'origen'      => 'talleres',
            'destino'     => 'comunidades',
            'descripcion' => 'Restringir talleres a miembros de una comunidad con descuento opcional',
            'ejemplo'     => 'Un taller exclusivo para miembros de una comunidad con 20% de descuento',
        ],
        'recetas_gc_ingredientes' => [
            'origen'      => 'recetas',
            'destino'     => 'grupos_consumo',
            'descripcion' => 'Vincular recetas con ingredientes disponibles en grupos de consumo',
            'ejemplo'     => 'Una receta muestra qué ingredientes están disponibles para comprar',
        ],
    ];
});
