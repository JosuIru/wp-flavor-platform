# AI Layout Assistant (Asistente de Diseno con IA)

Sistema de asistencia de diseno que usa inteligencia artificial para sugerir layouts, calcular espaciados, proponer colores complementarios y generar variantes de diseno.

## Descripcion

El AI Layout Assistant permite describir en lenguaje natural lo que quieres crear y genera automaticamente la estructura de bloques. Tambien puede analizar tu diseno actual y sugerir mejoras.

## Abrir AI Layout

- Paleta de comandos: "AI Layout" o "Asistente IA"
- Toolbar: Click en icono de IA
- Atajo: definido en configuracion

## Tabs del Panel

| Tab | Descripcion |
|-----|-------------|
| **Generate** | Generar layout desde descripcion |
| **Spacing** | Auto-espaciado inteligente |
| **Colors** | Paletas de colores complementarios |
| **Variants** | Generar variantes de diseno |
| **Analyze** | Analizar diseno actual |

## Generar Layout

### Uso Basico

1. Abre el tab "Generate"
2. Escribe una descripcion:
   - "Crea un hero section con titulo, subtitulo y boton CTA"
   - "Genera una seccion de pricing con 3 planes"
   - "Haz un formulario de contacto con campos nombre, email y mensaje"
3. Click en "Generar"
4. El layout se inserta en el canvas

### Ejemplos de Prompts

```
"Hero section minimalista con fondo oscuro"

"Grid de 3 columnas con cards de producto"

"Footer con 4 columnas: logo, enlaces, contacto, redes sociales"

"Seccion de testimonios con carrusel"

"Tabla de comparacion de features"

"FAQ con acordeones"
```

### Opciones de Generacion

- **Contexto**: Informacion adicional (industria, audiencia)
- **Estilo**: Minimalista, corporativo, creativo
- **Cantidad de bloques**: Limitar complejidad

## Auto-Spacing

Ajusta automaticamente el espaciado siguiendo una escala de 8px:

### Uso

1. Selecciona elementos a espaciar
2. Abre tab "Spacing"
3. Click en "Auto-space"
4. El sistema aplica espaciado coherente

### Configuracion

- **Grid Base**: 8px por defecto
- **Escala**: 8, 16, 24, 32, 48, 64, 96
- **Modo**: Dentro de contenedor / Entre hermanos

### Algoritmo

El sistema:
1. Analiza la jerarquia de elementos
2. Detecta relaciones (hermanos, padre-hijo)
3. Aplica espaciado basado en importancia
4. Respeta proporciones existentes

## Colores Complementarios

### Generar Paleta

1. Abre tab "Colors"
2. Selecciona un color base
3. Elige esquema de color
4. Click en "Generar"

### Esquemas de Color

| Esquema | Descripcion |
|---------|-------------|
| **Complementary** | Color opuesto en el circulo cromatico |
| **Analogous** | Colores adyacentes |
| **Triadic** | 3 colores equidistantes |
| **Split-Complementary** | Complementario dividido |
| **Monochromatic** | Variaciones del mismo color |

### Aplicar Colores

- Click en color para copiar
- Arrastra a elemento para aplicar
- Guarda como Design Token

## Generar Variantes

Crea variaciones de tu diseno actual:

### Uso

1. Selecciona seccion/componente
2. Abre tab "Variants"
3. Elige tipo de variacion:
   - Layout diferente
   - Colores diferentes
   - Espaciado diferente
   - Todo
4. Click en "Generar Variantes"

### Tipos de Variantes

- **Layout**: Misma estructura, diferente disposicion
- **Style**: Mismo layout, diferente estilo visual
- **Scale**: Diferentes tamanos (compact, normal, spacious)

## Analizar Diseno

### Uso

1. Abre tab "Analyze"
2. Click en "Analizar"
3. Recibe sugerencias de mejora

### Analisis Incluye

- **Consistencia**: Espaciados, colores, tipografia
- **Accesibilidad**: Contraste, tamanos de texto
- **Jerarquia**: Claridad visual
- **Balance**: Distribucion de elementos

### Sugerencias

El sistema sugiere:
- "El contraste del boton es bajo, considera usar #2563eb"
- "El espaciado entre secciones es inconsistente"
- "El heading-2 es mas grande que heading-1"

## API JavaScript

### Acceder al Store

```javascript
const aiLayout = Alpine.store('vbpAILayout');
```

### Propiedades

```javascript
aiLayout.isOpen           // Panel abierto
aiLayout.activeTab        // Tab activo
aiLayout.loading          // Cargando
aiLayout.error            // Error actual
aiLayout.prompt           // Input del usuario
aiLayout.aiAvailable      // IA disponible
aiLayout.fallbackEnabled  // Fallback activo
```

### Abrir/Cerrar

```javascript
aiLayout.open('generate');  // Abrir en tab especifico
aiLayout.close();
aiLayout.toggle();
aiLayout.setTab('colors');
```

### Generar Layout

```javascript
aiLayout.generateLayout('Hero section con imagen de fondo')
    .then(blocks => {
        console.log('Bloques generados:', blocks);
    });
```

### Auto-Spacing

```javascript
// Aplicar a seleccion
aiLayout.applyAutoSpacing(['el_1', 'el_2', 'el_3']);

// Obtener sugerencias
const suggestions = aiLayout.getSpacingSuggestions(['el_1', 'el_2']);
```

### Colores

```javascript
// Generar paleta
const palette = aiLayout.suggestColors('#3b82f6', 'complementary');
// Retorna: ['#3b82f6', '#f6823b', ...]

// Obtener variaciones
const variations = aiLayout.getColorVariations('#3b82f6');
// Retorna: { light: [...], dark: [...] }
```

### Variantes

```javascript
aiLayout.generateVariants('el_section', 'layout')
    .then(variants => {
        console.log('Variantes:', variants);
    });
```

### Analizar

```javascript
aiLayout.analyzeDesign()
    .then(result => {
        console.log('Puntuacion:', result.score);
        console.log('Sugerencias:', result.suggestions);
    });
```

## API REST

### Estado

```http
GET /wp-json/flavor-vbp/v1/ai/layout/status
```

### Generar Layout

```http
POST /wp-json/flavor-vbp/v1/ai/layout/generate
Content-Type: application/json

{
    "prompt": "Hero section con CTA",
    "context": {
        "industry": "tech",
        "style": "modern"
    }
}
```

### Auto-Spacing

```http
POST /wp-json/flavor-vbp/v1/ai/layout/auto-spacing
Content-Type: application/json

{
    "elements": [...],
    "gridBase": 8
}
```

### Colores

```http
POST /wp-json/flavor-vbp/v1/ai/layout/colors
Content-Type: application/json

{
    "baseColor": "#3b82f6",
    "scheme": "complementary"
}
```

### Generar Variantes

```http
POST /wp-json/flavor-vbp/v1/ai/layout/variants
Content-Type: application/json

{
    "elementId": "el_section",
    "type": "layout"
}
```

### Analizar

```http
POST /wp-json/flavor-vbp/v1/ai/layout/analyze
Content-Type: application/json

{
    "elements": [...],
    "postId": 123
}
```

## Templates Predefinidos

El sistema incluye templates para generacion rapida:

| Template | Descripcion |
|----------|-------------|
| hero-basic | Hero con titulo y CTA |
| hero-image | Hero con imagen de fondo |
| features-grid | Grid de 3 features |
| pricing-table | 3 planes de precio |
| testimonials | Seccion de testimonios |
| contact-form | Formulario de contacto |
| faq | Preguntas frecuentes |
| cta-banner | Banner de CTA |
| team-section | Equipo con fotos |
| stats-counter | Contadores animados |

### Usar Template

```javascript
aiLayout.loadTemplate('hero-basic').then(blocks => {
    // Insertar en canvas
});
```

## Configuracion

### API Key de IA

Si usas IA externa (OpenAI, Claude):

```php
// wp-config.php
define('VBP_AI_API_KEY', 'tu-api-key');
define('VBP_AI_PROVIDER', 'openai'); // o 'anthropic'
```

### Fallback sin IA

Si no hay API configurada, el sistema usa:
- Templates predefinidos
- Reglas de espaciado basadas en escala
- Algoritmos de color matematicos

## Consideraciones

- La generacion de layouts depende de la API de IA
- El auto-spacing funciona sin IA
- Los colores se calculan matematicamente
- El analisis basico no requiere IA

## Limitaciones

- Prompts muy vagos generan resultados genericos
- La IA puede no entender contextos muy especificos
- Variantes complejas pueden requerir ajustes manuales
- El analisis de accesibilidad es basico

## Solucionar Problemas

### "AI not available"

1. Verifica que la API key esta configurada
2. Comprueba la conexion a internet
3. Revisa limites de uso de la API

### Layouts generados no son correctos

1. Se mas especifico en el prompt
2. Agrega contexto (industria, estilo)
3. Usa templates como base y modifica

### Auto-spacing da resultados extraaos

1. Verifica que los elementos estan correctamente anidados
2. Ajusta el grid base si es necesario
3. Aplica a grupos mas pequenos
