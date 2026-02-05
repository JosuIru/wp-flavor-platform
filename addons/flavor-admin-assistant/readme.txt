=== Flavor Admin Assistant ===
Contributors: gailulabs
Tags: admin, assistant, ai, shortcuts, productivity
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Asistente con IA para el panel de administración de WordPress con atajos de teclado y herramientas inteligentes.

== Description ==

**Flavor Admin Assistant** convierte tu panel de WordPress en un entorno de trabajo inteligente con asistencia por IA, atajos de teclado y herramientas de productividad.

= Características Principales =

**Asistente IA Integrado**
* Chat con IA directamente en el admin
* Consultas en lenguaje natural
* Sugerencias contextuales
* Ayuda en tiempo real
* Soporte multiidioma

**Atajos de Teclado**
* 40+ atajos predefinidos
* Navegación rápida entre páginas
* Acciones comunes con teclas
* Comandos personalizables
* Barra de comandos (Cmd/Ctrl + K)

**Herramientas Inteligentes**
* Análisis de rendimiento del sitio
* Sugerencias de optimización
* Detección de problemas
* Reportes automáticos
* Estadísticas en tiempo real

**Control de Acceso por Roles**
* Permisos granulares
* Restricción por capacidades
* Configuración por rol
* Auditoría de acciones
* Logs de actividad

**Backup y Recuperación**
* Backup automático de configuración
* Restauración con un clic
* Exportación de settings
* Importación de configuraciones
* Versionado de cambios

**Analytics Cache**
* Caché inteligente de estadísticas
* Reportes pre-generados
* Carga instantánea de métricas
* Actualización en background
* Ahorro de recursos

= Atajos de Teclado Disponibles =

**Navegación**
* `Ctrl/Cmd + K` - Abrir barra de comandos
* `Ctrl/Cmd + ,` - Ir a ajustes
* `Ctrl/Cmd + Shift + P` - Ir a plugins
* `Ctrl/Cmd + Shift + T` - Ir a temas
* `Ctrl/Cmd + Shift + U` - Ir a usuarios

**Contenido**
* `Ctrl/Cmd + N` - Nueva entrada
* `Ctrl/Cmd + Shift + N` - Nueva página
* `Ctrl/Cmd + S` - Guardar
* `Ctrl/Cmd + P` - Publicar
* `Ctrl/Cmd + Shift + S` - Guardar borrador

**Asistente IA**
* `Ctrl/Cmd + J` - Abrir asistente IA
* `Ctrl/Cmd + /` - Ayuda contextual
* `Esc` - Cerrar asistente

**Personalización**
* Todos los atajos son configurables
* Crear atajos propios
* Deshabilitar atajos individuales
* Importar/exportar configuración

= Herramientas de IA =

El asistente puede ayudarte con:

**Gestión de Contenido**
* "Mostrar últimas entradas"
* "Buscar páginas sobre [tema]"
* "Listar comentarios pendientes"
* "Ver usuarios registrados hoy"

**Optimización**
* "Analizar rendimiento del sitio"
* "Detectar plugins inactivos"
* "Sugerir mejoras de SEO"
* "Revisar configuración de caché"

**Estadísticas**
* "¿Cuántas visitas tuve hoy?"
* "Mostrar entradas más populares"
* "Estadísticas de usuarios"
* "Reporte mensual"

**Tareas Comunes**
* "Crear backup de configuración"
* "Limpiar comentarios spam"
* "Actualizar todos los plugins"
* "Verificar actualizaciones"

= Requisitos =

* Flavor Platform 3.0 o superior
* WordPress 5.8+
* PHP 7.4+
* Navegador moderno (Chrome, Firefox, Safari, Edge)

= Integración con IA =

Para usar las funcionalidades de IA, necesitas:
* Motor de IA configurado en Flavor Platform
* API key válida (Claude, OpenAI, DeepSeek, o Mistral)
* Módulo Chat Core activo

Sin motor de IA, el addon funciona con atajos de teclado y herramientas básicas.

== Installation ==

1. Asegúrate de tener **Flavor Platform 3.0** instalado y activado
2. Sube la carpeta `flavor-admin-assistant` a `/wp-content/plugins/`
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. El addon se activará automáticamente en Flavor Platform > Addons
5. Ve a "Asistente Admin" en el menú lateral para configurar

== Configuración Inicial ==

### Paso 1: Activar el Asistente

1. Ve a **Asistente Admin > Configuración**
2. Activa el asistente IA
3. Configura los permisos por rol
4. Guarda cambios

### Paso 2: Configurar Atajos

1. Ve a **Asistente Admin > Atajos de Teclado**
2. Revisa los atajos predefinidos
3. Personaliza los que necesites
4. Crea atajos propios
5. Guarda configuración

### Paso 3: Probar el Asistente

1. Presiona `Ctrl/Cmd + K` en cualquier página del admin
2. Escribe un comando (ej: "nueva entrada")
3. El asistente ejecutará la acción
4. O presiona `Ctrl/Cmd + J` para abrir el chat IA

== Frequently Asked Questions ==

= ¿Necesito Flavor Platform para usar este addon? =

Sí, Admin Assistant es un addon que requiere Flavor Platform 3.0 o superior.

= ¿Necesito un motor de IA para usar los atajos? =

No, los atajos de teclado y herramientas básicas funcionan sin IA. El chat con IA es opcional.

= ¿Puedo usar esto en modo multisite? =

Sí, funciona perfectamente en WordPress multisite. Los atajos y configuración pueden ser globales o por sitio.

= ¿Los atajos funcionan en todos los navegadores? =

Sí, los atajos son compatibles con Chrome, Firefox, Safari y Edge.

= ¿Puedo deshabilitar atajos individuales? =

Sí, tienes control total sobre qué atajos activar o desactivar.

= ¿Afecta el rendimiento del admin? =

No, el impacto es mínimo. Los scripts se cargan de forma optimizada solo cuando se necesitan.

= ¿Puedo crear mis propios atajos? =

Sí, puedes crear atajos personalizados que ejecuten acciones específicas o comandos de IA.

= ¿Los datos se comparten con terceros? =

No, todo funciona localmente. Solo se comunica con tu motor de IA configurado (Claude, OpenAI, etc.) si lo activas.

== Screenshots ==

1. Barra de comandos (Cmd + K)
2. Chat con asistente IA
3. Panel de configuración de atajos
4. Control de acceso por roles
5. Analytics cache y estadísticas
6. Herramientas de backup
7. Lista de comandos disponibles

== Changelog ==

= 1.0.0 - 2025-02-04 =
* Lanzamiento inicial del addon
* Extraído del core de Flavor Platform
* 40+ atajos de teclado predefinidos
* Chat con asistente IA integrado
* Herramientas inteligentes
* Control de acceso por roles
* Sistema de backup y recuperación
* Analytics cache
* Barra de comandos con búsqueda
* Configuración personalizable

== Upgrade Notice ==

= 1.0.0 =
Primera versión del addon independiente. Si actualizas desde Flavor Platform 2.x, Admin Assistant ahora es un addon separado que debes activar.

== Comandos Disponibles ==

El asistente reconoce comandos en lenguaje natural:

**Navegación**
* "ir a plugins"
* "abrir ajustes"
* "ver temas"
* "gestionar usuarios"

**Contenido**
* "nueva entrada"
* "nueva página"
* "ver comentarios"
* "editar entrada [ID]"

**Búsqueda**
* "buscar [término]"
* "encontrar página [nombre]"
* "listar entradas de [categoría]"

**Estadísticas**
* "estadísticas de hoy"
* "visitas de esta semana"
* "entradas más vistas"

**Optimización**
* "analizar rendimiento"
* "detectar problemas"
* "sugerir mejoras"

**Sistema**
* "backup de configuración"
* "limpiar caché"
* "verificar actualizaciones"

Todos los comandos son personalizables y puedes agregar los tuyos.

== Roles y Permisos ==

Control granular de acceso:

**Administrador**
* Acceso completo a todas las funcionalidades
* Configurar atajos globales
* Gestionar permisos de otros roles
* Ver logs de actividad

**Editor**
* Atajos de contenido
* Herramientas de edición
* Estadísticas básicas

**Autor**
* Atajos de escritura
* Gestión de entradas propias
* Estadísticas personales

**Colaborador**
* Atajos básicos
* Escritura de borradores

**Suscriptor**
* Solo lectura
* Sin acceso a herramientas avanzadas

Cada rol puede personalizarse según tus necesidades.

== Soporte ==

Para soporte y documentación:

* Documentación: https://gailu.net/docs/admin-assistant
* Issues: https://github.com/gailu-labs/flavor-platform/issues
* Comunidad: https://community.gailu.net

== Créditos ==

Desarrollado por Gailu Labs - https://gailu.net

Inspirado en:
* GitHub Copilot
* VS Code Command Palette
* Raycast
* Superhuman
