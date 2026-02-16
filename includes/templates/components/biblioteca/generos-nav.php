<!-- Navegación de Géneros -->
<div class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-4">
            <!-- Título de sección móvil -->
            <div class="flex items-center justify-between mb-4 md:mb-0">
                <h3 class="text-lg font-semibold text-gray-900 md:hidden">Explorar por Género</h3>

                <!-- Botón hamburguesa para móvil -->
                <button class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors" onclick="toggleGenresMenu()">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Navegación horizontal (escritorio) / acordeón (móvil) -->
            <nav id="genresMenu" class="hidden md:block">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Géneros principales -->
                    <div class="flex flex-col md:flex-row gap-2 md:gap-1 flex-wrap">
                        <!-- Todos -->
                        <a href="<?php echo esc_url(add_query_arg('genero', '', remove_query_arg('genero'))); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg bg-purple-600 text-white font-medium transition-all duration-300 hover:bg-purple-700 hover:shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            <span>Todos</span>
                            <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs font-bold">124</span>
                        </a>

                        <!-- Literatura -->
                        <a href="<?php echo esc_url(add_query_arg('genero', 'literatura')); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg text-gray-700 font-medium transition-all duration-300 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-amber-500 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Literatura</span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">42</span>
                        </a>

                        <!-- Ciencia -->
                        <a href="<?php echo esc_url(add_query_arg('genero', 'ciencia')); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg text-gray-700 font-medium transition-all duration-300 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-blue-500 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <span>Ciencia</span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">28</span>
                        </a>

                        <!-- Historia -->
                        <a href="<?php echo esc_url(add_query_arg('genero', 'historia')); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg text-gray-700 font-medium transition-all duration-300 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-rose-500 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Historia</span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">19</span>
                        </a>

                        <!-- Arte -->
                        <a href="<?php echo esc_url(add_query_arg('genero', 'arte')); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg text-gray-700 font-medium transition-all duration-300 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-pink-500 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                            <span>Arte</span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">15</span>
                        </a>

                        <!-- Tecnología -->
                        <a href="<?php echo esc_url(add_query_arg('genero', 'tecnologia')); ?>" class="group flex items-center px-4 py-2.5 md:py-2 rounded-lg text-gray-700 font-medium transition-all duration-300 hover:bg-purple-50 hover:text-purple-700 hover:shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-green-500 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Tecnología</span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">20</span>
                        </a>
                    </div>

                    <!-- Botón Ver Más -->
                    <button class="flex items-center justify-center md:justify-start px-4 py-2.5 md:py-2 text-purple-600 font-medium hover:bg-purple-50 rounded-lg transition-all duration-300 group">
                        <span>Más géneros</span>
                        <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Categorías secundarias (collapsible) -->
        <div class="border-t border-gray-100 py-3 hidden" id="secondaryCategories">
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo esc_url(add_query_arg('genero', 'filosofia')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Filosofía <span class="text-xs">(8)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'biografias')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Biografías <span class="text-xs">(12)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'poesia')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Poesía <span class="text-xs">(6)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'ensayo')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Ensayo <span class="text-xs">(11)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'autoayuda')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Autoayuda <span class="text-xs">(9)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'economia')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Economía <span class="text-xs">(7)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'sociologia')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Sociología <span class="text-xs">(5)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'psicologia')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Psicología <span class="text-xs">(10)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'religion')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Religión <span class="text-xs">(4)</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('genero', 'musica')); ?>" class="px-3 py-1.5 text-sm text-gray-600 bg-gray-50 rounded-full hover:bg-purple-50 hover:text-purple-600 transition-colors">
                    Música <span class="text-xs">(6)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Breadcrumb / Filtro activo -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm text-gray-600">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-purple-600 transition-colors">Inicio</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="hover:text-purple-600 transition-colors">Biblioteca</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 font-medium">Todos los libros</span>
        </div>

        <!-- Contador de resultados -->
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">
                Mostrando <span class="font-semibold text-gray-900">1-24</span> de <span class="font-semibold text-gray-900">124</span> libros
            </span>
        </div>
    </div>

    <!-- Filtros activos -->
    <div class="flex items-center gap-2 mt-3 flex-wrap" id="activeFilters">
        <!-- Este div se mostraría solo cuando hay filtros activos -->
        <!-- <div class="flex items-center gap-2 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium">
            <span>Literatura</span>
            <button class="hover:text-purple-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div> -->
    </div>
</div>

<script>
function toggleGenresMenu() {
    const menu = document.getElementById('genresMenu');
    menu.classList.toggle('hidden');
}

// Cerrar menú en escritorio cuando se cambia el tamaño de pantalla
window.addEventListener('resize', function() {
    const menu = document.getElementById('genresMenu');
    if (window.innerWidth >= 768) {
        menu.classList.remove('hidden');
    }
});
</script>

<style>
/* Asegurar que el menú esté visible en escritorio */
@media (min-width: 768px) {
    #genresMenu {
        display: block !important;
    }
}
</style>
