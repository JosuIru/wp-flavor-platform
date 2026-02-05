<?php
/**
 * Vista Materiales - Módulo Reciclaje
 * Gestión de categorías de materiales reciclables
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

// Categorías de materiales predefinidas
$categorias_materiales = [
    'papel' => [
        'nombre' => __('Papel y Cartón', 'flavor-chat-ia'),
        'color' => '#6c757d',
        'icon' => '📄',
        'descripcion' => __('Papel, cartón, periódicos, revistas, cajas', 'flavor-chat-ia'),
        'aceptado' => ['Papel limpio', 'Cartón', 'Periódicos', 'Revistas', 'Cajas'],
        'no_aceptado' => ['Papel sucio', 'Papel encerado', 'Papel térmico'],
    ],
    'plastico' => [
        'nombre' => __('Plástico y Envases', 'flavor-chat-ia'),
        'color' => '#ffc107',
        'icon' => '🥤',
        'descripcion' => __('Botellas, envases, bolsas de plástico', 'flavor-chat-ia'),
        'aceptado' => ['Botellas PET', 'Envases de yogur', 'Bolsas de plástico', 'Tapones'],
        'no_aceptado' => ['Plásticos mezclados', 'Film plastificado', 'Juguetes de plástico'],
    ],
    'vidrio' => [
        'nombre' => __('Vidrio', 'flavor-chat-ia'),
        'color' => '#17a2b8',
        'icon' => '🍾',
        'descripcion' => __('Botellas y tarros de vidrio', 'flavor-chat-ia'),
        'aceptado' => ['Botellas', 'Tarros', 'Frascos de vidrio'],
        'no_aceptado' => ['Cristal de ventana', 'Espejos', 'Bombillas', 'Cerámica'],
    ],
    'organico' => [
        'nombre' => __('Orgánico', 'flavor-chat-ia'),
        'color' => '#28a745',
        'icon' => '🌱',
        'descripcion' => __('Restos de comida, residuos de jardín', 'flavor-chat-ia'),
        'aceptado' => ['Restos de frutas', 'Verduras', 'Cáscaras', 'Posos de café', 'Restos de jardín'],
        'no_aceptado' => ['Carne', 'Pescado', 'Lácteos', 'Aceites', 'Excrementos de mascotas'],
    ],
    'electronico' => [
        'nombre' => __('Electrónico (RAEE)', 'flavor-chat-ia'),
        'color' => '#dc3545',
        'icon' => '💻',
        'descripcion' => __('Aparatos electrónicos, pilas, baterías', 'flavor-chat-ia'),
        'aceptado' => ['Móviles', 'Ordenadores', 'Electrodomésticos', 'Cables', 'Pilas'],
        'no_aceptado' => ['Aparatos con aceite', 'Fluorescentes rotos'],
    ],
    'ropa' => [
        'nombre' => __('Ropa y Textil', 'flavor-chat-ia'),
        'color' => '#6f42c1',
        'icon' => '👕',
        'descripcion' => __('Ropa, zapatos, textiles del hogar', 'flavor-chat-ia'),
        'aceptado' => ['Ropa usada', 'Zapatos', 'Sábanas', 'Cortinas', 'Toallas'],
        'no_aceptado' => ['Ropa muy deteriorada', 'Trapos sucios', 'Colchones'],
    ],
    'aceite' => [
        'nombre' => __('Aceite Usado', 'flavor-chat-ia'),
        'color' => '#fd7e14',
        'icon' => '🫗',
        'descripcion' => __('Aceite de cocina usado', 'flavor-chat-ia'),
        'aceptado' => ['Aceite de freír', 'Aceite de conservas'],
        'no_aceptado' => ['Aceite de motor', 'Aceite industrial'],
    ],
    'pilas' => [
        'nombre' => __('Pilas y Baterías', 'flavor-chat-ia'),
        'color' => '#20c997',
        'icon' => '🔋',
        'descripcion' => __('Pilas, baterías, acumuladores', 'flavor-chat-ia'),
        'aceptado' => ['Pilas alcalinas', 'Pilas recargables', 'Baterías de móvil'],
        'no_aceptado' => ['Baterías de coche', 'Baterías industriales'],
    ],
];

// Obtener estadísticas por material
$estadisticas_materiales = [];
foreach (array_keys($categorias_materiales) as $tipo_material) {
    $stats = $wpdb->get_row($wpdb->prepare("
        SELECT
            COUNT(*) as total_depositos,
            SUM(cantidad_kg) as total_kg,
            AVG(cantidad_kg) as promedio_kg,
            COUNT(DISTINCT usuario_id) as usuarios_unicos
        FROM $tabla_depositos
        WHERE tipo_material = %s
    ", $tipo_material));

    $estadisticas_materiales[$tipo_material] = $stats;
}
?>

<div class="wrap flavor-reciclaje-materiales">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-category"></span>
        <?php echo esc_html__('Materiales Reciclables', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <div class="flavor-materiales-grid">
        <?php foreach ($categorias_materiales as $tipo => $material) : ?>
            <?php $stats = $estadisticas_materiales[$tipo]; ?>
            <div class="flavor-material-card" style="border-left-color: <?php echo esc_attr($material['color']); ?>">
                <div class="flavor-material-header">
                    <div class="flavor-material-icon" style="background-color: <?php echo esc_attr($material['color']); ?>20;">
                        <span style="font-size: 40px;"><?php echo $material['icon']; ?></span>
                    </div>
                    <div class="flavor-material-title">
                        <h2><?php echo esc_html($material['nombre']); ?></h2>
                        <p><?php echo esc_html($material['descripcion']); ?></p>
                    </div>
                </div>

                <div class="flavor-material-stats">
                    <div class="flavor-stat-item">
                        <span class="flavor-stat-value"><?php echo number_format($stats->total_kg ?? 0, 2); ?> kg</span>
                        <span class="flavor-stat-label"><?php echo esc_html__('Total Reciclado', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-stat-item">
                        <span class="flavor-stat-value"><?php echo number_format($stats->total_depositos ?? 0); ?></span>
                        <span class="flavor-stat-label"><?php echo esc_html__('Depósitos', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-stat-item">
                        <span class="flavor-stat-value"><?php echo number_format($stats->usuarios_unicos ?? 0); ?></span>
                        <span class="flavor-stat-label"><?php echo esc_html__('Usuarios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-material-info">
                    <div class="flavor-info-section">
                        <h4>
                            <span class="dashicons dashicons-yes" style="color: #28a745;"></span>
                            <?php echo esc_html__('Se Acepta', 'flavor-chat-ia'); ?>
                        </h4>
                        <ul>
                            <?php foreach ($material['aceptado'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="flavor-info-section">
                        <h4>
                            <span class="dashicons dashicons-no" style="color: #dc3545;"></span>
                            <?php echo esc_html__('No Se Acepta', 'flavor-chat-ia'); ?>
                        </h4>
                        <ul>
                            <?php foreach ($material['no_aceptado'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="flavor-material-actions">
                    <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-estadisticas&material=' . $tipo); ?>" class="button">
                        <?php echo esc_html__('Ver Estadísticas', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Consejos de reciclaje -->
    <div class="flavor-consejos-section">
        <h2><?php echo esc_html__('Consejos para Reciclar Correctamente', 'flavor-chat-ia'); ?></h2>

        <div class="flavor-consejos-grid">
            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-admin-site"></span>
                <h3><?php echo esc_html__('Limpio y Seco', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Asegúrate de que los materiales estén limpios y secos antes de depositarlos. Los envases sucios pueden contaminar el resto.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-image-filter"></span>
                <h3><?php echo esc_html__('Separar Correctamente', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Separa los materiales según su tipo. No mezcles diferentes materiales en el mismo contenedor.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-archive"></span>
                <h3><?php echo esc_html__('Aplastar Envases', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Aplasta las botellas y cajas para ahorrar espacio en los contenedores y facilitar el transporte.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-marker"></span>
                <h3><?php echo esc_html__('Sin Etiquetas', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Siempre que sea posible, retira las etiquetas de los envases antes de reciclarlos.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-dismiss"></span>
                <h3><?php echo esc_html__('En Caso de Duda', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Si no estás seguro de dónde va un material, es mejor depositarlo en el contenedor de resto que contaminar el reciclaje.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-consejo-card">
                <span class="dashicons dashicons-clock"></span>
                <h3><?php echo esc_html__('Depósito Regular', 'flavor-chat-ia'); ?></h3>
                <p><?php echo esc_html__('Establece una rutina para depositar tus materiales reciclables regularmente y evita acumulaciones.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-reciclaje-materiales {
    padding: 20px;
}

.flavor-materiales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.flavor-material-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 5px solid;
    overflow: hidden;
}

.flavor-material-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.flavor-material-icon {
    padding: 15px;
    border-radius: 8px;
}

.flavor-material-title h2 {
    margin: 0 0 5px;
    font-size: 20px;
}

.flavor-material-title p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.flavor-material-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.flavor-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.flavor-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #0073aa;
}

.flavor-stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.flavor-material-info {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.flavor-info-section h4 {
    font-size: 14px;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-info-section ul {
    margin: 0;
    padding-left: 20px;
}

.flavor-info-section li {
    font-size: 13px;
    margin-bottom: 5px;
    color: #555;
}

.flavor-material-actions {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

.flavor-consejos-section {
    margin-top: 40px;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-consejos-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.flavor-consejos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.flavor-consejo-card {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.flavor-consejo-card .dashicons {
    font-size: 48px;
    color: #0073aa;
    opacity: 0.8;
    margin-bottom: 10px;
}

.flavor-consejo-card h3 {
    font-size: 16px;
    margin: 10px 0;
}

.flavor-consejo-card p {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .flavor-materiales-grid {
        grid-template-columns: 1fr;
    }

    .flavor-material-info {
        grid-template-columns: 1fr;
    }

    .flavor-consejos-grid {
        grid-template-columns: 1fr;
    }
}
</style>
