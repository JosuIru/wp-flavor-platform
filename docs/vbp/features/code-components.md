# Code Components

Crear componentes personalizados con código HTML/CSS/JS.

## Qué es

Permite crear bloques custom escribiendo código directamente:
- HTML personalizado
- CSS con scoping automático
- JavaScript interactivo
- Integración con APIs externas

## Cómo crear

1. Añadir bloque "Code Component"
2. Abrir editor de código (doble clic o `Enter`)
3. Escribir HTML/CSS/JS
4. Guardar

## Editor de código

El editor incluye:
- Syntax highlighting
- Autocompletado
- Emmet abbreviations
- Validación de errores
- Preview en tiempo real

## Estructura

### HTML
```html
<div class="mi-componente">
    <h3>{{ title }}</h3>
    <p>{{ description }}</p>
    <button onclick="handleClick()">Acción</button>
</div>
```

### CSS (scoped)
```css
/* Se aplica solo a este componente */
.mi-componente {
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
}

.mi-componente h3 {
    color: var(--primary-color);
}
```

### JavaScript
```javascript
function handleClick() {
    alert('¡Clic!');
}

// Acceso a datos del componente
console.log(this.props.title);
```

## Props editables

Definir props que aparecen en el Inspector:

```html
<!--
@prop title: string = "Título por defecto"
@prop description: string = "Descripción..."
@prop showButton: boolean = true
@prop buttonColor: color = "#007bff"
-->

<div class="mi-componente">
    <h3>{{ title }}</h3>
    <p>{{ description }}</p>
    {% if showButton %}
    <button style="background: {{ buttonColor }}">Acción</button>
    {% endif %}
</div>
```

## Tipos de props

| Tipo | Descripción | Control en Inspector |
|------|-------------|---------------------|
| `string` | Texto | Input de texto |
| `number` | Número | Input numérico |
| `boolean` | Sí/No | Toggle |
| `color` | Color | Color picker |
| `image` | Imagen | Selector de medios |
| `select` | Opciones | Dropdown |
| `richtext` | Texto rico | Editor WYSIWYG |

## Ejemplos

### Contador
```html
<!--
@prop initialValue: number = 0
-->
<div class="contador">
    <button onclick="decrement()">-</button>
    <span id="value">{{ initialValue }}</span>
    <button onclick="increment()">+</button>
</div>

<style>
.contador {
    display: flex;
    gap: 10px;
    align-items: center;
}
button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
</style>

<script>
let count = {{ initialValue }};
function increment() {
    document.getElementById('value').textContent = ++count;
}
function decrement() {
    document.getElementById('value').textContent = --count;
}
</script>
```

### Card con API
```html
<!--
@prop userId: number = 1
-->
<div class="user-card" id="card-{{ _id }}">
    <img id="avatar-{{ _id }}" src="" alt="Avatar">
    <h4 id="name-{{ _id }}">Cargando...</h4>
    <p id="email-{{ _id }}"></p>
</div>

<script>
fetch('https://jsonplaceholder.typicode.com/users/{{ userId }}')
    .then(r => r.json())
    .then(user => {
        document.getElementById('name-{{ _id }}').textContent = user.name;
        document.getElementById('email-{{ _id }}').textContent = user.email;
    });
</script>
```

## Guardar como bloque

1. Crear Code Component
2. Clic derecho → "Guardar como bloque"
3. Dar nombre e icono
4. Aparece en la biblioteca de bloques

## Seguridad

- JavaScript se ejecuta en sandbox
- No acceso a cookies/localStorage del editor
- APIs externas requieren CORS
- Sanitización automática de HTML

## Casos de uso

- Widgets personalizados
- Integraciones con APIs externas
- Componentes interactivos complejos
- Prototipos rápidos
- Embeds custom
