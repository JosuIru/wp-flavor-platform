# 📦 Guía de Instalación - Integración APKs

## ⚠️ Importante: Evitar Conflictos de Clases

**Flavor Chat IA** y el **addon de Chat IA de wp-calendario-experiencias** NO pueden estar activos simultáneamente porque definen clases con los mismos nombres.

## Escenarios de Instalación

### Escenario 1: Solo Flavor Chat IA (Recomendado para Grupos de Consumo)

**Plugins activos**:
- ✅ Flavor Chat IA
- ❌ wp-calendario-experiencias (no necesario)

**Resultado**:
- Chat IA con múltiples proveedores (Claude, OpenAI, DeepSeek, Mistral)
- Módulos: Grupos de Consumo, Banco Tiempo, Marketplace, WooCommerce
- Perfiles de aplicación
- WPML integrado

**APKs móviles mostrarán**:
- Módulos de Flavor Chat IA solamente

### Escenario 2: Flavor Chat IA + wp-calendario-experiencias (Máxima Funcionalidad)

**Plugins activos**:
- ✅ Flavor Chat IA
- ✅ wp-calendario-experiencias
- ❌ Chat IA Addon (DEBE estar desactivado)

**Resultado**:
- Todo de Flavor Chat IA
- Sistema de reservas y tickets de Calendario Experiencias
- Sistema unificado de APKs

**APKs móviles mostrarán**:
- Módulos de Flavor Chat IA
- Módulo de Reservas de Calendario Experiencias
- Navegación unificada

**⚠️ CRÍTICO**: Desactivar el addon de Chat IA

### Escenario 3: Solo wp-calendario-experiencias (Sistema Original)

**Plugins activos**:
- ❌ Flavor Chat IA
- ✅ wp-calendario-experiencias
- ✅ Chat IA Addon

**Resultado**:
- Sistema original sin cambios

**APKs móviles mostrarán**:
- Solo módulo de Reservas

## Cómo Desactivar el Chat IA Addon

### Opción A: Desde Configuración de Calendario

1. Ve a **WordPress Admin**
2. Navega a **Calendario Experiencias** → **Configuración**
3. Busca la sección **"Addons"**
4. Desactiva el addon **"Chat IA"**
5. Guarda cambios

### Opción B: Renombrar Carpeta (Método Rápido)

```bash
cd wp-content/plugins/wp-calendario-experiencias/addons/
mv chat-ia-addon chat-ia-addon.DISABLED
```

### Opción C: Comentar en el Loader

Edita el archivo del plugin wp-calendario-experiencias que carga los addons:

```php
// Busca algo como:
// require_once __DIR__ . '/addons/chat-ia-addon/chat-ia-addon.php';

// Y comentalo:
// require_once __DIR__ . '/addons/chat-ia-addon/chat-ia-addon.php';
```

### Opción D: Desde WP-CLI

```bash
# Si el addon tiene un sistema de activación/desactivación
wp option update calendario_experiencias_addons_active '{"chat-ia": false}'
```

## Verificar que No Hay Conflictos

### Test 1: Verificar Clases

```bash
wp eval "
  \$classes_to_check = [
    'Chat_IA_Engine_Interface',
    'Flavor_Chat_IA',
    'Flavor_App_Integration'
  ];

  foreach (\$classes_to_check as \$class) {
    echo \"\$class: \" . (class_exists(\$class) ? 'EXISTS' : 'NO') . PHP_EOL;
  }
"
```

**Resultado esperado**:
```
Chat_IA_Engine_Interface: NO    # ← Importante que sea NO
Flavor_Chat_IA: EXISTS
Flavor_App_Integration: EXISTS
```

Si `Chat_IA_Engine_Interface` existe, significa que el addon de Chat IA está cargándose. Desactívalo.

### Test 2: Verificar Error Logs

```bash
# Ver si hay errores de "Cannot declare..."
tail -100 wp-content/debug.log | grep "Cannot declare"
```

**Resultado esperado**: Sin output (no errores)

Si ves algo como:
```
Fatal error: Cannot declare interface Chat_IA_Engine_Interface,
because the name is already in use...
```

Significa que ambos sistemas están cargándose. Desactiva el addon.

### Test 3: Activar Flavor Chat IA

```bash
# Intentar activar
wp plugin activate flavor-chat-ia

# Ver estado
wp plugin list | grep flavor
```

**Resultado esperado**:
```
flavor-chat-ia    1.5.0    active
```

Si falla con error fatal, revisa los logs y desactiva el addon de Chat IA.

## Pasos de Instalación Correcta

### Para Nueva Instalación

1. **Instalar Flavor Chat IA**:
   ```bash
   cd wp-content/plugins/
   # (el plugin ya está instalado)
   ```

2. **Si tienes wp-calendario-experiencias activo**:
   ```bash
   # Desactivar su addon de Chat IA
   cd wp-calendario-experiencias/addons/
   mv chat-ia-addon chat-ia-addon.DISABLED
   ```

3. **Activar Flavor Chat IA**:
   ```bash
   wp plugin activate flavor-chat-ia
   ```

4. **Verificar**:
   ```bash
   wp plugin list | grep -E "(flavor|calendario)"
   ```

5. **Probar endpoints**:
   ```bash
   curl "http://tu-sitio.com/wp-json/app-discovery/v1/info"
   ```

### Para Migración desde Chat IA Addon

Si ya tienes el addon de Chat IA de wp-calendario-experiencias activo:

1. **Exportar configuración** (si la necesitas):
   ```bash
   wp option get calendario_experiencias_chat_ia_settings > backup-chat-ia-config.json
   ```

2. **Desactivar addon**:
   ```bash
   cd wp-content/plugins/wp-calendario-experiencias/addons/
   mv chat-ia-addon chat-ia-addon.DISABLED
   ```

3. **Activar Flavor Chat IA**:
   ```bash
   wp plugin activate flavor-chat-ia
   ```

4. **Migrar configuración**:
   - Ve a **Flavor Chat IA** → **Configuración**
   - Introduce tu API key de Claude/OpenAI
   - Configura nombre del asistente
   - Activa los módulos que necesites

5. **Verificar endpoints de descubrimiento**:
   ```bash
   curl "http://tu-sitio.com/wp-json/app-discovery/v1/info"
   ```

## Configuración Post-Instalación

### 1. Configurar API Keys

Ve a **Flavor Chat IA** → **Configuración**:

- **Proveedor activo**: Claude (recomendado)
- **Claude API Key**: tu-api-key-aquí
- **Modelo**: claude-sonnet-4-20250514
- **Nombre del asistente**: "Asistente Virtual"
- **Tono**: Amigable

### 2. Activar Módulos

Ve a **Flavor Chat IA** → **Módulos**:

- ✅ Grupos de Consumo
- ✅ WooCommerce (si lo usas)
- ✅ Banco de Tiempo (opcional)
- ✅ Marketplace (opcional)

### 3. Configurar Perfil de Aplicación

Ve a **Flavor Chat IA** → **Perfiles de App**:

- Selecciona perfil: **Grupo de Consumo** (o el que necesites)
- Configura colores, logo, etc.

### 4. Probar Integración

```bash
# Ver módulos activos
curl "http://tu-sitio.com/wp-json/app-discovery/v1/modules"

# Ver tema configurado
curl "http://tu-sitio.com/wp-json/app-discovery/v1/theme"

# Probar chat
curl -X POST "http://tu-sitio.com/wp-json/unified-api/v1/chat" \
  -H "Content-Type: application/json" \
  -d '{"message":"Hola","session_id":"test123"}'
```

## Troubleshooting

### Error: "Cannot declare interface Chat_IA_Engine_Interface"

**Causa**: El addon de Chat IA sigue activo

**Solución**:
```bash
cd wp-content/plugins/wp-calendario-experiencias/addons/
mv chat-ia-addon chat-ia-addon.DISABLED
wp cache flush
```

### Error: "404 Not Found" en endpoints

**Causa**: Permalinks no actualizados

**Solución**:
```bash
wp rewrite flush
# O desde admin: Ajustes → Enlaces permanentes → Guardar
```

### Error: "500 Internal Server Error"

**Causa**: Error de PHP

**Solución**:
```bash
# Activar debug
# En wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Ver logs
tail -f wp-content/debug.log
```

### Endpoints devuelven datos vacíos

**Causa**: Plugin no se está cargando

**Solución**:
```bash
# Verificar que se carga
wp eval "echo class_exists('Flavor_App_Integration') ? 'OK' : 'FAIL';"

# Si devuelve FAIL, revisar que el archivo se carga en flavor-chat-ia.php
grep "app-integration" wp-content/plugins/flavor-chat-ia/flavor-chat-ia.php
```

## Verificación Final

Checklist para confirmar instalación correcta:

- [ ] Flavor Chat IA activo sin errores
- [ ] Chat IA Addon desactivado (si existe)
- [ ] No hay errores de "Cannot declare..." en logs
- [ ] Endpoint `/app-discovery/v1/info` funciona
- [ ] Endpoint `/app-discovery/v1/modules` lista módulos
- [ ] API keys configuradas
- [ ] Módulos activados
- [ ] Chat responde correctamente

## Siguientes Pasos

Una vez instalado correctamente:

1. ✅ Plugin instalado y funcionando
2. ⏳ Configurar módulos según necesidades
3. ⏳ Integrar con Flutter apps existentes
4. ⏳ Personalizar tema y colores
5. ⏳ Testing con apps móviles

---

**¡Sigue esta guía para una instalación sin problemas!** 📦
