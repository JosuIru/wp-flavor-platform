<?php
/**
 * Template: Calendario de Recogidas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Calendario de Recogidas';
$descripcion = $descripcion ?? 'Consulta los dias y horarios de recogida en tu zona';

$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

$recogidas = [
    'Lunes' => [
        ['tipo' => 'Organico', 'horario' => '21:00 - 23:00', 'color' => 'amber', 'icono' => '🍂'],
        ['tipo' => 'Resto', 'horario' => '21:00 - 23:00', 'color' => 'gray', 'icono' => '🗑️'],
    ],
    'Martes' => [
        ['tipo' => 'Papel/Carton', 'horario' => '21:00 - 23:00', 'color' => 'blue', 'icono' => '📦'],
    ],
    'Miercoles' => [
        ['tipo' => 'Organico', 'horario' => '21:00 - 23:00', 'color' => 'amber', 'icono' => '🍂'],
        ['tipo' => 'Envases', 'horario' => '21:00 - 23:00', 'color' => 'yellow', 'icono' => '🥤'],
    ],
    'Jueves' => [
        ['tipo' => 'Resto', 'horario' => '21:00 - 23:00', 'color' => 'gray', 'icono' => '🗑️'],
    ],
    'Viernes' => [
        ['tipo' => 'Organico', 'horario' => '21:00 - 23:00', 'color' => 'amber', 'icono' => '🍂'],
        ['tipo' => 'Vidrio', 'horario' => '21:00 - 23:00', 'color' => 'green', 'icono' => '🍾'],
    ],
    'Sabado' => [
        ['tipo' => 'Envases', 'horario' => '09:00 - 12:00', 'color' => 'yellow', 'icono' => '🥤'],
    ],
    'Domingo' => [],
];

$hoy = 'Miercoles'; // Simulado
?>

<section class="flavor-component py-16" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Calendario
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Selector de zona -->
        <div class="max-w-md mx-auto mb-10">
            <label class="block text-sm font-medium text-gray-700 mb-2">Selecciona tu zona</label>
            <select class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                <option>Centro - Zona 1</option>
                <option>Ensanche - Zona 2</option>
                <option>Norte - Zona 3</option>
                <option>Sur - Zona 4</option>
                <option>Este - Zona 5</option>
            </select>
        </div>

        <!-- Calendario semanal -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-12">
            <div class="grid grid-cols-7">
                <?php foreach ($dias_semana as $dia): ?>
                    <?php $esHoy = ($dia === $hoy); ?>
                    <div class="<?php echo $esHoy ? 'bg-emerald-500' : 'bg-gray-50'; ?> p-4 border-b border-r border-gray-200 last:border-r-0">
                        <div class="text-center">
                            <span class="block text-xs font-medium <?php echo $esHoy ? 'text-emerald-100' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo esc_html(substr($dia, 0, 3)); ?>
                            </span>
                            <?php if ($esHoy): ?>
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white text-emerald-600 font-bold text-sm mt-1">
                                    HOY
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="grid grid-cols-7 min-h-[200px]">
                <?php foreach ($dias_semana as $dia): ?>
                    <?php $esHoy = ($dia === $hoy); ?>
                    <div class="p-3 border-r border-gray-200 last:border-r-0 <?php echo $esHoy ? 'bg-emerald-50' : ''; ?>">
                        <?php if (!empty($recogidas[$dia])): ?>
                            <div class="space-y-2">
                                <?php foreach ($recogidas[$dia] as $recogida): ?>
                                    <div class="p-2 rounded-lg bg-<?php echo $recogida['color']; ?>-100 border border-<?php echo $recogida['color']; ?>-200">
                                        <span class="text-xl block text-center mb-1"><?php echo $recogida['icono']; ?></span>
                                        <p class="text-xs font-semibold text-center text-<?php echo $recogida['color']; ?>-800 truncate"><?php echo esc_html($recogida['tipo']); ?></p>
                                        <p class="text-xs text-center text-<?php echo $recogida['color']; ?>-600"><?php echo esc_html($recogida['horario']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="h-full flex items-center justify-center text-gray-400 text-xs">
                                Sin recogidas
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Proximas recogidas -->
        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-2xl mx-auto">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Proximas recogidas
            </h3>
            <div class="space-y-3">
                <?php
                $proximas = [
                    ['tipo' => 'Organico', 'cuando' => 'Hoy, 21:00', 'color' => 'amber', 'icono' => '🍂', 'urgente' => true],
                    ['tipo' => 'Envases', 'cuando' => 'Hoy, 21:00', 'color' => 'yellow', 'icono' => '🥤', 'urgente' => true],
                    ['tipo' => 'Resto', 'cuando' => 'Manana, 21:00', 'color' => 'gray', 'icono' => '🗑️', 'urgente' => false],
                    ['tipo' => 'Organico', 'cuando' => 'Viernes, 21:00', 'color' => 'amber', 'icono' => '🍂', 'urgente' => false],
                ];
                foreach ($proximas as $prox): ?>
                    <div class="flex items-center gap-4 p-3 rounded-xl <?php echo $prox['urgente'] ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50'; ?>">
                        <span class="text-2xl"><?php echo $prox['icono']; ?></span>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900"><?php echo esc_html($prox['tipo']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($prox['cuando']); ?></p>
                        </div>
                        <?php if ($prox['urgente']): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500 text-white animate-pulse">
                                PRONTO
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CTA Notificaciones -->
        <div class="text-center mt-12">
            <p class="text-gray-600 mb-4">Recibe recordatorios antes de cada recogida</p>
            <button class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span>Activar Notificaciones</span>
            </button>
        </div>
    </div>
</section>
