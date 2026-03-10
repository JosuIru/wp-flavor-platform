<?php
/**
 * Vista Recursos y Herramientas - Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_herramientas = $wpdb->prefix . 'flavor_huertos_herramientas';
$tabla_prestamos = $wpdb->prefix . 'flavor_huertos_prestamos';
$tabla_recursos = $wpdb->prefix . 'flavor_huertos_recursos';

// Verificar si las tablas existen
$tabla_herramientas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_herramientas'") === $tabla_herramientas;
$tabla_recursos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_recursos'") === $tabla_recursos;

// Filtros
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';

// Datos reales o demo
if ($tabla_herramientas_existe) {
    // Estadísticas de herramientas
    $total_herramientas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_herramientas");
    $herramientas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_herramientas WHERE estado = 'disponible'");
    $herramientas_prestadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_herramientas WHERE estado = 'prestada'");
    $herramientas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_herramientas WHERE estado = 'mantenimiento'");

    // Préstamos activos
    $prestamos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'");

    // Herramientas por tipo
    $herramientas_por_tipo = $wpdb->get_results("
        SELECT tipo, COUNT(*) as total
        FROM $tabla_herramientas
        GROUP BY tipo
        ORDER BY total DESC
    ");

    // Construir WHERE
    $where_clauses = ['1=1'];
    $where_values = [];

    if (!empty($filtro_tipo)) {
        $where_clauses[] = "tipo = %s";
        $where_values[] = $filtro_tipo;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "estado = %s";
        $where_values[] = $filtro_estado;
    }

    if (!empty($filtro_busqueda)) {
        $where_clauses[] = "(nombre LIKE %s OR descripcion LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Obtener herramientas
    if (!empty($where_values)) {
        $herramientas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_herramientas WHERE $where_sql ORDER BY tipo, nombre",
            $where_values
        ));
    } else {
        $herramientas = $wpdb->get_results("SELECT * FROM $tabla_herramientas WHERE $where_sql ORDER BY tipo, nombre");
    }

    // Tipos disponibles para filtro
    $tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_herramientas ORDER BY tipo");

    $usar_demo = empty($herramientas) && empty($filtro_tipo) && empty($filtro_estado) && empty($filtro_busqueda);
} else {
    $usar_demo = true;
}

// Recursos comunes (infraestructura)
if ($tabla_recursos_existe) {
    $recursos_comunes = $wpdb->get_results("SELECT * FROM $tabla_recursos WHERE activo = 1 ORDER BY tipo, nombre");
    $usar_demo_recursos = empty($recursos_comunes);
} else {
    $usar_demo_recursos = true;
}

// Demo data para herramientas
if ($usar_demo) {
    $total_herramientas = 28;
    $herramientas_disponibles = 20;
    $herramientas_prestadas = 6;
    $herramientas_mantenimiento = 2;
    $prestamos_activos = 6;

    $herramientas_por_tipo = [
        (object) ['tipo' => 'Cavado', 'total' => 8],
        (object) ['tipo' => 'Riego', 'total' => 10],
        (object) ['tipo' => 'Poda', 'total' => 6],
        (object) ['tipo' => 'Siembra', 'total' => 4],
    ];

    $tipos_disponibles = ['Cavado', 'Riego', 'Poda', 'Siembra', 'Transporte', 'Protección'];

    $herramientas = [
        (object) ['id' => 1, 'nombre' => 'Pala Grande', 'tipo' => 'Cavado', 'cantidad' => 5, 'estado' => 'disponible', 'ubicacion' => 'Almacén A', 'descripcion' => 'Pala de acero con mango de madera'],
        (object) ['id' => 2, 'nombre' => 'Azada', 'tipo' => 'Cavado', 'cantidad' => 3, 'estado' => 'disponible', 'ubicacion' => 'Almacén A', 'descripcion' => 'Azada de doble uso'],
        (object) ['id' => 3, 'nombre' => 'Regadera 10L', 'tipo' => 'Riego', 'cantidad' => 8, 'estado' => 'disponible', 'ubicacion' => 'Caseta Riego', 'descripcion' => 'Regadera de plástico 10 litros'],
        (object) ['id' => 4, 'nombre' => 'Manguera 25m', 'tipo' => 'Riego', 'cantidad' => 2, 'estado' => 'prestada', 'ubicacion' => 'Caseta Riego', 'descripcion' => 'Manguera extensible con pistola'],
        (object) ['id' => 5, 'nombre' => 'Tijeras de Poda', 'tipo' => 'Poda', 'cantidad' => 4, 'estado' => 'disponible', 'ubicacion' => 'Almacén A', 'descripcion' => 'Tijeras profesionales de poda'],
        (object) ['id' => 6, 'nombre' => 'Serrucho Poda', 'tipo' => 'Poda', 'cantidad' => 2, 'estado' => 'mantenimiento', 'ubicacion' => 'Taller', 'descripcion' => 'Serrucho curvo para ramas'],
        (object) ['id' => 7, 'nombre' => 'Semillero', 'tipo' => 'Siembra', 'cantidad' => 12, 'estado' => 'disponible', 'ubicacion' => 'Invernadero', 'descripcion' => 'Bandejas de semillero 40 alvéolos'],
        (object) ['id' => 8, 'nombre' => 'Rastrillo', 'tipo' => 'Cavado', 'cantidad' => 4, 'estado' => 'prestada', 'ubicacion' => 'Almacén A', 'descripcion' => 'Rastrillo de jardín metálico'],
        (object) ['id' => 9, 'nombre' => 'Carretilla', 'tipo' => 'Transporte', 'cantidad' => 3, 'estado' => 'disponible', 'ubicacion' => 'Almacén B', 'descripcion' => 'Carretilla de obra 80L'],
        (object) ['id' => 10, 'nombre' => 'Guantes', 'tipo' => 'Protección', 'cantidad' => 20, 'estado' => 'disponible', 'ubicacion' => 'Almacén A', 'descripcion' => 'Guantes de jardinería talla M/L'],
    ];
}

// Demo data para recursos comunes
if ($usar_demo_recursos) {
    $recursos_comunes = [
        (object) ['id' => 1, 'nombre' => 'Sistema de Riego Automático', 'tipo' => 'Infraestructura', 'descripcion' => 'Riego por goteo programable para todas las parcelas', 'estado' => 'operativo', 'ultima_revision' => '2024-01-15'],
        (object) ['id' => 2, 'nombre' => 'Composteras Comunitarias', 'tipo' => 'Compostaje', 'descripcion' => '4 composteras de 1000L para residuos orgánicos', 'estado' => 'operativo', 'ultima_revision' => '2024-02-01'],
        (object) ['id' => 3, 'nombre' => 'Almacén de Herramientas', 'tipo' => 'Instalación', 'descripcion' => 'Caseta de 20m² con estanterías y armarios', 'estado' => 'operativo', 'ultima_revision' => '2024-01-20'],
        (object) ['id' => 4, 'nombre' => 'Zona de Descanso', 'tipo' => 'Instalación', 'descripcion' => 'Área con bancos, pérgola y fuente de agua', 'estado' => 'operativo', 'ultima_revision' => '2024-02-10'],
        (object) ['id' => 5, 'nombre' => 'Invernadero', 'tipo' => 'Infraestructura', 'descripcion' => 'Invernadero de 30m² para semilleros', 'estado' => 'operativo', 'ultima_revision' => '2024-01-25'],
        (object) ['id' => 6, 'nombre' => 'Depósito de Agua', 'tipo' => 'Infraestructura', 'descripcion' => 'Depósito de 5000L para riego de emergencia', 'estado' => 'operativo', 'ultima_revision' => '2024-02-05'],
    ];
}

// Estados para badges
$estados_herramienta = [
    'disponible' => ['label' => __('Disponible', 'flavor-chat-ia'), 'color' => '#28a745'],
    'prestada' => ['label' => __('Prestada', 'flavor-chat-ia'), 'color' => '#ffc107'],
    'mantenimiento' => ['label' => __('Mantenimiento', 'flavor-chat-ia'), 'color' => '#dc3545'],
    'baja' => ['label' => __('Baja', 'flavor-chat-ia'), 'color' => '#6c757d'],
];

$estados_recurso = [
    'operativo' => ['label' => __('Operativo', 'flavor-chat-ia'), 'color' => '#28a745'],
    'revision' => ['label' => __('En Revisión', 'flavor-chat-ia'), 'color' => '#ffc107'],
    'averiado' => ['label' => __('Averiado', 'flavor-chat-ia'), 'color' => '#dc3545'],
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-hammer" style="color: #6f42c1;"></span>
        <?php echo esc_html__('Recursos y Herramientas', 'flavor-chat-ia'); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración. Los datos reales aparecerán cuando se registren herramientas y recursos.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="recursos-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #6f42c1;"><?php echo number_format($total_herramientas); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Herramientas', 'flavor-chat-ia'); ?></div>
                </div>
                <span class="dashicons dashicons-hammer" style="font-size: 32px; color: #6f42c1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($herramientas_disponibles); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Disponibles', 'flavor-chat-ia'); ?></div>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #ffc107;"><?php echo number_format($herramientas_prestadas); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Prestadas', 'flavor-chat-ia'); ?></div>
                </div>
                <span class="dashicons dashicons-external" style="font-size: 32px; color: #ffc107; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #dc3545;"><?php echo number_format($herramientas_mantenimiento); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('En Mantenimiento', 'flavor-chat-ia'); ?></div>
                </div>
                <span class="dashicons dashicons-admin-tools" style="font-size: 32px; color: #dc3545; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo number_format($prestamos_activos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Préstamos Activos', 'flavor-chat-ia'); ?></div>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 32px; color: #17a2b8; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Filtros -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'huertos-urbanos-recursos'); ?>">

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></label>
                    <select name="tipo" style="min-width: 140px;">
                        <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($tipos_disponibles as $tipo): ?>
                            <option value="<?php echo esc_attr($tipo); ?>" <?php selected($filtro_tipo, $tipo); ?>>
                                <?php echo esc_html($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado" style="min-width: 140px;">
                        <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($estados_herramienta as $estado_key => $estado_data): ?>
                            <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($filtro_estado, $estado_key); ?>>
                                <?php echo esc_html($estado_data['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="busqueda" value="<?php echo esc_attr($filtro_busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Nombre...', 'flavor-chat-ia'); ?>" style="min-width: 150px;">
                </div>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if (!empty($filtro_tipo) || !empty($filtro_estado) || !empty($filtro_busqueda)): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'huertos-urbanos-recursos')); ?>" class="button">
                        <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Gráfico por tipo -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                <span class="dashicons dashicons-chart-pie" style="font-size: 16px;"></span>
                <?php echo esc_html__('Por Categoría', 'flavor-chat-ia'); ?>
            </h4>
            <canvas id="chartTiposHerramientas" height="120"></canvas>
        </div>
    </div>

    <!-- Inventario de Herramientas -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 20px;">
        <div style="padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
            <h3 style="margin: 0; font-size: 16px;">
                <span class="dashicons dashicons-hammer" style="color: #6f42c1;"></span>
                <?php echo esc_html__('Inventario de Herramientas', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Herramienta', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                    <th style="width: 80px; text-align: center;"><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($herramientas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-hammer" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No se encontraron herramientas.', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($herramientas as $herramienta):
                        $estado_info = $estados_herramienta[$herramienta->estado] ?? ['label' => ucfirst($herramienta->estado), 'color' => '#666'];
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($herramienta->id); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($herramienta->nombre); ?></strong>
                                <?php if (!empty($herramienta->descripcion)): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($herramienta->descripcion); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background: #e9ecef; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo esc_html($herramienta->tipo); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <strong style="font-size: 16px;"><?php echo (int) $herramienta->cantidad; ?></strong>
                            </td>
                            <td>
                                <span class="dashicons dashicons-location" style="font-size: 14px; color: #666;"></span>
                                <?php echo esc_html($herramienta->ubicacion ?: '-'); ?>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?php echo esc_attr($estado_info['color']); ?>20; color: <?php echo esc_attr($estado_info['color']); ?>;">
                                    <?php echo esc_html($estado_info['label']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small" title="<?php echo esc_attr__('Registrar préstamo', 'flavor-chat-ia'); ?>" <?php echo $herramienta->estado !== 'disponible' ? 'disabled' : ''; ?>>
                                    <span class="dashicons dashicons-external" style="vertical-align: text-bottom;"></span>
                                </button>
                                <button class="button button-small" title="<?php echo esc_attr__('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit" style="vertical-align: text-bottom;"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recursos Comunes -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
            <h3 style="margin: 0; font-size: 16px;">
                <span class="dashicons dashicons-building" style="color: #28a745;"></span>
                <?php echo esc_html__('Recursos Comunes e Infraestructura', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; padding: 20px;">
            <?php foreach ($recursos_comunes as $recurso):
                $estado_recurso_info = $estados_recurso[$recurso->estado] ?? ['label' => ucfirst($recurso->estado), 'color' => '#666'];
            ?>
                <div class="recurso-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo esc_attr($estado_recurso_info['color']); ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <h4 style="margin: 0; font-size: 15px;"><?php echo esc_html($recurso->nombre); ?></h4>
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($estado_recurso_info['color']); ?>20; color: <?php echo esc_attr($estado_recurso_info['color']); ?>;">
                            <?php echo esc_html($estado_recurso_info['label']); ?>
                        </span>
                    </div>
                    <p style="margin: 0 0 10px 0; color: #666; font-size: 13px;"><?php echo esc_html($recurso->descripcion); ?></p>
                    <div style="display: flex; gap: 15px; font-size: 12px; color: #888;">
                        <span>
                            <span class="dashicons dashicons-tag" style="font-size: 14px;"></span>
                            <?php echo esc_html($recurso->tipo); ?>
                        </span>
                        <?php if (!empty($recurso->ultima_revision)): ?>
                            <span>
                                <span class="dashicons dashicons-calendar-alt" style="font-size: 14px;"></span>
                                <?php echo esc_html__('Revisión:', 'flavor-chat-ia'); ?> <?php echo date_i18n('d/m/Y', strtotime($recurso->ultima_revision)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartTiposHerramientas');
    if (ctx) {
        const data = <?php echo json_encode($herramientas_por_tipo); ?>;
        const colores = ['#6f42c1', '#28a745', '#ffc107', '#17a2b8', '#dc3545', '#f39c12'];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.tipo),
                datasets: [{
                    data: data.map(d => parseInt(d.total)),
                    backgroundColor: colores.slice(0, data.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>

<style>
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
.recurso-card:hover {
    background: #f0f0f0 !important;
    transition: all 0.2s ease;
}
</style>
