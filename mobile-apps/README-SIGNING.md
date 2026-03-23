# Firma de APKs para Producción

## Resumen

Para publicar en Google Play Store, las APKs deben estar firmadas con un keystore de producción. Este documento explica cómo configurarlo.

## Requisitos

- Java JDK instalado (para `keytool`)
- Flutter SDK

## Configuración Rápida

### 1. Generar Keystore

```bash
cd mobile-apps/scripts
chmod +x generate-keystore.sh
./generate-keystore.sh
```

El script te guiará para:
- Crear contraseñas seguras
- Generar el keystore
- Configurar `key.properties`

### 2. Verificar Configuración

```bash
# Ver información del keystore
keytool -list -v -keystore keystores/flavor-release.jks

# Verificar que key.properties existe
cat android/key.properties
```

### 3. Compilar APK de Release

```bash
./build_app_v2.sh client --release
```

## Estructura de Archivos

```
mobile-apps/
├── keystores/
│   └── flavor-release.jks      # Keystore (NO commitear)
├── android/
│   ├── key.properties          # Config firma (NO commitear)
│   └── key.properties.example  # Template (SI commitear)
└── scripts/
    ├── generate-keystore.sh    # Genera keystore
    └── backup-keystore.sh      # Crea backup
```

## Configuración Manual

Si prefieres hacerlo manualmente:

### 1. Generar Keystore

```bash
mkdir -p keystores

keytool -genkey -v \
  -keystore keystores/flavor-release.jks \
  -alias flavor-release-key \
  -keyalg RSA \
  -keysize 2048 \
  -validity 10000
```

### 2. Crear key.properties

```properties
# android/key.properties
storePassword=tu_contraseña_store
keyPassword=tu_contraseña_key
keyAlias=flavor-release-key
storeFile=../../keystores/flavor-release.jks
```

### 3. Configurar build.gradle

El archivo `android/app/build.gradle` ya está configurado para leer `key.properties`:

```gradle
def keystoreProperties = new Properties()
def keystorePropertiesFile = rootProject.file('key.properties')
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

android {
    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile file(keystoreProperties['storeFile'])
            storePassword keystoreProperties['storePassword']
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
        }
    }
}
```

## Backup del Keystore

**CRÍTICO:** Si pierdes el keystore, no podrás publicar actualizaciones de la app en Play Store.

### Crear Backup

```bash
./scripts/backup-keystore.sh
```

### Almacenamiento Recomendado

1. **USB externo** (offline)
2. **Gestor de contraseñas** (1Password, Bitwarden)
3. **Caja fuerte física**
4. **Almacenamiento cifrado en la nube** (con 2FA)

### Información a Guardar

- `flavor-release.jks` - El keystore
- `key.properties` - Las contraseñas
- Contraseña del keystore
- Contraseña de la clave
- Alias de la clave

## Google Play App Signing

Google Play ofrece [App Signing by Google Play](https://developer.android.com/studio/publish/app-signing#app-signing-google-play), donde Google gestiona la clave de firma.

### Ventajas
- Si pierdes tu keystore, puedes seguir publicando
- Protección adicional de Google
- App Bundles optimizados

### Configuración
1. Genera un "upload key" (tu keystore actual)
2. Sube la primera versión a Play Console
3. Google te pedirá inscribirte en App Signing
4. Google gestionará la clave de firma final

## Troubleshooting

### Error: keystore not found

```
Execution failed for task ':app:validateSigningRelease'.
> Keystore file not found
```

**Solución:** Verifica que `storeFile` en `key.properties` apunta al keystore correcto.

### Error: wrong password

```
keytool error: java.io.IOException: Keystore was tampered with, or password was incorrect
```

**Solución:** Verifica las contraseñas en `key.properties`.

### Error: key not found

```
Execution failed for task ':app:packageRelease'.
> A failure occurred while executing ...
> No key with alias 'xxx' found in keystore
```

**Solución:** Verifica que `keyAlias` coincide con el alias usado al crear el keystore.

## Verificar APK Firmado

```bash
# Ver información de firma
apksigner verify --print-certs app-release.apk

# O con jarsigner
jarsigner -verify -verbose -certs app-release.apk
```

## Seguridad

- ✅ Usa contraseñas fuertes (16+ caracteres)
- ✅ Guarda múltiples backups en lugares diferentes
- ✅ Nunca commits keystores ni key.properties
- ✅ Usa variables de entorno en CI/CD
- ❌ No compartas contraseñas por chat/email
- ❌ No uses contraseñas obvias

## CI/CD

Para GitHub Actions u otros CI:

```yaml
# .github/workflows/build.yml
env:
  KEYSTORE_BASE64: ${{ secrets.KEYSTORE_BASE64 }}
  STORE_PASSWORD: ${{ secrets.STORE_PASSWORD }}
  KEY_PASSWORD: ${{ secrets.KEY_PASSWORD }}

steps:
  - name: Decode Keystore
    run: |
      echo "$KEYSTORE_BASE64" | base64 -d > keystores/flavor-release.jks

  - name: Create key.properties
    run: |
      cat > android/key.properties << EOF
      storePassword=$STORE_PASSWORD
      keyPassword=$KEY_PASSWORD
      keyAlias=flavor-release-key
      storeFile=../../keystores/flavor-release.jks
      EOF
```

Para codificar el keystore en base64:
```bash
base64 -i keystores/flavor-release.jks -o keystore.base64.txt
```
