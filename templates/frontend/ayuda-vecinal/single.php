<?php
/**
 * Frontend: Single Solicitud de Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$solicitud = $solicitud ?? [];
$titulo = $solicitud['titulo'] ?? 'Solicitud de ayuda';
$descripcion = $solicitud['descripcion'] ?? '';
$autor = $solicitud['autor'] ?? 'Anonimo';
$avatar = $solicitud['avatar'] ?? 'https://i.pravatar.cc/150?img=1';
$categoria = $solicitud['categoria'] ?? 'General';
$urgente = $solicitud['urgente'] ?? false;
$tipo = $solicitud['tipo'] ?? 'necesito'; // necesito o ofrezco
$ubicacion = $solicitud['ubicacion'] ?? '';
$fecha = $solicitud['fecha'] ?? 'Hace 1 hora';
$respuestas = $solicitud['respuestas'] ?? [];
?>

<div class="flavor-single ayuda-vecinal">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-4xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-orange-600">Inicio</a>
                <span>/</span>
                <a href="#" class="hover:text-orange-600">Ayuda Vecinal</a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($titulo); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 py-8">
        <!-- Solicitud principal -->
        <article class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-start gap-4">
                    <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($autor); ?>" class="w-14 h-14 rounded-full object-cover">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-gray-900"><?php echo esc_html($autor); ?></span>
                            <?php if ($urgente): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Urgente</span>
                            <?php endif; ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold <?php echo $tipo === 'necesito' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'; ?>">
                                <?php echo $tipo === 'necesito' ? 'Necesita ayuda' : 'Ofrece ayuda'; ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span><?php echo esc_html($fecha); ?></span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <?php echo esc_html($categoria); ?>
                            </span>
                            <?php if ($ubicacion): ?>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <?php echo esc_html($ubicacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="p-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h1>
                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($descripcion); ?>
                </div>
            </div>

            <!-- Acciones -->
            <div class="px-6 pb-6 flex items-center gap-4">
                <button class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                        style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                    <?php echo $tipo === 'necesito' ? 'Ofrecer mi ayuda' : 'Solicitar ayuda'; ?>
                </button>
                <button class="px-6 py-3 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Compartir
                </button>
            </div>
        </article>

        <!-- Respuestas -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo count($respuestas); ?> Respuestas</h2>

            <!-- Formulario de respuesta -->
            <div class="bg-white rounded-2xl p-5 shadow-md mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center text-white font-bold">
                        TU
                    </div>
                    <div class="flex-1">
                        <textarea placeholder="Escribe tu respuesta..." class="w-full p-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none" rows="3"></textarea>
                        <div class="flex justify-end mt-3">
                            <button class="px-4 py-2 rounded-xl text-white font-semibold transition-all hover:scale-105"
                                    style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                                Enviar respuesta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de respuestas -->
            <div class="space-y-4">
                <?php if (empty($respuestas)): ?>
                    <div class="bg-gray-50 rounded-xl p-8 text-center">
                        <p class="text-gray-500">Se el primero en responder a esta solicitud</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($respuestas as $respuesta): ?>
                        <div class="bg-white rounded-xl p-5 shadow-md">
                            <div class="flex items-start gap-4">
                                <img src="<?php echo esc_url($respuesta['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                     alt="<?php echo esc_attr($respuesta['autor'] ?? 'Usuario'); ?>"
                                     class="w-10 h-10 rounded-full object-cover">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900"><?php echo esc_html($respuesta['autor'] ?? 'Anonimo'); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo esc_html($respuesta['tiempo'] ?? 'Hace 1 hora'); ?></span>
                                    </div>
                                    <p class="text-gray-700"><?php echo esc_html($respuesta['contenido'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
