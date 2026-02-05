<?php
/**
 * Template: Formulario de Alta de Socio
 *
 * Seccion informativa sobre el proceso de registro como socio.
 * Muestra los pasos del formulario y botones de accion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$pasos_registro = [
    [
        'numero'      => '1',
        'titulo'      => 'Datos personales',
        'descripcion' => 'Introduce tu nombre, email y datos de contacto para crear tu perfil de socio.',
        'icono'       => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    ],
    [
        'numero'      => '2',
        'titulo'      => 'Tipo de socio',
        'descripcion' => 'Selecciona el plan de membresia que mejor se adapte a tus necesidades.',
        'icono'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    ],
    [
        'numero'      => '3',
        'titulo'      => 'Pago seguro',
        'descripcion' => 'Completa el pago de forma segura. Puedes cancelar en cualquier momento.',
        'icono'       => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Registro Facil y Rapido'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Hazte socio en solo 3 sencillos pasos. El proceso completo tarda menos de 5 minutos.'); ?>
            </p>
            <div class="w-20 h-1 bg-rose-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Pasos del formulario -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto mb-14">
            <?php foreach ($pasos_registro as $indice_paso => $paso_actual): ?>
                <div class="relative group text-center">
                    <!-- Linea conectora (solo entre pasos, no despues del ultimo) -->
                    <?php if ($indice_paso < count($pasos_registro) - 1): ?>
                        <div class="hidden md:block absolute top-10 left-[60%] w-[80%] h-0.5 bg-rose-200"></div>
                    <?php endif; ?>

                    <!-- Numero del paso -->
                    <div class="relative z-10 w-20 h-20 bg-gradient-to-br from-rose-400 to-rose-500 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg group-hover:scale-110 transition duration-300">
                        <span class="text-2xl font-bold text-white"><?php echo esc_html($paso_actual['numero']); ?></span>
                    </div>

                    <!-- Icono -->
                    <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($paso_actual['icono']); ?>" />
                        </svg>
                    </div>

                    <!-- Titulo del paso -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        <?php echo esc_html($paso_actual['titulo']); ?>
                    </h3>

                    <!-- Descripcion -->
                    <p class="text-gray-600 text-sm leading-relaxed max-w-xs mx-auto">
                        <?php echo esc_html($paso_actual['descripcion']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informacion adicional -->
        <div class="max-w-2xl mx-auto bg-rose-50 rounded-xl p-6 mb-10">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-rose-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-rose-700 text-sm leading-relaxed">
                    <?php echo esc_html($info_adicional ?? 'Solo necesitas un email valido y un metodo de pago para los planes de pago. Los datos se procesan de forma segura y nunca se comparten con terceros.'); ?>
                </p>
            </div>
        </div>

        <!-- Botones de accion -->
        <div class="text-center">
            <!-- CTA principal -->
            <div class="mb-4">
                <a href="<?php echo esc_url($boton_url ?? '/socios/unirme/'); ?>"
                   class="inline-flex items-center bg-rose-500 hover:bg-rose-600 text-white px-10 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Comenzar Registro'); ?>
                </a>
            </div>

            <!-- Enlace secundario -->
            <a href="<?php echo esc_url($enlace_secundario_url ?? '/socios/mi-perfil/'); ?>"
               class="inline-flex items-center text-rose-600 hover:text-rose-700 font-medium transition duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <?php echo esc_html($enlace_secundario_texto ?? 'Ya soy socio'); ?>
            </a>
        </div>
    </div>
</section>
