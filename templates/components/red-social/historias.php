<?php
/**
 * Template: Carrusel de Historias
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Historias de la Comunidad';
$descripcion = $args['descripcion'] ?? 'Actualizado hace 24 horas';
$mostrar_crear = $args['mostrar_crear'] ?? true;
$tiempo_duracion = $args['tiempo_duracion'] ?? 6; // segundos

// Historias de ejemplo
$historias = [
    [
        'id' => 1,
        'autor' => 'Rosa Martínez',
        'avatar' => 'https://i.pravatar.cc/150?img=12',
        'imagen_portada' => 'https://picsum.photos/seed/historia1/400/600',
        'visto' => false,
        'duracion_segundos' => 5,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia1/400/600', 'texto' => 'Mi primer día en el huerto comunitario!'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia1b/400/600', 'texto' => '¡Miren qué tomates! 🍅'],
        ]
    ],
    [
        'id' => 2,
        'autor' => 'Asociación Vecinos',
        'avatar' => 'https://i.pravatar.cc/150?img=67',
        'imagen_portada' => 'https://picsum.photos/seed/historia2/400/600',
        'visto' => true,
        'duracion_segundos' => 7,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia2/400/600', 'texto' => 'Asamblea de vecinos hoy a las 19:00'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia2b/400/600', 'texto' => 'Temas importantes que tratar'],
        ]
    ],
    [
        'id' => 3,
        'autor' => 'Juan Carlos',
        'avatar' => 'https://i.pravatar.cc/150?img=45',
        'imagen_portada' => 'https://picsum.photos/seed/historia3/400/600',
        'visto' => false,
        'duracion_segundos' => 6,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia3/400/600', 'texto' => 'Ruta en bici este domingo'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia3b/400/600', 'texto' => 'Salida a las 9:00 AM del parque'],
        ]
    ],
    [
        'id' => 4,
        'autor' => 'Eco Mercado Barrial',
        'avatar' => 'https://i.pravatar.cc/150?img=55',
        'imagen_portada' => 'https://picsum.photos/seed/historia4/400/600',
        'visto' => true,
        'duracion_segundos' => 5,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia4/400/600', 'texto' => 'Nuevos productos de granja'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia4b/400/600', 'texto' => 'Frutas y verduras de km0'],
        ]
    ],
    [
        'id' => 5,
        'autor' => 'Marta López',
        'avatar' => 'https://i.pravatar.cc/150?img=33',
        'imagen_portada' => 'https://picsum.photos/seed/historia5/400/600',
        'visto' => false,
        'duracion_segundos' => 8,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia5/400/600', 'texto' => 'Mi perrita necesita un hogar'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia5b/400/600', 'texto' => 'Es muy cariñosa y dócil ❤️'],
        ]
    ],
    [
        'id' => 6,
        'autor' => 'Centro Cívico',
        'avatar' => 'https://i.pravatar.cc/150?img=70',
        'imagen_portada' => 'https://picsum.photos/seed/historia6/400/600',
        'visto' => true,
        'duracion_segundos' => 6,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia6/400/600', 'texto' => 'Taller de artesanía este sábado'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia6b/400/600', 'texto' => 'Inscripción abierta - ¡Plazas limitadas!'],
        ]
    ],
    [
        'id' => 7,
        'autor' => 'Panadería La Espiga',
        'avatar' => 'https://i.pravatar.cc/150?img=60',
        'imagen_portada' => 'https://picsum.photos/seed/historia7/400/600',
        'visto' => false,
        'duracion_segundos' => 5,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia7/400/600', 'texto' => '20% desc. en pan de masa madre'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia7b/400/600', 'texto' => 'Código: VECINO20 - Esta semana'],
        ]
    ],
    [
        'id' => 8,
        'autor' => 'Club Deportivo Local',
        'avatar' => 'https://i.pravatar.cc/150?img=48',
        'imagen_portada' => 'https://picsum.photos/seed/historia8/400/600',
        'visto' => false,
        'duracion_segundos' => 6,
        'contenido' => [
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia8/400/600', 'texto' => 'Partido amistoso de fútbol mañana'],
            ['tipo' => 'imagen', 'url' => 'https://picsum.photos/seed/historia8b/400/600', 'texto' => 'Todos los niveles bienvenidos'],
        ]
    ],
];
?>

<section class="flavor-component py-12 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-gray-600">
                    <?php echo esc_html($descripcion); ?>
                </p>
            </div>

            <!-- Carrusel de Historias -->
            <div class="flavor-stories-carousel">
                <div class="overflow-x-auto scrollbar-hide">
                    <div class="flex gap-4 pb-4" style="min-width: max-content;">
                        <!-- Crear Historia -->
                        <?php if ($mostrar_crear): ?>
                            <div class="flavor-create-story group cursor-pointer flex-shrink-0">
                                <div class="w-28 h-40 rounded-xl overflow-hidden shadow-lg border-2 border-transparent hover:border-blue-400 transition-all duration-300 bg-gradient-to-br from-blue-50 to-indigo-50 relative">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white mb-2 group-hover:scale-110 transition-transform">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700 text-center px-2">
                                            <?php echo esc_html__('Tu Historia', 'flavor-chat-ia'); ?>
                                        </p>
                                    </div>
                                    <div class="absolute top-2 left-2 right-2 flex items-center gap-0.5">
                                        <div class="w-1 h-1 rounded-full bg-gray-400"></div>
                                        <div class="w-1 h-1 rounded-full bg-gray-400"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Historias -->
                        <?php foreach ($historias as $historia): ?>
                            <div class="flavor-story-item group cursor-pointer flex-shrink-0 relative" data-story-id="<?php echo esc_attr($historia['id']); ?>">
                                <!-- Tarjeta de Historia -->
                                <div class="w-28 h-40 rounded-xl overflow-hidden shadow-lg border-4 transition-all duration-300 <?php echo $historia['visto'] ? 'border-gray-300 opacity-75' : 'border-blue-400 group-hover:border-blue-500'; ?>">
                                    <!-- Imagen de Portada -->
                                    <img src="<?php echo esc_url($historia['imagen_portada']); ?>" alt="<?php echo esc_attr($historia['autor']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                                    <!-- Barra de Progreso Superior -->
                                    <div class="absolute top-0 inset-x-0 h-1 bg-white/30">
                                        <div class="h-full bg-white rounded-full transition-all duration-300 group-hover:w-full" style="width: 0%;"></div>
                                    </div>

                                    <!-- Avatar y Nombre -->
                                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                                        <div class="flex items-center gap-1.5">
                                            <img src="<?php echo esc_url($historia['avatar']); ?>" alt="<?php echo esc_attr($historia['autor']); ?>" class="w-6 h-6 rounded-full border-2 border-white object-cover">
                                            <span class="text-xs font-bold text-white truncate">
                                                <?php echo esc_html($historia['autor']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Indicador de Visto -->
                                    <?php if ($historia['visto']): ?>
                                        <div class="absolute top-2 right-2 w-4 h-4 rounded-full bg-white/80 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    <?php else: ?>
                                        <div class="absolute top-2 right-2 w-3 h-3 rounded-full bg-blue-500 animate-pulse"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Vista Detallada de Historia (Modal Oculto) -->
            <div id="flavor-story-viewer" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4">
                <div class="relative w-full max-w-md">
                    <!-- Contenedor Principal -->
                    <div class="relative bg-black rounded-2xl overflow-hidden aspect-video">
                        <!-- Imagen/Contenido -->
                        <img id="story-image" src="" alt="" class="w-full h-full object-cover">

                        <!-- Overlay Superior -->
                        <div class="absolute top-0 inset-x-0 bg-gradient-to-b from-black/60 to-transparent p-4">
                            <!-- Barras de Progreso -->
                            <div class="flex gap-1 mb-3">
                                <div id="story-progress" class="h-1 flex-1 bg-white/30 rounded-full overflow-hidden">
                                    <div class="h-full bg-white w-0 transition-all duration-300"></div>
                                </div>
                            </div>

                            <!-- Header con Avatar y Nombre -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <img id="story-avatar" src="" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-white">
                                    <div>
                                        <p id="story-author" class="text-sm font-bold text-white"></p>
                                        <p class="text-xs text-white/80">
                                            <?php echo esc_html__('Hace 2h', 'flavor-chat-ia'); ?>
                                        </p>
                                    </div>
                                </div>
                                <button id="close-story" class="p-2 rounded-full hover:bg-white/20 transition-colors">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Texto de Historia -->
                        <div class="absolute inset-0 flex items-end p-4">
                            <p id="story-text" class="text-lg font-semibold text-white drop-shadow-lg text-center w-full"></p>
                        </div>

                        <!-- Botones de Navegación -->
                        <button id="prev-story" class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-full hover:bg-white/20 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button id="next-story" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 rounded-full hover:bg-white/20 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <!-- Acciones Inferiores -->
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <button class="p-2 rounded-full hover:bg-white/20 transition-colors">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 rounded-full hover:bg-white/20 transition-colors">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 rounded-full hover:bg-white/20 transition-colors">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                        </svg>
                                    </button>
                                </div>
                                <button class="px-4 py-2 rounded-full bg-white text-black font-semibold hover:bg-gray-100 transition-colors text-sm">
                                    <?php echo esc_html__('Seguir', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 text-center">
                    <svg class="w-8 h-8 text-blue-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('24 Horas', 'flavor-chat-ia'); ?>
                    </h3>
                    <p class="text-sm text-gray-600">
                        <?php echo esc_html__('Las historias desaparecen después de 24 horas', 'flavor-chat-ia'); ?>
                    </p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 text-center">
                    <svg class="w-8 h-8 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Actualizaciones Rápidas', 'flavor-chat-ia'); ?>
                    </h3>
                    <p class="text-sm text-gray-600">
                        <?php echo esc_html__('Comparte momentos sin afectar tu feed', 'flavor-chat-ia'); ?>
                    </p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 text-center">
                    <svg class="w-8 h-8 text-purple-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h3 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Privacidad', 'flavor-chat-ia'); ?>
                    </h3>
                    <p class="text-sm text-gray-600">
                        <?php echo esc_html__('Solo tus conexiones ven tus historias', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const storyItems = document.querySelectorAll('.flavor-story-item');
    const storyViewer = document.getElementById('flavor-story-viewer');
    const closeBtn = document.getElementById('close-story');
    const storyData = <?php echo json_encode($historias); ?>;
    let currentStoryIndex = 0;

    storyItems.forEach((item, index) => {
        item.addEventListener('click', function() {
            currentStoryIndex = index;
            displayStory(index);
            storyViewer.classList.remove('hidden');
            startStoryProgress();
        });
    });

    closeBtn.addEventListener('click', function() {
        storyViewer.classList.add('hidden');
    });

    document.getElementById('next-story').addEventListener('click', function() {
        if (currentStoryIndex < storyData.length - 1) {
            currentStoryIndex++;
            displayStory(currentStoryIndex);
        }
    });

    document.getElementById('prev-story').addEventListener('click', function() {
        if (currentStoryIndex > 0) {
            currentStoryIndex--;
            displayStory(currentStoryIndex);
        }
    });

    function displayStory(index) {
        const historia = storyData[index];
        const primeraImagen = historia.contenido[0];

        document.getElementById('story-image').src = primeraImagen.url;
        document.getElementById('story-author').textContent = historia.autor;
        document.getElementById('story-avatar').src = historia.avatar;
        document.getElementById('story-text').textContent = primeraImagen.texto;
    }

    function startStoryProgress() {
        const progressBar = document.getElementById('story-progress').querySelector('div');
        progressBar.style.width = '0%';

        const historia = storyData[currentStoryIndex];
        const duracion = (historia.duracion_segundos || 6) * 1000;

        progressBar.style.animation = `none`;
        setTimeout(() => {
            progressBar.style.transition = `width ${duracion}ms linear`;
            progressBar.style.width = '100%';
        }, 10);
    }
});
</script>
