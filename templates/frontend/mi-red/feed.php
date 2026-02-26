<?php
/**
 * Feed Principal - Mi Red Social
 *
 * Muestra el feed unificado con contenido de todos los módulos sociales.
 *
 * Variables disponibles:
 * - $datos_vista['feed']: array de items del feed
 * - $datos_vista['trending']: array de hashtags trending
 * - $datos_vista['sugerencias']: array de usuarios sugeridos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$feed = $datos_vista['feed'] ?? [];
?>

<div class="mi-red-feed">
    <!-- Composer (crear publicación rápida) -->
    <div class="mi-red-composer">
        <div class="mi-red-composer__avatar">
            <img src="<?php echo esc_url($usuario['avatar']); ?>" alt="">
        </div>
        <div class="mi-red-composer__input-wrapper">
            <button class="mi-red-composer__trigger" id="abrir-composer">
                <?php esc_html_e('¿Qué quieres compartir?', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <div class="mi-red-composer__actions">
            <button class="mi-red-composer__action" data-tipo="imagen" title="<?php esc_attr_e('Imagen', 'flavor-chat-ia'); ?>">
                📷
            </button>
            <button class="mi-red-composer__action" data-tipo="video" title="<?php esc_attr_e('Video', 'flavor-chat-ia'); ?>">
                🎬
            </button>
            <button class="mi-red-composer__action" data-tipo="enlace" title="<?php esc_attr_e('Enlace', 'flavor-chat-ia'); ?>">
                🔗
            </button>
        </div>
    </div>

    <!-- Formulario de publicación expandido -->
    <div class="mi-red-composer-expanded" id="composer-expandido" hidden>
        <form class="mi-red-composer-form" id="form-publicar">
            <div class="mi-red-composer-form__header">
                <img src="<?php echo esc_url($usuario['avatar']); ?>" alt="" class="mi-red-composer-form__avatar">
                <div class="mi-red-composer-form__meta">
                    <span class="mi-red-composer-form__nombre"><?php echo esc_html($usuario['nombre']); ?></span>
                    <select name="visibilidad" class="mi-red-composer-form__visibilidad">
                        <option value="comunidad"><?php esc_html_e('Comunidad', 'flavor-chat-ia'); ?></option>
                        <option value="publica"><?php esc_html_e('Público', 'flavor-chat-ia'); ?></option>
                        <option value="seguidores"><?php esc_html_e('Solo seguidores', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <button type="button" class="mi-red-composer-form__cerrar" id="cerrar-composer">✕</button>
            </div>

            <textarea name="contenido"
                      class="mi-red-composer-form__textarea"
                      placeholder="<?php esc_attr_e('¿Qué está pasando?', 'flavor-chat-ia'); ?>"
                      rows="3"
                      required></textarea>

            <div class="mi-red-composer-form__preview" id="preview-adjuntos" hidden></div>

            <div class="mi-red-composer-form__footer">
                <div class="mi-red-composer-form__tools">
                    <label class="mi-red-composer-tool" title="<?php esc_attr_e('Subir imagen', 'flavor-chat-ia'); ?>">
                        <input type="file" name="imagen" accept="image/*" hidden>
                        <span>📷</span>
                    </label>
                    <label class="mi-red-composer-tool" title="<?php esc_attr_e('Subir video', 'flavor-chat-ia'); ?>">
                        <input type="file" name="video" accept="video/*" hidden>
                        <span>🎬</span>
                    </label>
                    <button type="button" class="mi-red-composer-tool" data-accion="emoji" title="<?php esc_attr_e('Emojis', 'flavor-chat-ia'); ?>">
                        😊
                    </button>
                    <button type="button" class="mi-red-composer-tool" data-accion="hashtag" title="<?php esc_attr_e('Hashtag', 'flavor-chat-ia'); ?>">
                        #
                    </button>
                    <button type="button" class="mi-red-composer-tool" data-accion="mencion" title="<?php esc_attr_e('Mencionar', 'flavor-chat-ia'); ?>">
                        @
                    </button>
                </div>
                <button type="submit" class="mi-red-btn mi-red-btn--primary" id="btn-publicar">
                    <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Lista del feed -->
    <div class="mi-red-feed__list" id="feed-lista">
        <?php if (empty($feed)) : ?>
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">📭</div>
                <h3 class="mi-red-empty-state__title"><?php esc_html_e('Tu feed está vacío', 'flavor-chat-ia'); ?></h3>
                <p class="mi-red-empty-state__text">
                    <?php esc_html_e('Sigue a otros usuarios, únete a comunidades o crea tu primera publicación para empezar a ver contenido aquí.', 'flavor-chat-ia'); ?>
                </p>
                <div class="mi-red-empty-state__actions">
                    <a href="<?php echo esc_url($base_url . 'explorar/'); ?>" class="mi-red-btn mi-red-btn--secondary">
                        <?php esc_html_e('Explorar', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url($base_url . 'publicar/'); ?>" class="mi-red-btn mi-red-btn--primary">
                        <?php esc_html_e('Crear publicación', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <?php
            foreach ($feed as $item) :
                include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/partials/feed-item.php';
            endforeach;
            ?>
        <?php endif; ?>
    </div>

    <!-- Loader para scroll infinito -->
    <div class="mi-red-loader" id="feed-loader" hidden>
        <div class="mi-red-loader__spinner"></div>
        <span class="mi-red-loader__text"><?php esc_html_e('Cargando más...', 'flavor-chat-ia'); ?></span>
    </div>

    <!-- Mensaje fin del feed -->
    <div class="mi-red-feed__end" id="feed-fin" hidden>
        <p><?php esc_html_e('Has llegado al final del feed', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url($base_url . 'explorar/'); ?>" class="mi-red-btn mi-red-btn--outline">
            <?php esc_html_e('Explorar más contenido', 'flavor-chat-ia'); ?>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del feed
    if (typeof MiRedSocial !== 'undefined') {
        MiRedSocial.initFeed();
    }
});
</script>
