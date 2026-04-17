<?php
/**
 * Trait para respuestas REST estandarizadas
 *
 * Proporciona métodos comunes para formatear respuestas REST
 * con estructura consistente en toda la plataforma.
 *
 * @package FlavorPlatform
 * @subpackage Traits
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para respuestas REST
 */
trait Flavor_REST_Response_Trait {

    /**
     * Respuesta de éxito
     *
     * @param mixed  $data    Datos a devolver.
     * @param string $message Mensaje opcional.
     * @param int    $status  Código HTTP (default 200).
     * @return WP_REST_Response
     */
    protected function success_response($data, $message = '', $status = 200) {
        $response = [
            'success' => true,
            'data'    => $data,
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return new WP_REST_Response($response, $status);
    }

    /**
     * Respuesta de error
     *
     * @param string $code    Código de error.
     * @param string $message Mensaje de error.
     * @param int    $status  Código HTTP (default 400).
     * @param array  $data    Datos adicionales.
     * @return WP_Error
     */
    protected function error_response($code, $message, $status = 400, array $data = []) {
        $error_data = array_merge(['status' => $status], $data);
        return new WP_Error($code, $message, $error_data);
    }

    /**
     * Respuesta paginada
     *
     * @param array $items      Items a devolver.
     * @param int   $total      Total de items.
     * @param int   $page       Página actual.
     * @param int   $per_page   Items por página.
     * @param array $extra_meta Metadata adicional.
     * @return WP_REST_Response
     */
    protected function paginated_response(array $items, $total, $page, $per_page, array $extra_meta = []) {
        $total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;

        $meta = array_merge([
            'total'       => (int) $total,
            'page'        => (int) $page,
            'per_page'    => (int) $per_page,
            'total_pages' => $total_pages,
            'has_more'    => $page < $total_pages,
        ], $extra_meta);

        return new WP_REST_Response([
            'success' => true,
            'data'    => $items,
            'meta'    => $meta,
        ], 200);
    }

    /**
     * Respuesta de item no encontrado
     *
     * @param string $item_type Tipo de item (ej: 'evento', 'socio').
     * @return WP_Error
     */
    protected function not_found_response($item_type = 'item') {
        return $this->error_response(
            'not_found',
            sprintf(__('%s no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN), ucfirst($item_type)),
            404
        );
    }

    /**
     * Respuesta de acceso denegado
     *
     * @param string $reason Razón opcional.
     * @return WP_Error
     */
    protected function forbidden_response($reason = '') {
        $message = __('No tienes permiso para realizar esta acción', FLAVOR_PLATFORM_TEXT_DOMAIN);
        if (!empty($reason)) {
            $message .= ': ' . $reason;
        }
        return $this->error_response('forbidden', $message, 403);
    }

    /**
     * Respuesta de validación fallida
     *
     * @param array $errors Errores de validación ['campo' => 'mensaje'].
     * @return WP_Error
     */
    protected function validation_error_response(array $errors) {
        return $this->error_response(
            'validation_failed',
            __('Error de validación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            422,
            ['validation_errors' => $errors]
        );
    }

    /**
     * Respuesta de rate limit excedido
     *
     * @param int $retry_after Segundos hasta poder reintentar.
     * @return WP_Error
     */
    protected function rate_limit_response($retry_after = 60) {
        return $this->error_response(
            'rate_limit_exceeded',
            __('Demasiadas solicitudes. Intenta de nuevo más tarde.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            429,
            ['retry_after' => $retry_after]
        );
    }
}
