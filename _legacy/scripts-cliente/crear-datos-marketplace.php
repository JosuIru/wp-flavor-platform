<?php
/**
 * Script temporal para crear datos de prueba en Marketplace
 *
 * Ejecutar desde navegador: http://localhost:10028/wp-content/plugins/flavor-chat-ia/crear-datos-marketplace.php
 *
 * ELIMINAR DESPUÉS DE USAR
 */

// Cargar WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Verificar que es admin
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado. Debes ser administrador.');
}

echo "<h1>Creando datos de prueba para Marketplace</h1>";

// Crear categorías si no existen
$categorias = [
    'electronica' => 'Electrónica',
    'ropa' => 'Ropa y Accesorios',
    'hogar' => 'Hogar y Jardín',
    'deportes' => 'Deportes',
    'libros' => 'Libros y Cultura',
    'otros' => 'Otros',
];

echo "<h2>Creando categorías...</h2>";
foreach ($categorias as $slug => $nombre) {
    if (!term_exists($slug, 'marketplace_categoria')) {
        $result = wp_insert_term($nombre, 'marketplace_categoria', ['slug' => $slug]);
        if (!is_wp_error($result)) {
            echo "<p>✓ Categoría creada: {$nombre}</p>";
        } else {
            echo "<p>✗ Error: " . $result->get_error_message() . "</p>";
        }
    } else {
        echo "<p>- Categoría ya existe: {$nombre}</p>";
    }
}

// Crear tipos si no existen
$tipos = [
    'regalo' => 'Regalo',
    'venta' => 'Venta',
    'cambio' => 'Cambio',
    'alquiler' => 'Alquiler',
];

echo "<h2>Creando tipos...</h2>";
foreach ($tipos as $slug => $nombre) {
    if (!term_exists($slug, 'marketplace_tipo')) {
        $result = wp_insert_term($nombre, 'marketplace_tipo', ['slug' => $slug]);
        if (!is_wp_error($result)) {
            echo "<p>✓ Tipo creado: {$nombre}</p>";
        } else {
            echo "<p>✗ Error: " . $result->get_error_message() . "</p>";
        }
    } else {
        echo "<p>- Tipo ya existe: {$nombre}</p>";
    }
}

// Crear anuncios de ejemplo
$anuncios = [
    [
        'titulo' => 'Bicicleta de montaña - Poco uso',
        'descripcion' => 'Bicicleta de montaña marca Trek, modelo 2023. Usada solo 3 meses, en perfecto estado. Incluye luces y candado.',
        'tipo' => 'venta',
        'categoria' => 'deportes',
        'precio' => 350,
    ],
    [
        'titulo' => 'Sofá 3 plazas gris - Regalo',
        'descripcion' => 'Regalo sofá de 3 plazas color gris. Está en buen estado, solo tiene algo de desgaste en los cojines. Hay que venir a recogerlo.',
        'tipo' => 'regalo',
        'categoria' => 'hogar',
        'precio' => 0,
    ],
    [
        'titulo' => 'iPhone 13 - Cambio por Android',
        'descripcion' => 'Tengo un iPhone 13 de 128GB que quiero cambiar por un Android de gama alta (Samsung S23, Pixel 7 o similar). Batería al 92%.',
        'tipo' => 'cambio',
        'categoria' => 'electronica',
        'precio' => 0,
    ],
    [
        'titulo' => 'Cámara réflex Canon - Alquiler',
        'descripcion' => 'Alquilo cámara Canon EOS 80D con objetivo 18-135mm. Ideal para eventos, viajes o proyectos. Incluye bolsa y tarjeta SD.',
        'tipo' => 'alquiler',
        'categoria' => 'electronica',
        'precio' => 25,
    ],
    [
        'titulo' => 'Colección libros Harry Potter',
        'descripcion' => 'Colección completa de Harry Potter, edición de bolsillo en español. Los 7 libros en perfecto estado.',
        'tipo' => 'venta',
        'categoria' => 'libros',
        'precio' => 45,
    ],
    [
        'titulo' => 'Mesa de comedor extensible',
        'descripcion' => 'Mesa de comedor de madera maciza, extensible de 140 a 200 cm. Ideal para familias. Incluye 4 sillas a juego.',
        'tipo' => 'venta',
        'categoria' => 'hogar',
        'precio' => 280,
    ],
];

echo "<h2>Creando anuncios de ejemplo...</h2>";

$user_id = get_current_user_id();

foreach ($anuncios as $anuncio) {
    // Verificar si ya existe
    $existing = get_posts([
        'post_type' => 'marketplace_item',
        'title' => $anuncio['titulo'],
        'posts_per_page' => 1,
    ]);

    if (!empty($existing)) {
        echo "<p>- Ya existe: {$anuncio['titulo']}</p>";
        continue;
    }

    // Crear el post
    $post_id = wp_insert_post([
        'post_type' => 'marketplace_item',
        'post_status' => 'publish',
        'post_title' => $anuncio['titulo'],
        'post_content' => $anuncio['descripcion'],
        'post_author' => $user_id,
    ]);

    if (is_wp_error($post_id)) {
        echo "<p>✗ Error creando: {$anuncio['titulo']}</p>";
        continue;
    }

    // Asignar tipo
    wp_set_object_terms($post_id, $anuncio['tipo'], 'marketplace_tipo');

    // Asignar categoría
    wp_set_object_terms($post_id, $anuncio['categoria'], 'marketplace_categoria');

    // Guardar meta
    update_post_meta($post_id, '_marketplace_precio', $anuncio['precio']);
    update_post_meta($post_id, '_marketplace_estado', 'disponible');
    update_post_meta($post_id, '_marketplace_condicion', 'usado');

    echo "<p>✓ Anuncio creado: {$anuncio['titulo']} (ID: {$post_id})</p>";
}

echo "<h2>¡Proceso completado!</h2>";
echo "<p><strong>Ahora ve a:</strong> <a href='" . home_url('/mi-portal/marketplace/') . "'>Mi Portal > Marketplace</a></p>";
echo "<p><em>Recuerda eliminar este archivo después de usarlo.</em></p>";
