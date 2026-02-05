<?php
/**
 * Frontend: Archive de Fichaje Empleados - Panel de Control de Fichajes
 *
 * Muestra el dashboard principal de fichajes con reloj en vivo,
 * estadisticas, acciones rapidas, registros del dia y resumen semanal.
 *
 * @package FlavorChatIA
 * @subpackage FichajeEmpleados
 *
 * @var array $fichajes_hoy      Lista de fichajes del dia actual
 * @var int   $total_fichajes     Numero total de fichajes registrados
 * @var array $estadisticas       Estadisticas generales (horas_hoy, horas_semana, horas_mes, dias_trabajados)
 * @var array $resumen_semanal    Resumen de horas por dia de la semana actual
 */
if (!defined('ABSPATH')) exit;
$fichajes_hoy = $fichajes_hoy ?? [];
$total_fichajes = $total_fichajes ?? 0;
$estadisticas = $estadisticas ?? [];
$resumen_semanal = $resumen_semanal ?? [];
?>

<div class="flavor-frontend flavor-fichaje-archive">
    <!-- Header con gradiente azul/indigo -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Control de Fichajes</h1>
                <p class="text-blue-100">Gestiona tu jornada laboral de forma sencilla</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_fichajes); ?> fichajes registrados
                </span>
                <div class="bg-white/20 backdrop-blur px-5 py-3 rounded-xl text-center">
                    <p class="text-xs text-blue-100 mb-1">Hora actual</p>
                    <p id="flavor-reloj-fichaje" class="text-2xl font-bold font-mono">--:--:--</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#9201;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['horas_hoy'] ?? '0h 0m'); ?></p>
            <p class="text-sm text-gray-500">Horas hoy</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128197;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['horas_semana'] ?? '0h'); ?></p>
            <p class="text-sm text-gray-500">Horas esta semana</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128200;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['horas_mes'] ?? '0h'); ?></p>
            <p class="text-sm text-gray-500">Horas este mes</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128188;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['dias_trabajados'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Dias trabajados</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Acciones rapidas -->
    <div class="bg-blue-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Acciones rapidas</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?php echo esc_url(home_url('/fichaje/entrada/')); ?>"
               class="flex flex-col items-center gap-2 bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all group border border-gray-100">
                <div class="w-14 h-14 bg-green-500 text-white rounded-full flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    &#9654;
                </div>
                <span class="font-semibold text-gray-800 text-sm">Fichar Entrada</span>
            </a>
            <a href="<?php echo esc_url(home_url('/fichaje/salida/')); ?>"
               class="flex flex-col items-center gap-2 bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all group border border-gray-100">
                <div class="w-14 h-14 bg-red-500 text-white rounded-full flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    &#9724;
                </div>
                <span class="font-semibold text-gray-800 text-sm">Fichar Salida</span>
            </a>
            <a href="<?php echo esc_url(home_url('/fichaje/pausar/')); ?>"
               class="flex flex-col items-center gap-2 bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all group border border-gray-100">
                <div class="w-14 h-14 bg-amber-500 text-white rounded-full flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    &#9208;
                </div>
                <span class="font-semibold text-gray-800 text-sm">Pausar</span>
            </a>
            <a href="<?php echo esc_url(home_url('/fichaje/reanudar/')); ?>"
               class="flex flex-col items-center gap-2 bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all group border border-gray-100">
                <div class="w-14 h-14 bg-blue-500 text-white rounded-full flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                    &#9193;
                </div>
                <span class="font-semibold text-gray-800 text-sm">Reanudar</span>
            </a>
        </div>
    </div>

    <!-- Registros de hoy -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">Registros de hoy</h2>
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                <?php echo esc_html(count($fichajes_hoy)); ?> registros
            </span>
        </div>

        <?php if (empty($fichajes_hoy)): ?>
        <div class="text-center py-12 bg-gray-50 rounded-xl">
            <div class="text-5xl mb-3">&#128337;</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Sin fichajes hoy</h3>
            <p class="text-gray-500 mb-4">Comienza tu jornada fichando la entrada</p>
            <a href="<?php echo esc_url(home_url('/fichaje/entrada/')); ?>"
               class="inline-block bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-600 transition-colors">
                Fichar Entrada
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-500">Hora</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-500">Tipo</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-500">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichajes_hoy as $registro_fichaje): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="font-mono font-medium text-gray-800">
                                <?php echo esc_html($registro_fichaje['hora'] ?? '--:--'); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <?php
                            $tipo_fichaje = $registro_fichaje['tipo'] ?? 'entrada';
                            $colores_tipo_fichaje = [
                                'entrada'     => 'bg-green-100 text-green-700',
                                'salida'      => 'bg-red-100 text-red-700',
                                'pausa'       => 'bg-amber-100 text-amber-700',
                                'reanudacion' => 'bg-blue-100 text-blue-700',
                            ];
                            $clase_color_tipo = $colores_tipo_fichaje[$tipo_fichaje] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="<?php echo esc_attr($clase_color_tipo); ?> px-3 py-1 rounded-full text-xs font-medium capitalize">
                                <?php echo esc_html($tipo_fichaje); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <?php
                            $estado_fichaje = $registro_fichaje['estado'] ?? 'validado';
                            $colores_estado_fichaje = [
                                'validado'  => 'bg-green-100 text-green-700',
                                'pendiente' => 'bg-amber-100 text-amber-700',
                                'rechazado' => 'bg-red-100 text-red-700',
                            ];
                            $clase_color_estado = $colores_estado_fichaje[$estado_fichaje] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="<?php echo esc_attr($clase_color_estado); ?> px-3 py-1 rounded-full text-xs font-medium capitalize">
                                <?php echo esc_html($estado_fichaje); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Resumen semanal -->
    <?php if (!empty($resumen_semanal)): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Resumen semanal</h2>
        <div class="flex items-end justify-between gap-2 h-48">
            <?php
            $maximo_horas_semana = max(array_column($resumen_semanal, 'horas')) ?: 8;
            foreach ($resumen_semanal as $dia_resumen):
                $horas_dia_resumen = $dia_resumen['horas'] ?? 0;
                $porcentaje_barra = ($maximo_horas_semana > 0) ? ($horas_dia_resumen / $maximo_horas_semana) * 100 : 0;
                $es_dia_actual = !empty($dia_resumen['es_hoy']);
            ?>
            <div class="flex-1 flex flex-col items-center gap-2">
                <span class="text-xs font-medium text-gray-500"><?php echo esc_html($horas_dia_resumen); ?>h</span>
                <div class="w-full bg-gray-100 rounded-t-lg relative" style="height: 120px;">
                    <div class="absolute bottom-0 left-0 right-0 <?php echo $es_dia_actual ? 'bg-gradient-to-t from-blue-500 to-indigo-500' : 'bg-blue-200'; ?> rounded-t-lg transition-all"
                         style="height: <?php echo esc_attr($porcentaje_barra); ?>%;">
                    </div>
                </div>
                <span class="text-xs font-medium <?php echo $es_dia_actual ? 'text-indigo-600 font-bold' : 'text-gray-500'; ?>">
                    <?php echo esc_html($dia_resumen['dia_corto'] ?? ''); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA Solicitar correccion -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 text-center">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Algo no cuadra en tus fichajes?</h3>
        <p class="text-gray-600 mb-4">Puedes solicitar una correccion si detectas algun error en tus registros</p>
        <a href="<?php echo esc_url(home_url('/fichaje/solicitar-correccion/')); ?>"
           class="inline-block bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all shadow-md">
            Solicitar Correccion
        </a>
    </div>

    <!-- Paginacion -->
    <?php if ($total_fichajes > 20): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">&larr; Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_fichajes / 20); ?></span>
            <button class="px-4 py-2 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors">Siguiente &rarr;</button>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var elementoRelojFichaje = document.getElementById('flavor-reloj-fichaje');
    if (!elementoRelojFichaje) return;
    function actualizarRelojFichaje() {
        var ahora = new Date();
        var horas = String(ahora.getHours()).padStart(2, '0');
        var minutos = String(ahora.getMinutes()).padStart(2, '0');
        var segundos = String(ahora.getSeconds()).padStart(2, '0');
        elementoRelojFichaje.textContent = horas + ':' + minutos + ':' + segundos;
    }
    actualizarRelojFichaje();
    setInterval(actualizarRelojFichaje, 1000);
})();
</script>
