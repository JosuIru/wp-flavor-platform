<?php
/**
 * Template: Formulario de aportación de residuos orgánicos
 *
 * @package FlavorChatIA
 * @since 1.0.0
 *
 * Variables esperadas en $args:
 * - tipos_residuos: array con tipos de residuos disponibles
 * - composteras: array con composteras disponibles
 * - usuario_id: ID del usuario actual
 * - mensaje_exito: mensaje a mostrar tras envío exitoso
 * - mensaje_error: mensaje de error si existe
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$tiposResiduos = $args['tipos_residuos'] ?? [
    'frutas_verduras' => 'Frutas y verduras',
    'cascaras_huevo' => 'Cáscaras de huevo',
    'posos_cafe' => 'Posos de café/té',
    'hojas_secas' => 'Hojas secas',
    'cesped' => 'Césped cortado',
    'restos_poda' => 'Restos de poda',
    'papel_carton' => 'Papel/cartón sin tintas',
    'servilletas' => 'Servilletas usadas',
];

$composteras = $args['composteras'] ?? [
    ['id' => 1, 'nombre' => 'Compostera Plaza Mayor', 'ubicacion' => 'Plaza Mayor, 1'],
    ['id' => 2, 'nombre' => 'Compostera Parque Central', 'ubicacion' => 'Av. del Parque, 15'],
    ['id' => 3, 'nombre' => 'Compostera Huerto Comunitario', 'ubicacion' => 'C/ Verde, 8'],
];

$usuarioId = $args['usuario_id'] ?? get_current_user_id();
$mensajeExito = $args['mensaje_exito'] ?? '';
$mensajeError = $args['mensaje_error'] ?? '';
$pesoMinimo = $args['peso_minimo'] ?? 0.1;
$pesoMaximo = $args['peso_maximo'] ?? 50;
?>

<div class="flavor-compostaje-formulario">
    <div class="flavor-formulario-header">
        <h2 class="flavor-formulario-titulo">
            <span class="flavor-icono-hoja">🌱</span>
            Registrar aportación de residuos orgánicos
        </h2>
        <p class="flavor-formulario-descripcion">
            Registra tu aportación de residuos orgánicos y contribuye al compostaje comunitario.
        </p>
    </div>

    <?php if (!empty($mensajeExito)) : ?>
        <div class="flavor-mensaje flavor-mensaje-exito" role="alert">
            <span class="flavor-mensaje-icono">✓</span>
            <?php echo esc_html($mensajeExito); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)) : ?>
        <div class="flavor-mensaje flavor-mensaje-error" role="alert">
            <span class="flavor-mensaje-icono">!</span>
            <?php echo esc_html($mensajeError); ?>
        </div>
    <?php endif; ?>

    <form class="flavor-formulario" method="post" action="" id="flavor-form-aportacion">
        <?php wp_nonce_field('flavor_aportacion_residuos', 'flavor_nonce'); ?>
        <input type="hidden" name="usuario_id" value="<?php echo esc_attr($usuarioId); ?>">

        <div class="flavor-formulario-grupo">
            <label for="flavor-tipo-residuo" class="flavor-label">
                Tipo de residuo <span class="flavor-requerido">*</span>
            </label>
            <select
                id="flavor-tipo-residuo"
                name="tipo_residuo"
                class="flavor-select"
                required
                aria-describedby="flavor-tipo-residuo-ayuda"
            >
                <option value="">Selecciona el tipo de residuo</option>
                <?php foreach ($tiposResiduos as $claveTipo => $nombreTipo) : ?>
                    <option value="<?php echo esc_attr($claveTipo); ?>">
                        <?php echo esc_html($nombreTipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="flavor-tipo-residuo-ayuda" class="flavor-ayuda">
                Selecciona el tipo principal de residuo que vas a depositar.
            </span>
        </div>

        <div class="flavor-formulario-grupo">
            <label for="flavor-peso" class="flavor-label">
                Peso aproximado (kg) <span class="flavor-requerido">*</span>
            </label>
            <div class="flavor-input-grupo">
                <input
                    type="number"
                    id="flavor-peso"
                    name="peso"
                    class="flavor-input"
                    min="<?php echo esc_attr($pesoMinimo); ?>"
                    max="<?php echo esc_attr($pesoMaximo); ?>"
                    step="0.1"
                    placeholder="Ej: 2.5"
                    required
                    aria-describedby="flavor-peso-ayuda"
                >
                <span class="flavor-input-sufijo">kg</span>
            </div>
            <span id="flavor-peso-ayuda" class="flavor-ayuda">
                Indica el peso aproximado en kilogramos (entre <?php echo esc_html($pesoMinimo); ?> y <?php echo esc_html($pesoMaximo); ?> kg).
            </span>
        </div>

        <div class="flavor-formulario-grupo">
            <label for="flavor-compostera" class="flavor-label">
                Compostera <span class="flavor-requerido">*</span>
            </label>
            <select
                id="flavor-compostera"
                name="compostera_id"
                class="flavor-select"
                required
                aria-describedby="flavor-compostera-ayuda"
            >
                <option value="">Selecciona la compostera</option>
                <?php foreach ($composteras as $compostera) : ?>
                    <option value="<?php echo esc_attr($compostera['id']); ?>">
                        <?php echo esc_html($compostera['nombre']); ?> - <?php echo esc_html($compostera['ubicacion']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="flavor-compostera-ayuda" class="flavor-ayuda">
                Selecciona la compostera donde depositarás los residuos.
            </span>
        </div>

        <div class="flavor-formulario-grupo">
            <label for="flavor-fecha" class="flavor-label">
                Fecha de aportación
            </label>
            <input
                type="date"
                id="flavor-fecha"
                name="fecha"
                class="flavor-input"
                value="<?php echo esc_attr(date('Y-m-d')); ?>"
                max="<?php echo esc_attr(date('Y-m-d')); ?>"
            >
        </div>

        <div class="flavor-formulario-grupo">
            <label for="flavor-notas" class="flavor-label">
                Notas adicionales
            </label>
            <textarea
                id="flavor-notas"
                name="notas"
                class="flavor-textarea"
                rows="3"
                placeholder="Añade cualquier observación relevante..."
                maxlength="500"
            ></textarea>
        </div>

        <div class="flavor-formulario-acciones">
            <button type="submit" class="flavor-boton flavor-boton-primario">
                <span class="flavor-boton-icono">📝</span>
                Registrar aportación
            </button>
            <button type="reset" class="flavor-boton flavor-boton-secundario">
                Limpiar formulario
            </button>
        </div>
    </form>

    <div class="flavor-formulario-info">
        <h3 class="flavor-info-titulo">¿Qué puedes compostar?</h3>
        <div class="flavor-info-columnas">
            <div class="flavor-info-columna flavor-info-permitido">
                <h4>✅ Permitido</h4>
                <ul>
                    <li>Restos de frutas y verduras</li>
                    <li>Cáscaras de huevo trituradas</li>
                    <li>Posos de café y bolsas de té</li>
                    <li>Hojas secas y césped</li>
                    <li>Papel y cartón sin tintas</li>
                </ul>
            </div>
            <div class="flavor-info-columna flavor-info-prohibido">
                <h4>❌ No permitido</h4>
                <ul>
                    <li>Carnes y pescados</li>
                    <li>Lácteos y grasas</li>
                    <li>Plantas enfermas</li>
                    <li>Excrementos de mascotas</li>
                    <li>Materiales no biodegradables</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-compostaje-formulario {
    max-width: 600px;
    margin: 0 auto;
    padding: 1.5rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.flavor-formulario-header {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e8f5e9;
}

.flavor-formulario-titulo {
    font-size: 1.5rem;
    color: #2e7d32;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.flavor-icono-hoja {
    font-size: 1.8rem;
}

.flavor-formulario-descripcion {
    color: #666;
    margin: 0;
    font-size: 0.95rem;
}

.flavor-mensaje {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-mensaje-exito {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.flavor-mensaje-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.flavor-mensaje-icono {
    font-size: 1.25rem;
    font-weight: bold;
}

.flavor-formulario {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.flavor-formulario-grupo {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.flavor-label {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

.flavor-requerido {
    color: #c62828;
}

.flavor-input,
.flavor-select,
.flavor-textarea {
    padding: 0.75rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    width: 100%;
    box-sizing: border-box;
}

.flavor-input:focus,
.flavor-select:focus,
.flavor-textarea:focus {
    outline: none;
    border-color: #4caf50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
}

.flavor-input-grupo {
    position: relative;
    display: flex;
    align-items: center;
}

.flavor-input-grupo .flavor-input {
    padding-right: 3rem;
}

.flavor-input-sufijo {
    position: absolute;
    right: 1rem;
    color: #666;
    font-weight: 500;
}

.flavor-ayuda {
    font-size: 0.85rem;
    color: #757575;
}

.flavor-textarea {
    resize: vertical;
    min-height: 80px;
}

.flavor-formulario-acciones {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
}

.flavor-boton {
    flex: 1;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.flavor-boton-primario {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    color: #fff;
}

.flavor-boton-primario:hover {
    background: linear-gradient(135deg, #43a047, #1b5e20);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
}

.flavor-boton-secundario {
    background: #f5f5f5;
    color: #666;
    border: 2px solid #e0e0e0;
}

.flavor-boton-secundario:hover {
    background: #eeeeee;
    border-color: #bdbdbd;
}

.flavor-formulario-info {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e8f5e9;
}

.flavor-info-titulo {
    font-size: 1.1rem;
    color: #333;
    margin: 0 0 1rem;
    text-align: center;
}

.flavor-info-columnas {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.flavor-info-columna h4 {
    font-size: 0.95rem;
    margin: 0 0 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid currentColor;
}

.flavor-info-permitido h4 {
    color: #2e7d32;
}

.flavor-info-prohibido h4 {
    color: #c62828;
}

.flavor-info-columna ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-info-columna li {
    padding: 0.35rem 0;
    font-size: 0.9rem;
    color: #555;
}

/* Responsive */
@media (max-width: 600px) {
    .flavor-compostaje-formulario {
        padding: 1rem;
        border-radius: 8px;
    }

    .flavor-formulario-titulo {
        font-size: 1.25rem;
        flex-direction: column;
    }

    .flavor-formulario-acciones {
        flex-direction: column;
    }

    .flavor-info-columnas {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>
