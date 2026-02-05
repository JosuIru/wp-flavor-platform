<?php
/**
 * Frontend: Single Punto de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$punto = $punto ?? [];
$nombre = $punto['nombre'] ?? 'Punto de Reciclaje';
$direccion = $punto['direccion'] ?? '';
$contenedores = $punto['contenedores'] ?? [];
$horario = $punto['horario'] ?? '24 horas';
$servicios = $punto['servicios'] ?? [];
?>

<div class="flavor-single reciclaje">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-500 to-green-600 py-8 px-4">
        <div class="container mx-auto max-w-4xl">
            <nav class="flex items-center gap-2 text-sm text-white/80 mb-4">
                <a href="#" class="hover:text-white">Reciclaje</a>
                <span>/</span>
                <span class="text-white"><?php echo esc_html($nombre); ?></span>
            </nav>
            <h1 class="text-2xl md:text-3xl font-bold text-white"><?php echo esc_html($nombre); ?></h1>
            <p class="text-white/90"><?php echo esc_html($direccion); ?></p>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Contenedores disponibles -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Contenedores Disponibles</h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php
                    $contenedores_info = [
                        ['color' => 'bg-yellow-400', 'nombre' => 'Amarillo', 'residuos' => 'Plasticos, latas, briks'],
                        ['color' => 'bg-blue-500', 'nombre' => 'Azul', 'residuos' => 'Papel y carton'],
                        ['color' => 'bg-green-600', 'nombre' => 'Verde', 'residuos' => 'Vidrio'],
                        ['color' => 'bg-amber-700', 'nombre' => 'Marron', 'residuos' => 'Organico'],
                    ];
                    foreach ($contenedores_info as $cont):
                    ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                            <div class="w-8 h-8 rounded-lg <?php echo $cont['color']; ?>"></div>
                            <div>
                                <p class="font-bold text-gray-900 text-sm"><?php echo esc_html($cont['nombre']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($cont['residuos']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mapa -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="h-48 bg-gray-200 flex items-center justify-center">
                    <div class="text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <p class="text-sm">Ubicacion en mapa</p>
                    </div>
                </div>
                <div class="p-4">
                    <a href="#" class="flex items-center justify-center gap-2 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Como llegar
                    </a>
                </div>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white rounded-xl p-5 shadow-md">
                <h3 class="font-bold text-gray-900 mb-2">Horario de acceso</h3>
                <p class="text-gray-600"><?php echo esc_html($horario); ?></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-md">
                <h3 class="font-bold text-gray-900 mb-2">Servicios especiales</h3>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Aceite usado</span>
                    <span class="px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Pilas</span>
                    <span class="px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Ropa</span>
                </div>
            </div>
        </div>

        <!-- Guia de reciclaje -->
        <div class="bg-white rounded-2xl p-6 shadow-md mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Guia Rapida de Reciclaje</h2>
            <div class="prose max-w-none text-gray-700">
                <ul class="space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="w-4 h-4 rounded bg-yellow-400 mt-1 flex-shrink-0"></span>
                        <span><strong>Contenedor amarillo:</strong> Envases de plastico, latas, briks, tapones...</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-4 h-4 rounded bg-blue-500 mt-1 flex-shrink-0"></span>
                        <span><strong>Contenedor azul:</strong> Papel, carton, periodicos, revistas...</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-4 h-4 rounded bg-green-600 mt-1 flex-shrink-0"></span>
                        <span><strong>Contenedor verde:</strong> Botellas y tarros de vidrio (sin tapones)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-4 h-4 rounded bg-amber-700 mt-1 flex-shrink-0"></span>
                        <span><strong>Contenedor marron:</strong> Restos de comida, posos de cafe, cascaras...</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
