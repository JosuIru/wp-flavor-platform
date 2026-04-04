import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../config/app_config.dart';
import '../../utils/dynamic_form_support.dart';
import '../../utils/flavor_url_launcher.dart';
import '../../widgets/flavor_confirm_dialog.dart';
import '../../widgets/flavor_detail_widgets.dart';
import '../../widgets/flavor_image_viewer.dart';
import '../../utils/map_launch_helper.dart';
import '../../widgets/flavor_snackbar.dart';
import '../../widgets/flavor_webview_page.dart';
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
      case 'detail':
        return buildGenericScreen(context, module, type: ModuleScreenType.detail);
      case 'dashboard':
        return buildGenericScreen(context, module, type: ModuleScreenType.dashboard);
      default:
        return buildGenericScreen(context, module, type: ModuleScreenType.list);
    }
  }
}

Future<bool> _runModuleAction({
  required BuildContext context,
  required WidgetRef ref,
  required String moduleId,
  required String actionName,
  required Map<String, dynamic> params,
  void Function(Map<String, dynamic> result)? onResult,
}) async {
  final result = await ref.read(moduleServiceProvider).executeAction(
        moduleId: moduleId,
        actionName: actionName,
        params: params,
      );

  if (!context.mounted) return result != null;

  if (result != null) {
    onResult?.call(result);
    final message = result['message']?.toString() ??
        result['mensaje']?.toString() ??
        'Acción ejecutada correctamente';
    FlavorSnackbar.showSuccess(context, message);
    return true;
  }

  FlavorSnackbar.showError(context, 'No se pudo completar la acción.');
  return false;
}

List<_ModuleActionDescriptor> _extractSupportedItemActions(
  ModuleDefinition module,
  Map<String, dynamic>? moduleData,
  Map<String, dynamic>? item,
) {
  final actions = moduleData?['actions'];
  if (actions is! Map) return const [];

  final result = <_ModuleActionDescriptor>[];
  for (final entry in actions.entries) {
    final actionName = entry.key.toString();
    final config = entry.value is Map
        ? Map<String, dynamic>.from(entry.value as Map)
        : <String, dynamic>{};

    if (_isSupportedItemAction(actionName, config) &&
        _isActionAllowedForAppContext(module, actionName) &&
        _isActionAllowedForItemState(actionName, item)) {
      result.add(_ModuleActionDescriptor(name: actionName, config: config));
    }
  }

  return result;
}

bool _isSupportedItemAction(
  String actionName,
  Map<String, dynamic> config,
) {
  final lowered = actionName.toLowerCase();
  if (lowered.startsWith('listar') ||
      lowered.startsWith('buscar') ||
      lowered.startsWith('estad') ||
      lowered.startsWith('mis_') ||
      lowered.startsWith('mis-') ||
      lowered.startsWith('crear')) {
    return false;
  }

  final params =
      (config['params'] as List?)?.map((e) => e.toString()).toList() ??
          const <String>[];
  final hasItemReference =
      params.any((param) => param == 'id' || param.endsWith('_id'));
  final isMutatingName = lowered.startsWith('actualizar') ||
      lowered.startsWith('editar') ||
      lowered.startsWith('eliminar') ||
      lowered.startsWith('borrar') ||
      lowered.startsWith('cancelar') ||
      lowered.startsWith('confirmar') ||
      lowered.startsWith('dar_') ||
      lowered.startsWith('pausar') ||
      lowered.startsWith('rechazar') ||
      lowered.startsWith('aprobar') ||
      lowered.startsWith('inscribirse');

  return hasItemReference || isMutatingName;
}

String _buildActionLabel(String actionName) {
  final normalized = actionName.replaceAll('_', ' ').replaceAll('-', ' ').trim();
  if (normalized.isEmpty) return 'Acción';
  return normalized[0].toUpperCase() + normalized.substring(1);
}

bool _isActionAllowedForAppContext(
  ModuleDefinition module,
  String actionName,
) {
  if (AppConfig.isAdminApp) {
    return true;
  }

  final normalizedAction = actionName.toLowerCase().trim();
  final permissions = module.requiredPermissions.map((p) => p.toLowerCase()).toSet();
  final moduleRequiresAdmin = permissions.contains('manage_options') ||
      permissions.contains('administrator') ||
      permissions.contains('flavor_manage_permissions');

  if (moduleRequiresAdmin) {
    final nonAdminSafePrefixes = [
      'inscribirse',
      'reservar',
      'solicitar',
      'responder',
      'comentar',
      'favorito',
      'marcar_',
      'reportar',
    ];
    if (!nonAdminSafePrefixes.any(normalizedAction.startsWith)) {
      return false;
    }
  }

  final clearlyAdminActions = [
    'aprobar',
    'rechazar',
    'confirmar',
    'dar_baja',
    'dar_alta',
    'actualizar_estado',
    'pausar',
    'reactivar',
    'restaurar',
    'archivar',
    'asignar',
    'enviar_email',
    'estadisticas',
    'crear_factura',
    'actualizar_cliente',
    'crear_cliente',
    'actualizar_evento',
    'crear_evento',
  ];

  return !clearlyAdminActions.any(normalizedAction.startsWith);
}

bool _isActionAllowedForItemState(
  String actionName,
  Map<String, dynamic>? item,
) {
  final status = _extractItemStatus(item ?? const {});
  if (status == null) return true;

  final normalizedStatus = status.toLowerCase().trim();
  final normalizedAction = actionName.toLowerCase().trim();

  final isConfirmed = normalizedStatus.contains('confirmad') ||
      normalizedStatus == 'activo' ||
      normalizedStatus == 'publicado';
  final isCancelled = normalizedStatus.contains('cancelad') ||
      normalizedStatus.contains('rechazad') ||
      normalizedStatus == 'cerrado' ||
      normalizedStatus == 'finalizado';
  final isPending =
      normalizedStatus.contains('pendiente') || normalizedStatus == 'borrador';

  if (normalizedAction.contains('confirmar') && isConfirmed) {
    return false;
  }
  if (normalizedAction.contains('cancelar') && isCancelled) {
    return false;
  }
  if (normalizedAction.contains('rechazar') && isCancelled) {
    return false;
  }
  if ((normalizedAction.contains('aprobar') ||
          normalizedAction.contains('publicar')) &&
      isConfirmed) {
    return false;
  }
  if (normalizedAction.contains('inscribirse') && isCancelled) {
    return false;
  }
  if (normalizedAction.contains('reanudar') && !isCancelled) {
    return false;
  }
  if (normalizedAction.contains('pausar') && !isConfirmed) {
    return false;
  }
  if ((normalizedAction.contains('dar_baja') ||
          normalizedAction.contains('desactivar')) &&
      isCancelled) {
    return false;
  }
  if ((normalizedAction.contains('activar') ||
          normalizedAction.contains('restaurar')) &&
      (isConfirmed || isPending)) {
    return false;
  }

  return true;
}

Map<String, dynamic>? _extractActionFormConfig(
  Map<String, dynamic>? moduleData,
  String actionName,
) {
  final formConfigs = moduleData?['form_configs'];
  if (formConfigs is Map && formConfigs[actionName] is Map) {
    return Map<String, dynamic>.from(formConfigs[actionName] as Map);
  }
  return null;
}

Map<String, dynamic> _buildItemActionParams(
  _ModuleActionDescriptor action,
  Map<String, dynamic> item,
) {
  final params = <String, dynamic>{};
  final rawParams = action.config['params'] as List?;
  final paramNames =
      rawParams?.map((param) => param.toString()).toList() ?? const <String>[];

  for (final paramName in paramNames) {
    if (item.containsKey(paramName)) {
      params[paramName] = item[paramName];
      continue;
    }

    if ((paramName == 'id' || paramName.endsWith('_id')) && item['id'] != null) {
      params[paramName] = item['id'];
      continue;
    }

    final simplified = paramName.replaceAll('_id', '');
    if (item.containsKey(simplified)) {
      params[paramName] = item[simplified];
    }
  }

  if (params.isEmpty && item['id'] != null) {
    params['id'] = item['id'];
  }

  return params;
}

Future<bool> _confirmModuleAction(
  BuildContext context,
  String actionName,
) async {
  final lowered = actionName.toLowerCase();
  final needsConfirm = lowered.contains('eliminar') ||
      lowered.contains('borrar') ||
      lowered.contains('cancelar') ||
      lowered.contains('rechazar') ||
      lowered.contains('dar_baja') ||
      lowered.contains('confirmar') ||
      lowered.contains('pausar');

  if (!needsConfirm) return true;

  return FlavorConfirmDialog.show(
    context,
    title: _buildActionLabel(actionName),
    message: '¿Quieres continuar con esta acción?',
    destructive: lowered.contains('eliminar') ||
        lowered.contains('borrar') ||
        lowered.contains('cancelar') ||
        lowered.contains('rechazar') ||
        lowered.contains('dar_baja'),
  );
}

String? _extractItemStatus(Map<String, dynamic> item) {
  for (final key in const ['estado', 'status']) {
    final value = item[key];
    if (value != null && value.toString().trim().isNotEmpty) {
      return value.toString();
    }
  }
  return null;
}

String? _extractItemPrice(Map<String, dynamic> item) {
  for (final key in const ['precio', 'price', 'importe', 'coste']) {
    final value = item[key];
    if (value != null && value.toString().trim().isNotEmpty) {
      return value.toString();
    }
  }
  return null;
}

String? _extractItemDate(Map<String, dynamic> item) {
  for (final key in const [
    'fecha_inicio',
    'fecha',
    'date',
    'fecha_fin',
    'hora_inicio',
  ]) {
    final value = item[key];
    if (value != null && value.toString().trim().isNotEmpty) {
      return value.toString();
    }
  }
  return null;
}

String? _extractItemImageUrl(Map<String, dynamic> item) {
  for (final key in const [
    'imagen',
    'image',
    'foto',
    'thumbnail',
    'cover',
    'banner',
    'featured_image',
  ]) {
    final value = item[key];
    if (value is String && value.trim().isNotEmpty) {
      final normalized = value.trim();
      if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
        return normalized;
      }
    }
  }
  return null;
}

String _formatCompactDate(String raw) {
  final normalized = raw.replaceFirst(' ', 'T');
  final parsed = DateTime.tryParse(normalized);
  if (parsed == null) return raw;
  final date =
      '${parsed.day.toString().padLeft(2, '0')}/${parsed.month.toString().padLeft(2, '0')}';
  final hasTime = raw.contains(':');
  if (!hasTime) return date;
  return '$date ${parsed.hour.toString().padLeft(2, '0')}:${parsed.minute.toString().padLeft(2, '0')}';
}

String _formatCompactPrice(String raw) {
  final parsed = num.tryParse(raw.replaceAll(',', '.'));
  if (parsed == null) return raw;
  return '${parsed.toStringAsFixed(parsed % 1 == 0 ? 0 : 2)} EUR';
}

Color _statusColor(String value, Color fallback) {
  return switch (value.toLowerCase()) {
    'activo' || 'activa' || 'publicado' || 'confirmada' || 'confirmado' =>
      Colors.green,
    'pendiente' || 'borrador' || 'en_espera' => Colors.orange,
    'cancelado' || 'cancelada' || 'rechazado' || 'rechazada' => Colors.red,
    _ => fallback,
  };
}

class _ItemMetaWrap extends StatelessWidget {
  final Map<String, dynamic> item;
  final Color accentColor;

  const _ItemMetaWrap({
    required this.item,
    required this.accentColor,
  });

  @override
  Widget build(BuildContext context) {
    final status = _extractItemStatus(item);
    final price = _extractItemPrice(item);
    final date = _extractItemDate(item);
    final chips = <Widget>[];

    if (status != null) {
      chips.add(_MetaChip(
        label: _ModuleGeneratedDetailScreen._beautifyKey(status),
        color: _statusColor(status, accentColor),
      ));
    }
    if (date != null) {
      chips.add(_MetaChip(
        label: _formatCompactDate(date),
        color: accentColor.withOpacity(0.9),
        icon: Icons.calendar_today,
      ));
    }
    if (price != null) {
      chips.add(_MetaChip(
        label: _formatCompactPrice(price),
        color: accentColor.withOpacity(0.8),
        icon: Icons.payments_outlined,
      ));
    }

    if (chips.isEmpty) {
      final subtitle = item['subtitle']?.toString() ?? '';
      if (subtitle.isEmpty) return const SizedBox.shrink();
      return Text(
        subtitle,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: Theme.of(context).textTheme.bodySmall,
      );
    }

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: chips,
    );
  }
}

class _MetaChip extends StatelessWidget {
  final String label;
  final Color color;
  final IconData? icon;

  const _MetaChip({
    required this.label,
    required this.color,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 6),
          ],
          Text(
            label,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}

class _ItemThumbnail extends StatelessWidget {
  final Map<String, dynamic> item;
  final ModuleDefinition module;
  final double? width;
  final double? height;
  final BorderRadius? borderRadius;

  const _ItemThumbnail({
    required this.item,
    required this.module,
    this.width,
    this.height,
    this.borderRadius,
  });

  @override
  Widget build(BuildContext context) {
    final imageUrl = _extractItemImageUrl(item);
    final resolvedWidth = width ?? 48;
    final resolvedHeight = height ?? 48;
    final radius = borderRadius ?? BorderRadius.circular(12);

    if (imageUrl != null) {
      return ClipRRect(
        borderRadius: radius,
        child: Image.network(
          imageUrl,
          width: resolvedWidth,
          height: resolvedHeight,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) => _buildFallback(
            context,
            resolvedWidth,
            resolvedHeight,
            radius,
          ),
        ),
      );
    }

    return _buildFallback(context, resolvedWidth, resolvedHeight, radius);
  }

  Widget _buildFallback(
    BuildContext context,
    double resolvedWidth,
    double resolvedHeight,
    BorderRadius radius,
  ) {
    return Container(
      width: resolvedWidth,
      height: resolvedHeight,
      decoration: BoxDecoration(
        color: module.color.withOpacity(0.12),
        borderRadius: radius,
      ),
      child: Icon(
        module.icon,
        color: module.color,
        size: resolvedHeight * 0.45,
      ),
    );
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
  bool _isSubmittingCreate = false;
  bool _isSubmittingItemAction = false;

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
              actionLabel: 'Actualizar',
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index] as Map<String, dynamic>;
                final itemActions =
                    _extractSupportedItemActions(widget.module, data, item);
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: _ItemThumbnail(
                      item: item,
                      module: widget.module,
                    ),
                    title: Text(item['title']?.toString() ?? 'Sin título'),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: _ItemMetaWrap(
                        item: item,
                        accentColor: widget.module.color,
                      ),
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (itemActions.isNotEmpty)
                          PopupMenuButton<_ModuleActionDescriptor>(
                            onSelected: _isSubmittingItemAction
                                ? null
                                : (action) => _handleItemActionSelected(
                                      action: action,
                                      item: item,
                                      moduleData: data,
                                    ),
                            itemBuilder: (context) => itemActions
                                .map(
                                  (action) => PopupMenuItem<_ModuleActionDescriptor>(
                                    value: action,
                                    child: Text(_buildActionLabel(action.name)),
                                  ),
                                )
                                .toList(),
                          ),
                        const Icon(Icons.chevron_right),
                      ],
                    ),
                    onTap: () => _onItemTap(item, data),
                  ),
                );
              },
            ),
          );
        },
      ),
      floatingActionButton: FutureBuilder<Map<String, dynamic>?>(
        future: _dataFuture,
        builder: (context, snapshot) {
          final data = snapshot.data;
          final createAction = data == null ? null : _extractCreateAction(data);

          if (createAction == null) {
            return const SizedBox.shrink();
          }

          return FloatingActionButton(
            onPressed: () => _onAddItem(createAction),
            backgroundColor: widget.module.color,
            child: const Icon(Icons.add),
          );
        },
      ),
    );
  }

  void _onItemTap(Map<String, dynamic> item, Map<String, dynamic> moduleData) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _ModuleGeneratedDetailScreen(
          module: widget.module,
          item: item,
          moduleData: moduleData,
        ),
      ),
    );
  }

  void _onAddItem(String actionName) {
    _openCreateAction(actionName);
  }

  Future<void> _openCreateAction(String actionName) async {
    final data = await _dataFuture;
    if (!mounted || data == null) return;

    final formConfig = _extractFormConfig(data, actionName);
    if (formConfig == null) {
      FlavorSnackbar.showError(
        context,
        'La acción "$actionName" requiere un formulario específico del módulo.',
      );
      return;
    }

    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => _ModuleGeneratedCreateScreen(
          module: widget.module,
          actionName: actionName,
          formConfig: formConfig,
          onSubmit: _submitCreateAction,
        ),
      ),
    );

    if (created == true && mounted) {
      await _refresh();
    }
  }

  String? _extractCreateAction(Map<String, dynamic> data) {
    final actions = data['actions'];
    if (actions is List) {
      for (final action in actions) {
        if (action is Map<String, dynamic>) {
          final id = action['id']?.toString().toLowerCase();
          final name = action['name']?.toString().toLowerCase();
          final type = action['type']?.toString().toLowerCase();

          if (id == 'create' ||
              id == 'new' ||
              name == 'create' ||
              name == 'new' ||
              type == 'create') {
            return action['id']?.toString() ?? 'create';
          }
        }
      }
    }

    if (data['can_create'] == true || data['supports_create'] == true) {
      return 'create';
    }

    return null;
  }

  Map<String, dynamic>? _extractFormConfig(
    Map<String, dynamic> data,
    String actionName,
  ) {
    final formConfigs = data['form_configs'];
    if (formConfigs is Map && formConfigs[actionName] is Map<String, dynamic>) {
      return Map<String, dynamic>.from(formConfigs[actionName] as Map);
    }

    if (formConfigs is Map && formConfigs[actionName] is Map) {
      return Map<String, dynamic>.from(formConfigs[actionName] as Map);
    }

    return null;
  }

  Future<bool> _submitCreateAction(
    String actionName,
    Map<String, dynamic> params,
  ) async {
    if (_isSubmittingCreate) return false;

    setState(() {
      _isSubmittingCreate = true;
    });

    try {
      return await _runModuleAction(
        context: context,
        ref: ref,
        moduleId: widget.module.id,
        actionName: actionName,
        params: params,
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmittingCreate = false;
        });
      }
    }
  }

  Future<void> _handleItemActionSelected({
    required _ModuleActionDescriptor action,
    required Map<String, dynamic> item,
    required Map<String, dynamic> moduleData,
  }) async {
    final confirmed = await _confirmModuleAction(context, action.name);
    if (!confirmed || !mounted) return;

    final formConfig = _extractActionFormConfig(moduleData, action.name);
    if (formConfig != null) {
      final completed = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          builder: (_) => _ModuleGeneratedCreateScreen(
            module: widget.module,
            actionName: action.name,
            formConfig: formConfig,
            initialValues: item,
            onSubmit: _submitItemAction,
          ),
        ),
      );

      if (completed == true && mounted) {
        await _refresh();
      }
      return;
    }

    final success = await _submitItemAction(
      action.name,
      _buildItemActionParams(action, item),
    );

    if (success && mounted) {
      await _refresh();
    }
  }

  Future<bool> _submitItemAction(
    String actionName,
    Map<String, dynamic> params,
  ) async {
    if (_isSubmittingItemAction) return false;

    setState(() {
      _isSubmittingItemAction = true;
    });

    try {
      return await _runModuleAction(
        context: context,
        ref: ref,
        moduleId: widget.module.id,
        actionName: actionName,
        params: params,
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmittingItemAction = false;
        });
      }
    }
  }
}

/// Pantalla genérica de grid
class _ModuleGridScreen extends ConsumerStatefulWidget {
  final ModuleDefinition module;

  const _ModuleGridScreen({required this.module});

  @override
  ConsumerState<_ModuleGridScreen> createState() => _ModuleGridScreenState();
}

class _ModuleGridScreenState extends ConsumerState<_ModuleGridScreen> {
  late Future<Map<String, dynamic>?> _dataFuture;
  bool _isSubmittingItemAction = false;

  @override
  void initState() {
    super.initState();
    _dataFuture = ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
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
              onRetry: () => setState(() {
                _dataFuture = ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
              }),
            );
          }

          final items = snapshot.data!['items'] as List<dynamic>? ?? [];
          final moduleData = snapshot.data!;
          final itemActions =
              _extractSupportedItemActions(widget.module, moduleData, null);
          if (items.isEmpty) {
            return ModuleEmptyState(
              title: 'Sin elementos',
              message: 'No hay elementos en ${widget.module.name}',
            );
          }

          return GridView.builder(
            padding: const EdgeInsets.all(16),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.05,
            ),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index] as Map<String, dynamic>;
              return Card(
                clipBehavior: Clip.antiAlias,
                child: InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => _ModuleGeneratedDetailScreen(
                          module: widget.module,
                          item: item,
                          moduleData: moduleData,
                        ),
                      ),
                    );
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Stack(
                          children: [
                            _ItemThumbnail(
                              item: item,
                              module: widget.module,
                              width: double.infinity,
                              height: 92,
                              borderRadius: BorderRadius.circular(14),
                            ),
                            Positioned(
                              top: 6,
                              right: 6,
                              child: Row(
                                children: [
                                  if (itemActions.isNotEmpty)
                                    Container(
                                      decoration: BoxDecoration(
                                        color: Colors.white.withOpacity(0.92),
                                        borderRadius: BorderRadius.circular(999),
                                      ),
                                      child: PopupMenuButton<_ModuleActionDescriptor>(
                                        onSelected: _isSubmittingItemAction
                                            ? null
                                            : (action) => _handleItemActionSelected(
                                                  action: action,
                                                  item: item,
                                                  moduleData: moduleData,
                                                ),
                                        itemBuilder: (context) => itemActions
                                            .map(
                                              (action) => PopupMenuItem<_ModuleActionDescriptor>(
                                                value: action,
                                                child: Text(_buildActionLabel(action.name)),
                                              ),
                                            )
                                            .toList(),
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          item['title']?.toString() ?? 'Sin título',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 8),
                        _ItemMetaWrap(
                          item: item,
                          accentColor: widget.module.color,
                        ),
                        const Spacer(),
                        Text(
                          'Ver detalle',
                          style: Theme.of(context).textTheme.labelMedium?.copyWith(
                                color: widget.module.color,
                                fontWeight: FontWeight.w600,
                              ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _handleItemActionSelected({
    required _ModuleActionDescriptor action,
    required Map<String, dynamic> item,
    required Map<String, dynamic> moduleData,
  }) async {
    final confirmed = await _confirmModuleAction(context, action.name);
    if (!confirmed || !mounted) return;

    final formConfig = _extractActionFormConfig(moduleData, action.name);
    if (formConfig != null) {
      final completed = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          builder: (_) => _ModuleGeneratedCreateScreen(
            module: widget.module,
            actionName: action.name,
            formConfig: formConfig,
            initialValues: item,
            onSubmit: _submitItemAction,
          ),
        ),
      );

      if (completed == true && mounted) {
        setState(() {
          _dataFuture =
              ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
        });
      }
      return;
    }

    final success = await _submitItemAction(
      action.name,
      _buildItemActionParams(action, item),
    );

    if (success && mounted) {
      setState(() {
        _dataFuture =
            ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
      });
    }
  }

  Future<bool> _submitItemAction(
    String actionName,
    Map<String, dynamic> params,
  ) async {
    if (_isSubmittingItemAction) return false;

    setState(() {
      _isSubmittingItemAction = true;
    });

    try {
      return await _runModuleAction(
        context: context,
        ref: ref,
        moduleId: widget.module.id,
        actionName: actionName,
        params: params,
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmittingItemAction = false;
        });
      }
    }
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
class _ModuleDashboardScreen extends ConsumerStatefulWidget {
  final ModuleDefinition module;

  const _ModuleDashboardScreen({required this.module});

  @override
  ConsumerState<_ModuleDashboardScreen> createState() =>
      _ModuleDashboardScreenState();
}

class _ModuleDashboardScreenState extends ConsumerState<_ModuleDashboardScreen> {
  late Future<Map<String, dynamic>?> _dataFuture;

  @override
  void initState() {
    super.initState();
    _dataFuture = ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
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
              onRetry: () => setState(() {
                _dataFuture = ref.read(moduleServiceProvider).getModuleDetails(widget.module.id);
              }),
            );
          }

          final data = snapshot.data!;
          final stats = data['stats'] is Map<String, dynamic>
              ? data['stats'] as Map<String, dynamic>
              : <String, dynamic>{};
          final items = data['items'] as List<dynamic>? ?? [];
          final actions = data['actions'] as List<dynamic>? ?? [];

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _SummaryCard(
                    title: 'Elementos',
                    value: '${items.length}',
                    color: widget.module.color,
                    icon: widget.module.icon,
                  ),
                  _SummaryCard(
                    title: 'Acciones',
                    value: '${actions.length}',
                    color: widget.module.color.withOpacity(0.85),
                    icon: Icons.flash_on,
                  ),
                  ...stats.entries.take(4).map(
                    (entry) => _SummaryCard(
                      title: _ModuleGeneratedDetailScreen._beautifyKey(entry.key),
                      value: entry.value?.toString() ?? '0',
                      color: widget.module.color.withOpacity(0.7),
                      icon: Icons.insights,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if (items.isNotEmpty) ...[
                Text(
                  'Actividad reciente',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 12),
                ...items.take(5).map(
                  (item) => Card(
                    child: ListTile(
                      leading: Icon(widget.module.icon, color: widget.module.color),
                      title: Text(item['title']?.toString() ?? 'Sin título'),
                      subtitle: Text(item['subtitle']?.toString() ?? ''),
                    ),
                  ),
                ),
              ] else
                const ModuleEmptyState(
                  title: 'Sin datos',
                  message: 'Este dashboard todavía no tiene actividad visible.',
                ),
            ],
          );
        },
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final Color color;
  final IconData icon;

  const _SummaryCard({
    required this.title,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 160,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: color),
              const SizedBox(height: 12),
              Text(
                value,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                title,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ModuleGeneratedDetailScreen extends ConsumerStatefulWidget {
  final ModuleDefinition module;
  final Map<String, dynamic> item;
  final Map<String, dynamic>? moduleData;

  const _ModuleGeneratedDetailScreen({
    required this.module,
    required this.item,
    this.moduleData,
  });

  @override
  ConsumerState<_ModuleGeneratedDetailScreen> createState() =>
      _ModuleGeneratedDetailScreenState();

  static String _beautifyKey(String key) {
    return key
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .split(' ')
        .where((part) => part.isNotEmpty)
        .map((part) => part[0].toUpperCase() + part.substring(1))
        .join(' ');
  }

  static String _formatValue(dynamic value) {
    if (value is List) {
      return value.map((item) => item.toString()).join(', ');
    }
    if (value is Map) {
      return value.entries.map((e) => '${e.key}: ${e.value}').join('\n');
    }
    return value.toString();
  }
}

class _ModuleGeneratedDetailScreenState
    extends ConsumerState<_ModuleGeneratedDetailScreen> {
  late Map<String, dynamic> _item;
  bool _isExecutingAction = false;

  @override
  void initState() {
    super.initState();
    _item = Map<String, dynamic>.from(widget.item);
  }

  List<_ModuleActionDescriptor> get _itemActions {
    return _extractSupportedItemActions(widget.module, widget.moduleData, _item);
  }

  @override
  Widget build(BuildContext context) {
    final heroImageUrl = _findImageUrl();
    final statusValue = _findStatusValue();
    final visibleEntries = _item.entries.where((entry) {
      final key = entry.key.toLowerCase();
      return entry.value != null &&
          entry.value.toString().trim().isNotEmpty &&
          key != 'id' &&
          key != 'title' &&
          key != 'subtitle' &&
          !_isImageKey(key) &&
          !_isPrimaryStatusKey(key);
    }).toList();

    return Scaffold(
      appBar: AppBar(
        title: Text(_item['title']?.toString() ?? widget.module.name),
        backgroundColor: widget.module.color,
        foregroundColor: Colors.white,
        actions: [
          if (_itemActions.isNotEmpty)
            PopupMenuButton<_ModuleActionDescriptor>(
              onSelected: _isExecutingAction ? null : _handleActionSelected,
              itemBuilder: (context) => _itemActions
                  .map(
                    (action) => PopupMenuItem<_ModuleActionDescriptor>(
                      value: action,
                      child: Text(_buildActionLabel(action.name)),
                    ),
                  )
                  .toList(),
            ),
        ],
      ),
      body: ListView(
        children: [
          if (heroImageUrl != null)
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Image.network(
                heroImageUrl,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) =>
                    _buildHeroPlaceholder(context),
              ),
            )
          else
            _buildHeroPlaceholder(context),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (statusValue != null) ...[
                  _buildStatusChip(context, statusValue),
                  const SizedBox(height: 12),
                ],
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _item['title']?.toString() ?? widget.module.name,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        if ((_item['subtitle']?.toString() ?? '').isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            _item['subtitle'].toString(),
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  color: Colors.grey[700],
                                ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                if (visibleEntries.isEmpty)
                  const Card(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child:
                          Text('No hay más datos estructurados para este elemento.'),
                    ),
                  ),
                ...visibleEntries.map(_buildRichEntryCard),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeroPlaceholder(BuildContext context) {
    return Container(
      height: 220,
      color: widget.module.color.withOpacity(0.12),
      child: Center(
        child: Icon(
          widget.module.icon,
          size: 72,
          color: widget.module.color,
        ),
      ),
    );
  }

  Widget _buildStatusChip(BuildContext context, String value) {
    final normalized = value.toLowerCase();
    final color = switch (normalized) {
      'activo' || 'activa' || 'publicado' || 'confirmada' || 'confirmado' =>
        Colors.green,
      'pendiente' || 'borrador' || 'en_espera' => Colors.orange,
      'cancelado' || 'cancelada' || 'rechazado' || 'rechazada' => Colors.red,
      _ => widget.module.color,
    };

    return FlavorStatusChip(
      label: _ModuleGeneratedDetailScreen._beautifyKey(value),
      backgroundColor: color.withOpacity(0.12),
      foregroundColor: color,
    );
  }

  Widget _buildRichEntryCard(MapEntry<String, dynamic> entry) {
    final key = entry.key;
    final value = entry.value;
    final lowerKey = key.toLowerCase();

    if (value is List) {
      if (_looksLikeGallery(lowerKey, value)) {
        final galleryItems = value
            .map(_extractGalleryImageData)
            .whereType<_GalleryImageData>()
            .take(10)
            .toList();
        return Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _ModuleGeneratedDetailScreen._beautifyKey(key),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 112,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: galleryItems.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 10),
                    itemBuilder: (context, index) {
                      final image = galleryItems[index];
                      return ClipRRect(
                        borderRadius: BorderRadius.circular(14),
                        child: InkWell(
                          onTap: () => _openGalleryViewer(galleryItems, index),
                          child: Stack(
                            children: [
                              Image.network(
                                image.url,
                                width: 132,
                                height: 112,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) =>
                                    _buildGalleryFallback(),
                              ),
                              if ((image.caption ?? '').isNotEmpty)
                                Positioned(
                                  left: 0,
                                  right: 0,
                                  bottom: 0,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 6),
                                    color: Colors.black.withOpacity(0.45),
                                    child: Text(
                                      image.caption!,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 12,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      }

      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _ModuleGeneratedDetailScreen._beautifyKey(key),
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 12),
              if (value.isEmpty)
                const Text('Sin elementos')
              else
                ...value.take(8).map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: _buildListValueItem(item),
                  ),
                ),
            ],
          ),
        ),
      );
    }

    if (value is Map) {
      final mapValue = Map<String, dynamic>.from(value);
      if (_looksLikeCoordinatesMap(lowerKey, mapValue)) {
        final coordinates = _coordinatesFromMap(mapValue);
        if (coordinates != null) {
          return _EmbeddedMapCard(
            title: _ModuleGeneratedDetailScreen._beautifyKey(key),
            subtitle: _formatCoordinatesText(mapValue),
            coordinates: coordinates,
            accentColor: widget.module.color,
            onOpenFullMap: () => _openMapViewer(
              coordinates,
              title: _ModuleGeneratedDetailScreen._beautifyKey(key),
            ),
          );
        }

        return Card(
          child: ListTile(
            leading: Icon(Icons.map_outlined, color: widget.module.color),
            title: Text(_ModuleGeneratedDetailScreen._beautifyKey(key)),
            subtitle: Text(_formatCoordinatesText(mapValue)),
            trailing: const Icon(Icons.open_in_new),
            onTap: () => _openMapFromValue(mapValue),
          ),
        );
      }

      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _ModuleGeneratedDetailScreen._beautifyKey(key),
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 12),
              ...mapValue.entries.map(
                (nested) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        flex: 2,
                        child: Text(
                          _ModuleGeneratedDetailScreen._beautifyKey(nested.key),
                          style:
                              Theme.of(context).textTheme.bodySmall?.copyWith(
                                    fontWeight: FontWeight.w600,
                                  ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 3,
                        child: Text(
                          _ModuleGeneratedDetailScreen._formatValue(nested.value),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Card(
      child: ListTile(
        leading: _buildFieldIcon(key),
        title: Text(_ModuleGeneratedDetailScreen._beautifyKey(key)),
        subtitle: _buildScalarValue(key, value),
      ),
    );
  }

  Widget _buildListValueItem(dynamic item) {
    if (item is Map) {
      final itemMap = Map<String, dynamic>.from(item);
      if (_looksLikeDocumentMap(itemMap)) {
        final url = _extractDocumentUrl(itemMap);
        return InkWell(
          onTap: url == null
              ? null
              : () => _openDocument(
                    url,
                    title: _extractDocumentTitle(itemMap),
                  ),
          child: Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(Icons.description_outlined, color: widget.module.color),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    _extractDocumentTitle(itemMap),
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
                if (url != null)
                  Icon(Icons.open_in_new,
                      size: 18, color: Theme.of(context).colorScheme.primary),
              ],
            ),
          ),
        );
      }

      final title = itemMap['title'] ??
          itemMap['titulo'] ??
          itemMap['nombre'] ??
          itemMap['name'];
      final subtitle = itemMap['subtitle'] ??
          itemMap['descripcion'] ??
          itemMap['description'];
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title?.toString() ?? _ModuleGeneratedDetailScreen._formatValue(item),
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            if ((subtitle?.toString() ?? '').isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(subtitle.toString()),
            ],
          ],
        ),
      );
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 5),
          child: Icon(Icons.circle, size: 8),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(_ModuleGeneratedDetailScreen._formatValue(item)),
        ),
      ],
    );
  }

  Widget _buildScalarValue(String key, dynamic value) {
    final text = _ModuleGeneratedDetailScreen._formatValue(value);
    final lowerKey = key.toLowerCase();

    if (_looksLikeDocumentKey(lowerKey, text)) {
      return InkWell(
        onTap: () => _openDocument(
          text,
          title: _ModuleGeneratedDetailScreen._beautifyKey(key),
        ),
        child: Row(
          children: [
            Icon(Icons.description_outlined,
                size: 18, color: Theme.of(context).colorScheme.primary),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                _documentLabelFromUrl(text),
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  decoration: TextDecoration.underline,
                ),
              ),
            ),
          ],
        ),
      );
    }

    if (_looksLikeUrl(text)) {
      return InkWell(
        onTap: () => _openUrl(text),
        child: Text(
          text,
          style: TextStyle(
            color: Theme.of(context).colorScheme.primary,
            decoration: TextDecoration.underline,
          ),
        ),
      );
    }

    final isLongText =
        lowerKey.contains('descripcion') || lowerKey.contains('contenido');
    if (isLongText) {
      return Text(text, style: const TextStyle(height: 1.45));
    }

    if (_isDateKey(lowerKey)) {
      return Text(_formatDateText(text));
    }

    if (_isPriceKey(lowerKey)) {
      return Text(_formatPriceText(text));
    }

    if (_looksLikeCoordinatesScalar(lowerKey, text)) {
      return InkWell(
        onTap: () => _openMapFromScalar(text, title: key),
        child: Row(
          children: [
            Icon(Icons.map_outlined,
                size: 18, color: Theme.of(context).colorScheme.primary),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                text,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  decoration: TextDecoration.underline,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return Text(text);
  }

  Widget? _buildFieldIcon(String key) {
    final lowerKey = key.toLowerCase();
    final icon = switch (lowerKey) {
      final k when _isDateKey(k) => Icons.calendar_today,
      final k when _isPriceKey(k) => Icons.payments_outlined,
      final k when k.contains('telefono') || k.contains('phone') => Icons.phone,
      final k when k.contains('email') => Icons.email_outlined,
      final k when k.contains('ubicacion') ||
              k.contains('direccion') ||
              k.contains('lugar') =>
        Icons.location_on_outlined,
      final k when k.contains('url') || k.contains('web') || k.contains('link') =>
        Icons.link,
      final k when k.contains('estado') => Icons.flag_outlined,
      _ => widget.module.icon,
    };
    return Icon(icon, color: widget.module.color);
  }

  String? _findImageUrl() {
    const imageKeys = [
      'imagen',
      'image',
      'foto',
      'thumbnail',
      'cover',
      'banner',
      'featured_image',
    ];
    for (final key in imageKeys) {
      final value = _item[key];
      if (value is String && _looksLikeUrl(value)) {
        return value;
      }
    }
    return null;
  }

  String? _findStatusValue() {
    const statusKeys = ['estado', 'status'];
    for (final key in statusKeys) {
      final value = _item[key];
      if (value != null && value.toString().trim().isNotEmpty) {
        return value.toString();
      }
    }
    return null;
  }

  bool _isImageKey(String key) =>
      key.contains('imagen') ||
      key.contains('image') ||
      key.contains('foto') ||
      key.contains('thumbnail') ||
      key.contains('cover') ||
      key.contains('banner');

  bool _isPrimaryStatusKey(String key) => key == 'estado' || key == 'status';

  bool _isDateKey(String key) =>
      key.contains('fecha') ||
      key.contains('date') ||
      key.contains('hora') ||
      key.contains('time');

  bool _isPriceKey(String key) =>
      key.contains('precio') ||
      key.contains('price') ||
      key.contains('coste') ||
      key.contains('importe');

  bool _looksLikeUrl(String value) =>
      value.startsWith('http://') || value.startsWith('https://');

  bool _looksLikeDocumentKey(String key, String value) {
    return _looksLikeUrl(value) &&
        (key.contains('document') ||
            key.contains('archivo') ||
            key.contains('adjunto') ||
            key.contains('pdf') ||
            key.contains('doc'));
  }

  bool _looksLikeCoordinatesScalar(String key, String value) {
    if (!(key.contains('coord') || key.contains('lat') || key.contains('lng'))) {
      return false;
    }
    final parts = value.split(',');
    return parts.length == 2 &&
        double.tryParse(parts[0].trim()) != null &&
        double.tryParse(parts[1].trim()) != null;
  }

  bool _looksLikeCoordinatesMap(String key, Map<String, dynamic> value) {
    final hasLatLng =
        value['lat'] != null && (value['lng'] != null || value['lon'] != null);
    return hasLatLng ||
        key.contains('ubicacion') ||
        key.contains('coordenada') ||
        key.contains('location');
  }

  bool _looksLikeGallery(String key, List<dynamic> value) {
    if (!(key.contains('galeria') ||
        key.contains('gallery') ||
        key.contains('imagenes') ||
        key.contains('fotos'))) {
      return false;
    }
    return value.any((item) => _extractGalleryImageUrl(item) != null);
  }

  bool _looksLikeDocumentMap(Map<String, dynamic> value) =>
      _extractDocumentUrl(value) != null;

  String? _extractGalleryImageUrl(dynamic value) {
    return _extractGalleryImageData(value)?.url;
  }

  _GalleryImageData? _extractGalleryImageData(dynamic value) {
    if (value is String && _looksLikeUrl(value)) {
      return _GalleryImageData(url: value);
    }
    if (value is Map) {
      final map = Map<String, dynamic>.from(value);
      String? url;
      for (final key in const [
        'imagen',
        'image',
        'foto',
        'thumbnail',
        'url',
        'src',
      ]) {
        final candidate = map[key];
        if (candidate is String && _looksLikeUrl(candidate)) {
          url = candidate;
          break;
        }
      }
      if (url != null) {
        final caption = map['caption']?.toString() ??
            map['title']?.toString() ??
            map['titulo']?.toString() ??
            map['nombre']?.toString();
        return _GalleryImageData(url: url, caption: caption);
      }
    }
    return null;
  }

  String? _extractDocumentUrl(Map<String, dynamic> value) {
    for (final key in const ['url', 'archivo', 'documento', 'pdf', 'adjunto']) {
      final candidate = value[key];
      if (candidate is String && _looksLikeUrl(candidate)) {
        return candidate;
      }
    }
    return null;
  }

  String _extractDocumentTitle(Map<String, dynamic> value) {
    return value['title']?.toString() ??
        value['titulo']?.toString() ??
        value['nombre']?.toString() ??
        _documentLabelFromUrl(_extractDocumentUrl(value) ?? '');
  }

  String _documentLabelFromUrl(String url) {
    final uri = Uri.tryParse(url);
    if (uri == null || uri.pathSegments.isEmpty) return 'Documento';
    return uri.pathSegments.last;
  }

  Widget _buildGalleryFallback() {
    return Container(
      width: 132,
      height: 112,
      color: widget.module.color.withOpacity(0.12),
      child: Icon(
        Icons.photo_library_outlined,
        color: widget.module.color,
        size: 32,
      ),
    );
  }

  String _formatCoordinatesText(Map<String, dynamic> value) {
    final lat = value['lat']?.toString() ?? value['latitude']?.toString() ?? '';
    final lng = value['lng']?.toString() ??
        value['lon']?.toString() ??
        value['longitude']?.toString() ??
        '';
    if (lat.isNotEmpty && lng.isNotEmpty) {
      return '$lat, $lng';
    }
    return _ModuleGeneratedDetailScreen._formatValue(value);
  }

  String _formatDateText(String raw) {
    final normalized = raw.replaceFirst(' ', 'T');
    final parsed = DateTime.tryParse(normalized);
    if (parsed == null) return raw;
    final date =
        '${parsed.day.toString().padLeft(2, '0')}/${parsed.month.toString().padLeft(2, '0')}/${parsed.year}';
    final hasTime = raw.contains(':');
    if (!hasTime) return date;
    return '$date ${parsed.hour.toString().padLeft(2, '0')}:${parsed.minute.toString().padLeft(2, '0')}';
  }

  String _formatPriceText(String raw) {
    final parsed = num.tryParse(raw.replaceAll(',', '.'));
    if (parsed == null) return raw;
    return '${parsed.toStringAsFixed(parsed % 1 == 0 ? 0 : 2)} EUR';
  }

  _MapCoordinates? _coordinatesFromMap(Map<String, dynamic> value) {
    final lat = double.tryParse(
      value['lat']?.toString() ?? value['latitude']?.toString() ?? '',
    );
    final lng = double.tryParse(
      value['lng']?.toString() ??
          value['lon']?.toString() ??
          value['longitude']?.toString() ??
          '',
    );
    if (lat == null || lng == null) return null;
    return _MapCoordinates(lat: lat, lng: lng);
  }

  _MapCoordinates? _coordinatesFromScalar(String value) {
    final parts = value.split(',');
    if (parts.length != 2) return null;
    final lat = double.tryParse(parts[0].trim());
    final lng = double.tryParse(parts[1].trim());
    if (lat == null || lng == null) return null;
    return _MapCoordinates(lat: lat, lng: lng);
  }

  String _buildExternalMapUrl(_MapCoordinates coordinates) {
    return MapLaunchHelper.buildConfiguredMapUri(
      coordinates.lat,
      coordinates.lng,
    ).toString();
  }

  String _buildEmbeddedMapUrl(_MapCoordinates coordinates) {
    return MapLaunchHelper.buildEmbeddedMapUrl(
      coordinates.lat,
      coordinates.lng,
    );
  }

  Future<void> _openUrl(String url) async {
    await FlavorUrlLauncher.openExternalRaw(url);
  }

  String _documentExtension(String url) {
    final uri = Uri.tryParse(url);
    final path = uri?.path ?? url;
    final dotIndex = path.lastIndexOf('.');
    if (dotIndex == -1 || dotIndex == path.length - 1) return '';
    return path.substring(dotIndex + 1).toLowerCase();
  }

  Future<void> _openDocument(
    String url, {
    String? title,
  }) async {
    final extension = _documentExtension(url);
    const inlineExtensions = {'pdf', 'html', 'htm', 'txt'};
    if (!inlineExtensions.contains(extension)) {
      await _openUrl(url);
      return;
    }

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _DocumentViewerScreen(
          url: url,
          title: title ?? _documentLabelFromUrl(url),
          accentColor: widget.module.color,
        ),
      ),
    );
  }

  Future<void> _openMapFromValue(Map<String, dynamic> value) async {
    final coordinates = _coordinatesFromMap(value);
    if (coordinates == null) return;
    await _openMapViewer(coordinates);
  }

  Future<void> _openMapFromScalar(
    String value, {
    String? title,
  }) async {
    final coordinates = _coordinatesFromScalar(value);
    if (coordinates == null) return;
    await _openMapViewer(coordinates, title: title);
  }

  Future<void> _openMapViewer(
    _MapCoordinates coordinates, {
    String? title,
  }) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _MapViewerScreen(
          coordinates: coordinates,
          title: title ?? 'Mapa',
          accentColor: widget.module.color,
          provider: MapLaunchHelper.provider,
          embeddedMapUrl: _buildEmbeddedMapUrl(coordinates),
          externalMapUrl: _buildExternalMapUrl(coordinates),
        ),
      ),
    );
  }

  Future<void> _openGalleryViewer(
    List<_GalleryImageData> images,
    int initialIndex,
  ) async {
    if (images.isEmpty) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => FlavorImageViewer(
          images: images
              .map(
                (image) => FlavorImageViewerItem(
                  url: image.url,
                  caption: image.caption,
                ),
              )
              .toList(),
          initialIndex: initialIndex,
          accentColor: widget.module.color,
        ),
      ),
    );
  }

  Future<void> _handleActionSelected(_ModuleActionDescriptor action) async {
    final confirmed = await _confirmModuleAction(context, action.name);
    if (!confirmed || !mounted) return;

    final formConfig = _extractActionFormConfig(widget.moduleData, action.name);
    if (formConfig != null) {
      final created = await Navigator.of(context).push<bool>(
        MaterialPageRoute(
          builder: (_) => _ModuleGeneratedCreateScreen(
            module: widget.module,
            actionName: action.name,
            formConfig: formConfig,
            initialValues: _item,
            onSubmit: _submitAction,
          ),
        ),
      );

      if (created == true && mounted) {
        setState(() {});
      }
      return;
    }

    await _submitAction(action.name, _buildItemActionParams(action, _item));
  }

  Future<bool> _submitAction(
    String actionName,
    Map<String, dynamic> params,
  ) async {
    if (_isExecutingAction) return false;

    setState(() {
      _isExecutingAction = true;
    });

    try {
      return await _runModuleAction(
        context: context,
        ref: ref,
        moduleId: widget.module.id,
        actionName: actionName,
        params: params,
        onResult: (result) {
          final data = result['data'];
          if (data is Map) {
            setState(() {
              _item = {
                ..._item,
                ...Map<String, dynamic>.from(data),
              };
            });
          }
        },
      );
    } finally {
      if (mounted) {
        setState(() {
          _isExecutingAction = false;
        });
      }
    }
  }
}

class _ModuleActionDescriptor {
  final String name;
  final Map<String, dynamic> config;

  const _ModuleActionDescriptor({
    required this.name,
    required this.config,
  });
}

class _GalleryImageData {
  final String url;
  final String? caption;

  const _GalleryImageData({
    required this.url,
    this.caption,
  });
}

class _MapCoordinates {
  final double lat;
  final double lng;

  const _MapCoordinates({
    required this.lat,
    required this.lng,
  });
}

class _DocumentViewerScreen extends StatelessWidget {
  final String url;
  final String title;
  final Color accentColor;

  const _DocumentViewerScreen({
    required this.url,
    required this.title,
    required this.accentColor,
  });

  @override
  Widget build(BuildContext context) {
    return FlavorWebViewPage(
      title: title,
      url: url,
      backgroundColor: accentColor,
      foregroundColor: Colors.white,
      actionsBuilder: (context, _) => [
        IconButton(
          tooltip: 'Abrir fuera',
          onPressed: () => FlavorUrlLauncher.openExternalRaw(url),
          icon: const Icon(Icons.open_in_new),
        ),
      ],
    );
  }
}

class _EmbeddedMapCard extends StatefulWidget {
  final String title;
  final String subtitle;
  final _MapCoordinates coordinates;
  final Color accentColor;
  final VoidCallback onOpenFullMap;

  const _EmbeddedMapCard({
    required this.title,
    required this.subtitle,
    required this.coordinates,
    required this.accentColor,
    required this.onOpenFullMap,
  });

  @override
  State<_EmbeddedMapCard> createState() => _EmbeddedMapCardState();
}

class _EmbeddedMapCardState extends State<_EmbeddedMapCard> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    final embeddedMapUrl = MapLaunchHelper.buildEmbeddedMapUrl(
      widget.coordinates.lat,
      widget.coordinates.lng,
    );
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (_) => NavigationDecision.navigate,
        ),
      )
      ..loadRequest(Uri.parse(embeddedMapUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ListTile(
            leading: Icon(Icons.map_outlined, color: widget.accentColor),
            title: Text(widget.title),
            subtitle: Text(widget.subtitle),
            trailing: IconButton(
              tooltip: 'Abrir mapa',
              onPressed: widget.onOpenFullMap,
              icon: const Icon(Icons.open_in_full),
            ),
          ),
          SizedBox(
            height: 220,
            child: IgnorePointer(
              child: WebViewWidget(controller: _controller),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Align(
              alignment: Alignment.centerRight,
              child: FilledButton.tonalIcon(
                onPressed: widget.onOpenFullMap,
                icon: const Icon(Icons.map),
                label: const Text('Ver mapa'),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MapViewerScreen extends StatefulWidget {
  final _MapCoordinates coordinates;
  final String title;
  final Color accentColor;
  final String provider;
  final String embeddedMapUrl;
  final String externalMapUrl;

  const _MapViewerScreen({
    required this.coordinates,
    required this.title,
    required this.accentColor,
    required this.provider,
    required this.embeddedMapUrl,
    required this.externalMapUrl,
  });

  @override
  State<_MapViewerScreen> createState() => _MapViewerScreenState();
}

class _MapViewerScreenState extends State<_MapViewerScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: (_) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (_) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (_) {
            setState(() {
              _isLoading = false;
            });
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.embeddedMapUrl));
  }

  Future<void> _openExternally() async {
    await FlavorUrlLauncher.openExternalRaw(widget.externalMapUrl);
  }

  @override
  Widget build(BuildContext context) {
    final providerLabel = MapLaunchHelper.providerLabel;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        backgroundColor: widget.accentColor,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            tooltip: 'Abrir fuera',
            onPressed: _openExternally,
            icon: const Icon(Icons.open_in_new),
          ),
        ],
        bottom: _isLoading
            ? PreferredSize(
                preferredSize: const Size.fromHeight(4),
                child: LinearProgressIndicator(value: _progress),
              )
            : null,
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            color: widget.accentColor.withOpacity(0.08),
            child: Text(
              '$providerLabel · ${widget.coordinates.lat}, ${widget.coordinates.lng}',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ),
          Expanded(
            child: WebViewWidget(controller: _controller),
          ),
        ],
      ),
    );
  }
}

class _ModuleGeneratedCreateScreen extends StatefulWidget {
  final ModuleDefinition module;
  final String actionName;
  final Map<String, dynamic> formConfig;
  final Map<String, dynamic>? initialValues;
  final Future<bool> Function(String actionName, Map<String, dynamic> params)
      onSubmit;

  const _ModuleGeneratedCreateScreen({
    required this.module,
    required this.actionName,
    required this.formConfig,
    this.initialValues,
    required this.onSubmit,
  });

  @override
  State<_ModuleGeneratedCreateScreen> createState() =>
      _ModuleGeneratedCreateScreenState();
}

class _ModuleGeneratedCreateScreenState
    extends State<_ModuleGeneratedCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  final Map<String, bool> _checkboxValues = {};
  final Map<String, String?> _selectedValues = {};
  bool _isSubmitting = false;

  List<MapEntry<String, dynamic>> get _fields {
    final rawFields = widget.formConfig['fields'];
    if (rawFields is Map) {
      return rawFields.entries
          .map((entry) => MapEntry(entry.key.toString(), entry.value))
          .toList();
    }
    return const [];
  }

  @override
  void initState() {
    super.initState();
    for (final field in _fields) {
      final config = field.value is Map
          ? Map<String, dynamic>.from(field.value as Map)
          : <String, dynamic>{};
      final type = config['type']?.toString() ?? 'text';
      final defaultValue = widget.initialValues?[field.key] ?? config['default'];

      switch (type) {
        case 'checkbox':
          _checkboxValues[field.key] = DynamicFormSupport.parseBool(defaultValue);
          break;
        case 'select':
          final options = _normalizeOptions(config['options']);
          final normalizedDefault = defaultValue?.toString();
          _selectedValues[field.key] = normalizedDefault != null &&
                  options.any((option) => option.value == normalizedDefault)
              ? normalizedDefault
              : (options.isNotEmpty ? options.first.value : null);
          break;
        default:
          _controllers[field.key] = TextEditingController(
            text: defaultValue?.toString() ?? '',
          );
      }
    }
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.formConfig['title']?.toString() ?? 'Nueva acción'),
        backgroundColor: widget.module.color,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if ((widget.formConfig['description']?.toString() ?? '').isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Text(widget.formConfig['description'].toString()),
              ),
            ..._fields.map((field) => _buildField(field.key, field.value)),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _isSubmitting ? null : _handleSubmit,
              icon: _isSubmitting
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.save),
              label: Text(
                _isSubmitting
                    ? 'Guardando...'
                    : (widget.formConfig['submit_text']?.toString() ?? 'Guardar'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildField(String fieldName, dynamic rawConfig) {
    final config = rawConfig is Map ? Map<String, dynamic>.from(rawConfig) : <String, dynamic>{};
    final label = config['label']?.toString() ?? fieldName;
    final type = config['type']?.toString() ?? 'text';
    final required = config['required'] == true;
    final hint =
        config['placeholder']?.toString() ?? config['description']?.toString();
    final helperText = config['description']?.toString();

    final keyboardType = switch (type) {
      'email' => TextInputType.emailAddress,
      'tel' => TextInputType.phone,
      'number' => const TextInputType.numberWithOptions(decimal: true),
      'url' => TextInputType.url,
      'textarea' => TextInputType.multiline,
      _ => TextInputType.text,
    };

    if (type == 'checkbox') {
      final currentValue = _checkboxValues[fieldName] ?? false;
      return Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: CheckboxListTile(
          value: currentValue,
          contentPadding: EdgeInsets.zero,
          title: Text(config['checkbox_label']?.toString() ?? label),
          subtitle: helperText != null && helperText.isNotEmpty
              ? Text(helperText)
              : null,
          controlAffinity: ListTileControlAffinity.leading,
          onChanged: (value) {
            setState(() {
              _checkboxValues[fieldName] = value ?? false;
            });
          },
        ),
      );
    }

    if (type == 'select') {
      final options = _normalizeOptions(config['options']);
      final selectedValue = _selectedValues[fieldName];
      return Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: DropdownButtonFormField<String>(
          value: selectedValue,
          items: options
              .map(
                (option) => DropdownMenuItem<String>(
                  value: option.value,
                  child: Text(option.label),
                ),
              )
              .toList(),
          decoration: InputDecoration(
            labelText: required ? '$label *' : label,
            helperText: helperText,
            border: const OutlineInputBorder(),
          ),
          onChanged: (value) {
            setState(() {
              _selectedValues[fieldName] = value;
            });
          },
          validator: (value) {
            if (required && (value == null || value.trim().isEmpty)) {
              return '$label es obligatorio';
            }
            return null;
          },
        ),
      );
    }

    final controller =
        _controllers.putIfAbsent(fieldName, () => TextEditingController());
    final isDateField = type == 'date' || type == 'datetime-local';
    final isTimeField = type == 'time';
    final isReadOnlyPicker = isDateField || isTimeField;
    final maxLines = type == 'textarea'
        ? ((config['rows'] as num?)?.toInt() ?? 4)
        : 1;

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        maxLines: maxLines,
        readOnly: isReadOnlyPicker,
        decoration: InputDecoration(
          labelText: required ? '$label *' : label,
          hintText: hint,
          helperText: helperText,
          border: const OutlineInputBorder(),
          suffixIcon: isReadOnlyPicker
              ? Icon(
                  isTimeField ? Icons.access_time : Icons.calendar_today,
                )
              : null,
        ),
        onTap: isReadOnlyPicker
            ? () => _pickDateOrTime(
                  fieldName: fieldName,
                  type: type,
                  controller: controller,
                )
            : null,
        validator: (value) {
          if (required && (value == null || value.trim().isEmpty)) {
            return '$label es obligatorio';
          }
          return null;
        },
      ),
    );
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSubmitting = true;
    });

    final params = <String, dynamic>{};
    for (final field in _fields) {
      final fieldName = field.key;
      final config = field.value is Map
          ? Map<String, dynamic>.from(field.value as Map)
          : <String, dynamic>{};
      final type = config['type']?.toString() ?? 'text';

      switch (type) {
        case 'checkbox':
          params[fieldName] = _checkboxValues[fieldName] ?? false;
          break;
        case 'select':
          params[fieldName] = _selectedValues[fieldName] ?? '';
          break;
        default:
          params[fieldName] = _controllers[fieldName]?.text.trim() ?? '';
      }
    }

    final success = await widget.onSubmit(widget.actionName, params);

    if (!mounted) return;

    setState(() {
      _isSubmitting = false;
    });

    if (success) {
      Navigator.of(context).pop(true);
    }
  }

  Future<void> _pickDateOrTime({
    required String fieldName,
    required String type,
    required TextEditingController controller,
  }) async {
    if (type == 'time') {
      final picked = await DynamicFormSupport.pickTime(context);
      if (picked != null) {
        controller.text = picked.submitValue;
      }
      return;
    }

    if (type == 'datetime-local') {
      final picked = await DynamicFormSupport.pickDateTime(context);
      if (picked == null || !mounted) return;
      controller.text = picked.submitValue;
      return;
    }

    final picked = await DynamicFormSupport.pickDate(context);
    if (picked == null || !mounted) return;
    controller.text = picked.submitValue;
  }

  List<_FormOption> _normalizeOptions(dynamic rawOptions) {
    if (rawOptions is Map) {
      return rawOptions.entries
          .map(
            (entry) => _FormOption(
              value: entry.key.toString(),
              label: entry.value?.toString() ?? entry.key.toString(),
            ),
          )
          .toList();
    }

    if (rawOptions is List) {
      return rawOptions.map((option) {
        if (option is Map) {
          final value = option['value']?.toString() ??
              option['id']?.toString() ??
              option['key']?.toString() ??
              '';
          final label = option['label']?.toString() ??
              option['name']?.toString() ??
              value;
          return _FormOption(value: value, label: label);
        }
        final value = option.toString();
        return _FormOption(value: value, label: value);
      }).toList();
    }

    return const [];
  }
}

class _FormOption {
  final String value;
  final String label;

  const _FormOption({
    required this.value,
    required this.label,
  });
}

/// Tipos de pantalla de módulo
enum ModuleScreenType {
  list,
  grid,
  detail,
  dashboard,
}
