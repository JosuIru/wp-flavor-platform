# Accesibilidad (a11y)

Herramientas y funciones de accesibilidad integradas en el editor VBP.

## Descripcion

VBP incluye funciones de accesibilidad tanto para el editor como para el contenido creado. Permite verificar cumplimiento de WCAG, navegacion por teclado completa, lectores de pantalla, y herramientas para crear contenido accesible.

## Caracteristicas del Editor

### Navegacion por Teclado

Todas las funciones del editor son accesibles via teclado:

| Tecla | Accion |
|-------|--------|
| `Tab` | Navegar entre elementos |
| `Shift+Tab` | Navegar hacia atras |
| `Enter` | Activar/Editar |
| `Escape` | Cancelar/Cerrar |
| `Arrow Keys` | Navegar dentro de paneles |
| `Space` | Seleccionar/Toggle |
| `F10` | Acceder a menu |

### Roles ARIA

El editor usa roles ARIA apropiados:

- `role="application"` para el editor
- `role="toolbar"` para barras de herramientas
- `role="tree"` para panel de capas
- `role="listbox"` para selectores
- `aria-live` para notificaciones

### Lectores de Pantalla

Soporte para:
- NVDA
- JAWS
- VoiceOver (macOS)
- TalkBack (Android)

## Herramientas de Verificacion

### Panel de Accesibilidad

Abre el panel de accesibilidad para verificar el contenido:

- Atajo: `Ctrl+Alt+A`
- Menu: View > Accessibility
- Paleta de comandos: "a11y"

### Verificaciones Automaticas

| Verificacion | Descripcion |
|--------------|-------------|
| Contraste de color | WCAG AA/AAA |
| Alt de imagenes | Texto alternativo |
| Estructura de encabezados | Jerarquia correcta |
| Links descriptivos | No "click aqui" |
| Formularios | Labels asociados |
| Tabindex | Orden de foco |

### Ejecutar Auditoria

```javascript
const a11y = window.VBPAccessibility;

// Auditoria completa
const results = await a11y.audit();

// Auditoria de elemento
const elementResults = await a11y.auditElement(elementId);

// Verificar contraste
const contrast = a11y.checkContrast('#333333', '#ffffff');
// { ratio: 12.63, passesAA: true, passesAAA: true }

// Verificar estructura
const structure = a11y.checkHeadingStructure();
// { valid: true, issues: [] }
```

## API JavaScript

### VBPAccessibility

```javascript
const a11y = window.VBPAccessibility;

// Auditoria
a11y.audit();                          // Auditoria completa
a11y.auditElement(elementId);          // Auditar elemento
a11y.auditPage();                      // Auditar pagina publicada

// Contraste
a11y.checkContrast(fg, bg);            // Verificar contraste
a11y.suggestColor(fg, bg, target);     // Sugerir color accesible

// Estructura
a11y.checkHeadingStructure();          // Verificar encabezados
a11y.checkLinkText();                  // Verificar links
a11y.checkImageAlts();                 // Verificar alts
a11y.checkFormLabels();                // Verificar formularios

// Navegacion
a11y.getTabOrder();                    // Obtener orden de tab
a11y.setTabOrder(elementId, order);    // Establecer tabindex
a11y.skipLinks();                      // Configurar skip links

// Anuncios
a11y.announce(message);                // Anunciar a lectores
a11y.announcePolitely(message);        // Anunciar (no urgente)
a11y.announceAssertively(message);     // Anunciar (urgente)

// Preferencias
a11y.getPreferences();                 // Obtener preferencias
a11y.setPreferences(prefs);            // Establecer preferencias
```

### Preferencias de Usuario

```javascript
// Respetar preferencias del sistema
a11y.setPreferences({
    reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    highContrast: window.matchMedia('(prefers-contrast: more)').matches,
    colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
});
```

## Propiedades ARIA en Elementos

### En el Inspector

El inspector muestra una seccion "Accesibilidad" para configurar:

| Propiedad | Descripcion |
|-----------|-------------|
| `role` | Rol del elemento |
| `aria-label` | Etiqueta accesible |
| `aria-labelledby` | ID de elemento que etiqueta |
| `aria-describedby` | ID de elemento que describe |
| `aria-hidden` | Ocultar de tecnologias asistivas |
| `tabindex` | Orden de foco |
| `aria-expanded` | Estado expandido/colapsado |
| `aria-selected` | Estado de seleccion |
| `aria-disabled` | Estado deshabilitado |
| `aria-live` | Region en vivo |

### Configurar via API

```javascript
const store = Alpine.store('vbp');

store.updateElement(elementId, {
    aria: {
        role: 'button',
        label: 'Cerrar menu',
        expanded: false
    }
});
```

## Modos de Accesibilidad

### Reduced Motion

Reduce o elimina animaciones:

```javascript
// Activar modo reduced motion
a11y.setReducedMotion(true);

// Se aplica automaticamente con:
// @media (prefers-reduced-motion: reduce)
```

### High Contrast

Aumenta contraste visual:

```javascript
// Activar alto contraste
a11y.setHighContrast(true);
```

### Focus Visible

Asegura que el foco sea siempre visible:

```css
:focus-visible {
    outline: 3px solid var(--vbp-accent-color);
    outline-offset: 2px;
}
```

## Indicadores Visuales

### En el Canvas

El editor muestra indicadores de problemas:

- **Rojo**: Error critico (ej: imagen sin alt)
- **Naranja**: Advertencia (ej: contraste bajo)
- **Azul**: Sugerencia (ej: link generico)

### En el Panel

```
ACCESIBILIDAD
├── Errores (2)
│   ├── [!] img#hero: Falta alt
│   └── [!] button.cta: Sin texto accesible
├── Advertencias (3)
│   ├── [?] h1: Contraste 4.2:1 (AA requiere 4.5:1)
│   ├── [?] link: "Click aqui" no es descriptivo
│   └── [?] Heading: Salta de h2 a h4
└── Pasado (15)
    └── Todo OK
```

## Eventos

```javascript
// Auditoria completada
document.addEventListener('vbp:a11y:audit:complete', (e) => {
    console.log('Errores:', e.detail.errors);
    console.log('Advertencias:', e.detail.warnings);
});

// Problema detectado
document.addEventListener('vbp:a11y:issue:detected', (e) => {
    console.log('Tipo:', e.detail.type);
    console.log('Elemento:', e.detail.elementId);
    console.log('Mensaje:', e.detail.message);
});

// Problema corregido
document.addEventListener('vbp:a11y:issue:fixed', (e) => {
    console.log('Corregido:', e.detail.elementId);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+A` | Abrir panel de accesibilidad |
| `F6` | Cambiar entre regiones |
| `Ctrl+F6` | Siguiente panel |
| `Ctrl+Shift+F6` | Panel anterior |

## Integracion con WCAG

### Niveles Soportados

- **A**: Requisitos minimos
- **AA**: Recomendado (default)
- **AAA**: Maximo cumplimiento

### Configurar Nivel

```javascript
a11y.setWCAGLevel('AA');  // 'A', 'AA', 'AAA'
```

### Reglas por Nivel

| Regla | A | AA | AAA |
|-------|---|----|----|
| Texto alternativo | X | X | X |
| Contraste 4.5:1 | - | X | X |
| Contraste 7:1 | - | - | X |
| Redimensionar texto | X | X | X |
| Contenido reflow | - | X | X |
| Focus visible | X | X | X |
| Skip links | - | X | X |

## Recomendaciones

### Para el Contenido

1. **Imagenes**: Siempre incluir alt descriptivo
2. **Links**: Texto descriptivo, no "click aqui"
3. **Headings**: Seguir jerarquia (h1 > h2 > h3...)
4. **Contraste**: Minimo 4.5:1 para texto normal
5. **Formularios**: Labels asociados a inputs

### Para Interacciones

1. **Foco**: Visible y predecible
2. **Tiempo**: Sin limites de tiempo estrictos
3. **Movimiento**: Permitir pausar/detener
4. **Errores**: Mensajes claros y soluciones

## Solucionar Problemas

### El lector de pantalla no lee correctamente

1. Verifica roles ARIA
2. Comprueba estructura del documento
3. Revisa orden de lectura
4. Asegura que aria-live esta configurado

### El foco se pierde

1. Verifica tabindex
2. Comprueba que no hay trampas de foco
3. Revisa gestion de modales

### La auditoria no detecta problemas

1. Actualiza a la ultima version
2. Verifica que el DOM esta cargado
3. Algunas verificaciones requieren contenido real
