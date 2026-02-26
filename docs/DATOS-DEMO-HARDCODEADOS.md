# Datos de Demostración Hardcodeados

**Fecha de auditoría:** 2026-02-23
**Ubicación:** `includes/class-module-shortcodes.php`
**Método:** `get_sample_data()`

---

## Problema Detectado

Los shortcodes de fallback muestran **datos de demostración estáticos** en lugar de:
1. Conectar con los datos reales de la base de datos
2. Mostrar "Sin contenido" cuando no hay datos

Esto causa que todas las secciones del dashboard (Listado, Mis Anuncios, Publicar, etc.) muestren el **mismo contenido repetido**.

---

## Módulos Afectados (16)

| Módulo | Líneas | Datos de Ejemplo |
|--------|--------|------------------|
| talleres | 1352-1357 | Fotografía, Cocina, Yoga |
| grupos_consumo | 1359-1364 | Grupo Ecológico Norte, Cooperativa Sur |
| bicicletas_compartidas | 1366-1371 | Estación Centro, Parque, Universidad |
| carpooling | 1373-1378 | Madrid→Barcelona, Valencia→Alicante |
| biblioteca | 1380-1385 | El Quijote, 1984, Cien años de soledad |
| marketplace | 1387-1392 | Bicicleta montaña, Libros, Mueble jardín |
| incidencias | 1394-1399 | Farola, Bache, Contenedor |
| comunidades | 1401-1406 | Vecinal Centro, Club Lectura, Runners |
| espacios_comunes | 1408-1413 | Sala Reuniones, Salón Actos, Terraza |
| huertos_urbanos | 1415-1420 | Parcela A-12, B-05, Zona Comunitaria |
| podcast | 1422-1427 | Episodio 45, 44, 43 |
| banco_tiempo | 1429-1434 | Guitarra, Mudanza, Ordenadores |
| cursos | 1436-1441 | Python, Marketing, Inglés |
| parkings | 1443-1448 | Centro, Estación, Hospital |
| default | 1450-1456 | "Elemento de ejemplo 1, 2, 3" |

---

## Solución Propuesta

### Opción A: Eliminar datos de demo (Recomendada)

Modificar `get_sample_data()` para devolver array vacío y mostrar mensaje "Sin contenido":

```php
private function get_sample_data($modulo_id) {
    // No mostrar datos de demostración - usar shortcodes específicos
    return [];
}
```

### Opción B: Toggle de Modo Demo

Añadir setting global `flavor_demo_mode`:

```php
private function get_sample_data($modulo_id) {
    if (!get_option('flavor_demo_mode', false)) {
        return [];
    }
    // ... datos de demo actuales
}
```

### Opción C: Marcar como DEMO visible

Añadir badge "[DEMO]" a cada título para que sea evidente:

```php
['titulo'=>'[DEMO] Bicicleta de montaña', ...]
```

---

## Shortcodes Correctos por Módulo

Los módulos tienen shortcodes específicos implementados que SÍ conectan con datos reales:

### marketplace
- `[marketplace_catalogo]` - Catálogo de anuncios reales
- `[marketplace_mis_anuncios]` - Anuncios del usuario actual
- `[marketplace_formulario]` - Formulario de publicación
- `[marketplace_favoritos]` - Favoritos del usuario
- `[marketplace_busqueda]` - Búsqueda avanzada
- `[marketplace_detalle]` - Detalle de anuncio

### banco_tiempo
- `[banco_tiempo_listado]` - Servicios reales de la DB
- `[banco_tiempo_ofrecer]` - Formulario de oferta
- `[banco_tiempo_mis_intercambios]` - Intercambios del usuario

### eventos
- `[eventos_listado]` - Eventos reales
- `[eventos_calendario]` - Calendario
- `[eventos_crear]` - Formulario

### cursos
- `[cursos_catalogo]` - Cursos de la DB
- `[cursos_mis_inscripciones]` - Inscripciones del usuario

---

## Páginas que Necesitan Corrección

Las páginas del dashboard (`/mi-portal/{modulo}/`) deben usar los shortcodes específicos:

| Página | Shortcode Actual (Fallback) | Shortcode Correcto |
|--------|---------------------------|-------------------|
| /mi-portal/marketplace/ | marketplace_listado | marketplace_catalogo |
| /mi-portal/marketplace/mis-anuncios | marketplace_listado | marketplace_mis_anuncios |
| /mi-portal/marketplace/publicar | marketplace_listado | marketplace_formulario |
| /mi-portal/banco-tiempo/ | banco_tiempo_listado | banco_tiempo_listado (verificar conexión DB) |
| /mi-portal/banco-tiempo/ofrecer | banco_tiempo_listado | banco_tiempo_ofrecer |
| /mi-portal/cursos/ | cursos_listado | cursos_catalogo |

---

## Acción Inmediata

1. Modificar `get_sample_data()` para devolver `[]` en producción
2. Actualizar páginas con shortcodes correctos
3. Verificar que shortcodes específicos conectan con datos reales

---

*Documento generado automáticamente - 2026-02-23*
