<?php
/**
 * Template: Hero Ayuda Vecinal
 *
 * Sección hero principal para la red de ayuda vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo = $data['titulo'] ?? 'Ayuda Vecinal';
$subtitulo = $data['subtitulo'] ?? 'Una red de apoyo mutuo entre vecinos';
$imagen_fondo = $data['imagen_fondo'] ?? '';
$mostrar_formulario = $data['mostrar_formulario'] ?? true;

// Clase de fondo según si hay imagen o no
$clase_fondo = $imagen_fondo
    ? 'bg-cover bg-center bg-no-repeat'
    : 'bg-gradient-to-br from-rose-500 via-pink-600 to-red-600';
$estilo_fondo = $imagen_fondo ? sprintf('background-image: url(%s);', esc_url($imagen_fondo)) : '';
?>

<section class="relative <?php echo esc_attr($clase_fondo); ?> text-white overflow-hidden" style="<?php echo esc_attr($estilo_fondo); ?>">
    <!-- Overlay para mejorar legibilidad -->
    <?php if ($imagen_fondo): ?>
        <div class="absolute inset-0 bg-gradient-to-br from-rose-900/85 to-red-900/85"></div>
    <?php endif; ?>

    <!-- Elementos decorativos -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-rose-400/10 rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            <!-- Contenido principal -->
            <div class="text-center lg:text-left space-y-6 animate-fade-in-up">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm font-medium border border-white/20">
                    <svg class="w-5 h-5 text-rose-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>Red de Apoyo Comunitario</span>
                </div>

                <!-- Título -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    <?php echo esc_html($titulo); ?>
                </h1>

                <!-- Subtítulo -->
                <p class="text-lg sm:text-xl text-rose-100 max-w-2xl mx-auto lg:mx-0">
                    <?php echo esc_html($subtitulo); ?>
                </p>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-3 gap-4 pt-6">
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">280+</div>
                        <div class="text-sm text-rose-200">Voluntarios</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">150+</div>
                        <div class="text-sm text-rose-200">Ayudas activas</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">98%</div>
                        <div class="text-sm text-rose-200">Satisfacción</div>
                    </div>
                </div>

                <!-- Tipos de ayuda disponibles -->
                <div class="flex flex-wrap gap-2 pt-4">
                    <span class="px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                        Transporte
                    </span>
                    <span class="px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                        Compras
                    </span>
                    <span class="px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                        Acompañamiento
                    </span>
                    <span class="px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                        Gestiones
                    </span>
                    <span class="px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                        Cuidados
                    </span>
                </div>
            </div>

            <!-- Formulario de solicitud rápida -->
            <?php if ($mostrar_formulario): ?>
                <div class="animate-fade-in-up animation-delay-200">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-rose-600 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Solicitar Ayuda</h2>
                        </div>

                        <form class="space-y-4" action="#" method="POST">
                            <!-- Tipo de ayuda -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    ¿Qué tipo de ayuda necesitas?
                                </label>
                                <select
                                    name="tipo_ayuda"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-600 focus:border-transparent transition-all text-gray-900"
                                    required
                                >
                                    <option value="">Selecciona una opción</option>
                                    <option value="transporte">Transporte</option>
                                    <option value="compras">Hacer la compra</option>
                                    <option value="acompañamiento">Acompañamiento</option>
                                    <option value="gestiones">Gestiones administrativas</option>
                                    <option value="tecnologia">Ayuda con tecnología</option>
                                    <option value="cuidados">Cuidados ocasionales</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>

                            <!-- Descripción -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Describe brevemente lo que necesitas
                                </label>
                                <textarea
                                    name="descripcion"
                                    rows="3"
                                    placeholder="Ej: Necesito ayuda para ir al médico el martes por la mañana..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-600 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 resize-none"
                                    required
                                ></textarea>
                            </div>

                            <!-- Urgencia -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ¿Cuándo lo necesitas?
                                </label>
                                <select
                                    name="urgencia"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-600 focus:border-transparent transition-all text-gray-900"
                                    required
                                >
                                    <option value="">Selecciona urgencia</option>
                                    <option value="urgente">Urgente (hoy)</option>
                                    <option value="pronto">Pronto (esta semana)</option>
                                    <option value="flexible">Flexible (sin prisa)</option>
                                </select>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button
                                    type="submit"
                                    class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 hover:shadow-lg flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    Publicar Solicitud
                                </button>

                                <a
                                    href="#voluntario"
                                    class="flex-1 bg-white hover:bg-gray-50 text-rose-600 font-semibold py-4 px-6 rounded-xl border-2 border-rose-600 transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                    Ser Voluntario
                                </a>
                            </div>
                        </form>

                        <!-- Información adicional -->
                        <div class="flex items-start gap-2 pt-4 border-t border-gray-100">
                            <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-gray-600">
                                Tu solicitud será visible solo para vecinos verificados. La ayuda es gratuita y basada en la solidaridad.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Indicador de scroll -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden lg:block">
        <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out forwards;
}

.animation-delay-200 {
    animation-delay: 0.2s;
    opacity: 0;
}
</style>
