<?php
/**
 * Clase para tracking de emails
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Tracking {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Privado para singleton
    }

    /**
     * Obtener estadísticas de campaña
     */
    public function get_estadisticas_campania($campania_id) {
        global $wpdb;

        $tabla_campanias = $wpdb->prefix . 'flavor_em_campanias';
        $tabla_tracking = $wpdb->prefix . 'flavor_em_tracking';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_campanias WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            return null;
        }

        // Calcular métricas
        $tasa_apertura = $campania->total_enviados > 0
            ? ($campania->total_abiertos / $campania->total_enviados) * 100
            : 0;

        $tasa_clicks = $campania->total_abiertos > 0
            ? ($campania->total_clicks / $campania->total_abiertos) * 100
            : 0;

        $tasa_bajas = $campania->total_enviados > 0
            ? ($campania->total_bajas / $campania->total_enviados) * 100
            : 0;

        $tasa_rebotes = $campania->total_enviados > 0
            ? ($campania->total_rebotes / $campania->total_enviados) * 100
            : 0;

        // URLs más clickeadas
        $urls_clickeadas = $wpdb->get_results($wpdb->prepare(
            "SELECT url_clickeada, COUNT(*) as total
             FROM $tabla_tracking
             WHERE campania_id = %d AND tipo = 'click' AND url_clickeada IS NOT NULL
             GROUP BY url_clickeada
             ORDER BY total DESC
             LIMIT 10",
            $campania_id
        ));

        // Aperturas por hora
        $aperturas_por_hora = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(fecha) as hora, COUNT(*) as total
             FROM $tabla_tracking
             WHERE campania_id = %d AND tipo = 'abierto'
             GROUP BY HOUR(fecha)
             ORDER BY hora ASC",
            $campania_id
        ));

        // Dispositivos
        $dispositivos = $wpdb->get_results($wpdb->prepare(
            "SELECT dispositivo, COUNT(*) as total
             FROM $tabla_tracking
             WHERE campania_id = %d AND tipo = 'abierto' AND dispositivo IS NOT NULL
             GROUP BY dispositivo
             ORDER BY total DESC",
            $campania_id
        ));

        // Clientes de email
        $clientes_email = $wpdb->get_results($wpdb->prepare(
            "SELECT cliente_email, COUNT(*) as total
             FROM $tabla_tracking
             WHERE campania_id = %d AND tipo = 'abierto' AND cliente_email IS NOT NULL
             GROUP BY cliente_email
             ORDER BY total DESC
             LIMIT 10",
            $campania_id
        ));

        return [
            'campania' => [
                'id' => $campania->id,
                'nombre' => $campania->nombre,
                'asunto' => $campania->asunto,
                'estado' => $campania->estado,
                'fecha_envio' => $campania->fecha_inicio_envio,
            ],
            'metricas' => [
                'enviados' => $campania->total_enviados,
                'entregados' => $campania->total_entregados,
                'abiertos' => $campania->total_abiertos,
                'clicks' => $campania->total_clicks,
                'bajas' => $campania->total_bajas,
                'rebotes' => $campania->total_rebotes,
                'spam' => $campania->total_spam,
                'tasa_apertura' => round($tasa_apertura, 2),
                'tasa_clicks' => round($tasa_clicks, 2),
                'tasa_bajas' => round($tasa_bajas, 2),
                'tasa_rebotes' => round($tasa_rebotes, 2),
            ],
            'urls_clickeadas' => $urls_clickeadas,
            'aperturas_por_hora' => $aperturas_por_hora,
            'dispositivos' => $dispositivos,
            'clientes_email' => $clientes_email,
        ];
    }

    /**
     * Obtener estadísticas de automatización
     */
    public function get_estadisticas_automatizacion($automatizacion_id) {
        global $wpdb;

        $tabla_auto = $wpdb->prefix . 'flavor_em_automatizaciones';
        $tabla_auto_sus = $wpdb->prefix . 'flavor_em_auto_suscriptores';
        $tabla_tracking = $wpdb->prefix . 'flavor_em_tracking';

        $automatizacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_auto WHERE id = %d",
            $automatizacion_id
        ));

        if (!$automatizacion) {
            return null;
        }

        // Conteos por estado
        $estados = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as total
             FROM $tabla_auto_sus
             WHERE automatizacion_id = %d
             GROUP BY estado",
            $automatizacion_id
        ), OBJECT_K);

        // Estadísticas de emails
        $stats_emails = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(CASE WHEN tipo = 'enviado' THEN 1 END) as enviados,
                COUNT(CASE WHEN tipo = 'abierto' THEN 1 END) as abiertos,
                COUNT(CASE WHEN tipo = 'click' THEN 1 END) as clicks
             FROM $tabla_tracking
             WHERE automatizacion_id = %d",
            $automatizacion_id
        ));

        // Estadísticas por paso
        $pasos = json_decode($automatizacion->pasos, true) ?: [];
        $stats_pasos = [];

        foreach ($pasos as $index => $paso) {
            $en_paso = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_auto_sus
                 WHERE automatizacion_id = %d AND paso_actual = %d AND estado = 'activo'",
                $automatizacion_id,
                $index
            ));

            $stats_pasos[] = [
                'paso' => $index + 1,
                'tipo' => $paso['tipo'],
                'nombre' => $paso['nombre'] ?? '',
                'activos' => intval($en_paso),
            ];
        }

        return [
            'automatizacion' => [
                'id' => $automatizacion->id,
                'nombre' => $automatizacion->nombre,
                'estado' => $automatizacion->estado,
                'trigger' => $automatizacion->trigger_tipo,
            ],
            'conteos' => [
                'total_inscritos' => $automatizacion->total_inscritos,
                'activos' => isset($estados['activo']) ? $estados['activo']->total : 0,
                'completados' => $automatizacion->total_completados,
                'salidos' => $automatizacion->total_salidos,
            ],
            'emails' => [
                'enviados' => intval($stats_emails->enviados ?? 0),
                'abiertos' => intval($stats_emails->abiertos ?? 0),
                'clicks' => intval($stats_emails->clicks ?? 0),
            ],
            'pasos' => $stats_pasos,
        ];
    }

    /**
     * Obtener estadísticas de suscriptor
     */
    public function get_estadisticas_suscriptor($suscriptor_id) {
        global $wpdb;

        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
        $tabla_tracking = $wpdb->prefix . 'flavor_em_tracking';

        $suscriptor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_suscriptores WHERE id = %d",
            $suscriptor_id
        ));

        if (!$suscriptor) {
            return null;
        }

        // Historial de tracking
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, c.nombre as campania_nombre
             FROM $tabla_tracking t
             LEFT JOIN {$wpdb->prefix}flavor_em_campanias c ON t.campania_id = c.id
             WHERE t.suscriptor_id = %d
             ORDER BY t.fecha DESC
             LIMIT 50",
            $suscriptor_id
        ));

        // Listas
        $listas = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.estado as estado_suscripcion, sl.fecha_suscripcion, sl.fecha_baja
             FROM {$wpdb->prefix}flavor_em_listas l
             INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d",
            $suscriptor_id
        ));

        // Tags
        $tags = json_decode($suscriptor->tags, true) ?: [];

        return [
            'suscriptor' => [
                'id' => $suscriptor->id,
                'email' => $suscriptor->email,
                'nombre' => $suscriptor->nombre,
                'estado' => $suscriptor->estado,
                'fecha_registro' => $suscriptor->creado_en,
                'puntuacion' => $suscriptor->puntuacion,
            ],
            'metricas' => [
                'emails_recibidos' => $suscriptor->total_emails_enviados,
                'aperturas' => $suscriptor->total_abiertos,
                'clicks' => $suscriptor->total_clicks,
                'ultima_apertura' => $suscriptor->ultima_apertura,
                'ultimo_click' => $suscriptor->ultimo_click,
            ],
            'listas' => $listas,
            'tags' => $tags,
            'historial' => $historial,
        ];
    }

    /**
     * Obtener estadísticas globales
     */
    public function get_estadisticas_globales($periodo = '30 days') {
        global $wpdb;

        $fecha_inicio = date('Y-m-d H:i:s', strtotime('-' . $periodo));
        $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';

        // Detectar columna de fecha en suscriptores
        $col_fecha_suscriptor = null;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_suscriptores)) {
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_suscriptores");
            $col_fecha_suscriptor = in_array('creado_en', $columnas) ? 'creado_en' :
                                   (in_array('fecha_registro', $columnas) ? 'fecha_registro' : null);
        }

        // Totales
        $total_suscriptores = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_suscriptores} WHERE estado = 'activo'"
        );

        $total_listas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1"
        );

        $nuevos_suscriptores = 0;
        if ($col_fecha_suscriptor) {
            $nuevos_suscriptores = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_suscriptores} WHERE $col_fecha_suscriptor >= %s",
                $fecha_inicio
            ));
        }

        $bajas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_suscriptores
             WHERE fecha_baja >= %s",
            $fecha_inicio
        ));

        // Campañas
        $campanias_enviadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_em_campanias
             WHERE fecha_inicio_envio >= %s AND estado = 'enviada'",
            $fecha_inicio
        ));

        // Tracking
        $stats_tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(CASE WHEN tipo = 'enviado' THEN 1 END) as enviados,
                COUNT(CASE WHEN tipo = 'abierto' THEN 1 END) as abiertos,
                COUNT(CASE WHEN tipo = 'click' THEN 1 END) as clicks
             FROM {$wpdb->prefix}flavor_em_tracking
             WHERE fecha >= %s",
            $fecha_inicio
        ));

        // Crecimiento diario
        $crecimiento_diario = [];
        if ($col_fecha_suscriptor) {
            $crecimiento_diario = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE($col_fecha_suscriptor) as fecha, COUNT(*) as total
                 FROM {$tabla_suscriptores}
                 WHERE $col_fecha_suscriptor >= %s
                 GROUP BY DATE($col_fecha_suscriptor)
                 ORDER BY fecha ASC",
                $fecha_inicio
            ));
        }

        // Tasas promedio
        $tasas_promedio = $wpdb->get_row($wpdb->prepare(
            "SELECT
                AVG(CASE WHEN total_enviados > 0 THEN (total_abiertos / total_enviados) * 100 ELSE 0 END) as tasa_apertura_promedio,
                AVG(CASE WHEN total_abiertos > 0 THEN (total_clicks / total_abiertos) * 100 ELSE 0 END) as tasa_clicks_promedio
             FROM {$wpdb->prefix}flavor_em_campanias
             WHERE fecha_inicio_envio >= %s AND estado = 'enviada'",
            $fecha_inicio
        ));

        return [
            'periodo' => $periodo,
            'suscriptores' => [
                'total' => intval($total_suscriptores),
                'nuevos' => intval($nuevos_suscriptores),
                'bajas' => intval($bajas),
                'crecimiento_neto' => intval($nuevos_suscriptores) - intval($bajas),
            ],
            'listas' => intval($total_listas),
            'campanias' => [
                'enviadas' => intval($campanias_enviadas),
            ],
            'emails' => [
                'enviados' => intval($stats_tracking->enviados ?? 0),
                'abiertos' => intval($stats_tracking->abiertos ?? 0),
                'clicks' => intval($stats_tracking->clicks ?? 0),
            ],
            'tasas_promedio' => [
                'apertura' => round($tasas_promedio->tasa_apertura_promedio ?? 0, 2),
                'clicks' => round($tasas_promedio->tasa_clicks_promedio ?? 0, 2),
            ],
            'crecimiento_diario' => $crecimiento_diario,
        ];
    }

    /**
     * Detectar información del dispositivo desde User Agent
     */
    public static function detectar_dispositivo($user_agent) {
        $dispositivo = 'desktop';
        $cliente_email = 'desconocido';

        if (empty($user_agent)) {
            return ['dispositivo' => $dispositivo, 'cliente_email' => $cliente_email];
        }

        // Detectar dispositivo
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $user_agent)) {
            $dispositivo = preg_match('/ipad|tablet/i', $user_agent) ? 'tablet' : 'mobile';
        }

        // Detectar cliente de email
        $clientes = [
            'Gmail' => '/GoogleImageProxy|Gmail/i',
            'Apple Mail' => '/AppleWebKit.*Mail/i',
            'Outlook' => '/Microsoft Outlook|MSOffice/i',
            'Yahoo' => '/Yahoo/i',
            'Thunderbird' => '/Thunderbird/i',
            'Samsung Mail' => '/SamsungBrowser/i',
        ];

        foreach ($clientes as $nombre => $patron) {
            if (preg_match($patron, $user_agent)) {
                $cliente_email = $nombre;
                break;
            }
        }

        return [
            'dispositivo' => $dispositivo,
            'cliente_email' => $cliente_email,
        ];
    }

    /**
     * Limpiar tracking antiguo
     */
    public function limpiar_tracking_antiguo($dias = 365) {
        global $wpdb;

        $fecha_limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}flavor_em_tracking WHERE fecha < %s",
            $fecha_limite
        ));

        return $eliminados;
    }
}
