# Gaps y Mejoras Pendientes - Flavor Platform

## Estado Actual: Evaluación

| Área | Completitud | Estado |
|------|-------------|--------|
| Perfiles de App | 100% | COMPLETO |
| Sistema de Módulos | 95% | CASI COMPLETO |
| Page Creator | 100% | COMPLETO |
| Landing Generator | 90% | FUNCIONAL |
| Red de Nodos | 85% | FUNCIONAL |
| Dashboard Usuario | 100% | COMPLETO |
| Deep Links | 80% | FUNCIONAL |
| Diseño/Temas | 100% | COMPLETO |
| PWA | 40% | INCOMPLETO |
| Apps Flutter | 70% | PARCIAL |
| Documentación | 60% | MEJORABLE |

---

## Gaps Críticos (Prioridad ALTA)

### 1. PWA Incompleto

**Problema:** El Progressive Web App solo tiene manifest básico, falta:
- Service Worker completo con cache strategies
- Offline sync para datos
- Background sync
- Push notifications nativas

**Archivos afectados:**
- `/assets/gc-manifest.json` (básico)
- `/assets/gc-service-worker.js` (mínimo)

**Solución propuesta:**
```javascript
// Service worker con workbox
importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.5.4/workbox-sw.js');

workbox.precaching.precacheAndRoute(self.__WB_MANIFEST);

// Cache first para assets
workbox.routing.registerRoute(
  ({request}) => request.destination === 'image',
  new workbox.strategies.CacheFirst()
);

// Network first para API
workbox.routing.registerRoute(
  ({url}) => url.pathname.startsWith('/wp-json/flavor/'),
  new workbox.strategies.NetworkFirst({
    cacheName: 'api-cache',
    networkTimeoutSeconds: 3,
  })
);
```

**LOC estimadas:** ~300

---

### 2. Sincronización QR Apps Incompleta

**Problema:** El flujo de pairing QR existe pero incompleto:
- Falta endpoint de generación de token temporal
- Falta validación de token en app
- Falta persistencia de sesión post-pairing

**Archivos afectados:**
- `/includes/app-integration/class-deep-link-manager.php`
- `/includes/app-integration/class-app-integration.php`

**Solución propuesta:**
```php
// Endpoint para generar QR token
POST /wp-json/flavor/v1/app/generate-pair-token
Response: { "token": "abc123", "expires_at": "2024-...", "qr_data": "flavorapp://pair?token=abc123&site=https://..." }

// Endpoint para validar pairing
POST /wp-json/flavor/v1/app/validate-pair
Body: { "token": "abc123", "device_id": "xyz" }
Response: { "success": true, "auth_token": "permanent_token", "user_id": 123 }
```

**LOC estimadas:** ~400

---

### 3. Radio de Entrega para Productores

**Problema:** Los productores en la red de nodos no tienen campo de radio de entrega.

**Archivos afectados:**
- `/includes/modules/grupos-consumo/class-grupos-consumo-module.php`
- Metabox del CPT `gc_productor`

**Solución propuesta:**
```php
// Añadir meta field
register_post_meta('gc_productor', '_gc_radio_entrega_km', [
    'type' => 'number',
    'single' => true,
    'show_in_rest' => true,
]);

// En API de red de nodos, filtrar por distancia
$productores_cercanos = $wpdb->get_results($wpdb->prepare("
    SELECT p.*,
        (6371 * acos(cos(radians(%f)) * cos(radians(lat)) * cos(radians(lng) - radians(%f)) + sin(radians(%f)) * sin(radians(lat)))) AS distancia
    FROM {$wpdb->prefix}flavor_gc_productores p
    HAVING distancia <= radio_entrega_km
    ORDER BY distancia
", $lat_usuario, $lng_usuario, $lat_usuario));
```

**LOC estimadas:** ~150

---

## Gaps Medios (Prioridad MEDIA)

### 4. Visibilidad de Módulos (Público/Privado)

**Problema:** Los módulos no tienen flag para decidir si requieren login.

**Solución propuesta:**
```php
// En cada módulo
public function get_visibility() {
    return 'public'; // o 'private', 'members_only'
}

// En shortcodes
if ($module->get_visibility() === 'private' && !is_user_logged_in()) {
    return $this->render_login_required();
}
```

**LOC estimadas:** ~100

---

### 5. Wizard de Configuración Mejorado

**Problema:** El setup wizard existe pero es básico.

**Mejoras:**
- Paso de importación de demo data
- Paso de configuración de diseño visual
- Paso de configuración de notificaciones
- Preview en vivo de los cambios

**LOC estimadas:** ~500

---

### 6. Sistema de Permisos Granular

**Problema:** Los permisos son básicos (admin/user). Falta:
- Roles por módulo (coordinador_gc, productor_gc)
- Permisos por acción (puede_crear_evento, puede_aprobar_socios)

**Archivo existente:** `/includes/class-role-manager.php`

**LOC estimadas:** ~300

---

## Gaps Menores (Prioridad BAJA)

### 7. Más Temas Predefinidos

**Actual:** 26 temas
**Mejora:** Añadir temas por sector (salud, educación, cultura, deportes)

---

### 8. Importación/Exportación de Configuración

**Problema:** No se puede clonar configuración entre sitios fácilmente.

**Solución:** Exportar/importar JSON con:
- Perfil activo
- Módulos configurados
- Settings de diseño
- Páginas creadas

---

### 9. Integración con Más Pasarelas de Pago

**Actual:** WooCommerce (Stripe, PayPal)
**Mejora:**
- Bizum directo
- Redsys
- Transferencia SEPA

---

### 10. Sistema de Plugins/Addons de Terceros

**Actual:** Sistema de addons interno
**Mejora:** Marketplace de addons con validación

---

## Mejoras de UX

### 11. Onboarding Interactivo
- Tour guiado para nuevos admins
- Tooltips contextuales
- Videos tutoriales embebidos

### 12. Dashboard Admin Mejorado
- Widgets de métricas en tiempo real
- Gráficos de actividad
- Alertas y notificaciones

### 13. Editor Visual de Landings
- Drag & drop de secciones
- Preview en vivo
- Variantes A/B

---

## Mejoras Técnicas

### 14. Tests Automatizados
- PHPUnit para clases
- Jest para JavaScript
- Cypress para E2E

### 15. Documentación de API
- OpenAPI/Swagger spec
- Postman collection
- Ejemplos de integración

### 16. Internacionalización Completa
- Revisar todas las strings
- Generar .pot actualizado
- Traducciones a EN, EU, CA

---

## Roadmap Sugerido

### Fase 1: Críticos (1-2 semanas)
1. PWA completo
2. Pairing QR completo
3. Radio entrega productores

### Fase 2: Medios (2-4 semanas)
4. Visibilidad módulos
5. Wizard mejorado
6. Permisos granulares

### Fase 3: Polish (4-6 semanas)
7. Más temas
8. Import/Export
9. Onboarding interactivo

### Fase 4: Escala (6+ semanas)
10. Tests automatizados
11. Documentación API
12. Marketplace addons

---

## Estimación Total

| Categoría | LOC | Horas Estimadas |
|-----------|-----|-----------------|
| Gaps Críticos | ~850 | 20-30h |
| Gaps Medios | ~900 | 25-35h |
| Gaps Menores | ~500 | 15-20h |
| Mejoras UX | ~1000 | 30-40h |
| Mejoras Técnicas | ~2000 | 50-70h |
| **TOTAL** | **~5250** | **140-195h** |

---

## Archivos Clave para Modificar

1. `/includes/class-app-profiles.php` - Añadir visibilidad
2. `/includes/app-integration/class-app-integration.php` - Pairing QR
3. `/includes/modules/grupos-consumo/class-grupos-consumo-module.php` - Radio entrega
4. `/assets/pwa/` - Service worker completo
5. `/admin/class-setup-wizard.php` - Wizard mejorado
6. `/includes/class-role-manager.php` - Permisos granulares
