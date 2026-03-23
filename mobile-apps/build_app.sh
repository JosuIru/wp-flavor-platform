#!/bin/bash
# Script para construir las apps con los entry points correctos

FLAVOR=$1
MODE=${2:-debug}

if [ -z "$FLAVOR" ]; then
    echo "Uso: ./build_app.sh <flavor> [mode]"
    echo "  flavor: client | admin"
    echo "  mode: debug | release (default: debug)"
    exit 1
fi

case $FLAVOR in
    client)
        TARGET="lib/main_client.dart"
        ;;
    admin)
        TARGET="lib/main_admin.dart"
        ;;
    *)
        echo "Flavor desconocido: $FLAVOR"
        echo "Usa: client | admin"
        exit 1
        ;;
esac

echo "Construyendo $FLAVOR ($MODE) con target $TARGET..."
flutter build apk --$MODE --flavor $FLAVOR -t $TARGET

if [ $? -eq 0 ]; then
    APK_PATH="build/app/outputs/flutter-apk/app-$FLAVOR-$MODE.apk"
    echo ""
    echo "APK generado: $APK_PATH"
    echo ""
    echo "Para instalar: adb install -r $APK_PATH"
fi
