<?php
/**
 * Frontend: Detalle de Empresarial
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$empresa = $items[0] ?? [
    'id' => 1,
    'nombre' => 'Innovatech Solutions',
    'iniciales' => 'IS',
    'sector' => 'Tecnología',
    'descripcion' => 'Innovatech Solutions es una empresa líder en desarrollo de software a medida y soluciones cloud. Ofrecemos servicios de consultoría tecnológica, desarrollo de aplicaciones web y móviles, y migración a la nube.',
    'empleados' => 45,
    'ubicacion' => 'Madrid, España',
    'web' => 'https://innovatech.es',
    'fundada' => '2015',
    'email' => 'info@innovatech.es',
    'telefono' => '+34 912 345 678',
    'color' => 'bg-gray-500',
    'servicios' => ['Desarrollo Web', 'Apps Móviles', 'Cloud Computing', 'Consultoría IT', 'Ciberseguridad'],
];

$equipo = [
    ['nombre' => 'Roberto Díaz', 'cargo' => 'CEO & Fundador', 'iniciales' => 'RD'],
    ['nombre' => 'Sara López', 'cargo' => 'CTO', 'iniciales' => 'SL'],
    ['nombre' => 'Miguel Torres', 'cargo' => 'Director Comercial', 'iniciales' => 'MT'],
];

$ofertas_empleo = [
    ['titulo' => 'Desarrollador Full Stack', 'tipo' => 'Tiempo completo', 'ubicacion' => 'Madrid / Remoto'],
    ['titulo' => 'Diseñador UX/UI Senior', 'tipo' => 'Tiempo completo', 'ubicacion' => 'Madrid'],
];

$empresas_similares = [
    ['nombre' => 'Estrategia Global', 'sector' => 'Consultoría', 'iniciales' => 'EG', 'color' => 'bg-slate-600'],
    ['nombre' => 'Digital Factory', 'sector' => 'Tecnología', 'iniciales' => 'DF', 'color' => 'bg-gray-600'],
    ['nombre' => 'NexCode Labs', 'sector' => 'Software', 'iniciales' => 'NL', 'color' => 'bg-slate-500'],
];
?>

<div class="flavor-frontend flavor-empresarial-single max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Migas de pan -->
    <nav class="flex items-center text-sm text-gray-500 mb-6" aria-label="<?php echo esc_attr__('Navegación', 'flavor-chat-ia'); ?>">
        <a href="#" class="hover:text-slate-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <a href="#" class="hover:text-slate-600 transition-colors"><?php echo esc_html__('Directorio Empresarial', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium"><?php echo esc_html($empresa['nombre']); ?></span>
    </nav>

    <!-- Cuadrícula de 3 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Columna principal (2 columnas) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Perfil de la empresa -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-500 to-slate-600 h-28"></div>
                <div class="px-6 pb-6 -mt-10">
                    <div class="<?php echo esc_attr($empresa['color']); ?> w-20 h-20 rounded-xl flex items-center justify-center text-white text-2xl font-bold border-4 border-white shadow-lg">
                        <?php echo esc_html($empresa['iniciales']); ?>
                    </div>
                    <div class="mt-4">
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo esc_html($empresa['nombre']); ?></h1>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-700">
                            <?php echo esc_html($empresa['sector']); ?>
                        </span>
                    </div>
                    <p class="mt-4 text-gray-600 leading-relaxed"><?php echo wp_kses_post($empresa['descripcion']); ?></p>
                </div>
            </div>

            <!-- Servicios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Servicios', 'flavor-chat-ia'); ?></h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach (($empresa['servicios'] ?? []) as $servicio) : ?>
                        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
                            <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700"><?php echo esc_html($servicio); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Equipo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Equipo directivo', 'flavor-chat-ia'); ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?php foreach ($equipo as $miembro) : ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="w-12 h-12 bg-slate-500 rounded-full flex items-center justify-center text-white font-bold mx-auto mb-2">
                            <?php echo esc_html($miembro['iniciales']); ?>
                        </div>
                        <p class="font-medium text-gray-900 text-sm"><?php echo esc_html($miembro['nombre']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($miembro['cargo']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ofertas de empleo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Ofertas de empleo', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-3">
                    <?php foreach ($ofertas_empleo as $oferta) : ?>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-slate-300 transition-colors">
                        <div>
                            <h3 class="font-medium text-gray-900"><?php echo esc_html($oferta['titulo']); ?></h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                <?php echo esc_html($oferta['tipo']); ?> &middot; <?php echo esc_html($oferta['ubicacion']); ?>
                            </p>
                        </div>
                        <a href="#" class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                            <?php echo esc_html__('Aplicar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Barra lateral -->
        <div class="space-y-6">

            <!-- CTA de contacto -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-3"><?php echo esc_html__('¿Interesado en colaborar?', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html__('Ponte en contacto con esta empresa', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url('mailto:' . ($empresa['email'] ?? '')); ?>" class="block w-full text-center py-2.5 bg-gradient-to-r from-gray-500 to-slate-600 text-white font-semibold rounded-lg hover:from-gray-600 hover:to-slate-700 transition-all mb-3">
                    <?php echo esc_html__('Contactar empresa', 'flavor-chat-ia'); ?>
                </a>
                <?php if (!empty($empresa['web'])) : ?>
                <a href="<?php echo esc_url($empresa['web']); ?>" target="_blank" rel="noopener noreferrer" class="block w-full text-center py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <?php echo esc_html__('Visitar sitio web', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Información de la empresa -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Información', 'flavor-chat-ia'); ?></h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Fundada', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium"><?php echo esc_html($empresa['fundada']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Empleados', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium"><?php echo esc_html($empresa['empleados']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Sector', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium"><?php echo esc_html($empresa['sector']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium"><?php echo esc_html($empresa['ubicacion']); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Empresas similares -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Empresas similares', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($empresas_similares as $empresa_similar) : ?>
                    <a href="#" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="<?php echo esc_attr($empresa_similar['color']); ?> w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            <?php echo esc_html($empresa_similar['iniciales']); ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($empresa_similar['nombre']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($empresa_similar['sector']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>
