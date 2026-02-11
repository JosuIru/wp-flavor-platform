<?php
/**
 * Template: Radio Podcasts Grid
 * Lista/grid de podcasts de la radio comunitaria
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : __('Podcasts de la Radio Comunitaria', 'flavor-chat-ia');
$descripcion_seccion = isset($args['descripcion_seccion']) ? $args['descripcion_seccion'] : __('Escucha nuestros programas cuando quieras', 'flavor-chat-ia');
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$mostrar_suscriptores = isset($args['mostrar_suscriptores']) ? $args['mostrar_suscriptores'] : true;
$mostrar_episodios = isset($args['mostrar_episodios']) ? $args['mostrar_episodios'] : true;
$vista_inicial = isset($args['vista_inicial']) ? $args['vista_inicial'] : 'grid';

// Datos de demostración de podcasts
$podcasts_demo = isset($args['podcasts']) ? $args['podcasts'] : array(
    array(
        'id' => 1,
        'titulo' => 'Voces del Barrio',
        'descripcion' => 'Historias y testimonios de nuestra comunidad. Cada semana traemos las voces que construyen nuestro barrio.',
        'portada' => 'https://picsum.photos/seed/podcast1/400/400',
        'categoria' => 'comunidad',
        'conductor' => 'María González',
        'episodios' => 45,
        'suscriptores' => 1250,
        'duracion_promedio' => '35 min',
        'frecuencia' => 'Semanal',
        'ultimo_episodio' => '2024-01-15',
        'destacado' => true
    ),
    array(
        'id' => 2,
        'titulo' => 'Cultura Viva',
        'descripcion' => 'Arte, música y tradiciones locales. Un recorrido por la riqueza cultural de nuestra región.',
        'portada' => 'https://picsum.photos/seed/podcast2/400/400',
        'categoria' => 'cultura',
        'conductor' => 'Carlos Ramírez',
        'episodios' => 78,
        'suscriptores' => 2340,
        'duracion_promedio' => '45 min',
        'frecuencia' => 'Semanal',
        'ultimo_episodio' => '2024-01-18',
        'destacado' => false
    ),
    array(
        'id' => 3,
        'titulo' => 'Noticiero Comunitario',
        'descripcion' => 'Las noticias que importan a nuestra comunidad. Información local, veraz y oportuna.',
        'portada' => 'https://picsum.photos/seed/podcast3/400/400',
        'categoria' => 'noticias',
        'conductor' => 'Ana Martínez',
        'episodios' => 156,
        'suscriptores' => 3890,
        'duracion_promedio' => '25 min',
        'frecuencia' => 'Diario',
        'ultimo_episodio' => '2024-01-20',
        'destacado' => true
    ),
    array(
        'id' => 4,
        'titulo' => 'Salud y Bienestar',
        'descripcion' => 'Consejos de salud, medicina tradicional y bienestar para toda la familia.',
        'portada' => 'https://picsum.photos/seed/podcast4/400/400',
        'categoria' => 'salud',
        'conductor' => 'Dr. Roberto Sánchez',
        'episodios' => 32,
        'suscriptores' => 980,
        'duracion_promedio' => '40 min',
        'frecuencia' => 'Quincenal',
        'ultimo_episodio' => '2024-01-10',
        'destacado' => false
    ),
    array(
        'id' => 5,
        'titulo' => 'Música Sin Fronteras',
        'descripcion' => 'Un viaje musical por diferentes géneros y épocas. Desde lo tradicional hasta lo contemporáneo.',
        'portada' => 'https://picsum.photos/seed/podcast5/400/400',
        'categoria' => 'musica',
        'conductor' => 'DJ Pepe Luna',
        'episodios' => 89,
        'suscriptores' => 4520,
        'duracion_promedio' => '60 min',
        'frecuencia' => 'Semanal',
        'ultimo_episodio' => '2024-01-19',
        'destacado' => true
    ),
    array(
        'id' => 6,
        'titulo' => 'Juventud al Aire',
        'descripcion' => 'Programa hecho por y para jóvenes. Temas actuales, música y entretenimiento.',
        'portada' => 'https://picsum.photos/seed/podcast6/400/400',
        'categoria' => 'juventud',
        'conductor' => 'Colectivo Juvenil',
        'episodios' => 23,
        'suscriptores' => 750,
        'duracion_promedio' => '50 min',
        'frecuencia' => 'Semanal',
        'ultimo_episodio' => '2024-01-17',
        'destacado' => false
    )
);

// Categorías disponibles para filtros
$categorias_disponibles = array(
    'todos' => __('Todos', 'flavor-chat-ia'),
    'comunidad' => __('Comunidad', 'flavor-chat-ia'),
    'cultura' => __('Cultura', 'flavor-chat-ia'),
    'noticias' => __('Noticias', 'flavor-chat-ia'),
    'salud' => __('Salud', 'flavor-chat-ia'),
    'musica' => __('Música', 'flavor-chat-ia'),
    'juventud' => __('Juventud', 'flavor-chat-ia')
);

// Función auxiliar para formatear números grandes
function flavor_formatear_numero($numero) {
    if ($numero >= 1000) {
        return number_format($numero / 1000, 1) . 'K';
    }
    return $numero;
}
?>

<section class="flavor-podcasts-section">
    <div class="flavor-podcasts-container">

        <!-- Encabezado de la sección -->
        <header class="flavor-podcasts-header">
            <div class="flavor-podcasts-header-content">
                <h2 class="flavor-podcasts-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
                <?php if ($descripcion_seccion): ?>
                    <p class="flavor-podcasts-descripcion"><?php echo esc_html($descripcion_seccion); ?></p>
                <?php endif; ?>
            </div>

            <!-- Controles de vista -->
            <div class="flavor-podcasts-controles">
                <div class="flavor-podcasts-vista-toggle">
                    <button type="button"
                            class="flavor-vista-btn <?php echo $vista_inicial === 'grid' ? 'flavor-vista-btn--activo' : ''; ?>"
                            data-vista="grid"
                            aria-label="<?php esc_attr_e('Vista de cuadrícula', 'flavor-chat-ia'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                    <button type="button"
                            class="flavor-vista-btn <?php echo $vista_inicial === 'lista' ? 'flavor-vista-btn--activo' : ''; ?>"
                            data-vista="lista"
                            aria-label="<?php esc_attr_e('Vista de lista', 'flavor-chat-ia'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <?php if ($mostrar_filtros): ?>
        <!-- Filtros por categoría -->
        <nav class="flavor-podcasts-filtros" aria-label="<?php esc_attr_e('Filtrar podcasts por categoría', 'flavor-chat-ia'); ?>">
            <ul class="flavor-filtros-lista">
                <?php foreach ($categorias_disponibles as $categoria_slug => $categoria_nombre): ?>
                    <li class="flavor-filtros-item">
                        <button type="button"
                                class="flavor-filtro-btn <?php echo $categoria_slug === 'todos' ? 'flavor-filtro-btn--activo' : ''; ?>"
                                data-categoria="<?php echo esc_attr($categoria_slug); ?>">
                            <?php echo esc_html($categoria_nombre); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Grid/Lista de podcasts -->
        <div class="flavor-podcasts-grid flavor-podcasts-grid--<?php echo esc_attr($vista_inicial); ?> flavor-podcasts-grid--cols-<?php echo esc_attr($columnas); ?>">
            <?php foreach ($podcasts_demo as $podcast): ?>
                <article class="flavor-podcast-card <?php echo $podcast['destacado'] ? 'flavor-podcast-card--destacado' : ''; ?>"
                         data-categoria="<?php echo esc_attr($podcast['categoria']); ?>"
                         data-podcast-id="<?php echo esc_attr($podcast['id']); ?>">

                    <!-- Portada del podcast -->
                    <div class="flavor-podcast-portada">
                        <img src="<?php echo esc_url($podcast['portada']); ?>"
                             alt="<?php echo esc_attr($podcast['titulo']); ?>"
                             class="flavor-podcast-imagen"
                             loading="lazy">

                        <?php if ($podcast['destacado']): ?>
                            <span class="flavor-podcast-badge flavor-podcast-badge--destacado">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <?php esc_html_e('Destacado', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>

                        <!-- Botón de reproducción overlay -->
                        <button type="button" class="flavor-podcast-play-overlay" aria-label="<?php esc_attr_e('Reproducir último episodio', 'flavor-chat-ia'); ?>">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                        </button>

                        <!-- Categoría -->
                        <span class="flavor-podcast-categoria-tag">
                            <?php echo esc_html($categorias_disponibles[$podcast['categoria']] ?? $podcast['categoria']); ?>
                        </span>
                    </div>

                    <!-- Contenido del podcast -->
                    <div class="flavor-podcast-contenido">
                        <header class="flavor-podcast-header">
                            <h3 class="flavor-podcast-titulo">
                                <a href="#" class="flavor-podcast-enlace"><?php echo esc_html($podcast['titulo']); ?></a>
                            </h3>
                            <p class="flavor-podcast-conductor">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 14c3.31 0 6-2.69 6-6s-2.69-6-6-6-6 2.69-6 6 2.69 6 6 6z"></path>
                                    <path d="M2 21c0-3.31 4.03-6 10-6s10 2.69 10 6"></path>
                                </svg>
                                <?php echo esc_html($podcast['conductor']); ?>
                            </p>
                        </header>

                        <p class="flavor-podcast-descripcion"><?php echo esc_html($podcast['descripcion']); ?></p>

                        <!-- Metadatos -->
                        <div class="flavor-podcast-meta">
                            <?php if ($mostrar_episodios): ?>
                                <span class="flavor-podcast-meta-item" title="<?php esc_attr_e('Episodios', 'flavor-chat-ia'); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                        <line x1="8" y1="21" x2="16" y2="21"></line>
                                        <line x1="12" y1="17" x2="12" y2="21"></line>
                                    </svg>
                                    <?php echo esc_html($podcast['episodios']); ?> <?php esc_html_e('episodios', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($mostrar_suscriptores): ?>
                                <span class="flavor-podcast-meta-item" title="<?php esc_attr_e('Suscriptores', 'flavor-chat-ia'); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <?php echo esc_html(flavor_formatear_numero($podcast['suscriptores'])); ?>
                                </span>
                            <?php endif; ?>

                            <span class="flavor-podcast-meta-item" title="<?php esc_attr_e('Duración promedio', 'flavor-chat-ia'); ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html($podcast['duracion_promedio']); ?>
                            </span>
                        </div>

                        <!-- Información adicional -->
                        <div class="flavor-podcast-info-extra">
                            <span class="flavor-podcast-frecuencia">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo esc_html($podcast['frecuencia']); ?>
                            </span>
                        </div>

                        <!-- Acciones -->
                        <footer class="flavor-podcast-acciones">
                            <button type="button" class="flavor-btn flavor-btn--primario flavor-btn--sm flavor-podcast-suscribir">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <?php esc_html_e('Suscribirse', 'flavor-chat-ia'); ?>
                            </button>
                            <button type="button" class="flavor-btn flavor-btn--secundario flavor-btn--sm flavor-podcast-ver-episodios">
                                <?php esc_html_e('Ver episodios', 'flavor-chat-ia'); ?>
                            </button>
                        </footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Mensaje cuando no hay resultados -->
        <div class="flavor-podcasts-sin-resultados" style="display: none;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <p><?php esc_html_e('No se encontraron podcasts en esta categoría.', 'flavor-chat-ia'); ?></p>
        </div>

        <!-- Cargar más -->
        <div class="flavor-podcasts-cargar-mas">
            <button type="button" class="flavor-btn flavor-btn--outline flavor-btn--lg">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                <?php esc_html_e('Cargar más podcasts', 'flavor-chat-ia'); ?>
            </button>
        </div>

    </div>
</section>

<style>
/* ===== ESTILOS PARA PODCASTS GRID ===== */
.flavor-podcasts-section {
    padding: 2rem 0;
    background-color: var(--flavor-bg-secondary, #f8f9fa);
}

.flavor-podcasts-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Encabezado */
.flavor-podcasts-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.flavor-podcasts-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text-primary, #1a1a2e);
    margin: 0 0 0.5rem 0;
}

.flavor-podcasts-descripcion {
    color: var(--flavor-text-secondary, #6c757d);
    margin: 0;
    font-size: 1rem;
}

/* Controles de vista */
.flavor-podcasts-controles {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.flavor-podcasts-vista-toggle {
    display: flex;
    background: var(--flavor-bg-primary, #ffffff);
    border-radius: 8px;
    padding: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.flavor-vista-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: var(--flavor-text-secondary, #6c757d);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-vista-btn:hover {
    color: var(--flavor-primary, #4f46e5);
    background: var(--flavor-bg-secondary, #f8f9fa);
}

.flavor-vista-btn--activo {
    background: var(--flavor-primary, #4f46e5);
    color: white;
}

.flavor-vista-btn--activo:hover {
    background: var(--flavor-primary-dark, #4338ca);
    color: white;
}

/* Filtros */
.flavor-podcasts-filtros {
    margin-bottom: 2rem;
}

.flavor-filtros-lista {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-filtro-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--flavor-border, #e2e8f0);
    background: var(--flavor-bg-primary, #ffffff);
    color: var(--flavor-text-secondary, #6c757d);
    border-radius: 20px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-filtro-btn:hover {
    border-color: var(--flavor-primary, #4f46e5);
    color: var(--flavor-primary, #4f46e5);
}

.flavor-filtro-btn--activo {
    background: var(--flavor-primary, #4f46e5);
    border-color: var(--flavor-primary, #4f46e5);
    color: white;
}

/* Grid de podcasts */
.flavor-podcasts-grid {
    display: grid;
    gap: 1.5rem;
}

.flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

.flavor-podcasts-grid--lista {
    grid-template-columns: 1fr;
}

/* Card de podcast */
.flavor-podcast-card {
    background: var(--flavor-bg-primary, #ffffff);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.flavor-podcast-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.flavor-podcast-card--destacado {
    border: 2px solid var(--flavor-primary, #4f46e5);
}

/* Vista lista */
.flavor-podcasts-grid--lista .flavor-podcast-card {
    display: grid;
    grid-template-columns: 180px 1fr;
}

/* Portada */
.flavor-podcast-portada {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.flavor-podcasts-grid--lista .flavor-podcast-portada {
    aspect-ratio: 1;
}

.flavor-podcast-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.flavor-podcast-card:hover .flavor-podcast-imagen {
    transform: scale(1.05);
}

.flavor-podcast-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--flavor-warning, #f59e0b);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 12px;
    z-index: 2;
}

.flavor-podcast-categoria-tag {
    position: absolute;
    bottom: 12px;
    left: 12px;
    padding: 4px 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 0.75rem;
    border-radius: 12px;
    z-index: 2;
}

.flavor-podcast-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(79, 70, 229, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 3;
}

.flavor-podcast-card:hover .flavor-podcast-play-overlay {
    opacity: 1;
}

.flavor-podcast-play-overlay:hover {
    transform: translate(-50%, -50%) scale(1.1);
    background: var(--flavor-primary, #4f46e5);
}

/* Contenido */
.flavor-podcast-contenido {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-podcast-header {
    margin-bottom: 0.25rem;
}

.flavor-podcast-titulo {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.flavor-podcast-enlace {
    color: var(--flavor-text-primary, #1a1a2e);
    text-decoration: none;
}

.flavor-podcast-enlace:hover {
    color: var(--flavor-primary, #4f46e5);
}

.flavor-podcast-conductor {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: var(--flavor-text-secondary, #6c757d);
    margin: 0;
}

.flavor-podcast-contenido .flavor-podcast-descripcion {
    font-size: 0.875rem;
    color: var(--flavor-text-secondary, #6c757d);
    line-height: 1.5;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Meta información */
.flavor-podcast-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-podcast-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8125rem;
    color: var(--flavor-text-muted, #94a3b8);
}

.flavor-podcast-info-extra {
    padding-top: 0.5rem;
    border-top: 1px solid var(--flavor-border, #e2e8f0);
}

.flavor-podcast-frecuencia {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8125rem;
    color: var(--flavor-text-secondary, #6c757d);
}

/* Acciones */
.flavor-podcast-acciones {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
    padding-top: 0.75rem;
}

/* Botones */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 500;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.flavor-btn--sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.flavor-btn--lg {
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
}

.flavor-btn--primario {
    background: var(--flavor-primary, #4f46e5);
    color: white;
}

.flavor-btn--primario:hover {
    background: var(--flavor-primary-dark, #4338ca);
}

.flavor-btn--secundario {
    background: var(--flavor-bg-secondary, #f1f5f9);
    color: var(--flavor-text-primary, #1a1a2e);
}

.flavor-btn--secundario:hover {
    background: var(--flavor-bg-tertiary, #e2e8f0);
}

.flavor-btn--outline {
    background: transparent;
    border: 2px solid var(--flavor-primary, #4f46e5);
    color: var(--flavor-primary, #4f46e5);
}

.flavor-btn--outline:hover {
    background: var(--flavor-primary, #4f46e5);
    color: white;
}

/* Sin resultados */
.flavor-podcasts-sin-resultados {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--flavor-text-secondary, #6c757d);
}

.flavor-podcasts-sin-resultados svg {
    opacity: 0.5;
    margin-bottom: 1rem;
}

/* Cargar más */
.flavor-podcasts-cargar-mas {
    text-align: center;
    margin-top: 2rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-4 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-podcasts-header {
        flex-direction: column;
    }

    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-3,
    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-podcasts-grid--lista .flavor-podcast-card {
        grid-template-columns: 140px 1fr;
    }

    .flavor-filtros-lista {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.5rem;
        -webkit-overflow-scrolling: touch;
    }

    .flavor-filtro-btn {
        white-space: nowrap;
    }
}

@media (max-width: 480px) {
    .flavor-podcasts-titulo {
        font-size: 1.5rem;
    }

    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-2,
    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-3,
    .flavor-podcasts-grid--grid.flavor-podcasts-grid--cols-4 {
        grid-template-columns: 1fr;
    }

    .flavor-podcasts-grid--lista .flavor-podcast-card {
        grid-template-columns: 1fr;
    }

    .flavor-podcasts-grid--lista .flavor-podcast-portada {
        aspect-ratio: 16/9;
    }

    .flavor-podcast-acciones {
        flex-direction: column;
    }

    .flavor-podcast-acciones .flavor-btn {
        width: 100%;
    }
}
</style>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const contenedor = document.querySelector('.flavor-podcasts-section');
        if (!contenedor) return;

        const gridPodcasts = contenedor.querySelector('.flavor-podcasts-grid');
        const botonesFiltro = contenedor.querySelectorAll('.flavor-filtro-btn');
        const botonesVista = contenedor.querySelectorAll('.flavor-vista-btn');
        const tarjetasPodcast = contenedor.querySelectorAll('.flavor-podcast-card');
        const mensajeSinResultados = contenedor.querySelector('.flavor-podcasts-sin-resultados');

        // Cambio de vista (grid/lista)
        botonesVista.forEach(function(botonVista) {
            botonVista.addEventListener('click', function() {
                const vistaSeleccionada = this.dataset.vista;

                botonesVista.forEach(function(boton) {
                    boton.classList.remove('flavor-vista-btn--activo');
                });
                this.classList.add('flavor-vista-btn--activo');

                gridPodcasts.classList.remove('flavor-podcasts-grid--grid', 'flavor-podcasts-grid--lista');
                gridPodcasts.classList.add('flavor-podcasts-grid--' + vistaSeleccionada);
            });
        });

        // Filtrado por categoría
        botonesFiltro.forEach(function(botonFiltro) {
            botonFiltro.addEventListener('click', function() {
                const categoriaSeleccionada = this.dataset.categoria;

                botonesFiltro.forEach(function(boton) {
                    boton.classList.remove('flavor-filtro-btn--activo');
                });
                this.classList.add('flavor-filtro-btn--activo');

                let tarjetasVisibles = 0;

                tarjetasPodcast.forEach(function(tarjeta) {
                    const categoriaTarjeta = tarjeta.dataset.categoria;

                    if (categoriaSeleccionada === 'todos' || categoriaTarjeta === categoriaSeleccionada) {
                        tarjeta.style.display = '';
                        tarjetasVisibles++;
                    } else {
                        tarjeta.style.display = 'none';
                    }
                });

                // Mostrar mensaje si no hay resultados
                if (mensajeSinResultados) {
                    mensajeSinResultados.style.display = tarjetasVisibles === 0 ? 'block' : 'none';
                }
            });
        });

        // Botones de suscripción
        const botonesSuscribir = contenedor.querySelectorAll('.flavor-podcast-suscribir');
        botonesSuscribir.forEach(function(botonSuscribir) {
            botonSuscribir.addEventListener('click', function() {
                const tarjetaPodcast = this.closest('.flavor-podcast-card');
                const idPodcast = tarjetaPodcast.dataset.podcastId;
                const nombrePodcast = tarjetaPodcast.querySelector('.flavor-podcast-titulo').textContent;

                // Toggle estado de suscripción
                if (this.classList.contains('flavor-podcast-suscrito')) {
                    this.classList.remove('flavor-podcast-suscrito');
                    this.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg> Suscribirse';
                } else {
                    this.classList.add('flavor-podcast-suscrito');
                    this.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg> Suscrito';
                }

                // Emitir evento personalizado
                const eventoSuscripcion = new CustomEvent('flavor:podcast:suscripcion', {
                    detail: {
                        podcastId: idPodcast,
                        nombre: nombrePodcast,
                        suscrito: this.classList.contains('flavor-podcast-suscrito')
                    }
                });
                document.dispatchEvent(eventoSuscripcion);
            });
        });

        // Botones de reproducción
        const botonesPlay = contenedor.querySelectorAll('.flavor-podcast-play-overlay');
        botonesPlay.forEach(function(botonPlay) {
            botonPlay.addEventListener('click', function(evento) {
                evento.preventDefault();
                const tarjetaPodcast = this.closest('.flavor-podcast-card');
                const idPodcast = tarjetaPodcast.dataset.podcastId;
                const nombrePodcast = tarjetaPodcast.querySelector('.flavor-podcast-titulo').textContent;

                // Emitir evento de reproducción
                const eventoReproduccion = new CustomEvent('flavor:podcast:reproducir', {
                    detail: {
                        podcastId: idPodcast,
                        nombre: nombrePodcast
                    }
                });
                document.dispatchEvent(eventoReproduccion);
            });
        });
    });
})();
</script>
