<?php
/**
 * Controlador frontend: Tienda Local
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Tienda Local
 */
class Flavor_Frontend_Tienda_Local_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'tienda-local';
    protected $nombre = 'Tienda Local';
    protected $icono = '🛒';
    protected $color_primario = 'amber';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tienda_productos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['categoria', 'productor', 'ecologico', 'oferta', 'precio_min', 'precio_max']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["activo = 1 AND stock > 0"];
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }
        if (!empty($filtros['ecologico'])) {
            $where[] = "ecologico = 1";
        }
        if (!empty($filtros['oferta'])) {
            $where[] = "precio_oferta IS NOT NULL AND precio_oferta > 0";
        }
        if (!empty($filtros['precio_min'])) {
            $where[] = $wpdb->prepare('precio >= %f', floatval($filtros['precio_min']));
        }
        if (!empty($filtros['precio_max'])) {
            $where[] = $wpdb->prepare('precio <= %f', floatval($filtros['precio_max']));
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $productos = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY nombre LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'productos' => $this->procesar_productos($productos),
            'total_productos' => intval($total),
            'categorias' => $this->get_categorias(),
            'productores' => $this->get_productores(),
            'precio_min' => 0,
            'precio_max' => $this->get_precio_max(),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tienda_productos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $producto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND activo = 1",
            intval($item_id)
        ));

        if (!$producto) {
            return null;
        }

        return [
            'titulo_pagina' => $producto->nombre,
            'producto' => $this->procesar_producto_detalle($producto),
            'productos_relacionados' => $this->get_relacionados($producto->id, $producto->categoria),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_search_data($query) {
        if (empty($query)) {
            return [
                'resultados' => [],
                'total_resultados' => 0,
                'sugerencias' => ['verduras', 'frutas', 'pan', 'queso', 'miel', 'aceite'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tienda_productos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $productos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE activo = 1 AND stock > 0 AND (nombre LIKE %s OR descripcion LIKE %s) LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_productos($productos),
            'total_resultados' => count($productos),
            'sugerencias' => [],
        ];
    }

    private function procesar_productos($productos) {
        return array_map([$this, 'procesar_producto'], $productos);
    }

    private function procesar_producto($producto) {
        $fotos = json_decode($producto->fotos ?? '[]', true) ?: [];
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => wp_trim_words($producto->descripcion ?? '', 20),
            'categoria' => $producto->categoria ?? 'general',
            'precio' => floatval($producto->precio ?? 0),
            'precio_oferta' => $producto->precio_oferta ? floatval($producto->precio_oferta) : null,
            'unidad' => $producto->unidad ?? 'ud',
            'stock' => intval($producto->stock ?? 0),
            'ecologico' => (bool)($producto->ecologico ?? false),
            'local' => (bool)($producto->local ?? true),
            'oferta' => !empty($producto->precio_oferta),
            'productor' => $producto->productor_nombre ?? '',
            'imagen' => !empty($fotos) ? $fotos[0] : null,
            'url' => home_url('/' . $this->slug . '/' . $producto->id . '/'),
        ];
    }

    private function procesar_producto_detalle($producto) {
        $base = $this->procesar_producto($producto);
        $fotos = json_decode($producto->fotos ?? '[]', true) ?: [];
        $base['descripcion'] = $producto->descripcion ?? '';
        $base['galeria'] = $fotos;
        $base['info_adicional'] = json_decode($producto->info_adicional ?? '{}', true) ?: [];
        $base['origen'] = $producto->origen ?? '';
        $base['conservacion'] = $producto->conservacion ?? '';
        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'productos' => [
                [
                    'id' => 1,
                    'nombre' => 'Tomates ecológicos',
                    'descripcion' => 'Tomates de huerta local, cultivados sin pesticidas.',
                    'categoria' => 'verduras',
                    'precio' => 3.50,
                    'precio_oferta' => null,
                    'unidad' => 'kg',
                    'stock' => 25,
                    'ecologico' => true,
                    'local' => true,
                    'oferta' => false,
                    'productor' => 'Huerta del Pueblo',
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
                [
                    'id' => 2,
                    'nombre' => 'Miel de flores',
                    'descripcion' => 'Miel artesanal de apicultor local.',
                    'categoria' => 'despensa',
                    'precio' => 8.00,
                    'precio_oferta' => 6.50,
                    'unidad' => 'bote 500g',
                    'stock' => 15,
                    'ecologico' => true,
                    'local' => true,
                    'oferta' => true,
                    'productor' => 'Apiario San José',
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/2/'),
                ],
            ],
            'total_productos' => 2,
            'categorias' => $this->get_categorias(),
            'productores' => [],
            'precio_min' => 0,
            'precio_max' => 100,
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(2, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Producto',
            'producto' => [
                'id' => $item_id,
                'nombre' => 'Producto de ejemplo',
                'descripcion' => 'Descripción del producto.',
                'categoria' => 'general',
                'precio' => 0,
                'precio_oferta' => null,
                'unidad' => 'ud',
                'stock' => 0,
                'ecologico' => false,
                'local' => true,
                'oferta' => false,
                'productor' => '',
                'imagen' => null,
                'galeria' => [],
                'info_adicional' => [],
                'origen' => '',
                'conservacion' => '',
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'productos_relacionados' => [],
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'verduras', 'nombre' => 'Verduras', 'count' => 25],
            ['slug' => 'frutas', 'nombre' => 'Frutas', 'count' => 18],
            ['slug' => 'lacteos', 'nombre' => 'Lácteos', 'count' => 12],
            ['slug' => 'panaderia', 'nombre' => 'Panadería', 'count' => 8],
            ['slug' => 'carnes', 'nombre' => 'Carnes', 'count' => 10],
            ['slug' => 'despensa', 'nombre' => 'Despensa', 'count' => 30],
            ['slug' => 'bebidas', 'nombre' => 'Bebidas', 'count' => 15],
        ];
    }

    private function get_productores() {
        return [];
    }

    private function get_precio_max() {
        return 100;
    }

    private function get_relacionados($producto_id, $categoria) {
        return [];
    }

    protected function ajax_agregar_carrito($data) {
        $producto_id = intval($data['producto_id'] ?? 0);
        $cantidad = max(1, intval($data['cantidad'] ?? 1));

        // Guardar en sesión o cookie
        $carrito = json_decode(stripslashes($_COOKIE['flavor_carrito'] ?? '[]'), true) ?: [];

        $encontrado = false;
        foreach ($carrito as &$item) {
            if ($item['id'] === $producto_id) {
                $item['cantidad'] += $cantidad;
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $carrito[] = ['id' => $producto_id, 'cantidad' => $cantidad];
        }

        setcookie('flavor_carrito', json_encode($carrito), time() + 86400 * 7, '/');

        return ['success' => true, 'carrito' => $carrito, 'mensaje' => __('Producto añadido al carrito', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    protected function ajax_get_carrito($data) {
        $carrito = json_decode(stripslashes($_COOKIE['flavor_carrito'] ?? '[]'), true) ?: [];
        return ['success' => true, 'carrito' => $carrito];
    }

    protected function ajax_actualizar_carrito($data) {
        $carrito = $data['carrito'] ?? [];
        setcookie('flavor_carrito', json_encode($carrito), time() + 86400 * 7, '/');
        return ['success' => true, 'carrito' => $carrito];
    }
}
