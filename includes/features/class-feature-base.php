<?php
/**
 * Clase Base para Features Compartidas
 *
 * Define la interfaz común para todas las features del sistema
 * (ratings, favorites, comments, follow, share, etc.)
 *
 * @package FlavorChatIA
 * @subpackage Features
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase abstracta base para todas las features
 */
abstract class Flavor_Feature_Base {

    /**
     * ID de la feature
     *
     * @var string
     */
    protected $feature_id = '';

    /**
     * Instancia singleton
     *
     * @var static|null
     */
    protected static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @return static
     */
    public static function get_instance() {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Constructor protegido
     */
    protected function __construct() {
        $this->init();
    }

    /**
     * Inicialización de la feature
     *
     * @return void
     */
    abstract protected function init();

    /**
     * Renderizar la feature para una entidad
     *
     * @param string $entity_type Tipo de entidad (ej: 'evento', 'producto')
     * @param int $entity_id ID de la entidad
     * @param array $args Argumentos adicionales
     * @return string HTML de la feature
     */
    abstract public function render($entity_type, $entity_id, $args = []);

    /**
     * Obtener datos de la feature para una entidad
     *
     * @param string $entity_type Tipo de entidad
     * @param int $entity_id ID de la entidad
     * @return array Datos de la feature
     */
    abstract public function get_data($entity_type, $entity_id);

    /**
     * Registrar acción del usuario
     *
     * @param string $entity_type Tipo de entidad
     * @param int $entity_id ID de la entidad
     * @param int $user_id ID del usuario
     * @param mixed $value Valor de la acción
     * @return bool|WP_Error
     */
    abstract public function register_action($entity_type, $entity_id, $user_id, $value = null);

    /**
     * Verificar si el usuario puede usar esta feature
     *
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function user_can_use($user_id) {
        return is_user_logged_in() || $this->allow_anonymous();
    }

    /**
     * Verificar si la feature permite uso anónimo
     *
     * @return bool
     */
    protected function allow_anonymous() {
        return false;
    }

    /**
     * Obtener el ID de la feature
     *
     * @return string
     */
    public function get_id() {
        return $this->feature_id;
    }
}
