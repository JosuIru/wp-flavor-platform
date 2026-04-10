<?php
/**
 * Template: Eventos Calendario
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) { exit; }

// Obtener mes y anio actual o por parametro
$mes_actual = isset($_GET['mes']) ? absint($_GET['mes']) : (int) current_time('m');
$anio_actual = isset($_GET['anio']) ? absint($_GET['anio']) : (int) current_time('Y');
if ($mes_actual < 1 || $mes_actual > 12) { $mes_actual = (int) current_time('m'); }
if ($anio_actual < 2020 || $anio_actual > 2035) { $anio_actual = (int) current_time('Y'); }

$primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
$dias_en_mes = (int) date('t', $primer_dia_mes);
$dia_semana_inicio = (int) date('N', $primer_dia_mes);
$nombre_mes = date_i18n('F Y', $primer_dia_mes);

// Navegacion
$mes_anterior = $mes_actual - 1;
$anio_anterior = $anio_actual;
if ($mes_anterior < 1) { $mes_anterior = 12; $anio_anterior--; }
$mes_siguiente = $mes_actual + 1;
$anio_siguiente = $anio_actual;
if ($mes_siguiente > 12) { $mes_siguiente = 1; $anio_siguiente++; }

// Obtener eventos del mes
$eventos_del_mes = [];
$eventos_por_dia = [];
$modulo_eventos = null;
if (class_exists('Flavor_Platform_Module_Loader')) {
    $loader = Flavor_Platform_Module_Loader::get_instance();
    $modulo_eventos = $loader->get_module('eventos');
}
if ($modulo_eventos) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_eventos';
    if (Flavor_Platform_Helpers::tabla_existe($tabla)) {
        $fecha_inicio_mes = sprintf('%04d-%02d-01 00:00:00', $anio_actual, $mes_actual);
        $fecha_fin_mes = sprintf('%04d-%02d-%02d 23:59:59', $anio_actual, $mes_actual, $dias_en_mes);
        $eventos_del_mes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE estado = 'publicado' AND fecha_inicio BETWEEN %s AND %s ORDER BY fecha_inicio ASC",
                $fecha_inicio_mes, $fecha_fin_mes
            ), ARRAY_A
        );
        foreach ($eventos_del_mes as $evento_cal) {
            $dia = (int) date('j', strtotime($evento_cal['fecha_inicio']));
            $eventos_por_dia[$dia][] = $evento_cal;
        }
    }
}

// Fallback si no hay eventos
if (empty($eventos_del_mes)) {
    $fallback_dias = [5, 12, 15, 20, 25];
    $fallback_tipos = ['conferencia', 'taller', 'charla', 'festival', 'deportivo'];
    $fallback_nombres = ['Conferencia Digital', 'Taller Creativo', 'Charla Bienestar', 'Festival Local', 'Torneo Deportivo'];
    foreach ($fallback_dias as $idx => $dia_fb) {
        if ($dia_fb <= $dias_en_mes) {
            $eventos_por_dia[$dia_fb][] = [
                'id' => $idx + 1,
                'titulo' => $fallback_nombres[$idx],
                'tipo' => $fallback_tipos[$idx],
                'fecha_inicio' => sprintf('%04d-%02d-%02d 10:00:00', $anio_actual, $mes_actual, $dia_fb),
                'ubicacion' => 'Lugar por confirmar',
                'precio' => 0,
            ];
            $eventos_del_mes[] = $eventos_por_dia[$dia_fb][0];
        }
    }
}

$colores_tipo = [
    'conferencia' => '#3B82F6', 'taller' => '#8B5CF6', 'charla' => '#06B6D4',
    'festival' => '#F59E0B', 'deportivo' => '#10B981', 'cultural' => '#EC4899',
    'social' => '#F97316', 'networking' => '#6366F1',
];
$dias_semana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: var(--flavor-bg, #F9FAFB);">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold mb-4" style="color: var(--flavor-text, #111827);">
                <?php echo esc_html__('Calendario de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="text-lg" style="color: var(--flavor-text-muted, #6B7280);">
                <?php echo esc_html__('Consulta todos los eventos del mes en un vistazo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <div class="max-w-5xl mx-auto">
            <!-- Calendar Navigation -->
            <div class="flex items-center justify-between mb-6 p-4 rounded-xl bg-white shadow-sm border" style="border-color: #E5E7EB;">
                <a href="?mes=<?php echo esc_attr($mes_anterior); ?>&anio=<?php echo esc_attr($anio_anterior); ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors" style="color: var(--flavor-text, #111827);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline"><?php echo esc_html__('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <h3 class="text-xl font-bold" style="color: var(--flavor-text, #111827);">
                    <?php echo esc_html(ucfirst($nombre_mes)); ?>
                </h3>
                <a href="?mes=<?php echo esc_attr($mes_siguiente); ?>&anio=<?php echo esc_attr($anio_siguiente); ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors" style="color: var(--flavor-text, #111827);">
                    <span class="hidden sm:inline"><?php echo esc_html__('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden" style="border-color: #E5E7EB;">
                <!-- Day Headers -->
                <div class="grid grid-cols-7 border-b" style="border-color: #E5E7EB;">
                    <?php foreach ($dias_semana as $dia_nombre) : ?>
                        <div class="p-3 text-center text-sm font-semibold" style="color: var(--flavor-text-muted, #6B7280); background: #F9FAFB;">
                            <?php echo esc_html($dia_nombre); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar Days -->
                <div class="grid grid-cols-7">
                    <?php
                    $dia_hoy = (int) current_time('j');
                    $mes_hoy = (int) current_time('m');
                    $anio_hoy = (int) current_time('Y');
                    // Empty cells before first day
                    for ($celda_vacia = 1; $celda_vacia < $dia_semana_inicio; $celda_vacia++) :
                    ?>
                        <div class="min-h-[80px] p-2 border-b border-r" style="border-color: #F3F4F6; background: #FAFAFA;"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++) :
                        $es_hoy = ($dia === $dia_hoy && $mes_actual === $mes_hoy && $anio_actual === $anio_hoy);
                        $tiene_eventos = isset($eventos_por_dia[$dia]);
                        $eventos_dia = $eventos_por_dia[$dia] ?? [];
                    ?>
                        <div class="min-h-[80px] p-2 border-b border-r relative<?php echo $es_hoy ? ' bg-blue-50' : ''; ?>" style="border-color: #F3F4F6;">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm font-medium<?php echo $es_hoy ? ' text-white' : ''; ?>" style="<?php echo $es_hoy ? 'background: var(--flavor-primary, #3B82F6);' : 'color: var(--flavor-text, #111827);'; ?>">
                                <?php echo esc_html($dia); ?>
                            </span>
                            <?php if ($tiene_eventos) : ?>
                                <div class="mt-1 space-y-1">
                                    <?php foreach (array_slice($eventos_dia, 0, 2) as $evento_dia) :
                                        $color_ev = $colores_tipo[$evento_dia['tipo']] ?? '#6B7280';
                                    ?>
                                        <div class="text-xs px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80" style="background: <?php echo esc_attr($color_ev); ?>15; color: <?php echo esc_attr($color_ev); ?>; border-left: 2px solid <?php echo esc_attr($color_ev); ?>;" title="<?php echo esc_attr($evento_dia['titulo']); ?>">
                                            <?php echo esc_html(wp_trim_words($evento_dia['titulo'], 3, '...')); ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($eventos_dia) > 2) : ?>
                                        <div class="text-xs text-center font-medium" style="color: var(--flavor-primary, #3B82F6);">
                                            +<?php echo esc_html(count($eventos_dia) - 2); ?> <?php echo esc_html__('mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>

                    <?php
                    // Empty cells after last day
                    $celdas_usadas = ($dia_semana_inicio - 1) + $dias_en_mes;
                    $celdas_restantes = (7 - ($celdas_usadas % 7)) % 7;
                    for ($celda_final = 0; $celda_final < $celdas_restantes; $celda_final++) :
                    ?>
                        <div class="min-h-[80px] p-2 border-b border-r" style="border-color: #F3F4F6; background: #FAFAFA;"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Upcoming Events List -->
            <?php if (!empty($eventos_del_mes)) : ?>
                <div class="mt-8">
                    <h3 class="text-xl font-bold mb-4" style="color: var(--flavor-text, #111827);">
                        <?php echo esc_html__('Eventos del Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice($eventos_del_mes, 0, 6) as $evento_prox) :
                            $color_ev = $colores_tipo[$evento_prox['tipo']] ?? '#6B7280';
                            $fecha_ev = strtotime($evento_prox['fecha_inicio']);
                            $precio_ev = (float) ($evento_prox['precio'] ?? 0);
                        ?>
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-white shadow-sm border hover:shadow-md transition-shadow" style="border-color: #E5E7EB; border-left: 4px solid <?php echo esc_attr($color_ev); ?>;">
                                <div class="flex-shrink-0 text-center" style="min-width: 50px;">
                                    <div class="text-xs font-bold uppercase" style="color: <?php echo esc_attr($color_ev); ?>;">
                                        <?php echo esc_html(date_i18n('M', $fecha_ev)); ?>
                                    </div>
                                    <div class="text-2xl font-bold" style="color: var(--flavor-text, #111827);">
                                        <?php echo esc_html(date_i18n('d', $fecha_ev)); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold truncate" style="color: var(--flavor-text, #111827);">
                                        <?php echo esc_html($evento_prox['titulo']); ?>
                                    </h4>
                                    <div class="flex items-center gap-3 text-sm" style="color: var(--flavor-text-muted, #6B7280);">
                                        <span><?php echo esc_html(date_i18n('H:i', $fecha_ev)); ?></span>
                                        <?php if (!empty($evento_prox['ubicacion'])) : ?>
                                            <span><?php echo esc_html($evento_prox['ubicacion']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if ($precio_ev > 0) : ?>
                                        <span class="text-sm font-bold" style="color: <?php echo esc_attr($color_ev); ?>;">
                                            <?php echo esc_html(number_format($precio_ev, 2, ',', '.') . 'â¬'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="text-sm font-bold text-green-500">
                                            <?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
