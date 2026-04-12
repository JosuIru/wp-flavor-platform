# Command Palette

Paleta de comandos para acceso rápido a todas las funciones del editor.

## Qué es

Similar a VS Code o Figma, permite ejecutar cualquier acción escribiendo su nombre.

## Cómo abrir

| Atajo | Plataforma |
|-------|------------|
| `Cmd+K` | Mac |
| `Ctrl+K` | Windows/Linux |

## Funcionalidades

### Búsqueda de comandos
Escribe para filtrar:
- `add` → Ver todos los bloques para añadir
- `align` → Comandos de alineación
- `export` → Opciones de exportación

### Búsqueda de elementos
Prefijo `>` para buscar en el canvas:
- `>header` → Buscar elementos con "header"
- `>button` → Buscar todos los botones

### Acciones rápidas
Prefijo `/` para acciones:
- `/save` → Guardar página
- `/preview` → Abrir preview
- `/publish` → Publicar

## Comandos disponibles

### Bloques
| Comando | Acción |
|---------|--------|
| `Add Section` | Añadir sección |
| `Add Container` | Añadir contenedor |
| `Add Text` | Añadir texto |
| `Add Image` | Añadir imagen |
| `Add Button` | Añadir botón |

### Edición
| Comando | Acción |
|---------|--------|
| `Copy` | Copiar selección |
| `Paste` | Pegar |
| `Duplicate` | Duplicar |
| `Delete` | Eliminar |
| `Group` | Agrupar |
| `Ungroup` | Desagrupar |

### Alineación
| Comando | Acción |
|---------|--------|
| `Align Left` | Alinear izquierda |
| `Align Center` | Centrar horizontal |
| `Align Right` | Alinear derecha |
| `Align Top` | Alinear arriba |
| `Align Middle` | Centrar vertical |
| `Align Bottom` | Alinear abajo |

### Vista
| Comando | Acción |
|---------|--------|
| `Zoom In` | Aumentar zoom |
| `Zoom Out` | Reducir zoom |
| `Zoom to Fit` | Ajustar a pantalla |
| `Zoom to Selection` | Zoom a selección |
| `Toggle Rulers` | Mostrar/ocultar reglas |
| `Toggle Grid` | Mostrar/ocultar grid |

### Exportar
| Comando | Acción |
|---------|--------|
| `Export HTML` | Exportar como HTML |
| `Export JSON` | Exportar estructura |
| `Export Image` | Capturar como imagen |

## Personalización

Los plugins pueden registrar comandos propios:

```javascript
VBPCommandPalette.register({
    id: 'mi-comando',
    name: 'Mi Comando Personalizado',
    shortcut: 'Ctrl+Shift+M',
    action: () => { /* ... */ }
});
```
