<?php
/**
 * Pool Manager - Gestiona pools AMM simulados con formula de producto constante (x*y=k)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dex_Solana_Pool_Manager {

    /**
     * @var object Instancia del registro de tokens para resolver simbolos a mint addresses.
     */
    private $token_registry;

    /**
     * Constructor.
     *
     * @param object $token_registry Instancia del registro de tokens.
     */
    public function __construct($token_registry) {
        $this->token_registry = $token_registry;
    }

    /**
     * Crea un nuevo pool de liquidez AMM con reservas iniciales.
     *
     * @param string $token_a_simbolo   Simbolo del token A (ej: 'SOL').
     * @param string $token_b_simbolo   Simbolo del token B (ej: 'USDC').
     * @param float  $reserva_a_inicial Cantidad inicial del token A.
     * @param float  $reserva_b_inicial Cantidad inicial del token B.
     * @param float  $fee_porcentaje    Porcentaje de comision por swap (por defecto 0.30%).
     * @return array|false Datos del pool creado o false en caso de error.
     */
    public function crear_pool($token_a_simbolo, $token_b_simbolo, $reserva_a_inicial, $reserva_b_inicial, $fee_porcentaje = 0.30) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';

        // Resolver simbolos a mint addresses mediante el registro de tokens
        $token_a_datos = $this->token_registry->obtener_token_por_simbolo($token_a_simbolo);
        $token_b_datos = $this->token_registry->obtener_token_por_simbolo($token_b_simbolo);

        if (!$token_a_datos || !$token_b_datos) {
            flavor_chat_ia_log( 'Tokens no encontrados en registry: ' . $token_a_simbolo . ', ' . $token_b_simbolo, 'warning', 'DEX-Solana' );
            return false;
        }

        $mint_address_token_a = $token_a_datos['mint_address'] ?? null;
        $mint_address_token_b = $token_b_datos['mint_address'] ?? null;

        if (!$mint_address_token_a || !$mint_address_token_b) {
            flavor_chat_ia_log( 'Mint addresses no encontradas para tokens: ' . $token_a_simbolo . ', ' . $token_b_simbolo, 'warning', 'DEX-Solana' );
            return false;
        }

        // Generar identificador unico del pool
        $identificador_pool = $token_a_simbolo . '_' . $token_b_simbolo . '_' . uniqid();

        // Calcular constante de producto (x * y = k)
        $constante_k_inicial = $reserva_a_inicial * $reserva_b_inicial;

        // Los LP tokens iniciales son la raiz cuadrada del producto de reservas
        $lp_tokens_iniciales = sqrt($constante_k_inicial);

        // Convertir fee de porcentaje a decimal para almacenar (0.30% -> 0.0030)
        $fee_decimal_almacenamiento = $fee_porcentaje / 100;

        $resultado_insercion = $wpdb->insert(
            $tabla_pools,
            array(
                'pool_id'             => $identificador_pool,
                'token_a_mint'        => $mint_address_token_a,
                'token_a_simbolo'     => $token_a_simbolo,
                'token_b_mint'        => $mint_address_token_b,
                'token_b_simbolo'     => $token_b_simbolo,
                'reserva_a'           => $reserva_a_inicial,
                'reserva_b'           => $reserva_b_inicial,
                'constante_k'         => $constante_k_inicial,
                'total_lp_tokens'     => $lp_tokens_iniciales,
                'fee_porcentaje'      => $fee_decimal_almacenamiento,
                'volumen_24h_usd'     => 0,
                'fees_acumuladas_usd' => 0,
                'apy_estimado'        => 0,
                'activo'              => 1,
            ),
            array(
                '%s', '%s', '%s', '%s', '%s',
                '%f', '%f', '%f', '%f', '%f',
                '%f', '%f', '%f', '%d',
            )
        );

        if ($resultado_insercion === false) {
            return false;
        }

        $datos_pool_creado = array(
            'pool_id'             => $identificador_pool,
            'token_a_simbolo'     => $token_a_simbolo,
            'token_a_mint'        => $mint_address_token_a,
            'token_b_simbolo'     => $token_b_simbolo,
            'token_b_mint'        => $mint_address_token_b,
            'reserva_a'           => $reserva_a_inicial,
            'reserva_b'           => $reserva_b_inicial,
            'constante_k'         => $constante_k_inicial,
            'total_lp_tokens'     => $lp_tokens_iniciales,
            'fee_porcentaje'      => $fee_porcentaje,
            'volumen_24h_usd'     => 0,
            'fees_acumuladas_usd' => 0,
            'activo'              => 1,
        );

        return $datos_pool_creado;
    }

    /**
     * Calcula la cantidad de salida de un swap usando la formula de producto constante.
     * No modifica las reservas del pool (solo calculo).
     *
     * @param string $pool_id               Identificador del pool.
     * @param string $token_entrada_simbolo  Simbolo del token que se deposita.
     * @param float  $cantidad_entrada       Cantidad del token que se deposita.
     * @return array|false Resultado del calculo o false si hay error.
     */
    public function calcular_swap_en_pool($pool_id, $token_entrada_simbolo, $cantidad_entrada) {
        $datos_pool = $this->obtener_pool($pool_id);

        if (!$datos_pool) {
            return false;
        }

        // Determinar cual es el token de entrada y cual el de salida
        $es_token_a_entrada = ($token_entrada_simbolo === $datos_pool->token_a_simbolo);
        $es_token_b_entrada = ($token_entrada_simbolo === $datos_pool->token_b_simbolo);

        if (!$es_token_a_entrada && !$es_token_b_entrada) {
            return false;
        }

        $reserva_token_entrada = $es_token_a_entrada
            ? floatval($datos_pool->reserva_a)
            : floatval($datos_pool->reserva_b);

        $reserva_token_salida = $es_token_a_entrada
            ? floatval($datos_pool->reserva_b)
            : floatval($datos_pool->reserva_a);

        $simbolo_token_salida = $es_token_a_entrada
            ? $datos_pool->token_b_simbolo
            : $datos_pool->token_a_simbolo;

        // El fee se almacena como decimal (0.0030 para 0.30%), convertir a porcentaje para calculo
        $fee_porcentaje_pool = floatval($datos_pool->fee_porcentaje) * 100;

        // Aplicar comision: descontar fee de la cantidad de entrada
        $fee_cobrado = $cantidad_entrada * ($fee_porcentaje_pool / 100);
        $cantidad_con_fee_descontado = $cantidad_entrada * (1 - $fee_porcentaje_pool / 100);

        // Formula de producto constante: cantidad_salida = (reserva_salida * cantidad_con_fee) / (reserva_entrada + cantidad_con_fee)
        $cantidad_salida = ($reserva_token_salida * $cantidad_con_fee_descontado)
            / ($reserva_token_entrada + $cantidad_con_fee_descontado);

        // Precio efectivo: cuantos tokens de salida se obtienen por cada token de entrada
        $precio_efectivo = ($cantidad_entrada > 0) ? ($cantidad_salida / $cantidad_entrada) : 0;

        // Precio spot antes del swap (sin considerar impacto)
        $precio_spot_antes = ($reserva_token_entrada > 0) ? ($reserva_token_salida / $reserva_token_entrada) : 0;

        // Impacto en el precio (price impact) como porcentaje
        $impacto_precio_porcentaje = ($precio_spot_antes > 0)
            ? abs(1 - ($precio_efectivo / $precio_spot_antes)) * 100
            : 0;

        $resultado_calculo = array(
            'cantidad_salida'    => $cantidad_salida,
            'precio'             => $precio_efectivo,
            'impacto_precio'     => round($impacto_precio_porcentaje, 4),
            'fee_cobrado'        => $fee_cobrado,
            'token_salida'       => $simbolo_token_salida,
            'token_entrada'      => $token_entrada_simbolo,
            'cantidad_entrada'   => $cantidad_entrada,
            'reserva_entrada'    => $reserva_token_entrada,
            'reserva_salida'     => $reserva_token_salida,
        );

        return $resultado_calculo;
    }

    /**
     * Ejecuta un swap en el pool: calcula el resultado y actualiza las reservas.
     *
     * @param string $pool_id               Identificador del pool.
     * @param string $token_entrada_simbolo  Simbolo del token que se deposita.
     * @param float  $cantidad_entrada       Cantidad del token que se deposita.
     * @return array|false Resultado del swap ejecutado o false si hay error.
     */
    public function ejecutar_swap_en_pool($pool_id, $token_entrada_simbolo, $cantidad_entrada) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';

        // Primero calcular el resultado del swap sin modificar nada
        $resultado_calculo = $this->calcular_swap_en_pool($pool_id, $token_entrada_simbolo, $cantidad_entrada);

        if (!$resultado_calculo) {
            return false;
        }

        $datos_pool = $this->obtener_pool($pool_id);
        $cantidad_salida = $resultado_calculo['cantidad_salida'];
        $fee_cobrado = $resultado_calculo['fee_cobrado'];

        // Determinar nuevas reservas segun el token de entrada
        $es_token_a_entrada = ($token_entrada_simbolo === $datos_pool->token_a_simbolo);

        if ($es_token_a_entrada) {
            $nueva_reserva_a = floatval($datos_pool->reserva_a) + $cantidad_entrada;
            $nueva_reserva_b = floatval($datos_pool->reserva_b) - $cantidad_salida;
        } else {
            $nueva_reserva_a = floatval($datos_pool->reserva_a) - $cantidad_salida;
            $nueva_reserva_b = floatval($datos_pool->reserva_b) + $cantidad_entrada;
        }

        // Recalcular constante de producto tras el swap
        $nueva_constante_k = $nueva_reserva_a * $nueva_reserva_b;

        // Actualizar volumen 24h y fees acumuladas
        // Estimar valor en USD del fee cobrado (simplificacion: usar precio spot)
        $volumen_24h_actual = floatval($datos_pool->volumen_24h_usd);
        $fees_acumuladas_actuales = floatval($datos_pool->fees_acumuladas_usd);

        // Estimar valor USD del swap para volumen (cantidad_salida si el token salida es USDC, o fee_cobrado como aproximacion)
        $valor_swap_usd = $this->estimar_valor_usd_swap($token_entrada_simbolo, $cantidad_entrada, $resultado_calculo);
        $valor_fee_usd = $this->estimar_valor_usd_swap($token_entrada_simbolo, $fee_cobrado, $resultado_calculo);

        $nuevo_volumen_24h = $volumen_24h_actual + $valor_swap_usd;
        $nuevas_fees_acumuladas = $fees_acumuladas_actuales + $valor_fee_usd;

        // Actualizar el pool en la base de datos
        $wpdb->update(
            $tabla_pools,
            array(
                'reserva_a'           => $nueva_reserva_a,
                'reserva_b'           => $nueva_reserva_b,
                'constante_k'         => $nueva_constante_k,
                'volumen_24h_usd'     => $nuevo_volumen_24h,
                'fees_acumuladas_usd' => $nuevas_fees_acumuladas,
            ),
            array('pool_id' => $pool_id),
            array('%f', '%f', '%f', '%f', '%f'),
            array('%s')
        );

        $resultado_calculo['nueva_reserva_a'] = $nueva_reserva_a;
        $resultado_calculo['nueva_reserva_b'] = $nueva_reserva_b;
        $resultado_calculo['nueva_constante_k'] = $nueva_constante_k;

        return $resultado_calculo;
    }

    /**
     * Agrega liquidez a un pool existente de forma proporcional a las reservas actuales.
     *
     * @param string $pool_id           Identificador del pool.
     * @param float  $cantidad_token_a  Cantidad deseada del token A a depositar.
     * @param float  $cantidad_token_b  Cantidad deseada del token B a depositar.
     * @param int    $usuario_id        ID del usuario que provee la liquidez.
     * @return array|false Resultado de la provision de liquidez o false si hay error.
     */
    public function agregar_liquidez($pool_id, $cantidad_token_a, $cantidad_token_b, $usuario_id) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';
        $tabla_lp_posiciones = $wpdb->prefix . 'flavor_dex_lp_posiciones';

        $datos_pool = $this->obtener_pool($pool_id);

        if (!$datos_pool) {
            return false;
        }

        $reserva_actual_a = floatval($datos_pool->reserva_a);
        $reserva_actual_b = floatval($datos_pool->reserva_b);
        $total_lp_tokens_existentes = floatval($datos_pool->total_lp_tokens);

        // Calcular LP tokens a emitir y cantidades reales a depositar
        $lp_tokens_emitidos = 0;
        $cantidad_real_token_a = 0;
        $cantidad_real_token_b = 0;

        if ($total_lp_tokens_existentes == 0) {
            // Primer proveedor de liquidez: LP tokens = sqrt(a * b)
            $lp_tokens_emitidos = sqrt($cantidad_token_a * $cantidad_token_b);
            $cantidad_real_token_a = $cantidad_token_a;
            $cantidad_real_token_b = $cantidad_token_b;
        } else {
            // Proveedores subsiguientes: deposito proporcional a las reservas actuales
            // Calcular el ratio proporcional basado en el minimo de ambos aportes
            $ratio_token_a = $cantidad_token_a / $reserva_actual_a;
            $ratio_token_b = $cantidad_token_b / $reserva_actual_b;
            $ratio_minimo = min($ratio_token_a, $ratio_token_b);

            // Ajustar cantidades para mantener la proporcion del pool
            $cantidad_real_token_a = $ratio_minimo * $reserva_actual_a;
            $cantidad_real_token_b = $ratio_minimo * $reserva_actual_b;

            // LP tokens proporcionales al aporte
            $lp_tokens_emitidos = $ratio_minimo * $total_lp_tokens_existentes;
        }

        // Actualizar reservas y total de LP tokens en el pool
        $nueva_reserva_a = $reserva_actual_a + $cantidad_real_token_a;
        $nueva_reserva_b = $reserva_actual_b + $cantidad_real_token_b;
        $nuevo_total_lp_tokens = $total_lp_tokens_existentes + $lp_tokens_emitidos;
        $nueva_constante_k = $nueva_reserva_a * $nueva_reserva_b;

        $wpdb->update(
            $tabla_pools,
            array(
                'reserva_a'       => $nueva_reserva_a,
                'reserva_b'       => $nueva_reserva_b,
                'constante_k'     => $nueva_constante_k,
                'total_lp_tokens' => $nuevo_total_lp_tokens,
            ),
            array('pool_id' => $pool_id),
            array('%f', '%f', '%f', '%f'),
            array('%s')
        );

        // Verificar si el usuario ya tiene una posicion LP en este pool
        $posicion_existente = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tabla_lp_posiciones WHERE usuario_id = %d AND pool_id = %s",
                $usuario_id,
                $pool_id
            )
        );

        if ($posicion_existente) {
            // Actualizar posicion LP existente
            $lp_tokens_actualizados = floatval($posicion_existente->lp_tokens) + $lp_tokens_emitidos;
            $token_a_depositado_total = floatval($posicion_existente->token_a_depositado) + $cantidad_real_token_a;
            $token_b_depositado_total = floatval($posicion_existente->token_b_depositado) + $cantidad_real_token_b;

            $wpdb->update(
                $tabla_lp_posiciones,
                array(
                    'lp_tokens'          => $lp_tokens_actualizados,
                    'token_a_depositado' => $token_a_depositado_total,
                    'token_b_depositado' => $token_b_depositado_total,
                ),
                array(
                    'usuario_id' => $usuario_id,
                    'pool_id'    => $pool_id,
                ),
                array('%f', '%f', '%f'),
                array('%d', '%s')
            );
        } else {
            // Crear nueva posicion LP
            $wpdb->insert(
                $tabla_lp_posiciones,
                array(
                    'usuario_id'         => $usuario_id,
                    'pool_id'            => $pool_id,
                    'lp_tokens'          => $lp_tokens_emitidos,
                    'token_a_depositado' => $cantidad_real_token_a,
                    'token_b_depositado' => $cantidad_real_token_b,
                    'valor_entrada_usd'  => 0,
                    'staked'             => 0,
                ),
                array('%d', '%s', '%f', '%f', '%f', '%f', '%d')
            );
        }

        // Calcular porcentaje de participacion del usuario en el pool
        $porcentaje_participacion = ($nuevo_total_lp_tokens > 0)
            ? ($lp_tokens_emitidos / $nuevo_total_lp_tokens) * 100
            : 0;

        $resultado_liquidez = array(
            'lp_tokens_emitidos' => $lp_tokens_emitidos,
            'token_a_real'       => $cantidad_real_token_a,
            'token_b_real'       => $cantidad_real_token_b,
            'share_porcentaje'   => round($porcentaje_participacion, 4),
            'pool_id'            => $pool_id,
            'token_a_simbolo'    => $datos_pool->token_a_simbolo,
            'token_b_simbolo'    => $datos_pool->token_b_simbolo,
        );

        return $resultado_liquidez;
    }

    /**
     * Retira liquidez de un pool. El usuario puede retirar un porcentaje de su posicion.
     *
     * @param string $pool_id    Identificador del pool.
     * @param int    $usuario_id ID del usuario que retira liquidez.
     * @param float  $porcentaje Porcentaje de la posicion a retirar (1-100). Por defecto 100%.
     * @return array|false Cantidades retiradas o false si hay error.
     */
    public function retirar_liquidez($pool_id, $usuario_id, $porcentaje = 100) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';
        $tabla_lp_posiciones = $wpdb->prefix . 'flavor_dex_lp_posiciones';

        // Cargar posicion LP del usuario
        $posicion_usuario = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tabla_lp_posiciones WHERE usuario_id = %d AND pool_id = %s",
                $usuario_id,
                $pool_id
            )
        );

        if (!$posicion_usuario || floatval($posicion_usuario->lp_tokens) <= 0) {
            return false;
        }

        $datos_pool = $this->obtener_pool($pool_id);

        if (!$datos_pool) {
            return false;
        }

        $lp_tokens_usuario = floatval($posicion_usuario->lp_tokens);
        $total_lp_tokens_pool = floatval($datos_pool->total_lp_tokens);
        $reserva_actual_a = floatval($datos_pool->reserva_a);
        $reserva_actual_b = floatval($datos_pool->reserva_b);

        // Calcular LP tokens a quemar segun el porcentaje solicitado
        $porcentaje_normalizado = max(0, min(100, $porcentaje));
        $lp_tokens_a_retirar = $lp_tokens_usuario * ($porcentaje_normalizado / 100);

        // Calcular cantidades de tokens a devolver proporcionalmente
        $proporcion_retiro = ($total_lp_tokens_pool > 0)
            ? ($lp_tokens_a_retirar / $total_lp_tokens_pool)
            : 0;

        $token_a_retirado = $reserva_actual_a * $proporcion_retiro;
        $token_b_retirado = $reserva_actual_b * $proporcion_retiro;

        // Actualizar reservas del pool
        $nueva_reserva_a = $reserva_actual_a - $token_a_retirado;
        $nueva_reserva_b = $reserva_actual_b - $token_b_retirado;
        $nuevo_total_lp_tokens = $total_lp_tokens_pool - $lp_tokens_a_retirar;
        $nueva_constante_k = $nueva_reserva_a * $nueva_reserva_b;

        $wpdb->update(
            $tabla_pools,
            array(
                'reserva_a'       => $nueva_reserva_a,
                'reserva_b'       => $nueva_reserva_b,
                'constante_k'     => $nueva_constante_k,
                'total_lp_tokens' => $nuevo_total_lp_tokens,
            ),
            array('pool_id' => $pool_id),
            array('%f', '%f', '%f', '%f'),
            array('%s')
        );

        // Actualizar o eliminar la posicion LP del usuario
        $lp_tokens_restantes = $lp_tokens_usuario - $lp_tokens_a_retirar;

        if ($lp_tokens_restantes <= 0 || $porcentaje_normalizado >= 100) {
            // Eliminar la posicion LP completamente
            $wpdb->delete(
                $tabla_lp_posiciones,
                array(
                    'usuario_id' => $usuario_id,
                    'pool_id'    => $pool_id,
                ),
                array('%d', '%s')
            );
        } else {
            // Actualizar la posicion LP con los tokens restantes
            $token_a_depositado_restante = floatval($posicion_usuario->token_a_depositado) * (1 - $porcentaje_normalizado / 100);
            $token_b_depositado_restante = floatval($posicion_usuario->token_b_depositado) * (1 - $porcentaje_normalizado / 100);

            $wpdb->update(
                $tabla_lp_posiciones,
                array(
                    'lp_tokens'          => $lp_tokens_restantes,
                    'token_a_depositado' => $token_a_depositado_restante,
                    'token_b_depositado' => $token_b_depositado_restante,
                ),
                array(
                    'usuario_id' => $usuario_id,
                    'pool_id'    => $pool_id,
                ),
                array('%f', '%f', '%f'),
                array('%d', '%s')
            );
        }

        $resultado_retiro = array(
            'pool_id'           => $pool_id,
            'token_a_simbolo'   => $datos_pool->token_a_simbolo,
            'token_b_simbolo'   => $datos_pool->token_b_simbolo,
            'token_a_retirado'  => $token_a_retirado,
            'token_b_retirado'  => $token_b_retirado,
            'lp_tokens_quemados' => $lp_tokens_a_retirar,
            'porcentaje_retirado' => $porcentaje_normalizado,
        );

        return $resultado_retiro;
    }

    /**
     * Obtiene los detalles completos de un pool desde la base de datos.
     *
     * @param string $pool_id Identificador del pool.
     * @return object|null Fila del pool o null si no existe.
     */
    public function obtener_pool($pool_id) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';

        $datos_pool = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tabla_pools WHERE pool_id = %s",
                $pool_id
            )
        );

        return $datos_pool;
    }

    /**
     * Lista todos los pools, opcionalmente solo los activos.
     * Enriquece cada pool con el APY estimado.
     *
     * @param bool $solo_activos Si true, solo devuelve pools activos.
     * @return array Lista de pools con datos enriquecidos.
     */
    public function listar_pools($solo_activos = true) {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';

        if ($solo_activos) {
            $lista_pools = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_pools WHERE activo = %d ORDER BY volumen_24h_usd DESC",
                    1
                )
            );
        } else {
            $lista_pools = $wpdb->get_results(
                "SELECT * FROM $tabla_pools ORDER BY volumen_24h_usd DESC"
            );
        }

        if (!$lista_pools) {
            return array();
        }

        // Enriquecer cada pool con el APY estimado
        $pools_enriquecidos = array();

        foreach ($lista_pools as $datos_pool) {
            $pool_como_array = (array) $datos_pool;
            $pool_como_array['apy_estimado'] = $this->obtener_apy_estimado($datos_pool->pool_id);
            $pools_enriquecidos[] = $pool_como_array;
        }

        return $pools_enriquecidos;
    }

    /**
     * Estima el APY (Annual Percentage Yield) de un pool basado en las fees generadas.
     *
     * Formula: apy = (fees_24h * 365 / tvl) * 100
     *
     * @param string $pool_id Identificador del pool.
     * @return float APY estimado como porcentaje.
     */
    public function obtener_apy_estimado($pool_id) {
        $datos_pool = $this->obtener_pool($pool_id);

        if (!$datos_pool) {
            return 0.0;
        }

        $fees_24h = floatval($datos_pool->fees_acumuladas_usd);
        $reserva_a = floatval($datos_pool->reserva_a);
        $reserva_b = floatval($datos_pool->reserva_b);

        // Obtener precios de los tokens para calcular TVL
        $precio_token_a = $this->obtener_precio_token($datos_pool->token_a_simbolo);
        $precio_token_b = $this->obtener_precio_token($datos_pool->token_b_simbolo);

        // TVL (Total Value Locked) = reserva_a * precio_a + reserva_b * precio_b
        $valor_total_bloqueado = ($reserva_a * $precio_token_a) + ($reserva_b * $precio_token_b);

        if ($valor_total_bloqueado <= 0) {
            return 0.0;
        }

        // APY anualizado basado en fees de 24h
        $apy_estimado = ($fees_24h * 365 / $valor_total_bloqueado) * 100;

        return round($apy_estimado, 4);
    }

    /**
     * Crea pools iniciales con liquidez realista si no existen pools en el sistema.
     * Estos pools semilla permiten comenzar a operar sin necesidad de proveedores externos.
     *
     * @return array Lista de pools creados o array vacio si ya existian pools.
     */
    public function sembrar_pools_iniciales() {
        global $wpdb;

        $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';

        // Verificar si ya existen pools en el sistema
        $cantidad_pools_existentes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_pools");

        if ($cantidad_pools_existentes > 0) {
            return array();
        }

        // Definir pools semilla con liquidez realista
        $configuracion_pools_semilla = array(
            array(
                'token_a'   => 'SOL',
                'token_b'   => 'USDC',
                'reserva_a' => 5000,
                'reserva_b' => 750000,
                'fee'       => 0.30,
            ),
            array(
                'token_a'   => 'RAY',
                'token_b'   => 'USDC',
                'reserva_a' => 100000,
                'reserva_b' => 250000,
                'fee'       => 0.30,
            ),
            array(
                'token_a'   => 'JUP',
                'token_b'   => 'USDC',
                'reserva_a' => 200000,
                'reserva_b' => 200000,
                'fee'       => 0.30,
            ),
            array(
                'token_a'   => 'BONK',
                'token_b'   => 'USDC',
                'reserva_a' => 5000000000,
                'reserva_b' => 150000,
                'fee'       => 0.30,
            ),
            array(
                'token_a'   => 'SOL',
                'token_b'   => 'RAY',
                'reserva_a' => 2000,
                'reserva_b' => 80000,
                'fee'       => 0.30,
            ),
        );

        $pools_creados = array();

        foreach ($configuracion_pools_semilla as $configuracion_pool) {
            $pool_creado = $this->crear_pool(
                $configuracion_pool['token_a'],
                $configuracion_pool['token_b'],
                $configuracion_pool['reserva_a'],
                $configuracion_pool['reserva_b'],
                $configuracion_pool['fee']
            );

            if ($pool_creado) {
                $pools_creados[] = $pool_creado;
            }
        }

        return $pools_creados;
    }

    /**
     * Estima el valor en USD de un swap para actualizar el volumen del pool.
     *
     * @param string $token_entrada_simbolo Simbolo del token de entrada.
     * @param float  $cantidad              Cantidad del token.
     * @param array  $resultado_calculo     Resultado del calculo del swap.
     * @return float Valor estimado en USD.
     */
    private function estimar_valor_usd_swap($token_entrada_simbolo, $cantidad, $resultado_calculo) {
        // Si el token de entrada es una stablecoin, el valor es directo
        $stablecoins = array('USDC', 'USDT', 'DAI', 'BUSD');

        if (in_array(strtoupper($token_entrada_simbolo), $stablecoins)) {
            return $cantidad;
        }

        // Si el token de salida es una stablecoin, usar la cantidad de salida
        if (isset($resultado_calculo['token_salida']) && in_array(strtoupper($resultado_calculo['token_salida']), $stablecoins)) {
            $precio_implicito = $resultado_calculo['cantidad_salida'] / max($resultado_calculo['cantidad_entrada'], 0.0000001);
            return $cantidad * $precio_implicito;
        }

        // Intentar obtener precio del token desde el registro
        $precio_token = $this->obtener_precio_token($token_entrada_simbolo);

        return $cantidad * $precio_token;
    }

    /**
     * Obtiene el precio en USD de un token consultando el registro de tokens.
     *
     * @param string $simbolo_token Simbolo del token.
     * @return float Precio en USD del token.
     */
    private function obtener_precio_token($simbolo_token) {
        // Stablecoins siempre valen 1 USD
        $stablecoins = array('USDC', 'USDT', 'DAI', 'BUSD');

        if (in_array(strtoupper($simbolo_token), $stablecoins)) {
            return 1.0;
        }

        // Consultar el registro de tokens para obtener el precio actualizado
        if ($this->token_registry && method_exists($this->token_registry, 'obtener_precio')) {
            $precio_obtenido = $this->token_registry->obtener_precio($simbolo_token);
            if ($precio_obtenido && $precio_obtenido > 0) {
                return floatval($precio_obtenido);
            }
        }

        // Precios de referencia por defecto si el registro no los tiene
        $precios_referencia = array(
            'SOL'  => 150.0,
            'RAY'  => 2.50,
            'JUP'  => 1.00,
            'BONK' => 0.00003,
        );

        return isset($precios_referencia[strtoupper($simbolo_token)])
            ? $precios_referencia[strtoupper($simbolo_token)]
            : 0.0;
    }
}
