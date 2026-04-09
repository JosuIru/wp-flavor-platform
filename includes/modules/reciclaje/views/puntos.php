<?php
/**
 * Vista Puntos de Reciclaje - Módulo Reciclaje
 * Gestión de puntos de recogida
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';
$tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

// Procesar acciones
$mensaje_exito = '';
$mensaje_error = '';
$accion_actual = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$punto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('flavor_reciclaje_punto_action')) {
    if (isset($_POST['guardar_punto'])) {
        $datos_punto = [
            'nombre' => sanitize_text_field($_POST['nombre']),
            'tipo' => sanitize_text_field($_POST['tipo']),
            'direccion' => sanitize_text_field($_POST['direccion']),
            'latitud' => floatval($_POST['latitud']),
            'longitud' => floatval($_POST['longitud']),
            'materiales_aceptados' => json_encode($_POST['materiales_aceptados'] ?? []),
            'horario' => sanitize_textarea_field($_POST['horario']),
            'contacto' => sanitize_text_field($_POST['contacto']),
            'instrucciones' => sanitize_textarea_field($_POST['instrucciones']),
            'foto_url' => esc_url_raw($_POST['foto_url']),
            'estado' => sanitize_text_field($_POST['estado']),
        ];

        if ($punto_id > 0) {
            $resultado = $wpdb->update($tabla_puntos_reciclaje, $datos_punto, ['id' => $punto_id]);
            $mensaje_exito = __('Punto de reciclaje actualizado correctamente.', 'flavor-platform');
        } else {
            $resultado = $wpdb->insert($tabla_puntos_reciclaje, $datos_punto);
            $mensaje_exito = __('Punto de reciclaje creado correctamente.', 'flavor-platform');
            $punto_id = $wpdb->insert_id;
        }

        if ($resultado === false) {
            $mensaje_error = __('Error al guardar el punto de reciclaje.', 'flavor-platform');
        }
    } elseif (isset($_POST['eliminar_punto'])) {
        $wpdb->delete($tabla_puntos_reciclaje, ['id' => $punto_id]);
        $mensaje_exito = __('Punto de reciclaje eliminado correctamente.', 'flavor-platform');
        $accion_actual = 'list';
        $punto_id = 0;
    }
}

// Obtener punto actual si estamos editando
$punto_actual = null;
if ($punto_id > 0) {
    $punto_actual = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_puntos_reciclaje WHERE id = %d", $punto_id));
}

// Obtener todos los puntos para el listado y mapa
$puntos_reciclaje = $wpdb->get_results("
    SELECT p.*,
           COUNT(c.id) as total_contenedores,
           SUM(CASE WHEN c.necesita_vaciado = 1 THEN 1 ELSE 0 END) as contenedores_llenos
    FROM $tabla_puntos_reciclaje p
    LEFT JOIN $tabla_contenedores c ON p.id = c.punto_reciclaje_id
    GROUP BY p.id
    ORDER BY p.nombre ASC
");
?>

<div class="wrap flavor-reciclaje-puntos">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-location"></span>
        <?php echo esc_html__('Puntos de Reciclaje', 'flavor-platform'); ?>
    </h1>

    <?php if ($accion_actual === 'list') : ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-puntos&action=new'); ?>" class="page-title-action">
            <?php echo esc_html__('Añadir Nuevo', 'flavor-platform'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($mensaje_exito) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_exito); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($mensaje_error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($accion_actual === 'list') : ?>
        <!-- Vista de lista con mapa -->
        <div class="flavor-puntos-container">
            <div class="flavor-puntos-mapa">
                <div id="mapa-puntos-reciclaje" style="height: 600px; border-radius: 8px;"></div>
            </div>

            <div class="flavor-puntos-lista">
                <div class="flavor-filtros">
                    <input type="text" id="buscar-punto" class="regular-text" placeholder="<?php echo esc_attr__('Buscar punto...', 'flavor-platform'); ?>">
                    <select id="filtro-tipo" class="regular-text">
                        <option value=""><?php echo esc_html__('Todos los tipos', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('punto_limpio', 'flavor-platform'); ?>"><?php echo esc_html__('Punto Limpio', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('contenedor_comunitario', 'flavor-platform'); ?>"><?php echo esc_html__('Contenedor Comunitario', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('centro_acopio', 'flavor-platform'); ?>"><?php echo esc_html__('Centro de Acopio', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('movil', 'flavor-platform'); ?>"><?php echo esc_html__('Móvil', 'flavor-platform'); ?></option>
                    </select>
                    <select id="filtro-estado" class="regular-text">
                        <option value=""><?php echo esc_html__('Todos los estados', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('activo', 'flavor-platform'); ?>"><?php echo esc_html__('Activo', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('lleno', 'flavor-platform'); ?>"><?php echo esc_html__('Lleno', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('mantenimiento', 'flavor-platform'); ?>"><?php echo esc_html__('Mantenimiento', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('inactivo', 'flavor-platform'); ?>"><?php echo esc_html__('Inactivo', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Nombre', 'flavor-platform'); ?></th>
                            <th><?php echo esc_html__('Tipo', 'flavor-platform'); ?></th>
                            <th><?php echo esc_html__('Dirección', 'flavor-platform'); ?></th>
                            <th><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                            <th><?php echo esc_html__('Contenedores', 'flavor-platform'); ?></th>
                            <th><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-puntos">
                        <?php foreach ($puntos_reciclaje as $punto) : ?>
                            <tr data-tipo="<?php echo esc_attr($punto->tipo); ?>" data-estado="<?php echo esc_attr($punto->estado); ?>">
                                <td>
                                    <strong><?php echo esc_html($punto->nombre); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $tipos_labels = [
                                        'punto_limpio' => __('Punto Limpio', 'flavor-platform'),
                                        'contenedor_comunitario' => __('Contenedor Comunitario', 'flavor-platform'),
                                        'centro_acopio' => __('Centro de Acopio', 'flavor-platform'),
                                        'movil' => __('Móvil', 'flavor-platform'),
                                    ];
                                    echo esc_html($tipos_labels[$punto->tipo] ?? $punto->tipo);
                                    ?>
                                </td>
                                <td><?php echo esc_html($punto->direccion); ?></td>
                                <td>
                                    <?php
                                    $estados_clases = [
                                        'activo' => 'flavor-badge-success',
                                        'lleno' => 'flavor-badge-warning',
                                        'mantenimiento' => 'flavor-badge-danger',
                                        'inactivo' => 'flavor-badge-secondary',
                                    ];
                                    $estados_labels = [
                                        'activo' => __('Activo', 'flavor-platform'),
                                        'lleno' => __('Lleno', 'flavor-platform'),
                                        'mantenimiento' => __('Mantenimiento', 'flavor-platform'),
                                        'inactivo' => __('Inactivo', 'flavor-platform'),
                                    ];
                                    ?>
                                    <span class="flavor-badge <?php echo $estados_clases[$punto->estado] ?? 'flavor-badge-secondary'; ?>">
                                        <?php echo esc_html($estados_labels[$punto->estado] ?? $punto->estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo number_format($punto->total_contenedores); ?>
                                    <?php if ($punto->contenedores_llenos > 0) : ?>
                                        <span class="flavor-badge flavor-badge-warning">
                                            <?php echo sprintf(__('%d llenos', 'flavor-platform'), $punto->contenedores_llenos); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-puntos&action=edit&id=' . $punto->id); ?>" class="button button-small">
                                        <?php echo esc_html__('Editar', 'flavor-platform'); ?>
                                    </a>
                                    <button class="button button-small ver-en-mapa" data-lat="<?php echo esc_attr($punto->latitud); ?>" data-lng="<?php echo esc_attr($punto->longitud); ?>">
                                        <?php echo esc_html__('Ver en Mapa', 'flavor-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else : ?>
        <!-- Formulario de edición/creación -->
        <div class="flavor-form-container">
            <form method="post" action="" class="flavor-punto-form">
                <?php wp_nonce_field('flavor_reciclaje_punto_action'); ?>

                <div class="flavor-form-section">
                    <h2><?php echo $punto_id > 0 ? __('Editar Punto de Reciclaje', 'flavor-platform') : __('Nuevo Punto de Reciclaje', 'flavor-platform'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="nombre"><?php echo esc_html__('Nombre', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="nombre" id="nombre" class="regular-text" value="<?php echo esc_attr($punto_actual->nombre ?? ''); ?>" required>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="tipo"><?php echo esc_html__('Tipo', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <select name="tipo" id="tipo" required>
                                    <option value="<?php echo esc_attr__('punto_limpio', 'flavor-platform'); ?>" <?php selected($punto_actual->tipo ?? '', 'punto_limpio'); ?>><?php echo esc_html__('Punto Limpio', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('contenedor_comunitario', 'flavor-platform'); ?>" <?php selected($punto_actual->tipo ?? '', 'contenedor_comunitario'); ?>><?php echo esc_html__('Contenedor Comunitario', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('centro_acopio', 'flavor-platform'); ?>" <?php selected($punto_actual->tipo ?? '', 'centro_acopio'); ?>><?php echo esc_html__('Centro de Acopio', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('movil', 'flavor-platform'); ?>" <?php selected($punto_actual->tipo ?? '', 'movil'); ?>><?php echo esc_html__('Móvil', 'flavor-platform'); ?></option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="direccion"><?php echo esc_html__('Dirección', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="direccion" id="direccion" class="large-text" value="<?php echo esc_attr($punto_actual->direccion ?? ''); ?>" required>
                                <p class="description"><?php echo esc_html__('Introduce la dirección y busca en el mapa para obtener las coordenadas.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php echo esc_html__('Ubicación en Mapa', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <div id="mapa-ubicacion" style="height: 400px; margin-bottom: 10px; border-radius: 8px;"></div>
                                <div class="flavor-coords-inputs">
                                    <label>
                                        <?php echo esc_html__('Latitud:', 'flavor-platform'); ?>
                                        <input type="number" name="latitud" id="latitud" step="0.0000001" value="<?php echo esc_attr($punto_actual->latitud ?? ''); ?>" required>
                                    </label>
                                    <label>
                                        <?php echo esc_html__('Longitud:', 'flavor-platform'); ?>
                                        <input type="number" name="longitud" id="longitud" step="0.0000001" value="<?php echo esc_attr($punto_actual->longitud ?? ''); ?>" required>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php echo esc_html__('Materiales Aceptados', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <?php
                                $materiales_disponibles = ['papel', 'plastico', 'vidrio', 'organico', 'electronico', 'ropa', 'aceite', 'pilas'];
                                $materiales_seleccionados = isset($punto_actual->materiales_aceptados) ? json_decode($punto_actual->materiales_aceptados, true) : [];
                                foreach ($materiales_disponibles as $material) :
                                ?>
                                    <label style="display: inline-block; margin-right: 15px;">
                                        <input type="checkbox" name="materiales_aceptados[]" value="<?php echo esc_attr($material); ?>" <?php checked(in_array($material, $materiales_seleccionados)); ?>>
                                        <?php echo esc_html(ucfirst($material)); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="horario"><?php echo esc_html__('Horario', 'flavor-platform'); ?></label>
                            </th>
                            <td>
                                <textarea name="horario" id="horario" rows="3" class="large-text"><?php echo esc_textarea($punto_actual->horario ?? ''); ?></textarea>
                                <p class="description"><?php echo esc_html__('Ejemplo: Lunes a Viernes: 9:00 - 18:00', 'flavor-platform'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="contacto"><?php echo esc_html__('Contacto', 'flavor-platform'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="contacto" id="contacto" class="regular-text" value="<?php echo esc_attr($punto_actual->contacto ?? ''); ?>">
                                <p class="description"><?php echo esc_html__('Teléfono o email de contacto.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="instrucciones"><?php echo esc_html__('Instrucciones', 'flavor-platform'); ?></label>
                            </th>
                            <td>
                                <textarea name="instrucciones" id="instrucciones" rows="4" class="large-text"><?php echo esc_textarea($punto_actual->instrucciones ?? ''); ?></textarea>
                                <p class="description"><?php echo esc_html__('Instrucciones especiales para los usuarios.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="foto_url"><?php echo esc_html__('Foto', 'flavor-platform'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="foto_url" id="foto_url" class="large-text" value="<?php echo esc_url($punto_actual->foto_url ?? ''); ?>">
                                <button type="button" class="button" id="subir-foto"><?php echo esc_html__('Subir Imagen', 'flavor-platform'); ?></button>
                                <div id="preview-foto" style="margin-top: 10px;">
                                    <?php if (!empty($punto_actual->foto_url)) : ?>
                                        <img src="<?php echo esc_url($punto_actual->foto_url); ?>" style="max-width: 300px; height: auto; border-radius: 4px;">
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="estado"><?php echo esc_html__('Estado', 'flavor-platform'); ?> *</label>
                            </th>
                            <td>
                                <select name="estado" id="estado" required>
                                    <option value="<?php echo esc_attr__('activo', 'flavor-platform'); ?>" <?php selected($punto_actual->estado ?? 'activo', 'activo'); ?>><?php echo esc_html__('Activo', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('lleno', 'flavor-platform'); ?>" <?php selected($punto_actual->estado ?? '', 'lleno'); ?>><?php echo esc_html__('Lleno', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('mantenimiento', 'flavor-platform'); ?>" <?php selected($punto_actual->estado ?? '', 'mantenimiento'); ?>><?php echo esc_html__('Mantenimiento', 'flavor-platform'); ?></option>
                                    <option value="<?php echo esc_attr__('inactivo', 'flavor-platform'); ?>" <?php selected($punto_actual->estado ?? '', 'inactivo'); ?>><?php echo esc_html__('Inactivo', 'flavor-platform'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="guardar_punto" class="button button-primary">
                        <?php echo esc_html__('Guardar Punto', 'flavor-platform'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-puntos'); ?>" class="button">
                        <?php echo esc_html__('Cancelar', 'flavor-platform'); ?>
                    </a>
                    <?php if ($punto_id > 0) : ?>
                        <button type="submit" name="eliminar_punto" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de eliminar este punto?', 'flavor-platform')); ?>');">
                            <?php echo esc_html__('Eliminar Punto', 'flavor-platform'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    const puntosData = <?php echo json_encode($puntos_reciclaje); ?>;
    let mapaListado, mapaFormulario, marcadorFormulario;

    // Inicializar mapa de listado
    if ($('#mapa-puntos-reciclaje').length) {
        mapaListado = L.map('mapa-puntos-reciclaje').setView([43.3183, -1.9812], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapaListado);

        puntosData.forEach(punto => {
            const marker = L.marker([punto.latitud, punto.longitud]).addTo(mapaListado);
            marker.bindPopup(`<strong>${punto.nombre}</strong><br>${punto.direccion}`);
        });
    }

    // Inicializar mapa del formulario
    if ($('#mapa-ubicacion').length) {
        const latInicial = parseFloat($('#latitud').val()) || 43.3183;
        const lngInicial = parseFloat($('#longitud').val()) || -1.9812;

        mapaFormulario = L.map('mapa-ubicacion').setView([latInicial, lngInicial], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapaFormulario);

        marcadorFormulario = L.marker([latInicial, lngInicial], {draggable: true}).addTo(mapaFormulario);

        marcadorFormulario.on('dragend', function() {
            const pos = marcadorFormulario.getLatLng();
            $('#latitud').val(pos.lat.toFixed(7));
            $('#longitud').val(pos.lng.toFixed(7));
        });

        mapaFormulario.on('click', function(e) {
            marcadorFormulario.setLatLng(e.latlng);
            $('#latitud').val(e.latlng.lat.toFixed(7));
            $('#longitud').val(e.latlng.lng.toFixed(7));
        });
    }

    // Filtros
    $('#buscar-punto, #filtro-tipo, #filtro-estado').on('change keyup', function() {
        const busqueda = $('#buscar-punto').val().toLowerCase();
        const tipo = $('#filtro-tipo').val();
        const estado = $('#filtro-estado').val();

        $('#tabla-puntos tr').each(function() {
            const $row = $(this);
            const texto = $row.text().toLowerCase();
            const rowTipo = $row.data('tipo');
            const rowEstado = $row.data('estado');

            const coincideBusqueda = texto.includes(busqueda);
            const coincideTipo = !tipo || rowTipo === tipo;
            const coincideEstado = !estado || rowEstado === estado;

            $row.toggle(coincideBusqueda && coincideTipo && coincideEstado);
        });
    });

    // Ver en mapa
    $('.ver-en-mapa').on('click', function() {
        const lat = parseFloat($(this).data('lat'));
        const lng = parseFloat($(this).data('lng'));
        if (mapaListado) {
            mapaListado.setView([lat, lng], 18);
            $('html, body').animate({ scrollTop: $('#mapa-puntos-reciclaje').offset().top - 100 }, 500);
        }
    });

    // Subir imagen
    $('#subir-foto').on('click', function(e) {
        e.preventDefault();
        const frame = wp.media({
            title: '<?php echo esc_js(__('Seleccionar imagen', 'flavor-platform')); ?>',
            button: { text: '<?php echo esc_js(__('Usar imagen', 'flavor-platform')); ?>' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#foto_url').val(attachment.url);
            $('#preview-foto').html(`<img src="${attachment.url}" style="max-width: 300px; height: auto; border-radius: 4px;">`);
        });

        frame.open();
    });
});
</script>

<style>
.flavor-puntos-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.flavor-puntos-mapa,
.flavor-puntos-lista {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-filtros {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.flavor-filtros input,
.flavor-filtros select {
    flex: 1;
}

.flavor-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.flavor-badge-success { background: #d4edda; color: #155724; }
.flavor-badge-warning { background: #fff3cd; color: #856404; }
.flavor-badge-danger { background: #f8d7da; color: #721c24; }
.flavor-badge-secondary { background: #e2e3e5; color: #383d41; }

.flavor-form-container {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-coords-inputs {
    display: flex;
    gap: 20px;
}

.flavor-coords-inputs label {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-coords-inputs input {
    width: 150px;
}

@media (max-width: 1200px) {
    .flavor-puntos-container {
        grid-template-columns: 1fr;
    }
}
</style>
