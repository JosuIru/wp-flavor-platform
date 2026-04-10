<?php
/**
 * Componente Compartido: Breadcrumb
 *
 * @package FlavorPlatform
 * @var array $migas Array de ['label' => '', 'url' => '']
 * @var string $color_enlace
 */
if (!defined('ABSPATH')) exit;

$migas = $migas ?? [];
$color_enlace = $color_enlace ?? 'violet';

if (empty($migas)) return;
?>

<nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
    <a href="<?php echo esc_url(home_url('/')); ?>"
       class="hover:text-<?php echo esc_attr($color_enlace); ?>-600 transition-colors"
       aria-label="Inicio">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
    </a>

    <?php foreach ($migas as $indice => $miga): ?>
        <span class="text-gray-300" aria-hidden="true">›</span>

        <?php if ($indice < count($migas) - 1 && !empty($miga['url'])): ?>
            <a href="<?php echo esc_url($miga['url']); ?>"
               class="hover:text-<?php echo esc_attr($color_enlace); ?>-600 transition-colors">
                <?php echo esc_html($miga['label']); ?>
            </a>
        <?php else: ?>
            <span class="text-gray-700 font-medium" aria-current="page">
                <?php echo esc_html($miga['label']); ?>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
