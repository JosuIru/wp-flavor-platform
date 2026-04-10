<?php
/**
 * Template: Grupos Destacados
 * Grid de grupos de chat destacados con avatar, nombre, miembros y botón unirse
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_seccion = isset($args['titulo_seccion']) ? $args['titulo_seccion'] : __('Grupos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : __('Únete a comunidades activas de tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN);
$grupos = isset($args['grupos']) ? $args['grupos'] : array();
$mostrar_ver_todos = isset($args['mostrar_ver_todos']) ? $args['mostrar_ver_todos'] : true;
$url_ver_todos = isset($args['url_ver_todos']) ? $args['url_ver_todos'] : '#';
$columnas = isset($args['columnas']) ? intval($args['columnas']) : 3;
$clases_adicionales = isset($args['clases_adicionales']) ? $args['clases_adicionales'] : '';

// Datos de demostración si no se proporcionan grupos
if (empty($grupos)) {
    $grupos = array(
        array(
            'id' => 1,
            'nombre' => 'Vecinos del Centro',
            'avatar' => '',
            'descripcion' => 'Comunidad de vecinos del centro histórico',
            'miembros' => 234,
            'categoria' => 'Barrio',
            'es_privado' => false,
            'esta_unido' => false,
        ),
        array(
            'id' => 2,
            'nombre' => 'Padres y Madres CEIP Sol',
            'avatar' => '',
            'descripcion' => 'Grupo de familias del colegio CEIP Sol',
            'miembros' => 89,
            'categoria' => 'Educación',
            'es_privado' => false,
            'esta_unido' => true,
        ),
        array(
            'id' => 3,
            'nombre' => 'Runners del Parque',
            'avatar' => '',
            'descripcion' => 'Grupo para corredores del parque municipal',
            'miembros' => 156,
            'categoria' => 'Deporte',
            'es_privado' => false,
            'esta_unido' => false,
        ),
        array(
            'id' => 4,
            'nombre' => 'Mercadillo Solidario',
            'avatar' => '',
            'descripcion' => 'Intercambio y donación de objetos entre vecinos',
            'miembros' => 412,
            'categoria' => 'Solidaridad',
            'es_privado' => false,
            'esta_unido' => false,
        ),
        array(
            'id' => 5,
            'nombre' => 'Comunidad Calle Mayor 15',
            'avatar' => '',
            'descripcion' => 'Vecinos del edificio Calle Mayor 15',
            'miembros' => 24,
            'categoria' => 'Comunidad',
            'es_privado' => true,
            'esta_unido' => false,
        ),
        array(
            'id' => 6,
            'nombre' => 'Huerto Urbano Norte',
            'avatar' => '',
            'descripcion' => 'Amantes de la agricultura urbana',
            'miembros' => 67,
            'categoria' => 'Medio Ambiente',
            'es_privado' => false,
            'esta_unido' => true,
        ),
    );
}

// Colores para avatares por defecto según categoría
$colores_categoria = array(
    'Barrio' => '#4A90D9',
    'Educación' => '#7B68EE',
    'Deporte' => '#28A745',
    'Solidaridad' => '#E74C3C',
    'Comunidad' => '#F39C12',
    'Medio Ambiente' => '#27AE60',
    'default' => '#6C757D',
);

/**
 * Obtener inicial del nombre del grupo
 */
function flavor_obtener_inicial_grupo($nombre_grupo) {
    $palabras = explode(' ', trim($nombre_grupo));
    $inicial = '';
    foreach ($palabras as $palabra) {
        if (!empty($palabra) && strlen($inicial) < 2) {
            $inicial .= mb_strtoupper(mb_substr($palabra, 0, 1));
        }
    }
    return $inicial ?: 'G';
}

/**
 * Formatear número de miembros
 */
function flavor_formatear_miembros($numero_miembros) {
    if ($numero_miembros >= 1000) {
        return number_format($numero_miembros / 1000, 1) . 'k';
    }
    return number_format($numero_miembros);
}
?>

<section class="flavor-grupos-destacados <?php echo esc_attr($clases_adicionales); ?>">
    <div class="flavor-grupos-destacados__header">
        <div class="flavor-grupos-destacados__titulos">
            <h2 class="flavor-grupos-destacados__titulo"><?php echo esc_html($titulo_seccion); ?></h2>
            <?php if ($subtitulo) : ?>
                <p class="flavor-grupos-destacados__subtitulo"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($mostrar_ver_todos) : ?>
            <a href="<?php echo esc_url($url_ver_todos); ?>" class="flavor-grupos-destacados__ver-todos">
                <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="flavor-grupos-destacados__icono-flecha" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <div class="flavor-grupos-destacados__grid flavor-grupos-destacados__grid--cols-<?php echo esc_attr($columnas); ?>">
        <?php foreach ($grupos as $grupo) :
            $grupo_id = isset($grupo['id']) ? $grupo['id'] : 0;
            $grupo_nombre = isset($grupo['nombre']) ? $grupo['nombre'] : '';
            $grupo_avatar = isset($grupo['avatar']) ? $grupo['avatar'] : '';
            $grupo_descripcion = isset($grupo['descripcion']) ? $grupo['descripcion'] : '';
            $grupo_miembros = isset($grupo['miembros']) ? intval($grupo['miembros']) : 0;
            $grupo_categoria = isset($grupo['categoria']) ? $grupo['categoria'] : '';
            $grupo_es_privado = isset($grupo['es_privado']) ? $grupo['es_privado'] : false;
            $grupo_esta_unido = isset($grupo['esta_unido']) ? $grupo['esta_unido'] : false;

            $color_avatar = isset($colores_categoria[$grupo_categoria]) ? $colores_categoria[$grupo_categoria] : $colores_categoria['default'];
            $inicial_grupo = flavor_obtener_inicial_grupo($grupo_nombre);
        ?>
            <article class="flavor-grupo-card <?php echo $grupo_esta_unido ? 'flavor-grupo-card--unido' : ''; ?>">
                <div class="flavor-grupo-card__avatar-wrapper">
                    <?php if (!empty($grupo_avatar)) : ?>
                        <img
                            src="<?php echo esc_url($grupo_avatar); ?>"
                            alt="<?php echo esc_attr($grupo_nombre); ?>"
                            class="flavor-grupo-card__avatar-img"
                        />
                    <?php else : ?>
                        <div
                            class="flavor-grupo-card__avatar-placeholder"
                            style="background-color: <?php echo esc_attr($color_avatar); ?>;"
                        >
                            <span class="flavor-grupo-card__avatar-inicial"><?php echo esc_html($inicial_grupo); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($grupo_es_privado) : ?>
                        <span class="flavor-grupo-card__badge-privado" title="<?php esc_attr_e('Grupo privado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5.5H3C2.72386 5.5 2.5 5.72386 2.5 6V9.5C2.5 9.77614 2.72386 10 3 10H9C9.27614 10 9.5 9.77614 9.5 9.5V6C9.5 5.72386 9.27614 5.5 9 5.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M4 5.5V3.5C4 2.39543 4.89543 1.5 6 1.5C7.10457 1.5 8 2.39543 8 3.5V5.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flavor-grupo-card__contenido">
                    <h3 class="flavor-grupo-card__nombre"><?php echo esc_html($grupo_nombre); ?></h3>

                    <?php if ($grupo_categoria) : ?>
                        <span class="flavor-grupo-card__categoria"><?php echo esc_html($grupo_categoria); ?></span>
                    <?php endif; ?>

                    <?php if ($grupo_descripcion) : ?>
                        <p class="flavor-grupo-card__descripcion"><?php echo esc_html($grupo_descripcion); ?></p>
                    <?php endif; ?>

                    <div class="flavor-grupo-card__meta">
                        <div class="flavor-grupo-card__miembros">
                            <svg class="flavor-grupo-card__icono-miembros" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 14V12.6667C11 11.9594 10.719 11.2811 10.219 10.781C9.71895 10.281 9.04058 10 8.33333 10H3.66667C2.95942 10 2.28105 10.281 1.78095 10.781C1.28086 11.2811 1 11.9594 1 12.6667V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6 7.33333C7.47276 7.33333 8.66667 6.13943 8.66667 4.66667C8.66667 3.19391 7.47276 2 6 2C4.52724 2 3.33333 3.19391 3.33333 4.66667C3.33333 6.13943 4.52724 7.33333 6 7.33333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15 14V12.6667C14.9995 12.0758 14.7973 11.5019 14.4256 11.0349C14.0539 10.5679 13.5345 10.2344 12.9533 10.0867" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10.9533 2.08667C11.5361 2.23354 12.0573 2.56714 12.4302 3.03488C12.8031 3.50262 13.006 4.07789 13.006 4.67C13.006 5.26211 12.8031 5.83738 12.4302 6.30512C12.0573 6.77286 11.5361 7.10646 10.9533 7.25333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="flavor-grupo-card__miembros-texto">
                                <?php echo esc_html(flavor_formatear_miembros($grupo_miembros)); ?>
                                <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flavor-grupo-card__acciones">
                    <?php if ($grupo_esta_unido) : ?>
                        <button
                            type="button"
                            class="flavor-grupo-card__btn flavor-grupo-card__btn--unido"
                            data-grupo-id="<?php echo esc_attr($grupo_id); ?>"
                            disabled
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.3334 4L6.00008 11.3333L2.66675 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php esc_html_e('Unido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php else : ?>
                        <button
                            type="button"
                            class="flavor-grupo-card__btn flavor-grupo-card__btn--unirse"
                            data-grupo-id="<?php echo esc_attr($grupo_id); ?>"
                        >
                            <?php if ($grupo_es_privado) : ?>
                                <?php esc_html_e('Solicitar unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php else : ?>
                                <?php esc_html_e('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php endif; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<style>
.flavor-grupos-destacados {
    padding: 2rem 0;
}

.flavor-grupos-destacados__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.flavor-grupos-destacados__titulos {
    flex: 1;
    min-width: 200px;
}

.flavor-grupos-destacados__titulo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 0.25rem 0;
}

.flavor-grupos-destacados__subtitulo {
    font-size: 0.9375rem;
    color: #6c757d;
    margin: 0;
}

.flavor-grupos-destacados__ver-todos {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4A90D9;
    text-decoration: none;
    transition: color 0.2s ease;
}

.flavor-grupos-destacados__ver-todos:hover {
    color: #357ABD;
}

.flavor-grupos-destacados__icono-flecha {
    transition: transform 0.2s ease;
}

.flavor-grupos-destacados__ver-todos:hover .flavor-grupos-destacados__icono-flecha {
    transform: translateX(2px);
}

.flavor-grupos-destacados__grid {
    display: grid;
    gap: 1.25rem;
}

.flavor-grupos-destacados__grid--cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-grupos-destacados__grid--cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.flavor-grupos-destacados__grid--cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

/* Card de grupo */
.flavor-grupo-card {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.flavor-grupo-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.flavor-grupo-card--unido {
    border-color: #4A90D9;
    background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.flavor-grupo-card__avatar-wrapper {
    position: relative;
    width: 56px;
    height: 56px;
    flex-shrink: 0;
}

.flavor-grupo-card__avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 12px;
    object-fit: cover;
}

.flavor-grupo-card__avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-grupo-card__avatar-inicial {
    font-size: 1.25rem;
    font-weight: 600;
    color: #ffffff;
    text-transform: uppercase;
}

.flavor-grupo-card__badge-privado {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 22px;
    height: 22px;
    background: #ffffff;
    border: 2px solid #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.flavor-grupo-card__contenido {
    flex: 1;
    min-width: 0;
}

.flavor-grupo-card__nombre {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-grupo-card__categoria {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6c757d;
    background: #f1f3f4;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.flavor-grupo-card__descripcion {
    font-size: 0.8125rem;
    color: #6c757d;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.flavor-grupo-card__meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.flavor-grupo-card__miembros {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.flavor-grupo-card__icono-miembros {
    color: #6c757d;
}

.flavor-grupo-card__miembros-texto {
    font-size: 0.8125rem;
    color: #6c757d;
}

.flavor-grupo-card__acciones {
    margin-top: auto;
}

.flavor-grupo-card__btn {
    width: 100%;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    transition: all 0.2s ease;
}

.flavor-grupo-card__btn--unirse {
    background: #4A90D9;
    color: #ffffff;
}

.flavor-grupo-card__btn--unirse:hover {
    background: #357ABD;
}

.flavor-grupo-card__btn--unido {
    background: #e8f4fd;
    color: #4A90D9;
    cursor: default;
}

/* Responsive */
@media (max-width: 1024px) {
    .flavor-grupos-destacados__grid--cols-4 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .flavor-grupos-destacados__grid--cols-3,
    .flavor-grupos-destacados__grid--cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-grupos-destacados__titulo {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .flavor-grupos-destacados__grid--cols-2,
    .flavor-grupos-destacados__grid--cols-3,
    .flavor-grupos-destacados__grid--cols-4 {
        grid-template-columns: 1fr;
    }

    .flavor-grupo-card {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .flavor-grupo-card__avatar-wrapper {
        width: 48px;
        height: 48px;
    }

    .flavor-grupo-card__contenido {
        flex: 1;
        min-width: calc(100% - 64px);
    }

    .flavor-grupo-card__acciones {
        width: 100%;
    }
}
</style>
