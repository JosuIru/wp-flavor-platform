<?php
/**
 * Gestion de cuotas periodicas (subscriptions) para socios
 *
 * Genera cuotas mensuales automaticas, envia avisos de vencimiento
 * y marca cuotas vencidas mediante WP Cron.
 *
 * @package FlavorChatIA
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase de suscripciones/cuotas periodicas de socios
 */
class Flavor_Socios_Subscriptions {

    const CRON_HOOK_DIARIO = 'flavor_socios_cron_diario';
    const DIAS_AVISO_PREVIO = 7;
    const DIAS_GRACIA_VENCIMIENTO = 30;

    private static $instancia = null;

    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_action( self::CRON_HOOK_DIARIO, array( $this, 'ejecutar_tareas_diarias' ) );
    }

    public static function programar_cron() {
        if ( ! wp_next_scheduled( self::CRON_HOOK_DIARIO ) ) {
            $hora_ejecucion = strtotime( 'tomorrow 03:00:00' );
            wp_schedule_event( $hora_ejecucion, 'daily', self::CRON_HOOK_DIARIO );
        }
    }

    public static function desprogramar_cron() {
        $timestamp_siguiente_ejecucion = wp_next_scheduled( self::CRON_HOOK_DIARIO );
        if ( $timestamp_siguiente_ejecucion ) {
            wp_unschedule_event( $timestamp_siguiente_ejecucion, self::CRON_HOOK_DIARIO );
        }
        wp_clear_scheduled_hook( self::CRON_HOOK_DIARIO );
    }

    public function ejecutar_tareas_diarias() {
        flavor_chat_ia_log( 'Socios Subscriptions: Iniciando tareas diarias de cuotas.', 'info' );
        $this->generar_cuotas_mensuales();
        $this->enviar_avisos_vencimiento();
        $this->marcar_cuotas_vencidas();
        flavor_chat_ia_log( 'Socios Subscriptions: Tareas diarias finalizadas.', 'info' );
    }

    public function generar_cuotas_mensuales() {
        $mes_actual  = (int) gmdate( 'n' );
        $anio_actual = (int) gmdate( 'Y' );

        $lista_socios_activos = $this->obtener_socios_activos();

        if ( empty( $lista_socios_activos ) ) {
            return;
        }

        $configuracion_modulo  = get_option( 'flavor_chat_ia_module_socios_settings', array() );
        $dia_cargo_configurado = isset( $configuracion_modulo['dia_cargo'] ) ? absint( $configuracion_modulo['dia_cargo'] ) : 1;

        foreach ( $lista_socios_activos as $socio ) {
            if ( $this->cuota_ya_generada( (int) $socio->id, $mes_actual, $anio_actual ) ) {
                continue;
            }

            $importe_cuota  = floatval( $socio->cuota_mensual );
            $concepto_cuota = sprintf(
                __( 'Cuota socio %1$s/%2$s', 'flavor-chat-ia' ),
                str_pad( $mes_actual, 2, '0', STR_PAD_LEFT ),
                $anio_actual
            );

            $dia_cargo_real = min( $dia_cargo_configurado, (int) gmdate( 't' ) );
            $fecha_cargo    = sprintf( '%04d-%02d-%02d', $anio_actual, $mes_actual, $dia_cargo_real );

            $this->crear_cuota( (int) $socio->id, $importe_cuota, $concepto_cuota, $fecha_cargo );
        }
    }

    public function enviar_avisos_vencimiento() {
        global $wpdb;

        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $fecha_hoy    = gmdate( 'Y-m-d' );

        $fecha_vencimiento_en_7_dias = gmdate( 'Y-m-d', strtotime( '+' . self::DIAS_AVISO_PREVIO . ' days' ) );

        $cuotas_proximas_a_vencer = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE estado = 'pendiente' AND fecha_cargo = %s",
            $fecha_vencimiento_en_7_dias
        ) );

        foreach ( $cuotas_proximas_a_vencer as $cuota_proxima ) {
            $email_socio_previo = $this->obtener_email_socio( (int) $cuota_proxima->socio_id );
            if ( $email_socio_previo ) {
                $this->enviar_email_aviso_previo( $email_socio_previo, $cuota_proxima );
            }
        }

        $cuotas_vencen_hoy = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE estado = 'pendiente' AND fecha_cargo = %s",
            $fecha_hoy
        ) );

        foreach ( $cuotas_vencen_hoy as $cuota_hoy ) {
            $email_socio_hoy = $this->obtener_email_socio( (int) $cuota_hoy->socio_id );
            if ( $email_socio_hoy ) {
                $this->enviar_email_recordatorio_vencimiento( $email_socio_hoy, $cuota_hoy );
            }
        }

        $fecha_vencida_hace_7_dias = gmdate( 'Y-m-d', strtotime( '-' . self::DIAS_AVISO_PREVIO . ' days' ) );

        $cuotas_vencidas_recientes = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE estado = 'pendiente' AND fecha_cargo = %s",
            $fecha_vencida_hace_7_dias
        ) );

        foreach ( $cuotas_vencidas_recientes as $cuota_vencida ) {
            $email_socio_vencida = $this->obtener_email_socio( (int) $cuota_vencida->socio_id );
            if ( $email_socio_vencida ) {
                $this->enviar_email_cuota_vencida( $email_socio_vencida, $cuota_vencida );
            }
        }
    }

    public function marcar_cuotas_vencidas() {
        global $wpdb;

        $tabla_cuotas        = $wpdb->prefix . 'flavor_socios_cuotas';
        $fecha_limite_gracia = gmdate( 'Y-m-d', strtotime( '-' . self::DIAS_GRACIA_VENCIMIENTO . ' days' ) );

        $total_actualizadas = $wpdb->query( $wpdb->prepare(
            "UPDATE $tabla_cuotas SET estado = 'vencida' WHERE estado = 'pendiente' AND fecha_cargo <= %s",
            $fecha_limite_gracia
        ) );

        if ( $total_actualizadas > 0 ) {
            flavor_chat_ia_log(
                sprintf( 'Socios Subscriptions: %d cuota(s) marcada(s) como vencida(s).', $total_actualizadas ),
                'info'
            );
        }
    }

    private function obtener_socios_activos() {
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socios_activos = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, usuario_id, numero_socio, tipo_socio, cuota_mensual, cuota_reducida
             FROM $tabla_socios
             WHERE estado = %s",
            'activo'
        ) );

        return $socios_activos ? $socios_activos : array();
    }

    private function cuota_ya_generada( $socio_id, $mes, $anio ) {
        global $wpdb;

        $tabla_cuotas    = $wpdb->prefix . 'flavor_socios_cuotas';
        $periodo_buscado = sprintf( '%04d-%02d', $anio, $mes );

        $cuota_existente = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE socio_id = %d AND periodo = %s",
            $socio_id,
            $periodo_buscado
        ) );

        return ( (int) $cuota_existente > 0 );
    }

    private function crear_cuota( $socio_id, $importe_cuota, $concepto_cuota, $fecha_cargo_cuota ) {
        global $wpdb;

        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $periodo_cuota = substr( $fecha_cargo_cuota, 0, 7 );

        $resultado_insercion = $wpdb->insert(
            $tabla_cuotas,
            array(
                'socio_id'    => $socio_id,
                'periodo'     => $periodo_cuota,
                'importe'     => $importe_cuota,
                'fecha_cargo' => $fecha_cargo_cuota,
                'estado'      => 'pendiente',
                'notas'       => $concepto_cuota,
            ),
            array( '%d', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( false === $resultado_insercion ) {
            flavor_chat_ia_log(
                sprintf( 'Socios Subscriptions: Error al crear cuota para socio #%d: %s', $socio_id, $wpdb->last_error ),
                'error'
            );
            return false;
        }

        $identificador_cuota_nueva = $wpdb->insert_id;

        flavor_chat_ia_log(
            sprintf(
                'Socios Subscriptions: Cuota #%d creada para socio #%d - Periodo: %s - Importe: %.2f',
                $identificador_cuota_nueva,
                $socio_id,
                $periodo_cuota,
                $importe_cuota
            ),
            'info'
        );

        return $identificador_cuota_nueva;
    }

    private function obtener_email_socio( $socio_id ) {
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $identificador_usuario = $wpdb->get_var( $wpdb->prepare(
            "SELECT usuario_id FROM $tabla_socios WHERE id = %d",
            $socio_id
        ) );

        if ( ! $identificador_usuario ) {
            return false;
        }

        $datos_usuario = get_userdata( (int) $identificador_usuario );

        if ( ! $datos_usuario ) {
            return false;
        }

        return $datos_usuario->user_email;
    }

    private function enviar_email_aviso_previo( $direccion_email, $cuota ) {
        $nombre_sitio       = get_bloginfo( 'name' );
        $fecha_formateada   = date_i18n( 'd/m/Y', strtotime( $cuota->fecha_cargo ) );
        $importe_formateado = number_format( (float) $cuota->importe, 2, ',', '.' );

        $asunto_email = sprintf(
            __( '[%s] Tu cuota vence pronto', 'flavor-chat-ia' ),
            $nombre_sitio
        );

        $contenido_html = $this->construir_plantilla_email(
            __( 'Tu cuota vence pronto', 'flavor-chat-ia' ),
            sprintf(
                __( 'Te recordamos que tu cuota del periodo <strong>%1$s</strong> por importe de <strong>%2$s &euro;</strong> vence el <strong>%3$s</strong>.', 'flavor-chat-ia' ),
                esc_html( $cuota->periodo ),
                esc_html( $importe_formateado ),
                esc_html( $fecha_formateada )
            ),
            __( 'Por favor, asegurate de tener el pago al corriente antes de la fecha de vencimiento.', 'flavor-chat-ia' ),
            '#3498db'
        );

        $this->enviar_email( $direccion_email, $asunto_email, $contenido_html );
    }

    private function enviar_email_recordatorio_vencimiento( $direccion_email, $cuota ) {
        $nombre_sitio       = get_bloginfo( 'name' );
        $importe_formateado = number_format( (float) $cuota->importe, 2, ',', '.' );

        $asunto_email = sprintf(
            __( '[%s] Recordatorio: Tu cuota vence hoy', 'flavor-chat-ia' ),
            $nombre_sitio
        );

        $contenido_html = $this->construir_plantilla_email(
            __( 'Tu cuota vence hoy', 'flavor-chat-ia' ),
            sprintf(
                __( 'Hoy es la fecha de vencimiento de tu cuota del periodo <strong>%1$s</strong> por importe de <strong>%2$s &euro;</strong>.', 'flavor-chat-ia' ),
                esc_html( $cuota->periodo ),
                esc_html( $importe_formateado )
            ),
            __( 'Si ya has realizado el pago, puedes ignorar este mensaje. En caso contrario, te rogamos que lo hagas a la mayor brevedad posible.', 'flavor-chat-ia' ),
            '#f39c12'
        );

        $this->enviar_email( $direccion_email, $asunto_email, $contenido_html );
    }

    private function enviar_email_cuota_vencida( $direccion_email, $cuota ) {
        $nombre_sitio       = get_bloginfo( 'name' );
        $importe_formateado = number_format( (float) $cuota->importe, 2, ',', '.' );

        $asunto_email = sprintf(
            __( '[%s] Cuota vencida - Accion requerida', 'flavor-chat-ia' ),
            $nombre_sitio
        );

        $contenido_html = $this->construir_plantilla_email(
            __( 'Cuota vencida', 'flavor-chat-ia' ),
            sprintf(
                __( 'Tu cuota del periodo <strong>%1$s</strong> por importe de <strong>%2$s &euro;</strong> se encuentra <strong>vencida</strong>.', 'flavor-chat-ia' ),
                esc_html( $cuota->periodo ),
                esc_html( $importe_formateado )
            ),
            __( 'Te rogamos que regularices tu situacion lo antes posible. Si tienes alguna dificultad, no dudes en contactar con nosotros.', 'flavor-chat-ia' ),
            '#e74c3c'
        );

        $this->enviar_email( $direccion_email, $asunto_email, $contenido_html );
    }

    private function construir_plantilla_email( $titulo_email, $mensaje_principal, $mensaje_secundario, $color_encabezado ) {
        $nombre_sitio = esc_html( get_bloginfo( 'name' ) );
        $url_sitio    = esc_url( home_url( '/' ) );
        $anio_actual  = gmdate( 'Y' );

        $plantilla_html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: ' . esc_attr( $color_encabezado ) . '; padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;">' . esc_html( $titulo_email ) . '</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">' . $mensaje_principal . '</p>
                            <p style="color: #666666; font-size: 14px; line-height: 1.5; margin: 0;">' . esc_html( $mensaje_secundario ) . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 20px 40px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                &copy; ' . esc_html( $anio_actual ) . ' <a href="' . $url_sitio . '" style="color: ' . esc_attr( $color_encabezado ) . '; text-decoration: none;">' . $nombre_sitio . '</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $plantilla_html;
    }

    private function enviar_email( $direccion_destino, $asunto_email, $contenido_html ) {
        $cabeceras_email = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        );

        $resultado_envio = wp_mail( $direccion_destino, $asunto_email, $contenido_html, $cabeceras_email );

        if ( ! $resultado_envio ) {
            flavor_chat_ia_log(
                sprintf( 'Socios Subscriptions: Error al enviar email a %s - Asunto: %s', $direccion_destino, $asunto_email ),
                'error'
            );
        }

        return $resultado_envio;
    }
}

