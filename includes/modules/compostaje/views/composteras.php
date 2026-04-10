<?php
/**
 * Vista Composteras - Módulo Compostaje
 * Gestión de composteras comunitarias
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_composteras = $wpdb->prefix . 'flavor_composteras';

// Obtener todas las composteras
$composteras = $wpdb->get_results("SELECT * FROM $tabla_composteras ORDER BY nombre ASC");
?>

<div class="wrap flavor-compostaje-composteras">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-site"></span>
        <?php echo esc_html__('Gestión de Composteras', 'flavor-platform'); ?>
    </h1>

    <a href="#" class="page-title-action" onclick="abrirModalCompostera(); return false;">
        <?php echo esc_html__('Añadir Nueva', 'flavor-platform'); ?>
    </a>

    <hr class="wp-header-end">

    <div class="flavor-composteras-container">
        <div class="flavor-composteras-mapa">
            <div id="mapa-composteras" style="height: 600px; border-radius: 8px;"></div>
        </div>

        <div class="flavor-composteras-lista">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Nombre', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Tipo', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Capacidad', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Nivel', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($composteras as $compostera) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($compostera->nombre); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($compostera->tipo)); ?></td>
                            <td><?php echo number_format($compostera->capacidad_litros); ?> L</td>
                            <td>
                                <div class="flavor-nivel-bar">
                                    <div class="flavor-nivel-fill" style="width: <?php echo $compostera->nivel_llenado; ?>%;"></div>
                                </div>
                                <?php echo $compostera->nivel_llenado; ?>%
                            </td>
                            <td>
                                <?php
                                $estados_clases = [
                                    'activa' => 'flavor-badge-success',
                                    'llena' => 'flavor-badge-warning',
                                    'listo_recoger' => 'flavor-badge-info',
                                    'mantenimiento' => 'flavor-badge-danger',
                                    'inactiva' => 'flavor-badge-secondary',
                                ];
                                ?>
                                <span class="flavor-badge <?php echo $estados_clases[$compostera->estado] ?? 'flavor-badge-secondary'; ?>">
                                    <?php echo esc_html(ucfirst($compostera->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=compostaje&tab=composteras&action=editar&id=' . $compostera->id)); ?>" class="button button-small"><?php echo esc_html__('Editar', 'flavor-platform'); ?></a>
                                <button class="button button-small ver-en-mapa" data-lat="<?php echo esc_attr($compostera->latitud); ?>" data-lng="<?php echo esc_attr($compostera->longitud); ?>">
                                    <?php echo esc_html__('Ver en Mapa', 'flavor-platform'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const composterasData = <?php echo json_encode($composteras); ?>;
    
    // Inicializar mapa
    const map = L.map('mapa-composteras').setView([43.3183, -1.9812], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    composterasData.forEach(compostera => {
        const marker = L.marker([compostera.latitud, compostera.longitud]).addTo(map);
        marker.bindPopup(`<strong>${compostera.nombre}</strong><br>Nivel: ${compostera.nivel_llenado}%`);
    });

    $('.ver-en-mapa').on('click', function() {
        const lat = parseFloat($(this).data('lat'));
        const lng = parseFloat($(this).data('lng'));
        map.setView([lat, lng], 18);
        $('html, body').animate({ scrollTop: $('#mapa-composteras').offset().top - 100 }, 500);
    });
});
</script>

<style>
.flavor-composteras-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.flavor-composteras-mapa,
.flavor-composteras-lista {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-nivel-bar {
    width: 100px;
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    display: inline-block;
    margin-right: 10px;
}

.flavor-nivel-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
}

.flavor-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.flavor-badge-success { background: #d4edda; color: #155724; }
.flavor-badge-warning { background: #fff3cd; color: #856404; }
.flavor-badge-info { background: #d1ecf1; color: #0c5460; }
.flavor-badge-danger { background: #f8d7da; color: #721c24; }
.flavor-badge-secondary { background: #e2e3e5; color: #383d41; }

@media (max-width: 1200px) {
    .flavor-composteras-container {
        grid-template-columns: 1fr;
    }
}

.flavor-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 99999;
}

.flavor-modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    min-width: 400px;
    max-width: 90%;
    z-index: 100000;
}

.flavor-modal-content h3 {
    margin-top: 0;
}

.flavor-modal-content .form-row {
    margin-bottom: 15px;
}

.flavor-modal-content label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.flavor-modal-content input,
.flavor-modal-content select {
    width: 100%;
    padding: 8px;
}

.flavor-modal-actions {
    margin-top: 20px;
    text-align: right;
}
</style>

<!-- Modal Nueva Compostera -->
<div id="modal-nueva-compostera" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalCompostera()"></div>
    <div class="flavor-modal-content">
        <h3><?php echo esc_html__('Nueva Compostera', 'flavor-platform'); ?></h3>
        <form id="form-nueva-compostera" method="post">
            <?php wp_nonce_field('nueva_compostera', 'compostera_nonce'); ?>
            <input type="hidden" name="accion" value="crear_compostera">

            <div class="form-row">
                <label><?php echo esc_html__('Nombre', 'flavor-platform'); ?></label>
                <input type="text" name="nombre" required>
            </div>

            <div class="form-row">
                <label><?php echo esc_html__('Tipo', 'flavor-platform'); ?></label>
                <select name="tipo">
                    <option value="comunitaria"><?php echo esc_html__('Comunitaria', 'flavor-platform'); ?></option>
                    <option value="individual"><?php echo esc_html__('Individual', 'flavor-platform'); ?></option>
                    <option value="escolar"><?php echo esc_html__('Escolar', 'flavor-platform'); ?></option>
                </select>
            </div>

            <div class="form-row">
                <label><?php echo esc_html__('Capacidad (litros)', 'flavor-platform'); ?></label>
                <input type="number" name="capacidad_litros" value="500" min="100">
            </div>

            <div class="form-row">
                <label><?php echo esc_html__('Latitud', 'flavor-platform'); ?></label>
                <input type="text" name="latitud" placeholder="43.3183">
            </div>

            <div class="form-row">
                <label><?php echo esc_html__('Longitud', 'flavor-platform'); ?></label>
                <input type="text" name="longitud" placeholder="-1.9812">
            </div>

            <div class="flavor-modal-actions">
                <button type="button" class="button" onclick="cerrarModalCompostera()"><?php echo esc_html__('Cancelar', 'flavor-platform'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Crear Compostera', 'flavor-platform'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCompostera() {
    document.getElementById('modal-nueva-compostera').style.display = 'block';
}

function cerrarModalCompostera() {
    document.getElementById('modal-nueva-compostera').style.display = 'none';
}
</script>
