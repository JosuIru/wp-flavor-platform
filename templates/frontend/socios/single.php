<?php
/**
 * Frontend: Detalle de Socios
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$socio = $items[0] ?? [
    'id' => 1,
    'nombre' => 'María García López',
    'iniciales' => 'MG',
    'bio' => 'Emprendedora digital con más de 10 años de experiencia en marketing y tecnología. Apasionada por el networking y la innovación social.',
    'nivel' => 'Premium',
    'miembro_desde' => '2023-03-15',
    'intereses' => ['Tecnología', 'Marketing', 'Networking', 'Innovación', 'Diseño'],
    'color' => 'bg-rose-500',
    'actividad' => [
        ['tipo' => 'evento', 'texto' => 'Asistió al Workshop de Marketing Digital', 'fecha' => '2024-01-10'],
        ['tipo' => 'logro', 'texto' => 'Alcanzó nivel Premium', 'fecha' => '2023-12-01'],
        ['tipo' => 'conexion', 'texto' => 'Se conectó con 5 nuevos socios', 'fecha' => '2023-11-20'],
    ],
];

$socios_relacionados = [
    ['nombre' => 'Carlos Rodríguez', 'iniciales' => 'CR', 'nivel' => 'Pro', 'color' => 'bg-pink-600'],
    ['nombre' => 'Ana Martínez', 'iniciales' => 'AM', 'nivel' => 'Básico', 'color' => 'bg-fuchsia-500'],
    ['nombre' => 'Luis Fernández', 'iniciales' => 'LF', 'nivel' => 'Premium', 'color' => 'bg-rose-400'],
];
?>

<div class="flavor-frontend flavor-socios-single max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Migas de pan -->
    <nav class="flex items-center text-sm text-gray-500 mb-6" aria-label="<?php echo esc_attr__('Navegación', 'flavor-chat-ia'); ?>">
        <a href="/" class="hover:text-rose-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <a href="/socios/" class="hover:text-rose-600 transition-colors"><?php echo esc_html__('Socios', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium"><?php echo esc_html($socio['nombre']); ?></span>
    </nav>

    <!-- Cuadrícula de 3 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Columna principal (2 columnas) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Tarjeta de perfil -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-rose-500 to-pink-600 h-32"></div>
                <div class="px-6 pb-6 -mt-12">
                    <div class="<?php echo esc_attr($socio['color']); ?> w-24 h-24 rounded-full flex items-center justify-center text-white text-2xl font-bold border-4 border-white shadow-lg">
                        <?php echo esc_html($socio['iniciales']); ?>
                    </div>
                    <div class="mt-4">
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo esc_html($socio['nombre']); ?></h1>
                        <?php
                        $nivel_clase_perfil = match($socio['nivel']) {
                            'Premium' => 'bg-amber-100 text-amber-800',
                            'Pro' => 'bg-purple-100 text-purple-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        ?>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-medium <?php echo esc_attr($nivel_clase_perfil); ?>">
                            <?php echo esc_html($socio['nivel']); ?>
                        </span>
                    </div>
                    <p class="mt-4 text-gray-600 leading-relaxed"><?php echo wp_kses_post($socio['bio']); ?></p>
                    <p class="mt-3 text-sm text-gray-400">
                        <?php echo esc_html__('Miembro desde', 'flavor-chat-ia'); ?>
                        <?php echo esc_html(date_i18n('F Y', strtotime($socio['miembro_desde']))); ?>
                    </p>
                </div>
            </div>

            <!-- Intereses -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Intereses', 'flavor-chat-ia'); ?></h2>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (($socio['intereses'] ?? []) as $interes) : ?>
                        <span class="px-3 py-1.5 bg-rose-50 text-rose-700 rounded-lg text-sm font-medium"><?php echo esc_html($interes); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Actividad reciente', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-4">
                    <?php foreach (($socio['actividad'] ?? []) as $actividad_item) :
                        $icono_tipo = match($actividad_item['tipo']) {
                            'evento' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                            'logro' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                            default => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        };
                    ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-rose-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($icono_tipo); ?>"/></svg>
                        </div>
                        <div>
                            <p class="text-gray-800"><?php echo esc_html($actividad_item['texto']); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html(date_i18n('j M Y', strtotime($actividad_item['fecha']))); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Barra lateral -->
        <div class="space-y-6">

            <!-- Tarjeta de membresía -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-2"><?php echo esc_html__('Membresía', 'flavor-chat-ia'); ?></h3>
                <p class="text-rose-100 text-sm"><?php echo esc_html__('Nivel', 'flavor-chat-ia'); ?>: <?php echo esc_html($socio['nivel']); ?></p>
                <p class="text-rose-100 text-sm mt-1"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?>: <?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></p>
                <div class="mt-4 pt-4 border-t border-rose-400">
                    <p class="text-xs text-rose-200"><?php echo esc_html__('Miembro desde', 'flavor-chat-ia'); ?></p>
                    <p class="font-medium"><?php echo esc_html(date_i18n('j F Y', strtotime($socio['miembro_desde']))); ?></p>
                </div>
            </div>

            <!-- CTA de contacto -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-3"><?php echo esc_html__('¿Quieres conectar?', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html__('Envía un mensaje para iniciar una conversación', 'flavor-chat-ia'); ?></p>
                <a href="/socios/mi-perfil/?id=<?php echo esc_attr($socio['id'] ?? 0); ?>&contactar=1" class="block w-full text-center py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-semibold rounded-lg hover:from-rose-600 hover:to-pink-700 transition-all">
                    <?php echo esc_html__('Enviar mensaje', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Intereses en común -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Socios con intereses comunes', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($socios_relacionados as $relacionado) : ?>
                    <div class="flex items-center gap-3">
                        <div class="<?php echo esc_attr($relacionado['color']); ?> w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            <?php echo esc_html($relacionado['iniciales']); ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($relacionado['nombre']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($relacionado['nivel']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>
