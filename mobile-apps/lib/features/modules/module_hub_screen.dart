import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;
import '../../core/modules/module_definition.dart';
import '../../core/modules/module_screen_registry.dart';
import 'module_client_dashboard_screen.dart';

class ModuleHubScreen extends ConsumerWidget {
  final bool isAdmin;

  const ModuleHubScreen({
    super.key,
    this.isAdmin = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.moduleHubTitle),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: api.getAvailableModules(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.moduleHubError));
          }
          final modules = (response.data!['modules'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          if (modules.isEmpty) {
            return Center(child: Text(i18n.moduleHubEmpty));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: modules.length + 1,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              if (index == 0) {
                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    leading: const Icon(Icons.dashboard_outlined),
                    title: Text(i18n.clientDashboardTitle),
                    subtitle: Text(i18n.clientDashboardSubtitle),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const ModuleClientDashboardScreen(),
                        ),
                      );
                    },
                  ),
                );
              }

              final module = modules[index - 1];
              final id = module['id']?.toString() ?? '';
              final name = module['name']?.toString() ?? id;
              final description = module['description']?.toString() ?? '';
              final color = _parseColor(module['color']?.toString());
              final icon = _mapIcon(module['icon']?.toString());

              return Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: color ?? Theme.of(context).colorScheme.primaryContainer,
                    child: Icon(icon, color: Theme.of(context).colorScheme.onPrimaryContainer),
                  ),
                  title: Text(name),
                  subtitle: Text(description.isNotEmpty ? description : i18n.moduleHubNoDescription),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => _openModule(context, id, name, description),
                ),
              );
            },
          );
        },
      ),
    );
  }

  void _openModule(
    BuildContext context,
    String id,
    String name,
    String description,
  ) {
    // Crear definición del módulo desde los datos de la API
    final module = ModuleDefinition(
      id: id,
      name: name,
      description: description,
      icon: _mapIcon(null),
      color: Colors.blue,
      category: ModuleCategory.other,
      isActive: true,
      endpoints: const [],
    );

    // Usar el registro para obtener la pantalla (implementada o genérica)
    final registry = ModuleScreenRegistry();
    final screen = registry.getScreenForModule(
      context,
      module,
      fallbackTitle: name,
      fallbackDescription: description,
    );

    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  Color? _parseColor(String? value) {
    if (value == null || value.isEmpty) return null;
    final clean = value.replaceAll('#', '');
    final hex = clean.length == 6 ? 'FF$clean' : clean;
    try {
      return Color(int.parse(hex, radix: 16));
    } catch (_) {
      return null;
    }
  }

  IconData _mapIcon(String? icon) {
    switch (icon) {
      case 'calendar':
      case 'calendar_today':
        return Icons.calendar_today;
      case 'store':
      case 'storefront':
        return Icons.storefront;
      case 'groups':
      case 'groups_2':
        return Icons.groups;
      case 'handshake':
        return Icons.handshake;
      case 'event':
        return Icons.event;
      case 'receipt':
        return Icons.receipt;
      case 'people':
        return Icons.people;
      case 'chat':
      case 'chat_bubble':
        return Icons.chat;
      default:
        return Icons.extension;
    }
  }
}
