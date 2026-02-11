<?php
/**
 * Template: Formulario para solicitar plaza de parking
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Parkings
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Solicitar Plaza de Parking';
$parking_id = isset($args['parking_id']) ? $args['parking_id'] : null;
$parking_nombre = isset($args['parking_nombre']) ? $args['parking_nombre'] : '';
$mostrar_selector_parking = isset($args['mostrar_selector_parking']) ? $args['mostrar_selector_parking'] : true;
$paso_actual = isset($args['paso_actual']) ? $args['paso_actual'] : 1;
$total_pasos = isset($args['total_pasos']) ? $args['total_pasos'] : 4;

// Datos de demostración - parkings disponibles
$parkings_disponibles = isset($args['parkings_disponibles']) ? $args['parkings_disponibles'] : array(
    array(
        'id' => 1,
        'nombre' => 'Parking Residencial Las Flores',
        'direccion' => 'Calle Mayor 45, Madrid',
        'plazas_disponibles' => 12,
        'precio_hora' => 1.50,
        'precio_dia' => 15.00,
        'precio_mes' => 120.00
    ),
    array(
        'id' => 2,
        'nombre' => 'Parking Vecinal Centro',
        'direccion' => 'Calle Gran Vía 78, Madrid',
        'plazas_disponibles' => 25,
        'precio_hora' => 1.80,
        'precio_dia' => 16.00,
        'precio_mes' => 135.00
    ),
    array(
        'id' => 3,
        'nombre' => 'Parking Comunidad Verde',
        'direccion' => 'Calle del Prado 56, Madrid',
        'plazas_disponibles' => 18,
        'precio_hora' => 1.60,
        'precio_dia' => 14.50,
        'precio_mes' => 110.00
    ),
    array(
        'id' => 4,
        'nombre' => 'Parking Plaza Mayor',
        'direccion' => 'Plaza Mayor 2, Madrid',
        'plazas_disponibles' => 35,
        'precio_hora' => 2.50,
        'precio_dia' => 22.00,
        'precio_mes' => 180.00
    )
);

// Tipos de reserva disponibles
$tipos_reserva = array(
    'horas' => array(
        'label' => 'Por horas',
        'descripcion' => 'Ideal para visitas cortas',
        'icono' => '&#9201;'
    ),
    'dia' => array(
        'label' => 'Día completo',
        'descripcion' => 'De 08:00 a 22:00',
        'icono' => '&#9728;'
    ),
    'mensual' => array(
        'label' => 'Mensual',
        'descripcion' => 'Acceso ilimitado 24/7',
        'icono' => '&#128197;'
    )
);

// Tipos de vehículo
$tipos_vehiculo = array(
    'coche' => array('label' => 'Coche', 'icono' => '&#128663;'),
    'moto' => array('label' => 'Moto', 'icono' => '&#127949;'),
    'furgoneta' => array('label' => 'Furgoneta', 'icono' => '&#128656;'),
    'electrico' => array('label' => 'Eléctrico', 'icono' => '&#9889;')
);

// Horarios preferidos
$horarios_preferidos = array(
    'manana' => 'Mañana (08:00 - 14:00)',
    'tarde' => 'Tarde (14:00 - 20:00)',
    'noche' => 'Noche (20:00 - 08:00)',
    'completo' => 'Día completo',
    'flexible' => 'Horario flexible'
);

// Datos de vehículos guardados (demo)
$vehiculos_guardados = isset($args['vehiculos_guardados']) ? $args['vehiculos_guardados'] : array(
    array(
        'id' => 1,
        'matricula' => '1234 ABC',
        'marca' => 'Toyota',
        'modelo' => 'Corolla',
        'color' => 'Gris',
        'tipo' => 'coche',
        'predeterminado' => true
    ),
    array(
        'id' => 2,
        'matricula' => '5678 XYZ',
        'marca' => 'Volkswagen',
        'modelo' => 'Golf',
        'color' => 'Azul',
        'tipo' => 'coche',
        'predeterminado' => false
    )
);
?>

<div class="flavor-solicitar-parking-container">
    <div class="flavor-solicitar-header">
        <h2 class="flavor-solicitar-titulo"><?php echo esc_html($titulo); ?></h2>

        <!-- Indicador de progreso -->
        <div class="flavor-solicitar-progreso">
            <?php
            $etiquetas_pasos = array('Parking', 'Vehículo', 'Horario', 'Confirmar');
            for ($i = 1; $i <= $total_pasos; $i++) :
                $clase_paso = $i < $paso_actual ? 'completado' : ($i === $paso_actual ? 'activo' : '');
            ?>
                <div class="flavor-solicitar-paso <?php echo esc_attr($clase_paso); ?>">
                    <div class="flavor-solicitar-paso-numero">
                        <?php if ($i < $paso_actual) : ?>
                            <span>&#10003;</span>
                        <?php else : ?>
                            <?php echo $i; ?>
                        <?php endif; ?>
                    </div>
                    <span class="flavor-solicitar-paso-label"><?php echo esc_html($etiquetas_pasos[$i - 1]); ?></span>
                </div>
                <?php if ($i < $total_pasos) : ?>
                    <div class="flavor-solicitar-paso-linea <?php echo $i < $paso_actual ? 'completado' : ''; ?>"></div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>

    <form class="flavor-solicitar-form" id="flavor-solicitar-form">
        <!-- PASO 1: Selección de Parking y Tipo de Reserva -->
        <div class="flavor-solicitar-seccion" data-paso="1">
            <h3 class="flavor-solicitar-seccion-titulo">
                <span class="flavor-solicitar-seccion-icono">&#128205;</span>
                Selecciona el parking y tipo de reserva
            </h3>

            <?php if ($mostrar_selector_parking) : ?>
                <div class="flavor-solicitar-campo">
                    <label class="flavor-solicitar-label">Parking <span class="requerido">*</span></label>
                    <div class="flavor-solicitar-parkings-lista">
                        <?php foreach ($parkings_disponibles as $parking) : ?>
                            <label class="flavor-solicitar-parking-opcion">
                                <input type="radio" name="parking_id" value="<?php echo esc_attr($parking['id']); ?>" <?php echo $parking_id == $parking['id'] ? 'checked' : ''; ?>>
                                <div class="flavor-solicitar-parking-card">
                                    <div class="flavor-solicitar-parking-info">
                                        <span class="flavor-solicitar-parking-nombre"><?php echo esc_html($parking['nombre']); ?></span>
                                        <span class="flavor-solicitar-parking-direccion"><?php echo esc_html($parking['direccion']); ?></span>
                                    </div>
                                    <div class="flavor-solicitar-parking-precios">
                                        <span class="flavor-solicitar-parking-precio"><?php echo number_format($parking['precio_hora'], 2); ?>€/h</span>
                                        <span class="flavor-solicitar-parking-disponible"><?php echo esc_html($parking['plazas_disponibles']); ?> plazas</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <input type="hidden" name="parking_id" value="<?php echo esc_attr($parking_id); ?>">
                <div class="flavor-solicitar-parking-seleccionado">
                    <span class="flavor-solicitar-icon-parking">&#127359;</span>
                    <span><?php echo esc_html($parking_nombre); ?></span>
                </div>
            <?php endif; ?>

            <div class="flavor-solicitar-campo">
                <label class="flavor-solicitar-label">Tipo de reserva <span class="requerido">*</span></label>
                <div class="flavor-solicitar-tipos-reserva">
                    <?php foreach ($tipos_reserva as $clave_tipo => $tipo_info) : ?>
                        <label class="flavor-solicitar-tipo-opcion">
                            <input type="radio" name="tipo_reserva" value="<?php echo esc_attr($clave_tipo); ?>" <?php echo $clave_tipo === 'dia' ? 'checked' : ''; ?>>
                            <div class="flavor-solicitar-tipo-card">
                                <span class="flavor-solicitar-tipo-icono"><?php echo $tipo_info['icono']; ?></span>
                                <span class="flavor-solicitar-tipo-label"><?php echo esc_html($tipo_info['label']); ?></span>
                                <span class="flavor-solicitar-tipo-desc"><?php echo esc_html($tipo_info['descripcion']); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flavor-solicitar-fechas-row">
                <div class="flavor-solicitar-campo">
                    <label for="fecha_inicio" class="flavor-solicitar-label">Fecha de entrada <span class="requerido">*</span></label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="flavor-solicitar-input" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="flavor-solicitar-campo" id="campo_fecha_fin">
                    <label for="fecha_fin" class="flavor-solicitar-label">Fecha de salida <span class="requerido">*</span></label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="flavor-solicitar-input" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="flavor-solicitar-horas-row" id="campos_horas">
                <div class="flavor-solicitar-campo">
                    <label for="hora_entrada" class="flavor-solicitar-label">Hora de entrada</label>
                    <input type="time" id="hora_entrada" name="hora_entrada" class="flavor-solicitar-input" value="08:00">
                </div>

                <div class="flavor-solicitar-campo">
                    <label for="hora_salida" class="flavor-solicitar-label">Hora de salida</label>
                    <input type="time" id="hora_salida" name="hora_salida" class="flavor-solicitar-input" value="20:00">
                </div>
            </div>
        </div>

        <!-- PASO 2: Datos del Vehículo -->
        <div class="flavor-solicitar-seccion" data-paso="2">
            <h3 class="flavor-solicitar-seccion-titulo">
                <span class="flavor-solicitar-seccion-icono">&#128663;</span>
                Datos del vehículo
            </h3>

            <?php if (!empty($vehiculos_guardados)) : ?>
                <div class="flavor-solicitar-campo">
                    <label class="flavor-solicitar-label">Vehículos guardados</label>
                    <div class="flavor-solicitar-vehiculos-guardados">
                        <?php foreach ($vehiculos_guardados as $vehiculo) : ?>
                            <label class="flavor-solicitar-vehiculo-opcion">
                                <input type="radio" name="vehiculo_guardado" value="<?php echo esc_attr($vehiculo['id']); ?>" <?php echo $vehiculo['predeterminado'] ? 'checked' : ''; ?>>
                                <div class="flavor-solicitar-vehiculo-card">
                                    <span class="flavor-solicitar-vehiculo-icono"><?php echo $tipos_vehiculo[$vehiculo['tipo']]['icono'] ?? '&#128663;'; ?></span>
                                    <div class="flavor-solicitar-vehiculo-info">
                                        <span class="flavor-solicitar-vehiculo-matricula"><?php echo esc_html($vehiculo['matricula']); ?></span>
                                        <span class="flavor-solicitar-vehiculo-modelo"><?php echo esc_html($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?></span>
                                    </div>
                                    <?php if ($vehiculo['predeterminado']) : ?>
                                        <span class="flavor-solicitar-badge-predeterminado">Principal</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>

                        <label class="flavor-solicitar-vehiculo-opcion">
                            <input type="radio" name="vehiculo_guardado" value="nuevo">
                            <div class="flavor-solicitar-vehiculo-card flavor-solicitar-vehiculo-nuevo">
                                <span class="flavor-solicitar-vehiculo-icono">+</span>
                                <span>Añadir nuevo vehículo</span>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flavor-solicitar-vehiculo-nuevo-form" id="formulario_nuevo_vehiculo" style="<?php echo empty($vehiculos_guardados) ? '' : 'display: none;'; ?>">
                <div class="flavor-solicitar-campo">
                    <label class="flavor-solicitar-label">Tipo de vehículo <span class="requerido">*</span></label>
                    <div class="flavor-solicitar-tipos-vehiculo">
                        <?php foreach ($tipos_vehiculo as $clave_tipo => $tipo_info) : ?>
                            <label class="flavor-solicitar-tipo-vehiculo-opcion">
                                <input type="radio" name="tipo_vehiculo" value="<?php echo esc_attr($clave_tipo); ?>" <?php echo $clave_tipo === 'coche' ? 'checked' : ''; ?>>
                                <div class="flavor-solicitar-tipo-vehiculo-card">
                                    <span class="flavor-solicitar-tipo-vehiculo-icono"><?php echo $tipo_info['icono']; ?></span>
                                    <span><?php echo esc_html($tipo_info['label']); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flavor-solicitar-campo-row">
                    <div class="flavor-solicitar-campo">
                        <label for="matricula" class="flavor-solicitar-label">Matrícula <span class="requerido">*</span></label>
                        <input type="text" id="matricula" name="matricula" class="flavor-solicitar-input" placeholder="1234 ABC" pattern="[0-9]{4}\s?[A-Z]{3}">
                        <span class="flavor-solicitar-ayuda">Formato: 1234 ABC</span>
                    </div>

                    <div class="flavor-solicitar-campo">
                        <label for="color_vehiculo" class="flavor-solicitar-label">Color <span class="requerido">*</span></label>
                        <select id="color_vehiculo" name="color_vehiculo" class="flavor-solicitar-select">
                            <option value="">Selecciona color</option>
                            <option value="blanco">Blanco</option>
                            <option value="negro">Negro</option>
                            <option value="gris">Gris</option>
                            <option value="azul">Azul</option>
                            <option value="rojo">Rojo</option>
                            <option value="verde">Verde</option>
                            <option value="amarillo">Amarillo</option>
                            <option value="naranja">Naranja</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="flavor-solicitar-campo-row">
                    <div class="flavor-solicitar-campo">
                        <label for="marca_vehiculo" class="flavor-solicitar-label">Marca <span class="requerido">*</span></label>
                        <input type="text" id="marca_vehiculo" name="marca_vehiculo" class="flavor-solicitar-input" placeholder="Ej: Toyota, Volkswagen...">
                    </div>

                    <div class="flavor-solicitar-campo">
                        <label for="modelo_vehiculo" class="flavor-solicitar-label">Modelo <span class="requerido">*</span></label>
                        <input type="text" id="modelo_vehiculo" name="modelo_vehiculo" class="flavor-solicitar-input" placeholder="Ej: Corolla, Golf...">
                    </div>
                </div>

                <div class="flavor-solicitar-campo">
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="guardar_vehiculo" value="1" checked>
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Guardar este vehículo para futuras reservas
                    </label>
                </div>
            </div>
        </div>

        <!-- PASO 3: Horario Preferido y Observaciones -->
        <div class="flavor-solicitar-seccion" data-paso="3">
            <h3 class="flavor-solicitar-seccion-titulo">
                <span class="flavor-solicitar-seccion-icono">&#128339;</span>
                Preferencias y observaciones
            </h3>

            <div class="flavor-solicitar-campo">
                <label class="flavor-solicitar-label">Horario preferido de uso</label>
                <div class="flavor-solicitar-horarios">
                    <?php foreach ($horarios_preferidos as $clave_horario => $etiqueta_horario) : ?>
                        <label class="flavor-solicitar-horario-opcion">
                            <input type="radio" name="horario_preferido" value="<?php echo esc_attr($clave_horario); ?>" <?php echo $clave_horario === 'completo' ? 'checked' : ''; ?>>
                            <span class="flavor-solicitar-horario-label"><?php echo esc_html($etiqueta_horario); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flavor-solicitar-campo">
                <label class="flavor-solicitar-label">Preferencias de plaza</label>
                <div class="flavor-solicitar-preferencias-grid">
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="cerca_ascensor">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Cerca del ascensor
                    </label>
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="cerca_salida">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Cerca de la salida
                    </label>
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="plaza_amplia">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Plaza amplia
                    </label>
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="carga_electrica">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Con cargador eléctrico
                    </label>
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="planta_baja">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Planta baja
                    </label>
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="preferencias[]" value="esquina">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Plaza en esquina
                    </label>
                </div>
            </div>

            <div class="flavor-solicitar-campo">
                <label for="observaciones" class="flavor-solicitar-label">Observaciones adicionales</label>
                <textarea id="observaciones" name="observaciones" class="flavor-solicitar-textarea" rows="4" placeholder="Indica cualquier necesidad especial o información adicional que debamos conocer..."></textarea>
                <span class="flavor-solicitar-contador">0/500 caracteres</span>
            </div>

            <div class="flavor-solicitar-campo">
                <label for="telefono_contacto" class="flavor-solicitar-label">Teléfono de contacto <span class="requerido">*</span></label>
                <input type="tel" id="telefono_contacto" name="telefono_contacto" class="flavor-solicitar-input" placeholder="+34 600 000 000" required>
                <span class="flavor-solicitar-ayuda">Te notificaremos el estado de tu solicitud</span>
            </div>
        </div>

        <!-- PASO 4: Resumen y Confirmación -->
        <div class="flavor-solicitar-seccion" data-paso="4">
            <h3 class="flavor-solicitar-seccion-titulo">
                <span class="flavor-solicitar-seccion-icono">&#10003;</span>
                Resumen de la solicitud
            </h3>

            <div class="flavor-solicitar-resumen">
                <div class="flavor-solicitar-resumen-card">
                    <div class="flavor-solicitar-resumen-seccion">
                        <h4>Parking seleccionado</h4>
                        <div class="flavor-solicitar-resumen-item">
                            <span class="flavor-solicitar-resumen-icon">&#127359;</span>
                            <div class="flavor-solicitar-resumen-info">
                                <span class="flavor-solicitar-resumen-principal" id="resumen_parking_nombre">Parking Residencial Las Flores</span>
                                <span class="flavor-solicitar-resumen-secundario" id="resumen_parking_direccion">Calle Mayor 45, Madrid</span>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-solicitar-resumen-seccion">
                        <h4>Fechas y horario</h4>
                        <div class="flavor-solicitar-resumen-fechas">
                            <div class="flavor-solicitar-resumen-fecha">
                                <span class="flavor-solicitar-resumen-fecha-label">Entrada</span>
                                <span class="flavor-solicitar-resumen-fecha-valor" id="resumen_fecha_entrada">20/01/2024 08:00</span>
                            </div>
                            <span class="flavor-solicitar-resumen-fecha-flecha">&#8594;</span>
                            <div class="flavor-solicitar-resumen-fecha">
                                <span class="flavor-solicitar-resumen-fecha-label">Salida</span>
                                <span class="flavor-solicitar-resumen-fecha-valor" id="resumen_fecha_salida">20/01/2024 20:00</span>
                            </div>
                        </div>
                        <div class="flavor-solicitar-resumen-duracion">
                            <span>Duración total:</span>
                            <strong id="resumen_duracion">12 horas</strong>
                        </div>
                    </div>

                    <div class="flavor-solicitar-resumen-seccion">
                        <h4>Vehículo</h4>
                        <div class="flavor-solicitar-resumen-item">
                            <span class="flavor-solicitar-resumen-icon">&#128663;</span>
                            <div class="flavor-solicitar-resumen-info">
                                <span class="flavor-solicitar-resumen-principal" id="resumen_matricula">1234 ABC</span>
                                <span class="flavor-solicitar-resumen-secundario" id="resumen_vehiculo">Toyota Corolla - Gris</span>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-solicitar-resumen-seccion">
                        <h4>Preferencias</h4>
                        <div class="flavor-solicitar-resumen-preferencias" id="resumen_preferencias">
                            <span class="flavor-solicitar-resumen-tag">Cerca del ascensor</span>
                            <span class="flavor-solicitar-resumen-tag">Plaza amplia</span>
                        </div>
                    </div>
                </div>

                <div class="flavor-solicitar-precio-total">
                    <div class="flavor-solicitar-precio-desglose">
                        <div class="flavor-solicitar-precio-linea">
                            <span>Tarifa base (12 horas)</span>
                            <span id="resumen_tarifa_base">15.00€</span>
                        </div>
                        <div class="flavor-solicitar-precio-linea">
                            <span>Servicios adicionales</span>
                            <span id="resumen_servicios">0.00€</span>
                        </div>
                        <div class="flavor-solicitar-precio-linea flavor-solicitar-descuento" id="linea_descuento" style="display: none;">
                            <span>Descuento aplicado</span>
                            <span id="resumen_descuento">-0.00€</span>
                        </div>
                    </div>
                    <div class="flavor-solicitar-precio-final">
                        <span>Total estimado</span>
                        <span class="flavor-solicitar-precio-cantidad" id="resumen_total">15.00€</span>
                    </div>
                    <p class="flavor-solicitar-precio-nota">* El precio final se confirmará una vez asignada la plaza</p>
                </div>

                <div class="flavor-solicitar-codigo-promocional">
                    <label for="codigo_promocional" class="flavor-solicitar-label">¿Tienes un código promocional?</label>
                    <div class="flavor-solicitar-codigo-input-wrapper">
                        <input type="text" id="codigo_promocional" name="codigo_promocional" class="flavor-solicitar-input" placeholder="Introduce tu código">
                        <button type="button" class="flavor-solicitar-btn-aplicar">Aplicar</button>
                    </div>
                </div>

                <div class="flavor-solicitar-terminos">
                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="acepto_terminos" value="1" required>
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        He leído y acepto los <a href="#" target="_blank">términos y condiciones</a> y la <a href="#" target="_blank">política de privacidad</a>
                    </label>

                    <label class="flavor-solicitar-checkbox-label">
                        <input type="checkbox" name="acepto_comunicaciones" value="1">
                        <span class="flavor-solicitar-checkbox-custom"></span>
                        Deseo recibir ofertas y novedades por email
                    </label>
                </div>
            </div>
        </div>

        <!-- Navegación del formulario -->
        <div class="flavor-solicitar-navegacion">
            <button type="button" class="flavor-solicitar-btn-anterior" id="btn_anterior" style="visibility: hidden;">
                <span>&#8592;</span> Anterior
            </button>

            <div class="flavor-solicitar-indicador-pasos">
                Paso <span id="paso_actual_num"><?php echo esc_html($paso_actual); ?></span> de <?php echo esc_html($total_pasos); ?>
            </div>

            <button type="button" class="flavor-solicitar-btn-siguiente" id="btn_siguiente">
                Siguiente <span>&#8594;</span>
            </button>

            <button type="submit" class="flavor-solicitar-btn-enviar" id="btn_enviar" style="display: none;">
                <span>&#10003;</span> Enviar solicitud
            </button>
        </div>
    </form>

    <!-- Mensaje de éxito (oculto por defecto) -->
    <div class="flavor-solicitar-exito" id="mensaje_exito" style="display: none;">
        <div class="flavor-solicitar-exito-icono">&#10003;</div>
        <h3>¡Solicitud enviada correctamente!</h3>
        <p>Hemos recibido tu solicitud de plaza de parking. Te notificaremos por email y teléfono cuando se procese.</p>
        <div class="flavor-solicitar-exito-info">
            <span>Número de solicitud:</span>
            <strong id="numero_solicitud">SOL-2024-0001</strong>
        </div>
        <div class="flavor-solicitar-exito-acciones">
            <button type="button" class="flavor-solicitar-btn-nueva-solicitud">Nueva solicitud</button>
            <button type="button" class="flavor-solicitar-btn-mis-solicitudes">Ver mis solicitudes</button>
        </div>
    </div>
</div>

<style>
.flavor-solicitar-parking-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.flavor-solicitar-header {
    margin-bottom: 30px;
}

.flavor-solicitar-titulo {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 25px;
    text-align: center;
}

/* Progreso */
.flavor-solicitar-progreso {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
}

.flavor-solicitar-paso {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.flavor-solicitar-paso-numero {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.flavor-solicitar-paso.activo .flavor-solicitar-paso-numero {
    background: #4f46e5;
    color: white;
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
}

.flavor-solicitar-paso.completado .flavor-solicitar-paso-numero {
    background: #22c55e;
    color: white;
}

.flavor-solicitar-paso-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.flavor-solicitar-paso.activo .flavor-solicitar-paso-label {
    color: #4f46e5;
    font-weight: 600;
}

.flavor-solicitar-paso-linea {
    width: 60px;
    height: 3px;
    background: #e2e8f0;
    margin: 0 10px 25px;
    border-radius: 2px;
    transition: background 0.3s;
}

.flavor-solicitar-paso-linea.completado {
    background: #22c55e;
}

/* Secciones del formulario */
.flavor-solicitar-seccion {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    padding: 25px;
    margin-bottom: 20px;
}

.flavor-solicitar-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f5f9;
}

.flavor-solicitar-seccion-icono {
    font-size: 1.3rem;
}

/* Campos */
.flavor-solicitar-campo {
    margin-bottom: 20px;
}

.flavor-solicitar-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.flavor-solicitar-label .requerido {
    color: #ef4444;
}

.flavor-solicitar-input,
.flavor-solicitar-select,
.flavor-solicitar-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.flavor-solicitar-input:focus,
.flavor-solicitar-select:focus,
.flavor-solicitar-textarea:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.flavor-solicitar-ayuda {
    display: block;
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 6px;
}

.flavor-solicitar-contador {
    display: block;
    text-align: right;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 5px;
}

/* Parkings lista */
.flavor-solicitar-parkings-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-solicitar-parking-opcion input {
    display: none;
}

.flavor-solicitar-parking-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-solicitar-parking-opcion input:checked + .flavor-solicitar-parking-card {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.flavor-solicitar-parking-card:hover {
    border-color: #c7d2fe;
}

.flavor-solicitar-parking-nombre {
    font-weight: 600;
    color: #1a1a2e;
    display: block;
}

.flavor-solicitar-parking-direccion {
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-solicitar-parking-precios {
    text-align: right;
}

.flavor-solicitar-parking-precio {
    font-weight: 700;
    color: #4f46e5;
    display: block;
}

.flavor-solicitar-parking-disponible {
    font-size: 0.8rem;
    color: #22c55e;
}

/* Tipos de reserva */
.flavor-solicitar-tipos-reserva {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.flavor-solicitar-tipo-opcion input {
    display: none;
}

.flavor-solicitar-tipo-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.flavor-solicitar-tipo-opcion input:checked + .flavor-solicitar-tipo-card {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.flavor-solicitar-tipo-icono {
    font-size: 2rem;
}

.flavor-solicitar-tipo-label {
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-solicitar-tipo-desc {
    font-size: 0.75rem;
    color: #64748b;
}

/* Filas de campos */
.flavor-solicitar-fechas-row,
.flavor-solicitar-horas-row,
.flavor-solicitar-campo-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* Vehículos guardados */
.flavor-solicitar-vehiculos-guardados {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.flavor-solicitar-vehiculo-opcion input {
    display: none;
}

.flavor-solicitar-vehiculo-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-solicitar-vehiculo-opcion input:checked + .flavor-solicitar-vehiculo-card {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.flavor-solicitar-vehiculo-icono {
    font-size: 1.5rem;
}

.flavor-solicitar-vehiculo-matricula {
    font-weight: 700;
    font-family: monospace;
    display: block;
}

.flavor-solicitar-vehiculo-modelo {
    font-size: 0.8rem;
    color: #64748b;
}

.flavor-solicitar-badge-predeterminado {
    margin-left: auto;
    font-size: 0.65rem;
    padding: 3px 8px;
    background: #dcfce7;
    color: #22c55e;
    border-radius: 10px;
    font-weight: 600;
}

.flavor-solicitar-vehiculo-nuevo {
    border-style: dashed;
    justify-content: center;
    color: #64748b;
}

/* Tipos de vehículo */
.flavor-solicitar-tipos-vehiculo {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.flavor-solicitar-tipo-vehiculo-opcion input {
    display: none;
}

.flavor-solicitar-tipo-vehiculo-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-solicitar-tipo-vehiculo-opcion input:checked + .flavor-solicitar-tipo-vehiculo-card {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.flavor-solicitar-tipo-vehiculo-icono {
    font-size: 1.25rem;
}

/* Horarios */
.flavor-solicitar-horarios {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-solicitar-horario-opcion input {
    display: none;
}

.flavor-solicitar-horario-label {
    display: block;
    padding: 10px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.flavor-solicitar-horario-opcion input:checked + .flavor-solicitar-horario-label {
    border-color: #4f46e5;
    background: #f5f3ff;
    color: #4f46e5;
    font-weight: 500;
}

/* Preferencias grid */
.flavor-solicitar-preferencias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

/* Checkbox personalizado */
.flavor-solicitar-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9rem;
    color: #475569;
}

.flavor-solicitar-checkbox-label input {
    display: none;
}

.flavor-solicitar-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.flavor-solicitar-checkbox-label input:checked + .flavor-solicitar-checkbox-custom {
    background: #4f46e5;
    border-color: #4f46e5;
}

.flavor-solicitar-checkbox-label input:checked + .flavor-solicitar-checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 0.75rem;
}

/* Resumen */
.flavor-solicitar-resumen-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.flavor-solicitar-resumen-seccion {
    padding: 15px 0;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-solicitar-resumen-seccion:last-child {
    border-bottom: none;
}

.flavor-solicitar-resumen-seccion h4 {
    margin: 0 0 12px;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #94a3b8;
    letter-spacing: 0.5px;
}

.flavor-solicitar-resumen-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-solicitar-resumen-icon {
    font-size: 1.5rem;
}

.flavor-solicitar-resumen-principal {
    font-weight: 600;
    color: #1a1a2e;
    display: block;
}

.flavor-solicitar-resumen-secundario {
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-solicitar-resumen-fechas {
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-solicitar-resumen-fecha {
    text-align: center;
}

.flavor-solicitar-resumen-fecha-label {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
}

.flavor-solicitar-resumen-fecha-valor {
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-solicitar-resumen-fecha-flecha {
    color: #94a3b8;
    font-size: 1.25rem;
}

.flavor-solicitar-resumen-duracion {
    margin-top: 10px;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-solicitar-resumen-preferencias {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.flavor-solicitar-resumen-tag {
    padding: 5px 12px;
    background: #e0e7ff;
    color: #4f46e5;
    border-radius: 15px;
    font-size: 0.8rem;
}

/* Precio total */
.flavor-solicitar-precio-total {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.flavor-solicitar-precio-desglose {
    margin-bottom: 15px;
}

.flavor-solicitar-precio-linea {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-solicitar-descuento {
    color: #22c55e;
}

.flavor-solicitar-precio-final {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 2px solid #e2e8f0;
}

.flavor-solicitar-precio-cantidad {
    font-size: 1.75rem;
    font-weight: 700;
    color: #4f46e5;
}

.flavor-solicitar-precio-nota {
    margin: 10px 0 0;
    font-size: 0.8rem;
    color: #94a3b8;
    font-style: italic;
}

/* Código promocional */
.flavor-solicitar-codigo-input-wrapper {
    display: flex;
    gap: 10px;
}

.flavor-solicitar-btn-aplicar {
    padding: 12px 20px;
    background: #f1f5f9;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-solicitar-btn-aplicar:hover {
    background: #e2e8f0;
}

/* Términos */
.flavor-solicitar-terminos {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-solicitar-terminos a {
    color: #4f46e5;
    text-decoration: none;
}

.flavor-solicitar-terminos a:hover {
    text-decoration: underline;
}

/* Navegación */
.flavor-solicitar-navegacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
}

.flavor-solicitar-btn-anterior,
.flavor-solicitar-btn-siguiente,
.flavor-solicitar-btn-enviar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.flavor-solicitar-btn-anterior {
    background: #f1f5f9;
    color: #475569;
}

.flavor-solicitar-btn-anterior:hover {
    background: #e2e8f0;
}

.flavor-solicitar-btn-siguiente {
    background: #4f46e5;
    color: white;
}

.flavor-solicitar-btn-siguiente:hover {
    background: #4338ca;
}

.flavor-solicitar-btn-enviar {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.flavor-solicitar-btn-enviar:hover {
    filter: brightness(1.05);
}

.flavor-solicitar-indicador-pasos {
    font-size: 0.9rem;
    color: #64748b;
}

/* Mensaje de éxito */
.flavor-solicitar-exito {
    text-align: center;
    padding: 60px 30px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}

.flavor-solicitar-exito-icono {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    font-size: 2.5rem;
    border-radius: 50%;
    margin: 0 auto 25px;
}

.flavor-solicitar-exito h3 {
    font-size: 1.5rem;
    color: #1a1a2e;
    margin: 0 0 15px;
}

.flavor-solicitar-exito p {
    color: #64748b;
    margin: 0 0 25px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.flavor-solicitar-exito-info {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    background: #f5f3ff;
    border-radius: 10px;
    margin-bottom: 25px;
}

.flavor-solicitar-exito-info strong {
    font-family: monospace;
    font-size: 1.1rem;
    color: #4f46e5;
}

.flavor-solicitar-exito-acciones {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.flavor-solicitar-btn-nueva-solicitud,
.flavor-solicitar-btn-mis-solicitudes {
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.flavor-solicitar-btn-nueva-solicitud {
    background: #f1f5f9;
    color: #475569;
}

.flavor-solicitar-btn-mis-solicitudes {
    background: #4f46e5;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-solicitar-progreso {
        flex-wrap: wrap;
        gap: 5px;
    }

    .flavor-solicitar-paso-linea {
        width: 30px;
    }

    .flavor-solicitar-tipos-reserva {
        grid-template-columns: 1fr;
    }

    .flavor-solicitar-fechas-row,
    .flavor-solicitar-horas-row,
    .flavor-solicitar-campo-row {
        grid-template-columns: 1fr;
    }

    .flavor-solicitar-vehiculos-guardados {
        grid-template-columns: 1fr;
    }

    .flavor-solicitar-navegacion {
        flex-wrap: wrap;
        gap: 15px;
    }

    .flavor-solicitar-indicador-pasos {
        order: -1;
        width: 100%;
        text-align: center;
    }

    .flavor-solicitar-btn-anterior,
    .flavor-solicitar-btn-siguiente,
    .flavor-solicitar-btn-enviar {
        flex: 1;
        justify-content: center;
    }

    .flavor-solicitar-exito-acciones {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .flavor-solicitar-progreso {
        justify-content: space-between;
    }

    .flavor-solicitar-paso-linea {
        display: none;
    }

    .flavor-solicitar-paso-label {
        display: none;
    }

    .flavor-solicitar-preferencias-grid {
        grid-template-columns: 1fr;
    }

    .flavor-solicitar-resumen-fechas {
        flex-direction: column;
    }

    .flavor-solicitar-resumen-fecha-flecha {
        transform: rotate(90deg);
    }
}
</style>
