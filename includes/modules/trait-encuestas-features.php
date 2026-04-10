<?php
/**
 * Trait para integrar funcionalidades de encuestas en otros módulos
 *
 * Este trait permite que cualquier módulo del sistema integre fácilmente
 * encuestas embebidas sin necesidad de implementar toda la lógica.
 *
 * Uso:
 *   class Mi_Modulo {
 *       use Flavor_Encuestas_Features;
 *
 *       public function init() {
 *           $this->init_encuestas_features('mi_modulo');
 *       }
 *   }
 *
 * @package FlavorPlatform
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Encuestas_Features {

    /**
     * Tipo de contexto para encuestas
     *
     * @var string
     */
    protected $encuestas_contexto_tipo = '';

    /**
     * Instancia del módulo de encuestas
     *
     * @var Flavor_Platform_Module_Interface|null
     */
    protected $encuestas_module = null;

    /**
     * Inicializa las funcionalidades de encuestas
     *
     * @param string $contexto_tipo Identificador del tipo de contexto (ej: 'foro', 'comunidad')
     */
    protected function init_encuestas_features($contexto_tipo) {
        $this->encuestas_contexto_tipo = $contexto_tipo;

        // Verificar que el módulo de encuestas está activo
        if (!$this->is_encuestas_module_active()) {
            return;
        }

        // Obtener instancia del módulo
        $this->encuestas_module = $this->get_encuestas_module();

        // Registrar hooks
        $this->register_encuestas_hooks();
    }

    /**
     * Verifica si el módulo de encuestas está activo
     *
     * @return bool
     */
    protected function is_encuestas_module_active() {
        return Flavor_Platform_Module_Loader::is_module_active('encuestas');
    }

    /**
     * Obtiene la instancia del módulo de encuestas
     *
     * @return Flavor_Platform_Module_Interface|null
     */
    protected function get_encuestas_module() {
        if ($this->encuestas_module !== null) {
            return $this->encuestas_module;
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $this->encuestas_module = $loader->get_module('encuestas');

        return $this->encuestas_module;
    }

    /**
     * Registra hooks de encuestas
     */
    protected function register_encuestas_hooks() {
        // Los módulos pueden sobreescribir este método
        // para añadir hooks específicos
    }

    // =========================================================================
    // MÉTODOS PARA CREAR ENCUESTAS
    // =========================================================================

    /**
     * Crea una encuesta vinculada a una entidad
     *
     * @param int $entidad_id ID de la entidad (foro, comunidad, etc.)
     * @param array $datos_encuesta Datos de la encuesta
     * @return int|WP_Error ID de encuesta o error
     */
    protected function crear_encuesta_vinculada($entidad_id, $datos_encuesta) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return new WP_Error('no_module', __('Módulo de encuestas no disponible', 'flavor-platform'));
        }

        // Añadir contexto
        $datos_encuesta['contexto_tipo'] = $this->encuestas_contexto_tipo;
        $datos_encuesta['contexto_id'] = $entidad_id;

        return $module->crear_encuesta($datos_encuesta);
    }

    /**
     * Crea una encuesta simple de selección única
     *
     * @param int $entidad_id ID de la entidad
     * @param string $titulo Título/pregunta
     * @param array $opciones Array de opciones
     * @param array $config Configuración adicional
     * @return int|WP_Error
     */
    protected function crear_encuesta_simple($entidad_id, $titulo, $opciones, $config = []) {
        $datos_encuesta = wp_parse_args($config, [
            'titulo'      => $titulo,
            'tipo'        => 'encuesta',
            'estado'      => 'activa',
            'es_anonima'  => false,
            'campos'      => [
                [
                    'tipo'        => 'seleccion_unica',
                    'etiqueta'    => $titulo,
                    'opciones'    => $opciones,
                    'es_requerido'=> true,
                    'orden'       => 0,
                ],
            ],
        ]);

        return $this->crear_encuesta_vinculada($entidad_id, $datos_encuesta);
    }

    /**
     * Crea una encuesta de múltiples preguntas
     *
     * @param int $entidad_id ID de la entidad
     * @param string $titulo Título general
     * @param array $preguntas Array de preguntas con formato:
     *                         [['texto' => 'Pregunta', 'tipo' => 'seleccion_unica', 'opciones' => [...]], ...]
     * @param array $config Configuración adicional
     * @return int|WP_Error
     */
    protected function crear_formulario($entidad_id, $titulo, $preguntas, $config = []) {
        $campos = [];
        foreach ($preguntas as $indice => $pregunta) {
            $campos[] = [
                'tipo'         => $pregunta['tipo'] ?? 'texto',
                'etiqueta'     => $pregunta['texto'],
                'descripcion'  => $pregunta['descripcion'] ?? '',
                'opciones'     => $pregunta['opciones'] ?? [],
                'es_requerido' => $pregunta['requerido'] ?? true,
                'orden'        => $indice,
            ];
        }

        $datos_encuesta = wp_parse_args($config, [
            'titulo'      => $titulo,
            'tipo'        => 'formulario',
            'estado'      => 'activa',
            'campos'      => $campos,
        ]);

        return $this->crear_encuesta_vinculada($entidad_id, $datos_encuesta);
    }

    // =========================================================================
    // MÉTODOS PARA OBTENER ENCUESTAS
    // =========================================================================

    /**
     * Obtiene encuestas vinculadas a una entidad
     *
     * @param int $entidad_id ID de la entidad
     * @param array $args Argumentos adicionales
     * @return array
     */
    protected function obtener_encuestas_entidad($entidad_id, $args = []) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return [];
        }

        return $module->listar_por_contexto($this->encuestas_contexto_tipo, $entidad_id, $args);
    }

    /**
     * Obtiene una encuesta específica
     *
     * @param int $encuesta_id ID de la encuesta
     * @return object|null
     */
    protected function obtener_encuesta($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return null;
        }

        return $module->obtener_encuesta($encuesta_id);
    }

    /**
     * Cuenta encuestas activas de una entidad
     *
     * @param int $entidad_id ID de la entidad
     * @return int
     */
    protected function contar_encuestas_activas($entidad_id) {
        $encuestas = $this->obtener_encuestas_entidad($entidad_id, ['estado' => 'activa']);
        return count($encuestas);
    }

    // =========================================================================
    // MÉTODOS PARA RESPONDER
    // =========================================================================

    /**
     * Registra respuestas a una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @param array $respuestas Respuestas [campo_id => valor]
     * @return bool|WP_Error
     */
    protected function responder_encuesta($encuesta_id, $respuestas) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return new WP_Error('no_module', __('Módulo de encuestas no disponible', 'flavor-platform'));
        }

        return $module->registrar_respuestas($encuesta_id, $respuestas);
    }

    /**
     * Verifica si el usuario actual ya participó
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool
     */
    protected function usuario_ya_participo($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return false;
        }

        return $module->usuario_ya_participo($encuesta_id, get_current_user_id());
    }

    // =========================================================================
    // MÉTODOS PARA RESULTADOS
    // =========================================================================

    /**
     * Obtiene resultados de una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return array
     */
    protected function obtener_resultados_encuesta($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return [];
        }

        return $module->obtener_resultados($encuesta_id);
    }

    /**
     * Verifica si el usuario puede ver resultados
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool
     */
    protected function puede_ver_resultados($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return false;
        }

        return $module->puede_ver_resultados($encuesta_id);
    }

    // =========================================================================
    // MÉTODOS PARA RENDERIZADO
    // =========================================================================

    /**
     * Renderiza una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return string HTML
     */
    protected function render_encuesta($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return '';
        }

        $renderer = $module->get_renderer();
        if (!$renderer) {
            return '';
        }

        return $renderer->render_encuesta($encuesta_id);
    }

    /**
     * Renderiza encuesta en formato mini (para chat/feed)
     *
     * @param int $encuesta_id ID de la encuesta
     * @return string HTML
     */
    protected function render_encuesta_mini($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return '';
        }

        $renderer = $module->get_renderer();
        if (!$renderer) {
            return '';
        }

        return $renderer->render_encuesta_mini($encuesta_id);
    }

    /**
     * Renderiza formulario de creación de encuesta
     *
     * @param int $entidad_id ID de la entidad donde crear encuesta
     * @return string HTML
     */
    protected function render_crear_encuesta($entidad_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return '';
        }

        $renderer = $module->get_renderer();
        if (!$renderer) {
            return '';
        }

        return $renderer->render_formulario_crear([
            'contexto'    => $this->encuestas_contexto_tipo,
            'contexto_id' => $entidad_id,
        ]);
    }

    /**
     * Renderiza lista de encuestas de una entidad
     *
     * @param int $entidad_id ID de la entidad
     * @param array $args Argumentos adicionales
     * @return string HTML
     */
    protected function render_lista_encuestas($entidad_id, $args = []) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return '';
        }

        $renderer = $module->get_renderer();
        if (!$renderer) {
            return '';
        }

        $args = wp_parse_args($args, [
            'tipo'   => $this->encuestas_contexto_tipo,
            'id'     => $entidad_id,
            'estado' => 'activa',
            'limit'  => 10,
        ]);

        return $renderer->render_lista_contexto($args);
    }

    // =========================================================================
    // MÉTODOS PARA ADMINISTRACIÓN
    // =========================================================================

    /**
     * Cierra una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool|WP_Error
     */
    protected function cerrar_encuesta($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return new WP_Error('no_module', __('Módulo de encuestas no disponible', 'flavor-platform'));
        }

        return $module->cerrar_encuesta($encuesta_id);
    }

    /**
     * Elimina una encuesta
     *
     * @param int $encuesta_id ID de la encuesta
     * @return bool|WP_Error
     */
    protected function eliminar_encuesta($encuesta_id) {
        $module = $this->get_encuestas_module();
        if (!$module) {
            return new WP_Error('no_module', __('Módulo de encuestas no disponible', 'flavor-platform'));
        }

        return $module->eliminar_encuesta($encuesta_id);
    }

    /**
     * Elimina todas las encuestas de una entidad
     * Útil cuando se elimina la entidad padre (ej: eliminar foro)
     *
     * @param int $entidad_id ID de la entidad
     * @return int Número de encuestas eliminadas
     */
    protected function eliminar_encuestas_entidad($entidad_id) {
        $encuestas = $this->obtener_encuestas_entidad($entidad_id, ['estado' => '']);
        $eliminadas = 0;

        foreach ($encuestas as $encuesta) {
            $resultado = $this->eliminar_encuesta($encuesta->id);
            if (!is_wp_error($resultado)) {
                $eliminadas++;
            }
        }

        return $eliminadas;
    }

    // =========================================================================
    // HOOKS Y FILTROS
    // =========================================================================

    /**
     * Registra que este módulo soporta encuestas
     * Útil para mostrar opciones de encuesta en la UI
     *
     * @param array $modulos Lista de módulos que soportan encuestas
     * @return array
     */
    public function registrar_soporte_encuestas($modulos) {
        $modulos[$this->encuestas_contexto_tipo] = [
            'label'       => $this->get_name(),
            'module_id'   => $this->get_id(),
            'icon'        => $this->module_icon ?? 'dashicons-admin-generic',
        ];

        return $modulos;
    }

    /**
     * Añade tab de encuestas en la vista de entidad
     * Los módulos pueden llamar este método para añadir una tab
     *
     * @param array $tabs Tabs existentes
     * @param int $entidad_id ID de la entidad
     * @return array
     */
    protected function add_encuestas_tab($tabs, $entidad_id) {
        if (!$this->is_encuestas_module_active()) {
            return $tabs;
        }

        $total_encuestas = $this->contar_encuestas_activas($entidad_id);

        $tabs['encuestas'] = [
            'label'    => sprintf(
                __('Encuestas %s', 'flavor-platform'),
                $total_encuestas > 0 ? "($total_encuestas)" : ''
            ),
            'icon'     => 'dashicons-forms',
            'callback' => function() use ($entidad_id) {
                return $this->render_tab_encuestas($entidad_id);
            },
            'priority' => 60,
        ];

        return $tabs;
    }

    /**
     * Renderiza contenido de la tab de encuestas
     *
     * @param int $entidad_id ID de la entidad
     * @return string HTML
     */
    protected function render_tab_encuestas($entidad_id) {
        ob_start();
        ?>
        <div class="flavor-encuestas-tab">
            <div class="flavor-encuestas-tab__header">
                <h3><?php esc_html_e('Encuestas', 'flavor-platform'); ?></h3>

                <?php if (is_user_logged_in()): ?>
                    <button type="button"
                            class="flavor-encuestas-tab__crear"
                            data-toggle="crear-encuesta">
                        + <?php esc_html_e('Crear encuesta', 'flavor-platform'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="flavor-encuestas-tab__crear-form" style="display: none;">
                <?php echo $this->render_crear_encuesta($entidad_id); ?>
            </div>

            <div class="flavor-encuestas-tab__lista">
                <?php echo $this->render_lista_encuestas($entidad_id); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
