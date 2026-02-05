<?php
/**
 * Template: Grid de Clientes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = $titulo_seccion ?? __('Nuestros Clientes', 'flavor-chat-ia');
$estado_filtro = $estado_filtro ?? 'todos';
$limite_mostrar = absint($limite ?? 12);

// Obtener clientes reales de la base de datos
$clientes_lista = [];

if (class_exists('wpdb')) {
    global $wpdb;
    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_clientes)) {
        $condicion_estado = '';
        $valores_sql = [];

        if ($estado_filtro !== 'todos' && !empty($estado_filtro)) {
            $condicion_estado = 'WHERE estado = %s';
            $valores_sql[] = $estado_filtro;
        }

        $valores_sql[] = $limite_mostrar;

        $consulta_sql = "SELECT * FROM $tabla_clientes $condicion_estado ORDER BY updated_at DESC LIMIT %d";
        $clientes_lista = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_sql));
    }
}

// Fallback con datos de ejemplo si no hay clientes reales
if (empty($clientes_lista)) {
    $clientes_lista = [
        (object) [
            'id' => 1,
            'nombre' => 'Maria Garcia Lopez',
            'email' => 'maria@ejemplo.com',
            'telefono' => '+34 612 345 678',
            'empresa' => 'Diseños Creativos SL',
            'cargo' => 'Directora Creativa',
            'tipo' => 'empresa',
            'estado' => 'activo',
            'etiquetas' => '["diseno","premium"]',
            'valor_estimado' => 15000,
            'origen' => 'web',
            'notas_count' => 8,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-2 days')),
        ],
        (object) [
            'id' => 2,
            'nombre' => 'Carlos Rodriguez Perez',
            'email' => 'carlos@ejemplo.com',
            'telefono' => '+34 698 765 432',
            'empresa' => 'TechStart',
            'cargo' => 'CEO',
            'tipo' => 'empresa',
            'estado' => 'potencial',
            'etiquetas' => '["tecnologia","startup"]',
            'valor_estimado' => 25000,
            'origen' => 'referido',
            'notas_count' => 3,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-1 week')),
        ],
        (object) [
            'id' => 3,
            'nombre' => 'Ana Martinez Ruiz',
            'email' => 'ana@ejemplo.com',
            'telefono' => '+34 634 567 890',
            'empresa' => '',
            'cargo' => '',
            'tipo' => 'particular',
            'estado' => 'activo',
            'etiquetas' => '["fidelizado"]',
            'valor_estimado' => 3500,
            'origen' => 'directo',
            'notas_count' => 12,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ],
        (object) [
            'id' => 4,
            'nombre' => 'Luis Fernandez Gomez',
            'email' => 'luis@ejemplo.com',
            'telefono' => '+34 678 901 234',
            'empresa' => 'Consultoria Global',
            'cargo' => 'Director Comercial',
            'tipo' => 'empresa',
            'estado' => 'activo',
            'etiquetas' => '["consultoria","vip"]',
            'valor_estimado' => 45000,
            'origen' => 'redes',
            'notas_count' => 15,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        ],
        (object) [
            'id' => 5,
            'nombre' => 'Elena Torres Navarro',
            'email' => 'elena@ejemplo.com',
            'telefono' => '+34 645 678 901',
            'empresa' => '',
            'cargo' => 'Freelance',
            'tipo' => 'autonomo',
            'estado' => 'potencial',
            'etiquetas' => '["freelance","diseno"]',
            'valor_estimado' => 5000,
            'origen' => 'web',
            'notas_count' => 2,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-5 days')),
        ],
        (object) [
            'id' => 6,
            'nombre' => 'Ayuntamiento de Bilbao',
            'email' => 'contacto@bilbao.eus',
            'telefono' => '+34 944 204 200',
            'empresa' => 'Ayuntamiento de Bilbao',
            'cargo' => 'Departamento TIC',
            'tipo' => 'administracion',
            'estado' => 'activo',
            'etiquetas' => '["gobierno","gran cuenta"]',
            'valor_estimado' => 80000,
            'origen' => 'directo',
            'notas_count' => 20,
            'ultima_interaccion' => date('Y-m-d H:i:s', strtotime('-12 hours')),
        ],
    ];
}

// Colores por estado
$colores_estado = [
    'activo' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981', 'label' => __('Activo', 'flavor-chat-ia')],
    'potencial' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b', 'label' => __('Potencial', 'flavor-chat-ia')],
    'inactivo' => ['bg' => 'rgba(107, 114, 128, 0.1)', 'text' => '#6b7280', 'label' => __('Inactivo', 'flavor-chat-ia')],
    'perdido' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#ef4444', 'label' => __('Perdido', 'flavor-chat-ia')],
];

// Iconos por tipo
$iconos_tipo = [
    'particular' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'empresa' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
    'autonomo' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
    'administracion' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>',
];
?>

<section class="flavor-component flavor-section" style="background: var(--flavor-background-alt, #f8f9fa);">
    <div class="flavor-container">
        <!-- Encabezado de seccion -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg" style="color: var(--flavor-text-secondary, #666666);">
                <?php esc_html_e('Gestiona tu cartera de clientes de forma eficiente', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Filtros rapidos -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <?php
            $filtros_disponibles = [
                'todos' => __('Todos', 'flavor-chat-ia'),
                'activo' => __('Activos', 'flavor-chat-ia'),
                'potencial' => __('Potenciales', 'flavor-chat-ia'),
                'inactivo' => __('Inactivos', 'flavor-chat-ia'),
                'perdido' => __('Perdidos', 'flavor-chat-ia'),
            ];
            foreach ($filtros_disponibles as $filtro_clave => $filtro_etiqueta):
                $filtro_activo = ($estado_filtro === $filtro_clave);
            ?>
                <button class="px-5 py-2 rounded-full text-sm font-medium transition-all duration-200"
                        style="<?php echo $filtro_activo
                            ? 'background: var(--flavor-primary, #667eea); color: white;'
                            : 'background: white; color: var(--flavor-text-secondary, #666); border: 1px solid #e5e7eb;'; ?>"
                        data-filtro="<?php echo esc_attr($filtro_clave); ?>">
                    <?php echo esc_html($filtro_etiqueta); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de clientes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($clientes_lista as $cliente_item):
                $estado_actual = $cliente_item->estado ?? 'potencial';
                $color_estado = $colores_estado[$estado_actual] ?? $colores_estado['potencial'];
                $tipo_actual = $cliente_item->tipo ?? 'particular';
                $icono_tipo = $iconos_tipo[$tipo_actual] ?? $iconos_tipo['particular'];

                $etiquetas_lista = [];
                if (!empty($cliente_item->etiquetas)) {
                    $etiquetas_lista = json_decode($cliente_item->etiquetas, true);
                    if (!is_array($etiquetas_lista)) {
                        $etiquetas_lista = [];
                    }
                }

                $valor_cliente = floatval($cliente_item->valor_estimado ?? 0);
                $valor_formateado = number_format($valor_cliente, 0, ',', '.') . ' &euro;';

                // Tiempo desde ultima interaccion
                $tiempo_transcurrido = '';
                if (!empty($cliente_item->ultima_interaccion)) {
                    $diferencia_tiempo = time() - strtotime($cliente_item->ultima_interaccion);
                    if ($diferencia_tiempo < 3600) {
                        $tiempo_transcurrido = sprintf(__('Hace %d min', 'flavor-chat-ia'), floor($diferencia_tiempo / 60));
                    } elseif ($diferencia_tiempo < 86400) {
                        $tiempo_transcurrido = sprintf(__('Hace %d h', 'flavor-chat-ia'), floor($diferencia_tiempo / 3600));
                    } else {
                        $tiempo_transcurrido = sprintf(__('Hace %d dias', 'flavor-chat-ia'), floor($diferencia_tiempo / 86400));
                    }
                }

                // Inicial del nombre para avatar
                $inicial_nombre = mb_strtoupper(mb_substr($cliente_item->nombre, 0, 1));
                // Color hash para avatar
                $hash_color = abs(crc32($cliente_item->nombre ?? 'A')) % 360;
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                    <div class="p-6">
                        <!-- Cabecera: avatar + nombre + estado -->
                        <div class="flex items-start gap-4 mb-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg"
                                 style="background: hsl(<?php echo $hash_color; ?>, 65%, 55%);">
                                <?php echo esc_html($inicial_nombre); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold truncate" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($cliente_item->nombre); ?>
                                </h3>
                                <?php if (!empty($cliente_item->empresa)): ?>
                                    <p class="text-sm truncate" style="color: var(--flavor-text-secondary, #666);">
                                        <?php echo esc_html($cliente_item->empresa); ?>
                                        <?php if (!empty($cliente_item->cargo)): ?>
                                            &middot; <?php echo esc_html($cliente_item->cargo); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php elseif (!empty($cliente_item->cargo)): ?>
                                    <p class="text-sm truncate" style="color: var(--flavor-text-secondary, #666);">
                                        <?php echo esc_html($cliente_item->cargo); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <!-- Badge estado -->
                            <span class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-semibold"
                                  style="background: <?php echo $color_estado['bg']; ?>; color: <?php echo $color_estado['text']; ?>;">
                                <?php echo esc_html($color_estado['label']); ?>
                            </span>
                        </div>

                        <!-- Informacion de contacto -->
                        <div class="space-y-2 mb-4">
                            <?php if (!empty($cliente_item->email)): ?>
                                <div class="flex items-center gap-2 text-sm" style="color: var(--flavor-text-secondary, #666);">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="truncate"><?php echo esc_html($cliente_item->email); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($cliente_item->telefono)): ?>
                                <div class="flex items-center gap-2 text-sm" style="color: var(--flavor-text-secondary, #666);">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span><?php echo esc_html($cliente_item->telefono); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tipo e icono -->
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium"
                                  style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary, #667eea);">
                                <?php echo $icono_tipo; ?>
                                <?php echo esc_html(ucfirst($tipo_actual)); ?>
                            </span>
                            <?php if (!empty($cliente_item->origen)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                      style="background: rgba(107, 114, 128, 0.1); color: #6b7280;">
                                    <?php echo esc_html(ucfirst($cliente_item->origen)); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Etiquetas -->
                        <?php if (!empty($etiquetas_lista)): ?>
                            <div class="flex flex-wrap gap-1 mb-4">
                                <?php foreach (array_slice($etiquetas_lista, 0, 3) as $etiqueta_texto): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs"
                                          style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.08); color: var(--flavor-primary, #667eea);">
                                        #<?php echo esc_html($etiqueta_texto); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($etiquetas_lista) > 3): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="color: var(--flavor-text-muted, #999);">
                                        +<?php echo count($etiquetas_lista) - 3; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pie: valor + notas + ultima interaccion -->
                        <div class="flex items-center justify-between pt-4 border-t" style="border-color: #f3f4f6;">
                            <?php if ($valor_cliente > 0): ?>
                                <span class="text-sm font-bold" style="color: var(--flavor-primary, #667eea);">
                                    <?php echo $valor_formateado; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-sm" style="color: var(--flavor-text-muted, #999);">
                                    <?php esc_html_e('Sin valor', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>

                            <div class="flex items-center gap-3">
                                <?php if (!empty($cliente_item->notas_count) && $cliente_item->notas_count > 0): ?>
                                    <span class="flex items-center gap-1 text-xs" style="color: var(--flavor-text-muted, #999);">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        <?php echo esc_html($cliente_item->notas_count); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($tiempo_transcurrido)): ?>
                                    <span class="text-xs" style="color: var(--flavor-text-muted, #999);">
                                        <?php echo esc_html($tiempo_transcurrido); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($clientes_lista)): ?>
            <!-- Estado vacio -->
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--flavor-text-muted, #999);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-xl font-semibold mb-2" style="color: var(--flavor-text-primary, #1a1a1a);">
                    <?php esc_html_e('No hay clientes aun', 'flavor-chat-ia'); ?>
                </h3>
                <p style="color: var(--flavor-text-secondary, #666);">
                    <?php esc_html_e('Empieza a agregar clientes a tu CRM para verlos aqui.', 'flavor-chat-ia'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>
