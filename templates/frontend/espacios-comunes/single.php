<?php
/**
 * Frontend: Single Espacio Comun
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$espacio = $espacio ?? [];
$nombre = $espacio['nombre'] ?? 'Espacio';
$descripcion = $espacio['descripcion'] ?? '';
$capacidad = $espacio['capacidad'] ?? 0;
$precio = $espacio['precio'] ?? '0€';
$imagenes = $espacio['imagenes'] ?? [];
$equipamiento = $espacio['equipamiento'] ?? [];
$horarios = $espacio['horarios'] ?? [];
$normas = $espacio['normas'] ?? [];
?>

<div class="flavor-single espacios-comunes">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-rose-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-rose-600">Espacios</a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($nombre); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Galeria de imagenes -->
                <div class="mb-8">
                    <div class="relative aspect-[16/9] rounded-2xl overflow-hidden mb-4">
                        <img id="imagen-principal"
                             src="<?php echo esc_url($imagenes[0] ?? 'https://picsum.photos/seed/esp1/800/450'); ?>"
                             alt="<?php echo esc_attr($nombre); ?>"
                             class="w-full h-full object-cover">
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-bold bg-green-500 text-white">
                            Disponible
                        </span>
                    </div>
                    <?php if (count($imagenes) > 1): ?>
                        <div class="grid grid-cols-4 gap-2">
                            <?php foreach ($imagenes as $indice => $img): ?>
                                <button onclick="document.getElementById('imagen-principal').src='<?php echo esc_url($img); ?>'"
                                        class="aspect-video rounded-lg overflow-hidden border-2 border-transparent hover:border-rose-500 transition-colors">
                                    <img src="<?php echo esc_url($img); ?>" alt="" class="w-full h-full object-cover">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info basica -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo esc_html($nombre); ?></h1>
                        <div class="text-right">
                            <span class="text-3xl font-bold text-rose-600"><?php echo esc_html($precio); ?></span>
                            <span class="text-gray-500">/hora</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 text-gray-600 mb-6">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Hasta <?php echo esc_html($capacidad); ?> personas
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <?php echo esc_html($espacio['superficie'] ?? '50'); ?> m²
                        </span>
                    </div>

                    <p class="text-gray-700 leading-relaxed"><?php echo esc_html($descripcion); ?></p>
                </div>

                <!-- Equipamiento -->
                <?php if (!empty($equipamiento)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Equipamiento Incluido</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <?php foreach ($equipamiento as $equipo): ?>
                                <div class="flex items-center gap-2 p-3 rounded-xl bg-rose-50">
                                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-gray-700"><?php echo esc_html($equipo); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Normas de uso -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Normas de Uso</h2>
                    <ul class="space-y-2">
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Reserva minima de 1 hora
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Cancelacion gratuita hasta 24h antes
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Dejar el espacio tal y como se encontro
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            No superar la capacidad maxima indicada
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar de reserva -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Reservar Espacio</h2>

                    <!-- Selector de fecha -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                        <input type="date"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Selector de hora inicio -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hora inicio</label>
                            <select class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                                <?php for ($h = 8; $h <= 21; $h++): ?>
                                    <option value="<?php echo $h; ?>:00"><?php echo sprintf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hora fin</label>
                            <select class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                                <?php for ($h = 9; $h <= 22; $h++): ?>
                                    <option value="<?php echo $h; ?>:00"><?php echo sprintf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Resumen precio -->
                    <div class="bg-rose-50 rounded-xl p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700"><?php echo esc_html($precio); ?> x 1 hora</span>
                            <span class="font-semibold"><?php echo esc_html($precio); ?></span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-rose-200">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-rose-600"><?php echo esc_html($precio); ?></span>
                        </div>
                    </div>

                    <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
                        Reservar Ahora
                    </button>

                    <p class="text-xs text-gray-500 text-center mt-4">
                        No se te cobrara nada hasta confirmar la reserva
                    </p>
                </div>

                <!-- Contacto -->
                <div class="bg-white rounded-2xl p-6 shadow-md mt-6">
                    <h3 class="font-bold text-gray-900 mb-4">Necesitas ayuda?</h3>
                    <a href="#contacto" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-rose-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block font-medium text-gray-900">Contactar</span>
                            <span class="text-sm text-gray-500">Respuesta en menos de 24h</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
