<?php
/**
 * Template: CTA para organizar talleres
 *
 * Muestra los beneficios, requisitos y botón de registro para animar
 * a los usuarios a convertirse en organizadores de talleres.
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Talleres
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_principal = isset($args['titulo_principal']) ? $args['titulo_principal'] : '¿Quieres organizar tu propio taller?';
$subtitulo = isset($args['subtitulo']) ? $args['subtitulo'] : 'Comparte tu conocimiento con la comunidad y genera ingresos extra';

$beneficios = isset($args['beneficios']) ? $args['beneficios'] : array(
    array(
        'icono' => 'dashicons-money-alt',
        'titulo' => 'Genera ingresos',
        'descripcion' => 'Recibe el 70% de las inscripciones a tus talleres'
    ),
    array(
        'icono' => 'dashicons-groups',
        'titulo' => 'Amplía tu red',
        'descripcion' => 'Conecta con personas interesadas en tu área de expertise'
    ),
    array(
        'icono' => 'dashicons-calendar-alt',
        'titulo' => 'Flexibilidad total',
        'descripcion' => 'Tú decides cuándo, dónde y cómo impartir tus talleres'
    ),
    array(
        'icono' => 'dashicons-megaphone',
        'titulo' => 'Promoción incluida',
        'descripcion' => 'Nosotros nos encargamos de difundir tu taller en la comunidad'
    )
);

$requisitos = isset($args['requisitos']) ? $args['requisitos'] : array(
    'Experiencia demostrable en el tema que deseas enseñar',
    'Disponibilidad para impartir al menos un taller al mes',
    'Compromiso con la calidad y atención a los participantes',
    'Capacidad de comunicación clara y didáctica'
);

$texto_boton_registro = isset($args['texto_boton_registro']) ? $args['texto_boton_registro'] : 'Quiero ser organizador';
$url_registro = isset($args['url_registro']) ? $args['url_registro'] : '#registro-organizador';

$estadisticas = isset($args['estadisticas']) ? $args['estadisticas'] : array(
    array('numero' => '150+', 'etiqueta' => 'Organizadores activos'),
    array('numero' => '500+', 'etiqueta' => 'Talleres realizados'),
    array('numero' => '10K+', 'etiqueta' => 'Participantes satisfechos')
);

$mostrar_estadisticas = isset($args['mostrar_estadisticas']) ? $args['mostrar_estadisticas'] : true;
$mostrar_requisitos = isset($args['mostrar_requisitos']) ? $args['mostrar_requisitos'] : true;

$clases_adicionales = isset($args['clases_adicionales']) ? $args['clases_adicionales'] : '';
?>

<section class="flavor-cta-organizador <?php echo esc_attr($clases_adicionales); ?>">
    <div class="flavor-cta-organizador__contenedor">

        <!-- Encabezado -->
        <header class="flavor-cta-organizador__encabezado">
            <h2 class="flavor-cta-organizador__titulo">
                <?php echo esc_html($titulo_principal); ?>
            </h2>
            <p class="flavor-cta-organizador__subtitulo">
                <?php echo esc_html($subtitulo); ?>
            </p>
        </header>

        <!-- Beneficios -->
        <div class="flavor-cta-organizador__beneficios">
            <h3 class="flavor-cta-organizador__seccion-titulo">Beneficios de ser organizador</h3>
            <div class="flavor-cta-organizador__beneficios-grid">
                <?php foreach ($beneficios as $beneficio) : ?>
                    <div class="flavor-cta-organizador__beneficio">
                        <span class="flavor-cta-organizador__beneficio-icono dashicons <?php echo esc_attr($beneficio['icono']); ?>"></span>
                        <h4 class="flavor-cta-organizador__beneficio-titulo">
                            <?php echo esc_html($beneficio['titulo']); ?>
                        </h4>
                        <p class="flavor-cta-organizador__beneficio-descripcion">
                            <?php echo esc_html($beneficio['descripcion']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Requisitos -->
        <?php if ($mostrar_requisitos && !empty($requisitos)) : ?>
            <div class="flavor-cta-organizador__requisitos">
                <h3 class="flavor-cta-organizador__seccion-titulo">Requisitos</h3>
                <ul class="flavor-cta-organizador__requisitos-lista">
                    <?php foreach ($requisitos as $requisito) : ?>
                        <li class="flavor-cta-organizador__requisito">
                            <span class="flavor-cta-organizador__requisito-check dashicons dashicons-yes-alt"></span>
                            <?php echo esc_html($requisito); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <?php if ($mostrar_estadisticas && !empty($estadisticas)) : ?>
            <div class="flavor-cta-organizador__estadisticas">
                <?php foreach ($estadisticas as $estadistica) : ?>
                    <div class="flavor-cta-organizador__estadistica">
                        <span class="flavor-cta-organizador__estadistica-numero">
                            <?php echo esc_html($estadistica['numero']); ?>
                        </span>
                        <span class="flavor-cta-organizador__estadistica-etiqueta">
                            <?php echo esc_html($estadistica['etiqueta']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Botón de registro -->
        <div class="flavor-cta-organizador__accion">
            <a href="<?php echo esc_url($url_registro); ?>" class="flavor-cta-organizador__boton">
                <?php echo esc_html($texto_boton_registro); ?>
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </a>
            <p class="flavor-cta-organizador__nota">
                Sin compromisos. Revisaremos tu solicitud en 48 horas.
            </p>
        </div>

    </div>
</section>

<style>
.flavor-cta-organizador {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 20px;
    color: #fff;
}

.flavor-cta-organizador__contenedor {
    max-width: 1200px;
    margin: 0 auto;
}

.flavor-cta-organizador__encabezado {
    text-align: center;
    margin-bottom: 50px;
}

.flavor-cta-organizador__titulo {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 15px;
    color: #fff;
}

.flavor-cta-organizador__subtitulo {
    font-size: 1.25rem;
    opacity: 0.9;
    margin: 0;
}

.flavor-cta-organizador__seccion-titulo {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 25px;
    text-align: center;
    color: #fff;
}

/* Beneficios */
.flavor-cta-organizador__beneficios {
    margin-bottom: 50px;
}

.flavor-cta-organizador__beneficios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.flavor-cta-organizador__beneficio {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    transition: transform 0.3s ease, background 0.3s ease;
}

.flavor-cta-organizador__beneficio:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.25);
}

.flavor-cta-organizador__beneficio-icono {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 15px;
    display: block;
}

.flavor-cta-organizador__beneficio-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 10px;
    color: #fff;
}

.flavor-cta-organizador__beneficio-descripcion {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
    line-height: 1.6;
}

/* Requisitos */
.flavor-cta-organizador__requisitos {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 50px;
}

.flavor-cta-organizador__requisitos-lista {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.flavor-cta-organizador__requisito {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1rem;
    line-height: 1.5;
}

.flavor-cta-organizador__requisito-check {
    color: #4ade80;
    font-size: 24px;
    flex-shrink: 0;
}

/* Estadísticas */
.flavor-cta-organizador__estadisticas {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin-bottom: 50px;
    flex-wrap: wrap;
}

.flavor-cta-organizador__estadistica {
    text-align: center;
}

.flavor-cta-organizador__estadistica-numero {
    display: block;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
}

.flavor-cta-organizador__estadistica-etiqueta {
    display: block;
    font-size: 0.95rem;
    opacity: 0.9;
    margin-top: 8px;
}

/* Botón de acción */
.flavor-cta-organizador__accion {
    text-align: center;
}

.flavor-cta-organizador__boton {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    color: #764ba2;
    padding: 18px 40px;
    border-radius: 50px;
    font-size: 1.15rem;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.flavor-cta-organizador__boton:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    color: #667eea;
}

.flavor-cta-organizador__nota {
    font-size: 0.875rem;
    opacity: 0.8;
    margin: 15px 0 0;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-cta-organizador {
        padding: 40px 15px;
    }

    .flavor-cta-organizador__titulo {
        font-size: 1.75rem;
    }

    .flavor-cta-organizador__subtitulo {
        font-size: 1rem;
    }

    .flavor-cta-organizador__beneficios-grid {
        grid-template-columns: 1fr;
    }

    .flavor-cta-organizador__requisitos {
        padding: 25px;
    }

    .flavor-cta-organizador__requisitos-lista {
        grid-template-columns: 1fr;
    }

    .flavor-cta-organizador__estadisticas {
        gap: 30px;
    }

    .flavor-cta-organizador__estadistica-numero {
        font-size: 2.25rem;
    }

    .flavor-cta-organizador__boton {
        padding: 15px 30px;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .flavor-cta-organizador__titulo {
        font-size: 1.5rem;
    }

    .flavor-cta-organizador__beneficio {
        padding: 20px;
    }

    .flavor-cta-organizador__estadisticas {
        flex-direction: column;
        gap: 20px;
    }
}
</style>
