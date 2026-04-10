<?php
/**
 * Template: Chat Grupos CTA Crear Grupo
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'No encuentras tu grupo?';
$url_crear = $url_crear ?? '#crear-grupo';

$beneficios_crear = $beneficios_crear ?? [
    [
        'titulo'      => 'Tu decides las reglas',
        'descripcion' => 'Establece las normas de convivencia que mejor se adapten a tu comunidad.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    ],
    [
        'titulo'      => 'Invita a quien quieras',
        'descripcion' => 'Comparte el enlace de invitacion con amigos, familiares o companeros.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>',
    ],
    [
        'titulo'      => 'Modera el contenido',
        'descripcion' => 'Herramientas de moderacion para mantener un espacio seguro y respetuoso.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-20" style="background: linear-gradient(135deg, #FDF2F8 0%, #FAE8FF 100%);">
    <div class="flavor-container">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Titulo -->
            <div class="mb-4">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-pink-100 text-pink-700 text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo esc_html__('Crea tu propia comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
                <?php echo esc_html__('Crea tu propio grupo en segundos y empieza a construir la comunidad que siempre quisiste.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <!-- Beneficios -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <?php foreach ($beneficios_crear as $beneficio_item) : ?>
                    <div class="flex flex-col items-center gap-3 p-6 rounded-2xl bg-white shadow-sm border border-pink-100">
                        <div class="w-12 h-12 rounded-xl bg-pink-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $beneficio_item['icono']; ?>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800"><?php echo esc_html($beneficio_item['titulo']); ?></h3>
                        <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($beneficio_item['descripcion']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Boton CTA -->
            <a href="<?php echo esc_url($url_crear); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-pink-500 to-fuchsia-600 text-white font-semibold text-lg hover:from-pink-600 hover:to-fuchsia-700 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php echo esc_html__('Crear mi Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
