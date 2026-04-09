<?php
/**
 * Template: Calculadora de Huella Ecológica
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = Flavor_Chat_Huella_Ecologica_Module::CATEGORIAS_HUELLA;
?>

<div class="he-container">
    <header class="he-header">
        <h2>
            <span class="dashicons dashicons-palmtree"></span>
            <?php esc_html_e('Calculadora de Huella Ecológica', 'flavor-platform'); ?>
        </h2>
        <p><?php esc_html_e('Descubre tu impacto ambiental diario y cómo reducirlo', 'flavor-platform'); ?></p>
    </header>

    <div class="he-calculadora">
        <!-- Indicadores de pasos -->
        <div class="he-calculadora__pasos">
            <div class="he-paso activo" data-paso="1">
                <span class="he-paso__numero">1</span>
                <span><?php esc_html_e('Transporte', 'flavor-platform'); ?></span>
            </div>
            <div class="he-paso" data-paso="2">
                <span class="he-paso__numero">2</span>
                <span><?php esc_html_e('Energía', 'flavor-platform'); ?></span>
            </div>
            <div class="he-paso" data-paso="3">
                <span class="he-paso__numero">3</span>
                <span><?php esc_html_e('Alimentación', 'flavor-platform'); ?></span>
            </div>
            <div class="he-paso" data-paso="4">
                <span class="he-paso__numero">4</span>
                <span><?php esc_html_e('Consumo', 'flavor-platform'); ?></span>
            </div>
        </div>

        <form class="he-form-calculadora">
            <!-- Paso 1: Transporte -->
            <section class="he-calculadora__seccion activa" data-paso="1">
                <div class="he-categoria-header" style="color: <?php echo esc_attr($categorias['transporte']['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($categorias['transporte']['icono']); ?>"></span>
                    <h3><?php esc_html_e('Transporte', 'flavor-platform'); ?></h3>
                </div>

                <div class="he-form-row">
                    <div class="he-form-grupo">
                        <label for="km_coche"><?php esc_html_e('Kilómetros en coche/moto por semana', 'flavor-platform'); ?></label>
                        <div class="he-input-con-unidad">
                            <input type="number" name="km_coche" id="km_coche" min="0" step="1" value="0">
                            <span class="he-unidad">km</span>
                        </div>
                    </div>
                    <div class="he-form-grupo">
                        <label for="km_avion"><?php esc_html_e('Kilómetros en avión al año', 'flavor-platform'); ?></label>
                        <div class="he-input-con-unidad">
                            <input type="number" name="km_avion" id="km_avion" min="0" step="100" value="0">
                            <span class="he-unidad">km</span>
                        </div>
                    </div>
                </div>

                <div class="he-form-grupo">
                    <label for="transporte_principal"><?php esc_html_e('Tu medio de transporte principal', 'flavor-platform'); ?></label>
                    <select name="transporte_principal" id="transporte_principal">
                        <option value="coche_solo"><?php esc_html_e('Coche (solo/a)', 'flavor-platform'); ?></option>
                        <option value="coche_compartido"><?php esc_html_e('Coche compartido', 'flavor-platform'); ?></option>
                        <option value="moto"><?php esc_html_e('Moto/Scooter', 'flavor-platform'); ?></option>
                        <option value="transporte_publico"><?php esc_html_e('Transporte público', 'flavor-platform'); ?></option>
                        <option value="bicicleta"><?php esc_html_e('Bicicleta', 'flavor-platform'); ?></option>
                        <option value="andando"><?php esc_html_e('Caminar', 'flavor-platform'); ?></option>
                        <option value="electrico"><?php esc_html_e('Vehículo eléctrico', 'flavor-platform'); ?></option>
                    </select>
                </div>
            </section>

            <!-- Paso 2: Energía -->
            <section class="he-calculadora__seccion" data-paso="2">
                <div class="he-categoria-header" style="color: <?php echo esc_attr($categorias['energia']['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($categorias['energia']['icono']); ?>"></span>
                    <h3><?php esc_html_e('Energía en el hogar', 'flavor-platform'); ?></h3>
                </div>

                <div class="he-form-row">
                    <div class="he-form-grupo">
                        <label for="kwh_mes"><?php esc_html_e('Consumo eléctrico mensual', 'flavor-platform'); ?></label>
                        <div class="he-input-con-unidad">
                            <input type="number" name="kwh_mes" id="kwh_mes" min="0" step="10" value="200">
                            <span class="he-unidad">kWh</span>
                        </div>
                        <p class="description"><?php esc_html_e('Lo encuentras en tu factura de luz', 'flavor-platform'); ?></p>
                    </div>
                    <div class="he-form-grupo">
                        <label for="gas_mes"><?php esc_html_e('Consumo de gas mensual', 'flavor-platform'); ?></label>
                        <div class="he-input-con-unidad">
                            <input type="number" name="gas_mes" id="gas_mes" min="0" step="5" value="0">
                            <span class="he-unidad">m³</span>
                        </div>
                    </div>
                </div>

                <div class="he-form-grupo">
                    <label for="energia_renovable"><?php esc_html_e('¿Usas energía renovable?', 'flavor-platform'); ?></label>
                    <select name="energia_renovable" id="energia_renovable">
                        <option value="no"><?php esc_html_e('No / No lo sé', 'flavor-platform'); ?></option>
                        <option value="parcial"><?php esc_html_e('Parcialmente', 'flavor-platform'); ?></option>
                        <option value="si"><?php esc_html_e('Sí, 100% renovable', 'flavor-platform'); ?></option>
                        <option value="autoconsumo"><?php esc_html_e('Autoconsumo (paneles solares)', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="he-form-grupo">
                    <label for="calefaccion"><?php esc_html_e('Sistema de calefacción principal', 'flavor-platform'); ?></label>
                    <select name="calefaccion" id="calefaccion">
                        <option value="gas_natural"><?php esc_html_e('Gas natural', 'flavor-platform'); ?></option>
                        <option value="electrica"><?php esc_html_e('Eléctrica', 'flavor-platform'); ?></option>
                        <option value="bomba_calor"><?php esc_html_e('Bomba de calor / Aerotermia', 'flavor-platform'); ?></option>
                        <option value="pellets"><?php esc_html_e('Pellets / Biomasa', 'flavor-platform'); ?></option>
                        <option value="gasoil"><?php esc_html_e('Gasóleo', 'flavor-platform'); ?></option>
                        <option value="no_tengo"><?php esc_html_e('No tengo calefacción', 'flavor-platform'); ?></option>
                    </select>
                </div>
            </section>

            <!-- Paso 3: Alimentación -->
            <section class="he-calculadora__seccion" data-paso="3">
                <div class="he-categoria-header" style="color: <?php echo esc_attr($categorias['alimentacion']['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($categorias['alimentacion']['icono']); ?>"></span>
                    <h3><?php esc_html_e('Alimentación', 'flavor-platform'); ?></h3>
                </div>

                <div class="he-form-grupo">
                    <label for="tipo_dieta"><?php esc_html_e('Tu tipo de dieta', 'flavor-platform'); ?></label>
                    <select name="tipo_dieta" id="tipo_dieta">
                        <option value="omnivora"><?php esc_html_e('Omnívora (como de todo)', 'flavor-platform'); ?></option>
                        <option value="flexitariana"><?php esc_html_e('Flexitariana (poca carne)', 'flavor-platform'); ?></option>
                        <option value="vegetariana"><?php esc_html_e('Vegetariana', 'flavor-platform'); ?></option>
                        <option value="vegana"><?php esc_html_e('Vegana', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="he-form-row">
                    <div class="he-form-grupo">
                        <label for="consumo_carne"><?php esc_html_e('Días con carne a la semana', 'flavor-platform'); ?></label>
                        <input type="number" name="consumo_carne" id="consumo_carne" min="0" max="7" value="4">
                    </div>
                    <div class="he-form-grupo">
                        <label for="comida_local"><?php esc_html_e('% de comida local/km0', 'flavor-platform'); ?></label>
                        <select name="comida_local" id="comida_local">
                            <option value="0"><?php esc_html_e('Casi nada', 'flavor-platform'); ?></option>
                            <option value="25"><?php esc_html_e('~25%', 'flavor-platform'); ?></option>
                            <option value="50"><?php esc_html_e('~50%', 'flavor-platform'); ?></option>
                            <option value="75"><?php esc_html_e('~75%', 'flavor-platform'); ?></option>
                            <option value="100"><?php esc_html_e('Casi todo local', 'flavor-platform'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="he-form-grupo">
                    <label for="desperdicio"><?php esc_html_e('¿Cuánta comida desperdicias?', 'flavor-platform'); ?></label>
                    <select name="desperdicio" id="desperdicio">
                        <option value="mucho"><?php esc_html_e('Bastante (tiro comida frecuentemente)', 'flavor-platform'); ?></option>
                        <option value="algo"><?php esc_html_e('Algo (a veces sobra comida)', 'flavor-platform'); ?></option>
                        <option value="poco"><?php esc_html_e('Poco (aprovecho casi todo)', 'flavor-platform'); ?></option>
                        <option value="nada"><?php esc_html_e('Nada (zero waste)', 'flavor-platform'); ?></option>
                    </select>
                </div>
            </section>

            <!-- Paso 4: Consumo -->
            <section class="he-calculadora__seccion" data-paso="4">
                <div class="he-categoria-header" style="color: <?php echo esc_attr($categorias['consumo']['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($categorias['consumo']['icono']); ?>"></span>
                    <h3><?php esc_html_e('Consumo y residuos', 'flavor-platform'); ?></h3>
                </div>

                <div class="he-form-grupo">
                    <label for="compras_nuevas"><?php esc_html_e('Compras de productos nuevos al mes', 'flavor-platform'); ?></label>
                    <select name="compras_nuevas" id="compras_nuevas">
                        <option value="1"><?php esc_html_e('Casi nada (solo necesidades)', 'flavor-platform'); ?></option>
                        <option value="3"><?php esc_html_e('Pocas (compro poco)', 'flavor-platform'); ?></option>
                        <option value="5"><?php esc_html_e('Moderadas', 'flavor-platform'); ?></option>
                        <option value="8"><?php esc_html_e('Bastantes', 'flavor-platform'); ?></option>
                        <option value="12"><?php esc_html_e('Muchas (compras frecuentes)', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="he-form-row">
                    <div class="he-form-grupo">
                        <label for="ropa_nueva"><?php esc_html_e('Prendas de ropa nuevas al año', 'flavor-platform'); ?></label>
                        <input type="number" name="ropa_nueva" id="ropa_nueva" min="0" value="12">
                    </div>
                    <div class="he-form-grupo">
                        <label for="electronica"><?php esc_html_e('Dispositivos electrónicos nuevos al año', 'flavor-platform'); ?></label>
                        <input type="number" name="electronica" id="electronica" min="0" value="2">
                    </div>
                </div>

                <div class="he-form-grupo">
                    <label for="reciclaje"><?php esc_html_e('¿Reciclas?', 'flavor-platform'); ?></label>
                    <select name="reciclaje" id="reciclaje">
                        <option value="no"><?php esc_html_e('No / Rara vez', 'flavor-platform'); ?></option>
                        <option value="algo"><?php esc_html_e('A veces', 'flavor-platform'); ?></option>
                        <option value="si"><?php esc_html_e('Sí, separo los residuos', 'flavor-platform'); ?></option>
                        <option value="compostar"><?php esc_html_e('Sí, y compostar orgánico', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="he-form-grupo">
                    <label for="agua_consumo"><?php esc_html_e('Consumo de agua', 'flavor-platform'); ?></label>
                    <select name="agua_consumo" id="agua_consumo">
                        <option value="alto"><?php esc_html_e('Alto (duchas largas, riego frecuente)', 'flavor-platform'); ?></option>
                        <option value="medio"><?php esc_html_e('Medio', 'flavor-platform'); ?></option>
                        <option value="bajo"><?php esc_html_e('Bajo (ahorro activamente)', 'flavor-platform'); ?></option>
                        <option value="muy_bajo"><?php esc_html_e('Muy bajo (reutilizo agua, recojo lluvia)', 'flavor-platform'); ?></option>
                    </select>
                </div>
            </section>

            <!-- Navegación -->
            <div class="he-calculadora__acciones">
                <button type="button" class="he-btn he-btn--secondary he-btn-anterior" style="display: none;">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Anterior', 'flavor-platform'); ?>
                </button>
                <button type="button" class="he-btn he-btn--primary he-btn-siguiente">
                    <?php esc_html_e('Siguiente', 'flavor-platform'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
                <button type="button" class="he-btn he-btn--primary he-btn-calcular" style="display: none;">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Calcular mi huella', 'flavor-platform'); ?>
                </button>
            </div>
        </form>

        <!-- Resultado -->
        <div class="he-resultado" style="display: none;">
            <div class="he-resultado__huella">0<small> kg CO2/día</small></div>
            <div class="he-resultado__label"><?php esc_html_e('Tu huella ecológica diaria estimada', 'flavor-platform'); ?></div>

            <div class="he-resultado__comparativa">
                <div class="he-comparativa-item">
                    <div class="he-comparativa-item__valor">0 kg</div>
                    <div class="he-comparativa-item__label"><?php esc_html_e('Por mes', 'flavor-platform'); ?></div>
                </div>
                <div class="he-comparativa-item">
                    <div class="he-comparativa-item__valor">0 kg</div>
                    <div class="he-comparativa-item__label"><?php esc_html_e('Por año', 'flavor-platform'); ?></div>
                </div>
                <div class="he-comparativa-item">
                    <div class="he-comparativa-item__valor">7.5 kg</div>
                    <div class="he-comparativa-item__label"><?php esc_html_e('Media España', 'flavor-platform'); ?></div>
                </div>
            </div>

            <div class="he-resultado__desglose">
                <!-- Se llena dinámicamente -->
            </div>

            <?php if (is_user_logged_in()) : ?>
            <div style="margin-top: 2rem;">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huella_ecologica', 'mis-registros')); ?>" class="he-btn he-btn--secondary">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Ver mi historial', 'flavor-platform'); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huella_ecologica', 'acciones')); ?>" class="he-btn" style="background: white; color: var(--he-primary);">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Empezar a reducir', 'flavor-platform'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
