<?php
/**
 * Template: Lista de episodios de una serie
 *
 * Variables disponibles:
 * @var object $serie     - Datos de la serie de podcast
 * @var array  $episodios - Lista de episodios de la serie
 * @var int    $pagina_actual - Pagina actual de paginacion
 * @var int    $total_paginas - Total de paginas
 * @var bool   $esta_suscrito - Si el usuario esta suscrito a la serie
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$serie = $serie ?? null;
$episodios = $episodios ?? [];
$pagina_actual = $pagina_actual ?? 1;
$total_paginas = $total_paginas ?? 1;
$esta_suscrito = $esta_suscrito ?? false;

if (!$serie) {
    echo '<div class="flavor-aviso flavor-aviso-error">' . esc_html__('Serie no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    return;
}

$total_episodios = count($episodios);
?>

<div class="flavor-podcast-lista-episodios">

    <!-- Header de la serie -->
    <header class="flavor-serie-header-compacto">
        <div class="flavor-serie-cover-mini">
            <?php if (!empty($serie->imagen_url)): ?>
                <img src="<?php echo esc_url($serie->imagen_url); ?>"
                     alt="<?php echo esc_attr($serie->titulo); ?>">
            <?php else: ?>
                <div class="flavor-serie-placeholder">
                    <span class="dashicons dashicons-microphone"></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="flavor-serie-info-compacto">
            <span class="flavor-badge"><?php echo esc_html(ucfirst($serie->categoria ?? 'podcast')); ?></span>
            <h1 class="flavor-serie-titulo-principal"><?php echo esc_html($serie->titulo); ?></h1>
            <?php if (!empty($serie->autor_nombre)): ?>
            <p class="flavor-serie-autor">
                <?php echo esc_html__('Por', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' ' . esc_html($serie->autor_nombre); ?>
            </p>
            <?php endif; ?>
            <div class="flavor-serie-stats-compacto">
                <span class="flavor-stat">
                    <span class="dashicons dashicons-playlist-audio"></span>
                    <?php echo sprintf(
                        esc_html(_n('%d episodio', '%d episodios', $total_episodios, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $total_episodios
                    ); ?>
                </span>
                <?php if (isset($serie->total_suscriptores)): ?>
                <span class="flavor-stat">
                    <span class="dashicons dashicons-groups"></span>
                    <?php echo sprintf(
                        esc_html(_n('%d suscriptor', '%d suscriptores', $serie->total_suscriptores, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        intval($serie->total_suscriptores)
                    ); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php if (is_user_logged_in()): ?>
        <div class="flavor-serie-acciones-compacto">
            <button type="button"
                    class="flavor-btn <?php echo $esta_suscrito ? 'flavor-btn-outline flavor-suscrito' : 'flavor-btn-primary'; ?> flavor-btn-suscribir"
                    data-serie-id="<?php echo intval($serie->id); ?>">
                <span class="dashicons dashicons-<?php echo $esta_suscrito ? 'yes' : 'heart'; ?>"></span>
                <?php echo $esta_suscrito ? esc_html__('Suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
        <?php endif; ?>
    </header>

    <!-- Filtros y ordenacion -->
    <div class="flavor-episodios-toolbar">
        <div class="flavor-episodios-contador">
            <?php echo sprintf(
                esc_html__('Mostrando %d episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $total_episodios
            ); ?>
        </div>
        <div class="flavor-episodios-ordenar">
            <label for="orden-episodios"><?php esc_html_e('Ordenar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="orden-episodios" class="flavor-select flavor-select-sm">
                <option value="recientes"><?php esc_html_e('Mas recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="antiguos"><?php esc_html_e('Mas antiguos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="populares"><?php esc_html_e('Mas populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
    </div>

    <!-- Lista de episodios -->
    <?php if (!empty($episodios)): ?>
    <div class="flavor-episodios-lista-vertical">
        <?php foreach ($episodios as $indice => $episodio):
            $duracion_minutos = !empty($episodio->duracion_segundos) ? intval($episodio->duracion_segundos / 60) : 0;
            $duracion_texto = '';
            if ($duracion_minutos >= 60) {
                $horas = floor($duracion_minutos / 60);
                $mins = $duracion_minutos % 60;
                $duracion_texto = sprintf('%dh %02dmin', $horas, $mins);
            } elseif ($duracion_minutos > 0) {
                $duracion_texto = $duracion_minutos . ' min';
            }
        ?>
        <article class="flavor-episodio-fila"
                 data-id="<?php echo intval($episodio->id); ?>"
                 data-numero="<?php echo intval($episodio->numero_episodio ?? ($indice + 1)); ?>">

            <div class="flavor-episodio-numero-col">
                <span class="flavor-episodio-num">
                    <?php echo intval($episodio->numero_episodio ?? ($indice + 1)); ?>
                </span>
            </div>

            <div class="flavor-episodio-play-col">
                <button type="button"
                        class="flavor-btn-play-circular"
                        data-audio="<?php echo esc_url($episodio->audio_url ?? ''); ?>"
                        title="<?php esc_attr_e('Reproducir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
            </div>

            <div class="flavor-episodio-info-col">
                <h3 class="flavor-episodio-titulo">
                    <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id)); ?>">
                        <?php echo esc_html($episodio->titulo); ?>
                    </a>
                </h3>
                <?php if (!empty($episodio->descripcion)): ?>
                <p class="flavor-episodio-extracto">
                    <?php echo esc_html(wp_trim_words(strip_tags($episodio->descripcion), 25, '...')); ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="flavor-episodio-meta-col">
                <?php if (!empty($episodio->fecha_publicacion)): ?>
                <span class="flavor-episodio-fecha">
                    <?php echo esc_html(date_i18n('j M Y', strtotime($episodio->fecha_publicacion))); ?>
                </span>
                <?php endif; ?>
                <?php if ($duracion_texto): ?>
                <span class="flavor-episodio-duracion">
                    <?php echo esc_html($duracion_texto); ?>
                </span>
                <?php endif; ?>
            </div>

            <div class="flavor-episodio-stats-col">
                <?php if (isset($episodio->reproducciones)): ?>
                <span class="flavor-episodio-repro" title="<?php esc_attr_e('Reproducciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php echo esc_html(number_format_i18n($episodio->reproducciones)); ?>
                </span>
                <?php endif; ?>
            </div>

            <div class="flavor-episodio-acciones-col">
                <a href="<?php echo esc_url(add_query_arg('episodio', $episodio->id)); ?>"
                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1): ?>
    <nav class="flavor-paginacion">
        <?php if ($pagina_actual > 1): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1)); ?>"
           class="flavor-btn flavor-btn-sm flavor-btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>

        <span class="flavor-paginacion-info">
            <?php echo sprintf(
                esc_html__('Pagina %d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $pagina_actual,
                $total_paginas
            ); ?>
        </span>

        <?php if ($pagina_actual < $total_paginas): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1)); ?>"
           class="flavor-btn flavor-btn-sm flavor-btn-outline">
            <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="flavor-episodios-vacio">
        <span class="dashicons dashicons-format-audio"></span>
        <h3><?php esc_html_e('Esta serie aun no tiene episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('Vuelve pronto para escuchar nuevo contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php if (is_user_logged_in() && !$esta_suscrito): ?>
        <button type="button"
                class="flavor-btn flavor-btn-primary flavor-btn-suscribir"
                data-serie-id="<?php echo intval($serie->id); ?>">
            <span class="dashicons dashicons-bell"></span>
            <?php esc_html_e('Suscribirse para recibir notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<style>
.flavor-podcast-lista-episodios {
    max-width: 1000px;
    margin: 0 auto;
}

/* Header compacto */
.flavor-serie-header-compacto {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(99,102,241,0.06), rgba(129,140,248,0.06));
    border-radius: 16px;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .flavor-serie-header-compacto {
        flex-direction: column;
        text-align: center;
    }
}

.flavor-serie-cover-mini {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(99,102,241,0.2);
}

.flavor-serie-cover-mini img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-serie-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--podcast-primary, #6366f1), var(--podcast-secondary, #818cf8));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-serie-placeholder .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
}

.flavor-serie-info-compacto {
    flex: 1;
}

.flavor-serie-titulo-principal {
    margin: 0.5rem 0;
    font-size: 1.5rem;
    color: #1e293b;
}

.flavor-serie-autor {
    margin: 0 0 0.75rem;
    color: #64748b;
    font-size: 0.95rem;
}

.flavor-serie-stats-compacto {
    display: flex;
    gap: 1.25rem;
}

@media (max-width: 768px) {
    .flavor-serie-stats-compacto {
        justify-content: center;
    }
}

.flavor-stat {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #64748b;
    font-size: 0.9rem;
}

.flavor-stat .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-serie-acciones-compacto {
    flex-shrink: 0;
}

/* Toolbar */
.flavor-episodios-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

@media (max-width: 480px) {
    .flavor-episodios-toolbar {
        flex-direction: column;
        gap: 0.75rem;
    }
}

.flavor-episodios-contador {
    color: #64748b;
    font-size: 0.9rem;
}

.flavor-episodios-ordenar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.flavor-episodios-ordenar label {
    color: #64748b;
    font-size: 0.9rem;
}

.flavor-select-sm {
    padding: 0.4rem 1.75rem 0.4rem 0.75rem;
    font-size: 0.875rem;
}

/* Lista vertical */
.flavor-episodios-lista-vertical {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.flavor-episodio-fila {
    display: grid;
    grid-template-columns: 50px 50px 1fr auto auto auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s, transform 0.2s;
}

.flavor-episodio-fila:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .flavor-episodio-fila {
        grid-template-columns: 40px 40px 1fr;
        grid-template-rows: auto auto;
        gap: 0.75rem;
    }

    .flavor-episodio-meta-col,
    .flavor-episodio-stats-col,
    .flavor-episodio-acciones-col {
        grid-column: 3;
        grid-row: 2;
    }

    .flavor-episodio-meta-col {
        grid-column: 1 / 3;
    }
}

.flavor-episodio-numero-col {
    text-align: center;
}

.flavor-episodio-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border-radius: 50%;
    font-weight: 600;
    color: #64748b;
    font-size: 0.9rem;
}

.flavor-episodio-play-col {
    display: flex;
    justify-content: center;
}

.flavor-btn-play-circular {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: var(--podcast-primary, #6366f1);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, background 0.2s;
}

.flavor-btn-play-circular:hover {
    transform: scale(1.08);
    background: var(--podcast-accent, #4f46e5);
}

.flavor-btn-play-circular .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.flavor-episodio-info-col {
    min-width: 0;
}

.flavor-episodio-titulo {
    margin: 0 0 0.25rem;
    font-size: 1rem;
}

.flavor-episodio-titulo a {
    color: #1e293b;
    text-decoration: none;
}

.flavor-episodio-titulo a:hover {
    color: var(--podcast-primary, #6366f1);
}

.flavor-episodio-extracto {
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flavor-episodio-meta-col {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .flavor-episodio-meta-col {
        flex-direction: row;
        gap: 0.75rem;
    }
}

.flavor-episodio-fecha,
.flavor-episodio-duracion {
    font-size: 0.85rem;
    color: #94a3b8;
}

.flavor-episodio-stats-col {
    min-width: 70px;
}

.flavor-episodio-repro {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: #94a3b8;
}

.flavor-episodio-repro .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Paginacion */
.flavor-paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.flavor-paginacion-info {
    color: #64748b;
    font-size: 0.9rem;
}

/* Estado vacio */
.flavor-episodios-vacio {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border-radius: 16px;
}

.flavor-episodios-vacio .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.flavor-episodios-vacio h3 {
    margin: 0 0 0.5rem;
    color: #1e293b;
}

.flavor-episodios-vacio p {
    margin: 0 0 1.5rem;
    color: #64748b;
}
</style>
