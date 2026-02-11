<?php
/**
 * Template: Multimedia Carousel
 * Carrusel de contenido multimedia destacado (fotos, videos)
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : __('Contenido Destacado', 'flavor-chat-ia');
$mostrar_titulo = isset($args['mostrar_titulo']) ? $args['mostrar_titulo'] : true;
$autoplay = isset($args['autoplay']) ? $args['autoplay'] : true;
$intervalo_autoplay = isset($args['intervalo_autoplay']) ? intval($args['intervalo_autoplay']) : 5000;
$mostrar_navegacion = isset($args['mostrar_navegacion']) ? $args['mostrar_navegacion'] : true;
$mostrar_indicadores = isset($args['mostrar_indicadores']) ? $args['mostrar_indicadores'] : true;
$mostrar_miniaturas = isset($args['mostrar_miniaturas']) ? $args['mostrar_miniaturas'] : false;
$pausar_al_hover = isset($args['pausar_al_hover']) ? $args['pausar_al_hover'] : true;
$efecto_transicion = isset($args['efecto_transicion']) ? $args['efecto_transicion'] : 'slide'; // 'slide', 'fade'
$altura_carousel = isset($args['altura']) ? $args['altura'] : '500px';

// Datos de demostración de contenido multimedia
$items_multimedia = isset($args['items']) ? $args['items'] : array(
    array(
        'id' => 1,
        'tipo' => 'imagen',
        'url' => 'https://picsum.photos/seed/carousel1/1200/600',
        'miniatura' => 'https://picsum.photos/seed/carousel1/150/100',
        'titulo' => 'Festival Cultural 2024',
        'descripcion' => 'Celebrando nuestra diversidad cultural con danzas, música y gastronomía tradicional.',
        'autor' => 'Juan Pérez',
        'fecha' => '2024-01-15',
        'categoria' => 'Eventos',
        'enlace' => '#',
        'destacado' => true
    ),
    array(
        'id' => 2,
        'tipo' => 'video',
        'url' => 'https://picsum.photos/seed/carousel2/1200/600',
        'miniatura' => 'https://picsum.photos/seed/carousel2/150/100',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'titulo' => 'Inauguración del Centro Comunitario',
        'descripcion' => 'Revive el emotivo momento de la apertura de nuestro nuevo espacio de encuentro.',
        'autor' => 'María González',
        'fecha' => '2024-01-10',
        'categoria' => 'Comunidad',
        'duracion' => '5:32',
        'enlace' => '#',
        'destacado' => true
    ),
    array(
        'id' => 3,
        'tipo' => 'imagen',
        'url' => 'https://picsum.photos/seed/carousel3/1200/600',
        'miniatura' => 'https://picsum.photos/seed/carousel3/150/100',
        'titulo' => 'Taller de Artesanías',
        'descripcion' => 'Aprendiendo técnicas ancestrales de tejido y cerámica con nuestros maestros artesanos.',
        'autor' => 'Carlos Ramírez',
        'fecha' => '2024-01-08',
        'categoria' => 'Cultura',
        'enlace' => '#',
        'destacado' => false
    ),
    array(
        'id' => 4,
        'tipo' => 'video',
        'url' => 'https://picsum.photos/seed/carousel4/1200/600',
        'miniatura' => 'https://picsum.photos/seed/carousel4/150/100',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'titulo' => 'Concierto de la Orquesta Juvenil',
        'descripcion' => 'Los jóvenes talentos de nuestra comunidad brillaron en el escenario principal.',
        'autor' => 'Ana Martínez',
        'fecha' => '2024-01-05',
        'categoria' => 'Música',
        'duracion' => '12:45',
        'enlace' => '#',
        'destacado' => true
    ),
    array(
        'id' => 5,
        'tipo' => 'imagen',
        'url' => 'https://picsum.photos/seed/carousel5/1200/600',
        'miniatura' => 'https://picsum.photos/seed/carousel5/150/100',
        'titulo' => 'Mural Comunitario',
        'descripcion' => 'El nuevo mural que embellece nuestra plaza principal, creado por artistas locales.',
        'autor' => 'Roberto Sánchez',
        'fecha' => '2024-01-03',
        'categoria' => 'Arte',
        'enlace' => '#',
        'destacado' => false
    )
);

// ID único para el carrusel
$carousel_id = 'flavor-carousel-' . uniqid();
$total_items = count($items_multimedia);
?>

<section class="flavor-carousel-section" id="<?php echo esc_attr($carousel_id); ?>">
    <div class="flavor-carousel-wrapper">

        <?php if ($mostrar_titulo && $titulo_seccion): ?>
        <header class="flavor-carousel-header">
            <h2 class="flavor-carousel-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
        </header>
        <?php endif; ?>

        <!-- Contenedor principal del carrusel -->
        <div class="flavor-carousel-container"
             data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
             data-intervalo="<?php echo esc_attr($intervalo_autoplay); ?>"
             data-pausar-hover="<?php echo $pausar_al_hover ? 'true' : 'false'; ?>"
             data-efecto="<?php echo esc_attr($efecto_transicion); ?>"
             style="--flavor-carousel-altura: <?php echo esc_attr($altura_carousel); ?>;">

            <!-- Track del carrusel -->
            <div class="flavor-carousel-track flavor-carousel-track--<?php echo esc_attr($efecto_transicion); ?>">
                <?php foreach ($items_multimedia as $indice => $item): ?>
                <div class="flavor-carousel-slide <?php echo $indice === 0 ? 'flavor-carousel-slide--activo' : ''; ?>"
                     data-slide-index="<?php echo esc_attr($indice); ?>"
                     data-tipo="<?php echo esc_attr($item['tipo']); ?>">

                    <!-- Fondo/Imagen del slide -->
                    <div class="flavor-carousel-slide-media">
                        <?php if ($item['tipo'] === 'video'): ?>
                            <div class="flavor-carousel-video-wrapper">
                                <img src="<?php echo esc_url($item['url']); ?>"
                                     alt="<?php echo esc_attr($item['titulo']); ?>"
                                     class="flavor-carousel-imagen"
                                     loading="<?php echo $indice === 0 ? 'eager' : 'lazy'; ?>">
                                <button type="button" class="flavor-carousel-play-video"
                                        data-video-url="<?php echo esc_url($item['video_url']); ?>"
                                        aria-label="<?php esc_attr_e('Reproducir video', 'flavor-chat-ia'); ?>">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="11" fill="rgba(0,0,0,0.6)"/>
                                        <polygon points="9.5 7.5 16.5 12 9.5 16.5 9.5 7.5" fill="white"/>
                                    </svg>
                                </button>
                                <?php if (isset($item['duracion'])): ?>
                                <span class="flavor-carousel-duracion"><?php echo esc_html($item['duracion']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo esc_url($item['url']); ?>"
                                 alt="<?php echo esc_attr($item['titulo']); ?>"
                                 class="flavor-carousel-imagen"
                                 loading="<?php echo $indice === 0 ? 'eager' : 'lazy'; ?>">
                        <?php endif; ?>

                        <!-- Overlay oscuro para mejor legibilidad -->
                        <div class="flavor-carousel-overlay"></div>
                    </div>

                    <!-- Contenido del slide -->
                    <div class="flavor-carousel-slide-content">
                        <div class="flavor-carousel-content-inner">
                            <!-- Badges -->
                            <div class="flavor-carousel-badges">
                                <span class="flavor-carousel-badge flavor-carousel-badge--tipo">
                                    <?php if ($item['tipo'] === 'video'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                        </svg>
                                        <?php esc_html_e('Video', 'flavor-chat-ia'); ?>
                                    <?php else: ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        <?php esc_html_e('Foto', 'flavor-chat-ia'); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="flavor-carousel-badge flavor-carousel-badge--categoria">
                                    <?php echo esc_html($item['categoria']); ?>
                                </span>
                                <?php if ($item['destacado']): ?>
                                <span class="flavor-carousel-badge flavor-carousel-badge--destacado">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                    <?php esc_html_e('Destacado', 'flavor-chat-ia'); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Título -->
                            <h3 class="flavor-carousel-slide-titulo">
                                <a href="<?php echo esc_url($item['enlace']); ?>" class="flavor-carousel-enlace">
                                    <?php echo esc_html($item['titulo']); ?>
                                </a>
                            </h3>

                            <!-- Descripción -->
                            <p class="flavor-carousel-slide-descripcion">
                                <?php echo esc_html($item['descripcion']); ?>
                            </p>

                            <!-- Meta información -->
                            <div class="flavor-carousel-slide-meta">
                                <span class="flavor-carousel-meta-autor">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <?php echo esc_html($item['autor']); ?>
                                </span>
                                <span class="flavor-carousel-meta-fecha">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item['fecha']))); ?>
                                </span>
                            </div>

                            <!-- Botón de acción -->
                            <a href="<?php echo esc_url($item['enlace']); ?>" class="flavor-carousel-btn">
                                <?php if ($item['tipo'] === 'video'): ?>
                                    <?php esc_html_e('Ver video', 'flavor-chat-ia'); ?>
                                <?php else: ?>
                                    <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                                <?php endif; ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($mostrar_navegacion && $total_items > 1): ?>
            <!-- Botones de navegación -->
            <button type="button" class="flavor-carousel-nav flavor-carousel-nav--anterior"
                    aria-label="<?php esc_attr_e('Anterior', 'flavor-chat-ia'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button type="button" class="flavor-carousel-nav flavor-carousel-nav--siguiente"
                    aria-label="<?php esc_attr_e('Siguiente', 'flavor-chat-ia'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            <?php endif; ?>

            <?php if ($mostrar_indicadores && $total_items > 1): ?>
            <!-- Indicadores (dots) -->
            <div class="flavor-carousel-indicadores">
                <?php for ($indicador = 0; $indicador < $total_items; $indicador++): ?>
                <button type="button"
                        class="flavor-carousel-indicador <?php echo $indicador === 0 ? 'flavor-carousel-indicador--activo' : ''; ?>"
                        data-slide="<?php echo esc_attr($indicador); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Ir al slide %d', 'flavor-chat-ia'), $indicador + 1)); ?>">
                    <span class="flavor-carousel-indicador-progreso"></span>
                </button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php if ($autoplay): ?>
            <!-- Control de autoplay -->
            <button type="button" class="flavor-carousel-autoplay flavor-carousel-autoplay--activo"
                    aria-label="<?php esc_attr_e('Pausar reproducción automática', 'flavor-chat-ia'); ?>">
                <svg class="flavor-icono-pausar" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16"></rect>
                    <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
                <svg class="flavor-icono-reproducir" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </button>
            <?php endif; ?>

            <!-- Contador de slides -->
            <div class="flavor-carousel-contador">
                <span class="flavor-carousel-contador-actual">1</span>
                <span class="flavor-carousel-contador-separador">/</span>
                <span class="flavor-carousel-contador-total"><?php echo esc_html($total_items); ?></span>
            </div>
        </div>

        <?php if ($mostrar_miniaturas && $total_items > 1): ?>
        <!-- Miniaturas -->
        <div class="flavor-carousel-miniaturas">
            <div class="flavor-carousel-miniaturas-track">
                <?php foreach ($items_multimedia as $indice => $item): ?>
                <button type="button"
                        class="flavor-carousel-miniatura <?php echo $indice === 0 ? 'flavor-carousel-miniatura--activa' : ''; ?>"
                        data-slide="<?php echo esc_attr($indice); ?>"
                        aria-label="<?php echo esc_attr(sprintf(__('Ver: %s', 'flavor-chat-ia'), $item['titulo'])); ?>">
                    <img src="<?php echo esc_url($item['miniatura']); ?>"
                         alt="<?php echo esc_attr($item['titulo']); ?>"
                         loading="lazy">
                    <?php if ($item['tipo'] === 'video'): ?>
                    <span class="flavor-carousel-miniatura-play">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                    </span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- Modal para videos -->
<div class="flavor-carousel-modal" id="<?php echo esc_attr($carousel_id); ?>-modal">
    <div class="flavor-carousel-modal-overlay"></div>
    <div class="flavor-carousel-modal-content">
        <button type="button" class="flavor-carousel-modal-cerrar" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="flavor-carousel-modal-video">
            <iframe src="" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS PARA CARRUSEL MULTIMEDIA ===== */

.flavor-carousel-section {
    --flavor-carousel-primary: #4f46e5;
    --flavor-carousel-primary-dark: #4338ca;
    --flavor-carousel-bg: #ffffff;
    --flavor-carousel-text: #ffffff;
    --flavor-carousel-text-secondary: rgba(255, 255, 255, 0.8);
    --flavor-carousel-overlay: rgba(0, 0, 0, 0.4);
    --flavor-carousel-altura: 500px;
}

.flavor-carousel-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header */
.flavor-carousel-header {
    margin-bottom: 1.5rem;
}

.flavor-carousel-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text-primary, #1a1a2e);
    margin: 0;
}

/* Contenedor principal */
.flavor-carousel-container {
    position: relative;
    width: 100%;
    height: var(--flavor-carousel-altura);
    border-radius: 16px;
    overflow: hidden;
    background: #1a1a2e;
}

/* Track */
.flavor-carousel-track {
    position: relative;
    width: 100%;
    height: 100%;
}

.flavor-carousel-track--slide {
    display: flex;
    transition: transform 0.5s ease-in-out;
}

.flavor-carousel-track--slide .flavor-carousel-slide {
    flex: 0 0 100%;
    width: 100%;
}

.flavor-carousel-track--fade .flavor-carousel-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.flavor-carousel-track--fade .flavor-carousel-slide--activo {
    opacity: 1;
    z-index: 1;
}

/* Slide */
.flavor-carousel-slide {
    position: relative;
    width: 100%;
    height: 100%;
}

.flavor-carousel-slide-media {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.flavor-carousel-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        to right,
        rgba(0, 0, 0, 0.7) 0%,
        rgba(0, 0, 0, 0.3) 50%,
        rgba(0, 0, 0, 0.1) 100%
    );
}

/* Video wrapper */
.flavor-carousel-video-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.flavor-carousel-play-video {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    z-index: 3;
    transition: transform 0.3s ease;
}

.flavor-carousel-play-video:hover {
    transform: translate(-50%, -50%) scale(1.1);
}

.flavor-carousel-duracion {
    position: absolute;
    bottom: 12px;
    right: 12px;
    padding: 4px 8px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
    z-index: 3;
}

/* Contenido del slide */
.flavor-carousel-slide-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    padding: 3rem;
    z-index: 2;
}

.flavor-carousel-content-inner {
    max-width: 500px;
}

/* Badges */
.flavor-carousel-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.flavor-carousel-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-carousel-badge--tipo {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.flavor-carousel-badge--categoria {
    background: var(--flavor-carousel-primary);
    color: white;
}

.flavor-carousel-badge--destacado {
    background: #f59e0b;
    color: white;
}

/* Título del slide */
.flavor-carousel-slide-titulo {
    font-size: 2rem;
    font-weight: 700;
    color: var(--flavor-carousel-text);
    margin: 0 0 1rem 0;
    line-height: 1.2;
}

.flavor-carousel-enlace {
    color: inherit;
    text-decoration: none;
}

.flavor-carousel-enlace:hover {
    text-decoration: underline;
}

/* Descripción */
.flavor-carousel-slide-descripcion {
    font-size: 1rem;
    color: var(--flavor-carousel-text-secondary);
    line-height: 1.6;
    margin: 0 0 1.5rem 0;
}

/* Meta información */
.flavor-carousel-slide-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.flavor-carousel-meta-autor,
.flavor-carousel-meta-fecha {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: var(--flavor-carousel-text-secondary);
}

/* Botón de acción */
.flavor-carousel-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.875rem 1.5rem;
    background: var(--flavor-carousel-primary);
    color: white;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.flavor-carousel-btn:hover {
    background: var(--flavor-carousel-primary-dark);
    transform: translateX(4px);
}

/* Navegación */
.flavor-carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
}

.flavor-carousel-nav:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-50%) scale(1.1);
}

.flavor-carousel-nav--anterior {
    left: 1rem;
}

.flavor-carousel-nav--siguiente {
    right: 1rem;
}

/* Indicadores */
.flavor-carousel-indicadores {
    position: absolute;
    bottom: 1.5rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.flavor-carousel-indicador {
    position: relative;
    width: 40px;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border: none;
    border-radius: 2px;
    cursor: pointer;
    overflow: hidden;
    transition: background 0.3s ease;
}

.flavor-carousel-indicador:hover {
    background: rgba(255, 255, 255, 0.5);
}

.flavor-carousel-indicador--activo {
    background: rgba(255, 255, 255, 0.5);
}

.flavor-carousel-indicador-progreso {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: white;
    width: 0;
    transition: width 0.1s linear;
}

.flavor-carousel-indicador--activo .flavor-carousel-indicador-progreso {
    animation: flavor-carousel-progreso var(--intervalo, 5000ms) linear forwards;
}

@keyframes flavor-carousel-progreso {
    from { width: 0; }
    to { width: 100%; }
}

/* Control de autoplay */
.flavor-carousel-autoplay {
    position: absolute;
    bottom: 1.5rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    z-index: 10;
    transition: background 0.3s ease;
}

.flavor-carousel-autoplay:hover {
    background: rgba(255, 255, 255, 0.3);
}

.flavor-carousel-autoplay--activo .flavor-icono-pausar {
    display: block;
}

.flavor-carousel-autoplay--activo .flavor-icono-reproducir {
    display: none;
}

.flavor-carousel-autoplay:not(.flavor-carousel-autoplay--activo) .flavor-icono-pausar {
    display: none;
}

.flavor-carousel-autoplay:not(.flavor-carousel-autoplay--activo) .flavor-icono-reproducir {
    display: block;
}

/* Contador */
.flavor-carousel-contador {
    position: absolute;
    bottom: 1.5rem;
    left: 1rem;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 10;
}

.flavor-carousel-contador-actual {
    font-weight: 700;
}

/* Miniaturas */
.flavor-carousel-miniaturas {
    margin-top: 1rem;
    overflow: hidden;
}

.flavor-carousel-miniaturas-track {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    scrollbar-width: thin;
    scrollbar-color: var(--flavor-carousel-primary) transparent;
}

.flavor-carousel-miniaturas-track::-webkit-scrollbar {
    height: 4px;
}

.flavor-carousel-miniaturas-track::-webkit-scrollbar-thumb {
    background: var(--flavor-carousel-primary);
    border-radius: 2px;
}

.flavor-carousel-miniatura {
    position: relative;
    flex-shrink: 0;
    width: 120px;
    height: 80px;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
}

.flavor-carousel-miniatura:hover {
    border-color: var(--flavor-carousel-primary);
    opacity: 0.8;
}

.flavor-carousel-miniatura--activa {
    border-color: var(--flavor-carousel-primary);
}

.flavor-carousel-miniatura img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-carousel-miniatura-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    color: white;
}

/* Modal de video */
.flavor-carousel-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.flavor-carousel-modal.flavor-carousel-modal--abierto {
    display: flex;
}

.flavor-carousel-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
}

.flavor-carousel-modal-content {
    position: relative;
    width: 90%;
    max-width: 1000px;
    z-index: 1;
}

.flavor-carousel-modal-cerrar {
    position: absolute;
    top: -50px;
    right: 0;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.flavor-carousel-modal-cerrar:hover {
    opacity: 1;
}

.flavor-carousel-modal-video {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 8px;
    background: #000;
}

.flavor-carousel-modal-video iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .flavor-carousel-slide-content {
        padding: 2rem;
    }

    .flavor-carousel-slide-titulo {
        font-size: 1.75rem;
    }
}

@media (max-width: 768px) {
    .flavor-carousel-container {
        --flavor-carousel-altura: 400px;
        border-radius: 12px;
    }

    .flavor-carousel-slide-content {
        padding: 1.5rem;
        align-items: flex-end;
    }

    .flavor-carousel-content-inner {
        max-width: 100%;
    }

    .flavor-carousel-overlay {
        background: linear-gradient(
            to top,
            rgba(0, 0, 0, 0.8) 0%,
            rgba(0, 0, 0, 0.4) 50%,
            rgba(0, 0, 0, 0.2) 100%
        );
    }

    .flavor-carousel-slide-titulo {
        font-size: 1.5rem;
    }

    .flavor-carousel-slide-descripcion {
        display: none;
    }

    .flavor-carousel-nav {
        width: 40px;
        height: 40px;
    }

    .flavor-carousel-indicadores {
        bottom: 1rem;
    }

    .flavor-carousel-indicador {
        width: 24px;
    }

    .flavor-carousel-contador,
    .flavor-carousel-autoplay {
        bottom: 1rem;
    }

    .flavor-carousel-miniatura {
        width: 100px;
        height: 65px;
    }
}

@media (max-width: 480px) {
    .flavor-carousel-container {
        --flavor-carousel-altura: 350px;
    }

    .flavor-carousel-slide-titulo {
        font-size: 1.25rem;
    }

    .flavor-carousel-badges {
        flex-wrap: nowrap;
        overflow-x: auto;
    }

    .flavor-carousel-slide-meta {
        gap: 1rem;
    }

    .flavor-carousel-btn {
        width: 100%;
        justify-content: center;
    }

    .flavor-carousel-nav--anterior {
        left: 0.5rem;
    }

    .flavor-carousel-nav--siguiente {
        right: 0.5rem;
    }
}
</style>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const carruseles = document.querySelectorAll('.flavor-carousel-container');

        carruseles.forEach(function(contenedorCarrusel) {
            const seccion = contenedorCarrusel.closest('.flavor-carousel-section');
            const track = contenedorCarrusel.querySelector('.flavor-carousel-track');
            const slides = contenedorCarrusel.querySelectorAll('.flavor-carousel-slide');
            const botonAnterior = contenedorCarrusel.querySelector('.flavor-carousel-nav--anterior');
            const botonSiguiente = contenedorCarrusel.querySelector('.flavor-carousel-nav--siguiente');
            const indicadores = contenedorCarrusel.querySelectorAll('.flavor-carousel-indicador');
            const botonAutoplay = contenedorCarrusel.querySelector('.flavor-carousel-autoplay');
            const contadorActual = contenedorCarrusel.querySelector('.flavor-carousel-contador-actual');
            const miniaturas = seccion ? seccion.querySelectorAll('.flavor-carousel-miniatura') : [];
            const botonesPlayVideo = contenedorCarrusel.querySelectorAll('.flavor-carousel-play-video');

            const habilitarAutoplay = contenedorCarrusel.dataset.autoplay === 'true';
            const intervaloAutoplay = parseInt(contenedorCarrusel.dataset.intervalo) || 5000;
            const pausarAlHover = contenedorCarrusel.dataset.pausarHover === 'true';
            const efectoTransicion = contenedorCarrusel.dataset.efecto || 'slide';

            let indiceActual = 0;
            let timerAutoplay = null;
            let autoplayActivo = habilitarAutoplay;
            const totalSlides = slides.length;

            // Establecer variable CSS para el intervalo
            contenedorCarrusel.style.setProperty('--intervalo', intervaloAutoplay + 'ms');

            // Función para ir a un slide específico
            function irASlide(indice) {
                if (indice < 0) indice = totalSlides - 1;
                if (indice >= totalSlides) indice = 0;

                // Remover clase activa de todos los slides
                slides.forEach(function(slide) {
                    slide.classList.remove('flavor-carousel-slide--activo');
                });

                // Agregar clase activa al slide actual
                slides[indice].classList.add('flavor-carousel-slide--activo');

                // Para efecto slide
                if (efectoTransicion === 'slide') {
                    track.style.transform = 'translateX(-' + (indice * 100) + '%)';
                }

                // Actualizar indicadores
                indicadores.forEach(function(indicador, indicadorIndice) {
                    indicador.classList.toggle('flavor-carousel-indicador--activo', indicadorIndice === indice);
                    // Reiniciar animación de progreso
                    const progreso = indicador.querySelector('.flavor-carousel-indicador-progreso');
                    if (progreso) {
                        progreso.style.animation = 'none';
                        progreso.offsetHeight; // Forzar reflow
                        if (indicadorIndice === indice && autoplayActivo) {
                            progreso.style.animation = '';
                        }
                    }
                });

                // Actualizar miniaturas
                miniaturas.forEach(function(miniatura, miniaturaIndice) {
                    miniatura.classList.toggle('flavor-carousel-miniatura--activa', miniaturaIndice === indice);
                });

                // Actualizar contador
                if (contadorActual) {
                    contadorActual.textContent = indice + 1;
                }

                indiceActual = indice;

                // Reiniciar autoplay
                if (autoplayActivo) {
                    iniciarAutoplay();
                }
            }

            // Función para ir al siguiente slide
            function siguienteSlide() {
                irASlide(indiceActual + 1);
            }

            // Función para ir al slide anterior
            function anteriorSlide() {
                irASlide(indiceActual - 1);
            }

            // Iniciar autoplay
            function iniciarAutoplay() {
                detenerAutoplay();
                if (autoplayActivo) {
                    timerAutoplay = setTimeout(siguienteSlide, intervaloAutoplay);
                }
            }

            // Detener autoplay
            function detenerAutoplay() {
                if (timerAutoplay) {
                    clearTimeout(timerAutoplay);
                    timerAutoplay = null;
                }
            }

            // Toggle autoplay
            function toggleAutoplay() {
                autoplayActivo = !autoplayActivo;

                if (botonAutoplay) {
                    botonAutoplay.classList.toggle('flavor-carousel-autoplay--activo', autoplayActivo);
                }

                if (autoplayActivo) {
                    iniciarAutoplay();
                    // Reiniciar animación del indicador actual
                    indicadores.forEach(function(indicador, indicadorIndice) {
                        const progreso = indicador.querySelector('.flavor-carousel-indicador-progreso');
                        if (progreso && indicadorIndice === indiceActual) {
                            progreso.style.animation = '';
                        }
                    });
                } else {
                    detenerAutoplay();
                    // Pausar animación de los indicadores
                    indicadores.forEach(function(indicador) {
                        const progreso = indicador.querySelector('.flavor-carousel-indicador-progreso');
                        if (progreso) {
                            progreso.style.animation = 'none';
                        }
                    });
                }
            }

            // Event listeners para navegación
            if (botonAnterior) {
                botonAnterior.addEventListener('click', anteriorSlide);
            }

            if (botonSiguiente) {
                botonSiguiente.addEventListener('click', siguienteSlide);
            }

            // Event listeners para indicadores
            indicadores.forEach(function(indicador, indicadorIndice) {
                indicador.addEventListener('click', function() {
                    irASlide(indicadorIndice);
                });
            });

            // Event listeners para miniaturas
            miniaturas.forEach(function(miniatura, miniaturaIndice) {
                miniatura.addEventListener('click', function() {
                    irASlide(miniaturaIndice);
                });
            });

            // Event listener para botón de autoplay
            if (botonAutoplay) {
                botonAutoplay.addEventListener('click', toggleAutoplay);
            }

            // Pausar al hover
            if (pausarAlHover) {
                contenedorCarrusel.addEventListener('mouseenter', function() {
                    if (autoplayActivo) {
                        detenerAutoplay();
                    }
                });

                contenedorCarrusel.addEventListener('mouseleave', function() {
                    if (autoplayActivo) {
                        iniciarAutoplay();
                    }
                });
            }

            // Soporte para teclado
            contenedorCarrusel.addEventListener('keydown', function(evento) {
                if (evento.key === 'ArrowLeft') {
                    anteriorSlide();
                } else if (evento.key === 'ArrowRight') {
                    siguienteSlide();
                }
            });

            // Soporte para swipe en móviles
            let inicioTouch = 0;
            let finTouch = 0;

            contenedorCarrusel.addEventListener('touchstart', function(evento) {
                inicioTouch = evento.changedTouches[0].screenX;
            }, { passive: true });

            contenedorCarrusel.addEventListener('touchend', function(evento) {
                finTouch = evento.changedTouches[0].screenX;
                manejarSwipe();
            }, { passive: true });

            function manejarSwipe() {
                const umbralSwipe = 50;
                const diferencia = inicioTouch - finTouch;

                if (Math.abs(diferencia) > umbralSwipe) {
                    if (diferencia > 0) {
                        siguienteSlide();
                    } else {
                        anteriorSlide();
                    }
                }
            }

            // Modal de video
            const modal = document.getElementById(seccion.id + '-modal');
            const modalOverlay = modal ? modal.querySelector('.flavor-carousel-modal-overlay') : null;
            const modalCerrar = modal ? modal.querySelector('.flavor-carousel-modal-cerrar') : null;
            const modalIframe = modal ? modal.querySelector('iframe') : null;

            botonesPlayVideo.forEach(function(botonPlay) {
                botonPlay.addEventListener('click', function(evento) {
                    evento.stopPropagation();
                    const urlVideo = this.dataset.videoUrl;

                    if (modal && modalIframe && urlVideo) {
                        modalIframe.src = urlVideo + '?autoplay=1';
                        modal.classList.add('flavor-carousel-modal--abierto');
                        document.body.style.overflow = 'hidden';

                        // Pausar autoplay mientras el modal está abierto
                        if (autoplayActivo) {
                            detenerAutoplay();
                        }
                    }
                });
            });

            function cerrarModal() {
                if (modal && modalIframe) {
                    modal.classList.remove('flavor-carousel-modal--abierto');
                    modalIframe.src = '';
                    document.body.style.overflow = '';

                    // Reanudar autoplay
                    if (autoplayActivo) {
                        iniciarAutoplay();
                    }
                }
            }

            if (modalOverlay) {
                modalOverlay.addEventListener('click', cerrarModal);
            }

            if (modalCerrar) {
                modalCerrar.addEventListener('click', cerrarModal);
            }

            // Cerrar modal con Escape
            document.addEventListener('keydown', function(evento) {
                if (evento.key === 'Escape' && modal && modal.classList.contains('flavor-carousel-modal--abierto')) {
                    cerrarModal();
                }
            });

            // Iniciar autoplay si está habilitado
            if (habilitarAutoplay) {
                iniciarAutoplay();
            }

            // Emitir evento cuando cambia el slide
            function emitirEventoCambio() {
                const eventoCambio = new CustomEvent('flavor:carousel:cambio', {
                    detail: {
                        indice: indiceActual,
                        total: totalSlides
                    }
                });
                document.dispatchEvent(eventoCambio);
            }

            // Observar cambios de slide
            const observadorSlide = new MutationObserver(function(mutaciones) {
                mutaciones.forEach(function(mutacion) {
                    if (mutacion.attributeName === 'class') {
                        if (mutacion.target.classList.contains('flavor-carousel-slide--activo')) {
                            emitirEventoCambio();
                        }
                    }
                });
            });

            slides.forEach(function(slide) {
                observadorSlide.observe(slide, { attributes: true });
            });
        });
    });
})();
</script>
