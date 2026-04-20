<?php
/**
 * Contrato de una fuente de datos consumible por bloques dinámicos de VBP.
 *
 * Los módulos del ecosistema (eventos, socios, biblioteca, etc.) implementan
 * esta interfaz para exponerse al editor como "collections". El bloque
 * Dynamic List del canvas pregunta al registry por las fuentes disponibles,
 * ejecuta consultas a través de ellas y renderiza los items devueltos.
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Flavor_VBP_Collection_Source {

    /**
     * Identificador único de la colección. Se usa en URLs REST y en el
     * storage de los bloques del canvas, por lo que debe ser estable entre
     * versiones (ej: "eventos", "socios", "biblioteca").
     *
     * @return string
     */
    public function get_identifier();

    /**
     * Etiqueta legible mostrada en el selector del inspector.
     *
     * @return string
     */
    public function get_label();

    /**
     * Descripción corta opcional mostrada como ayuda en el inspector.
     *
     * @return string
     */
    public function get_description();

    /**
     * Metadatos de los campos de query soportados. Cada entrada describe un
     * filtro configurable desde el inspector:
     *
     *   [
     *     'estado' => [
     *       'label'   => 'Estado',
     *       'type'    => 'enum',          // string|int|bool|enum|date
     *       'options' => ['publicado', 'borrador'], // solo enum
     *       'default' => 'publicado',
     *     ],
     *     'limit' => [
     *       'label'   => 'Máximo de items',
     *       'type'    => 'int',
     *       'min'     => 1,
     *       'max'     => 100,
     *       'default' => 10,
     *     ],
     *   ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_query_fields();

    /**
     * Ejecuta la consulta y devuelve los items normalizados. Cada item es
     * un array asociativo con claves comunes que los bloques pueden
     * referenciar: `id`, `title`, `excerpt`, `image`, `url`, `date`. Los
     * campos específicos del módulo pueden añadirse bajo la clave `meta`.
     *
     * La implementación es responsable de sanear $query_args, respetar
     * permisos del usuario actual y limitar resultados a valores seguros.
     *
     * @param array<string, mixed> $query_args Argumentos validados contra get_query_fields().
     * @return array<int, array<string, mixed>>
     */
    public function query( array $query_args );

    /**
     * Devuelve el número total de items que hacen match con $query_args,
     * ignorando limit/page. Se usa para construir metadata de paginación
     * en la respuesta REST (total, total_pages, has_more). Las fuentes
     * suelen obtener esto vía trait Flavor_VBP_Paginated_Collection_Trait.
     *
     * @param array<string, mixed> $query_args Mismos args que query().
     * @return int
     */
    public function get_total_count( array $query_args );
}
