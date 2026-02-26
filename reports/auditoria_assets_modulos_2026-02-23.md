# Auditoria de Assets CSS/JS en Modulos - Flavor Chat IA

**Fecha:** 2026-02-23
**Plugin:** flavor-chat-ia
**Ruta base:** `/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia/includes/modules/`

---

## Resumen Ejecutivo

| Metrica | Valor |
|---------|-------|
| Total de modulos auditados | 47 |
| Total de assets verificados | 108 |
| Assets existentes | 102 |
| Assets faltantes (criticos) | 6 |
| Assets opcionales (con verificacion) | 2 |

---

## Assets Faltantes (Criticos)

Estos archivos estan referenciados en el codigo PHP pero NO existen en el sistema de archivos:

| Modulo | Archivo PHP | Asset Faltante | Tipo |
|--------|-------------|----------------|------|
| **carpooling** | frontend/class-carpooling-frontend-controller.php | `assets/carpooling-frontend.css` | CSS |
| **carpooling** | frontend/class-carpooling-frontend-controller.php | `assets/carpooling-frontend.js` | JS |
| **cursos** | class-cursos-module.php | `assets/css/cursos-instructor.css` | CSS |
| **cursos** | class-cursos-module.php | `assets/js/cursos-instructor.js` | JS |
| **grupos-consumo** | class-gc-conciencia-features.php | `assets/css/trueques.css` | CSS |
| **grupos-consumo** | class-gc-conciencia-features.php | `assets/js/trueques.js` | JS |

---

## Assets Opcionales (Con Verificacion file_exists)

Estos modulos verifican la existencia del archivo antes de encolarlo, por lo que no generan errores:

| Modulo | Asset | Estado |
|--------|-------|--------|
| **podcast** | `assets/css/podcast.css` | No existe (verificado antes de encolar) |
| **podcast** | `assets/js/podcast.js` | No existe (verificado antes de encolar) |

---

## Detalle por Modulo

### Modulos con Todos los Assets Presentes

| Modulo | CSS | JS | Estado |
|--------|-----|----| ------ |
| advertising | assets/css/advertising-frontend.css | assets/js/advertising-frontend.js, assets/js/tracking.js | OK |
| avisos-municipales | assets/css/avisos.css | assets/js/avisos.js | OK |
| ayuda-vecinal | assets/css/ayuda-vecinal.css | assets/js/ayuda-vecinal.js | OK |
| banco-tiempo | assets/css/banco-tiempo.css | assets/js/banco-tiempo.js | OK |
| biblioteca | assets/css/biblioteca-frontend.css | assets/js/biblioteca-frontend.js | OK |
| biodiversidad-local | assets/css/biodiversidad-local.css | assets/js/biodiversidad-local.js | OK |
| chat-estados | assets/css/chat-estados.css | assets/js/chat-estados.js | OK |
| chat-grupos | assets/css/chat-grupos.css | assets/js/chat-grupos.js | OK |
| chat-interno | assets/css/chat-interno.css | assets/js/chat-interno.js | OK |
| circulos-cuidados | assets/css/circulos-cuidados.css | assets/js/circulos-cuidados.js | OK |
| colectivos | assets/css/colectivos.css | assets/js/colectivos.js | OK |
| compostaje | assets/css/compostaje.css | assets/js/compostaje.js | OK |
| comunidades | assets/css/comunidades.css | assets/js/comunidades.js | OK |
| economia-don | assets/css/economia-don.css | assets/js/economia-don.js | OK |
| economia-suficiencia | assets/css/economia-suficiencia.css | assets/js/economia-suficiencia.js | OK |
| email-marketing | assets/css/em-admin.css, em-frontend.css | assets/js/em-admin.js, em-frontend.js | OK |
| empresarial | assets/css/empresarial.css | assets/js/empresarial.js | OK |
| espacios-comunes | assets/css/espacios-frontend.css | assets/js/espacios-frontend.js | OK |
| eventos | assets/css/eventos.css | assets/js/eventos.js | OK |
| facturas | assets/css/facturas.css | assets/js/facturas.js | OK |
| fichaje-empleados | assets/css/fichaje-empleados.css | assets/js/fichaje-empleados.js | OK |
| huella-ecologica | assets/css/huella-ecologica.css | assets/js/huella-ecologica.js | OK |
| huertos-urbanos | assets/css/huertos.css | assets/js/huertos.js | OK |
| incidencias | assets/css/incidencias-frontend.css | assets/js/incidencias-frontend.js | OK |
| justicia-restaurativa | assets/css/justicia-restaurativa.css | assets/js/justicia-restaurativa.js | OK |
| marketplace | assets/marketplace-frontend.css | assets/marketplace-frontend.js | OK |
| multimedia | assets/css/multimedia-frontend.css, multimedia-admin.css | assets/js/multimedia-frontend.js, multimedia-admin.js | OK |
| parkings | assets/css/parkings.css | assets/js/parkings.js | OK |
| participacion | assets/css/participacion.css | assets/js/participacion.js | OK |
| presupuestos-participativos | assets/css/presupuestos.css | assets/js/presupuestos.js | OK |
| radio | assets/css/radio-frontend.css, radio-admin.css | assets/js/radio-frontend.js, radio-admin.js | OK |
| reciclaje | assets/css/reciclaje.css, reciclaje-admin.css | assets/js/reciclaje.js, reciclaje-admin.js | OK |
| red-social | assets/css/red-social.css | assets/js/red-social.js | OK |
| reservas | assets/css/reservas.css | assets/js/reservas.js | OK |
| saberes-ancestrales | assets/css/saberes-ancestrales.css | assets/js/saberes-ancestrales.js | OK |
| sello-conciencia | assets/css/sello-conciencia.css | (no usa JS) | OK |
| socios | assets/css/socios.css | assets/js/socios.js | OK |
| talleres | assets/css/talleres.css | assets/js/talleres.js | OK |
| trabajo-digno | assets/css/trabajo-digno.css | assets/js/trabajo-digno.js | OK |
| tramites | assets/css/tramites.css | assets/js/tramites.js | OK |
| transparencia | assets/css/transparencia.css | assets/js/transparencia.js | OK |

### Grupos Consumo (Detalle)

| Asset | Ruta | Estado |
|-------|------|--------|
| gc-frontend.css | assets/gc-frontend.css | OK |
| gc-frontend.js | assets/gc-frontend.js | OK |
| gc-catalogo.css | assets/gc-catalogo.css | OK |
| gc-catalogo.js | assets/gc-catalogo.js | OK |
| gc-dashboard.css | assets/gc-dashboard.css | OK |
| gc-dashboard.js | assets/gc-dashboard.js | OK |
| trueques.css | assets/css/trueques.css | **FALTA** |
| trueques.js | assets/js/trueques.js | **FALTA** |

### Cursos (Detalle)

| Asset | Ruta | Estado |
|-------|------|--------|
| cursos-frontend.css | assets/css/cursos-frontend.css | OK |
| cursos-frontend.js | assets/js/cursos-frontend.js | OK |
| cursos-aula.css | assets/css/cursos-aula.css | OK |
| cursos-aula.js | assets/js/cursos-aula.js | OK |
| cursos-instructor.css | assets/css/cursos-instructor.css | **FALTA** |
| cursos-instructor.js | assets/js/cursos-instructor.js | **FALTA** |

### Carpooling (Detalle)

| Asset | Ruta | Archivo que lo usa | Estado |
|-------|------|-------------------|--------|
| carpooling.css | assets/css/carpooling.css | class-carpooling-module.php | OK |
| carpooling.js | assets/js/carpooling.js | class-carpooling-module.php | OK |
| carpooling-frontend.css | assets/carpooling-frontend.css | frontend/class-carpooling-frontend-controller.php | **FALTA** |
| carpooling-frontend.js | assets/carpooling-frontend.js | frontend/class-carpooling-frontend-controller.php | **FALTA** |

---

## Modulos sin Enqueue de Assets Propios

Los siguientes modulos no tienen registros de wp_enqueue_style/script propios:

- **bicicletas-compartidas**: Sin assets propios registrados
- **trading-ia**: Sin assets propios en la busqueda (posiblemente usa assets externos)
- **class-shared-features.php**: Usa assets de la carpeta raiz del plugin (assets/css/shared-features.css, assets/js/shared-features.js) - **OK**

---

## Assets en Carpeta Raiz del Plugin

| Asset | Ruta | Estado |
|-------|------|--------|
| shared-features.css | /assets/css/shared-features.css | OK |
| shared-features.js | /assets/js/shared-features.js | OK |

---

## Recomendaciones

### Acciones Inmediatas (Prioridad Alta)

1. **Crear assets faltantes de carpooling:**
   - `/includes/modules/carpooling/assets/carpooling-frontend.css`
   - `/includes/modules/carpooling/assets/carpooling-frontend.js`

   *Alternativa:* Modificar `frontend/class-carpooling-frontend-controller.php` para usar los assets existentes `assets/css/carpooling.css` y `assets/js/carpooling.js`

2. **Crear assets faltantes de cursos-instructor:**
   - `/includes/modules/cursos/assets/css/cursos-instructor.css`
   - `/includes/modules/cursos/assets/js/cursos-instructor.js`

3. **Crear assets faltantes de trueques (grupos-consumo):**
   - `/includes/modules/grupos-consumo/assets/css/trueques.css`
   - `/includes/modules/grupos-consumo/assets/js/trueques.js`

### Mejoras Sugeridas

1. **Estandarizar estructura de assets:** Algunos modulos usan `assets/css/` y `assets/js/`, mientras otros usan directamente `assets/`. Considerar unificar.

2. **Implementar verificacion file_exists:** Seguir el patron del modulo `podcast` que verifica si el archivo existe antes de encolarlo para evitar errores 404.

3. **Consolidar assets duplicados:** El modulo carpooling tiene dos conjuntos de assets (carpooling.css/js y carpooling-frontend.css/js). Considerar consolidar.

---

## Metodologia de Auditoria

1. Busqueda de todas las llamadas a `wp_register_style`, `wp_register_script`, `wp_enqueue_style`, `wp_enqueue_script` en archivos PHP dentro de `/includes/modules/`
2. Extraccion de rutas de assets referenciadas
3. Verificacion de existencia fisica de cada archivo en el sistema de archivos
4. Clasificacion por estado (existe/falta/opcional)

---

*Informe generado automaticamente - 2026-02-23*
