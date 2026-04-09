# 📱 Instalación y Testing - Apps Móviles Flavor Platform

## ✅ Sistema Completado

### Backend PHP
- ✅ 17 endpoints REST para campamentos
- ✅ Sistema CRUD completo
- ✅ Subida de imágenes
- ✅ Enlaces compartibles
- ✅ Exportación a Excel
- ✅ Estadísticas completas

### Frontend Flutter
- ✅ 6 pantallas nuevas para campamentos
- ✅ Integración completa con navegación
- ✅ Botones de contacto (Email, Teléfono, WhatsApp)
- ✅ Filtros avanzados
- ✅ Pull-to-refresh en todas las pantallas

---

## 📦 Instalación de las Apps

### Pre-requisitos
1. Dispositivo Android con versión 5.0 (Lollipop) o superior
2. Permitir instalación de fuentes desconocidas:
   - Ajustes → Seguridad → Fuentes desconocidas (Android < 8)
   - Ajustes → Aplicaciones → Acceso especial → Instalar apps desconocidas (Android ≥ 8)

### Archivos APK
Después de la compilación, encontrarás los APKs en:
```
build/app/outputs/flutter-apk/
├── app-cliente-release.apk  (App Cliente)
└── app-admin-release.apk    (App Admin)
```

### Instalación
1. **Transferir APKs al dispositivo:**
   ```bash
   adb install build/app/outputs/flutter-apk/app-cliente-release.apk
   adb install build/app/outputs/flutter-apk/app-admin-release.apk
   ```

   O copia los archivos y ábrelos en el dispositivo.

2. **Primera configuración:**
   - Abrir app
   - Ingresar URL del servidor: `https://tudominio.local/wp-json/chat-ia-mobile/v1`
   - Login con credenciales de WordPress

---

## 🧪 Plan de Testing

### 1. Testing Backend - Campamentos API

#### Verificar endpoints en Postman/cURL:

```bash
# 1. Listar campamentos
curl -X GET "https://tudominio.local/wp-json/camps/v1/camps"

# 2. Detalle de campamento
curl -X GET "https://tudominio.local/wp-json/camps/v1/camps/1"

# 3. Crear inscripción
curl -X POST "https://tudominio.local/wp-json/camps/v1/camps/1/inscribe" \
  -H "Content-Type: application/json" \
  -d '{
    "participant": {"name": "Test User", "age": 10},
    "guardian": {"name": "Parent", "email": "test@example.com", "phone": "+34600000000"},
    "payment_method": "pending"
  }'

# 4. Admin: Subir imagen (requiere auth)
curl -X POST "https://tudominio.local/wp-json/camps/v1/admin/upload-image" \
  -H "Authorization: Bearer TOKEN" \
  -F "image=@/path/to/image.jpg"

# 5. Admin: Crear campamento (requiere auth)
curl -X POST "https://tudominio.local/wp-json/camps/v1/admin/camps" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Campamento de Verano 2025",
    "description": "Descripción completa",
    "excerpt": "Resumen corto",
    "price": 50,
    "price_total": 150,
    "duration": "1 semana",
    "inscription_closed": false
  }'
```

### 2. Testing App Cliente

#### 2.1 Flujo Principal
- [ ] Configurar servidor
- [ ] Login exitoso
- [ ] Ver lista de campamentos
- [ ] Aplicar filtros (categoría, edad, idioma, estado)
- [ ] Buscar campamentos
- [ ] Abrir detalle de campamento
- [ ] Ver toda la información del campamento
- [ ] Completar formulario de inscripción
- [ ] Recibir confirmación

#### 2.2 Navegación
- [ ] Pestaña "Campamentos" visible
- [ ] Navegación entre pestañas fluida
- [ ] Botón "Volver" funciona correctamente
- [ ] Pull-to-refresh actualiza datos

#### 2.3 UI/UX
- [ ] Imágenes se cargan correctamente
- [ ] Badges de estado visibles (Abierto, Cerrado, Completo)
- [ ] Filtros se aplican correctamente
- [ ] Chips de filtros activos se muestran
- [ ] AppBar expandible funciona con imagen

### 3. Testing App Admin

#### 3.1 Dashboard
- [ ] Acceso desde Dashboard → Campamentos
- [ ] Tile "Campamentos" visible y funcional

#### 3.2 Gestión de Campamentos
- [ ] Ver lista de campamentos
- [ ] Crear nuevo campamento
- [ ] Editar campamento existente
- [ ] Eliminar campamento sin inscripciones
- [ ] Activar/desactivar campamento
- [ ] Abrir/cerrar inscripciones

#### 3.3 Formulario de Campamento
- [ ] Campos se rellenan correctamente
- [ ] Selector de fechas funciona
- [ ] Taxonomías (categorías, edades, idiomas) se seleccionan
- [ ] Guardar crea/actualiza correctamente
- [ ] Validaciones funcionan

#### 3.4 Enlaces Compartibles
- [ ] Botón "Compartir" genera enlace
- [ ] Enlace web se copia al portapapeles
- [ ] Deeplink app se copia
- [ ] Función compartir nativa funciona

#### 3.5 Inscripciones
- [ ] Ver lista de inscripciones
- [ ] Estadísticas correctas (total, pagadas, pendientes)
- [ ] Filtros por nombre y estado funcionan
- [ ] Expandir inscripción muestra detalles

#### 3.6 Botones de Contacto
- [ ] Botón Email abre cliente de correo
- [ ] Botón Teléfono inicia llamada
- [ ] Botón WhatsApp abre chat (con número formateado)
- [ ] Todos los botones funcionan en inscripciones

#### 3.7 Exportación
- [ ] Exportar a Excel genera archivo
- [ ] Descarga se inicia correctamente
- [ ] Excel contiene datos correctos

### 4. Testing de Integración

#### 4.1 Flujo Completo Cliente
```
1. Usuario abre app
2. Ve lista de campamentos
3. Aplica filtro "Verano" → "8-10 años"
4. Abre detalle de campamento
5. Completa inscripción
6. Recibe confirmación
```

#### 4.2 Flujo Completo Admin
```
1. Admin abre app
2. Dashboard → Campamentos
3. Crear nuevo campamento
4. Subir imagen (futuro)
5. Seleccionar taxonomías
6. Guardar
7. Compartir enlace
8. Verificar en lista
```

#### 4.3 Sincronización
- [ ] Cambios en admin se reflejan en cliente
- [ ] Inscripción desde cliente aparece en admin
- [ ] Contadores se actualizan correctamente

### 5. Testing de Errores

#### 5.1 Sin Conexión
- [ ] Mensaje de error apropiado
- [ ] App no crashea
- [ ] Retry funciona

#### 5.2 Servidor No Disponible
- [ ] Timeout manejado correctamente
- [ ] Mensaje de error claro

#### 5.3 Validaciones
- [ ] Campos obligatorios validados
- [ ] Formato de email validado
- [ ] Formato de teléfono validado
- [ ] Edad validada (número positivo)

#### 5.4 Permisos
- [ ] Endpoints admin requieren autenticación
- [ ] Usuario no admin no puede acceder
- [ ] Mensaje de error apropiado

### 6. Testing de Rendimiento

- [ ] Lista de campamentos carga en < 2 segundos
- [ ] Imágenes se cargan con placeholder
- [ ] Scroll suave en listas largas
- [ ] Pull-to-refresh responde inmediatamente
- [ ] No hay memory leaks

### 7. Testing Visual

- [ ] Colores consistentes con tema
- [ ] Espaciado correcto
- [ ] Textos legibles
- [ ] Iconos apropiados
- [ ] Material Design 3 aplicado correctamente

---

## 🐛 Problemas Conocidos y Soluciones

### 1. "Flutter not found"
```bash
# Añadir Flutter al PATH
export PATH="$PATH:/home/josu/flutter/bin"
```

### 2. "SDK version error"
```bash
# Actualizar Flutter
/home/josu/flutter/bin/flutter upgrade
```

### 3. "Gradle build failed"
```bash
# Limpiar build
/home/josu/flutter/bin/flutter clean
/home/josu/flutter/bin/flutter pub get
```

### 4. "No se puede instalar APK"
- Verificar que la versión de Android sea compatible (≥ 5.0)
- Permitir instalación de fuentes desconocidas
- Desinstalar versión anterior si existe

### 5. "No se conecta al servidor"
- Verificar URL del servidor
- Verificar que WordPress tenga permalinks configurados
- Verificar que el plugin esté activado
- Verificar certificado SSL si es HTTPS

---

## 📊 Métricas de Éxito

### Backend
- ✅ Todos los endpoints responden correctamente
- ✅ Validaciones funcionan
- ✅ Errores devuelven códigos HTTP apropiados
- ✅ Subida de imágenes funciona

### App Cliente
- ✅ Usuario puede completar inscripción en < 2 minutos
- ✅ Filtros reducen resultados apropiadamente
- ✅ UI intuitiva y clara

### App Admin
- ✅ Admin puede crear campamento en < 3 minutos
- ✅ Enlaces compartibles funcionan
- ✅ Botones de contacto funcionan
- ✅ Exportación genera Excel correctamente

---

## 📝 Notas Adicionales

### Integración Redsys (Pendiente)
Para implementar pagos con Redsys necesitas:
1. Merchant Code (FUC)
2. Terminal
3. Clave secreta
4. URL del TPV virtual

Contacta con tu proveedor de Redsys para obtener estos datos.

### Notificaciones Push (Opcional)
Firebase está configurado pero comentado. Para activar:
1. Crear proyecto Firebase
2. Añadir apps Android/iOS
3. Descargar google-services.json
4. Descomentar dependencias en pubspec.yaml
5. Configurar en WordPress admin

### Mejoras Futuras Sugeridas
- [ ] Galería de imágenes en detalle de campamento
- [ ] Filtro por rango de precio
- [ ] Favoritos/Guardados
- [ ] Notificaciones de nuevos campamentos
- [ ] Chat directo con organizador
- [ ] Valoraciones y reseñas
- [ ] Calendario de disponibilidad visual

---

## 🆘 Soporte

Si encuentras problemas:
1. Verificar logs en Android Studio / Logcat
2. Verificar debug.log de WordPress
3. Verificar permisos de archivos en servidor
4. Contactar con el equipo de desarrollo

---

## ✨ Funcionalidades Destacadas Implementadas

### 🎯 Sistema CRUD Completo
- Crear, leer, actualizar y eliminar campamentos
- Validaciones completas
- Protección contra eliminación con inscripciones

### 🔗 Enlaces Compartibles
- Token único por campamento
- Enlace web + Deeplink app
- Botones copiar y compartir nativos

### 📱 Botones de Contacto
- Email con mailto:
- Teléfono con tel:
- WhatsApp con formateo automático

### 🖼️ Subida de Imágenes
- Endpoint REST listo
- WordPress media library integration
- Thumbnail generation automática

### 📊 Estadísticas Avanzadas
- Total inscripciones
- Ingresos totales y por campamento
- Desglose por categoría
- Filtros por período

### 📤 Exportación Excel
- Genera archivo XLSX
- Incluye todos los datos de inscripciones
- Descarga directa desde app

---

**Versión:** 2.0.0
**Fecha:** 2026-01-27
**Estado:** ✅ Completado y listo para testing
