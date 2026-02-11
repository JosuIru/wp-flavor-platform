<?php
/**
 * Gestion de yield farming y staking de LP tokens
 *
 * @package FlavorChatIA
 * @subpackage DexSolana
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dex_Solana_Farming {

    /**
     * Nombre de la tabla de farming
     *
     * @var string
     */
    private $tabla_farming;

    /**
     * Nombre de la tabla de posiciones LP
     *
     * @var string
     */
    private $tabla_lp_posiciones;

    /**
     * Nombre de la tabla de tokens
     *
     * @var string
     */
    private $tabla_tokens;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->tabla_farming      = $wpdb->prefix . 'flavor_dex_farming';
        $this->tabla_lp_posiciones = $wpdb->prefix . 'flavor_dex_lp_posiciones';
        $this->tabla_tokens       = $wpdb->prefix . 'flavor_dex_tokens';
    }

    /**
     * Crea un programa de farming para un pool determinado
     *
     * @param string $pool_id            Identificador del pool AMM.
     * @param string $reward_token_simbolo Simbolo del token de recompensa (e.g. JUP, RAY).
     * @param string $reward_token_mint  Direccion mint del token de recompensa.
     * @param float  $reward_por_dia     Cantidad de tokens de recompensa repartidos por dia.
     * @param int    $duracion_dias      Duracion del programa en dias (por defecto 90).
     * @return array Datos del programa creado.
     */
    public function crear_programa($pool_id, $reward_token_simbolo, $reward_token_mint, $reward_por_dia, $duracion_dias = 90) {
        global $wpdb;

        $programa_id        = 'farm_' . $pool_id . '_' . uniqid();
        $reward_por_segundo = $reward_por_dia / 86400;
        $fecha_inicio       = current_time('mysql');
        $fecha_fin          = gmdate('Y-m-d H:i:s', strtotime($fecha_inicio) + ($duracion_dias * 86400));

        $datos_programa = array(
            'programa_id'         => $programa_id,
            'pool_id'             => $pool_id,
            'reward_token_mint'   => $reward_token_mint,
            'reward_token_simbolo' => $reward_token_simbolo,
            'reward_por_segundo'  => $reward_por_segundo,
            'total_staked_lp'     => 0,
            'duracion_dias'       => $duracion_dias,
            'fecha_inicio'        => $fecha_inicio,
            'fecha_fin'           => $fecha_fin,
            'activo'              => 1,
        );

        $wpdb->insert(
            $this->tabla_farming,
            $datos_programa,
            array('%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%d')
        );

        return $datos_programa;
    }

    /**
     * Stakea LP tokens de un usuario en un programa de farming
     *
     * @param string $programa_id Identificador del programa de farming.
     * @param int    $usuario_id  ID del usuario de WordPress.
     * @param float  $lp_tokens   Cantidad de LP tokens a stakear.
     * @return array Resultado del staking.
     */
    public function stakear_lp($programa_id, $usuario_id, $lp_tokens) {
        global $wpdb;

        // Cargar el programa de farming
        $programa = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                $programa_id
            ),
            ARRAY_A
        );

        if (!$programa) {
            return array('success' => false, 'error' => __('Programa de farming no encontrado', 'flavor-chat-ia'));
        }

        // Verificar que el programa esta activo
        if ((int) $programa['activo'] !== 1) {
            return array('success' => false, 'error' => __('El programa de farming no esta activo', 'flavor-chat-ia'));
        }

        // Verificar que no ha expirado
        $timestamp_actual = current_time('timestamp');
        $timestamp_fin    = strtotime($programa['fecha_fin']);

        if ($timestamp_actual >= $timestamp_fin) {
            return array('success' => false, 'error' => __('El programa de farming ha expirado', 'flavor-chat-ia'));
        }

        // Obtener la posicion LP del usuario para este pool
        $posicion_lp = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_lp_posiciones} WHERE usuario_id = %d AND pool_id = %s",
                $usuario_id,
                $programa['pool_id']
            ),
            ARRAY_A
        );

        if (!$posicion_lp) {
            return array('success' => false, 'error' => __('El usuario no tiene posicion LP en este pool', 'flavor-chat-ia'));
        }

        if ((float) $posicion_lp['lp_tokens'] < $lp_tokens) {
            return array('success' => false, 'error' => __('LP tokens insuficientes', 'flavor-chat-ia'));
        }

        if ((int) $posicion_lp['staked'] === 1) {
            return array('success' => false, 'error' => __('Los LP tokens ya estan stakeados', 'flavor-chat-ia'));
        }

        // Marcar la posicion como stakeada
        $timestamp_staking       = current_time('mysql');
        $rewards_acumulados_json = wp_json_encode(array(
            'programa_id'         => $programa_id,
            'ultimo_harvest'      => $timestamp_staking,
            'total_cosechado'     => 0,
            'historial_harvests'  => array(),
        ));

        $wpdb->update(
            $this->tabla_lp_posiciones,
            array(
                'staked'                  => 1,
                'rewards_acumulados_json' => $rewards_acumulados_json,
            ),
            array(
                'usuario_id' => $usuario_id,
                'pool_id'    => $programa['pool_id'],
            ),
            array('%d', '%s'),
            array('%d', '%s')
        );

        // Actualizar el total stakeado en el programa de farming
        $nuevo_total_staked = (float) $programa['total_staked_lp'] + $lp_tokens;

        $wpdb->update(
            $this->tabla_farming,
            array('total_staked_lp' => $nuevo_total_staked),
            array('programa_id' => $programa_id),
            array('%f'),
            array('%s')
        );

        return array(
            'success'          => true,
            'programa'         => $programa,
            'lp_tokens_staked' => $lp_tokens,
            'total_staked'     => $nuevo_total_staked,
        );
    }

    /**
     * Retira LP tokens de un programa de farming
     *
     * @param string $programa_id Identificador del programa de farming.
     * @param int    $usuario_id  ID del usuario de WordPress.
     * @return array Resultado del unstaking.
     */
    public function unstakear_lp($programa_id, $usuario_id) {
        global $wpdb;

        // Primero cosechar rewards pendientes
        $resultado_cosecha = $this->cosechar_rewards($programa_id, $usuario_id);

        // Cargar el programa de farming
        $programa = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                $programa_id
            ),
            ARRAY_A
        );

        if (!$programa) {
            return array('success' => false, 'error' => __('Programa de farming no encontrado', 'flavor-chat-ia'));
        }

        // Obtener la posicion LP del usuario
        $posicion_lp = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_lp_posiciones} WHERE usuario_id = %d AND pool_id = %s AND staked = 1",
                $usuario_id,
                $programa['pool_id']
            ),
            ARRAY_A
        );

        if (!$posicion_lp) {
            return array('success' => false, 'error' => __('No se encontro posicion LP stakeada para este usuario', 'flavor-chat-ia'));
        }

        $lp_tokens_devueltos = (float) $posicion_lp['lp_tokens'];

        // Desmarcar la posicion como stakeada
        $wpdb->update(
            $this->tabla_lp_posiciones,
            array('staked' => 0),
            array(
                'usuario_id' => $usuario_id,
                'pool_id'    => $programa['pool_id'],
            ),
            array('%d'),
            array('%d', '%s')
        );

        // Decrementar el total stakeado en el programa
        $nuevo_total_staked = max(0, (float) $programa['total_staked_lp'] - $lp_tokens_devueltos);

        $wpdb->update(
            $this->tabla_farming,
            array('total_staked_lp' => $nuevo_total_staked),
            array('programa_id' => $programa_id),
            array('%f'),
            array('%s')
        );

        return array(
            'success'            => true,
            'lp_tokens_devueltos' => $lp_tokens_devueltos,
            'rewards_cosechados' => $resultado_cosecha,
        );
    }

    /**
     * Cosecha las recompensas pendientes de un usuario en un programa de farming
     *
     * @param string $programa_id Identificador del programa de farming.
     * @param int    $usuario_id  ID del usuario de WordPress.
     * @return array Resultado de la cosecha.
     */
    public function cosechar_rewards($programa_id, $usuario_id) {
        global $wpdb;

        // Calcular rewards pendientes
        $rewards_pendientes = $this->calcular_rewards_pendientes($programa_id, $usuario_id);

        if (!$rewards_pendientes || $rewards_pendientes['cantidad'] <= 0) {
            return array(
                'success'             => true,
                'reward_token'        => '',
                'cantidad_rewards'    => 0,
                'valor_usd_estimado'  => 0,
            );
        }

        // Cargar el programa para obtener datos del reward token
        $programa = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                $programa_id
            ),
            ARRAY_A
        );

        if (!$programa) {
            return array('success' => false, 'error' => __('Programa de farming no encontrado', 'flavor-chat-ia'));
        }

        // Obtener precio del reward token
        $precio_reward_token = $this->obtener_precio_token($programa['reward_token_mint']);
        $valor_usd_estimado  = $rewards_pendientes['cantidad'] * $precio_reward_token;

        // Actualizar el timestamp de ultimo harvest en la posicion LP
        $posicion_lp = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_lp_posiciones} WHERE usuario_id = %d AND pool_id = %s AND staked = 1",
                $usuario_id,
                $programa['pool_id']
            ),
            ARRAY_A
        );

        if ($posicion_lp) {
            $rewards_datos = json_decode($posicion_lp['rewards_acumulados_json'], true);

            if (!is_array($rewards_datos)) {
                $rewards_datos = array(
                    'programa_id'        => $programa_id,
                    'ultimo_harvest'     => current_time('mysql'),
                    'total_cosechado'    => 0,
                    'historial_harvests' => array(),
                );
            }

            $rewards_datos['ultimo_harvest']   = current_time('mysql');
            $rewards_datos['total_cosechado'] += $rewards_pendientes['cantidad'];
            $rewards_datos['historial_harvests'][] = array(
                'timestamp' => current_time('mysql'),
                'cantidad'  => $rewards_pendientes['cantidad'],
                'valor_usd' => $valor_usd_estimado,
            );

            $wpdb->update(
                $this->tabla_lp_posiciones,
                array('rewards_acumulados_json' => wp_json_encode($rewards_datos)),
                array(
                    'usuario_id' => $usuario_id,
                    'pool_id'    => $programa['pool_id'],
                ),
                array('%s'),
                array('%d', '%s')
            );
        }

        return array(
            'success'            => true,
            'reward_token'       => $programa['reward_token_simbolo'],
            'cantidad_rewards'   => $rewards_pendientes['cantidad'],
            'valor_usd_estimado' => $valor_usd_estimado,
        );
    }

    /**
     * Calcula las recompensas pendientes de cosechar para un usuario
     *
     * @param string $programa_id Identificador del programa de farming.
     * @param int    $usuario_id  ID del usuario de WordPress.
     * @return array Datos de rewards pendientes.
     */
    public function calcular_rewards_pendientes($programa_id, $usuario_id) {
        global $wpdb;

        // Cargar el programa de farming
        $programa = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                $programa_id
            ),
            ARRAY_A
        );

        if (!$programa) {
            return array('cantidad' => 0, 'tiempo_transcurrido_segundos' => 0, 'porcentaje_pool' => 0);
        }

        $total_staked_lp = (float) $programa['total_staked_lp'];

        if ($total_staked_lp <= 0) {
            return array('cantidad' => 0, 'tiempo_transcurrido_segundos' => 0, 'porcentaje_pool' => 0);
        }

        // Obtener la posicion LP stakeada del usuario
        $posicion_lp = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_lp_posiciones} WHERE usuario_id = %d AND pool_id = %s AND staked = 1",
                $usuario_id,
                $programa['pool_id']
            ),
            ARRAY_A
        );

        if (!$posicion_lp) {
            return array('cantidad' => 0, 'tiempo_transcurrido_segundos' => 0, 'porcentaje_pool' => 0);
        }

        $lp_tokens_usuario = (float) $posicion_lp['lp_tokens'];
        $rewards_datos     = json_decode($posicion_lp['rewards_acumulados_json'], true);

        if (!is_array($rewards_datos) || empty($rewards_datos['ultimo_harvest'])) {
            return array('cantidad' => 0, 'tiempo_transcurrido_segundos' => 0, 'porcentaje_pool' => 0);
        }

        // Calcular el tiempo transcurrido desde el ultimo harvest
        $timestamp_ultimo_harvest = strtotime($rewards_datos['ultimo_harvest']);
        $timestamp_actual         = current_time('timestamp');

        // No acumular rewards mas alla de la fecha de fin del programa
        $timestamp_fin = strtotime($programa['fecha_fin']);
        if ($timestamp_actual > $timestamp_fin) {
            $timestamp_actual = $timestamp_fin;
        }

        $tiempo_transcurrido_segundos = max(0, $timestamp_actual - $timestamp_ultimo_harvest);

        if ($tiempo_transcurrido_segundos <= 0) {
            return array('cantidad' => 0, 'tiempo_transcurrido_segundos' => 0, 'porcentaje_pool' => 0);
        }

        // Formula: rewards = (user_staked_lp / total_staked_lp) * reward_por_segundo * tiempo
        $porcentaje_pool    = $lp_tokens_usuario / $total_staked_lp;
        $reward_por_segundo = (float) $programa['reward_por_segundo'];
        $cantidad_rewards   = $porcentaje_pool * $reward_por_segundo * $tiempo_transcurrido_segundos;

        return array(
            'cantidad'                     => $cantidad_rewards,
            'tiempo_transcurrido_segundos' => $tiempo_transcurrido_segundos,
            'porcentaje_pool'              => $porcentaje_pool * 100,
        );
    }

    /**
     * Calcula el APR (Annual Percentage Rate) de un programa de farming
     *
     * @param string $programa_id Identificador del programa de farming.
     * @return float APR en porcentaje.
     */
    public function calcular_apr($programa_id) {
        global $wpdb;

        $programa = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                $programa_id
            ),
            ARRAY_A
        );

        if (!$programa) {
            return 0;
        }

        $total_staked_lp = (float) $programa['total_staked_lp'];

        if ($total_staked_lp <= 0) {
            return 0;
        }

        $reward_por_segundo  = (float) $programa['reward_por_segundo'];
        $precio_reward_token = $this->obtener_precio_token($programa['reward_token_mint']);

        // Estimar el TVL stakeado: usamos el total de LP tokens stakeados
        // como proxy del valor (en una implementacion real se calcularia el valor en USD)
        $tvl_staked = $total_staked_lp;

        if ($tvl_staked <= 0) {
            return 0;
        }

        // APR = (reward_por_segundo * 86400 * 365 * precio_reward_token) / tvl_staked * 100
        $recompensa_anual_usd = $reward_por_segundo * 86400 * 365 * $precio_reward_token;
        $apr                  = ($recompensa_anual_usd / $tvl_staked) * 100;

        return round($apr, 2);
    }

    /**
     * Lista los programas de farming disponibles
     *
     * @param bool $solo_activos Si es true, solo devuelve programas activos y no expirados.
     * @return array Lista de programas enriquecidos con APR.
     */
    public function listar_programas($solo_activos = true) {
        global $wpdb;

        if ($solo_activos) {
            $programas = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->tabla_farming} WHERE activo = %d AND fecha_fin > %s ORDER BY fecha_inicio DESC",
                    1,
                    current_time('mysql')
                ),
                ARRAY_A
            );
        } else {
            $programas = $wpdb->get_results(
                "SELECT * FROM {$this->tabla_farming} ORDER BY fecha_inicio DESC",
                ARRAY_A
            );
        }

        if (empty($programas)) {
            return array();
        }

        // Enriquecer cada programa con su APR calculado
        $programas_enriquecidos = array();

        foreach ($programas as $programa) {
            $programa['apr']        = $this->calcular_apr($programa['programa_id']);
            $programas_enriquecidos[] = $programa;
        }

        return $programas_enriquecidos;
    }

    /**
     * Obtiene todas las posiciones stakeadas de un usuario en todos los programas de farming
     *
     * @param int $usuario_id ID del usuario de WordPress.
     * @return array Lista de posiciones con rewards pendientes calculados.
     */
    public function obtener_posiciones_usuario($usuario_id) {
        global $wpdb;

        // Obtener todas las posiciones LP stakeadas del usuario
        $posiciones_stakeadas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_lp_posiciones} WHERE usuario_id = %d AND staked = 1",
                $usuario_id
            ),
            ARRAY_A
        );

        if (empty($posiciones_stakeadas)) {
            return array();
        }

        $posiciones_con_rewards = array();

        foreach ($posiciones_stakeadas as $posicion) {
            $rewards_datos = json_decode($posicion['rewards_acumulados_json'], true);

            if (!is_array($rewards_datos) || empty($rewards_datos['programa_id'])) {
                continue;
            }

            $programa_id = $rewards_datos['programa_id'];

            // Cargar datos del programa de farming
            $programa = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->tabla_farming} WHERE programa_id = %s",
                    $programa_id
                ),
                ARRAY_A
            );

            if (!$programa) {
                continue;
            }

            // Calcular rewards pendientes
            $rewards_pendientes = $this->calcular_rewards_pendientes($programa_id, $usuario_id);

            $posiciones_con_rewards[] = array(
                'posicion'           => $posicion,
                'programa'           => $programa,
                'rewards_pendientes' => $rewards_pendientes,
                'apr'                => $this->calcular_apr($programa_id),
            );
        }

        return $posiciones_con_rewards;
    }

    /**
     * Crea los programas de farming iniciales para los pools semilla
     *
     * Solo crea programas si no existe ninguno en la tabla de farming.
     *
     * @param array $pools Array de pools disponibles con sus pool_id.
     * @return array Lista de programas creados.
     */
    public function sembrar_programas_iniciales($pools) {
        global $wpdb;

        // Verificar si ya existen programas
        $cantidad_programas_existentes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_farming}"
        );

        if ($cantidad_programas_existentes > 0) {
            return array();
        }

        $programas_creados = array();

        // Definir configuracion de farming para cada pool semilla
        $configuraciones_farming = array(
            'SOL/USDC' => array(
                'reward_token_simbolo' => 'JUP',
                'reward_token_mint'    => 'JUPyiwrYJFskUPiHa7hkeR8VUtAeFoSYbKedZNsDvCN',
                'reward_por_dia'       => 1000,
                'duracion_dias'        => 90,
            ),
            'RAY/USDC' => array(
                'reward_token_simbolo' => 'RAY',
                'reward_token_mint'    => '4k3Dyjzvzp8eMZWUXbBCjEvwSkkk59S5iCNLY3QrkX6R',
                'reward_por_dia'       => 500,
                'duracion_dias'        => 90,
            ),
            'JUP/USDC' => array(
                'reward_token_simbolo' => 'JUP',
                'reward_token_mint'    => 'JUPyiwrYJFskUPiHa7hkeR8VUtAeFoSYbKedZNsDvCN',
                'reward_por_dia'       => 2000,
                'duracion_dias'        => 90,
            ),
        );

        foreach ($pools as $pool) {
            // Construir la clave del par a partir de los simbolos del pool
            $clave_par = $pool['token_a_simbolo'] . '/' . $pool['token_b_simbolo'];

            if (!isset($configuraciones_farming[$clave_par])) {
                continue;
            }

            $configuracion = $configuraciones_farming[$clave_par];

            $programa_creado = $this->crear_programa(
                $pool['pool_id'],
                $configuracion['reward_token_simbolo'],
                $configuracion['reward_token_mint'],
                $configuracion['reward_por_dia'],
                $configuracion['duracion_dias']
            );

            $programas_creados[] = $programa_creado;
        }

        return $programas_creados;
    }

    /**
     * Obtiene el precio en USD de un token a partir de su mint address
     *
     * @param string $mint_address Direccion mint del token.
     * @return float Precio en USD del token.
     */
    private function obtener_precio_token($mint_address) {
        global $wpdb;

        $precio = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT precio_usd FROM {$this->tabla_tokens} WHERE mint_address = %s",
                $mint_address
            )
        );

        return $precio ? (float) $precio : 0;
    }
}
