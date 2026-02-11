<?php
/**
 * Template: Sugerencias de Conexión
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Personas que Puedes Conocer';
$descripcion = $args['descripcion'] ?? 'Amplía tu red conectando con vecinos con intereses similares';
$limite_sugerencias = $args['limite_sugerencias'] ?? 12;
$mostrar_intereses = $args['mostrar_intereses'] ?? true;

// Sugerencias de conexión con intereses similares
$sugerencias_conexion = [
    [
        'id' => 1,
        'nombre' => 'Laura Fernández',
        'avatar' => 'https://i.pravatar.cc/150?img=15',
        'ocupacion' => 'Ecologista y Activista',
        'ubicacion' => 'Barrio Centro',
        'amigos_comunes' => 7,
        'intereses' => ['Ecología', 'Huertos', 'Sostenibilidad'],
        'estado_conexion' => 'sugerir', // sugerir, seguido, amigo
        'compatibilidad' => 92,
    ],
    [
        'id' => 2,
        'nombre' => 'Carlos Mendoza',
        'avatar' => 'https://i.pravatar.cc/150?img=42',
        'ocupacion' => 'Instructor de Yoga',
        'ubicacion' => 'Barrio Sur',
        'amigos_comunes' => 5,
        'intereses' => ['Bienestar', 'Meditación', 'Deporte'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 85,
    ],
    [
        'id' => 3,
        'nombre' => 'Sofía García López',
        'avatar' => 'https://i.pravatar.cc/150?img=28',
        'ocupacion' => 'Chef Pastelera',
        'ubicacion' => 'Barrio Este',
        'amigos_comunes' => 12,
        'intereses' => ['Cocina', 'Repostería', 'Gastronomía'],
        'estado_conexion' => 'seguido',
        'compatibilidad' => 78,
    ],
    [
        'id' => 4,
        'nombre' => 'David Torres',
        'avatar' => 'https://i.pravatar.cc/150?img=63',
        'ocupacion' => 'Programador e Instructor TIC',
        'ubicacion' => 'Barrio Centro',
        'amigos_comunes' => 4,
        'intereses' => ['Tecnología', 'Programación', 'Educación'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 88,
    ],
    [
        'id' => 5,
        'nombre' => 'Marta Jiménez',
        'avatar' => 'https://i.pravatar.cc/150?img=35',
        'ocupacion' => 'Voluntaria Comunitaria',
        'ubicacion' => 'Barrio Oeste',
        'amigos_comunes' => 9,
        'intereses' => ['Voluntariado', 'Solidaridad', 'Comunidad'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 95,
    ],
    [
        'id' => 6,
        'nombre' => 'Roberto Sánchez',
        'avatar' => 'https://i.pravatar.cc/150?img=51',
        'ocupacion' => 'Fotógrafo Profesional',
        'ubicacion' => 'Barrio Centro',
        'amigos_comunes' => 6,
        'intereses' => ['Fotografía', 'Arte', 'Cultura'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 82,
    ],
    [
        'id' => 7,
        'nombre' => 'Elena Carrasco',
        'avatar' => 'https://i.pravatar.cc/150?img=18',
        'ocupacion' => 'Diseñadora Gráfica',
        'ubicacion' => 'Barrio Sur',
        'amigos_comunes' => 8,
        'intereses' => ['Diseño', 'Marketing', 'Creatividad'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 79,
    ],
    [
        'id' => 8,
        'nombre' => 'Miguel Ángel Ruiz',
        'avatar' => 'https://i.pravatar.cc/150?img=47',
        'ocupacion' => 'Mecánico y Emprendedor',
        'ubicacion' => 'Barrio Este',
        'amigos_comunes' => 3,
        'intereses' => ['Mecánica', 'Emprendimiento', 'Negocios'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 71,
    ],
    [
        'id' => 9,
        'nombre' => 'Patricia Vázquez',
        'avatar' => 'https://i.pravatar.cc/150?img=24',
        'ocupacion' => 'Escritora y Periodista',
        'ubicacion' => 'Barrio Centro',
        'amigos_comunes' => 11,
        'intereses' => ['Literatura', 'Escritura', 'Periodismo'],
        'estado_conexion' => 'amigo',
        'compatibilidad' => 90,
    ],
    [
        'id' => 10,
        'nombre' => 'Álvaro Castillo',
        'avatar' => 'https://i.pravatar.cc/150?img=58',
        'ocupacion' => 'Entrenador Personal',
        'ubicacion' => 'Barrio Oeste',
        'amigos_comunes' => 7,
        'intereses' => ['Fitness', 'Deporte', 'Salud'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 84,
    ],
    [
        'id' => 11,
        'nombre' => 'Natalia Gómez',
        'avatar' => 'https://i.pravatar.cc/150?img=31',
        'ocupacion' => 'Terapeuta Ocupacional',
        'ubicacion' => 'Barrio Sur',
        'amigos_comunes' => 5,
        'intereses' => ['Salud', 'Bienestar', 'Educación'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 81,
    ],
    [
        'id' => 12,
        'nombre' => 'Fernando López',
        'avatar' => 'https://i.pravatar.cc/150?img=65',
        'ocupacion' => 'Maestro Jubilado',
        'ubicacion' => 'Barrio Este',
        'amigos_comunes' => 13,
        'intereses' => ['Educación', 'Mentoring', 'Sabiduría'],
        'estado_conexion' => 'sugerir',
        'compatibilidad' => 87,
    ],
];

// Filtrar por límite
$sugerencias_mostradas = array_slice($sugerencias_conexion, 0, $limite_sugerencias);

// Ordenar por compatibilidad
usort($sugerencias_mostradas, function($a, $b) {
    return $b['compatibilidad'] - $a['compatibilidad'];
});
?>

<section class="flavor-component py-16 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #a855f7 0%, #d946ef 100%); color: white;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m0 0l-2-1m2 1v2.5M14 4l-2 1m0 0l-2-1m2 1v2.5"/>
                    </svg>
                    <?php echo esc_html__('Red de Conexiones', 'flavor-chat-ia'); ?>
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    <?php echo esc_html($descripcion); ?>
                </p>
            </div>

            <!-- Sugerencias Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php foreach ($sugerencias_mostradas as $sugerencia): ?>
                    <div class="flavor-suggestion-card bg-white rounded-2xl overflow-hidden shadow-lg border border-gray-100 hover:shadow-2xl hover:border-purple-200 transition-all duration-300 group">
                        <!-- Banner Superior -->
                        <div class="h-20 bg-gradient-to-r from-purple-400 via-pink-400 to-rose-400 relative overflow-hidden">
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full -top-20 -left-20"></div>
                                <div class="absolute bottom-0 right-0 w-40 h-40 bg-white rounded-full -bottom-20 -right-20"></div>
                            </div>
                            <!-- Indicador de Compatibilidad -->
                            <div class="absolute top-4 right-4 flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-white bg-black/40 backdrop-blur-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <?php echo esc_html($sugerencia['compatibilidad']); ?>%
                            </div>
                        </div>

                        <!-- Avatar -->
                        <div class="px-6 pt-0 pb-4">
                            <div class="flex justify-center -mt-10 mb-4">
                                <img src="<?php echo esc_url($sugerencia['avatar']); ?>" alt="<?php echo esc_attr($sugerencia['nombre']); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                            </div>

                            <!-- Información Principal -->
                            <div class="text-center mb-3">
                                <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">
                                    <?php echo esc_html($sugerencia['nombre']); ?>
                                </h3>
                                <p class="text-sm text-gray-600 font-medium mb-1">
                                    <?php echo esc_html($sugerencia['ocupacion']); ?>
                                </p>
                                <div class="flex items-center justify-center gap-1 text-xs text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <?php echo esc_html($sugerencia['ubicacion']); ?>
                                </div>
                            </div>

                            <!-- Amigos en Común -->
                            <div class="bg-purple-50 rounded-lg p-3 mb-4 text-center">
                                <p class="text-xs text-gray-600 mb-1">
                                    <?php echo esc_html__('Amigos en común', 'flavor-chat-ia'); ?>
                                </p>
                                <div class="flex items-center justify-center gap-2">
                                    <div class="flex -space-x-2">
                                        <?php for ($i = 0; $i < min(3, $sugerencia['amigos_comunes']); $i++): ?>
                                            <img src="https://i.pravatar.cc/150?img=<?php echo $i + 10; ?>" alt="" class="w-6 h-6 rounded-full object-cover border-2 border-white">
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">
                                        <?php echo esc_html($sugerencia['amigos_comunes']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Intereses -->
                            <?php if ($mostrar_intereses && !empty($sugerencia['intereses'])): ?>
                                <div class="mb-4">
                                    <p class="text-xs text-gray-600 font-medium mb-2">
                                        <?php echo esc_html__('Intereses', 'flavor-chat-ia'); ?>
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($sugerencia['intereses'] as $interes): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium text-purple-600 bg-purple-100">
                                                <?php echo esc_html($interes); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Botón de Acción -->
                            <div class="pt-4 border-t border-gray-100">
                                <?php if ($sugerencia['estado_conexion'] === 'sugerir'): ?>
                                    <button class="w-full py-2 px-4 rounded-lg font-semibold transition-all duration-300 text-white hover:shadow-lg" style="background: linear-gradient(135deg, #a855f7 0%, #d946ef 100%);">
                                        <?php echo esc_html__('Conectar', 'flavor-chat-ia'); ?>
                                    </button>
                                    <button class="w-full mt-2 py-2 px-4 rounded-lg font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                                        <?php echo esc_html__('Descartar', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php elseif ($sugerencia['estado_conexion'] === 'seguido'): ?>
                                    <button class="w-full py-2 px-4 rounded-lg font-semibold text-gray-600 border border-purple-300 bg-purple-50 hover:bg-purple-100 transition-colors" disabled>
                                        <?php echo esc_html__('Siguiendo', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php else: ?>
                                    <button class="w-full py-2 px-4 rounded-lg font-semibold text-gray-600 border border-gray-200 bg-gray-50 hover:bg-gray-100 transition-colors" disabled>
                                        <?php echo esc_html__('Conectado', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sección de Filtros y Acciones Adicionales -->
            <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Búsqueda por Interés -->
                    <div class="flavor-filter-section">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                            <?php echo esc_html__('Filtrar por Intereses', 'flavor-chat-ia'); ?>
                        </h3>
                        <div class="space-y-2">
                            <?php $intereses_principales = ['Ecología', 'Deporte', 'Cocina', 'Tecnología', 'Arte']; ?>
                            <?php foreach ($intereses_principales as $interes_filtro): ?>
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors">
                                    <input type="checkbox" class="w-4 h-4 rounded text-purple-600 cursor-pointer" value="<?php echo esc_attr($interes_filtro); ?>">
                                    <span class="text-gray-700"><?php echo esc_html($interes_filtro); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Estadísticas de Conexiones -->
                    <div class="flavor-stats-section">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <?php echo esc_html__('Tu Red', 'flavor-chat-ia'); ?>
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                <span class="text-gray-600"><?php echo esc_html__('Conexiones', 'flavor-chat-ia'); ?></span>
                                <span class="font-bold text-lg text-blue-600">247</span>
                            </div>
                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                <span class="text-gray-600"><?php echo esc_html__('Siguiendo', 'flavor-chat-ia'); ?></span>
                                <span class="font-bold text-lg text-purple-600">89</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600"><?php echo esc_html__('Seguidores', 'flavor-chat-ia'); ?></span>
                                <span class="font-bold text-lg text-green-600">312</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recomendaciones -->
                    <div class="flavor-recommendations-section">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <?php echo esc_html__('Consejos', 'flavor-chat-ia'); ?>
                        </h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex gap-2">
                                <span class="text-purple-600 font-bold">•</span>
                                <span><?php echo esc_html__('Completa tu perfil para mejores sugerencias', 'flavor-chat-ia'); ?></span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-purple-600 font-bold">•</span>
                                <span><?php echo esc_html__('Indica tus intereses para conexiones afines', 'flavor-chat-ia'); ?></span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-purple-600 font-bold">•</span>
                                <span><?php echo esc_html__('Participa en grupos de tu interés', 'flavor-chat-ia'); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
