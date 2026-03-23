<?php
/**
 * Relaciones por Defecto entre Módulos
 *
 * Define las relaciones predeterminadas que se crean cuando no hay
 * configuración en la base de datos. Estas relaciones son ejemplos
 * de configuración común y se pueden modificar desde el admin.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene las relaciones por defecto entre módulos
 *
 * @return array Matriz de relaciones: 'parent_id' => ['child_id1', 'child_id2', ...]
 */
function flavor_get_default_module_relations() {
    return [
        // GRUPOS DE CONSUMO
        // Herramientas comunitarias para gestión de consumo
        'grupos_consumo' => [
            'foros',            // Discusiones sobre productos/productores
            'recetas',          // Recetas con productos del catálogo
            'multimedia',       // Fotos de productos y repartos
            'eventos',          // Eventos de reparto y asambleas
            'socios',           // Gestión de miembros del grupo
            'biblioteca',       // Documentación y recursos
        ],

        // EVENTOS
        // Herramientas para gestión de eventos comunitarios
        'eventos' => [
            'foros',            // Discusiones previas/posteriores al evento
            'multimedia',       // Galería de fotos/videos del evento
            'chat_interno',     // Coordinación en tiempo real
            'espacios_comunes', // Reserva de espacios para eventos
            'talleres',         // Eventos que son talleres
            'red_social',       // Compartir eventos en la red
        ],

        // COMUNIDADES
        // Herramientas para gestión de comunidades vecinales/temáticas
        'comunidades' => [
            'foros',            // Espacio de debate comunitario
            'red_social',       // Feed de actividad de la comunidad
            'multimedia',       // Galería multimedia comunitaria
            'eventos',          // Calendario de eventos
            'participacion',    // Toma de decisiones
            'transparencia',    // Documentación y finanzas
        ],

        // SOCIOS
        // Gestión de membresía y asociados
        'socios' => [
            'transparencia',    // Acceso a cuentas y presupuestos
            'eventos',          // Eventos exclusivos para socios
            'grupos_consumo',   // Ventajas en grupos de consumo
            'cursos',           // Cursos con descuento para socios
            'foros',            // Foro de socios
            'presupuestos_participativos', // Votación de presupuestos
        ],

        // MARKETPLACE
        // Tienda/mercado local
        'marketplace' => [
            'recetas',          // Recetas con productos del marketplace
            'eventos',          // Mercadillos y ferias
            'multimedia',       // Fotos de productos
            'trabajo_digno',    // Ofertas de trabajo de vendedores
            'economia_don',     // Intercambio de productos
            'foros',            // Consultas sobre productos
        ],

        // TALLERES
        // Cursos y talleres formativos
        'talleres' => [
            'eventos',          // Talleres como eventos
            'multimedia',       // Material multimedia del taller
            'biblioteca',       // Bibliografía y recursos
            'cursos',           // Cursos relacionados
            'espacios_comunes', // Reserva de salas
            'foros',            // Dudas y consultas post-taller
        ],

        // CURSOS
        // Formación y educación
        'cursos' => [
            'multimedia',       // Videos y materiales del curso
            'biblioteca',       // Bibliografía recomendada
            'talleres',         // Talleres prácticos del curso
            'eventos',          // Eventos del curso
            'foros',            // Foro de estudiantes
            'podcast',          // Episodios complementarios
        ],

        // ESPACIOS COMUNES
        // Reserva y gestión de espacios compartidos
        'espacios_comunes' => [
            'eventos',          // Eventos en los espacios
            'multimedia',       // Fotos de los espacios
            'reservas',         // Sistema de reservas
            'talleres',         // Talleres en los espacios
            'incidencias',      // Reportar problemas en espacios
        ],

        // INCIDENCIAS
        // Gestión de reportes y problemas
        'incidencias' => [
            'multimedia',       // Fotos del problema
            'espacios_comunes', // Incidencias de espacios
            'participacion',    // Priorización de soluciones
            'transparencia',    // Seguimiento de resolución
        ],

        // BIBLIOTECA
        // Gestión de recursos documentales
        'biblioteca' => [
            'multimedia',       // Audiolibros, documentales
            'podcast',          // Podcast sobre libros
            'foros',            // Club de lectura
            'eventos',          // Presentaciones de libros
            'cursos',           // Cursos relacionados
        ],

        // PODCAST
        // Radio/podcast comunitario
        'podcast' => [
            'multimedia',       // Videos complementarios
            'eventos',          // Grabaciones en vivo
            'foros',            // Comentarios de episodios
            'biblioteca',       // Transcripciones y recursos
            'red_social',       // Compartir episodios
        ],

        // RADIO
        // Emisora comunitaria
        'radio' => [
            'podcast',          // Podcasts de programas
            'eventos',          // Eventos de la radio
            'multimedia',       // Archivo multimedia
            'foros',            // Participación de oyentes
        ],

        // RECICLAJE
        // Gestión de reciclaje comunitario
        'reciclaje' => [
            'eventos',          // Eventos de recogida
            'multimedia',       // Infografías educativas
            'participacion',    // Campañas de reciclaje
            'transparencia',    // Estadísticas de reciclaje
        ],

        // PARTICIPACIÓN
        // Procesos participativos
        'participacion' => [
            'foros',            // Debate de propuestas
            'presupuestos_participativos', // Votación de presupuestos
            'transparencia',    // Resultados y seguimiento
            'eventos',          // Asambleas participativas
            'multimedia',       // Material informativo
        ],

        // TRANSPARENCIA
        // Transparencia y rendición de cuentas
        'transparencia' => [
            'participacion',    // Consulta ciudadana
            'presupuestos_participativos', // Presupuestos aprobados
            'socios',           // Info para socios
            'biblioteca',       // Documentación legal
        ],

        // COLECTIVOS
        // Gestión de colectivos y asociaciones
        'colectivos' => [
            'foros',            // Debate interno
            'eventos',          // Eventos del colectivo
            'proyectos',        // Proyectos en marcha
            'multimedia',       // Galería del colectivo
            'transparencia',    // Cuentas del colectivo
        ],

        // HUERTOS URBANOS
        // Gestión de huertos comunitarios
        'huertos_urbanos' => [
            'recetas',          // Recetas con verduras del huerto
            'multimedia',       // Fotos del huerto
            'eventos',          // Jornadas en el huerto
            'foros',            // Consejos de cultivo
            'biblioteca',       // Guías de permacultura
        ],

        // COMPOSTAJE
        // Gestión de compostaje comunitario
        'compostaje' => [
            'reciclaje',        // Integración con reciclaje
            'eventos',          // Talleres de compostaje
            'multimedia',       // Tutoriales
            'participacion',    // Campañas de compostaje
        ],

        // CARPOOLING
        // Coche compartido
        'carpooling' => [
            'eventos',          // Viajes a eventos
            'chat_interno',     // Coordinación de viajes
            'foros',            // Grupos de ruta
        ],

        // BANCO TIEMPO
        // Intercambio de servicios
        'banco_tiempo' => [
            'trabajo_digno',    // Ofertas de servicio
            'eventos',          // Encuentros de intercambio
            'foros',            // Consultas
            'multimedia',       // Tutoriales de servicios
        ],

        // ECONOMÍA DEL DON
        // Donaciones y regalos
        'economia_don' => [
            'marketplace',      // Integración con tienda
            'eventos',          // Mercados de regalo
            'foros',            // Consultas sobre donaciones
            'multimedia',       // Fotos de objetos
        ],

        // TRABAJO DIGNO
        // Ofertas de empleo ético
        'trabajo_digno' => [
            'foros',            // Consultas laborales
            'eventos',          // Ferias de empleo
            'cursos',           // Formación para el empleo
            'banco_tiempo',     // Intercambio de servicios
        ],

        // FOROS (módulo horizontal base)
        // Solo incluir si se usa como módulo vertical en algún contexto
        'foros' => [
            'multimedia',       // Adjuntar imágenes en posts
            'eventos',          // Eventos mencionados
        ],

        // RED SOCIAL (módulo horizontal base)
        'red_social' => [
            'multimedia',       // Compartir fotos/videos
            'eventos',          // Compartir eventos
            'foros',            // Discusiones ampliadas
        ],
    ];
}

/**
 * Inicializa las relaciones por defecto en la base de datos
 *
 * Solo crea las relaciones si NO existen previamente.
 * Útil para ejecutar después de instalar el plugin o migrar.
 *
 * @param string $context Contexto para las relaciones (default: 'global')
 * @return int Número de relaciones creadas
 */
function flavor_init_default_module_relations($context = 'global') {
    global $wpdb;
    $table = $wpdb->prefix . 'flavor_module_relations';

    // Verificar que la tabla existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        return 0;
    }

    $relations = flavor_get_default_module_relations();
    $created = 0;

    foreach ($relations as $parent_id => $children) {
        // Verificar si el módulo parent está activo
        if (!Flavor_Chat_Module_Loader::is_module_active($parent_id)) {
            continue;
        }

        // Verificar si ya existen relaciones para este parent
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE parent_module_id = %s AND context = %s",
            $parent_id,
            $context
        ));

        if ($exists > 0) {
            continue; // Ya tiene configuración, no sobrescribir
        }

        // Crear relaciones
        $priority = 10;
        foreach ($children as $child_id) {
            // Verificar que el child también está activo
            if (!Flavor_Chat_Module_Loader::is_module_active($child_id)) {
                continue;
            }

            $inserted = $wpdb->insert($table, [
                'parent_module_id' => $parent_id,
                'child_module_id'  => $child_id,
                'context'          => $context,
                'priority'         => $priority,
                'enabled'          => 1,
                'created_at'       => current_time('mysql'),
            ]);

            if ($inserted) {
                $created++;
            }

            $priority += 10;
        }
    }

    return $created;
}
