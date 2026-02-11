<?php
/**
 * Template: Hero Grupos de Consumo
 *
 * Seccion hero para la landing de grupos de consumo.
 * Muestra titulo, subtitulo, estadisticas y botones de accion.
 *
 * @var string $titulo_hero
 * @var string $subtitulo_hero
 * @var int    $productos_disponibles
 * @var int    $pedidos_activos
 * @var string $ahorro_medio
 * @var string $url_ver_productos
 * @var string $url_unirse_grupo
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero            = $titulo_hero ?? 'Grupos de Consumo';
$subtitulo_hero         = $subtitulo_hero ?? 'Compra colectiva de productos locales, ecologicos y de proximidad. Juntos conseguimos mejores precios';
$productos_disponibles  = $productos_disponibles ?? 214;
$pedidos_activos        = $pedidos_activos ?? 18;
$ahorro_medio           = $ahorro_medio ?? '25%';
$url_ver_productos      = $url_ver_productos ?? '/grupos-consumo/productos/';
$url_unirse_grupo       = $url_unirse_grupo ?? '/grupos-consumo/unirse/';

$categorias_busqueda = $categorias_busqueda ?? [
    'todas'      => 'Todas las categorias',
    'frutas'     => 'Frutas y Verduras',
    'lacteos'    => 'Lacteos',
    'panaderia'  => 'Panaderia',
    'conservas'  => 'Conservas',
    'carne'      => 'Carne y Pescado',
];
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, #22C55E 0%, #059669 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html($productos_disponibles); ?> <?php echo esc_html__('productos disponibles', 'flavor-chat-ia'); ?></span>
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
                        <input type="text" name="buscar_producto" placeholder="<?php echo esc_attr__('Que producto buscas?', 'flavor-chat-ia'); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl bg-white/10 backdrop-blur text-white placeholder-white/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
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

            <!-- Botones CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="<?php echo esc_url($url_ver_productos); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white text-green-600 font-semibold text-lg hover:bg-white/90 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <?php echo esc_html__('Ver Productos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url($url_unirse_grupo); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/30 text-white font-semibold text-lg hover:bg-white/10 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <?php echo esc_html__('Unirme a un Grupo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($productos_disponibles); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Productos Disponibles', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($pedidos_activos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Pedidos Activos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($ahorro_medio); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Ahorro Medio', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
