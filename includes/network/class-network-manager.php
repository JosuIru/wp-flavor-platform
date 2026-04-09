<?php
/**
 * Gestor principal de la Red de Comunidades
 *
 * Singleton que orquesta todo el sistema de red:
 * nodos, conexiones, contenido compartido, colaboraciones,
 * mensajería, mapa, directorio y sellos de calidad.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Versión del sistema de red
     */
    const VERSION = '1.0.0';

    /**
     * Módulos registrados
     */
    private $modulos_registrados = [];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_modulos();
        $this->init_hooks();

        // Inicializar admin en contexto de administración
        if (is_admin()) {
            $this->init_admin();
        }
    }

    /**
     * Inicializa el panel de administración de red
     */
    private function init_admin() {
        require_once FLAVOR_CHAT_IA_PATH . 'includes/network/class-network-admin.php';
        Flavor_Network_Admin::get_instance();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Verificar/actualizar BD al cargar
        add_action('admin_init', [$this, 'check_db_version']);

        // Registrar widget de dashboard
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);

        // Shortcodes públicos
        if (!shortcode_exists('flavor_network_directory')) {
            add_shortcode('flavor_network_directory', [$this, 'shortcode_directory']);
        }
        if (!shortcode_exists('flavor_network_map')) {
            add_shortcode('flavor_network_map', [$this, 'shortcode_map']);
        }
        if (!shortcode_exists('flavor_network_board')) {
            add_shortcode('flavor_network_board', [$this, 'shortcode_board']);
        }
        if (!shortcode_exists('flavor_network_events')) {
            add_shortcode('flavor_network_events', [$this, 'shortcode_events']);
        }
        if (!shortcode_exists('flavor_network_alerts')) {
            add_shortcode('flavor_network_alerts', [$this, 'shortcode_alerts']);
        }
        if (!shortcode_exists('flavor_network_catalog')) {
            add_shortcode('flavor_network_catalog', [$this, 'shortcode_catalog']);
        }
        if (!shortcode_exists('flavor_network_collaborations')) {
            add_shortcode('flavor_network_collaborations', [$this, 'shortcode_collaborations']);
        }
        if (!shortcode_exists('flavor_network_time_offers')) {
            add_shortcode('flavor_network_time_offers', [$this, 'shortcode_time_offers']);
        }
        if (!shortcode_exists('flavor_network_node_profile')) {
            add_shortcode('flavor_network_node_profile', [$this, 'shortcode_node_profile']);
        }
        if (!shortcode_exists('flavor_network_questions')) {
            add_shortcode('flavor_network_questions', [$this, 'shortcode_network_questions']);
        }

        // Assets frontend
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);

        // Sincronización federada de nodos (sin servidor central)
        add_action('flavor_network_sync_peers', [$this, 'sync_with_peers']);
        if (!wp_next_scheduled('flavor_network_sync_peers')) {
            wp_schedule_event(time(), 'hourly', 'flavor_network_sync_peers');
        }
    }

    public function sync_with_peers() {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $peers = $wpdb->get_results(
            "SELECT id, site_url FROM {$tabla}
             WHERE es_nodo_local = 0
               AND estado = 'activo'
               AND site_url <> ''"
        );

        if (!$peers) {
            return;
        }

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $page = 1;
            $max_pages = 5;
            do {
                $url = add_query_arg([
                    'pagina' => $page,
                    'por_pagina' => 100,
                ], $site_url . '/wp-json/flavor-network/v1/directory');

                $response = wp_remote_get($url, [
                    'timeout' => 12,
                    'headers' => [
                        'User-Agent' => 'FlavorChatIA/1.0 (+https://flavor-chat-ia)',
                    ],
                ]);

                if (is_wp_error($response)) {
                    break;
                }

                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if (!is_array($data) || empty($data['nodos'])) {
                    break;
                }

                foreach ($data['nodos'] as $nodo_data) {
                    if (empty($nodo_data['site_url'])) {
                        continue;
                    }
                    if (untrailingslashit($nodo_data['site_url']) === untrailingslashit(get_site_url())) {
                        continue;
                    }
                    Flavor_Network_Node::upsert_remote_node($nodo_data);
                }

                $page++;
                $total_pages = (int) ($data['paginas'] ?? 1);
            } while ($page <= $total_pages && $page <= $max_pages);
        }

        // Marcar nodos no vistos recientemente como inactivos
        $limite_inactivo = gmdate('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla}
             SET estado = 'inactivo'
             WHERE es_nodo_local = 0
               AND estado = 'activo'
               AND (ultima_sincronizacion IS NULL OR ultima_sincronizacion < %s)",
            $limite_inactivo
        ));

        // Sincronizar contenido federado
        $this->sync_producers_from_peers($peers);
        $this->sync_events_from_peers($peers);
        $this->sync_carpooling_from_peers($peers);
        $this->sync_workshops_from_peers($peers);
        $this->sync_spaces_from_peers($peers);
        $this->sync_marketplace_from_peers($peers);
        $this->sync_timebank_from_peers($peers);
        $this->sync_courses_from_peers($peers);
    }

    /**
     * Sincroniza productores desde los nodos de la red
     */
    private function sync_producers_from_peers($peers) {
        global $wpdb;

        $tabla_productores = $wpdb->prefix . 'flavor_network_producers';
        $tabla_productos = $wpdb->prefix . 'flavor_network_producer_products';

        // Verificar que las tablas existen
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_productores'") !== $tabla_productores) {
            return;
        }

        // Obtener coordenadas de este nodo
        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));

        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            // Construir URL con coordenadas para filtrar por distancia
            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/producers'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['productores'])) {
                continue;
            }

            foreach ($data['productores'] as $prod_data) {
                // No importar productores propios
                if ($prod_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                // Verificar si ya existe
                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_productores
                     WHERE nodo_id = %s AND productor_id = %d",
                    $prod_data['nodo_id'],
                    $prod_data['productor_id']
                ));

                $datos_insertar = [
                    'nodo_id'           => $prod_data['nodo_id'],
                    'productor_id'      => $prod_data['productor_id'],
                    'nombre'            => sanitize_text_field($prod_data['nombre']),
                    'slug'              => sanitize_title($prod_data['slug']),
                    'ubicacion'         => sanitize_text_field($prod_data['ubicacion'] ?? ''),
                    'latitud'           => floatval($prod_data['latitud'] ?? 0),
                    'longitud'          => floatval($prod_data['longitud'] ?? 0),
                    'radio_entrega_km'  => floatval($prod_data['radio_entrega_km'] ?? 0),
                    'certificacion_eco' => $prod_data['certificacion_eco'] ? 1 : 0,
                    'compartir_en_red'  => 1,
                    'acepta_mensajeria' => $prod_data['acepta_mensajeria'] ? 1 : 0,
                    'visible_en_red'    => 1,
                    'actualizado_en'    => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_productores, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_productores, $datos_insertar);
                }
            }
        }

        // Limpiar productores federados obsoletos (no actualizados en 7 días)
        $limite_obsoleto = gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_productores
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            $limite_obsoleto
        ));
    }

    /**
     * Sincroniza eventos desde los nodos de la red
     */
    private function sync_events_from_peers($peers) {
        global $wpdb;

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 100; // Radio de 100km para eventos
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/events'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['eventos'])) {
                continue;
            }

            foreach ($data['eventos'] as $ev_data) {
                if ($ev_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_eventos WHERE nodo_id = %s AND evento_id = %d",
                    $ev_data['nodo_id'],
                    $ev_data['evento_id']
                ));

                $datos_insertar = [
                    'nodo_id'            => $ev_data['nodo_id'],
                    'evento_id'          => $ev_data['evento_id'],
                    'titulo'             => sanitize_text_field($ev_data['titulo']),
                    'descripcion'        => sanitize_textarea_field($ev_data['descripcion'] ?? ''),
                    'tipo'               => sanitize_text_field($ev_data['tipo'] ?? 'social'),
                    'fecha_inicio'       => sanitize_text_field($ev_data['fecha_inicio']),
                    'fecha_fin'          => sanitize_text_field($ev_data['fecha_fin'] ?? ''),
                    'ubicacion'          => sanitize_text_field($ev_data['ubicacion'] ?? ''),
                    'es_online'          => $ev_data['es_online'] ? 1 : 0,
                    'precio'             => floatval($ev_data['precio'] ?? 0),
                    'aforo_maximo'       => absint($ev_data['aforo_maximo'] ?? 0),
                    'inscritos_count'    => absint($ev_data['inscritos_count'] ?? 0),
                    'organizador_nombre' => sanitize_text_field($ev_data['organizador_nombre'] ?? ''),
                    'imagen_url'         => esc_url_raw($ev_data['imagen_url'] ?? ''),
                    'visible_en_red'     => 1,
                    'actualizado_en'     => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_eventos, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_eventos, $datos_insertar);
                }
            }
        }

        // Limpiar eventos obsoletos (pasados o no actualizados en 3 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_eventos
             WHERE nodo_id != %s
               AND (fecha_inicio < NOW() OR actualizado_en < %s)",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (3 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza viajes de carpooling desde los nodos de la red
     */
    private function sync_carpooling_from_peers($peers) {
        global $wpdb;

        $tabla_viajes = $wpdb->prefix . 'flavor_network_carpooling';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") !== $tabla_viajes) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['origen_lat'] = $lat_local;
                $url_params['origen_lng'] = $lng_local;
                $url_params['radio'] = 150; // Radio de 150km para viajes
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/carpooling'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['viajes'])) {
                continue;
            }

            foreach ($data['viajes'] as $v_data) {
                if ($v_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_viajes WHERE nodo_id = %s AND viaje_id = %d",
                    $v_data['nodo_id'],
                    $v_data['viaje_id']
                ));

                $datos_insertar = [
                    'nodo_id'            => $v_data['nodo_id'],
                    'viaje_id'           => $v_data['viaje_id'],
                    'origen'             => sanitize_text_field($v_data['origen']),
                    'destino'            => sanitize_text_field($v_data['destino']),
                    'fecha_salida'       => sanitize_text_field($v_data['fecha_salida']),
                    'hora_salida'        => sanitize_text_field($v_data['hora_salida'] ?? ''),
                    'conductor_nombre'   => sanitize_text_field($v_data['conductor_nombre'] ?? ''),
                    'plazas_disponibles' => absint($v_data['plazas_disponibles'] ?? 0),
                    'precio_plaza'       => floatval($v_data['precio_plaza'] ?? 0),
                    'permite_equipaje'   => $v_data['permite_equipaje'] ? 1 : 0,
                    'permite_mascotas'   => $v_data['permite_mascotas'] ? 1 : 0,
                    'estado'             => 'activo',
                    'visible_en_red'     => 1,
                    'actualizado_en'     => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_viajes, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_viajes, $datos_insertar);
                }
            }
        }

        // Limpiar viajes obsoletos (pasados o no actualizados)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_viajes
             WHERE nodo_id != %s
               AND (fecha_salida < NOW() OR actualizado_en < %s)",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (2 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza talleres desde los nodos de la red
     */
    private function sync_workshops_from_peers($peers) {
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_network_workshops';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") !== $tabla_talleres) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 100;
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/workshops'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['talleres'])) {
                continue;
            }

            foreach ($data['talleres'] as $t_data) {
                if ($t_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_talleres WHERE nodo_id = %s AND taller_id = %d",
                    $t_data['nodo_id'],
                    $t_data['taller_id']
                ));

                $datos_insertar = [
                    'nodo_id'              => $t_data['nodo_id'],
                    'taller_id'            => $t_data['taller_id'],
                    'titulo'               => sanitize_text_field($t_data['titulo']),
                    'slug'                 => sanitize_title($t_data['slug'] ?? ''),
                    'descripcion'          => sanitize_textarea_field($t_data['descripcion'] ?? ''),
                    'categoria'            => sanitize_text_field($t_data['categoria'] ?? ''),
                    'nivel'                => sanitize_text_field($t_data['nivel'] ?? 'todos'),
                    'duracion_horas'       => floatval($t_data['duracion_horas'] ?? 0),
                    'numero_sesiones'      => absint($t_data['numero_sesiones'] ?? 1),
                    'max_participantes'    => absint($t_data['max_participantes'] ?? 20),
                    'inscritos_actuales'   => absint($t_data['inscritos_actuales'] ?? 0),
                    'precio'               => floatval($t_data['precio'] ?? 0),
                    'es_gratuito'          => $t_data['es_gratuito'] ? 1 : 0,
                    'ubicacion'            => sanitize_text_field($t_data['ubicacion'] ?? ''),
                    'organizador_nombre'   => sanitize_text_field($t_data['organizador_nombre'] ?? ''),
                    'imagen_url'           => esc_url_raw($t_data['imagen_url'] ?? ''),
                    'fecha_primera_sesion' => sanitize_text_field($t_data['fecha_primera_sesion'] ?? ''),
                    'estado'               => 'publicado',
                    'visible_en_red'       => 1,
                    'actualizado_en'       => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_talleres, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_talleres, $datos_insertar);
                }
            }
        }

        // Limpiar talleres obsoletos (no actualizados en 5 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_talleres
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (5 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza espacios comunes desde los nodos de la red
     */
    private function sync_spaces_from_peers($peers) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_network_spaces';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") !== $tabla_espacios) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 50; // Radio de 50km para espacios
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/spaces'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['espacios'])) {
                continue;
            }

            foreach ($data['espacios'] as $e_data) {
                if ($e_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_espacios WHERE nodo_id = %s AND espacio_id = %d",
                    $e_data['nodo_id'],
                    $e_data['espacio_id']
                ));

                $datos_insertar = [
                    'nodo_id'            => $e_data['nodo_id'],
                    'espacio_id'         => $e_data['espacio_id'],
                    'nombre'             => sanitize_text_field($e_data['nombre']),
                    'descripcion'        => sanitize_textarea_field($e_data['descripcion'] ?? ''),
                    'tipo'               => sanitize_text_field($e_data['tipo'] ?? 'salon_eventos'),
                    'ubicacion'          => sanitize_text_field($e_data['ubicacion'] ?? ''),
                    'capacidad_personas' => absint($e_data['capacidad_personas'] ?? 0),
                    'precio_hora'        => floatval($e_data['precio_hora'] ?? 0),
                    'precio_dia'         => floatval($e_data['precio_dia'] ?? 0),
                    'dias_disponibles'   => sanitize_text_field($e_data['dias_disponibles'] ?? 'L,M,X,J,V,S,D'),
                    'foto_principal'     => esc_url_raw($e_data['foto_principal'] ?? ''),
                    'estado'             => 'disponible',
                    'visible_en_red'     => 1,
                    'actualizado_en'     => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_espacios, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_espacios, $datos_insertar);
                }
            }
        }

        // Limpiar espacios obsoletos (no actualizados en 7 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_espacios
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza anuncios del marketplace desde los nodos de la red
     */
    private function sync_marketplace_from_peers($peers) {
        global $wpdb;

        $tabla_anuncios = $wpdb->prefix . 'flavor_network_marketplace';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 100; // Radio de 100km para anuncios
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/marketplace'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['anuncios'])) {
                continue;
            }

            foreach ($data['anuncios'] as $a_data) {
                if ($a_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_anuncios WHERE nodo_id = %s AND anuncio_id = %d",
                    $a_data['nodo_id'],
                    $a_data['anuncio_id']
                ));

                $datos_insertar = [
                    'nodo_id'          => $a_data['nodo_id'],
                    'anuncio_id'       => $a_data['anuncio_id'],
                    'titulo'           => sanitize_text_field($a_data['titulo']),
                    'slug'             => sanitize_title($a_data['slug'] ?? ''),
                    'descripcion'      => sanitize_textarea_field($a_data['descripcion'] ?? ''),
                    'tipo'             => sanitize_text_field($a_data['tipo'] ?? 'venta'),
                    'categoria'        => sanitize_text_field($a_data['categoria'] ?? ''),
                    'precio'           => isset($a_data['precio']) ? floatval($a_data['precio']) : null,
                    'es_gratuito'      => $a_data['es_gratuito'] ? 1 : 0,
                    'condicion'        => sanitize_text_field($a_data['condicion'] ?? 'buen_estado'),
                    'imagen_principal' => esc_url_raw($a_data['imagen_principal'] ?? ''),
                    'ubicacion'        => sanitize_text_field($a_data['ubicacion'] ?? ''),
                    'envio_disponible' => $a_data['envio_disponible'] ? 1 : 0,
                    'usuario_nombre'   => sanitize_text_field($a_data['usuario_nombre'] ?? ''),
                    'estado'           => 'publicado',
                    'visible_en_red'   => 1,
                    'actualizado_en'   => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_anuncios, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_anuncios, $datos_insertar);
                }
            }
        }

        // Limpiar anuncios obsoletos (no actualizados en 7 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_anuncios
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza servicios del banco de tiempo desde los nodos de la red
     */
    private function sync_timebank_from_peers($peers) {
        global $wpdb;

        $tabla_servicios = $wpdb->prefix . 'flavor_network_time_bank';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") !== $tabla_servicios) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 50;
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/timebank'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['servicios'])) {
                continue;
            }

            foreach ($data['servicios'] as $servicio_data) {
                if ($servicio_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_servicios WHERE nodo_id = %s AND servicio_id = %d",
                    $servicio_data['nodo_id'],
                    $servicio_data['servicio_id']
                ));

                $datos_insertar = [
                    'nodo_id'                  => $servicio_data['nodo_id'],
                    'servicio_id'              => $servicio_data['servicio_id'],
                    'titulo'                   => sanitize_text_field($servicio_data['titulo']),
                    'descripcion'              => sanitize_textarea_field($servicio_data['descripcion'] ?? ''),
                    'tipo'                     => sanitize_text_field($servicio_data['tipo'] ?? 'oferta'),
                    'categoria'                => sanitize_text_field($servicio_data['categoria'] ?? ''),
                    'horas_estimadas'          => floatval($servicio_data['horas_estimadas'] ?? 1),
                    'modalidad'                => sanitize_text_field($servicio_data['modalidad'] ?? 'presencial'),
                    'disponibilidad'           => sanitize_text_field($servicio_data['disponibilidad'] ?? ''),
                    'ubicacion'                => sanitize_text_field($servicio_data['ubicacion'] ?? ''),
                    'usuario_nombre'           => sanitize_text_field($servicio_data['usuario_nombre'] ?? ''),
                    'valoracion_promedio'      => floatval($servicio_data['valoracion_promedio'] ?? 0),
                    'intercambios_completados' => intval($servicio_data['intercambios_completados'] ?? 0),
                    'estado'                   => 'activo',
                    'visible_en_red'           => 1,
                    'actualizado_en'           => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_servicios, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_servicios, $datos_insertar);
                }
            }
        }

        // Limpiar servicios obsoletos (no actualizados en 7 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_servicios
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Sincroniza cursos desde los nodos de la red
     */
    private function sync_courses_from_peers($peers) {
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_network_courses';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cursos'") !== $tabla_cursos) {
            return;
        }

        $lat_local = floatval(get_option('flavor_network_lat', 0));
        $lng_local = floatval(get_option('flavor_network_lng', 0));
        $nodo_local_id = get_option('flavor_network_node_id', '');

        foreach ($peers as $peer) {
            $site_url = untrailingslashit($peer->site_url);
            if (empty($site_url)) {
                continue;
            }

            $url_params = ['limite' => 50];
            if ($lat_local && $lng_local) {
                $url_params['lat'] = $lat_local;
                $url_params['lng'] = $lng_local;
                $url_params['radio'] = 100;
            }

            $url = add_query_arg(
                $url_params,
                $site_url . '/wp-json/flavor-integration/v1/federation/courses'
            );

            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => [
                    'User-Agent'    => 'FlavorChatIA/1.0',
                    'X-Origin-Node' => get_site_url(),
                    'X-Node-Token'  => get_option('flavor_network_token', ''),
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!is_array($data) || empty($data['cursos'])) {
                continue;
            }

            foreach ($data['cursos'] as $curso_data) {
                if ($curso_data['nodo_id'] === $nodo_local_id) {
                    continue;
                }

                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $tabla_cursos WHERE nodo_id = %s AND curso_id = %d",
                    $curso_data['nodo_id'],
                    $curso_data['curso_id']
                ));

                $datos_insertar = [
                    'nodo_id'             => $curso_data['nodo_id'],
                    'curso_id'            => $curso_data['curso_id'],
                    'titulo'              => sanitize_text_field($curso_data['titulo']),
                    'slug'                => sanitize_title($curso_data['slug'] ?? ''),
                    'descripcion'         => sanitize_textarea_field($curso_data['descripcion'] ?? ''),
                    'categoria'           => sanitize_text_field($curso_data['categoria'] ?? ''),
                    'nivel'               => sanitize_text_field($curso_data['nivel'] ?? 'todos'),
                    'modalidad'           => sanitize_text_field($curso_data['modalidad'] ?? 'online'),
                    'duracion_horas'      => floatval($curso_data['duracion_horas'] ?? 0),
                    'numero_lecciones'    => intval($curso_data['numero_lecciones'] ?? 0),
                    'max_alumnos'         => intval($curso_data['max_alumnos'] ?? 30),
                    'inscritos_actuales'  => intval($curso_data['inscritos_actuales'] ?? 0),
                    'precio'              => floatval($curso_data['precio'] ?? 0),
                    'es_gratuito'         => !empty($curso_data['es_gratuito']) ? 1 : 0,
                    'ubicacion'           => sanitize_text_field($curso_data['ubicacion'] ?? ''),
                    'instructor_nombre'   => sanitize_text_field($curso_data['instructor_nombre'] ?? ''),
                    'valoracion_promedio' => floatval($curso_data['valoracion_promedio'] ?? 0),
                    'imagen_url'          => esc_url_raw($curso_data['imagen_url'] ?? ''),
                    'fecha_inicio'        => $curso_data['fecha_inicio'] ?? null,
                    'fecha_fin'           => $curso_data['fecha_fin'] ?? null,
                    'estado'              => 'publicado',
                    'visible_en_red'      => 1,
                    'actualizado_en'      => current_time('mysql'),
                ];

                if ($existe) {
                    $wpdb->update($tabla_cursos, $datos_insertar, ['id' => $existe]);
                } else {
                    $datos_insertar['creado_en'] = current_time('mysql');
                    $wpdb->insert($tabla_cursos, $datos_insertar);
                }
            }
        }

        // Limpiar cursos obsoletos (no actualizados en 7 días)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_cursos
             WHERE nodo_id != %s
               AND actualizado_en < %s",
            $nodo_local_id,
            gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))
        ));
    }

    /**
     * Registra los módulos disponibles de la red
     */
    private function registrar_modulos() {
        $this->modulos_registrados = [
            // Conexión Básica
            'perfil_publico'     => [
                'nombre'      => __('Perfil público en red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tu ficha visible para otras entidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-id-alt',
            ],
            'qr_entidad'         => [
                'nombre'      => __('QR de entidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Código para escanear y ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-smartphone',
            ],
            'geolocalizacion'    => [
                'nombre'      => __('Geolocalización', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Aparecer en mapa de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-location-alt',
            ],
            'categorizacion'     => [
                'nombre'      => __('Categorización', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tipo de entidad, sector, tags', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-tag',
            ],
            'nivel_consciencia'  => [
                'nombre'      => __('Nivel de consciencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Básico / Transición / Consciente / Referente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-star-filled',
            ],

            // Interconexión
            'conexiones'         => [
                'nombre'      => __('Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Solicitar, gestionar y nivelar conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-networking',
            ],
            'favoritos'          => [
                'nombre'      => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Marcar entidades de interés', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-heart',
            ],
            'recomendaciones'    => [
                'nombre'      => __('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Sugerir entidades a otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-megaphone',
            ],

            // Compartir Contenido
            'catalogo_publico'   => [
                'nombre'      => __('Catálogo público', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Productos visibles a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-cart',
            ],
            'servicios_publicos' => [
                'nombre'      => __('Servicios públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Directorio de profesionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-businessman',
            ],
            'espacios'           => [
                'nombre'      => __('Espacios compartibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Banco de espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-building',
            ],
            'recursos'           => [
                'nombre'      => __('Recursos compartibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Herramientas, vehículos...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-hammer',
            ],
            'eventos'            => [
                'nombre'      => __('Eventos públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Agenda de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-calendar-alt',
            ],
            'banco_tiempo'       => [
                'nombre'      => __('Ofertas de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Banco de tiempo inter-nodos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-clock',
            ],
            'saberes'            => [
                'nombre'      => __('Saberes públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Formaciones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-book',
            ],
            'excedentes'         => [
                'nombre'      => __('Excedentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Economía circular', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-update',
            ],
            'necesidades'        => [
                'nombre'      => __('Necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Pedir ayuda a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-sos',
            ],

            // Colaboración
            'compras_colectivas' => [
                'nombre'      => __('Compras colectivas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Unir pedidos para mejor precio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-groups',
            ],
            'logistica'          => [
                'nombre'      => __('Logística compartida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Coordinar transportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-car',
            ],
            'proyectos'          => [
                'nombre'      => __('Proyectos conjuntos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Colaborar en iniciativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-lightbulb',
            ],
            'alianzas'           => [
                'nombre'      => __('Alianzas temáticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Grupos por afinidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-admin-links',
            ],
            'hermanamientos'     => [
                'nombre'      => __('Hermanamientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Vínculo estable con otra entidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-admin-users',
            ],
            'mentoria'           => [
                'nombre'      => __('Mentoría cruzada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Acompañamiento mutuo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-welcome-learn-more',
            ],

            // Comunicación
            'tablon_red'         => [
                'nombre'      => __('Tablón de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Ver/publicar anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-clipboard',
            ],
            'preguntas_red'      => [
                'nombre'      => __('Preguntas a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Inteligencia colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-editor-help',
            ],
            'alertas_solidarias' => [
                'nombre'      => __('Alertas solidarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Necesidades urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-warning',
            ],
            'newsletter_red'     => [
                'nombre'      => __('Newsletter de red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Resumen periódico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-email-alt',
            ],
            'mensajeria'         => [
                'nombre'      => __('Mensajería inter-nodos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Chat entre entidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-format-chat',
            ],

            // Calidad y Mapa
            'sello_calidad'      => [
                'nombre'      => __('Sello App Consciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Certificación y niveles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'calidad',
                'icono'       => 'dashicons-awards',
            ],
            'mapa_apps'          => [
                'nombre'      => __('Mapa de Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Mapa público con filtros y buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'calidad',
                'icono'       => 'dashicons-admin-site-alt3',
            ],
        ];
    }

    /**
     * Comprueba y actualiza la versión de BD si es necesario
     */
    public function check_db_version() {
        Flavor_Network_Installer::maybe_upgrade();
    }

    /**
     * Obtiene los módulos registrados
     */
    public function get_modulos() {
        return $this->modulos_registrados;
    }

    /**
     * Obtiene módulos por categoría
     */
    public function get_modulos_por_categoria($categoria) {
        return array_filter($this->modulos_registrados, function($modulo) use ($categoria) {
            return $modulo['categoria'] === $categoria;
        });
    }

    /**
     * Obtiene las categorías de módulos
     */
    public function get_categorias() {
        return [
            'conexion'       => __('Conexión Básica', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'interconexion'  => __('Interconexión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'contenido'      => __('Compartir Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colaboracion'   => __('Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunicacion'   => __('Comunicación de Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'calidad'        => __('Calidad y Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Obtiene los módulos activos del nodo local
     */
    public function get_modulos_activos() {
        $nodo_local = Flavor_Network_Node::get_local_node();
        if (!$nodo_local) {
            return [];
        }
        return $nodo_local->get_modulos_activos();
    }

    /**
     * Comprueba si un módulo está activo
     */
    public function is_modulo_activo($modulo_id) {
        $activos = $this->get_modulos_activos();
        return in_array($modulo_id, $activos);
    }

    // ─── Shortcodes ───

    public function shortcode_directory($atts) {
        $atts = shortcode_atts([
            'tipo'   => '',
            'pais'   => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-directory.php';
        return ob_get_clean();
    }

    public function shortcode_map($atts) {
        $atts = shortcode_atts([
            'altura' => '500px',
            'tipo'   => '',
            'zoom'   => 6,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        $this->enqueue_map_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-map.php';
        return ob_get_clean();
    }

    public function shortcode_board($atts) {
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 15,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-board.php';
        return ob_get_clean();
    }

    public function shortcode_events($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-events.php';
        return ob_get_clean();
    }

    public function shortcode_alerts($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-alerts.php';
        return ob_get_clean();
    }

    public function shortcode_catalog($atts) {
        $atts = shortcode_atts([
            'nodo' => '',
            'tipo' => '',
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-catalog.php';
        return ob_get_clean();
    }

    public function shortcode_collaborations($atts) {
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-collaborations.php';
        return ob_get_clean();
    }

    public function shortcode_time_offers($atts) {
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-time-offers.php';
        return ob_get_clean();
    }

    public function shortcode_node_profile($atts) {
        $atts = shortcode_atts([
            'slug' => '',
        ], $atts);

        if (empty($atts['slug']) && isset($_GET['nodo'])) {
            $atts['slug'] = sanitize_text_field($_GET['nodo']);
        }

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-node-profile.php';
        return ob_get_clean();
    }

    public function shortcode_network_questions($atts) {
        $atts = shortcode_atts([
            'categoria' => '',
            'limite'    => 10,
        ], $atts, 'flavor_network_questions');

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_CHAT_IA_PATH . 'includes/network/templates/network-questions.php';
        return ob_get_clean();
    }

    // ─── Assets ───

    public function maybe_enqueue_frontend_assets() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

        if (!is_admin() && strpos($request_uri, '/mi-portal') !== false) {
            return;
        }

        // Solo cargar si hay shortcodes en el contenido
        global $post;
        if (!$post) {
            return;
        }

        $shortcodes_red = ['flavor_network_directory', 'flavor_network_map', 'flavor_network_board',
                           'flavor_network_events', 'flavor_network_alerts', 'flavor_network_catalog',
                           'flavor_network_collaborations', 'flavor_network_time_offers', 'flavor_network_node_profile',
                           'flavor_network_questions'];

        foreach ($shortcodes_red as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $this->enqueue_frontend_assets();
                if ($shortcode === 'flavor_network_map') {
                    $this->enqueue_map_assets();
                }
                break;
            }
        }
    }

    private function enqueue_frontend_assets() {
        if (wp_style_is('flavor-network-frontend', 'enqueued')) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-network-frontend',
            FLAVOR_CHAT_IA_URL . "assets/css/modules/network-frontend{$sufijo_asset}.css",
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-network-frontend',
            FLAVOR_CHAT_IA_URL . "assets/js/network-frontend{$sufijo_asset}.js",
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('flavor-network-frontend', 'flavorNetwork', [
            'apiUrl' => rest_url(Flavor_Network_API::API_NAMESPACE),
            'nonce'  => wp_create_nonce('wp_rest'),
            'i18n'   => [
                'cargando'       => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados' => __('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'          => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ver_mas'        => __('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'buscar'         => __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    private function enqueue_map_assets() {
        // Leaflet CSS y JS desde CDN
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'flavor-network-map',
            FLAVOR_CHAT_IA_URL . "assets/js/network-map{$sufijo_asset}.js",
            ['jquery', 'leaflet', 'flavor-network-frontend'],
            self::VERSION,
            true
        );
    }

    /**
     * Registra el widget de dashboard de la red
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     * @since 4.1.0
     */
    public function register_dashboard_widget($registry) {
        // Cargar la clase del widget si no existe
        $widget_path = FLAVOR_CHAT_IA_PATH . 'includes/network/class-network-dashboard-widget.php';

        if (!class_exists('Flavor_Network_Dashboard_Widget') && file_exists($widget_path)) {
            require_once $widget_path;
        }

        // Registrar solo si la clase existe y las tablas de red están disponibles
        if (class_exists('Flavor_Network_Dashboard_Widget')) {
            $widget = new Flavor_Network_Dashboard_Widget();

            // Solo registrar si el widget puede mostrarse (tablas existen)
            if (method_exists($widget, 'can_display') && !$widget->can_display()) {
                return;
            }

            $registry->register($widget);
        }
    }
}
