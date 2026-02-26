<?php
/**
 * Template: Widget del ultimo episodio
 *
 * Variables disponibles:
 * @var object $episodio         - Datos del episodio mas reciente
 * @var object $serie            - Datos de la serie del episodio
 * @var string $estilo           - Estilo del widget: 'card', 'banner', 'compact', 'featured'
 * @var bool   $mostrar_serie    - Si mostrar info de la serie
 * @var bool   $mostrar_boton_serie - Si mostrar boton para ver la serie
 * @var string $titulo_widget    - Titulo personalizado del widget
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
$estilo = $estilo ?? 'card';
$mostrar_serie = $mostrar_serie ?? true;
$mostrar_boton_serie = $mostrar_boton_serie ?? true;
$titulo_widget = $titulo_widget ?? __('Ultimo episodio', 'flavor-chat-ia');

if (!$episodio) {
    return; // No mostrar nada si no hay episodio
}

// Imagen de portada
$imagen_portada = !empty($episodio->imagen_url) ? $episodio->imagen_url : ($serie->imagen_url ?? '');

// Duracion formateada
$duracion_texto = '';
if (!empty($episodio->duracion_segundos)) {
    $minutos_totales = intval($episodio->duracion_segundos / 60);
    if ($minutos_totales >= 60) {
        $horas = floor($minutos_totales / 60);
        $minutos = $minutos_totales % 60;
        $duracion_texto = sprintf('%dh %02dmin', $horas, $minutos);
    } else {
        $duracion_texto = $minutos_totales . ' min';
    }
}

// Tiempo relativo
$fecha_publicacion = strtotime($episodio->fecha_publicacion ?? '');
$tiempo_transcurrido = '';
if ($fecha_publicacion) {
    $diferencia = time() - $fecha_publicacion;
    if ($diferencia < 86400) {
        $tiempo_transcurrido = __('Hoy', 'flavor-chat-ia');
    } elseif ($diferencia < 172800) {
        $tiempo_transcurrido = __('Ayer', 'flavor-chat-ia');
    } elseif ($diferencia < 604800) {
        $tiempo_transcurrido = sprintf(__('Hace %d dias', 'flavor-chat-ia'), floor($diferencia / 86400));
    } else {
        $tiempo_transcurrido = date_i18n('j M Y', $fecha_publicacion);
    }
}

// ID unico
$widget_id = 'flavor-ultimo-episodio-' . intval($episodio->id);
?>

<div class="flavor-podcast-ultimo-episodio flavor-ultimo-<?php echo esc_attr($estilo); ?>"
     id="<?php echo esc_attr($widget_id); ?>"
     data-episodio-id="<?php echo intval($episodio->id); ?>">

    <?php if ($estilo === 'featured'): ?>
    <!-- Estilo Featured (destacado grande) -->
    <div class="flavor-ultimo-featured">
        <div class="flavor-ultimo-featured-fondo">
            <?php if ($imagen_portada): ?>
            <div class="flavor-ultimo-featured-fondo-img" style="background-image: url('<?php echo esc_url($imagen_portada); ?>');"></div>
            <?php endif; ?>
            <div class="flavor-ultimo-featured-overlay"></div>
        </div>

        <div class="flavor-ultimo-featured-contenido">
            <span class="flavor-ultimo-badge">
                <span class="dashicons dashicons-controls-play"></span>
                <?php echo esc_html($titulo_widget); ?>
            </span>

            <div class="flavor-ultimo-featured-main">
                <?php if ($imagen_portada): ?>
                <div class="flavor-ultimo-featured-cover">
                    <img src="<?php echo esc_url($imagen_portada); ?>" alt="">
                    <button type="button"
                            class="flavor-btn-play-grande"
                            data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="flavor-ultimo-featured-info">
                    <?php if ($mostrar_serie && $serie): ?>
                    <span class="flavor-ultimo-serie-nombre">
                        <?php echo esc_html($serie->titulo); ?>
                    </span>
                    <?php endif; ?>

                    <h2 class="flavor-ultimo-titulo-grande">
                        <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id, home_url('/podcast/'))); ?>">
                            <?php echo esc_html($episodio->titulo); ?>
                        </a>
                    </h2>

                    <?php if (!empty($episodio->descripcion)): ?>
                    <p class="flavor-ultimo-extracto">
                        <?php echo esc_html(wp_trim_words(strip_tags($episodio->descripcion), 30, '...')); ?>
                    </p>
                    <?php endif; ?>

                    <div class="flavor-ultimo-meta-featured">
                        <?php if ($tiempo_transcurrido): ?>
                        <span class="flavor-meta-item"><?php echo esc_html($tiempo_transcurrido); ?></span>
                        <?php endif; ?>
                        <?php if ($duracion_texto): ?>
                        <span class="flavor-meta-item"><?php echo esc_html($duracion_texto); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($episodio->numero_episodio)): ?>
                        <span class="flavor-meta-item">
                            <?php echo sprintf(esc_html__('Ep. %d', 'flavor-chat-ia'), intval($episodio->numero_episodio)); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-ultimo-acciones">
                        <button type="button"
                                class="flavor-btn flavor-btn-primary flavor-btn-play-episodio"
                                data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                            <span class="dashicons dashicons-controls-play"></span>
                            <?php esc_html_e('Escuchar ahora', 'flavor-chat-ia'); ?>
                        </button>
                        <?php if ($mostrar_boton_serie && $serie): ?>
                        <a href="<?php echo esc_url(add_query_arg('serie', $serie->id, home_url('/podcast/'))); ?>"
                           class="flavor-btn flavor-btn-outline flavor-btn-ver-serie">
                            <?php esc_html_e('Ver serie', 'flavor-chat-ia'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($estilo === 'banner'): ?>
    <!-- Estilo Banner (horizontal) -->
    <div class="flavor-ultimo-banner">
        <div class="flavor-ultimo-banner-contenido">
            <span class="flavor-ultimo-badge-mini">
                <span class="dashicons dashicons-format-audio"></span>
                <?php echo esc_html($titulo_widget); ?>
            </span>

            <div class="flavor-ultimo-banner-main">
                <?php if ($imagen_portada): ?>
                <div class="flavor-ultimo-banner-cover">
                    <img src="<?php echo esc_url($imagen_portada); ?>" alt="">
                </div>
                <?php endif; ?>

                <div class="flavor-ultimo-banner-info">
                    <?php if ($mostrar_serie && $serie): ?>
                    <span class="flavor-ultimo-serie-mini"><?php echo esc_html($serie->titulo); ?></span>
                    <?php endif; ?>
                    <h3 class="flavor-ultimo-titulo-banner">
                        <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id, home_url('/podcast/'))); ?>">
                            <?php echo esc_html($episodio->titulo); ?>
                        </a>
                    </h3>
                    <div class="flavor-ultimo-meta-banner">
                        <?php if ($tiempo_transcurrido): ?>
                        <span><?php echo esc_html($tiempo_transcurrido); ?></span>
                        <?php endif; ?>
                        <?php if ($duracion_texto): ?>
                        <span><?php echo esc_html($duracion_texto); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="button"
                        class="flavor-btn-play-banner"
                        data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
            </div>
        </div>
    </div>

    <?php elseif ($estilo === 'compact'): ?>
    <!-- Estilo Compact (muy reducido) -->
    <div class="flavor-ultimo-compact">
        <div class="flavor-ultimo-compact-play">
            <button type="button"
                    class="flavor-btn-play-compact"
                    data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                <?php if ($imagen_portada): ?>
                <img src="<?php echo esc_url($imagen_portada); ?>" alt="" class="flavor-compact-cover">
                <?php endif; ?>
                <span class="flavor-compact-overlay">
                    <span class="dashicons dashicons-controls-play"></span>
                </span>
            </button>
        </div>
        <div class="flavor-ultimo-compact-info">
            <span class="flavor-compact-badge"><?php esc_html_e('Nuevo', 'flavor-chat-ia'); ?></span>
            <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id, home_url('/podcast/'))); ?>"
               class="flavor-compact-titulo">
                <?php echo esc_html(wp_trim_words($episodio->titulo, 8)); ?>
            </a>
            <?php if ($duracion_texto): ?>
            <span class="flavor-compact-duracion"><?php echo esc_html($duracion_texto); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Estilo Card (default) -->
    <div class="flavor-ultimo-card">
        <?php if ($titulo_widget): ?>
        <div class="flavor-ultimo-card-header">
            <h3 class="flavor-ultimo-card-titulo">
                <span class="dashicons dashicons-microphone"></span>
                <?php echo esc_html($titulo_widget); ?>
            </h3>
        </div>
        <?php endif; ?>

        <div class="flavor-ultimo-card-body">
            <div class="flavor-ultimo-card-cover">
                <?php if ($imagen_portada): ?>
                <img src="<?php echo esc_url($imagen_portada); ?>" alt="">
                <?php else: ?>
                <div class="flavor-ultimo-card-placeholder">
                    <span class="dashicons dashicons-format-audio"></span>
                </div>
                <?php endif; ?>
                <button type="button"
                        class="flavor-btn-play-card-overlay"
                        data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
            </div>

            <div class="flavor-ultimo-card-info">
                <?php if ($mostrar_serie && $serie): ?>
                <a href="<?php echo esc_url(add_query_arg('serie', $serie->id, home_url('/podcast/'))); ?>"
                   class="flavor-ultimo-card-serie">
                    <?php echo esc_html($serie->titulo); ?>
                </a>
                <?php endif; ?>

                <h4 class="flavor-ultimo-card-ep-titulo">
                    <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id, home_url('/podcast/'))); ?>">
                        <?php echo esc_html($episodio->titulo); ?>
                    </a>
                </h4>

                <div class="flavor-ultimo-card-meta">
                    <?php if (!empty($episodio->numero_episodio)): ?>
                    <span class="flavor-badge-sm">
                        <?php echo sprintf(esc_html__('Ep. %d', 'flavor-chat-ia'), intval($episodio->numero_episodio)); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($tiempo_transcurrido): ?>
                    <span><?php echo esc_html($tiempo_transcurrido); ?></span>
                    <?php endif; ?>
                    <?php if ($duracion_texto): ?>
                    <span><?php echo esc_html($duracion_texto); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flavor-ultimo-card-footer">
            <button type="button"
                    class="flavor-btn flavor-btn-primary flavor-btn-sm flavor-btn-play-episodio"
                    data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>">
                <span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e('Escuchar', 'flavor-chat-ia'); ?>
            </button>
            <?php if ($mostrar_boton_serie && $serie): ?>
            <a href="<?php echo esc_url(add_query_arg('serie', $serie->id, home_url('/podcast/'))); ?>"
               class="flavor-btn flavor-btn-outline flavor-btn-sm">
                <?php esc_html_e('Ver serie', 'flavor-chat-ia'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
.flavor-podcast-ultimo-episodio {
    --ultimo-primary: var(--podcast-primary, #6366f1);
    --ultimo-secondary: var(--podcast-secondary, #818cf8);
}

/* ========================
   Estilo Featured
   ======================== */
.flavor-ultimo-featured {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    min-height: 400px;
}

.flavor-ultimo-featured-fondo {
    position: absolute;
    inset: 0;
}

.flavor-ultimo-featured-fondo-img {
    position: absolute;
    inset: -20px;
    background-size: cover;
    background-position: center;
    filter: blur(30px);
    opacity: 0.6;
}

.flavor-ultimo-featured-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(99,102,241,0.9) 0%, rgba(79,70,229,0.85) 100%);
}

.flavor-ultimo-featured-contenido {
    position: relative;
    z-index: 1;
    padding: 2rem;
    color: #fff;
}

.flavor-ultimo-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.flavor-ultimo-featured-main {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

@media (max-width: 768px) {
    .flavor-ultimo-featured-main {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

.flavor-ultimo-featured-cover {
    position: relative;
    width: 200px;
    height: 200px;
    flex-shrink: 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 16px 48px rgba(0,0,0,0.3);
}

.flavor-ultimo-featured-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-btn-play-grande {
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

.flavor-ultimo-featured-cover:hover .flavor-btn-play-grande {
    opacity: 1;
}

.flavor-btn-play-grande .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}

.flavor-ultimo-featured-info {
    flex: 1;
}

.flavor-ultimo-serie-nombre {
    display: block;
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 0.5rem;
}

.flavor-ultimo-titulo-grande {
    margin: 0 0 1rem;
    font-size: 1.75rem;
    line-height: 1.3;
}

.flavor-ultimo-titulo-grande a {
    color: inherit;
    text-decoration: none;
}

.flavor-ultimo-titulo-grande a:hover {
    text-decoration: underline;
}

.flavor-ultimo-extracto {
    margin: 0 0 1.25rem;
    font-size: 1rem;
    line-height: 1.6;
    opacity: 0.9;
}

.flavor-ultimo-meta-featured {
    display: flex;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .flavor-ultimo-meta-featured {
        justify-content: center;
    }
}

.flavor-ultimo-acciones {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .flavor-ultimo-acciones {
        justify-content: center;
    }
}

.flavor-ultimo-featured .flavor-btn-outline {
    border-color: rgba(255,255,255,0.4);
    color: #fff;
}

.flavor-ultimo-featured .flavor-btn-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.6);
}

/* ========================
   Estilo Banner
   ======================== */
.flavor-ultimo-banner {
    background: linear-gradient(135deg, var(--ultimo-primary), var(--ultimo-secondary));
    border-radius: 12px;
    overflow: hidden;
}

.flavor-ultimo-banner-contenido {
    padding: 1rem 1.5rem;
}

.flavor-ultimo-badge-mini {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.8);
    margin-bottom: 0.75rem;
}

.flavor-ultimo-badge-mini .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-ultimo-banner-main {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.flavor-ultimo-banner-cover {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.flavor-ultimo-banner-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-ultimo-banner-info {
    flex: 1;
    min-width: 0;
    color: #fff;
}

.flavor-ultimo-serie-mini {
    display: block;
    font-size: 0.75rem;
    opacity: 0.7;
    margin-bottom: 0.15rem;
}

.flavor-ultimo-titulo-banner {
    margin: 0 0 0.25rem;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-ultimo-titulo-banner a {
    color: inherit;
    text-decoration: none;
}

.flavor-ultimo-meta-banner {
    display: flex;
    gap: 0.75rem;
    font-size: 0.8rem;
    opacity: 0.7;
}

.flavor-btn-play-banner {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    background: #fff;
    color: var(--ultimo-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.flavor-btn-play-banner:hover {
    transform: scale(1.05);
}

.flavor-btn-play-banner .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

/* ========================
   Estilo Compact
   ======================== */
.flavor-ultimo-compact {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.flavor-btn-play-compact {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    border: none;
    background: linear-gradient(135deg, var(--ultimo-primary), var(--ultimo-secondary));
    cursor: pointer;
    flex-shrink: 0;
}

.flavor-compact-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-compact-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s;
}

.flavor-btn-play-compact:hover .flavor-compact-overlay {
    opacity: 1;
}

.flavor-ultimo-compact-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.flavor-compact-badge {
    display: inline-block;
    padding: 0.15rem 0.4rem;
    background: rgba(99,102,241,0.1);
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--ultimo-primary);
    text-transform: uppercase;
    width: fit-content;
}

.flavor-compact-titulo {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-compact-titulo:hover {
    color: var(--ultimo-primary);
}

.flavor-compact-duracion {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* ========================
   Estilo Card (default)
   ======================== */
.flavor-ultimo-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.flavor-ultimo-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-ultimo-card-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.95rem;
    color: #64748b;
}

.flavor-ultimo-card-titulo .dashicons {
    color: var(--ultimo-primary);
}

.flavor-ultimo-card-body {
    padding: 1.25rem;
}

.flavor-ultimo-card-cover {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.flavor-ultimo-card-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-ultimo-card-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--ultimo-primary), var(--ultimo-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-ultimo-card-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}

.flavor-btn-play-card-overlay {
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

.flavor-ultimo-card-cover:hover .flavor-btn-play-card-overlay {
    opacity: 1;
}

.flavor-btn-play-card-overlay .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
}

.flavor-ultimo-card-info {
    text-align: center;
}

.flavor-ultimo-card-serie {
    display: block;
    font-size: 0.85rem;
    color: var(--ultimo-primary);
    text-decoration: none;
    margin-bottom: 0.35rem;
}

.flavor-ultimo-card-serie:hover {
    text-decoration: underline;
}

.flavor-ultimo-card-ep-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.05rem;
    line-height: 1.4;
}

.flavor-ultimo-card-ep-titulo a {
    color: #1e293b;
    text-decoration: none;
}

.flavor-ultimo-card-ep-titulo a:hover {
    color: var(--ultimo-primary);
}

.flavor-ultimo-card-meta {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    font-size: 0.8rem;
    color: #94a3b8;
}

.flavor-badge-sm {
    padding: 0.15rem 0.5rem;
    background: #f1f5f9;
    border-radius: 4px;
    font-size: 0.75rem;
}

.flavor-ultimo-card-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}
</style>

<script>
(function() {
    var widget = document.getElementById('<?php echo esc_js($widget_id); ?>');
    if (!widget) return;

    var btnsPlay = widget.querySelectorAll('[data-audio]');
    btnsPlay.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var audioUrl = this.dataset.audio;
            if (!audioUrl) return;

            // Disparar evento personalizado para el player global
            var evento = new CustomEvent('flavorPodcastPlay', {
                detail: {
                    episodioId: <?php echo intval($episodio->id); ?>,
                    audioUrl: audioUrl,
                    titulo: '<?php echo esc_js($episodio->titulo); ?>',
                    serie: '<?php echo esc_js($serie ? $serie->titulo : ''); ?>'
                }
            });
            document.dispatchEvent(evento);

            // Fallback: reproducir directamente si no hay player global
            if (typeof FlavorPodcast === 'undefined') {
                var audio = new Audio(audioUrl);
                audio.play();
            }
        });
    });
})();
</script>
