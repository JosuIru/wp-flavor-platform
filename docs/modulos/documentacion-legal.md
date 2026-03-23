# Módulo: Documentación Legal

> Repositorio de documentos legales para la defensa del territorio

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `documentacion_legal` |
| **Versión** | 1.0.0+ |
| **Categoría** | Administración / Legal |

---

## Descripción

Repositorio de documentos legales: leyes, sentencias, modelos de denuncia, recursos administrativos y guías jurídicas para la defensa del territorio. Permite subir, buscar, clasificar y compartir documentación legal relevante.

### Características Principales

- **Tipos de Documento**: Leyes, decretos, ordenanzas, sentencias, modelos, guías
- **Ámbitos**: Estatal, autonómico, provincial, municipal, europeo
- **Categorías**: Medio ambiente, urbanismo, aguas, patrimonio, energía...
- **Búsqueda Fulltext**: En título, descripción, contenido y palabras clave
- **Favoritos**: Guardar documentos con notas personales
- **Comentarios**: Anotaciones y discusión por documento
- **Verificación**: Sistema de validación de documentos

---

## Tablas de Base de Datos

### `{prefix}_flavor_documentacion_legal`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `titulo` | varchar(255) | Título del documento |
| `descripcion` | text | Descripción |
| `contenido` | longtext | Contenido completo |
| `tipo` | enum | Ver tipos de documento |
| `categoria` | varchar(100) | Categoría temática |
| `subcategoria` | varchar(100) | Subcategoría |
| `ambito` | enum | Ámbito territorial |
| `fecha_publicacion` | date | Fecha de publicación oficial |
| `fecha_vigencia` | date | Fecha de entrada en vigencia |
| `numero_referencia` | varchar(100) | Número de referencia oficial |
| `boe_bop` | varchar(255) | Referencia BOE/BOP |
| `url_oficial` | varchar(500) | URL fuente oficial |
| `archivo_adjunto` | varchar(255) | URL del archivo |
| `archivo_tipo` | varchar(50) | MIME type |
| `archivo_tamano` | int | Tamaño en bytes |
| `palabras_clave` | text | Keywords para búsqueda |
| `etiquetas` | text | Tags |
| `autor_id` | bigint | Usuario que subió |
| `verificado` | tinyint | Documento verificado |
| `verificado_por` | bigint | Usuario verificador |
| `destacado` | tinyint | Documento destacado |
| `descargas` | int | Contador descargas |
| `visitas` | int | Contador visitas |
| `estado` | enum | Estado del documento |

**Índices:**
- `tipo`, `categoria`, `ambito`, `autor_id`, `estado`, `verificado`
- FULLTEXT: `titulo`, `descripcion`, `contenido`, `palabras_clave`

### `{prefix}_flavor_documentacion_legal_categorias`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(100) | Nombre de categoría |
| `slug` | varchar(100) | Slug único |
| `descripcion` | text | Descripción |
| `icono` | varchar(50) | Icono dashicons |
| `color` | varchar(7) | Color hex |
| `parent_id` | bigint | Categoría padre |
| `orden` | int | Orden de visualización |

### `{prefix}_flavor_documentacion_legal_favoritos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `documento_id` | bigint | Documento guardado |
| `user_id` | bigint | Usuario |
| `notas` | text | Notas personales |
| `created_at` | datetime | Fecha de guardado |

### `{prefix}_flavor_documentacion_legal_comentarios`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `documento_id` | bigint | Documento |
| `user_id` | bigint | Usuario |
| `comentario` | text | Texto del comentario |
| `tipo` | enum | `nota`, `pregunta`, `aclaracion`, `correccion` |
| `estado` | enum | `visible`, `oculto`, `resuelto` |
| `parent_id` | bigint | Comentario padre (hilos) |

---

## Tipos de Documento

| Tipo | Nombre |
|------|--------|
| `ley` | Ley |
| `decreto` | Decreto |
| `ordenanza` | Ordenanza |
| `sentencia` | Sentencia |
| `modelo_denuncia` | Modelo de Denuncia |
| `modelo_recurso` | Modelo de Recurso |
| `guia` | Guía Jurídica |
| `informe` | Informe |
| `otro` | Otro |

## Ámbitos Territoriales

| Ámbito | Nombre |
|--------|--------|
| `estatal` | Estatal |
| `autonomico` | Autonómico |
| `provincial` | Provincial |
| `municipal` | Municipal |
| `europeo` | Europeo |

## Estados de Documento

| Estado | Descripción |
|--------|-------------|
| `publicado` | Visible públicamente |
| `borrador` | En edición |
| `revision` | Pendiente de verificación |
| `archivado` | Archivado (no visible) |

---

## Categorías por Defecto

| Categoría | Slug | Icono | Color |
|-----------|------|-------|-------|
| Medio Ambiente | medio-ambiente | admin-site-alt3 | #16a34a |
| Urbanismo | urbanismo | building | #2563eb |
| Aguas | aguas | admin-site | #0891b2 |
| Montes y Forestación | montes | palmtree | #065f46 |
| Patrimonio | patrimonio | bank | #9333ea |
| Participación Ciudadana | participacion | groups | #dc2626 |
| Transparencia | transparencia | visibility | #f59e0b |
| Derechos Fundamentales | derechos | shield | #7c3aed |
| Energía | energia | lightbulb | #eab308 |
| Agricultura | agricultura | carrot | #84cc16 |

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[documentacion_legal_listar]` | Listado de documentos | `tipo`, `categoria`, `limite`, `orden` |
| `[documentacion_legal_detalle]` | Detalle de documento | `id` |
| `[documentacion_legal_buscar]` | Buscador | - |
| `[documentacion_legal_categorias]` | Lista de categorías | - |
| `[documentacion_legal_subir]` | Formulario de subida | - |
| `[documentacion_legal_mis_guardados]` | Mis favoritos | - |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `requiere_verificacion` | bool | true | Documentos requieren verificación |
| `permitir_comentarios` | bool | true | Permitir comentarios |
| `permitir_descargas` | bool | true | Permitir descargas |
| `mostrar_visitas` | bool | true | Mostrar contador visitas |
| `tipos_archivo_permitidos` | array | [pdf,doc,docx,odt,txt] | Extensiones permitidas |
| `tamano_maximo_archivo` | int | 10 | MB máximo por archivo |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `documentacion_legal_subir` | Subir documento | Usuario |
| `documentacion_legal_guardar` | Guardar en favoritos | Usuario |
| `documentacion_legal_quitar_guardado` | Quitar de favoritos | Usuario |
| `documentacion_legal_comentar` | Añadir comentario | Usuario |
| `documentacion_legal_descargar` | Descargar archivo | Público |
| `documentacion_legal_buscar` | Buscar documentos | Público |
| `documentacion_legal_listar` | Listar documentos | Público |

---

## Vistas Frontend

| Vista | Archivo | Descripción |
|-------|---------|-------------|
| Listado | `views/listado.php` | Grid de documentos |
| Detalle | `views/detalle.php` | Ficha completa |
| Buscar | `views/buscar.php` | Buscador avanzado |
| Categorías | `views/categorias.php` | Lista de categorías |
| Subir | `views/subir.php` | Formulario de subida |
| Mis Guardados | `views/mis-guardados.php` | Favoritos del usuario |

---

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver documentos públicos | Público |
| Descargar archivos | Público (configurable) |
| Subir documentos | Usuario registrado |
| Guardar en favoritos | Usuario registrado |
| Comentar | Usuario registrado |
| Verificar documentos | Administrador |

---

## Flujo de Trabajo

```
1. SUBIR
   Usuario sube documento
   Estado: borrador
   ↓
2. ENVIAR A REVISIÓN
   Usuario solicita publicación
   Estado: revision
   ↓
3. VERIFICAR
   Admin verifica y aprueba
   Estado: publicado
   verificado: true
   ↓
4. CONSULTAR
   Usuarios pueden ver, descargar, comentar
   Se incrementan contadores
   ↓
5. ARCHIVAR (opcional)
   Admin archiva documento obsoleto
   Estado: archivado
```

---

## Notas de Implementación

- Búsqueda fulltext en MySQL para búsqueda rápida
- Los archivos se suben a wp-uploads
- Tipos de archivo restringidos por seguridad
- Los documentos no verificados solo los ve el autor
- Los favoritos permiten notas personales
- Los comentarios soportan hilos (parent_id)
- Integración con módulo seguimiento-denuncias (plantillas)

