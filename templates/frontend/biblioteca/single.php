<?php
/**
 * Frontend: Single Libro
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$libro = $libro ?? [];
$titulo = $libro['titulo'] ?? 'Libro';
$autor = $libro['autor'] ?? 'Autor desconocido';
$descripcion = $libro['descripcion'] ?? '';
$portada = $libro['portada'] ?? 'https://picsum.photos/seed/libro1/400/600';
$genero = $libro['genero'] ?? 'General';
$disponible = $libro['disponible'] ?? true;
$propietario = $libro['propietario'] ?? [];
$resenas = $libro['resenas'] ?? [];
?>

<div class="flavor-single biblioteca">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-5xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-indigo-600"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
                <span>/</span>
                <a href="#" class="hover:text-indigo-600"><?php echo esc_html__('Biblioteca', 'flavor-chat-ia'); ?></a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($titulo); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-5xl px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Portada -->
            <div class="md:col-span-1">
                <div class="sticky top-4">
                    <div class="relative aspect-[2/3] rounded-2xl overflow-hidden shadow-xl">
                        <img src="<?php echo esc_url($portada); ?>"
                             alt="<?php echo esc_attr($titulo); ?>"
                             class="w-full h-full object-cover">
                        <span class="absolute top-3 right-3 px-3 py-1 rounded-full text-sm font-bold <?php echo $disponible ? 'bg-green-500' : 'bg-orange-500'; ?> text-white">
                            <?php echo $disponible ? 'Disponible' : 'Prestado'; ?>
                        </span>
                    </div>

                    <?php if ($disponible): ?>
                        <button class="w-full mt-4 py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                            <?php echo esc_html__('Solicitar Prestamo', 'flavor-chat-ia'); ?>
                        </button>
                    <?php else: ?>
                        <button class="w-full mt-4 py-4 rounded-xl text-lg font-semibold text-white bg-gray-400 cursor-not-allowed">
                            <?php echo esc_html__('No Disponible', 'flavor-chat-ia'); ?>
                        </button>
                        <p class="text-sm text-gray-500 text-center mt-2">
                            <?php echo esc_html__('Disponible en aproximadamente 2 semanas', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-700 mb-4">
                        <?php echo esc_html($genero); ?>
                    </span>

                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html($titulo); ?></h1>
                    <p class="text-xl text-gray-600 mb-6">por <?php echo esc_html($autor); ?></p>

                    <div class="prose max-w-none text-gray-700">
                        <?php echo wp_kses_post($descripcion); ?>
                    </div>
                </div>

                <!-- Propietario -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4"><?php echo esc_html__('Compartido por', 'flavor-chat-ia'); ?></h2>
                    <div class="flex items-center gap-4">
                        <img src="<?php echo esc_url($propietario['avatar'] ?? 'https://i.pravatar.cc/150?img=1'); ?>"
                             alt="<?php echo esc_attr($propietario['nombre'] ?? 'Usuario'); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($propietario['nombre'] ?? 'Vecino'); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($propietario['libros_compartidos'] ?? 5); ?> libros compartidos</p>
                        </div>
                        <a href="#" class="ml-auto px-4 py-2 rounded-xl text-indigo-600 bg-indigo-50 font-medium hover:bg-indigo-100 transition-colors">
                            <?php echo esc_html__('Ver perfil', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>

                <!-- Resenas -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Resenas de vecinos', 'flavor-chat-ia'); ?></h2>
                        <button class="text-indigo-600 font-medium text-sm hover:text-indigo-700">
                            <?php echo esc_html__('Escribir resena', 'flavor-chat-ia'); ?>
                        </button>
                    </div>

                    <?php if (empty($resenas)): ?>
                        <div class="bg-gray-50 rounded-xl p-6 text-center">
                            <p class="text-gray-500"><?php echo esc_html__('Se el primero en dejar una resena', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($resenas as $resena): ?>
                                <div class="border-b border-gray-100 pb-4 last:border-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="<?php echo esc_url($resena['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                             alt="" class="w-8 h-8 rounded-full object-cover">
                                        <span class="font-medium text-gray-900"><?php echo esc_html($resena['autor'] ?? 'Anonimo'); ?></span>
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 0; $i < ($resena['estrellas'] ?? 5); $i++): ?>
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-700"><?php echo esc_html($resena['texto'] ?? ''); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
