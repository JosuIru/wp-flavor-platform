<?php
/**
 * Template: Reproductor completo (pagina dedicada de reproduccion)
 *
 * Variables disponibles:
 * @var object $episodio          - Datos del episodio actual
 * @var object $serie             - Datos de la serie
 * @var array  $playlist          - Lista de episodios de la serie para navegacion
 * @var int    $indice_actual     - Indice del episodio actual en la playlist
 * @var bool   $puede_interactuar - Si el usuario puede interactuar
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$episodio = $episodio ?? null;
$serie = $serie ?? null;
$playlist = $playlist ?? [];
$indice_actual = $indice_actual ?? 0;
$puede_interactuar = $puede_interactuar ?? is_user_logged_in();

if (!$episodio || empty($episodio->audio_url)) {
    echo '<div class="flavor-aviso flavor-aviso-error">' . esc_html__('Episodio no disponible.', 'flavor-chat-ia') . '</div>';
    return;
}

// Determinar episodios anterior y siguiente
$episodio_anterior = isset($playlist[$indice_actual - 1]) ? $playlist[$indice_actual - 1] : null;
$episodio_siguiente = isset($playlist[$indice_actual + 1]) ? $playlist[$indice_actual + 1] : null;

// Imagen de portada
$imagen_portada = !empty($episodio->imagen_url) ? $episodio->imagen_url : ($serie->imagen_url ?? '');

// Duracion formateada
$duracion_total = '';
if (!empty($episodio->duracion_segundos)) {
    $horas = floor($episodio->duracion_segundos / 3600);
    $minutos = floor(($episodio->duracion_segundos % 3600) / 60);
    $segundos = $episodio->duracion_segundos % 60;
    if ($horas > 0) {
        $duracion_total = sprintf('%d:%02d:%02d', $horas, $minutos, $segundos);
    } else {
        $duracion_total = sprintf('%d:%02d', $minutos, $segundos);
    }
}

$reproductor_id = 'flavor-reproductor-' . intval($episodio->id);
?>

<div class="flavor-podcast-reproductor-completo"
     id="<?php echo esc_attr($reproductor_id); ?>"
     data-episodio-id="<?php echo intval($episodio->id); ?>">

    <!-- Fondo con gradiente basado en portada -->
    <div class="flavor-reproductor-fondo">
        <?php if ($imagen_portada): ?>
        <div class="flavor-reproductor-fondo-imagen" style="background-image: url('<?php echo esc_url($imagen_portada); ?>');"></div>
        <?php endif; ?>
        <div class="flavor-reproductor-fondo-overlay"></div>
    </div>

    <div class="flavor-reproductor-contenedor">

        <!-- Header con navegacion -->
        <header class="flavor-reproductor-header">
            <a href="<?php echo esc_url(add_query_arg('serie', $serie->id ?? 0, home_url('/podcast/'))); ?>"
               class="flavor-reproductor-volver">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php esc_html_e('Volver a la serie', 'flavor-chat-ia'); ?>
            </a>
            <?php if ($serie): ?>
            <span class="flavor-reproductor-serie-nombre">
                <?php echo esc_html($serie->titulo); ?>
            </span>
            <?php endif; ?>
        </header>

        <!-- Area principal del reproductor -->
        <main class="flavor-reproductor-main">

            <!-- Portada grande -->
            <div class="flavor-reproductor-portada">
                <?php if ($imagen_portada): ?>
                <img src="<?php echo esc_url($imagen_portada); ?>"
                     alt="<?php echo esc_attr($episodio->titulo); ?>"
                     class="flavor-reproductor-imagen">
                <?php else: ?>
                <div class="flavor-reproductor-placeholder">
                    <span class="dashicons dashicons-microphone"></span>
                </div>
                <?php endif; ?>

                <!-- Visualizador de audio (barras) -->
                <div class="flavor-reproductor-visualizador">
                    <?php for ($barra = 0; $barra < 20; $barra++): ?>
                    <div class="flavor-visualizador-barra"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Info del episodio -->
            <div class="flavor-reproductor-info">
                <?php if (!empty($episodio->numero_episodio)): ?>
                <span class="flavor-reproductor-numero">
                    <?php echo sprintf(esc_html__('Episodio %d', 'flavor-chat-ia'), intval($episodio->numero_episodio)); ?>
                </span>
                <?php endif; ?>

                <h1 class="flavor-reproductor-titulo"><?php echo esc_html($episodio->titulo); ?></h1>

                <div class="flavor-reproductor-meta">
                    <?php if (!empty($episodio->fecha_publicacion)): ?>
                    <span class="flavor-meta-item">
                        <?php echo esc_html(date_i18n('j F Y', strtotime($episodio->fecha_publicacion))); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (isset($episodio->reproducciones)): ?>
                    <span class="flavor-meta-item">
                        <?php echo esc_html(number_format_i18n($episodio->reproducciones)); ?>
                        <?php esc_html_e('reproducciones', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Controles del reproductor -->
            <div class="flavor-reproductor-controles">

                <!-- Barra de progreso -->
                <div class="flavor-reproductor-progreso">
                    <div class="flavor-reproductor-track">
                        <div class="flavor-reproductor-buffered"></div>
                        <div class="flavor-reproductor-progress-fill"></div>
                        <input type="range"
                               class="flavor-reproductor-seek"
                               min="0"
                               max="100"
                               value="0"
                               step="0.01"
                               aria-label="<?php esc_attr_e('Progreso de reproduccion', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="flavor-reproductor-tiempos">
                        <span class="flavor-tiempo-actual">0:00</span>
                        <span class="flavor-tiempo-total"><?php echo esc_html($duracion_total ?: '0:00'); ?></span>
                    </div>
                </div>

                <!-- Botones principales -->
                <div class="flavor-reproductor-btns-principales">
                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-shuffle <?php echo empty($playlist) ? 'flavor-btn-disabled' : ''; ?>"
                            title="<?php esc_attr_e('Aleatorio', 'flavor-chat-ia'); ?>"
                            <?php echo empty($playlist) ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-randomize"></span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-anterior <?php echo !$episodio_anterior ? 'flavor-btn-disabled' : ''; ?>"
                            title="<?php esc_attr_e('Episodio anterior', 'flavor-chat-ia'); ?>"
                            <?php echo !$episodio_anterior ? 'disabled' : ''; ?>
                            data-episodio-id="<?php echo $episodio_anterior ? intval($episodio_anterior->id) : ''; ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-retroceder"
                            title="<?php esc_attr_e('Retroceder 15 segundos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-undo"></span>
                        <span class="flavor-btn-salto-texto">15</span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn-play"
                            title="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-play flavor-icono-play"></span>
                        <span class="dashicons dashicons-controls-pause flavor-icono-pause"></span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-avanzar"
                            title="<?php esc_attr_e('Avanzar 30 segundos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-redo"></span>
                        <span class="flavor-btn-salto-texto">30</span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-siguiente <?php echo !$episodio_siguiente ? 'flavor-btn-disabled' : ''; ?>"
                            title="<?php esc_attr_e('Episodio siguiente', 'flavor-chat-ia'); ?>"
                            <?php echo !$episodio_siguiente ? 'disabled' : ''; ?>
                            data-episodio-id="<?php echo $episodio_siguiente ? intval($episodio_siguiente->id) : ''; ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </button>

                    <button type="button"
                            class="flavor-reproductor-btn flavor-btn-repetir"
                            title="<?php esc_attr_e('Repetir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-repeat"></span>
                    </button>
                </div>

                <!-- Controles secundarios -->
                <div class="flavor-reproductor-btns-secundarios">
                    <div class="flavor-control-velocidad">
                        <button type="button" class="flavor-btn-velocidad" data-velocidad="1">
                            <span class="flavor-velocidad-valor">1x</span>
                        </button>
                    </div>

                    <div class="flavor-control-volumen">
                        <button type="button" class="flavor-btn-mute" title="<?php esc_attr_e('Silenciar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-controls-volumeon flavor-icono-volumen-on"></span>
                            <span class="dashicons dashicons-controls-volumeoff flavor-icono-volumen-off"></span>
                        </button>
                        <div class="flavor-volumen-slider-wrapper">
                            <input type="range"
                                   class="flavor-volumen-slider"
                                   min="0"
                                   max="100"
                                   value="80"
                                   aria-label="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
                        </div>
                    </div>

                    <div class="flavor-control-acciones">
                        <?php if ($puede_interactuar): ?>
                        <button type="button"
                                class="flavor-btn-accion flavor-btn-like"
                                data-episodio-id="<?php echo intval($episodio->id); ?>"
                                title="<?php esc_attr_e('Me gusta', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-heart"></span>
                        </button>
                        <?php endif; ?>

                        <button type="button"
                                class="flavor-btn-accion flavor-btn-compartir"
                                title="<?php esc_attr_e('Compartir', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-share"></span>
                        </button>

                        <?php if (!empty($episodio->audio_url)): ?>
                        <a href="<?php echo esc_url($episodio->audio_url); ?>"
                           class="flavor-btn-accion flavor-btn-descargar"
                           title="<?php esc_attr_e('Descargar', 'flavor-chat-ia'); ?>"
                           download>
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <?php endif; ?>

                        <button type="button"
                                class="flavor-btn-accion flavor-btn-playlist"
                                title="<?php esc_attr_e('Ver playlist', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-playlist-audio"></span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Playlist lateral -->
        <?php if (!empty($playlist)): ?>
        <aside class="flavor-reproductor-playlist" id="playlist-lateral">
            <div class="flavor-playlist-header">
                <h3>
                    <span class="dashicons dashicons-playlist-audio"></span>
                    <?php esc_html_e('Lista de episodios', 'flavor-chat-ia'); ?>
                </h3>
                <button type="button" class="flavor-playlist-cerrar">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="flavor-playlist-lista">
                <?php foreach ($playlist as $indice_item => $item_episodio):
                    $es_actual = ($item_episodio->id == $episodio->id);
                ?>
                <button type="button"
                        class="flavor-playlist-item <?php echo $es_actual ? 'flavor-playlist-item-actual' : ''; ?>"
                        data-episodio-id="<?php echo intval($item_episodio->id); ?>"
                        data-audio-url="<?php echo esc_url($item_episodio->audio_url ?? ''); ?>">
                    <span class="flavor-playlist-item-num">
                        <?php if ($es_actual): ?>
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php else: ?>
                        <?php echo intval($item_episodio->numero_episodio ?? ($indice_item + 1)); ?>
                        <?php endif; ?>
                    </span>
                    <span class="flavor-playlist-item-titulo">
                        <?php echo esc_html($item_episodio->titulo); ?>
                    </span>
                    <?php if (!empty($item_episodio->duracion_segundos)): ?>
                    <span class="flavor-playlist-item-duracion">
                        <?php echo esc_html(floor($item_episodio->duracion_segundos / 60) . ' min'); ?>
                    </span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </aside>
        <?php endif; ?>

        <!-- Descripcion expandible -->
        <?php if (!empty($episodio->descripcion) || !empty($episodio->notas)): ?>
        <section class="flavor-reproductor-detalles">
            <button type="button" class="flavor-detalles-toggle">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
                <?php esc_html_e('Ver detalles del episodio', 'flavor-chat-ia'); ?>
            </button>
            <div class="flavor-detalles-contenido">
                <?php if (!empty($episodio->descripcion)): ?>
                <div class="flavor-detalles-seccion">
                    <h4><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></h4>
                    <?php echo wp_kses_post($episodio->descripcion); ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($episodio->notas)): ?>
                <div class="flavor-detalles-seccion">
                    <h4><?php esc_html_e('Notas del episodio', 'flavor-chat-ia'); ?></h4>
                    <?php echo wp_kses_post($episodio->notas); ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>

    <!-- Audio element -->
    <audio id="audio-reproductor" class="flavor-audio-elemento" preload="metadata">
        <source src="<?php echo esc_url($episodio->audio_url); ?>" type="audio/mpeg">
    </audio>

</div>

<style>
.flavor-podcast-reproductor-completo {
    position: relative;
    min-height: 100vh;
    background: #0f172a;
    color: #fff;
    overflow: hidden;
}

/* Fondo */
.flavor-reproductor-fondo {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.flavor-reproductor-fondo-imagen {
    position: absolute;
    inset: -50px;
    background-size: cover;
    background-position: center;
    filter: blur(80px);
    opacity: 0.4;
}

.flavor-reproductor-fondo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15,23,42,0.8) 0%, rgba(15,23,42,0.95) 100%);
}

.flavor-reproductor-contenedor {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Header */
.flavor-reproductor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.flavor-reproductor-volver {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.flavor-reproductor-volver:hover {
    color: #fff;
}

.flavor-reproductor-serie-nombre {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.6);
}

/* Main */
.flavor-reproductor-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

/* Portada */
.flavor-reproductor-portada {
    position: relative;
    width: 280px;
    height: 280px;
    margin-bottom: 2rem;
}

@media (min-width: 768px) {
    .flavor-reproductor-portada {
        width: 320px;
        height: 320px;
    }
}

.flavor-reproductor-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.flavor-reproductor-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-reproductor-placeholder .dashicons {
    font-size: 100px;
    width: 100px;
    height: 100px;
    opacity: 0.8;
}

.flavor-reproductor-visualizador {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 40px;
    opacity: 0;
    transition: opacity 0.3s;
}

.flavor-podcast-reproductor-completo.reproduciendo .flavor-reproductor-visualizador {
    opacity: 1;
}

.flavor-visualizador-barra {
    width: 4px;
    background: linear-gradient(to top, #6366f1, #818cf8);
    border-radius: 2px;
    animation: visualizer 0.5s ease-in-out infinite alternate;
}

.flavor-visualizador-barra:nth-child(odd) {
    animation-delay: 0.1s;
}

.flavor-visualizador-barra:nth-child(3n) {
    animation-delay: 0.2s;
}

@keyframes visualizer {
    from { height: 10px; }
    to { height: 35px; }
}

/* Info */
.flavor-reproductor-info {
    margin-bottom: 2.5rem;
}

.flavor-reproductor-numero {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(99,102,241,0.3);
    border-radius: 20px;
    font-size: 0.8rem;
    margin-bottom: 0.75rem;
}

.flavor-reproductor-titulo {
    margin: 0 0 0.75rem;
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1.3;
}

@media (min-width: 768px) {
    .flavor-reproductor-titulo {
        font-size: 1.75rem;
    }
}

.flavor-reproductor-meta {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.6);
}

/* Controles */
.flavor-reproductor-controles {
    width: 100%;
    max-width: 600px;
}

/* Progreso */
.flavor-reproductor-progreso {
    margin-bottom: 1.5rem;
}

.flavor-reproductor-track {
    position: relative;
    height: 6px;
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
    overflow: hidden;
    cursor: pointer;
}

.flavor-reproductor-buffered {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: rgba(255,255,255,0.15);
    width: 0%;
}

.flavor-reproductor-progress-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #818cf8);
    width: 0%;
    transition: width 0.1s linear;
}

.flavor-reproductor-seek {
    position: absolute;
    top: -8px;
    left: 0;
    width: 100%;
    height: 22px;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.flavor-reproductor-tiempos {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
    font-variant-numeric: tabular-nums;
}

/* Botones principales */
.flavor-reproductor-btns-principales {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.flavor-reproductor-btn {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.1);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.flavor-reproductor-btn:hover:not(.flavor-btn-disabled) {
    background: rgba(255,255,255,0.2);
    transform: scale(1.05);
}

.flavor-reproductor-btn.flavor-btn-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.flavor-btn-salto-texto {
    position: absolute;
    bottom: 4px;
    font-size: 0.6rem;
    font-weight: 700;
}

.flavor-reproductor-btn-play {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-reproductor-btn-play:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 30px rgba(99,102,241,0.5);
}

.flavor-reproductor-btn-play .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
}

.flavor-reproductor-btn-play .flavor-icono-pause {
    display: none;
}

.flavor-podcast-reproductor-completo.reproduciendo .flavor-reproductor-btn-play .flavor-icono-play {
    display: none;
}

.flavor-podcast-reproductor-completo.reproduciendo .flavor-reproductor-btn-play .flavor-icono-pause {
    display: block;
}

/* Botones secundarios */
.flavor-reproductor-btns-secundarios {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-btn-velocidad {
    padding: 0.4rem 0.8rem;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    background: transparent;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-velocidad:hover {
    background: rgba(255,255,255,0.1);
}

.flavor-control-volumen {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-btn-mute {
    background: none;
    border: none;
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    padding: 0.25rem;
}

.flavor-btn-mute:hover {
    color: #fff;
}

.flavor-btn-mute .flavor-icono-volumen-off {
    display: none;
}

.flavor-podcast-reproductor-completo.silenciado .flavor-btn-mute .flavor-icono-volumen-on {
    display: none;
}

.flavor-podcast-reproductor-completo.silenciado .flavor-btn-mute .flavor-icono-volumen-off {
    display: block;
}

.flavor-volumen-slider {
    width: 80px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
}

.flavor-volumen-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #fff;
    cursor: pointer;
}

.flavor-control-acciones {
    display: flex;
    gap: 0.5rem;
}

.flavor-btn-accion {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.8);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-btn-accion:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.flavor-btn-like.liked {
    background: rgba(239,68,68,0.2);
    color: #ef4444;
}

/* Playlist */
.flavor-reproductor-playlist {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    max-width: 100%;
    height: 100vh;
    background: rgba(15,23,42,0.98);
    z-index: 100;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.flavor-reproductor-playlist.visible {
    right: 0;
}

.flavor-playlist-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.flavor-playlist-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1rem;
}

.flavor-playlist-cerrar {
    background: none;
    border: none;
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    padding: 0.25rem;
}

.flavor-playlist-cerrar:hover {
    color: #fff;
}

.flavor-playlist-lista {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.flavor-playlist-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 0.875rem 1rem;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: rgba(255,255,255,0.8);
    text-align: left;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-playlist-item:hover {
    background: rgba(255,255,255,0.1);
}

.flavor-playlist-item-actual {
    background: rgba(99,102,241,0.2);
    color: #fff;
}

.flavor-playlist-item-num {
    width: 32px;
    height: 32px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.flavor-playlist-item-actual .flavor-playlist-item-num {
    background: #6366f1;
}

.flavor-playlist-item-titulo {
    flex: 1;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-playlist-item-duracion {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.5);
}

/* Detalles */
.flavor-reproductor-detalles {
    margin-top: 2rem;
}

.flavor-detalles-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background: rgba(255,255,255,0.05);
    border: none;
    border-radius: 10px;
    color: rgba(255,255,255,0.7);
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-detalles-toggle:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.flavor-detalles-toggle .dashicons {
    transition: transform 0.3s;
}

.flavor-reproductor-detalles.expandido .flavor-detalles-toggle .dashicons {
    transform: rotate(180deg);
}

.flavor-detalles-contenido {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.flavor-reproductor-detalles.expandido .flavor-detalles-contenido {
    max-height: 1000px;
    padding-top: 1rem;
}

.flavor-detalles-seccion {
    padding: 1.5rem;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.flavor-detalles-seccion h4 {
    margin: 0 0 1rem;
    font-size: 0.95rem;
    color: rgba(255,255,255,0.6);
}

.flavor-detalles-seccion p,
.flavor-detalles-seccion ul,
.flavor-detalles-seccion ol {
    color: rgba(255,255,255,0.85);
    line-height: 1.7;
}

.flavor-audio-elemento {
    display: none;
}
</style>

<script>
(function() {
    var contenedor = document.getElementById('<?php echo esc_js($reproductor_id); ?>');
    if (!contenedor) return;

    var audio = document.getElementById('audio-reproductor');
    var btnPlay = contenedor.querySelector('.flavor-reproductor-btn-play');
    var seekBar = contenedor.querySelector('.flavor-reproductor-seek');
    var progressFill = contenedor.querySelector('.flavor-reproductor-progress-fill');
    var tiempoActual = contenedor.querySelector('.flavor-tiempo-actual');
    var tiempoTotal = contenedor.querySelector('.flavor-tiempo-total');
    var btnVelocidad = contenedor.querySelector('.flavor-btn-velocidad');
    var btnMute = contenedor.querySelector('.flavor-btn-mute');
    var volumenSlider = contenedor.querySelector('.flavor-volumen-slider');
    var btnRetroceder = contenedor.querySelector('.flavor-btn-retroceder');
    var btnAvanzar = contenedor.querySelector('.flavor-btn-avanzar');
    var btnPlaylist = contenedor.querySelector('.flavor-btn-playlist');
    var playlistPanel = contenedor.querySelector('.flavor-reproductor-playlist');
    var playlistCerrar = contenedor.querySelector('.flavor-playlist-cerrar');
    var detallesToggle = contenedor.querySelector('.flavor-detalles-toggle');
    var detallesSeccion = contenedor.querySelector('.flavor-reproductor-detalles');
    var btnRepetir = contenedor.querySelector('.flavor-btn-repetir');

    var repetirActivo = false;

    function formatearTiempo(segundos) {
        segundos = Math.floor(segundos);
        var horas = Math.floor(segundos / 3600);
        var minutos = Math.floor((segundos % 3600) / 60);
        var segs = segundos % 60;
        if (horas > 0) {
            return horas + ':' + (minutos < 10 ? '0' : '') + minutos + ':' + (segs < 10 ? '0' : '') + segs;
        }
        return minutos + ':' + (segs < 10 ? '0' : '') + segs;
    }

    function actualizarProgreso() {
        if (!audio.duration) return;
        var porcentaje = (audio.currentTime / audio.duration) * 100;
        progressFill.style.width = porcentaje + '%';
        seekBar.value = porcentaje;
        tiempoActual.textContent = formatearTiempo(audio.currentTime);
    }

    btnPlay.addEventListener('click', function() {
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    });

    audio.addEventListener('play', function() {
        contenedor.classList.add('reproduciendo');
    });

    audio.addEventListener('pause', function() {
        contenedor.classList.remove('reproduciendo');
    });

    audio.addEventListener('timeupdate', actualizarProgreso);

    audio.addEventListener('loadedmetadata', function() {
        tiempoTotal.textContent = formatearTiempo(audio.duration);
    });

    audio.addEventListener('ended', function() {
        if (repetirActivo) {
            audio.currentTime = 0;
            audio.play();
        }
    });

    seekBar.addEventListener('input', function() {
        if (audio.duration) {
            audio.currentTime = (this.value / 100) * audio.duration;
        }
    });

    if (btnVelocidad) {
        var velocidades = [1, 1.25, 1.5, 1.75, 2, 0.75];
        var indiceVelocidad = 0;
        btnVelocidad.addEventListener('click', function() {
            indiceVelocidad = (indiceVelocidad + 1) % velocidades.length;
            audio.playbackRate = velocidades[indiceVelocidad];
            this.querySelector('.flavor-velocidad-valor').textContent = velocidades[indiceVelocidad] + 'x';
        });
    }

    if (btnMute) {
        btnMute.addEventListener('click', function() {
            audio.muted = !audio.muted;
            contenedor.classList.toggle('silenciado', audio.muted);
        });
    }

    if (volumenSlider) {
        volumenSlider.addEventListener('input', function() {
            audio.volume = this.value / 100;
        });
    }

    if (btnRetroceder) {
        btnRetroceder.addEventListener('click', function() {
            audio.currentTime = Math.max(0, audio.currentTime - 15);
        });
    }

    if (btnAvanzar) {
        btnAvanzar.addEventListener('click', function() {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 30);
        });
    }

    if (btnRepetir) {
        btnRepetir.addEventListener('click', function() {
            repetirActivo = !repetirActivo;
            this.classList.toggle('activo', repetirActivo);
            this.style.background = repetirActivo ? 'rgba(99,102,241,0.3)' : '';
        });
    }

    if (btnPlaylist && playlistPanel) {
        btnPlaylist.addEventListener('click', function() {
            playlistPanel.classList.toggle('visible');
        });

        if (playlistCerrar) {
            playlistCerrar.addEventListener('click', function() {
                playlistPanel.classList.remove('visible');
            });
        }
    }

    if (detallesToggle && detallesSeccion) {
        detallesToggle.addEventListener('click', function() {
            detallesSeccion.classList.toggle('expandido');
        });
    }
})();
</script>
