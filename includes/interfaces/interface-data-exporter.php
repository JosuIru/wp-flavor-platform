<?php
/**
 * Interface para Exportadores de Datos
 *
 * Define el contrato que deben cumplir todas las clases que exportan datos
 * en Flavor Platform. Esta interfaz permite exportar datos en multiples
 * formatos (CSV, JSON, XML, etc.) de manera consistente.
 *
 * @package    FlavorPlatform
 * @subpackage Interfaces
 * @since      2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface Flavor_Data_Exporter_Interface
 *
 * Contrato estandar para exportadores de datos.
 * Los exportadores pueden manejar diferentes tipos de datos
 * (usuarios, reportes, tokens de diseno, etc.) y formatos de salida.
 *
 * Clases que podrian implementar esta interfaz:
 * - Flavor_Data_Exporter (privacy)
 * - Report_Exporter (analytics)
 * - Flavor_Design_Tokens_Exporter (design tokens)
 *
 * @since 2.2.0
 */
interface Flavor_Data_Exporter_Interface {

    /**
     * Exporta los datos en el formato especificado.
     *
     * Este es el metodo principal de exportacion. Debe devolver los datos
     * exportados en el formato solicitado o un WP_Error en caso de fallo.
     *
     * @since 2.2.0
     *
     * @param string $format  Formato de exportacion (csv, json, xml, etc.).
     *                        Debe ser uno de los formatos soportados.
     * @param array  $options Opciones adicionales de exportacion.
     *                        Puede incluir filtros, limites, campos especificos, etc.
     *                        - 'limit': Numero maximo de registros.
     *                        - 'offset': Desplazamiento para paginacion.
     *                        - 'fields': Campos especificos a incluir.
     *                        - 'filters': Condiciones de filtrado.
     *
     * @return array|string|WP_Error Datos exportados como array/string o WP_Error en caso de error.
     *                               El tipo de retorno depende del formato:
     *                               - 'json': string JSON
     *                               - 'csv': string CSV
     *                               - 'array': array PHP
     */
    public function export( string $format = 'json', array $options = array() );

    /**
     * Obtiene el formato de exportacion por defecto.
     *
     * @since 2.2.0
     *
     * @return string El identificador del formato por defecto (ej: 'json', 'csv').
     */
    public function get_default_format(): string;

    /**
     * Obtiene la lista de formatos de exportacion soportados.
     *
     * @since 2.2.0
     *
     * @return array Lista de formatos soportados con sus metadatos.
     *               Cada formato debe incluir:
     *               - 'id': Identificador unico del formato
     *               - 'name': Nombre legible del formato
     *               - 'extension': Extension del archivo (sin punto)
     *               - 'mime_type': Tipo MIME del archivo
     *               Ejemplo:
     *               [
     *                   'csv' => [
     *                       'id' => 'csv',
     *                       'name' => 'CSV',
     *                       'extension' => 'csv',
     *                       'mime_type' => 'text/csv'
     *                   ]
     *               ]
     */
    public function get_supported_formats(): array;

    /**
     * Verifica si un formato de exportacion esta soportado.
     *
     * @since 2.2.0
     *
     * @param string $format Identificador del formato a verificar.
     *
     * @return bool True si el formato esta soportado, false en caso contrario.
     */
    public function supports_format( string $format ): bool;

    /**
     * Genera el nombre de archivo para la exportacion.
     *
     * Crea un nombre de archivo unico y descriptivo para la exportacion,
     * incluyendo tipicamente un timestamp para evitar colisiones.
     *
     * @since 2.2.0
     *
     * @param string $format Formato de exportacion para determinar la extension.
     * @param string $prefix Prefijo opcional para el nombre del archivo.
     *
     * @return string Nombre del archivo generado (ej: 'export-usuarios-2024-01-15-143025.csv').
     */
    public function generate_filename( string $format, string $prefix = '' ): string;

    /**
     * Obtiene el tipo de datos que este exportador maneja.
     *
     * Identifica que tipo de datos exporta esta clase (usuarios, reportes,
     * tokens, configuraciones, etc.).
     *
     * @since 2.2.0
     *
     * @return string Identificador del tipo de datos (ej: 'users', 'reports', 'design-tokens').
     */
    public function get_data_type(): string;

    /**
     * Obtiene los datos crudos antes de formatear.
     *
     * Este metodo recupera los datos en formato array PHP,
     * antes de aplicar cualquier transformacion de formato.
     *
     * @since 2.2.0
     *
     * @param array $options Opciones de filtrado y seleccion de datos.
     *                       - 'limit': Numero maximo de registros.
     *                       - 'offset': Desplazamiento para paginacion.
     *                       - 'fields': Campos especificos a incluir.
     *                       - 'filters': Condiciones de filtrado.
     *
     * @return array|WP_Error Array de datos o WP_Error si falla la recuperacion.
     */
    public function get_data( array $options = array() );

    /**
     * Obtiene los campos disponibles para exportar.
     *
     * Lista todos los campos que pueden incluirse en la exportacion,
     * permitiendo al usuario seleccionar cuales quiere exportar.
     *
     * @since 2.2.0
     *
     * @return array Lista de campos con sus metadatos.
     *               Cada campo debe incluir:
     *               - 'id': Identificador unico del campo
     *               - 'label': Etiqueta legible
     *               - 'type': Tipo de dato (string, int, date, etc.)
     *               - 'exportable': Si puede exportarse (bool)
     *               - 'default': Si se incluye por defecto (bool)
     */
    public function get_exportable_fields(): array;

    /**
     * Valida las opciones de exportacion.
     *
     * Verifica que las opciones proporcionadas sean validas
     * antes de proceder con la exportacion.
     *
     * @since 2.2.0
     *
     * @param array $options Opciones a validar.
     *
     * @return true|WP_Error True si las opciones son validas, WP_Error con detalles del error.
     */
    public function validate_options( array $options );

    /**
     * Obtiene el numero total de registros exportables.
     *
     * Cuenta cuantos registros serian exportados con las opciones dadas,
     * util para mostrar informacion al usuario antes de exportar.
     *
     * @since 2.2.0
     *
     * @param array $options Opciones de filtrado (las mismas que para export()).
     *
     * @return int Numero total de registros que serian exportados.
     */
    public function get_total_count( array $options = array() ): int;
}
