<?php
/**
 * Template: Equipo / Staff
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Nuestro Equipo';
$descripcion_seccion = $descripcion_seccion ?? 'Profesionales comprometidos con tu éxito';
$layout = $layout ?? 'grid';
$columnas = $columnas ?? '4';
$mostrar_redes_sociales = $mostrar_redes_sociales ?? true;

// Usar items del repeater o fallback a datos de ejemplo
$miembros_equipo = $items ?? [];

// Asegurar estructura consistente y resolver IDs de imagen
foreach ($miembros_equipo as &$miembro_item) {
    $miembro_item['nombre'] = $miembro_item['nombre'] ?? '';
    $miembro_item['puesto'] = $miembro_item['puesto'] ?? '';
    $miembro_item['bio'] = $miembro_item['bio'] ?? '';
    $miembro_item['foto'] = $miembro_item['foto'] ?? '';
    $miembro_item['linkedin'] = $miembro_item['linkedin'] ?? '';
    $miembro_item['twitter'] = $miembro_item['twitter'] ?? '';
    $miembro_item['email'] = $miembro_item['email'] ?? '';

    // Si la foto es un ID de attachment, obtener la URL
    if (!empty($miembro_item['foto']) && is_numeric($miembro_item['foto'])) {
        $foto_url = wp_get_attachment_image_url($miembro_item['foto'], 'medium_large');
        $miembro_item['foto'] = $foto_url ?: '';
    }
}
unset($miembro_item);

// Fallback: si no hay items configurados, mostrar ejemplo
if (empty($miembros_equipo)) {
    $miembros_equipo = [
        [
            'nombre' => 'María García',
            'puesto' => 'CEO & Fundadora',
            'foto' => 'https://i.pravatar.cc/400?img=1',
            'bio' => 'Líder visionaria con 15 años de experiencia en transformación digital',
            'linkedin' => '#',
            'twitter' => '#',
            'email' => 'maria@empresa.com'
        ],
        [
            'nombre' => 'Carlos Rodríguez',
            'puesto' => 'Director de Tecnología',
            'foto' => 'https://i.pravatar.cc/400?img=13',
            'bio' => 'Experto en arquitectura cloud y soluciones escalables',
            'linkedin' => '#',
            'twitter' => '#',
            'email' => 'carlos@empresa.com'
        ],
        [
            'nombre' => 'Ana Martínez',
            'puesto' => 'Directora de Marketing',
            'foto' => 'https://i.pravatar.cc/400?img=5',
            'bio' => 'Especialista en estrategias de crecimiento y branding digital',
            'linkedin' => '#',
            'twitter' => '#',
            'email' => 'ana@empresa.com'
        ],
        [
            'nombre' => 'David López',
            'puesto' => 'Lead Developer',
            'foto' => 'https://i.pravatar.cc/400?img=12',
            'bio' => 'Desarrollador full-stack apasionado por el código limpio',
            'linkedin' => '#',
            'twitter' => '#',
            'email' => 'david@empresa.com'
        ],
    ];
}

// Clases de columnas según la selección
$grid_columnas = [
    '2' => 'md:grid-cols-2',
    '3' => 'md:grid-cols-2 lg:grid-cols-3',
    '4' => 'md:grid-cols-2 lg:grid-cols-4'
];

$clase_columnas = $grid_columnas[$columnas] ?? $grid_columnas['4'];
?>

<section class="flavor-component flavor-section" style="background: var(--flavor-background-alt, #f8f9fa);">
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

        <?php if ($layout === 'grid'): ?>
            <!-- Layout Grid -->
            <div class="grid grid-cols-1 <?php echo esc_attr($clase_columnas); ?> gap-8">
                <?php foreach ($miembros_equipo as $miembro): ?>
                    <div class="group text-center">
                        <div class="relative overflow-hidden rounded-2xl mb-6 shadow-lg transition-all duration-300 group-hover:shadow-2xl">
                            <!-- Foto -->
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                <img src="<?php echo esc_url($miembro['foto']); ?>"
                                     alt="<?php echo esc_attr($miembro['nombre']); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            </div>

                            <!-- Overlay con redes sociales -->
                            <?php if ($mostrar_redes_sociales): ?>
                                <div class="absolute inset-0 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-all duration-300"
                                     style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);">
                                    <a href="<?php echo esc_url($miembro['linkedin']); ?>"
                                       class="p-3 bg-white rounded-full transition-transform duration-300 hover:scale-110"
                                       style="color: var(--flavor-primary, #667eea);">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo esc_url($miembro['twitter']); ?>"
                                       class="p-3 bg-white rounded-full transition-transform duration-300 hover:scale-110"
                                       style="color: var(--flavor-primary, #667eea);">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                                        </svg>
                                    </a>
                                    <a href="mailto:<?php echo esc_attr($miembro['email']); ?>"
                                       class="p-3 bg-white rounded-full transition-transform duration-300 hover:scale-110"
                                       style="color: var(--flavor-primary, #667eea);">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Información -->
                        <h3 class="text-2xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html($miembro['nombre']); ?>
                        </h3>
                        <p class="text-lg font-semibold mb-3" style="color: var(--flavor-primary, #667eea);">
                            <?php echo esc_html($miembro['puesto']); ?>
                        </p>
                        <p class="text-sm" style="color: var(--flavor-text-secondary, #666666);">
                            <?php echo esc_html($miembro['bio']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($layout === 'list'): ?>
            <!-- Layout List -->
            <div class="space-y-8">
                <?php foreach ($miembros_equipo as $miembro): ?>
                    <div class="flex flex-col md:flex-row gap-6 items-center bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="flex-shrink-0">
                            <img src="<?php echo esc_url($miembro['foto']); ?>"
                                 alt="<?php echo esc_attr($miembro['nombre']); ?>"
                                 class="w-32 h-32 rounded-full object-cover border-4"
                                 style="border-color: var(--flavor-primary, #667eea);">
                        </div>
                        <div class="flex-grow text-center md:text-left">
                            <h3 class="text-2xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($miembro['nombre']); ?>
                            </h3>
                            <p class="text-lg font-semibold mb-3" style="color: var(--flavor-primary, #667eea);">
                                <?php echo esc_html($miembro['puesto']); ?>
                            </p>
                            <p class="mb-4" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($miembro['bio']); ?>
                            </p>
                            <?php if ($mostrar_redes_sociales): ?>
                                <div class="flex gap-3 justify-center md:justify-start">
                                    <a href="<?php echo esc_url($miembro['linkedin']); ?>"
                                       class="p-2 rounded-full transition-all duration-300 hover:scale-110"
                                       style="background: var(--flavor-primary, #667eea); color: white;">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo esc_url($miembro['twitter']); ?>"
                                       class="p-2 rounded-full transition-all duration-300 hover:scale-110"
                                       style="background: var(--flavor-primary, #667eea); color: white;">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                                        </svg>
                                    </a>
                                    <a href="mailto:<?php echo esc_attr($miembro['email']); ?>"
                                       class="p-2 rounded-full transition-all duration-300 hover:scale-110"
                                       style="background: var(--flavor-primary, #667eea); color: white;">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: // slider ?>
            <!-- Layout Slider - simplified grid for demo -->
            <div class="overflow-x-auto pb-8">
                <div class="flex gap-8 min-w-min">
                    <?php foreach ($miembros_equipo as $miembro): ?>
                        <div class="group text-center" style="min-width: 280px;">
                            <div class="relative overflow-hidden rounded-2xl mb-6 shadow-lg">
                                <img src="<?php echo esc_url($miembro['foto']); ?>"
                                     alt="<?php echo esc_attr($miembro['nombre']); ?>"
                                     class="w-full h-80 object-cover">
                            </div>
                            <h3 class="text-xl font-bold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($miembro['nombre']); ?>
                            </h3>
                            <p class="font-semibold" style="color: var(--flavor-primary, #667eea);">
                                <?php echo esc_html($miembro['puesto']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
