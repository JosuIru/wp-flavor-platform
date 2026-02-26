# Sistema de Integraciones Dinámicas entre Módulos

## Visión General

El sistema de integraciones permite que los módulos **polivalentes** (proveedores de contenido) se vinculen automáticamente con los módulos **base** (consumidores de contenido) cuando ambos están activos.

```
┌─────────────────────────────────────────────────────────────────────┐
│                    MÓDULOS BASE (Consumidores)                       │
│  Productos │ Productores │ Eventos │ Talleres │ Cursos │ Espacios   │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
         ┌──────────────┬──────────────┬──────────────┐
         │   Recetas    │  Multimedia  │   Podcast    │
         │   Videos     │   Facturas   │  Biblioteca  │
         │  Red Social  │    Radio     │   Huella     │
         └──────────────┴──────────────┴──────────────┘
                    MÓDULOS POLIVALENTES (Proveedores)
```

## Clasificación de Módulos

### MÓDULOS POLIVALENTES (Proveedores) - 9 módulos
Ofrecen contenido que puede vincularse a cualquier entidad:

| ID | Módulo | CPT/Tabla | Qué Ofrece |
|----|--------|-----------|------------|
| `recetas` | Recetas | `flavor_receta` | Recetas con ingredientes y pasos |
| `multimedia` | Multimedia | `flavor_multimedia` | Fotos, videos, galerías |
| `podcast` | Podcast | `flavor_podcast_*` | Episodios de audio |
| `radio` | Radio | `flavor_radio_*` | Programas de radio |
| `biblioteca` | Biblioteca | `flavor_biblioteca_*` | Libros y recursos |
| `email_marketing` | Email Marketing | `flavor_email_*` | Campañas de email |
| `sello_conciencia` | Sello Conciencia | - | Puntuación sostenibilidad |
| `huella_ecologica` | Huella Ecológica | - | Métricas ambientales |
| `facturas` | Facturas | `flavor_facturas` | Documentos de facturación |

### MÓDULOS BASE (Consumidores) - 15 módulos
Definen entidades que pueden recibir contenido de los polivalentes:

| ID | Módulo | Entidades | Acepta |
|----|--------|-----------|--------|
| `grupos_consumo` | Grupos de Consumo | Productos, Productores, Consumidores | recetas, multimedia, facturas |
| `eventos` | Eventos | Eventos | multimedia, podcast, recetas |
| `talleres` | Talleres | Talleres | multimedia, recetas, biblioteca |
| `cursos` | Cursos | Cursos, Lecciones | multimedia, biblioteca |
| `espacios_comunes` | Espacios Comunes | Espacios | multimedia, eventos |
| `huertos_urbanos` | Huertos Urbanos | Huertos, Parcelas | recetas, multimedia |
| `socios` | Socios | Socios | facturas, email_marketing |
| `marketplace` | Marketplace | Anuncios | multimedia |
| `reservas` | Reservas | Reservas | - |
| `comunidades` | Comunidades | Comunidades | multimedia, eventos |
| `incidencias` | Incidencias | Incidencias | multimedia |
| `bares` | Bares | Establecimientos | recetas, multimedia |
| `carpooling` | Carpooling | Viajes | - |
| `bicicletas_compartidas` | Bicicletas | Estaciones | - |
| `parkings` | Parkings | Parkings | - |

### MÓDULOS ESPECÍFICOS - 24 módulos
No participan en el sistema de integraciones relacionales.

---

## Arquitectura Técnica

### Archivos del Sistema

```
includes/modules/
├── class-integration-registry.php    # Registro central
├── trait-module-integrations.php     # Traits para providers/consumers
└── INTEGRACIONES_README.md           # Esta documentación
```

### Traits Disponibles

#### `Flavor_Module_Integration_Provider`
Para módulos que OFRECEN contenido:

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {
    use Flavor_Module_Integration_Provider;

    protected function get_integration_content_type() {
        return [
            'id'         => 'mi_contenido',
            'label'      => __('Mi Contenido', 'flavor-chat-ia'),
            'icon'       => 'dashicons-admin-post',
            'post_type'  => 'mi_cpt',
            'capability' => 'edit_posts',
        ];
    }

    public function init() {
        $this->register_as_integration_provider();
        // ... resto de init
    }
}
```

#### `Flavor_Module_Integration_Consumer`
Para módulos que ACEPTAN contenido:

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {
    use Flavor_Module_Integration_Consumer;

    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast'];
    }

    protected function get_integration_targets() {
        return [
            [
                'type'      => 'post',
                'post_type' => 'mi_entidad',
                'context'   => 'side', // metabox location
            ],
        ];
    }

    public function init() {
        $this->register_as_integration_consumer();
        // ... resto de init
    }
}
```

### Cómo Funciona

1. **Al cargar WordPress:**
   - Cada módulo activo se registra como provider y/o consumer
   - El `Integration_Registry` recopila todos los registros

2. **Al editar una entidad (ej: Producto):**
   - El sistema detecta qué providers están activos
   - Muestra metaboxes para vincular cada tipo de contenido
   - Guarda las relaciones en post_meta/user_meta

3. **En el frontend:**
   - `flavor_integrations()->render_related_content($id)` muestra contenido vinculado

### Meta Keys

Las relaciones se guardan con el patrón:
- `_flavor_rel_{provider_id}` - Array de IDs relacionados
- `_flavor_rel_{provider_id}_reverse` - Relación inversa (en el contenido)

Ejemplo:
```php
// En un producto (ID: 123)
_flavor_rel_recetas = [45, 67, 89]  // IDs de recetas vinculadas

// En una receta (ID: 45)
_flavor_rel_recetas_reverse = [123, 456]  // Productos que la usan
```

---

## API REST

### Endpoints Disponibles

```
GET  /wp-json/flavor/v1/integrations
     → Lista todos los providers y consumers activos

GET  /wp-json/flavor/v1/integrations/{post|user}/{id}
     → Obtiene todas las relaciones de un objeto

POST /wp-json/flavor/v1/integrations/{post|user}/{id}/{provider_id}
     Body: { "content_id": 123 }
     → Añade una relación

DELETE /wp-json/flavor/v1/integrations/{post|user}/{id}/{provider_id}
       Body: { "content_id": 123 }
       → Elimina una relación
```

---

## Helpers Globales

```php
// Obtener el registro de integraciones
$registry = flavor_integrations();

// Verificar si una integración está activa
$registry->is_integration_active('recetas');
$registry->is_integration_active('recetas', 'grupos_consumo');

// Obtener contenido relacionado
$related = $registry->get_related_content($product_id, 'post');
// Retorna: ['recetas' => [...], 'multimedia' => [...]]

// Obtener objetos que referencian un contenido
$products = $registry->get_reverse_relations($receta_id, 'recetas');

// Renderizar contenido relacionado en frontend
$registry->render_related_content($product_id, 'post', [
    'title'   => 'Contenido Relacionado',
    'layout'  => 'grid',
    'columns' => 3,
]);
```

---

## Matriz de Integraciones Recomendadas

| Consumer ↓ / Provider → | recetas | multimedia | podcast | facturas | biblioteca | sello |
|-------------------------|:-------:|:----------:|:-------:|:--------:|:----------:|:-----:|
| Productos (WC)          |    ✓    |     ✓      |    ✓    |          |            |   ✓   |
| Productores             |    ✓    |     ✓      |    ✓    |    ✓     |            |   ✓   |
| Consumidores            |    ✓    |     ✓      |         |    ✓     |            |       |
| Eventos                 |    ✓    |     ✓      |    ✓    |          |            |   ✓   |
| Talleres                |    ✓    |     ✓      |         |          |     ✓      |       |
| Cursos                  |         |     ✓      |    ✓    |          |     ✓      |       |
| Espacios                |         |     ✓      |         |          |            |   ✓   |
| Huertos                 |    ✓    |     ✓      |         |          |            |   ✓   |
| Socios                  |         |            |         |    ✓     |            |       |
| Comunidades             |         |     ✓      |    ✓    |          |            |       |

---

## Ejemplo Completo: Módulo Recetas como Provider

```php
class Flavor_Chat_Recetas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Provider;  // ← Trait de provider

    /**
     * Definir el tipo de contenido que ofrece
     */
    protected function get_integration_content_type() {
        return [
            'id'         => 'recetas',
            'label'      => __('Recetas', 'flavor-chat-ia'),
            'icon'       => 'dashicons-carrot',
            'post_type'  => 'flavor_receta',
            'capability' => 'edit_posts',
        ];
    }

    public function init() {
        // Registrar como provider de integraciones
        $this->register_as_integration_provider();

        // ... resto del init del módulo
    }
}
```

## Ejemplo Completo: Grupos de Consumo como Consumer

```php
class Flavor_Chat_Grupos_Consumo_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Consumer;  // ← Trait de consumer

    /**
     * Qué tipos de contenido acepta
     */
    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast', 'facturas'];
    }

    /**
     * Dónde mostrar las integraciones
     */
    protected function get_integration_targets() {
        return [
            [
                'type'      => 'post',
                'post_type' => 'gc_producto',
                'context'   => 'side',
            ],
            [
                'type'      => 'post',
                'post_type' => 'gc_productor',
                'context'   => 'normal',
            ],
        ];
    }

    public function init() {
        // Registrar como consumer de integraciones
        $this->register_as_integration_consumer();

        // ... resto del init del módulo
    }
}
```

---

## Próximos Pasos

1. ✅ Crear sistema base de integraciones
2. ⏳ Actualizar módulos polivalentes para usar `Integration_Provider`
3. ⏳ Actualizar módulos base para usar `Integration_Consumer`
4. ⏳ Añadir UI de configuración para activar/desactivar integraciones específicas
5. ⏳ Crear widgets de dashboard mostrando relaciones
6. ⏳ Implementar búsqueda de contenido relacionado

---

## Changelog

### v1.0.0 (2024-02)
- Sistema inicial de integraciones
- Traits `Integration_Provider` y `Integration_Consumer`
- Registro central `Integration_Registry`
- API REST para gestión de relaciones
- Documentación inicial
