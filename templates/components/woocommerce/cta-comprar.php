<?php
/**
 * Template: WooCommerce CTA Comprar
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Descubre nuestro catalogo';
$descripcion_cta = $descripcion_cta ?? 'Miles de productos esperan por ti. Envio rapido, pago seguro y satisfaccion garantizada.';
$url_productos = $url_productos ?? '#todos-productos';

$garantias_compra = $garantias_compra ?? [
    [
        'titulo' => 'Envio Gratuito',
        'descripcion' => 'En pedidos superiores a 49 euros. Entrega en 24-48 horas laborables.',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>',
    ],
    [
        'titulo' => 'Pago Seguro',
        'descripcion' => 'Tus datos protegidos con cifrado SSL. Tarjeta, PayPal, Bizum y mas metodos.',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
    ],
    [
        'titulo' => 'Satisfaccion Garantizada',
        'descripcion' => 'Devolucion gratuita en los primeros 30 dias si no quedas satisfecho.',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-20" style="background: linear-gradient(135deg, #FAF5FF 0%, #EEF2FF 100%);">
    <div class="flavor-container">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Badge -->
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-100 text-purple-700 text-sm font-medium mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <?php echo esc_html__('Tienda online', 'flavor-chat-ia'); ?>
            </span>

            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
                <?php echo esc_html($descripcion_cta); ?>
            </p>

            <!-- Garantias -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <?php foreach ($garantias_compra as $garantia_item) : ?>
                    <div class="flex flex-col items-center gap-3 p-6 rounded-2xl bg-white shadow-sm border border-purple-100">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $garantia_item['icono']; ?>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800"><?php echo esc_html($garantia_item['titulo']); ?></h3>
                        <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($garantia_item['descripcion']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Metodos de pago -->
            <div class="flex items-center justify-center gap-4 mb-8">
                <div class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-600">Visa</div>
                <div class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-600">MasterCard</div>
                <div class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-600">PayPal</div>
                <div class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-sm font-medium text-gray-600">Bizum</div>
            </div>

            <!-- Boton CTA -->
            <a href="<?php echo esc_url($url_productos); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold text-lg hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <?php echo esc_html__('Ver Todos los Productos', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>
