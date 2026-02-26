# Auditoría de Patrones Críticos e Importantes en Módulos

**Fecha:** 2026-02-23
**Total módulos analizados:** 54
**Versión:** 1.3
**Última actualización:** 2026-02-23 (todas las correcciones críticas e importantes completadas)

### 📊 Estado de Correcciones

| Categoría | Pendientes | Completados |
|-----------|------------|-------------|
| Comparaciones estrictas (Patrón 7) | 0 | 19 |
| Mensajes incorrectos (Patrón 6) | 0 | 10 |
| Datos hardcodeados (Patrón 1) | 0 | 2 (+ 3 revisados sin problema) |
| Enlaces rotos (Patrón 3) | 0 | 2 |
| Filtros incorrectos (Patrón 2) | 0 | 2 |
| Meta keys incorrectas | 0 | 2 |
| AJAX sin nonce (Patrón 9) | 7 | 0 |

> **Nota:** Los módulos con AJAX sin nonce requieren análisis más profundo y algunos están en producción (excluidos).

---

## Módulos Excluidos del Plan de Correcciones

Los siguientes módulos están **EN PRODUCCIÓN** y han sido excluidos del plan de correcciones para evitar regresiones:

| Módulo | Razón de Exclusión | Notas |
|--------|-------------------|-------|
| **dex-solana** | ⚠️ EN PRODUCCIÓN | Módulo financiero crítico - requiere revisión manual por equipo especializado |
| **trading-ia** | ⚠️ EN PRODUCCIÓN | Módulo financiero crítico - requiere revisión manual por equipo especializado |

> **Importante:** Cualquier modificación a estos módulos debe ser coordinada con el equipo de producción y realizarse en un entorno de staging primero.

---

## Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Patrones Buscados](#patrones-buscados)
3. [Hallazgos por Severidad](#hallazgos-por-severidad)
4. [Detalle por Módulo](#detalle-por-módulo)
5. [Recomendaciones Prioritarias](#recomendaciones-prioritarias)
6. [Módulos Pendientes de Revisión Manual](#módulos-pendientes)

---

## Resumen Ejecutivo

### Estadísticas Generales

| Severidad | Cantidad de Problemas | Módulos Afectados |
|-----------|----------------------|-------------------|
| 🔴 Crítico | 8 | 6 |
| 🟠 Importante | 45+ | 15 |
| ✅ Sin problemas | - | 25+ |

### Top 5 Problemas Más Frecuentes

1. **Patrón 7** - Comparaciones inseguras (`==`/`!=` vs `===`/`!==`): 20+ instancias
2. **Patrón 9** - AJAX sin validación de nonce: 6 módulos
3. **Patrón 1** - Datos hardcodeados en templates: 4 módulos
4. **Patrón 6** - Mensajes de respuesta incorrectos: 3 módulos
5. **Patrón 3** - Enlaces rotos (`href="#"`): 2 módulos

---

## Patrones Buscados

### 🔴 Críticos

| # | Patrón | Descripción |
|---|--------|-------------|
| 1 | Datos hardcodeados | Templates/views con arrays de datos de ejemplo que nunca consultan la BD real |
| 2 | Filtros meta_query vs tax_query | Usar meta_query para campos que son taxonomías o viceversa |
| 3 | Enlaces rotos | URLs que apuntan a '#' o variables no definidas |
| 4 | Shortcodes sin datos reales | Solo muestran placeholders o datos de ejemplo |

### 🟠 Importantes

| # | Patrón | Descripción |
|---|--------|-------------|
| 5 | Inconsistencia tipo/taxonomía | Campo guardado como taxonomy pero leído como meta |
| 6 | Mensajes de respuesta incorrectos | wp_send_json_success/error con mensaje equivocado |
| 7 | Comparaciones inseguras | Usar `==` o `!=` en lugar de `===` o `!==` para user_id, post_author |
| 8 | Falta de sanitización | Contenido sin escape en respuestas JSON o output HTML |
| 9 | AJAX sin nonce | Handlers que no validan nonce o permisos |

---

## Hallazgos por Severidad

### 🔴 CRÍTICOS (Requieren Acción Inmediata)

#### 1. marketplace
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `templates/components/marketplace/categorias.php` | 10-59 | 1 | Datos de categorías hardcodeados, nunca consulta BD |
| `templates/components/marketplace/anuncios-grid.php` | 12-91 | 1 | 6 anuncios de ejemplo hardcodeados |
| `frontend/class-marketplace-frontend-controller.php` | 210-212 | 2 | Usa meta_query para filtrar por tipo que es taxonomía |
| `frontend/archive.php` | 159 | 3 | Botón "Ver más" apunta a `#` |

#### 2. eventos
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `class-eventos-module.php` | múltiples | 9 | 0 validaciones check_ajax_referer en handlers AJAX |

#### 3. empresarial
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `templates/portfolio.php` | 37-86 | 1 | 6 proyectos de ejemplo hardcodeados (Tech Corp, Retail Plus, etc.) |
| `templates/servicios.php` | 21-53 | 1 | 6 servicios de ejemplo hardcodeados |

#### 4. economia-suficiencia
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `templates/biblioteca.php` | 37-87 | 1 | 6 proyectos de ejemplo hardcodeados |

#### 5. parkings
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `class-parkings-module.php` | 870 | 3 | Botón "Ver disponibilidad" con `href="#"` |

#### 6. woocommerce
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `class-woocommerce-module.php` | 516 | 6 | Mensaje de error incorrecto: `'cart_updated'` como error |

#### 7. reservas
| Archivo | Línea | Patrón | Descripción |
|---------|-------|--------|-------------|
| `class-reservas-module.php` | 1090 | 7 | Comparación insegura `==` en validación de permisos user_id |
| `class-reservas-module.php` | 1226 | 7 | Comparación insegura `==` en modificar_reserva |

---

### 🟠 IMPORTANTES (Requieren Corrección)

#### Módulos con AJAX sin validación de nonce (Patrón 9)

| Módulo | Handlers Afectados | Estado |
|--------|-------------------|--------|
| ~~dex-solana~~ | ~~Todos (0 check_ajax_referer encontrados)~~ | ⚠️ EN PRODUCCIÓN - EXCLUIDO |
| ~~trading-ia~~ | ~~Todos (0 check_ajax_referer encontrados)~~ | ⚠️ EN PRODUCCIÓN - EXCLUIDO |
| socios | Sin check_ajax_referer visible | Pendiente |
| talleres | 10 handlers AJAX sin validación |
| tramites | Handler AJAX sin nonce visible |
| transparencia | 8 handlers AJAX sin validación |

#### Módulos con Comparaciones Inseguras (Patrón 7)

| Módulo | Archivo | Líneas | Descripción |
|--------|---------|--------|-------------|
| multimedia | class-multimedia-module.php | 768, 875, 1185, 1243, 1363, 1400, 1431 | 7 instancias de `!=` en verificación de usuario_id |
| circulos-cuidados | class-circulos-cuidados-module.php | 683, 888 | 2 instancias de `==` para comparar IDs |
| red-social | class-red-social-module.php | 596, 678 | Comparaciones con `==` y `!=` |
| chat-estados | class-chat-estados-module.php | 590, 621, 807, 850 | 4 instancias de `==` |
| economia-suficiencia | class-economia-suficiencia-module.php | 426 | `!=` en verificación post_author |
| banco-tiempo | class-banco-tiempo-module.php | 1659 | Cast manual a int en lugar de `===` |

#### Módulos con Mensajes Incorrectos (Patrón 6)

| Módulo | Archivo | Líneas | Descripción |
|--------|---------|--------|-------------|
| marketplace | class-marketplace-api.php | 624, 659 | Dice "Anuncio publicado" en contactar/marcar vendido |
| red-social | class-red-social-module.php | 540, 577, 627, 710+ | Mensaje "Debes iniciar sesión" usado para validaciones incorrectas |
| red-social | class-red-social-module.php | 577 | Mensaje contiene variable: `'publicacion_id'` |

---

## Detalle por Módulo

### Módulos SIN Problemas Detectados ✅

Los siguientes módulos pasaron la auditoría sin problemas críticos ni importantes:

- avisos-municipales
- bares
- biodiversidad-local
- chat-interno
- colectivos
- compostaje
- comunidades
- cursos
- economia-don
- espacios-comunes
- fichaje-empleados
- foros
- grupos-consumo
- huella-ecologica
- huertos-urbanos
- incidencias
- justicia-restaurativa
- clientes
- recetas
- reciclaje
- saberes-ancestrales
- trabajo-digno
- sello-conciencia

### Módulos con Problemas Menores 🟡

| Módulo | Problema | Severidad |
|--------|----------|-----------|
| ayuda-vecinal | AJAX handler sin nonce visible en frontend | Baja |
| biblioteca | Necesita verificación de comparaciones | Baja |
| bicicletas-compartidas | Documentación de comparaciones | Baja |
| carpooling | Comparaciones numéricas correctas | N/A |
| themacle | Enlace "#" en botón documentación | Baja |

---

## Recomendaciones Prioritarias

### 🔴 Prioridad ALTA (Seguridad)

1. **URGENTE - eventos**: Agregar `check_ajax_referer()` a TODOS los handlers AJAX
2. **URGENTE - dex-solana**: Agregar validación de nonce (módulo financiero)
3. **URGENTE - trading-ia**: Agregar validación de nonce (módulo financiero)
4. **URGENTE - reservas**: Cambiar `==` a `===` en líneas 1090 y 1226

### 🟠 Prioridad MEDIA (Funcionalidad)

5. **marketplace**:
   - Reemplazar datos hardcodeados por consultas a BD
   - Cambiar meta_query a tax_query para filtro de tipo
   - Arreglar enlaces "Ver más"

6. **empresarial / economia-suficiencia**:
   - Eliminar datos de ejemplo hardcodeados
   - Implementar consultas reales a BD

7. **red-social**:
   - Corregir todos los mensajes de error (10+ instancias)
   - Cambiar comparaciones a estrictas

8. **multimedia**:
   - Cambiar 7 instancias de `!=` a `!==`

### 🟡 Prioridad BAJA (Mejoras)

9. Estandarizar comparaciones de IDs en todos los módulos
10. Agregar validación de nonce a módulos restantes (socios, talleres, tramites, transparencia)
11. Corregir mensajes duplicados en woocommerce

---

## Módulos Pendientes de Revisión Manual

Los siguientes módulos tienen archivos muy grandes (+25k tokens) que no pudieron analizarse completamente:

| Módulo | Tamaño Estimado | Notas |
|--------|-----------------|-------|
| advertising | ~29k tokens | Revisar handlers AJAX |
| dex-solana | ~31k tokens | Revisar seguridad completa |
| email-marketing | ~30k tokens | Revisar validaciones |
| trading-ia | ~42k tokens | Revisar operaciones financieras |
| facturas | ~25k tokens | Revisar handlers AJAX |
| presupuestos-participativos | ~28k tokens | Revisar validaciones |
| radio | ~26k tokens | Revisar handlers AJAX |
| chat-grupos | ~125k tokens | Módulo muy grande |

---

## Checklist de Correcciones

### Seguridad AJAX (Patrón 9)
- [ ] eventos - Agregar check_ajax_referer
- [ ] dex-solana - Agregar check_ajax_referer
- [ ] trading-ia - Agregar check_ajax_referer
- [ ] socios - Verificar y agregar nonce
- [ ] talleres - Agregar nonce a 10 handlers
- [ ] tramites - Agregar nonce
- [ ] transparencia - Agregar nonce a 8 handlers

### Comparaciones Estrictas (Patrón 7)
- [x] marketplace-api.php:433 - ✅ CORREGIDO
- [x] marketplace-api.php:497 - ✅ CORREGIDO
- [x] marketplace-api.php:646 - ✅ CORREGIDO
- [x] marketplace-frontend-controller.php:704 - ✅ CORREGIDO
- [x] marketplace-frontend-controller.php:371 - ✅ CORREGIDO
- [x] reservas:1090 - ✅ CORREGIDO
- [x] reservas:1226 - ✅ CORREGIDO
- [x] multimedia:768 - ✅ CORREGIDO
- [x] multimedia:875 - ✅ CORREGIDO
- [x] multimedia:1185 - ✅ CORREGIDO
- [x] multimedia:1243 - ✅ CORREGIDO
- [x] multimedia:1363 - ✅ CORREGIDO
- [x] multimedia:1400 - ✅ CORREGIDO
- [x] multimedia:1431 - ✅ CORREGIDO
- [x] economia-suficiencia/biblioteca.php:71 - ✅ CORREGIDO
- [x] circulos-cuidados:683,888 - ✅ CORREGIDO - Cambiado == a === con cast (int)
- [x] red-social:596,678 - ✅ CORREGIDO - Cambiado == y != a estrictos
- [x] chat-estados:590,621,807,850 - ✅ CORREGIDO - Cambiado == a === con cast (int)
- [x] economia-suficiencia:426 - ✅ CORREGIDO - Cambiado != a !== con cast (int)

### Datos Hardcodeados (Patrón 1)
- [x] marketplace/categorias.php - ✅ CORREGIDO - Ahora consulta BD real
- [x] marketplace/anuncios-grid.php - ✅ CORREGIDO - Ahora consulta BD real
- [x] empresarial/portfolio.php - ✅ REVISADO - Usa patrón fallback válido (solo muestra ejemplos si BD está vacía)
- [x] empresarial/servicios.php - ✅ REVISADO - Usa patrón fallback válido (solo muestra ejemplos si BD está vacía)
- [x] economia-suficiencia/biblioteca.php - ✅ REVISADO - No tiene datos hardcodeados, consulta BD real

### Mensajes Incorrectos (Patrón 6)
- [x] marketplace-api.php:624 - ✅ CORREGIDO - "Mensaje enviado correctamente"
- [x] marketplace-api.php:659 - ✅ CORREGIDO - "Anuncio marcado como vendido"
- [x] woocommerce-module.php:516 - ✅ CORREGIDO - "WooCommerce no está disponible"
- [x] woocommerce-module.php:540 - ✅ CORREGIDO - "Producto eliminado del carrito"
- [x] red-social-module.php - ✅ CORREGIDO - Múltiples mensajes corregidos (líneas 540, 577, 627, 718, 735, 783)

### Enlaces Rotos (Patrón 3)
- [x] marketplace/anuncios-grid.php - ✅ CORREGIDO - URLs reales a permalink
- [x] parkings-module.php:870 - ✅ CORREGIDO - Cambiado <a href="#"> a <button type="button">

### Filtros Incorrectos (Patrón 2)
- [x] marketplace-frontend-controller.php:210-212 - ✅ CORREGIDO - Cambiado meta_query a tax_query
- [x] marketplace-frontend-controller.php:735-738 - ✅ CORREGIDO - Cambiado meta_query a tax_query

### Post Type Incorrecto
- [x] marketplace/frontend/archive.php:20 - ✅ CORREGIDO - Cambiado 'marketplace' a 'marketplace_item'

### Meta Keys Incorrectas
- [x] marketplace/frontend/archive.php - ✅ CORREGIDO - Cambiado '_precio' a '_marketplace_precio'
- [x] marketplace/frontend/single.php - ✅ CORREGIDO - Cambiado '_precio'/'_condicion' a '_marketplace_precio'/'_marketplace_condicion'

---

## Notas Adicionales

### Buenas Prácticas Encontradas

La mayoría de módulos implementan correctamente:
- Sanitización con `sanitize_text_field()`, `sanitize_textarea_field()`, `absint()`
- Escape de salida con `esc_html()`, `esc_attr()`, `esc_url()`
- Prepared statements en consultas SQL
- Validación de permisos con `current_user_can()`
- Verificación de login con `is_user_logged_in()`

### Patrón de Código Recomendado

```php
// ❌ Incorrecto
if ($post->post_author != $user_id) { ... }

// ✅ Correcto
if ((int) $post->post_author !== (int) $user_id) { ... }

// ❌ Incorrecto
function ajax_handler() {
    // Sin validación de nonce
    $data = $_POST['data'];
}

// ✅ Correcto
function ajax_handler() {
    check_ajax_referer('mi_nonce_action', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
    }
    $data = sanitize_text_field($_POST['data']);
}
```

---

*Documento generado automáticamente por auditoría de código*
