<?php
/**
 * Template: Navegacion de Generos Literarios
 * Navegacion sticky con filtros por genero literario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Explorar por Genero';
$genero_activo = $genero_activo ?? 'todos';
$mostrar_contador = $mostrar_contador ?? true;

$generos = [
    'todos' => ['nombre' => 'Todos', 'cantidad' => 3500, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'],
    'ficcion' => ['nombre' => 'Ficcion', 'cantidad' => 890, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'],
    'no-ficcion' => ['nombre' => 'No Ficcion', 'cantidad' => 650, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'],
    'infantil' => ['nombre' => 'Infantil', 'cantidad' => 420, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    'juvenil' => ['nombre' => 'Juvenil', 'cantidad' => 310, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>'],
    'ciencia' => ['nombre' => 'Ciencia', 'cantidad' => 275, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>'],
    'historia' => ['nombre' => 'Historia', 'cantidad' => 340, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    'fantasia' => ['nombre' => 'Fantasia', 'cantidad' => 220, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>'],
    'biografia' => ['nombre' => 'Biografia', 'cantidad' => 145, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'],
    'poesia' => ['nombre' => 'Poesia', 'cantidad' => 95, 'icono' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'],
];
?>

<nav class="flavor-component sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-md border-b border-indigo-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-3 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900"><?php echo esc_html($titulo); ?></h2>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Filtra nuestra coleccion por categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>

        <div class="py-3">
            <div class="overflow-x-auto scrollbar-hide">
                <div class="flex items-center gap-2 min-w-max pb-1">
                    <?php foreach ($generos as $slug => $genero):
                        $activo = ($slug === $genero_activo);
                    ?>
                        <a href="<?php echo esc_url(add_query_arg('genero', $slug)); ?>"
                           class="flex items-center gap-2 px-4 py-2.5 rounded-full font-medium text-sm whitespace-nowrap transition-all duration-300 <?php echo $activo
                               ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-indigo-500/30 scale-105'
                               : 'bg-gray-100 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:shadow-md'; ?>">
                            <span class="<?php echo $activo ? 'text-white' : 'text-indigo-500'; ?>">
                                <?php echo $genero['icono']; ?>
                            </span>
                            <span><?php echo esc_html($genero['nombre']); ?></span>
                            <?php if ($mostrar_contador): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs <?php echo $activo
                                    ? 'bg-white/20 text-white'
                                    : 'bg-indigo-100 text-indigo-600'; ?>">
                                    <?php echo esc_html(number_format($genero['cantidad'])); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
