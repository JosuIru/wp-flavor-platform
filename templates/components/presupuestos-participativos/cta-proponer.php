<?php
/**
 * Template: Call to Action - Proponer Proyecto
 *
 * Call to action para invitar a proponer un proyecto
 * en los presupuestos participativos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Tu idea puede transformar tu comunidad';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Presenta tu propuesta y participa en la construcción de una ciudad mejor para todos';
$texto_boton = isset($args['texto_boton']) ? $args['texto_boton'] : 'Proponer mi proyecto';
$url_boton = isset($args['url_boton']) ? $args['url_boton'] : '#proponer';
$texto_boton_secundario = isset($args['texto_boton_secundario']) ? $args['texto_boton_secundario'] : 'Ver proyectos activos';
$url_boton_secundario = isset($args['url_boton_secundario']) ? $args['url_boton_secundario'] : '#proyectos';
$mostrar_estadisticas = isset($args['mostrar_estadisticas']) ? (bool) $args['mostrar_estadisticas'] : true;
$mostrar_boton_secundario = isset($args['mostrar_boton_secundario']) ? (bool) $args['mostrar_boton_secundario'] : true;
$imagen_fondo = isset($args['imagen_fondo']) ? $args['imagen_fondo'] : '';
$estilo_variante = isset($args['variante']) ? $args['variante'] : 'gradiente'; // gradiente, imagen, simple

// Estadísticas de demostración si no hay datos reales
$estadisticas = isset($args['estadisticas']) ? $args['estadisticas'] : array();
if (empty($estadisticas) && $mostrar_estadisticas) {
    $estadisticas = array(
        array(
            'valor' => '1.247',
            'etiqueta' => 'Propuestas recibidas',
            'icono' => 'lightbulb'
        ),
        array(
            'valor' => '€2.5M',
            'etiqueta' => 'Presupuesto disponible',
            'icono' => 'euro-sign'
        ),
        array(
            'valor' => '89',
            'etiqueta' => 'Proyectos ejecutados',
            'icono' => 'check-circle'
        ),
        array(
            'valor' => '15.420',
            'etiqueta' => 'Ciudadanos participantes',
            'icono' => 'users'
        )
    );
}

// Características destacadas
$caracteristicas = isset($args['caracteristicas']) ? $args['caracteristicas'] : array();
if (empty($caracteristicas)) {
    $caracteristicas = array(
        array(
            'icono' => 'clock',
            'texto' => 'Proceso abierto hasta el 31 de marzo'
        ),
        array(
            'icono' => 'shield-alt',
            'texto' => 'Participación segura y verificada'
        ),
        array(
            'icono' => 'hand-holding-heart',
            'texto' => 'Apoyo técnico gratuito'
        )
    );
}

$clase_contenedor = 'flavor-cta-proponer flavor-cta-' . esc_attr($estilo_variante);
if (isset($args['clase_extra'])) {
    $clase_contenedor .= ' ' . esc_attr($args['clase_extra']);
}

$estilo_fondo = '';
if ($estilo_variante === 'imagen' && !empty($imagen_fondo)) {
    $estilo_fondo = 'background-image: url(' . esc_url($imagen_fondo) . ');';
}
?>

<section class="<?php echo esc_attr($clase_contenedor); ?>" <?php echo !empty($estilo_fondo) ? 'style="' . esc_attr($estilo_fondo) . '"' : ''; ?>>
    <?php if ($estilo_variante === 'imagen') : ?>
        <div class="flavor-cta-overlay"></div>
    <?php endif; ?>

    <div class="flavor-cta-contenedor">
        <div class="flavor-cta-contenido">
            <div class="flavor-cta-badge">
                <svg class="flavor-cta-badge-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"></path>
                </svg>
                <span>Presupuestos Participativos 2024</span>
            </div>

            <?php if (!empty($titulo)) : ?>
                <h2 class="flavor-cta-titulo"><?php echo esc_html($titulo); ?></h2>
            <?php endif; ?>

            <?php if (!empty($subtitulo)) : ?>
                <p class="flavor-cta-subtitulo"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>

            <?php if (!empty($caracteristicas)) : ?>
                <ul class="flavor-cta-caracteristicas">
                    <?php foreach ($caracteristicas as $caracteristica) : ?>
                        <li class="flavor-cta-caracteristica">
                            <?php if (!empty($caracteristica['icono'])) : ?>
                                <i class="fas fa-<?php echo esc_attr($caracteristica['icono']); ?>" aria-hidden="true"></i>
                            <?php endif; ?>
                            <span><?php echo esc_html($caracteristica['texto']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="flavor-cta-botones">
                <a href="<?php echo esc_url($url_boton); ?>" class="flavor-cta-boton flavor-cta-boton-primario">
                    <svg class="flavor-cta-boton-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span><?php echo esc_html($texto_boton); ?></span>
                </a>

                <?php if ($mostrar_boton_secundario && !empty($texto_boton_secundario)) : ?>
                    <a href="<?php echo esc_url($url_boton_secundario); ?>" class="flavor-cta-boton flavor-cta-boton-secundario">
                        <span><?php echo esc_html($texto_boton_secundario); ?></span>
                        <svg class="flavor-cta-boton-flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($mostrar_estadisticas && !empty($estadisticas)) : ?>
            <div class="flavor-cta-estadisticas">
                <?php foreach ($estadisticas as $estadistica) : ?>
                    <div class="flavor-cta-estadistica">
                        <?php if (!empty($estadistica['icono'])) : ?>
                            <div class="flavor-cta-estadistica-icono">
                                <i class="fas fa-<?php echo esc_attr($estadistica['icono']); ?>" aria-hidden="true"></i>
                            </div>
                        <?php endif; ?>
                        <div class="flavor-cta-estadistica-valor"><?php echo esc_html($estadistica['valor']); ?></div>
                        <div class="flavor-cta-estadistica-etiqueta"><?php echo esc_html($estadistica['etiqueta']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="flavor-cta-decoracion">
        <div class="flavor-cta-circulo flavor-cta-circulo-1"></div>
        <div class="flavor-cta-circulo flavor-cta-circulo-2"></div>
        <div class="flavor-cta-circulo flavor-cta-circulo-3"></div>
    </div>
</section>

<style>
.flavor-cta-proponer {
    position: relative;
    padding: 3rem 1.5rem;
    border-radius: 1.5rem;
    margin: 2rem 0;
    overflow: hidden;
}

.flavor-cta-gradiente {
    background: linear-gradient(135deg, #1e40af 0%, #7c3aed 50%, #db2777 100%);
}

.flavor-cta-imagen {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.flavor-cta-simple {
    background: #1e293b;
}

.flavor-cta-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.9) 0%, rgba(124, 58, 237, 0.85) 100%);
    z-index: 1;
}

.flavor-cta-contenedor {
    position: relative;
    z-index: 2;
    max-width: 1100px;
    margin: 0 auto;
}

.flavor-cta-contenido {
    text-align: center;
    color: #ffffff;
}

.flavor-cta-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.flavor-cta-badge-icono {
    width: 1rem;
    height: 1rem;
}

.flavor-cta-titulo {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.flavor-cta-subtitulo {
    font-size: 1.125rem;
    line-height: 1.6;
    opacity: 0.9;
    margin: 0 0 1.5rem 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.flavor-cta-caracteristicas {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.flavor-cta-caracteristica {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.flavor-cta-caracteristica i {
    opacity: 0.8;
}

.flavor-cta-botones {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2.5rem;
}

.flavor-cta-boton {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    padding: 1rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.flavor-cta-boton-primario {
    background: #ffffff;
    color: #1e40af;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.flavor-cta-boton-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #f8fafc;
}

.flavor-cta-boton-secundario {
    background: transparent;
    color: #ffffff;
    border: 2px solid rgba(255, 255, 255, 0.4);
}

.flavor-cta-boton-secundario:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.6);
}

.flavor-cta-boton-icono {
    width: 1.25rem;
    height: 1.25rem;
}

.flavor-cta-boton-flecha {
    width: 1.125rem;
    height: 1.125rem;
    transition: transform 0.2s ease;
}

.flavor-cta-boton-secundario:hover .flavor-cta-boton-flecha {
    transform: translateX(4px);
}

.flavor-cta-estadisticas {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.flavor-cta-estadistica {
    text-align: center;
    padding: 1rem;
}

.flavor-cta-estadistica-icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 0.75rem;
    margin-bottom: 0.75rem;
    color: #ffffff;
    font-size: 1rem;
}

.flavor-cta-estadistica-valor {
    font-size: 1.5rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.25rem;
}

.flavor-cta-estadistica-etiqueta {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.75);
}

/* Decoraciones */
.flavor-cta-decoracion {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 1;
}

.flavor-cta-circulo {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
}

.flavor-cta-circulo-1 {
    width: 300px;
    height: 300px;
    top: -100px;
    right: -50px;
}

.flavor-cta-circulo-2 {
    width: 200px;
    height: 200px;
    bottom: -50px;
    left: -50px;
}

.flavor-cta-circulo-3 {
    width: 150px;
    height: 150px;
    top: 50%;
    left: 20%;
    transform: translateY(-50%);
}

/* Responsive Design */
@media (min-width: 640px) {
    .flavor-cta-proponer {
        padding: 4rem 2rem;
    }

    .flavor-cta-titulo {
        font-size: 2.5rem;
    }

    .flavor-cta-botones {
        flex-direction: row;
        justify-content: center;
    }

    .flavor-cta-estadisticas {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 768px) {
    .flavor-cta-proponer {
        padding: 5rem 3rem;
    }

    .flavor-cta-titulo {
        font-size: 3rem;
    }

    .flavor-cta-subtitulo {
        font-size: 1.25rem;
    }

    .flavor-cta-estadistica-valor {
        font-size: 2rem;
    }

    .flavor-cta-estadistica-icono {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .flavor-cta-proponer {
        padding: 6rem 4rem;
    }

    .flavor-cta-titulo {
        font-size: 3.5rem;
    }

    .flavor-cta-circulo-1 {
        width: 500px;
        height: 500px;
        top: -200px;
        right: -100px;
    }

    .flavor-cta-circulo-2 {
        width: 350px;
        height: 350px;
        bottom: -100px;
        left: -100px;
    }

    .flavor-cta-circulo-3 {
        width: 250px;
        height: 250px;
    }
}

/* Animaciones sutiles */
@keyframes flavor-pulse {
    0%, 100% {
        opacity: 0.05;
    }
    50% {
        opacity: 0.1;
    }
}

.flavor-cta-circulo-1 {
    animation: flavor-pulse 4s ease-in-out infinite;
}

.flavor-cta-circulo-2 {
    animation: flavor-pulse 5s ease-in-out infinite 1s;
}

.flavor-cta-circulo-3 {
    animation: flavor-pulse 6s ease-in-out infinite 2s;
}
</style>
