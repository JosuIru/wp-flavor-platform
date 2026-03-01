<?php
/**
 * Dashboard Tab para Clientes (CRM)
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Clientes_Dashboard_Tab {

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
        // Solo mostrar a usuarios con permisos
        if (!current_user_can('edit_posts')) {
            return $tabs;
        }

        $tabs['clientes'] = [
            'label' => __('CRM', 'flavor-chat-ia'),
            'icon' => 'dashicons-businessman',
            'callback' => [$this, 'render_tab'],
            'priority' => 60,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'lista';
        $cliente_id = isset($_GET['cliente_id']) ? absint($_GET['cliente_id']) : null;

        ?>
        <div class="flavor-clientes-dashboard">
            <!-- Navegación interna -->
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=clientes&subtab=lista" class="subtab <?php echo $subtab === 'lista' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Clientes
                </a>
                <a href="?tab=clientes&subtab=nuevo" class="subtab <?php echo $subtab === 'nuevo' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> Nuevo Cliente
                </a>
                <a href="?tab=clientes&subtab=actividad" class="subtab <?php echo $subtab === 'actividad' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clock"></span> Actividad
                </a>
                <a href="?tab=clientes&subtab=estadisticas" class="subtab <?php echo $subtab === 'estadisticas' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Estadísticas
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                if ($cliente_id && $subtab === 'detalle') {
                    $this->render_detalle_cliente($cliente_id);
                } else {
                    switch ($subtab) {
                        case 'nuevo':
                            $this->render_nuevo_cliente();
                            break;
                        case 'actividad':
                            $this->render_actividad($datos);
                            break;
                        case 'estadisticas':
                            $this->render_estadisticas($datos);
                            break;
                        default:
                            $this->render_lista_clientes($datos);
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_lista_clientes($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Total Clientes',
                'value' => $datos['total_clientes'],
                'icon' => 'dashicons-groups',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Activos',
                'value' => $datos['clientes_activos'],
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Potenciales',
                'value' => $datos['clientes_potenciales'],
                'icon' => 'dashicons-star-empty',
                'color' => 'yellow'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Valor Estimado',
                'value' => number_format($datos['valor_total'], 0, ',', '.') . '€',
                'icon' => 'dashicons-money-alt',
                'color' => 'purple'
            ]);
            ?>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="crm-filtros">
            <form method="get" class="form-filtros">
                <input type="hidden" name="tab" value="clientes">
                <input type="text" name="buscar" placeholder="Buscar cliente..."
                       value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php selected($_GET['estado'] ?? '', 'activo'); ?>>Activos</option>
                    <option value="potencial" <?php selected($_GET['estado'] ?? '', 'potencial'); ?>>Potenciales</option>
                    <option value="inactivo" <?php selected($_GET['estado'] ?? '', 'inactivo'); ?>>Inactivos</option>
                    <option value="perdido" <?php selected($_GET['estado'] ?? '', 'perdido'); ?>>Perdidos</option>
                </select>
                <select name="tipo">
                    <option value="">Todos los tipos</option>
                    <option value="particular" <?php selected($_GET['tipo'] ?? '', 'particular'); ?>>Particular</option>
                    <option value="empresa" <?php selected($_GET['tipo'] ?? '', 'empresa'); ?>>Empresa</option>
                    <option value="autonomo" <?php selected($_GET['tipo'] ?? '', 'autonomo'); ?>>Autónomo</option>
                </select>
                <button type="submit" class="flavor-btn">Filtrar</button>
            </form>
        </div>

        <!-- Lista de clientes -->
        <div class="clientes-tabla">
            <?php if (empty($datos['clientes'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-businessman"></span>
                    <p>No hay clientes registrados</p>
                    <a href="?tab=clientes&subtab=nuevo" class="flavor-btn flavor-btn-primary">Añadir primer cliente</a>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Contacto</th>
                            <th>Valor Est.</th>
                            <th>Última Nota</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['clientes'] as $cliente): ?>
                            <tr>
                                <td>
                                    <div class="cliente-nombre">
                                        <strong><?php echo esc_html($cliente->nombre); ?></strong>
                                        <?php if ($cliente->empresa): ?>
                                            <small><?php echo esc_html($cliente->empresa); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-tipo-<?php echo $cliente->tipo; ?>">
                                        <?php echo ucfirst($cliente->tipo); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-estado-<?php echo $cliente->estado; ?>">
                                        <?php echo ucfirst($cliente->estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cliente->email): ?>
                                        <a href="mailto:<?php echo esc_attr($cliente->email); ?>"><?php echo esc_html($cliente->email); ?></a><br>
                                    <?php endif; ?>
                                    <?php if ($cliente->telefono): ?>
                                        <a href="tel:<?php echo esc_attr($cliente->telefono); ?>"><?php echo esc_html($cliente->telefono); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $cliente->valor_estimado ? number_format($cliente->valor_estimado, 0, ',', '.') . '€' : '-'; ?></td>
                                <td>
                                    <?php if ($cliente->ultima_nota): ?>
                                        <span title="<?php echo esc_attr($cliente->ultima_nota); ?>">
                                            <?php echo human_time_diff(strtotime($cliente->ultima_nota_fecha)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="sin-notas">Sin notas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?tab=clientes&subtab=detalle&cliente_id=<?php echo $cliente->id; ?>"
                                       class="flavor-btn flavor-btn-sm">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_nuevo_cliente() {
        ?>
        <div class="nuevo-cliente-form">
            <h3>Registrar nuevo cliente</h3>

            <form id="form-nuevo-cliente" method="post">
                <?php wp_nonce_field('flavor_nuevo_cliente', 'cliente_nonce'); ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre completo *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono">
                    </div>
                    <div class="form-group">
                        <label for="empresa">Empresa</label>
                        <input type="text" id="empresa" name="empresa">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo de cliente</label>
                        <select id="tipo" name="tipo">
                            <option value="particular">Particular</option>
                            <option value="empresa">Empresa</option>
                            <option value="autonomo">Autónomo</option>
                            <option value="administracion">Administración</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="potencial">Potencial</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="origen">Origen</label>
                        <select id="origen" name="origen">
                            <option value="web">Web</option>
                            <option value="referido">Referido</option>
                            <option value="redes">Redes sociales</option>
                            <option value="directo">Contacto directo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="valor_estimado">Valor estimado (€)</label>
                        <input type="number" id="valor_estimado" name="valor_estimado" min="0" step="0.01">
                    </div>
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label for="etiquetas">Etiquetas (separadas por comas)</label>
                    <input type="text" id="etiquetas" name="etiquetas" placeholder="importante, seguimiento, urgente...">
                </div>

                <div class="form-group">
                    <label for="nota_inicial">Nota inicial</label>
                    <textarea id="nota_inicial" name="nota_inicial" rows="3" placeholder="Primera impresión, contexto, etc."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">Guardar Cliente</button>
                    <a href="?tab=clientes" class="flavor-btn">Cancelar</a>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_detalle_cliente($cliente_id) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        $cliente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente) {
            echo '<div class="flavor-error">Cliente no encontrado</div>';
            return;
        }

        $notas = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as autor_nombre
             FROM $tabla_notas n
             JOIN {$wpdb->users} u ON n.usuario_id = u.ID
             WHERE n.cliente_id = %d
             ORDER BY n.created_at DESC",
            $cliente_id
        ));
        ?>
        <div class="cliente-detalle">
            <!-- Cabecera -->
            <div class="cliente-cabecera">
                <div class="cliente-info-principal">
                    <h2><?php echo esc_html($cliente->nombre); ?></h2>
                    <?php if ($cliente->empresa): ?>
                        <p class="cliente-empresa"><?php echo esc_html($cliente->empresa); ?> - <?php echo esc_html($cliente->cargo); ?></p>
                    <?php endif; ?>
                    <div class="cliente-badges">
                        <span class="badge badge-tipo-<?php echo $cliente->tipo; ?>"><?php echo ucfirst($cliente->tipo); ?></span>
                        <span class="badge badge-estado-<?php echo $cliente->estado; ?>"><?php echo ucfirst($cliente->estado); ?></span>
                    </div>
                </div>
                <div class="cliente-acciones">
                    <a href="?tab=clientes&subtab=editar&cliente_id=<?php echo $cliente->id; ?>" class="flavor-btn">Editar</a>
                    <button class="flavor-btn flavor-btn-danger eliminar-cliente" data-id="<?php echo $cliente->id; ?>">Eliminar</button>
                </div>
            </div>

            <div class="cliente-grid">
                <!-- Información de contacto -->
                <div class="cliente-contacto flavor-card">
                    <h3>Contacto</h3>
                    <dl>
                        <?php if ($cliente->email): ?>
                            <dt>Email</dt>
                            <dd><a href="mailto:<?php echo esc_attr($cliente->email); ?>"><?php echo esc_html($cliente->email); ?></a></dd>
                        <?php endif; ?>
                        <?php if ($cliente->telefono): ?>
                            <dt>Teléfono</dt>
                            <dd><a href="tel:<?php echo esc_attr($cliente->telefono); ?>"><?php echo esc_html($cliente->telefono); ?></a></dd>
                        <?php endif; ?>
                        <?php if ($cliente->direccion): ?>
                            <dt>Dirección</dt>
                            <dd><?php echo esc_html($cliente->direccion); ?></dd>
                        <?php endif; ?>
                    </dl>

                    <div class="acciones-rapidas">
                        <a href="mailto:<?php echo esc_attr($cliente->email); ?>" class="flavor-btn flavor-btn-sm">
                            <span class="dashicons dashicons-email"></span> Email
                        </a>
                        <a href="tel:<?php echo esc_attr($cliente->telefono); ?>" class="flavor-btn flavor-btn-sm">
                            <span class="dashicons dashicons-phone"></span> Llamar
                        </a>
                    </div>
                </div>

                <!-- Información comercial -->
                <div class="cliente-comercial flavor-card">
                    <h3>Información comercial</h3>
                    <dl>
                        <dt>Valor estimado</dt>
                        <dd><strong><?php echo $cliente->valor_estimado ? number_format($cliente->valor_estimado, 2, ',', '.') . '€' : '-'; ?></strong></dd>
                        <dt>Origen</dt>
                        <dd><?php echo ucfirst($cliente->origen); ?></dd>
                        <dt>Fecha registro</dt>
                        <dd><?php echo date_i18n('j F Y', strtotime($cliente->created_at)); ?></dd>
                        <?php if ($cliente->etiquetas): ?>
                            <dt>Etiquetas</dt>
                            <dd>
                                <?php foreach (explode(',', $cliente->etiquetas) as $tag): ?>
                                    <span class="tag"><?php echo esc_html(trim($tag)); ?></span>
                                <?php endforeach; ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Historial de notas -->
                <div class="cliente-notas flavor-card">
                    <h3>Historial de interacciones</h3>

                    <!-- Añadir nota -->
                    <form id="form-nueva-nota" class="nueva-nota-form">
                        <?php wp_nonce_field('flavor_nueva_nota', 'nota_nonce'); ?>
                        <input type="hidden" name="cliente_id" value="<?php echo $cliente->id; ?>">
                        <div class="form-row">
                            <select name="tipo_nota">
                                <option value="nota">Nota</option>
                                <option value="llamada">Llamada</option>
                                <option value="email">Email</option>
                                <option value="reunion">Reunión</option>
                                <option value="tarea">Tarea</option>
                                <option value="seguimiento">Seguimiento</option>
                            </select>
                            <textarea name="contenido" placeholder="Añadir nota..." rows="2"></textarea>
                            <button type="submit" class="flavor-btn flavor-btn-primary">Añadir</button>
                        </div>
                    </form>

                    <!-- Lista de notas -->
                    <div class="notas-lista">
                        <?php if (empty($notas)): ?>
                            <p class="sin-notas">No hay notas registradas</p>
                        <?php else: ?>
                            <?php foreach ($notas as $nota): ?>
                                <div class="nota-item nota-tipo-<?php echo $nota->tipo; ?>">
                                    <div class="nota-icono">
                                        <span class="dashicons <?php echo $this->get_icono_nota($nota->tipo); ?>"></span>
                                    </div>
                                    <div class="nota-contenido">
                                        <div class="nota-meta">
                                            <span class="nota-tipo"><?php echo ucfirst($nota->tipo); ?></span>
                                            <span class="nota-autor"><?php echo esc_html($nota->autor_nombre); ?></span>
                                            <span class="nota-fecha"><?php echo human_time_diff(strtotime($nota->created_at)); ?></span>
                                        </div>
                                        <p><?php echo esc_html($nota->contenido); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_actividad($datos) {
        ?>
        <div class="crm-actividad">
            <h3>Actividad reciente</h3>

            <?php if (empty($datos['actividad_reciente'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clock"></span>
                    <p>No hay actividad reciente</p>
                </div>
            <?php else: ?>
                <div class="timeline-actividad">
                    <?php foreach ($datos['actividad_reciente'] as $item): ?>
                        <div class="actividad-item">
                            <div class="actividad-icono">
                                <span class="dashicons <?php echo $this->get_icono_nota($item->tipo); ?>"></span>
                            </div>
                            <div class="actividad-contenido">
                                <strong><?php echo esc_html($item->cliente_nombre); ?></strong>
                                <span class="tipo"><?php echo ucfirst($item->tipo); ?></span>
                                <p><?php echo esc_html(wp_trim_words($item->contenido, 15)); ?></p>
                                <span class="fecha"><?php echo human_time_diff(strtotime($item->created_at)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_estadisticas($datos) {
        ?>
        <div class="crm-estadisticas">
            <h3>Estadísticas del CRM</h3>

            <div class="stats-grid">
                <!-- Por estado -->
                <div class="flavor-card">
                    <h4>Por Estado</h4>
                    <div class="stat-bars">
                        <?php foreach ($datos['por_estado'] as $estado => $cantidad): ?>
                            <div class="stat-bar">
                                <span class="label"><?php echo ucfirst($estado); ?></span>
                                <div class="bar">
                                    <div class="fill estado-<?php echo $estado; ?>"
                                         style="width: <?php echo $datos['total_clientes'] > 0 ? ($cantidad / $datos['total_clientes']) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="value"><?php echo $cantidad; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Por tipo -->
                <div class="flavor-card">
                    <h4>Por Tipo</h4>
                    <div class="stat-bars">
                        <?php foreach ($datos['por_tipo'] as $tipo => $cantidad): ?>
                            <div class="stat-bar">
                                <span class="label"><?php echo ucfirst($tipo); ?></span>
                                <div class="bar">
                                    <div class="fill tipo-<?php echo $tipo; ?>"
                                         style="width: <?php echo $datos['total_clientes'] > 0 ? ($cantidad / $datos['total_clientes']) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="value"><?php echo $cantidad; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Por origen -->
                <div class="flavor-card">
                    <h4>Por Origen</h4>
                    <div class="stat-bars">
                        <?php foreach ($datos['por_origen'] as $origen => $cantidad): ?>
                            <div class="stat-bar">
                                <span class="label"><?php echo ucfirst($origen); ?></span>
                                <div class="bar">
                                    <div class="fill" style="width: <?php echo $datos['total_clientes'] > 0 ? ($cantidad / $datos['total_clientes']) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="value"><?php echo $cantidad; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        // Filtros
        $where = "1=1";
        $buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

        if ($buscar) {
            $where .= $wpdb->prepare(" AND (nombre LIKE %s OR email LIKE %s OR empresa LIKE %s)",
                '%' . $buscar . '%', '%' . $buscar . '%', '%' . $buscar . '%');
        }
        if ($estado_filtro) {
            $where .= $wpdb->prepare(" AND estado = %s", $estado_filtro);
        }
        if ($tipo_filtro) {
            $where .= $wpdb->prepare(" AND tipo = %s", $tipo_filtro);
        }

        // Clientes
        $clientes = $wpdb->get_results(
            "SELECT c.*,
                    (SELECT contenido FROM $tabla_notas WHERE cliente_id = c.id ORDER BY created_at DESC LIMIT 1) as ultima_nota,
                    (SELECT created_at FROM $tabla_notas WHERE cliente_id = c.id ORDER BY created_at DESC LIMIT 1) as ultima_nota_fecha
             FROM $tabla_clientes c
             WHERE $where
             ORDER BY c.created_at DESC
             LIMIT 50"
        );

        // Estadísticas
        $total_clientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");
        $clientes_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'activo'");
        $clientes_potenciales = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'potencial'");
        $valor_total = $wpdb->get_var("SELECT SUM(valor_estimado) FROM $tabla_clientes WHERE estado = 'activo'");

        // Por estado
        $por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY estado",
            OBJECT_K
        );
        $estados = [];
        foreach ($por_estado as $estado => $row) {
            $estados[$estado] = $row->cantidad;
        }

        // Por tipo
        $por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY tipo",
            OBJECT_K
        );
        $tipos = [];
        foreach ($por_tipo as $tipo => $row) {
            $tipos[$tipo] = $row->cantidad;
        }

        // Por origen
        $por_origen = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY origen",
            OBJECT_K
        );
        $origenes = [];
        foreach ($por_origen as $origen => $row) {
            $origenes[$origen] = $row->cantidad;
        }

        // Actividad reciente
        $actividad_reciente = $wpdb->get_results(
            "SELECT n.*, c.nombre as cliente_nombre
             FROM $tabla_notas n
             JOIN $tabla_clientes c ON n.cliente_id = c.id
             ORDER BY n.created_at DESC
             LIMIT 20"
        );

        return [
            'clientes' => $clientes ?: [],
            'total_clientes' => $total_clientes ?: 0,
            'clientes_activos' => $clientes_activos ?: 0,
            'clientes_potenciales' => $clientes_potenciales ?: 0,
            'valor_total' => $valor_total ?: 0,
            'por_estado' => $estados,
            'por_tipo' => $tipos,
            'por_origen' => $origenes,
            'actividad_reciente' => $actividad_reciente ?: [],
        ];
    }

    private function get_icono_nota($tipo) {
        $iconos = [
            'nota' => 'dashicons-edit',
            'llamada' => 'dashicons-phone',
            'email' => 'dashicons-email',
            'reunion' => 'dashicons-groups',
            'tarea' => 'dashicons-clipboard',
            'seguimiento' => 'dashicons-update',
        ];
        return $iconos[$tipo] ?? 'dashicons-marker';
    }
}

Flavor_Clientes_Dashboard_Tab::get_instance();
