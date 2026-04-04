import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class AvisosMunicipalesScreen extends ConsumerStatefulWidget {
  const AvisosMunicipalesScreen({super.key});

  @override
  ConsumerState<AvisosMunicipalesScreen> createState() => _AvisosMunicipalesScreenState();
}

class _AvisosMunicipalesScreenState extends ConsumerState<AvisosMunicipalesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  String _categoriaSeleccionada = 'todas';

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getAvisosMunicipales();
  }

  Future<void> _refresh() async {
    setState(() => _future = ref.read(apiClientProvider).getAvisosMunicipales(categoria: _categoriaSeleccionada != 'todas' ? _categoriaSeleccionada : null));
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Avisos Municipales'),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings),
            onPressed: () => _configurarSuscripciones(context),
            tooltip: i18n.avisosSubscriptions,
          ),
        ],
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.avisosError,
              onRetry: _refresh,
              icon: Icons.campaign_outlined,
            );
          }

          final data = response.data!;
          final avisos = (data['avisos'] as List?)?.cast<Map<String, dynamic>>() ?? [];
          final categorias = (data['categorias'] as List?)?.cast<String>() ?? [];
          return Column(
            children: [
              _buildCategoriaFilter(context, categorias, i18n),
              Expanded(
                child: avisos.isEmpty
                    ? FlavorEmptyState(
                        icon: Icons.campaign_outlined,
                        title: i18n.avisosEmpty,
                      )
                    : RefreshIndicator(
                        onRefresh: _refresh,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: avisos.length,
                          itemBuilder: (context, index) => _buildAvisoCard(context, avisos[index], i18n),
                        ),
                      ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCategoriaFilter(BuildContext context, List<String> categorias, AppLocalizations i18n) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Row(
          children: [
            FilterChip(
              label: Text(i18n.avisosAll),
              selected: _categoriaSeleccionada == 'todas',
              onSelected: (selected) {
                if (selected) {
                  setState(() {
                    _categoriaSeleccionada = 'todas';
                    _refresh();
                  });
                }
              },
            ),
            const SizedBox(width: 8),
            ...categorias.map((categoria) {
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: FilterChip(
                  label: Text(categoria),
                  selected: _categoriaSeleccionada == categoria,
                  onSelected: (selected) {
                    if (selected) {
                      setState(() {
                        _categoriaSeleccionada = categoria;
                        _refresh();
                      });
                    }
                  },
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildAvisoCard(BuildContext context, Map<String, dynamic> aviso, AppLocalizations i18n) {
    final titulo = aviso['titulo']?.toString() ?? '';
    final contenido = aviso['contenido']?.toString() ?? '';
    final categoria = aviso['categoria']?.toString() ?? '';
    final fecha = aviso['fecha']?.toString() ?? '';
    final prioridad = aviso['prioridad']?.toString() ?? 'normal';
    final leido = aviso['leido'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: leido ? 0 : 2,
      color: leido ? null : Colors.blue.shade50,
      child: InkWell(
        onTap: () => _verDetalleAviso(context, aviso),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: _getPrioridadColor(prioridad).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(_getCategoriaIcon(categoria), color: _getPrioridadColor(prioridad), size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          titulo,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: leido ? FontWeight.normal : FontWeight.bold,
                              ),
                        ),
                        Row(
                          children: [
                            Chip(
                              label: Text(categoria),
                              visualDensity: VisualDensity.compact,
                            ),
                            if (prioridad == 'urgente') ...[
                              const SizedBox(width: 4),
                              Chip(
                                label: Text(i18n.avisosUrgent),
                                visualDensity: VisualDensity.compact,
                                backgroundColor: Colors.red.shade100,
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (!leido)
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Colors.blue,
                        shape: BoxShape.circle,
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                contenido,
                style: Theme.of(context).textTheme.bodyMedium,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14),
                  const SizedBox(width: 4),
                  Text(fecha, style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  IconData _getCategoriaIcon(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'obras':
        return Icons.construction;
      case 'eventos':
        return Icons.event;
      case 'servicios':
        return Icons.home_repair_service;
      case 'emergencias':
        return Icons.warning;
      case 'cultura':
        return Icons.museum;
      case 'deportes':
        return Icons.sports;
      case 'medio ambiente':
        return Icons.eco;
      case 'tráfico':
        return Icons.traffic;
      default:
        return Icons.announcement;
    }
  }

  Color _getPrioridadColor(String prioridad) {
    switch (prioridad) {
      case 'urgente':
        return Colors.red;
      case 'alta':
        return Colors.orange;
      case 'normal':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }

  void _verDetalleAviso(BuildContext context, Map<String, dynamic> aviso) {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);
    final id = (aviso['id'] as num?)?.toInt() ?? 0;
    final titulo = aviso['titulo']?.toString() ?? '';
    final contenido = aviso['contenido']?.toString() ?? '';
    final categoria = aviso['categoria']?.toString() ?? '';
    final fecha = aviso['fecha']?.toString() ?? '';
    final prioridad = aviso['prioridad']?.toString() ?? 'normal';
    final enlace = aviso['enlace']?.toString() ?? '';

    // Marcar como leído
    api.marcarAvisoLeido(id);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.7,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (context, scrollController) {
            return Container(
              padding: const EdgeInsets.all(16),
              child: ListView(
                controller: scrollController,
                children: [
                  Row(
                    children: [
                      Icon(_getCategoriaIcon(categoria), color: _getPrioridadColor(prioridad), size: 32),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          titulo,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Chip(label: Text(categoria)),
                      const SizedBox(width: 8),
                      if (prioridad == 'urgente')
                        Chip(
                          label: Text(i18n.avisosUrgent),
                          backgroundColor: Colors.red.shade100,
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 16),
                      const SizedBox(width: 4),
                      Text(fecha),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    contenido,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                  if (enlace.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: () async {
                        Navigator.pop(context);
                        if (!mounted) return;
                        await FlavorUrlLauncher.openExternal(
                          context,
                          enlace,
                          emptyMessage: 'No hay enlace disponible.',
                          errorMessage: 'No se puede abrir el enlace',
                          normalizeHttpScheme: true,
                        );
                      },
                      icon: const Icon(Icons.link),
                      label: Text(i18n.avisosMoreInfo),
                    ),
                  ],
                  const SizedBox(height: 16),
                ],
              ),
            );
          },
        );
      },
    ).then((_) => _refresh());
  }

  void _configurarSuscripciones(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final response = await api.getAvisosMunicipales();
    if (!response.success || response.data == null) return;

    final categorias = (response.data!['categorias'] as List?)?.cast<String>() ?? [];
    final suscripciones = (response.data!['mis_suscripciones'] as List?)?.cast<String>() ?? [];
    final suscripcionesSet = Set<String>.from(suscripciones);

    if (!context.mounted) return;

    await showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.avisosSubscriptions),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: categorias.map((categoria) {
                    final suscrito = suscripcionesSet.contains(categoria);
                    return CheckboxListTile(
                      title: Text(categoria),
                      value: suscrito,
                      onChanged: (value) {
                        setDialogState(() {
                          if (value == true) {
                            suscripcionesSet.add(categoria);
                          } else {
                            suscripcionesSet.remove(categoria);
                          }
                        });
                      },
                    );
                  }).toList(),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text(i18n.commonCancel),
                ),
                FilledButton(
                  onPressed: () async {
                    final result = await api.actualizarSuscripcionesAvisos(suscripcionesSet.toList());
                    if (context.mounted) {
                      Navigator.pop(context);
                      final msg = result.success ? i18n.avisosSubscriptionsSuccess : (result.error ?? i18n.avisosSubscriptionsError);
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
                      _refresh();
                    }
                  },
                  child: Text(i18n.commonSave),
                ),
              ],
            );
          },
        );
      },
    );
  }
}
