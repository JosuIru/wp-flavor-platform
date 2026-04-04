# Guiones para Video Tutoriales - Flavor Platform

> **Herramienta recomendada**: Recordly (`/home/josu/Descargas/Recordly-linux-x64.AppImage`)
> **Formato**: MP4 1920x1080 o 1280x720
> **Subir a**: YouTube (sin listar) y agregar URLs en `class-guided-tours.php`

---

## Video 1: Primeros Pasos (3 min)

**ID del tour**: `tour_primeros_pasos`
**Archivo a editar**: `admin/class-guided-tours.php` linea 111

### Configuracion previa
- WordPress limpio con Flavor Platform instalado
- Usuario administrador nuevo (para simular primera vez)
- Tema flavor-starter activo

---

### ESCENA 1: Bienvenida (0:00 - 0:25)

**Pantalla**: Dashboard de Flavor Platform

**Narrar**:
> "Bienvenido a Flavor Platform. En los proximos 3 minutos aprenderas lo basico para configurar tu plataforma comunitaria. Ya seas una asociacion, cooperativa, ayuntamiento o empresa, Flavor se adapta a tus necesidades."

**Acciones**:
1. Mostrar el dashboard completo
2. Scroll suave para mostrar los widgets

---

### ESCENA 2: Menu de Control (0:25 - 0:50)

**Pantalla**: Menu lateral de WordPress

**Narrar**:
> "Todo lo que necesitas esta en este menu lateral. El Dashboard te muestra el estado general, Modulos te permite activar funciones, y Configuracion para personalizar todo a tu gusto."

**Acciones**:
1. Hover sobre el menu "Flavor Platform"
2. Mostrar los submenus que se despliegan
3. Clic en Dashboard
4. Clic en Modulos (solo mostrar, no entrar)
5. Clic en Configuracion (solo mostrar)

---

### ESCENA 3: Tipo de Organizacion (0:50 - 1:20)

**Pantalla**: Seccion de perfil activo en Dashboard

**Narrar**:
> "Flavor se adapta a ti. Puedes elegir entre diferentes perfiles: asociacion vecinal, ayuntamiento, cooperativa, coworking... Cada perfil activa automaticamente los modulos mas relevantes para tu caso."

**Acciones**:
1. Mostrar la tarjeta de perfil activo
2. Si hay selector de perfiles, mostrar las opciones
3. Explicar visualmente que cada perfil tiene iconos diferentes

---

### ESCENA 4: Activar Modulos (1:20 - 1:50)

**Pantalla**: Pagina de Modulos

**Narrar**:
> "Los modulos son las funcionalidades de tu plataforma: Eventos, Cursos, Reservas, Miembros, Chat IA... Activa solo los que necesites. Siempre puedes anadir mas despues. Menos modulos significa mejor rendimiento."

**Acciones**:
1. Navegar a la pagina de Modulos
2. Mostrar la lista de modulos disponibles
3. Activar/desactivar un modulo como ejemplo
4. Mostrar el toggle de activacion

---

### ESCENA 5: Personalizar Colores (1:50 - 2:20)

**Pantalla**: Pagina de Diseno

**Narrar**:
> "Adapta la apariencia a tu marca. Puedes cambiar colores, tipografias y logo. Tu plataforma, tu estilo. Los cambios se aplican en todo el sitio y las apps moviles."

**Acciones**:
1. Navegar a Diseno
2. Mostrar el selector de colores
3. Cambiar un color como ejemplo
4. Mostrar la vista previa en vivo

---

### ESCENA 6: Boton de Ayuda (2:20 - 2:45)

**Pantalla**: Cualquier pagina del admin mostrando el boton flotante

**Narrar**:
> "Tienes dudas? Este boton flotante de ayuda esta siempre disponible. Te da acceso a tours guiados como este, documentacion y recursos. No dudes en usarlo cuando lo necesites."

**Acciones**:
1. Localizar el boton de ayuda flotante (esquina inferior)
2. Hacer clic para mostrar el menu desplegable
3. Mostrar la lista de tours disponibles

---

### ESCENA 7: Despedida (2:45 - 3:00)

**Pantalla**: Dashboard

**Narrar**:
> "Ya conoces lo basico! Te recomendamos hacer el Tour del Dashboard para profundizar. O simplemente explora y descubre. Bienvenido a la comunidad Flavor!"

**Acciones**:
1. Volver al Dashboard
2. Mostrar acciones rapidas disponibles
3. Fade out

---

## Video 2: Tour del Dashboard (4 min)

**ID del tour**: `tour_dashboard`
**Archivo a editar**: `admin/class-guided-tours.php` linea 168

### Configuracion previa
- Varios modulos activos (eventos, socios, marketplace)
- Datos de ejemplo cargados
- Al menos 1 alerta pendiente

---

### ESCENA 1: Introduccion al Dashboard (0:00 - 0:20)

**Pantalla**: Dashboard completo

**Narrar**:
> "Este es tu centro de control principal. Aqui tienes una vision completa del estado de tu plataforma, metricas clave y accesos rapidos a las funciones mas importantes."

**Acciones**:
1. Mostrar el dashboard cargado
2. Panoramica general de todos los widgets

---

### ESCENA 2: Perfil de Aplicacion (0:20 - 0:45)

**Pantalla**: Widget de perfil activo

**Narrar**:
> "Esta tarjeta muestra que tipo de aplicacion tienes configurada: Asociacion, Ayuntamiento, Cooperativa... Cada perfil activa modulos y funciones especificas para tu caso de uso."

**Acciones**:
1. Senalar la tarjeta de perfil
2. Mostrar el icono y nombre del perfil
3. Si hay boton de cambiar, mostrarlo

---

### ESCENA 3: Panel de Metricas (0:45 - 1:15)

**Pantalla**: Grid de metricas

**Narrar**:
> "Estadisticas en tiempo real: usuarios registrados, modulos activos, conversaciones del chat IA, eventos proximos y actividad de la comunidad. De un vistazo sabes como va todo."

**Acciones**:
1. Recorrer cada metrica visualmente
2. Mostrar numeros y tendencias
3. Explicar que significan los iconos

---

### ESCENA 4: Semaforo de Salud (1:15 - 1:45)

**Pantalla**: Widget de estado del sistema

**Narrar**:
> "El semaforo de salud te indica el estado tecnico. Verde significa que todo funciona correctamente. Amarillo hay aspectos a revisar. Rojo son problemas que necesitan atencion inmediata."

**Acciones**:
1. Mostrar el indicador de salud
2. Si hay checks individuales, mostrarlos
3. Explicar cada estado con ejemplos visuales

---

### ESCENA 5: Centro de Alertas (1:45 - 2:10)

**Pantalla**: Widget de alertas

**Narrar**:
> "Notificaciones importantes que requieren tu atencion: nuevos usuarios pendientes de aprobar, modulos sin configurar, actualizaciones disponibles... Revisa este panel regularmente."

**Acciones**:
1. Mostrar lista de alertas
2. Hacer clic en una alerta para mostrar que lleva a la accion
3. Volver al dashboard

---

### ESCENA 6: Actividad Reciente (2:10 - 2:35)

**Pantalla**: Widget de actividad

**Narrar**:
> "Registro de las ultimas acciones en la plataforma: inscripciones, publicaciones, reservas, comentarios... Ideal para saber que esta pasando en tu comunidad sin revisar cada modulo."

**Acciones**:
1. Mostrar el feed de actividad
2. Scroll si hay mas elementos
3. Mostrar timestamps y usuarios

---

### ESCENA 7: Acciones Rapidas (2:35 - 3:00)

**Pantalla**: Widget de acciones rapidas

**Narrar**:
> "Atajos directos a las tareas mas frecuentes: crear evento, anadir curso, publicar noticia, gestionar miembros... Un clic y estas ahi. Ahorra tiempo en tu gestion diaria."

**Acciones**:
1. Mostrar los botones de acciones rapidas
2. Hacer clic en uno para demostrar que funciona
3. Volver al dashboard

---

### ESCENA 8: Resumen de Modulos (3:00 - 3:25)

**Pantalla**: Widget de modulos activos

**Narrar**:
> "Vista rapida de los modulos activos y su estado. Desde aqui puedes acceder directamente al dashboard de cada modulo. Muy util para navegar rapidamente."

**Acciones**:
1. Mostrar la lista de modulos
2. Hacer clic en uno para ir a su dashboard
3. Volver al dashboard principal

---

### ESCENA 9: Navegacion y Ayuda (3:25 - 4:00)

**Pantalla**: Menu lateral y boton de ayuda

**Narrar**:
> "Recuerda que el menu lateral te da acceso a todas las secciones. Los submenus se expanden al pasar el raton. Y el boton de ayuda flotante siempre esta disponible cuando tengas dudas."

**Acciones**:
1. Mostrar el menu lateral
2. Expandir algunos submenus
3. Mostrar el boton de ayuda
4. Fade out con mensaje final

---

## Video 3: Tour de Modulos (5 min)

**ID del tour**: `tour_modulos`
**Archivo a editar**: `admin/class-guided-tours.php` linea 248

### Configuracion previa
- Varios modulos activos e inactivos
- Al menos 3-4 categorias de modulos
- Un modulo con dependencias

---

### ESCENA 1: Que son los Modulos (0:00 - 0:30)

**Pantalla**: Pagina de gestion de modulos

**Narrar**:
> "Los modulos extienden las capacidades de tu plataforma. Cada modulo anade funcionalidades especificas: eventos para gestionar actividades, cursos para formacion, reservas para espacios, miembros para socios... y mucho mas."

**Acciones**:
1. Mostrar la pagina de modulos
2. Scroll para ver la variedad disponible
3. Mostrar que hay muchas opciones

---

### ESCENA 2: Tarjetas de Modulo (0:30 - 1:00)

**Pantalla**: Detalle de una tarjeta de modulo

**Narrar**:
> "Cada modulo tiene su propia tarjeta con informacion importante: nombre, descripcion, estado actual y opciones de configuracion. Puedes ver rapidamente que hace cada uno."

**Acciones**:
1. Acercar zoom a una tarjeta especifica
2. Senalar el icono, titulo, descripcion
3. Mostrar el estado (activo/inactivo)

---

### ESCENA 3: Activar y Desactivar (1:00 - 1:40)

**Pantalla**: Toggle de activacion

**Narrar**:
> "Usa este interruptor para activar o desactivar modulos. El sistema verifica automaticamente los requisitos antes de activar. Si un modulo necesita otro para funcionar, te lo indicara."

**Acciones**:
1. Localizar el toggle de un modulo inactivo
2. Hacer clic para activarlo
3. Mostrar el mensaje de confirmacion
4. Desactivarlo de nuevo
5. Mostrar que el cambio es inmediato

---

### ESCENA 4: Configuracion del Modulo (1:40 - 2:15)

**Pantalla**: Boton de configuracion y panel de ajustes

**Narrar**:
> "Cada modulo tiene su propia configuracion. Accede haciendo clic en el boton de ajustes para personalizar segun las necesidades de tu organizacion. Las opciones varian segun el modulo."

**Acciones**:
1. Hacer clic en configuracion de un modulo
2. Mostrar el panel de opciones
3. Cambiar alguna opcion como ejemplo
4. Guardar y volver

---

### ESCENA 5: Dependencias (2:15 - 2:50)

**Pantalla**: Modulo con dependencias

**Narrar**:
> "Algunos modulos requieren otros modulos o plugins para funcionar correctamente. El sistema te avisa de los requisitos antes de activar. Por ejemplo, el modulo de pagos puede necesitar WooCommerce."

**Acciones**:
1. Buscar un modulo con dependencias
2. Intentar activarlo
3. Mostrar el mensaje de requisitos
4. Explicar como resolver las dependencias

---

### ESCENA 6: Filtrar por Categoria (2:50 - 3:20)

**Pantalla**: Filtros de categoria

**Narrar**:
> "Filtra modulos por categoria para encontrar rapidamente lo que necesitas: comunicacion, gestion, economia, comunidad, cultura... Muy util cuando tienes muchos modulos disponibles."

**Acciones**:
1. Localizar el filtro de categorias
2. Seleccionar una categoria
3. Mostrar como se filtra la lista
4. Seleccionar otra categoria
5. Volver a mostrar todos

---

### ESCENA 7: Buscar Modulos (3:20 - 3:50)

**Pantalla**: Campo de busqueda

**Narrar**:
> "Usa el buscador para encontrar modulos por nombre o funcionalidad. Escribe lo que necesitas y el sistema te muestra las coincidencias. Ideal cuando sabes exactamente que buscas."

**Acciones**:
1. Localizar el campo de busqueda
2. Escribir "eventos"
3. Mostrar resultados filtrados
4. Limpiar busqueda
5. Buscar otra cosa como "reservas"

---

### ESCENA 8: Modulos Activos (3:50 - 4:20)

**Pantalla**: Seccion de modulos activos

**Narrar**:
> "Esta vista rapida te muestra los modulos que tienes activados actualmente. Desde aqui puedes acceder a su dashboard especifico o ir directamente a su configuracion."

**Acciones**:
1. Mostrar la lista de modulos activos
2. Senalar los indicadores de estado
3. Hacer clic en "Ver dashboard" de alguno

---

### ESCENA 9: Dashboard del Modulo (4:20 - 5:00)

**Pantalla**: Dashboard de un modulo especifico (ej: Eventos)

**Narrar**:
> "Cada modulo activo tiene su propio dashboard con estadisticas, listados y acciones especificas. Aqui gestionas todo lo relacionado con esa funcionalidad. Un clic y estas trabajando directamente."

**Acciones**:
1. Mostrar el dashboard del modulo
2. Senalar estadisticas propias
3. Mostrar listados de contenido
4. Mostrar acciones disponibles
5. Volver a la pagina de modulos
6. Fade out con mensaje final

---

## Como agregar las URLs de video

Una vez subidos los videos a YouTube, edita el archivo:

```
admin/class-guided-tours.php
```

Busca cada tour y agrega la URL en el campo `video_url`:

```php
// Linea ~111
$this->tours['tour_primeros_pasos'] = [
    // ...
    'video_url' => 'https://www.youtube.com/watch?v=TU_VIDEO_ID',
    // ...
];

// Linea ~168
$this->tours['tour_dashboard'] = [
    // ...
    'video_url' => 'https://www.youtube.com/watch?v=TU_VIDEO_ID',
    // ...
];

// Linea ~248
$this->tours['tour_modulos'] = [
    // ...
    'video_url' => 'https://www.youtube.com/watch?v=TU_VIDEO_ID',
    // ...
];
```

El sistema convierte automaticamente las URLs de YouTube al formato embed.

---

## Proximos videos a crear (prioridad media)

| Tour | Duracion | Linea en PHP |
|------|----------|--------------|
| `tour_diseno` | 4 min | ~316 |
| `tour_landing` | 5 min | ~365 |
| `tour_chat_ia` | 4 min | ~502 |
| `tour_configuracion` | 5 min | ~552 |
| `tour_app_profiles` | 3 min | ~626 |

---

## Tips para grabar con Recordly

1. **Resolucion**: 1920x1080 o 1280x720
2. **FPS**: 30 fps es suficiente para tutoriales
3. **Audio**: Graba narracion por separado si es posible
4. **Cursor**: Muestra el cursor con highlight
5. **Pausas**: Haz pausas breves entre acciones
6. **Zoom**: Usa zoom en elementos pequenos
7. **Consistencia**: Usa el mismo tema de WordPress en todos los videos

### Ejecutar Recordly

```bash
chmod +x /home/josu/Descargas/Recordly-linux-x64.AppImage
/home/josu/Descargas/Recordly-linux-x64.AppImage
```
