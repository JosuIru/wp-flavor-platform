import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';

/// Pantalla de detalle de evento con inscripción
class EventosDetailScreen extends ConsumerStatefulWidget {
  final int eventoId;
  final String eventoTitle;

  const EventosDetailScreen({
    super.key,
    required this.eventoId,
    required this.eventoTitle,
  });

  @override
  ConsumerState<EventosDetailScreen> createState() => _EventosDetailScreenState();
}

class _EventosDetailScreenState extends ConsumerState<EventosDetailScreen> {
  bool _isLoading = true;
  bool _isInscribing = false;
  Map<String, dynamic>? _evento;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadEventoDetail();
  }

  Future<void> _loadEventoDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/eventos/${widget.eventoId}');

      if (response.success && response.data != null) {
        setState(() {
          _evento = response.data!['evento'] ?? response.data;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar el evento';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error de conexión: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _inscribirse() async {
    final i18n = AppLocalizations.of(context);

    // Confirmar inscripción
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.commonConfirm),
        content: const Text('¿Deseas inscribirte en este evento?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Inscribirse'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isInscribing = true);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/eventos/${widget.eventoId}/inscribir',
        data: {},
      );

      if (mounted) {
        setState(() => _isInscribing = false);

        if (response.success) {
          // Recargar detalle para actualizar estado de inscripción
          _loadEventoDetail();

          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('¡Inscripción exitosa!'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al inscribirse'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isInscribing = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error de conexión: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.eventoTitle),
      ),
      body: _isLoading
          ? const FlavorLoadingState()
          : _error != null
              ? Center(
                      child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, size: 64, color: Colors.red),
                      const SizedBox(height: 16),
                      Text(_error!, style: theme.textTheme.bodyLarge),
                      const SizedBox(height: 16),
                      FilledButton.icon(
                        onPressed: _loadEventoDetail,
                        icon: const Icon(Icons.refresh),
                        label: Text(i18n.commonRetry),
                      ),
                    ],
                  ),
                )
              : _evento == null
                  ? const Center(child: Text('Evento no encontrado'))
                  : RefreshIndicator(
                      onRefresh: _loadEventoDetail,
                      child: SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Imagen del evento
                            if (_evento!['imagen'] != null)
                              Image.network(
                                _evento!['imagen'],
                                width: double.infinity,
                                height: 250,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) => Container(
                                  height: 250,
                                  color: Colors.grey[300],
                                  child: const Icon(Icons.event, size: 80, color: Colors.grey),
                                ),
                              ),

                            Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Título
                                  Text(
                                    _evento!['titulo'] ?? widget.eventoTitle,
                                    style: theme.textTheme.headlineSmall?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 16),

                                  // Fecha y hora
                                  _buildInfoRow(
                                    Icons.calendar_today,
                                    _formatDate(_evento!['fecha']),
                                    theme,
                                  ),
                                  const SizedBox(height: 8),

                                  // Hora
                                  if (_evento!['hora'] != null)
                                    _buildInfoRow(
                                      Icons.access_time,
                                      _evento!['hora'],
                                      theme,
                                    ),
                                  const SizedBox(height: 8),

                                  // Ubicación
                                  if (_evento!['ubicacion'] != null)
                                    _buildInfoRow(
                                      Icons.location_on,
                                      _evento!['ubicacion'],
                                      theme,
                                    ),
                                  const SizedBox(height: 8),

                                  // Plazas
                                  if (_evento!['plazas_totales'] != null)
                                    _buildInfoRow(
                                      Icons.people,
                                      '${_evento!['plazas_ocupadas'] ?? 0} / ${_evento!['plazas_totales']} plazas',
                                      theme,
                                    ),
                                  const SizedBox(height: 24),

                                  // Descripción
                                  Text(
                                    'Descripción',
                                    style: theme.textTheme.titleMedium?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    _evento!['descripcion'] ?? 'Sin descripción',
                                    style: theme.textTheme.bodyLarge,
                                  ),
                                  const SizedBox(height: 24),

                                  // Estado de inscripción
                                  if (_evento!['inscrito'] == true)
                                    Card(
                                      color: Colors.green.shade50,
                                      child: Padding(
                                        padding: const EdgeInsets.all(16),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.check_circle, color: Colors.green),
                                            const SizedBox(width: 12),
                                            Expanded(
                                              child: Text(
                                                '¡Ya estás inscrito en este evento!',
                                                style: TextStyle(
                                                  color: Colors.green.shade900,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),

                                  // Botón de inscripción
                                  if (_evento!['inscrito'] != true)
                                    const SizedBox(height: 16),

                                  if (_evento!['inscrito'] != true)
                                    SizedBox(
                                      width: double.infinity,
                                      child: FilledButton.icon(
                                        onPressed: _isInscribing ? null : _inscribirse,
                                        icon: _isInscribing
                                            ? const FlavorInlineSpinner(color: Colors.white)
                                            : const Icon(Icons.check_circle_outline),
                                        label: Text(_isInscribing ? 'Inscribiendo...' : 'Inscribirse'),
                                        style: FilledButton.styleFrom(
                                          padding: const EdgeInsets.symmetric(vertical: 16),
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
    );
  }

  Widget _buildInfoRow(IconData icon, String text, ThemeData theme) {
    return Row(
      children: [
        Icon(icon, size: 20, color: theme.colorScheme.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: theme.textTheme.bodyMedium,
          ),
        ),
      ],
    );
  }

  String _formatDate(dynamic date) {
    if (date == null) return 'Fecha no especificada';

    try {
      DateTime dateTime;
      if (date is String) {
        dateTime = DateTime.parse(date);
      } else if (date is DateTime) {
        dateTime = date;
      } else {
        return date.toString();
      }

      return DateFormat('EEEE, d MMMM yyyy', 'es_ES').format(dateTime);
    } catch (e) {
      return date.toString();
    }
  }
}
