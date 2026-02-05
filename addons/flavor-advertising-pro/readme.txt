=== Flavor Advertising Pro ===
Contributors: gailulabs
Tags: advertising, ads, banner, monetization, ethical advertising
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo de publicidad ética para monetizar tu sitio de forma transparente y respetuosa con GDPR.

== Description ==

**Flavor Advertising Pro** es un sistema completo de publicidad ética que te permite gestionar anuncios, anunciantes, campañas y pagos de forma profesional, respetando la privacidad de tus usuarios.

= Características Principales =

**Gestión Completa de Anuncios**
* Crea y gestiona anuncios de forma visual
* 4 formatos de banner (Horizontal, Sidebar, Card, Nativo)
* Editor visual de anuncios
* Programación temporal de campañas
* Rotación automática de anuncios
* A/B testing integrado

**Tracking Ético**
* IntersectionObserver API (sin cookies invasivas)
* Métricas detalladas (impresiones, clics, CTR)
* Estadísticas en tiempo real
* Exportación de datos
* GDPR compliant por defecto

**Gestión de Anunciantes**
* Panel de anunciantes
* Estadísticas por anunciante
* Historial de pagos
* Facturas automáticas
* Reportes mensuales

**Pagos Multi-método**
* PayPal
* Stripe
* Transferencia bancaria
* Criptomonedas
* Personalizable

**Red Global de Anuncios** (requiere Network Communities addon)
* Comparte anuncios entre múltiples sitios
* Pool global de anunciantes
* Distribución automática de beneficios
* Estadísticas de red

**Categorías Éticas**
* Lista de categorías prohibidas (armas, drogas, adultos, etc.)
* Revisión manual de anuncios
* Aprobación automática configurable
* Moderación de contenido

= Formatos de Anuncios =

**Banner Horizontal**
* 970x250 (Leaderboard)
* 728x90 (Horizontal)
* 468x60 (Standard)

**Banner Sidebar**
* 300x600 (Half page)
* 300x250 (Medium rectangle)
* 160x600 (Wide skyscraper)

**Card**
* 250x250 (Square)
* 300x300 (Large square)

**Nativo**
* Integrado en el contenido
* Adaptado al diseño del sitio
* Etiquetado como "Publicidad"

= Requisitos =

* Flavor Platform 3.0 o superior
* WordPress 5.8+
* PHP 7.4+

= Integración con IA =

Si tienes módulos de IA activos, el sistema puede:
* Sugerir ubicaciones óptimas para anuncios
* Analizar rendimiento de campañas
* Generar reportes automáticos
* Optimizar rotación de anuncios

= GDPR Compliant =

**Advertising Pro** respeta la privacidad:
* ✅ No usa cookies de terceros
* ✅ No comparte datos con terceros
* ✅ Tracking con IntersectionObserver (no invasivo)
* ✅ Opción de desactivar tracking
* ✅ Exportación de datos del usuario
* ✅ Derecho al olvido integrado

== Installation ==

1. Asegúrate de tener **Flavor Platform 3.0** instalado y activado
2. Sube la carpeta `flavor-advertising-pro` a `/wp-content/plugins/`
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. El addon se activará automáticamente en Flavor Platform > Addons
5. Ve a "Publicidad" en el menú lateral para configurar

== Configuración Inicial ==

### Paso 1: Configuración General

1. Ve a **Publicidad > Configuración**
2. Define tus métodos de pago aceptados
3. Configura las categorías permitidas/prohibidas
4. Establece precios base (CPM, CPC)
5. Guarda cambios

### Paso 2: Crear tu Primer Anunciante

1. Ve a **Publicidad > Anunciantes**
2. Haz clic en "Añadir Anunciante"
3. Rellena: nombre, contacto, método de pago
4. Asigna un presupuesto mensual
5. Guarda

### Paso 3: Crear tu Primer Anuncio

1. Ve a **Publicidad > Anuncios**
2. Haz clic en "Añadir Anuncio"
3. Selecciona el anunciante
4. Sube la imagen del banner
5. Define URL de destino
6. Programa fechas de inicio/fin
7. Publica

### Paso 4: Mostrar Anuncios en tu Sitio

Usa los shortcodes disponibles:

```
[flavor_ad format="horizontal"]
[flavor_ad format="sidebar"]
[flavor_ad format="card"]
[flavor_ad format="native"]
```

O usa widgets en el Personalizador de WordPress.

== Frequently Asked Questions ==

= ¿Necesito Flavor Platform para usar este addon? =

Sí, Advertising Pro es un addon que requiere Flavor Platform 3.0 o superior.

= ¿Es compatible con GDPR? =

Sí, 100%. No usamos cookies de terceros ni compartimos datos. El tracking es mediante IntersectionObserver API, que es respetuoso con la privacidad.

= ¿Puedo cobrar a los anunciantes? =

Sí, el sistema incluye gestión de pagos con múltiples métodos (PayPal, Stripe, transferencia, crypto).

= ¿Cuántos anuncios puedo crear? =

Ilimitados. No hay restricciones en el número de anuncios, anunciantes o campañas.

= ¿Funciona con la red de comunidades? =

Sí, si tienes el addon Network Communities, puedes compartir anuncios entre múltiples sitios y crear una red global.

= ¿Puedo prohibir ciertas categorías de anuncios? =

Sí, tienes control total sobre qué categorías permitir o prohibir (ej: no armas, no adultos, no alcohol).

= ¿Los anuncios afectan el SEO? =

No, los anuncios están implementados de forma que no afectan negativamente al SEO. Usan lazy loading y están correctamente etiquetados.

= ¿Hay límite de impresiones/clics? =

No, el sistema de tracking no tiene límites. Todas las métricas se almacenan en tu propia base de datos.

== Screenshots ==

1. Panel de control de publicidad
2. Gestión de anuncios
3. Estadísticas y métricas
4. Gestión de anunciantes
5. Configuración de pagos
6. Red global de anuncios
7. Ejemplos de formatos de banner

== Changelog ==

= 1.0.0 - 2025-02-04 =
* Lanzamiento inicial del addon
* Extraído del core de Flavor Platform
* 4 formatos de banner
* Sistema de tracking ético
* Gestión de anunciantes
* Múltiples métodos de pago
* Red global de anuncios (con Network Communities)
* Estadísticas detalladas
* GDPR compliant
* Panel de administración completo

== Upgrade Notice ==

= 1.0.0 =
Primera versión del addon independiente. Si actualizas desde Flavor Platform 2.x, Advertising Pro ahora es un addon separado que debes activar.

== Shortcodes ==

**Mostrar anuncio**
```
[flavor_ad format="horizontal" category="tecnologia"]
[flavor_ad format="sidebar"]
[flavor_ad format="card" advertiser="123"]
[flavor_ad format="native"]
```

Parámetros:
* `format` - Formato del anuncio (horizontal, sidebar, card, native)
* `category` - Filtrar por categoría (opcional)
* `advertiser` - Mostrar solo de un anunciante específico (opcional)
* `random` - Mostrar anuncio aleatorio (default: true)

**Estadísticas públicas**
```
[flavor_ad_stats type="summary"]
```

== API REST ==

Endpoints disponibles en `/wp-json/flavor-advertising/v1/`:

* `/ads` - Lista de anuncios
* `/stats` - Estadísticas globales
* `/track/impression` - Registrar impresión
* `/track/click` - Registrar clic

Documentación completa: https://gailu.net/docs/advertising-api

== Métodos de Pago Soportados ==

* **PayPal** - Pagos automáticos via API
* **Stripe** - Tarjetas de crédito/débito
* **Transferencia** - Manual con confirmación
* **Criptomonedas** - Bitcoin, Ethereum (con gateway)
* **Personalizado** - Agrega tu propio método

== Categorías Prohibidas por Defecto ==

Por ética y para proteger a tu audiencia:
* ❌ Armas y munición
* ❌ Drogas y sustancias ilegales
* ❌ Contenido adulto
* ❌ Apuestas y juegos de azar (en algunos países)
* ❌ Esquemas piramidales
* ❌ Productos falsificados
* ❌ Servicios ilegales

Puedes personalizar esta lista según tus necesidades.

== Soporte ==

Para soporte y documentación:

* Documentación: https://gailu.net/docs/advertising-pro
* Issues: https://github.com/gailu-labs/flavor-platform/issues
* Comunidad: https://community.gailu.net

== Créditos ==

Desarrollado por Gailu Labs - https://gailu.net

Librerías utilizadas:
* Chart.js para estadísticas
* IntersectionObserver API para tracking ético
