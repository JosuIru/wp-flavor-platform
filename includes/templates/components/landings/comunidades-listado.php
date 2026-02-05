<?php
/**
 * Template: Listado Comunidades
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 6;
$tipo_filtro = $tipo ?? 'todas';

$comunidades = [
    ['id' => 1, 'nombre' => 'Vecinos del Centro', 'descripcion' => 'Comunidad del barrio centro', 'miembros' => 234, 'tipo' => 'Vecinal', 'verificada' => true],
    ['id' => 2, 'nombre' => 'Runners del Parque', 'descripcion' => 'Grupo de corredores', 'miembros' => 89, 'tipo' => 'Deportiva', 'verificada' => false],
    ['id' => 3, 'nombre' => 'Club de Lectura', 'descripcion' => 'Amantes de la literatura', 'miembros' => 45, 'tipo' => 'Cultural', 'verificada' => true],
    ['id' => 4, 'nombre' => 'Voluntarios Solidarios', 'descripcion' => 'Red de voluntariado', 'miembros' => 156, 'tipo' => 'Solidaria', 'verificada' => true],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? 'Comunidades Activas'); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach (array_slice($comunidades, 0, $limite) as $comunidad): ?>
            <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100">
                <div class="h-32 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
                    <span class="text-5xl">🏘️</span>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="bg-rose-100 text-rose-700 text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo esc_html($comunidad['tipo']); ?>
                        </span>
                        <?php if ($comunidad['verificada']): ?>
                        <span class="text-green-500" title="Verificada">✓</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">
                        <a href="<?php echo esc_url(home_url('/comunidades/' . $comunidad['id'] . '/')); ?>" class="hover:text-rose-600">
                            <?php echo esc_html($comunidad['nombre']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo esc_html($comunidad['descripcion']); ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>👥 <?php echo esc_html($comunidad['miembros']); ?> miembros</span>
                        <a href="<?php echo esc_url(home_url('/comunidades/' . $comunidad['id'] . '/')); ?>" class="text-rose-600 font-medium hover:underline">
                            Unirse →
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="inline-block bg-rose-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-rose-600 transition-colors">
                Ver todas las comunidades
            </a>
        </div>
    </div>
</section>
