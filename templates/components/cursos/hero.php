<?php
/**
 * Template: Hero Cursos
 * @package FlavorChatIA
 */

$imagen_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>

        <!-- Pattern Overlay -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Cursos Comunitarios'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Aprende y comparte conocimientos'); ?></p>

            <?php if (!empty($mostrar_buscador)): ?>
            <div class="flavor-card max-w-3xl mx-auto">
                <form class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('¿Qué quieres aprender?', 'flavor-chat-ia'); ?></label>
                            <input type="text" placeholder="<?php echo esc_attr__('Buscar cursos...', 'flavor-chat-ia'); ?>" class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" style="color: var(--flavor-text);"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></label>
                            <select class="w-full px-4 py-3 border rounded-lg" style="border-color: #e5e7eb;">
                                <option><?php echo esc_html__('Todas las categorías', 'flavor-chat-ia'); ?></option>
                                <option><?php echo esc_html__('Tecnología', 'flavor-chat-ia'); ?></option>
                                <option><?php echo esc_html__('Idiomas', 'flavor-chat-ia'); ?></option>
                                <option><?php echo esc_html__('Arte y Creatividad', 'flavor-chat-ia'); ?></option>
                                <option><?php echo esc_html__('Cocina', 'flavor-chat-ia'); ?></option>
                                <option><?php echo esc_html__('Salud y Bienestar', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="flavor-button flavor-button-primary w-full md:w-auto px-12"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></button>
                </form>
                <div class="grid grid-cols-3 gap-6 mt-8 pt-8 border-t" style="border-color: #e5e7eb;">
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">150+</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Cursos', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);"><?php echo esc_html__('2.5K+', 'flavor-chat-ia'); ?></div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Estudiantes', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold" style="color: var(--flavor-primary);">85+</div>
                        <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Instructores', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
