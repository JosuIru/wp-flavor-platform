import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider, currentUserIdProvider;
import '../../../core/crypto/crypto.dart';

class ChatInternoScreen extends ConsumerStatefulWidget {
  const ChatInternoScreen({super.key});

  @override
  ConsumerState<ChatInternoScreen> createState() => _ChatInternoScreenState();
}

class _ChatInternoScreenState extends ConsumerState<ChatInternoScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  bool _e2eInitialized = false;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getChatInternoConversaciones();
    _initializeE2E();
  }

  Future<void> _initializeE2E() async {
    final userId = ref.read(currentUserIdProvider);
    if (userId != null && userId > 0) {
      final e2eNotifier = ref.read(e2eNotifierProvider(userId).notifier);
      await e2eNotifier.initialize();
      if (mounted) {
        setState(() {
          _e2eInitialized = true;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final userId = ref.watch(currentUserIdProvider);
    final e2eState = userId != null && userId > 0
        ? ref.watch(e2eNotifierProvider(userId))
        : null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Chat Interno'),
        actions: [
          // Indicador E2E
          if (e2eState != null)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: Icon(
                e2eState.isEnabled && e2eState.hasKeys
                    ? Icons.lock
                    : Icons.lock_open,
                color: e2eState.isEnabled && e2eState.hasKeys
                    ? Colors.green
                    : Colors.grey,
                size: 20,
              ),
            ),
        ],
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'Error al cargar conversaciones'));
          }
          final convs = (res.data!['conversaciones'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (convs.isEmpty) {
            return const Center(child: Text('No hay conversaciones'));
          }
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: convs.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final c = convs[index];
              final id = (c['id'] as num?)?.toInt() ?? 0;
              final otro = (c['con_usuario'] as Map?) ?? {};
              final otroUserId = (otro['id'] as num?)?.toInt() ?? 0;
              final nombre = otro['nombre']?.toString() ?? 'Usuario';
              final preview = c['ultimo_mensaje']?.toString() ?? '';
              final cifrado = c['cifrado'] == true || c['cifrado'] == 1;

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: const Icon(Icons.person),
                  title: Row(
                    children: [
                      Expanded(child: Text(nombre)),
                      if (cifrado)
                        const Padding(
                          padding: EdgeInsets.only(left: 4),
                          child: Icon(Icons.lock, size: 14, color: Colors.green),
                        ),
                    ],
                  ),
                  subtitle: Text(
                    cifrado ? '[Mensaje cifrado]' : preview,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => ChatInternoMensajesScreen(
                          conversacionId: id,
                          otroUsuarioId: otroUserId,
                          titulo: nombre,
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ChatInternoMensajesScreen extends ConsumerStatefulWidget {
  final int conversacionId;
  final int otroUsuarioId;
  final String titulo;

  const ChatInternoMensajesScreen({
    super.key,
    required this.conversacionId,
    required this.otroUsuarioId,
    required this.titulo,
  });

  @override
  ConsumerState<ChatInternoMensajesScreen> createState() => _ChatInternoMensajesScreenState();
}

class _ChatInternoMensajesScreenState extends ConsumerState<ChatInternoMensajesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _sending = false;
  List<Map<String, dynamic>> _mensajesDescifrados = [];

  @override
  void initState() {
    super.initState();
    _loadMessages();
  }

  void _loadMessages() {
    _future = ref.read(apiClientProvider).getChatInternoMensajes(
      conversacionId: widget.conversacionId,
    );
    _future.then((res) {
      if (res.success && res.data != null) {
        _decryptMessages(res.data!['mensajes'] as List<dynamic>? ?? []);
      }
    });
  }

  Future<void> _decryptMessages(List<dynamic> mensajes) async {
    final userId = ref.read(currentUserIdProvider);
    if (userId == null) return;

    final e2eNotifier = ref.read(e2eNotifierProvider(userId).notifier);
    final decrypted = <Map<String, dynamic>>[];

    for (final m in mensajes.whereType<Map<String, dynamic>>()) {
      final cifrado = m['cifrado'] == true || m['cifrado'] == 1;
      String texto;

      if (cifrado && m['ciphertext'] != null) {
        // Intentar descifrar
        final senderId = (m['remitente_id'] as num?)?.toInt() ?? 0;
        try {
          final encryptedData = jsonDecode(m['ciphertext'] as String) as Map<String, dynamic>;
          final descifrado = await e2eNotifier.decryptMessageFromJson(senderId, encryptedData);
          texto = descifrado ?? '[No se pudo descifrar]';
        } catch (e) {
          texto = '[Error al descifrar]';
        }
      } else {
        texto = m['mensaje']?.toString() ?? '';
      }

      decrypted.add({
        ...m,
        'texto_descifrado': texto,
      });
    }

    if (mounted) {
      setState(() {
        _mensajesDescifrados = decrypted;
      });
      _scrollToBottom();
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _enviar() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _sending) return;

    setState(() => _sending = true);
    _controller.clear();

    try {
      final userId = ref.read(currentUserIdProvider);
      final e2eState = userId != null ? ref.read(e2eNotifierProvider(userId)) : null;

      if (e2eState != null && e2eState.isEnabled && e2eState.hasKeys) {
        // Enviar cifrado
        await _enviarCifrado(text, userId!);
      } else {
        // Enviar sin cifrar (fallback)
        await ref.read(apiClientProvider).sendChatInternoMensaje(
          conversacionId: widget.conversacionId,
          mensaje: text,
        );
      }

      _loadMessages();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al enviar: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<void> _enviarCifrado(String plaintext, int userId) async {
    final e2eNotifier = ref.read(e2eNotifierProvider(userId).notifier);
    final encrypted = await e2eNotifier.encryptMessage(widget.otroUsuarioId, plaintext);

    if (encrypted == null) {
      // Fallback a texto plano
      await ref.read(apiClientProvider).sendChatInternoMensaje(
        conversacionId: widget.conversacionId,
        mensaje: plaintext,
      );
      return;
    }

    // Enviar mensaje cifrado
    await ref.read(apiClientProvider).sendChatInternoMensajeCifrado(
      conversacionId: widget.conversacionId,
      ciphertext: jsonEncode(encrypted.toJson()),
      e2eHeader: jsonEncode(encrypted.header.toJson()),
      e2eVersion: 1,
    );
  }

  @override
  Widget build(BuildContext context) {
    final userId = ref.watch(currentUserIdProvider);
    final e2eState = userId != null ? ref.watch(e2eNotifierProvider(userId)) : null;
    final isE2EActive = e2eState?.isEnabled == true && e2eState?.hasKeys == true;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Expanded(child: Text(widget.titulo)),
            if (isE2EActive)
              const Padding(
                padding: EdgeInsets.only(left: 4),
                child: Icon(Icons.lock, size: 16, color: Colors.green),
              ),
          ],
        ),
        actions: [
          if (isE2EActive)
            IconButton(
              icon: const Icon(Icons.verified_user),
              tooltip: 'Verificar identidad',
              onPressed: () => _showVerificationDialog(context),
            ),
        ],
      ),
      body: Column(
        children: [
          // Banner E2E
          if (isE2EActive)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              color: Colors.green.withOpacity(0.1),
              child: Row(
                children: [
                  const Icon(Icons.lock, size: 14, color: Colors.green),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'Cifrado de extremo a extremo activado',
                      style: TextStyle(fontSize: 12, color: Colors.green),
                    ),
                  ),
                ],
              ),
            ),

          // Lista de mensajes
          Expanded(
            child: _mensajesDescifrados.isEmpty
                ? FutureBuilder<ApiResponse<Map<String, dynamic>>>(
                    future: _future,
                    builder: (context, snapshot) {
                      if (!snapshot.hasData) {
                        return const Center(child: CircularProgressIndicator());
                      }
                      final res = snapshot.data!;
                      if (!res.success || res.data == null) {
                        return Center(child: Text(res.error ?? 'Error al cargar mensajes'));
                      }
                      return const Center(child: Text('No hay mensajes'));
                    },
                  )
                : ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.all(16),
                    itemCount: _mensajesDescifrados.length,
                    itemBuilder: (context, index) {
                      final m = _mensajesDescifrados[index];
                      final texto = m['texto_descifrado']?.toString() ?? '';
                      final autor = m['remitente_nombre']?.toString() ?? '';
                      final esMio = m['es_mio'] == true;
                      final cifrado = m['cifrado'] == true || m['cifrado'] == 1;

                      return Align(
                        alignment: esMio ? Alignment.centerRight : Alignment.centerLeft,
                        child: Container(
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          padding: const EdgeInsets.all(12),
                          constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width * 0.75,
                          ),
                          decoration: BoxDecoration(
                            color: esMio
                                ? Theme.of(context).colorScheme.primary.withOpacity(0.15)
                                : Theme.of(context).colorScheme.surfaceContainerHighest,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (!esMio)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 4),
                                  child: Text(
                                    autor,
                                    style: Theme.of(context).textTheme.labelSmall,
                                  ),
                                ),
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Flexible(child: Text(texto)),
                                  if (cifrado)
                                    const Padding(
                                      padding: EdgeInsets.only(left: 4),
                                      child: Icon(
                                        Icons.lock,
                                        size: 12,
                                        color: Colors.green,
                                      ),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),

          // Campo de entrada
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 4,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _controller,
                      decoration: InputDecoration(
                        hintText: isE2EActive
                            ? 'Mensaje cifrado...'
                            : 'Escribe un mensaje',
                        border: const OutlineInputBorder(),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        prefixIcon: isE2EActive
                            ? const Icon(Icons.lock, size: 18, color: Colors.green)
                            : null,
                      ),
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _enviar(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _sending ? null : _enviar,
                    icon: _sending
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _showVerificationDialog(BuildContext context) async {
    final userId = ref.read(currentUserIdProvider);
    if (userId == null) return;

    final e2eNotifier = ref.read(e2eNotifierProvider(userId).notifier);
    final localFingerprint = await e2eNotifier.getLocalFingerprint();
    final remoteFingerprint = await e2eNotifier.getRemoteFingerprint(widget.otroUsuarioId);

    if (!mounted) return;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Verificar identidad'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Compara estos codigos con la otra persona para verificar que la conexion es segura:',
              style: TextStyle(fontSize: 12),
            ),
            const SizedBox(height: 16),
            const Text('Tu codigo:', style: TextStyle(fontWeight: FontWeight.bold)),
            Text(
              localFingerprint ?? 'No disponible',
              style: const TextStyle(fontFamily: 'monospace', fontSize: 12),
            ),
            const SizedBox(height: 12),
            Text('Codigo de ${widget.titulo}:',
                style: const TextStyle(fontWeight: FontWeight.bold)),
            Text(
              remoteFingerprint ?? 'No disponible',
              style: const TextStyle(fontFamily: 'monospace', fontSize: 12),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
          if (remoteFingerprint != null)
            FilledButton.icon(
              onPressed: () async {
                Navigator.pop(context);
                await _marcarIdentidadVerificada(remoteFingerprint);
              },
              icon: const Icon(Icons.check),
              label: const Text('Verificado'),
            ),
        ],
      ),
    );
  }

  Future<void> _marcarIdentidadVerificada(String fingerprint) async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/e2e/identidad/verificar', data: {
        'usuario_id': widget.otroUsuarioId,
        'fingerprint': fingerprint,
      });

      if (!mounted) return;

      if (response.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Row(
              children: [
                Icon(Icons.verified_user, color: Colors.white, size: 18),
                SizedBox(width: 8),
                Text('Identidad verificada correctamente'),
              ],
            ),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al verificar identidad'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }
}
