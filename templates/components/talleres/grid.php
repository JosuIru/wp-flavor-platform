<?php
/**
 * Template: Grid de Talleres
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Talleres Disponibles';
$descripcion = $args['descripcion'] ?? 'Aprende nuevas habilidades con instructores experimentados';
$mostrar_filtros = $args['mostrar_filtros'] ?? true;
$columnas = $args['columnas'] ?? 3; // 2, 3 o 4
$limite_talleres = $args['limite_talleres'] ?? 9;

// Datos de talleres
$talleres_disponibles = [
    [
        'id' => 1,
        'titulo' => 'Huerto Urbano Sostenible',
        'categoria' => 'Ecología',
        'color_categoria' => 'green',
        'icono_categoria' => 'leaf',
        'descripcion' => 'Aprende a crear y mantener tu propio huerto urbano en casa o balcón. Totalmente ecológico y sostenible.',
        'instructor' => 'Ana Verde',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=22',
        'instructor_rol' => 'Especialista Ambiental',
        'horario' => 'Sábados 10:00',
        'duracion' => '8 semanas',
        'plazas' => '12/15',
        'plazas_disponibles' => 3,
        'nivel' => 'Principiante',
        'precio' => 'Gratuito',
        'imagen_color' => 'from-green-500 to-emerald-600',
        'etiquetas' => ['Naturaleza', 'Sostenibilidad', 'Educación'],
        'fecha_inicio' => '15 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 2,
        'titulo' => 'Cocina Saludable y Deliciosa',
        'categoria' => 'Cocina',
        'color_categoria' => 'orange',
        'icono_categoria' => 'chef',
        'descripcion' => 'Recetas sencillas y nutritivas para toda la familia. Aprende técnicas básicas de cocina.',
        'instructor' => 'Laura García',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=19',
        'instructor_rol' => 'Chef Profesional',
        'horario' => 'Martes 18:00',
        'duracion' => '6 semanas',
        'plazas' => '10/12',
        'plazas_disponibles' => 2,
        'nivel' => 'Principiante',
        'precio' => '45€',
        'imagen_color' => 'from-orange-500 to-red-600',
        'etiquetas' => ['Nutrición', 'Cocina', 'Salud'],
        'fecha_inicio' => '18 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 3,
        'titulo' => 'Tecnología para Mayores',
        'categoria' => 'Tecnología',
        'color_categoria' => 'blue',
        'icono_categoria' => 'tech',
        'descripcion' => 'Aprende a usar smartphones, tablets y redes sociales. Ritmo adaptado y ameno.',
        'instructor' => 'Pedro García',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=54',
        'instructor_rol' => 'Instructor TIC',
        'horario' => 'Lunes 17:00',
        'duracion' => '8 semanas',
        'plazas' => '10/10',
        'plazas_disponibles' => 0,
        'nivel' => 'Principiante',
        'precio' => '30€',
        'imagen_color' => 'from-blue-500 to-cyan-600',
        'etiquetas' => ['Tecnología', 'Digital', 'Educación'],
        'fecha_inicio' => '12 de Febrero',
        'estado' => 'completo',
    ],
    [
        'id' => 4,
        'titulo' => 'Fotografía Digital Básica',
        'categoria' => 'Fotografía',
        'color_categoria' => 'purple',
        'icono_categoria' => 'camera',
        'descripcion' => 'Descubre el arte de la fotografía digital. Composición, luz y edición básica.',
        'instructor' => 'Roberto Soto',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=52',
        'instructor_rol' => 'Fotógrafo',
        'horario' => 'Jueves 19:00',
        'duracion' => '5 semanas',
        'plazas' => '8/10',
        'plazas_disponibles' => 2,
        'nivel' => 'Principiante',
        'precio' => '50€',
        'imagen_color' => 'from-purple-500 to-pink-600',
        'etiquetas' => ['Fotografía', 'Arte', 'Creatividad'],
        'fecha_inicio' => '20 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 5,
        'titulo' => 'Yoga y Meditación',
        'categoria' => 'Bienestar',
        'color_categoria' => 'indigo',
        'icono_categoria' => 'lotus',
        'descripcion' => 'Relájate y mejora tu bienestar físico y mental. Sesiones adaptadas a todos los niveles.',
        'instructor' => 'Marta Sánchez',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=26',
        'instructor_rol' => 'Instructora de Yoga',
        'horario' => 'Miércoles 18:30',
        'duracion' => 'Continuo',
        'plazas' => '15/20',
        'plazas_disponibles' => 5,
        'nivel' => 'Todos los niveles',
        'precio' => '40€/mes',
        'imagen_color' => 'from-indigo-500 to-purple-600',
        'etiquetas' => ['Bienestar', 'Salud', 'Meditación'],
        'fecha_inicio' => 'Próxima semana',
        'estado' => 'disponible',
    ],
    [
        'id' => 6,
        'titulo' => 'Bisutería Creativa',
        'categoria' => 'Manualidades',
        'color_categoria' => 'pink',
        'icono_categoria' => 'craft',
        'descripcion' => 'Crea tus propias joyas y accesorios únicos con materiales reciclados. Totalmente ecológico.',
        'instructor' => 'María Díaz',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=16',
        'instructor_rol' => 'Diseñadora',
        'horario' => 'Viernes 17:00',
        'duracion' => '4 semanas',
        'plazas' => '12/15',
        'plazas_disponibles' => 3,
        'nivel' => 'Principiante',
        'precio' => '35€',
        'imagen_color' => 'from-pink-500 to-rose-600',
        'etiquetas' => ['Creatividad', 'Reciclaje', 'Arte'],
        'fecha_inicio' => '22 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 7,
        'titulo' => 'Inglés Conversacional',
        'categoria' => 'Idiomas',
        'color_categoria' => 'red',
        'icono_categoria' => 'language',
        'descripcion' => 'Aprende inglés de forma práctica y amena. Enfoque en conversación y situaciones reales.',
        'instructor' => 'Sarah Johnson',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=30',
        'instructor_rol' => 'Docente Nativa',
        'horario' => 'Martes 19:00',
        'duracion' => '10 semanas',
        'plazas' => '10/12',
        'plazas_disponibles' => 2,
        'nivel' => 'Intermedio',
        'precio' => '55€',
        'imagen_color' => 'from-red-500 to-orange-600',
        'etiquetas' => ['Idiomas', 'Educación', 'Comunicación'],
        'fecha_inicio' => '17 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 8,
        'titulo' => 'Reparaciones del Hogar',
        'categoria' => 'Práctico',
        'color_categoria' => 'yellow',
        'icono_categoria' => 'tools',
        'descripcion' => 'Aprende a realizar reparaciones básicas en casa. Ahorra dinero y aprende habilidades útiles.',
        'instructor' => 'Carlos López',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=62',
        'instructor_rol' => 'Maestro Reparador',
        'horario' => 'Sábados 11:00',
        'duracion' => '6 semanas',
        'plazas' => '9/12',
        'plazas_disponibles' => 3,
        'nivel' => 'Principiante',
        'precio' => '40€',
        'imagen_color' => 'from-yellow-500 to-amber-600',
        'etiquetas' => ['Práctico', 'Habilidades', 'Ahorro'],
        'fecha_inicio' => '13 de Febrero',
        'estado' => 'disponible',
    ],
    [
        'id' => 9,
        'titulo' => 'Escritura Creativa',
        'categoria' => 'Creatividad',
        'color_categoria' => 'teal',
        'icono_categoria' => 'pen',
        'descripcion' => 'Desarrolla tu escritura creativa. Cuentos, poesía y técnicas narrativas con retroalimentación.',
        'instructor' => 'Elena Torres',
        'instructor_avatar' => 'https://i.pravatar.cc/150?img=27',
        'instructor_rol' => 'Escritora',
        'horario' => 'Jueves 18:00',
        'duracion' => '8 semanas',
        'plazas' => '8/10',
        'plazas_disponibles' => 2,
        'nivel' => 'Principiante',
        'precio' => '45€',
        'imagen_color' => 'from-teal-500 to-cyan-600',
        'etiquetas' => ['Literatura', 'Creatividad', 'Expresión'],
        'fecha_inicio' => '19 de Febrero',
        'estado' => 'disponible',
    ],
];

// Filtrar por límite
$talleres_mostrados = array_slice($talleres_disponibles, 0, $limite_talleres);

// Clase de grid según columnas
$clase_grid = match($columnas) {
    2 => 'grid-cols-1 md:grid-cols-2',
    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
};
?>

<section class="flavor-component py-16 bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    <?php echo esc_html($descripcion); ?>
                </p>
            </div>

            <!-- Filtros -->
            <?php if ($mostrar_filtros): ?>
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mb-12">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-1">
                            <label class="text-sm font-semibold text-gray-700 mb-2 block">
                                <?php echo esc_html__('Buscar talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <input type="text" placeholder="<?php echo esc_attr__('Nombre del taller, categoría...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700 mb-2 block">
                                <?php echo esc_html__('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <select class="px-4 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                <option value="popular"><?php echo esc_html__('Más Popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="nuevo"><?php echo esc_html__('Más Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="precio"><?php echo esc_html__('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Categorías -->
                    <div class="mt-6">
                        <p class="text-sm font-semibold text-gray-700 mb-3">
                            <?php echo esc_html__('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 rounded-full bg-blue-100 text-blue-700 font-medium hover:bg-blue-200 transition-colors">
                                <?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium hover:bg-gray-200 transition-colors">
                                <?php echo esc_html__('Ecología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium hover:bg-gray-200 transition-colors">
                                <?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium hover:bg-gray-200 transition-colors">
                                <?php echo esc_html__('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium hover:bg-gray-200 transition-colors">
                                <?php echo esc_html__('Bienestar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grid de Talleres -->
            <div class="grid <?php echo esc_attr($clase_grid); ?> gap-8 mb-12">
                <?php foreach ($talleres_mostrados as $taller): ?>
                    <div class="flavor-taller-card group bg-white rounded-2xl overflow-hidden shadow-lg border-2 border-gray-100 hover:shadow-2xl hover:border-blue-300 transition-all duration-300">
                        <!-- Imagen/Header -->
                        <div class="relative h-48 bg-gradient-to-br <?php echo esc_attr($taller['imagen_color']); ?> overflow-hidden">
                            <!-- Icono Decorativo -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-24 h-24 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>

                            <!-- Badge de Categoría -->
                            <div class="absolute top-4 right-4 px-3 py-1 bg-white rounded-full text-xs font-bold" style="color: var(--color-<?php echo esc_attr($taller['color_categoria']); ?>-600);">
                                <?php echo esc_html($taller['categoria']); ?>
                            </div>

                            <!-- Badge de Estado -->
                            <?php if ($taller['estado'] === 'completo'): ?>
                                <div class="absolute top-4 left-4 px-3 py-1 bg-red-500 rounded-full text-xs font-bold text-white">
                                    <?php echo esc_html__('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </div>
                            <?php else: ?>
                                <div class="absolute top-4 left-4 px-3 py-1 bg-green-500 rounded-full text-xs font-bold text-white animate-pulse">
                                    <?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Info Horario -->
                            <div class="absolute bottom-4 inset-x-4 bg-black/50 backdrop-blur-sm rounded-lg p-3">
                                <div class="flex items-center justify-between text-white text-sm">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <?php echo esc_html($taller['horario']); ?>
                                    </span>
                                    <span class="font-bold"><?php echo esc_html($taller['duracion']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div class="p-6">
                            <!-- Título -->
                            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-2">
                                <?php echo esc_html($taller['titulo']); ?>
                            </h3>

                            <!-- Descripción -->
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                <?php echo esc_html($taller['descripcion']); ?>
                            </p>

                            <!-- Instructor -->
                            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                                <img src="<?php echo esc_url($taller['instructor_avatar']); ?>" alt="<?php echo esc_attr($taller['instructor']); ?>" class="w-10 h-10 rounded-full object-cover">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo esc_html($taller['instructor']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo esc_html($taller['instructor_rol']); ?></p>
                                </div>
                            </div>

                            <!-- Etiquetas -->
                            <div class="mb-4">
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($taller['etiquetas'] as $etiqueta): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            <?php echo esc_html($etiqueta); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Info Plazas y Precio -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span><?php echo esc_html($taller['plazas']); ?></span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">
                                    <?php echo esc_html($taller['precio']); ?>
                                </span>
                            </div>

                            <!-- Botón Acción -->
                            <?php if ($taller['estado'] === 'completo'): ?>
                                <button class="w-full py-2 px-4 bg-gray-300 text-gray-600 font-semibold rounded-lg cursor-not-allowed" disabled>
                                    <?php echo esc_html__('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php else: ?>
                                <button class="w-full py-2 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all hover:shadow-lg">
                                    <?php echo esc_html__('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>

                            <!-- Nivel -->
                            <p class="text-xs text-gray-500 mt-3 text-center">
                                <span class="font-semibold"><?php echo esc_html__('Nivel:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php echo esc_html($taller['nivel']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Botón Ver Más -->
            <div class="text-center">
                <button class="px-8 py-3 rounded-full font-bold text-white shadow-lg hover:shadow-xl transition-all" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                    <?php echo esc_html__('Ver Más Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>
</section>
