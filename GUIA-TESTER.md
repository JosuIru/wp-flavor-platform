# Guía para personas que prueban Flavor Platform

¡Gracias por ayudar a probar **Flavor Platform**! 🙌

Este documento está pensado para que cualquier persona —sin necesidad de saber programar— pueda
instalar el plugin, recorrer sus funcionalidades y enviarnos **feedback, errores y cosas que no se
entiendan**. No hace falta que lo pruebes todo: con que recorras lo que te resulte interesante y nos
cuentes tu experiencia, ya nos ayudas muchísimo.

> **¿Qué es Flavor Platform?** Es un plugin de WordPress "todo en uno" para comunidades,
> cooperativas, asociaciones y redes ciudadanas. Reúne más de 70 módulos (socios, eventos, grupos de
> consumo, reservas, foros, chat, transparencia, crowdfunding…), un editor visual de páginas y apps
> móviles, todo dentro de un mismo panel.

---

## 1. Descargar el plugin

El código está en GitHub (repositorio **público**, no necesitas cuenta para descargarlo):

**👉 Repositorio: https://github.com/JosuIru/wp-flavor-platform**

### Opción recomendada: descargar la última versión estable (release)

Es la forma más fiable: un ZIP ya empaquetado y listo para instalar en WordPress.

1. Entra en la página de versiones: https://github.com/JosuIru/wp-flavor-platform/releases
2. En la última versión (la marcada como **Latest**), abre la sección **Assets**.
3. Descarga el archivo **`flavor-platform-X.Y.Z.zip`** (no el "Source code").

   - Enlace directo a la versión actual:
     https://github.com/JosuIru/wp-flavor-platform/releases/download/v3.5.13/flavor-platform-3.5.13.zip

Este ZIP ya trae la carpeta con el nombre correcto y sin archivos de desarrollo, así que se instala
directo en WordPress.

### Opción alternativa: última versión en desarrollo (rama `master`)

Solo si quieres probar lo más reciente, aún sin publicar como versión estable:

1. Entra en https://github.com/JosuIru/wp-flavor-platform
2. Pulsa el botón verde **`Code`** y elige **`Download ZIP`**.

   - Enlace directo: https://github.com/JosuIru/wp-flavor-platform/archive/refs/heads/master.zip

Descargarás `wp-flavor-platform-master.zip`. Ojo: este paquete incluye archivos de desarrollo y la
carpeta interna se llama `wp-flavor-platform-master`, así que puede que tengas que renombrarla a
`flavor-platform` (ver el aviso del paso 2).

---

## 2. Instalar en WordPress

Necesitas un WordPress de pruebas (versión **5.8 o superior**). **No lo instales en una web real en
producción** — usa un entorno de pruebas.

### Opción A — Subir el ZIP desde el panel (la más fácil)

1. Entra en tu WordPress como administrador.
2. Ve a **Plugins → Añadir nuevo → Subir plugin**.
3. Selecciona el ZIP que descargaste y pulsa **Instalar ahora**.
4. Cuando termine, pulsa **Activar**.

> ⚠️ Si descargaste el ZIP de la **rama `master`** (opción alternativa) y WordPress se queja del
> nombre de la carpeta, descomprime el archivo, renombra la carpeta a `flavor-platform`, vuelve a
> comprimirla y súbela. Con el ZIP de la **release** esto no debería pasar. Si te ocurre, **anótalo
> como feedback**: es justo el tipo de fricción que queremos detectar.

### Opción B — Copiar la carpeta manualmente

1. Descomprime el ZIP.
2. Copia la carpeta a `wp-content/plugins/` de tu WordPress (debería quedar como
   `wp-content/plugins/flavor-platform`).
3. En el panel, ve a **Plugins** y pulsa **Activar** en *Flavor Platform*.

✅ **Sabrás que está bien instalado** cuando, tras activarlo, aparezca un menú nuevo de **Flavor
Platform** (o similar) en la barra lateral del panel de WordPress.

---

## 3. Primeros pasos recomendados

No actives todos los módulos de golpe. Empieza poco a poco para que sea más fácil detectar de dónde
viene cada problema:

1. Entra en el panel del plugin y date una vuelta por el **Dashboard**.
2. Ve a la sección de **Módulos** y activa **solo 2 o 3** para empezar (por ejemplo: `socios`,
   `eventos`, `comunidades`).
3. Comprueba que aparece la pantalla o sección de cada módulo que activaste.
4. Crea algún contenido de prueba (un evento, un socio…) y mira si se guarda y se muestra bien.
5. Cuando te sientas cómoda/o, prueba más módulos o el editor visual de páginas.

---

## 4. Qué probar (checklist por áreas)

Marca lo que vayas probando. **No tienes que hacerlo todo.** Para cada cosa, fíjate en tres
preguntas: *¿Funciona? ¿Se entiende? ¿Es agradable de usar?*

### 🧭 Instalación y primeras impresiones
- [ ] El plugin se instala y activa sin errores.
- [ ] Aparece el menú del plugin en el panel.
- [ ] El Dashboard se entiende: sé qué puedo hacer desde aquí.
- [ ] Encuentro fácilmente dónde activar/desactivar módulos.

### 👥 Comunidad y personas
- [ ] Activar el módulo de **socios** y dar de alta una persona.
- [ ] Crear una **comunidad** o **colectivo**.
- [ ] Probar **foros**, **chat** o **encuestas** si los activas.
- [ ] Ver si los nombres y textos se entienden sin explicación previa.

### 📅 Eventos, cultura y ocio
- [ ] Crear un **evento** y comprobar que se muestra en la web.
- [ ] Probar **cursos**, **talleres**, **biblioteca** o **recetas**.

### 🛒 Economía social
- [ ] Probar **grupos de consumo** o **marketplace**.
- [ ] Mirar **transparencia**, **crowdfunding** o **presupuestos participativos**.

### 🏛️ Territorio y participación
- [ ] Probar **avisos municipales**, **trámites** o **mapa de actores**.

### 🌱 Ecología
- [ ] Probar **huertos urbanos**, **reciclaje**, **carpooling** o **energía comunitaria**.

### 🎨 Editor visual de páginas (Visual Builder Pro)
- [ ] Crear una página con el editor visual.
- [ ] Probar los presets de diseño (`modern`, `community`, `eco`…).
- [ ] Ver si los bloques que muestran datos (eventos, socios…) se ven correctos.

### 🔐 Permisos y roles
- [ ] Crear un usuario que **no** sea administrador y comprobar qué ve y qué no.
- [ ] Detectar si alguien sin permisos puede ver algo que no debería (¡importante!).

### 📱 Web pública (lo que ve un visitante)
- [ ] Navegar la web como visitante anónimo (modo incógnito del navegador).
- [ ] Probar en **móvil** además de en ordenador.
- [ ] Comprobar que no aparecen errores raros, textos en inglés sueltos o secciones rotas.

---

## 5. Cómo enviarnos el feedback

Esta es la parte **más importante**. Cuanto más concreto, mejor. Hay dos formas:

### Forma rápida (recomendada): plantilla por cada cosa

Copia esta plantilla y rellénala una vez por cada problema, duda o sugerencia. Puedes pegarlas todas
en un mismo documento o mensaje:

```
─────────────────────────────────────────
TIPO:        🐛 Bug  /  ❓ No lo entiendo  /  💡 Sugerencia  /  👍 Me gusta
DÓNDE:       (módulo, pantalla o página. Ej: "Módulo Eventos → crear evento")
QUÉ PASÓ:    (describe qué hiciste y qué ocurrió)
QUÉ ESPERABA: (qué creías que iba a pasar)
GRAVEDAD:    🔴 Me bloquea  /  🟠 Molesta pero puedo seguir  /  🟢 Detalle menor
CAPTURA:     (adjunta imagen si puedes)
DISPOSITIVO: (ordenador / móvil, navegador y, si lo sabes, versión de WordPress)
─────────────────────────────────────────
```

**Ejemplo rellenado:**

```
─────────────────────────────────────────
TIPO:        🐛 Bug
DÓNDE:       Módulo Eventos → crear evento
QUÉ PASÓ:    Al guardar un evento con fecha de mañana, sale una página en blanco.
QUÉ ESPERABA: Que me volviera a la lista de eventos con el evento creado.
GRAVEDAD:    🔴 Me bloquea
CAPTURA:     captura-evento-blanco.png
DISPOSITIVO: Portátil, Chrome, WordPress 6.4
─────────────────────────────────────────
```

### Consejos para que tu feedback sea útil

- **Cuenta los pasos exactos** para reproducir el problema (1, 2, 3…). Si nosotros podemos repetirlo,
  podemos arreglarlo.
- **Las capturas de pantalla valen oro.** Si puedes, marca con un círculo o flecha lo que falla.
- **Lo que no entiendes también es un fallo.** Si una pantalla te confunde, anótalo: el objetivo es
  que sea fácil de usar para cualquiera.
- **No filtres "tonterías".** Un texto mal escrito, un botón que no se ve, algo en inglés… todo suma.

### ¿Dónde lo envío?

- Por el canal que hayamos acordado (correo, mensaje, documento compartido).
- Si tienes cuenta de GitHub y te animas, puedes abrir una incidencia ("issue") directamente en el
  repositorio: https://github.com/JosuIru/wp-flavor-platform/issues — pero **no es obligatorio**, con
  enviarnos la plantilla rellenada es más que suficiente.

---

## 6. Avisos importantes

- 🧪 Es **software en pruebas**: pueden aparecer errores. ¡Justo por eso estás aquí!
- 💾 Usa una instalación de **pruebas**, nunca una web real con datos importantes.
- 🚫 Si algo da miedo o parece destructivo (borrar datos, etc.), **mejor pregunta antes** de
  confirmar.

---

¡Gracias de nuevo por tu tiempo! Cada error que detectes y cada "esto no lo entiendo" que nos cuentes
hace que la plataforma sea mejor para todo el mundo. 💛
