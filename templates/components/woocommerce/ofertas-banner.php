<?php
/**
 * Template: WooCommerce Ofertas Banner
 * Muestra productos en oferta con imagen, precios original y de oferta
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_ofertas = $titulo_ofertas ?? 'Productos en Oferta';
$descripcion_ofertas = $descripcion_ofertas ?? 'Descubre nuestras mejores ofertas y ahorra en tus productos favoritos';
$color_fondo = $color_fondo ?? '#A855F7';
$mostrar_cuenta_regresiva = $mostrar_cuenta_regresiva ?? true;

$productos_ofertas = $productos_ofertas ?? [
    [
        'titulo'           => 'Laptop Gaming Pro',
        'precio_original'  => '1.299.99',
        'precio_oferta'    => '899.99',
        'porcentaje_ahorro' => 31,
        'imagen_url'       => '',
        'gradiente'        => 'from-blue-400 to-cyan-500',
        'categoria'        => 'Electronica',
        'stock_disponible' => true,
    ],
    [
        'titulo'           => 'Monitor 4K Ultra Wide',
        'precio_original'  => '599.99',
        'precio_oferta'    => '399.99',
        'porcentaje_ahorro' => 33,
        'imagen_url'       => '',
        'gradiente'        => 'from-green-400 to-teal-500',
        'categoria'        => 'Accesorios',
        'stock_disponible' => true,
    ],
    [
        'titulo'           => 'Teclado Mecanico RGB',
        'precio_original'  => '199.99',
        'precio_oferta'    => '129.99',
        'porcentaje_ahorro' => 35,
        'imagen_url'       => '',
        'gradiente'        => 'from-pink-400 to-rose-500',
        'categoria'        => 'Perifericos',
        'stock_disponible' => true,
    ],
    [
        'titulo'           => 'Mouse Inalambrico Pro',
        'precio_original'  => '89.99',
        'precio_oferta'    => '59.99',
        'porcentaje_ahorro' => 33,
        'imagen_url'       => '',
        'gradiente'        => 'from-yellow-400 to-orange-500',
        'categoria'        => 'Perifericos',
        'stock_disponible' => false,
    ],
];

$fecha_fin_oferta = $fecha_fin_oferta ?? '';
?>

<section class="flavor-component flavor-section py-12 lg:py-16 overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color_fondo); ?> 0%, rgba(168, 85, 247, 0.8) 100%);">
    <!-- Patron decorativo de fondo -->
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice">
            <defs>
                <pattern id="patron-ofertas" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                    <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="1"/>
                    <circle cx="50" cy="50" r="20" fill="none" stroke="currentColor" stroke-width="0.5"/>
                </pattern>
            </defs>
            <rect width="1200" height="600" fill="url(#patron-ofertas)"/>
        </svg>
    </div>

    <div class="flavor-container relative z-10">
        <!-- Encabezado de la seccion -->
        <div class="text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-4" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Ofertas Limitadas', 'flavor-chat-ia'); ?></span>
            </div>

            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-3">
                <?php echo esc_html($titulo_ofertas); ?>
            </h2>
            <p class="text-lg text-white/80 max-w-2xl mx-auto">
                <?php echo esc_html($descripcion_ofertas); ?>
            </p>
        </div>

        <!-- Contador regresivo (opcional) -->
        <?php if ($mostrar_cuenta_regresiva && !empty($fecha_fin_oferta)) : ?>
            <div class="flex justify-center mb-10">
                <div class="inline-flex items-center gap-4 px-6 py-4 rounded-2xl bg-white/10 backdrop-blur border border-white/20">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white" id="flavor-dias">0</div>
                        <div class="text-xs text-white/70 uppercase tracking-wide"><?php echo esc_html__('Dias', 'flavor-chat-ia'); ?></div>
                    </div>
                    <span class="text-white/50">:</span>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white" id="flavor-horas">0</div>
                        <div class="text-xs text-white/70 uppercase tracking-wide"><?php echo esc_html__('Horas', 'flavor-chat-ia'); ?></div>
                    </div>
                    <span class="text-white/50">:</span>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white" id="flavor-minutos">0</div>
                        <div class="text-xs text-white/70 uppercase tracking-wide"><?php echo esc_html__('Minutos', 'flavor-chat-ia'); ?></div>
                    </div>
                    <span class="text-white/50">:</span>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white" id="flavor-segundos">0</div>
                        <div class="text-xs text-white/70 uppercase tracking-wide"><?php echo esc_html__('Segundos', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grid de productos en oferta -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($productos_ofertas as $producto_oferta) : ?>
                <div class="flavor-card-oferta group">
                    <!-- Card principal -->
                    <div class="relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 h-full flex flex-col bg-white">

                        <!-- Imagen placeholder -->
                        <div class="relative h-56 bg-gradient-to-br <?php echo esc_attr($producto_oferta['gradiente']); ?> overflow-hidden flex-shrink-0">
                            <!-- Imagen -->
                            <?php if (!empty($producto_oferta['imagen_url'])) : ?>
                                <img src="<?php echo esc_url($producto_oferta['imagen_url']); ?>"
                                     alt="<?php echo esc_attr($producto_oferta['titulo']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            <?php else : ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-20 h-20 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <!-- Badge de categoria -->
                            <div class="absolute top-3 left-3 px-3 py-1 rounded-lg bg-white/90 backdrop-blur text-purple-700 text-xs font-semibold">
                                <?php echo esc_html($producto_oferta['categoria']); ?>
                            </div>

                            <!-- Badge de porcentaje ahorro -->
                            <div class="absolute top-3 right-3 w-16 h-16 rounded-full bg-red-500 flex items-center justify-center shadow-lg">
                                <div class="text-center">
                                    <div class="text-white font-bold text-lg leading-none"><?php echo esc_html($producto_oferta['porcentaje_ahorro']); ?></div>
                                    <div class="text-white text-xs">% OFF</div>
                                </div>
                            </div>

                            <!-- Indicador de stock -->
                            <?php if (!$producto_oferta['stock_disponible']) : ?>
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                    <span class="px-4 py-2 rounded-lg bg-gray-700 text-white font-semibold text-sm"><?php echo esc_html__('Agotado', 'flavor-chat-ia'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Contenido -->
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 line-clamp-2">
                                <?php echo esc_html($producto_oferta['titulo']); ?>
                            </h3>

                            <!-- Precios -->
                            <div class="mb-5 space-y-2 mt-auto">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-bold text-red-600">
                                        <?php echo esc_html($producto_oferta['precio_oferta']); ?>&euro;
                                    </span>
                                    <span class="text-sm text-gray-400 line-through">
                                        <?php echo esc_html($producto_oferta['precio_original']); ?>&euro;
                                    </span>
                                </div>
                                <div class="text-xs text-green-600 font-semibold">
                                    <?php
                                    $ahorros = floatval($producto_oferta['precio_original']) - floatval($producto_oferta['precio_oferta']);
                                    echo esc_html(sprintf(__('Ahorras: %s€', 'flavor-chat-ia'), number_format($ahorros, 2, ',', '.')));
                                    ?>
                                </div>
                            </div>

                            <!-- Botones -->
                            <?php if ($producto_oferta['stock_disponible']) : ?>
                                <button class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 text-white font-semibold hover:from-red-600 hover:to-orange-600 transition-all duration-300 flex items-center justify-center gap-2 group-hover:shadow-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                    </svg>
                                    <?php echo esc_html__('Comprar Ahora', 'flavor-chat-ia'); ?>
                                </button>
                            <?php else : ?>
                                <button disabled class="w-full px-4 py-3 rounded-xl bg-gray-300 text-gray-600 font-semibold cursor-not-allowed opacity-60">
                                    <?php echo esc_html__('Sin Stock', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Boton ver todas las ofertas -->
        <div class="text-center mt-12">
            <a href="#" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-purple-600 font-semibold text-lg hover:bg-white/90 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <?php echo esc_html__('Ver Todas las Ofertas', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>

<?php if ($mostrar_cuenta_regresiva && !empty($fecha_fin_oferta)) : ?>
<script>
(function() {
    const fechaFin = new Date('<?php echo esc_js($fecha_fin_oferta); ?>').getTime();

    function actualizarContador() {
        const ahora = new Date().getTime();
        const diferencia = fechaFin - ahora;

        if (diferencia <= 0) {
            document.getElementById('flavor-dias').textContent = '0';
            document.getElementById('flavor-horas').textContent = '0';
            document.getElementById('flavor-minutos').textContent = '0';
            document.getElementById('flavor-segundos').textContent = '0';
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        document.getElementById('flavor-dias').textContent = dias;
        document.getElementById('flavor-horas').textContent = String(horas).padStart(2, '0');
        document.getElementById('flavor-minutos').textContent = String(minutos).padStart(2, '0');
        document.getElementById('flavor-segundos').textContent = String(segundos).padStart(2, '0');
    }

    actualizarContador();
    setInterval(actualizarContador, 1000);
})();
</script>
<?php endif; ?>
