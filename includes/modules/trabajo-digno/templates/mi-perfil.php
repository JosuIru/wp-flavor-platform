<?php
/**
 * Template: Mi Perfil Profesional
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="td-empty-state"><p>' . esc_html__('Debes iniciar sesión para gestionar tu perfil.', 'flavor-chat-ia') . '</p></div>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$sectores = Flavor_Chat_Trabajo_Digno_Module::SECTORES;
$jornadas = Flavor_Chat_Trabajo_Digno_Module::JORNADAS;

// Obtener perfil existente
$perfil = get_posts([
    'post_type' => 'td_perfil',
    'author' => $user_id,
    'posts_per_page' => 1,
]);

$perfil_data = [];
if (!empty($perfil)) {
    $perfil = $perfil[0];
    $perfil_data = [
        'titulo' => $perfil->post_title,
        'descripcion' => $perfil->post_content,
        'experiencia' => get_post_meta($perfil->ID, '_td_experiencia', true),
        'formacion' => get_post_meta($perfil->ID, '_td_formacion', true),
        'disponibilidad' => get_post_meta($perfil->ID, '_td_disponibilidad', true),
        'sectores' => wp_get_post_terms($perfil->ID, 'td_sector', ['fields' => 'slugs']),
        'habilidades' => wp_get_post_terms($perfil->ID, 'td_habilidad', ['fields' => 'names']),
    ];
}

// Obtener mis postulaciones
$mis_postulaciones = [];
$ofertas_con_postulaciones = get_posts([
    'post_type' => 'td_oferta',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => '_td_postulaciones', 'compare' => 'EXISTS']
    ],
]);

foreach ($ofertas_con_postulaciones as $oferta) {
    $postulaciones = get_post_meta($oferta->ID, '_td_postulaciones', true) ?: [];
    foreach ($postulaciones as $p) {
        if ($p['user_id'] == $user_id) {
            $mis_postulaciones[] = [
                'oferta_id' => $oferta->ID,
                'titulo' => $oferta->post_title,
                'fecha' => $p['fecha'],
                'estado' => $p['estado'],
            ];
            break;
        }
    }
}
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Mi Perfil Profesional', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Gestiona tu información y consulta tus postulaciones', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Tabs -->
    <div class="td-tabs">
        <button class="td-tab activo" data-tab="tab-perfil">
            <span class="dashicons dashicons-id-alt"></span>
            <?php esc_html_e('Mi Perfil', 'flavor-chat-ia'); ?>
        </button>
        <button class="td-tab" data-tab="tab-postulaciones">
            <span class="dashicons dashicons-portfolio"></span>
            <?php esc_html_e('Mis Postulaciones', 'flavor-chat-ia'); ?>
            <?php if (count($mis_postulaciones) > 0) : ?>
            <span style="background: var(--td-primary); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem; margin-left: 4px;">
                <?php echo esc_html(count($mis_postulaciones)); ?>
            </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab: Perfil -->
    <div id="tab-perfil" class="td-tab-contenido">
        <div class="td-perfil-section">
            <div class="td-perfil-header">
                <div class="td-perfil-avatar">
                    <?php echo get_avatar($user_id, 80); ?>
                </div>
                <div class="td-perfil-info">
                    <h3><?php echo esc_html($user->display_name); ?></h3>
                    <p><?php echo esc_html($user->user_email); ?></p>
                </div>
            </div>

            <form class="td-form td-form-perfil" style="box-shadow: none; padding: 0;">
                <div class="td-form-grupo">
                    <label for="td-perfil-titulo"><?php esc_html_e('Título profesional', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="titulo" id="td-perfil-titulo"
                           value="<?php echo esc_attr($perfil_data['titulo'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Ej: Desarrollador/a web, Técnico/a agrícola...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="td-form-grupo">
                    <label for="td-perfil-descripcion"><?php esc_html_e('Sobre mí', 'flavor-chat-ia'); ?></label>
                    <textarea name="descripcion" id="td-perfil-descripcion" rows="4"
                              placeholder="<?php esc_attr_e('Breve descripción profesional...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($perfil_data['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="td-form-row">
                    <div class="td-form-grupo">
                        <label for="td-perfil-experiencia"><?php esc_html_e('Experiencia', 'flavor-chat-ia'); ?></label>
                        <textarea name="experiencia" id="td-perfil-experiencia" rows="4"
                                  placeholder="<?php esc_attr_e('Detalla tu experiencia laboral...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($perfil_data['experiencia'] ?? ''); ?></textarea>
                    </div>
                    <div class="td-form-grupo">
                        <label for="td-perfil-formacion"><?php esc_html_e('Formación', 'flavor-chat-ia'); ?></label>
                        <textarea name="formacion" id="td-perfil-formacion" rows="4"
                                  placeholder="<?php esc_attr_e('Estudios, cursos, certificaciones...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($perfil_data['formacion'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="td-form-grupo">
                    <label for="td-perfil-disponibilidad"><?php esc_html_e('Disponibilidad', 'flavor-chat-ia'); ?></label>
                    <select name="disponibilidad" id="td-perfil-disponibilidad">
                        <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($jornadas as $jornada_id => $jornada_nombre) : ?>
                        <option value="<?php echo esc_attr($jornada_id); ?>" <?php selected($perfil_data['disponibilidad'] ?? '', $jornada_id); ?>>
                            <?php echo esc_html($jornada_nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="td-btn td-btn--primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Guardar Perfil', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab: Postulaciones -->
    <div id="tab-postulaciones" class="td-tab-contenido" style="display: none;">
        <?php if (!empty($mis_postulaciones)) : ?>
        <div class="td-postulaciones-lista">
            <?php foreach ($mis_postulaciones as $postulacion) : ?>
            <div class="td-postulacion-item">
                <div class="td-postulacion-item__info">
                    <h4><?php echo esc_html($postulacion['titulo']); ?></h4>
                    <span style="color: var(--td-text-light); font-size: 0.85rem;">
                        <?php echo esc_html(date_i18n('j M Y', strtotime($postulacion['fecha']))); ?>
                    </span>
                </div>
                <span class="td-postulacion-item__estado td-postulacion-item__estado--<?php echo esc_attr($postulacion['estado']); ?>">
                    <?php
                    $estados_labels = [
                        'pendiente' => __('Pendiente', 'flavor-chat-ia'),
                        'aceptada' => __('Aceptada', 'flavor-chat-ia'),
                        'rechazada' => __('Rechazada', 'flavor-chat-ia'),
                    ];
                    echo esc_html($estados_labels[$postulacion['estado']] ?? $postulacion['estado']);
                    ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="td-empty-state">
            <span class="dashicons dashicons-portfolio"></span>
            <p><?php esc_html_e('Aún no has postulado a ninguna oferta.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/trabajo-digno/')); ?>" class="td-btn td-btn--primary">
                <?php esc_html_e('Ver ofertas disponibles', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
