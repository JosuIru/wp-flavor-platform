/// Sync Provider
///
/// Provider de Riverpod para gestionar el estado de sincronización
/// de la app con sitios WordPress.

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/app_sync_service.dart';
import '../../features/layouts/layout_config.dart';

/// Estado de la sincronización
class SyncState {
  final SyncStatus status;
  final String? siteUrl;
  final String? siteName;
  final String? error;
  final bool isLoading;
  final DateTime? lastSync;
  final LayoutConfig? layoutConfig;
  final LayoutTheme? theme;

  const SyncState({
    this.status = SyncStatus.idle,
    this.siteUrl,
    this.siteName,
    this.error,
    this.isLoading = false,
    this.lastSync,
    this.layoutConfig,
    this.theme,
  });

  SyncState copyWith({
    SyncStatus? status,
    String? siteUrl,
    String? siteName,
    String? error,
    bool? isLoading,
    DateTime? lastSync,
    LayoutConfig? layoutConfig,
    LayoutTheme? theme,
  }) {
    return SyncState(
      status: status ?? this.status,
      siteUrl: siteUrl ?? this.siteUrl,
      siteName: siteName ?? this.siteName,
      error: error,
      isLoading: isLoading ?? this.isLoading,
      lastSync: lastSync ?? this.lastSync,
      layoutConfig: layoutConfig ?? this.layoutConfig,
      theme: theme ?? this.theme,
    );
  }

  bool get isSynced => siteUrl != null && siteUrl!.isNotEmpty;

  /// Obtiene ThemeData basado en la configuración
  ThemeData getThemeData({Brightness brightness = Brightness.light}) {
    if (theme != null) {
      return theme!.toThemeData(brightness: brightness);
    }
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: Colors.blue,
        brightness: brightness,
      ),
    );
  }
}

/// Notifier para gestionar la sincronización
class SyncNotifier extends StateNotifier<SyncState> {
  final AppSyncService _syncService = AppSyncService();

  SyncNotifier() : super(const SyncState()) {
    _initialize();
  }

  /// Inicializar estado desde caché
  Future<void> _initialize() async {
    state = state.copyWith(isLoading: true);

    await _syncService.initialize();

    // Cargar estado cacheado
    final layoutService = LayoutService();

    state = state.copyWith(
      status: _syncService.status,
      siteUrl: _syncService.currentSiteUrl,
      siteName: _syncService.currentSiteName,
      lastSync: _syncService.lastSyncTime,
      layoutConfig: layoutService.isLoaded ? layoutService.config : null,
      theme: layoutService.isLoaded ? layoutService.theme : null,
      isLoading: false,
    );

    // Escuchar cambios del servicio
    _syncService.addListener(_onSyncServiceChanged);
  }

  void _onSyncServiceChanged() {
    final layoutService = LayoutService();
    state = state.copyWith(
      status: _syncService.status,
      siteUrl: _syncService.currentSiteUrl,
      siteName: _syncService.currentSiteName,
      lastSync: _syncService.lastSyncTime,
      layoutConfig: layoutService.isLoaded ? layoutService.config : null,
      theme: layoutService.isLoaded ? layoutService.theme : null,
    );
  }

  /// Sincronizar con un sitio
  Future<SyncResult> syncWithSite(String siteUrl) async {
    state = state.copyWith(
      isLoading: true,
      error: null,
    );

    final result = await _syncService.syncWithSite(siteUrl);

    if (result.success) {
      final layoutService = LayoutService();
      state = state.copyWith(
        status: SyncStatus.success,
        siteUrl: siteUrl,
        siteName: result.siteName,
        lastSync: DateTime.now(),
        layoutConfig: layoutService.config,
        theme: layoutService.theme,
        isLoading: false,
      );
    } else {
      state = state.copyWith(
        status: SyncStatus.error,
        error: result.error,
        isLoading: false,
      );
    }

    return result;
  }

  /// Refrescar configuración
  Future<SyncResult> refresh() async {
    if (!state.isSynced) {
      return SyncResult.error('No hay sitio configurado');
    }

    return syncWithSite(state.siteUrl!);
  }

  /// Desconectar del sitio
  Future<void> disconnect() async {
    state = state.copyWith(isLoading: true);

    await _syncService.disconnect();

    state = const SyncState();
  }

  @override
  void dispose() {
    _syncService.removeListener(_onSyncServiceChanged);
    super.dispose();
  }
}

/// Provider principal de sincronización
final syncProvider = StateNotifierProvider<SyncNotifier, SyncState>((ref) {
  return SyncNotifier();
});

/// Provider del tema actual
final appThemeProvider = Provider<ThemeData>((ref) {
  final syncState = ref.watch(syncProvider);
  return syncState.getThemeData();
});

/// Provider del tema oscuro
final appDarkThemeProvider = Provider<ThemeData>((ref) {
  final syncState = ref.watch(syncProvider);
  return syncState.getThemeData(brightness: Brightness.dark);
});

/// Provider de configuración de layout
final layoutConfigProvider = Provider<LayoutConfig>((ref) {
  final syncState = ref.watch(syncProvider);
  return syncState.layoutConfig ?? LayoutConfig.defaults();
});

/// Provider de items de navegación
final navigationItemsProvider = Provider<List<NavigationItem>>((ref) {
  final config = ref.watch(layoutConfigProvider);
  return config.navigationItems;
});

/// Provider para saber si usar bottom navigation
final useBottomNavigationProvider = Provider<bool>((ref) {
  final config = ref.watch(layoutConfigProvider);
  return config.menu.useBottomNavigation;
});
