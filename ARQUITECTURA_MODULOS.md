# 🏗️ Arquitectura de Módulos - Flavor Chat IA

## 📋 Índice

1. [Concepto General](#concepto-general)
2. [Perfiles de Aplicación](#perfiles-de-aplicación)
3. [Estructura de un Módulo](#estructura-de-un-módulo)
4. [Crear un Nuevo Módulo](#crear-un-nuevo-módulo)
5. [Custom Post Types y Fields](#custom-post-types-y-fields)
6. [Integración con el Chat IA](#integración-con-el-chat-ia)
7. [Ejemplos Completos](#ejemplos-completos)

---

## 🎯 Concepto General

El plugin **Flavor Chat IA** utiliza una arquitectura modular que permite:

- ✅ **Activar/Desactivar funcionalidades** según las necesidades
- ✅ **Perfiles predefinidos** para diferentes tipos de aplicaciones
- ✅ **Cada módulo es independiente** (addon separado)
- ✅ **Auto-contenido**: incluye CPT, taxonomías, meta boxes, shortcodes, etc.
- ✅ **Integración automática** con el Chat IA (Claude API)

---

## 🎨 Perfiles de Aplicación

Los perfiles agrupan módulos según el tipo de proyecto:

### Perfiles Disponibles

| Perfil | Descripción | Módulos Incluidos |
|--------|-------------|-------------------|
| **Tienda Online** | E-commerce con WooCommerce | WooCommerce, Chat, Marketplace |
| **Grupo de Consumo** | Pedidos colectivos | Grupos Consumo, Chat, Eventos |
| **Restaurante** | Menús, reservas, pedidos | Restaurante, Reservas, Chat |
| **Banco de Tiempo** | Intercambio de servicios | Banco Tiempo, Chat, Membresías |
| **Comunidad** | Asociación, ONG | Membresías, Eventos, Foro |
| **Coworking** | Espacios compartidos | Reservas, Membresías, Fichajes |
| **Marketplace** | Compraventa comunitaria | Marketplace, Chat, Valoraciones |
| **Personalizado** | Selección manual | Todos disponibles |

### Gestión desde Admin

Accede a **WordPress Admin → Flavor Chat IA → Perfil App** para:

- 🔹 Seleccionar el perfil activo
- 🔹 Ver módulos requeridos y opcionales
- 🔹 Activar/desactivar módulos opcionales
- 🔹 Configurar cada módulo

---

## 📦 Estructura de un Módulo

Cada módulo es un **addon independiente** ubicado en:

```
includes/modules/{nombre-modulo}/
├── class-{nombre}-module.php      # Clase principal
├── install.php                    # (Opcional) Instalación de tablas
├── assets/                        # (Opcional) CSS/JS específicos
│   ├── css/
│   └── js/
└── templates/                     # (Opcional) Plantillas frontend
    └── single-{cpt}.php
```

### Clase Base

Todos los módulos extienden `Flavor_Chat_Module_Base`:

```php
class Flavor_Chat_MiModulo_Module extends Flavor_Chat_Module_Base {

    public function __construct() {
        $this->id = 'mi_modulo';
        $this->name = __('Mi Módulo', 'flavor-chat-ia');
        $this->description = __('Descripción del módulo', 'flavor-chat-ia');
        parent::__construct();
    }

    // Métodos requeridos...
}
```

### Métodos Obligatorios

| Método | Descripción |
|--------|-------------|
| `get_id()` | ID único del módulo |
| `get_name()` | Nombre visible |
| `get_description()` | Descripción breve |
| `can_activate()` | Verifica dependencias |
| `get_activation_error()` | Mensaje si no puede activarse |
| `init()` | Inicialización (hooks, CPT, etc.) |
| `get_actions()` | Acciones disponibles para el chat |
| `execute_action()` | Ejecuta una acción |
| `get_tool_definitions()` | Definiciones para Claude API |
| `get_knowledge_base()` | Conocimiento para el sistema |
| `get_faqs()` | FAQs del módulo |

---

## 🛠️ Crear un Nuevo Módulo

### Paso 1: Crear Directorio y Archivo Principal

```bash
mkdir -p includes/modules/mi-modulo
```

```php
<?php
// includes/modules/mi-modulo/class-mi-modulo-module.php

class Flavor_Chat_MiModulo_Module extends Flavor_Chat_Module_Base {

    public function __construct() {
        $this->id = 'mi_modulo';
        $this->name = __('Mi Módulo', 'flavor-chat-ia');
        $this->description = __('Descripción', 'flavor-chat-ia');
        parent::__construct();
    }

    public function can_activate() {
        // Verificar dependencias
        return true;
    }

    public function init() {
        // Registrar CPT, taxonomías, hooks...
        add_action('init', [$this, 'registrar_cpt']);
    }

    public function get_actions() {
        return [
            'mi_accion' => [
                'description' => 'Descripción de la acción',
                'params' => ['param1', 'param2'],
            ],
        ];
    }

    public function execute_action($action_name, $params) {
        if ($action_name === 'mi_accion') {
            return $this->action_mi_accion($params);
        }
        return ['success' => false, 'error' => 'Acción no encontrada'];
    }

    private function action_mi_accion($params) {
        // Lógica de la acción
        return [
            'success' => true,
            'datos' => ['resultado' => 'OK'],
        ];
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'mi_modulo_accion',
                'description' => 'Descripción para Claude',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'param1' => [
                            'type' => 'string',
                            'description' => 'Descripción del parámetro',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function get_knowledge_base() {
        return "Conocimiento que el chat debe tener sobre este módulo.";
    }

    public function get_faqs() {
        return [
            ['pregunta' => '¿Qué hace?', 'respuesta' => 'Explicación...'],
        ];
    }
}
```

### Paso 2: Registrar el Módulo

Edita `includes/modules/class-module-loader.php`:

```php
private function discover_modules() {
    $modules_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

    $builtin_modules = [
        'woocommerce' => [...],
        'banco_tiempo' => [...],
        'marketplace' => [...],
        'mi_modulo' => [  // ← Añadir aquí
            'file' => $modules_path . 'mi-modulo/class-mi-modulo-module.php',
            'class' => 'Flavor_Chat_MiModulo_Module',
        ],
    ];
    // ...
}
```

### Paso 3: Añadir al Perfil

Edita `includes/class-app-profiles.php`:

```php
'mi_perfil' => [
    'nombre' => __('Mi Perfil', 'flavor-chat-ia'),
    'descripcion' => __('Descripción del perfil', 'flavor-chat-ia'),
    'icono' => 'dashicons-admin-generic',
    'modulos_requeridos' => ['mi_modulo', 'chat'],
    'modulos_opcionales' => ['eventos'],
    'color' => '#3498db',
],
```

---

## 🗄️ Custom Post Types y Fields

### Registrar CPT en el Módulo

```php
public function init() {
    add_action('init', [$this, 'registrar_cpt']);
    add_action('init', [$this, 'registrar_taxonomias']);
    add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
    add_action('save_post_mi_cpt', [$this, 'guardar_meta_boxes']);
}

public function registrar_cpt() {
    register_post_type('mi_cpt', [
        'labels' => [
            'name' => __('Mis Items', 'flavor-chat-ia'),
            'singular_name' => __('Item', 'flavor-chat-ia'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-admin-generic',
        'show_in_rest' => true,
    ]);
}

public function registrar_taxonomias() {
    register_taxonomy('mi_categoria', 'mi_cpt', [
        'labels' => [
            'name' => __('Categorías', 'flavor-chat-ia'),
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
}

public function registrar_meta_boxes() {
    add_meta_box(
        'mi_meta_box',
        __('Datos Adicionales', 'flavor-chat-ia'),
        [$this, 'renderizar_meta_box'],
        'mi_cpt',
        'normal',
        'high'
    );
}

public function renderizar_meta_box($post) {
    wp_nonce_field('mi_meta_nonce', 'mi_meta_nonce_field');
    $valor = get_post_meta($post->ID, '_mi_campo', true);
    ?>
    <label for="mi_campo"><?php _e('Mi Campo', 'flavor-chat-ia'); ?></label>
    <input type="text" id="mi_campo" name="mi_campo"
           value="<?php echo esc_attr($valor); ?>" class="widefat" />
    <?php
}

public function guardar_meta_boxes($post_id) {
    if (!isset($_POST['mi_meta_nonce_field']) ||
        !wp_verify_nonce($_POST['mi_meta_nonce_field'], 'mi_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['mi_campo'])) {
        update_post_meta($post_id, '_mi_campo', sanitize_text_field($_POST['mi_campo']));
    }
}
```

### Crear Tablas Personalizadas

```php
// includes/modules/mi-modulo/install.php

function mi_modulo_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tabla = $wpdb->prefix . 'mi_modulo_datos';
    $sql = "CREATE TABLE IF NOT EXISTS $tabla (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        dato varchar(255) NOT NULL,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
```

Llamar desde el plugin principal al activarse:

```php
// En flavor-chat-ia.php -> activate()
require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/mi-modulo/install.php';
mi_modulo_crear_tablas();
```

---

## 🤖 Integración con el Chat IA

### Definir Herramientas (Tools) para Claude

```php
public function get_tool_definitions() {
    return [
        [
            'name' => 'mi_modulo_buscar',
            'description' => 'Busca items en el módulo',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'busqueda' => [
                        'type' => 'string',
                        'description' => 'Término de búsqueda',
                    ],
                    'limite' => [
                        'type' => 'integer',
                        'description' => 'Máximo de resultados',
                    ],
                ],
                'required' => ['busqueda'],
            ],
        ],
    ];
}
```

### Proporcionar Conocimiento al Sistema

```php
public function get_knowledge_base() {
    return <<<KNOWLEDGE
**Mi Módulo - Guía de Uso**

Este módulo permite a los usuarios realizar las siguientes acciones:
- Buscar items
- Crear nuevos items
- Actualizar items existentes

Comandos disponibles:
- "buscar [término]": busca items
- "crear item": inicia el proceso de creación

Importante: Los usuarios deben estar autenticados para crear items.
KNOWLEDGE;
}
```

### FAQs Automáticas

```php
public function get_faqs() {
    return [
        [
            'pregunta' => '¿Cómo creo un nuevo item?',
            'respuesta' => 'Puedes decir "crear item" y te guiaré en el proceso.',
        ],
        [
            'pregunta' => '¿Puedo buscar items de otros usuarios?',
            'respuesta' => 'Sí, todos los items públicos son visibles para todos.',
        ],
    ];
}
```

---

## 📚 Ejemplos Completos

### Ejemplo 1: Módulo Simple (Sin CPT)

**Caso de uso**: Sistema de notificaciones

```php
class Flavor_Chat_Notificaciones_Module extends Flavor_Chat_Module_Base {

    public function __construct() {
        $this->id = 'notificaciones';
        $this->name = __('Notificaciones', 'flavor-chat-ia');
        $this->description = __('Sistema de notificaciones en tiempo real', 'flavor-chat-ia');
        parent::__construct();
    }

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'cargar_scripts']);
    }

    public function get_actions() {
        return [
            'enviar_notificacion' => [
                'description' => 'Enviar notificación a usuario',
                'params' => ['usuario_id', 'mensaje', 'tipo'],
            ],
            'ver_notificaciones' => [
                'description' => 'Ver notificaciones del usuario',
                'params' => ['usuario_id', 'no_leidas'],
            ],
        ];
    }

    private function action_enviar_notificacion($params) {
        // Lógica de envío...
        return ['success' => true];
    }
}
```

### Ejemplo 2: Módulo Completo (Con CPT)

**Ver**: `includes/modules/marketplace/class-marketplace-module.php` (ya creado)

---

## 🚀 Próximos Pasos

### Módulos Sugeridos para Implementar

1. **Grupos de Consumo**
   - CPT: Pedidos colectivos, Productores
   - Gestión de repartos, pagos, ciclos

2. **Restaurante**
   - CPT: Menús, Reservas, Mesas
   - Sistema de turnos, comandas

3. **Eventos & Actividades**
   - CPT: Eventos, Inscripciones
   - Calendario, recordatorios

4. **Fichajes**
   - Registro de entradas/salidas
   - Turnos, vacaciones, ausencias

5. **Membresías**
   - Gestión de socios
   - Cuotas, renovaciones, beneficios

6. **Foro**
   - CPT: Temas, Respuestas
   - Moderación, notificaciones

7. **Voluntariado**
   - CPT: Proyectos, Voluntarios
   - Gestión de horas, certificados

8. **Crowdfunding**
   - CPT: Campañas, Aportaciones
   - Metas, recompensas

9. **Reservas**
   - CPT: Espacios, Reservas
   - Calendario, disponibilidad

10. **Valoraciones**
    - Sistema de reviews
    - Puntuaciones, comentarios

---

## 📞 Soporte

Para más información o dudas sobre cómo crear módulos, consulta:

- Código existente en `includes/modules/`
- Documentación de WordPress: [Custom Post Types](https://developer.wordpress.org/plugins/post-types/)
- Claude API: [Tool Use](https://docs.anthropic.com/claude/docs/tool-use)

---

**¡Feliz desarrollo modular!** 🎉
