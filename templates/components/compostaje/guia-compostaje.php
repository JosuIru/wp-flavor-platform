<?php
/**
 * Template: Guía de Compostaje
 * @package FlavorChatIA
 *
 * @var string $titulo
 * @var string $descripcion
 * @var bool $mostrar_descarga
 */
?>

<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Encabezado -->
        <div class="text-center mb-16">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h2 class="text-4xl font-bold mb-4" style="color: #2D5016;">
                <?php echo esc_html($titulo ?? 'Guía de Compostaje'); ?>
            </h2>
            <p class="text-lg" style="color: #57534e; max-width: 700px; margin: 0 auto;">
                <?php echo esc_html($descripcion ?? 'Aprende qué materiales puedes compostar y cuáles debes evitar'); ?>
            </p>
            <div class="w-20 h-1 mx-auto mt-6 rounded-full" style="background: #6B4423;"></div>
        </div>

        <!-- Contenido Principal -->
        <div class="max-w-6xl mx-auto">
            <!-- Sección: Qué SÍ compostar -->
            <div class="mb-12">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: #dcfce7;">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold" style="color: #16a34a;">Qué SÍ Compostar</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Restos de Frutas y Verduras -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-2" style="border-color: #16a34a;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #2D5016;">Frutas y Verduras</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Restos de frutas y verduras crudas</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Pieles, cáscaras y semillas</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Restos de ensaladas sin aliñar</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Posos de Café -->
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-6 border-2" style="border-color: #d97706;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #2D5016;">Café e Infusiones</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Posos de café y filtros</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Bolsitas de té e infusiones</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Restos de hierbas aromáticas</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Cáscaras de Huevo -->
                    <div class="bg-gradient-to-br from-stone-50 to-neutral-50 rounded-xl p-6 border-2" style="border-color: #78716c;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #2D5016;">Cáscaras de Huevo</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Cáscaras trituradas (mejor)</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Aportan calcio al compost</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Ayudan a regular el pH</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Papel y Cartón -->
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-6 border-2" style="border-color: #ea580c;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #2D5016;">Papel y Cartón</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Servilletas y papel de cocina usados</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Cartón troceado (sin tinta)</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Cajas de huevos de cartón</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Qué NO compostar -->
            <div class="mb-12">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: #fee2e2;">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold" style="color: #dc2626;">Qué NO Compostar</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Carnes y Pescados -->
                    <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-xl p-6 border-2" style="border-color: #dc2626;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #991b1b;">Proteínas Animales</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Carnes, pescados y mariscos</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Huesos y espinas</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Atraen plagas y malos olores</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Aceites y Grasas -->
                    <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl p-6 border-2" style="border-color: #d97706;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #78350f;">Aceites y Grasas</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Aceites vegetales o de origen animal</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Alimentos muy grasos o fritos</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Impiden la aireación del compost</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Lácteos -->
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border-2" style="border-color: #0891b2;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #164e63;">Productos Lácteos</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Leche, yogur, queso</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Mantequilla y nata</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Generan malos olores</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Otros Residuos -->
                    <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-6 border-2" style="border-color: #64748b;">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-12 h-12 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg mb-2" style="color: #334155;">Otros No Compostables</h4>
                                <ul class="space-y-1 text-sm" style="color: #57534e;">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Plásticos, metales, vidrio</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Excrementos de mascotas</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Cenizas de carbón o madera tratada</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consejos Adicionales -->
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-8 border-2" style="border-color: #16a34a;">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-10 h-10 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <h3 class="text-2xl font-bold" style="color: #2D5016;">Consejos para un Buen Compostaje</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: #16a34a;">
                            <span class="text-white font-bold">1</span>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-1" style="color: #2D5016;">Trocea los residuos</h5>
                            <p class="text-sm" style="color: #57534e;">Los trozos pequeños se descomponen más rápido</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: #16a34a;">
                            <span class="text-white font-bold">2</span>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-1" style="color: #2D5016;">Equilibra húmedo y seco</h5>
                            <p class="text-sm" style="color: #57534e;">Alterna capas de restos húmedos con secos</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: #16a34a;">
                            <span class="text-white font-bold">3</span>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-1" style="color: #2D5016;">Airea regularmente</h5>
                            <p class="text-sm" style="color: #57534e;">Remueve el compost para oxigenarlo</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: #16a34a;">
                            <span class="text-white font-bold">4</span>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-1" style="color: #2D5016;">Controla la humedad</h5>
                            <p class="text-sm" style="color: #57534e;">Debe estar húmedo pero no encharcado</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($mostrar_descarga)): ?>
            <!-- Descarga de Guía -->
            <div class="mt-12 text-center">
                <div class="inline-block bg-white rounded-xl shadow-lg p-8 border-2" style="border-color: #6B4423;">
                    <svg class="w-16 h-16 mx-auto mb-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h4 class="text-xl font-bold mb-2" style="color: #2D5016;">Descarga la Guía Completa</h4>
                    <p class="mb-6" style="color: #57534e;">Guía en PDF con toda la información sobre compostaje</p>
                    <button class="px-8 py-3 rounded-lg font-semibold text-white transition-all duration-300 transform hover:scale-105" style="background: #6B4423;">
                        Descargar Guía PDF
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
