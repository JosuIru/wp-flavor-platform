<?php
/**
 * Template: Dashboard de Estadisticas de Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Estadisticas de la Biblioteca';
$subtitulo = $subtitulo ?? 'Resumen del estado actual de nuestra coleccion';

$stats = [
    ['numero' => '3,500', 'texto' => 'Total Libros', 'color' => 'blue', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'],
    ['numero' => '245', 'texto' => 'Prestamos Activos', 'color' => 'indigo', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'],
    ['numero' => '850', 'texto' => 'Usuarios', 'color' => 'purple', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'],
    ['numero' => '3,120', 'texto' => 'Disponibles', 'color' => 'green', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    ['numero' => '45', 'texto' => 'Nuevos Este Mes', 'color' => 'amber', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>'],
    ['numero' => '38', 'texto' => 'Reservas', 'color' => 'rose', 'icono' => '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>'],
];

$libros_populares = [
    ['titulo' => 'Cien Anos de Soledad', 'autor' => 'Gabriel Garcia Marquez', 'prestamos' => 156, 'disponible' => true],
    ['titulo' => 'El Principito', 'autor' => 'Antoine de Saint-Exupery', 'prestamos' => 142, 'disponible' => true],
    ['titulo' => '1984', 'autor' => 'George Orwell', 'prestamos' => 128, 'disponible' => false],
    ['titulo' => 'Sapiens', 'autor' => 'Yuval Noah Harari', 'prestamos' => 115, 'disponible' => true],
    ['titulo' => 'Harry Potter', 'autor' => 'J.K. Rowling', 'prestamos' => 108, 'disponible' => false],
];

$actividad = [
    ['accion' => 'prestamo', 'libro' => 'Don Quijote', 'usuario' => 'Maria G.', 'tiempo' => 'Hace 5 min'],
    ['accion' => 'devolucion', 'libro' => 'La Sombra del Viento', 'usuario' => 'Carlos R.', 'tiempo' => 'Hace 15 min'],
    ['accion' => 'reserva', 'libro' => 'Rayuela', 'usuario' => 'Ana L.', 'tiempo' => 'Hace 32 min'],
    ['accion' => 'nuevo', 'libro' => 'El Alquimista', 'usuario' => 'Sistema', 'tiempo' => 'Hace 1 hora'],
];
?>

<section class="flavor-component py-12 md:py-16 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">

        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-sm font-medium mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Panel de Control
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3"><?php echo esc_html($titulo); ?></h2>
            <p class="text-lg text-gray-600"><?php echo esc_html($subtitulo); ?></p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-10">
            <?php foreach ($stats as $stat): ?>
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-<?php echo $stat['color']; ?>-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 bg-gradient-to-br from-<?php echo $stat['color']; ?>-500 to-<?php echo $stat['color']; ?>-600 rounded-xl">
                            <?php echo $stat['icono']; ?>
                        </div>
                    </div>
                    <div class="text-2xl md:text-3xl font-bold text-gray-900 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo esc_html($stat['texto']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Libros Populares -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Libros Mas Populares</h3>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($libros_populares as $i => $libro): ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full <?php echo $i < 3 ? 'bg-gradient-to-br from-amber-400 to-orange-500 text-white' : 'bg-gray-100 text-gray-600'; ?> font-bold text-sm">
                                    <?php echo $i + 1; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 truncate group-hover:text-indigo-600 transition-colors"><?php echo esc_html($libro['titulo']); ?></h4>
                                    <p class="text-sm text-gray-500 truncate"><?php echo esc_html($libro['autor']); ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900"><?php echo esc_html($libro['prestamos']); ?> prestamos</div>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?php echo $libro['disponible'] ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>">
                                        <?php echo $libro['disponible'] ? 'Disponible' : 'Prestado'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Actividad Reciente</h3>
                        </div>
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <?php
                    $colores_accion = [
                        'prestamo' => 'blue',
                        'devolucion' => 'green',
                        'reserva' => 'amber',
                        'nuevo' => 'purple'
                    ];
                    $etiquetas = [
                        'prestamo' => 'Prestamo',
                        'devolucion' => 'Devolucion',
                        'reserva' => 'Reserva',
                        'nuevo' => 'Nuevo libro'
                    ];
                    foreach ($actividad as $item):
                        $color = $colores_accion[$item['accion']] ?? 'gray';
                    ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="p-2 bg-<?php echo $color; ?>-100 rounded-xl">
                                    <svg class="w-5 h-5 text-<?php echo $color; ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700">
                                            <?php echo esc_html($etiquetas[$item['accion']]); ?>
                                        </span>
                                        <span class="text-xs text-gray-400"><?php echo esc_html($item['tiempo']); ?></span>
                                    </div>
                                    <p class="text-sm text-gray-900"><span class="font-medium"><?php echo esc_html($item['libro']); ?></span></p>
                                    <p class="text-xs text-gray-500">por <?php echo esc_html($item['usuario']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
