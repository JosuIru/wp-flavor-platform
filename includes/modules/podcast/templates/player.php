<?php
/**
 * Template: Reproductor de audio embebido
 *
 * Variables disponibles:
 * @var object $episodio  - Datos del episodio a reproducir
 * @var object $serie     - Datos de la serie (opcional)
 * @var string $estilo    - Estilo del player: 'compacto', 'normal', 'minimal'
 * @var bool   $autoplay  - Si debe reproducirse automaticamente
 * @var bool   $mostrar_portada - Si mostrar la portada
 * @var bool   $mostrar_titulo - Si mostrar el titulo
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
$estilo = $estilo ?? 'normal';
$autoplay = $autoplay ?? false;
$mostrar_portada = $mostrar_portada ?? true;
$mostrar_titulo = $mostrar_titulo ?? true;

if (!$episodio || empty($episodio->audio_url)) {
    return;
}

// Generar ID unico para el player
$player_id = 'flavor-player-' . intval($episodio->id) . '-' . wp_rand(1000, 9999);

// Obtener imagen de portada
$imagen_portada = !empty($episodio->imagen_url) ? $episodio->imagen_url : ($serie->imagen_url ?? '');

// Formatear duracion
$duracion_formateada = '';
if (!empty($episodio->duracion_segundos)) {
    $minutos_totales = intval($episodio->duracion_segundos / 60);
    $segundos_restantes = intval($episodio->duracion_segundos % 60);
    $duracion_formateada = sprintf('%d:%02d', $minutos_totales, $segundos_restantes);
}
?>

<div class="flavor-podcast-player flavor-player-<?php echo esc_attr($estilo); ?>"
     id="<?php echo esc_attr($player_id); ?>"
     data-episodio-id="<?php echo intval($episodio->id); ?>"
     data-audio-url="<?php echo esc_url($episodio->audio_url); ?>"
     data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">

    <?php if ($estilo === 'minimal'): ?>
    <!-- Player Minimal -->
    <div class="flavor-player-minimal-contenedor">
        <button type="button" class="flavor-player-btn-play-minimal" aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-controls-play flavor-icono-play"></span>
            <span class="dashicons dashicons-controls-pause flavor-icono-pause" style="display:none;"></span>
        </button>
        <div class="flavor-player-minimal-info">
            <?php if ($mostrar_titulo && !empty($episodio->titulo)): ?>
            <span class="flavor-player-minimal-titulo"><?php echo esc_html(wp_trim_words($episodio->titulo, 8)); ?></span>
            <?php endif; ?>
            <span class="flavor-player-minimal-tiempo">
                <span class="flavor-tiempo-actual">0:00</span>
                <?php if ($duracion_formateada): ?>
                / <span class="flavor-tiempo-total"><?php echo esc_html($duracion_formateada); ?></span>
                <?php endif; ?>
            </span>
        </div>
        <div class="flavor-player-minimal-barra">
            <div class="flavor-player-minimal-progreso"></div>
            <input type="range" class="flavor-player-seek" min="0" max="100" value="0" step="0.1">
        </div>
    </div>

    <?php elseif ($estilo === 'compacto'): ?>
    <!-- Player Compacto -->
    <div class="flavor-player-compacto-contenedor">
        <?php if ($mostrar_portada && $imagen_portada): ?>
        <div class="flavor-player-compacto-cover">
            <img src="<?php echo esc_url($imagen_portada); ?>" alt="">
            <button type="button" class="flavor-player-btn-play-overlay" aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-controls-play flavor-icono-play"></span>
                <span class="dashicons dashicons-controls-pause flavor-icono-pause" style="display:none;"></span>
            </button>
        </div>
        <?php endif; ?>

        <div class="flavor-player-compacto-contenido">
            <?php if ($mostrar_titulo): ?>
            <div class="flavor-player-compacto-info">
                <?php if ($serie): ?>
                <span class="flavor-player-serie-nombre"><?php echo esc_html($serie->titulo); ?></span>
                <?php endif; ?>
                <h4 class="flavor-player-titulo"><?php echo esc_html($episodio->titulo); ?></h4>
            </div>
            <?php endif; ?>

            <div class="flavor-player-compacto-controles">
                <?php if (!$mostrar_portada || !$imagen_portada): ?>
                <button type="button" class="flavor-player-btn-play-compacto" aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-controls-play flavor-icono-play"></span>
                    <span class="dashicons dashicons-controls-pause flavor-icono-pause" style="display:none;"></span>
                </button>
                <?php endif; ?>

                <div class="flavor-player-barra-wrapper">
                    <span class="flavor-tiempo-actual">0:00</span>
                    <div class="flavor-player-barra-track">
                        <div class="flavor-player-progreso-fill"></div>
                        <input type="range" class="flavor-player-seek" min="0" max="100" value="0" step="0.1">
                    </div>
                    <span class="flavor-tiempo-total"><?php echo esc_html($duracion_formateada ?: '0:00'); ?></span>
                </div>

                <button type="button" class="flavor-player-btn-volumen" aria-label="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-controls-volumeon"></span>
                </button>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Player Normal (default) -->
    <div class="flavor-player-normal-contenedor">
        <?php if ($mostrar_portada && $imagen_portada): ?>
        <div class="flavor-player-normal-cover">
            <img src="<?php echo esc_url($imagen_portada); ?>" alt="">
        </div>
        <?php endif; ?>

        <div class="flavor-player-normal-main">
            <?php if ($mostrar_titulo): ?>
            <div class="flavor-player-normal-info">
                <?php if ($serie): ?>
                <span class="flavor-player-serie-nombre"><?php echo esc_html($serie->titulo); ?></span>
                <?php endif; ?>
                <h4 class="flavor-player-titulo"><?php echo esc_html($episodio->titulo); ?></h4>
                <?php if (!empty($episodio->numero_episodio)): ?>
                <span class="flavor-player-episodio-num">
                    <?php echo sprintf(esc_html__('Episodio %d', 'flavor-chat-ia'), intval($episodio->numero_episodio)); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flavor-player-normal-controles">
                <div class="flavor-player-btns-principales">
                    <button type="button" class="flavor-player-btn-salto" data-salto="-15" aria-label="<?php esc_attr_e('Retroceder 15 segundos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                        <span class="flavor-salto-label">15</span>
                    </button>

                    <button type="button" class="flavor-player-btn-play-principal" aria-label="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-play flavor-icono-play"></span>
                        <span class="dashicons dashicons-controls-pause flavor-icono-pause" style="display:none;"></span>
                    </button>

                    <button type="button" class="flavor-player-btn-salto" data-salto="30" aria-label="<?php esc_attr_e('Avanzar 30 segundos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                        <span class="flavor-salto-label">30</span>
                    </button>
                </div>

                <div class="flavor-player-barra-progreso">
                    <span class="flavor-tiempo-actual">0:00</span>
                    <div class="flavor-player-track">
                        <div class="flavor-player-progreso-fill"></div>
                        <div class="flavor-player-cargando"></div>
                        <input type="range" class="flavor-player-seek" min="0" max="100" value="0" step="0.1">
                    </div>
                    <span class="flavor-tiempo-total"><?php echo esc_html($duracion_formateada ?: '0:00'); ?></span>
                </div>

                <div class="flavor-player-btns-secundarios">
                    <div class="flavor-player-velocidad">
                        <button type="button" class="flavor-player-btn-velocidad" data-velocidad="1">
                            1x
                        </button>
                    </div>

                    <div class="flavor-player-volumen-wrapper">
                        <button type="button" class="flavor-player-btn-mute" aria-label="<?php esc_attr_e('Silenciar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-controls-volumeon"></span>
                        </button>
                        <input type="range" class="flavor-player-volumen" min="0" max="100" value="80" aria-label="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Audio element (oculto) -->
    <audio class="flavor-player-audio" preload="metadata">
        <source src="<?php echo esc_url($episodio->audio_url); ?>" type="audio/mpeg">
    </audio>
</div>

<style>
/* Variables */
.flavor-podcast-player {
    --player-primary: var(--podcast-primary, #6366f1);
    --player-secondary: var(--podcast-secondary, #818cf8);
    --player-bg: #fff;
    --player-text: #1e293b;
    --player-text-muted: #64748b;
    --player-border: #e2e8f0;
    --player-track: #e2e8f0;
    --player-progress: linear-gradient(90deg, var(--player-primary), var(--player-secondary));
}

.flavor-player-audio {
    display: none;
}

/* ========================
   Player Minimal
   ======================== */
.flavor-player-minimal .flavor-player-minimal-contenedor {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: var(--player-bg);
    border: 1px solid var(--player-border);
    border-radius: 50px;
}

.flavor-player-btn-play-minimal {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: var(--player-primary);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.flavor-player-btn-play-minimal:hover {
    transform: scale(1.05);
}

.flavor-player-minimal-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.flavor-player-minimal-titulo {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--player-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-player-minimal-tiempo {
    font-size: 0.75rem;
    color: var(--player-text-muted);
    font-variant-numeric: tabular-nums;
}

.flavor-player-minimal-barra {
    flex: 1;
    height: 4px;
    background: var(--player-track);
    border-radius: 2px;
    position: relative;
    min-width: 60px;
}

.flavor-player-minimal-progreso {
    height: 100%;
    background: var(--player-progress);
    border-radius: 2px;
    width: 0%;
    transition: width 0.1s linear;
}

.flavor-player-minimal-barra .flavor-player-seek {
    position: absolute;
    top: -6px;
    left: 0;
    width: 100%;
    height: 16px;
    opacity: 0;
    cursor: pointer;
}

/* ========================
   Player Compacto
   ======================== */
.flavor-player-compacto .flavor-player-compacto-contenedor {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--player-bg);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.flavor-player-compacto-cover {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}

.flavor-player-compacto-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-player-btn-play-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.flavor-player-compacto-cover:hover .flavor-player-btn-play-overlay {
    opacity: 1;
}

.flavor-player-btn-play-overlay .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-player-compacto-contenido {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.flavor-player-compacto-info {
    margin-bottom: 0.5rem;
}

.flavor-player-serie-nombre {
    font-size: 0.8rem;
    color: var(--player-primary);
    display: block;
    margin-bottom: 0.15rem;
}

.flavor-player-titulo {
    margin: 0;
    font-size: 0.95rem;
    color: var(--player-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-player-compacto-controles {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-player-btn-play-compacto {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: var(--player-primary);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-player-barra-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-tiempo-actual,
.flavor-tiempo-total {
    font-size: 0.8rem;
    color: var(--player-text-muted);
    font-variant-numeric: tabular-nums;
    min-width: 35px;
}

.flavor-player-barra-track {
    flex: 1;
    height: 6px;
    background: var(--player-track);
    border-radius: 3px;
    position: relative;
    overflow: hidden;
}

.flavor-player-progreso-fill {
    height: 100%;
    background: var(--player-progress);
    border-radius: 3px;
    width: 0%;
    transition: width 0.1s linear;
}

.flavor-player-barra-track .flavor-player-seek {
    position: absolute;
    top: -5px;
    left: 0;
    width: 100%;
    height: 16px;
    opacity: 0;
    cursor: pointer;
}

.flavor-player-btn-volumen {
    background: none;
    border: none;
    color: var(--player-text-muted);
    cursor: pointer;
    padding: 0.25rem;
}

.flavor-player-btn-volumen:hover {
    color: var(--player-text);
}

/* ========================
   Player Normal
   ======================== */
.flavor-player-normal .flavor-player-normal-contenedor {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--player-bg);
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

@media (max-width: 600px) {
    .flavor-player-normal .flavor-player-normal-contenedor {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

.flavor-player-normal-cover {
    width: 140px;
    height: 140px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(99,102,241,0.2);
}

.flavor-player-normal-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-player-normal-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.flavor-player-normal-info {
    margin-bottom: 1rem;
}

.flavor-player-normal-info .flavor-player-titulo {
    font-size: 1.1rem;
    margin: 0.25rem 0 0.35rem;
}

.flavor-player-episodio-num {
    font-size: 0.8rem;
    color: var(--player-text-muted);
}

.flavor-player-normal-controles {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.flavor-player-btns-principales {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.flavor-player-btn-salto {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid var(--player-border);
    background: var(--player-bg);
    color: var(--player-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.flavor-player-btn-salto:hover {
    border-color: var(--player-primary);
    color: var(--player-primary);
}

.flavor-salto-label {
    position: absolute;
    bottom: 0px;
    font-size: 0.6rem;
    font-weight: 600;
}

.flavor-player-btn-play-principal {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: none;
    background: var(--player-progress);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-player-btn-play-principal:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(99,102,241,0.35);
}

.flavor-player-btn-play-principal .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-player-barra-progreso {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-player-track {
    flex: 1;
    height: 8px;
    background: var(--player-track);
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.flavor-player-cargando {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: rgba(99,102,241,0.2);
    width: 0%;
}

.flavor-player-track .flavor-player-progreso-fill {
    position: relative;
    z-index: 1;
}

.flavor-player-track .flavor-player-seek {
    position: absolute;
    top: -4px;
    left: 0;
    width: 100%;
    height: 16px;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.flavor-player-btns-secundarios {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

@media (max-width: 600px) {
    .flavor-player-btns-secundarios {
        justify-content: center;
        gap: 1.5rem;
    }
}

.flavor-player-btn-velocidad {
    padding: 0.35rem 0.75rem;
    border: 1px solid var(--player-border);
    border-radius: 6px;
    background: var(--player-bg);
    color: var(--player-text-muted);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-player-btn-velocidad:hover {
    border-color: var(--player-primary);
    color: var(--player-primary);
}

.flavor-player-volumen-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-player-btn-mute {
    background: none;
    border: none;
    color: var(--player-text-muted);
    cursor: pointer;
    padding: 0.25rem;
}

.flavor-player-btn-mute:hover {
    color: var(--player-text);
}

.flavor-player-volumen {
    width: 80px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: var(--player-track);
    border-radius: 2px;
}

.flavor-player-volumen::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--player-primary);
    cursor: pointer;
}

/* Estado reproduciendo */
.flavor-podcast-player.reproduciendo .flavor-icono-play {
    display: none;
}

.flavor-podcast-player.reproduciendo .flavor-icono-pause {
    display: block !important;
}
</style>

<script>
(function() {
    var container = document.getElementById('<?php echo esc_js($player_id); ?>');
    if (!container) return;

    var audio = container.querySelector('.flavor-player-audio');
    var btnPlay = container.querySelectorAll('[class*="btn-play"]');
    var seekBar = container.querySelector('.flavor-player-seek');
    var progressFill = container.querySelector('.flavor-player-progreso-fill, .flavor-player-minimal-progreso');
    var tiempoActual = container.querySelector('.flavor-tiempo-actual');
    var tiempoTotal = container.querySelector('.flavor-tiempo-total');
    var btnVelocidad = container.querySelector('.flavor-player-btn-velocidad');
    var btnMute = container.querySelector('.flavor-player-btn-mute');
    var volumenSlider = container.querySelector('.flavor-player-volumen');
    var btnsSalto = container.querySelectorAll('.flavor-player-btn-salto');

    function formatearTiempo(segundos) {
        segundos = Math.floor(segundos);
        var minutos = Math.floor(segundos / 60);
        var segs = segundos % 60;
        return minutos + ':' + (segs < 10 ? '0' : '') + segs;
    }

    function actualizarProgreso() {
        if (!audio.duration) return;
        var porcentaje = (audio.currentTime / audio.duration) * 100;
        if (progressFill) progressFill.style.width = porcentaje + '%';
        if (seekBar) seekBar.value = porcentaje;
        if (tiempoActual) tiempoActual.textContent = formatearTiempo(audio.currentTime);
    }

    btnPlay.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        });
    });

    audio.addEventListener('play', function() {
        container.classList.add('reproduciendo');
    });

    audio.addEventListener('pause', function() {
        container.classList.remove('reproduciendo');
    });

    audio.addEventListener('timeupdate', actualizarProgreso);

    audio.addEventListener('loadedmetadata', function() {
        if (tiempoTotal) tiempoTotal.textContent = formatearTiempo(audio.duration);
    });

    if (seekBar) {
        seekBar.addEventListener('input', function() {
            if (audio.duration) {
                audio.currentTime = (this.value / 100) * audio.duration;
            }
        });
    }

    if (btnVelocidad) {
        var velocidades = [1, 1.25, 1.5, 1.75, 2, 0.75];
        var indiceVelocidad = 0;
        btnVelocidad.addEventListener('click', function() {
            indiceVelocidad = (indiceVelocidad + 1) % velocidades.length;
            audio.playbackRate = velocidades[indiceVelocidad];
            this.textContent = velocidades[indiceVelocidad] + 'x';
        });
    }

    if (btnMute) {
        btnMute.addEventListener('click', function() {
            audio.muted = !audio.muted;
            var icono = this.querySelector('.dashicons');
            if (icono) {
                icono.className = audio.muted
                    ? 'dashicons dashicons-controls-volumeoff'
                    : 'dashicons dashicons-controls-volumeon';
            }
        });
    }

    if (volumenSlider) {
        volumenSlider.addEventListener('input', function() {
            audio.volume = this.value / 100;
        });
    }

    btnsSalto.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var salto = parseInt(this.dataset.salto, 10);
            audio.currentTime = Math.max(0, Math.min(audio.duration, audio.currentTime + salto));
        });
    });

    // Autoplay
    if (container.dataset.autoplay === 'true') {
        audio.play().catch(function() {
            // Autoplay bloqueado por el navegador
        });
    }
})();
</script>
