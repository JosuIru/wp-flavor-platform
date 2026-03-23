# Módulo: Recetas

> Gestiona recetas vinculables a productos con ingredientes, pasos y tiempos

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `recetas` |
| **Versión** | 1.0.0+ |
| **Categoría** | Contenido / Recursos |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Integration_Provider`

### Rol de Integración

Este módulo es un **PROVIDER** de contenido. Otros módulos pueden vincular recetas a sus entidades (productos, eventos, talleres, huertos, etc.).

---

## Descripción

Módulo de recetas completo con ingredientes, pasos de preparación, tiempos, dificultad y vinculación con productos de WooCommerce y otros módulos.

### Características Principales

- **Recetas Completas**: Ingredientes, pasos, tiempos
- **Dificultad**: Fácil, media, difícil
- **Información Nutricional**: Calorías por porción
- **Vinculación Productos**: WooCommerce y Grupos de Consumo
- **Taxonomías**: Categorías y tipos de dieta
- **Multimedia**: Fotos y videos
- **Integración**: Otros módulos pueden vincular recetas

---

## Custom Post Type

| CPT | Nombre | Descripción |
|-----|--------|-------------|
| `flavor_receta` | Receta | Recetas de cocina |

**Supports**: title, editor, thumbnail, excerpt
**Archive**: true (slug: `receta`)
**REST API**: true

---

## Taxonomías

### receta_categoria

Categorías de recetas.

| Categoría | Slug |
|-----------|------|
| Entrantes | entrantes |
| Platos Principales | principales |
| Postres | postres |
| Bebidas | bebidas |
| Ensaladas | ensaladas |
| Sopas y Cremas | sopas |
| Aperitivos | aperitivos |
| Conservas | conservas |

### receta_dieta

Tipos de dieta.

| Dieta | Slug |
|-------|------|
| Vegetariana | vegetariana |
| Vegana | vegana |
| Sin Gluten | sin-gluten |
| Sin Lactosa | sin-lactosa |
| Keto | keto |
| Mediterránea | mediterranea |

---

## Meta Fields

| Meta Key | Tipo | Descripción |
|----------|------|-------------|
| `_receta_tiempo_preparacion` | int | Minutos de preparación |
| `_receta_tiempo_coccion` | int | Minutos de cocción |
| `_receta_porciones` | int | Número de porciones |
| `_receta_dificultad` | string | facil, media, dificil |
| `_receta_calorias` | int | Calorías por porción |
| `_receta_ingredientes` | array | Lista de ingredientes |
| `_receta_pasos` | array | Pasos de preparación |

### Estructura de Ingredientes

```php
[
    [
        'cantidad' => '2',
        'unidad' => 'kg',
        'nombre' => 'Patatas'
    ],
    // ...
]
```

### Estructura de Pasos

```php
[
    [
        'titulo' => 'Preparar ingredientes',
        'descripcion' => 'Pelar y cortar las patatas...',
        'imagen' => 'url_imagen'
    ],
    // ...
]
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `mostrar_en_productos` | bool | true | Mostrar en WooCommerce |
| `permitir_valoraciones` | bool | true | Permitir valorar |
| `mostrar_tiempo_preparacion` | bool | true | Mostrar tiempos |
| `mostrar_dificultad` | bool | true | Mostrar dificultad |

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[flavor_recetas]` | Listado de recetas | `categoria`, `dieta`, `limite` |
| `[flavor_receta]` | Receta individual | `id` |

---

## Integración WooCommerce

Cuando WooCommerce está activo:

- **Tab en producto**: Pestaña para vincular recetas
- **Meta producto**: `_recetas_vinculadas` (array de IDs)
- **Frontend**: Sección de recetas después del contenido

### Hooks WooCommerce

| Hook | Descripción |
|------|-------------|
| `woocommerce_product_data_tabs` | Añadir pestaña |
| `woocommerce_product_data_panels` | Contenido pestaña |
| `woocommerce_process_product_meta` | Guardar datos |
| `woocommerce_after_single_product_summary` | Mostrar recetas |

---

## Integración Grupos de Consumo

Si el módulo `grupos_consumo` está activo:

- Meta box para vincular productos GC
- Los ingredientes pueden ser productos del grupo
- Shortcode para mostrar recetas con productos disponibles

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `flavor_buscar_recetas` | Buscar recetas | Admin |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `flavor-recetas-dashboard` | Panel principal |

---

## Meta Boxes en Editor

| Meta Box | Ubicación | Descripción |
|----------|-----------|-------------|
| Detalles de la Receta | normal | Tiempos, porciones, dificultad |
| Ingredientes | normal | Lista de ingredientes |
| Pasos de Preparación | normal | Pasos con descripción |
| Productos Vinculados | side | WooCommerce |
| Productos GC | normal | Grupos de Consumo |
| Videos | normal | Videos de la receta |

---

## Como Provider de Integración

Otros módulos pueden vincular recetas usando el trait `Flavor_Module_Integration_Consumer`:

```php
// En el módulo consumidor
protected function get_accepted_integrations() {
    return ['recetas'];
}
```

---

## Notas de Implementación

- CPT público con archivo
- Taxonomías jerárquicas (categorías) y no jerárquicas (dietas)
- Los ingredientes y pasos usan repeaters dinámicos
- Integración automática cuando WooCommerce está activo
- Compatible con el sistema de integraciones del plugin
- Assets frontend y admin independientes
