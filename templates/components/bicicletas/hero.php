<?php
/**
 * Hero Section - Bicicletas Compartidas
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Bicicletas
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_principal = isset($args['titulo_principal']) ? $args['titulo_principal'] : 'Muévete por la ciudad de forma sostenible';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Bicicletas compartidas disponibles las 24 horas del día, los 7 días de la semana';
$imagen_fondo = isset($args['imagen_fondo']) ? $args['imagen_fondo'] : '';
$texto_boton_primario = isset($args['texto_boton_primario']) ? $args['texto_boton_primario'] : 'Comenzar ahora';
$enlace_boton_primario = isset($args['enlace_boton_primario']) ? $args['enlace_boton_primario'] : '#registro';
$texto_boton_secundario = isset($args['texto_boton_secundario']) ? $args['texto_boton_secundario'] : 'Ver estaciones';
$enlace_boton_secundario = isset($args['enlace_boton_secundario']) ? $args['enlace_boton_secundario'] : '#mapa';
$estadisticas = isset($args['estadisticas']) ? $args['estadisticas'] : array();
$overlay_color = isset($args['overlay_color']) ? $args['overlay_color'] : 'rgba(0, 0, 0, 0.5)';
$altura_hero = isset($args['altura_hero']) ? $args['altura_hero'] : '100vh';

// Datos de demostración para estadísticas si no hay datos reales
if (empty($estadisticas)) {
    $estadisticas = array(
        array(
            'valor' => '500+',
            'etiqueta' => 'Bicicletas disponibles'
        ),
        array(
            'valor' => '150',
            'etiqueta' => 'Estaciones activas'
        ),
        array(
            'valor' => '50K+',
            'etiqueta' => 'Usuarios satisfechos'
        ),
        array(
            'valor' => '24/7',
            'etiqueta' => 'Disponibilidad'
        )
    );
}

// Imagen de fondo por defecto (placeholder)
$estilo_fondo = '';
if (!empty($imagen_fondo)) {
    $estilo_fondo = 'background-image: url(' . esc_url($imagen_fondo) . ');';
} else {
    $estilo_fondo = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);';
}
?>

<section class="flavor-hero-bicicletas" style="<?php echo esc_attr($estilo_fondo); ?> min-height: <?php echo esc_attr($altura_hero); ?>;">
    <div class="flavor-hero-overlay" style="background-color: <?php echo esc_attr($overlay_color); ?>;"></div>

    <div class="flavor-hero-contenido">
        <div class="flavor-hero-texto">
            <h1 class="flavor-hero-titulo">
                <?php echo esc_html($titulo_principal); ?>
            </h1>

            <p class="flavor-hero-subtitulo">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <div class="flavor-hero-botones">
                <a href="<?php echo esc_url($enlace_boton_primario); ?>" class="flavor-boton flavor-boton-primario">
                    <span class="flavor-boton-icono">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18.5" cy="17.5" r="3.5"></circle>
                            <circle cx="5.5" cy="17.5" r="3.5"></circle>
                            <circle cx="15" cy="5" r="1"></circle>
                            <path d="M12 17.5V14l-3-3 4-3 2 3h2"></path>
                        </svg>
                    </span>
                    <?php echo esc_html($texto_boton_primario); ?>
                </a>

                <a href="<?php echo esc_url($enlace_boton_secundario); ?>" class="flavor-boton flavor-boton-secundario">
                    <span class="flavor-boton-icono">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </span>
                    <?php echo esc_html($texto_boton_secundario); ?>
                </a>
            </div>
        </div>

        <?php if (!empty($estadisticas)) : ?>
        <div class="flavor-hero-estadisticas">
            <?php foreach ($estadisticas as $estadistica) : ?>
            <div class="flavor-estadistica-item">
                <span class="flavor-estadistica-valor"><?php echo esc_html($estadistica['valor']); ?></span>
                <span class="flavor-estadistica-etiqueta"><?php echo esc_html($estadistica['etiqueta']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="flavor-hero-scroll-indicador">
        <span class="flavor-scroll-texto">Descubre más</span>
        <div class="flavor-scroll-flecha">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="7 13 12 18 17 13"></polyline>
                <polyline points="7 6 12 11 17 6"></polyline>
            </svg>
        </div>
    </div>
</section>

<style>
.flavor-hero-bicicletas {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #ffffff;
    overflow: hidden;
}

.flavor-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.flavor-hero-contenido {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    width: 100%;
    padding: 2rem;
    text-align: center;
}

.flavor-hero-texto {
    margin-bottom: 3rem;
}

.flavor-hero-titulo {
    font-size: clamp(2rem, 5vw, 4rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1.5rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.flavor-hero-subtitulo {
    font-size: clamp(1rem, 2vw, 1.5rem);
    font-weight: 400;
    opacity: 0.9;
    max-width: 700px;
    margin: 0 auto 2rem;
    line-height: 1.6;
}

.flavor-hero-botones {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.flavor-boton {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 50px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.flavor-boton-icono {
    display: flex;
    align-items: center;
}

.flavor-boton-primario {
    background: linear-gradient(135deg, #00c853 0%, #00e676 100%);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 200, 83, 0.4);
}

.flavor-boton-primario:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 200, 83, 0.5);
}

.flavor-boton-secundario {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 2px solid rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
}

.flavor-boton-secundario:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: #ffffff;
}

.flavor-hero-estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 2rem;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.flavor-estadistica-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.flavor-estadistica-valor {
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    font-weight: 700;
    color: #00e676;
}

.flavor-estadistica-etiqueta {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.flavor-hero-scroll-indicador {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: flavor-bounce 2s infinite;
}

.flavor-scroll-texto {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 0.5rem;
    opacity: 0.7;
}

.flavor-scroll-flecha {
    opacity: 0.7;
}

@keyframes flavor-bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateX(-50%) translateY(0);
    }
    40% {
        transform: translateX(-50%) translateY(-10px);
    }
    60% {
        transform: translateX(-50%) translateY(-5px);
    }
}

@media (max-width: 768px) {
    .flavor-hero-bicicletas {
        background-attachment: scroll;
    }

    .flavor-hero-contenido {
        padding: 1rem;
    }

    .flavor-hero-estadisticas {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        padding: 1.5rem;
    }

    .flavor-boton {
        width: 100%;
        justify-content: center;
    }
}
</style>
