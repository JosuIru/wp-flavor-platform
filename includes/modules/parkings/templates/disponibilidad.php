<?php
/**
 * Template: Disponibilidad de Plazas de Parking
 *
 * Muestra las plazas disponibles con colores verde (libre) / rojo (ocupada).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_parkings = $wpdb->prefix . 'flavor_parkings';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

// Verificar si existen las tablas
if (!Flavor_Chat_Helpers::tabla_existe($tabla_parkings)) {
    echo '<div class="parkings-empty"><p>' . esc_html__('El módulo de parkings no está configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Filtros
$parking_filtro = isset($_GET['parking']) ? absint($_GET['parking']) : 0;
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Obtener parkings disponibles
$parkings_disponibles = $wpdb->get_results("SELECT id, nombre, direccion FROM $tabla_parkings WHERE estado = 'activo' ORDER BY nombre");

// Construir consulta de plazas
$where_conditions = ["p.estado = 'activa'"];
$query_params = [];

if ($parking_filtro) {
    $where_conditions[] = "p.parking_id = %d";
    $query_params[] = $parking_filtro;
}

if ($tipo_filtro) {
    $where_conditions[] = "p.tipo = %s";
    $query_params[] = $tipo_filtro;
}

$where_sql = implode(' AND ', $where_conditions);

// Obtener plazas con su estado actual de ocupación
$plazas_query = "
    SELECT
        p.id,
        p.numero,
        p.tipo,
        p.parking_id,
        pk.nombre AS parking_nombre,
        pk.direccion AS parking_direccion,
        CASE
            WHEN r.id IS NOT NULL THEN 'ocupada'
            ELSE 'libre'
        END AS disponibilidad
    FROM $tabla_plazas p
    LEFT JOIN $tabla_parkings pk ON p.parking_id = pk.id
    LEFT JOIN $tabla_reservas r ON p.id = r.plaza_id
        AND r.estado = 'activa'
        AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin
    WHERE $where_sql
    ORDER BY pk.nombre, p.numero
";

if (!empty($query_params)) {
    $plazas = $wpdb->get_results($wpdb->prepare($plazas_query, $query_params));
} else {
    $plazas = $wpdb->get_results($plazas_query);
}

// Agrupar plazas por parking
$plazas_por_parking = [];
foreach ($plazas as $plaza) {
    $parking_id = $plaza->parking_id;
    if (!isset($plazas_por_parking[$parking_id])) {
        $plazas_por_parking[$parking_id] = [
            'nombre' => $plaza->parking_nombre,
            'direccion' => $plaza->parking_direccion,
            'plazas' => [],
            'libres' => 0,
            'ocupadas' => 0
        ];
    }
    $plazas_por_parking[$parking_id]['plazas'][] = $plaza;
    if ($plaza->disponibilidad === 'libre') {
        $plazas_por_parking[$parking_id]['libres']++;
    } else {
        $plazas_por_parking[$parking_id]['ocupadas']++;
    }
}

// Tipos de plazas disponibles
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_plazas WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo");

$tipos_labels = [
    'normal' => __('Normal', 'flavor-chat-ia'),
    'discapacitado' => __('Movilidad reducida', 'flavor-chat-ia'),
    'moto' => __('Moto', 'flavor-chat-ia'),
    'electrico' => __('Vehículo eléctrico', 'flavor-chat-ia'),
    'grande' => __('Plaza grande', 'flavor-chat-ia'),
];
?>

<div class="parkings-disponibilidad">
    <header class="parkings-header">
        <h2><?php esc_html_e('Disponibilidad de Plazas', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Consulta en tiempo real las plazas disponibles', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Filtros -->
    <div class="parkings-filtros">
        <form class="parkings-filtros__form" method="get">
            <div class="filtro-grupo">
                <select name="parking" onchange="this.form.submit()">
                    <option value=""><?php esc_html_e('Todos los parkings', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($parkings_disponibles as $parking_item): ?>
                        <option value="<?php echo esc_attr($parking_item->id); ?>" <?php selected($parking_filtro, $parking_item->id); ?>>
                            <?php echo esc_html($parking_item->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($tipos_disponibles): ?>
            <div class="filtro-grupo">
                <select name="tipo" onchange="this.form.submit()">
                    <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_disponibles as $tipo_item): ?>
                        <option value="<?php echo esc_attr($tipo_item); ?>" <?php selected($tipo_filtro, $tipo_item); ?>>
                            <?php echo esc_html($tipos_labels[$tipo_item] ?? ucfirst($tipo_item)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="filtro-leyenda">
                <span class="leyenda-item">
                    <span class="leyenda-color leyenda-color--libre"></span>
                    <?php esc_html_e('Libre', 'flavor-chat-ia'); ?>
                </span>
                <span class="leyenda-item">
                    <span class="leyenda-color leyenda-color--ocupada"></span>
                    <?php esc_html_e('Ocupada', 'flavor-chat-ia'); ?>
                </span>
            </div>
        </form>
    </div>

    <?php if (!empty($plazas_por_parking)): ?>
        <div class="parkings-grid">
            <?php foreach ($plazas_por_parking as $parking_id => $parking_data): ?>
                <div class="parking-card">
                    <header class="parking-card__header">
                        <h3 class="parking-card__nombre"><?php echo esc_html($parking_data['nombre']); ?></h3>
                        <span class="parking-card__ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($parking_data['direccion']); ?>
                        </span>
                    </header>

                    <div class="parking-card__resumen">
                        <div class="resumen-stat resumen-stat--libres">
                            <span class="stat-numero"><?php echo esc_html($parking_data['libres']); ?></span>
                            <span class="stat-label"><?php esc_html_e('Libres', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="resumen-stat resumen-stat--ocupadas">
                            <span class="stat-numero"><?php echo esc_html($parking_data['ocupadas']); ?></span>
                            <span class="stat-label"><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>

                    <div class="parking-card__plazas">
                        <?php foreach ($parking_data['plazas'] as $plaza):
                            $estado_clase = $plaza->disponibilidad === 'libre' ? 'plaza--libre' : 'plaza--ocupada';
                            $tipo_icono = '';
                            switch ($plaza->tipo) {
                                case 'discapacitado':
                                    $tipo_icono = '♿';
                                    break;
                                case 'moto':
                                    $tipo_icono = '🏍️';
                                    break;
                                case 'electrico':
                                    $tipo_icono = '⚡';
                                    break;
                                case 'grande':
                                    $tipo_icono = '🚐';
                                    break;
                            }
                        ?>
                            <div class="plaza <?php echo esc_attr($estado_clase); ?>"
                                 title="<?php printf(esc_attr__('Plaza %s - %s', 'flavor-chat-ia'), $plaza->numero, $plaza->disponibilidad === 'libre' ? __('Disponible', 'flavor-chat-ia') : __('Ocupada', 'flavor-chat-ia')); ?>">
                                <span class="plaza__numero"><?php echo esc_html($plaza->numero); ?></span>
                                <?php if ($tipo_icono): ?>
                                    <span class="plaza__tipo"><?php echo $tipo_icono; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (is_user_logged_in() && $parking_data['libres'] > 0): ?>
                        <footer class="parking-card__footer">
                            <a href="<?php echo esc_url(add_query_arg('parking', $parking_id, home_url('/mi-portal/parkings/solicitar/'))); ?>" class="btn btn-primary btn-sm">
                                <?php esc_html_e('Reservar plaza', 'flavor-chat-ia'); ?>
                            </a>
                        </footer>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="parkings-empty-state">
            <span class="dashicons dashicons-car"></span>
            <p><?php esc_html_e('No se encontraron plazas con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.parkings-disponibilidad { max-width: 1200px; margin: 0 auto; }
.parkings-header { text-align: center; margin-bottom: 2rem; }
.parkings-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.parkings-header p { color: #6b7280; margin: 0; }

.parkings-filtros { margin-bottom: 2rem; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.parkings-filtros__form { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
.filtro-grupo select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; }
.filtro-leyenda { display: flex; gap: 1rem; margin-left: auto; }
.leyenda-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #6b7280; }
.leyenda-color { width: 14px; height: 14px; border-radius: 3px; }
.leyenda-color--libre { background: #10b981; }
.leyenda-color--ocupada { background: #ef4444; }

.parkings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }

.parking-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.parking-card__header { padding: 1.25rem; border-bottom: 1px solid #f3f4f6; }
.parking-card__nombre { margin: 0 0 0.5rem; font-size: 1.125rem; color: #1f2937; }
.parking-card__ubicacion { display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; color: #6b7280; }

.parking-card__resumen { display: flex; padding: 1rem 1.25rem; gap: 1.5rem; background: #fafbfc; }
.resumen-stat { text-align: center; }
.stat-numero { display: block; font-size: 1.5rem; font-weight: 700; }
.resumen-stat--libres .stat-numero { color: #10b981; }
.resumen-stat--ocupadas .stat-numero { color: #ef4444; }
.stat-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.parking-card__plazas { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1.25rem; }
.plaza { width: 40px; height: 40px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: default; transition: transform 0.15s; position: relative; }
.plaza:hover { transform: scale(1.1); }
.plaza--libre { background: #d1fae5; color: #047857; border: 2px solid #10b981; }
.plaza--ocupada { background: #fee2e2; color: #b91c1c; border: 2px solid #ef4444; }
.plaza__numero { line-height: 1; }
.plaza__tipo { font-size: 0.6rem; line-height: 1; }

.parking-card__footer { padding: 1rem 1.25rem; border-top: 1px solid #f3f4f6; text-align: center; }

.parkings-empty-state { text-align: center; padding: 3rem 1rem; color: #6b7280; }
.parkings-empty-state .dashicons { font-size: 3rem; width: auto; height: auto; margin-bottom: 1rem; opacity: 0.5; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }

@media (max-width: 640px) {
    .parkings-filtros__form { flex-direction: column; align-items: stretch; }
    .filtro-leyenda { margin-left: 0; justify-content: center; }
    .parkings-grid { grid-template-columns: 1fr; }
}
</style>
