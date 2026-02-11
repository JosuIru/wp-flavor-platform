import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../providers/providers.dart' show apiClientProvider;

/// Provider para módulos activos en admin (navegación dinámica y dashboard)
final adminModulesProvider = FutureProvider<List<String>>((ref) async {
  final api = ref.read(apiClientProvider);

  final response = await api.getAvailableModules();
  if (response.success && response.data != null) {
    final modules = (response.data!['modules'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .map((m) {
          final id = (m['id'] ?? m['slug'] ?? m['module'])?.toString() ?? '';
          final enabled = m['enabled'] == true ||
              m['active'] == true ||
              m['status'] == 'active' ||
              m['show_in_navigation'] == true;
          return enabled ? id : '';
        })
        .where((id) => id.isNotEmpty)
        .toList();
    return modules;
  }

  return [];
});
