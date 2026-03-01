<?php
/**
 * Dashboard Tab para Publicidad
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Advertising_Dashboard_Tab {

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
        // Solo mostrar a anunciantes o admins
        if (!current_user_can('edit_posts') && !$this->es_anunciante()) {
            return $tabs;
        }

        $tabs['publicidad'] = [
            'label' => __('Publicidad', 'flavor-chat-ia'),
            'icon' => 'dashicons-megaphone',
            'callback' => [$this, 'render_tab'],
            'priority' => 75,
        ];
        return $tabs;
    }

    private function es_anunciante() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_anuncios';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM $tabla WHERE anunciante_id = %d LIMIT 1",
            get_current_user_id()
        ));
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'mis-anuncios';

        ?>
        <div class="flavor-publicidad-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=publicidad&subtab=mis-anuncios" class="subtab <?php echo $subtab === 'mis-anuncios' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-megaphone"></span> Mis Anuncios
                </a>
                <a href="?tab=publicidad&subtab=crear" class="subtab <?php echo $subtab === 'crear' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> Crear Anuncio
                </a>
                <a href="?tab=publicidad&subtab=estadisticas" class="subtab <?php echo $subtab === 'estadisticas' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Estadísticas
                </a>
                <a href="?tab=publicidad&subtab=facturacion" class="subtab <?php echo $subtab === 'facturacion' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Facturación
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'crear':
                        $this->render_crear_anuncio();
                        break;
                    case 'estadisticas':
                        $this->render_estadisticas($datos);
                        break;
                    case 'facturacion':
                        $this->render_facturacion($datos);
                        break;
                    default:
                        $this->render_mis_anuncios($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_mis_anuncios($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Anuncios Activos',
                'value' => $datos['anuncios_activos'],
                'icon' => 'dashicons-megaphone',
                'color' => 'green'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Impresiones Hoy',
                'value' => number_format($datos['impresiones_hoy']),
                'icon' => 'dashicons-visibility',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Clicks Hoy',
                'value' => $datos['clicks_hoy'],
                'icon' => 'dashicons-admin-links',
                'color' => 'purple'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'CTR',
                'value' => $datos['ctr'] . '%',
                'icon' => 'dashicons-chart-line',
                'color' => 'yellow'
            ]);
            ?>
        </div>

        <!-- Lista de anuncios -->
        <div class="anuncios-lista">
            <?php if (empty($datos['anuncios'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p>No tienes anuncios creados</p>
                    <a href="?tab=publicidad&subtab=crear" class="flavor-btn flavor-btn-primary">Crear primer anuncio</a>
                </div>
            <?php else: ?>
                <?php foreach ($datos['anuncios'] as $anuncio): ?>
                    <div class="anuncio-card">
                        <div class="anuncio-preview">
                            <?php if ($anuncio->imagen): ?>
                                <img src="<?php echo esc_url($anuncio->imagen); ?>" alt="">
                            <?php else: ?>
                                <div class="anuncio-texto-preview">
                                    <strong><?php echo esc_html($anuncio->titulo); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="anuncio-info">
                            <h4><?php echo esc_html($anuncio->titulo); ?></h4>
                            <p><?php echo esc_html(wp_trim_words($anuncio->descripcion, 15)); ?></p>
                            <div class="anuncio-meta">
                                <span class="badge badge-<?php echo $anuncio->estado; ?>">
                                    <?php echo ucfirst($anuncio->estado); ?>
                                </span>
                                <span class="tipo"><?php echo ucfirst($anuncio->tipo); ?></span>
                                <span class="ubicacion"><?php echo ucfirst($anuncio->ubicacion); ?></span>
                            </div>
                            <div class="anuncio-stats">
                                <span><span class="dashicons dashicons-visibility"></span> <?php echo number_format($anuncio->impresiones); ?></span>
                                <span><span class="dashicons dashicons-admin-links"></span> <?php echo number_format($anuncio->clicks); ?></span>
                                <span>CTR: <?php echo $anuncio->impresiones > 0 ? round(($anuncio->clicks / $anuncio->impresiones) * 100, 2) : 0; ?>%</span>
                            </div>
                        </div>
                        <div class="anuncio-acciones">
                            <?php if ($anuncio->estado === 'activo'): ?>
                                <button class="flavor-btn flavor-btn-sm pausar-anuncio" data-id="<?php echo $anuncio->id; ?>">Pausar</button>
                            <?php elseif ($anuncio->estado === 'pausado'): ?>
                                <button class="flavor-btn flavor-btn-sm activar-anuncio" data-id="<?php echo $anuncio->id; ?>">Activar</button>
                            <?php endif; ?>
                            <a href="?tab=publicidad&subtab=editar&id=<?php echo $anuncio->id; ?>" class="flavor-btn flavor-btn-sm">Editar</a>
                            <a href="?tab=publicidad&subtab=stats&id=<?php echo $anuncio->id; ?>" class="flavor-btn flavor-btn-sm">Ver Stats</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_crear_anuncio() {
        ?>
        <div class="crear-anuncio">
            <h3>Crear nuevo anuncio</h3>

            <form id="form-crear-anuncio" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_crear_anuncio', 'anuncio_nonce'); ?>

                <!-- Tipo de anuncio -->
                <div class="form-group">
                    <label>Tipo de anuncio</label>
                    <div class="tipos-anuncio">
                        <label class="tipo-opcion">
                            <input type="radio" name="tipo" value="banner" checked>
                            <div class="tipo-card">
                                <span class="dashicons dashicons-format-image"></span>
                                <strong>Banner</strong>
                                <small>Imagen + texto</small>
                            </div>
                        </label>
                        <label class="tipo-opcion">
                            <input type="radio" name="tipo" value="texto">
                            <div class="tipo-card">
                                <span class="dashicons dashicons-text"></span>
                                <strong>Solo texto</strong>
                                <small>Título + descripción</small>
                            </div>
                        </label>
                        <label class="tipo-opcion">
                            <input type="radio" name="tipo" value="nativo">
                            <div class="tipo-card">
                                <span class="dashicons dashicons-admin-post"></span>
                                <strong>Nativo</strong>
                                <small>Integrado en feed</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="titulo" required maxlength="60">
                        <small>Máximo 60 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>URL de destino *</label>
                        <input type="url" name="url" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" maxlength="150"></textarea>
                    <small>Máximo 150 caracteres</small>
                </div>

                <div class="form-group" id="grupo-imagen">
                    <label>Imagen del anuncio</label>
                    <input type="file" name="imagen" accept="image/*">
                    <small>Tamaño recomendado: 1200x628px</small>
                </div>

                <!-- Ubicación -->
                <div class="form-group">
                    <label>Ubicación</label>
                    <select name="ubicacion">
                        <option value="sidebar">Sidebar</option>
                        <option value="header">Cabecera</option>
                        <option value="feed">Feed principal</option>
                        <option value="footer">Pie de página</option>
                        <option value="inline">Dentro de contenido</option>
                    </select>
                </div>

                <!-- Segmentación -->
                <h4>Segmentación</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Comunidades</label>
                        <select name="comunidades[]" multiple>
                            <option value="todas">Todas</option>
                            <!-- Cargar comunidades dinámicamente -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Módulos donde mostrar</label>
                        <select name="modulos[]" multiple>
                            <option value="marketplace">Marketplace</option>
                            <option value="eventos">Eventos</option>
                            <option value="foros">Foros</option>
                            <option value="red-social">Red Social</option>
                        </select>
                    </div>
                </div>

                <!-- Presupuesto -->
                <h4>Presupuesto y duración</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Presupuesto diario (€)</label>
                        <input type="number" name="presupuesto_diario" min="1" step="0.5" value="5">
                    </div>
                    <div class="form-group">
                        <label>Fecha inicio</label>
                        <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha fin</label>
                        <input type="date" name="fecha_fin">
                        <small>Dejar vacío para indefinido</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="accion" value="borrador" class="flavor-btn">Guardar borrador</button>
                    <button type="submit" name="accion" value="publicar" class="flavor-btn flavor-btn-primary">Publicar anuncio</button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_estadisticas($datos) {
        ?>
        <div class="publicidad-estadisticas">
            <h3>Estadísticas globales</h3>

            <!-- Métricas generales -->
            <div class="stats-resumen flavor-card">
                <div class="stats-periodo">
                    <select id="periodo-stats">
                        <option value="7">Últimos 7 días</option>
                        <option value="30" selected>Últimos 30 días</option>
                        <option value="90">Últimos 90 días</option>
                    </select>
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-valor"><?php echo number_format($datos['total_impresiones']); ?></span>
                        <span class="stat-label">Impresiones</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-valor"><?php echo number_format($datos['total_clicks']); ?></span>
                        <span class="stat-label">Clicks</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-valor"><?php echo $datos['ctr_global']; ?>%</span>
                        <span class="stat-label">CTR medio</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-valor"><?php echo number_format($datos['gasto_total'], 2); ?>€</span>
                        <span class="stat-label">Gasto total</span>
                    </div>
                </div>
            </div>

            <!-- Por anuncio -->
            <div class="stats-por-anuncio flavor-card">
                <h4>Rendimiento por anuncio</h4>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Anuncio</th>
                            <th>Impresiones</th>
                            <th>Clicks</th>
                            <th>CTR</th>
                            <th>Gasto</th>
                            <th>CPC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['stats_anuncios'] as $stat): ?>
                            <tr>
                                <td><?php echo esc_html($stat->titulo); ?></td>
                                <td><?php echo number_format($stat->impresiones); ?></td>
                                <td><?php echo number_format($stat->clicks); ?></td>
                                <td><?php echo $stat->impresiones > 0 ? round(($stat->clicks / $stat->impresiones) * 100, 2) : 0; ?>%</td>
                                <td><?php echo number_format($stat->gasto, 2); ?>€</td>
                                <td><?php echo $stat->clicks > 0 ? number_format($stat->gasto / $stat->clicks, 2) : '-'; ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function render_facturacion($datos) {
        ?>
        <div class="publicidad-facturacion">
            <h3>Facturación</h3>

            <!-- Saldo actual -->
            <div class="saldo-actual flavor-card">
                <h4>Tu saldo</h4>
                <div class="saldo-valor"><?php echo number_format($datos['saldo'], 2); ?>€</div>
                <button class="flavor-btn flavor-btn-primary" id="btn-recargar">Recargar saldo</button>
            </div>

            <!-- Historial de pagos -->
            <div class="historial-pagos flavor-card">
                <h4>Movimientos</h4>
                <?php if (empty($datos['movimientos'])): ?>
                    <p class="sin-movimientos">No hay movimientos</p>
                <?php else: ?>
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Importe</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos['movimientos'] as $mov): ?>
                                <tr class="movimiento-<?php echo $mov->tipo; ?>">
                                    <td><?php echo date_i18n('j M Y', strtotime($mov->fecha)); ?></td>
                                    <td><?php echo esc_html($mov->concepto); ?></td>
                                    <td class="<?php echo $mov->importe >= 0 ? 'positivo' : 'negativo'; ?>">
                                        <?php echo ($mov->importe >= 0 ? '+' : '') . number_format($mov->importe, 2); ?>€
                                    </td>
                                    <td><?php echo number_format($mov->saldo_despues, 2); ?>€</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_anuncios = $wpdb->prefix . 'flavor_anuncios';
        $tabla_stats = $wpdb->prefix . 'flavor_anuncios_stats';
        $tabla_movimientos = $wpdb->prefix . 'flavor_anuncios_movimientos';

        // Anuncios del usuario
        $anuncios = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*,
                    COALESCE((SELECT SUM(impresiones) FROM $tabla_stats WHERE anuncio_id = a.id), 0) as impresiones,
                    COALESCE((SELECT SUM(clicks) FROM $tabla_stats WHERE anuncio_id = a.id), 0) as clicks
             FROM $tabla_anuncios a
             WHERE a.anunciante_id = %d
             ORDER BY a.created_at DESC",
            $user_id
        ));

        // Estadísticas
        $anuncios_activos = 0;
        $impresiones_hoy = 0;
        $clicks_hoy = 0;
        foreach ($anuncios as $a) {
            if ($a->estado === 'activo') $anuncios_activos++;
        }

        $stats_hoy = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(impresiones) as impresiones, SUM(clicks) as clicks
             FROM $tabla_stats s
             JOIN $tabla_anuncios a ON s.anuncio_id = a.id
             WHERE a.anunciante_id = %d AND s.fecha = CURDATE()",
            $user_id
        ));
        $impresiones_hoy = $stats_hoy->impresiones ?? 0;
        $clicks_hoy = $stats_hoy->clicks ?? 0;
        $ctr = $impresiones_hoy > 0 ? round(($clicks_hoy / $impresiones_hoy) * 100, 2) : 0;

        // Stats globales
        $stats_global = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(impresiones) as impresiones, SUM(clicks) as clicks, SUM(gasto) as gasto
             FROM $tabla_stats s
             JOIN $tabla_anuncios a ON s.anuncio_id = a.id
             WHERE a.anunciante_id = %d",
            $user_id
        ));

        // Stats por anuncio
        $stats_anuncios = $wpdb->get_results($wpdb->prepare(
            "SELECT a.titulo, SUM(s.impresiones) as impresiones, SUM(s.clicks) as clicks, SUM(s.gasto) as gasto
             FROM $tabla_anuncios a
             LEFT JOIN $tabla_stats s ON a.id = s.anuncio_id
             WHERE a.anunciante_id = %d
             GROUP BY a.id
             ORDER BY impresiones DESC",
            $user_id
        ));

        // Saldo
        $saldo = $wpdb->get_var($wpdb->prepare(
            "SELECT saldo FROM {$wpdb->prefix}flavor_anunciantes WHERE usuario_id = %d",
            $user_id
        )) ?: 0;

        // Movimientos
        $movimientos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_movimientos WHERE usuario_id = %d ORDER BY fecha DESC LIMIT 20",
            $user_id
        ));

        return [
            'anuncios' => $anuncios ?: [],
            'anuncios_activos' => $anuncios_activos,
            'impresiones_hoy' => $impresiones_hoy,
            'clicks_hoy' => $clicks_hoy,
            'ctr' => $ctr,
            'total_impresiones' => $stats_global->impresiones ?? 0,
            'total_clicks' => $stats_global->clicks ?? 0,
            'ctr_global' => ($stats_global->impresiones ?? 0) > 0 ? round((($stats_global->clicks ?? 0) / $stats_global->impresiones) * 100, 2) : 0,
            'gasto_total' => $stats_global->gasto ?? 0,
            'stats_anuncios' => $stats_anuncios ?: [],
            'saldo' => $saldo,
            'movimientos' => $movimientos ?: [],
        ];
    }
}

Flavor_Advertising_Dashboard_Tab::get_instance();
