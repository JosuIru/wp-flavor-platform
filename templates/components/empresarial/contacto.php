<?php
/**
 * Template: Formulario de Contacto
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Contacta con Nosotros';
$descripcion_seccion = $descripcion_seccion ?? 'Estamos aquí para ayudarte. Envíanos tu consulta y te responderemos pronto.';
$layout = $layout ?? 'dos_columnas';
$email_destino = $email_destino ?? get_option('admin_email');
$mostrar_telefono = $mostrar_telefono ?? true;
$telefono = $telefono ?? '+34 900 000 000';
$mostrar_direccion = $mostrar_direccion ?? true;
$direccion = $direccion ?? 'Calle Principal 123, 28001 Madrid, España';
?>

<section class="flavor-component flavor-section" style="background: var(--flavor-background, #ffffff);">
    <div class="flavor-container">
        <!-- Encabezado de sección -->
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <?php if (!empty($descripcion_seccion)): ?>
                <p class="text-xl" style="color: var(--flavor-text-secondary, #666666);">
                    <?php echo esc_html($descripcion_seccion); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($layout === 'dos_columnas'): ?>
            <!-- Layout Dos Columnas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <!-- Información de contacto -->
                <div class="space-y-8">
                    <div>
                        <h3 class="text-2xl font-bold mb-6" style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html__('Información de Contacto', 'flavor-chat-ia'); ?>
                        </h3>
                        <p class="text-lg mb-8" style="color: var(--flavor-text-secondary, #666666);">
                            <?php echo esc_html__('Estamos disponibles para responder tus preguntas. No dudes en ponerte en contacto con nosotros.', 'flavor-chat-ia'); ?>
                        </p>
                    </div>

                    <!-- Métodos de contacto -->
                    <div class="space-y-6">
                        <?php if ($mostrar_telefono): ?>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                                     style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-lg mb-1" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html__('Teléfono', 'flavor-chat-ia'); ?>
                                    </div>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $telefono)); ?>"
                                       class="text-lg hover:underline" style="color: var(--flavor-primary, #667eea);">
                                        <?php echo esc_html($telefono); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-lg mb-1" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html__('Email', 'flavor-chat-ia'); ?>
                                </div>
                                <a href="mailto:<?php echo esc_attr($email_destino); ?>"
                                   class="text-lg hover:underline" style="color: var(--flavor-primary, #667eea);">
                                    <?php echo esc_html($email_destino); ?>
                                </a>
                            </div>
                        </div>

                        <?php if ($mostrar_direccion): ?>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                                     style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-lg mb-1" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html__('Dirección', 'flavor-chat-ia'); ?>
                                    </div>
                                    <p class="text-lg" style="color: var(--flavor-text-secondary, #666666);">
                                        <?php echo esc_html($direccion); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Redes sociales -->
                    <div class="pt-8 border-t" style="border-color: rgba(0,0,0,0.1);">
                        <p class="font-semibold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html__('Síguenos en Redes Sociales', 'flavor-chat-ia'); ?>
                        </p>
                        <div class="flex gap-4">
                            <a href="#" class="w-12 h-12 rounded-lg flex items-center justify-center transition-all duration-300 hover:transform hover:scale-110"
                               style="background: var(--flavor-primary, #667eea); color: white;">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-12 h-12 rounded-lg flex items-center justify-center transition-all duration-300 hover:transform hover:scale-110"
                               style="background: var(--flavor-primary, #667eea); color: white;">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-12 h-12 rounded-lg flex items-center justify-center transition-all duration-300 hover:transform hover:scale-110"
                               style="background: var(--flavor-primary, #667eea); color: white;">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm3.066 9.645c.01.199.01.398.01.598 0 6.1-4.644 13.133-13.133 13.133-2.61 0-5.036-.763-7.078-2.073.363.042.724.063 1.095.063 2.14 0 4.111-.726 5.675-1.949-2 -.036-3.686-1.358-4.268-3.17.282.042.564.063.854.063.405 0 .81-.054 1.188-.155-2.087-.418-3.66-2.263-3.66-4.476v-.057c.615.343 1.319.546 2.065.57-1.224-.817-2.029-2.212-2.029-3.792 0-.835.225-1.618.616-2.292 2.25 2.762 5.615 4.577 9.404 4.766-.078-.343-.118-.698-.118-1.062 0-2.572 2.087-4.658 4.658-4.658 1.34 0 2.551.565 3.401 1.469 1.062-.208 2.06-.594 2.958-1.125-.349 1.087-1.087 1.999-2.051 2.574.943-.112 1.849-.361 2.688-.729-.625.935-1.415 1.755-2.324 2.413z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-2xl p-8 shadow-2xl">
                    <form class="space-y-6" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="<?php echo esc_attr__('flavor_contact_form', 'flavor-chat-ia'); ?>">
                        <?php wp_nonce_field('flavor_contact_form', 'flavor_contact_nonce'); ?>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html__('Nombre Completo *', 'flavor-chat-ia'); ?>
                            </label>
                            <input type="text"
                                   name="nombre_completo"
                                   required
                                   class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none"
                                   style="border-color: rgba(0,0,0,0.1); focus:border-color: var(--flavor-primary, #667eea);"
                                   placeholder="<?php echo esc_attr__('Juan Pérez', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html__('Email *', 'flavor-chat-ia'); ?>
                                </label>
                                <input type="email"
                                       name="email"
                                       required
                                       class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none"
                                       style="border-color: rgba(0,0,0,0.1);"
                                       placeholder="<?php echo esc_attr__('juan@empresa.com', 'flavor-chat-ia'); ?>">
                            </div>

                            <div>
                                <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html__('Teléfono', 'flavor-chat-ia'); ?>
                                </label>
                                <input type="tel"
                                       name="telefono"
                                       class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none"
                                       style="border-color: rgba(0,0,0,0.1);"
                                       placeholder="+34 600 000 000">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html__('Empresa', 'flavor-chat-ia'); ?>
                            </label>
                            <input type="text"
                                   name="empresa"
                                   class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none"
                                   style="border-color: rgba(0,0,0,0.1);"
                                   placeholder="<?php echo esc_attr__('Mi Empresa S.L.', 'flavor-chat-ia'); ?>">
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html__('Asunto *', 'flavor-chat-ia'); ?>
                            </label>
                            <select name="asunto"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none"
                                    style="border-color: rgba(0,0,0,0.1);">
                                <option value=""><?php echo esc_html__('Selecciona una opción', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('consulta', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Consulta General', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('presupuesto', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Solicitar Presupuesto', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('soporte', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Soporte Técnico', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('ventas', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Información de Ventas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Otro', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html__('Mensaje *', 'flavor-chat-ia'); ?>
                            </label>
                            <textarea name="mensaje"
                                      required
                                      rows="6"
                                      class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 focus:outline-none resize-none"
                                      style="border-color: rgba(0,0,0,0.1);"
                                      placeholder="<?php echo esc_attr__('Cuéntanos más sobre tu consulta...', 'flavor-chat-ia'); ?>"></textarea>
                        </div>

                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="acepto_privacidad"
                                   required
                                   class="mt-1"
                                   id="acepto_privacidad">
                            <label for="acepto_privacidad" class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html__('Acepto la', 'flavor-chat-ia'); ?> <a href="#" class="underline" style="color: var(--flavor-primary, #667eea);"><?php echo esc_html__('política de privacidad', 'flavor-chat-ia'); ?></a> <?php echo esc_html__('y el tratamiento de mis datos *', 'flavor-chat-ia'); ?>
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-xl"
                                style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                            <?php echo esc_html__('Enviar Mensaje', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($layout === 'con_mapa'): ?>
            <!-- Layout con Mapa (formulario arriba, mapa e info abajo) -->
            <div class="space-y-12">
                <!-- Formulario -->
                <div class="max-w-3xl mx-auto bg-white rounded-2xl p-8 shadow-2xl">
                    <form class="space-y-6" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="<?php echo esc_attr__('flavor_contact_form', 'flavor-chat-ia'); ?>">
                        <?php wp_nonce_field('flavor_contact_form', 'flavor_contact_nonce'); ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Nombre *', 'flavor-chat-ia'); ?></label>
                                <input type="text" name="nombre_completo" required class="w-full px-4 py-3 rounded-lg border-2" style="border-color: rgba(0,0,0,0.1);">
                            </div>
                            <div>
                                <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Email *', 'flavor-chat-ia'); ?></label>
                                <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border-2" style="border-color: rgba(0,0,0,0.1);">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Mensaje *', 'flavor-chat-ia'); ?></label>
                            <textarea name="mensaje" required rows="4" class="w-full px-4 py-3 rounded-lg border-2" style="border-color: rgba(0,0,0,0.1);"></textarea>
                        </div>

                        <button type="submit" class="w-full px-8 py-4 text-lg font-semibold rounded-lg" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                            <?php echo esc_html__('Enviar Mensaje', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                </div>

                <!-- Mapa e información -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-gray-200 rounded-2xl h-96 flex items-center justify-center">
                        <p class="text-gray-600"><?php echo esc_html__('Mapa de Google Maps aquí', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="space-y-6">
                        <?php if ($mostrar_telefono): ?>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: var(--flavor-primary, #667eea); color: white;">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <div class="font-semibold"><?php echo esc_html__('Teléfono', 'flavor-chat-ia'); ?></div>
                                    <p><?php echo esc_html($telefono); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($mostrar_direccion): ?>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: var(--flavor-primary, #667eea); color: white;">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                </div>
                                <div>
                                    <div class="font-semibold"><?php echo esc_html__('Dirección', 'flavor-chat-ia'); ?></div>
                                    <p><?php echo esc_html($direccion); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: // simple ?>
            <!-- Layout Simple (solo formulario centrado) -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl p-8 md:p-12 shadow-2xl">
                    <form class="space-y-6" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="<?php echo esc_attr__('flavor_contact_form', 'flavor-chat-ia'); ?>">
                        <?php wp_nonce_field('flavor_contact_form', 'flavor_contact_nonce'); ?>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Nombre Completo *', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="nombre_completo" required class="w-full px-4 py-3 rounded-lg border-2" style="border-color: rgba(0,0,0,0.1);" placeholder="<?php echo esc_attr__('Tu nombre', 'flavor-chat-ia'); ?>">
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Email *', 'flavor-chat-ia'); ?></label>
                            <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border-2" style="border-color: rgba(0,0,0,0.1);" placeholder="<?php echo esc_attr__('tu@email.com', 'flavor-chat-ia'); ?>">
                        </div>

                        <div>
                            <label class="block font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);"><?php echo esc_html__('Mensaje *', 'flavor-chat-ia'); ?></label>
                            <textarea name="mensaje" required rows="6" class="w-full px-4 py-3 rounded-lg border-2 resize-none" style="border-color: rgba(0,0,0,0.1);" placeholder="<?php echo esc_attr__('Tu mensaje...', 'flavor-chat-ia'); ?>"></textarea>
                        </div>

                        <button type="submit" class="w-full px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:shadow-xl" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                            <?php echo esc_html__('Enviar Mensaje', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
