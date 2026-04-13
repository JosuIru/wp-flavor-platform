# 🎉 Nuevas Funcionalidades Añadidas

## 📦 **Nuevo Módulo: Grupos de Consumo**

### ¿Qué es?
Sistema completo para gestionar **pedidos colectivos de productores locales**. Ideal para cooperativas, grupos de consumo ecológico y compra comunitaria.

### Archivos creados:
- `includes/modules/grupos-consumo/class-grupos-consumo-module.php`
- `includes/modules/grupos-consumo/install.php`

### ✨ Características:

#### 🌾 **Gestión de Productores**
- Custom Post Type: `gc_productor`
- Información de contacto completa
- Certificaciones ecológicas
- Métodos de producción
- Ubicación

#### 🥕 **Catálogo de Productos**
- Custom Post Type: `gc_producto`
- Precio por unidad (kg, L, unidad, caja, ramo)
- Stock disponible
- Cantidad mínima de pedido
- Temporada y origen
- Categorías: Frutas, Verduras, Lácteos, Carne, Pescado, Pan, Conservas, Bebidas

#### 📅 **Ciclos de Pedido**
- Custom Post Type: `gc_ciclo`
- Estados personalizados:
  - `gc_abierto` - Activo para pedidos
  - `gc_cerrado` - Ya no acepta pedidos
  - `gc_entregado` - Completado
- Fechas de apertura, cierre y entrega
- Lugar de entrega y hora
- Cierre automático con cron job
- Notas para los miembros

#### 📊 **Sistema de Pedidos**
Tablas en BD:
- `wp_flavor_gc_pedidos` - Pedidos individuales
- `wp_flavor_gc_entregas` - Gestión de entregas y pagos
- `wp_flavor_gc_consolidado` - Pedidos consolidados por productor
- `wp_flavor_gc_notificaciones` - Sistema de notificaciones

#### 🎯 **Funcionalidades**
- ✅ Pedidos online durante ciclo abierto
- ✅ Modificación de pedidos (configurable tiempo límite)
- ✅ Consolidación automática de pedidos por productor
- ✅ Gestión de pagos y recogidas
- ✅ Notificaciones automáticas
- ✅ Resumen de pedidos por ciclo
- ✅ Exportación de datos para productores
- ✅ Estadísticas y productos más solicitados

#### 💬 **Integración con Chat IA**
Comandos disponibles:
- "¿Qué productos hay disponibles?"
- "Buscar productos de verduras"
- "¿Cuándo cierra el ciclo actual?"
- "¿Dónde es la entrega?"
- "Mostrar mi pedido"

#### 🎨 **Shortcodes**
```php
[gc_ciclo_actual]        // Muestra información del ciclo activo
[gc_productos categoria="frutas" limite="12"]  // Lista de productos
[gc_mi_pedido]           // Pedido del usuario actual
```

#### ⚙️ **Configuración**
Opciones del módulo:
- Días de anticipación para pedidos
- Hora de cierre automático
- Permitir modificar pedidos
- Horas límite para modificación
- Porcentaje de gastos de gestión
- Requerir aprobación de productores
- Notificaciones automáticas

---

## 🛠️ **Clase de Helpers/Utilidades**

### Archivo: `includes/class-helpers.php`

Funciones comunes para **todos los módulos**:

### 💰 Formateo
```php
Flavor_Chat_Helpers::formatear_precio(15.50);
// → "15,50 €"

Flavor_Chat_Helpers::formatear_fecha($fecha, 'medium');
// → "27/01/2026 14:30"

Flavor_Chat_Helpers::tiempo_transcurrido($fecha);
// → "hace 2 horas"

Flavor_Chat_Helpers::tiempo_restante($fecha);
// → "quedan 3 días"
```

### 📧 Notificaciones
```php
// Email con template HTML
Flavor_Chat_Helpers::enviar_email(
    $usuario_id,
    'Asunto del email',
    'Contenido del mensaje',
    ['headers' => [...]]
);

// Notificación en BD
Flavor_Chat_Helpers::crear_notificacion(
    $usuario_id,
    'nuevo_pedido',
    'Pedido confirmado',
    'Tu pedido #123 ha sido confirmado',
    ['tipo' => 'pedido', 'id' => 123]
);

// Obtener notificaciones
$notificaciones = Flavor_Chat_Helpers::obtener_notificaciones(
    $usuario_id,
    ['solo_no_leidas' => true, 'limite' => 10]
);
```

### 🔧 Utilidades
```php
// Sanitizar array recursivamente
$datos_limpios = Flavor_Chat_Helpers::sanitizar_array($_POST['datos']);

// Generar slug único
$slug = Flavor_Chat_Helpers::generar_slug_unico('Mi Titulo', 'post');

// Validaciones
Flavor_Chat_Helpers::validar_email($email);
Flavor_Chat_Helpers::validar_telefono($telefono);

// Avatar
$avatar_url = Flavor_Chat_Helpers::obtener_avatar_url($usuario_id, 96);

// Truncar texto
$resumen = Flavor_Chat_Helpers::truncar_texto($texto, 50);
```

### 📊 Exportación CSV
```php
// Convertir array a CSV
$csv_content = Flavor_Chat_Helpers::array_a_csv(
    $datos,
    ['Nombre', 'Email', 'Total']
);

// Descargar CSV
Flavor_Chat_Helpers::descargar_csv($csv_content, 'pedidos.csv');
```

### ⚙️ Gestión de Configuración
```php
// Obtener configuración de un módulo
$config = Flavor_Chat_Helpers::obtener_config_modulo('grupos_consumo');
$opcion = Flavor_Chat_Helpers::obtener_config_modulo('grupos_consumo', 'dias_anticipacion', 7);

// Actualizar configuración
Flavor_Chat_Helpers::actualizar_config_modulo('grupos_consumo', 'dias_anticipacion', 10);
```

### 📝 Logging
```php
// Registrar evento en el log (solo en debug mode)
Flavor_Chat_Helpers::registrar_evento(
    'pedido_creado',
    'Usuario creó un nuevo pedido',
    ['pedido_id' => 123, 'usuario_id' => 45]
);
```

---

## 🎨 **Template Frontend de Ejemplo**

### Archivo: `templates/marketplace/single-marketplace_item.php`

Template completo y funcional para mostrar anuncios del Marketplace.

### Características:
- ✅ Diseño responsive
- ✅ Galería de imágenes
- ✅ Badges visuales (tipo, estado)
- ✅ Información del vendedor
- ✅ Botones de contacto
- ✅ Compartir en redes sociales
- ✅ Anuncios relacionados
- ✅ Estilos CSS incluidos
- ✅ Usa la clase Helpers para formateo

### Personalizar:
Puedes sobreescribirlo copiándolo a tu tema:
```
tu-tema/flavor-chat-ia/marketplace/single-marketplace_item.php
```

---

## 📝 **Cómo Usar Todo Esto**

### 1. **Activar Módulo Grupos de Consumo**

```bash
# 1. Activar plugin (si no lo está)
cd wp-content/plugins
mv flavor-chat-ia.DISABLED flavor-chat-ia

# 2. En WordPress Admin → Plugins → Activar
```

Luego:
- **WordPress Admin → Flavor Chat IA → Perfil App**
- Selecciona el perfil **"Grupo de Consumo"**
- O en **"Personalizado"** activa el módulo manualmente

### 2. **Crear tu primer ciclo**

1. Ve a **Productores → Añadir Productor**
2. Rellena información (nombre, contacto, certificación)
3. Ve a **Productos → Añadir Producto**
4. Asocia producto con productor, añade precio
5. Ve a **Ciclos de Pedido → Crear Ciclo**
6. Establece fechas y lugar de entrega
7. ¡Publica!

### 3. **Usar Helpers en tu código**

```php
// En cualquier módulo o template
$precio_formateado = Flavor_Chat_Helpers::formatear_precio(25.50);
$fecha_legible = Flavor_Chat_Helpers::formatear_fecha($mi_fecha);

// Enviar notificación
Flavor_Chat_Helpers::crear_notificacion(
    $usuario_id,
    'ciclo_cerrado',
    'Ciclo cerrado',
    'El ciclo de esta semana ha cerrado. Prepara tus bolsas para la recogida.'
);
```

### 4. **Personalizar Templates**

```bash
# Copiar template a tu tema
mkdir -p wp-content/themes/tu-tema/flavor-chat-ia/marketplace
cp wp-content/plugins/flavor-chat-ia/templates/marketplace/single-marketplace_item.php \
   wp-content/themes/tu-tema/flavor-chat-ia/marketplace/

# Editar y personalizar
nano wp-content/themes/tu-tema/flavor-chat-ia/marketplace/single-marketplace_item.php
```

---

## 🎯 **Resumen de Módulos Disponibles**

| Módulo | Estado | CPTs | Tablas BD | Integración IA |
|--------|--------|------|-----------|----------------|
| **WooCommerce** | ✅ Completo | - | - | ✅ |
| **Banco de Tiempo** | ✅ Completo | - | 2 tablas | ✅ |
| **Marketplace** | ✅ Completo | 1 CPT | - | ✅ |
| **Grupos de Consumo** | ⭐ NUEVO | 3 CPTs | 4 tablas | ✅ |

---

## 📊 **Base de Datos Actualizada**

### Nuevas Tablas:

#### Grupos de Consumo:
```sql
wp_flavor_gc_pedidos              -- Pedidos de usuarios
wp_flavor_gc_entregas             -- Gestión de entregas y pagos
wp_flavor_gc_consolidado          -- Pedidos consolidados por productor
wp_flavor_gc_notificaciones       -- Sistema de notificaciones
```

#### Custom Post Types:
```sql
gc_productor                       -- Productores locales
gc_producto                        -- Catálogo de productos
gc_ciclo                          -- Ciclos de pedido
```

#### Taxonomías:
```sql
gc_categoria                       -- Categorías de productos
```

---

## 🚀 **Próximos Módulos Sugeridos**

Ahora que tienes Helpers y ejemplos completos, crear nuevos módulos es más fácil:

### 1. **Módulo Restaurante**
- CPT: Menús, Reservas, Mesas
- Gestión de turnos y comandas
- Integración con TPV

### 2. **Módulo Eventos**
- CPT: Eventos, Inscripciones
- Calendario integrado
- Sistema de tickets

### 3. **Módulo Membresías**
- Gestión de socios y cuotas
- Renovaciones automáticas
- Niveles de membresía

### 4. **Módulo Fichajes**
- Control horario
- Turnos y vacaciones
- Reportes de asistencia

### 5. **Módulo Foro**
- CPT: Temas, Respuestas
- Sistema de moderación
- Notificaciones

---

## 💡 **Tips de Desarrollo**

### Usar Helpers en tus módulos:

```php
class Mi_Nuevo_Modulo extends Flavor_Chat_Module_Base {

    private function action_crear_item($params) {
        // Usar helpers
        $email_valido = Flavor_Chat_Helpers::validar_email($params['email']);
        $precio_formateado = Flavor_Chat_Helpers::formatear_precio($params['precio']);

        // Crear y notificar
        $item_id = $this->crear_item_en_bd($params);

        Flavor_Chat_Helpers::crear_notificacion(
            $usuario_id,
            'item_creado',
            'Nuevo item creado',
            'Tu item ha sido publicado correctamente'
        );

        Flavor_Chat_Helpers::enviar_email(
            $usuario_id,
            'Item publicado',
            '<p>Tu item <strong>' . $params['titulo'] . '</strong> ya está publicado.</p>'
        );

        return ['success' => true, 'item_id' => $item_id];
    }
}
```

### Template Loading:

```php
// En tu módulo
public function init() {
    add_filter('single_template', [$this, 'cargar_template']);
}

public function cargar_template($template) {
    if (is_singular('mi_cpt')) {
        // Buscar en tema primero
        $theme_template = locate_template('flavor-chat-ia/mi-modulo/single-mi_cpt.php');

        if ($theme_template) {
            return $theme_template;
        }

        // Fallback a template del plugin
        return FLAVOR_CHAT_IA_PATH . 'templates/mi-modulo/single-mi_cpt.php';
    }

    return $template;
}
```

---

## 📚 **Documentación Actualizada**

Todos los documentos están actualizados con la nueva información:
- ✅ `README.md` - Documentación principal
- ✅ `RESUMEN_IMPLEMENTACION.md` - Guía de uso
- ✅ `ARQUITECTURA_MODULOS.md` - Guía técnica
- ✅ `DIAGRAMA_ARQUITECTURA.md` - Diagramas visuales
- ✅ `PLANTILLA_MODULO.php` - Template para nuevos módulos
- ⭐ `NUEVAS_FUNCIONALIDADES.md` - Este documento

---

## 🎉 **¡Listo para Usar!**

Ahora tienes:
- ✅ 4 módulos completos funcionando
- ✅ Sistema de helpers/utilidades reutilizable
- ✅ Templates frontend personalizables
- ✅ Documentación exhaustiva
- ✅ Ejemplos de todo tipo (CPT, tablas BD, taxonomías)
- ✅ Integración completa con el chat IA

**¿Siguiente paso?**
1. Prueba el módulo de Grupos de Consumo
2. Personaliza los templates a tu gusto
3. Crea tu propio módulo usando los helpers
4. ¡Comparte tu experiencia!

---

**¿Preguntas?** Revisa la documentación o consulta los ejemplos en el código! 🚀
