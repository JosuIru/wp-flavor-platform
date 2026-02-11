import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../module_definition.dart';
import '../module_service.dart';
import '../widgets/module_empty_state.dart';

/// Builder automático de pantallas de módulo
///
/// Genera pantallas genéricas basadas en la configuración del módulo
class ModuleScreenBuilder {
  /// Construye una pantalla genérica para un módulo
  static Widget buildGenericScreen(
    BuildContext context,
    ModuleDefinition module, {
    ModuleScreenType type = ModuleScreenType.list,
  }) {
    switch (type) {
      case ModuleScreenType.list:
        return _ModuleListScreen(module: module);
      case ModuleScreenType.grid:
        return _ModuleGridScreen(module: module);
      case ModuleScreenType.detail:
        return _ModuleDetailScreen(module: module);
      case ModuleScreenType.dashboard:
        return _ModuleDashboardScreen(module: module);
    }
  }

  /// Construye una pantalla desde configuración de API
  static Widget buildFromConfig(
    BuildContext context,
    ModuleDefinition module,
    Map<String, dynamic> config,
  ) {
    final screenType = config['screen_type']?.toString();

    switch (screenType) {
      case 'list':
        return buildGenericScreen(context, module, type: ModuleScreenType.list);
      case 'grid':
        return buildGenericScreen(context, module, type: ModuleScreenType.grid);
      case 'dashboard':
        return buildGenericScreen(context, module, type: ModuleScreenType.dashboard);
      default:
        return buildGenericScreen(context, module, type: ModuleScreenType.list);
    }
  }
}

/// Pantalla genérica de lista para un módulo
class _ModuleListScreen extends ConsumerStatefulWidget {
  final ModuleDefinition module;

  const _ModuleListScreen({required this.module});

  @override
  ConsumerState<_ModuleListScreen> createState() => _ModuleListScreenState();
}

class _ModuleListScreenState extends ConsumerState<_ModuleListScreen> {
  late Future<Map<String, dynamic>?> _dataFuture;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  void _loadData() {
    _dataFuture = ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
  }

  Future<void> _refresh() async {
    setState(() {
      _loadData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.module.name),
        backgroundColor: widget.module.color,
        foregroundColor: Colors.white,
      ),
      body: FutureBuilder<Map<String, dynamic>?>(
        future: _dataFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const ModuleLoadingState();
          }

          if (snapshot.hasError || snapshot.data == null) {
            return ModuleErrorState(
              message: snapshot.error?.toString(),
              onRetry: _refresh,
            );
          }

          final data = snapshot.data!;
          final items = data['items'] as List<dynamic>? ?? [];

          if (items.isEmpty) {
            return ModuleEmptyState(
              title: 'Sin elementos',
              message: 'No hay elementos en ${widget.module.name}',
              onAction: _refresh,
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index] as Map<String, dynamic>;
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: Icon(widget.module.icon, color: widget.module.color),
                    title: Text(item['title']?.toString() ?? 'Sin título'),
                    subtitle: Text(item['subtitle']?.toString() ?? ''),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => _onItemTap(item),
                  ),
                );
              },
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _onAddItem,
        backgroundColor: widget.module.color,
        child: const Icon(Icons.add),
      ),
    );
  }

  void _onItemTap(Map<String, dynamic> item) {
    // TODO: Navegar a detalle
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Item: ${item['title']}')),
    );
  }

  void _onAddItem() {
    // TODO: Navegar a formulario de creación
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Crear nuevo elemento')),
    );
  }
}

/// Pantalla genérica de grid
class _ModuleGridScreen extends StatelessWidget {
  final ModuleDefinition module;

  const _ModuleGridScreen({required this.module});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(module.name),
        backgroundColor: module.color,
        foregroundColor: Colors.white,
      ),
      body: Center(
        child: Text('Vista de Grid para ${module.name}'),
      ),
    );
  }
}

/// Pantalla genérica de detalle
class _ModuleDetailScreen extends StatelessWidget {
  final ModuleDefinition module;

  const _ModuleDetailScreen({required this.module});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(module.name),
        backgroundColor: module.color,
        foregroundColor: Colors.white,
      ),
      body: Center(
        child: Text('Vista de Detalle para ${module.name}'),
      ),
    );
  }
}

/// Pantalla genérica de dashboard
class _ModuleDashboardScreen extends StatelessWidget {
  final ModuleDefinition module;

  const _ModuleDashboardScreen({required this.module});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(module.name),
        backgroundColor: module.color,
        foregroundColor: Colors.white,
      ),
      body: Center(
        child: Text('Dashboard para ${module.name}'),
      ),
    );
  }
}

/// Tipos de pantalla de módulo
enum ModuleScreenType {
  list,
  grid,
  detail,
  dashboard,
}
