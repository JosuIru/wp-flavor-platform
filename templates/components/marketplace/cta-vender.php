<?php
/**
 * Template: Marketplace CTA Vender
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Tienes algo que ya no necesitas?';
$url_publicar = $url_publicar ?? '#publicar-anuncio';

$beneficios_vender = $beneficios_vender ?? [
    [
        'titulo'      => 'Sin comisiones',
        'descripcion' => 'Publica y vende sin pagar ningun tipo de comision. El 100% del beneficio es tuyo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ],
    [
        'titulo'      => 'Tus vecinos de confianza',
        'descripcion' => 'Compra y vende a personas de tu barrio. Transacciones cercanas y seguras.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
    [
        'titulo'      => 'Ecologico',
        'descripcion' => 'Reutilizar es la mejor forma de cuidar el planeta. Dale una segunda vida a tus cosas.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-20" style="background: linear-gradient(135deg, #F7FEE7 0%, #DCFCE7 100%);">
    <div class="flavor-container">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Badge -->
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-100 text-green-700 text-sm font-medium mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <?php echo esc_html__('Empieza a vender hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>

            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
                <?php echo esc_html__('Publica tu anuncio en segundos y llega a compradores de tu zona. Facil, rapido y gratis.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <!-- Beneficios -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <?php foreach ($beneficios_vender as $beneficio_item) : ?>
                    <div class="flex flex-col items-center gap-3 p-6 rounded-2xl bg-white shadow-sm border border-green-100">
                        <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $beneficio_item['icono']; ?>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800"><?php echo esc_html($beneficio_item['titulo']); ?></h3>
                        <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($beneficio_item['descripcion']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Boton CTA -->
            <a href="<?php echo esc_url($url_publicar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-lime-500 to-green-600 text-white font-semibold text-lg hover:from-lime-600 hover:to-green-700 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php echo esc_html__('Publicar Anuncio Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
