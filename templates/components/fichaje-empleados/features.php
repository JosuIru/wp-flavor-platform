<?php
/**
 * Template: Fichaje Empleados Features
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_features = $titulo_features ?? 'Control Horario Completo';

$funcionalidades_fichaje = $funcionalidades_fichaje ?? [
    [
        'titulo'      => 'Fichaje con geolocalizacion',
        'descripcion' => 'Verifica la ubicacion de tus empleados al fichar. Control de presencia desde cualquier lugar.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color'       => '#64748B',
    ],
    [
        'titulo'      => 'Control de pausas',
        'descripcion' => 'Registra los descansos y pausas de cada empleado. Transparencia total en los tiempos de trabajo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#475569',
    ],
    [
        'titulo'      => 'Informes automaticos',
        'descripcion' => 'Genera informes de horas trabajadas, ausencias y puntualidad de forma automatica.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'color'       => '#334155',
    ],
    [
        'titulo'      => 'Calendario laboral',
        'descripcion' => 'Configura festivos, turnos y horarios especiales. Vista de calendario para todo el equipo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'color'       => '#6B7280',
    ],
    [
        'titulo'      => 'Gestion de vacaciones',
        'descripcion' => 'Los empleados solicitan vacaciones y los responsables las aprueban o rechazan facilmente.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#94A3B8',
    ],
    [
        'titulo'      => 'Alertas de ausencia',
        'descripcion' => 'Recibe notificaciones cuando un empleado no ficha a su hora o acumula ausencias.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'color'       => '#9CA3AF',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3"><?php echo esc_html($titulo_features); ?></h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto"><?php echo esc_html__('Todas las herramientas para gestionar la asistencia de tu equipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($funcionalidades_fichaje as $funcionalidad_item) : ?>
                <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($funcionalidad_item['color']); ?>15;">
                        <svg class="w-6 h-6" style="color: <?php echo esc_attr($funcionalidad_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $funcionalidad_item['icono']; ?>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo esc_html($funcionalidad_item['titulo']); ?></h3>
                    <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($funcionalidad_item['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
