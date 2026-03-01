<?php
/**
 * Componente: Chat Bubble
 *
 * Burbuja de chat/mensaje para conversaciones.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $message    Contenido del mensaje
 * @param string $sender     Nombre del remitente
 * @param string $avatar     URL del avatar
 * @param string $time       Hora del mensaje
 * @param bool   $is_own     Es mensaje propio (alineado a la derecha)
 * @param string $status     Estado: sent, delivered, read
 * @param array  $attachments Archivos adjuntos
 * @param bool   $show_avatar Mostrar avatar
 * @param string $variant    Variante: default, minimal, rounded
 */

if (!defined('ABSPATH')) {
    exit;
}

$message = $message ?? '';
$sender = $sender ?? '';
$avatar = $avatar ?? '';
$time = $time ?? '';
$is_own = $is_own ?? false;
$status = $status ?? '';
$attachments = $attachments ?? [];
$show_avatar = $show_avatar ?? true;
$variant = $variant ?? 'default';

// Colores según si es propio o no
$bubble_class = $is_own
    ? 'bg-blue-600 text-white rounded-br-none'
    : 'bg-gray-100 text-gray-900 rounded-bl-none';

$time_class = $is_own ? 'text-blue-200' : 'text-gray-400';

// Estado del mensaje
$status_icons = [
    'sent'      => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    'delivered' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>',
    'read'      => '<svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>',
];
?>

<div class="flavor-chat-bubble flex gap-2 <?php echo $is_own ? 'flex-row-reverse' : ''; ?> mb-3">
    <?php if ($show_avatar && !$is_own): ?>
        <?php if ($avatar): ?>
            <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($sender); ?>" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-1">
        <?php else: ?>
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-sm font-medium flex-shrink-0 mt-1">
                <?php echo esc_html(mb_substr($sender, 0, 1)); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="max-w-[75%] <?php echo $is_own ? 'items-end' : 'items-start'; ?>">
        <?php if (!$is_own && $sender): ?>
            <p class="text-xs font-medium text-gray-600 mb-1 px-1"><?php echo esc_html($sender); ?></p>
        <?php endif; ?>

        <div class="<?php echo esc_attr($bubble_class); ?> rounded-2xl px-4 py-2 shadow-sm">
            <!-- Mensaje -->
            <p class="text-sm whitespace-pre-wrap break-words"><?php echo nl2br(esc_html($message)); ?></p>

            <!-- Adjuntos -->
            <?php if (!empty($attachments)): ?>
                <div class="mt-2 space-y-1">
                    <?php foreach ($attachments as $file): ?>
                        <?php
                        $is_image = in_array(strtolower(pathinfo($file['url'] ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        ?>
                        <?php if ($is_image): ?>
                            <img src="<?php echo esc_url($file['url']); ?>" alt="" class="max-w-full rounded-lg cursor-pointer hover:opacity-90">
                        <?php else: ?>
                            <a href="<?php echo esc_url($file['url']); ?>" target="_blank" class="flex items-center gap-2 text-xs <?php echo $is_own ? 'text-blue-100 hover:text-white' : 'text-blue-600 hover:text-blue-700'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <?php echo esc_html($file['name'] ?? basename($file['url'])); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Hora y estado -->
            <div class="flex items-center justify-end gap-1 mt-1 -mb-1">
                <?php if ($time): ?>
                    <span class="text-[10px] <?php echo esc_attr($time_class); ?>"><?php echo esc_html($time); ?></span>
                <?php endif; ?>
                <?php if ($is_own && $status && isset($status_icons[$status])): ?>
                    <span class="<?php echo esc_attr($time_class); ?>"><?php echo $status_icons[$status]; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
