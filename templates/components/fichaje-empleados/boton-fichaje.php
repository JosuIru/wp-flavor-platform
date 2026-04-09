<?php
/**
 * Template: Boton de Fichaje
 *
 * Seccion prominente con boton grande para fichar entrada/salida,
 * reloj con hora actual, botones secundarios y estado del empleado.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$nombre_empleado = $nombre_empleado ?? 'Empleado';
$estado_fichaje = $estado_fichaje ?? 'sin_fichar';
$hora_entrada_actual = $hora_entrada_actual ?? '--:--';
$url_fichar_entrada = $url_fichar_entrada ?? '/fichaje/entrada/';
$url_fichar_salida = $url_fichar_salida ?? '/fichaje/salida/';
$url_pausar_fichaje = $url_pausar_fichaje ?? '/fichaje/pausar/';

$estados_fichaje_config = [
    'trabajando'  => ['etiqueta' => 'Trabajando', 'color_fondo' => 'bg-green-100', 'color_texto' => 'text-green-700', 'color_punto' => 'bg-green-500'],
    'pausado'     => ['etiqueta' => 'En pausa',   'color_fondo' => 'bg-yellow-100', 'color_texto' => 'text-yellow-700', 'color_punto' => 'bg-yellow-500'],
    'sin_fichar'  => ['etiqueta' => 'Sin fichar', 'color_fondo' => 'bg-gray-100', 'color_texto' => 'text-gray-600', 'color_punto' => 'bg-gray-400'],
];
$estado_actual_config = $estados_fichaje_config[$estado_fichaje] ?? $estados_fichaje_config['sin_fichar'];
?>

<section class="flavor-component flavor-section py-12 lg:py-20" style="background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);">
    <div class="flavor-container">
        <div class="max-w-2xl mx-auto text-center">
            <!-- Saludo y estado -->
            <div class="mb-6">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-3">
                    <?php echo esc_html__('Hola,', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($nombre_empleado); ?>
                </h2>
                <!-- Indicador de estado -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full <?php echo esc_attr($estado_actual_config['color_fondo']); ?>">
                    <span class="w-2.5 h-2.5 rounded-full <?php echo esc_attr($estado_actual_config['color_punto']); ?> animate-pulse"></span>
                    <span class="text-sm font-medium <?php echo esc_attr($estado_actual_config['color_texto']); ?>">
                        <?php echo esc_html($estado_actual_config['etiqueta']); ?>
                    </span>
                </div>
            </div>

            <!-- Reloj con hora actual -->
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-40 h-40 rounded-full bg-white shadow-lg border border-gray-100">
                    <div class="text-center">
                        <div id="flavor-reloj-fichaje" class="text-4xl font-bold text-gray-800" style="font-variant-numeric: tabular-nums;">
                            --:--
                        </div>
                        <div class="text-sm text-gray-400 mt-1"><?php echo esc_html__('Hora actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>

            <!-- Hora de entrada (si ya ficho) -->
            <?php if ($hora_entrada_actual !== '--:--'): ?>
                <div class="mb-6 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white shadow-sm border border-gray-100">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-sm text-gray-600"><?php echo esc_html__('Entrada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <strong><?php echo esc_html($hora_entrada_actual); ?></strong></span>
                </div>
            <?php endif; ?>

            <!-- Boton principal de fichaje -->
            <div class="mb-6">
                <a href="<?php echo esc_url($url_fichar_entrada); ?>"
                   class="inline-flex items-center gap-3 px-12 py-5 rounded-2xl bg-gradient-to-r from-slate-600 to-gray-700 text-white font-bold text-xl hover:from-slate-700 hover:to-gray-800 transition-all shadow-xl hover:shadow-2xl transform hover:scale-105 duration-300">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo esc_html__('Fichar Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <!-- Botones secundarios -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="<?php echo esc_url($url_fichar_salida); ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-gray-700 font-semibold border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <?php echo esc_html__('Fichar Salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url($url_pausar_fichaje); ?>"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-gray-700 font-semibold border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo esc_html__('Pausar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Script para el reloj en tiempo real -->
<script>
(function() {
    var elementoReloj = document.getElementById('flavor-reloj-fichaje');
    if (!elementoReloj) return;

    function actualizarRelojFichaje() {
        var ahora = new Date();
        var horasFormateadas = String(ahora.getHours()).padStart(2, '0');
        var minutosFormateados = String(ahora.getMinutes()).padStart(2, '0');
        var segundosFormateados = String(ahora.getSeconds()).padStart(2, '0');
        elementoReloj.textContent = horasFormateadas + ':' + minutosFormateados + ':' + segundosFormateados;
    }

    actualizarRelojFichaje();
    setInterval(actualizarRelojFichaje, 1000);
})();
</script>
