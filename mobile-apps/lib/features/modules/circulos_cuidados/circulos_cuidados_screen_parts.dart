part of 'circulos_cuidados_screen.dart';

extension _CirculosCuidadosScreenParts on _CirculosCuidadosScreenState {
  void _crearCirculo() {
    final i18n = AppLocalizations.of(context);
    final nombreController = TextEditingController();
    final descripcionController = TextEditingController();
    String tipoSeleccionado = 'general';

    final tipos = [
      {'id': 'general', 'nombre': i18n.circulosCuidadosTypeGeneral},
      {'id': 'infancia', 'nombre': i18n.circulosCuidadosTypeInfancia},
      {'id': 'mayores', 'nombre': i18n.circulosCuidadosTypeMayores},
      {'id': 'salud', 'nombre': i18n.circulosCuidadosTypeSalud},
      {'id': 'emocional', 'nombre': i18n.circulosCuidadosTypeEmocional},
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 20,
            right: 20,
            top: 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.favorite, color: Colors.pink.shade400),
                    const SizedBox(width: 12),
                    Text(
                      i18n.circulosCuidadosCreateTitle,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nombreController,
                  decoration: InputDecoration(
                    labelText: i18n.circulosCuidadosNameLabel,
                    prefixIcon: const Icon(Icons.group),
                    border: const OutlineInputBorder(),
                    hintText: i18n.circulosCuidadosNameHint,
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: tipoSeleccionado,
                  decoration: InputDecoration(
                    labelText: i18n.circulosCuidadosTypeLabel,
                    prefixIcon: const Icon(Icons.category),
                    border: const OutlineInputBorder(),
                  ),
                  items: tipos.map((tipo) {
                    return DropdownMenuItem<String>(
                      value: tipo['id'],
                      child: Text(tipo['nombre']!),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setModalState(() => tipoSeleccionado = value);
                    }
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: InputDecoration(
                    labelText: i18n.circulosCuidadosDescriptionLabel,
                    prefixIcon: const Icon(Icons.description),
                    border: const OutlineInputBorder(),
                    hintText: i18n.circulosCuidadosDescriptionHint,
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      if (nombreController.text.isEmpty) {
                        FlavorSnackbar.showError(context, i18n.circulosCuidadosNameRequired);
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarNuevoCirculo(
                        nombre: nombreController.text,
                        tipo: tipoSeleccionado,
                        descripcion: descripcionController.text,
                      );
                    },
                    icon: const Icon(Icons.add),
                    label: Text(i18n.circulosCuidadosCreateButton),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _enviarNuevoCirculo({
    required String nombre,
    required String tipo,
    required String descripcion,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/circulos-cuidados/crear', data: {
        'nombre': nombre,
        'tipo': tipo,
        'descripcion': descripcion,
      });

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(
              context, AppLocalizations.of(context).circulosCuidadosCreatedSuccess);
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al crear');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, AppLocalizations.of(context).commonError(e.toString()));
      }
    }
  }

  void _unirseCirculo(Map<String, dynamic> circulo) async {
    final i18n = AppLocalizations.of(context);
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.circulosCuidadosJoinTitle),
        content: Text(i18n.circulosCuidadosJoinConfirm(circulo['nombre']?.toString() ?? '')),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.circulosCuidadosJoinSend),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/circulos-cuidados/unirse', data: {
        'circulo_id': circulo['id'],
      });

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(
              context,
              AppLocalizations.of(context)
                  .circulosCuidadosJoinSent(circulo['nombre']?.toString() ?? ''));
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al unirse');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, AppLocalizations.of(context).commonError(e.toString()));
      }
    }
  }

  void _verCirculo(Map<String, dynamic> circulo) {
    final i18n = AppLocalizations.of(context);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: Colors.pink.shade100,
                    child: Icon(Icons.favorite, size: 28, color: Colors.pink.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          circulo['nombre'] ?? '',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        if ((circulo['tipo'] ?? '').isNotEmpty)
                          Text(
                            circulo['tipo'],
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if ((circulo['descripcion'] ?? '').isNotEmpty) ...[
                Text(circulo['descripcion']),
                const SizedBox(height: 20),
              ],
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildStatColumn(
                        icon: Icons.group,
                        value: '${circulo['total_miembros'] ?? 0}',
                        label: i18n.circulosCuidadosStatMembers,
                      ),
                      _buildStatColumn(
                        icon: Icons.event,
                        value: '${circulo['total_reuniones'] ?? 0}',
                        label: i18n.circulosCuidadosStatMeetings,
                      ),
                      _buildStatColumn(
                        icon: Icons.volunteer_activism,
                        value: '${circulo['ayudas_prestadas'] ?? 0}',
                        label: i18n.circulosCuidadosStatHelp,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              if ((circulo['proxima_reunion'] ?? '').isNotEmpty) ...[
                Text(
                  i18n.circulosCuidadosNextMeeting,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Card(
                  child: ListTile(
                    leading: Icon(Icons.event, color: Colors.pink.shade400),
                    title: Text(circulo['proxima_reunion']),
                    subtitle: Text(circulo['lugar_reunion'] ?? i18n.circulosCuidadosMeetingPlaceTbd),
                  ),
                ),
                const SizedBox(height: 20),
              ],
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _abrirChatCirculo(circulo);
                      },
                      icon: const Icon(Icons.chat),
                      label: Text(i18n.circulosCuidadosChat),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _ofrecerAyuda(circulo);
                      },
                      icon: const Icon(Icons.volunteer_activism),
                      label: Text(i18n.circulosCuidadosOfferHelp),
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

  void _abrirChatCirculo(Map<String, dynamic> circulo) {
    final circuloId = circulo['id'];
    final nombre = circulo['nombre'] ?? AppLocalizations.of(context).circulosCuidadosChatDefaultTitle;

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _ChatCirculoScreen(
          circuloId: circuloId,
          nombreCirculo: nombre,
        ),
      ),
    );
  }

  void _ofrecerAyuda(Map<String, dynamic> circulo) {
    final i18n = AppLocalizations.of(context);
    final descripcionController = TextEditingController();
    String tipoAyuda = 'general';

    final tiposAyuda = [
      {'id': 'general', 'nombre': i18n.circulosCuidadosHelpTypeGeneral},
      {'id': 'cuidado_ninos', 'nombre': i18n.circulosCuidadosHelpTypeNinos},
      {'id': 'cuidado_mayores', 'nombre': i18n.circulosCuidadosHelpTypeMayores},
      {'id': 'compras', 'nombre': i18n.circulosCuidadosHelpTypeCompras},
      {'id': 'transporte', 'nombre': i18n.circulosCuidadosHelpTypeTransporte},
      {'id': 'compania', 'nombre': i18n.circulosCuidadosHelpTypeCompania},
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 20,
            right: 20,
            top: 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.volunteer_activism, color: Colors.pink.shade400),
                    const SizedBox(width: 12),
                    Text(
                      i18n.circulosCuidadosOfferHelpTitle,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  i18n.circulosCuidadosOfferCircleLabel(circulo['nombre']?.toString() ?? ''),
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),
                DropdownButtonFormField<String>(
                  value: tipoAyuda,
                  decoration: InputDecoration(
                    labelText: i18n.circulosCuidadosHelpTypeLabel,
                    prefixIcon: const Icon(Icons.category),
                    border: const OutlineInputBorder(),
                  ),
                  items: tiposAyuda.map((tipo) {
                    return DropdownMenuItem<String>(
                      value: tipo['id'],
                      child: Text(tipo['nombre']!),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setModalState(() => tipoAyuda = value);
                    }
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: InputDecoration(
                    labelText: i18n.circulosCuidadosOfferDescriptionLabel,
                    prefixIcon: const Icon(Icons.description),
                    border: const OutlineInputBorder(),
                    hintText: i18n.circulosCuidadosOfferDescriptionHint,
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      if (descripcionController.text.trim().isEmpty) {
                        FlavorSnackbar.showError(
                            context, i18n.circulosCuidadosOfferDescriptionRequired);
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarOfertaAyuda(
                        circuloId: circulo['id'],
                        tipo: tipoAyuda,
                        descripcion: descripcionController.text.trim(),
                      );
                    },
                    icon: const Icon(Icons.send),
                    label: Text(i18n.circulosCuidadosOfferSend),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _enviarOfertaAyuda({
    required dynamic circuloId,
    required String tipo,
    required String descripcion,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/circulos-cuidados/$circuloId/ofrecer-ayuda', data: {
        'tipo': tipo,
        'descripcion': descripcion,
      });

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(
              context, AppLocalizations.of(context).circulosCuidadosOfferSent);
        }
      } else {
        throw Exception(response.error ?? 'Error al enviar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, AppLocalizations.of(context).commonError(e.toString()));
      }
    }
  }
}

class _ChatCirculoScreen extends ConsumerStatefulWidget {
  final dynamic circuloId;
  final String nombreCirculo;

  const _ChatCirculoScreen({
    required this.circuloId,
    required this.nombreCirculo,
  });

  @override
  ConsumerState<_ChatCirculoScreen> createState() => _ChatCirculoScreenState();
}

class _ChatCirculoScreenState extends ConsumerState<_ChatCirculoScreen> {
  List<Map<String, dynamic>> _mensajes = [];
  bool _cargando = true;
  bool _enviando = false;
  final TextEditingController _mensajeController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _cargarMensajes();
  }

  @override
  void dispose() {
    _mensajeController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _cargarMensajes() async {
    setState(() => _cargando = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/circulos-cuidados/${widget.circuloId}/mensajes');
      if (response.success && response.data != null) {
        setState(() {
          _mensajes = (response.data!['mensajes'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _cargando = false;
        });
        _scrollAlFinal();
      } else {
        setState(() => _cargando = false);
      }
    } catch (e) {
      setState(() => _cargando = false);
    }
  }

  void _scrollAlFinal() {
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

  Future<void> _enviarMensaje() async {
    final texto = _mensajeController.text.trim();
    if (texto.isEmpty || _enviando) return;

    setState(() => _enviando = true);
    _mensajeController.clear();

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post(
        '/circulos-cuidados/${widget.circuloId}/mensajes',
        data: {'mensaje': texto},
      );

      if (response.success) {
        _cargarMensajes();
      } else {
        if (mounted) {
          FlavorSnackbar.showError(context, response.error ?? 'Error al enviar');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, AppLocalizations.of(context).commonError(e.toString()));
      }
    } finally {
      if (mounted) {
        setState(() => _enviando = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.nombreCirculo),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarMensajes,
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _cargando
                ? const FlavorLoadingState()
                : _mensajes.isEmpty
                    ? FlavorEmptyState(
                        icon: Icons.chat_bubble_outline,
                        title: i18n.circulosCuidadosChatEmptyTitle,
                        message: i18n.circulosCuidadosChatEmptyMessage,
                      )
                    : RefreshIndicator(
                        onRefresh: _cargarMensajes,
                        child: ListView.builder(
                          controller: _scrollController,
                          padding: const EdgeInsets.all(16),
                          itemCount: _mensajes.length,
                          itemBuilder: (context, index) {
                            final mensaje = _mensajes[index];
                            final texto = mensaje['mensaje'] ?? '';
                            final autor = mensaje['autor'] ?? i18n.circulosCuidadosChatDefaultAuthor;
                            final fecha = mensaje['fecha'] ?? '';
                            final esMio = mensaje['es_mio'] == true;

                            return Align(
                              alignment: esMio ? Alignment.centerRight : Alignment.centerLeft,
                              child: Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                padding: const EdgeInsets.all(12),
                                constraints: BoxConstraints(
                                  maxWidth: MediaQuery.of(context).size.width * 0.75,
                                ),
                                decoration: BoxDecoration(
                                  color: esMio ? Colors.pink.shade100 : Colors.grey.shade200,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    if (!esMio)
                                      Text(
                                        autor,
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 12,
                                          color: Colors.pink.shade700,
                                        ),
                                      ),
                                    Text(texto),
                                    Text(
                                      fecha,
                                      style: TextStyle(
                                        fontSize: 10,
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                      ),
          ),
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
                      controller: _mensajeController,
                      decoration: InputDecoration(
                        hintText: i18n.circulosCuidadosChatInputHint,
                        border: const OutlineInputBorder(),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _enviarMensaje(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _enviando ? null : _enviarMensaje,
                    icon: _enviando ? const FlavorInlineSpinner() : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
