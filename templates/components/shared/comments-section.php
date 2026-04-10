<?php
/**
 * Componente: Comments Section
 *
 * Sección de comentarios unificada para cualquier tipo de contenido.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $item_type    Tipo de item (incidencia, evento, propuesta, etc.)
 * @param int    $item_id      ID del item
 * @param array  $comments     Comentarios existentes
 * @param int    $total        Total de comentarios
 * @param bool   $allow_reply  Permitir respuestas anidadas
 * @param bool   $allow_votes  Permitir votar comentarios
 * @param int    $max_depth    Máxima profundidad de respuestas
 * @param string $order        Orden: newest, oldest, popular
 * @param string $color        Color del tema
 */

if (!defined('ABSPATH')) {
    exit;
}

$item_type = $item_type ?? 'post';
$item_id = intval($item_id ?? 0);
$comments = $comments ?? [];
$total = intval($total ?? count($comments));
$allow_reply = $allow_reply ?? true;
$allow_votes = $allow_votes ?? false;
$max_depth = intval($max_depth ?? 3);
$order = $order ?? 'newest';
$color = $color ?? 'blue';

$section_id = 'comments-' . $item_id . '-' . wp_rand(100, 999);
$current_user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'bg_solid' => 'bg-blue-500'];
}
?>

<div class="flavor-comments-section" id="<?php echo esc_attr($section_id); ?>" data-item-type="<?php echo esc_attr($item_type); ?>" data-item-id="<?php echo esc_attr($item_id); ?>">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <?php printf(esc_html(_n('%d Comentario', '%d Comentarios', $total, FLAVOR_PLATFORM_TEXT_DOMAIN)), $total); ?>
        </h3>

        <!-- Orden -->
        <select class="flavor-comments-order text-sm border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="newest" <?php selected($order, 'newest'); ?>><?php esc_html_e('Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="oldest" <?php selected($order, 'oldest'); ?>><?php esc_html_e('Más antiguos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php if ($allow_votes): ?>
                <option value="popular" <?php selected($order, 'popular'); ?>><?php esc_html_e('Más votados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php endif; ?>
        </select>
    </div>

    <!-- Formulario de nuevo comentario -->
    <?php if ($is_logged_in): ?>
        <div class="flavor-comment-form bg-gray-50 rounded-xl p-4 mb-6">
            <div class="flex gap-3">
                <?php echo get_avatar($current_user_id, 40, '', '', ['class' => 'w-10 h-10 rounded-full flex-shrink-0']); ?>
                <div class="flex-1">
                    <textarea class="flavor-comment-input w-full px-4 py-3 border border-gray-200 rounded-xl resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              rows="2"
                              placeholder="<?php esc_attr_e('Escribe un comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-xs text-gray-500">
                            <?php esc_html_e('Sé respetuoso con los demás usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <button type="button" class="flavor-submit-comment px-4 py-2 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                                disabled>
                            <?php esc_html_e('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-center">
            <p class="text-gray-600 mb-2"><?php esc_html_e('Inicia sesión para comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-lg hover:opacity-90">
                <?php esc_html_e('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Lista de comentarios -->
    <div class="flavor-comments-list space-y-4">
        <?php if (empty($comments)): ?>
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p><?php esc_html_e('Sé el primero en comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php
                $this_comment = $comment;
                $depth = 0;
                include __DIR__ . '/comments-section.php'; // Recursive render handled below
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Cargar más -->
    <?php if ($total > count($comments)): ?>
        <div class="text-center mt-6">
            <button type="button" class="flavor-load-more-comments px-6 py-2 text-sm font-medium <?php echo esc_attr($color_classes['text']); ?> border border-current rounded-lg hover:bg-gray-50 transition-colors">
                <?php esc_html_e('Cargar más comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Template de comentario individual -->
<template id="<?php echo esc_attr($section_id); ?>-template">
    <div class="flavor-comment group" data-comment-id="">
        <div class="flex gap-3">
            <div class="flex-shrink-0">
                <img src="" alt="" class="w-10 h-10 rounded-full comment-avatar">
            </div>
            <div class="flex-1 min-w-0">
                <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 comment-author"></span>
                            <span class="text-xs text-gray-500 comment-date"></span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <?php if ($allow_votes): ?>
                                <button type="button" class="comment-vote-up p-1 text-gray-400 hover:text-green-500 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                </button>
                                <span class="text-xs font-medium text-gray-500 comment-votes">0</span>
                                <button type="button" class="comment-vote-down p-1 text-gray-400 hover:text-red-500 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-gray-700 text-sm comment-content"></p>
                </div>

                <!-- Acciones -->
                <div class="flex items-center gap-4 mt-2 ml-2">
                    <?php if ($allow_reply && $is_logged_in): ?>
                        <button type="button" class="comment-reply text-xs text-gray-500 hover:text-gray-700">
                            <?php esc_html_e('Responder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="comment-report text-xs text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                        <?php esc_html_e('Reportar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <!-- Respuestas -->
                <div class="comment-replies mt-3 ml-4 border-l-2 border-gray-100 pl-4 space-y-3"></div>
            </div>
        </div>
    </div>
</template>

<script>
(function() {
    const sectionId = '<?php echo esc_js($section_id); ?>';
    const container = document.getElementById(sectionId);
    if (!container) return;

    const itemType = container.dataset.itemType;
    const itemId = container.dataset.itemId;
    const commentsList = container.querySelector('.flavor-comments-list');
    const template = document.getElementById(sectionId + '-template');
    const textarea = container.querySelector('.flavor-comment-input');
    const submitBtn = container.querySelector('.flavor-submit-comment');
    const orderSelect = container.querySelector('.flavor-comments-order');
    const loadMoreBtn = container.querySelector('.flavor-load-more-comments');

    let offset = <?php echo count($comments); ?>;
    const limit = 10;

    // Habilitar/deshabilitar botón de enviar
    if (textarea) {
        textarea.addEventListener('input', function() {
            submitBtn.disabled = this.value.trim().length === 0;
        });
    }

    // Enviar comentario
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            const content = textarea.value.trim();
            if (!content) return;

            submitBtn.disabled = true;
            submitBtn.textContent = '<?php esc_html_e('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';

            fetch(flavorAjax.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_add_comment',
                    item_type: itemType,
                    item_id: itemId,
                    content: content,
                    _wpnonce: flavorAjax.nonce
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    submitBtn.disabled = true;

                    // Añadir comentario al inicio
                    const emptyMsg = commentsList.querySelector('.text-center');
                    if (emptyMsg) emptyMsg.remove();

                    const newComment = createCommentElement(data.data);
                    commentsList.insertBefore(newComment, commentsList.firstChild);

                    // Actualizar contador
                    const header = container.querySelector('h3');
                    const currentCount = parseInt(header.textContent.match(/\d+/)[0]) + 1;
                    header.innerHTML = header.innerHTML.replace(/\d+/, currentCount);
                }
                submitBtn.textContent = '<?php esc_html_e('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php esc_html_e('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
            });
        });
    }

    // Crear elemento de comentario
    function createCommentElement(data) {
        const clone = template.content.cloneNode(true);
        const el = clone.querySelector('.flavor-comment');

        el.dataset.commentId = data.id;
        el.querySelector('.comment-avatar').src = data.avatar;
        el.querySelector('.comment-author').textContent = data.author;
        el.querySelector('.comment-date').textContent = data.date;
        el.querySelector('.comment-content').textContent = data.content;

        if (el.querySelector('.comment-votes')) {
            el.querySelector('.comment-votes').textContent = data.votes || 0;
        }

        bindCommentActions(el);
        return el;
    }

    // Vincular acciones de comentario
    function bindCommentActions(el) {
        const replyBtn = el.querySelector('.comment-reply');
        const voteUpBtn = el.querySelector('.comment-vote-up');
        const voteDownBtn = el.querySelector('.comment-vote-down');
        const reportBtn = el.querySelector('.comment-report');

        if (replyBtn) {
            replyBtn.addEventListener('click', function() {
                // Implementar formulario de respuesta inline
                const repliesContainer = el.querySelector('.comment-replies');
                if (repliesContainer.querySelector('.reply-form')) return;

                const replyForm = document.createElement('div');
                replyForm.className = 'reply-form flex gap-2 mt-2';
                replyForm.innerHTML = `
                    <input type="text" class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="<?php esc_attr_e('Escribe una respuesta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <button type="button" class="px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        <?php esc_html_e('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                `;

                repliesContainer.insertBefore(replyForm, repliesContainer.firstChild);

                const input = replyForm.querySelector('input');
                const sendBtn = replyForm.querySelectorAll('button')[0];
                const cancelBtn = replyForm.querySelectorAll('button')[1];

                input.focus();

                cancelBtn.addEventListener('click', () => replyForm.remove());

                sendBtn.addEventListener('click', function() {
                    const content = input.value.trim();
                    if (!content) return;

                    fetch(flavorAjax.url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'flavor_add_comment',
                            item_type: itemType,
                            item_id: itemId,
                            parent_id: el.dataset.commentId,
                            content: content,
                            _wpnonce: flavorAjax.nonce
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            replyForm.remove();
                            const reply = createCommentElement(data.data);
                            repliesContainer.appendChild(reply);
                        }
                    });
                });
            });
        }

        if (voteUpBtn) {
            voteUpBtn.addEventListener('click', () => voteComment(el.dataset.commentId, 1, el));
        }
        if (voteDownBtn) {
            voteDownBtn.addEventListener('click', () => voteComment(el.dataset.commentId, -1, el));
        }
    }

    // Votar comentario
    function voteComment(commentId, value, el) {
        fetch(flavorAjax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'flavor_vote_comment',
                comment_id: commentId,
                value: value,
                _wpnonce: flavorAjax.nonce
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                el.querySelector('.comment-votes').textContent = data.data.votes;
            }
        });
    }

    // Cambiar orden
    if (orderSelect) {
        orderSelect.addEventListener('change', function() {
            window.location.href = updateQueryParam(window.location.href, 'comment_order', this.value);
        });
    }

    // Cargar más
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            this.disabled = true;
            this.textContent = '<?php esc_html_e('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';

            fetch(flavorAjax.url + '?' + new URLSearchParams({
                action: 'flavor_get_comments',
                item_type: itemType,
                item_id: itemId,
                offset: offset,
                limit: limit,
                order: orderSelect ? orderSelect.value : 'newest',
                _wpnonce: flavorAjax.nonce
            }))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.comments) {
                    data.data.comments.forEach(c => {
                        commentsList.appendChild(createCommentElement(c));
                    });
                    offset += data.data.comments.length;

                    if (!data.data.has_more) {
                        loadMoreBtn.remove();
                    } else {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = '<?php esc_html_e('Cargar más comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
                    }
                }
            });
        });
    }

    // Bind existing comments
    commentsList.querySelectorAll('.flavor-comment').forEach(bindCommentActions);

    function updateQueryParam(url, param, value) {
        const urlObj = new URL(url);
        urlObj.searchParams.set(param, value);
        return urlObj.toString();
    }
})();
</script>
