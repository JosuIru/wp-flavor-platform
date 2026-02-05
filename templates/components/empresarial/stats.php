<?php
/**
 * Template: Estadísticas / Métricas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables
$titulo_seccion = $titulo_seccion ?? 'Resultados que Hablan por Sí Solos';
$estilo = $estilo ?? 'highlighted';

// Iconos por defecto para estadísticas
$iconos_estadisticas = [
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
];

// Usar items del repeater o fallback
$estadisticas = $items ?? [];

// Backward compatibility: si no hay items pero hay stat_1-4, construir array
if (empty($estadisticas) && !empty($stat_1_numero)) {
    $estadisticas = [];
    for ($stat_idx = 1; $stat_idx <= 4; $stat_idx++) {
        $numero_var = 'stat_' . $stat_idx . '_numero';
        $texto_var = 'stat_' . $stat_idx . '_texto';
        if (!empty($$numero_var)) {
            $estadisticas[] = [
                'numero' => $$numero_var,
                'texto' => $$texto_var ?? '',
            ];
        }
    }
}

// Fallback a datos de ejemplo
if (empty($estadisticas)) {
    $estadisticas = [
        ['numero' => '500+', 'texto' => 'Clientes Satisfechos'],
        ['numero' => '15+', 'texto' => 'Años de Experiencia'],
        ['numero' => '98%', 'texto' => 'Tasa de Satisfacción'],
        ['numero' => '24/7', 'texto' => 'Soporte Disponible'],
    ];
}

// Asignar iconos a cada estadística
foreach ($estadisticas as $stat_idx => &$stat_item) {
    $stat_item['icono'] = $iconos_estadisticas[$stat_idx % count($iconos_estadisticas)];
}
unset($stat_item);
?>

<?php if ($estilo === 'highlighted'): ?>
    <!-- Estilo Highlighted con fondo degradado -->
    <section class="flavor-component flavor-section relative overflow-hidden"
             style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%);">

        <!-- Patrón decorativo -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

        <div class="flavor-container relative z-10">
            <!-- Encabezado de sección -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white">
                    <?php echo esc_html($titulo_seccion); ?>
                </h2>
            </div>

            <!-- Grid de estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($estadisticas as $stat): ?>
                    <div class="text-center group">
                        <div class="bg-white bg-opacity-20 backdrop-filter backdrop-blur-lg rounded-2xl p-8 transition-all duration-300 hover:bg-opacity-30 hover:transform hover:scale-105 hover:shadow-2xl">
                            <div class="mb-4 inline-flex items-center justify-center text-white">
                                <?php echo $stat['icono']; ?>
                            </div>
                            <div class="text-5xl md:text-6xl font-bold text-white mb-4 transition-transform duration-300 group-hover:scale-110">
                                <?php echo esc_html($stat['numero']); ?>
                            </div>
                            <div class="text-lg text-white text-opacity-95 font-semibold">
                                <?php echo esc_html($stat['texto']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php elseif ($estilo === 'cards'): ?>
    <!-- Estilo Cards con sombras -->
    <section class="flavor-component flavor-section" style="background: var(--flavor-background-alt, #f8f9fa);">
        <div class="flavor-container">
            <!-- Encabezado de sección -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                    <?php echo esc_html($titulo_seccion); ?>
                </h2>
            </div>

            <!-- Grid de estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($estadisticas as $stat): ?>
                    <div class="text-center group">
                        <div class="bg-white rounded-2xl p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:transform hover:-translate-y-2">
                            <div class="mb-6 inline-flex items-center justify-center p-4 rounded-xl transition-all duration-300 group-hover:scale-110"
                                 style="background: linear-gradient(135deg, var(--flavor-primary, #667eea) 0%, var(--flavor-secondary, #764ba2) 100%); color: white;">
                                <?php echo $stat['icono']; ?>
                            </div>
                            <div class="text-5xl md:text-6xl font-bold mb-4 transition-all duration-300"
                                 style="color: var(--flavor-primary, #667eea);">
                                <?php echo esc_html($stat['numero']); ?>
                            </div>
                            <div class="text-lg font-semibold" style="color: var(--flavor-text-secondary, #666666);">
                                <?php echo esc_html($stat['texto']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php else: // minimal ?>
    <!-- Estilo Minimal sin bordes -->
    <section class="flavor-component flavor-section" style="background: var(--flavor-background, #ffffff);">
        <div class="flavor-container">
            <!-- Encabezado de sección -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                    <?php echo esc_html($titulo_seccion); ?>
                </h2>
            </div>

            <!-- Grid de estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <?php foreach ($estadisticas as $stat): ?>
                    <div class="text-center group">
                        <div class="mb-4 inline-flex items-center justify-center transition-all duration-300 group-hover:scale-110"
                             style="color: var(--flavor-primary, #667eea);">
                            <?php echo $stat['icono']; ?>
                        </div>
                        <div class="text-5xl md:text-6xl font-bold mb-4 transition-all duration-300"
                             style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html($stat['numero']); ?>
                        </div>
                        <div class="text-lg font-semibold" style="color: var(--flavor-text-secondary, #666666);">
                            <?php echo esc_html($stat['texto']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Línea decorativa -->
            <div class="mt-16 max-w-3xl mx-auto">
                <div class="h-1 rounded-full" style="background: linear-gradient(90deg, transparent 0%, var(--flavor-primary, #667eea) 50%, transparent 100%);"></div>
            </div>
        </div>
    </section>
<?php endif; ?>
