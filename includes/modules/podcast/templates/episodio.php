<?php
/**
 * Template: Detalle de episodio con reproductor
 *
 * Variables disponibles:
 * @var object $episodio          - Datos del episodio
 * @var object $serie             - Datos de la serie
 * @var array  $episodios_relacionados - Episodios de la misma serie
 * @var bool   $puede_interactuar - Si el usuario puede dar like/comentar
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$episodio = $episodio ?? null;
$serie = $serie ?? null;
$episodios_relacionados = $episodios_relacionados ?? [];
$puede_interactuar = $puede_interactuar ?? is_user_logged_in();

if (!$episodio) {
    echo '<div class="flavor-aviso flavor-aviso-error">' . esc_html__('Episodio no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    return;
}

// Formatear duracion
$duracion_formateada = '';
if (!empty($episodio->duracion_segundos)) {
    $minutos_totales = intval($episodio->duracion_segundos / 60);
    if ($minutos_totales >= 60) {
        $horas = floor($minutos_totales / 60);
        $minutos = $minutos_totales % 60;
        $duracion_formateada = sprintf('%dh %02dmin', $horas, $minutos);
    } else {
        $duracion_formateada = $minutos_totales . ' min';
    }
}
?>

<article class="flavor-podcast-episodio-detalle">

    <!-- Breadcrumb / Navegacion -->
    <nav class="flavor-podcast-breadcrumb">
        <a href="<?php echo esc_url(home_url('/podcast/')); ?>" class="flavor-breadcrumb-link">
            <span class="dashicons dashicons-microphone"></span>
            <?php esc_html_e('Podcasts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span class="flavor-breadcrumb-sep">/</span>
        <?php if ($serie): ?>
        <a href="<?php echo esc_url(add_query_arg('serie', $serie->id, home_url('/podcast/'))); ?>" class="flavor-breadcrumb-link">
            <?php echo esc_html($serie->titulo); ?>
        </a>
        <span class="flavor-breadcrumb-sep">/</span>
        <?php endif; ?>
        <span class="flavor-breadcrumb-actual">
            <?php echo esc_html__('Episodio', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' ' . intval($episodio->numero_episodio ?? 1); ?>
        </span>
    </nav>

    <!-- Header del episodio -->
    <header class="flavor-episodio-header-principal">
        <div class="flavor-episodio-portada">
            <?php if (!empty($episodio->imagen_url)): ?>
                <img src="<?php echo esc_url($episodio->imagen_url); ?>"
                     alt="<?php echo esc_attr($episodio->titulo); ?>"
                     class="flavor-episodio-imagen">
            <?php elseif (!empty($serie->imagen_url)): ?>
                <img src="<?php echo esc_url($serie->imagen_url); ?>"
                     alt="<?php echo esc_attr($serie->titulo); ?>"
                     class="flavor-episodio-imagen">
            <?php else: ?>
                <div class="flavor-episodio-placeholder">
                    <span class="dashicons dashicons-format-audio"></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="flavor-episodio-info-principal">
            <?php if (!empty($episodio->numero_episodio)): ?>
            <span class="flavor-episodio-numero-badge">
                <?php echo sprintf(esc_html__('Episodio %d', FLAVOR_PLATFORM_TEXT_DOMAIN), intval($episodio->numero_episodio)); ?>
            </span>
            <?php endif; ?>

            <h1 class="flavor-episodio-titulo"><?php echo esc_html($episodio->titulo); ?></h1>

            <?php if ($serie): ?>
            <p class="flavor-episodio-serie-nombre">
                <a href="<?php echo esc_url(add_query_arg('serie', $serie->id, home_url('/podcast/'))); ?>">
                    <?php echo esc_html($serie->titulo); ?>
                </a>
            </p>
            <?php endif; ?>

            <div class="flavor-episodio-meta-principal">
                <?php if (!empty($episodio->fecha_publicacion)): ?>
                <span class="flavor-meta-item">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n('j F Y', strtotime($episodio->fecha_publicacion))); ?>
                </span>
                <?php endif; ?>

                <?php if ($duracion_formateada): ?>
                <span class="flavor-meta-item">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html($duracion_formateada); ?>
                </span>
                <?php endif; ?>

                <?php if (isset($episodio->reproducciones)): ?>
                <span class="flavor-meta-item">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php echo esc_html(number_format_i18n($episodio->reproducciones)); ?>
                    <?php esc_html_e('reproducciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Reproductor principal -->
    <section class="flavor-episodio-player-section">
        <div class="flavor-podcast-player-completo"
             data-episodio-id="<?php echo intval($episodio->id); ?>"
             data-audio-url="<?php echo esc_url($episodio->audio_url ?? ''); ?>">

            <audio id="flavor-episodio-audio-<?php echo intval($episodio->id); ?>"
                   class="flavor-audio-principal"
                   preload="metadata">
                <source src="<?php echo esc_url($episodio->audio_url ?? ''); ?>" type="audio/mpeg">
                <?php esc_html_e('Tu navegador no soporta el elemento de audio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </audio>

            <div class="flavor-player-controles-principales">
                <button type="button" class="flavor-player-btn-retroceder" title="<?php esc_attr_e('Retroceder 15s', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-skipback"></span>
                    <span class="flavor-player-salto">15</span>
                </button>

                <button type="button" class="flavor-player-btn-play-principal" title="<?php esc_attr_e('Reproducir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>

                <button type="button" class="flavor-player-btn-avanzar" title="<?php esc_attr_e('Avanzar 30s', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-skipforward"></span>
                    <span class="flavor-player-salto">30</span>
                </button>
            </div>

            <div class="flavor-player-barra-progreso">
                <span class="flavor-player-tiempo-actual">0:00</span>
                <div class="flavor-player-track">
                    <div class="flavor-player-progreso-fill"></div>
                    <input type="range" class="flavor-player-seek" min="0" max="100" value="0" step="0.1">
                </div>
                <span class="flavor-player-tiempo-total">0:00</span>
            </div>

            <div class="flavor-player-controles-secundarios">
                <div class="flavor-player-velocidad">
                    <button type="button" class="flavor-player-btn-velocidad" data-speed="1">1x</button>
                </div>

                <div class="flavor-player-volumen">
                    <button type="button" class="flavor-player-btn-mute" title="<?php esc_attr_e('Silenciar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-controls-volumeon"></span>
                    </button>
                    <input type="range" class="flavor-player-volumen-slider" min="0" max="100" value="80">
                </div>
            </div>
        </div>
    </section>

    <!-- Acciones -->
    <?php if ($puede_interactuar): ?>
    <div class="flavor-episodio-acciones">
        <button type="button"
                class="flavor-btn flavor-btn-outline flavor-btn-like-episodio"
                data-episodio-id="<?php echo intval($episodio->id); ?>">
            <span class="dashicons dashicons-heart"></span>
            <?php esc_html_e('Me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if (isset($episodio->likes) && $episodio->likes > 0): ?>
            <span class="flavor-like-count"><?php echo intval($episodio->likes); ?></span>
            <?php endif; ?>
        </button>

        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-compartir-episodio">
            <span class="dashicons dashicons-share"></span>
            <?php esc_html_e('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>

        <?php if (!empty($episodio->audio_url)): ?>
        <a href="<?php echo esc_url($episodio->audio_url); ?>"
           class="flavor-btn flavor-btn-outline"
           download>
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Descripcion -->
    <?php if (!empty($episodio->descripcion)): ?>
    <section class="flavor-episodio-descripcion-seccion">
        <h2 class="flavor-seccion-titulo">
            <span class="dashicons dashicons-text"></span>
            <?php esc_html_e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="flavor-episodio-descripcion-contenido">
            <?php echo wp_kses_post($episodio->descripcion); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Notas del episodio -->
    <?php if (!empty($episodio->notas)): ?>
    <section class="flavor-episodio-notas-seccion">
        <h2 class="flavor-seccion-titulo">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e('Notas del episodio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="flavor-episodio-notas-contenido">
            <?php echo wp_kses_post($episodio->notas); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Episodios relacionados -->
    <?php if (!empty($episodios_relacionados)): ?>
    <section class="flavor-episodios-relacionados-seccion">
        <h2 class="flavor-seccion-titulo">
            <span class="dashicons dashicons-playlist-audio"></span>
            <?php esc_html_e('Mas episodios de esta serie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="flavor-episodios-relacionados-lista">
            <?php foreach ($episodios_relacionados as $episodio_relacionado):
                if ($episodio_relacionado->id == $episodio->id) continue;
            ?>
            <article class="flavor-episodio-relacionado-item">
                <div class="flavor-episodio-relacionado-numero">
                    <?php echo intval($episodio_relacionado->numero_episodio ?? 0); ?>
                </div>
                <div class="flavor-episodio-relacionado-info">
                    <h3>
                        <a href="<?php echo esc_url(add_query_arg('episodio', $episodio_relacionado->id)); ?>">
                            <?php echo esc_html($episodio_relacionado->titulo); ?>
                        </a>
                    </h3>
                    <span class="flavor-episodio-relacionado-fecha">
                        <?php echo esc_html(date_i18n('j M Y', strtotime($episodio_relacionado->fecha_publicacion))); ?>
                    </span>
                </div>
                <button type="button"
                        class="flavor-btn-play-mini"
                        data-audio="<?php echo esc_url($episodio_relacionado->audio_url ?? ''); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</article>

<style>
.flavor-podcast-episodio-detalle {
    max-width: 900px;
    margin: 0 auto;
    padding: 1.5rem;
}

.flavor-podcast-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-breadcrumb-link {
    color: var(--podcast-primary, #6366f1);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.flavor-breadcrumb-link:hover {
    text-decoration: underline;
}

.flavor-breadcrumb-sep {
    color: #cbd5e1;
}

.flavor-episodio-header-principal {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(129,140,248,0.08));
    border-radius: 16px;
}

@media (max-width: 640px) {
    .flavor-episodio-header-principal {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

.flavor-episodio-portada {
    width: 220px;
    height: 220px;
    flex-shrink: 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(99,102,241,0.25);
}

.flavor-episodio-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-episodio-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--podcast-primary, #6366f1), var(--podcast-secondary, #818cf8));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-episodio-placeholder .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
}

.flavor-episodio-info-principal {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.flavor-episodio-numero-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--podcast-primary, #6366f1);
    color: #fff;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    width: fit-content;
}

.flavor-episodio-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.75rem;
    color: #1e293b;
    line-height: 1.3;
}

.flavor-episodio-serie-nombre {
    margin: 0 0 1rem;
    font-size: 1rem;
}

.flavor-episodio-serie-nombre a {
    color: var(--podcast-primary, #6366f1);
    text-decoration: none;
}

.flavor-episodio-serie-nombre a:hover {
    text-decoration: underline;
}

.flavor-episodio-meta-principal {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
}

.flavor-meta-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #64748b;
    font-size: 0.9rem;
}

.flavor-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Player principal */
.flavor-episodio-player-section {
    margin-bottom: 2rem;
}

.flavor-podcast-player-completo {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.flavor-audio-principal {
    display: none;
}

.flavor-player-controles-principales {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-bottom: 1.25rem;
}

.flavor-player-btn-retroceder,
.flavor-player-btn-avanzar {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.flavor-player-btn-retroceder:hover,
.flavor-player-btn-avanzar:hover {
    border-color: var(--podcast-primary, #6366f1);
    color: var(--podcast-primary, #6366f1);
}

.flavor-player-salto {
    position: absolute;
    bottom: -4px;
    font-size: 0.65rem;
    font-weight: 600;
}

.flavor-player-btn-play-principal {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--podcast-primary, #6366f1), var(--podcast-secondary, #818cf8));
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-player-btn-play-principal:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(99,102,241,0.4);
}

.flavor-player-btn-play-principal .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-player-barra-progreso {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.flavor-player-tiempo-actual,
.flavor-player-tiempo-total {
    font-size: 0.85rem;
    color: #64748b;
    font-variant-numeric: tabular-nums;
    min-width: 45px;
}

.flavor-player-track {
    flex: 1;
    position: relative;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-player-progreso-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, var(--podcast-primary, #6366f1), var(--podcast-secondary, #818cf8));
    width: 0%;
    transition: width 0.1s linear;
}

.flavor-player-seek {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.flavor-player-controles-secundarios {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-player-btn-velocidad {
    padding: 0.35rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.flavor-player-btn-velocidad:hover {
    border-color: var(--podcast-primary, #6366f1);
    color: var(--podcast-primary, #6366f1);
}

.flavor-player-volumen {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-player-btn-mute {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
}

.flavor-player-volumen-slider {
    width: 80px;
    height: 4px;
    -webkit-appearance: none;
    appearance: none;
    background: #e2e8f0;
    border-radius: 2px;
}

.flavor-player-volumen-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--podcast-primary, #6366f1);
    cursor: pointer;
}

/* Acciones */
.flavor-episodio-acciones {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.flavor-like-count {
    background: #f1f5f9;
    padding: 0.1rem 0.5rem;
    border-radius: 10px;
    font-size: 0.8rem;
    margin-left: 0.25rem;
}

/* Secciones */
.flavor-episodio-descripcion-seccion,
.flavor-episodio-notas-seccion,
.flavor-episodios-relacionados-seccion {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem;
    font-size: 1.1rem;
    color: #1e293b;
}

.flavor-seccion-titulo .dashicons {
    color: var(--podcast-primary, #6366f1);
}

.flavor-episodio-descripcion-contenido,
.flavor-episodio-notas-contenido {
    color: #475569;
    line-height: 1.7;
}

/* Episodios relacionados */
.flavor-episodios-relacionados-lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-episodio-relacionado-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem;
    background: #fff;
    border-radius: 10px;
    transition: box-shadow 0.2s;
}

.flavor-episodio-relacionado-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.flavor-episodio-relacionado-numero {
    width: 36px;
    height: 36px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #64748b;
    flex-shrink: 0;
}

.flavor-episodio-relacionado-info {
    flex: 1;
    min-width: 0;
}

.flavor-episodio-relacionado-info h3 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
}

.flavor-episodio-relacionado-info h3 a {
    color: #1e293b;
    text-decoration: none;
}

.flavor-episodio-relacionado-info h3 a:hover {
    color: var(--podcast-primary, #6366f1);
}

.flavor-episodio-relacionado-fecha {
    font-size: 0.8rem;
    color: #94a3b8;
}
</style>
