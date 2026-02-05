<?php
/**
 * Template: Categorías de Ayuda
 *
 * Muestra las diferentes categorías de ayuda disponibles en la red vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo_seccion = $data['titulo'] ?? 'Tipos de Ayuda Disponibles';
$subtitulo_seccion = $data['subtitulo'] ?? 'Descubre las diferentes formas en las que puedes ayudar o recibir ayuda de tus vecinos';
$categorias_personalizadas = $data['categorias'] ?? [];

// Categorías predefinidas
$categorias_predeterminadas = [
    [
        'nombre' => 'Transporte',
        'descripcion' => 'Acompañamiento a citas médicas, gestiones o desplazamientos',
        'icono' => 'car',
        'color' => 'blue',
        'ejemplos' => ['Ir al médico', 'Recoger niños', 'Gestiones oficiales']
    ],
    [
        'nombre' => 'Compras y Recados',
        'descripcion' => 'Ayuda con la compra semanal, farmacia y pequeños recados',
        'icono' => 'shopping',
        'color' => 'green',
        'ejemplos' => ['Compra semanal', 'Farmacia', 'Correos']
    ],
    [
        'nombre' => 'Acompañamiento',
        'descripcion' => 'Compañía para paseos, charlas o actividades sociales',
        'icono' => 'users',
        'color' => 'purple',
        'ejemplos' => ['Paseos', 'Charlar', 'Actividades']
    ],
    [
        'nombre' => 'Tecnología',
        'descripcion' => 'Ayuda con dispositivos, internet, apps y videollamadas',
        'icono' => 'tech',
        'color' => 'indigo',
        'ejemplos' => ['Configurar móvil', 'Videollamadas', 'Apps']
    ],
    [
        'nombre' => 'Gestiones Administrativas',
        'descripcion' => 'Apoyo con trámites, papeleos y documentación',
        'icono' => 'document',
        'color' => 'orange',
        'ejemplos' => ['Trámites online', 'Cita previa', 'Formularios']
    ],
    [
        'nombre' => 'Cuidados Ocasionales',
        'descripcion' => 'Atención temporal a personas mayores, niños o mascotas',
        'icono' => 'heart',
        'color' => 'rose',
        'ejemplos' => ['Acompañar mayores', 'Cuidar niños', 'Pasear mascotas']
    ]
];

$categorias_a_mostrar = !empty($categorias_personalizadas) ? $categorias_personalizadas : $categorias_predeterminadas;

// Mapeo de colores
$colores_mapeados = [
    'blue' => ['bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600', 'text' => 'text-blue-600', 'bg_light' => 'bg-blue-50', 'border' => 'border-blue-200'],
    'green' => ['bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600', 'text' => 'text-green-600', 'bg_light' => 'bg-green-50', 'border' => 'border-green-200'],
    'purple' => ['bg' => 'bg-purple-500', 'hover' => 'hover:bg-purple-600', 'text' => 'text-purple-600', 'bg_light' => 'bg-purple-50', 'border' => 'border-purple-200'],
    'indigo' => ['bg' => 'bg-indigo-500', 'hover' => 'hover:bg-indigo-600', 'text' => 'text-indigo-600', 'bg_light' => 'bg-indigo-50', 'border' => 'border-indigo-200'],
    'orange' => ['bg' => 'bg-orange-500', 'hover' => 'hover:bg-orange-600', 'text' => 'text-orange-600', 'bg_light' => 'bg-orange-50', 'border' => 'border-orange-200'],
    'rose' => ['bg' => 'bg-rose-500', 'hover' => 'hover:bg-rose-600', 'text' => 'text-rose-600', 'bg_light' => 'bg-rose-50', 'border' => 'border-rose-200']
];

// Mapeo de iconos
function obtener_icono_svg($tipo_icono) {
    $iconos_svg = [
        'car' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
        'shopping' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>',
        'tech' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
        'heart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>'
    ];
    return $iconos_svg[$tipo_icono] ?? $iconos_svg['heart'];
}
?>

<section class="py-12 sm:py-16 lg:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Encabezado de sección -->
        <div class="text-center mb-12 lg:mb-16">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-rose-100 rounded-full text-rose-700 text-sm font-medium mb-4">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
                Formas de Colaborar
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>
        </div>

        <!-- Grid de categorías -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php foreach ($categorias_a_mostrar as $categoria):
                $colores_categoria = $colores_mapeados[$categoria['color']] ?? $colores_mapeados['rose'];
                $icono_svg = obtener_icono_svg($categoria['icono']);
            ?>
                <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border-2 <?php echo esc_attr($colores_categoria['border']); ?> hover:border-opacity-50 transform hover:-translate-y-2">

                    <!-- Header con icono -->
                    <div class="p-6 <?php echo esc_attr($colores_categoria['bg_light']); ?> relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 <?php echo esc_attr($colores_categoria['bg']); ?> opacity-10 rounded-full transform translate-x-16 -translate-y-16"></div>

                        <div class="relative flex items-center gap-4">
                            <div class="w-14 h-14 <?php echo esc_attr($colores_categoria['bg']); ?> rounded-2xl flex items-center justify-center flex-shrink-0 transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $icono_svg; ?>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">
                                <?php echo esc_html($categoria['nombre']); ?>
                            </h3>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6 space-y-4">
                        <p class="text-gray-600 leading-relaxed">
                            <?php echo esc_html($categoria['descripcion']); ?>
                        </p>

                        <!-- Ejemplos -->
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                <svg class="w-4 h-4 <?php echo esc_attr($colores_categoria['text']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Ejemplos:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($categoria['ejemplos'] as $ejemplo): ?>
                                    <span class="px-3 py-1.5 <?php echo esc_attr($colores_categoria['bg_light']); ?> <?php echo esc_attr($colores_categoria['text']); ?> text-xs font-medium rounded-full border <?php echo esc_attr($colores_categoria['border']); ?>">
                                        <?php echo esc_html($ejemplo); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botón de acción -->
                        <button class="w-full mt-4 <?php echo esc_attr($colores_categoria['bg']); ?> <?php echo esc_attr($colores_categoria['hover']); ?> text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform group-hover:scale-105 flex items-center justify-center gap-2">
                            <span>Ver Solicitudes</span>
                            <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Estadísticas de impacto -->
        <div class="mt-16 bg-gradient-to-br from-rose-500 via-pink-600 to-red-600 rounded-3xl shadow-2xl p-8 sm:p-12 text-white relative overflow-hidden">
            <!-- Elementos decorativos -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

            <div class="relative">
                <div class="text-center mb-8">
                    <h3 class="text-3xl sm:text-4xl font-bold mb-3">Nuestro Impacto Comunitario</h3>
                    <p class="text-rose-100 text-lg">Juntos construimos una red de apoyo sólida y cercana</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 lg:gap-8">
                    <div class="text-center">
                        <div class="text-4xl sm:text-5xl font-bold mb-2">1,450+</div>
                        <div class="text-rose-100 text-sm sm:text-base">Ayudas realizadas</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl sm:text-5xl font-bold mb-2">280+</div>
                        <div class="text-rose-100 text-sm sm:text-base">Voluntarios activos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl sm:text-5xl font-bold mb-2">98%</div>
                        <div class="text-rose-100 text-sm sm:text-base">Satisfacción</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl sm:text-5xl font-bold mb-2">24h</div>
                        <div class="text-rose-100 text-sm sm:text-base">Respuesta media</div>
                    </div>
                </div>

                <div class="text-center mt-8">
                    <button class="inline-flex items-center gap-2 px-8 py-4 bg-white text-rose-600 font-semibold rounded-xl hover:bg-rose-50 transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Únete como Voluntario
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
