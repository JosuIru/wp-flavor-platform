# Migración del Web Builder a Addon (v3.0)

## ⚠️ Cambio Importante en v3.0

A partir de Flavor Platform 3.0, el **Web Builder** ha sido extraído como un addon independiente para mejorar el rendimiento y la modularidad del plugin.

## ¿Qué significa esto para ti?

### Si NO usas el Web Builder

✅ **Nada que hacer**. Tu sitio funcionará más rápido al no cargar código innecesario.

### Si SÍ usas el Web Builder o Landing Pages

⚠️ **Acción requerida**: Debes instalar el addon Web Builder Pro para mantener la funcionalidad.

## Guía de Migración Paso a Paso

### Opción 1: Instalación Manual (Recomendada)

1. **Descargar el addon**
   - Descarga `flavor-web-builder-pro.zip` desde [gailu.net/addons](https://gailu.net/addons)

2. **Instalar el addon**
   - Ve a **Plugins > Añadir nuevo** en WordPress
   - Haz clic en **Subir plugin**
   - Selecciona el archivo `flavor-web-builder-pro.zip`
   - Haz clic en **Instalar ahora**

3. **Activar el addon**
   - Haz clic en **Activar plugin**
   - El addon se registrará automáticamente en Flavor Platform

4. **Verificar funcionamiento**
   - Ve a **Flavor Platform > Addons**
   - Verifica que "Web Builder Pro" aparece como activo
   - Ve a **Landing Pages** y verifica que tus páginas siguen funcionando

### Opción 2: Desde el Panel de Addons (Próximamente)

1. Ve a **Flavor Platform > Addons**
2. Haz clic en **Explorar Addons**
3. Busca "Web Builder Pro"
4. Haz clic en **Instalar**
5. Haz clic en **Activar**

## ¿Qué se mantiene igual?

✅ **Todas tus Landing Pages existentes** - Se preservan intactas
✅ **Todos tus componentes** - Los 170+ componentes siguen disponibles
✅ **Tus templates personalizados** - Se mantienen sin cambios
✅ **Configuraciones** - Todas las configuraciones se preservan
✅ **Funcionalidad** - El Web Builder funciona exactamente igual

## ¿Qué cambia?

### Antes (v2.x)
```
Flavor Platform
  └── Web Builder (incluido en el core)
```

### Ahora (v3.0+)
```
Flavor Platform (core ligero)
  └── Web Builder Pro (addon independiente)
```

## Ventajas de la Nueva Arquitectura

### 1. **Mejor Rendimiento**
- El core de Flavor Platform es ~740KB más ligero
- Solo se carga el Web Builder si lo necesitas
- Menos consumo de memoria

### 2. **Actualizaciones Independientes**
- Puedes actualizar el Web Builder sin actualizar el core
- Ciclos de actualización más rápidos
- Menos riesgo de incompatibilidades

### 3. **Activación/Desactivación Flexible**
- Desactiva el Web Builder si no lo usas
- Reactívalo cuando lo necesites
- Sin pérdida de datos

## Preguntas Frecuentes

### ¿Perderé mis Landing Pages al actualizar?

**No.** Todas tus landing pages se preservan. Solo necesitas instalar el addon para volver a editarlas.

### ¿El addon es gratuito?

**Sí.** El Web Builder Pro sigue siendo gratuito, solo que ahora es un plugin separado.

### ¿Tengo que pagar por el addon?

**No.** El addon Web Builder Pro es gratuito y de código abierto (GPL v2).

### ¿Qué pasa si no instalo el addon?

- Tus landing pages existentes seguirán siendo **visibles para los visitantes**
- **No podrás editar** landing pages existentes
- **No podrás crear** nuevas landing pages
- El resto de Flavor Platform funcionará normalmente

### ¿Puedo volver a la versión anterior?

Sí, pero no es recomendado. Si tienes problemas:

1. Desactiva el addon Web Builder Pro
2. Degrada a Flavor Platform 2.x
3. Reporta el problema en GitHub

### ¿Necesito desinstalar algo?

No. Puedes dejar los archivos del web-builder en el core (están comentados y no se cargan).

### ¿El addon tiene todas las funcionalidades?

**Sí**. El addon Web Builder Pro tiene las mismas funcionalidades que tenía en el core:

- 170+ componentes
- 17 templates predefinidos
- Constructor drag & drop
- Asistente IA
- Vista previa en tiempo real
- Custom Post Type `flavor_landing`

## Problemas Conocidos y Soluciones

### Problema: "No aparece el menú Landing Pages"

**Solución**: Asegúrate de que el addon Web Builder Pro está activado en **Flavor Platform > Addons**.

### Problema: "Mis landing pages muestran error 404"

**Solución**:
1. Ve a **Ajustes > Enlaces permanentes**
2. Haz clic en **Guardar cambios** (sin modificar nada)
3. Esto regenerará las reglas de reescritura

### Problema: "El Page Builder no carga en el editor"

**Solución**:
1. Desactiva el addon Web Builder Pro
2. Reactívalo
3. Limpia la caché del navegador (Ctrl + F5)
4. Recarga la página de edición

### Problema: "Error: Flavor Platform no encontrado"

**Solución**: Asegúrate de tener Flavor Platform 3.0 o superior instalado y activado.

## Soporte

Si tienes problemas con la migración:

1. **Documentación**: https://gailu.net/docs/migration-web-builder
2. **GitHub Issues**: https://github.com/gailu-labs/flavor-platform/issues
3. **Comunidad**: https://community.gailu.net
4. **Email**: soporte@gailu.net

## Línea de Tiempo

| Fecha | Versión | Estado |
|-------|---------|--------|
| 2025-02-04 | 3.0.0 | Web Builder extraído como addon |
| 2025-02-01 | 2.0.0 | Última versión con Web Builder en el core |

## Beneficios a Largo Plazo

Esta migración es parte de una estrategia más amplia para hacer Flavor Platform más modular:

### Próximos Addons Planificados

1. ✅ **Web Builder Pro** (completado)
2. 🔄 **Network Communities** (en progreso)
3. 🔄 **Advertising Pro** (en progreso)
4. 📋 **Mobile Apps Integration** (planificado)
5. 📋 **Admin Assistant AI** (planificado)
6. 📋 **Notifications Pro** (planificado)

## Feedback

Tu opinión es importante. Si tienes sugerencias sobre esta migración, por favor:

1. Abre un issue en [GitHub](https://github.com/gailu-labs/flavor-platform/issues)
2. Comparte tu experiencia en [la comunidad](https://community.gailu.net)
3. Contáctanos en soporte@gailu.net

---

**Gracias por usar Flavor Platform** ❤️

*Versión del documento: 1.0.0 - Última actualización: 2025-02-04*
