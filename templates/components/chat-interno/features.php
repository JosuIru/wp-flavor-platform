<?php
/**
 * Template: Chat Interno Features
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_features = $titulo_features ?? 'Todo lo que necesitas para comunicarte';

$funcionalidades_chat = $funcionalidades_chat ?? [
    [
        'titulo'      => 'Mensajes Privados',
        'descripcion' => 'Conversaciones uno a uno con total privacidad. Tus mensajes solo los veis tu y tu contacto.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'color'       => '#F43F5E',
    ],
    [
        'titulo'      => 'Grupos',
        'descripcion' => 'Crea grupos tematicos y chatea con varias personas a la vez de forma organizada.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'color'       => '#EC4899',
    ],
    [
        'titulo'      => 'Archivos Compartidos',
        'descripcion' => 'Envia y recibe imagenes, documentos y archivos de forma rapida y segura.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>',
        'color'       => '#E879F9',
    ],
    [
        'titulo'      => 'Videollamadas',
        'descripcion' => 'Inicia videollamadas directamente desde el chat sin necesidad de aplicaciones externas.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
        'color'       => '#FB7185',
    ],
    [
        'titulo'      => 'Cifrado E2E',
        'descripcion' => 'Cifrado extremo a extremo en todas las comunicaciones. Nadie mas puede leer tus mensajes.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'color'       => '#FDA4AF',
    ],
    [
        'titulo'      => 'Sin Publicidad',
        'descripcion' => 'Disfruta de una experiencia limpia sin anuncios ni interrupciones. Tu privacidad importa.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
        'color'       => '#F9A8D4',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3"><?php echo esc_html($titulo_features); ?></h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto"><?php echo esc_html__('Herramientas de comunicacion pensadas para tu comodidad y seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($funcionalidades_chat as $funcionalidad_item) : ?>
                <div class="flavor-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($funcionalidad_item['color']); ?>15;">
                        <svg class="w-6 h-6" style="color: <?php echo esc_attr($funcionalidad_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $funcionalidad_item['icono']; ?>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo esc_html($funcionalidad_item['titulo']); ?></h3>
                    <p class="text-sm text-gray-500 leading-relaxed"><?php echo esc_html($funcionalidad_item['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
