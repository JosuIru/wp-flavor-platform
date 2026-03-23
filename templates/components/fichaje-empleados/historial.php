<?php
/**
 * Template: Historial de Fichajes
 *
 * Tabla con los registros recientes de fichaje del empleado.
 * Columnas: fecha, entrada, salida, pausas y total de horas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_historial = $titulo_historial ?? 'Historial de Fichajes';
$url_solicitar_correccion = $url_solicitar_correccion ?? Flavor_Chat_Helpers::get_action_url('fichaje_empleados', 'solicitar-correccion');

$registros_fichaje = $registros_fichaje ?? [
    [
        'fecha'       => '29/01/2026',
        'entrada'     => '08:55',
        'salida'      => '17:05',
        'pausas'      => '1h 00m',
        'total_horas' => '7h 10m',
        'estado'      => 'completo',
    ],
    [
        'fecha'       => '28/01/2026',
        'entrada'     => '09:02',
        'salida'      => '17:30',
        'pausas'      => '0h 45m',
        'total_horas' => '7h 43m',
        'estado'      => 'completo',
    ],
    [
        'fecha'       => '27/01/2026',
        'entrada'     => '08:48',
        'salida'      => '16:50',
        'pausas'      => '1h 00m',
        'total_horas' => '7h 02m',
        'estado'      => 'completo',
    ],
    [
        'fecha'       => '24/01/2026',
        'entrada'     => '09:10',
        'salida'      => '17:15',
        'pausas'      => '0h 30m',
        'total_horas' => '7h 35m',
        'estado'      => 'completo',
    ],
    [
        'fecha'       => '23/01/2026',
        'entrada'     => '08:50',
        'salida'      => '--:--',
        'pausas'      => '0h 00m',
        'total_horas' => '--:--',
        'estado'      => 'incompleto',
    ],
];

$estados_registro_config = [
    'completo'   => ['etiqueta' => 'Completo',   'color_fondo' => 'bg-green-100', 'color_texto' => 'text-green-700'],
    'incompleto' => ['etiqueta' => 'Incompleto', 'color_fondo' => 'bg-yellow-100', 'color_texto' => 'text-yellow-700'],
    'ausencia'   => ['etiqueta' => 'Ausencia',   'color_fondo' => 'bg-red-100', 'color_texto' => 'text-red-700'],
];
?>

<section class="flavor-component flavor-section py-12 lg:py-20 bg-white">
    <div class="flavor-container">
        <!-- Titulo -->
        <div class="text-center mb-10">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3">
                <?php echo esc_html($titulo_historial); ?>
            </h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                <?php echo esc_html__('Consulta tus registros de entrada y salida recientes', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Tabla de registros -->
        <div class="max-w-5xl mx-auto">
            <!-- Version escritorio -->
            <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Entrada', 'flavor-chat-ia'); ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Salida', 'flavor-chat-ia'); ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Pausas', 'flavor-chat-ia'); ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Total Horas', 'flavor-chat-ia'); ?></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($registros_fichaje as $registro_actual):
                            $estado_registro_actual = $estados_registro_config[$registro_actual['estado']] ?? $estados_registro_config['completo'];
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 text-sm font-medium text-gray-800"><?php echo esc_html($registro_actual['fecha']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo esc_html($registro_actual['entrada']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo esc_html($registro_actual['salida']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo esc_html($registro_actual['pausas']); ?></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?php echo esc_html($registro_actual['total_horas']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo esc_attr($estado_registro_actual['color_fondo']); ?> <?php echo esc_attr($estado_registro_actual['color_texto']); ?>">
                                        <?php echo esc_html($estado_registro_actual['etiqueta']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Version movil (tarjetas) -->
            <div class="md:hidden space-y-4">
                <?php foreach ($registros_fichaje as $registro_movil):
                    $estado_registro_movil = $estados_registro_config[$registro_movil['estado']] ?? $estados_registro_config['completo'];
                ?>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-semibold text-gray-800"><?php echo esc_html($registro_movil['fecha']); ?></span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo esc_attr($estado_registro_movil['color_fondo']); ?> <?php echo esc_attr($estado_registro_movil['color_texto']); ?>">
                                <?php echo esc_html($estado_registro_movil['etiqueta']); ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-400"><?php echo esc_html__('Entrada:', 'flavor-chat-ia'); ?></span>
                                <span class="text-gray-700 ml-1"><?php echo esc_html($registro_movil['entrada']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400"><?php echo esc_html__('Salida:', 'flavor-chat-ia'); ?></span>
                                <span class="text-gray-700 ml-1"><?php echo esc_html($registro_movil['salida']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400"><?php echo esc_html__('Pausas:', 'flavor-chat-ia'); ?></span>
                                <span class="text-gray-700 ml-1"><?php echo esc_html($registro_movil['pausas']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400"><?php echo esc_html__('Total:', 'flavor-chat-ia'); ?></span>
                                <span class="font-semibold text-gray-800 ml-1"><?php echo esc_html($registro_movil['total_horas']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA Solicitar Correccion -->
            <div class="mt-8 text-center">
                <a href="<?php echo esc_url($url_solicitar_correccion); ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-gray-700 font-semibold border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <?php echo esc_html__('Solicitar Correccion', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
