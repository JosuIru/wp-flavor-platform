<?php
/**
 * Template: Registrar Aportación de Compostaje
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="compostaje-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesión para registrar aportaciones', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Necesitas estar conectado para registrar tus aportaciones de compostaje.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
$tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';

// Obtener puntos de compostaje activos
$puntos = $wpdb->get_results(
    "SELECT id, nombre, direccion, tipo FROM $tabla_puntos WHERE estado = 'activo' ORDER BY nombre ASC"
);

// Obtener materiales compostables
$materiales = $wpdb->get_results(
    "SELECT codigo, nombre, categoria, puntos_por_kg, consejos FROM $tabla_materiales WHERE activo = 1 ORDER BY orden ASC"
);

// Si no hay materiales en BD, usar predefinidos
if (empty($materiales)) {
    $materiales = [
        (object) ['codigo' => 'frutas_verduras', 'nombre' => 'Frutas y verduras', 'categoria' => 'verde', 'puntos_por_kg' => 5],
        (object) ['codigo' => 'posos_cafe', 'nombre' => 'Posos de café', 'categoria' => 'verde', 'puntos_por_kg' => 6],
        (object) ['codigo' => 'cesped_fresco', 'nombre' => 'Césped fresco', 'categoria' => 'verde', 'puntos_por_kg' => 4],
        (object) ['codigo' => 'restos_cocina', 'nombre' => 'Restos de cocina', 'categoria' => 'verde', 'puntos_por_kg' => 5],
        (object) ['codigo' => 'hojas_secas', 'nombre' => 'Hojas secas', 'categoria' => 'marron', 'puntos_por_kg' => 6],
        (object) ['codigo' => 'papel_carton', 'nombre' => 'Papel y cartón', 'categoria' => 'marron', 'puntos_por_kg' => 7],
        (object) ['codigo' => 'ramas_poda', 'nombre' => 'Ramas y poda', 'categoria' => 'marron', 'puntos_por_kg' => 5],
        (object) ['codigo' => 'cascaras_huevo', 'nombre' => 'Cáscaras de huevo', 'categoria' => 'especial', 'puntos_por_kg' => 8],
    ];
}

// Punto preseleccionado
$punto_id = isset($_GET['punto_id']) ? absint($_GET['punto_id']) : '';

$categoria_labels = [
    'verde' => __('Material verde (rico en nitrógeno)', 'flavor-chat-ia'),
    'marron' => __('Material marrón (rico en carbono)', 'flavor-chat-ia'),
    'especial' => __('Material especial', 'flavor-chat-ia'),
];

$categoria_colors = [
    'verde' => '#10b981',
    'marron' => '#92400e',
    'especial' => '#6366f1',
];
?>

<div class="compostaje-registrar-wrapper">
    <div class="registrar-header">
        <h2><?php esc_html_e('Registrar Aportación', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Registra tu aportación al compostaje comunitario y gana puntos', 'flavor-chat-ia'); ?></p>
    </div>

    <form id="form-registrar-aportacion" class="registrar-form" method="post">
        <?php wp_nonce_field('compostaje_registrar_aportacion', 'compostaje_nonce'); ?>

        <!-- Paso 1: Seleccionar punto -->
        <div class="form-section">
            <h3><span class="step-number">1</span> <?php esc_html_e('Punto de compostaje', 'flavor-chat-ia'); ?></h3>

            <?php if ($puntos): ?>
                <div class="puntos-selector">
                    <?php foreach ($puntos as $punto): ?>
                        <label class="punto-opcion">
                            <input type="radio" name="punto_id" value="<?php echo esc_attr($punto->id); ?>"
                                   <?php checked($punto_id, $punto->id); ?> required>
                            <div class="punto-opcion-content">
                                <span class="punto-nombre"><?php echo esc_html($punto->nombre); ?></span>
                                <span class="punto-direccion"><?php echo esc_html($punto->direccion); ?></span>
                                <span class="punto-tipo"><?php echo esc_html(ucfirst($punto->tipo)); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="form-notice warning">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e('No hay puntos de compostaje disponibles actualmente.', 'flavor-chat-ia'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paso 2: Tipo de material -->
        <div class="form-section">
            <h3><span class="step-number">2</span> <?php esc_html_e('Tipo de material', 'flavor-chat-ia'); ?></h3>

            <div class="materiales-grid">
                <?php
                $materiales_por_categoria = [];
                foreach ($materiales as $material) {
                    $materiales_por_categoria[$material->categoria][] = $material;
                }

                foreach ($materiales_por_categoria as $categoria => $items):
                ?>
                    <div class="categoria-grupo">
                        <h4 style="color: <?php echo esc_attr($categoria_colors[$categoria] ?? '#6b7280'); ?>">
                            <?php echo esc_html($categoria_labels[$categoria] ?? ucfirst($categoria)); ?>
                        </h4>
                        <div class="materiales-lista">
                            <?php foreach ($items as $material): ?>
                                <label class="material-opcion">
                                    <input type="radio" name="tipo_material" value="<?php echo esc_attr($material->codigo); ?>"
                                           data-categoria="<?php echo esc_attr($categoria); ?>"
                                           data-puntos="<?php echo esc_attr($material->puntos_por_kg); ?>" required>
                                    <div class="material-content">
                                        <span class="material-nombre"><?php echo esc_html($material->nombre); ?></span>
                                        <span class="material-puntos"><?php echo esc_html($material->puntos_por_kg); ?> pts/kg</span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Paso 3: Cantidad -->
        <div class="form-section">
            <h3><span class="step-number">3</span> <?php esc_html_e('Cantidad', 'flavor-chat-ia'); ?></h3>

            <div class="cantidad-input-wrapper">
                <button type="button" class="cantidad-btn minus" onclick="ajustarCantidad(-0.5)">−</button>
                <input type="number" id="cantidad_kg" name="cantidad_kg"
                       min="0.1" max="50" step="0.1" value="1"
                       class="cantidad-input" required>
                <span class="cantidad-unidad">kg</span>
                <button type="button" class="cantidad-btn plus" onclick="ajustarCantidad(0.5)">+</button>
            </div>

            <div class="cantidad-rapida">
                <button type="button" class="btn-rapido" onclick="setCantidad(0.5)">0.5 kg</button>
                <button type="button" class="btn-rapido" onclick="setCantidad(1)">1 kg</button>
                <button type="button" class="btn-rapido" onclick="setCantidad(2)">2 kg</button>
                <button type="button" class="btn-rapido" onclick="setCantidad(5)">5 kg</button>
            </div>

            <div class="puntos-preview">
                <span class="preview-label"><?php esc_html_e('Puntos estimados:', 'flavor-chat-ia'); ?></span>
                <span class="preview-puntos" id="puntos-estimados">0</span>
            </div>
        </div>

        <!-- Paso 4: Notas (opcional) -->
        <div class="form-section">
            <h3><span class="step-number">4</span> <?php esc_html_e('Notas (opcional)', 'flavor-chat-ia'); ?></h3>

            <textarea id="notas" name="notas" rows="3"
                      placeholder="<?php esc_attr_e('Añade información adicional sobre tu aportación...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <!-- Resumen y envío -->
        <div class="form-actions">
            <div class="form-notice info">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('Tu aportación se registrará automáticamente. Recuerda depositar el material correctamente.', 'flavor-chat-ia'); ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Registrar aportación', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>

    <!-- Mensaje de resultado -->
    <div id="resultado-registro" class="resultado-mensaje" style="display: none;"></div>
</div>

<style>
.compostaje-registrar-wrapper { max-width: 700px; margin: 0 auto; }
.compostaje-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.compostaje-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }

.registrar-header { text-align: center; margin-bottom: 2rem; }
.registrar-header h2 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #1f2937; }
.registrar-header p { margin: 0; color: #6b7280; }

.registrar-form { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

.form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #f3f4f6; }
.form-section:last-of-type { border-bottom: none; }
.form-section h3 { display: flex; align-items: center; gap: 0.75rem; margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }
.step-number { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #4f46e5; color: white; border-radius: 50%; font-size: 0.85rem; font-weight: 600; }

.puntos-selector { display: flex; flex-direction: column; gap: 0.75rem; }
.punto-opcion { cursor: pointer; }
.punto-opcion input { display: none; }
.punto-opcion-content { padding: 1rem; border: 2px solid #e5e7eb; border-radius: 10px; transition: all 0.2s; }
.punto-opcion input:checked + .punto-opcion-content { border-color: #10b981; background: #ecfdf5; }
.punto-nombre { display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; }
.punto-direccion { display: block; font-size: 0.85rem; color: #6b7280; }
.punto-tipo { display: inline-block; margin-top: 0.5rem; padding: 2px 8px; background: #f3f4f6; border-radius: 4px; font-size: 0.75rem; color: #6b7280; }

.materiales-grid { display: flex; flex-direction: column; gap: 1.5rem; }
.categoria-grupo h4 { margin: 0 0 0.75rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
.materiales-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.5rem; }
.material-opcion { cursor: pointer; }
.material-opcion input { display: none; }
.material-content { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; transition: all 0.2s; }
.material-opcion input:checked + .material-content { border-color: #10b981; background: #ecfdf5; }
.material-nombre { display: block; font-size: 0.85rem; font-weight: 500; color: #1f2937; }
.material-puntos { display: block; font-size: 0.75rem; color: #10b981; margin-top: 0.25rem; }

.cantidad-input-wrapper { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem; }
.cantidad-btn { width: 44px; height: 44px; border: none; border-radius: 50%; background: #f3f4f6; font-size: 1.5rem; color: #374151; cursor: pointer; transition: all 0.2s; }
.cantidad-btn:hover { background: #e5e7eb; }
.cantidad-input { width: 100px; height: 50px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 2px solid #e5e7eb; border-radius: 8px; }
.cantidad-unidad { font-size: 1.1rem; font-weight: 500; color: #6b7280; }

.cantidad-rapida { display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1rem; flex-wrap: wrap; }
.btn-rapido { padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 0.85rem; color: #374151; transition: all 0.2s; }
.btn-rapido:hover { background: #f3f4f6; }

.puntos-preview { display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 1rem; background: #ecfdf5; border-radius: 8px; }
.preview-label { font-size: 0.9rem; color: #065f46; }
.preview-puntos { font-size: 1.5rem; font-weight: 700; color: #10b981; }

textarea { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; resize: vertical; font-family: inherit; }
textarea:focus { outline: none; border-color: #10b981; }

.form-notice { display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem; }
.form-notice.info { background: #eff6ff; color: #1e40af; }
.form-notice.warning { background: #fef3c7; color: #92400e; }
.form-notice .dashicons { font-size: 18px; width: 18px; height: 18px; flex-shrink: 0; }

.form-actions { text-align: center; }

.resultado-mensaje { margin-top: 1.5rem; padding: 1.5rem; border-radius: 10px; text-align: center; }
.resultado-mensaje.success { background: #d1fae5; color: #065f46; }
.resultado-mensaje.error { background: #fee2e2; color: #991b1b; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-lg { padding: 1rem 2rem; font-size: 1rem; }
.btn-primary { background: #10b981; color: white; }
.btn-primary:hover { background: #059669; }
.btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
</style>

<script>
function ajustarCantidad(delta) {
    var input = document.getElementById('cantidad_kg');
    var valor = parseFloat(input.value) || 1;
    valor = Math.max(0.1, Math.min(50, valor + delta));
    input.value = valor.toFixed(1);
    calcularPuntos();
}

function setCantidad(valor) {
    document.getElementById('cantidad_kg').value = valor;
    calcularPuntos();
}

function calcularPuntos() {
    var cantidad = parseFloat(document.getElementById('cantidad_kg').value) || 0;
    var materialSeleccionado = document.querySelector('input[name="tipo_material"]:checked');
    var puntosPorKg = materialSeleccionado ? parseInt(materialSeleccionado.dataset.puntos) : 0;
    var puntosEstimados = Math.round(cantidad * puntosPorKg);
    document.getElementById('puntos-estimados').textContent = puntosEstimados;
}

// Event listeners
document.querySelectorAll('input[name="tipo_material"]').forEach(function(radio) {
    radio.addEventListener('change', calcularPuntos);
});
document.getElementById('cantidad_kg').addEventListener('input', calcularPuntos);

// Form submission
document.getElementById('form-registrar-aportacion').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('action', 'compostaje_registrar_aportacion');

    fetch(flavorCompostaje?.ajaxUrl || ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        var resultado = document.getElementById('resultado-registro');
        if (data.success) {
            resultado.className = 'resultado-mensaje success';
            resultado.innerHTML = '<strong>¡Aportación registrada!</strong><br>' +
                                  'Has ganado ' + (data.data?.puntos || 0) + ' puntos. ¡Gracias por contribuir!';
            this.reset();
            document.getElementById('puntos-estimados').textContent = '0';
        } else {
            resultado.className = 'resultado-mensaje error';
            resultado.innerHTML = data.data?.message || 'Error al registrar la aportación.';
        }
        resultado.style.display = 'block';
        resultado.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .catch(error => {
        console.error('Error:', error);
        var resultado = document.getElementById('resultado-registro');
        resultado.className = 'resultado-mensaje error';
        resultado.innerHTML = 'Error de conexión. Inténtalo de nuevo.';
        resultado.style.display = 'block';
    });
});

// Calcular puntos inicial
calcularPuntos();
</script>
