# Configuración de Apps Móviles - Flavor Platform

Guía completa para conectar apps móviles Android/iOS a Flavor Platform.

---

## 🎯 Resumen Rápido

Para conectar tu app necesitas:

1. ✅ Permalinks configurados correctamente en WordPress
2. ✅ REST API funcionando
3. ✅ URL accesible desde el móvil
4. ✅ Ingresar URL base en la app (sin `/wp-json/`)

---

## 📋 Checklist de Configuración

### 1. Verificar Permalinks

**¿Por qué?** La REST API de WordPress requiere permalinks "bonitos" para funcionar.

**Pasos:**

1. Ir a: `WordPress Admin > Ajustes > Enlaces permanentes`

2. Seleccionar **cualquiera de estas opciones:**
   - ✅ **Nombre de entrada** (Recomendado): `/%postname%/`
   - ✅ Día y nombre: `/%year%/%monthnum%/%day%/%postname%/`
   - ✅ Mes y nombre: `/%year%/%monthnum%/%postname%/`
   - ✅ Estructura personalizada: `/%postname%/` o similar

3. **NO seleccionar:**
   - ❌ Simple: `?p=123` (la REST API NO funcionará)

4. Click en **"Guardar cambios"**

5. **Verificar:**
   - Visitar: `https://tusitio.com/wp-json/`
   - Debe mostrar JSON con información de la API
   - Si muestra 404, repetir pasos anteriores

### 2. Verificar REST API Funciona

**Abrir en navegador:**

```
https://tusitio.com/wp-json/
```

**Debe mostrar algo como:**
```json
{
  "name": "Mi Sitio",
  "description": "Just another WordPress site",
  "url": "https://tusitio.com",
  "routes": {
    "/wp/v2": {
      "namespace": "wp/v2"
    },
    ...
  }
}
```

Si muestra **404 o página en blanco**, los permalinks no están bien configurados.

### 3. Verificar Endpoints de Flavor Platform

**Probar estos endpoints en el navegador:**

```bash
# Información del sistema
https://tusitio.com/wp-json/app-discovery/v1/info

# Módulos disponibles
https://tusitio.com/wp-json/app-discovery/v1/modules

# Configuración de tema
https://tusitio.com/wp-json/app-discovery/v1/theme

# Layouts para apps
https://tusitio.com/wp-json/app-discovery/v1/layouts
```

Todos deben retornar JSON válido.

### 4. Script de Prueba Automático

Creamos un script de prueba para ti. Accede a:

```
http://tusitio.local/test-api.php
```

Este script:
- ✅ Muestra la URL que debes usar en la app
- ✅ Prueba todos los endpoints automáticamente
- ✅ Muestra los resultados en formato visual
- ✅ Te dice exactamente qué está fallando si algo no funciona

---

## 🌐 Configuración según Tipo de Servidor

### A) Servidor en Producción (Dominio Real)

**URL a usar en la app:**
```
https://tudominio.com
```

**Pasos:**
1. Asegurarse que el sitio tiene SSL (HTTPS)
2. Verificar que la REST API es accesible públicamente
3. Ingresar la URL en la app (solo el dominio, sin rutas)

**Ejemplo:**
- ✅ Correcto: `https://mitienda.com`
- ❌ Incorrecto: `https://mitienda.com/wp-json/`
- ❌ Incorrecto: `https://mitienda.com/wp-admin/`

### B) Local by Flywheel (Desarrollo Local)

**Problema:** Las URLs `.local` solo funcionan en tu computadora, no desde el móvil.

**Solución 1: Usar ngrok (Recomendado para testing)**

```bash
# 1. Descargar ngrok
# https://ngrok.com/download

# 2. Ejecutar (reemplazar el puerto si usas otro)
ngrok http 80

# 3. ngrok mostrará una URL pública:
# Forwarding: https://abc123.ngrok.io -> http://localhost:80

# 4. Usar esa URL en la app:
https://abc123.ngrok.io
```

**⚠️ Importante:**
- La URL de ngrok cambia cada vez que lo reinicias
- Gratis: sesiones de 2 horas
- Perfecto para desarrollo y testing

**Solución 2: Acceder por IP local (Misma red WiFi)**

```bash
# 1. Obtener IP de tu computadora
# En Mac/Linux:
ifconfig | grep "inet "
# En Windows:
ipconfig

# 2. Busca algo como: 192.168.1.XXX

# 3. Configurar Local para aceptar conexiones externas
# En Local: Site > Edit > Advanced
# Custom domain: 192.168.1.XXX

# 4. Usar esa IP en la app (desde móvil en misma WiFi):
http://192.168.1.XXX
```

**⚠️ Limitaciones:**
- Solo funciona en la misma red WiFi
- IP puede cambiar

**Solución 3: Usar subdomain tunnel de Local**

```bash
# 1. En Local: hacer click en "Share" (icono compartir)
# 2. Local creará un túnel temporal
# 3. Te dará una URL pública como:
# https://abc123.localsite.io

# 4. Usar esa URL en la app
```

### C) XAMPP / MAMP / WAMP

Similar a Local:

```bash
# 1. Usar ngrok
ngrok http 80

# 2. O configurar para aceptar conexiones de red local
# Editar httpd.conf:
# Listen 0.0.0.0:80

# 3. Usar IP local
http://192.168.1.XXX
```

---

## 📱 Configurar en la App

### Paso a Paso en la App:

1. **Abrir la app**

2. **Ir a "Configuración" o "Conectar Servidor"**
   - Puede estar en menú principal
   - O en pantalla de bienvenida

3. **Ingresar URL:**
   ```
   https://tudominio.com
   ```
   O la URL de ngrok si estás en local:
   ```
   https://abc123.ngrok.io
   ```

4. **La app automáticamente:**
   - Agregará `/wp-json/app-discovery/v1/info`
   - Verificará la conexión
   - Descargará módulos y configuración

5. **Si aparece error:**
   - Verificar que la URL es correcta
   - Verificar que no incluye `/wp-json/` al final
   - Usar el script de prueba para verificar endpoints

---

## 🔧 Solución de Problemas Comunes

### Error: "URL no disponible"

**Causas posibles:**

1. **Permalinks mal configurados**
   - Solución: Ir a `Ajustes > Enlaces permanentes` y seleccionar "Nombre de entrada"
   - Guardar cambios

2. **URL incorrecta en la app**
   - Solución: Verificar que solo usas `https://dominio.com` sin rutas adicionales

3. **Sitio local no accesible desde móvil**
   - Solución: Usar ngrok para crear túnel público

4. **Firewall bloqueando**
   - Solución: Verificar firewall del servidor
   - En Local: verificar que acepta conexiones externas

5. **Plugin no activado**
   - Solución: Verificar que Flavor Platform está activado en WordPress

### Error: "No se pueden cargar módulos"

**Solución:**
```bash
# 1. Verificar endpoint de módulos:
https://tusitio.com/wp-json/app-discovery/v1/modules

# 2. Debe retornar JSON con array de módulos
# 3. Si está vacío, activar módulos en:
# Flavor Platform > Módulos
```

### Error: "Chat no funciona"

**Solución:**
```bash
# 1. Verificar configuración de IA:
# Flavor Platform > Configuración
# Ingresar API key válida

# 2. Verificar endpoint de chat:
POST https://tusitio.com/wp-json/unified-api/v1/chat
# Debe estar accesible
```

### Error: "Tema/diseño no carga"

**Solución:**
```bash
# 1. Configurar perfil de app:
# Flavor Platform > Perfil de Aplicación
# Configurar colores, nombre, descripción

# 2. Verificar endpoint:
https://tusitio.com/wp-json/app-discovery/v1/theme
```

---

## 🧪 Testing Completo

### 1. Test Manual con cURL

```bash
# Test info del sistema
curl -X GET https://tusitio.com/wp-json/app-discovery/v1/info

# Test módulos
curl -X GET https://tusitio.com/wp-json/app-discovery/v1/modules

# Test tema
curl -X GET https://tusitio.com/wp-json/app-discovery/v1/theme

# Test chat (requiere mensaje)
curl -X POST https://tusitio.com/wp-json/unified-api/v1/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Hola", "user_id": "test"}'
```

### 2. Test con Postman

1. Importar colección de endpoints
2. Configurar variable `base_url` = `https://tusitio.com`
3. Ejecutar cada request
4. Verificar que todos retornan 200 OK

### 3. Test con el Script PHP

```bash
# Acceder a:
http://tusitio.local/test-api.php

# Hacer click en "Probar Endpoint" para cada uno
# Todos deben mostrar ✅ verde
```

---

## ⚙️ Configuración Avanzada

### Personalizar Perfil de App

```
Flavor Platform > Perfil de Aplicación
```

**Configurar:**
- Nombre de la app
- Descripción
- Icono (logo)
- Color primario
- Color secundario
- Splash screen
- Términos y condiciones

**Estos datos se envían automáticamente a la app vía:**
```
/wp-json/app-discovery/v1/info
```

### Configurar Módulos Visibles en App

```
Flavor Platform > Módulos
```

**Activar solo los que quieres en la app:**
- Chat Core (requerido)
- Product Catalog
- Shopping Cart
- Booking System
- User Profiles
- etc.

**Los módulos activos aparecerán en:**
```
/wp-json/app-discovery/v1/modules
```

### Configurar Layouts para Apps

```
Flavor Platform > Layouts
```

Los layouts se sincronizan automáticamente con la app.

**La app consulta:**
```
/wp-json/app-discovery/v1/layouts
```

---

## 🔐 Seguridad

### HTTPS Requerido en Producción

Para producción, **siempre usa HTTPS**:

```bash
# ✅ Correcto
https://tudominio.com

# ❌ Inseguro (solo para desarrollo)
http://tudominio.com
```

### CORS (Cross-Origin)

Si tienes problemas de CORS, añadir a `wp-config.php`:

```php
// Permitir CORS para apps móviles
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

O usar plugin: [WP REST API CORS](https://wordpress.org/plugins/wp-cors/)

### Autenticación (Opcional)

Para endpoints protegidos, la app puede usar:

1. **JWT (JSON Web Tokens)**
2. **OAuth 2.0**
3. **Application Passwords** (WordPress 5.6+)

Configurar en: `Flavor Platform > Seguridad de API`

---

## 📞 Soporte

Si después de seguir esta guía sigues teniendo problemas:

1. **Ejecutar script de prueba:**
   ```
   http://tusitio.local/test-api.php
   ```

2. **Capturar pantalla de los errores**

3. **Verificar logs de WordPress:**
   ```bash
   wp-content/debug.log
   ```

4. **Contactar soporte:**
   - Email: support@gailu.net
   - Incluir: URL del sitio, captura de test-api.php, versión de la app

---

## ✅ Checklist Final

Antes de considerar la configuración completa, verifica:

- [ ] Permalinks configurados (no "Simple")
- [ ] `/wp-json/` retorna JSON válido
- [ ] `/wp-json/app-discovery/v1/info` funciona
- [ ] `/wp-json/app-discovery/v1/modules` retorna módulos
- [ ] Script test-api.php muestra todo en verde
- [ ] URL es accesible desde el móvil (no `.local` sin túnel)
- [ ] Motor de IA configurado (si vas a usar chat)
- [ ] Perfil de app configurado
- [ ] Módulos necesarios activados
- [ ] App puede conectarse correctamente

---

**¡Listo!** Tu app móvil debería poder conectarse sin problemas. 🚀
