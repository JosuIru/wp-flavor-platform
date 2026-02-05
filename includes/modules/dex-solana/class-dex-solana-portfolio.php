<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona el portfolio por usuario para el DEX de Solana.
 * Incluye balances, posiciones LP y posiciones de farming.
 */
class Flavor_Dex_Solana_Portfolio {

    /**
     * @var int ID del usuario de WordPress.
     */
    private $usuario_id;

    /**
     * @var float Balance inicial en USDC al crear el portfolio.
     */
    private $balance_inicial_usdc;

    /**
     * @var object|null Fila completa de la base de datos con los datos del portfolio.
     */
    private $datos_portfolio;

    /**
     * @var array Tokens SPL adicionales (clave: símbolo, valor: cantidad).
     */
    private $tokens;

    /**
     * @var array Posiciones de liquidez (LP) del usuario.
     */
    private $posiciones_lp;

    /**
     * @var array Posiciones de farming del usuario.
     */
    private $posiciones_farming;

    /**
     * Constructor.
     *
     * @param int   $usuario_id          ID del usuario de WordPress.
     * @param float $balance_inicial_usdc Balance inicial en USDC (por defecto 1000).
     */
    public function __construct($usuario_id, $balance_inicial_usdc = 1000.0) {
        $this->usuario_id          = $usuario_id;
        $this->balance_inicial_usdc = $balance_inicial_usdc;

        $this->cargar_o_crear_portfolio();
    }

    /**
     * Carga el portfolio existente desde la base de datos o crea uno nuevo
     * si el usuario no tiene registro previo.
     */
    private function cargar_o_crear_portfolio() {
        global $wpdb;

        $nombre_tabla    = $wpdb->prefix . 'flavor_dex_portfolio';
        $fila_existente  = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$nombre_tabla} WHERE usuario_id = %d",
                $this->usuario_id
            )
        );

        if (!$fila_existente) {
            $datos_insercion = array(
                'usuario_id'             => $this->usuario_id,
                'balance_usdc'           => $this->balance_inicial_usdc,
                'balance_sol'            => 0,
                'balance_inicial_usdc'   => $this->balance_inicial_usdc,
                'tokens_json'            => wp_json_encode(array()),
                'lp_posiciones_json'     => wp_json_encode(array()),
                'farming_posiciones_json' => wp_json_encode(array()),
                'fees_acumuladas_usd'    => 0,
                'contador_swaps'         => 0,
                'modo_activo'            => 'paper',
                'wallet_address'         => '',
            );

            $wpdb->insert($nombre_tabla, $datos_insercion);

            $fila_existente = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$nombre_tabla} WHERE usuario_id = %d",
                    $this->usuario_id
                )
            );
        }

        $this->datos_portfolio   = $fila_existente;
        $this->tokens            = json_decode($fila_existente->tokens_json, true) ?: array();
        $this->posiciones_lp     = json_decode($fila_existente->lp_posiciones_json, true) ?: array();
        $this->posiciones_farming = json_decode($fila_existente->farming_posiciones_json, true) ?: array();
    }

    /**
     * Obtiene el balance de un token específico.
     *
     * @param string $simbolo Símbolo del token (e.g. 'USDC', 'SOL', 'RAY').
     * @return float Balance disponible del token.
     */
    public function obtener_balance_token($simbolo) {
        $simbolo_normalizado = strtoupper(trim($simbolo));

        if ($simbolo_normalizado === 'USDC') {
            return (float) $this->datos_portfolio->balance_usdc;
        }

        if ($simbolo_normalizado === 'SOL') {
            return (float) $this->datos_portfolio->balance_sol;
        }

        if (isset($this->tokens[$simbolo_normalizado])) {
            return (float) $this->tokens[$simbolo_normalizado];
        }

        return 0.0;
    }

    /**
     * Agrega una cantidad de tokens al portfolio del usuario.
     *
     * @param string $simbolo  Símbolo del token.
     * @param float  $cantidad Cantidad a agregar.
     */
    public function agregar_tokens($simbolo, $cantidad) {
        $simbolo_normalizado = strtoupper(trim($simbolo));
        $cantidad_a_agregar  = (float) $cantidad;

        if ($simbolo_normalizado === 'USDC') {
            $this->datos_portfolio->balance_usdc = (float) $this->datos_portfolio->balance_usdc + $cantidad_a_agregar;
        } elseif ($simbolo_normalizado === 'SOL') {
            $this->datos_portfolio->balance_sol = (float) $this->datos_portfolio->balance_sol + $cantidad_a_agregar;
        } else {
            if (!isset($this->tokens[$simbolo_normalizado])) {
                $this->tokens[$simbolo_normalizado] = 0.0;
            }
            $this->tokens[$simbolo_normalizado] += $cantidad_a_agregar;
        }

        $this->guardar();
    }

    /**
     * Resta una cantidad de tokens del portfolio del usuario.
     *
     * @param string $simbolo  Símbolo del token.
     * @param float  $cantidad Cantidad a restar.
     * @return bool True si se pudo restar, false si el balance es insuficiente.
     */
    public function restar_tokens($simbolo, $cantidad) {
        $simbolo_normalizado = strtoupper(trim($simbolo));
        $cantidad_a_restar   = (float) $cantidad;
        $balance_actual      = $this->obtener_balance_token($simbolo_normalizado);

        if ($balance_actual < $cantidad_a_restar) {
            return false;
        }

        if ($simbolo_normalizado === 'USDC') {
            $this->datos_portfolio->balance_usdc = (float) $this->datos_portfolio->balance_usdc - $cantidad_a_restar;
        } elseif ($simbolo_normalizado === 'SOL') {
            $this->datos_portfolio->balance_sol = (float) $this->datos_portfolio->balance_sol - $cantidad_a_restar;
        } else {
            $this->tokens[$simbolo_normalizado] -= $cantidad_a_restar;

            if ($this->tokens[$simbolo_normalizado] <= 0) {
                unset($this->tokens[$simbolo_normalizado]);
            }
        }

        $this->guardar();
        return true;
    }

    /**
     * Obtiene el estado completo del portfolio para mostrar al usuario.
     *
     * @return array Estado completo con balances, posiciones y metadatos.
     */
    public function obtener_estado_completo() {
        return array(
            'balance_usdc'          => (float) $this->datos_portfolio->balance_usdc,
            'balance_sol'           => (float) $this->datos_portfolio->balance_sol,
            'balance_inicial_usdc'  => (float) $this->datos_portfolio->balance_inicial_usdc,
            'tokens'                => $this->tokens,
            'posiciones_lp'         => $this->posiciones_lp,
            'posiciones_farming'    => $this->posiciones_farming,
            'fees_acumuladas_usd'   => (float) $this->datos_portfolio->fees_acumuladas_usd,
            'contador_swaps'        => (int) $this->datos_portfolio->contador_swaps,
            'modo_activo'           => $this->datos_portfolio->modo_activo,
            'wallet_address'        => $this->datos_portfolio->wallet_address,
        );
    }

    /**
     * Incrementa el contador de swaps realizados por el usuario.
     */
    public function incrementar_contador_swaps() {
        $this->datos_portfolio->contador_swaps = (int) $this->datos_portfolio->contador_swaps + 1;
        $this->guardar();
    }

    /**
     * Agrega una cantidad de fees acumuladas en USD al portfolio.
     *
     * @param float $cantidad_usd Cantidad de fees en USD a agregar.
     */
    public function agregar_fees($cantidad_usd) {
        $cantidad_fees = (float) $cantidad_usd;
        $this->datos_portfolio->fees_acumuladas_usd = (float) $this->datos_portfolio->fees_acumuladas_usd + $cantidad_fees;
        $this->guardar();
    }

    /**
     * Cambia el modo del portfolio entre 'paper' y 'real'.
     *
     * @param string $modo Modo deseado: 'paper' o 'real'.
     * @return bool True si el cambio fue exitoso, false si el modo no es válido.
     */
    public function cambiar_modo($modo) {
        $modo_normalizado  = strtolower(trim($modo));
        $modos_permitidos  = array('paper', 'real');

        if (!in_array($modo_normalizado, $modos_permitidos, true)) {
            return false;
        }

        $this->datos_portfolio->modo_activo = $modo_normalizado;
        $this->guardar();
        return true;
    }

    /**
     * Obtiene el modo activo actual del portfolio.
     *
     * @return string Modo activo ('paper' o 'real').
     */
    public function obtener_modo() {
        return $this->datos_portfolio->modo_activo;
    }

    /**
     * Establece la dirección de wallet para el modo real.
     *
     * @param string $direccion Dirección de la wallet de Solana.
     */
    public function set_wallet_address($direccion) {
        $direccion_sanitizada = sanitize_text_field($direccion);
        $this->datos_portfolio->wallet_address = $direccion_sanitizada;
        $this->guardar();
    }

    /**
     * Resetea el portfolio al estado inicial.
     * Limpia todos los tokens, posiciones LP y farming.
     *
     * @param float|null $balance_inicial_usdc Nuevo balance inicial. Si es null, usa el original.
     */
    public function reset($balance_inicial_usdc = null) {
        $nuevo_balance_inicial = ($balance_inicial_usdc !== null)
            ? (float) $balance_inicial_usdc
            : (float) $this->datos_portfolio->balance_inicial_usdc;

        $this->datos_portfolio->balance_usdc         = $nuevo_balance_inicial;
        $this->datos_portfolio->balance_sol           = 0;
        $this->datos_portfolio->balance_inicial_usdc  = $nuevo_balance_inicial;
        $this->datos_portfolio->fees_acumuladas_usd   = 0;
        $this->datos_portfolio->contador_swaps        = 0;

        $this->tokens             = array();
        $this->posiciones_lp      = array();
        $this->posiciones_farming = array();

        $this->guardar();
    }

    /**
     * Agrega una posición de liquidez (LP) al portfolio.
     *
     * @param string $pool_id             Identificador único del pool.
     * @param float  $lp_tokens           Cantidad de LP tokens recibidos.
     * @param float  $token_a_depositado  Cantidad del token A depositado.
     * @param float  $token_b_depositado  Cantidad del token B depositado.
     * @param float  $valor_usd           Valor total en USD de la posición al momento del depósito.
     */
    public function agregar_posicion_lp($pool_id, $lp_tokens, $token_a_depositado, $token_b_depositado, $valor_usd) {
        $nueva_posicion_lp = array(
            'pool_id'             => $pool_id,
            'lp_tokens'           => (float) $lp_tokens,
            'token_a_depositado'  => (float) $token_a_depositado,
            'token_b_depositado'  => (float) $token_b_depositado,
            'valor_usd'           => (float) $valor_usd,
            'fecha_entrada'       => current_time('mysql'),
        );

        $this->posiciones_lp[$pool_id] = $nueva_posicion_lp;
        $this->guardar();
    }

    /**
     * Retira una posición de liquidez (LP) del portfolio.
     *
     * @param string $pool_id Identificador del pool a retirar.
     * @return array|false Datos de la posición retirada, o false si no existe.
     */
    public function retirar_posicion_lp($pool_id) {
        if (!isset($this->posiciones_lp[$pool_id])) {
            return false;
        }

        $posicion_retirada = $this->posiciones_lp[$pool_id];
        unset($this->posiciones_lp[$pool_id]);
        $this->guardar();

        return $posicion_retirada;
    }

    /**
     * Obtiene la posición LP para un pool específico.
     *
     * @param string $pool_id Identificador del pool.
     * @return array|null Datos de la posición LP, o null si no existe.
     */
    public function obtener_posicion_lp($pool_id) {
        if (isset($this->posiciones_lp[$pool_id])) {
            return $this->posiciones_lp[$pool_id];
        }

        return null;
    }

    /**
     * Persiste todos los datos del portfolio en la base de datos.
     */
    private function guardar() {
        global $wpdb;

        $nombre_tabla        = $wpdb->prefix . 'flavor_dex_portfolio';
        $datos_a_actualizar  = array(
            'balance_usdc'            => $this->datos_portfolio->balance_usdc,
            'balance_sol'             => $this->datos_portfolio->balance_sol,
            'balance_inicial_usdc'    => $this->datos_portfolio->balance_inicial_usdc,
            'tokens_json'             => wp_json_encode($this->tokens),
            'lp_posiciones_json'      => wp_json_encode($this->posiciones_lp),
            'farming_posiciones_json'  => wp_json_encode($this->posiciones_farming),
            'fees_acumuladas_usd'     => $this->datos_portfolio->fees_acumuladas_usd,
            'contador_swaps'          => $this->datos_portfolio->contador_swaps,
            'modo_activo'             => $this->datos_portfolio->modo_activo,
            'wallet_address'          => $this->datos_portfolio->wallet_address,
        );

        $condicion_where = array(
            'usuario_id' => $this->usuario_id,
        );

        $wpdb->update($nombre_tabla, $datos_a_actualizar, $condicion_where);
    }
}
