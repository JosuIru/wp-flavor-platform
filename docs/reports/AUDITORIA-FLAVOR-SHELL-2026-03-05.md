# Auditoría del Sistema Flavor Shell

**Fecha**: 2026-03-05
**Versión analizada**: 3.2.0
**Analista**: Claude Code

---

## Resumen General

El **Flavor Admin Shell** es un sistema de navegación que reemplaza el sidebar nativo de WordPress con una interfaz moderna y elegante. Usa Alpine.js para la reactividad y está bien estructurado.

| Archivo | Líneas | Función |
|---------|--------|---------|
| `admin/class-admin-shell.php` | 530 | Clase principal PHP |
| `admin/js/admin-shell.js` | 356 | Componente Alpine.js |
| `admin/views/shell-sidebar.php` | 258 | Template HTML |
| `admin/css/admin-shell.css` | ~1000 | Estilos |

---

## Aspectos Positivos

### 1. Arquitectura limpia
- Patrón Singleton correctamente implementado
- Separación clara entre PHP, JS y templates
- Uso de constantes para prefijos (`PAGE_PREFIXES`, `POST_TYPE_PREFIXES`)

### 2. Seguridad
- Validación con `check_ajax_referer()` en AJAX handlers
- Sanitización de inputs con `sanitize_text_field()`
- Uso de `esc_html()`, `esc_attr()`, `esc_url()` en outputs

### 3. Accesibilidad
- Skip link para contenido (`#wpbody-content`)
- Roles ARIA (`role="navigation"`, `role="menu"`, `role="menuitem"`)
- `aria-current="page"` en item activo
- `aria-label` en botones

### 4. UX/Funcionalidad
- Modo oscuro con persistencia en localStorage
- Estado colapsado persistido
- Atajos de teclado (Ctrl+B, flechas, Escape)
- Responsive con menú mobile

### 5. Sistema de Vistas
- Permite filtrar menús según rol (Admin vs Gestor)
- Integración con `Flavor_Admin_Menu_Manager`

---

## Problemas Detectados

### 1. Dependencia de CDN externo (RIESGO MEDIO)

```php
// admin/class-admin-shell.php línea 259
'https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js'
```

- Alpine.js se carga desde CDN externo
- Si el CDN falla, el shell queda no funcional
- **Recomendación**: Incluir Alpine.js localmente

### 2. Posible filtro script_loader_tag sin cleanup (BAJO)

```php
// admin/class-admin-shell.php líneas 265-270
add_filter('script_loader_tag', function($tag, $handle) {
    if ($handle === 'alpine') {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 2);
```

- El filtro se añade cada vez que se encolan assets
- Podría añadir múltiples veces el mismo callback

### 3. MutationObserver duplicado (BAJO)

```javascript
// admin/js/admin-shell.js
// Se crea un observer en wrapWPContent() y otro en DOMContentLoaded
// Ambos observan los mismos elementos
```

- Dos MutationObservers observando `#poststuff`
- Potencial overhead innecesario

### 4. Múltiples setTimeouts hardcodeados (BAJO)

```javascript
// admin/js/admin-shell.js líneas 328-331
setTimeout(fixPostEditorLayout, 100);
setTimeout(fixPostEditorLayout, 300);
setTimeout(fixPostEditorLayout, 500);
setTimeout(fixPostEditorLayout, 1000);
```

- Hack para lidiar con WordPress/jQuery UI
- Funcionalmente OK pero indica fragilidad

### 5. Vista selector siempre visible (DISEÑO)

- El selector de vistas (Admin/Gestor) se muestra siempre
- Podría confundir a usuarios que no gestionan grupos

### 6. Emojis en código (MENOR)

```php
// admin/views/shell-sidebar.php líneas 82, 95, 103
echo $es_vista_admin ? '👤' : '👥';
```

- Los emojis pueden no renderizar igual en todos los sistemas

---

## Flujo de Ejecución

```
┌─────────────────────────────────────────────────────────────┐
│                    INICIALIZACIÓN                           │
├─────────────────────────────────────────────────────────────┤
│ 1. flavor-chat-ia.php → require class-admin-shell.php      │
│ 2. Singleton → Flavor_Admin_Shell::get_instance()          │
│ 3. Constructor añade hooks:                                 │
│    - admin_init (vacío)                                     │
│    - admin_enqueue_scripts (enqueue_assets)                 │
│    - admin_footer (render_shell)                            │
│    - wp_ajax_* (toggle_shell)                               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    DETECCIÓN DE PÁGINA                      │
├─────────────────────────────────────────────────────────────┤
│ is_flavor_page() verifica:                                  │
│ - 53 prefijos de páginas admin                              │
│ - 14 prefijos de CPTs                                       │
│ - 13 prefijos de taxonomías                                 │
│ - Screen ID de WordPress                                    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    RENDERIZADO                              │
├─────────────────────────────────────────────────────────────┤
│ Si is_flavor_page() && is_shell_enabled():                  │
│ - Encolar CSS/JS                                            │
│ - Añadir clase 'fls-shell-active' al body                   │
│ - Renderizar shell-sidebar.php en admin_footer              │
│ - Alpine.js inicializa componente flavorShell               │
└─────────────────────────────────────────────────────────────┘
```

---

## Estructura de Navegación

El shell organiza **13 secciones** con **~50 items**:

| Sección | Items |
|---------|-------|
| Mi App | Dashboard, Widgets, Módulos, Diseño, Páginas, Editor Visual |
| Comunidad | Socios, Colectivos, Comunidades, Foros, Red Social |
| Economía | Grupos Consumo, Marketplace, Banco Tiempo, Economía Don |
| Actividades | Eventos, Cursos, Talleres, Reservas |
| Servicios | Trámites, Incidencias, Ayuda Vecinal, Participación |
| Recursos | Huertos, Espacios, Biblioteca, Carpooling |
| Sostenibilidad | Reciclaje, Compostaje, Energía, Bicicletas |
| Comunicación | Multimedia, Radio, Podcast, Campañas |
| Chat IA | Configuración, Escalados |
| Apps | Apps Móviles, Deep Links, Red |
| Extensiones | Addons, Marketplace, Newsletter |
| Herramientas | Export/Import, Diagnóstico, Actividad, API Docs |
| Ayuda | Documentación, Tours |

---

## Estado del Usuario

### User Meta

| Meta Key | Valor | Efecto |
|----------|-------|--------|
| `flavor_admin_shell_disabled` | `'1'` | Shell desactivado para usuario |
| `flavor_admin_vista_activa` | `'admin'` / `'gestor_grupos'` | Vista activa |

### LocalStorage

- `flavorShellCollapsed`: Estado colapsado
- `flavorShellDarkMode`: Modo oscuro

---

## Conclusión

El **Flavor Shell** está **bien implementado** con buenas prácticas de seguridad y accesibilidad. Los problemas detectados son menores y no afectan funcionalidad crítica.

**Puntuación**: 4/5

| Categoría | Estado |
|-----------|--------|
| Seguridad | Correcto |
| Accesibilidad | Buena |
| Performance | Aceptable (CDN, observers duplicados) |
| Mantenibilidad | Buena estructura |
| UX | Moderna y funcional |

---

## Mejoras Propuestas

### 1. Subvistas de Módulos en el Shell

Cuando el usuario está en un dashboard de módulo (ej: `eventos-dashboard`), mostrar las subvistas/tabs de ese módulo como subitems expandidos en el shell.

**Estado actual**: Solo se muestra el item principal "Eventos"
**Propuesta**: Expandir automáticamente mostrando las tabs del dashboard (Listado, Calendario, Inscripciones, etc.)

### 2. Badges de Notificaciones

Añadir indicadores numéricos en las secciones/items del shell para mostrar:
- Acciones pendientes
- Notificaciones sin leer
- Items que requieren atención

**Ejemplos**:
- Incidencias: mostrar número de incidencias abiertas
- Trámites: mostrar expedientes pendientes de revisión
- Socios: mostrar solicitudes pendientes de aprobación

### 3. Incluir Alpine.js localmente

Descargar Alpine.js y servirlo desde el plugin para evitar dependencia de CDN externo.

---

## Archivos Analizados

- `/admin/class-admin-shell.php`
- `/admin/js/admin-shell.js`
- `/admin/css/admin-shell.css`
- `/admin/views/shell-sidebar.php`
