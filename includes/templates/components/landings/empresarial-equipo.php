<?php
/**
 * Template: Equipo Empresarial
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$miembros_equipo = [
    ['nombre' => 'Ana García', 'cargo' => 'CEO & Fundadora', 'avatar' => '👩‍💼', 'bio' => '15 años de experiencia en tecnología empresarial'],
    ['nombre' => 'Carlos Ruiz', 'cargo' => 'CTO', 'avatar' => '👨‍💻', 'bio' => 'Experto en arquitectura de software y cloud'],
    ['nombre' => 'María López', 'cargo' => 'Directora Comercial', 'avatar' => '👩‍🔬', 'bio' => 'Especialista en desarrollo de negocio B2B'],
    ['nombre' => 'David Martín', 'cargo' => 'Director de Producto', 'avatar' => '👨‍🎨', 'bio' => 'Apasionado por crear productos que importan'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20 bg-white">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Nuestro Equipo'); ?></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Profesionales apasionados comprometidos con tu éxito</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($miembros_equipo as $miembro): ?>
            <div class="group text-center">
                <div class="relative mb-6">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center text-5xl group-hover:scale-105 transition-transform">
                        <?php echo $miembro['avatar']; ?>
                    </div>
                    <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="#" class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-800 text-white rounded-full flex items-center justify-center hover:bg-gray-900 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo esc_html($miembro['nombre']); ?></h3>
                <p class="text-blue-600 font-medium text-sm mb-2"><?php echo esc_html($miembro['cargo']); ?></p>
                <p class="text-gray-500 text-sm"><?php echo esc_html($miembro['bio']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-16 bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-8 md:p-12 text-center text-white">
            <h3 class="text-2xl md:text-3xl font-bold mb-4">¿Quieres unirte a nuestro equipo?</h3>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">Estamos siempre buscando talento. Descubre las oportunidades disponibles.</p>
            <a href="#" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-colors">
                Ver Ofertas de Empleo
            </a>
        </div>
    </div>
</section>
