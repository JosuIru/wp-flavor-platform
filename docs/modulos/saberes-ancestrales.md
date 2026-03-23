# Módulo: Saberes Ancestrales

> Preservación y transmisión del conocimiento tradicional comunitario

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `saberes_ancestrales` |
| **Versión** | 1.0.0+ |
| **Categoría** | Cultura / Aprendizaje |
| **Rol** | Transversal |
| **Disponible en App** | Cliente |

### Principios Gailu

- `aprendizaje` - Compartir conocimientos
- `cuidados` - Honrar a los mayores

### Contribuye a

- `cohesion` - Conectar generaciones
- `resiliencia` - Preservar patrimonio cultural

### Valoración de Conciencia: 88/100

- `madurez_ciclica` (0.30) - Ciclos generacionales
- `conciencia_fundamental` (0.25) - Sabiduría acumulada
- `interdependencia_radical` (0.20) - Conexión intergeneracional
- `valor_intrinseco` (0.15) - Valor del conocimiento tradicional
- `abundancia_organizable` (0.10) - Organizar y transmitir saberes

---

## Descripción

Preserva y transmite el conocimiento tradicional de la comunidad, conectando generaciones y honrando la sabiduría de los mayores. Permite documentar saberes, organizar talleres de transmisión y mantener un catálogo vivo de conocimiento ancestral.

### Características Principales

- **Catálogo de Saberes**: Documentación de conocimientos tradicionales
- **Portadores/Guardianes**: Personas que poseen los saberes
- **Talleres de Transmisión**: Aprendizaje práctico
- **Mentorías**: Acompañamiento uno a uno
- **Círculos de Saberes**: Encuentros grupales
- **Agradecimientos**: Sistema de reconocimiento

---

## Categorías de Saber

| Categoría | Nombre | Descripción | Color |
|-----------|--------|-------------|-------|
| `agricultura` | Agricultura tradicional | Cultivos, ciclos lunares, semillas antiguas | #8B4513 |
| `artesania` | Artesanía | Oficios manuales, tejidos, cerámica | #D2691E |
| `medicina` | Medicina natural | Plantas medicinales, remedios caseros | #228B22 |
| `gastronomia` | Gastronomía tradicional | Recetas, conservas, fermentos | #FF6347 |
| `tradiciones` | Tradiciones y rituales | Fiestas, ceremonias, costumbres | #9932CC |
| `construccion` | Construcción tradicional | Técnicas constructivas ancestrales | #CD853F |
| `musica` | Música y danza | Canciones, instrumentos, bailes | #4169E1 |
| `narracion` | Narración oral | Cuentos, leyendas, refranes | #708090 |
| `oficios` | Oficios perdidos | Herrería, carpintería, cestería | #696969 |

---

## Tipos de Transmisión

| Tipo | Nombre | Descripción |
|------|--------|-------------|
| `documentacion` | Documentación | Registro escrito, fotos, vídeos |
| `taller` | Taller práctico | Aprendizaje presencial guiado |
| `mentoria` | Mentoría | Acompañamiento uno a uno |
| `circulo` | Círculo de saberes | Encuentro grupal de intercambio |

---

## Custom Post Types

### `sa_saber` - Saberes Documentados

| Campo | Descripción |
|-------|-------------|
| Título | Nombre del saber |
| Contenido | Descripción completa |
| Autor | Quien documentó |
| Thumbnail | Imagen representativa |

**Meta Fields:**
- `_sa_origen` - Origen/tradición
- `_sa_portador` - Nombre del portador
- `_sa_documentado_por` - Usuario que documentó
- `_sa_fecha_documentacion` - Fecha de documentación
- `_sa_agradecimientos` - Contador de agradecimientos

### `sa_portador` - Portadores de Saberes

Personas mayores o sabias que poseen conocimientos tradicionales.

### `sa_taller` - Talleres de Transmisión

| Meta | Descripción |
|------|-------------|
| `_sa_fecha` | Fecha del taller |
| `_sa_plazas` | Plazas disponibles |
| `_sa_inscritos` | Array de usuarios inscritos |

### `sa_solicitud` - Solicitudes de Aprendizaje

| Meta | Descripción |
|------|-------------|
| `_sa_saber_id` | Saber que quiere aprender |
| `_sa_mensaje` | Mensaje del solicitante |
| `_sa_estado` | Estado de la solicitud |

---

## Taxonomías

### `sa_categoria`

Categorías de saber (agricultura, artesanía, medicina, etc.)

### `sa_origen`

Origen geográfico o cultural del saber.

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/`

| Endpoint | Método | Descripción | Auth |
|----------|--------|-------------|------|
| `/saberes` | GET | Listar saberes | Público |
| `/saberes/{id}` | GET | Obtener saber | Público |
| `/saberes` | POST | Registrar saber | Usuario |
| `/saberes/talleres` | GET | Listar talleres | Público |
| `/saberes/talleres/{id}/inscribirse` | POST | Inscribirse en taller | Usuario |
| `/saberes/mis-aprendizajes` | GET | Mis aprendizajes | Usuario |

### GET `/saberes`

**Parámetros:**
- `categoria` - Filtrar por categoría
- `limite` - Número máximo (default: 20)

**Respuesta:**
```json
{
  "success": true,
  "total": 42,
  "saberes": [
    {
      "id": 123,
      "titulo": "Cultivo de patatas de siembra",
      "descripcion": "Técnicas tradicionales...",
      "categoria": "agricultura",
      "portador": "María Etxeberri",
      "origen": "Baztan",
      "agradecimientos": 15,
      "fecha": "2026-02-15"
    }
  ],
  "categorias": ["agricultura", "artesania", "medicina", ...]
}
```

### POST `/saberes`

**Body:**
```json
{
  "titulo": "Nombre del saber",
  "descripcion": "Descripción completa",
  "categoria": "artesania",
  "portador": "Nombre del portador",
  "origen": "Lugar de origen"
}
```

**Respuesta:**
```json
{
  "success": true,
  "mensaje": "Saber documentado. Será revisado antes de publicarse.",
  "id": 124
}
```

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[saberes_catalogo]` | Catálogo de saberes |
| `[saberes_portadores]` | Lista de portadores |
| `[saberes_talleres]` | Talleres disponibles |
| `[saberes_compartir]` | Formulario para documentar |
| `[saberes_mis_aprendizajes]` | Mis aprendizajes |
| `[flavor_saberes_catalogo]` | Alias con prefijo |
| `[flavor_saberes_compartir]` | Alias con prefijo |
| `[flavor_saberes_talleres]` | Alias con prefijo |

---

## Configuración

El módulo usa la configuración base del sistema con los siguientes contextos:

| Contexto | Dashboard |
|----------|-----------|
| `aprendizaje` | Cliente y Admin |
| `comunidad` | Cliente |
| `cultura` | Cliente y Admin |
| `saberes` | Cliente |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `sa_registrar_saber` | Documentar nuevo saber | Usuario |
| `sa_solicitar_aprendizaje` | Solicitar aprender un saber | Usuario |
| `sa_inscribirse_taller` | Inscribirse en taller | Usuario |
| `sa_proponer_taller` | Proponer nuevo taller | Usuario |
| `sa_agradecer_saber` | Agradecer un saber | Usuario |

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Catálogo | Listado de saberes | No |
| Guardianes | Portadores de saberes | No |
| Talleres | Talleres disponibles | No |
| Documentar | Formulario para añadir | Sí |
| Aprender | Mis aprendizajes | Sí |
| Foro | Foro contextual | No |
| Chat | Chat del saber | Sí |
| Multimedia | Archivos del saber | No |
| Red social | Actividad social | Sí |

---

## Integraciones

### Acepta contenido de:

- `recetas` - Recetas tradicionales
- `biblioteca` - Documentación
- `multimedia` - Fotos y vídeos
- `podcast` - Entrevistas a portadores
- `videos` - Material audiovisual

### Enseña a módulos:

- `comunidades`
- `talleres`
- `cursos`

---

## Páginas de Administración

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `saberes-dashboard` | Panel principal |
| Saberes | `saberes-listado` | Listado de saberes |
| Talleres | `saberes-talleres` | Gestión de talleres |
| Portadores | `saberes-portadores` | Gestión de portadores |

---

## Estadísticas

### Estadísticas Generales

```json
{
  "saberes_total": 42,
  "portadores": 15,
  "talleres_proximos": 3,
  "saberes_por_categoria": [
    {"categoria": "agricultura", "total": 12},
    {"categoria": "artesania", "total": 8}
  ]
}
```

### Estadísticas de Usuario

```json
{
  "saberes_documentados": 5,
  "talleres_inscritos": 2,
  "solicitudes_pendientes": 1
}
```

---

## Flujo de Documentación

```
1. DOCUMENTAR
   Usuario documenta un saber
   Estado: pending (requiere revisión)
   ↓
2. REVISAR
   Admin revisa y aprueba
   Estado: publish
   ↓
3. AGRADECER
   Otros usuarios agradecen
   Se incrementa contador
   ↓
4. TRANSMITIR
   Se organizan talleres
   ↓
5. APRENDER
   Usuarios se inscriben y aprenden
```

---

## Notas de Implementación

- Los saberes requieren revisión antes de publicarse (status `pending`)
- Los portadores son personas identificadas, no usuarios del sistema
- Los talleres tienen plazas limitadas
- El sistema de agradecimientos es acumulativo
- Integración con dashboard unificado de usuario
- Usa trait `Flavor_Module_Integration_Consumer` para recibir contenido
- Rol transversal con prioridad 40 en dashboard

