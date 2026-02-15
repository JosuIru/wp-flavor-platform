<?php
/**
 * Template: Biblioteca de Objetos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Categorías de objetos
$categorias_objetos = [
    'herramientas' => ['nombre' => 'Herramientas', 'icono' => '🔧'],
    'electronica' => ['nombre' => 'Electrónica', 'icono' => '📱'],
    'deportes' => ['nombre' => 'Deportes', 'icono' => '⚽'],
    'cocina' => ['nombre' => 'Cocina', 'icono' => '🍳'],
    'jardineria' => ['nombre' => 'Jardinería', 'icono' => '🌱'],
    'bricolaje' => ['nombre' => 'Bricolaje', 'icono' => '🪚'],
    'libros' => ['nombre' => 'Libros', 'icono' => '📚'],
    'juegos' => ['nombre' => 'Juegos', 'icono' => '🎮'],
    'otros' => ['nombre' => 'Otros', 'icono' => '📦'],
];

// Obtener recursos disponibles
global $wpdb;
$recursos = $wpdb->get_results(
    "SELECT p.*, pm_cat.meta_value as categoria, pm_estado.meta_value as estado,
            pm_prestamos.meta_value as prestamos
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_es_categoria'
     LEFT JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_es_estado'
     LEFT JOIN {$wpdb->postmeta} pm_prestamos ON p.ID = pm_prestamos.post_id AND pm_prestamos.meta_key = '_es_prestamos'
     WHERE p.post_type = 'es_recurso'
       AND p.post_status = 'publish'
     ORDER BY p.post_date DESC"
);
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Biblioteca de Objetos', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('¿Por qué comprar algo que solo usarás una vez? Comparte y pide prestado.', 'flavor-chat-ia'); ?></p>
    </header>

    <div class="es-biblioteca-header">
        <div class="es-biblioteca-filtros">
            <button class="es-filtro-btn activo" data-filtro="todos"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></button>
            <?php foreach ($categorias_objetos as $cat_id => $cat_data) : ?>
            <button class="es-filtro-btn" data-filtro="<?php echo esc_attr($cat_id); ?>">
                <?php echo esc_html($cat_data['icono'] . ' ' . $cat_data['nombre']); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php if (is_user_logged_in()) : ?>
        <button class="es-btn es-btn--primary es-btn-abrir-modal" data-modal="modal-compartir">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Compartir objeto', 'flavor-chat-ia'); ?>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($recursos) : ?>
    <div class="es-biblioteca-grid">
        <?php foreach ($recursos as $recurso) :
            $cat_data = $categorias_objetos[$recurso->categoria] ?? ['nombre' => 'Otro', 'icono' => '📦'];
            $propietario = get_userdata($recurso->post_author);
            $es_propio = $recurso->post_author == $user_id;
        ?>
        <article class="es-recurso-card" data-categoria="<?php echo esc_attr($recurso->categoria); ?>">
            <div class="es-recurso-card__imagen">
                <?php if (has_post_thumbnail($recurso->ID)) : ?>
                    <?php echo get_the_post_thumbnail($recurso->ID, 'medium'); ?>
                <?php else : ?>
                    <span><?php echo esc_html($cat_data['icono']); ?></span>
                <?php endif; ?>
            </div>

            <div class="es-recurso-card__body">
                <h3 class="es-recurso-card__nombre"><?php echo esc_html($recurso->post_title); ?></h3>
                <p class="es-recurso-card__descripcion"><?php echo esc_html(wp_trim_words($recurso->post_content, 15)); ?></p>

                <div class="es-recurso-card__meta">
                    <div class="es-recurso-card__propietario">
                        <?php echo get_avatar($recurso->post_author, 28); ?>
                        <span><?php echo esc_html($propietario->display_name); ?></span>
                    </div>
                    <span class="es-estado-badge es-estado-badge--<?php echo esc_attr($recurso->estado ?: 'disponible'); ?>">
                        <?php echo $recurso->estado === 'prestado'
                            ? esc_html__('Prestado', 'flavor-chat-ia')
                            : esc_html__('Disponible', 'flavor-chat-ia'); ?>
                    </span>
                </div>

                <?php if (is_user_logged_in() && !$es_propio && $recurso->estado !== 'prestado') : ?>
                <button class="es-btn es-btn--primary es-btn--small es-btn-solicitar" data-recurso="<?php echo esc_attr($recurso->ID); ?>" style="width: 100%; margin-top: 1rem;">
                    <?php esc_html_e('Solicitar préstamo', 'flavor-chat-ia'); ?>
                </button>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="es-empty-state">
        <span class="dashicons dashicons-archive"></span>
        <p><?php esc_html_e('Aún no hay objetos en la biblioteca.', 'flavor-chat-ia'); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <p><?php esc_html_e('¡Sé el primero en compartir algo!', 'flavor-chat-ia'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal compartir objeto -->
<div id="modal-compartir" class="es-modal">
    <div class="es-modal__contenido">
        <div class="es-modal__header">
            <h3><?php esc_html_e('Compartir un objeto', 'flavor-chat-ia'); ?></h3>
            <button class="es-modal__cerrar">&times;</button>
        </div>
        <form class="es-modal__body es-form-recurso">
            <div class="es-form-grupo">
                <label for="recurso-nombre"><?php esc_html_e('¿Qué quieres compartir?', 'flavor-chat-ia'); ?> *</label>
                <input type="text" name="nombre" id="recurso-nombre" required
                       placeholder="<?php esc_attr_e('Ej: Taladro eléctrico', 'flavor-chat-ia'); ?>">
            </div>

            <div class="es-form-grupo">
                <label for="recurso-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                <select name="categoria" id="recurso-categoria">
                    <?php foreach ($categorias_objetos as $cat_id => $cat_data) : ?>
                    <option value="<?php echo esc_attr($cat_id); ?>">
                        <?php echo esc_html($cat_data['icono'] . ' ' . $cat_data['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="es-form-grupo">
                <label for="recurso-descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" id="recurso-descripcion" rows="3"
                          placeholder="<?php esc_attr_e('Estado, características, marca...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="es-form-grupo">
                <label for="recurso-condiciones"><?php esc_html_e('Condiciones de préstamo', 'flavor-chat-ia'); ?></label>
                <textarea name="condiciones" id="recurso-condiciones" rows="2"
                          placeholder="<?php esc_attr_e('Ej: Máximo 1 semana, recoger en mi casa...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="es-modal__footer">
                <button type="button" class="es-btn es-btn--secondary es-modal__cerrar">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" class="es-btn es-btn--primary">
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Compartir', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
