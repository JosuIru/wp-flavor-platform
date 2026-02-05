# 📱 Guía del Panel de Configuración de Apps

## Acceso al Panel

Ve a **WordPress Admin** → **Flavor Chat IA** → **Apps Móviles**

---

## 🎨 Pestañas del Panel

### 1. General

Configura la información básica de tu app:

**Nombre de la App**:
- Es el nombre que aparecerá en la app móvil
- Por defecto usa el nombre del sitio
- Ejemplo: "Basabere Nueva" o "Comunidad Basabere"

**Descripción**:
- Descripción corta de tu comunidad
- Aparece en la pantalla de inicio de la app
- Máximo 2-3 líneas

---

### 2. Branding

Personaliza la apariencia de tu app:

#### Logo de la App
- **Formato recomendado**: PNG transparente
- **Tamaño recomendado**: 512x512px
- **Se usa para**: Icono de la app, pantalla de inicio, navegación

**Cómo subirlo**:
1. Click en "Seleccionar Logo"
2. Elige una imagen de tu biblioteca o sube una nueva
3. Click en "Usar este logo"

#### Colores

**Color Primario**:
- Color principal de la app
- Se usa en: Barra superior, botones principales, navegación
- Ejemplo: Verde #4CAF50

**Color Secundario**:
- Complementa al color primario
- Se usa en: Elementos secundarios, fondos
- Ejemplo: Verde claro #8BC34A

**Color de Acento**:
- Para resaltar elementos importantes
- Se usa en: Notificaciones, alertas, badges
- Ejemplo: Naranja #FF9800

**Selector de color**: Haz click en el cuadro de color para abrir el selector visual.

---

### 3. Seguridad

Gestiona los tokens de API para autenticar las apps.

#### ¿Qué son los Tokens de API?

Los tokens son "llaves" que permiten a las apps conectarse de forma segura con tu servidor. Son como contraseñas únicas para cada app.

#### Generar un Token

1. **Nombre del Token**: Dale un nombre identificativo
   - Ejemplos:
     - "App Android Producción"
     - "App iOS Testing"
     - "App Cliente Beta"

2. **Click en "Generar Token"**

3. **⚠️ IMPORTANTE**:
   - Copia el token que aparece
   - Guárdalo en un lugar seguro
   - **NO podrás verlo de nuevo**
   - Este token se usará en la configuración de la app Flutter

#### Tokens Activos

Tabla con todos los tokens generados:
- **Nombre**: Identificador del token
- **Fecha de Creación**: Cuándo se generó
- **Último Uso**: Última vez que se usó
- **Acciones**: Revocar (eliminar) el token

**Revocar un token**: Si una app está comprometida o ya no la usas, puedes revocar su token. La app dejará de funcionar hasta que generes un nuevo token.

#### Endpoints de la API

Sección informativa que muestra las URLs que las apps deben usar:
- `/app-discovery/v1/info` - Para detectar plugins
- `/app-discovery/v1/modules` - Para obtener módulos
- `/app-discovery/v1/theme` - Para obtener colores y logo

---

### 4. Módulos

Muestra los módulos disponibles para las apps.

**Información mostrada**:
- Nombre del módulo
- Descripción
- Si tiene API disponible (✓ Sí / ○ En desarrollo)
- Ruta base de los endpoints

**Módulos disponibles**:
- ✅ **Grupos de Consumo**: Pedidos colectivos
- ✅ **Banco de Tiempo**: Intercambio de servicios
- ✅ **Marketplace**: Anuncios de regalo/venta
- ✅ **WooCommerce**: Tienda online

**Activar/Desactivar módulos**:
Link directo a "Configuración de Módulos" para activar o desactivar módulos.

---

### 5. Vista Previa

Simulación visual de cómo se verá tu app con la configuración actual.

**Elementos de la vista previa**:
- Barra superior con logo y nombre
- Cards de módulos con iconos
- Botones con el color primario
- Todo en tiempo real según tu configuración

**Actualiza la vista previa**: Guarda los cambios en las pestañas anteriores y vuelve a Vista Previa para ver los resultados.

---

## 🔄 Flujo de Configuración Recomendado

### Primera Vez

1. **General**: Configura nombre y descripción
2. **Branding**: Sube tu logo y elige colores
3. **Guardar cambios**
4. **Seguridad**: Genera un token para tu app
5. **Vista Previa**: Verifica cómo se ve
6. **Módulos**: Revisa qué módulos están activos

### Cada vez que generes una APK

1. **Seguridad**: Genera un nuevo token con nombre identificativo
2. **Copia el token** y guárdalo
3. **Usa el token** en la configuración de tu app Flutter
4. **Vista Previa**: Verifica los colores antes de compilar

---

## 💡 Consejos

### Para el Logo
- ✅ Usa PNG con fondo transparente
- ✅ Diseño simple y reconocible
- ✅ Que funcione en fondos claros y oscuros
- ❌ No uses imágenes con mucho detalle
- ❌ Evita textos pequeños

### Para los Colores
- ✅ Usa colores de tu marca
- ✅ Asegura buen contraste (texto sobre fondo)
- ✅ Prueba en Vista Previa
- ❌ Evita colores muy claros o muy oscuros
- ❌ No uses colores que se parezcan mucho entre sí

### Para los Tokens
- ✅ Usa nombres descriptivos
- ✅ Un token por app (Android, iOS, Testing, etc.)
- ✅ Guárdalos en un gestor de contraseñas
- ❌ No compartas tokens públicamente
- ❌ No uses el mismo token en múltiples apps
- ❌ Revoca tokens de apps antiguas o de prueba

---

## 🔧 Configuración en Flutter

Una vez tengas tu token, úsalo en la app Flutter:

```dart
// lib/config/api_config.dart
class ApiConfig {
  static const String baseUrl = 'https://tu-sitio.com';
  static const String apiToken = 'tu-token-generado-aqui';

  static Map<String, String> get headers => {
    'Authorization': 'Bearer $apiToken',
    'Content-Type': 'application/json',
  };
}
```

---

## 🎯 QR para las Apps

Para que las apps escaneen el QR y se conecten automáticamente:

1. Las apps ya existentes de wp-calendario-experiencias funcionan tal cual
2. Escanean el QR con la URL del sitio
3. Automáticamente detectan qué plugins están activos
4. Cargan los módulos disponibles
5. Usan los colores y logo configurados aquí

**No necesitas generar un nuevo QR**, las apps existentes funcionan directamente.

---

## ❓ Preguntas Frecuentes

**¿Necesito recompilar la app cada vez que cambio colores?**
- No, los colores se obtienen de la API al iniciar la app.

**¿Qué pasa si revoco un token activo?**
- La app dejará de funcionar inmediatamente. Los usuarios tendrán que actualizar a una versión con el nuevo token.

**¿Puedo usar el mismo token para Android e iOS?**
- Sí, pero no es recomendado. Mejor usa un token diferente para cada plataforma para mayor seguridad.

**¿Los tokens expiran?**
- No, los tokens no tienen fecha de expiración. Solo dejan de funcionar si los revocas manualmente.

**¿Necesito este panel si solo uso wp-calendario-experiencias?**
- No es obligatorio, pero te permite personalizar la apariencia de las apps.

**¿Cómo sé si mi configuración está funcionando?**
- Ve a Vista Previa y verifica que todo se ve bien. Luego, en la app, escanea el QR y verifica que carga correctamente.

---

## 🆘 Solución de Problemas

### La app no conecta con el servidor

1. Verifica que el token es correcto
2. Comprueba que no has revocado el token
3. Asegúrate de que la URL del sitio es correcta
4. Verifica que los endpoints están accesibles (prueba en navegador)

### Los colores no se aplican en la app

1. Guarda los cambios en la pestaña Branding
2. Cierra y vuelve a abrir la app (limpia caché)
3. Verifica en Vista Previa que los colores se guardan correctamente

### El logo no aparece

1. Verifica que el logo se subió correctamente
2. Comprueba que el formato es compatible (PNG, JPG)
3. Asegúrate de que la imagen no es demasiado grande (>2MB)
4. Prueba subiendo otra imagen

---

## 📚 Recursos Adicionales

- **Documentación API**: `/includes/app-integration/README.md`
- **Guía de Testing**: `/includes/app-integration/TESTING.md`
- **Estado de Integración**: `/includes/app-integration/ESTADO_INTEGRACION.md`

---

**¡Panel de configuración listo para usar!** 🚀

Cualquier cambio que hagas aquí se reflejará automáticamente en las apps móviles la próxima vez que se inicien.
