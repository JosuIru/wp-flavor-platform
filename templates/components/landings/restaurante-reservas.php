<?php
/**
 * Template para Restaurante - Formulario de Reservas
 *
 * Variables: $titulo, $horarios, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Reserva tu mesa', FLAVOR_PLATFORM_TEXT_DOMAIN);
$color_primario = $color_primario ?? '#f56e28';

$horarios = $horarios ?? [
    'comida' => ['inicio' => '13:00', 'fin' => '16:00'],
    'cena' => ['inicio' => '20:00', 'fin' => '23:30'],
];
?>

<section class="flavor-restaurante-reservas" id="formulario-reserva" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-reservas-wrapper">
            <div class="flavor-reservas-info">
                <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
                <p class="flavor-reservas-descripcion">
                    <?php esc_html_e('Reserva con antelación para asegurar tu mesa. Para grupos de más de 8 personas, contáctanos por teléfono.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>

                <div class="flavor-horarios">
                    <h3><?php esc_html_e('Horarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-horario-item">
                        <span class="flavor-horario-turno"><?php esc_html_e('Comidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-horario-horas"><?php echo esc_html($horarios['comida']['inicio'] . ' - ' . $horarios['comida']['fin']); ?></span>
                    </div>
                    <div class="flavor-horario-item">
                        <span class="flavor-horario-turno"><?php esc_html_e('Cenas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-horario-horas"><?php echo esc_html($horarios['cena']['inicio'] . ' - ' . $horarios['cena']['fin']); ?></span>
                    </div>
                </div>

                <div class="flavor-contacto-rapido">
                    <a href="tel:+34900000000" class="flavor-telefono">
                        <span class="dashicons dashicons-phone"></span>
                        900 000 000
                    </a>
                    <a href="https://wa.me/34600000000" class="flavor-whatsapp" target="_blank" rel="noopener">
                        <span class="dashicons dashicons-whatsapp"></span>
                        WhatsApp
                    </a>
                </div>
            </div>

            <div class="flavor-reservas-form">
                <form class="flavor-form-reserva" action="" method="post" data-source="landing">
                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="reserva-fecha"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="date" id="reserva-fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="flavor-form-group">
                            <label for="reserva-hora"><?php esc_html_e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="reserva-hora" name="hora" required>
                                <option value=""><?php esc_html_e('Seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <optgroup label="<?php esc_attr_e('Comidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <option value="13:00">13:00</option>
                                    <option value="13:30">13:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Cenas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <option value="20:00">20:00</option>
                                    <option value="20:30">20:30</option>
                                    <option value="21:00">21:00</option>
                                    <option value="21:30">21:30</option>
                                    <option value="22:00">22:00</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="reserva-personas"><?php esc_html_e('Número de personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <div class="flavor-personas-selector">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <label class="flavor-persona-option">
                                    <input type="radio" name="personas" value="<?php echo $i; ?>" <?php echo $i === 2 ? 'checked' : ''; ?>>
                                    <span><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group">
                            <label for="reserva-nombre"><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="reserva-nombre" name="nombre" required>
                        </div>
                        <div class="flavor-form-group">
                            <label for="reserva-telefono"><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="tel" id="reserva-telefono" name="telefono" required>
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="reserva-email"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="email" id="reserva-email" name="email" required>
                    </div>

                    <div class="flavor-form-group">
                        <label for="reserva-notas"><?php esc_html_e('Notas especiales (alergias, celebraciones...)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="reserva-notas" name="notas" rows="2"></textarea>
                    </div>

                    <button type="submit" class="flavor-submit-reserva">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Confirmar Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>

                    <p class="flavor-form-nota">
                        <?php esc_html_e('Recibirás un email de confirmación. Si no puedes asistir, cancela con al menos 2 horas de antelación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>

                    <div class="flavor-form-message" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-restaurante-reservas {
    padding: 5rem 0;
    background: #1f2937;
    color: white;
}
.flavor-reservas-wrapper {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 3rem;
    align-items: start;
}
.flavor-reservas-info .flavor-section-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem;
    color: white;
}
.flavor-reservas-descripcion {
    color: #9ca3af;
    margin: 0 0 2rem;
    line-height: 1.6;
}
.flavor-horarios h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.75rem;
    color: #d1d5db;
}
.flavor-horario-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #374151;
}
.flavor-horario-turno {
    color: #9ca3af;
}
.flavor-horario-horas {
    font-weight: 600;
}
.flavor-contacto-rapido {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
.flavor-telefono,
.flavor-whatsapp {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s;
}
.flavor-telefono {
    background: #374151;
    color: white;
}
.flavor-whatsapp {
    background: #25d366;
    color: white;
}
.flavor-telefono:hover,
.flavor-whatsapp:hover {
    transform: translateY(-2px);
}
.flavor-reservas-form {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    color: #1f2937;
}
.flavor-form-reserva {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.flavor-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.flavor-form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}
.flavor-form-group input,
.flavor-form-group select,
.flavor-form-group textarea {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}
.flavor-form-group input:focus,
.flavor-form-group select:focus,
.flavor-form-group textarea:focus {
    outline: none;
    border-color: var(--color-primario);
}
.flavor-personas-selector {
    display: flex;
    gap: 0.5rem;
}
.flavor-persona-option {
    flex: 1;
}
.flavor-persona-option input {
    display: none;
}
.flavor-persona-option span {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background: #f3f4f6;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-persona-option input:checked + span {
    background: var(--color-primario);
    color: white;
}
.flavor-persona-option:hover span {
    background: #e5e7eb;
}
.flavor-persona-option input:checked:hover + span {
    background: var(--color-primario);
}
.flavor-submit-reserva {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--color-primario);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: filter 0.2s, transform 0.2s;
}
.flavor-submit-reserva:hover {
    filter: brightness(1.1);
    transform: translateY(-2px);
}
.flavor-form-nota {
    font-size: 0.8125rem;
    color: #6b7280;
    text-align: center;
    margin: 0;
}
@media (max-width: 768px) {
    .flavor-restaurante-reservas {
        padding: 3rem 0;
    }
    .flavor-reservas-wrapper {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    .flavor-form-row {
        grid-template-columns: 1fr;
    }
    .flavor-contacto-rapido {
        flex-direction: column;
    }
}
</style>
