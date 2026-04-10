<?php
/**
 * Template: Categorias de Ayuda Vecinal
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Como Puedes Ayudar?';
$descripcion = $descripcion ?? 'Elige el tipo de ayuda que quieres ofrecer a tus vecinos';

$categorias = [
    ['nombre' => 'Compras y Recados', 'descripcion' => 'Acompanar o hacer compras, recoger medicinas', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>', 'solicitudes' => 24, 'voluntarios' => 18],
    ['nombre' => 'Acompanamiento', 'descripcion' => 'Visitas, paseos, citas medicas', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>', 'solicitudes' => 31, 'voluntarios' => 12],
    ['nombre' => 'Bricolaje y Reparaciones', 'descripcion' => 'Pequenas reparaciones en el hogar', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', 'solicitudes' => 18, 'voluntarios' => 9],
    ['nombre' => 'Cuidado de Mascotas', 'descripcion' => 'Pasear, alimentar o cuidar animales', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>', 'solicitudes' => 15, 'voluntarios' => 22],
    ['nombre' => 'Ensenanza y Tecnologia', 'descripcion' => 'Clases de informatica, idiomas, etc.', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>', 'solicitudes' => 27, 'voluntarios' => 14],
    ['nombre' => 'Transporte', 'descripcion' => 'Llevar en coche a citas o gestiones', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>', 'solicitudes' => 12, 'voluntarios' => 8],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <?php echo esc_html__('Categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($categorias as $cat): ?>
                <a href="#categoria-<?php echo sanitize_title($cat['nombre']); ?>" class="group relative bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100 overflow-hidden">
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(234, 88, 12, 0.05) 100%);"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center p-4 rounded-2xl mb-4 transition-all duration-300 group-hover:scale-110 bg-gradient-to-br from-amber-100 to-orange-100 text-amber-600">
                            <?php echo $cat['icono']; ?>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-amber-600 transition-colors mb-2"><?php echo esc_html($cat['nombre']); ?></h3>
                        <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($cat['descripcion']); ?></p>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-4 text-xs">
                                <span class="flex items-center gap-1 text-orange-600 font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <?php echo esc_html($cat['solicitudes']); ?> solicitudes
                                </span>
                                <span class="flex items-center gap-1 text-green-600 font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <?php echo esc_html($cat['voluntarios']); ?> voluntarios
                                </span>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-amber-500 transition-all duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Stats generales -->
        <div class="mt-16 rounded-2xl p-8" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-amber-600 mb-1">127</div>
                    <div class="text-sm text-gray-600"><?php echo esc_html__('Solicitudes activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-orange-600 mb-1">83</div>
                    <div class="text-sm text-gray-600"><?php echo esc_html__('Voluntarios disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-green-600 mb-1">456</div>
                    <div class="text-sm text-gray-600"><?php echo esc_html__('Ayudas completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-purple-600 mb-1">4.9</div>
                    <div class="text-sm text-gray-600"><?php echo esc_html__('Valoracion media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
