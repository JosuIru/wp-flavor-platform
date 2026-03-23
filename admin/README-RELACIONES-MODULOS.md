# Configuración de Relaciones entre Módulos

## Guía Rápida para Administradores

### ¿Qué es esto?

Este panel te permite configurar qué herramientas (Foro, Chat, Multimedia, etc.) aparecen en la navegación de cada módulo principal (Grupos de Consumo, Eventos, Comunidades, etc.).

---

## Acceso

**Admin → Flavor Platform → Relaciones Módulos**

---

## Conceptos Básicos

### Módulos Verticales (Principales)

Son los módulos de negocio principal:
- Grupos de Consumo
- Eventos
- Comunidades
- Socios
- Marketplace
- Etc.

### Módulos Horizontales (Herramientas)

Son servicios que se integran:
- Foros (discusiones)
- Chat Interno (mensajería)
- Multimedia (galería)
- Recetas
- Biblioteca
- Podcast
- Radio
- Red Social

---

## Cómo Usar

### 1. Seleccionar Contexto

En la parte superior, elige:
- **Global (por defecto)**: Aplica a todo el sitio
- **Comunidad específica**: Configuración solo para esa comunidad

### 2. Configurar Relaciones

Para cada módulo vertical:
1. Marca los checkboxes de los módulos horizontales que quieres vincular
2. Los seleccionados aparecerán en la navegación de ese módulo

**Ejemplo**: Si marcas "Foros" y "Recetas" en "Grupos de Consumo", los usuarios verán esos enlaces cuando accedan a Grupos de Consumo.

### 3. Ver Previsualización

En la parte inferior verás cómo quedará la navegación:

```
Grupos Consumo > [Foros] [Recetas] [Multimedia]
Eventos > [Foros] [Chat] [Multimedia]
```

### 4. Guardar

Haz clic en **"Guardar Relaciones"**.

---

## Casos de Uso Comunes

### Caso 1: Grupo de Consumo Ecológico

**Quieres**: Que los miembros puedan ver recetas, fotos de productos y participar en el foro.

**Configuración**:
- Módulo: Grupos de Consumo
- Marcar: ☑ Foros, ☑ Recetas, ☑ Multimedia

### Caso 2: Comunidad Vecinal Activa

**Quieres**: Máxima comunicación entre vecinos con red social, foros y eventos.

**Configuración**:
- Módulo: Comunidades
- Marcar: ☑ Foros, ☑ Red Social, ☑ Eventos, ☑ Multimedia, ☑ Participación

### Caso 3: Asociación Educativa

**Quieres**: Talleres con material multimedia y biblioteca de recursos.

**Configuración**:
- Módulo: Talleres
- Marcar: ☑ Multimedia, ☑ Biblioteca, ☑ Eventos, ☑ Cursos

### Caso 4: Diferentes Comunidades, Diferentes Necesidades

**Situación**: Tienes 3 comunidades en tu plataforma, cada una con necesidades distintas.

**Solución**:
1. Configurar relaciones globales para todas
2. Para cada comunidad específica, cambiar el contexto y personalizar

---

## Configuración por Contexto

### Global

Afecta a todas las instancias que NO tengan configuración específica.

**Usar cuando**: Quieres la misma configuración en toda la plataforma.

### Por Comunidad

Configuración específica que sobrescribe la global.

**Usar cuando**: Cada comunidad tiene necesidades diferentes.

**Ejemplo**:
- **Global**: Grupos Consumo con Foros + Recetas
- **Comunidad "La Verde"**: Grupos Consumo con Foros + Recetas + Multimedia + Podcast
- **Comunidad "El Barrio"**: Grupos Consumo solo con Foros

---

## Valores por Defecto

Al instalar Flavor Platform, se crean relaciones por defecto inteligentes basadas en casos de uso comunes:

- **Grupos de Consumo**: Foros, Recetas, Multimedia, Eventos, Socios, Biblioteca
- **Eventos**: Foros, Multimedia, Chat, Espacios Comunes, Talleres, Red Social
- **Comunidades**: Foros, Red Social, Multimedia, Eventos, Participación, Transparencia
- Etc.

Puedes modificar estas relaciones en cualquier momento.

---

## Resetear Configuración

Si quieres volver a los valores originales del código:

1. Haz clic en **"Resetear a Valores por Defecto"**
2. Confirma la acción
3. La página se recargará con los valores del código

**Nota**: Esto ELIMINA toda configuración personalizada guardada.

---

## Preguntas Frecuentes

### ¿Puedo tener diferentes configuraciones por comunidad?

**Sí**. Usa el selector de contexto para elegir una comunidad específica y configura sus relaciones.

### ¿Qué pasa si no configuro ninguna relación?

El sistema usa los valores por defecto definidos en el código del módulo.

### ¿Los cambios afectan inmediatamente?

**Sí**. Los usuarios verán los cambios en la navegación inmediatamente después de guardar.

### ¿Puedo agregar módulos horizontales nuevos?

Los módulos disponibles dependen de cuáles estén activos en tu instalación. Activa primero el módulo en **Flavor Platform → Módulos**.

### ¿Cómo sé qué módulos vincular?

**Recomendación**: Piensa en qué herramientas necesitan tus usuarios cuando usan cada módulo principal.

- **Grupos de Consumo**: Probablemente necesiten Recetas (usar productos), Foros (preguntar), Multimedia (ver fotos)
- **Eventos**: Chat (coordinación), Multimedia (galería), Foros (discusión)

---

## Soporte Técnico

Si tienes problemas:

1. **Verificar estado del sistema**: Ejecuta el test
   ```bash
   wp eval-file tools/test-module-relations.php
   ```

2. **Ver documentación completa**: `/docs/SISTEMA-RELACIONES-MODULOS.md`

3. **Contactar soporte**: soporte@gailu.net

---

**Última actualización**: 2026-03-22
**Versión del sistema**: 2.2.0
