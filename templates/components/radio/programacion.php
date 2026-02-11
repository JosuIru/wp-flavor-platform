<?php
/**
 * Template: Parrilla de programacion de radio comunitaria
 *
 * Muestra la programacion de radio organizada por dias, con el programa
 * actual destacado y los proximos programas.
 *
 * @package Flavor_Chat_IA
 * @subpackage Templates/Components/Radio
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Programacion de Radio';
$nombre_radio = isset($args['nombre_radio']) ? $args['nombre_radio'] : 'Radio Comunitaria Flavor';

$dias_semana = array('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo');
$nombres_dias = array(
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miercoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sabado',
    'domingo' => 'Domingo'
);

// Programacion de demostracion
$programacion_default = array(
    'lunes' => array(
        array('hora_inicio' => '06:00', 'hora_fin' => '09:00', 'programa' => 'Despertando Juntos', 'locutor' => 'Maria Garcia', 'genero' => 'Magazine matutino'),
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Musica sin Fronteras', 'locutor' => 'Carlos Ruiz', 'genero' => 'Musica variada'),
        array('hora_inicio' => '12:00', 'hora_fin' => '14:00', 'programa' => 'Noticiero del Mediodia', 'locutor' => 'Ana Martinez', 'genero' => 'Noticias'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Tarde de Boleros', 'locutor' => 'Roberto Sanchez', 'genero' => 'Boleros y baladas'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Rock en Espanol', 'locutor' => 'Diego Luna', 'genero' => 'Rock'),
        array('hora_inicio' => '20:00', 'hora_fin' => '23:00', 'programa' => 'Noches de Jazz', 'locutor' => 'Patricia Vega', 'genero' => 'Jazz'),
        array('hora_inicio' => '23:00', 'hora_fin' => '06:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    ),
    'martes' => array(
        array('hora_inicio' => '06:00', 'hora_fin' => '09:00', 'programa' => 'Despertando Juntos', 'locutor' => 'Maria Garcia', 'genero' => 'Magazine matutino'),
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Salsa y Sabor', 'locutor' => 'Hector Lavoe Jr.', 'genero' => 'Salsa'),
        array('hora_inicio' => '12:00', 'hora_fin' => '14:00', 'programa' => 'Noticiero del Mediodia', 'locutor' => 'Ana Martinez', 'genero' => 'Noticias'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Cultura Viva', 'locutor' => 'Laura Torres', 'genero' => 'Cultural'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Electronica Underground', 'locutor' => 'DJ Pixel', 'genero' => 'Electronica'),
        array('hora_inicio' => '20:00', 'hora_fin' => '23:00', 'programa' => 'Tertulia Nocturna', 'locutor' => 'Fernando Paz', 'genero' => 'Debate'),
        array('hora_inicio' => '23:00', 'hora_fin' => '06:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    ),
    'miercoles' => array(
        array('hora_inicio' => '06:00', 'hora_fin' => '09:00', 'programa' => 'Despertando Juntos', 'locutor' => 'Maria Garcia', 'genero' => 'Magazine matutino'),
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Folclore Latinoamericano', 'locutor' => 'Mercedes Sosa Jr.', 'genero' => 'Folclore'),
        array('hora_inicio' => '12:00', 'hora_fin' => '14:00', 'programa' => 'Noticiero del Mediodia', 'locutor' => 'Ana Martinez', 'genero' => 'Noticias'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Cine y Series', 'locutor' => 'Pablo Montero', 'genero' => 'Entretenimiento'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Hip Hop Latino', 'locutor' => 'MC Flow', 'genero' => 'Hip Hop'),
        array('hora_inicio' => '20:00', 'hora_fin' => '23:00', 'programa' => 'Clasicos del Rock', 'locutor' => 'Arturo Stone', 'genero' => 'Rock clasico'),
        array('hora_inicio' => '23:00', 'hora_fin' => '06:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    ),
    'jueves' => array(
        array('hora_inicio' => '06:00', 'hora_fin' => '09:00', 'programa' => 'Despertando Juntos', 'locutor' => 'Maria Garcia', 'genero' => 'Magazine matutino'),
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Reggae Vibes', 'locutor' => 'Bob Marley Jr.', 'genero' => 'Reggae'),
        array('hora_inicio' => '12:00', 'hora_fin' => '14:00', 'programa' => 'Noticiero del Mediodia', 'locutor' => 'Ana Martinez', 'genero' => 'Noticias'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Deportes al Dia', 'locutor' => 'Andres Cantor Jr.', 'genero' => 'Deportes'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Pop Internacional', 'locutor' => 'Sofia Reyes', 'genero' => 'Pop'),
        array('hora_inicio' => '20:00', 'hora_fin' => '23:00', 'programa' => 'Blues Session', 'locutor' => 'B.B. King Jr.', 'genero' => 'Blues'),
        array('hora_inicio' => '23:00', 'hora_fin' => '06:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    ),
    'viernes' => array(
        array('hora_inicio' => '06:00', 'hora_fin' => '09:00', 'programa' => 'Despertando Juntos', 'locutor' => 'Maria Garcia', 'genero' => 'Magazine matutino'),
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Cumbia Power', 'locutor' => 'Los Angeles Azules Jr.', 'genero' => 'Cumbia'),
        array('hora_inicio' => '12:00', 'hora_fin' => '14:00', 'programa' => 'Noticiero del Mediodia', 'locutor' => 'Ana Martinez', 'genero' => 'Noticias'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Viernes de Exitos', 'locutor' => 'DJ Mix', 'genero' => 'Exitos'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Pre-Party Mix', 'locutor' => 'DJ Fiesta', 'genero' => 'Dance'),
        array('hora_inicio' => '20:00', 'hora_fin' => '02:00', 'programa' => 'Noche de Fiesta', 'locutor' => 'DJ Weekend', 'genero' => 'Fiesta'),
        array('hora_inicio' => '02:00', 'hora_fin' => '06:00', 'programa' => 'After Hours', 'locutor' => 'DJ Night', 'genero' => 'Chill')
    ),
    'sabado' => array(
        array('hora_inicio' => '08:00', 'hora_fin' => '11:00', 'programa' => 'Mananas de Sabado', 'locutor' => 'Juan Weekend', 'genero' => 'Magazine'),
        array('hora_inicio' => '11:00', 'hora_fin' => '14:00', 'programa' => 'Cocina en la Radio', 'locutor' => 'Chef Radio', 'genero' => 'Gastronomia'),
        array('hora_inicio' => '14:00', 'hora_fin' => '17:00', 'programa' => 'Musica Tropical', 'locutor' => 'Celia Cruz Jr.', 'genero' => 'Tropical'),
        array('hora_inicio' => '17:00', 'hora_fin' => '20:00', 'programa' => 'Rock Nacional', 'locutor' => 'Gustavo Cerati Jr.', 'genero' => 'Rock argentino'),
        array('hora_inicio' => '20:00', 'hora_fin' => '02:00', 'programa' => 'Sabado Gigante', 'locutor' => 'DJ Master', 'genero' => 'Fiesta'),
        array('hora_inicio' => '02:00', 'hora_fin' => '08:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    ),
    'domingo' => array(
        array('hora_inicio' => '09:00', 'hora_fin' => '12:00', 'programa' => 'Domingo Familiar', 'locutor' => 'Familia Radio', 'genero' => 'Familiar'),
        array('hora_inicio' => '12:00', 'hora_fin' => '15:00', 'programa' => 'Clasicos de Siempre', 'locutor' => 'Nostalgia FM', 'genero' => 'Clasicos'),
        array('hora_inicio' => '15:00', 'hora_fin' => '18:00', 'programa' => 'Musica Relajante', 'locutor' => 'Zen Radio', 'genero' => 'Chill'),
        array('hora_inicio' => '18:00', 'hora_fin' => '21:00', 'programa' => 'Countdown Semanal', 'locutor' => 'Top Charts', 'genero' => 'Exitos'),
        array('hora_inicio' => '21:00', 'hora_fin' => '00:00', 'programa' => 'Despidiendo el Finde', 'locutor' => 'DJ Sunday', 'genero' => 'Variado'),
        array('hora_inicio' => '00:00', 'hora_fin' => '09:00', 'programa' => 'Musica Continua', 'locutor' => 'Automatico', 'genero' => 'Musica variada')
    )
);

$programacion = isset($args['programacion']) ? $args['programacion'] : $programacion_default;

$mostrar_programa_actual = isset($args['mostrar_programa_actual']) ? $args['mostrar_programa_actual'] : true;
$mostrar_proximos = isset($args['mostrar_proximos']) ? $args['mostrar_proximos'] : true;
$cantidad_proximos = isset($args['cantidad_proximos']) ? $args['cantidad_proximos'] : 3;

$url_streaming = isset($args['url_streaming']) ? $args['url_streaming'] : '#escuchar-ahora';
$clases_adicionales = isset($args['clases_adicionales']) ? $args['clases_adicionales'] : '';

// Obtener dia actual y hora
$dia_actual = strtolower(date('l'));
$dia_actual_map = array(
    'monday' => 'lunes',
    'tuesday' => 'martes',
    'wednesday' => 'miercoles',
    'thursday' => 'jueves',
    'friday' => 'viernes',
    'saturday' => 'sabado',
    'sunday' => 'domingo'
);
$dia_actual_key = isset($dia_actual_map[$dia_actual]) ? $dia_actual_map[$dia_actual] : 'lunes';
$hora_actual = date('H:i');

// Funcion para determinar si un programa esta al aire
function flavor_esta_al_aire($hora_inicio, $hora_fin, $hora_actual) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    $actual = strtotime($hora_actual);

    // Manejar programas que cruzan medianoche
    if ($fin < $inicio) {
        return ($actual >= $inicio || $actual < $fin);
    }

    return ($actual >= $inicio && $actual < $fin);
}

// Encontrar programa actual
$programa_actual = null;
if (isset($programacion[$dia_actual_key])) {
    foreach ($programacion[$dia_actual_key] as $programa) {
        if (flavor_esta_al_aire($programa['hora_inicio'], $programa['hora_fin'], $hora_actual)) {
            $programa_actual = $programa;
            break;
        }
    }
}

// Obtener proximos programas
$proximos_programas = array();
if ($mostrar_proximos && isset($programacion[$dia_actual_key])) {
    $encontrado_actual = false;
    foreach ($programacion[$dia_actual_key] as $programa) {
        if ($encontrado_actual && count($proximos_programas) < $cantidad_proximos) {
            $proximos_programas[] = $programa;
        }
        if ($programa_actual && $programa['hora_inicio'] === $programa_actual['hora_inicio']) {
            $encontrado_actual = true;
        }
    }
}
?>

<section class="flavor-radio-programacion <?php echo esc_attr($clases_adicionales); ?>">
    <div class="flavor-radio-programacion__contenedor">

        <!-- Encabezado -->
        <header class="flavor-radio-programacion__encabezado">
            <h2 class="flavor-radio-programacion__titulo">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="flavor-radio-programacion__nombre-radio">
                <?php echo esc_html($nombre_radio); ?>
            </p>
        </header>

        <!-- Programa Actual -->
        <?php if ($mostrar_programa_actual && $programa_actual) : ?>
            <div class="flavor-radio-programacion__actual">
                <div class="flavor-radio-programacion__actual-badge">
                    <span class="flavor-radio-programacion__en-vivo-indicador"></span>
                    EN VIVO AHORA
                </div>
                <div class="flavor-radio-programacion__actual-info">
                    <h3 class="flavor-radio-programacion__actual-programa">
                        <?php echo esc_html($programa_actual['programa']); ?>
                    </h3>
                    <p class="flavor-radio-programacion__actual-locutor">
                        <span class="dashicons dashicons-microphone"></span>
                        <?php echo esc_html($programa_actual['locutor']); ?>
                    </p>
                    <p class="flavor-radio-programacion__actual-horario">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($programa_actual['hora_inicio']); ?> - <?php echo esc_html($programa_actual['hora_fin']); ?>
                    </p>
                    <span class="flavor-radio-programacion__actual-genero">
                        <?php echo esc_html($programa_actual['genero']); ?>
                    </span>
                </div>
                <a href="<?php echo esc_url($url_streaming); ?>" class="flavor-radio-programacion__escuchar-btn">
                    <span class="dashicons dashicons-controls-play"></span>
                    Escuchar ahora
                </a>
            </div>
        <?php endif; ?>

        <!-- Proximos programas -->
        <?php if ($mostrar_proximos && !empty($proximos_programas)) : ?>
            <div class="flavor-radio-programacion__proximos">
                <h3 class="flavor-radio-programacion__proximos-titulo">A continuacion</h3>
                <div class="flavor-radio-programacion__proximos-lista">
                    <?php foreach ($proximos_programas as $proximo) : ?>
                        <div class="flavor-radio-programacion__proximo-item">
                            <span class="flavor-radio-programacion__proximo-hora">
                                <?php echo esc_html($proximo['hora_inicio']); ?>
                            </span>
                            <div class="flavor-radio-programacion__proximo-info">
                                <span class="flavor-radio-programacion__proximo-programa">
                                    <?php echo esc_html($proximo['programa']); ?>
                                </span>
                                <span class="flavor-radio-programacion__proximo-locutor">
                                    <?php echo esc_html($proximo['locutor']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Selector de dias -->
        <div class="flavor-radio-programacion__selector-dias">
            <?php foreach ($dias_semana as $dia) : ?>
                <button
                    class="flavor-radio-programacion__dia-btn <?php echo ($dia === $dia_actual_key) ? 'flavor-radio-programacion__dia-btn--activo' : ''; ?>"
                    data-dia="<?php echo esc_attr($dia); ?>"
                >
                    <?php echo esc_html($nombres_dias[$dia]); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Parrilla de programacion -->
        <div class="flavor-radio-programacion__parrilla">
            <?php foreach ($dias_semana as $dia) : ?>
                <div
                    class="flavor-radio-programacion__dia-contenido <?php echo ($dia === $dia_actual_key) ? 'flavor-radio-programacion__dia-contenido--activo' : ''; ?>"
                    data-dia="<?php echo esc_attr($dia); ?>"
                >
                    <?php if (isset($programacion[$dia])) : ?>
                        <div class="flavor-radio-programacion__programas-lista">
                            <?php foreach ($programacion[$dia] as $programa) : ?>
                                <?php
                                $esta_al_aire = ($dia === $dia_actual_key) && flavor_esta_al_aire($programa['hora_inicio'], $programa['hora_fin'], $hora_actual);
                                ?>
                                <div class="flavor-radio-programacion__programa-item <?php echo $esta_al_aire ? 'flavor-radio-programacion__programa-item--al-aire' : ''; ?>">
                                    <div class="flavor-radio-programacion__programa-horario">
                                        <span class="flavor-radio-programacion__hora-inicio">
                                            <?php echo esc_html($programa['hora_inicio']); ?>
                                        </span>
                                        <span class="flavor-radio-programacion__hora-separador">-</span>
                                        <span class="flavor-radio-programacion__hora-fin">
                                            <?php echo esc_html($programa['hora_fin']); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-radio-programacion__programa-detalles">
                                        <h4 class="flavor-radio-programacion__programa-nombre">
                                            <?php echo esc_html($programa['programa']); ?>
                                            <?php if ($esta_al_aire) : ?>
                                                <span class="flavor-radio-programacion__live-badge">EN VIVO</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="flavor-radio-programacion__programa-locutor">
                                            <?php echo esc_html($programa['locutor']); ?>
                                        </p>
                                    </div>
                                    <span class="flavor-radio-programacion__programa-genero">
                                        <?php echo esc_html($programa['genero']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="flavor-radio-programacion__sin-programas">
                            No hay programacion disponible para este dia.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<style>
.flavor-radio-programacion {
    background: #1a1a2e;
    padding: 60px 20px;
    color: #fff;
}

.flavor-radio-programacion__contenedor {
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-radio-programacion__encabezado {
    text-align: center;
    margin-bottom: 40px;
}

.flavor-radio-programacion__titulo {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 10px;
    color: #fff;
}

.flavor-radio-programacion__nombre-radio {
    font-size: 1.1rem;
    color: #e94560;
    margin: 0;
}

/* Programa actual */
.flavor-radio-programacion__actual {
    background: linear-gradient(135deg, #e94560 0%, #0f3460 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 40px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 30px;
}

.flavor-radio-programacion__actual-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 1px;
}

.flavor-radio-programacion__en-vivo-indicador {
    width: 10px;
    height: 10px;
    background: #4ade80;
    border-radius: 50%;
    animation: flavor-pulse 1.5s infinite;
}

@keyframes flavor-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

.flavor-radio-programacion__actual-info {
    flex: 1;
    min-width: 200px;
}

.flavor-radio-programacion__actual-programa {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 10px;
    color: #fff;
}

.flavor-radio-programacion__actual-locutor,
.flavor-radio-programacion__actual-horario {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 5px 0;
    font-size: 1rem;
    opacity: 0.9;
}

.flavor-radio-programacion__actual-genero {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    margin-top: 10px;
}

.flavor-radio-programacion__escuchar-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    color: #e94560;
    padding: 15px 30px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.flavor-radio-programacion__escuchar-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
    color: #e94560;
}

/* Proximos programas */
.flavor-radio-programacion__proximos {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 40px;
}

.flavor-radio-programacion__proximos-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 20px;
    color: #e94560;
}

.flavor-radio-programacion__proximos-lista {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.flavor-radio-programacion__proximo-item {
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(255, 255, 255, 0.05);
    padding: 15px 20px;
    border-radius: 12px;
    flex: 1;
    min-width: 200px;
}

.flavor-radio-programacion__proximo-hora {
    font-size: 1.25rem;
    font-weight: 700;
    color: #e94560;
}

.flavor-radio-programacion__proximo-info {
    display: flex;
    flex-direction: column;
}

.flavor-radio-programacion__proximo-programa {
    font-weight: 600;
    font-size: 1rem;
}

.flavor-radio-programacion__proximo-locutor {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Selector de dias */
.flavor-radio-programacion__selector-dias {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    overflow-x: auto;
    padding-bottom: 10px;
    -webkit-overflow-scrolling: touch;
}

.flavor-radio-programacion__dia-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    padding: 12px 24px;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.flavor-radio-programacion__dia-btn:hover {
    background: rgba(233, 69, 96, 0.3);
}

.flavor-radio-programacion__dia-btn--activo {
    background: #e94560;
    font-weight: 600;
}

/* Parrilla */
.flavor-radio-programacion__dia-contenido {
    display: none;
}

.flavor-radio-programacion__dia-contenido--activo {
    display: block;
}

.flavor-radio-programacion__programas-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-radio-programacion__programa-item {
    display: flex;
    align-items: center;
    gap: 20px;
    background: rgba(255, 255, 255, 0.05);
    padding: 20px;
    border-radius: 12px;
    transition: background 0.3s ease;
}

.flavor-radio-programacion__programa-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.flavor-radio-programacion__programa-item--al-aire {
    background: linear-gradient(90deg, rgba(233, 69, 96, 0.3) 0%, rgba(255, 255, 255, 0.05) 100%);
    border-left: 4px solid #e94560;
}

.flavor-radio-programacion__programa-horario {
    min-width: 120px;
    font-size: 1rem;
    font-weight: 500;
    color: #e94560;
}

.flavor-radio-programacion__hora-separador {
    margin: 0 5px;
    opacity: 0.5;
}

.flavor-radio-programacion__programa-detalles {
    flex: 1;
}

.flavor-radio-programacion__programa-nombre {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 5px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.flavor-radio-programacion__live-badge {
    background: #e94560;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 20px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.flavor-radio-programacion__programa-locutor {
    font-size: 0.9rem;
    margin: 0;
    opacity: 0.7;
}

.flavor-radio-programacion__programa-genero {
    background: rgba(255, 255, 255, 0.1);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    white-space: nowrap;
}

.flavor-radio-programacion__sin-programas {
    text-align: center;
    padding: 40px;
    opacity: 0.6;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-radio-programacion {
        padding: 40px 15px;
    }

    .flavor-radio-programacion__titulo {
        font-size: 1.75rem;
    }

    .flavor-radio-programacion__actual {
        flex-direction: column;
        text-align: center;
        padding: 25px;
    }

    .flavor-radio-programacion__actual-info {
        text-align: center;
    }

    .flavor-radio-programacion__actual-locutor,
    .flavor-radio-programacion__actual-horario {
        justify-content: center;
    }

    .flavor-radio-programacion__actual-programa {
        font-size: 1.5rem;
    }

    .flavor-radio-programacion__proximos-lista {
        flex-direction: column;
    }

    .flavor-radio-programacion__selector-dias {
        justify-content: flex-start;
    }

    .flavor-radio-programacion__dia-btn {
        padding: 10px 18px;
        font-size: 0.85rem;
    }

    .flavor-radio-programacion__programa-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .flavor-radio-programacion__programa-horario {
        min-width: auto;
    }

    .flavor-radio-programacion__programa-genero {
        align-self: flex-start;
    }
}

@media (max-width: 480px) {
    .flavor-radio-programacion__titulo {
        font-size: 1.5rem;
    }

    .flavor-radio-programacion__dia-btn {
        padding: 8px 14px;
        font-size: 0.8rem;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const botonesSelector = document.querySelectorAll('.flavor-radio-programacion__dia-btn');
        const contenidosDias = document.querySelectorAll('.flavor-radio-programacion__dia-contenido');

        botonesSelector.forEach(function(boton) {
            boton.addEventListener('click', function() {
                const diaSeleccionado = this.getAttribute('data-dia');

                // Actualizar botones
                botonesSelector.forEach(function(btn) {
                    btn.classList.remove('flavor-radio-programacion__dia-btn--activo');
                });
                this.classList.add('flavor-radio-programacion__dia-btn--activo');

                // Mostrar contenido del dia seleccionado
                contenidosDias.forEach(function(contenido) {
                    contenido.classList.remove('flavor-radio-programacion__dia-contenido--activo');
                    if (contenido.getAttribute('data-dia') === diaSeleccionado) {
                        contenido.classList.add('flavor-radio-programacion__dia-contenido--activo');
                    }
                });
            });
        });
    });
})();
</script>
