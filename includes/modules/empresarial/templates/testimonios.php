<?php
/**
 * Template: Testimonios
 *
 * @package FlavorChatIA
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = esc_html($atts['titulo']);
$tipo_layout = sanitize_key($atts['layout']);

// Obtener testimonios desde opciones o definir por defecto
$lista_testimonios = get_option('flavor_empresarial_testimonios', []);

if (empty($lista_testimonios)) {
    $lista_testimonios = [
        [
            'nombre'     => 'Laura Fernández',
            'puesto'     => __('CEO', 'flavor-chat-ia'),
            'empresa'    => 'Tech Solutions',
            'testimonio' => __('Excelente servicio. Han transformado completamente nuestros procesos digitales y los resultados han superado nuestras expectativas.', 'flavor-chat-ia'),
            'rating'     => 5,
            'foto'       => '',
        ],
        [
            'nombre'     => 'Miguel Rodríguez',
            'puesto'     => __('Director de Operaciones', 'flavor-chat-ia'),
            'empresa'    => 'Grupo Industrial',
            'testimonio' => __('Profesionales comprometidos y resultados tangibles. La colaboración con este equipo ha sido clave para nuestro crecimiento.', 'flavor-chat-ia'),
            'rating'     => 5,
            'foto'       => '',
        ],
        [
            'nombre'     => 'Carmen Díaz',
            'puesto'     => __('Fundadora', 'flavor-chat-ia'),
            'empresa'    => 'Startup Innovation',
            'testimonio' => __('Desde el primer día demostraron un profundo conocimiento de nuestras necesidades. El ROI ha sido impresionante.', 'flavor-chat-ia'),
            'rating'     => 5,
            'foto'       => '',
        ],
    ];
}
?>

<section class="flavor-emp-testimonios flavor-emp-testimonios-<?php echo esc_attr($tipo_layout); ?>">
    <div class="flavor-emp-testimonios-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="flavor-emp-seccion-titulo"><?php echo $titulo_seccion; ?></h2>
        <?php endif; ?>
    </div>

    <div class="flavor-emp-testimonios-container" data-layout="<?php echo esc_attr($tipo_layout); ?>">
        <?php if ($tipo_layout === 'carousel'): ?>
            <div class="testimonios-carousel">
                <div class="testimonios-track">
        <?php endif; ?>

        <?php foreach ($lista_testimonios as $indice => $testimonio): ?>
            <article class="flavor-emp-testimonio-card <?php echo $tipo_layout === 'carousel' ? 'carousel-slide' : ''; ?>">
                <div class="testimonio-contenido">
                    <div class="testimonio-comilla">
                        <span class="dashicons dashicons-format-quote"></span>
                    </div>

                    <blockquote class="testimonio-texto">
                        <?php echo esc_html($testimonio['testimonio']); ?>
                    </blockquote>

                    <?php if (!empty($testimonio['rating'])): ?>
                        <div class="testimonio-rating">
                            <?php for ($estrella = 1; $estrella <= 5; $estrella++): ?>
                                <span class="estrella <?php echo $estrella <= $testimonio['rating'] ? 'llena' : 'vacia'; ?>">
                                    <span class="dashicons dashicons-star-<?php echo $estrella <= $testimonio['rating'] ? 'filled' : 'empty'; ?>"></span>
                                </span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="testimonio-autor">
                    <div class="autor-foto">
                        <?php if (!empty($testimonio['foto'])): ?>
                            <img src="<?php echo esc_url($testimonio['foto']); ?>" alt="<?php echo esc_attr($testimonio['nombre']); ?>">
                        <?php else: ?>
                            <div class="autor-foto-placeholder">
                                <?php echo esc_html(mb_substr($testimonio['nombre'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="autor-info">
                        <strong class="autor-nombre"><?php echo esc_html($testimonio['nombre']); ?></strong>
                        <span class="autor-cargo">
                            <?php echo esc_html($testimonio['puesto']); ?>
                            <?php if (!empty($testimonio['empresa'])): ?>
                                - <?php echo esc_html($testimonio['empresa']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($tipo_layout === 'carousel'): ?>
                </div>
                <div class="testimonios-controles">
                    <button type="button" class="control-prev" aria-label="<?php esc_attr_e('Anterior', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <div class="testimonios-dots">
                        <?php foreach ($lista_testimonios as $indice => $testimonio): ?>
                            <button type="button" class="dot <?php echo $indice === 0 ? 'active' : ''; ?>" data-slide="<?php echo esc_attr($indice); ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="control-next" aria-label="<?php esc_attr_e('Siguiente', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
