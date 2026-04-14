<?php
/**
 * Interface para Exportadores de Archivos Descargables
 *
 * Extiende la interfaz Data_Exporter para anadir funcionalidades
 * especificas de descarga de archivos, como generacion de tokens
 * de descarga, servir archivos y limpieza de exportaciones antiguas.
 *
 * @package    FlavorPlatform
 * @subpackage Interfaces
 * @since      2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface Flavor_File_Exporter_Interface
 *
 * Contrato para exportadores que generan archivos descargables.
 * Incluye metodos para manejo de archivos temporales,
 * tokens de descarga seguros y limpieza automatica.
 *
 * Clases que podrian implementar esta interfaz:
 * - Flavor_Data_Exporter (genera ZIPs con datos de usuario)
 * - Report_Exporter (genera CSVs de reportes)
 *
 * @since 2.2.0
 */
interface Flavor_File_Exporter_Interface extends Flavor_Data_Exporter_Interface {

    /**
     * Exporta los datos a un archivo en el servidor.
     *
     * Genera un archivo de exportacion y lo guarda en el directorio
     * de exportaciones del servidor para su posterior descarga.
     *
     * @since 2.2.0
     *
     * @param string $format  Formato de exportacion.
     * @param array  $options Opciones de exportacion.
     *
     * @return array|WP_Error Array con informacion del archivo generado o WP_Error.
     *                        El array debe incluir:
     *                        - 'path': Ruta absoluta al archivo
     *                        - 'filename': Nombre del archivo
     *                        - 'url': URL de descarga (con token si es necesario)
     *                        - 'size': Tamano del archivo en bytes
     *                        - 'mime_type': Tipo MIME
     */
    public function export_to_file( string $format, array $options = array() );

    /**
     * Genera un token de descarga temporal seguro.
     *
     * Crea un token unico que permite descargar el archivo de forma
     * segura sin exponer la ruta directa del archivo.
     *
     * @since 2.2.0
     *
     * @param string $filename   Nombre del archivo para el que se genera el token.
     * @param int    $expiration Tiempo de expiracion en segundos (por defecto 1 hora).
     *
     * @return string Token unico de descarga.
     */
    public function generate_download_token( string $filename, int $expiration = 3600 ): string;

    /**
     * Valida un token de descarga.
     *
     * Verifica que el token sea valido, no haya expirado y corresponda
     * al archivo solicitado.
     *
     * @since 2.2.0
     *
     * @param string $token    Token a validar.
     * @param string $filename Nombre del archivo asociado al token.
     *
     * @return bool True si el token es valido, false en caso contrario.
     */
    public function validate_download_token( string $token, string $filename ): bool;

    /**
     * Sirve un archivo para descarga.
     *
     * Envia el archivo al navegador con las cabeceras HTTP apropiadas
     * para forzar la descarga. Este metodo termina la ejecucion del script.
     *
     * @since 2.2.0
     *
     * @param string $filename Nombre del archivo a servir.
     *
     * @return void Este metodo no retorna, termina con exit().
     */
    public function serve_download( string $filename ): void;

    /**
     * Obtiene el directorio de exportaciones.
     *
     * Retorna la ruta absoluta al directorio donde se almacenan
     * los archivos de exportacion temporales.
     *
     * @since 2.2.0
     *
     * @return string Ruta absoluta al directorio de exportaciones.
     */
    public function get_export_directory(): string;

    /**
     * Obtiene la URL del directorio de exportaciones.
     *
     * Retorna la URL base para acceder a los archivos exportados.
     *
     * @since 2.2.0
     *
     * @return string URL del directorio de exportaciones.
     */
    public function get_export_url(): string;

    /**
     * Limpia archivos de exportacion antiguos.
     *
     * Elimina archivos de exportacion que excedan el tiempo maximo
     * de retencion configurado.
     *
     * @since 2.2.0
     *
     * @param int $max_age_seconds Edad maxima de los archivos en segundos.
     *                             Por defecto 86400 (24 horas).
     *
     * @return int Numero de archivos eliminados.
     */
    public function cleanup_old_exports( int $max_age_seconds = 86400 ): int;

    /**
     * Verifica si existe un archivo de exportacion.
     *
     * @since 2.2.0
     *
     * @param string $filename Nombre del archivo a verificar.
     *
     * @return bool True si el archivo existe, false en caso contrario.
     */
    public function file_exists( string $filename ): bool;

    /**
     * Elimina un archivo de exportacion especifico.
     *
     * @since 2.2.0
     *
     * @param string $filename Nombre del archivo a eliminar.
     *
     * @return bool True si se elimino correctamente, false si fallo o no existia.
     */
    public function delete_file( string $filename ): bool;

    /**
     * Obtiene el tamano maximo de exportacion permitido.
     *
     * Retorna el tamano maximo en bytes que puede tener una exportacion.
     * Util para exportaciones grandes que podrian exceder los limites de memoria.
     *
     * @since 2.2.0
     *
     * @return int Tamano maximo en bytes, 0 para sin limite.
     */
    public function get_max_export_size(): int;

    /**
     * Verifica si la exportacion requiere procesamiento en lotes.
     *
     * Para exportaciones grandes, puede ser necesario procesar
     * los datos en lotes para evitar timeouts o problemas de memoria.
     *
     * @since 2.2.0
     *
     * @param array $options Opciones de exportacion.
     *
     * @return bool True si requiere procesamiento en lotes.
     */
    public function requires_batch_processing( array $options = array() ): bool;
}
