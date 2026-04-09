<?php
/**
 * Template: Catalogo de podcasts/series
 *
 * Variables disponibles:
 * @var array  $series           - Lista de series de podcast
 * @var array  $categorias       - Categorias disponibles
 * @var string $categoria_actual - Categoria seleccionada para filtrar
 * @var string $orden_actual     - Orden actual (recientes, populares)
 * @var string $busqueda         - Termino de busqueda actual
 * @var int    $pagina_actual    - Pagina actual de paginacion
 * @var int    $total_paginas    - Total de paginas
 * @var array  $estadisticas     - Estadisticas globales del catalogo
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$series = $series ?? [];
$categorias = $categorias ?? [
    'noticias' => __('Noticias', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'entrevistas' => __('Entrevistas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'historias' => __('Historias', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'debates' => __('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cultura' => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'educacion' => __('Educacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'entretenimiento' => __('Entretenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'tecnologia' => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
$categoria_actual = $categoria_actual ?? '';
$orden_actual = $orden_actual ?? 'recientes';
$busqueda = $busqueda ?? '';
$pagina_actual = $pagina_actual ?? 1;
$total_paginas = $total_paginas ?? 1;
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-podcast-catalogo">

    <!-- Hero / Header -->
    <header class="flavor-catalogo-hero">
        <div class="flavor-catalogo-hero-contenido">
            <h1 class="flavor-catalogo-titulo">
                <span class="dashicons dashicons-microphone"></span>
                <?php esc_html_e('Podcasts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="flavor-catalogo-descripcion">
                <?php esc_html_e('Descubre y escucha los mejores podcasts de nuestra comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <?php if (!empty($estadisticas)): ?>
            <div class="flavor-catalogo-stats">
                <?php if (isset($estadisticas['total_series'])): ?>
                <div class="flavor-catalogo-stat">
                    <span class="flavor-stat-valor"><?php echo intval($estadisticas['total_series']); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Series', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($estadisticas['total_episodios'])): ?>
                <div class="flavor-catalogo-stat">
                    <span class="flavor-stat-valor"><?php echo intval($estadisticas['total_episodios']); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($estadisticas['total_creadores'])): ?>
                <div class="flavor-catalogo-stat">
                    <span class="flavor-stat-valor"><?php echo intval($estadisticas['total_creadores']); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Creadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Filtros -->
    <section class="flavor-catalogo-filtros">
        <form method="get" class="flavor-filtros-form">
            <!-- Busqueda -->
            <div class="flavor-filtro-busqueda">
                <span class="dashicons dashicons-search"></span>
                <input type="text"
                       name="buscar"
                       id="buscar-podcast"
                       value="<?php echo esc_attr($busqueda); ?>"
                       placeholder="<?php esc_attr_e('Buscar podcasts...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       class="flavor-input-busqueda">
                <?php if (!empty($busqueda)): ?>
                <a href="<?php echo esc_url(remove_query_arg('buscar')); ?>" class="flavor-limpiar-busqueda">
                    <span class="dashicons dashicons-no-alt"></span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Categoria -->
            <div class="flavor-filtro-categoria">
                <select name="categoria" id="filtro-categoria-podcast" class="flavor-select">
                    <option value=""><?php esc_html_e('Todas las categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias as $clave_categoria => $nombre_categoria): ?>
                    <option value="<?php echo esc_attr($clave_categoria); ?>"
                            <?php selected($categoria_actual, $clave_categoria); ?>>
                        <?php echo esc_html($nombre_categoria); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Orden -->
            <div class="flavor-filtro-orden">
                <select name="orden" id="filtro-orden-podcast" class="flavor-select">
                    <option value="recientes" <?php selected($orden_actual, 'recientes'); ?>>
                        <?php esc_html_e('Mas recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </option>
                    <option value="populares" <?php selected($orden_actual, 'populares'); ?>>
                        <?php esc_html_e('Mas populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </option>
                    <option value="alfabetico" <?php selected($orden_actual, 'alfabetico'); ?>>
                        <?php esc_html_e('Alfabetico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </option>
                </select>
            </div>

            <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-filtrar">
                <span class="dashicons dashicons-filter"></span>
                <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </form>
    </section>

    <!-- Categorias destacadas (chips) -->
    <nav class="flavor-categorias-chips">
        <a href="<?php echo esc_url(remove_query_arg('categoria')); ?>"
           class="flavor-chip <?php echo empty($categoria_actual) ? 'flavor-chip-activo' : ''; ?>">
            <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php foreach (array_slice($categorias, 0, 6) as $clave_chip => $nombre_chip): ?>
        <a href="<?php echo esc_url(add_query_arg('categoria', $clave_chip)); ?>"
           class="flavor-chip <?php echo $categoria_actual === $clave_chip ? 'flavor-chip-activo' : ''; ?>">
            <?php echo esc_html($nombre_chip); ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Grid de series -->
    <?php if (!empty($series)): ?>
    <div class="flavor-series-grid" id="series-container">
        <?php foreach ($series as $serie): ?>
        <article class="flavor-serie-card"
                 data-categoria="<?php echo esc_attr($serie->categoria ?? ''); ?>"
                 data-id="<?php echo intval($serie->id); ?>">

            <a href="<?php echo esc_url(add_query_arg('serie', $serie->id)); ?>" class="flavor-serie-link">
                <div class="flavor-serie-cover">
                    <?php if (!empty($serie->imagen_url)): ?>
                    <img src="<?php echo esc_url($serie->imagen_url); ?>"
                         alt="<?php echo esc_attr($serie->titulo); ?>"
                         loading="lazy">
                    <?php else: ?>
                    <div class="flavor-serie-cover-placeholder">
                        <span class="dashicons dashicons-microphone"></span>
                    </div>
                    <?php endif; ?>
                    <div class="flavor-serie-overlay">
                        <span class="dashicons dashicons-controls-play"></span>
                    </div>
                </div>

                <div class="flavor-serie-contenido">
                    <h3 class="flavor-serie-titulo"><?php echo esc_html($serie->titulo); ?></h3>

                    <?php if (!empty($serie->autor_nombre)): ?>
                    <p class="flavor-serie-autor"><?php echo esc_html($serie->autor_nombre); ?></p>
                    <?php endif; ?>

                    <div class="flavor-serie-meta">
                        <?php if (!empty($serie->categoria)): ?>
                        <span class="flavor-badge"><?php echo esc_html(ucfirst($serie->categoria)); ?></span>
                        <?php endif; ?>
                        <?php if (isset($serie->total_episodios)): ?>
                        <span class="flavor-serie-episodios">
                            <?php echo sprintf(esc_html__('%d ep.', FLAVOR_PLATFORM_TEXT_DOMAIN), intval($serie->total_episodios)); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($serie->descripcion)): ?>
                    <p class="flavor-serie-extracto">
                        <?php echo esc_html(wp_trim_words(strip_tags($serie->descripcion), 15, '...')); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </a>

            <?php if (isset($serie->total_suscriptores) && $serie->total_suscriptores > 0): ?>
            <div class="flavor-serie-suscriptores">
                <span class="dashicons dashicons-groups"></span>
                <?php echo esc_html(number_format_i18n($serie->total_suscriptores)); ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1): ?>
    <nav class="flavor-paginacion" aria-label="<?php esc_attr_e('Paginacion de podcasts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <?php if ($pagina_actual > 1): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1)); ?>"
           class="flavor-btn flavor-btn-outline flavor-btn-pag">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>

        <div class="flavor-paginacion-numeros">
            <?php
            $rango_paginas = 2;
            $inicio_pagina = max(1, $pagina_actual - $rango_paginas);
            $fin_pagina = min($total_paginas, $pagina_actual + $rango_paginas);

            if ($inicio_pagina > 1):
            ?>
            <a href="<?php echo esc_url(add_query_arg('pag', 1)); ?>" class="flavor-pag-num">1</a>
            <?php if ($inicio_pagina > 2): ?>
            <span class="flavor-pag-ellipsis">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($numero_pagina = $inicio_pagina; $numero_pagina <= $fin_pagina; $numero_pagina++): ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $numero_pagina)); ?>"
               class="flavor-pag-num <?php echo $numero_pagina === $pagina_actual ? 'flavor-pag-actual' : ''; ?>">
                <?php echo intval($numero_pagina); ?>
            </a>
            <?php endfor; ?>

            <?php if ($fin_pagina < $total_paginas): ?>
            <?php if ($fin_pagina < $total_paginas - 1): ?>
            <span class="flavor-pag-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?php echo esc_url(add_query_arg('pag', $total_paginas)); ?>" class="flavor-pag-num">
                <?php echo intval($total_paginas); ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if ($pagina_actual < $total_paginas): ?>
        <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1)); ?>"
           class="flavor-btn flavor-btn-outline flavor-btn-pag">
            <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <!-- Estado vacio -->
    <div class="flavor-catalogo-vacio">
        <span class="dashicons dashicons-microphone"></span>
        <h3><?php esc_html_e('No se encontraron podcasts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <?php if (!empty($busqueda) || !empty($categoria_actual)): ?>
        <p><?php esc_html_e('Prueba a modificar los filtros de busqueda.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <a href="<?php echo esc_url(remove_query_arg(['buscar', 'categoria', 'orden'])); ?>"
           class="flavor-btn flavor-btn-outline">
            <span class="dashicons dashicons-dismiss"></span>
            <?php esc_html_e('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php else: ?>
        <p><?php esc_html_e('Aun no hay podcasts publicados. Vuelve pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- CTA para crear podcast (usuarios logueados) -->
    <?php if (is_user_logged_in()): ?>
    <section class="flavor-catalogo-cta">
        <div class="flavor-cta-contenido">
            <h2><?php esc_html_e('Crea tu propio podcast', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Comparte tu voz con la comunidad. Crear una serie es facil y gratuito.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('podcast', 'crear-serie')); ?>"
               class="flavor-btn flavor-btn-primary flavor-btn-lg">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Crear nueva serie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </section>
    <?php endif; ?>

</div>

<style>
.flavor-podcast-catalogo {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
}

/* Hero */
.flavor-catalogo-hero {
    background: linear-gradient(135deg, var(--podcast-primary, #6366f1), var(--podcast-accent, #4f46e5));
    border-radius: 20px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    color: #fff;
    text-align: center;
}

.flavor-catalogo-titulo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin: 0 0 0.75rem;
    font-size: 2rem;
}

.flavor-catalogo-titulo .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
}

.flavor-catalogo-descripcion {
    margin: 0 0 1.5rem;
    font-size: 1.1rem;
    opacity: 0.9;
}

.flavor-catalogo-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;
}

.flavor-catalogo-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.flavor-stat-valor {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.flavor-stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

/* Filtros */
.flavor-catalogo-filtros {
    background: #fff;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.flavor-filtros-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

@media (max-width: 768px) {
    .flavor-filtros-form {
        flex-direction: column;
    }

    .flavor-filtro-busqueda,
    .flavor-filtro-categoria,
    .flavor-filtro-orden {
        width: 100%;
    }
}

.flavor-filtro-busqueda {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.flavor-filtro-busqueda .dashicons {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.flavor-input-busqueda {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
}

.flavor-input-busqueda:focus {
    outline: none;
    border-color: var(--podcast-primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.flavor-limpiar-busqueda {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    padding: 0.25rem;
}

.flavor-limpiar-busqueda:hover {
    color: #64748b;
}

.flavor-btn-filtrar {
    white-space: nowrap;
}

/* Chips categorias */
.flavor-categorias-chips {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.flavor-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    border-radius: 20px;
    color: #64748b;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-chip:hover {
    background: #e2e8f0;
    color: #475569;
}

.flavor-chip-activo {
    background: var(--podcast-primary, #6366f1);
    color: #fff;
}

.flavor-chip-activo:hover {
    background: var(--podcast-accent, #4f46e5);
    color: #fff;
}

/* Grid de series */
.flavor-series-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.flavor-serie-card {
    position: relative;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.3s, box-shadow 0.3s;
}

.flavor-serie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

.flavor-serie-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.flavor-serie-cover {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.flavor-serie-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.flavor-serie-card:hover .flavor-serie-cover img {
    transform: scale(1.05);
}

.flavor-serie-cover-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--podcast-primary, #6366f1), var(--podcast-secondary, #818cf8));
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-serie-cover-placeholder .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
}

.flavor-serie-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.flavor-serie-card:hover .flavor-serie-overlay {
    opacity: 1;
}

.flavor-serie-overlay .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #fff;
}

.flavor-serie-contenido {
    padding: 1.25rem;
}

.flavor-serie-titulo {
    margin: 0 0 0.35rem;
    font-size: 1.1rem;
    color: #1e293b;
    line-height: 1.3;
}

.flavor-serie-autor {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
    color: #64748b;
}

.flavor-serie-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.flavor-serie-episodios {
    font-size: 0.85rem;
    color: #64748b;
}

.flavor-serie-extracto {
    margin: 0;
    font-size: 0.85rem;
    color: #94a3b8;
    line-height: 1.5;
}

.flavor-serie-suscriptores {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.75rem;
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    font-size: 0.8rem;
    color: #64748b;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.flavor-serie-suscriptores .dashicons {
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
    margin-bottom: 2rem;
}

.flavor-paginacion-numeros {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.flavor-pag-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 0.5rem;
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.flavor-pag-num:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.flavor-pag-actual {
    background: var(--podcast-primary, #6366f1);
    color: #fff;
}

.flavor-pag-actual:hover {
    background: var(--podcast-accent, #4f46e5);
    color: #fff;
}

.flavor-pag-ellipsis {
    padding: 0 0.5rem;
    color: #94a3b8;
}

.flavor-btn-pag {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

/* Estado vacio */
.flavor-catalogo-vacio {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8fafc;
    border-radius: 16px;
}

.flavor-catalogo-vacio .dashicons {
    font-size: 72px;
    width: 72px;
    height: 72px;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.flavor-catalogo-vacio h3 {
    margin: 0 0 0.5rem;
    color: #1e293b;
}

.flavor-catalogo-vacio p {
    margin: 0 0 1.5rem;
    color: #64748b;
}

/* CTA */
.flavor-catalogo-cta {
    background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(129,140,248,0.08));
    border-radius: 16px;
    padding: 2.5rem;
    text-align: center;
}

.flavor-cta-contenido h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
}

.flavor-cta-contenido p {
    margin: 0 0 1.5rem;
    color: #64748b;
}

.flavor-btn-lg {
    padding: 0.875rem 1.75rem;
    font-size: 1rem;
}
</style>
