# Páginas de Ejemplo por Módulo

## ✅ Fase 2 Completada: Formularios en Módulos Prioritarios

Se han añadido formularios funcionales a **6 módulos prioritarios**:
1. ✅ **Talleres** - 3 formularios
2. ✅ **Facturas** - 2 formularios
3. ✅ **Socios** - 3 formularios
4. ✅ **Fichaje-empleados** - 5 formularios
5. ✅ **Eventos** - 3 formularios
6. ✅ **Foros** - 4 formularios

**Total: 20 formularios funcionales listos para usar** 🎉

---

## 📋 1. TALLERES

### Páginas a Crear

#### Página: `/talleres/` (Principal)
**Shortcode:**
```
[flavor_module_listing module="talleres" action="talleres_disponibles" columnas="3" limite="12"]
```

#### Página: `/talleres/crear/`
**Shortcode:**
```
<h1>Comparte tu Conocimiento</h1>
<p>Organiza un taller y enseña a tu comunidad</p>

[flavor_module_form module="talleres" action="crear_taller"]
```

#### Página: `/talleres/inscribirse/`
**Shortcode:**
```
[flavor_module_form module="talleres" action="inscribirse"]
```
**Nota:** Pasar `taller_id` via URL: `?taller_id=123`

#### Página: `/talleres/mis-talleres/`
**Shortcode:**
```
[flavor_module_dashboard module="talleres"]
```

### CTAs en Landing
```html
<a href="/talleres/" class="btn-primary">Ver Talleres</a>
<a href="/talleres/crear/" class="btn-secondary">Crear Taller</a>
```

---

## 💰 2. FACTURAS

### Páginas a Crear

#### Página: `/facturas/` (Principal)
**Shortcode:**
```
[flavor_module_listing module="facturas" action="listar_facturas" columnas="2"]
```

#### Página: `/facturas/crear/`
**Shortcode:**
```
<h1>Nueva Factura</h1>
<p>Crea una factura en segundos</p>

[flavor_module_form module="facturas" action="crear_factura"]
```

#### Página: `/facturas/mis-facturas/`
**Shortcode:**
```
<h1>Mis Facturas</h1>

[flavor_module_dashboard module="facturas"]
```

#### Página: `/facturas/buscar/`
**Shortcode:**
```
<h1>Buscar Facturas</h1>

[flavor_module_form module="facturas" action="buscar_facturas"]
```

### CTAs en Landing
```html
<a href="/facturas/crear/" class="btn-primary">Nueva Factura</a>
<a href="/facturas/mis-facturas/" class="btn-secondary">Mis Facturas</a>
```

---

## 👥 3. SOCIOS

### Páginas a Crear

#### Página: `/socios/` (Principal)
**Shortcode:**
```
<h1>Únete a Nuestra Comunidad</h1>
<p>Descubre los beneficios de ser socio</p>

<!-- Explicación de beneficios aquí -->

<a href="/socios/unirme/" class="btn-primary btn-lg">Hacerse Socio</a>
```

#### Página: `/socios/unirme/`
**Shortcode:**
```
[flavor_module_form module="socios" action="dar_alta_socio"]
```

#### Página: `/socios/mi-perfil/`
**Shortcode:**
```
<h1>Mi Perfil de Socio</h1>

[flavor_module_dashboard module="socios"]
```

#### Página: `/socios/pagar-cuota/`
**Shortcode:**
```
[flavor_module_form module="socios" action="pagar_cuota"]
```
**Nota:** Pasar `cuota_id` via URL

#### Página: `/socios/actualizar-datos/`
**Shortcode:**
```
<h1>Actualizar Mis Datos</h1>

[flavor_module_form module="socios" action="actualizar_datos"]
```

### CTAs en Landing
```html
<a href="/socios/unirme/" class="btn-primary">Unirme Ahora</a>
<a href="/socios/" class="btn-secondary">Más Información</a>
```

---

## ⏰ 4. FICHAJE-EMPLEADOS

### Páginas a Crear

#### Página: `/fichaje/` (Dashboard)
**Shortcode:**
```
<h1>Control de Fichajes</h1>

[flavor_module_dashboard module="fichaje-empleados"]

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 2rem;">
    <a href="/fichaje/entrada/" class="btn-primary">Fichar Entrada</a>
    <a href="/fichaje/salida/" class="btn-secondary">Fichar Salida</a>
    <a href="/fichaje/pausar/" class="btn-secondary">Pausar Jornada</a>
    <a href="/fichaje/reanudar/" class="btn-secondary">Reanudar</a>
</div>
```

#### Página: `/fichaje/entrada/`
**Shortcode:**
```
[flavor_module_form module="fichaje-empleados" action="fichar_entrada"]
```

#### Página: `/fichaje/salida/`
**Shortcode:**
```
[flavor_module_form module="fichaje-empleados" action="fichar_salida"]
```

#### Página: `/fichaje/pausar/`
**Shortcode:**
```
[flavor_module_form module="fichaje-empleados" action="pausar_jornada"]
```

#### Página: `/fichaje/reanudar/`
**Shortcode:**
```
[flavor_module_form module="fichaje-empleados" action="reanudar_jornada"]
```

#### Página: `/fichaje/solicitar-correccion/`
**Shortcode:**
```
<h1>Solicitar Corrección de Fichaje</h1>
<p>¿Olvidaste fichar? Solicita una corrección</p>

[flavor_module_form module="fichaje-empleados" action="solicitar_cambio"]
```

### CTAs en Landing
```html
<a href="/fichaje/" class="btn-primary">Ir a Fichaje</a>
```

---

## 🎉 5. EVENTOS

### Páginas a Crear

#### Página: `/eventos/` (Principal)
**Shortcode:**
```
<h1>Eventos de la Comunidad</h1>

[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]
```

#### Página: `/eventos/crear/`
**Shortcode:**
```
<h1>Organiza un Evento</h1>
<p>Crea encuentros para la comunidad</p>

[flavor_module_form module="eventos" action="crear_evento"]
```

#### Página: `/eventos/inscribirse/`
**Shortcode:**
```
[flavor_module_form module="eventos" action="inscribirse_evento"]
```
**Nota:** Pasar `evento_id` via URL

#### Página: `/eventos/mis-eventos/`
**Shortcode:**
```
<h1>Mis Eventos</h1>

[flavor_module_dashboard module="eventos"]
```

### CTAs en Landing
```html
<a href="/eventos/" class="btn-primary">Ver Eventos</a>
<a href="/eventos/crear/" class="btn-secondary">Crear Evento</a>
```

---

## 💬 6. FOROS

### Páginas a Crear

#### Página: `/foros/` (Principal)
**Shortcode:**
```
<h1>Foros de la Comunidad</h1>

[flavor_module_listing module="foros" action="listar_temas" columnas="1"]
```

#### Página: `/foros/nuevo-tema/`
**Shortcode:**
```
<h1>Crear Nuevo Tema</h1>
<p>Inicia una nueva discusión</p>

[flavor_module_form module="foros" action="crear_tema"]
```

#### Página: `/foros/tema/` (Detalle de tema)
**Shortcode:**
```
<!-- Título y contenido del tema se cargan dinámicamente -->

[flavor_module_form module="foros" action="responder_tema"]
```

#### Página: `/foros/editar/`
**Shortcode:**
```
[flavor_module_form module="foros" action="editar_mensaje"]
```
**Nota:** Pasar `mensaje_id` via URL

#### Página: `/foros/reportar/`
**Shortcode:**
```
<h1>Reportar Mensaje</h1>

[flavor_module_form module="foros" action="reportar_mensaje"]
```
**Nota:** Pasar `mensaje_id` via URL

### CTAs en Landing
```html
<a href="/foros/" class="btn-primary">Ver Foros</a>
<a href="/foros/nuevo-tema/" class="btn-secondary">Nuevo Tema</a>
```

---

## 🚀 Cómo Crear las Páginas en WordPress

### Método Rápido

1. **WordPress Admin → Páginas → Añadir nueva**
2. Escribe el **Título** de la página
3. En el **slug de URL**, usa la estructura recomendada arriba
4. **Copia y pega** el shortcode correspondiente
5. **Publica**
6. Repite para cada página

### Ejemplo Completo: Talleres

```
Página 1:
- Título: Talleres
- Slug: talleres
- Contenido: [flavor_module_listing module="talleres" action="talleres_disponibles" columnas="3"]

Página 2:
- Título: Crear Taller
- Slug: talleres-crear (o usar jerarquía: Padre = Talleres)
- Contenido: [flavor_module_form module="talleres" action="crear_taller"]

Página 3:
- Título: Inscribirse en Taller
- Slug: talleres-inscribirse
- Contenido: [flavor_module_form module="talleres" action="inscribirse"]

Página 4:
- Título: Mis Talleres
- Slug: talleres-mis-talleres
- Contenido: [flavor_module_dashboard module="talleres"]
```

---

## 📊 Resumen de Formularios Implementados

### Talleres (3)
- ✅ Inscribirse en taller
- ✅ Crear taller
- ✅ Valorar taller

### Facturas (2)
- ✅ Crear factura
- ✅ Buscar facturas

### Socios (3)
- ✅ Dar de alta como socio
- ✅ Pagar cuota
- ✅ Actualizar datos personales

### Fichaje-Empleados (5)
- ✅ Fichar entrada
- ✅ Fichar salida
- ✅ Pausar jornada
- ✅ Reanudar jornada
- ✅ Solicitar corrección

### Eventos (3)
- ✅ Crear evento
- ✅ Inscribirse en evento
- ✅ Cancelar inscripción

### Foros (4)
- ✅ Crear tema
- ✅ Responder a tema
- ✅ Editar mensaje
- ✅ Reportar mensaje

---

## 🔄 Flujo Completo de Usuario

### Ejemplo: Inscripción en Taller

1. Usuario visita `/talleres/`
2. Ve listado de talleres disponibles
3. Click en "Ver más" de un taller
4. Click en "Inscribirse"
5. Redirige a `/talleres/inscribirse/?taller_id=123`
6. Rellena formulario
7. Submit → Validación → Guardado
8. Mensaje de éxito
9. Redirección automática a `/talleres/mis-talleres/`
10. Ve su inscripción en el dashboard

### Ejemplo: Crear Factura

1. Usuario admin visita `/facturas/crear/`
2. Rellena datos del cliente
3. Añade concepto y precio
4. Selecciona IVA y método de pago
5. Submit → Se genera número de factura automático
6. Se guarda en base de datos
7. Mensaje de éxito con número de factura
8. Redirección a `/facturas/mis-facturas/`
9. Puede descargar PDF (próxima funcionalidad)

---

## 🎨 Personalización de Formularios

Todos los formularios usan las clases CSS de `/assets/css/flavor-modules.css`. Puedes personalizar añadiendo CSS adicional:

```css
/* Tu tema o CSS personalizado */

.flavor-form-container {
    background: #your-color;
}

.flavor-btn-primary {
    background-color: #your-brand-color;
}
```

---

## 🔐 Seguridad

Todos los formularios incluyen:

✅ **WordPress Nonces** - Protección CSRF
✅ **Validación Frontend** - HTML5 + Alpine.js
✅ **Validación Backend** - PHP en REST API
✅ **Sanitización** - Limpieza automática de datos
✅ **Permisos** - Verificación de capacidades de usuario
✅ **Escape de salida** - Prevención XSS

---

## 📱 Mobile-First

Todos los formularios son completamente **responsive**:

- ✅ Táctil-friendly
- ✅ Campos grandes para móvil
- ✅ Teclados contextuales (email, teléfono, número)
- ✅ Mensajes visibles
- ✅ Botones accesibles

---

## 🧪 Testing Rápido

Para probar cualquier formulario:

1. Crea una página de prueba
2. Añade el shortcode correspondiente
3. Visita la página
4. Rellena y envía el formulario
5. Verifica:
   - ✅ Validación funciona
   - ✅ Loading state aparece
   - ✅ Mensaje de éxito/error se muestra
   - ✅ Redirección funciona (si aplica)
   - ✅ Datos se guardan en BD

---

## 🎯 Próximos Pasos Opcionales

### Más Módulos

Aplicar el mismo patrón a:
- Huertos urbanos
- Incidencias
- Presupuestos participativos
- Compostaje
- Marketplace (mejorar el existente)
- Tramites
- Parkings
- Espacios comunes

### Mejoras Funcionales

- Subida de archivos (imágenes, PDFs)
- Validación asíncrona (AJAX)
- Autocompletado de campos
- Campos dependientes (mostrar/ocultar según selección)
- Preview de contenido (ej: previsualizar evento antes de publicar)
- Múltiples líneas dinámicas (ej: varias líneas de factura)

---

**¡Sistema completo y listo para producción!** ✨
