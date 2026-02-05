<?php
/**
 * Frontend: Single Colectivo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$colectivo = $colectivo ?? [];
$nombre_colectivo = $colectivo['nombre'] ?? 'Colectivo';
$descripcion_colectivo = $colectivo['descripcion'] ?? '';
$mision_colectivo = $colectivo['mision'] ?? '';
$categoria_colectivo = $colectivo['categoria'] ?? 'General';
$miembros_total = $colectivo['miembros'] ?? 0;
$actividades_colectivo = $colectivo['actividades'] ?? [];
$eventos_proximos = $colectivo['eventos'] ?? [];
$contacto_colectivo = $colectivo['contacto'] ?? [];
?>

<div class="flavor-single colectivos">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-rose-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-rose-600">Colectivos</a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($nombre_colectivo); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Header del colectivo -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <div class="flex items-center gap-5 mb-6">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold text-3xl">
                                <?php echo esc_html(mb_substr($nombre_colectivo, 0, 1)); ?>
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo esc_html($nombre_colectivo); ?></h1>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-rose-100 text-rose-700 mt-1">
                                <?php echo esc_html($categoria_colectivo); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Descripcion -->
                    <div class="prose max-w-none text-gray-700 mb-6">
                        <?php echo wp_kses_post($descripcion_colectivo ?: '<p>Este colectivo reune a vecinos y vecinas comprometidos con la mejora de nuestra comunidad. Nuestras actividades buscan fomentar la participacion ciudadana y fortalecer los lazos entre los habitantes del barrio.</p>'); ?>
                    </div>

                    <!-- Mision -->
                    <div class="bg-rose-50 rounded-xl p-4 mb-6">
                        <h3 class="font-bold text-rose-700 mb-2">Nuestra mision</h3>
                        <p class="text-gray-700 text-sm">
                            <?php echo esc_html($mision_colectivo ?: 'Promover la cohesion social, la participacion activa y el desarrollo sostenible de nuestro barrio a traves de actividades culturales, sociales y medioambientales.'); ?>
                        </p>
                    </div>
                </div>

                <!-- Actividades -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Actividades</h2>
                    <?php if (!empty($actividades_colectivo)): ?>
                        <ul class="space-y-3">
                            <?php foreach ($actividades_colectivo as $actividad): ?>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-gray-700"><?php echo esc_html($actividad); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Reuniones vecinales mensuales</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Talleres de formacion comunitaria</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Jornadas de limpieza del barrio</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Eventos culturales y festivos</span>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Proximos eventos -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Proximos eventos</h2>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 rounded-xl bg-gray-50 hover:bg-rose-50 transition-colors">
                            <div class="w-14 h-14 rounded-xl bg-rose-100 flex flex-col items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-rose-600">FEB</span>
                                <span class="text-lg font-bold text-rose-700">15</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Asamblea General</h3>
                                <p class="text-sm text-gray-500">18:00 - Centro Civico</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-xl bg-gray-50 hover:bg-rose-50 transition-colors">
                            <div class="w-14 h-14 rounded-xl bg-rose-100 flex flex-col items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-rose-600">FEB</span>
                                <span class="text-lg font-bold text-rose-700">22</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Taller de participacion</h3>
                                <p class="text-sm text-gray-500">17:00 - Salon de actos</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-4 rounded-xl bg-gray-50 hover:bg-rose-50 transition-colors">
                            <div class="w-14 h-14 rounded-xl bg-rose-100 flex flex-col items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-rose-600">MAR</span>
                                <span class="text-lg font-bold text-rose-700">01</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Jornada de limpieza</h3>
                                <p class="text-sm text-gray-500">10:00 - Parque central</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- CTA Unirse -->
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4 mb-6">
                    <div class="text-center mb-4">
                        <span class="text-4xl font-bold text-rose-600"><?php echo esc_html($miembros_total ?: '87'); ?></span>
                        <p class="text-gray-500 text-sm">miembros activos</p>
                    </div>
                    <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);">
                        Unirse al Colectivo
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-3">Abierto a nuevos miembros</p>
                </div>

                <!-- Miembros preview -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Miembros destacados</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=10" alt="" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Carmen Vidal</p>
                                <p class="text-xs text-gray-500">Presidenta</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=25" alt="" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Roberto Sanchez</p>
                                <p class="text-xs text-gray-500">Secretario</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="https://i.pravatar.cc/150?img=38" alt="" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Isabel Torres</p>
                                <p class="text-xs text-gray-500">Tesorera</p>
                            </div>
                        </div>
                    </div>
                    <a href="#" class="block text-center text-sm text-rose-600 font-medium mt-4 hover:text-rose-700">
                        Ver todos los miembros
                    </a>
                </div>

                <!-- Contacto -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Contacto</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span><?php echo esc_html($contacto_colectivo['email'] ?? 'contacto@colectivo.org'); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span><?php echo esc_html($contacto_colectivo['direccion'] ?? 'Centro Civico del Barrio'); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><?php echo esc_html($contacto_colectivo['horario'] ?? 'Martes y jueves 18:00-20:00'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
