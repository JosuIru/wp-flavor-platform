<?php
/**
 * Módulo Sello de Conciencia - App Consciente
 *
 * Evalúa automáticamente el nivel de conciencia de la aplicación
 * basándose en los módulos activos y su alineación con las 5 premisas
 * fundamentales de una economía consciente.
 *
 * @package FlavorChatIA
 * @subpackage Modules\SelloConciencia
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo Sello de Conciencia
 *
 * Gestiona la evaluación automática de módulos según las 5 premisas
 * y calcula el nivel global de conciencia de la aplicación.
 *
 * @since 4.2.0
 */
class Flavor_Chat_Sello_Conciencia_Module extends Flavor_Chat_Module_Base {

    /**
     * Versión del sistema de evaluación
     */
    const VERSION = '1.0.0';

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * LAS 5 PREMISAS FUNDAMENTALES DE UNA ECONOMÍA CONSCIENTE
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * Estas premisas constituyen el marco ético y filosófico sobre el cual
     * se evalúa cada módulo. Un módulo puede contribuir a una o varias premisas.
     *
     * Cada premisa tiene:
     * - id: Identificador único
     * - nombre: Nombre corto
     * - descripcion: Descripción completa de la premisa
     * - principio: El principio ontológico subyacente
     * - consecuencia: La consecuencia práctica en la aplicación
     * - peso: Peso relativo en la puntuación global (0.0 - 1.0)
     */
    const PREMISAS = [
        'conciencia_fundamental' => [
            'id' => 'conciencia_fundamental',
            'nombre' => 'La conciencia es fundamental',
            'descripcion' => 'La materia no es lo único real; la conciencia es tan fundamental como ella, quizás más. Todos los seres tienen inteligencia, valor, dignidad.',
            'principio' => 'La ética emerge de reconocer que todo ser importa, no porque nos convenga sino porque son. El sufrimiento importa ontológicamente, no es "solo química".',
            'consecuencia' => 'Módulos que reconocen la dignidad de las personas, facilitan la participación consciente, respetan la autonomía y promueven el bienestar integral.',
            'peso' => 0.20,
            'icono' => 'dashicons-heart',
            'color' => '#9b59b6',
        ],

        'abundancia_organizable' => [
            'id' => 'abundancia_organizable',
            'nombre' => 'La abundancia es organizable',
            'descripcion' => 'No hay escasez de recursos; hay escasez de distribución equitativa. Cuando la distribución es equitativa, todos tienen suficiente.',
            'principio' => 'Economía basada en satisfacer necesidades, no en acumulación infinita. El trabajo es contribución, no competencia.',
            'consecuencia' => 'Módulos que facilitan el acceso equitativo a recursos, promueven la economía colaborativa, permiten compartir excedentes y organizan la distribución justa.',
            'peso' => 0.20,
            'icono' => 'dashicons-share-alt',
            'color' => '#27ae60',
        ],

        'interdependencia_radical' => [
            'id' => 'interdependencia_radical',
            'nombre' => 'La interdependencia es radical',
            'descripcion' => 'La separación es abstracción útil pero no realidad ontológica. No estamos separados: las decisiones afectan a otros, somos parte de tejidos ecológicos más grandes, la cooperación es lo natural.',
            'principio' => 'Se rechaza la externalización de costos. El daño al otro se entiende como auto-daño.',
            'consecuencia' => 'Módulos que fomentan la cooperación, crean redes de apoyo mutuo, visibilizan las conexiones entre personas y comunidades, y facilitan la acción colectiva.',
            'peso' => 0.20,
            'icono' => 'dashicons-networking',
            'color' => '#3498db',
        ],

        'madurez_ciclica' => [
            'id' => 'madurez_ciclica',
            'nombre' => 'La madurez es cíclica',
            'descripcion' => 'Los sistemas sanos crecen, maduran y se renuevan cíclicamente. Los límites no son obstáculos sino condiciones de vida. Respetar ciclos es salud; acelerar infinitamente es enfermedad.',
            'principio' => 'Producción que respeta capacidad de regeneración, suficiencia como valor, honrar ritmos naturales.',
            'consecuencia' => 'Módulos que respetan límites, promueven la sostenibilidad, consideran el largo plazo, permiten pausas y renovaciones, y no fomentan el crecimiento infinito.',
            'peso' => 0.20,
            'icono' => 'dashicons-update',
            'color' => '#e67e22',
        ],

        'valor_intrinseco' => [
            'id' => 'valor_intrinseco',
            'nombre' => 'El valor es intrínseco',
            'descripcion' => 'Las cosas valen por lo que son, no por lo que puede extraerse de ellas. Un bosque vale porque es, no porque tenga madera. Una persona vale porque existe, no porque sea productiva.',
            'principio' => 'No todo se monetiza. Existe espacio para economía de regalos, intercambio directo, servicio.',
            'consecuencia' => 'Módulos que permiten intercambios no monetarios, valoran la contribución más allá del dinero, reconocen el valor intrínseco de personas y recursos.',
            'peso' => 0.20,
            'icono' => 'dashicons-awards',
            'color' => '#f39c12',
        ],
    ];

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * NIVELES DE CONCIENCIA
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * Según la puntuación global, se asigna un nivel de conciencia
     */
    const NIVELES = [
        'ninguno' => [
            'id' => 'ninguno',
            'nombre' => 'Sin evaluar',
            'descripcion' => 'La aplicación no ha sido evaluada o no tiene módulos activos',
            'min_puntuacion' => 0,
            'max_puntuacion' => 0,
            'color' => '#95a5a6',
            'icono' => 'dashicons-minus',
        ],
        'basico' => [
            'id' => 'basico',
            'nombre' => 'Básico',
            'descripcion' => 'La aplicación cumple requisitos mínimos de conciencia',
            'min_puntuacion' => 1,
            'max_puntuacion' => 25,
            'color' => '#e74c3c',
            'icono' => 'dashicons-marker',
        ],
        'transicion' => [
            'id' => 'transicion',
            'nombre' => 'En Transición',
            'descripcion' => 'La aplicación está en proceso de integrar más prácticas conscientes',
            'min_puntuacion' => 26,
            'max_puntuacion' => 50,
            'color' => '#f39c12',
            'icono' => 'dashicons-arrow-up-alt',
        ],
        'consciente' => [
            'id' => 'consciente',
            'nombre' => 'Consciente',
            'descripcion' => 'La aplicación integra activamente las premisas de conciencia',
            'min_puntuacion' => 51,
            'max_puntuacion' => 75,
            'color' => '#27ae60',
            'icono' => 'dashicons-yes-alt',
        ],
        'referente' => [
            'id' => 'referente',
            'nombre' => 'Referente',
            'descripcion' => 'La aplicación es un ejemplo de economía consciente',
            'min_puntuacion' => 76,
            'max_puntuacion' => 100,
            'color' => '#9b59b6',
            'icono' => 'dashicons-star-filled',
        ],
    ];

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * VALORACIÓN DE MÓDULOS
     * ═══════════════════════════════════════════════════════════════════════════
     *
     * Cada módulo tiene una puntuación de 0-100 y contribuye a una o más premisas.
     * La estructura incluye:
     * - puntuacion: Puntuación base del módulo (0-100)
     * - premisas: Array de premisas a las que contribuye con su peso relativo
     * - descripcion_contribucion: Cómo contribuye este módulo a la conciencia
     */
    const MODULOS_VALORACION = [
        // ─── Módulos de Economía Colaborativa ───
        'grupos_consumo' => [
            'nombre' => 'Grupos de Consumo',
            'puntuacion' => 85,
            'premisas' => [
                'abundancia_organizable' => 0.35,
                'interdependencia_radical' => 0.25,
                'madurez_ciclica' => 0.20,
                'valor_intrinseco' => 0.15,
                'conciencia_fundamental' => 0.05,
            ],
            'descripcion_contribucion' => 'Organiza la distribución equitativa de alimentos locales, conecta productores con consumidores, respeta ciclos naturales de producción y valora el trabajo agrícola.',
            'categoria' => 'economia',
        ],

        'banco_tiempo' => [
            'nombre' => 'Banco de Tiempo',
            'puntuacion' => 95,
            'premisas' => [
                'valor_intrinseco' => 0.35,
                'abundancia_organizable' => 0.25,
                'interdependencia_radical' => 0.25,
                'conciencia_fundamental' => 0.15,
            ],
            'descripcion_contribucion' => 'Reconoce que el tiempo de todas las personas vale igual, independientemente de su profesión. Facilita intercambios basados en necesidades reales, no en dinero.',
            'categoria' => 'economia',
        ],

        'moneda_local' => [
            'nombre' => 'Moneda Local',
            'puntuacion' => 80,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'interdependencia_radical' => 0.30,
                'madurez_ciclica' => 0.20,
                'valor_intrinseco' => 0.20,
            ],
            'descripcion_contribucion' => 'Mantiene la riqueza circulando en la comunidad, fortalece la economía local y reduce la dependencia de sistemas financieros extractivos.',
            'categoria' => 'economia',
        ],

        'mercado_social' => [
            'nombre' => 'Mercado Social',
            'puntuacion' => 75,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'interdependencia_radical' => 0.25,
                'valor_intrinseco' => 0.25,
                'conciencia_fundamental' => 0.20,
            ],
            'descripcion_contribucion' => 'Conecta consumidores con proveedores éticos, promueve el consumo responsable y visibiliza alternativas al mercado convencional.',
            'categoria' => 'economia',
        ],

        'marketplace' => [
            'nombre' => 'Marketplace',
            'puntuacion' => 60,
            'premisas' => [
                'abundancia_organizable' => 0.40,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.30,
            ],
            'descripcion_contribucion' => 'Facilita el intercambio de bienes y servicios dentro de la comunidad, aunque puede tender hacia dinámicas de mercado convencional.',
            'categoria' => 'economia',
        ],

        // ─── Módulos de Recursos Compartidos ───
        'espacios_comunes' => [
            'nombre' => 'Espacios Comunes',
            'puntuacion' => 90,
            'premisas' => [
                'abundancia_organizable' => 0.35,
                'interdependencia_radical' => 0.30,
                'madurez_ciclica' => 0.20,
                'conciencia_fundamental' => 0.15,
            ],
            'descripcion_contribucion' => 'Permite el uso compartido de espacios, maximiza la utilidad de recursos infrautilizados y fomenta la gestión colectiva.',
            'categoria' => 'recursos',
        ],

        'bicicletas_compartidas' => [
            'nombre' => 'Bicicletas Compartidas',
            'puntuacion' => 85,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'madurez_ciclica' => 0.30,
                'interdependencia_radical' => 0.20,
                'valor_intrinseco' => 0.20,
            ],
            'descripcion_contribucion' => 'Promueve movilidad sostenible, reduce la necesidad de propiedad individual y fomenta el cuidado compartido de recursos.',
            'categoria' => 'recursos',
        ],

        'biblioteca' => [
            'nombre' => 'Biblioteca de Cosas',
            'puntuacion' => 90,
            'premisas' => [
                'abundancia_organizable' => 0.35,
                'madurez_ciclica' => 0.25,
                'valor_intrinseco' => 0.20,
                'interdependencia_radical' => 0.20,
            ],
            'descripcion_contribucion' => 'Permite acceder a herramientas y objetos sin necesidad de comprarlos, reduce el consumismo y promueve la cultura del préstamo.',
            'categoria' => 'recursos',
        ],

        'huertos_urbanos' => [
            'nombre' => 'Huertos Urbanos',
            'puntuacion' => 88,
            'premisas' => [
                'madurez_ciclica' => 0.30,
                'abundancia_organizable' => 0.25,
                'interdependencia_radical' => 0.25,
                'conciencia_fundamental' => 0.20,
            ],
            'descripcion_contribucion' => 'Reconecta a las personas con los ciclos naturales, produce alimentos locales y fomenta el trabajo comunitario.',
            'categoria' => 'recursos',
        ],

        'carpooling' => [
            'nombre' => 'Coche Compartido',
            'puntuacion' => 78,
            'premisas' => [
                'abundancia_organizable' => 0.35,
                'madurez_ciclica' => 0.25,
                'interdependencia_radical' => 0.25,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Reduce el uso individual del coche, comparte recursos de movilidad y disminuye la huella ecológica.',
            'categoria' => 'recursos',
        ],

        'parkings' => [
            'nombre' => 'Parkings Compartidos',
            'puntuacion' => 65,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'interdependencia_radical' => 0.30,
                'madurez_ciclica' => 0.20,
            ],
            'descripcion_contribucion' => 'Optimiza el uso de plazas de aparcamiento infrautilizadas.',
            'categoria' => 'recursos',
        ],

        // ─── Módulos de Sostenibilidad Ambiental ───
        'reciclaje' => [
            'nombre' => 'Reciclaje',
            'puntuacion' => 75,
            'premisas' => [
                'madurez_ciclica' => 0.40,
                'interdependencia_radical' => 0.25,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Cierra ciclos de materiales, reduce residuos y fomenta la responsabilidad ambiental compartida.',
            'categoria' => 'ambiente',
        ],

        'compostaje' => [
            'nombre' => 'Compostaje',
            'puntuacion' => 88,
            'premisas' => [
                'madurez_ciclica' => 0.45,
                'interdependencia_radical' => 0.25,
                'valor_intrinseco' => 0.15,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Transforma residuos en recursos, cierra el ciclo de nutrientes y conecta la ciudad con el campo.',
            'categoria' => 'ambiente',
        ],

        'energia_comunitaria' => [
            'nombre' => 'Energía Comunitaria',
            'puntuacion' => 92,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'interdependencia_radical' => 0.30,
                'madurez_ciclica' => 0.25,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Democratiza el acceso a la energía, promueve renovables y construye soberanía energética comunitaria.',
            'categoria' => 'ambiente',
        ],

        // ─── Módulos de Cuidados y Bienestar ───
        'cuidados' => [
            'nombre' => 'Red de Cuidados',
            'puntuacion' => 95,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Reconoce el valor del trabajo de cuidados, organiza el apoyo mutuo y distribuye la responsabilidad colectivamente.',
            'categoria' => 'cuidados',
        ],

        'ayuda_vecinal' => [
            'nombre' => 'Ayuda Vecinal',
            'puntuacion' => 88,
            'premisas' => [
                'interdependencia_radical' => 0.35,
                'conciencia_fundamental' => 0.30,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.15,
            ],
            'descripcion_contribucion' => 'Facilita la ayuda entre vecinos, fortalece los vínculos comunitarios y organiza el apoyo mutuo.',
            'categoria' => 'cuidados',
        ],

        'salud_comunitaria' => [
            'nombre' => 'Salud Comunitaria',
            'puntuacion' => 85,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.30,
                'abundancia_organizable' => 0.20,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Promueve la salud como derecho y responsabilidad compartida, facilita el acceso a recursos y prioriza la prevención.',
            'categoria' => 'cuidados',
        ],

        // ─── Módulos de Participación y Gobernanza ───
        'asambleas' => [
            'nombre' => 'Asambleas',
            'puntuacion' => 90,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.35,
                'abundancia_organizable' => 0.15,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Facilita la toma de decisiones democrática, respeta todas las voces y construye consensos colectivos.',
            'categoria' => 'participacion',
        ],

        'participacion' => [
            'nombre' => 'Participación Ciudadana',
            'puntuacion' => 82,
            'premisas' => [
                'conciencia_fundamental' => 0.35,
                'interdependencia_radical' => 0.35,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Facilita la participación activa de la ciudadanía en decisiones colectivas.',
            'categoria' => 'participacion',
        ],

        'presupuestos_participativos' => [
            'nombre' => 'Presupuestos Participativos',
            'puntuacion' => 85,
            'premisas' => [
                'abundancia_organizable' => 0.35,
                'conciencia_fundamental' => 0.30,
                'interdependencia_radical' => 0.35,
            ],
            'descripcion_contribucion' => 'Democratiza la gestión de recursos comunes, permite decidir colectivamente sobre el presupuesto.',
            'categoria' => 'participacion',
        ],

        'votaciones' => [
            'nombre' => 'Votaciones',
            'puntuacion' => 70,
            'premisas' => [
                'conciencia_fundamental' => 0.40,
                'interdependencia_radical' => 0.30,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Permite la expresión democrática de preferencias y facilita la toma de decisiones colectivas.',
            'categoria' => 'participacion',
        ],

        'incidencias' => [
            'nombre' => 'Gestión de Incidencias',
            'puntuacion' => 65,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Canaliza las necesidades y problemas de la comunidad, facilita respuestas coordinadas.',
            'categoria' => 'participacion',
        ],

        'transparencia' => [
            'nombre' => 'Transparencia',
            'puntuacion' => 78,
            'premisas' => [
                'conciencia_fundamental' => 0.40,
                'interdependencia_radical' => 0.35,
                'valor_intrinseco' => 0.25,
            ],
            'descripcion_contribucion' => 'Promueve la rendición de cuentas y el acceso a información pública.',
            'categoria' => 'participacion',
        ],

        // ─── Módulos de Comunicación y Cultura ───
        'eventos' => [
            'nombre' => 'Eventos',
            'puntuacion' => 70,
            'premisas' => [
                'interdependencia_radical' => 0.35,
                'conciencia_fundamental' => 0.25,
                'valor_intrinseco' => 0.20,
                'abundancia_organizable' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita el encuentro, fortalece los vínculos comunitarios y celebra la vida en común.',
            'categoria' => 'cultura',
        ],

        'avisos_municipales' => [
            'nombre' => 'Avisos Municipales',
            'puntuacion' => 55,
            'premisas' => [
                'interdependencia_radical' => 0.45,
                'abundancia_organizable' => 0.35,
                'conciencia_fundamental' => 0.20,
            ],
            'descripcion_contribucion' => 'Mantiene informada a la comunidad sobre asuntos relevantes.',
            'categoria' => 'cultura',
        ],

        'noticias' => [
            'nombre' => 'Noticias',
            'puntuacion' => 55,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Comparte información relevante para la comunidad, mantiene a todos informados y conectados.',
            'categoria' => 'cultura',
        ],

        'cursos' => [
            'nombre' => 'Cursos y Formación',
            'puntuacion' => 80,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'interdependencia_radical' => 0.25,
                'conciencia_fundamental' => 0.25,
                'valor_intrinseco' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita el aprendizaje colectivo, comparte conocimientos y desarrolla capacidades comunitarias.',
            'categoria' => 'cultura',
        ],

        'talleres' => [
            'nombre' => 'Talleres',
            'puntuacion' => 82,
            'premisas' => [
                'abundancia_organizable' => 0.30,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.25,
                'conciencia_fundamental' => 0.15,
            ],
            'descripcion_contribucion' => 'Transmite conocimientos prácticos, fomenta el hacer colectivo y valora los saberes tradicionales.',
            'categoria' => 'cultura',
        ],

        'multimedia' => [
            'nombre' => 'Multimedia',
            'puntuacion' => 50,
            'premisas' => [
                'abundancia_organizable' => 0.40,
                'interdependencia_radical' => 0.35,
                'valor_intrinseco' => 0.25,
            ],
            'descripcion_contribucion' => 'Permite compartir contenido multimedia de la comunidad.',
            'categoria' => 'cultura',
        ],

        'radio' => [
            'nombre' => 'Radio Comunitaria',
            'puntuacion' => 75,
            'premisas' => [
                'interdependencia_radical' => 0.35,
                'abundancia_organizable' => 0.30,
                'conciencia_fundamental' => 0.20,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Da voz a la comunidad, democratiza los medios de comunicación.',
            'categoria' => 'cultura',
        ],

        'podcast' => [
            'nombre' => 'Podcast',
            'puntuacion' => 65,
            'premisas' => [
                'interdependencia_radical' => 0.35,
                'abundancia_organizable' => 0.35,
                'valor_intrinseco' => 0.30,
            ],
            'descripcion_contribucion' => 'Comparte contenido de audio, democratiza la comunicación.',
            'categoria' => 'cultura',
        ],

        'foros' => [
            'nombre' => 'Foros',
            'puntuacion' => 60,
            'premisas' => [
                'interdependencia_radical' => 0.45,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.25,
            ],
            'descripcion_contribucion' => 'Facilita el debate y la deliberación colectiva.',
            'categoria' => 'cultura',
        ],

        'red_social' => [
            'nombre' => 'Red Social',
            'puntuacion' => 55,
            'premisas' => [
                'interdependencia_radical' => 0.50,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita la conexión entre personas de la comunidad.',
            'categoria' => 'cultura',
        ],

        // ─── Módulos de Red y Colaboración ───
        'red_nodos' => [
            'nombre' => 'Red de Comunidades',
            'puntuacion' => 88,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'abundancia_organizable' => 0.25,
                'valor_intrinseco' => 0.20,
                'conciencia_fundamental' => 0.15,
            ],
            'descripcion_contribucion' => 'Conecta comunidades, facilita la colaboración inter-territorial y construye redes de apoyo mutuo.',
            'categoria' => 'red',
        ],

        'colectivos' => [
            'nombre' => 'Colectivos',
            'puntuacion' => 75,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Facilita la organización de grupos con intereses comunes.',
            'categoria' => 'red',
        ],

        'comunidades' => [
            'nombre' => 'Comunidades',
            'puntuacion' => 78,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.30,
            ],
            'descripcion_contribucion' => 'Permite crear y gestionar comunidades dentro de la aplicación.',
            'categoria' => 'red',
        ],

        // ─── Módulos de Comunicación Interna ───
        'chat_interno' => [
            'nombre' => 'Chat Interno',
            'puntuacion' => 55,
            'premisas' => [
                'interdependencia_radical' => 0.50,
                'conciencia_fundamental' => 0.30,
                'abundancia_organizable' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita la comunicación directa entre miembros.',
            'categoria' => 'comunicacion',
        ],

        'chat_grupos' => [
            'nombre' => 'Chat Grupos',
            'puntuacion' => 60,
            'premisas' => [
                'interdependencia_radical' => 0.50,
                'conciencia_fundamental' => 0.25,
                'abundancia_organizable' => 0.25,
            ],
            'descripcion_contribucion' => 'Facilita la comunicación grupal entre miembros.',
            'categoria' => 'comunicacion',
        ],

        // ─── Módulos Administrativos (menor impacto directo) ───
        'fichaje_empleados' => [
            'nombre' => 'Control de Fichaje',
            'puntuacion' => 40,
            'premisas' => [
                'conciencia_fundamental' => 0.50,
                'abundancia_organizable' => 0.30,
                'madurez_ciclica' => 0.20,
            ],
            'descripcion_contribucion' => 'Gestiona el tiempo de trabajo de forma transparente, aunque su contribución directa a la conciencia es limitada.',
            'categoria' => 'admin',
        ],

        'directorio' => [
            'nombre' => 'Directorio',
            'puntuacion' => 50,
            'premisas' => [
                'interdependencia_radical' => 0.50,
                'abundancia_organizable' => 0.30,
                'conciencia_fundamental' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita el contacto entre miembros y visibiliza la red de personas de la comunidad.',
            'categoria' => 'admin',
        ],

        'socios' => [
            'nombre' => 'Gestión de Socios',
            'puntuacion' => 50,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'abundancia_organizable' => 0.35,
                'conciencia_fundamental' => 0.25,
            ],
            'descripcion_contribucion' => 'Organiza la membresía de la comunidad.',
            'categoria' => 'admin',
        ],

        'documentos' => [
            'nombre' => 'Gestión Documental',
            'puntuacion' => 45,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'interdependencia_radical' => 0.30,
                'valor_intrinseco' => 0.20,
            ],
            'descripcion_contribucion' => 'Organiza y comparte documentos de la comunidad, facilita la transparencia.',
            'categoria' => 'admin',
        ],

        'facturas' => [
            'nombre' => 'Facturación',
            'puntuacion' => 35,
            'premisas' => [
                'abundancia_organizable' => 0.60,
                'conciencia_fundamental' => 0.40,
            ],
            'descripcion_contribucion' => 'Gestiona la facturación de forma organizada.',
            'categoria' => 'admin',
        ],

        'tramites' => [
            'nombre' => 'Trámites',
            'puntuacion' => 45,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'conciencia_fundamental' => 0.30,
                'interdependencia_radical' => 0.20,
            ],
            'descripcion_contribucion' => 'Facilita la gestión de trámites y procedimientos.',
            'categoria' => 'admin',
        ],

        'reservas' => [
            'nombre' => 'Reservas',
            'puntuacion' => 55,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'interdependencia_radical' => 0.30,
                'madurez_ciclica' => 0.20,
            ],
            'descripcion_contribucion' => 'Organiza el acceso a recursos compartidos.',
            'categoria' => 'admin',
        ],

        // ─── Módulos de negocio (valoración según uso) ───
        'advertising' => [
            'nombre' => 'Publicidad',
            'puntuacion' => 20,
            'premisas' => [
                'abundancia_organizable' => 0.60,
                'valor_intrinseco' => 0.40,
            ],
            'descripcion_contribucion' => 'Puede generar recursos para la comunidad, pero debe usarse con cuidado para no mercantilizar el espacio comunitario.',
            'categoria' => 'negocio',
            'advertencia' => 'Este módulo puede entrar en tensión con los valores de una economía consciente si no se gestiona éticamente.',
        ],

        'clientes' => [
            'nombre' => 'Gestión de Clientes',
            'puntuacion' => 30,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'conciencia_fundamental' => 0.30,
                'interdependencia_radical' => 0.20,
            ],
            'descripcion_contribucion' => 'Organiza las relaciones comerciales.',
            'categoria' => 'negocio',
        ],

        'woocommerce' => [
            'nombre' => 'WooCommerce',
            'puntuacion' => 35,
            'premisas' => [
                'abundancia_organizable' => 0.60,
                'interdependencia_radical' => 0.25,
                'valor_intrinseco' => 0.15,
            ],
            'descripcion_contribucion' => 'Facilita la venta de productos, puede usarse para comercio ético.',
            'categoria' => 'negocio',
        ],

        'email_marketing' => [
            'nombre' => 'Email Marketing',
            'puntuacion' => 25,
            'premisas' => [
                'abundancia_organizable' => 0.60,
                'interdependencia_radical' => 0.40,
            ],
            'descripcion_contribucion' => 'Comunicación masiva que debe usarse de forma respetuosa.',
            'categoria' => 'negocio',
            'advertencia' => 'Puede ser intrusivo si no se usa con responsabilidad.',
        ],

        'empresarial' => [
            'nombre' => 'Módulo Empresarial',
            'puntuacion' => 30,
            'premisas' => [
                'abundancia_organizable' => 0.50,
                'conciencia_fundamental' => 0.30,
                'interdependencia_radical' => 0.20,
            ],
            'descripcion_contribucion' => 'Herramientas para gestión empresarial.',
            'categoria' => 'negocio',
        ],

        'bares' => [
            'nombre' => 'Bares y Hostelería',
            'puntuacion' => 45,
            'premisas' => [
                'interdependencia_radical' => 0.40,
                'abundancia_organizable' => 0.35,
                'conciencia_fundamental' => 0.25,
            ],
            'descripcion_contribucion' => 'Gestiona espacios de encuentro y socialización.',
            'categoria' => 'negocio',
        ],
    ];

    /**
     * Cache de evaluación
     *
     * @var array|null
     */
    private $evaluacion_cache = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'sello_conciencia';
        $this->name = __('Sello de Conciencia', 'flavor-chat-ia');
        $this->description = __('Evalúa automáticamente el nivel de conciencia de la aplicación según los módulos activos y las 5 premisas fundamentales.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'mostrar_en_dashboard' => true,
            'mostrar_en_footer' => false,
            'mostrar_desglose' => true,
            'tamano_badge' => 'medium',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar widget de dashboard
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX para refrescar evaluación
        add_action('wp_ajax_sello_conciencia_evaluar', [$this, 'ajax_evaluar']);

        // Invalidar cache cuando cambian los módulos activos
        add_action('flavor_module_activated', [$this, 'invalidar_cache']);
        add_action('flavor_module_deactivated', [$this, 'invalidar_cache']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('sello_conciencia', [$this, 'shortcode_sello']);
        add_shortcode('premisas_conciencia', [$this, 'shortcode_premisas']);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * EVALUACIÓN AUTOMÁTICA
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Evalúa todos los módulos activos y calcula el nivel de conciencia
     *
     * @return array Resultado de la evaluación
     */
    public function evaluar(): array {
        // Verificar cache
        if ($this->evaluacion_cache !== null) {
            return $this->evaluacion_cache;
        }

        // Obtener módulos activos
        $modulos_activos = $this->get_modulos_activos();

        if (empty($modulos_activos)) {
            return $this->crear_evaluacion_vacia();
        }

        // Evaluar cada módulo
        $evaluaciones = [];
        $puntuacion_total = 0;
        $contribuciones_premisas = [];

        // Inicializar contribuciones
        foreach (self::PREMISAS as $premisa_id => $premisa) {
            $contribuciones_premisas[$premisa_id] = [
                'puntuacion' => 0,
                'modulos' => [],
            ];
        }

        foreach ($modulos_activos as $modulo_id) {
            $valoracion = $this->get_valoracion_modulo($modulo_id);

            if ($valoracion) {
                $evaluaciones[$modulo_id] = $valoracion;
                $puntuacion_total += $valoracion['puntuacion'];

                // Calcular contribución a cada premisa
                foreach ($valoracion['premisas'] as $premisa_id => $peso) {
                    $contribucion = $valoracion['puntuacion'] * $peso;
                    $contribuciones_premisas[$premisa_id]['puntuacion'] += $contribucion;
                    $contribuciones_premisas[$premisa_id]['modulos'][] = [
                        'modulo_id' => $modulo_id,
                        'nombre' => $valoracion['nombre'],
                        'contribucion' => $contribucion,
                    ];
                }
            }
        }

        // Calcular puntuación global (promedio ponderado)
        $num_modulos = count($evaluaciones);
        $puntuacion_global = $num_modulos > 0 ? round($puntuacion_total / $num_modulos) : 0;

        // Determinar nivel
        $nivel = $this->determinar_nivel($puntuacion_global);

        // Calcular puntuación por premisa (normalizada a 100)
        $puntuaciones_premisas = [];
        foreach ($contribuciones_premisas as $premisa_id => $datos) {
            $max_posible = $num_modulos * 100 * self::PREMISAS[$premisa_id]['peso'];
            $puntuaciones_premisas[$premisa_id] = $max_posible > 0
                ? round(($datos['puntuacion'] / $max_posible) * 100)
                : 0;
        }

        $this->evaluacion_cache = [
            'puntuacion_global' => $puntuacion_global,
            'nivel' => $nivel,
            'modulos_evaluados' => $evaluaciones,
            'num_modulos' => $num_modulos,
            'contribuciones_premisas' => $contribuciones_premisas,
            'puntuaciones_premisas' => $puntuaciones_premisas,
            'premisas' => self::PREMISAS,
            'fecha_evaluacion' => current_time('mysql'),
        ];

        return $this->evaluacion_cache;
    }

    /**
     * Crea una evaluación vacía
     *
     * @return array
     */
    private function crear_evaluacion_vacia(): array {
        return [
            'puntuacion_global' => 0,
            'nivel' => self::NIVELES['ninguno'],
            'modulos_evaluados' => [],
            'num_modulos' => 0,
            'contribuciones_premisas' => [],
            'puntuaciones_premisas' => [],
            'premisas' => self::PREMISAS,
            'fecha_evaluacion' => current_time('mysql'),
        ];
    }

    /**
     * Obtiene la lista de módulos activos (excluyendo este módulo)
     *
     * @return array IDs de módulos activos
     */
    public function get_modulos_activos(): array {
        $modulos = [];

        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            if (method_exists($loader, 'get_active_modules')) {
                $activos = $loader->get_active_modules();
                foreach ($activos as $modulo) {
                    $id = is_object($modulo) ? $modulo->get_id() : $modulo;
                    // Excluir este módulo de la evaluación
                    if ($id !== 'sello_conciencia') {
                        $modulos[] = $id;
                    }
                }
            }
        }

        if (empty($modulos)) {
            $modulos = get_option('flavor_active_modules', []);
            $modulos = array_filter($modulos, function($id) {
                return $id !== 'sello_conciencia';
            });
        }

        // Normalizar IDs (guiones a guiones bajos)
        return array_map(function($id) {
            return str_replace('-', '_', $id);
        }, $modulos);
    }

    /**
     * Obtiene la valoración de un módulo específico
     *
     * @param string $modulo_id ID del módulo
     * @return array|null Valoración o null si no existe
     */
    public function get_valoracion_modulo(string $modulo_id): ?array {
        $modulo_id = str_replace('-', '_', $modulo_id);

        if (isset(self::MODULOS_VALORACION[$modulo_id])) {
            return self::MODULOS_VALORACION[$modulo_id];
        }

        // Intentar obtener valoración del propio módulo
        return $this->get_valoracion_desde_modulo($modulo_id);
    }

    /**
     * Obtiene valoración desde el propio módulo (si implementa el método)
     *
     * @param string $modulo_id ID del módulo
     * @return array|null
     */
    private function get_valoracion_desde_modulo(string $modulo_id): ?array {
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $modulo = $loader->get_module($modulo_id);

            if ($modulo && method_exists($modulo, 'get_consciousness_valuation')) {
                return $modulo->get_consciousness_valuation();
            }
        }

        // Valoración por defecto para módulos no catalogados
        return [
            'nombre' => ucfirst(str_replace('_', ' ', $modulo_id)),
            'puntuacion' => 50,
            'premisas' => [
                'abundancia_organizable' => 0.25,
                'interdependencia_radical' => 0.25,
                'conciencia_fundamental' => 0.25,
                'valor_intrinseco' => 0.25,
            ],
            'descripcion_contribucion' => __('Módulo sin valoración específica.', 'flavor-chat-ia'),
            'categoria' => 'otros',
        ];
    }

    /**
     * Determina el nivel de conciencia según la puntuación
     *
     * @param int $puntuacion Puntuación global
     * @return array Datos del nivel
     */
    public function determinar_nivel(int $puntuacion): array {
        foreach (self::NIVELES as $nivel) {
            if ($puntuacion >= $nivel['min_puntuacion'] && $puntuacion <= $nivel['max_puntuacion']) {
                return $nivel;
            }
        }

        return self::NIVELES['ninguno'];
    }

    /**
     * Invalida el cache de evaluación
     */
    public function invalidar_cache(): void {
        $this->evaluacion_cache = null;
        delete_transient('flavor_sello_conciencia_evaluacion');
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * WIDGET DE DASHBOARD
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Registra el widget de dashboard
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     */
    public function register_dashboard_widget($registry): void {
        $settings = $this->get_settings();

        if (empty($settings['mostrar_en_dashboard'])) {
            return;
        }

        $widget_path = dirname(__FILE__) . '/class-sello-conciencia-widget.php';

        if (!class_exists('Flavor_Sello_Conciencia_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        if (class_exists('Flavor_Sello_Conciencia_Widget')) {
            $registry->register(new Flavor_Sello_Conciencia_Widget($this));
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * SHORTCODES
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Shortcode para mostrar el sello
     * Uso: [sello_conciencia size="medium" show_details="true"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_sello($atts): string {
        $atts = shortcode_atts([
            'size' => 'medium',
            'show_details' => 'false',
        ], $atts);

        $evaluacion = $this->evaluar();

        ob_start();
        include dirname(__FILE__) . '/templates/badge.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para mostrar las premisas
     * Uso: [premisas_conciencia]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_premisas($atts): string {
        ob_start();
        include dirname(__FILE__) . '/templates/premisas.php';
        return ob_get_clean();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * AJAX
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Handler AJAX para evaluar
     */
    public function ajax_evaluar(): void {
        check_ajax_referer('sello_conciencia_nonce', 'nonce');

        $this->invalidar_cache();
        $evaluacion = $this->evaluar();

        wp_send_json_success($evaluacion);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * ASSETS
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        wp_enqueue_style(
            'flavor-sello-conciencia',
            FLAVOR_CHAT_IA_URL . 'includes/modules/sello-conciencia/assets/css/sello-conciencia.css',
            [],
            self::VERSION
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook): void {
        if (strpos($hook, 'flavor') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-sello-conciencia-admin',
            FLAVOR_CHAT_IA_URL . 'includes/modules/sello-conciencia/assets/css/sello-conciencia.css',
            [],
            self::VERSION
        );
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * MÉTODOS DE CONSULTA
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * Obtiene todas las premisas
     *
     * @return array
     */
    public function get_premisas(): array {
        return self::PREMISAS;
    }

    /**
     * Obtiene todos los niveles
     *
     * @return array
     */
    public function get_niveles(): array {
        return self::NIVELES;
    }

    /**
     * Obtiene todas las valoraciones de módulos
     *
     * @return array
     */
    public function get_todas_valoraciones(): array {
        return self::MODULOS_VALORACION;
    }

    /**
     * Obtiene un resumen rápido del estado actual
     *
     * @return array
     */
    public function get_resumen(): array {
        $evaluacion = $this->evaluar();

        return [
            'puntuacion' => $evaluacion['puntuacion_global'],
            'nivel' => $evaluacion['nivel']['nombre'],
            'nivel_id' => $evaluacion['nivel']['id'],
            'color' => $evaluacion['nivel']['color'],
            'icono' => $evaluacion['nivel']['icono'],
            'modulos_activos' => $evaluacion['num_modulos'],
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * INTEGRACIÓN CON CHAT IA
     * ═══════════════════════════════════════════════════════════════════════════
     */

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'evaluar' => [
                'description' => 'Evaluar el nivel de conciencia de la aplicación',
                'params' => [],
            ],
            'ver_premisas' => [
                'description' => 'Ver las 5 premisas fundamentales',
                'params' => [],
            ],
            'ver_valoracion_modulo' => [
                'description' => 'Ver la valoración de un módulo específico',
                'params' => ['modulo_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        switch ($nombre_accion) {
            case 'evaluar':
                return [
                    'success' => true,
                    'evaluacion' => $this->evaluar(),
                ];

            case 'ver_premisas':
                return [
                    'success' => true,
                    'premisas' => self::PREMISAS,
                ];

            case 'ver_valoracion_modulo':
                $modulo_id = $parametros['modulo_id'] ?? '';
                $valoracion = $this->get_valoracion_modulo($modulo_id);
                return [
                    'success' => $valoracion !== null,
                    'valoracion' => $valoracion,
                ];

            default:
                return [
                    'success' => false,
                    'error' => __('Acción no reconocida', 'flavor-chat-ia'),
                ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        $premisas_texto = '';
        foreach (self::PREMISAS as $premisa) {
            $premisas_texto .= "\n\n**{$premisa['nombre']}**\n";
            $premisas_texto .= "{$premisa['descripcion']}\n";
            $premisas_texto .= "Principio: {$premisa['principio']}\n";
            $premisas_texto .= "En la práctica: {$premisa['consecuencia']}";
        }

        return <<<KNOWLEDGE
**Sello de Conciencia - App Consciente**

Este módulo evalúa automáticamente el nivel de conciencia de la aplicación basándose en los módulos activos y su alineación con las 5 premisas fundamentales de una economía consciente.

**Las 5 Premisas Fundamentales:**
{$premisas_texto}

**Niveles de Conciencia:**
- Básico (1-25): Cumple requisitos mínimos
- En Transición (26-50): En proceso de mejora
- Consciente (51-75): Integra activamente las premisas
- Referente (76-100): Ejemplo de economía consciente

**Comandos disponibles:**
- "evaluar conciencia": muestra la evaluación actual
- "ver premisas": explica las 5 premisas
- "valoración de [módulo]": muestra cómo contribuye un módulo específico
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué es el Sello de Conciencia?',
                'respuesta' => 'Es un sistema de evaluación automática que mide el nivel de conciencia de la aplicación según los módulos activos y su alineación con las 5 premisas de una economía consciente.',
            ],
            [
                'pregunta' => '¿Cómo se calcula la puntuación?',
                'respuesta' => 'Cada módulo tiene una valoración de 0-100 según su contribución a las 5 premisas. La puntuación global es el promedio de todos los módulos activos.',
            ],
            [
                'pregunta' => '¿Cuáles son las 5 premisas?',
                'respuesta' => '1) La conciencia es fundamental, 2) La abundancia es organizable, 3) La interdependencia es radical, 4) La madurez es cíclica, 5) El valor es intrínseco.',
            ],
            [
                'pregunta' => '¿Puedo mejorar mi nivel de conciencia?',
                'respuesta' => 'Sí, activando módulos que contribuyan más a las premisas de conciencia, como Banco de Tiempo, Grupos de Consumo, Espacios Comunes o Red de Cuidados.',
            ],
        ];
    }
}
