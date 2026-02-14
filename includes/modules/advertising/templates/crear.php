<?php
/**
 * Template: Crear Anuncio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si se accede directamente, proporcionar contexto
if (!isset($base_url)) {
    $base_url = remove_query_arg(['vista', 'ad_id']);
}

$configuracion = get_option('flavor_advertising_settings', []);
$reparto_default = $configuracion['reparto_comunidad_default'] ?? 30;
?>

<div class="flavor-ads-crear">
    <div class="ads-crear-header">
        <a href="<?php echo esc_url($base_url); ?>" class="ads-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e('Volver al dashboard', 'flavor-chat-ia'); ?>
        </a>
        <h2><?php esc_html_e('Crear nuevo anuncio', 'flavor-chat-ia'); ?></h2>
        <p class="ads-crear-desc"><?php esc_html_e('Tu anuncio será revisado antes de publicarse. Una vez aprobado, comenzará a mostrarse automáticamente.', 'flavor-chat-ia'); ?></p>
    </div>

    <form class="ads-form" id="crear-anuncio-form">
        <?php wp_nonce_field('flavor_ads_nonce', 'nonce'); ?>

        <div class="ads-form-section">
            <h3><?php esc_html_e('Información básica', 'flavor-chat-ia'); ?></h3>

            <div class="form-grupo">
                <label for="anuncio-titulo">
                    <?php esc_html_e('Título del anuncio', 'flavor-chat-ia'); ?> <span class="required">*</span>
                </label>
                <input type="text" id="anuncio-titulo" name="titulo" required
                       placeholder="<?php esc_attr_e('Ej: Promoción de verano', 'flavor-chat-ia'); ?>">
                <p class="form-help"><?php esc_html_e('Este título se usará para identificar tu anuncio internamente.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="form-row">
                <div class="form-grupo">
                    <label for="anuncio-tipo"><?php esc_html_e('Tipo de anuncio', 'flavor-chat-ia'); ?></label>
                    <select id="anuncio-tipo" name="tipo">
                        <option value="banner_horizontal"><?php esc_html_e('Banner Horizontal (728x90)', 'flavor-chat-ia'); ?></option>
                        <option value="banner_sidebar"><?php esc_html_e('Banner Sidebar (300x250)', 'flavor-chat-ia'); ?></option>
                        <option value="banner_card"><?php esc_html_e('Tarjeta con imagen', 'flavor-chat-ia'); ?></option>
                        <option value="banner_nativo"><?php esc_html_e('Anuncio nativo', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="ads-form-section">
            <h3><?php esc_html_e('Contenido del anuncio', 'flavor-chat-ia'); ?></h3>

            <div class="form-grupo">
                <label for="anuncio-url">
                    <?php esc_html_e('URL de destino', 'flavor-chat-ia'); ?> <span class="required">*</span>
                </label>
                <input type="url" id="anuncio-url" name="url_destino" required
                       placeholder="https://tu-sitio.com/landing-page">
                <p class="form-help"><?php esc_html_e('La página donde llegarán los usuarios al hacer clic.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="form-grupo">
                <label for="anuncio-imagen"><?php esc_html_e('Imagen del anuncio', 'flavor-chat-ia'); ?></label>
                <div class="ads-upload-wrapper">
                    <input type="url" id="anuncio-imagen" name="imagen"
                           placeholder="<?php esc_attr_e('URL de la imagen', 'flavor-chat-ia'); ?>">
                    <button type="button" class="btn btn-sm btn-outline ads-upload-btn" id="upload-imagen-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <p class="form-help"><?php esc_html_e('Tamaños recomendados: 728x90 (horizontal), 300x250 (sidebar), 350x200 (tarjeta).', 'flavor-chat-ia'); ?></p>
                <div id="imagen-preview" class="ads-imagen-preview" style="display: none;">
                    <img src="" alt="Preview">
                </div>
            </div>

            <div class="form-grupo">
                <label for="anuncio-cta"><?php esc_html_e('Texto del botón (CTA)', 'flavor-chat-ia'); ?></label>
                <input type="text" id="anuncio-cta" name="texto_cta"
                       placeholder="<?php esc_attr_e('Ej: Descubrir más', 'flavor-chat-ia'); ?>"
                       value="<?php esc_attr_e('Saber más', 'flavor-chat-ia'); ?>">
            </div>
        </div>

        <div class="ads-form-section">
            <h3><?php esc_html_e('Presupuesto y duración', 'flavor-chat-ia'); ?></h3>

            <div class="form-row">
                <div class="form-grupo">
                    <label for="anuncio-presupuesto"><?php esc_html_e('Presupuesto total (€)', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="anuncio-presupuesto" name="presupuesto"
                           min="5" step="0.01" value="50">
                    <p class="form-help"><?php esc_html_e('Tu anuncio se pausará al agotar el presupuesto.', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="form-grupo">
                    <label for="anuncio-fecha-inicio"><?php esc_html_e('Fecha de inicio', 'flavor-chat-ia'); ?></label>
                    <input type="date" id="anuncio-fecha-inicio" name="fecha_inicio"
                           value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>

                <div class="form-grupo">
                    <label for="anuncio-fecha-fin"><?php esc_html_e('Fecha de fin (opcional)', 'flavor-chat-ia'); ?></label>
                    <input type="date" id="anuncio-fecha-fin" name="fecha_fin">
                </div>
            </div>
        </div>

        <div class="ads-form-section ads-section-info">
            <h3><?php esc_html_e('Reparto comunitario', 'flavor-chat-ia'); ?></h3>
            <div class="ads-reparto-info">
                <span class="dashicons dashicons-groups"></span>
                <div>
                    <p>
                        <?php printf(
                            esc_html__('El %d%% de los ingresos de este anuncio se repartirán con la comunidad.', 'flavor-chat-ia'),
                            $reparto_default
                        ); ?>
                    </p>
                    <p class="ads-reparto-desc">
                        <?php esc_html_e('Este sistema permite que los miembros activos de la comunidad reciban parte de los beneficios publicitarios.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="ads-form-actions">
            <a href="<?php echo esc_url($base_url); ?>" class="btn btn-outline">
                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('Enviar para revisión', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.ads-crear-header {
    margin-bottom: 2rem;
}

.ads-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--ads-gray-500);
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.ads-back-link:hover {
    color: var(--ads-primary);
}

.ads-crear-desc {
    color: var(--ads-gray-500);
    margin-top: 0.5rem;
}

.ads-form-section {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--ads-shadow);
}

.ads-form-section h3 {
    margin: 0 0 1.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--ads-gray-100);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-grupo {
    margin-bottom: 1.25rem;
}

.form-grupo:last-child {
    margin-bottom: 0;
}

.form-grupo label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--ads-gray-700);
}

.form-grupo .required {
    color: var(--ads-danger);
}

.form-help {
    font-size: 0.8rem;
    color: var(--ads-gray-400);
    margin-top: 0.35rem;
}

.ads-upload-wrapper {
    display: flex;
    gap: 0.5rem;
}

.ads-upload-wrapper input {
    flex: 1;
}

.ads-imagen-preview {
    margin-top: 1rem;
    border: 1px solid var(--ads-gray-200);
    border-radius: 8px;
    overflow: hidden;
    max-width: 400px;
}

.ads-imagen-preview img {
    width: 100%;
    height: auto;
    display: block;
}

.ads-section-info {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(99, 102, 241, 0.1));
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.ads-reparto-info {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.ads-reparto-info .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: var(--ads-primary);
    flex-shrink: 0;
}

.ads-reparto-info p {
    margin: 0;
}

.ads-reparto-desc {
    font-size: 0.875rem;
    color: var(--ads-gray-500);
    margin-top: 0.25rem !important;
}

.ads-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.ads-form-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .flavor-ads-crear {
        padding: 1rem;
    }

    .ads-form-actions {
        flex-direction: column;
    }

    .ads-form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Preview de imagen
    $('#anuncio-imagen').on('input', function() {
        const url = $(this).val();
        if (url && url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
            $('#imagen-preview').show().find('img').attr('src', url);
        } else {
            $('#imagen-preview').hide();
        }
    });

    // Media uploader (si está disponible)
    $('#upload-imagen-btn').on('click', function(e) {
        e.preventDefault();

        if (typeof wp !== 'undefined' && wp.media) {
            const frame = wp.media({
                title: '<?php esc_html_e('Seleccionar imagen', 'flavor-chat-ia'); ?>',
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $('#anuncio-imagen').val(attachment.url).trigger('input');
            });

            frame.open();
        } else {
            alert('<?php esc_html_e('Por favor, introduce la URL de la imagen manualmente.', 'flavor-chat-ia'); ?>');
        }
    });
});
</script>
