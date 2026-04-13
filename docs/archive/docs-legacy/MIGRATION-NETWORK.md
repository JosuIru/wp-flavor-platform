# Migración del Network Communities a Addon (v3.0)

## ⚠️ Cambio Importante en v3.0

A partir de Flavor Platform 3.0, el **Sistema de Red de Comunidades** ha sido extraído como un addon independiente para mejorar el rendimiento y la modularidad del plugin.

## ¿Qué significa esto para ti?

### Si NO usas el sistema de Red

✅ **Nada que hacer**. Tu sitio funcionará más rápido al no cargar código innecesario (~254 KB menos).

### Si SÍ usas el Network Communities

⚠️ **Acción requerida**: Debes instalar el addon Network Communities para mantener la funcionalidad de red.

## Guía de Migración Paso a Paso

### Paso 1: Verificar si usas Network

¿Tu sitio está conectado a una red de comunidades? Verifica:

- **Panel admin**: ¿Tienes el menú "Red de Comunidades"?
- **Base de datos**: ¿Tienes tablas `wp_flavor_network_*`?
- **Shortcodes**: ¿Usas `[flavor_network_*]` en páginas?
- **Nodos**: ¿Tu sitio se conecta con otros sitios Flavor?

Si respondiste **SÍ** a alguna, necesitas el addon.

### Paso 2: Instalar el Addon

**Opción A: Instalación Manual**

1. Descarga `flavor-network-communities.zip`
2. Ve a **Plugins > Añadir nuevo**
3. Haz clic en **Subir plugin**
4. Selecciona el archivo ZIP
5. Haz clic en **Instalar ahora**
6. Haz clic en **Activar plugin**

**Opción B: Desde Panel de Addons (si está disponible)**

1. Ve a **Flavor Platform > Addons**
2. Busca "Network Communities"
3. Haz clic en **Instalar**
4. Haz clic en **Activar**

### Paso 3: Verificar Funcionamiento

1. Ve a **Flavor Platform > Addons**
2. Verifica que "Network Communities" aparece como **Activo**
3. Ve a **Red de Comunidades** en el menú lateral
4. Verifica que tus conexiones de red siguen activas
5. Prueba los shortcodes en tus páginas

## ¿Qué se mantiene igual?

✅ **Todas tus conexiones de red** - Se preservan intactas
✅ **Configuración de nodos** - Se mantiene sin cambios
✅ **Datos de red** - Eventos, catálogo, tablón, etc.
✅ **Tablas de base de datos** - No se modifican
✅ **Shortcodes** - Siguen funcionando igual
✅ **API REST** - Endpoints mantienen las mismas rutas

## ¿Qué cambia?

### Antes (v2.x)
```
Flavor Platform
  └── Network Communities (incluido en el core)
```

### Ahora (v3.0+)
```
Flavor Platform (core ligero)
  └── Network Communities (addon independiente)
```

## Ventajas de la Nueva Arquitectura

### 1. **Mejor Rendimiento**
- El core es ~254 KB más ligero
- Solo se carga si lo necesitas
- Menos consumo de memoria y CPU

### 2. **Actualizaciones Independientes**
- Actualiza Network sin actualizar el core
- Ciclos de desarrollo más rápidos
- Menos riesgo de conflictos

### 3. **Desactivación Flexible**
- Desactiva la red si no la usas temporalmente
- Reactiva cuando necesites
- Sin pérdida de datos

## Datos que se Preservan

El addon mantiene acceso a todas estas tablas:

| Tabla | Contenido |
|-------|-----------|
| `wp_flavor_network_nodes` | Nodos de la red |
| `wp_flavor_network_connections` | Conexiones entre nodos |
| `wp_flavor_network_content` | Contenido compartido |
| `wp_flavor_network_events` | Eventos de la red |
| `wp_flavor_network_catalog` | Catálogo global |
| `wp_flavor_network_board` | Tablón de avisos |
| `wp_flavor_network_logs` | Logs de sincronización |

**Nota**: Las tablas NO se eliminan al desactivar el addon. Solo se eliminan si desinstalas completamente el plugin.

## Preguntas Frecuentes

### ¿Perderé mis conexiones de red al actualizar?

**No.** Todas tus conexiones se preservan. Solo necesitas instalar el addon para seguir gestionándolas.

### ¿El addon es gratuito?

**Sí.** Network Communities sigue siendo gratuito y open source (GPL v2).

### ¿Mis nodos conectados deben actualizar también?

**Sí, pero no al mismo tiempo.** Puedes actualizar de forma gradual:

1. El nodo con el addon puede comunicarse con nodos v2.x
2. Actualiza primero el nodo raíz
3. Luego actualiza los nodos secundarios
4. La red sigue funcionando durante la migración

### ¿Qué pasa si no instalo el addon?

- Las conexiones de red **se mantienen en la BD**
- NO podrás **gestionar la red** desde el admin
- Los shortcodes **no funcionarán**
- La API REST **no estará disponible**
- El resto de Flavor Platform funciona normalmente

### ¿Puedo volver a la versión anterior?

Sí, pero no es recomendado:

1. Desactiva el addon Network Communities
2. Degrada a Flavor Platform 2.x
3. El sistema de red funcionará como antes
4. Reporta el problema si hubo algún error

### ¿La API REST cambia de URL?

No, los endpoints mantienen las mismas rutas:

```
/wp-json/flavor-network/v1/nodes
/wp-json/flavor-network/v1/events
/wp-json/flavor-network/v1/catalog
...etc
```

### ¿Los módulos de red siguen disponibles?

**Sí**, todos los módulos se mantienen:

- ✅ Perfil Público
- ✅ Directorio
- ✅ Mapa
- ✅ Tablón
- ✅ Eventos
- ✅ Alertas
- ✅ Catálogo
- ✅ Colaboraciones
- ✅ Ofertas de Tiempo
- ✅ Preguntas

### ¿Afecta a mis nodos ya conectados?

No directamente. Cada nodo debe actualizar de forma independiente. La red sigue funcionando durante la migración.

## Problemas Conocidos y Soluciones

### Problema: "No aparece el menú Red de Comunidades"

**Solución**: Asegúrate de que el addon Network Communities está activado en **Flavor Platform > Addons**.

### Problema: "Mis conexiones no aparecen"

**Solución**:
1. Verifica que las tablas `wp_flavor_network_*` existen en la BD
2. Desactiva y reactiva el addon
3. Verifica los logs en modo debug

### Problema: "Los shortcodes no funcionan"

**Solución**:
1. Asegúrate de que el addon está activado
2. Limpia la caché del sitio
3. Recarga la página

### Problema: "Error de conexión entre nodos"

**Solución**:
1. Verifica que AMBOS nodos tienen Flavor Platform 3.0+
2. Asegúrate de que AMBOS tienen el addon Network instalado
3. Revisa los logs de conexión en ambos nodos
4. Verifica las claves de API

### Problema: "Error: Flavor Platform no encontrado"

**Solución**: Asegúrate de tener Flavor Platform 3.0 o superior instalado y activado.

## Migración de Redes Grandes

Si tienes una red con **más de 10 nodos**, sigue este plan:

### Fase 1: Preparación (Día 0)
- Anuncia la actualización a todos los nodos
- Envía la documentación de migración
- Define una ventana de mantenimiento

### Fase 2: Nodo Raíz (Día 1)
- Actualiza el nodo raíz primero
- Instala el addon Network Communities
- Verifica que las conexiones funcionan
- Comprueba la API REST

### Fase 3: Nodos Secundarios (Día 2-7)
- Actualiza 2-3 nodos por día
- Verifica cada conexión después de actualizar
- Monitoriza los logs de sincronización

### Fase 4: Verificación (Día 8)
- Verifica que todos los nodos están actualizados
- Comprueba la sincronización global
- Prueba todos los módulos de red

## Soporte durante la Migración

Si tienes problemas durante la migración:

1. **Documentación**: https://gailu.net/docs/network-migration
2. **GitHub Issues**: https://github.com/gailu-labs/flavor-platform/issues
3. **Comunidad**: https://community.gailu.net
4. **Email**: soporte@gailu.net

## Checklist de Migración

Usa esta checklist para verificar que todo funciona:

### Antes de Actualizar
- [ ] Hacer backup completo de la base de datos
- [ ] Exportar configuración de red
- [ ] Documentar nodos conectados
- [ ] Avisar a administradores de otros nodos

### Durante la Actualización
- [ ] Actualizar Flavor Platform a 3.0
- [ ] Instalar addon Network Communities
- [ ] Activar el addon
- [ ] Verificar que aparece en Addons

### Después de Actualizar
- [ ] Verificar menú "Red de Comunidades"
- [ ] Comprobar conexiones de nodos
- [ ] Probar shortcodes en páginas
- [ ] Verificar API REST endpoints
- [ ] Comprobar sincronización de contenido
- [ ] Probar cada módulo activado

## Línea de Tiempo

| Fecha | Versión | Estado |
|-------|---------|--------|
| 2025-02-04 | 3.0.0 | Network Communities extraído como addon |
| 2025-02-01 | 2.0.0 | Última versión con Network en el core |

## Beneficios a Largo Plazo

Esta migración permite:

✅ **Desarrollo más rápido** de nuevas funcionalidades
✅ **Mejor testing** de características de red
✅ **Actualizaciones independientes** sin afectar el core
✅ **Menor superficie de ataque** en seguridad
✅ **Código más mantenible** y organizado

## Feedback

Tu opinión es importante. Comparte tu experiencia:

1. Abre un issue en [GitHub](https://github.com/gailu-labs/flavor-platform/issues)
2. Participa en [la comunidad](https://community.gailu.net)
3. Escríbenos a soporte@gailu.net

---

**Gracias por usar Flavor Platform** ❤️

*Versión del documento: 1.0.0 - Última actualización: 2025-02-04*
