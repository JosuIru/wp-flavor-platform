<?php
/**
 * Frontend: Single Compostera
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$compostera = $compostera ?? [];
$nombre_compostera = $compostera['nombre'] ?? 'Compostera Comunitaria';
$ubicacion_compostera = $compostera['ubicacion'] ?? 'Barrio centro';
$descripcion_compostera = $compostera['descripcion'] ?? '';
$capacidad_compostera = $compostera['capacidad'] ?? 65;
$estado_compostera = $compostera['estado'] ?? 'activa';
$horario_compostera = $compostera['horario'] ?? '';
$normas_compostera = $compostera['normas'] ?? [];
$participantes_compostera = $compostera['participantes'] ?? [];
$composteras_cercanas = $compostera['cercanas'] ?? [];
?>

<div class="flavor-single compostaje">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-green-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-green-600">Compostaje</a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($nombre_compostera); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Header compostera -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo esc_html($nombre_compostera); ?></h1>
                        <?php
                        $colores_estado = [
                            'activa'         => 'bg-green-100 text-green-700',
                            'llena'          => 'bg-amber-100 text-amber-700',
                            'mantenimiento'  => 'bg-red-100 text-red-700',
                        ];
                        $clase_estado = $colores_estado[$estado_compostera] ?? $colores_estado['activa'];
                        ?>
                        <span class="px-3 py-1 rounded-full text-sm font-bold <?php echo esc_attr($clase_estado); ?>">
                            <?php echo esc_html(ucfirst($estado_compostera)); ?>
                        </span>
                    </div>

                    <!-- Ubicacion -->
                    <div class="flex items-center gap-2 text-gray-600 mb-4">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span><?php echo esc_html($ubicacion_compostera); ?></span>
                    </div>

                    <!-- Mapa placeholder -->
                    <div class="aspect-[16/9] rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center mb-6 overflow-hidden">
                        <div class="text-center">
                            <svg class="w-12 h-12 text-green-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <p class="text-green-400 text-sm">Mapa de ubicacion</p>
                        </div>
                    </div>

                    <!-- Descripcion -->
                    <div class="prose max-w-none text-gray-700">
                        <?php echo wp_kses_post($descripcion_compostera ?: '<p>Esta compostera comunitaria permite a los vecinos del barrio depositar sus residuos organicos para convertirlos en compost de calidad. El compost resultante se distribuye entre los participantes para uso en jardines y huertos urbanos.</p>'); ?>
                    </div>
                </div>

                <!-- Indicador de capacidad -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Capacidad actual</h2>
                    <div class="relative mb-4">
                        <div class="w-full h-6 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all <?php echo $capacidad_compostera > 80 ? 'bg-amber-500' : 'bg-green-500'; ?>"
                                 style="width: <?php echo esc_attr($capacidad_compostera); ?>%"></div>
                        </div>
                        <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 font-bold text-sm <?php echo $capacidad_compostera > 50 ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo esc_html($capacidad_compostera); ?>%
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        <?php if ($capacidad_compostera > 80): ?>
                            La compostera esta casi llena. Consulta la fecha de vaciado.
                        <?php else: ?>
                            Hay espacio disponible para depositar residuos organicos.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Horario y recogidas -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Horario</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-green-50">
                            <h3 class="font-bold text-green-700 mb-2">Deposito de residuos</h3>
                            <p class="text-sm text-gray-700"><?php echo esc_html($horario_compostera ?: 'Lunes a sabado: 8:00 - 20:00'); ?></p>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50">
                            <h3 class="font-bold text-emerald-700 mb-2">Recogida de compost</h3>
                            <p class="text-sm text-gray-700">Primer sabado de cada mes: 10:00 - 13:00</p>
                        </div>
                    </div>
                </div>

                <!-- Normas -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Normas de uso</h2>
                    <ul class="space-y-3">
                        <?php
                        $normas_defecto = [
                            'Solo depositar residuos organicos: restos de frutas, verduras, cascaras de huevo, posos de cafe',
                            'No depositar carne, pescado, lacteos ni aceites',
                            'Utilizar los cubos proporcionados para el deposito',
                            'Mantener limpia la zona de la compostera',
                            'Respetar los horarios establecidos',
                        ];
                        $lista_normas = !empty($normas_compostera) ? $normas_compostera : $normas_defecto;
                        foreach ($lista_normas as $norma):
                        ?>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-700 text-sm"><?php echo esc_html($norma); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Preview participantes -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Participantes</h2>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex -space-x-3">
                            <?php for ($indice_avatar = 1; $indice_avatar <= 5; $indice_avatar++): ?>
                                <img src="https://i.pravatar.cc/150?img=<?php echo $indice_avatar * 8; ?>" alt=""
                                     class="w-10 h-10 rounded-full border-2 border-white object-cover">
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm text-gray-500 ml-2">y 23 participantes mas</span>
                    </div>
                    <a href="#" class="text-green-600 font-medium text-sm hover:text-green-700">Ver todos los participantes</a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- CTA Unirse -->
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4 mb-6">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900">Participa en el compostaje</h3>
                        <p class="text-sm text-gray-500 mt-1">Reduce tu huella de carbono</p>
                    </div>
                    <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #22c55e 0%, #059669 100%);">
                        Unirse a Compostera
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-3">Registro gratuito</p>
                </div>

                <!-- Estadisticas de la compostera -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Estadisticas</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Participantes</span>
                            <span class="font-semibold text-green-600">28</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Kg este mes</span>
                            <span class="font-semibold">245 kg</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600 text-sm">Kg total</span>
                            <span class="font-semibold">1.2t</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-green-50">
                            <span class="text-gray-600 text-sm">CO2 evitado</span>
                            <span class="font-bold text-green-600">680 kg</span>
                        </div>
                    </div>
                </div>

                <!-- Composteras cercanas -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Composteras cercanas</h3>
                    <div class="space-y-3">
                        <a href="#" class="block p-3 rounded-xl hover:bg-green-50 transition-colors">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-gray-900 text-sm">Compostera Parque Norte</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Activa</span>
                            </div>
                            <p class="text-xs text-gray-500">450m - 45% capacidad</p>
                        </a>
                        <a href="#" class="block p-3 rounded-xl hover:bg-green-50 transition-colors">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-gray-900 text-sm">Compostera Plaza Mayor</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Llena</span>
                            </div>
                            <p class="text-xs text-gray-500">800m - 95% capacidad</p>
                        </a>
                        <a href="#" class="block p-3 rounded-xl hover:bg-green-50 transition-colors">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-gray-900 text-sm">Compostera Jardin Sur</p>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Activa</span>
                            </div>
                            <p class="text-xs text-gray-500">1.2km - 30% capacidad</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
