import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/utils/flavor_share_helper.dart';
import '../../../core/widgets/flavor_initials_avatar.dart';
import '../../../core/widgets/flavor_meta_chip.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class ForoDetalleScreen extends ConsumerStatefulWidget {
  final dynamic foroId;

  const ForoDetalleScreen({super.key, required this.foroId});

  @override
  ConsumerState<ForoDetalleScreen> createState() => _ForoDetalleScreenState();
}

class _ForoDetalleScreenState extends ConsumerState<ForoDetalleScreen> {
  Map<String, dynamic>? _datosForo;
  List<dynamic> _listaRespuestas = [];
  bool _cargando = true;
  String? _mensajeError;
  final TextEditingController _controladorRespuesta = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  @override
  void dispose() {
    _controladorRespuesta.dispose();
    super.dispose();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/foros/${widget.foroId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosForo = respuesta.data!['data'] ?? respuesta.data!;
          _listaRespuestas =
              _datosForo?['respuestas'] ?? _datosForo?['replies'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar foro';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  Future<void> _enviarRespuesta() async {
    if (_controladorRespuesta.text.trim().isEmpty) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/foros/${widget.foroId}/respuestas',
        data: {'contenido': _controladorRespuesta.text.trim()},
      );
      if (!mounted) return;
      await FlavorMutation.runApiResponse(
        context,
        request: () => Future.value(respuesta),
        successMessage: 'Respuesta enviada',
        fallbackErrorMessage: 'Error al enviar',
        onSuccess: () async {
          _controladorRespuesta.clear();
          await _cargarDetalle();
        },
      );
    } catch (excepcion) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, excepcion.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Debate'),
        actions: [
          IconButton(icon: const Icon(Icons.share), onPressed: _compartirForo),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDetalle,
                  icon: Icons.forum,
                )
              : _datosForo == null
                  ? const FlavorEmptyState(
                      icon: Icons.forum,
                      title: 'No se encontraron datos',
                    )
                  : Column(
                      children: [
                        Expanded(
                          child: ListView(
                            padding: const EdgeInsets.all(16),
                            children: [
                              Text(
                                _datosForo!['titulo'] ??
                                    _datosForo!['nombre'] ??
                                    'Debate',
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  FlavorInitialsAvatar(
                                    name: (_datosForo!['autor'] ?? 'U').toString(),
                                    radius: 16,
                                  ),
                                  const SizedBox(width: 8),
                                  Text(_datosForo!['autor'] ?? 'Usuario'),
                                  const Spacer(),
                                  Text(
                                    _datosForo!['fecha'] ?? '',
                                    style: TextStyle(
                                      color: Colors.grey.shade600,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  if ((_datosForo!['categoria'] ?? '').toString().isNotEmpty)
                                    FlavorMetaChip(
                                      icon: Icons.label_outline,
                                      label: (_datosForo!['categoria']).toString(),
                                    ),
                                  FlavorMetaChip(
                                    icon: Icons.reply_outlined,
                                    label: '${_listaRespuestas.length} respuestas',
                                  ),
                                  if ((_datosForo!['vistas'] ?? _datosForo!['views'] ?? 0) != 0)
                                    FlavorMetaChip(
                                      icon: Icons.visibility_outlined,
                                      label: '${_datosForo!['vistas'] ?? _datosForo!['views']} vistas',
                                    ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              if (_datosForo!['contenido'] != null ||
                                  _datosForo!['descripcion'] != null)
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.all(16),
                                  decoration: BoxDecoration(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .surfaceContainerHighest,
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Text(
                                    _datosForo!['contenido'] ??
                                        _datosForo!['descripcion'],
                                  ),
                                ),
                              const Divider(height: 32),
                              Text(
                                'Respuestas (${_listaRespuestas.length})',
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 12),
                              if (_listaRespuestas.isEmpty)
                                Center(
                                  child: Padding(
                                    padding: const EdgeInsets.all(24),
                                    child: Text(
                                      'Se el primero en responder',
                                      style: TextStyle(
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                  ),
                                )
                              else
                                ..._listaRespuestas
                                    .map((respuesta) => _RespuestaCard(
                                          item: respuesta
                                              as Map<String, dynamic>,
                                        )),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.05),
                                blurRadius: 10,
                                offset: const Offset(0, -2),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: _controladorRespuesta,
                                  decoration: InputDecoration(
                                    hintText: 'Escribe tu respuesta...',
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(24),
                                    ),
                                    contentPadding:
                                        const EdgeInsets.symmetric(
                                      horizontal: 16,
                                      vertical: 12,
                                    ),
                                  ),
                                  maxLines: null,
                                ),
                              ),
                              const SizedBox(width: 8),
                              IconButton.filled(
                                onPressed: _enviarRespuesta,
                                icon: const Icon(Icons.send),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
    );
  }

  void _compartirForo() {
    if (_datosForo == null) return;

    final tituloForo = _datosForo!['titulo'] ?? _datosForo!['nombre'] ?? 'Debate';
    final autorForo = _datosForo!['autor'] ?? 'Usuario';
    final contenidoForo =
        _datosForo!['contenido'] ?? _datosForo!['descripcion'] ?? '';
    final totalRespuestas = _listaRespuestas.length;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.share, color: Colors.purple.shade400),
                const SizedBox(width: 12),
                const Text(
                  'Compartir debate',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    tituloForo,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Por $autorForo • $totalRespuestas respuestas',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Compartir mediante:',
              style: TextStyle(fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _ShareOption(
                  icon: Icons.copy,
                  label: 'Copiar enlace',
                  color: Colors.blue,
                  onTap: () async {
                    final enlace = 'https://ejemplo.com/foros/${widget.foroId}';
                    Navigator.pop(context);
                    await FlavorShareHelper.copyText(
                      this.context,
                      enlace,
                      successMessage: 'Enlace copiado al portapapeles',
                    );
                  },
                ),
                _ShareOption(
                  icon: Icons.message,
                  label: 'Chat interno',
                  color: Colors.purple,
                  onTap: () {
                    Navigator.pop(context);
                    _compartirEnChat(tituloForo, contenidoForo);
                  },
                ),
                _ShareOption(
                  icon: Icons.email,
                  label: 'Email',
                  color: Colors.orange,
                  onTap: () async {
                    Navigator.pop(context);
                    await FlavorShareHelper.shareText(
                      'Debate: $tituloForo\n\n$contenidoForo\n\nhttps://ejemplo.com/foros/${widget.foroId}',
                      subject: tituloForo,
                    );
                  },
                ),
                _ShareOption(
                  icon: Icons.more_horiz,
                  label: 'Mas',
                  color: Colors.grey,
                  onTap: () async {
                    Navigator.pop(context);
                    await FlavorShareHelper.shareText(
                      'Debate: $tituloForo\n\n$contenidoForo\n\nhttps://ejemplo.com/foros/${widget.foroId}',
                      subject: tituloForo,
                    );
                  },
                ),
              ],
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Future<void> _compartirEnChat(String titulo, String contenido) async {
    final controladorMensaje = TextEditingController(
      text:
          '📢 Te comparto este debate:\n\n"$titulo"\n\n${contenido.length > 100 ? '${contenido.substring(0, 100)}...' : contenido}',
    );

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Compartir en chat'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Escribe un mensaje para acompañar el enlace:'),
            const SizedBox(height: 12),
            TextField(
              controller: controladorMensaje,
              maxLines: 4,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'Mensaje...',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Compartir'),
          ),
        ],
      ),
    );

    controladorMensaje.dispose();

    if (confirmar == true && mounted) {
      FlavorSnackbar.showSuccess(context, 'Debate compartido en el chat');
    }
  }
}

class _ShareOption extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _ShareOption({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: Column(
          children: [
            CircleAvatar(
              backgroundColor: color.withOpacity(0.1),
              radius: 24,
              child: Icon(icon, color: color),
            ),
            const SizedBox(height: 6),
            Text(label, style: const TextStyle(fontSize: 11)),
          ],
        ),
      ),
    );
  }
}

class _RespuestaCard extends StatelessWidget {
  final Map<String, dynamic> item;

  const _RespuestaCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final autorRespuesta =
        item['autor'] ?? item['author'] ?? item['usuario'] ?? 'Usuario';
    final contenidoRespuesta =
        item['contenido'] ?? item['content'] ?? item['texto'] ?? '';
    final fechaRespuesta = item['fecha'] ?? item['created_at'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outlineVariant,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              FlavorInitialsAvatar(
                name: autorRespuesta,
                radius: 14,
                textStyle: const TextStyle(fontSize: 12),
              ),
              const SizedBox(width: 8),
              Text(
                autorRespuesta,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              Text(
                fechaRespuesta,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(contenidoRespuesta),
        ],
      ),
    );
  }
}
