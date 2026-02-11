<?php
/**
 * Template: Marketplace Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Marketplace Local';
$subtitulo_hero = $subtitulo_hero ?? 'Compra, vende e intercambia en tu barrio';
$productos_activos = $productos_activos ?? 385;
$total_vendedores = $total_vendedores ?? 120;
$total_transacciones = $total_transacciones ?? 1540;

$categorias_busqueda = $categorias_busqueda ?? [
    'todas'       => 'Todas las categorias',
    'electronica' => 'Electronica',
    'hogar'       => 'Hogar',
    'ropa'        => 'Ropa',
    'deportes'    => 'Deportes',
    'libros'      => 'Libros',
    'motor'       => 'Motor',
];
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #84CC16) 0%, var(--flavor-secondary, #16A34A) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html($productos_activos); ?> <?php echo esc_html__('productos disponibles', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- Barra de busqueda con dropdown -->
            <div class="max-w-3xl mx-auto mb-10">
                <form class="flex flex-col sm:flex-row gap-3" method="get">
                    <div class="flex-1 relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="buscar_producto" placeholder="<?php echo esc_attr__('Que estas buscando?', 'flavor-chat-ia'); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl bg-white/10 backdrop-blur text-white placeholder-white/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
                    </div>
                    <select name="categoria_producto" class="px-4 py-4 rounded-xl bg-white/10 backdrop-blur text-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30">
                        <?php foreach ($categorias_busqueda as $valor_categoria => $etiqueta_categoria) : ?>
                            <option value="<?php echo esc_attr($valor_categoria); ?>"><?php echo esc_html($etiqueta_categoria); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-6 py-4 rounded-xl bg-white text-green-600 font-semibold hover:bg-white/90 transition-colors">
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($productos_activos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Productos Activos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($total_vendedores); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Vendedores', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($total_transacciones)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Transacciones', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
