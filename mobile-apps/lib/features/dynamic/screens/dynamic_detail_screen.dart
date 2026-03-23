import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../models/module_config.dart';
import '../widgets/icon_helper.dart';
import 'dynamic_form_screen.dart';

/// Pantalla de detalle dinámica
class DynamicDetailScreen extends ConsumerStatefulWidget {
  final ModuleConfig config;
  final String itemId;
  final Map<String, dynamic>? initialData;

  const DynamicDetailScreen({
    super.key,
    required this.config,
    required this.itemId,
    this.initialData,
  });

  @override
  ConsumerState<DynamicDetailScreen> createState() => _DynamicDetailScreenState();
}

class _DynamicDetailScreenState extends ConsumerState<DynamicDetailScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.initialData != null) {
      _data = widget.initialData;
      _loading = false;
    }
    _loadData();
  }

  Future<void> _loadData() async {
    final endpoint = widget.config.detailEndpoint ?? '${widget.config.endpoint}/${widget.itemId}';

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(endpoint);

      if (response.success && response.data != null) {
        setState(() {
          _data = response.data!['data'] ?? response.data!['item'] ?? response.data;
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar detalles';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final titulo = _data?['titulo'] ?? _data?['nombre'] ?? _data?['title'] ?? widget.config.titulo;

    return Scaffold(
      appBar: AppBar(
        title: Text(titulo.toString()),
        actions: [
          if (widget.config.acciones.any((a) => a.id == 'editar'))
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: _onEdit,
            ),
          PopupMenuButton<ModuleAction>(
            onSelected: _executeAction,
            itemBuilder: (context) => widget.config.acciones
                .where((a) => a.id != 'ver' && a.id != 'navigate')
                .map((action) => PopupMenuItem(
                      value: action,
                      child: Row(
                        children: [
                          Icon(IconHelper.getIcon(action.icono), size: 20),
                          const SizedBox(width: 12),
                          Text(action.label),
                        ],
                      ),
                    ))
                .toList(),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading && _data == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _data == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
            const SizedBox(height: 16),
            Text(_error!),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _loadData,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      );
    }

    if (_data == null) {
      return const Center(child: Text('No hay datos'));
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen header
            if (_hasImage())
              Image.network(
                _getImageUrl(),
                height: 250,
                width: double.infinity,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => _buildImagePlaceholder(),
              )
            else
              _buildImagePlaceholder(),

            // Estado badge
            if (_data!['estado'] != null)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: _buildStatusChip(_data!['estado'].toString()),
              ),

            // Contenido
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: _buildFields(),
              ),
            ),

            // Acciones
            if (widget.config.acciones.isNotEmpty)
              Padding(
                padding: const EdgeInsets.all(16),
                child: _buildActions(),
              ),
          ],
        ),
      ),
    );
  }

  bool _hasImage() {
    return _data!['imagen'] != null ||
           _data!['image'] != null ||
           _data!['foto'] != null ||
           _data!['thumbnail'] != null;
  }

  String _getImageUrl() {
    return _data!['imagen']?.toString() ??
           _data!['image']?.toString() ??
           _data!['foto']?.toString() ??
           _data!['thumbnail']?.toString() ??
           '';
  }

  Widget _buildImagePlaceholder() {
    return Container(
      height: 200,
      color: Colors.grey.shade200,
      child: Center(
        child: Icon(
          IconHelper.getIcon(widget.config.icono),
          size: 80,
          color: Colors.grey.shade400,
        ),
      ),
    );
  }

  Widget _buildStatusChip(String estado) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: IconHelper.getStatusColor(estado),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        IconHelper.getStatusLabel(estado),
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  List<Widget> _buildFields() {
    final widgets = <Widget>[];

    // Campos prioritarios
    final priorityFields = ['descripcion', 'description', 'contenido', 'content'];
    for (final field in priorityFields) {
      if (_data![field] != null && _data![field].toString().isNotEmpty) {
        widgets.add(Text(
          _data![field].toString(),
          style: const TextStyle(fontSize: 16, height: 1.5),
        ));
        widgets.add(const SizedBox(height: 24));
        break;
      }
    }

    // Campos de información
    final infoFields = <String, IconData>{
      'fecha': Icons.calendar_today,
      'fecha_inicio': Icons.calendar_today,
      'fecha_fin': Icons.event,
      'hora': Icons.schedule,
      'hora_inicio': Icons.schedule,
      'hora_fin': Icons.schedule,
      'lugar': Icons.place,
      'ubicacion': Icons.place,
      'direccion': Icons.location_on,
      'telefono': Icons.phone,
      'email': Icons.email,
      'precio': Icons.attach_money,
      'capacidad': Icons.people,
      'plazas': Icons.event_seat,
      'categoria': Icons.category,
      'tipo': Icons.label,
      'autor': Icons.person,
      'creado_por': Icons.person,
    };

    for (final entry in infoFields.entries) {
      if (_data![entry.key] != null && _data![entry.key].toString().isNotEmpty) {
        widgets.add(_buildInfoRow(
          icon: entry.value,
          label: _formatLabel(entry.key),
          value: _formatValue(entry.key, _data![entry.key]),
        ));
      }
    }

    return widgets;
  }

  Widget _buildInfoRow({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                ),
                Text(
                  value,
                  style: const TextStyle(fontSize: 16),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _formatLabel(String key) {
    return key
        .replaceAll('_', ' ')
        .split(' ')
        .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
        .join(' ');
  }

  String _formatValue(String key, dynamic value) {
    if (key.contains('fecha') && value is String) {
      try {
        final date = DateTime.parse(value);
        return '${date.day}/${date.month}/${date.year}';
      } catch (e) {
        debugPrint('Error formateando fecha "$key": $e');
      }
    }
    if (key.contains('precio') && value is num) {
      return '${value.toStringAsFixed(2)} €';
    }
    return value.toString();
  }

  Widget _buildActions() {
    final mainActions = widget.config.acciones
        .where((a) => a.tipo == 'api_call')
        .take(2)
        .toList();

    if (mainActions.isEmpty) return const SizedBox.shrink();

    return Row(
      children: mainActions.map((action) {
        return Expanded(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: FilledButton.icon(
              onPressed: () => _executeAction(action),
              icon: Icon(IconHelper.getIcon(action.icono)),
              label: Text(action.label),
            ),
          ),
        );
      }).toList(),
    );
  }

  void _onEdit() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => DynamicFormScreen(
          config: widget.config,
          mode: FormMode.edit,
          initialData: _data,
        ),
      ),
    ).then((result) {
      if (result == true) _loadData();
    });
  }

  Future<void> _executeAction(ModuleAction action) async {
    if (action.requiereConfirmacion) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(action.label),
          content: const Text('¿Estás seguro?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Confirmar'),
            ),
          ],
        ),
      );
      if (confirmed != true) return;
    }

    if (action.endpoint != null) {
      final api = ref.read(apiClientProvider);
      final endpoint = action.endpoint!.replaceAll('{id}', widget.itemId);

      try {
        final response = await api.post(endpoint, data: {});
        if (response.success) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('${action.label} completado')),
            );
          }
          _loadData();
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e')),
          );
        }
      }
    }
  }
}
