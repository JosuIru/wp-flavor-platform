<?php
/**
 * Frontend: Single Curso
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$curso = $curso ?? [];
$titulo = $curso['titulo'] ?? 'Curso';
$descripcion = $curso['descripcion'] ?? '';
$imagen = $curso['imagen'] ?? 'https://picsum.photos/seed/curso1/800/450';
$categoria = $curso['categoria'] ?? 'General';
$fecha = $curso['fecha'] ?? '15 Feb 2024';
$horario = $curso['horario'] ?? '18:00 - 20:00';
$duracion = $curso['duracion'] ?? '2 horas';
$precio = $curso['precio'] ?? '25€';
$gratuito = $curso['gratuito'] ?? false;
$instructor = $curso['instructor'] ?? [];
$temario = $curso['temario'] ?? [];
$plazas_disponibles = $curso['plazas_disponibles'] ?? 10;
?>

<div class="flavor-single cursos">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600" role="navigation" aria-label="<?php esc_attr_e('Migas de pan', 'flavor-chat-ia'); ?>">
                <a href="#" class="hover:text-purple-600"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
                <span aria-hidden="true">/</span>
                <a href="#" class="hover:text-purple-600"><?php echo esc_html__('Cursos', 'flavor-chat-ia'); ?></a>
                <span aria-hidden="true">/</span>
                <span class="text-gray-900 font-medium" aria-current="page"><?php echo esc_html($titulo); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Imagen -->
                <div class="relative aspect-[16/9] rounded-2xl overflow-hidden mb-6">
                    <img src="<?php echo esc_url($imagen); ?>"
                         alt="<?php echo esc_attr($titulo); ?>"
                         class="w-full h-full object-cover">
                    <?php if ($gratuito): ?>
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-bold bg-green-500 text-white"><?php echo esc_html__('Gratuito', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-purple-100 text-purple-700 mb-4">
                        <?php echo esc_html($categoria); ?>
                    </span>

                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h1>

                    <div class="flex flex-wrap items-center gap-4 text-gray-600 mb-6">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="sr-only"><?php echo esc_html__('Fecha:', 'flavor-chat-ia'); ?></span>
                            <?php echo esc_html($fecha); ?>
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="sr-only"><?php echo esc_html__('Horario:', 'flavor-chat-ia'); ?></span>
                            <?php echo esc_html($horario); ?>
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="sr-only"><?php echo esc_html__('Plazas:', 'flavor-chat-ia'); ?></span>
                            <?php echo esc_html($plazas_disponibles); ?> plazas
                        </span>
                    </div>

                    <div class="prose max-w-none text-gray-700">
                        <?php echo wp_kses_post($descripcion); ?>
                    </div>
                </div>

                <!-- Temario -->
                <?php if (!empty($temario)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Que aprenderas', 'flavor-chat-ia'); ?></h2>
                        <ul class="space-y-3">
                            <?php foreach ($temario as $item): ?>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-purple-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-gray-700"><?php echo esc_html($item); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Instructor -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Instructor', 'flavor-chat-ia'); ?></h2>
                    <div class="flex items-start gap-4">
                        <img src="<?php echo esc_url($instructor['avatar'] ?? 'https://i.pravatar.cc/150?img=1'); ?>"
                             alt="<?php echo esc_attr($instructor['nombre'] ?? 'Instructor'); ?>"
                             class="w-16 h-16 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900 text-lg"><?php echo esc_html($instructor['nombre'] ?? 'Nombre Instructor'); ?></p>
                            <p class="text-purple-600 mb-2"><?php echo esc_html($instructor['especialidad'] ?? 'Especialista'); ?></p>
                            <p class="text-gray-600 text-sm"><?php echo esc_html($instructor['bio'] ?? 'Breve biografia del instructor...'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar de inscripcion -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4">
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold <?php echo $gratuito ? 'text-green-600' : 'text-purple-600'; ?>">
                            <?php echo $gratuito ? 'Gratis' : esc_html($precio); ?>
                        </span>
                    </div>

                    <!-- Info rapida -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></span>
                            <span class="font-semibold"><?php echo esc_html($fecha); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Horario', 'flavor-chat-ia'); ?></span>
                            <span class="font-semibold"><?php echo esc_html($horario); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Duracion', 'flavor-chat-ia'); ?></span>
                            <span class="font-semibold"><?php echo esc_html($duracion); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-purple-50">
                            <span class="text-gray-600"><?php echo esc_html__('Plazas disponibles', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold text-purple-600"><?php echo esc_html($plazas_disponibles); ?></span>
                        </div>
                    </div>

                    <?php if ($plazas_disponibles > 0): ?>
                        <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);">
                            <?php echo esc_html__('Inscribirse Ahora', 'flavor-chat-ia'); ?>
                        </button>
                    <?php else: ?>
                        <button class="w-full py-4 rounded-xl text-lg font-semibold text-white bg-gray-400 cursor-not-allowed">
                            <?php echo esc_html__('Completo', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>

                    <p class="text-xs text-gray-500 text-center mt-4">
                        <?php echo esc_html__('Cancelacion gratuita hasta 48h antes', 'flavor-chat-ia'); ?>
                    </p>
                </div>

                <!-- Compartir -->
                <div class="bg-white rounded-2xl p-6 shadow-md mt-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Compartir curso', 'flavor-chat-ia'); ?></h3>
                    <div class="flex items-center gap-3">
                        <button class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200 transition-colors"
                                aria-label="<?php esc_attr_e('Compartir en Facebook', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </button>
                        <button class="w-10 h-10 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center hover:bg-sky-200 transition-colors"
                                aria-label="<?php esc_attr_e('Compartir en Twitter', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </button>
                        <button class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200 transition-colors"
                                aria-label="<?php esc_attr_e('Compartir en WhatsApp', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
