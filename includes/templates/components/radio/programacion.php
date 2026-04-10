<?php
/**
 * Template: Radio Programming Schedule
 *
 * Weekly programming schedule for community radio
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Radio
 */

defined('ABSPATH') || exit;

// Default values
$titulo_seccion = $args['titulo_seccion'] ?? 'Programación Semanal';
$descripcion_seccion = $args['descripcion_seccion'] ?? 'Descubre todos los programas de nuestra radio comunitaria. ¡Encuentra tu espacio favorito!';
$programas = $args['programas'] ?? [];
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$mostrar_filtro_dias = $args['mostrar_filtro_dias'] ?? true;
$color_principal = $args['color_principal'] ?? 'purple';

// Default sample data if empty
if (empty($programas)) {
    $programas = [
        [
            'nombre' => 'Buenos Días Comunidad',
            'descripcion' => 'Despierta con noticias locales y música alegre',
            'locutor' => 'María González',
            'horario' => '07:00 - 09:00',
            'dias' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
            'categoria' => 'Magazín',
            'imagen' => ''
        ],
        [
            'nombre' => 'Música Tradicional',
            'descripcion' => 'Los mejores sonidos de nuestra tierra',
            'locutor' => 'Carlos Ruiz',
            'horario' => '09:00 - 11:00',
            'dias' => ['Lunes', 'Miércoles', 'Viernes'],
            'categoria' => 'Musical',
            'imagen' => ''
        ],
        [
            'nombre' => 'Conexión Joven',
            'descripcion' => 'Programas por y para los jóvenes de la comunidad',
            'locutor' => 'Ana Martínez',
            'horario' => '16:00 - 18:00',
            'dias' => ['Martes', 'Jueves'],
            'categoria' => 'Juvenil',
            'imagen' => ''
        ],
        [
            'nombre' => 'Cultura en Vivo',
            'descripcion' => 'Entrevistas, eventos culturales y tradiciones',
            'locutor' => 'Luis Fernández',
            'horario' => '19:00 - 21:00',
            'dias' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
            'categoria' => 'Cultural',
            'imagen' => ''
        ],
        [
            'nombre' => 'Deportes Comunitarios',
            'descripcion' => 'Todo el deporte local y regional',
            'locutor' => 'Pedro Sánchez',
            'horario' => '18:00 - 19:00',
            'dias' => ['Sábado', 'Domingo'],
            'categoria' => 'Deportes',
            'imagen' => ''
        ],
        [
            'nombre' => 'Noches de Jazz',
            'descripcion' => 'La mejor selección de jazz y música suave',
            'locutor' => 'Laura Torres',
            'horario' => '21:00 - 23:00',
            'dias' => ['Viernes', 'Sábado'],
            'categoria' => 'Musical',
            'imagen' => ''
        ],
    ];
}

// Category colors
$colores_categoria = [
    'Magazín' => 'blue',
    'Musical' => 'pink',
    'Juvenil' => 'green',
    'Cultural' => 'purple',
    'Deportes' => 'orange',
    'Informativo' => 'red',
    'Entretenimiento' => 'yellow'
];
?>

<section class="py-12 md:py-16 bg-white">
    <div class="container mx-auto px-4">
        <!-- Section Header -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600">
                <?php echo esc_html($descripcion_seccion); ?>
            </p>
        </div>

        <?php if ($mostrar_filtro_dias): ?>
            <!-- Day Filter -->
            <div class="mb-8 overflow-x-auto pb-4">
                <div class="flex justify-center gap-2 min-w-max mx-auto">
                    <button class="dia-filter-btn px-6 py-2.5 bg-<?php echo esc_attr($color_principal); ?>-600 text-white font-semibold rounded-full hover:bg-<?php echo esc_attr($color_principal); ?>-700 transition-colors duration-200 active"
                            data-dia="todos">
                        Todos
                    </button>
                    <?php foreach ($dias_semana as $dia): ?>
                        <button class="dia-filter-btn px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-full hover:bg-gray-200 transition-colors duration-200"
                                data-dia="<?php echo esc_attr($dia); ?>">
                            <?php echo esc_html($dia); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Programs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
            <?php foreach ($programas as $index => $programa): ?>
                <?php
                $nombre_programa = $programa['nombre'] ?? 'Programa sin nombre';
                $descripcion_programa = $programa['descripcion'] ?? '';
                $locutor_programa = $programa['locutor'] ?? '';
                $horario_programa = $programa['horario'] ?? '';
                $dias_programa = $programa['dias'] ?? [];
                $categoria_programa = $programa['categoria'] ?? 'General';
                $imagen_programa = $programa['imagen'] ?? '';
                $color_categoria = $colores_categoria[$categoria_programa] ?? 'gray';
                $dias_data = implode(',', $dias_programa);
                ?>

                <article class="programa-card bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 group hover:-translate-y-1"
                         data-dias="<?php echo esc_attr($dias_data); ?>">
                    <!-- Image/Icon Header -->
                    <div class="relative h-48 bg-gradient-to-br from-<?php echo esc_attr($color_categoria); ?>-500 to-<?php echo esc_attr($color_categoria); ?>-700 overflow-hidden">
                        <?php if ($imagen_programa): ?>
                            <img src="<?php echo esc_url($imagen_programa); ?>"
                                 alt="<?php echo esc_attr($nombre_programa); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-24 h-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Category Badge -->
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-<?php echo esc_attr($color_categoria); ?>-700 text-xs font-bold rounded-full shadow-lg">
                                <?php echo esc_html($categoria_programa); ?>
                            </span>
                        </div>

                        <!-- Overlay gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Title -->
                        <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-<?php echo esc_attr($color_principal); ?>-600 transition-colors">
                            <?php echo esc_html($nombre_programa); ?>
                        </h3>

                        <!-- Description -->
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            <?php echo esc_html($descripcion_programa); ?>
                        </p>

                        <!-- Locutor -->
                        <?php if ($locutor_programa): ?>
                            <div class="flex items-center gap-2 text-gray-700 mb-3">
                                <svg class="w-4 h-4 flex-shrink-0 text-<?php echo esc_attr($color_categoria); ?>-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-sm font-medium"><?php echo esc_html($locutor_programa); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Schedule -->
                        <?php if ($horario_programa): ?>
                            <div class="flex items-center gap-2 text-gray-700 mb-4">
                                <svg class="w-4 h-4 flex-shrink-0 text-<?php echo esc_attr($color_categoria); ?>-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-semibold"><?php echo esc_html($horario_programa); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Days -->
                        <?php if (!empty($dias_programa)): ?>
                            <div class="flex flex-wrap gap-1.5 mb-4">
                                <?php foreach ($dias_programa as $dia): ?>
                                    <span class="px-2.5 py-1 bg-<?php echo esc_attr($color_categoria); ?>-50 text-<?php echo esc_attr($color_categoria); ?>-700 text-xs font-medium rounded-lg border border-<?php echo esc_attr($color_categoria); ?>-200">
                                        <?php echo esc_html(substr($dia, 0, 3)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Button -->
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-<?php echo esc_attr($color_categoria); ?>-500 to-<?php echo esc_attr($color_categoria); ?>-600 text-white font-semibold rounded-xl hover:from-<?php echo esc_attr($color_categoria); ?>-600 hover:to-<?php echo esc_attr($color_categoria); ?>-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <span>Escuchar ahora</span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden text-center py-16">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay programas disponibles</h3>
            <p class="text-gray-500">No se encontraron programas para el día seleccionado.</p>
        </div>

        <!-- Download Schedule CTA -->
        <div class="mt-12 text-center">
            <a href="#"
               class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-<?php echo esc_attr($color_principal); ?>-600 to-<?php echo esc_attr($color_principal); ?>-700 text-white font-semibold rounded-full hover:from-<?php echo esc_attr($color_principal); ?>-700 hover:to-<?php echo esc_attr($color_principal); ?>-800 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Descargar Programación Completa (PDF)</span>
            </a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.dia-filter-btn');
    const programCards = document.querySelectorAll('.programa-card');
    const emptyState = document.getElementById('emptyState');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            filterButtons.forEach(btn => {
                btn.classList.remove('active', 'bg-<?php echo esc_js($color_principal); ?>-600', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });

            this.classList.add('active', 'bg-<?php echo esc_js($color_principal); ?>-600', 'text-white');
            this.classList.remove('bg-gray-100', 'text-gray-700');

            // Filter programs
            const selectedDay = this.dataset.dia;
            let visibleCount = 0;

            programCards.forEach(card => {
                const cardDays = card.dataset.dias.split(',');

                if (selectedDay === 'todos' || cardDays.includes(selectedDay)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide empty state
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        });
    });
});
</script>
