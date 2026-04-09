<?php
/**
 * Formulario de reserva para frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-reserva-formulario-wrapper">
    <form class="flavor-reserva-formulario" id="flavor-reserva-form" method="post">
        <?php wp_nonce_field('flavor_reserva_nonce', 'flavor_reserva_nonce_field'); ?>

        <h3 class="flavor-reserva-titulo">
            <?php esc_html_e('Hacer una Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>

        <div class="flavor-reserva-campo">
            <label for="flavor-reserva-tipo-servicio">
                <?php esc_html_e('Tipo de servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <select id="flavor-reserva-tipo-servicio" name="tipo_servicio" required>
                <option value="mesa_restaurante"><?php esc_html_e('Mesa de Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="espacio_coworking"><?php esc_html_e('Espacio Coworking', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="clase_deportiva"><?php esc_html_e('Clase Deportiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>

        <div class="flavor-reserva-fila">
            <div class="flavor-reserva-campo flavor-reserva-campo--medio">
                <label for="flavor-reserva-fecha">
                    <?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <input type="date" id="flavor-reserva-fecha" name="fecha_reserva"
                       min="<?php echo esc_attr(date('Y-m-d')); ?>"
                       max="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>"
                       required>
            </div>

            <div class="flavor-reserva-campo flavor-reserva-campo--medio">
                <label for="flavor-reserva-hora">
                    <?php esc_html_e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <input type="time" id="flavor-reserva-hora" name="hora_inicio"
                       min="09:00" max="22:00" required>
            </div>
        </div>

        <div class="flavor-reserva-campo">
            <label for="flavor-reserva-personas">
                <?php esc_html_e('Numero de personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <input type="number" id="flavor-reserva-personas" name="num_personas"
                   min="1" max="50" value="1" required>
        </div>

        <div class="flavor-reserva-campo">
            <label for="flavor-reserva-nombre">
                <?php esc_html_e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <input type="text" id="flavor-reserva-nombre" name="nombre_cliente"
                   placeholder="<?php esc_attr_e('Tu nombre y apellidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                   required>
        </div>

        <div class="flavor-reserva-fila">
            <div class="flavor-reserva-campo flavor-reserva-campo--medio">
                <label for="flavor-reserva-email">
                    <?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <input type="email" id="flavor-reserva-email" name="email_cliente"
                       placeholder="<?php esc_attr_e('tu@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       required>
            </div>

            <div class="flavor-reserva-campo flavor-reserva-campo--medio">
                <label for="flavor-reserva-telefono">
                    <?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <input type="tel" id="flavor-reserva-telefono" name="telefono_cliente"
                       placeholder="<?php esc_attr_e('600 123 456', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
        </div>

        <div class="flavor-reserva-campo">
            <label for="flavor-reserva-notas">
                <?php esc_html_e('Notas adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <textarea id="flavor-reserva-notas" name="notas" rows="3"
                      placeholder="<?php esc_attr_e('Alergias, preferencias, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
        </div>

        <div class="flavor-reserva-submit">
            <button type="submit" class="flavor-reserva-boton">
                <?php esc_html_e('Confirmar Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <div class="flavor-reserva-mensaje" id="flavor-reserva-mensaje" style="display:none;"></div>
    </form>
</div>

<style>
.flavor-reserva-formulario-wrapper {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}
.flavor-reserva-formulario {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
}
.flavor-reserva-titulo {
    margin: 0 0 20px;
    font-size: 1.4em;
    color: #333;
    text-align: center;
}
.flavor-reserva-campo {
    margin-bottom: 16px;
}
.flavor-reserva-campo label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 0.9em;
    color: #555;
}
.flavor-reserva-campo input,
.flavor-reserva-campo select,
.flavor-reserva-campo textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1em;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.flavor-reserva-campo input:focus,
.flavor-reserva-campo select:focus,
.flavor-reserva-campo textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.15);
}
.flavor-reserva-fila {
    display: flex;
    gap: 16px;
}
.flavor-reserva-campo--medio {
    flex: 1;
}
.flavor-reserva-submit {
    margin-top: 20px;
    text-align: center;
}
.flavor-reserva-boton {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 12px 32px;
    font-size: 1em;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}
.flavor-reserva-boton:hover {
    background: #005a87;
}
.flavor-reserva-mensaje {
    margin-top: 16px;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    font-size: 0.95em;
}
.flavor-reserva-mensaje--exito {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.flavor-reserva-mensaje--error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
@media (max-width: 480px) {
    .flavor-reserva-fila {
        flex-direction: column;
        gap: 0;
    }
    .flavor-reserva-formulario {
        padding: 16px;
    }
}
</style>
