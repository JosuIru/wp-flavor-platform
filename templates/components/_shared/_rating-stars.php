<?php
/**
 * Componente Compartido: Estrellas de Valoración
 *
 * @package FlavorPlatform
 * @var float $valoracion 0.0 - 5.0
 * @var int $total_valoraciones
 * @var bool $mostrar_numero
 * @var bool $mostrar_total
 * @var string $tamano 'sm', 'md', 'lg'
 * @var bool $interactivo
 * @var string $nombre_campo
 */
if (!defined('ABSPATH')) exit;

$valoracion = $valoracion ?? 0;
$total_valoraciones = $total_valoraciones ?? 0;
$mostrar_numero = $mostrar_numero ?? true;
$mostrar_total = $mostrar_total ?? false;
$tamano = $tamano ?? 'md';
$interactivo = $interactivo ?? false;
$nombre_campo = $nombre_campo ?? 'valoracion';

$clases_tamano = [
    'sm' => 'text-sm gap-0.5',
    'md' => 'text-base gap-1',
    'lg' => 'text-xl gap-1',
];
$clase_tamano = $clases_tamano[$tamano] ?? $clases_tamano['md'];
?>

<div class="flex items-center <?php echo esc_attr($clase_tamano); ?>"
     <?php if ($interactivo): ?>role="radiogroup" aria-label="Valoración"<?php endif; ?>>

    <?php if ($interactivo): ?>
        <input type="hidden" name="<?php echo esc_attr($nombre_campo); ?>" value="<?php echo esc_attr($valoracion); ?>" id="flavor-rating-value">
    <?php endif; ?>

    <div class="flex <?php echo esc_attr($clase_tamano); ?> text-yellow-400"
         <?php if ($interactivo): ?>id="flavor-rating-stars"<?php endif; ?>>
        <?php for ($estrella = 1; $estrella <= 5; $estrella++): ?>
            <?php if ($interactivo): ?>
                <button type="button"
                        class="flavor-star cursor-pointer hover:scale-110 transition-transform focus:outline-none focus:ring-2 focus:ring-yellow-300 rounded"
                        data-value="<?php echo esc_attr($estrella); ?>"
                        aria-label="<?php echo esc_attr($estrella); ?> estrella<?php echo $estrella > 1 ? 's' : ''; ?>"
                        role="radio"
                        aria-checked="<?php echo $estrella <= $valoracion ? 'true' : 'false'; ?>">
                    <?php echo $estrella <= $valoracion ? '★' : '☆'; ?>
                </button>
            <?php else: ?>
                <?php if ($estrella <= floor($valoracion)): ?>
                    <span aria-hidden="true">★</span>
                <?php elseif ($estrella - 0.5 <= $valoracion): ?>
                    <span aria-hidden="true" class="relative">
                        <span class="text-gray-300">★</span>
                        <span class="absolute inset-0 overflow-hidden" style="width: 50%;">★</span>
                    </span>
                <?php else: ?>
                    <span aria-hidden="true" class="text-gray-300">★</span>
                <?php endif; ?>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <?php if ($mostrar_numero): ?>
        <span class="text-gray-700 font-semibold ml-1">
            <?php echo esc_html(number_format($valoracion, 1)); ?>
        </span>
    <?php endif; ?>

    <?php if ($mostrar_total && $total_valoraciones > 0): ?>
        <span class="text-gray-500 text-sm">
            (<?php echo esc_html($total_valoraciones); ?> valoracion<?php echo $total_valoraciones !== 1 ? 'es' : ''; ?>)
        </span>
    <?php endif; ?>

    <span class="sr-only">
        <?php echo esc_html(number_format($valoracion, 1)); ?> de 5 estrellas
        <?php if ($total_valoraciones > 0): ?>
            basado en <?php echo esc_html($total_valoraciones); ?> valoraciones
        <?php endif; ?>
    </span>
</div>

<?php if ($interactivo): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var contenedorEstrellas = document.getElementById('flavor-rating-stars');
    var campoValor = document.getElementById('flavor-rating-value');
    if (!contenedorEstrellas || !campoValor) return;

    contenedorEstrellas.querySelectorAll('.flavor-star').forEach(function(botonEstrella) {
        botonEstrella.addEventListener('click', function() {
            var valorSeleccionado = parseInt(this.dataset.value);
            campoValor.value = valorSeleccionado;

            contenedorEstrellas.querySelectorAll('.flavor-star').forEach(function(estrella, indice) {
                estrella.textContent = (indice + 1) <= valorSeleccionado ? '★' : '☆';
                estrella.setAttribute('aria-checked', (indice + 1) <= valorSeleccionado ? 'true' : 'false');
            });
        });
    });
});
</script>
<?php endif; ?>
