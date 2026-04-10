<?php
/**
 * Template: Player embebido de podcast
 *
 * Player de podcast con controles completos: play/pause, barra de progreso,
 * control de velocidad y volumen.
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Podcast
 */

if (!defined('ABSPATH')) exit;

// Generar ID único para el player
$player_id = isset($args['player_id']) ? $args['player_id'] : 'flavor-podcast-player-' . uniqid();

// Extraer variables del array $args con valores por defecto
$titulo_episodio = isset($args['titulo_episodio']) ? $args['titulo_episodio'] : 'Episodio 42: El futuro de la inteligencia artificial';
$nombre_podcast = isset($args['nombre_podcast']) ? $args['nombre_podcast'] : 'Tech & Café Podcast';
$descripcion_episodio = isset($args['descripcion_episodio']) ? $args['descripcion_episodio'] : 'En este episodio exploramos cómo la IA está transformando nuestras vidas cotidianas, desde los asistentes virtuales hasta los coches autónomos. Conversamos con expertos en el campo sobre las oportunidades y desafíos que nos esperan.';

$url_audio = isset($args['url_audio']) ? $args['url_audio'] : '';
$duracion_total = isset($args['duracion_total']) ? $args['duracion_total'] : '45:32';
$fecha_publicacion = isset($args['fecha_publicacion']) ? $args['fecha_publicacion'] : '15 de enero, 2025';

$imagen_portada = isset($args['imagen_portada']) ? $args['imagen_portada'] : '';
$imagen_placeholder = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300"><rect fill="#667eea" width="300" height="300"/><text x="50%" y="50%" fill="#fff" font-family="Arial" font-size="48" text-anchor="middle" dy=".3em">🎙️</text></svg>');
$imagen_mostrar = !empty($imagen_portada) ? $imagen_portada : $imagen_placeholder;

$velocidades_disponibles = isset($args['velocidades_disponibles']) ? $args['velocidades_disponibles'] : array(0.5, 0.75, 1, 1.25, 1.5, 1.75, 2);
$velocidad_inicial = isset($args['velocidad_inicial']) ? $args['velocidad_inicial'] : 1;
$volumen_inicial = isset($args['volumen_inicial']) ? $args['volumen_inicial'] : 80;

$mostrar_descripcion = isset($args['mostrar_descripcion']) ? $args['mostrar_descripcion'] : true;
$mostrar_compartir = isset($args['mostrar_compartir']) ? $args['mostrar_compartir'] : true;
$mostrar_descargar = isset($args['mostrar_descargar']) ? $args['mostrar_descargar'] : true;

$enlaces_plataformas = isset($args['enlaces_plataformas']) ? $args['enlaces_plataformas'] : array(
    array('nombre' => 'Spotify', 'url' => '#spotify', 'icono' => 'dashicons-spotify'),
    array('nombre' => 'Apple Podcasts', 'url' => '#apple', 'icono' => 'dashicons-apple'),
    array('nombre' => 'Google Podcasts', 'url' => '#google', 'icono' => 'dashicons-google')
);

$clases_adicionales = isset($args['clases_adicionales']) ? $args['clases_adicionales'] : '';

// Datos de demostración para el capítulo
$capitulos = isset($args['capitulos']) ? $args['capitulos'] : array(
    array('tiempo' => '00:00', 'titulo' => 'Introducción'),
    array('tiempo' => '02:30', 'titulo' => '¿Qué es la IA?'),
    array('tiempo' => '10:15', 'titulo' => 'Aplicaciones actuales'),
    array('tiempo' => '22:45', 'titulo' => 'Entrevista con expertos'),
    array('tiempo' => '38:00', 'titulo' => 'El futuro de la IA'),
    array('tiempo' => '43:20', 'titulo' => 'Conclusiones y despedida')
);
?>

<article class="flavor-podcast-player <?php echo esc_attr($clases_adicionales); ?>" id="<?php echo esc_attr($player_id); ?>">
    <div class="flavor-podcast-player__contenedor">

        <!-- Información del episodio -->
        <header class="flavor-podcast-player__header">
            <div class="flavor-podcast-player__portada">
                <img
                    src="<?php echo esc_url($imagen_mostrar); ?>"
                    alt="<?php echo esc_attr($titulo_episodio); ?>"
                    class="flavor-podcast-player__portada-imagen"
                />
            </div>
            <div class="flavor-podcast-player__info">
                <span class="flavor-podcast-player__nombre-podcast">
                    <?php echo esc_html($nombre_podcast); ?>
                </span>
                <h2 class="flavor-podcast-player__titulo">
                    <?php echo esc_html($titulo_episodio); ?>
                </h2>
                <div class="flavor-podcast-player__meta">
                    <span class="flavor-podcast-player__fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html($fecha_publicacion); ?>
                    </span>
                    <span class="flavor-podcast-player__duracion">
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($duracion_total); ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Player principal -->
        <div class="flavor-podcast-player__controles">

            <!-- Botones de control -->
            <div class="flavor-podcast-player__botones-principales">
                <button
                    type="button"
                    class="flavor-podcast-player__btn flavor-podcast-player__btn-retroceder"
                    title="Retroceder 15 segundos"
                    data-action="rewind"
                >
                    <span class="dashicons dashicons-controls-back"></span>
                    <span class="flavor-podcast-player__btn-label">15</span>
                </button>

                <button
                    type="button"
                    class="flavor-podcast-player__btn flavor-podcast-player__btn-play"
                    title="Reproducir / Pausar"
                    data-action="play-pause"
                >
                    <span class="flavor-podcast-player__icono-play dashicons dashicons-controls-play"></span>
                    <span class="flavor-podcast-player__icono-pause dashicons dashicons-controls-pause" style="display: none;"></span>
                </button>

                <button
                    type="button"
                    class="flavor-podcast-player__btn flavor-podcast-player__btn-avanzar"
                    title="Avanzar 30 segundos"
                    data-action="forward"
                >
                    <span class="flavor-podcast-player__btn-label">30</span>
                    <span class="dashicons dashicons-controls-forward"></span>
                </button>
            </div>

            <!-- Barra de progreso -->
            <div class="flavor-podcast-player__progreso">
                <span class="flavor-podcast-player__tiempo-actual">00:00</span>
                <div class="flavor-podcast-player__barra-contenedor">
                    <div class="flavor-podcast-player__barra-fondo">
                        <div class="flavor-podcast-player__barra-cargado"></div>
                        <div class="flavor-podcast-player__barra-progreso"></div>
                        <div class="flavor-podcast-player__barra-handle"></div>
                    </div>
                    <input
                        type="range"
                        class="flavor-podcast-player__barra-input"
                        min="0"
                        max="100"
                        value="0"
                        aria-label="Progreso de reproducción"
                    />
                </div>
                <span class="flavor-podcast-player__tiempo-total"><?php echo esc_html($duracion_total); ?></span>
            </div>

            <!-- Controles secundarios -->
            <div class="flavor-podcast-player__controles-secundarios">

                <!-- Control de velocidad -->
                <div class="flavor-podcast-player__velocidad">
                    <button
                        type="button"
                        class="flavor-podcast-player__btn flavor-podcast-player__btn-velocidad"
                        title="Velocidad de reproducción"
                        data-action="speed"
                    >
                        <span class="flavor-podcast-player__velocidad-valor"><?php echo esc_html($velocidad_inicial); ?>x</span>
                    </button>
                    <div class="flavor-podcast-player__velocidad-menu">
                        <?php foreach ($velocidades_disponibles as $velocidad) : ?>
                            <button
                                type="button"
                                class="flavor-podcast-player__velocidad-opcion <?php echo ($velocidad == $velocidad_inicial) ? 'flavor-podcast-player__velocidad-opcion--activa' : ''; ?>"
                                data-velocidad="<?php echo esc_attr($velocidad); ?>"
                            >
                                <?php echo esc_html($velocidad); ?>x
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Control de volumen -->
                <div class="flavor-podcast-player__volumen">
                    <button
                        type="button"
                        class="flavor-podcast-player__btn flavor-podcast-player__btn-volumen"
                        title="Volumen"
                        data-action="mute"
                    >
                        <span class="flavor-podcast-player__icono-volumen-alto dashicons dashicons-controls-volumeon"></span>
                        <span class="flavor-podcast-player__icono-volumen-mute dashicons dashicons-controls-volumeoff" style="display: none;"></span>
                    </button>
                    <div class="flavor-podcast-player__volumen-slider">
                        <input
                            type="range"
                            class="flavor-podcast-player__volumen-input"
                            min="0"
                            max="100"
                            value="<?php echo esc_attr($volumen_inicial); ?>"
                            aria-label="Volumen"
                        />
                        <div class="flavor-podcast-player__volumen-nivel" style="width: <?php echo esc_attr($volumen_inicial); ?>%;"></div>
                    </div>
                </div>

                <!-- Botones adicionales -->
                <div class="flavor-podcast-player__acciones-extra">
                    <?php if ($mostrar_compartir) : ?>
                        <button
                            type="button"
                            class="flavor-podcast-player__btn flavor-podcast-player__btn-compartir"
                            title="Compartir"
                            data-action="share"
                        >
                            <span class="dashicons dashicons-share"></span>
                        </button>
                    <?php endif; ?>

                    <?php if ($mostrar_descargar && !empty($url_audio)) : ?>
                        <a
                            href="<?php echo esc_url($url_audio); ?>"
                            class="flavor-podcast-player__btn flavor-podcast-player__btn-descargar"
                            title="Descargar episodio"
                            download
                        >
                            <span class="dashicons dashicons-download"></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Descripción del episodio -->
        <?php if ($mostrar_descripcion && !empty($descripcion_episodio)) : ?>
            <div class="flavor-podcast-player__descripcion">
                <h3 class="flavor-podcast-player__descripcion-titulo">Sobre este episodio</h3>
                <p class="flavor-podcast-player__descripcion-texto">
                    <?php echo esc_html($descripcion_episodio); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Capítulos -->
        <?php if (!empty($capitulos)) : ?>
            <div class="flavor-podcast-player__capitulos">
                <h3 class="flavor-podcast-player__capitulos-titulo">Capítulos</h3>
                <ul class="flavor-podcast-player__capitulos-lista">
                    <?php foreach ($capitulos as $indice => $capitulo) : ?>
                        <li class="flavor-podcast-player__capitulo">
                            <button
                                type="button"
                                class="flavor-podcast-player__capitulo-btn"
                                data-tiempo="<?php echo esc_attr($capitulo['tiempo']); ?>"
                            >
                                <span class="flavor-podcast-player__capitulo-numero"><?php echo esc_html($indice + 1); ?></span>
                                <span class="flavor-podcast-player__capitulo-titulo"><?php echo esc_html($capitulo['titulo']); ?></span>
                                <span class="flavor-podcast-player__capitulo-tiempo"><?php echo esc_html($capitulo['tiempo']); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Enlaces a plataformas -->
        <?php if (!empty($enlaces_plataformas)) : ?>
            <div class="flavor-podcast-player__plataformas">
                <span class="flavor-podcast-player__plataformas-titulo">Escuchar en:</span>
                <div class="flavor-podcast-player__plataformas-lista">
                    <?php foreach ($enlaces_plataformas as $plataforma) : ?>
                        <a
                            href="<?php echo esc_url($plataforma['url']); ?>"
                            class="flavor-podcast-player__plataforma-link"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="<?php echo esc_attr($plataforma['nombre']); ?>"
                        >
                            <span class="dashicons <?php echo esc_attr($plataforma['icono']); ?>"></span>
                            <?php echo esc_html($plataforma['nombre']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Elemento de audio oculto -->
    <?php if (!empty($url_audio)) : ?>
        <audio class="flavor-podcast-player__audio" preload="metadata">
            <source src="<?php echo esc_url($url_audio); ?>" type="audio/mpeg">
            Tu navegador no soporta el elemento de audio.
        </audio>
    <?php endif; ?>
</article>

<style>
.flavor-podcast-player {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.flavor-podcast-player__contenedor {
    padding: 30px;
}

/* Header */
.flavor-podcast-player__header {
    display: flex;
    gap: 25px;
    margin-bottom: 30px;
}

.flavor-podcast-player__portada {
    flex-shrink: 0;
}

.flavor-podcast-player__portada-imagen {
    width: 140px;
    height: 140px;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.flavor-podcast-player__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.flavor-podcast-player__nombre-podcast {
    font-size: 0.875rem;
    color: #667eea;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.flavor-podcast-player__titulo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 12px;
    line-height: 1.3;
}

.flavor-podcast-player__meta {
    display: flex;
    gap: 20px;
    color: #666;
    font-size: 0.9rem;
}

.flavor-podcast-player__fecha,
.flavor-podcast-player__duracion {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Controles principales */
.flavor-podcast-player__controles {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
}

.flavor-podcast-player__botones-principales {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.flavor-podcast-player__btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    color: #1a1a2e;
}

.flavor-podcast-player__btn-retroceder,
.flavor-podcast-player__btn-avanzar {
    width: 50px;
    height: 50px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    position: relative;
}

.flavor-podcast-player__btn-retroceder:hover,
.flavor-podcast-player__btn-avanzar:hover {
    background: #667eea;
    color: #fff;
    transform: scale(1.05);
}

.flavor-podcast-player__btn-label {
    position: absolute;
    font-size: 0.65rem;
    font-weight: 700;
    bottom: 8px;
}

.flavor-podcast-player__btn-play {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    color: #fff;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.flavor-podcast-player__btn-play:hover {
    transform: scale(1.08);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

.flavor-podcast-player__btn-play .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

/* Barra de progreso */
.flavor-podcast-player__progreso {
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-podcast-player__tiempo-actual,
.flavor-podcast-player__tiempo-total {
    font-size: 0.85rem;
    font-weight: 500;
    color: #666;
    min-width: 45px;
}

.flavor-podcast-player__tiempo-total {
    text-align: right;
}

.flavor-podcast-player__barra-contenedor {
    flex: 1;
    position: relative;
    height: 8px;
}

.flavor-podcast-player__barra-fondo {
    position: absolute;
    width: 100%;
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-podcast-player__barra-cargado {
    position: absolute;
    height: 100%;
    background: #c5c5c5;
    border-radius: 4px;
    width: 60%;
}

.flavor-podcast-player__barra-progreso {
    position: absolute;
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    width: 0%;
    transition: width 0.1s linear;
}

.flavor-podcast-player__barra-handle {
    position: absolute;
    width: 16px;
    height: 16px;
    background: #667eea;
    border-radius: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    left: 0%;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.flavor-podcast-player__barra-contenedor:hover .flavor-podcast-player__barra-handle {
    opacity: 1;
}

.flavor-podcast-player__barra-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    margin: 0;
}

/* Controles secundarios */
.flavor-podcast-player__controles-secundarios {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
    margin-top: 20px;
}

/* Velocidad */
.flavor-podcast-player__velocidad {
    position: relative;
}

.flavor-podcast-player__btn-velocidad {
    background: #fff;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.flavor-podcast-player__btn-velocidad:hover {
    background: #667eea;
    color: #fff;
}

.flavor-podcast-player__velocidad-menu {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #fff;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
    z-index: 10;
}

.flavor-podcast-player__velocidad:hover .flavor-podcast-player__velocidad-menu {
    display: flex;
}

.flavor-podcast-player__velocidad-opcion {
    background: none;
    border: none;
    padding: 8px 20px;
    font-size: 0.9rem;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.flavor-podcast-player__velocidad-opcion:hover {
    background: #f0f0f0;
}

.flavor-podcast-player__velocidad-opcion--activa {
    background: #667eea;
    color: #fff;
}

.flavor-podcast-player__velocidad-opcion--activa:hover {
    background: #5a6fd6;
}

/* Volumen */
.flavor-podcast-player__volumen {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-podcast-player__btn-volumen {
    width: 40px;
    height: 40px;
}

.flavor-podcast-player__btn-volumen:hover {
    color: #667eea;
}

.flavor-podcast-player__volumen-slider {
    width: 80px;
    height: 6px;
    background: #ddd;
    border-radius: 3px;
    position: relative;
    overflow: hidden;
}

.flavor-podcast-player__volumen-nivel {
    position: absolute;
    height: 100%;
    background: #667eea;
    border-radius: 3px;
}

.flavor-podcast-player__volumen-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    margin: 0;
    top: 0;
    left: 0;
}

/* Acciones extra */
.flavor-podcast-player__acciones-extra {
    display: flex;
    gap: 10px;
}

.flavor-podcast-player__btn-compartir,
.flavor-podcast-player__btn-descargar {
    width: 40px;
    height: 40px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-decoration: none;
    color: #1a1a2e;
}

.flavor-podcast-player__btn-compartir:hover,
.flavor-podcast-player__btn-descargar:hover {
    background: #667eea;
    color: #fff;
}

/* Descripción */
.flavor-podcast-player__descripcion {
    margin-bottom: 25px;
}

.flavor-podcast-player__descripcion-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 12px;
}

.flavor-podcast-player__descripcion-texto {
    font-size: 0.95rem;
    color: #555;
    line-height: 1.7;
    margin: 0;
}

/* Capítulos */
.flavor-podcast-player__capitulos {
    margin-bottom: 25px;
}

.flavor-podcast-player__capitulos-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 15px;
}

.flavor-podcast-player__capitulos-lista {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-podcast-player__capitulo {
    border-bottom: 1px solid #eee;
}

.flavor-podcast-player__capitulo:last-child {
    border-bottom: none;
}

.flavor-podcast-player__capitulo-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 10px;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background 0.2s ease;
}

.flavor-podcast-player__capitulo-btn:hover {
    background: #f8f9fa;
}

.flavor-podcast-player__capitulo-numero {
    width: 28px;
    height: 28px;
    background: #667eea;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.flavor-podcast-player__capitulo-titulo {
    flex: 1;
    font-size: 0.95rem;
    color: #1a1a2e;
}

.flavor-podcast-player__capitulo-tiempo {
    font-size: 0.85rem;
    color: #888;
    font-family: monospace;
}

/* Plataformas */
.flavor-podcast-player__plataformas {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.flavor-podcast-player__plataformas-titulo {
    font-size: 0.9rem;
    color: #888;
}

.flavor-podcast-player__plataformas-lista {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.flavor-podcast-player__plataforma-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f8f9fa;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #1a1a2e;
    text-decoration: none;
    transition: all 0.2s ease;
}

.flavor-podcast-player__plataforma-link:hover {
    background: #667eea;
    color: #fff;
}

/* Audio oculto */
.flavor-podcast-player__audio {
    display: none;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-podcast-player__contenedor {
        padding: 20px;
    }

    .flavor-podcast-player__header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .flavor-podcast-player__portada-imagen {
        width: 120px;
        height: 120px;
    }

    .flavor-podcast-player__titulo {
        font-size: 1.25rem;
    }

    .flavor-podcast-player__meta {
        justify-content: center;
    }

    .flavor-podcast-player__controles {
        padding: 20px 15px;
    }

    .flavor-podcast-player__controles-secundarios {
        flex-wrap: wrap;
        gap: 15px;
    }

    .flavor-podcast-player__volumen-slider {
        width: 60px;
    }

    .flavor-podcast-player__plataformas {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .flavor-podcast-player__btn-play {
        width: 60px;
        height: 60px;
    }

    .flavor-podcast-player__btn-play .dashicons {
        font-size: 26px;
        width: 26px;
        height: 26px;
    }

    .flavor-podcast-player__btn-retroceder,
    .flavor-podcast-player__btn-avanzar {
        width: 44px;
        height: 44px;
    }

    .flavor-podcast-player__progreso {
        flex-wrap: wrap;
    }

    .flavor-podcast-player__barra-contenedor {
        order: 3;
        width: 100%;
        margin-top: 10px;
    }

    .flavor-podcast-player__capitulo-btn {
        padding: 12px 5px;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const playerContainer = document.getElementById('<?php echo esc_js($player_id); ?>');
        if (!playerContainer) return;

        const audio = playerContainer.querySelector('.flavor-podcast-player__audio');
        const botonPlay = playerContainer.querySelector('[data-action="play-pause"]');
        const iconoPlay = playerContainer.querySelector('.flavor-podcast-player__icono-play');
        const iconoPause = playerContainer.querySelector('.flavor-podcast-player__icono-pause');
        const botonRetroceder = playerContainer.querySelector('[data-action="rewind"]');
        const botonAvanzar = playerContainer.querySelector('[data-action="forward"]');
        const barraInput = playerContainer.querySelector('.flavor-podcast-player__barra-input');
        const barraProgreso = playerContainer.querySelector('.flavor-podcast-player__barra-progreso');
        const barraHandle = playerContainer.querySelector('.flavor-podcast-player__barra-handle');
        const tiempoActual = playerContainer.querySelector('.flavor-podcast-player__tiempo-actual');
        const volumenInput = playerContainer.querySelector('.flavor-podcast-player__volumen-input');
        const volumenNivel = playerContainer.querySelector('.flavor-podcast-player__volumen-nivel');
        const botonMute = playerContainer.querySelector('[data-action="mute"]');
        const iconoVolumenAlto = playerContainer.querySelector('.flavor-podcast-player__icono-volumen-alto');
        const iconoVolumenMute = playerContainer.querySelector('.flavor-podcast-player__icono-volumen-mute');
        const velocidadValor = playerContainer.querySelector('.flavor-podcast-player__velocidad-valor');
        const opcionesVelocidad = playerContainer.querySelectorAll('.flavor-podcast-player__velocidad-opcion');
        const botonesCapitulo = playerContainer.querySelectorAll('.flavor-podcast-player__capitulo-btn');

        let volumenAnterior = <?php echo esc_js($volumen_inicial); ?>;

        // Función para formatear tiempo
        function formatearTiempo(segundos) {
            const minutos = Math.floor(segundos / 60);
            const segs = Math.floor(segundos % 60);
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        // Función para convertir tiempo string a segundos
        function tiempoASegundos(tiempoString) {
            const partes = tiempoString.split(':');
            if (partes.length === 2) {
                return parseInt(partes[0]) * 60 + parseInt(partes[1]);
            }
            return 0;
        }

        if (audio) {
            // Play/Pause
            botonPlay.addEventListener('click', function() {
                if (audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            });

            audio.addEventListener('play', function() {
                iconoPlay.style.display = 'none';
                iconoPause.style.display = 'block';
            });

            audio.addEventListener('pause', function() {
                iconoPlay.style.display = 'block';
                iconoPause.style.display = 'none';
            });

            // Retroceder/Avanzar
            botonRetroceder.addEventListener('click', function() {
                audio.currentTime = Math.max(0, audio.currentTime - 15);
            });

            botonAvanzar.addEventListener('click', function() {
                audio.currentTime = Math.min(audio.duration, audio.currentTime + 30);
            });

            // Actualizar barra de progreso
            audio.addEventListener('timeupdate', function() {
                const porcentaje = (audio.currentTime / audio.duration) * 100;
                barraProgreso.style.width = porcentaje + '%';
                barraHandle.style.left = porcentaje + '%';
                barraInput.value = porcentaje;
                tiempoActual.textContent = formatearTiempo(audio.currentTime);
            });

            // Seek
            barraInput.addEventListener('input', function() {
                const tiempo = (this.value / 100) * audio.duration;
                audio.currentTime = tiempo;
            });

            // Volumen
            volumenInput.addEventListener('input', function() {
                const volumen = this.value / 100;
                audio.volume = volumen;
                volumenNivel.style.width = this.value + '%';
                volumenAnterior = this.value;

                if (volumen === 0) {
                    iconoVolumenAlto.style.display = 'none';
                    iconoVolumenMute.style.display = 'block';
                } else {
                    iconoVolumenAlto.style.display = 'block';
                    iconoVolumenMute.style.display = 'none';
                }
            });

            // Mute
            botonMute.addEventListener('click', function() {
                if (audio.volume > 0) {
                    volumenAnterior = audio.volume * 100;
                    audio.volume = 0;
                    volumenInput.value = 0;
                    volumenNivel.style.width = '0%';
                    iconoVolumenAlto.style.display = 'none';
                    iconoVolumenMute.style.display = 'block';
                } else {
                    audio.volume = volumenAnterior / 100;
                    volumenInput.value = volumenAnterior;
                    volumenNivel.style.width = volumenAnterior + '%';
                    iconoVolumenAlto.style.display = 'block';
                    iconoVolumenMute.style.display = 'none';
                }
            });

            // Velocidad
            opcionesVelocidad.forEach(function(opcion) {
                opcion.addEventListener('click', function() {
                    const velocidad = parseFloat(this.getAttribute('data-velocidad'));
                    audio.playbackRate = velocidad;
                    velocidadValor.textContent = velocidad + 'x';

                    opcionesVelocidad.forEach(function(opt) {
                        opt.classList.remove('flavor-podcast-player__velocidad-opcion--activa');
                    });
                    this.classList.add('flavor-podcast-player__velocidad-opcion--activa');
                });
            });

            // Capítulos
            botonesCapitulo.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const tiempoString = this.getAttribute('data-tiempo');
                    const segundos = tiempoASegundos(tiempoString);
                    audio.currentTime = segundos;
                    if (audio.paused) {
                        audio.play();
                    }
                });
            });

            // Inicializar volumen
            audio.volume = <?php echo esc_js($volumen_inicial); ?> / 100;
        }

        // Compartir (sin audio)
        const botonCompartir = playerContainer.querySelector('[data-action="share"]');
        if (botonCompartir) {
            botonCompartir.addEventListener('click', function() {
                if (navigator.share) {
                    navigator.share({
                        title: '<?php echo esc_js($titulo_episodio); ?>',
                        text: '<?php echo esc_js($nombre_podcast); ?> - <?php echo esc_js($titulo_episodio); ?>',
                        url: window.location.href
                    });
                } else {
                    // Fallback: copiar URL
                    navigator.clipboard.writeText(window.location.href).then(function() {
                        alert('¡Enlace copiado al portapapeles!');
                    });
                }
            });
        }
    });
})();
</script>
