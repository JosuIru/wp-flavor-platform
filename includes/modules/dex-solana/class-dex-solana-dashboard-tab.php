<?php
/**
 * Dashboard Tab para DEX Solana
 *
 * Integración con exchange descentralizado en Solana.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Dex_Solana_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['dex-solana'] = [
            'label' => __('DEX Solana', 'flavor-platform'),
            'icon' => 'dashicons-randomize',
            'callback' => [$this, 'render_tab'],
            'priority' => 85,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'wallet';

        ?>
        <div class="flavor-dex-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=dex-solana&subtab=wallet" class="subtab <?php echo $subtab === 'wallet' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-portfolio"></span> Wallet
                </a>
                <a href="?tab=dex-solana&subtab=swap" class="subtab <?php echo $subtab === 'swap' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-randomize"></span> Swap
                </a>
                <a href="?tab=dex-solana&subtab=historial" class="subtab <?php echo $subtab === 'historial' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Historial
                </a>
                <a href="?tab=dex-solana&subtab=staking" class="subtab <?php echo $subtab === 'staking' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-pie"></span> Staking
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'swap':
                        $this->render_swap($datos);
                        break;
                    case 'historial':
                        $this->render_historial($datos);
                        break;
                    case 'staking':
                        $this->render_staking($datos);
                        break;
                    default:
                        $this->render_wallet($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_wallet($datos) {
        ?>
        <div class="dex-wallet">
            <!-- Conectar wallet -->
            <?php if (empty($datos['wallet_conectada'])): ?>
                <div class="conectar-wallet">
                    <h3>Conecta tu wallet</h3>
                    <p>Conecta tu wallet de Solana para empezar a operar en el DEX.</p>
                    <div class="wallet-opciones">
                        <button class="wallet-btn" data-wallet="phantom">
                            <img src="<?php echo FLAVOR_CHAT_IA_URL; ?>assets/images/phantom.svg" alt="Phantom">
                            Phantom
                        </button>
                        <button class="wallet-btn" data-wallet="solflare">
                            <img src="<?php echo FLAVOR_CHAT_IA_URL; ?>assets/images/solflare.svg" alt="Solflare">
                            Solflare
                        </button>
                        <button class="wallet-btn" data-wallet="backpack">
                            <img src="<?php echo FLAVOR_CHAT_IA_URL; ?>assets/images/backpack.svg" alt="Backpack">
                            Backpack
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Wallet conectada -->
                <div class="wallet-info">
                    <div class="wallet-header">
                        <div class="wallet-address">
                            <span class="label">Wallet conectada</span>
                            <code><?php echo esc_html($this->truncar_address($datos['wallet_conectada'])); ?></code>
                            <button class="copiar-address" title="Copiar">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <button class="flavor-btn flavor-btn-sm desconectar-wallet">Desconectar</button>
                    </div>

                    <!-- Balance total -->
                    <div class="wallet-balance-total">
                        <span class="label">Balance total</span>
                        <div class="balance-valor">
                            <span class="valor-usd">$<?php echo number_format($datos['balance_total_usd'], 2); ?></span>
                            <span class="cambio <?php echo $datos['cambio_24h'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                <?php echo ($datos['cambio_24h'] >= 0 ? '+' : '') . number_format($datos['cambio_24h'], 2); ?>%
                            </span>
                        </div>
                    </div>

                    <!-- Lista de tokens -->
                    <div class="wallet-tokens">
                        <h4>Mis tokens</h4>
                        <?php if (empty($datos['tokens'])): ?>
                            <p class="sin-tokens">No tienes tokens en esta wallet</p>
                        <?php else: ?>
                            <table class="flavor-table">
                                <thead>
                                    <tr>
                                        <th>Token</th>
                                        <th>Balance</th>
                                        <th>Precio</th>
                                        <th>Valor</th>
                                        <th>24h</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($datos['tokens'] as $token): ?>
                                        <tr>
                                            <td>
                                                <div class="token-info">
                                                    <?php if ($token['logo']): ?>
                                                        <img src="<?php echo esc_url($token['logo']); ?>" alt="" width="24">
                                                    <?php endif; ?>
                                                    <span><?php echo esc_html($token['symbol']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($token['balance'], 4); ?></td>
                                            <td>$<?php echo number_format($token['precio'], 4); ?></td>
                                            <td><strong>$<?php echo number_format($token['valor_usd'], 2); ?></strong></td>
                                            <td class="<?php echo $token['cambio_24h'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                                <?php echo ($token['cambio_24h'] >= 0 ? '+' : '') . number_format($token['cambio_24h'], 2); ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .conectar-wallet { text-align: center; padding: 60px 20px; }
            .wallet-opciones { display: flex; justify-content: center; gap: 20px; margin-top: 30px; }
            .wallet-btn { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px 30px; border: 2px solid #ddd; border-radius: 12px; background: #fff; cursor: pointer; transition: all 0.2s; }
            .wallet-btn:hover { border-color: #667eea; transform: translateY(-2px); }
            .wallet-btn img { width: 48px; height: 48px; }
            .wallet-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
            .wallet-address { display: flex; align-items: center; gap: 10px; }
            .wallet-address code { background: #f5f5f5; padding: 8px 15px; border-radius: 8px; font-size: 14px; }
            .wallet-balance-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
            .balance-valor { display: flex; align-items: baseline; gap: 15px; margin-top: 10px; }
            .valor-usd { font-size: 36px; font-weight: 700; }
            .cambio { font-size: 16px; padding: 4px 10px; border-radius: 20px; }
            .cambio.positivo { background: rgba(76, 175, 80, 0.3); }
            .cambio.negativo { background: rgba(244, 67, 54, 0.3); }
            .token-info { display: flex; align-items: center; gap: 8px; }
            td.positivo { color: #4caf50; }
            td.negativo { color: #f44336; }
        </style>
        <?php
    }

    private function render_swap($datos) {
        ?>
        <div class="dex-swap">
            <div class="swap-card">
                <h3>Intercambiar tokens</h3>

                <div class="swap-form">
                    <!-- Token origen -->
                    <div class="swap-input">
                        <label>Envías</label>
                        <div class="input-grupo">
                            <input type="number" id="swap-desde-cantidad" placeholder="0.00" step="any">
                            <select id="swap-desde-token">
                                <option value="SOL">SOL</option>
                                <option value="USDC">USDC</option>
                                <option value="USDT">USDT</option>
                            </select>
                        </div>
                        <span class="balance-disponible">Balance: <span id="balance-desde">0</span></span>
                    </div>

                    <!-- Botón intercambiar -->
                    <div class="swap-toggle">
                        <button type="button" id="btn-swap-toggle">
                            <span class="dashicons dashicons-arrow-down-alt"></span>
                        </button>
                    </div>

                    <!-- Token destino -->
                    <div class="swap-input">
                        <label>Recibes</label>
                        <div class="input-grupo">
                            <input type="number" id="swap-hacia-cantidad" placeholder="0.00" readonly>
                            <select id="swap-hacia-token">
                                <option value="USDC">USDC</option>
                                <option value="SOL">SOL</option>
                                <option value="USDT">USDT</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info del swap -->
                    <div class="swap-info" style="display: none;">
                        <div class="info-row">
                            <span>Precio</span>
                            <span id="swap-precio">-</span>
                        </div>
                        <div class="info-row">
                            <span>Impacto en precio</span>
                            <span id="swap-impacto">-</span>
                        </div>
                        <div class="info-row">
                            <span>Fee de red</span>
                            <span id="swap-fee">~0.000005 SOL</span>
                        </div>
                    </div>

                    <button type="button" id="btn-ejecutar-swap" class="flavor-btn flavor-btn-primary flavor-btn-block">
                        Conectar wallet para intercambiar
                    </button>
                </div>
            </div>
        </div>

        <style>
            .dex-swap { max-width: 450px; margin: 0 auto; }
            .swap-card { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .swap-input { background: #f8f9fa; border-radius: 12px; padding: 15px; margin-bottom: 10px; }
            .swap-input label { display: block; font-size: 13px; color: #666; margin-bottom: 8px; }
            .input-grupo { display: flex; gap: 10px; }
            .input-grupo input { flex: 1; font-size: 24px; border: none; background: transparent; }
            .input-grupo select { font-size: 16px; font-weight: 600; border: none; background: #e9ecef; padding: 8px 15px; border-radius: 8px; }
            .balance-disponible { font-size: 12px; color: #999; margin-top: 8px; display: block; }
            .swap-toggle { text-align: center; margin: -5px 0; position: relative; z-index: 1; }
            .swap-toggle button { width: 40px; height: 40px; border-radius: 50%; border: 4px solid #fff; background: #667eea; color: #fff; cursor: pointer; }
            .swap-info { background: #f8f9fa; border-radius: 12px; padding: 15px; margin: 15px 0; }
            .info-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; }
            .info-row:last-child { margin-bottom: 0; }
            .flavor-btn-block { width: 100%; padding: 15px; font-size: 16px; }
        </style>
        <?php
    }

    private function render_historial($datos) {
        ?>
        <div class="dex-historial">
            <h3>Historial de transacciones</h3>

            <?php if (empty($datos['transacciones'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-list-view"></span>
                    <p>No hay transacciones registradas</p>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Tokens</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>TX</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['transacciones'] as $tx): ?>
                            <tr>
                                <td>
                                    <span class="tipo-<?php echo $tx->tipo; ?>">
                                        <?php echo ucfirst($tx->tipo); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($tx->token_desde . ' → ' . $tx->token_hacia); ?></td>
                                <td><?php echo number_format($tx->cantidad_desde, 4); ?> → <?php echo number_format($tx->cantidad_hacia, 4); ?></td>
                                <td>$<?php echo number_format($tx->precio_usd, 2); ?></td>
                                <td><?php echo date_i18n('j M H:i', strtotime($tx->created_at)); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $tx->estado; ?>">
                                        <?php echo ucfirst($tx->estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="https://solscan.io/tx/<?php echo esc_attr($tx->tx_hash); ?>" target="_blank" title="Ver en Solscan">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_staking($datos) {
        ?>
        <div class="dex-staking">
            <h3>Staking</h3>

            <div class="staking-resumen">
                <div class="staking-card">
                    <span class="label">Total en staking</span>
                    <span class="valor">$<?php echo number_format($datos['staking_total'], 2); ?></span>
                </div>
                <div class="staking-card">
                    <span class="label">Recompensas acumuladas</span>
                    <span class="valor">$<?php echo number_format($datos['recompensas_acumuladas'], 2); ?></span>
                </div>
                <div class="staking-card">
                    <span class="label">APY promedio</span>
                    <span class="valor"><?php echo number_format($datos['apy_promedio'], 2); ?>%</span>
                </div>
            </div>

            <div class="pools-staking">
                <h4>Pools disponibles</h4>
                <div class="pools-grid">
                    <?php foreach ($datos['pools'] as $pool): ?>
                        <div class="pool-card">
                            <div class="pool-header">
                                <span class="pool-nombre"><?php echo esc_html($pool['nombre']); ?></span>
                                <span class="pool-apy"><?php echo number_format($pool['apy'], 2); ?>% APY</span>
                            </div>
                            <div class="pool-stats">
                                <div>
                                    <span class="label">TVL</span>
                                    <span>$<?php echo number_format($pool['tvl'], 0); ?></span>
                                </div>
                                <div>
                                    <span class="label">Mi stake</span>
                                    <span>$<?php echo number_format($pool['mi_stake'], 2); ?></span>
                                </div>
                            </div>
                            <button class="flavor-btn flavor-btn-sm flavor-btn-primary" data-pool="<?php echo esc_attr($pool['id']); ?>">
                                Stake
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <style>
            .staking-resumen { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
            .staking-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px; border-radius: 12px; text-align: center; }
            .staking-card .label { display: block; font-size: 13px; opacity: 0.8; margin-bottom: 5px; }
            .staking-card .valor { font-size: 24px; font-weight: 700; }
            .pools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
            .pool-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .pool-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .pool-nombre { font-weight: 600; }
            .pool-apy { color: #4caf50; font-weight: 700; }
            .pool-stats { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
            .pool-stats .label { display: block; color: #999; font-size: 12px; }
        </style>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();

        // Wallet conectada
        $wallet_conectada = get_user_meta($user_id, 'solana_wallet_address', true);

        // Datos simulados para demo
        return [
            'wallet_conectada' => $wallet_conectada,
            'balance_total_usd' => 1234.56,
            'cambio_24h' => 2.45,
            'tokens' => [
                ['symbol' => 'SOL', 'balance' => 5.234, 'precio' => 150.00, 'valor_usd' => 785.10, 'cambio_24h' => 3.2, 'logo' => ''],
                ['symbol' => 'USDC', 'balance' => 449.46, 'precio' => 1.00, 'valor_usd' => 449.46, 'cambio_24h' => 0.01, 'logo' => ''],
            ],
            'transacciones' => [],
            'staking_total' => 500.00,
            'recompensas_acumuladas' => 25.50,
            'apy_promedio' => 8.5,
            'pools' => [
                ['id' => 'sol-usdc', 'nombre' => 'SOL-USDC', 'apy' => 12.5, 'tvl' => 5000000, 'mi_stake' => 250],
                ['id' => 'sol-usdt', 'nombre' => 'SOL-USDT', 'apy' => 10.2, 'tvl' => 3000000, 'mi_stake' => 0],
            ],
        ];
    }

    private function truncar_address($address) {
        if (strlen($address) <= 12) return $address;
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
}

Flavor_Dex_Solana_Dashboard_Tab::get_instance();
