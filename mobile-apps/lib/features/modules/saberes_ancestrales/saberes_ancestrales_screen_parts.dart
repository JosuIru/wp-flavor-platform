part of 'saberes_ancestrales_screen.dart';

extension _SaberesAncestralesScreenParts on _SaberesAncestralesScreenState {
  void _compartirSaber() {
    final tituloController = TextEditingController();
    final descripcionController = TextEditingController();
    final portadorController = TextEditingController();
    String categoriaSeleccionada = 'medicina';

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
                    Icon(Icons.auto_stories, color: Colors.brown.shade600),
                    const SizedBox(width: 12),
                    const Text(
                      'Compartir un Saber',
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
                Text(
                  'Documenta y preserva el conocimiento ancestral de tu comunidad.',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: tituloController,
                  decoration: const InputDecoration(
                    labelText: 'Titulo del saber',
                    prefixIcon: Icon(Icons.title),
                    border: OutlineInputBorder(),
                    hintText: 'Ej: Preparacion de unguentos naturales',
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: categoriaSeleccionada,
                  decoration: const InputDecoration(
                    labelText: 'Categoria',
                    prefixIcon: Icon(Icons.category),
                    border: OutlineInputBorder(),
                  ),
                  items: _categorias.where((c) => c != 'todos').map((cat) {
                    return DropdownMenuItem<String>(
                      value: cat,
                      child: Text(cat[0].toUpperCase() + cat.substring(1)),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setModalState(() => categoriaSeleccionada = value);
                    }
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: portadorController,
                  decoration: const InputDecoration(
                    labelText: 'Portador/a del saber',
                    prefixIcon: Icon(Icons.elderly),
                    border: OutlineInputBorder(),
                    hintText: 'Nombre de quien transmite este conocimiento',
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: const InputDecoration(
                    labelText: 'Descripcion del saber',
                    prefixIcon: Icon(Icons.description),
                    border: OutlineInputBorder(),
                    hintText: 'Describe el conocimiento, su origen y uso...',
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(backgroundColor: Colors.brown),
                    onPressed: () async {
                      if (tituloController.text.isEmpty) {
                        FlavorSnackbar.showError(context, 'El titulo es obligatorio');
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarSaber(
                        titulo: tituloController.text,
                        categoria: categoriaSeleccionada,
                        portador: portadorController.text,
                        descripcion: descripcionController.text,
                      );
                    },
                    icon: const Icon(Icons.save),
                    label: const Text('Guardar saber'),
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

  Future<void> _enviarSaber({
    required String titulo,
    required String categoria,
    required String portador,
    required String descripcion,
  }) async {
    final api = ref.read(apiClientProvider);
    try {
      final response = await api.post('/saberes-ancestrales/crear', data: {
        'titulo': titulo,
        'categoria': categoria,
        'portador': portador,
        'descripcion': descripcion,
      });
      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Saber guardado correctamente');
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al guardar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _verDetalle(Map<String, dynamic> saber) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.75,
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
                    backgroundColor: Colors.brown.shade100,
                    child: Icon(Icons.auto_stories, color: Colors.brown.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          saber['titulo'] ?? '',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        if ((saber['categoria'] ?? '').isNotEmpty)
                          Container(
                            margin: const EdgeInsets.only(top: 4),
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.brown.shade100,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              saber['categoria'],
                              style: TextStyle(fontSize: 12, color: Colors.brown.shade700),
                            ),
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
              if ((saber['portador'] ?? '').isNotEmpty) ...[
                Card(
                  color: Colors.brown.shade50,
                  child: ListTile(
                    leading: Icon(Icons.elderly, color: Colors.brown.shade600),
                    title: const Text('Portador/a del saber'),
                    subtitle: Text(
                      saber['portador'],
                      style: const TextStyle(fontWeight: FontWeight.w500),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],
              if ((saber['descripcion'] ?? '').isNotEmpty) ...[
                const Text(
                  'Descripcion',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(saber['descripcion']),
                const SizedBox(height: 20),
              ],
              if ((saber['origen'] ?? '').isNotEmpty) ...[
                const Text(
                  'Origen',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(saber['origen']),
                const SizedBox(height: 20),
              ],
              if ((saber['aplicaciones'] ?? '').isNotEmpty) ...[
                const Text(
                  'Aplicaciones',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(saber['aplicaciones']),
                const SizedBox(height: 20),
              ],
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _compartirSaberContenido(saber);
                      },
                      icon: const Icon(Icons.share),
                      label: const Text('Compartir'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: Colors.brown),
                      onPressed: () {
                        Navigator.pop(context);
                        _contactarPortador(saber);
                      },
                      icon: const Icon(Icons.message),
                      label: const Text('Contactar'),
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

  void _compartirSaberContenido(Map<String, dynamic> saber) {
    final titulo = saber['titulo'] ?? 'Saber ancestral';
    final categoria = saber['categoria'] ?? '';
    final portador = saber['portador'] ?? '';

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
                Icon(Icons.share, color: Colors.brown.shade600),
                const SizedBox(width: 12),
                const Text(
                  'Compartir saber',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.copy),
              title: const Text('Copiar enlace'),
              onTap: () async {
                final saberId = saber['id']?.toString() ?? '';
                final enlace = 'https://ejemplo.com/saberes-ancestrales/$saberId';
                await Clipboard.setData(ClipboardData(text: enlace));
                if (context.mounted) {
                  Navigator.pop(context);
                  FlavorSnackbar.showSuccess(context, 'Enlace copiado al portapapeles');
                }
              },
            ),
            ListTile(
              leading: const Icon(Icons.chat),
              title: const Text('Compartir en chat interno'),
              onTap: () {
                Navigator.pop(context);
                _compartirEnChatInterno(titulo, categoria);
              },
            ),
            ListTile(
              leading: const Icon(Icons.email),
              title: const Text('Enviar por email'),
              onTap: () async {
                Navigator.pop(context);
                final subject = 'Saber ancestral: $titulo';
                final body =
                    'Te comparto este saber ancestral:\n\n'
                    'Título: $titulo\n'
                    '${categoria.isNotEmpty ? 'Categoría: $categoria\n' : ''}'
                    '${portador.isNotEmpty ? 'Portador: $portador\n' : ''}';
                if (!context.mounted) return;
                await FlavorContactLauncher.email(
                  context,
                  '',
                  subject: subject,
                  body: body,
                  errorMessage: 'No se puede abrir el cliente de email',
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.groups),
              title: const Text('Compartir en comunidad'),
              onTap: () async {
                Navigator.pop(context);
                await _compartirEnComunidad(saber);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Future<void> _compartirEnComunidad(Map<String, dynamic> saber) async {
    final api = ref.read(apiClientProvider);
    final saberId = saber['id'];
    final titulo = saber['titulo'] ?? 'Saber ancestral';

    try {
      final response = await api.post('/saberes-ancestrales/$saberId/compartir-comunidad', data: {
        'tipo': 'saber_ancestral',
      });
      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Compartido en la comunidad: $titulo');
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al compartir');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _compartirEnChatInterno(String titulo, String categoria) {
    final mensajeController = TextEditingController(
      text: 'Te comparto este saber ancestral: "$titulo" (Categoria: $categoria)',
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
          left: 20,
          right: 20,
          top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.chat, color: Colors.brown.shade600),
                const SizedBox(width: 12),
                const Text(
                  'Compartir en chat',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: mensajeController,
              decoration: const InputDecoration(
                labelText: 'Mensaje',
                prefixIcon: Icon(Icons.message),
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: Colors.brown),
                onPressed: () async {
                  Navigator.pop(context);
                  await _enviarMensajeChat(mensajeController.text);
                },
                icon: const Icon(Icons.send),
                label: const Text('Enviar mensaje'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Future<void> _enviarMensajeChat(String mensaje) async {
    final api = ref.read(apiClientProvider);
    try {
      final response = await api.post('/chat-interno/enviar', data: {
        'mensaje': mensaje,
        'tipo': 'compartir_saber',
      });
      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Mensaje enviado correctamente');
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al enviar mensaje');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _contactarPortador(Map<String, dynamic> saber) {
    final portador = saber['portador'] ?? 'el portador';
    final titulo = saber['titulo'] ?? 'Saber ancestral';
    final mensajeController = TextEditingController(
      text: 'Hola, me interesa aprender mas sobre "$titulo".',
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
          left: 20,
          right: 20,
          top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.brown.shade100,
                  child: Icon(Icons.elderly, color: Colors.brown.shade600),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Contactar portador',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        portador,
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
            const SizedBox(height: 8),
            Text(
              'Enviale un mensaje para aprender mas sobre este saber.',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: mensajeController,
              decoration: const InputDecoration(
                labelText: 'Tu mensaje',
                prefixIcon: Icon(Icons.message),
                border: OutlineInputBorder(),
                hintText: 'Escribe tu consulta o interes...',
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: Colors.brown),
                onPressed: () async {
                  if (mensajeController.text.isEmpty) {
                    FlavorSnackbar.showError(context, 'Escribe un mensaje');
                    return;
                  }
                  Navigator.pop(context);
                  await _enviarContactoPortador(saber, mensajeController.text);
                },
                icon: const Icon(Icons.send),
                label: const Text('Enviar mensaje'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Future<void> _enviarContactoPortador(Map<String, dynamic> saber, String mensaje) async {
    final api = ref.read(apiClientProvider);
    final saberId = saber['id'];
    try {
      final response = await api.post('/saberes-ancestrales/$saberId/contactar', data: {
        'mensaje': mensaje,
      });
      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Mensaje enviado al portador. Te contactara pronto.');
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al enviar mensaje');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }
}
