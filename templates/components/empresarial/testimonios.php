<?php
/**
 * Template: Testimonios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Lo Que Dicen Nuestros Clientes';
$layout = $layout ?? 'carousel';
$mostrar_foto = $mostrar_foto ?? true;
$mostrar_empresa = $mostrar_empresa ?? true;

// Usar items del repeater o fallback a datos de ejemplo
$testimonios_mostrar = $items ?? [];

// Fallback: si no hay items configurados, mostrar ejemplo minimo
if (empty($testimonios_mostrar)) {
    $testimonios_mostrar = [
        [
            'nombre' => 'Roberto Martín',
            'puesto' => 'CEO',
            'empresa' => 'TechCorp Solutions',
            'foto' => 'https://i.pravatar.cc/150?img=33',
            'testimonio' => 'El equipo superó nuestras expectativas. Su profesionalismo transformó nuestra operación digital.',
            'rating' => 5,
        ],
        [
            'nombre' => 'Carmen Ruiz',
            'puesto' => 'Directora de Marketing',
            'empresa' => 'Innovatech',
            'foto' => 'https://i.pravatar.cc/150?img=23',
            'testimonio' => 'Entendieron nuestras necesidades y entregaron una solución que excedió las expectativas.',
            'rating' => 5,
        ],
        [
            'nombre' => 'Luis Hernández',
            'puesto' => 'CTO',
            'empresa' => 'Digital Ventures',
            'foto' => 'https://i.pravatar.cc/150?img=52',
            'testimonio' => 'Su experiencia técnica nos permitió lanzar nuestro producto en tiempo récord.',
            'rating' => 5,
        ],
    ];
}

// Asegurar que rating sea numérico
foreach ($testimonios_mostrar as &$testimonio_item) {
    $testimonio_item['rating'] = intval($testimonio_item['rating'] ?? 5);
    $testimonio_item['foto'] = $testimonio_item['foto'] ?? '';
}
unset($testimonio_item);
?>

<section class="flavor-component flavor-section" style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">
    <div class="flavor-container">
        <!-- Encabezado de sección -->
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>

            <!-- Decoración de estrellas -->
            <div class="flex justify-center gap-2 mb-6">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg class="w-8 h-8 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
            </div>

            <p class="text-xl text-white text-opacity-95">
                <?php echo esc_html__('Más de 500 empresas confían en nosotros', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <?php if ($layout === 'carousel'): ?>
            <!-- Layout Carousel - simplified as horizontal scroll -->
            <div class="overflow-x-auto pb-8 -mx-4 px-4">
                <div class="flex gap-8 min-w-min">
                    <?php foreach ($testimonios_mostrar as $testimonio): ?>
                        <div class="bg-white rounded-2xl p-8 shadow-2xl transition-transform duration-300 hover:-translate-y-2"
                             style="min-width: 380px; max-width: 420px;">

                            <!-- Rating -->
                            <div class="flex gap-1 mb-6">
                                <?php for ($i = 0; $i < $testimonio['rating']; $i++): ?>
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>

                            <!-- Testimonio -->
                            <p class="text-lg mb-8 leading-relaxed italic" style="color: var(--flavor-text-secondary, #666666);">
                                "<?php echo esc_html($testimonio['testimonio']); ?>"
                            </p>

                            <!-- Autor -->
                            <div class="flex items-center gap-4">
                                <?php if ($mostrar_foto): ?>
                                    <img src="<?php echo esc_url($testimonio['foto']); ?>"
                                         alt="<?php echo esc_attr($testimonio['nombre']); ?>"
                                         class="w-16 h-16 rounded-full object-cover border-4"
                                         style="border-color: var(--flavor-primary, #667eea);">
                                <?php endif; ?>
                                <div>
                                    <div class="font-bold text-lg" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($testimonio['nombre']); ?>
                                    </div>
                                    <div class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                        <?php echo esc_html($testimonio['puesto']); ?>
                                        <?php if ($mostrar_empresa): ?>
                                            <span style="color: var(--flavor-primary, #667eea);"> · <?php echo esc_html($testimonio['empresa']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($layout === 'masonry'): ?>
            <!-- Layout Masonry -->
            <div class="columns-1 md:columns-2 lg:columns-3 gap-8">
                <?php foreach ($testimonios_mostrar as $testimonio): ?>
                    <div class="break-inside-avoid mb-8">
                        <div class="bg-white rounded-2xl p-8 shadow-2xl">
                            <!-- Rating -->
                            <div class="flex gap-1 mb-6">
                                <?php for ($i = 0; $i < $testimonio['rating']; $i++): ?>
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>

                            <!-- Testimonio -->
                            <p class="text-lg mb-8 leading-relaxed italic" style="color: var(--flavor-text-secondary, #666666);">
                                "<?php echo esc_html($testimonio['testimonio']); ?>"
                            </p>

                            <!-- Autor -->
                            <div class="flex items-center gap-4">
                                <?php if ($mostrar_foto): ?>
                                    <img src="<?php echo esc_url($testimonio['foto']); ?>"
                                         alt="<?php echo esc_attr($testimonio['nombre']); ?>"
                                         class="w-14 h-14 rounded-full object-cover">
                                <?php endif; ?>
                                <div>
                                    <div class="font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($testimonio['nombre']); ?>
                                    </div>
                                    <div class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                        <?php echo esc_html($testimonio['puesto']); ?>
                                        <?php if ($mostrar_empresa): ?>
                                            <span style="color: var(--flavor-primary, #667eea);"> · <?php echo esc_html($testimonio['empresa']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: // grid ?>
            <!-- Layout Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($testimonios_mostrar as $testimonio): ?>
                    <div class="bg-white rounded-2xl p-8 shadow-2xl transition-transform duration-300 hover:-translate-y-2">
                        <!-- Rating -->
                        <div class="flex gap-1 mb-6">
                            <?php for ($i = 0; $i < $testimonio['rating']; $i++): ?>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>

                        <!-- Testimonio -->
                        <p class="text-lg mb-8 leading-relaxed italic" style="color: var(--flavor-text-secondary, #666666);">
                            "<?php echo esc_html($testimonio['testimonio']); ?>"
                        </p>

                        <!-- Autor -->
                        <div class="flex items-center gap-4">
                            <?php if ($mostrar_foto): ?>
                                <img src="<?php echo esc_url($testimonio['foto']); ?>"
                                     alt="<?php echo esc_attr($testimonio['nombre']); ?>"
                                     class="w-14 h-14 rounded-full object-cover">
                            <?php endif; ?>
                            <div>
                                <div class="font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($testimonio['nombre']); ?>
                                </div>
                                <div class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                                    <?php echo esc_html($testimonio['puesto']); ?>
                                    <?php if ($mostrar_empresa): ?>
                                        <span style="color: var(--flavor-primary, #667eea);"> · <?php echo esc_html($testimonio['empresa']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Call to action -->
        <div class="text-center mt-16">
            <p class="text-2xl font-semibold text-white mb-6">
                <?php echo esc_html__('¿Listo para unirte a nuestros clientes satisfechos?', 'flavor-chat-ia'); ?>
            </p>
            <a href="#contacto"
               class="flavor-button inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:shadow-2xl"
               style="background: white; color: var(--flavor-primary, #667eea);">
                <span><?php echo esc_html__('Comienza Hoy', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
