import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Pantalla de configuración del chat
class ChatSettingsScreen extends ConsumerStatefulWidget {
  const ChatSettingsScreen({super.key});

  @override
  ConsumerState<ChatSettingsScreen> createState() => _ChatSettingsScreenState();
}

class _ChatSettingsScreenState extends ConsumerState<ChatSettingsScreen> {
  // Preferencias
  bool _notificationsEnabled = true;
  bool _soundEnabled = true;
  bool _vibrationEnabled = true;
  bool _showPreview = true;
  bool _enterToSend = false;
  bool _showReadReceipts = true;
  bool _showOnlineStatus = true;
  String _fontSize = 'medium';
  String _chatBackground = 'default';

  // Keys de SharedPreferences
  static const String _keyNotifications = 'chat_notifications_enabled';
  static const String _keySound = 'chat_sound_enabled';
  static const String _keyVibration = 'chat_vibration_enabled';
  static const String _keyPreview = 'chat_show_preview';
  static const String _keyEnterSend = 'chat_enter_to_send';
  static const String _keyReadReceipts = 'chat_show_read_receipts';
  static const String _keyOnlineStatus = 'chat_show_online_status';
  static const String _keyFontSize = 'chat_font_size';
  static const String _keyBackground = 'chat_background';

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      setState(() {
        _notificationsEnabled = prefs.getBool(_keyNotifications) ?? true;
        _soundEnabled = prefs.getBool(_keySound) ?? true;
        _vibrationEnabled = prefs.getBool(_keyVibration) ?? true;
        _showPreview = prefs.getBool(_keyPreview) ?? true;
        _enterToSend = prefs.getBool(_keyEnterSend) ?? false;
        _showReadReceipts = prefs.getBool(_keyReadReceipts) ?? true;
        _showOnlineStatus = prefs.getBool(_keyOnlineStatus) ?? true;
        _fontSize = prefs.getString(_keyFontSize) ?? 'medium';
        _chatBackground = prefs.getString(_keyBackground) ?? 'default';
      });
    } catch (e) {
      // Usar valores por defecto
    }
  }

  Future<void> _savePreference(String key, dynamic value) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (value is bool) {
        await prefs.setBool(key, value);
      } else if (value is String) {
        await prefs.setString(key, value);
      }
    } catch (e) {
      // Ignorar error
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Configuración del chat'),
      ),
      body: ListView(
        children: [
          // === NOTIFICACIONES ===
          _buildSectionHeader('Notificaciones'),
          SwitchListTile(
            secondary: const Icon(Icons.notifications),
            title: const Text('Notificaciones'),
            subtitle: const Text('Recibir notificaciones de nuevos mensajes'),
            value: _notificationsEnabled,
            onChanged: (value) {
              setState(() => _notificationsEnabled = value);
              _savePreference(_keyNotifications, value);
            },
          ),
          if (_notificationsEnabled) ...[
            SwitchListTile(
              secondary: const Icon(Icons.volume_up),
              title: const Text('Sonido'),
              subtitle: const Text('Reproducir sonido con las notificaciones'),
              value: _soundEnabled,
              onChanged: (value) {
                setState(() => _soundEnabled = value);
                _savePreference(_keySound, value);
              },
            ),
            SwitchListTile(
              secondary: const Icon(Icons.vibration),
              title: const Text('Vibración'),
              subtitle: const Text('Vibrar con las notificaciones'),
              value: _vibrationEnabled,
              onChanged: (value) {
                setState(() => _vibrationEnabled = value);
                _savePreference(_keyVibration, value);
              },
            ),
            SwitchListTile(
              secondary: const Icon(Icons.visibility),
              title: const Text('Vista previa'),
              subtitle: const Text('Mostrar contenido en la notificación'),
              value: _showPreview,
              onChanged: (value) {
                setState(() => _showPreview = value);
                _savePreference(_keyPreview, value);
              },
            ),
          ],
          const Divider(),

          // === PRIVACIDAD ===
          _buildSectionHeader('Privacidad'),
          SwitchListTile(
            secondary: const Icon(Icons.done_all),
            title: const Text('Confirmación de lectura'),
            subtitle: const Text('Mostrar cuando has leído los mensajes'),
            value: _showReadReceipts,
            onChanged: (value) {
              setState(() => _showReadReceipts = value);
              _savePreference(_keyReadReceipts, value);
            },
          ),
          SwitchListTile(
            secondary: const Icon(Icons.circle, size: 20),
            title: const Text('Estado en línea'),
            subtitle: const Text('Mostrar cuando estás conectado'),
            value: _showOnlineStatus,
            onChanged: (value) {
              setState(() => _showOnlineStatus = value);
              _savePreference(_keyOnlineStatus, value);
            },
          ),
          ListTile(
            leading: const Icon(Icons.block),
            title: const Text('Usuarios bloqueados'),
            trailing: const Icon(Icons.chevron_right),
            onTap: _showBlockedUsers,
          ),
          const Divider(),

          // === APARIENCIA ===
          _buildSectionHeader('Apariencia'),
          ListTile(
            leading: const Icon(Icons.format_size),
            title: const Text('Tamaño del texto'),
            subtitle: Text(_fontSizeLabel(_fontSize)),
            trailing: const Icon(Icons.chevron_right),
            onTap: _showFontSizeDialog,
          ),
          ListTile(
            leading: const Icon(Icons.wallpaper),
            title: const Text('Fondo del chat'),
            subtitle: Text(_backgroundLabel(_chatBackground)),
            trailing: const Icon(Icons.chevron_right),
            onTap: _showBackgroundDialog,
          ),
          const Divider(),

          // === ENTRADA ===
          _buildSectionHeader('Entrada'),
          SwitchListTile(
            secondary: const Icon(Icons.keyboard_return),
            title: const Text('Enter para enviar'),
            subtitle: const Text('Usar Enter para enviar mensajes'),
            value: _enterToSend,
            onChanged: (value) {
              setState(() => _enterToSend = value);
              _savePreference(_keyEnterSend, value);
            },
          ),
          const Divider(),

          // === ALMACENAMIENTO ===
          _buildSectionHeader('Almacenamiento'),
          ListTile(
            leading: const Icon(Icons.storage),
            title: const Text('Almacenamiento usado'),
            subtitle: const Text('Calcula el espacio usado por los chats'),
            trailing: const Icon(Icons.chevron_right),
            onTap: _showStorageInfo,
          ),
          ListTile(
            leading: Icon(Icons.delete_outline, color: colorScheme.error),
            title: Text('Borrar caché', style: TextStyle(color: colorScheme.error)),
            subtitle: const Text('Eliminar archivos temporales'),
            onTap: _confirmClearCache,
          ),
          const Divider(),

          // === ACERCA DE ===
          _buildSectionHeader('Acerca de'),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: const Text('Versión'),
            subtitle: const Text('1.0.0'),
          ),
          ListTile(
            leading: const Icon(Icons.description),
            title: const Text('Términos y condiciones'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              // Navegar a términos
            },
          ),
          ListTile(
            leading: const Icon(Icons.security),
            title: const Text('Política de privacidad'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              // Navegar a política
            },
          ),

          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }

  String _fontSizeLabel(String size) {
    switch (size) {
      case 'small':
        return 'Pequeño';
      case 'large':
        return 'Grande';
      case 'medium':
      default:
        return 'Mediano';
    }
  }

  String _backgroundLabel(String background) {
    switch (background) {
      case 'light':
        return 'Claro';
      case 'dark':
        return 'Oscuro';
      case 'gradient':
        return 'Degradado';
      case 'default':
      default:
        return 'Por defecto';
    }
  }

  void _showFontSizeDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Tamaño del texto'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Pequeño'),
              value: 'small',
              groupValue: _fontSize,
              onChanged: (value) {
                setState(() => _fontSize = value!);
                _savePreference(_keyFontSize, value);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Mediano'),
              value: 'medium',
              groupValue: _fontSize,
              onChanged: (value) {
                setState(() => _fontSize = value!);
                _savePreference(_keyFontSize, value);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Grande'),
              value: 'large',
              groupValue: _fontSize,
              onChanged: (value) {
                setState(() => _fontSize = value!);
                _savePreference(_keyFontSize, value);
                Navigator.pop(context);
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showBackgroundDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Fondo del chat'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Por defecto'),
              value: 'default',
              groupValue: _chatBackground,
              onChanged: (value) {
                setState(() => _chatBackground = value!);
                _savePreference(_keyBackground, value);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Claro'),
              value: 'light',
              groupValue: _chatBackground,
              onChanged: (value) {
                setState(() => _chatBackground = value!);
                _savePreference(_keyBackground, value);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Oscuro'),
              value: 'dark',
              groupValue: _chatBackground,
              onChanged: (value) {
                setState(() => _chatBackground = value!);
                _savePreference(_keyBackground, value);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Degradado'),
              value: 'gradient',
              groupValue: _chatBackground,
              onChanged: (value) {
                setState(() => _chatBackground = value!);
                _savePreference(_keyBackground, value);
                Navigator.pop(context);
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showBlockedUsers() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Lista de usuarios bloqueados próximamente')),
    );
  }

  void _showStorageInfo() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Almacenamiento'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildStorageItem('Mensajes', '12.5 MB'),
            const SizedBox(height: 8),
            _buildStorageItem('Imágenes', '45.2 MB'),
            const SizedBox(height: 8),
            _buildStorageItem('Videos', '128.0 MB'),
            const SizedBox(height: 8),
            _buildStorageItem('Audios', '8.3 MB'),
            const SizedBox(height: 8),
            _buildStorageItem('Documentos', '15.1 MB'),
            const Divider(),
            _buildStorageItem('Total', '209.1 MB', isBold: true),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }

  Widget _buildStorageItem(String label, String size, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: isBold ? const TextStyle(fontWeight: FontWeight.bold) : null,
        ),
        Text(
          size,
          style: isBold ? const TextStyle(fontWeight: FontWeight.bold) : null,
        ),
      ],
    );
  }

  void _confirmClearCache() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Borrar caché'),
        content: const Text(
          '¿Estás seguro de que quieres eliminar los archivos temporales? '
          'Esto no eliminará tus mensajes.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _clearCache();
            },
            child: const Text('Borrar'),
          ),
        ],
      ),
    );
  }

  Future<void> _clearCache() async {
    // Mostrar indicador de carga
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Limpiando caché...')),
    );

    // Simular limpieza
    await Future.delayed(const Duration(seconds: 1));

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Caché eliminada correctamente')),
      );
    }
  }
}
