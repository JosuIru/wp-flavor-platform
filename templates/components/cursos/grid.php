<?php
/**
 * Template: Grid de Cursos
 *
 * Muestra una cuadrícula de cursos con imagen, título, instructor, duración, nivel y precio
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : 'Nuestros Cursos';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Explora nuestra amplia variedad de cursos diseñados para impulsar tu carrera';
$columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$mostrar_filtros = isset($args['mostrar_filtros']) ? $args['mostrar_filtros'] : true;
$cursos = isset($args['cursos']) ? $args['cursos'] : array();

// Datos de demostración si no se proporcionan cursos
if (empty($cursos)) {
    $cursos = array(
        array(
            'id' => 1,
            'imagen' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=250&fit=crop',
            'titulo' => 'Desarrollo Web Full Stack',
            'instructor' => 'María García',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=1',
            'duracion' => '40 horas',
            'nivel' => 'Intermedio',
            'precio' => 199.99,
            'precio_original' => 299.99,
            'categoria' => 'Programación',
            'rating' => 4.8,
            'estudiantes' => 1234,
            'descripcion' => 'Aprende HTML, CSS, JavaScript, React y Node.js'
        ),
        array(
            'id' => 2,
            'imagen' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400&h=250&fit=crop',
            'titulo' => 'Diseño UX/UI Profesional',
            'instructor' => 'Carlos López',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=2',
            'duracion' => '35 horas',
            'nivel' => 'Principiante',
            'precio' => 149.99,
            'precio_original' => null,
            'categoria' => 'Diseño',
            'rating' => 4.9,
            'estudiantes' => 856,
            'descripcion' => 'Domina Figma, Adobe XD y principios de diseño'
        ),
        array(
            'id' => 3,
            'imagen' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop',
            'titulo' => 'Data Science con Python',
            'instructor' => 'Ana Martínez',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=3',
            'duracion' => '60 horas',
            'nivel' => 'Avanzado',
            'precio' => 249.99,
            'precio_original' => 399.99,
            'categoria' => 'Programación',
            'rating' => 4.7,
            'estudiantes' => 2105,
            'descripcion' => 'Machine Learning, pandas, numpy y visualización'
        ),
        array(
            'id' => 4,
            'imagen' => 'https://images.unsplash.com/photo-1543269865-cbf427effbad?w=400&h=250&fit=crop',
            'titulo' => 'Marketing Digital Completo',
            'instructor' => 'Pedro Sánchez',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=4',
            'duracion' => '25 horas',
            'nivel' => 'Principiante',
            'precio' => 99.99,
            'precio_original' => null,
            'categoria' => 'Marketing',
            'rating' => 4.6,
            'estudiantes' => 3421,
            'descripcion' => 'SEO, SEM, redes sociales y email marketing'
        ),
        array(
            'id' => 5,
            'imagen' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&h=250&fit=crop',
            'titulo' => 'Inglés para Negocios',
            'instructor' => 'Laura Wilson',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=5',
            'duracion' => '50 horas',
            'nivel' => 'Intermedio',
            'precio' => 129.99,
            'precio_original' => 179.99,
            'categoria' => 'Idiomas',
            'rating' => 4.8,
            'estudiantes' => 1876,
            'descripcion' => 'Comunicación profesional y vocabulario empresarial'
        ),
        array(
            'id' => 6,
            'imagen' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=400&h=250&fit=crop',
            'titulo' => 'Gestión de Proyectos Ágiles',
            'instructor' => 'Roberto Díaz',
            'instructor_avatar' => 'https://i.pravatar.cc/40?img=6',
            'duracion' => '30 horas',
            'nivel' => 'Intermedio',
            'precio' => 179.99,
            'precio_original' => null,
            'categoria' => 'Negocios',
            'rating' => 4.5,
            'estudiantes' => 987,
            'descripcion' => 'Scrum, Kanban y metodologías ágiles'
        )
    );
}

// Función para obtener clase de nivel
function flavor_obtener_clase_nivel($nivel) {
    $clases = array(
        'Principiante' => 'flavor-nivel--principiante',
        'Intermedio' => 'flavor-nivel--intermedio',
        'Avanzado' => 'flavor-nivel--avanzado'
    );
    return isset($clases[$nivel]) ? $clases[$nivel] : 'flavor-nivel--principiante';
}

// Función para renderizar estrellas
function flavor_renderizar_estrellas($rating) {
    $estrellas_completas = floor($rating);
    $tiene_media = ($rating - $estrellas_completas) >= 0.5;
    $estrellas_html = '';

    for ($i = 0; $i < 5; $i++) {
        if ($i < $estrellas_completas) {
            $estrellas_html .= '<span class="flavor-estrella flavor-estrella--llena">★</span>';
        } elseif ($i == $estrellas_completas && $tiene_media) {
            $estrellas_html .= '<span class="flavor-estrella flavor-estrella--media">★</span>';
        } else {
            $estrellas_html .= '<span class="flavor-estrella flavor-estrella--vacia">☆</span>';
        }
    }

    return $estrellas_html;
}
?>

<section class="flavor-cursos-grid-section">
    <div class="flavor-cursos-container">

        <?php if ($titulo_seccion || $subtitulo) : ?>
        <header class="flavor-cursos-header">
            <?php if ($titulo_seccion) : ?>
                <h2 class="flavor-cursos-titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <?php endif; ?>
            <?php if ($subtitulo) : ?>
                <p class="flavor-cursos-subtitulo"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </header>
        <?php endif; ?>

        <?php if ($mostrar_filtros) : ?>
        <nav class="flavor-cursos-filtros">
            <button class="flavor-filtro-btn flavor-filtro-btn--activo" data-filtro="todos">Todos</button>
            <button class="flavor-filtro-btn" data-filtro="programacion">Programación</button>
            <button class="flavor-filtro-btn" data-filtro="diseno">Diseño</button>
            <button class="flavor-filtro-btn" data-filtro="marketing">Marketing</button>
            <button class="flavor-filtro-btn" data-filtro="idiomas">Idiomas</button>
            <button class="flavor-filtro-btn" data-filtro="negocios">Negocios</button>
        </nav>
        <?php endif; ?>

        <div class="flavor-cursos-grid flavor-cursos-grid--<?php echo esc_attr($columnas); ?>-cols">
            <?php foreach ($cursos as $curso) :
                $tiene_descuento = !empty($curso['precio_original']) && $curso['precio_original'] > $curso['precio'];
                $porcentaje_descuento = $tiene_descuento ? round((1 - $curso['precio'] / $curso['precio_original']) * 100) : 0;
            ?>
            <article class="flavor-curso-card" data-categoria="<?php echo esc_attr(sanitize_title($curso['categoria'])); ?>">

                <div class="flavor-curso-imagen-wrapper">
                    <img
                        src="<?php echo esc_url($curso['imagen']); ?>"
                        alt="<?php echo esc_attr($curso['titulo']); ?>"
                        class="flavor-curso-imagen"
                        loading="lazy"
                    >
                    <?php if ($tiene_descuento) : ?>
                        <span class="flavor-curso-descuento">-<?php echo esc_html($porcentaje_descuento); ?>%</span>
                    <?php endif; ?>
                    <span class="flavor-curso-categoria"><?php echo esc_html($curso['categoria']); ?></span>
                </div>

                <div class="flavor-curso-contenido">

                    <div class="flavor-curso-meta-superior">
                        <span class="flavor-curso-nivel <?php echo esc_attr(flavor_obtener_clase_nivel($curso['nivel'])); ?>">
                            <?php echo esc_html($curso['nivel']); ?>
                        </span>
                        <span class="flavor-curso-duracion">
                            <svg class="flavor-icono" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($curso['duracion']); ?>
                        </span>
                    </div>

                    <h3 class="flavor-curso-titulo-card">
                        <a href="#curso-<?php echo esc_attr($curso['id']); ?>"><?php echo esc_html($curso['titulo']); ?></a>
                    </h3>

                    <p class="flavor-curso-descripcion"><?php echo esc_html($curso['descripcion']); ?></p>

                    <div class="flavor-curso-instructor">
                        <img
                            src="<?php echo esc_url($curso['instructor_avatar']); ?>"
                            alt="<?php echo esc_attr($curso['instructor']); ?>"
                            class="flavor-instructor-avatar"
                        >
                        <span class="flavor-instructor-nombre"><?php echo esc_html($curso['instructor']); ?></span>
                    </div>

                    <div class="flavor-curso-rating">
                        <div class="flavor-estrellas">
                            <?php echo flavor_renderizar_estrellas($curso['rating']); ?>
                        </div>
                        <span class="flavor-rating-numero"><?php echo esc_html($curso['rating']); ?></span>
                        <span class="flavor-estudiantes">(<?php echo esc_html(number_format($curso['estudiantes'])); ?> estudiantes)</span>
                    </div>

                    <div class="flavor-curso-footer">
                        <div class="flavor-curso-precio">
                            <?php if ($tiene_descuento) : ?>
                                <span class="flavor-precio-original">$<?php echo esc_html(number_format($curso['precio_original'], 2)); ?></span>
                            <?php endif; ?>
                            <span class="flavor-precio-actual">$<?php echo esc_html(number_format($curso['precio'], 2)); ?></span>
                        </div>
                        <button class="flavor-btn flavor-btn--primario flavor-btn--pequeno">
                            Inscribirse
                        </button>
                    </div>

                </div>

            </article>
            <?php endforeach; ?>
        </div>

        <div class="flavor-cursos-ver-mas">
            <a href="#todos-los-cursos" class="flavor-btn flavor-btn--secundario">
                Ver todos los cursos
                <svg class="flavor-icono" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>

    </div>
</section>

<style>
/* Grid de Cursos - Estilos */
.flavor-cursos-grid-section {
    padding: 4rem 1rem;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.flavor-cursos-container {
    max-width: 1280px;
    margin: 0 auto;
}

/* Header */
.flavor-cursos-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.flavor-cursos-titulo {
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.75rem 0;
}

.flavor-cursos-subtitulo {
    font-size: 1.125rem;
    color: #64748b;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Filtros */
.flavor-cursos-filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.flavor-filtro-btn {
    padding: 0.5rem 1.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    background: #ffffff;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-filtro-btn:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.flavor-filtro-btn--activo {
    background: #6366f1;
    border-color: #6366f1;
    color: #ffffff;
}

/* Grid */
.flavor-cursos-grid {
    display: grid;
    gap: 1.5rem;
}

.flavor-cursos-grid--2-cols {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.flavor-cursos-grid--3-cols {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.flavor-cursos-grid--4-cols {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

/* Card de Curso */
.flavor-curso-card {
    background: #ffffff;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.flavor-curso-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
}

/* Imagen */
.flavor-curso-imagen-wrapper {
    position: relative;
    overflow: hidden;
    aspect-ratio: 16 / 10;
}

.flavor-curso-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.flavor-curso-card:hover .flavor-curso-imagen {
    transform: scale(1.05);
}

.flavor-curso-descuento {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: #ef4444;
    color: #ffffff;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-curso-categoria {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    color: #475569;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Contenido */
.flavor-curso-contenido {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.flavor-curso-meta-superior {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.flavor-curso-nivel {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.flavor-nivel--principiante {
    background: #dcfce7;
    color: #166534;
}

.flavor-nivel--intermedio {
    background: #fef3c7;
    color: #92400e;
}

.flavor-nivel--avanzado {
    background: #fce7f3;
    color: #9d174d;
}

.flavor-curso-duracion {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.flavor-curso-duracion .flavor-icono {
    width: 14px;
    height: 14px;
}

.flavor-curso-titulo-card {
    font-size: 1.0625rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
}

.flavor-curso-titulo-card a {
    color: #1e293b;
    text-decoration: none;
    transition: color 0.2s ease;
}

.flavor-curso-titulo-card a:hover {
    color: #6366f1;
}

.flavor-curso-descripcion {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 1rem 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Instructor */
.flavor-curso-instructor {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.flavor-instructor-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}

.flavor-instructor-nombre {
    font-size: 0.8125rem;
    color: #475569;
    font-weight: 500;
}

/* Rating */
.flavor-curso-rating {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.flavor-estrellas {
    display: flex;
    gap: 1px;
}

.flavor-estrella {
    font-size: 0.875rem;
}

.flavor-estrella--llena {
    color: #f59e0b;
}

.flavor-estrella--media {
    color: #f59e0b;
    opacity: 0.5;
}

.flavor-estrella--vacia {
    color: #cbd5e1;
}

.flavor-rating-numero {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
}

.flavor-estudiantes {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Footer */
.flavor-curso-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}

.flavor-curso-precio {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.flavor-precio-original {
    font-size: 0.875rem;
    color: #94a3b8;
    text-decoration: line-through;
}

.flavor-precio-actual {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

/* Botones */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.flavor-btn--primario {
    background: #6366f1;
    color: #ffffff;
}

.flavor-btn--primario:hover {
    background: #4f46e5;
}

.flavor-btn--secundario {
    background: transparent;
    color: #6366f1;
    border: 2px solid #6366f1;
}

.flavor-btn--secundario:hover {
    background: #6366f1;
    color: #ffffff;
}

.flavor-btn--pequeno {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
}

/* Ver más */
.flavor-cursos-ver-mas {
    text-align: center;
    margin-top: 2.5rem;
}

.flavor-cursos-ver-mas .flavor-btn {
    padding: 0.875rem 2rem;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-cursos-grid-section {
        padding: 3rem 1rem;
    }

    .flavor-cursos-filtros {
        gap: 0.375rem;
    }

    .flavor-filtro-btn {
        padding: 0.375rem 0.875rem;
        font-size: 0.8125rem;
    }

    .flavor-cursos-grid {
        grid-template-columns: 1fr;
    }

    .flavor-curso-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .flavor-btn--pequeno {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .flavor-cursos-header {
        margin-bottom: 1.5rem;
    }

    .flavor-cursos-subtitulo {
        font-size: 1rem;
    }

    .flavor-curso-contenido {
        padding: 1rem;
    }
}
</style>
