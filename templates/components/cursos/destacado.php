<?php
/**
 * Template: Curso Destacado
 *
 * Tarjeta grande de curso destacado con descripción completa y CTA
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$etiqueta_promocion = isset($args['etiqueta_promocion']) ? $args['etiqueta_promocion'] : 'Curso Destacado';
$mostrar_contador = isset($args['mostrar_contador']) ? $args['mostrar_contador'] : true;
$estilo_visual = isset($args['estilo_visual']) ? $args['estilo_visual'] : 'horizontal'; // horizontal, vertical, hero
$curso = isset($args['curso']) ? $args['curso'] : array();

// Datos de demostración si no se proporciona curso
if (empty($curso)) {
    $curso = array(
        'id' => 1,
        'imagen' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&h=500&fit=crop',
        'imagen_instructor' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop',
        'titulo' => 'Masterclass: Desarrollo Web Full Stack con React y Node.js',
        'descripcion_corta' => 'Domina el desarrollo web moderno de principio a fin',
        'descripcion_completa' => 'En este curso completo aprenderás a construir aplicaciones web profesionales utilizando las tecnologías más demandadas del mercado. Desde los fundamentos de HTML, CSS y JavaScript hasta frameworks avanzados como React para el frontend y Node.js con Express para el backend. Incluye proyectos reales, bases de datos, autenticación, deployment y mejores prácticas de la industria.',
        'instructor' => array(
            'nombre' => 'Carlos Mendoza',
            'titulo' => 'Senior Full Stack Developer',
            'empresa' => 'Google',
            'avatar' => 'https://i.pravatar.cc/80?img=12'
        ),
        'duracion' => '65 horas',
        'lecciones' => 248,
        'nivel' => 'Todos los niveles',
        'idioma' => 'Español',
        'certificado' => true,
        'acceso_ilimitado' => true,
        'precio' => 49.99,
        'precio_original' => 199.99,
        'rating' => 4.9,
        'total_reviews' => 2847,
        'estudiantes' => 12543,
        'fecha_actualizacion' => '2024-01-15',
        'caracteristicas' => array(
            'Proyectos prácticos reales',
            'Soporte del instructor',
            'Comunidad exclusiva',
            'Recursos descargables'
        ),
        'tecnologias' => array('React', 'Node.js', 'MongoDB', 'Express', 'JavaScript', 'TypeScript'),
        'oferta_termina' => '2024-02-28 23:59:59'
    );
}

// Calcular porcentaje de descuento
$tiene_descuento = !empty($curso['precio_original']) && $curso['precio_original'] > $curso['precio'];
$porcentaje_descuento = $tiene_descuento ? round((1 - $curso['precio'] / $curso['precio_original']) * 100) : 0;

// Función para renderizar estrellas
function flavor_destacado_renderizar_estrellas($rating) {
    $estrellas_completas = floor($rating);
    $tiene_media = ($rating - $estrellas_completas) >= 0.5;
    $estrellas_html = '';

    for ($i = 0; $i < 5; $i++) {
        if ($i < $estrellas_completas) {
            $estrellas_html .= '<span class="flavor-estrella-destacado flavor-estrella-destacado--llena">★</span>';
        } elseif ($i == $estrellas_completas && $tiene_media) {
            $estrellas_html .= '<span class="flavor-estrella-destacado flavor-estrella-destacado--media">★</span>';
        } else {
            $estrellas_html .= '<span class="flavor-estrella-destacado flavor-estrella-destacado--vacia">☆</span>';
        }
    }

    return $estrellas_html;
}
?>

<section class="flavor-curso-destacado-section flavor-curso-destacado--<?php echo esc_attr($estilo_visual); ?>">
    <div class="flavor-curso-destacado-container">

        <article class="flavor-curso-destacado-card">

            <?php if ($estilo_visual === 'hero') : ?>
            <!-- ESTILO HERO - Imagen de fondo completa -->
            <div class="flavor-destacado-hero-bg">
                <img
                    src="<?php echo esc_url($curso['imagen']); ?>"
                    alt="<?php echo esc_attr($curso['titulo']); ?>"
                    class="flavor-destacado-hero-imagen"
                >
                <div class="flavor-destacado-hero-overlay"></div>
            </div>

            <div class="flavor-destacado-hero-contenido">
                <div class="flavor-destacado-hero-texto">
                    <?php if ($etiqueta_promocion) : ?>
                        <span class="flavor-destacado-etiqueta"><?php echo esc_html($etiqueta_promocion); ?></span>
                    <?php endif; ?>

                    <h2 class="flavor-destacado-titulo"><?php echo esc_html($curso['titulo']); ?></h2>
                    <p class="flavor-destacado-descripcion-corta"><?php echo esc_html($curso['descripcion_corta']); ?></p>

                    <div class="flavor-destacado-rating">
                        <span class="flavor-destacado-rating-numero"><?php echo esc_html($curso['rating']); ?></span>
                        <div class="flavor-destacado-estrellas">
                            <?php echo flavor_destacado_renderizar_estrellas($curso['rating']); ?>
                        </div>
                        <span class="flavor-destacado-reviews">(<?php echo esc_html(number_format($curso['total_reviews'])); ?> reseñas)</span>
                        <span class="flavor-destacado-estudiantes"><?php echo esc_html(number_format($curso['estudiantes'])); ?> estudiantes</span>
                    </div>

                    <div class="flavor-destacado-instructor-hero">
                        <img src="<?php echo esc_url($curso['instructor']['avatar']); ?>" alt="<?php echo esc_attr($curso['instructor']['nombre']); ?>" class="flavor-destacado-instructor-avatar">
                        <div>
                            <span class="flavor-destacado-instructor-nombre"><?php echo esc_html($curso['instructor']['nombre']); ?></span>
                            <span class="flavor-destacado-instructor-cargo"><?php echo esc_html($curso['instructor']['titulo']); ?> en <?php echo esc_html($curso['instructor']['empresa']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="flavor-destacado-hero-cta">
                    <div class="flavor-destacado-precio-box">
                        <?php if ($tiene_descuento) : ?>
                            <span class="flavor-destacado-descuento-badge">-<?php echo esc_html($porcentaje_descuento); ?>%</span>
                            <span class="flavor-destacado-precio-original">$<?php echo esc_html(number_format($curso['precio_original'], 2)); ?></span>
                        <?php endif; ?>
                        <span class="flavor-destacado-precio-actual">$<?php echo esc_html(number_format($curso['precio'], 2)); ?></span>
                    </div>

                    <?php if ($mostrar_contador && !empty($curso['oferta_termina'])) : ?>
                    <div class="flavor-destacado-contador" data-fecha-fin="<?php echo esc_attr($curso['oferta_termina']); ?>">
                        <span class="flavor-contador-label">La oferta termina en:</span>
                        <div class="flavor-contador-tiempo">
                            <div class="flavor-contador-item">
                                <span class="flavor-contador-numero" data-tipo="dias">00</span>
                                <span class="flavor-contador-texto">Días</span>
                            </div>
                            <div class="flavor-contador-item">
                                <span class="flavor-contador-numero" data-tipo="horas">00</span>
                                <span class="flavor-contador-texto">Horas</span>
                            </div>
                            <div class="flavor-contador-item">
                                <span class="flavor-contador-numero" data-tipo="minutos">00</span>
                                <span class="flavor-contador-texto">Min</span>
                            </div>
                            <div class="flavor-contador-item">
                                <span class="flavor-contador-numero" data-tipo="segundos">00</span>
                                <span class="flavor-contador-texto">Seg</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button class="flavor-btn flavor-btn--grande flavor-btn--primario flavor-btn--bloque">
                        Inscribirme ahora
                    </button>
                    <button class="flavor-btn flavor-btn--grande flavor-btn--ghost flavor-btn--bloque">
                        Vista previa gratuita
                    </button>
                </div>
            </div>

            <?php else : ?>
            <!-- ESTILO HORIZONTAL / VERTICAL -->
            <div class="flavor-destacado-imagen-wrapper">
                <img
                    src="<?php echo esc_url($curso['imagen']); ?>"
                    alt="<?php echo esc_attr($curso['titulo']); ?>"
                    class="flavor-destacado-imagen"
                >
                <?php if ($tiene_descuento) : ?>
                    <span class="flavor-destacado-descuento">-<?php echo esc_html($porcentaje_descuento); ?>% OFF</span>
                <?php endif; ?>
                <?php if ($etiqueta_promocion) : ?>
                    <span class="flavor-destacado-etiqueta-imagen"><?php echo esc_html($etiqueta_promocion); ?></span>
                <?php endif; ?>

                <div class="flavor-destacado-tecnologias">
                    <?php foreach (array_slice($curso['tecnologias'], 0, 4) as $tecnologia) : ?>
                        <span class="flavor-tecnologia-tag"><?php echo esc_html($tecnologia); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($curso['tecnologias']) > 4) : ?>
                        <span class="flavor-tecnologia-tag flavor-tecnologia-tag--mas">+<?php echo count($curso['tecnologias']) - 4; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-destacado-contenido">
                <header class="flavor-destacado-header">
                    <div class="flavor-destacado-meta-superior">
                        <span class="flavor-destacado-nivel"><?php echo esc_html($curso['nivel']); ?></span>
                        <span class="flavor-destacado-actualizado">
                            <svg class="flavor-icono" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                            Actualizado <?php echo esc_html(date('M Y', strtotime($curso['fecha_actualizacion']))); ?>
                        </span>
                    </div>

                    <h2 class="flavor-destacado-titulo"><?php echo esc_html($curso['titulo']); ?></h2>
                    <p class="flavor-destacado-descripcion-corta"><?php echo esc_html($curso['descripcion_corta']); ?></p>
                </header>

                <div class="flavor-destacado-rating-info">
                    <div class="flavor-destacado-rating">
                        <span class="flavor-destacado-rating-numero"><?php echo esc_html($curso['rating']); ?></span>
                        <div class="flavor-destacado-estrellas">
                            <?php echo flavor_destacado_renderizar_estrellas($curso['rating']); ?>
                        </div>
                        <span class="flavor-destacado-reviews">(<?php echo esc_html(number_format($curso['total_reviews'])); ?>)</span>
                    </div>
                    <span class="flavor-destacado-separador">|</span>
                    <span class="flavor-destacado-estudiantes">
                        <svg class="flavor-icono" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php echo esc_html(number_format($curso['estudiantes'])); ?> estudiantes
                    </span>
                </div>

                <p class="flavor-destacado-descripcion-completa"><?php echo esc_html($curso['descripcion_completa']); ?></p>

                <div class="flavor-destacado-info-grid">
                    <div class="flavor-destacado-info-item">
                        <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <div>
                            <span class="flavor-info-valor"><?php echo esc_html($curso['duracion']); ?></span>
                            <span class="flavor-info-label">de contenido</span>
                        </div>
                    </div>
                    <div class="flavor-destacado-info-item">
                        <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        <div>
                            <span class="flavor-info-valor"><?php echo esc_html($curso['lecciones']); ?> lecciones</span>
                            <span class="flavor-info-label">en video HD</span>
                        </div>
                    </div>
                    <div class="flavor-destacado-info-item">
                        <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <div>
                            <span class="flavor-info-valor"><?php echo $curso['certificado'] ? 'Certificado' : 'Sin certificado'; ?></span>
                            <span class="flavor-info-label">de finalización</span>
                        </div>
                    </div>
                    <div class="flavor-destacado-info-item">
                        <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <div>
                            <span class="flavor-info-valor">Recursos</span>
                            <span class="flavor-info-label">descargables</span>
                        </div>
                    </div>
                </div>

                <ul class="flavor-destacado-caracteristicas">
                    <?php foreach ($curso['caracteristicas'] as $caracteristica) : ?>
                    <li class="flavor-caracteristica-item">
                        <svg class="flavor-icono flavor-icono--check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php echo esc_html($caracteristica); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flavor-destacado-instructor">
                    <img
                        src="<?php echo esc_url($curso['instructor']['avatar']); ?>"
                        alt="<?php echo esc_attr($curso['instructor']['nombre']); ?>"
                        class="flavor-destacado-instructor-foto"
                    >
                    <div class="flavor-destacado-instructor-info">
                        <span class="flavor-destacado-creado-por">Creado por</span>
                        <span class="flavor-destacado-instructor-nombre"><?php echo esc_html($curso['instructor']['nombre']); ?></span>
                        <span class="flavor-destacado-instructor-cargo"><?php echo esc_html($curso['instructor']['titulo']); ?> en <?php echo esc_html($curso['instructor']['empresa']); ?></span>
                    </div>
                </div>

                <footer class="flavor-destacado-footer">
                    <div class="flavor-destacado-precio-wrapper">
                        <?php if ($tiene_descuento) : ?>
                            <span class="flavor-destacado-precio-original">$<?php echo esc_html(number_format($curso['precio_original'], 2)); ?></span>
                        <?php endif; ?>
                        <span class="flavor-destacado-precio-actual">$<?php echo esc_html(number_format($curso['precio'], 2)); ?></span>
                        <?php if ($tiene_descuento) : ?>
                            <span class="flavor-destacado-ahorro">Ahorras $<?php echo esc_html(number_format($curso['precio_original'] - $curso['precio'], 2)); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($mostrar_contador && !empty($curso['oferta_termina'])) : ?>
                    <div class="flavor-destacado-contador-inline" data-fecha-fin="<?php echo esc_attr($curso['oferta_termina']); ?>">
                        <svg class="flavor-icono" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Oferta termina en <strong class="flavor-contador-inline-tiempo">2d 14h 32m</strong></span>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-destacado-botones">
                        <button class="flavor-btn flavor-btn--grande flavor-btn--primario">
                            <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Inscribirme ahora
                        </button>
                        <button class="flavor-btn flavor-btn--grande flavor-btn--secundario">
                            <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            Vista previa
                        </button>
                    </div>

                    <p class="flavor-destacado-garantia">
                        <svg class="flavor-icono" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                        Garantía de devolución de 30 días
                    </p>
                </footer>
            </div>
            <?php endif; ?>

        </article>

    </div>
</section>

<style>
/* Curso Destacado - Estilos Base */
.flavor-curso-destacado-section {
    padding: 4rem 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.flavor-curso-destacado-container {
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-curso-destacado-card {
    background: #ffffff;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

/* ====== ESTILO HORIZONTAL ====== */
.flavor-curso-destacado--horizontal .flavor-curso-destacado-card {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
}

.flavor-destacado-imagen-wrapper {
    position: relative;
    min-height: 400px;
}

.flavor-destacado-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-destacado-descuento {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.flavor-destacado-etiqueta-imagen {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.flavor-destacado-tecnologias {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.flavor-tecnologia-tag {
    background: rgba(255, 255, 255, 0.95);
    color: #475569;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    backdrop-filter: blur(8px);
}

.flavor-tecnologia-tag--mas {
    background: #6366f1;
    color: #ffffff;
}

/* Contenido */
.flavor-destacado-contenido {
    padding: 2rem;
    display: flex;
    flex-direction: column;
}

.flavor-destacado-header {
    margin-bottom: 1.25rem;
}

.flavor-destacado-meta-superior {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.flavor-destacado-nivel {
    background: #ecfdf5;
    color: #059669;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-destacado-actualizado {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.flavor-destacado-titulo {
    font-size: clamp(1.375rem, 3vw, 1.75rem);
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.flavor-destacado-descripcion-corta {
    font-size: 1.0625rem;
    color: #64748b;
    margin: 0;
}

/* Rating */
.flavor-destacado-rating-info {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-destacado-rating {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.flavor-destacado-rating-numero {
    font-size: 1rem;
    font-weight: 700;
    color: #f59e0b;
}

.flavor-destacado-estrellas {
    display: flex;
}

.flavor-estrella-destacado {
    font-size: 1rem;
}

.flavor-estrella-destacado--llena {
    color: #f59e0b;
}

.flavor-estrella-destacado--media {
    color: #f59e0b;
    opacity: 0.5;
}

.flavor-estrella-destacado--vacia {
    color: #e2e8f0;
}

.flavor-destacado-reviews {
    font-size: 0.875rem;
    color: #64748b;
}

.flavor-destacado-separador {
    color: #e2e8f0;
}

.flavor-destacado-estudiantes {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #64748b;
}

/* Descripción completa */
.flavor-destacado-descripcion-completa {
    font-size: 0.9375rem;
    color: #475569;
    line-height: 1.7;
    margin: 0 0 1.5rem 0;
}

/* Info Grid */
.flavor-destacado-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.flavor-destacado-info-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.flavor-destacado-info-item .flavor-icono {
    color: #6366f1;
    flex-shrink: 0;
    margin-top: 2px;
}

.flavor-info-valor {
    display: block;
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9375rem;
}

.flavor-info-label {
    display: block;
    font-size: 0.8125rem;
    color: #64748b;
}

/* Características */
.flavor-destacado-caracteristicas {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.625rem;
}

.flavor-caracteristica-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #475569;
}

.flavor-icono--check {
    color: #10b981;
    flex-shrink: 0;
}

/* Instructor */
.flavor-destacado-instructor {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.flavor-destacado-instructor-foto {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.flavor-destacado-instructor-info {
    display: flex;
    flex-direction: column;
}

.flavor-destacado-creado-por {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.flavor-destacado-instructor-nombre {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.flavor-destacado-instructor-cargo {
    font-size: 0.8125rem;
    color: #64748b;
}

/* Footer */
.flavor-destacado-footer {
    margin-top: auto;
    padding-top: 1.5rem;
    border-top: 1px solid #f1f5f9;
}

.flavor-destacado-precio-wrapper {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.flavor-destacado-precio-original {
    font-size: 1.125rem;
    color: #94a3b8;
    text-decoration: line-through;
}

.flavor-destacado-precio-actual {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
}

.flavor-destacado-ahorro {
    font-size: 0.875rem;
    font-weight: 600;
    color: #10b981;
    background: #ecfdf5;
    padding: 0.25rem 0.625rem;
    border-radius: 0.25rem;
}

/* Contador inline */
.flavor-destacado-contador-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #ef4444;
    margin-bottom: 1rem;
}

.flavor-contador-inline-tiempo {
    font-weight: 700;
}

/* Botones */
.flavor-destacado-botones {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.flavor-btn--grande {
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
}

.flavor-btn--primario {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    border: none;
    flex: 1;
}

.flavor-btn--primario:hover {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.flavor-btn--secundario {
    background: #ffffff;
    color: #6366f1;
    border: 2px solid #e2e8f0;
}

.flavor-btn--secundario:hover {
    border-color: #6366f1;
    background: #f8fafc;
}

.flavor-destacado-garantia {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

.flavor-destacado-garantia .flavor-icono {
    color: #10b981;
}

/* ====== ESTILO VERTICAL ====== */
.flavor-curso-destacado--vertical .flavor-curso-destacado-card {
    max-width: 500px;
    margin: 0 auto;
}

.flavor-curso-destacado--vertical .flavor-destacado-imagen-wrapper {
    min-height: 280px;
}

.flavor-curso-destacado--vertical .flavor-destacado-info-grid,
.flavor-curso-destacado--vertical .flavor-destacado-caracteristicas {
    grid-template-columns: 1fr;
}

/* ====== ESTILO HERO ====== */
.flavor-curso-destacado--hero {
    padding: 0;
}

.flavor-curso-destacado--hero .flavor-curso-destacado-container {
    max-width: 100%;
}

.flavor-curso-destacado--hero .flavor-curso-destacado-card {
    border-radius: 0;
    position: relative;
    min-height: 600px;
}

.flavor-destacado-hero-bg {
    position: absolute;
    inset: 0;
}

.flavor-destacado-hero-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-destacado-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.7) 50%, rgba(15, 23, 42, 0.4) 100%);
}

.flavor-destacado-hero-contenido {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 4rem;
    padding: 4rem;
    min-height: 600px;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
}

.flavor-destacado-hero-texto {
    color: #ffffff;
}

.flavor-destacado-etiqueta {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 1.25rem;
}

.flavor-curso-destacado--hero .flavor-destacado-titulo {
    font-size: clamp(2rem, 4vw, 3rem);
    color: #ffffff;
    margin-bottom: 1rem;
}

.flavor-curso-destacado--hero .flavor-destacado-descripcion-corta {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 1.5rem;
}

.flavor-curso-destacado--hero .flavor-destacado-rating {
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.flavor-curso-destacado--hero .flavor-destacado-rating-numero {
    color: #f59e0b;
}

.flavor-curso-destacado--hero .flavor-destacado-reviews,
.flavor-curso-destacado--hero .flavor-destacado-estudiantes {
    color: rgba(255, 255, 255, 0.7);
}

.flavor-destacado-instructor-hero {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.flavor-destacado-instructor-hero img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.flavor-destacado-instructor-hero .flavor-destacado-instructor-nombre {
    display: block;
    color: #ffffff;
    font-weight: 600;
}

.flavor-destacado-instructor-hero .flavor-destacado-instructor-cargo {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

/* Hero CTA */
.flavor-destacado-hero-cta {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 1.5rem;
    padding: 2rem;
}

.flavor-destacado-precio-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.flavor-destacado-descuento-badge {
    background: #ef4444;
    color: #ffffff;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 700;
}

.flavor-destacado-hero-cta .flavor-destacado-precio-original {
    color: rgba(255, 255, 255, 0.5);
}

.flavor-destacado-hero-cta .flavor-destacado-precio-actual {
    font-size: 2.5rem;
    color: #ffffff;
}

/* Contador Hero */
.flavor-destacado-contador {
    margin-bottom: 1.5rem;
}

.flavor-contador-label {
    display: block;
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.75rem;
}

.flavor-contador-tiempo {
    display: flex;
    gap: 0.75rem;
}

.flavor-contador-item {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.75rem;
    border-radius: 0.5rem;
    text-align: center;
    min-width: 60px;
}

.flavor-contador-numero {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
}

.flavor-contador-texto {
    font-size: 0.6875rem;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
}

.flavor-btn--bloque {
    width: 100%;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.flavor-btn--ghost {
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
}

.flavor-btn--ghost:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-curso-destacado--horizontal .flavor-curso-destacado-card {
        grid-template-columns: 1fr;
    }

    .flavor-destacado-imagen-wrapper {
        min-height: 300px;
    }

    .flavor-destacado-hero-contenido {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 2rem;
    }

    .flavor-curso-destacado--hero .flavor-curso-destacado-card {
        min-height: auto;
    }
}

@media (max-width: 768px) {
    .flavor-curso-destacado-section {
        padding: 2rem 1rem;
    }

    .flavor-destacado-contenido {
        padding: 1.5rem;
    }

    .flavor-destacado-info-grid {
        grid-template-columns: 1fr;
    }

    .flavor-destacado-caracteristicas {
        grid-template-columns: 1fr;
    }

    .flavor-destacado-botones {
        flex-direction: column;
    }

    .flavor-destacado-precio-actual {
        font-size: 1.75rem;
    }

    .flavor-contador-tiempo {
        gap: 0.5rem;
    }

    .flavor-contador-item {
        min-width: 50px;
        padding: 0.5rem;
    }

    .flavor-contador-numero {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .flavor-destacado-imagen-wrapper {
        min-height: 220px;
    }

    .flavor-destacado-tecnologias {
        display: none;
    }

    .flavor-destacado-instructor {
        flex-direction: column;
        text-align: center;
    }

    .flavor-destacado-rating-info {
        flex-direction: column;
        align-items: flex-start;
    }

    .flavor-destacado-separador {
        display: none;
    }
}
</style>

<script>
// Contador regresivo para ofertas
document.addEventListener('DOMContentLoaded', function() {
    const contadores = document.querySelectorAll('.flavor-destacado-contador, .flavor-destacado-contador-inline');

    contadores.forEach(function(contador) {
        const fechaFinString = contador.getAttribute('data-fecha-fin');
        if (!fechaFinString) return;

        const fechaFin = new Date(fechaFinString).getTime();

        function actualizarContador() {
            const ahora = new Date().getTime();
            const diferencia = fechaFin - ahora;

            if (diferencia < 0) {
                contador.innerHTML = '<span class="flavor-oferta-expirada">Oferta expirada</span>';
                return;
            }

            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

            // Para contador completo
            const numeroDias = contador.querySelector('[data-tipo="dias"]');
            const numeroHoras = contador.querySelector('[data-tipo="horas"]');
            const numeroMinutos = contador.querySelector('[data-tipo="minutos"]');
            const numeroSegundos = contador.querySelector('[data-tipo="segundos"]');

            if (numeroDias) numeroDias.textContent = String(dias).padStart(2, '0');
            if (numeroHoras) numeroHoras.textContent = String(horas).padStart(2, '0');
            if (numeroMinutos) numeroMinutos.textContent = String(minutos).padStart(2, '0');
            if (numeroSegundos) numeroSegundos.textContent = String(segundos).padStart(2, '0');

            // Para contador inline
            const contadorInline = contador.querySelector('.flavor-contador-inline-tiempo');
            if (contadorInline) {
                contadorInline.textContent = `${dias}d ${horas}h ${minutos}m`;
            }
        }

        actualizarContador();
        setInterval(actualizarContador, 1000);
    });
});
</script>
