# 📢 Sistema de Publicidad Ética - Documentación Completa

Sistema integral de gestión de anuncios éticos con distribución de beneficios para comunidades y proyectos sociales.

---

## 🎯 Características Principales

### 1. **Anuncios Éticos**
- ✅ Categorías permitidas/prohibidas configurables
- ✅ Anunciantes verificados
- ✅ Transparencia total (siempre etiquetados como "Anuncio")
- ✅ No tracking invasivo (GDPR compliant)
- ✅ Anunciantes locales y globales

### 2. **Red Global de Anuncios**
- ✅ Compartir anuncios entre múltiples sitios WordPress
- ✅ Alcance local, red o global configurable por anuncio
- ✅ API centralizada para gestión de red
- ✅ Estadísticas agregadas de toda la red

### 3. **Sistema de Pagos y Distribución**
- ✅ Múltiples métodos de pago (PayPal, Stripe, Transferencia, Crypto)
- ✅ Distribución automática de beneficios
- ✅ Reparto configurable: sitio/plataforma/comunidad
- ✅ Tracking completo de transacciones
- ✅ Umbral mínimo para solicitar pagos

### 4. **Tracking y Estadísticas**
- ✅ Impresiones (con IntersectionObserver - no trackea si no es visible)
- ✅ Clicks
- ✅ CTR (Click-Through Rate)
- ✅ Ingresos por anuncio/sitio/anunciante
- ✅ Dashboard con gráficas (Chart.js)

---

## 📁 Estructura del Sistema

```
includes/
├── advertising/
│   ├── class-advertising-system.php     # Sistema principal
│   └── views/
│       ├── dashboard.php               # Dashboard de estadísticas
│       ├── network.php                 # Gestión red global
│       ├── payments.php                # Sistema de pagos
│       └── settings.php                # Configuración
├── modules/
│   └── advertising/
│       └── class-advertising-module.php # Módulo para Page Builder
templates/
└── components/
    └── advertising/
        ├── banner-horizontal.php        # Banner 728x90
        ├── banner-sidebar.php           # Banner 300x250
        ├── banner-card.php              # Anuncio tipo tarjeta
        └── banner-nativo.php            # Anuncio nativo integrado
assets/
└── js/
    └── ad-tracking.js                   # JavaScript de tracking
```

---

## 🚀 Uso Básico

### Paso 1: Acceder al Sistema

**WordPress Admin** → **Publicidad**

Submenús disponibles:
- **Dashboard**: Estadísticas generales
- **Anuncios**: Gestión de anuncios
- **Anunciantes**: Gestión de anunciantes
- **Campañas**: Gestión de campañas publicitarias
- **Red Global**: Conexión a la red de sitios
- **Pagos**: Gestión de ingresos y pagos
- **Configuración**: Settings del sistema

### Paso 2: Crear un Anunciante

1. Ir a **Publicidad** → **Anunciantes**
2. Click en "Añadir Anunciante"
3. Rellenar datos:
   - Nombre de la empresa
   - Logo (imagen destacada)
   - Descripción
   - Información de contacto
4. Publicar

### Paso 3: Crear un Anuncio

1. Ir a **Publicidad** → **Anuncios**
2. Click en "Añadir Anuncio"
3. **Detalles del Anuncio:**
   - **Título**: Nombre del anuncio
   - **Contenido**: Descripción del anuncio
   - **Imagen destacada**: Visual principal
   - **Tipo**: Banner, Card, Nativo, Video
   - **Tamaño**: Leaderboard, Rectangle, Skyscraper, Custom
   - **URL de Destino**: Dónde lleva el anuncio
   - **Texto del Botón**: CTA (ej: "Más información")
   - **Anunciante**: Seleccionar de la lista

4. **Segmentación y Alcance:**
   - **Alcance**:
     - **Solo este sitio**: El anuncio solo se muestra aquí
     - **Red de sitios**: Seleccionar sitios específicos de la red
     - **Global**: Se muestra en todos los sitios conectados
   - **Ubicaciones**: Dónde se puede mostrar (header, sidebar, content, footer)
   - **Fecha inicio/fin**: Duración de la campaña
   - **Presupuesto**: Límite de gasto
   - **Reparto de ingresos**: % que reciben los sitios que muestran el anuncio

5. Publicar

### Paso 4: Añadir Anuncio a una Página

#### Opción A: Con Page Builder

1. Editar una Landing Page
2. Abrir el Page Builder
3. Buscar componentes de "Publicidad":
   - Banner Horizontal
   - Banner Sidebar
   - Anuncio Tipo Tarjeta
   - Anuncio Nativo
4. Arrastrar al canvas
5. Seleccionar el anuncio de la lista
6. Configurar opciones
7. Guardar

#### Opción B: Con Shortcode

```php
// En cualquier página, post o widget
[flavor_ad id="123" type="horizontal"]
```

#### Opción C: Con PHP (en template)

```php
<?php
if (function_exists('flavor_display_ad')) {
    flavor_display_ad(123, 'horizontal');
}
?>
```

---

## 🌐 Red Global de Anuncios

### ¿Qué es la Red Global?

La Red Global permite que múltiples sitios WordPress compartan anuncios y distribuyan beneficios automáticamente.

**Beneficios:**
- 📈 Mayor alcance para anunciantes
- 💰 Más ingresos para sitios participantes
- 🤝 Colaboración entre comunidades
- 📊 Estadísticas centralizadas

### Cómo Unirse a la Red

1. Ir a **Publicidad** → **Red Global**
2. Click en "Unirse a la Red Global"
3. Rellenar formulario:
   - **API Key**: Solicitar en https://network.flavorapps.com
   - **Nombre del Sitio**: Identificador único
   - **URL**: URL pública del sitio
4. Click en "Conectar"

### Una vez Conectado

**Configuración disponible:**
- ☑️ **Permitir anuncios globales en este sitio**: Mostrar anuncios de otros sitios
- ☑️ **Compartir mis anuncios con la red**: Permitir que otros sitios muestren tus anuncios
- 🔢 **Porcentaje de comisión**: % que se queda la red (10-50%)

**Ver estadísticas:**
- Total de sitios en la red
- Anuncios compartidos
- Ingresos generados por la red
- Listado de sitios participantes

---

## 💰 Sistema de Pagos

### Distribución de Beneficios

Por defecto, los ingresos se reparten así:

```
100% Ingresos del Anuncio
├─ 70% → Sitio que muestra el anuncio
├─ 25% → Plataforma/Red
└─  5% → Proyectos comunitarios (opcional)
```

**Configurable en:** Publicidad → Configuración → Distribución de Ingresos

### Solicitar un Pago

1. Ir a **Publicidad** → **Pagos**
2. Verificar balance pendiente (debe superar el umbral mínimo)
3. Click en "Solicitar Pago"
4. Rellenar formulario:
   - **Monto**: Cantidad a retirar
   - **Método de pago**: Seleccionar
   - **Notas adicionales**: Opcional
5. Enviar solicitud

**Estados de pago:**
- **Pending**: Solicitado, en revisión
- **Processing**: En proceso de pago
- **Paid**: Pagado
- **Cancelled**: Cancelado

### Métodos de Pago

#### 1. PayPal
- Email de cuenta PayPal
- Pago automático en 3-5 días hábiles

#### 2. Transferencia Bancaria
- IBAN
- Nombre del titular
- BIC/SWIFT
- Pago manual en 5-10 días hábiles

#### 3. Stripe
- ID de cuenta Stripe Connect
- Pago automático en 2-3 días hábiles

#### 4. Criptomonedas
- Dirección de wallet
- Red (Bitcoin, Ethereum, etc.)
- Pago automático en 1-24 horas

**Configurar en:** Publicidad → Pagos → Configuración de Pago

---

## ⚙️ Configuración Avanzada

### Monetización

**CPM (Coste Por Mil Impresiones)**
- Valor por defecto: €2.00
- El anunciante paga por cada 1000 visualizaciones

**CPC (Coste Por Click)**
- Valor por defecto: €0.50
- El anunciante paga solo cuando alguien hace click

**Umbral Mínimo de Pago**
- Default: €50.00
- Monto mínimo para poder solicitar un pago

### Ética y Transparencia

**Mostrar siempre etiqueta "Anuncio"**
- ☑️ Activado por defecto
- Cumplimiento regulaciones de publicidad

**Permitir solo anunciantes verificados**
- ☑️ Recomendado activar
- Los anunciantes deben pasar proceso de verificación

**Excluir categorías sensibles**
- ☑️ Recomendado activar
- Bloquea: alcohol, apuestas, tabaco, armas, contenido adulto

**Categorías prohibidas personalizadas**
```
alcohol
apuestas
tabaco
armas
contenido-adulto
politica-partidista
piramides
```

### Red Global (Avanzado)

**API Key de la Red**
- Obtener en https://network.flavorapps.com
- Identificador único para tu sitio

**URL del Servidor Central**
- Default: `https://network.flavorapps.com/api/v1`
- Solo cambiar si usas servidor propio

**Modo Sandbox (Testing)**
- ☑️ Activar para pruebas
- Los anuncios no generan ingresos reales
- Útil para desarrollo

### Privacidad

**Cumplir con GDPR**
- ☑️ Recomendado siempre activado
- Banner de cookies si es necesario
- Permite opt-out del tracking

**No trackear usuarios sin consentimiento**
- ☑️ Recomendado activado
- Solo trackea con consentimiento explícito
- Compatible con plugins de cookies

**Anonimizar IPs en tracking**
- ☑️ Recomendado activado
- Las IPs se almacenan anonimizadas (192.168.x.x)
- Mayor privacidad para usuarios

---

## 📊 Componentes Visuales

### 1. Banner Horizontal

**Uso típico:** Header, antes/después de contenido, footer

**Tamaños estándar:**
- Leaderboard: 728x90px
- Full Banner: 468x60px
- Responsive: Se adapta al contenedor

**Campos configurables:**
- Anuncio (seleccionar de lista)
- Posición (header/content_top/content_bottom/footer)
- Mostrar etiqueta "Anuncio" (sí/no)

**Código del componente:**
```php
// En Page Builder, seleccionar "Banner Horizontal"
// O con shortcode:
[flavor_ad id="123" type="horizontal" position="content_top" label="true"]
```

### 2. Banner Sidebar

**Uso típico:** Barra lateral, widgets

**Tamaños estándar:**
- Medium Rectangle: 300x250px
- Large Rectangle: 336x280px
- Responsive

**Campos configurables:**
- Anuncio
- Mostrar etiqueta
- Sticky (fijo al hacer scroll)

**Código:**
```php
[flavor_ad id="123" type="sidebar" sticky="true"]
```

### 3. Anuncio Tipo Tarjeta

**Uso típico:** Entre contenido, grids de artículos

**Estilos:**
- **Minimal**: Compacto, imagen circular, CTA inline
- **Card**: Tarjeta estándar con imagen, título, excerpt, botón
- **Featured**: Grande y destacado con imagen 16:9

**Campos configurables:**
- Anuncio
- Estilo (minimal/card/featured)

**Código:**
```php
[flavor_ad id="123" type="card" style="featured"]
```

### 4. Anuncio Nativo

**Uso típico:** Dentro de artículos de blog

**Características:**
- Se mimetiza con el contenido
- Imagen inline con texto flotante
- CTA sutil
- Badge discreto "Patrocinado"

**Campos configurables:**
- Anuncio
- Título personalizado (opcional)

**Código:**
```php
[flavor_ad id="123" type="native" custom_title="Descubre esta herramienta"]
```

---

## 🔧 Funciones PHP para Desarrolladores

### Mostrar un anuncio

```php
<?php
/**
 * Mostrar anuncio por ID
 *
 * @param int $ad_id ID del anuncio
 * @param string $type Tipo: horizontal, sidebar, card, native
 * @param array $args Argumentos adicionales
 */
flavor_display_ad(123, 'horizontal', [
    'position' => 'content_top',
    'show_label' => true,
]);
?>
```

### Obtener estadísticas de un anuncio

```php
<?php
$stats = flavor_get_ad_stats(123, [
    'period' => 'month', // today, week, month, year, all
    'site_id' => 'local', // local, o ID de sitio de red
]);

echo 'Impresiones: ' . $stats['impressions'];
echo 'Clicks: ' . $stats['clicks'];
echo 'CTR: ' . $stats['ctr'] . '%';
echo 'Ingresos: €' . $stats['revenue'];
?>
```

### Verificar si hay anuncios disponibles para una posición

```php
<?php
if (flavor_has_ads_for_position('header')) {
    flavor_display_random_ad('header');
}
?>
```

### Hook: Antes de mostrar un anuncio

```php
<?php
add_action('flavor_before_ad_display', function($ad_id, $type) {
    // Tu código aquí
    // Ejemplo: Log de anuncios mostrados
    error_log("Mostrando anuncio #{$ad_id} tipo {$type}");
}, 10, 2);
?>
```

### Hook: Después de registrar un click

```php
<?php
add_action('flavor_ad_click_tracked', function($ad_id, $user_ip) {
    // Tu código aquí
    // Ejemplo: Notificar al anunciante
    wp_mail(get_anunciante_email($ad_id), 'Nuevo click', 'Tu anuncio recibió un click');
}, 10, 2);
?>
```

---

## 📈 Dashboard y Estadísticas

### Métricas Principales

**Visualización en Dashboard:**

1. **Total Impresiones**: Número de veces que se han visto anuncios
2. **Total Clicks**: Número de clicks en anuncios
3. **CTR Promedio**: (Clicks / Impresiones) × 100
4. **Ingresos Totales**: Suma de todos los ingresos
5. **Ingresos Pendientes**: Balance pendiente de cobro
6. **Anuncios Activos/Pausados**: Estado de los anuncios

**Gráfica de Rendimiento:**
- Impresiones vs Clicks (últimos 30 días)
- Chart.js interactivo
- Comparativa visual

**Top 5 Anuncios:**
- Ranking por rendimiento
- Columnas: Anuncio, Impresiones, Clicks, CTR, Ingresos
- Ordenable por columna

---

## 🛡️ Seguridad y Privacidad

### Medidas Implementadas

1. **Nonces en todos los formularios**: Protección CSRF
2. **Escapado de outputs**: Prevención XSS
3. **Prepared statements**: Prevención SQL injection
4. **Capability checks**: Solo admins pueden gestionar
5. **Anonimización de IPs**: Cumplimiento GDPR
6. **Consentimiento de tracking**: Opt-in/Opt-out
7. **HTTPS para tracking API**: Conexiones seguras
8. **Rate limiting**: Prevención de abuse en tracking

### Cumplimiento GDPR

**Activar en:** Publicidad → Configuración → Privacidad

Cuando está activado:
- ✅ No se trackea sin consentimiento
- ✅ IPs anonimizadas
- ✅ Derecho al olvido (eliminar datos de usuario)
- ✅ Portabilidad de datos
- ✅ Banner de cookies informativo

---

## 🔍 Troubleshooting

### Los anuncios no se muestran

**Verificar:**
1. ¿El anuncio está publicado? (no borrador/privado)
2. ¿Está dentro del rango de fechas? (fecha inicio/fin)
3. ¿El presupuesto no se ha agotado?
4. ¿La ubicación es correcta? (header/sidebar/etc)
5. ¿El componente está añadido a la página?

**Debug:**
```php
// Añadir a functions.php temporalmente
add_filter('flavor_debug_ads', '__return_true');
```

### El tracking no funciona

**Verificar:**
1. ¿JavaScript está cargado? (ver consola del navegador)
2. ¿AJAX URL es correcto? (`admin-ajax.php`)
3. ¿Nonce es válido? (regenerar si es necesario)
4. ¿IntersectionObserver está disponible? (navegadores modernos)

**Browsers soportados:**
- Chrome/Edge 51+
- Firefox 55+
- Safari 12.1+
- iOS Safari 12.2+

### Los pagos no se procesan

**Verificar:**
1. ¿Balance supera el umbral mínimo?
2. ¿Método de pago configurado correctamente?
3. ¿Datos de pago completos? (email PayPal, IBAN, etc)
4. ¿Estado de la transacción? (ver en Pagos)

**Contactar soporte:**
- Email: support@flavorapps.com
- Con: ID de transacción, monto, método de pago

---

## 📞 Soporte y Recursos

### Documentación Adicional

- **API Reference**: https://docs.flavorapps.com/advertising-api
- **Video Tutorials**: https://youtube.com/flavorapps
- **FAQ**: https://help.flavorapps.com/advertising

### Comunidad

- **Foro**: https://community.flavorapps.com
- **Discord**: https://discord.gg/flavorapps
- **GitHub**: https://github.com/flavorapps/advertising-system

### Contacto

- **Email**: support@flavorapps.com
- **Chat**: Widget en el admin
- **Teléfono**: +34 XXX XXX XXX (horario 9-18h)

---

**Versión:** 1.0.0
**Fecha:** 2026-01-28
**Estado:** ✅ Sistema completo y funcional
