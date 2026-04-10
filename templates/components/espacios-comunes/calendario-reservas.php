<?php
/**
 * Template: Calendario de Reservas de Espacios
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Calendario de Reservas';
$espacio_seleccionado = $espacio_seleccionado ?? 'Salon de Actos';

$horas = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
$dias = ['Lun 12', 'Mar 13', 'Mie 14', 'Jue 15', 'Vie 16', 'Sab 17', 'Dom 18'];

$reservas = [
    'Mar 13' => ['10:00' => 'Reunion Vecinos', '11:00' => 'Reunion Vecinos'],
    'Mie 14' => ['17:00' => 'Taller Ceramica', '18:00' => 'Taller Ceramica', '19:00' => 'Taller Ceramica'],
    'Jue 15' => ['12:00' => 'Conferencia', '13:00' => 'Conferencia'],
    'Sab 17' => ['10:00' => 'Evento Privado', '11:00' => 'Evento Privado', '12:00' => 'Evento Privado', '13:00' => 'Evento Privado'],
];

$espacios = ['Salon de Actos', 'Sala de Reuniones A', 'Sala de Reuniones B', 'Aula de Formacion', 'Cocina Comunitaria'];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <?php echo esc_html__('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
        </div>

        <!-- Selector de espacio y controles -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700"><?php echo esc_html__('Espacio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select class="px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 bg-white">
                    <?php foreach ($espacios as $espacio): ?>
                        <option <?php echo $espacio === $espacio_seleccionado ? 'selected' : ''; ?>><?php echo esc_html($espacio); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button class="p-2 rounded-lg bg-gray-100 hover:bg-rose-100 text-gray-600 hover:text-rose-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <span class="px-4 py-2 font-semibold text-gray-900"><?php echo esc_html__('Febrero 2024', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <button class="p-2 rounded-lg bg-gray-100 hover:bg-rose-100 text-gray-600 hover:text-rose-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="flex items-center justify-center gap-6 mb-6">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-green-100 border border-green-300"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-rose-200 border border-rose-300"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('Reservado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-gray-200 border border-gray-300"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <!-- Calendario -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-4 text-left text-sm font-semibold text-gray-600 border-b border-r border-gray-200"><?php echo esc_html__('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <?php foreach ($dias as $dia): ?>
                                <th class="p-4 text-center text-sm font-semibold text-gray-900 border-b border-r border-gray-200 last:border-r-0">
                                    <?php echo esc_html($dia); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas as $hora): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 text-sm font-medium text-gray-600 border-b border-r border-gray-200 bg-gray-50">
                                    <?php echo esc_html($hora); ?>
                                </td>
                                <?php foreach ($dias as $dia): ?>
                                    <?php
                                    $reservado = isset($reservas[$dia][$hora]);
                                    $nombreReserva = $reservado ? $reservas[$dia][$hora] : '';
                                    ?>
                                    <td class="p-2 border-b border-r border-gray-200 last:border-r-0">
                                        <?php if ($reservado): ?>
                                            <div class="p-2 rounded-lg bg-rose-100 border border-rose-200 text-center">
                                                <span class="text-xs font-medium text-rose-700"><?php echo esc_html($nombreReserva); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <button class="w-full p-2 rounded-lg bg-green-50 border border-green-200 hover:bg-green-100 transition-colors group">
                                                <span class="text-xs text-green-600 group-hover:font-medium"><?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info del espacio seleccionado -->
        <div class="mt-8 bg-rose-50 rounded-2xl p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1"><?php echo esc_html($espacio_seleccionado); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Capacidad: 100 personas · Precio: 50€/hora · Equipamiento: Proyector, Microfono, WiFi', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <a href="#nueva-reserva" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <?php echo esc_html__('Nueva Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>
