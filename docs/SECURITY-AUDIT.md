# Visual Builder Pro - Auditoría de Seguridad

**Fecha:** 2026-04-11
**Versión auditada:** 3.5.x
**Auditor:** Claude Code
**Estado:** COMPLETADA CON CORRECCIONES

---

## Correcciones Aplicadas

Las siguientes correcciones de seguridad se implementaron como parte de esta auditoría:

1. **Rate limiting en API Claude** (`class-vbp-claude-api.php`)
   - Nuevo método `check_request_rate_limit()` limita a 100 requests/minuto por API key
   - Previene abuso de recursos y ataques DoS

2. **Headers de seguridad HTTP** (`class-vbp-loader.php`)
   - Añadido `X-Content-Type-Options: nosniff`
   - Añadido `X-Frame-Options: SAMEORIGIN`
   - Cache-Control para endpoints sensibles

3. **Función flavor_log_warning()** (`flavor-platform.php`)
   - Añadida función de logging para warnings de seguridad

4. **Mejora en verificación de API Key** (`class-vbp-claude-api.php`)
   - Ahora usa `hash_equals()` para comparación timing-safe
   - Integración con función centralizada `flavor_verify_vbp_api_key()`

---

## Resumen Ejecutivo

Se ha realizado una auditoría de seguridad completa del Visual Builder Pro (VBP). El código presenta una arquitectura de seguridad sólida con buenas prácticas implementadas.

### Estado General: BUENO (Mejorado post-auditoría)

| Categoría | Estado | Puntuación |
|-----------|--------|------------|
| Autenticación | Excelente | 9/10 |
| Autorización | Bueno | 8/10 |
| Validación de entrada | Bueno | 7/10 |
| Protección XSS | Bueno | 7/10 |
| Protección CSRF | Bueno | 8/10 |
| SQL Injection | Excelente | 9/10 |
| Rate Limiting | Excelente | 9/10 |
| Logging de seguridad | Bueno | 8/10 |
| Encriptación | Excelente | 9/10 |
| Headers HTTP | Bueno | 8/10 |

---

## 1. Autenticación y Autorización

### 1.1 API Key Management

**Estado: BUENO**

#### Fortalezas encontradas:

1. **Generación segura de API Key** (`flavor-platform.php:816-834`)
   - Usa `wp_generate_password(32, false, false)` para generar keys de 32 caracteres
   - Keys únicas por instalación usando `wp_hash()` con `NONCE_SALT`

2. **Verificación con timing-safe comparison** (`flavor-platform.php:617`)
   ```php
   if ( hash_equals( $valid_key, $key ) ) {
   ```
   Previene timing attacks.

3. **Rate limiting implementado** (`flavor-platform.php:605-624`)
   - Máximo 5 intentos fallidos por IP en 5 minutos
   - Logging de intentos fallidos

4. **API Key solo en headers** (`flavor-platform.php:799-807`)
   - No acepta API key desde URL/parámetros (evita exposición en logs)

#### Vulnerabilidades identificadas:

1. **BAJA** - Key legacy aún soportada con flag
   - Archivo: `class-vbp-claude-api.php:758-761`
   - La key legacy `flavor-vbp-2024` puede habilitarse con `FLAVOR_VBP_ALLOW_LEGACY_KEY`
   - **Recomendación:** Eliminar soporte legacy en próxima versión mayor

2. **BAJA** - API key no expira automáticamente
   - **Recomendación:** Implementar expiración opcional de keys

### 1.2 Permisos de WordPress

**Estado: BUENO**

Verificación de capabilities correcta en:
- `class-vbp-rest-api.php` - `current_user_can('edit_posts')`, `current_user_can('read_post', $id)`
- `class-vbp-settings.php` - `current_user_can('manage_options')`
- `class-vbp-audit-log.php` - `current_user_can('manage_options')`

---

## 2. Validación de Entrada

### 2.1 SQL Injection

**Estado: EXCELENTE**

El código utiliza correctamente `$wpdb->prepare()` con placeholders:

```php
// class-vbp-form-handler.php:393-396
$sql = $wpdb->prepare(
    "SELECT * FROM {$this->table_name} WHERE " . implode( ' AND ', $where ) .
    " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
    array_merge( $values, array( $args['per_page'], $offset ) )
);
```

También se utilizan las funciones seguras de WordPress:
- `$wpdb->insert()` con format arrays
- `$wpdb->update()` con format arrays
- `$wpdb->delete()` con format arrays

### 2.2 XSS (Cross-Site Scripting)

**Estado: BUENO**

#### Sanitización de entrada encontrada:
- `sanitize_text_field()` - uso extensivo
- `sanitize_textarea_field()` - para campos de texto largo
- `sanitize_email()` - para emails
- `sanitize_key()` - para keys de arrays
- `absint()` - para enteros
- `sanitize_title()` - para slugs

#### Escape de salida encontrado:
- `esc_html()` - en `class-vbp-settings.php`
- `esc_attr()` - en `class-vbp-rest-api.php:722`
- `esc_url()` - para URLs

#### Vulnerabilidades potenciales:

1. **MEDIA** - Endpoints públicos sin autenticación
   - Archivo: `class-vbp-rest-api.php:145-156`
   - `/blocks/schema` y `/shortcodes` son públicos
   - **Impacto:** Exposición de información del sistema
   - **Recomendación:** Evaluar si realmente necesitan ser públicos

2. **BAJA** - innerHTML en JavaScript
   - Múltiples usos de `innerHTML` en archivos JS de VBP
   - La mayoría maneja contenido sanitizado desde el backend
   - **Recomendación:** Revisar uso de `x-html` en Alpine.js

### 2.3 CSRF (Cross-Site Request Forgery)

**Estado: BUENO**

#### Protección implementada:

1. **REST API** - Usa `X-WP-Nonce` en headers (`vbp-api.js:65-66`)
   ```javascript
   headers: {
       'Content-Type': 'application/json',
       'X-WP-Nonce': VBP_Config.restNonce
   }
   ```

2. **AJAX** - Verifica nonce en `class-vbp-form-handler.php:107-113`
   ```php
   if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce(
       sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
       'vbp_form_submit'
   ) ) {
   ```

3. **Settings** - Usa `check_ajax_referer()` en `class-vbp-settings.php:356`

---

## 3. Seguridad en REST API

### 3.1 Endpoints con `__return_true`

**Estado: REQUIERE ATENCIÓN**

Se encontraron 11 endpoints públicos:

| Archivo | Endpoint | Justificación |
|---------|----------|---------------|
| `class-vbp-rest-api.php:145` | `/blocks/schema` | Facilitar integración |
| `class-vbp-rest-api.php:156` | `/shortcodes` | Documentación pública |
| `class-vbp-rest-api.php:220` | `/ping` | Health check |
| `class-vbp-rest-api.php:263` | `/blog-posts` | Frontend público |
| `class-vbp-rest-api.php:304` | `/categories` | Frontend público |
| `class-vbp-realtime-server.php:151` | Heartbeat | sendBeacon |
| `class-vbp-ab-testing.php:253-264` | Tracking | Métricas A/B |
| `class-vbp-global-styles.php:165-176` | CSS público | Estilos frontend |
| `class-vbp-collaboration-api.php:80` | Heartbeat | sendBeacon |

**Recomendación:** Documentar explícitamente por qué cada endpoint es público y evaluar si se puede restringir.

---

## 4. Rate Limiting

### 4.1 Implementación actual

**Estado: BUENO**

Rate limiting implementado en:

1. **API Key verification** (`flavor-platform.php:605-624`)
   - 5 intentos / 5 minutos por IP

2. **Form submissions** (`class-vbp-form-handler.php:116-128`)
   - 5 envíos / 5 minutos por IP

3. **A/B Testing** (`class-vbp-ab-testing.php:714-719`)
   - Limita tracking por visitante

### 4.2 Mejoras recomendadas

1. **Implementar rate limiting global para API REST**
   - Actualmente no hay límite en endpoints de Claude API
   - **Recomendación:** Agregar límite de 100 requests/minuto por API key

---

## 5. Encriptación

### 5.1 API Key Encryption

**Estado: EXCELENTE**

Archivo: `includes/security/class-api-key-encryption.php`

#### Características:
- AES-256-GCM (authenticated encryption)
- IV aleatorio por encriptación
- HKDF para derivación de clave
- Fallback a AES-256-CBC + HMAC
- Caché en memoria limpiada en shutdown
- Funciones de rotación de keys

```php
private const CIPHER_METHOD = 'aes-256-gcm';
private const TAG_LENGTH = 16;
```

---

## 6. Logging de Seguridad

### 6.1 Audit Log System

**Estado: BUENO**

Archivo: `class-vbp-audit-log.php`

#### Eventos registrados:
- Creación/modificación/eliminación de páginas
- Cambios de estado de publicación
- Acciones de colaboración
- Tests A/B
- Comentarios

#### Funcionalidades:
- Limpieza automática programada
- Exportación de logs
- API REST para consulta
- Almacenamiento de IP y User-Agent

---

## 7. Protección contra Bots

### 7.1 Honeypot

**Estado: BUENO**

Implementado en `class-vbp-form-handler.php:154-160`:
```php
// Verificar honeypot (campo oculto para bots)
if ( ! empty( $_POST['website_url'] ) ) {
    // Probable bot, simular éxito pero no hacer nada
    wp_send_json_success(...);
}
```

---

## 8. Path Traversal

### 8.1 Protección implementada

**Estado: BUENO**

En `flavor-platform.php:846-864`:
```php
// Sanitizar y prevenir path traversal
$relative_path = str_replace( '..', '', $relative_path );
$relative_path = preg_replace( '#/+#', '/', $relative_path );

// Verificar que el archivo resuelto está dentro del directorio del plugin
$real_plugin_path = realpath( FLAVOR_PLATFORM_PATH );
$real_file_path = realpath( dirname( $file ) );

if ( $real_file_path && $real_plugin_path ) {
    if ( strpos( $real_file_path, $real_plugin_path ) !== 0 ) {
        flavor_log_error( 'Path traversal detectado: ' . $relative_path, 'Security' );
        return false;
    }
}
```

---

## 9. Headers de Seguridad

### 9.1 Estado actual

**Estado: NO IMPLEMENTADO**

No se encontraron headers de seguridad específicos en el código VBP:
- Content-Security-Policy
- X-Content-Type-Options
- X-Frame-Options

**Recomendación:** Agregar headers en respuestas de API:

```php
// Añadir en class-vbp-rest-api.php
add_filter( 'rest_post_dispatch', function( $response ) {
    $response->header( 'X-Content-Type-Options', 'nosniff' );
    return $response;
}, 10, 1 );
```

---

## 10. Vulnerabilidades Identificadas (Resumen)

### Críticas: 0

### Altas: 0

### Medias: 2

1. **Endpoints públicos exponen información del sistema**
   - Riesgo: Reconnaissance
   - Mitigación: Limitar información expuesta o requerir auth

2. **Sin rate limiting en API Claude**
   - Riesgo: DoS, abuso de recursos
   - Mitigación: Implementar límite por API key

### Bajas: 3

1. **Key legacy con flag habilitador**
2. **API keys sin expiración automática**
3. **Sin headers de seguridad HTTP**

---

## 11. Checklist de Seguridad

### Pre-despliegue

- [x] API keys generadas de forma segura
- [x] Rate limiting en autenticación
- [x] Validación de entrada con funciones de WordPress
- [x] Escape de salida en templates
- [x] CSRF protection con nonces
- [x] SQL injection prevention con prepare()
- [x] Logging de intentos de autenticación fallidos
- [x] Encriptación de datos sensibles
- [ ] Headers de seguridad HTTP
- [ ] Rate limiting global en API

### Producción

- [ ] Deshabilitar `FLAVOR_VBP_ALLOW_LEGACY_KEY`
- [ ] Regenerar API keys periódicamente
- [ ] Monitorizar logs de auditoría
- [ ] Revisar endpoints públicos
- [ ] Configurar WAF si disponible

---

## 12. Recomendaciones de Hardening

### Inmediatas (Prioridad Alta)

1. **Añadir rate limiting a endpoints de Claude API**

```php
// En class-vbp-claude-api.php, método verificar_api_key()
private function check_rate_limit( $api_key ) {
    $key_hash = md5( $api_key );
    $transient_key = 'vbp_api_rate_' . $key_hash;
    $count = (int) get_transient( $transient_key );

    if ( $count >= 100 ) { // 100 requests por minuto
        return new WP_Error( 'rate_limited', 'Rate limit exceeded', array( 'status' => 429 ) );
    }

    set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );
    return true;
}
```

2. **Añadir headers de seguridad**

```php
add_filter( 'rest_post_dispatch', 'vbp_add_security_headers', 10, 3 );
function vbp_add_security_headers( $response, $server, $request ) {
    if ( strpos( $request->get_route(), 'flavor-vbp' ) !== false ) {
        $response->header( 'X-Content-Type-Options', 'nosniff' );
        $response->header( 'X-Frame-Options', 'SAMEORIGIN' );
    }
    return $response;
}
```

### Próxima versión (Prioridad Media)

1. Eliminar soporte para key legacy
2. Implementar expiración opcional de API keys
3. Añadir 2FA para operaciones críticas (opcional)
4. Implementar Content-Security-Policy para editor

### Largo plazo (Prioridad Baja)

1. Auditoría de código por terceros
2. Programa de bug bounty
3. Certificación de seguridad

---

## 13. Conclusión

El Visual Builder Pro implementa buenas prácticas de seguridad en la mayoría de áreas críticas. La autenticación, validación de entrada y protección contra SQL injection son sólidas. Las principales áreas de mejora son:

1. Rate limiting en API REST
2. Headers de seguridad HTTP
3. Revisión de endpoints públicos

El código está preparado para producción con las mitigaciones menores recomendadas.

---

## Anexo A: Archivos Auditados

```
includes/visual-builder-pro/
├── class-vbp-claude-api.php         ✓ Auditado
├── class-vbp-rest-api.php           ✓ Auditado
├── class-vbp-settings.php           ✓ Auditado
├── class-vbp-form-handler.php       ✓ Auditado
├── class-vbp-audit-log.php          ✓ Auditado
├── class-vbp-collaboration-api.php  ✓ Auditado
├── class-vbp-ab-testing.php         ✓ Auditado
├── class-vbp-global-styles.php      ✓ Auditado
├── class-vbp-realtime-server.php    ✓ Auditado
└── ai/
    ├── class-vbp-ai-layout.php      ✓ Auditado
    └── class-vbp-ai-content.php     ✓ Auditado

includes/security/
└── class-api-key-encryption.php     ✓ Auditado

assets/vbp/js/
├── vbp-api.js                       ✓ Auditado
├── vbp-store.js                     ✓ Revisado
└── [otros archivos JS]              ✓ Revisado patrones

flavor-platform.php                  ✓ Auditado (funciones de API key)
```

## Anexo B: Herramientas utilizadas

- Análisis estático de código (grep, búsqueda de patrones)
- Revisión manual de archivos críticos
- Verificación de funciones de sanitización de WordPress

---

*Documento generado como parte de la auditoría de seguridad del Visual Builder Pro.*
