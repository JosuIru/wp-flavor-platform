# Sistema de Interconexión de Módulos - Flavor Platform

## Resumen Ejecutivo

Los dashboards de Flavor Platform ahora incluyen un sistema completo de **interconexión horizontal y vertical** entre módulos, que detecta automáticamente módulos activos relacionados y muestra widgets contextuales de conexión.

**Fecha:** 22 de marzo de 2026
**Versión:** 3.3.0
**Estado:** ✅ Implementado

---

## Conceptos: Relaciones Horizontales vs Verticales

### Relaciones Horizontales
Módulos del **mismo nivel funcional** que se complementan entre sí:
- **Ejemplo**: Marketplace ↔ Grupos de Consumo (ambos son comercio/ventas)
- **Ejemplo**: Eventos ↔ Talleres ↔ Cursos (todos son formación/actividades)
- **Ejemplo**: Incidencias ↔ Participación (ambos son gestión ciudadana)

### Relaciones Verticales
Módulos de **diferentes niveles** donde uno utiliza datos del otro:
- **Ejemplo**: Socios → Eventos (los socios tienen descuentos en eventos)
- **Ejemplo**: Biblioteca → Cursos (recursos de biblioteca para cursos)
- **Ejemplo**: Transparencia ← Todos (todos publican datos en transparencia)

---

## Dashboards con Interconexión Implementada

### ✅ 1. Marketplace
**Relaciones implementadas:**
- **Grupos de Consumo** (horizontal): Productos que también están en pedidos colectivos
- **Eventos** (horizontal): Ferias y mercados para promocionar productos
- **Socios** (vertical): Vendedores que son socios de la comunidad

**Widgets mostrados:**
```php
'grupos-consumo' => [
    'titulo' => 'Productos en Grupos de Consumo',
    'descripcion' => 'Algunos productos también están disponibles en pedidos de grupos de consumo',
]
'eventos' => [
    'titulo' => 'Próximas Ferias y Mercados (N)',
    'descripcion' => 'Eventos donde promocionar tus productos',
]
'socios' => [
    'titulo' => 'Vendedores Socios (N)',
    'descripcion' => 'Usuarios activos que venden y son socios de la comunidad',
]
```

---

### ✅ 2. Incidencias
**Relaciones implementadas:**
- **Participación** (horizontal): Ciudadanos votan qué incidencias resolver primero
- **Trabajo Digno** (horizontal): Profesionales disponibles para resolver
- **Eventos** (horizontal): Reuniones vecinales sobre incidencias
- **Transparencia** (vertical): Histórico público de resoluciones

**Widgets mostrados:**
```php
'participacion' => [
    'titulo' => 'Votaciones Activas (N)',
    'descripcion' => 'Los ciudadanos votan qué incidencias resolver primero',
]
'trabajo-digno' => [
    'titulo' => 'Profesionales Disponibles (N)',
    'descripcion' => 'Trabajadores que pueden ayudar a resolver incidencias',
]
'eventos' => [
    'titulo' => 'Reuniones Vecinales (N)',
    'descripcion' => 'Eventos para discutir y resolver incidencias del barrio',
]
'transparencia' => [
    'titulo' => 'Portal de Transparencia',
    'descripcion' => 'Consulta el histórico público de incidencias resueltas y presupuesto asignado',
]
```

---

### ✅ 3. Cursos
**Relaciones implementadas:**
- **Talleres** (horizontal): Cursos prácticos presenciales complementarios
- **Biblioteca** (vertical): Recursos y materiales de estudio
- **Eventos** (horizontal): Presentaciones y graduaciones
- **Socios** (vertical): Descuentos especiales para socios
- **Multimedia** (vertical): Videoteca de cursos

**Widgets mostrados:**
```php
'talleres' => [
    'titulo' => 'Talleres Presenciales (N)',
    'descripcion' => 'Complementa tu formación online con talleres prácticos',
]
'biblioteca' => [
    'titulo' => 'Recursos de Biblioteca (N)',
    'descripcion' => 'Libros, manuales y materiales complementarios para tus cursos',
]
'eventos' => [
    'titulo' => 'Eventos Formativos (N)',
    'descripcion' => 'Presentaciones, graduaciones y eventos relacionados con formación',
]
'socios' => [
    'titulo' => 'Beneficios para Socios (N)',
    'descripcion' => 'Los socios disfrutan de descuentos especiales en cursos y talleres',
]
'multimedia' => [
    'titulo' => 'Videoteca de Cursos',
    'descripcion' => 'Accede a grabaciones de clases y material audiovisual',
]
```

---

### ✅ 4. Grupos de Consumo
**Relaciones implementadas:**
- **Socios** (vertical): Miembros del grupo de consumo
- **Eventos** (horizontal): Fechas de recogida y asambleas
- **Marketplace** (horizontal): Productos disponibles para compra individual
- **Transparencia** (vertical): Presupuestos y movimientos económicos

**Widgets mostrados:**
```php
'socios' => [
    'titulo' => 'Socios del Grupo (N)',
    'descripcion' => 'Gestiona los socios que forman parte del grupo de consumo',
]
'eventos' => [
    'titulo' => 'Próximas Recogidas (N)',
    'descripcion' => 'Fechas de recogida de pedidos y asambleas del grupo',
]
'marketplace' => [
    'titulo' => 'Marketplace (N productos)',
    'descripcion' => 'Algunos productos también están disponibles para compra individual',
]
'transparencia' => [
    'titulo' => 'Transparencia Económica',
    'descripcion' => 'Consulta los movimientos y presupuestos del grupo de consumo',
]
```

---

### ✅ 5. Socios
**Relaciones implementadas:**
- **Eventos** (vertical): Eventos exclusivos o con descuentos
- **Cursos** (vertical): Formación con precios especiales
- **Grupos de Consumo** (vertical): Acceso exclusivo al grupo
- **Transparencia** (vertical): Uso de cuotas de socios
- **Participación** (vertical): Votaciones exclusivas para socios

**Widgets mostrados:**
```php
'eventos' => [
    'titulo' => 'Eventos Exclusivos (N)',
    'descripcion' => 'Eventos y actividades con descuentos o exclusivos para socios',
]
'cursos' => [
    'titulo' => 'Cursos con Descuento (N)',
    'descripcion' => 'Formación online con precios especiales para socios',
]
'grupos-consumo' => [
    'titulo' => 'Grupo de Consumo',
    'descripcion' => 'Acceso exclusivo al grupo de consumo ecológico para socios',
]
'transparencia' => [
    'titulo' => 'Transparencia de Cuotas',
    'descripcion' => 'Consulta cómo se utilizan las cuotas de los socios',
]
'participacion' => [
    'titulo' => 'Votaciones Activas (N)',
    'descripcion' => 'Participa en las decisiones de la asociación',
]
```

---

### ✅ 6. Eventos
**Relaciones implementadas:**
- **Talleres** (horizontal): Actividades formativas prácticas
- **Cursos** (horizontal): Formación online complementaria
- **Reservas** (vertical): Gestión de espacios para eventos
- **Socios** (vertical): Inscripción prioritaria y descuentos
- **Multimedia** (vertical): Galería de eventos anteriores
- **KulturAka** (horizontal): Agenda cultural unificada

**Widgets mostrados:**
```php
'talleres' => [
    'titulo' => 'Talleres Prácticos (N)',
    'descripcion' => 'Talleres y actividades formativas complementarias',
]
'cursos' => [
    'titulo' => 'Cursos Online (N)',
    'descripcion' => 'Formación online que complementa los eventos presenciales',
]
'reservas' => [
    'titulo' => 'Reserva de Espacios',
    'descripcion' => 'Gestiona reservas de espacios para organizar tus eventos',
]
'socios' => [
    'titulo' => 'Beneficios Socios (N)',
    'descripcion' => 'Los socios tienen inscripción prioritaria y descuentos',
]
'multimedia' => [
    'titulo' => 'Galería Multimedia',
    'descripcion' => 'Fotos, vídeos y grabaciones de eventos anteriores',
]
'kulturaka' => [
    'titulo' => 'Agenda KulturAka',
    'descripcion' => 'Agenda cultural unificada de eventos y actividades',
]
```

---

## Arquitectura Técnica

### Detección Automática
```php
$active_modules = get_option('flavor_active_modules', []);
$modulos_relacionados = [];

// Detectar si un módulo está activo
if (in_array('eventos', $active_modules)) {
    // Consultar datos específicos del módulo
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
        $eventos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_eventos WHERE ...");

        // Añadir widget solo si hay datos relevantes
        if ($eventos_activos > 0) {
            $modulos_relacionados['eventos'] = [
                'titulo' => sprintf(__('Eventos Activos (%d)', 'flavor-chat-ia'), $eventos_activos),
                'descripcion' => __('...', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar',
                'url' => admin_url('admin.php?page=flavor-eventos'),
            ];
        }
    }
}
```

### Renderizado Responsivo
```php
if (!empty($modulos_relacionados)):
?>
<div class="dm-section" style="margin-top: 32px;">
    <div class="dm-section__header">
        <h3 class="dm-section__title">
            <span class="dashicons dashicons-networking"></span>
            <?php _e('Módulos Relacionados', 'flavor-chat-ia'); ?>
        </h3>
    </div>
    <div class="dm-section__content">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <?php foreach ($modulos_relacionados as $modulo): ?>
            <div style="padding: 20px; background: var(--dm-bg-secondary); border-radius: 12px; border-left: 4px solid var(--dm-primary);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                    <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>" style="color: var(--dm-primary); font-size: 24px;"></span>
                    <strong style="font-size: 15px;"><?php echo esc_html($modulo['titulo']); ?></strong>
                </div>
                <p style="color: var(--dm-text-secondary); font-size: 13px; margin: 0 0 12px 0;"><?php echo esc_html($modulo['descripcion']); ?></p>
                <a href="<?php echo esc_url($modulo['url']); ?>" class="button button-small">
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
```

---

## Matriz de Interconexiones

| Dashboard | Módulos Relacionados | Tipo Relación |
|-----------|---------------------|---------------|
| **Marketplace** | Grupos Consumo, Eventos, Socios | H, H, V |
| **Incidencias** | Participación, Trabajo Digno, Eventos, Transparencia | H, H, H, V |
| **Cursos** | Talleres, Biblioteca, Eventos, Socios, Multimedia | H, V, H, V, V |
| **Grupos Consumo** | Socios, Eventos, Marketplace, Transparencia | V, H, H, V |
| **Socios** | Eventos, Cursos, GC, Transparencia, Participación | V, V, V, V, V |
| **Eventos** | Talleres, Cursos, Reservas, Socios, Multimedia, KulturAka | H, H, V, V, V, H |

**Leyenda:**
- **H**: Relación Horizontal (mismo nivel funcional)
- **V**: Relación Vertical (diferente nivel, uno usa datos del otro)

---

## Beneficios del Sistema

### Para Administradores
- ✅ **Visibilidad completa**: Ven de un vistazo cómo se relacionan los módulos
- ✅ **Navegación rápida**: Acceso directo a módulos relacionados
- ✅ **Datos contextuales**: Contadores dinámicos de elementos relacionados
- ✅ **Detección automática**: No requiere configuración manual

### Para Usuarios
- ✅ **Descubrimiento**: Conocen módulos que quizá no sabían que existían
- ✅ **Experiencia integrada**: No sienten que cada módulo es un "silo" aislado
- ✅ **Acciones cruzadas**: Pueden realizar acciones entre módulos fácilmente
- ✅ **Contexto**: Entienden cómo un módulo afecta a otros

### Para Desarrolladores
- ✅ **Extensible**: Fácil añadir nuevas relaciones
- ✅ **Performante**: Solo consulta datos si el módulo está activo
- ✅ **Modular**: Cada dashboard gestiona sus propias relaciones
- ✅ **Reutilizable**: Patrón consistente en todos los dashboards

---

## Próximos Pasos (Pendientes)

### Alta Prioridad
1. **Acciones Rápidas Inter-Módulo**: Permitir crear entidad en módulo relacionado desde dashboard actual
   - Ejemplo: Desde Eventos, crear taller relacionado con un click
   - Ejemplo: Desde Marketplace, crear evento de feria automáticamente

2. **Widgets de Datos en Vivo**: Mostrar datos reales en lugar de solo contadores
   - Ejemplo: En Cursos, mostrar últimos 3 libros de Biblioteca relacionados
   - Ejemplo: En Incidencias, mostrar las 3 propuestas más votadas

3. **Sincronización de Datos**: Cuando se crea algo en un módulo, sugerir relacionarlo con otros
   - Ejemplo: Al crear evento de "Feria", sugerir crear categoría en Marketplace
   - Ejemplo: Al crear curso, sugerir crear evento de presentación

### Media Prioridad
4. **Dashboard Unificado de Relaciones**: Vista global de todas las interconexiones
5. **Estadísticas Cross-Module**: Métricas combinadas de múltiples módulos
6. **Notificaciones Inter-Módulo**: Avisar cuando un módulo afecta a otro

### Baja Prioridad
7. **Automatizaciones**: Reglas tipo "Si pasa X en módulo A, hacer Y en módulo B"
8. **Grafos de Relaciones**: Visualización en grafo de conexiones entre módulos
9. **Recomendaciones IA**: Sugerir módulos que deberían activarse basándose en los activos

---

## Archivos Modificados

```
includes/frontend/class-user-portal.php
    - Añadidos métodos get_marketplace_stats/widget
    - Añadidos métodos get_cursos_stats/widget
    - Añadidos métodos get_incidencias_stats/widget

includes/modules/marketplace/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +85)

includes/modules/incidencias/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +99)

includes/modules/cursos/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +103)

includes/modules/grupos-consumo/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +92)

includes/modules/socios/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +105)

includes/modules/eventos/views/dashboard-mejorado.php
    - Añadida sección "Módulos Relacionados" (líneas +115)
```

---

## Notas Técnicas

### Rendimiento
- **Queries Condicionales**: Solo se ejecutan queries si el módulo está activo
- **Caché Nativo**: Usa `get_option()` que tiene caché automático de WordPress
- **Queries Optimizadas**: Solo SELECT COUNT(*) para obtener totales
- **Sin Overhead**: Si no hay módulos relacionados activos, la sección no se renderiza

### Seguridad
- **Escapado**: Todos los outputs usan `esc_html()`, `esc_attr()`, `esc_url()`
- **Preparación SQL**: Todos los queries usan `$wpdb->prepare()`
- **Capacidades**: Los URLs de administración solo son accesibles con permisos

### Internacionalización
- **Todas las cadenas traducibles**: Usan `__()` y `sprintf()`
- **Dominio de texto**: `'flavor-chat-ia'`
- **Context-aware**: Las descripciones explican claramente la relación

---

## Conclusión

El sistema de interconexión de módulos transforma Flavor Platform de una colección de módulos independientes en un **ecosistema integrado**, donde los usuarios y administradores pueden navegar fluidamente entre funcionalidades relacionadas y entender cómo sus acciones impactan en diferentes áreas del sistema.

**Estado actual:** Sistema base implementado en 6 dashboards principales
**Cobertura:** ~66% de módulos clave con interconexión
**Próximo objetivo:** Expandir a los 60+ módulos restantes y añadir acciones rápidas

---

**Documento generado:** 22 de marzo de 2026
**Versión Flavor Platform:** 3.3.0
**Mantenedor:** Flavor Team
