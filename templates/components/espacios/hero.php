<?php
/**
 * Template: Hero Section para Espacios Comunes
 *
 * Muestra una sección hero con imagen de fondo, título y CTA de reserva
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_principal = isset($args['titulo_principal']) ? $args['titulo_principal'] : 'Espacios que Inspiran Productividad';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Reserva salas de reuniones, auditorios y espacios de coworking diseñados para impulsar tu creatividad y colaboración.';
$imagen_fondo = isset($args['imagen_fondo']) ? $args['imagen_fondo'] : '';
$texto_boton_primario = isset($args['texto_boton_primario']) ? $args['texto_boton_primario'] : 'Reservar Ahora';
$enlace_boton_primario = isset($args['enlace_boton_primario']) ? $args['enlace_boton_primario'] : '#espacios';
$texto_boton_secundario = isset($args['texto_boton_secundario']) ? $args['texto_boton_secundario'] : 'Ver Disponibilidad';
$enlace_boton_secundario = isset($args['enlace_boton_secundario']) ? $args['enlace_boton_secundario'] : '#calendario';
$mostrar_estadisticas = isset($args['mostrar_estadisticas']) ? $args['mostrar_estadisticas'] : true;
$estadisticas = isset($args['estadisticas']) ? $args['estadisticas'] : array();
$altura_hero = isset($args['altura_hero']) ? $args['altura_hero'] : '600px';
$overlay_color = isset($args['overlay_color']) ? $args['overlay_color'] : 'rgba(0, 0, 0, 0.5)';

// Datos de demostración para estadísticas
if (empty($estadisticas)) {
    $estadisticas = array(
        array(
            'numero' => '15+',
            'etiqueta' => 'Espacios Disponibles',
            'icono' => 'building'
        ),
        array(
            'numero' => '500+',
            'etiqueta' => 'Reservas Mensuales',
            'icono' => 'calendar-check'
        ),
        array(
            'numero' => '98%',
            'etiqueta' => 'Clientes Satisfechos',
            'icono' => 'star'
        ),
        array(
            'numero' => '24/7',
            'etiqueta' => 'Soporte Disponible',
            'icono' => 'headset'
        )
    );
}

// Imagen de fondo por defecto (placeholder)
$estilo_fondo = '';
if (!empty($imagen_fondo)) {
    $estilo_fondo = "background-image: url('{$imagen_fondo}');";
} else {
    $estilo_fondo = "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);";
}
?>

<section class="flavor-espacios-hero" style="<?php echo esc_attr($estilo_fondo); ?> min-height: <?php echo esc_attr($altura_hero); ?>;">
    <div class="flavor-espacios-hero__overlay" style="background: <?php echo esc_attr($overlay_color); ?>;"></div>

    <div class="flavor-espacios-hero__container">
        <div class="flavor-espacios-hero__content">
            <h1 class="flavor-espacios-hero__titulo">
                <?php echo esc_html($titulo_principal); ?>
            </h1>

            <p class="flavor-espacios-hero__subtitulo">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <div class="flavor-espacios-hero__botones">
                <a href="<?php echo esc_url($enlace_boton_primario); ?>" class="flavor-espacios-hero__boton flavor-espacios-hero__boton--primario">
                    <span class="flavor-espacios-hero__boton-icono">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </span>
                    <?php echo esc_html($texto_boton_primario); ?>
                </a>

                <a href="<?php echo esc_url($enlace_boton_secundario); ?>" class="flavor-espacios-hero__boton flavor-espacios-hero__boton--secundario">
                    <?php echo esc_html($texto_boton_secundario); ?>
                </a>
            </div>
        </div>

        <?php if ($mostrar_estadisticas && !empty($estadisticas)) : ?>
        <div class="flavor-espacios-hero__estadisticas">
            <?php foreach ($estadisticas as $estadistica) : ?>
            <div class="flavor-espacios-hero__estadistica">
                <div class="flavor-espacios-hero__estadistica-icono">
                    <?php echo esc_html($estadistica['icono']); ?>
                </div>
                <div class="flavor-espacios-hero__estadistica-numero">
                    <?php echo esc_html($estadistica['numero']); ?>
                </div>
                <div class="flavor-espacios-hero__estadistica-etiqueta">
                    <?php echo esc_html($estadistica['etiqueta']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-espacios-hero {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    overflow: hidden;
}

.flavor-espacios-hero__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.flavor-espacios-hero__container {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
    text-align: center;
}

.flavor-espacios-hero__content {
    max-width: 800px;
    margin: 0 auto 50px;
}

.flavor-espacios-hero__titulo {
    font-size: 3rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 20px;
    line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.flavor-espacios-hero__subtitulo {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 40px;
    line-height: 1.6;
}

.flavor-espacios-hero__botones {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.flavor-espacios-hero__boton {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.flavor-espacios-hero__boton--primario {
    background: #ffffff;
    color: #667eea;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.flavor-espacios-hero__boton--primario:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.flavor-espacios-hero__boton--secundario {
    background: transparent;
    color: #ffffff;
    border: 2px solid #ffffff;
}

.flavor-espacios-hero__boton--secundario:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.flavor-espacios-hero__boton-icono {
    display: flex;
    align-items: center;
}

.flavor-espacios-hero__estadisticas {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    max-width: 900px;
    margin: 0 auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.flavor-espacios-hero__estadistica {
    text-align: center;
    color: #ffffff;
}

.flavor-espacios-hero__estadistica-icono {
    font-size: 1.5rem;
    margin-bottom: 8px;
    opacity: 0.8;
}

.flavor-espacios-hero__estadistica-numero {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.flavor-espacios-hero__estadistica-etiqueta {
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 992px) {
    .flavor-espacios-hero__titulo {
        font-size: 2.5rem;
    }

    .flavor-espacios-hero__estadisticas {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
}

@media (max-width: 576px) {
    .flavor-espacios-hero__container {
        padding: 40px 16px;
    }

    .flavor-espacios-hero__titulo {
        font-size: 1.875rem;
    }

    .flavor-espacios-hero__subtitulo {
        font-size: 1rem;
    }

    .flavor-espacios-hero__botones {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-espacios-hero__boton {
        justify-content: center;
        padding: 14px 24px;
    }

    .flavor-espacios-hero__estadisticas {
        grid-template-columns: repeat(2, 1fr);
        padding: 20px;
        gap: 16px;
    }

    .flavor-espacios-hero__estadistica-numero {
        font-size: 1.5rem;
    }
}
</style>
