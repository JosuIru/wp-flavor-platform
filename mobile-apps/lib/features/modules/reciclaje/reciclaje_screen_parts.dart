part of 'reciclaje_screen.dart';

extension _ReciclajeScreenParts on _ReciclajeScreenState {
  void _showGuiaReciclaje(BuildContext context) {
    final i18n = AppLocalizations.of(context);
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
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.recycling, color: Colors.green, size: 32),
                      const SizedBox(width: 12),
                      Text(
                        i18n.reciclajeGuide,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: ListView(
                      controller: scrollController,
                      children: [
                        _buildGuiaItem(
                          context,
                          Colors.brown,
                          Icons.compost,
                          i18n.reciclajeOrganic,
                          i18n.reciclajeOrganicDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.blue,
                          Icons.description,
                          i18n.reciclajePaper,
                          i18n.reciclajePaperDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.yellow,
                          Icons.local_drink,
                          i18n.reciclajePlastic,
                          i18n.reciclajePlasticDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.green,
                          Icons.wine_bar,
                          i18n.reciclajeGlass,
                          i18n.reciclajeGlassDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.red,
                          Icons.devices,
                          i18n.reciclajeElectronic,
                          i18n.reciclajeElectronicDesc,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildGuiaItem(BuildContext context, Color color, IconData icon, String title, String description) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, color: color, size: 32),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _addRecordatorio(BuildContext context, Map<String, dynamic> recogida) {
    final i18n = AppLocalizations.of(context);
    final tipo = recogida['tipo']?.toString() ?? 'Recogida';
    final fecha = recogida['fecha']?.toString() ?? '';
    final hora = recogida['hora']?.toString() ?? '';
    final zona = recogida['zona']?.toString() ?? '';

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.alarm_add, color: Colors.green),
            const SizedBox(width: 8),
            Text(i18n.reciclajeAddReminder),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Se configurará un recordatorio para:'),
            const SizedBox(height: 12),
            Card(
              color: _getTipoColor(tipo).withOpacity(0.1),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(_getTipoIcon(tipo), color: _getTipoColor(tipo)),
                        const SizedBox(width: 8),
                        Text(
                          tipo,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    if (fecha.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 14),
                          const SizedBox(width: 4),
                          Text('$fecha${hora.isNotEmpty ? " - $hora" : ""}'),
                        ],
                      ),
                    ],
                    if (zona.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.place, size: 14),
                          const SizedBox(width: 4),
                          Text(zona),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'Recibirás una notificación el día anterior.',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () async {
              Navigator.pop(context);
              await _guardarRecordatorio(recogida);
            },
            child: const Text('Activar recordatorio'),
          ),
        ],
      ),
    );
  }

  Future<void> _guardarRecordatorio(Map<String, dynamic> recogida) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/reciclaje/recordatorios', data: {
        'tipo': recogida['tipo'],
        'fecha': recogida['fecha'],
        'hora': recogida['hora'],
        'zona': recogida['zona'],
      });

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, i18n.reciclajeReminderAdded);
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al guardar recordatorio');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _abrirMapa(BuildContext context, Map<String, dynamic> punto) {
    final i18n = AppLocalizations.of(context);
    final nombre = punto['nombre']?.toString() ?? 'Punto de reciclaje';
    final direccion = punto['direccion']?.toString() ?? '';
    final latitud = punto['latitud'] ?? punto['lat'];
    final longitud = punto['longitud'] ?? punto['lng'] ?? punto['lon'];
    final tiposAcepta = punto['tipos_acepta']?.toString() ?? '';
    final horario = punto['horario']?.toString() ?? '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.5,
        minChildSize: 0.3,
        maxChildSize: 0.7,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Row(
                children: [
                  const CircleAvatar(
                    backgroundColor: Colors.green,
                    child: Icon(Icons.recycling, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombre,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        if (direccion.isNotEmpty)
                          Text(
                            direccion,
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              if (horario.isNotEmpty)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.schedule, color: Colors.green),
                  title: const Text('Horario'),
                  subtitle: Text(horario),
                ),
              if (tiposAcepta.isNotEmpty) ...[
                const Text(
                  'Tipos de residuos aceptados:',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: tiposAcepta.split(',').map((tipo) {
                    final tipoTrimmed = tipo.trim();
                    return Chip(
                      avatar: Icon(
                        _getTipoIcon(tipoTrimmed),
                        size: 16,
                        color: _getTipoColor(tipoTrimmed),
                      ),
                      label: Text(tipoTrimmed),
                      backgroundColor: _getTipoColor(tipoTrimmed).withOpacity(0.1),
                      visualDensity: VisualDensity.compact,
                    );
                  }).toList(),
                ),
              ],
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _copiarDireccion(direccion.isNotEmpty ? direccion : nombre);
                      },
                      icon: const Icon(Icons.copy),
                      label: const Text('Copiar dirección'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _abrirEnMapasExternos(nombre, direccion, latitud, longitud);
                      },
                      icon: const Icon(Icons.directions),
                      label: Text(i18n.reciclajeDirections),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _copiarDireccion(String direccion) async {
    if (direccion.isEmpty) return;

    await Clipboard.setData(ClipboardData(text: direccion));

    if (mounted) {
      FlavorSnackbar.showSuccess(context, 'Dirección copiada al portapapeles');
    }
  }

  void _abrirEnMapasExternos(String nombre, String direccion, dynamic latitud, dynamic longitud) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Abrir en aplicación de mapas',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: const Icon(Icons.map, color: Colors.blue),
                title: const Text('Google Maps'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('google', nombre, direccion, latitud, longitud);
                },
              ),
              ListTile(
                leading: const Icon(Icons.map_outlined, color: Colors.grey),
                title: const Text('Apple Maps'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('apple', nombre, direccion, latitud, longitud);
                },
              ),
              ListTile(
                leading: const Icon(Icons.navigation, color: Colors.orange),
                title: const Text('Waze'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('waze', nombre, direccion, latitud, longitud);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _lanzarMapa(String app, String nombre, String direccion, dynamic lat, dynamic lng) async {
    Uri? uri;
    final latStr = lat?.toString();
    final lngStr = lng?.toString();
    final query = direccion.isNotEmpty ? direccion : nombre;

    switch (app) {
      case 'google':
        if (latStr != null && lngStr != null) {
          final latValue = double.tryParse(latStr);
          final lngValue = double.tryParse(lngStr);
          if (latValue != null && lngValue != null) {
            uri = MapLaunchHelper.buildConfiguredMapUri(
              latValue,
              lngValue,
              query: query,
            );
          }
        }
        uri ??= Uri.parse(
          'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(query)}',
        );
        break;
      case 'apple':
        if (latStr != null && lngStr != null) {
          uri = Uri.parse('https://maps.apple.com/?ll=$latStr,$lngStr&q=${Uri.encodeComponent(nombre)}');
        } else {
          uri = Uri.parse('https://maps.apple.com/?q=${Uri.encodeComponent(query)}');
        }
        break;
      case 'waze':
        if (latStr != null && lngStr != null) {
          uri = Uri.parse('https://waze.com/ul?ll=$latStr,$lngStr&navigate=yes');
        } else {
          uri = Uri.parse('https://waze.com/ul?q=${Uri.encodeComponent(query)}');
        }
        break;
    }

    if (uri != null) {
      if (!mounted) return;
      await FlavorUrlLauncher.openExternalUri(
        context,
        uri,
        errorMessage: 'No se puede abrir ${app.toUpperCase()}',
      );
    }
  }

  void _verDetallesTipo(BuildContext context, String tipo) {
    final i18n = AppLocalizations.of(context);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(tipo),
        content: Text('${i18n.reciclajeDetailsFor} $tipo'),
        actions: [
          FilledButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.commonClose),
          ),
        ],
      ),
    );
  }
}
