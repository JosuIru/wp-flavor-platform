<?php
/**
 * Frontend: Formulario de Publicar Anuncio en Marketplace
 *
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$usuario_id = get_current_user_id();

if (!$usuario_id) {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/mi-portal/marketplace/publicar/';
    $request_url = home_url('/' . ltrim($request_uri, '/'));
    echo '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">';
    echo '<p class="text-yellow-800">' . esc_html__('Debes iniciar sesión para publicar un anuncio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(wp_login_url($request_url)) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . esc_html__('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

// Obtener categorías
$categorias = get_terms([
    'taxonomy'   => 'marketplace_categoria',
    'hide_empty' => false,
]);

// Condiciones del producto
$condiciones = [
    'nuevo'      => __('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'como_nuevo' => __('Como nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'buen_estado'=> __('Buen estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'usado'      => __('Usado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'para_piezas'=> __('Para piezas', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Tipos de transacción
$tipos = [
    'venta'       => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'intercambio' => __('Intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'regalo'      => __('Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="flavor-frontend flavor-marketplace-formulario">
    <!-- Header -->
    <div class="bg-gradient-to-r from-lime-500 to-green-600 text-white rounded-2xl p-6 mb-6 shadow-lg">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center text-3xl">📢</div>
            <div>
                <h2 class="text-2xl font-bold mb-1"><?php echo esc_html__('Publicar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="text-lime-100"><?php echo esc_html__('Vende, intercambia o regala productos a tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <form id="marketplace-publicar-form" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" enctype="multipart/form-data">
        <?php wp_nonce_field('marketplace_publicar', 'marketplace_nonce'); ?>
        <?php
        // Campo oculto para comunidad_id (si se publica desde una comunidad)
        // Primero intenta desde el shortcode, luego desde GET
        $comunidad_id = isset($marketplace_comunidad_id) && $marketplace_comunidad_id > 0
            ? absint($marketplace_comunidad_id)
            : (isset($_GET['comunidad_id']) ? absint($_GET['comunidad_id']) : 0);

        if ($comunidad_id > 0):
            // Obtener nombre de la comunidad para mostrar al usuario
            global $wpdb;
            $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
            $comunidad_nombre = $wpdb->get_var($wpdb->prepare(
                "SELECT nombre FROM {$tabla_comunidades} WHERE id = %d AND estado = 'activa'",
                $comunidad_id
            ));
        ?>
        <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad_id); ?>">
        <?php if ($comunidad_nombre): ?>
        <div class="mb-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
            <p class="text-blue-800 flex items-center gap-2">
                <span class="dashicons dashicons-groups"></span>
                <?php printf(
                    esc_html__('Este anuncio se publicará en la comunidad: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '<strong>' . esc_html($comunidad_nombre) . '</strong>'
                ); ?>
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Columna izquierda -->
            <div class="space-y-5">
                <!-- Título -->
                <div>
                    <label for="mp-titulo" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Título del anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="mp-titulo" name="titulo" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors"
                           placeholder="<?php echo esc_attr__('Ej: Bicicleta de montaña en perfecto estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <!-- Descripción -->
                <div>
                    <label for="mp-descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="text-red-500">*</span>
                    </label>
                    <textarea id="mp-descripcion" name="descripcion" rows="5" required
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors resize-none"
                              placeholder="<?php echo esc_attr__('Describe tu producto con detalle: características, estado, motivo de venta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <!-- Categoría y Tipo -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="mp-categoria" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="mp-categoria" name="categoria"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors">
                            <option value=""><?php echo esc_html__('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php if (!is_wp_error($categorias) && !empty($categorias)): ?>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="mp-tipo" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Tipo de transacción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="mp-tipo" name="tipo"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors">
                            <?php foreach ($tipos as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Precio y Condición -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="mp-precio" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Precio (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="number" id="mp-precio" name="precio" min="0" step="0.01"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors"
                               placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html__('Deja en blanco para "A negociar"', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                    <div>
                        <label for="mp-condicion" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Condición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select id="mp-condicion" name="condicion"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors">
                            <?php foreach ($condiciones as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Ubicación -->
                <div>
                    <label for="mp-ubicacion" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <input type="text" id="mp-ubicacion" name="ubicacion"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-colors"
                           placeholder="<?php echo esc_attr__('Ej: Centro, Madrid', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <!-- Columna derecha - Imágenes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <?php echo esc_html__('Fotos del producto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>

                <div id="mp-imagenes-dropzone"
                     class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-lime-500 transition-colors cursor-pointer bg-gray-50">
                    <div class="text-5xl mb-3">📷</div>
                    <p class="text-gray-600 font-medium mb-1"><?php echo esc_html__('Arrastra imágenes aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="text-sm text-gray-500"><?php echo esc_html__('o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="text-xs text-gray-400 mt-2"><?php echo esc_html__('Máximo 5 imágenes, 5MB cada una', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <input type="file" id="mp-imagenes" name="imagenes[]" multiple accept="image/*" class="hidden">
                </div>

                <!-- Preview de imágenes -->
                <div id="mp-imagenes-preview" class="grid grid-cols-3 gap-3 mt-4"></div>

                <!-- Consejos -->
                <div class="bg-lime-50 rounded-xl p-4 mt-6">
                    <h4 class="font-semibold text-gray-800 mb-2">💡 <?php echo esc_html__('Consejos para vender rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <?php echo esc_html__('Usa fotos claras y bien iluminadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li>• <?php echo esc_html__('Describe defectos o detalles importantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li>• <?php echo esc_html__('Pon un precio competitivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li>• <?php echo esc_html__('Responde rápido a los mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100">
            <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('marketplace', '')); ?>"
               class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                <?php echo esc_html__('← Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <div class="flex gap-3">
                <button type="button" id="mp-guardar-borrador"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                    <?php echo esc_html__('Guardar borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit"
                        class="px-8 py-3 bg-lime-500 text-white rounded-xl font-semibold hover:bg-lime-600 transition-colors shadow-md">
                    <?php echo esc_html__('Publicar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dropzone = document.getElementById('mp-imagenes-dropzone');
    var fileInput = document.getElementById('mp-imagenes');
    var preview = document.getElementById('mp-imagenes-preview');

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('border-lime-500', 'bg-lime-50');
        });

        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('border-lime-500', 'bg-lime-50');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-lime-500', 'bg-lime-50');
            fileInput.files = e.dataTransfer.files;
            mostrarPreviews(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', function() {
            mostrarPreviews(this.files);
        });
    }

    function mostrarPreviews(files) {
        preview.innerHTML = '';
        Array.from(files).slice(0, 5).forEach(function(file, index) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var div = document.createElement('div');
                div.className = 'relative aspect-square rounded-lg overflow-hidden bg-gray-100';
                div.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">' +
                    '<button type="button" class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs hover:bg-red-600" onclick="this.parentElement.remove()">×</button>';
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
