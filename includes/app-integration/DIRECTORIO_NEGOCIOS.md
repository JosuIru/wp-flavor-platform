# 📍 Directorio Público de Negocios

## ¿Qué es?

El Directorio de Negocios permite que los usuarios de las apps descubran y se conecten a diferentes negocios/comunidades que tengan los plugins instalados, **sin tener que cambiar de app**.

## 🎯 Objetivo

Crear un ecosistema donde:
- **Una sola app** funciona con múltiples negocios
- Los usuarios pueden **cambiar de negocio** dentro de la misma app
- Los negocios se **descubren automáticamente**
- Todo es **plug and play** (sin recompilar)

---

## 🏗️ Arquitectura

### Backend (WordPress)

#### Endpoints disponibles:

**1. Listar negocios públicos**
```
GET /wp-json/app-discovery/v1/businesses

Parámetros opcionales:
- region: euskal_herria, cataluna, madrid, etc.
- category: cooperativa, asociacion, comunidad, etc.
- search: término de búsqueda
- limit: número máximo de resultados (default: 50)

Respuesta:
{
  "success": true,
  "businesses": [
    {
      "url": "https://basabere.com",
      "name": "Basabere Nueva",
      "description": "Comunidad de economía social",
      "logo": "https://basabere.com/logo.png",
      "region": "euskal_herria",
      "category": "comunidad",
      "systems": ["flavor-chat-ia"],
      "modules": ["grupos_consumo", "banco_tiempo"],
      "language": "eu",
      "last_updated": "2026-01-28T10:30:00+01:00"
    }
  ],
  "total": 15,
  "regions": { /* mapa de regiones */ },
  "categories": { /* mapa de categorías */ }
}
```

**2. Registrar negocio**
```
POST /wp-json/app-discovery/v1/businesses/register

Requiere autenticación de administrador

Respuesta:
{
  "success": true,
  "message": "Negocio registrado exitosamente",
  "business": { /* datos del negocio */ }
}
```

**3. Verificar un negocio**
```
POST /wp-json/app-discovery/v1/businesses/verify

Body:
{
  "url": "https://ejemplo.com"
}

Respuesta:
{
  "success": true,
  "business": {
    "url": "https://ejemplo.com",
    "name": "Ejemplo",
    "systems": ["flavor-chat-ia"],
    /* ... */
  }
}
```

---

## 📱 Frontend (Flutter)

### Flujo del usuario:

1. **Usuario abre la app** conectada a Negocio A
2. **Tap en "Cambiar de negocio"**
3. **Ve lista de negocios disponibles** filtrados por región
4. **Selecciona Negocio B**
5. **La app se conecta automáticamente** a Negocio B
6. **UI se actualiza** con logo y colores de Negocio B
7. **Módulos se cargan** según lo que tenga Negocio B

---

## 💻 Configuración en WordPress

### Activar el directorio:

1. Ve a **Flavor Chat IA** → **Apps Móviles** → **Directorio**

2. Activa **"Aparecer en el Directorio"**

3. Configura:
   - **Región**: Euskal Herria, Cataluña, etc.
   - **Categoría**: Cooperativa, Asociación, etc.

4. **Guarda cambios**

5. Click en **"Registrar Ahora"** para sincronizar con el directorio central

### Información que se comparte:

✅ **Pública** (visible para todos):
- Nombre del negocio
- Descripción
- Logo
- URL del sitio
- Región y categoría
- Módulos disponibles

❌ **Privada** (NUNCA se comparte):
- Tokens de API
- Datos de usuarios
- Contenido de posts
- Información sensible

---

## 🔐 Seguridad

### Tokens por negocio

Cada negocio tiene sus propios tokens de API:
- Un usuario conectado a Negocio A usa el token de A
- Al cambiar a Negocio B, usa el token de B
- Los tokens se almacenan de forma segura en la app

### Validación

- Solo negocios **verificados** aparecen en el directorio
- Se verifica que `/wp-json/app-discovery/v1/info` responda correctamente
- Se comprueba que tengan al menos un plugin instalado

---

## 🌍 Servidor Central (Futuro)

Actualmente, el sistema funciona de forma **local** (cada WordPress mantiene su caché).

**Próxima fase**: Servidor central que:
- Agrega todos los negocios registrados
- Mantiene un índice actualizado
- Permite búsquedas globales
- Proporciona estadísticas

**URL propuesta**: `https://directory.flavorapps.com`

---

## 📊 Casos de Uso

### Caso 1: Usuario nómada

María vive en Bilbao pero viaja a Barcelona:
1. Tiene la app configurada con su cooperativa de Bilbao
2. En Barcelona, abre la app y busca negocios en Cataluña
3. Encuentra una cooperativa en Barcelona
4. Se conecta con un tap
5. Puede hacer pedidos en esa cooperativa
6. Al volver a Bilbao, cambia de nuevo a su cooperativa

### Caso 2: Usuario multi-comunidad

Jon participa en 3 comunidades diferentes:
1. Grupo de consumo en su barrio
2. Banco de tiempo en su ciudad
3. Marketplace regional
4. Cambia entre ellas según necesite
5. Todo desde la misma app

### Caso 3: Expansión regional

Una federación de cooperativas:
1. Cada cooperativa instala el plugin
2. Todas aparecen en el directorio
3. Los miembros pueden usar servicios de cualquier cooperativa
4. Fortalece la red de economía social

---

## 🎨 UI/UX en Flutter

### Pantalla de cambio de negocio:

```
┌─────────────────────────────────────┐
│ ← Cambiar de Negocio                │
├─────────────────────────────────────┤
│                                     │
│ 🔍 Buscar por nombre...             │
│                                     │
│ 📍 Filtrar por región               │
│    [Euskal Herria ▼]                │
│                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━   │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ 🏢 Basabere Nueva              │ │
│ │ Comunidad de economía social   │ │
│ │ 📍 Euskal Herria               │ │
│ │ ✓ Grupos Consumo ✓ Banco Tiempo│ │
│ └─────────────────────────────────┘ │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ 🏪 Cooperativa Garapen         │ │
│ │ Cooperativa de consumo         │ │
│ │ 📍 Euskal Herria               │ │
│ │ ✓ Grupos Consumo ✓ WooCommerce │ │
│ └─────────────────────────────────┘ │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ 🌱 Cooperativa La Revolta      │ │
│ │ Cooperativa agroecológica      │ │
│ │ 📍 Cataluña                    │ │
│ │ ✓ Marketplace ✓ Banco Tiempo   │ │
│ └─────────────────────────────────┘ │
│                                     │
└─────────────────────────────────────┘
```

### Flujo de conexión:

```
1. Usuario hace tap en un negocio
2. Mostrar loading: "Conectando con [Nombre]..."
3. Llamar a /app-discovery/v1/info
4. Obtener tema, módulos, etc.
5. Guardar configuración localmente
6. Recargar la app con nueva configuración
7. Mostrar mensaje: "Conectado a [Nombre]"
```

---

## 🧪 Testing

### Probar el endpoint:

```bash
# Listar negocios
curl "http://basaberenueva.local/wp-json/app-discovery/v1/businesses"

# Filtrar por región
curl "http://basaberenueva.local/wp-json/app-discovery/v1/businesses?region=euskal_herria"

# Buscar por nombre
curl "http://basaberenueva.local/wp-json/app-discovery/v1/businesses?search=basabere"

# Verificar un negocio
curl -X POST "http://basaberenueva.local/wp-json/app-discovery/v1/businesses/verify" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://basabere.com"}'
```

---

## 📈 Roadmap

### Fase 1: Local ✅ (COMPLETADO)
- [x] Endpoint de listado
- [x] Endpoint de registro
- [x] Endpoint de verificación
- [x] Panel de admin
- [x] Configuración de región/categoría

### Fase 2: Flutter 🚧 (EN PROGRESO)
- [ ] Servicio de directorio
- [ ] Pantalla de cambio de negocio
- [ ] Gestión multi-sitio
- [ ] Caché local de negocios

### Fase 3: Servidor Central 📅 (FUTURO)
- [ ] API central agregadora
- [ ] Sincronización automática
- [ ] Búsqueda global
- [ ] Estadísticas y analytics

---

## 🎉 Beneficios

### Para Usuarios:
- ✅ Una sola app para todo
- ✅ Descubrir nuevos negocios fácilmente
- ✅ Cambiar entre comunidades sin problemas
- ✅ Experiencia consistente

### Para Negocios:
- ✅ Mayor visibilidad
- ✅ Acceso a una red más amplia
- ✅ Fácil onboarding de nuevos miembros
- ✅ Costos reducidos (una app para todos)

### Para la Red:
- ✅ Fortalece la economía social
- ✅ Facilita la colaboración
- ✅ Efecto red (más negocios = más usuarios)
- ✅ Escalable globalmente

---

## 🆘 Soporte

**Documentación**:
- `README.md` - Guía general del sistema
- `GUIA_PANEL_APPS.md` - Panel de configuración
- `TESTING.md` - Cómo probar endpoints

**Testing rápido**:
```bash
curl "http://tu-sitio.com/wp-json/app-discovery/v1/businesses" | jq
```

---

**Sistema de Directorio de Negocios** ✨
**Backend completo y listo para Flutter** 🚀
