# Comments (Comentarios)

Sistema de comentarios para feedback y colaboración en diseños.

## Qué es

Permite añadir comentarios anclados a elementos específicos del diseño:
- Feedback de clientes
- Revisiones de equipo
- Notas para desarrolladores
- Historial de decisiones

## Cómo usar

### Añadir comentario
1. Activar modo comentarios: clic en icono 💬 o `C`
2. Clic en el elemento a comentar
3. Escribir comentario
4. Enviar

### Ver comentarios
- Icono 💬 en elementos con comentarios
- Panel lateral muestra todos los comentarios
- Filtrar por estado: Abiertos / Resueltos / Todos

## Funcionalidades

### Menciones
Mencionar a usuarios con `@`:
```
@maria ¿Puedes revisar este botón?
```

### Hilos
Responder a comentarios para crear conversaciones:
```
Juan: El color no me convence
  └─ María: ¿Qué tal este tono? [imagen]
      └─ Juan: ¡Perfecto!
```

### Estados
| Estado | Descripción |
|--------|-------------|
| 🟡 Abierto | Pendiente de resolver |
| ✅ Resuelto | Marcado como completado |
| 📌 Fijado | Importante, no se puede resolver |

### Adjuntos
Añadir a los comentarios:
- Imágenes
- Enlaces
- Archivos
- Capturas de pantalla

## Panel de comentarios

El panel lateral muestra:
- Lista de todos los comentarios
- Filtros (abiertos, resueltos, míos)
- Búsqueda por texto
- Ordenar por fecha/elemento

## Notificaciones

Los usuarios reciben notificaciones cuando:
- Son mencionados con @
- Responden a su comentario
- Se resuelve un hilo donde participaron

## Permisos

| Rol | Puede comentar | Puede resolver | Puede eliminar |
|-----|----------------|----------------|----------------|
| Viewer | ❌ | ❌ | ❌ |
| Commenter | ✅ | Solo propios | Solo propios |
| Editor | ✅ | ✅ | Solo propios |
| Admin | ✅ | ✅ | ✅ |

## Atajos

| Atajo | Acción |
|-------|--------|
| `C` | Activar modo comentarios |
| `Esc` | Salir de modo comentarios |
| `N` | Siguiente comentario |
| `P` | Comentario anterior |

## Integración con flujos de trabajo

Los comentarios se integran con:
- **Workflows**: Bloquear publicación si hay comentarios abiertos
- **Branching**: Comentarios por rama de diseño
- **Historial**: Ver comentarios de versiones anteriores

## Exportar

Exportar comentarios como:
- PDF con capturas y comentarios
- CSV para seguimiento
- JSON para integración
