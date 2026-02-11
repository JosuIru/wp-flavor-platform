import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../module_definition.dart';
import '../builders/module_screen_builder.dart';

/// Cargador lazy de módulos
///
/// Carga módulos y sus pantallas solo cuando son necesarios
class ModuleLazyLoader {
  static final ModuleLazyLoader _instance = ModuleLazyLoader._internal();
  factory ModuleLazyLoader() => _instance;
  ModuleLazyLoader._internal();

  // Cache de módulos cargados
  final Map<String, ModuleDefinition> _loadedModules = {};

  // Cache de constructores de pantallas
  final Map<String, Widget Function(BuildContext)> _screenBuilders = {};

  /// Registra un constructor de pantalla para un módulo
  void registerScreenBuilder(
    String moduleId,
    Widget Function(BuildContext) builder,
  ) {
    _screenBuilders[moduleId] = builder;
    debugPrint('Screen builder registrado para: $moduleId');
  }

  /// Obtiene el constructor de pantalla para un módulo
  Widget Function(BuildContext)? getScreenBuilder(String moduleId) {
    return _screenBuilders[moduleId];
  }

  /// Carga un módulo bajo demanda
  Future<ModuleDefinition?> loadModule(String moduleId) async {
    // Si ya está cargado, devolverlo
    if (_loadedModules.containsKey(moduleId)) {
      return _loadedModules[moduleId];
    }

    // Simular carga (aquí se haría una petición a la API si es necesario)
    await Future.delayed(const Duration(milliseconds: 100));

    // TODO: Cargar desde API si no está en caché
    debugPrint('Módulo cargado lazy: $moduleId');

    return _loadedModules[moduleId];
  }

  /// Precarga módulos en segundo plano
  Future<void> preloadModules(List<String> moduleIds) async {
    for (final moduleId in moduleIds) {
      if (!_loadedModules.containsKey(moduleId)) {
        await loadModule(moduleId);
      }
    }
    debugPrint('Precargados ${moduleIds.length} módulos');
  }

  /// Descarga módulos que no se están usando
  void unloadModule(String moduleId) {
    _loadedModules.remove(moduleId);
    _screenBuilders.remove(moduleId);
    debugPrint('Módulo descargado: $moduleId');
  }

  /// Limpia todos los módulos cargados
  void clearAll() {
    _loadedModules.clear();
    _screenBuilders.clear();
    debugPrint('Todos los módulos descargados');
  }

  /// Obtiene la cantidad de módulos cargados
  int get loadedCount => _loadedModules.length;

  /// Verifica si un módulo está cargado
  bool isLoaded(String moduleId) {
    return _loadedModules.containsKey(moduleId);
  }
}

/// Widget que carga un módulo de forma lazy
class LazyModuleScreen extends StatefulWidget {
  final String moduleId;
  final ModuleDefinition? module;

  const LazyModuleScreen({
    super.key,
    required this.moduleId,
    this.module,
  });

  @override
  State<LazyModuleScreen> createState() => _LazyModuleScreenState();
}

class _LazyModuleScreenState extends State<LazyModuleScreen> {
  late Future<Widget> _screenFuture;

  @override
  void initState() {
    super.initState();
    _screenFuture = _loadScreen();
  }

  Future<Widget> _loadScreen() async {
    final loader = ModuleLazyLoader();

    // Verificar si hay un builder registrado
    final builder = loader.getScreenBuilder(widget.moduleId);
    if (builder != null) {
      return builder(context);
    }

    // Si no hay builder, usar el genérico
    final module = widget.module ?? await loader.loadModule(widget.moduleId);

    if (module == null) {
      return const _ErrorScreen(message: 'Módulo no encontrado');
    }

    return ModuleScreenBuilder.buildGenericScreen(context, module);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Widget>(
      future: _screenFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Scaffold(
            body: Center(
              child: CircularProgressIndicator(),
            ),
          );
        }

        if (snapshot.hasError) {
          return _ErrorScreen(message: snapshot.error.toString());
        }

        return snapshot.data ?? const _ErrorScreen();
      },
    );
  }
}

class _ErrorScreen extends StatelessWidget {
  final String message;

  const _ErrorScreen({this.message = 'Error al cargar módulo'});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Error')),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text(message),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Volver'),
            ),
          ],
        ),
      ),
    );
  }
}

/// Provider de Riverpod para el lazy loader
final moduleLazyLoaderProvider = Provider<ModuleLazyLoader>((ref) {
  return ModuleLazyLoader();
});
