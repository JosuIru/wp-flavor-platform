<?php
/**
 * Dashboard Tab para Trading IA
 *
 * Sistema de trading automatizado con inteligencia artificial.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trading_Ia_Dashboard_Tab {

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
        $tabs['trading-ia'] = [
            'label' => __('Trading IA', 'flavor-chat-ia'),
            'icon' => 'dashicons-chart-area',
            'callback' => [$this, 'render_tab'],
            'priority' => 86,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'dashboard';

        ?>
        <div class="flavor-trading-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=trading-ia&subtab=dashboard" class="subtab <?php echo $subtab === 'dashboard' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </a>
                <a href="?tab=trading-ia&subtab=estrategias" class="subtab <?php echo $subtab === 'estrategias' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> Estrategias
                </a>
                <a href="?tab=trading-ia&subtab=bots" class="subtab <?php echo $subtab === 'bots' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-controls-repeat"></span> Mis Bots
                </a>
                <a href="?tab=trading-ia&subtab=historial" class="subtab <?php echo $subtab === 'historial' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Historial
                </a>
                <a href="?tab=trading-ia&subtab=senales" class="subtab <?php echo $subtab === 'senales' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-megaphone"></span> Señales
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'estrategias':
                        $this->render_estrategias($datos);
                        break;
                    case 'bots':
                        $this->render_bots($datos);
                        break;
                    case 'historial':
                        $this->render_historial($datos);
                        break;
                    case 'senales':
                        $this->render_senales($datos);
                        break;
                    default:
                        $this->render_dashboard($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_dashboard($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Balance Total',
                'value' => '$' . number_format($datos['balance_total'], 2),
                'icon' => 'dashicons-chart-area',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'P&L Hoy',
                'value' => ($datos['pnl_hoy'] >= 0 ? '+' : '') . '$' . number_format($datos['pnl_hoy'], 2),
                'icon' => 'dashicons-chart-line',
                'color' => $datos['pnl_hoy'] >= 0 ? 'green' : 'red'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Bots Activos',
                'value' => $datos['bots_activos'],
                'icon' => 'dashicons-controls-repeat',
                'color' => 'purple'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Win Rate',
                'value' => $datos['win_rate'] . '%',
                'icon' => 'dashicons-yes-alt',
                'color' => 'yellow'
            ]);
            ?>
        </div>

        <div class="trading-grid">
            <!-- Rendimiento -->
            <div class="trading-card rendimiento">
                <h4>Rendimiento mensual</h4>
                <div class="rendimiento-grafico" id="grafico-rendimiento">
                    <!-- Aquí iría un gráfico con Chart.js -->
                    <div class="placeholder-grafico">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <p>Gráfico de rendimiento</p>
                    </div>
                </div>
                <div class="rendimiento-stats">
                    <div class="stat">
                        <span class="label">Este mes</span>
                        <span class="valor <?php echo $datos['rendimiento_mes'] >= 0 ? 'positivo' : 'negativo'; ?>">
                            <?php echo ($datos['rendimiento_mes'] >= 0 ? '+' : '') . number_format($datos['rendimiento_mes'], 2); ?>%
                        </span>
                    </div>
                    <div class="stat">
                        <span class="label">Último mes</span>
                        <span class="valor"><?php echo number_format($datos['rendimiento_mes_anterior'], 2); ?>%</span>
                    </div>
                    <div class="stat">
                        <span class="label">Año</span>
                        <span class="valor"><?php echo number_format($datos['rendimiento_anual'], 2); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Posiciones abiertas -->
            <div class="trading-card posiciones">
                <h4>Posiciones abiertas</h4>
                <?php if (empty($datos['posiciones'])): ?>
                    <p class="sin-posiciones">No hay posiciones abiertas</p>
                <?php else: ?>
                    <div class="posiciones-lista">
                        <?php foreach ($datos['posiciones'] as $pos): ?>
                            <div class="posicion-item <?php echo $pos['tipo']; ?>">
                                <div class="posicion-par">
                                    <strong><?php echo esc_html($pos['par']); ?></strong>
                                    <span class="tipo"><?php echo strtoupper($pos['tipo']); ?></span>
                                </div>
                                <div class="posicion-datos">
                                    <span>Entrada: $<?php echo number_format($pos['precio_entrada'], 2); ?></span>
                                    <span>Actual: $<?php echo number_format($pos['precio_actual'], 2); ?></span>
                                </div>
                                <div class="posicion-pnl <?php echo $pos['pnl'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($pos['pnl'] >= 0 ? '+' : '') . number_format($pos['pnl'], 2); ?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Últimas operaciones -->
            <div class="trading-card operaciones">
                <h4>Últimas operaciones</h4>
                <table class="flavor-table compact">
                    <thead>
                        <tr>
                            <th>Par</th>
                            <th>Tipo</th>
                            <th>P&L</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($datos['operaciones'], 0, 5) as $op): ?>
                            <tr>
                                <td><?php echo esc_html($op['par']); ?></td>
                                <td class="tipo-<?php echo $op['tipo']; ?>"><?php echo strtoupper($op['tipo']); ?></td>
                                <td class="<?php echo $op['pnl'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($op['pnl'] >= 0 ? '+' : '') . number_format($op['pnl'], 2); ?>%
                                </td>
                                <td><?php echo date_i18n('j M H:i', strtotime($op['fecha'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .trading-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 20px; }
            .trading-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .trading-card h4 { margin-bottom: 15px; }
            .placeholder-grafico { height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px; color: #999; }
            .placeholder-grafico .dashicons { font-size: 48px; width: 48px; height: 48px; }
            .rendimiento-stats { display: flex; justify-content: space-around; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
            .rendimiento-stats .stat { text-align: center; }
            .rendimiento-stats .label { display: block; font-size: 12px; color: #999; }
            .rendimiento-stats .valor { font-size: 18px; font-weight: 600; }
            .rendimiento-stats .valor.positivo { color: #4caf50; }
            .rendimiento-stats .valor.negativo { color: #f44336; }
            .posicion-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
            .posicion-item.long { border-left: 3px solid #4caf50; }
            .posicion-item.short { border-left: 3px solid #f44336; }
            .posicion-par .tipo { font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 8px; }
            .posicion-item.long .tipo { background: #e8f5e9; color: #4caf50; }
            .posicion-item.short .tipo { background: #ffebee; color: #f44336; }
            .posicion-datos { font-size: 13px; color: #666; }
            .posicion-pnl { font-weight: 700; }
            .posicion-pnl.positivo { color: #4caf50; }
            .posicion-pnl.negativo { color: #f44336; }
            .tipo-long { color: #4caf50; }
            .tipo-short { color: #f44336; }
            .positivo { color: #4caf50; }
            .negativo { color: #f44336; }
        </style>
        <?php
    }

    private function render_estrategias($datos) {
        ?>
        <div class="trading-estrategias">
            <h3>Estrategias de Trading</h3>

            <div class="estrategias-grid">
                <?php foreach ($datos['estrategias'] as $estrategia): ?>
                    <div class="estrategia-card">
                        <div class="estrategia-header">
                            <h4><?php echo esc_html($estrategia['nombre']); ?></h4>
                            <span class="badge badge-<?php echo $estrategia['riesgo']; ?>">
                                Riesgo: <?php echo ucfirst($estrategia['riesgo']); ?>
                            </span>
                        </div>
                        <p class="estrategia-desc"><?php echo esc_html($estrategia['descripcion']); ?></p>
                        <div class="estrategia-stats">
                            <div class="stat">
                                <span class="label">Win Rate</span>
                                <span class="valor"><?php echo $estrategia['win_rate']; ?>%</span>
                            </div>
                            <div class="stat">
                                <span class="label">Profit Factor</span>
                                <span class="valor"><?php echo $estrategia['profit_factor']; ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Max Drawdown</span>
                                <span class="valor"><?php echo $estrategia['max_drawdown']; ?>%</span>
                            </div>
                        </div>
                        <div class="estrategia-acciones">
                            <button class="flavor-btn flavor-btn-primary usar-estrategia" data-id="<?php echo $estrategia['id']; ?>">
                                Usar estrategia
                            </button>
                            <button class="flavor-btn ver-backtest" data-id="<?php echo $estrategia['id']; ?>">
                                Ver backtest
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .estrategias-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
            .estrategia-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .estrategia-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
            .estrategia-desc { color: #666; font-size: 14px; margin-bottom: 15px; }
            .estrategia-stats { display: flex; justify-content: space-between; padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 15px; }
            .estrategia-stats .stat { text-align: center; }
            .estrategia-stats .label { display: block; font-size: 11px; color: #999; }
            .estrategia-stats .valor { font-weight: 600; }
            .estrategia-acciones { display: flex; gap: 10px; }
            .badge-bajo { background: #e8f5e9; color: #4caf50; }
            .badge-medio { background: #fff3e0; color: #ff9800; }
            .badge-alto { background: #ffebee; color: #f44336; }
        </style>
        <?php
    }

    private function render_bots($datos) {
        ?>
        <div class="trading-bots">
            <div class="bots-header">
                <h3>Mis Bots de Trading</h3>
                <button class="flavor-btn flavor-btn-primary" id="btn-crear-bot">
                    <span class="dashicons dashicons-plus"></span> Crear bot
                </button>
            </div>

            <?php if (empty($datos['bots'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-controls-repeat"></span>
                    <p>No tienes bots configurados</p>
                    <button class="flavor-btn flavor-btn-primary" id="btn-crear-primer-bot">Crear mi primer bot</button>
                </div>
            <?php else: ?>
                <div class="bots-lista">
                    <?php foreach ($datos['bots'] as $bot): ?>
                        <div class="bot-card estado-<?php echo $bot['estado']; ?>">
                            <div class="bot-header">
                                <div class="bot-info">
                                    <h4><?php echo esc_html($bot['nombre']); ?></h4>
                                    <span class="bot-par"><?php echo esc_html($bot['par']); ?></span>
                                </div>
                                <div class="bot-estado">
                                    <span class="estado-indicador"></span>
                                    <?php echo ucfirst($bot['estado']); ?>
                                </div>
                            </div>
                            <div class="bot-stats">
                                <div class="stat">
                                    <span class="label">P&L Total</span>
                                    <span class="valor <?php echo $bot['pnl_total'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                        <?php echo ($bot['pnl_total'] >= 0 ? '+' : '') . number_format($bot['pnl_total'], 2); ?>%
                                    </span>
                                </div>
                                <div class="stat">
                                    <span class="label">Operaciones</span>
                                    <span class="valor"><?php echo $bot['operaciones']; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Win Rate</span>
                                    <span class="valor"><?php echo $bot['win_rate']; ?>%</span>
                                </div>
                            </div>
                            <div class="bot-acciones">
                                <?php if ($bot['estado'] === 'activo'): ?>
                                    <button class="flavor-btn flavor-btn-sm pausar-bot" data-id="<?php echo $bot['id']; ?>">Pausar</button>
                                <?php else: ?>
                                    <button class="flavor-btn flavor-btn-sm iniciar-bot" data-id="<?php echo $bot['id']; ?>">Iniciar</button>
                                <?php endif; ?>
                                <button class="flavor-btn flavor-btn-sm editar-bot" data-id="<?php echo $bot['id']; ?>">Editar</button>
                                <button class="flavor-btn flavor-btn-sm ver-logs" data-id="<?php echo $bot['id']; ?>">Logs</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .bots-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .bots-lista { display: grid; gap: 20px; }
            .bot-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .bot-card.estado-activo { border-left: 4px solid #4caf50; }
            .bot-card.estado-pausado { border-left: 4px solid #ff9800; }
            .bot-card.estado-error { border-left: 4px solid #f44336; }
            .bot-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
            .bot-par { font-size: 13px; color: #666; }
            .bot-estado { display: flex; align-items: center; gap: 8px; font-size: 13px; }
            .estado-indicador { width: 8px; height: 8px; border-radius: 50%; }
            .estado-activo .estado-indicador { background: #4caf50; animation: pulse 2s infinite; }
            .estado-pausado .estado-indicador { background: #ff9800; }
            .estado-error .estado-indicador { background: #f44336; }
            @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            .bot-stats { display: flex; gap: 30px; padding: 15px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 15px; }
            .bot-stats .stat .label { display: block; font-size: 12px; color: #999; }
            .bot-stats .stat .valor { font-size: 18px; font-weight: 600; }
            .bot-acciones { display: flex; gap: 10px; }
        </style>
        <?php
    }

    private function render_historial($datos) {
        ?>
        <div class="trading-historial">
            <h3>Historial de operaciones</h3>

            <div class="historial-filtros">
                <form method="get">
                    <input type="hidden" name="tab" value="trading-ia">
                    <input type="hidden" name="subtab" value="historial">
                    <select name="par">
                        <option value="">Todos los pares</option>
                        <option value="BTC/USDT">BTC/USDT</option>
                        <option value="ETH/USDT">ETH/USDT</option>
                        <option value="SOL/USDT">SOL/USDT</option>
                    </select>
                    <select name="resultado">
                        <option value="">Todos</option>
                        <option value="ganadora">Ganadoras</option>
                        <option value="perdedora">Perdedoras</option>
                    </select>
                    <input type="date" name="desde">
                    <input type="date" name="hasta">
                    <button type="submit" class="flavor-btn">Filtrar</button>
                </form>
            </div>

            <table class="flavor-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Par</th>
                        <th>Tipo</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Tamaño</th>
                        <th>P&L</th>
                        <th>Bot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos['operaciones'] as $op): ?>
                        <tr>
                            <td><?php echo date_i18n('j M Y H:i', strtotime($op['fecha'])); ?></td>
                            <td><?php echo esc_html($op['par']); ?></td>
                            <td class="tipo-<?php echo $op['tipo']; ?>"><?php echo strtoupper($op['tipo']); ?></td>
                            <td>$<?php echo number_format($op['precio_entrada'], 2); ?></td>
                            <td>$<?php echo number_format($op['precio_salida'], 2); ?></td>
                            <td><?php echo number_format($op['tamano'], 4); ?></td>
                            <td class="<?php echo $op['pnl'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                <?php echo ($op['pnl'] >= 0 ? '+' : '') . number_format($op['pnl'], 2); ?>%
                            </td>
                            <td><?php echo esc_html($op['bot_nombre']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_senales($datos) {
        ?>
        <div class="trading-senales">
            <h3>Señales de Trading</h3>

            <div class="senales-lista">
                <?php foreach ($datos['senales'] as $senal): ?>
                    <div class="senal-card tipo-<?php echo $senal['tipo']; ?>">
                        <div class="senal-header">
                            <span class="senal-par"><?php echo esc_html($senal['par']); ?></span>
                            <span class="senal-tipo"><?php echo strtoupper($senal['tipo']); ?></span>
                        </div>
                        <div class="senal-precios">
                            <div class="precio-item">
                                <span class="label">Entrada</span>
                                <span class="valor">$<?php echo number_format($senal['entrada'], 2); ?></span>
                            </div>
                            <div class="precio-item">
                                <span class="label">Take Profit</span>
                                <span class="valor positivo">$<?php echo number_format($senal['take_profit'], 2); ?></span>
                            </div>
                            <div class="precio-item">
                                <span class="label">Stop Loss</span>
                                <span class="valor negativo">$<?php echo number_format($senal['stop_loss'], 2); ?></span>
                            </div>
                        </div>
                        <div class="senal-meta">
                            <span class="confianza">Confianza: <?php echo $senal['confianza']; ?>%</span>
                            <span class="tiempo"><?php echo human_time_diff(strtotime($senal['fecha'])); ?></span>
                        </div>
                        <div class="senal-acciones">
                            <button class="flavor-btn flavor-btn-sm flavor-btn-primary ejecutar-senal" data-id="<?php echo $senal['id']; ?>">
                                Ejecutar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .senales-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
            .senal-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .senal-card.tipo-long { border-top: 4px solid #4caf50; }
            .senal-card.tipo-short { border-top: 4px solid #f44336; }
            .senal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
            .senal-par { font-size: 18px; font-weight: 700; }
            .senal-tipo { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
            .tipo-long .senal-tipo { background: #e8f5e9; color: #4caf50; }
            .tipo-short .senal-tipo { background: #ffebee; color: #f44336; }
            .senal-precios { display: flex; justify-content: space-between; margin-bottom: 15px; }
            .precio-item { text-align: center; }
            .precio-item .label { display: block; font-size: 11px; color: #999; }
            .precio-item .valor { font-weight: 600; }
            .senal-meta { display: flex; justify-content: space-between; font-size: 13px; color: #666; padding: 10px 0; border-top: 1px solid #eee; margin-bottom: 15px; }
        </style>
        <?php
    }

    private function obtener_datos_usuario() {
        // Datos de demostración
        return [
            'balance_total' => 10500.00,
            'pnl_hoy' => 125.50,
            'bots_activos' => 3,
            'win_rate' => 68,
            'rendimiento_mes' => 8.5,
            'rendimiento_mes_anterior' => 5.2,
            'rendimiento_anual' => 45.3,
            'posiciones' => [
                ['par' => 'BTC/USDT', 'tipo' => 'long', 'precio_entrada' => 65000, 'precio_actual' => 67500, 'pnl' => 3.85],
                ['par' => 'ETH/USDT', 'tipo' => 'short', 'precio_entrada' => 3500, 'precio_actual' => 3400, 'pnl' => 2.86],
            ],
            'operaciones' => [
                ['par' => 'SOL/USDT', 'tipo' => 'long', 'pnl' => 5.2, 'fecha' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'precio_entrada' => 145, 'precio_salida' => 152.5, 'tamano' => 10, 'bot_nombre' => 'Bot Momentum'],
                ['par' => 'BTC/USDT', 'tipo' => 'long', 'pnl' => -1.5, 'fecha' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'precio_entrada' => 66000, 'precio_salida' => 65010, 'tamano' => 0.1, 'bot_nombre' => 'Bot Scalper'],
                ['par' => 'ETH/USDT', 'tipo' => 'short', 'pnl' => 3.8, 'fecha' => date('Y-m-d H:i:s', strtotime('-1 day')), 'precio_entrada' => 3600, 'precio_salida' => 3463.2, 'tamano' => 2, 'bot_nombre' => 'Bot Swing'],
            ],
            'estrategias' => [
                ['id' => 1, 'nombre' => 'Momentum Scalping', 'descripcion' => 'Estrategia de scalping basada en momentum y volumen', 'riesgo' => 'alto', 'win_rate' => 62, 'profit_factor' => 1.8, 'max_drawdown' => 15],
                ['id' => 2, 'nombre' => 'Mean Reversion', 'descripcion' => 'Aprovecha las reversiones a la media en rangos', 'riesgo' => 'medio', 'win_rate' => 71, 'profit_factor' => 2.1, 'max_drawdown' => 8],
                ['id' => 3, 'nombre' => 'Trend Following', 'descripcion' => 'Sigue tendencias con gestión de riesgo conservadora', 'riesgo' => 'bajo', 'win_rate' => 45, 'profit_factor' => 2.5, 'max_drawdown' => 5],
            ],
            'bots' => [
                ['id' => 1, 'nombre' => 'Bot Momentum', 'par' => 'BTC/USDT', 'estado' => 'activo', 'pnl_total' => 12.5, 'operaciones' => 45, 'win_rate' => 65],
                ['id' => 2, 'nombre' => 'Bot Scalper', 'par' => 'ETH/USDT', 'estado' => 'activo', 'pnl_total' => -2.1, 'operaciones' => 120, 'win_rate' => 58],
                ['id' => 3, 'nombre' => 'Bot Swing', 'par' => 'SOL/USDT', 'estado' => 'pausado', 'pnl_total' => 8.3, 'operaciones' => 12, 'win_rate' => 75],
            ],
            'senales' => [
                ['id' => 1, 'par' => 'BTC/USDT', 'tipo' => 'long', 'entrada' => 66500, 'take_profit' => 69000, 'stop_loss' => 65000, 'confianza' => 85, 'fecha' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
                ['id' => 2, 'par' => 'ETH/USDT', 'tipo' => 'short', 'entrada' => 3450, 'take_profit' => 3200, 'stop_loss' => 3550, 'confianza' => 72, 'fecha' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ],
        ];
    }
}

Flavor_Trading_Ia_Dashboard_Tab::get_instance();
