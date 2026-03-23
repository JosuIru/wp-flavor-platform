# Módulo: Economía de Suficiencia

> Promueve un modelo económico basado en "suficiente" vs "máximo"

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `economia_suficiencia` |
| **Versión** | 1.0.0+ |
| **Categoría** | Economía / Filosofía |
| **Rol** | Transversal |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`

### Principios Gailu

- **Implementa**: `aprendizaje`, `economia_local`
- **Contribuye a**: `resiliencia`, `autonomia`

### Módulos Relacionados

- `grupos_consumo` - Enseña/soporta
- `marketplace` - Enseña/soporta
- `comunidades` - Enseña/soporta

---

## Descripción

Módulo transversal que promueve un modelo económico basado en la suficiencia en lugar del consumismo. Basado en la teoría de necesidades humanas de Max-Neef, ayuda a los usuarios a identificar sus necesidades reales y adoptar prácticas de consumo consciente.

### Características Principales

- **Evaluación de Necesidades**: Basada en Max-Neef
- **Compromisos de Suficiencia**: Metas personales
- **Biblioteca de Objetos**: Compartir en lugar de comprar
- **Reflexiones**: Diario personal de suficiencia
- **Niveles de Progreso**: Gamificación del camino
- **Comunidad**: Estadísticas colectivas

---

## Categorías de Necesidades (Max-Neef)

| Categoría | Nombre | Descripción | Color |
|-----------|--------|-------------|-------|
| `subsistencia` | Subsistencia | Alimentación, salud, vivienda, trabajo | #e74c3c |
| `proteccion` | Protección | Seguridad, cuidado, prevención, derechos | #3498db |
| `afecto` | Afecto | Amor, familia, amistades, intimidad | #e91e63 |
| `entendimiento` | Entendimiento | Educación, conocimiento, curiosidad | #9c27b0 |
| `participacion` | Participación | Comunidad, decisiones colectivas | #ff9800 |
| `ocio` | Ocio | Descanso, tiempo libre, juego | #4caf50 |
| `creacion` | Creación | Creatividad, trabajo significativo | #00bcd4 |
| `identidad` | Identidad | Pertenencia, autoestima, coherencia | #795548 |
| `libertad` | Libertad | Autonomía, rebeldía, diferencia | #607d8b |

---

## Tipos de Compromiso

| Tipo | Nombre | Descripción |
|------|--------|-------------|
| `reducir_consumo` | Reducir consumo | Comprar menos, usar lo que tengo |
| `compartir` | Compartir recursos | Prestar, compartir, donar |
| `reparar` | Reparar | Arreglar en vez de comprar nuevo |
| `local` | Consumir local | Priorizar comercio de proximidad |
| `etico` | Consumo ético | Productos justos y sostenibles |
| `tiempo` | Más tiempo, menos dinero | Priorizar tiempo libre |
| `autoconsumo` | Autoconsumo | Producir lo que necesito |
| `desconectar` | Desconectar | Reducir dependencia tecnológica |

---

## Niveles de Suficiencia

| Nivel | Nombre | Puntos Mín. | Color |
|-------|--------|-------------|-------|
| `explorando` | Explorando | 0 | #95a5a6 |
| `consciente` | Consciente | 50 | #3498db |
| `practicante` | Practicante | 150 | #27ae60 |
| `mentor` | Mentor | 300 | #9b59b6 |
| `sabio` | Sabio/a | 500 | #f39c12 |

---

## Custom Post Types

| CPT | Nombre | Descripción |
|-----|--------|-------------|
| `es_reflexion` | Reflexiones | Diario personal de suficiencia |
| `es_compromiso` | Compromisos | Metas de suficiencia |
| `es_practica` | Prácticas | Registro de acciones |
| `es_recurso` | Biblioteca de Objetos | Objetos para compartir |

---

## REST API Endpoints

| Endpoint | Método | Auth | Descripción |
|----------|--------|------|-------------|
| `/flavor/v1/economia-suficiencia/reflexiones` | GET | Usuario | Mis reflexiones |
| `/flavor/v1/economia-suficiencia/compromisos` | GET | Usuario | Mis compromisos |
| `/flavor/v1/economia-suficiencia/comunidad` | GET | Público | Stats comunidad |
| `/flavor/v1/economia-suficiencia/biblioteca` | GET | Público | Biblioteca objetos |

### Parámetros de Biblioteca

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `categoria` | string | Filtrar por categoría |
| `limite` | int | Resultados (default: 20) |

---

## Shortcodes

| Shortcode | Alias | Descripción |
|-----------|-------|-------------|
| `[suficiencia_intro]` | `[flavor_suficiencia_intro]` | Introducción al módulo |
| `[suficiencia_evaluacion]` | `[flavor_suficiencia_evaluacion]` | Test de necesidades |
| `[suficiencia_compromisos]` | `[flavor_suficiencia_compromisos]` | Gestión de compromisos |
| `[suficiencia_biblioteca]` | `[flavor_suficiencia_biblioteca]` | Biblioteca de objetos |
| `[suficiencia_mi_camino]` | `[flavor_suficiencia_mi_camino]` | Mi progreso personal |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `es_guardar_reflexion` | Guardar reflexión | Usuario |
| `es_hacer_compromiso` | Crear compromiso | Usuario |
| `es_registrar_practica` | Registrar práctica | Usuario |
| `es_compartir_recurso` | Añadir a biblioteca | Usuario |
| `es_solicitar_prestamo` | Solicitar préstamo | Usuario |
| `es_evaluar_necesidades` | Evaluar necesidades | Usuario |

---

## Dashboard Widget

El módulo registra un widget transversal que muestra:

- Mi nivel actual de suficiencia
- Compromisos activos
- Próximas acciones
- Estadísticas de la comunidad

### Contextos de Dashboard

| Tipo | Contextos |
|------|-----------|
| Cliente | consumo, suficiencia, aprendizaje, comunidad |
| Admin | consumo, aprendizaje, admin |

---

## Flujo de Usuario

```
1. EXPLORAR
   Usuario descubre el concepto de suficiencia
   Nivel: Explorando
   ↓
2. EVALUAR
   Completa test de necesidades (Max-Neef)
   Identifica áreas de mejora
   ↓
3. COMPROMETERSE
   Elige compromisos de suficiencia
   Define metas concretas
   ↓
4. PRACTICAR
   Registra acciones diarias
   Gana puntos
   ↓
5. COMPARTIR
   Añade objetos a la biblioteca
   Comparte reflexiones
   ↓
6. INSPIRAR
   Alcanza nivel Mentor/Sabio
   Ayuda a otros en su camino
```

---

## Biblioteca de Objetos

Sistema de préstamo de objetos entre miembros:

| Campo | Descripción |
|-------|-------------|
| `titulo` | Nombre del objeto |
| `descripcion` | Descripción y condiciones |
| `categoria` | Categoría del objeto |
| `tipo` | Préstamo, regalo, intercambio |
| `autor` | Usuario propietario |
| `imagen` | Foto del objeto |

---

## Integración Transversal

Como módulo transversal, se integra en otros módulos:

- **Grupos de Consumo**: Reflexiones sobre consumo
- **Marketplace**: Cuestionamiento antes de comprar
- **Comunidades**: Compromisos colectivos

---

## Notas de Implementación

- Prioridad dashboard transversal: 30
- Los CPT no son públicos (privacidad)
- Puntos calculados por compromisos cumplidos
- Inspirado en decrecimiento y buen vivir
- Gamificación suave para motivar sin consumismo
- Las reflexiones son privadas por defecto
