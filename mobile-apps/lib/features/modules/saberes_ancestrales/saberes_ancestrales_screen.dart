import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';

class SaberesAncestralesScreen extends ConsumerStatefulWidget {
  const SaberesAncestralesScreen({super.key});

  @override
  ConsumerState<SaberesAncestralesScreen> createState() => _SaberesAncestralesScreenState();
}

class _SaberesAncestralesScreenState extends ConsumerState<SaberesAncestralesScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _saberes = [];
  List<Map<String, dynamic>> _talleres = [];
  String _categoriaSeleccionada = 'todos';

  final _categorias = ['todos', 'medicina', 'agricultura', 'artesania', 'rituales', 'gastronomia'];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/saberes-ancestrales/catalogo', queryParameters: {
        if (_categoriaSeleccionada != 'todos') 'categoria': _categoriaSeleccionada,
      });
      if (response.success && response.data != null) {
        setState(() {
          _saberes = (response.data!['saberes'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Saberes Ancestrales')),
      body: Column(
        children: [
          _buildCategoriaFilter(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadData,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (_talleres.isNotEmpty) ...[
                            _buildTalleresSection(),
                            const SizedBox(height: 24),
                          ],
                          _buildSaberesSection(),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _compartirSaber,
        icon: const Icon(Icons.add),
        label: const Text('Compartir saber'),
      ),
    );
  }

  Widget _buildCategoriaFilter() {
    return Container(
      height: 50,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _categorias.length,
        itemBuilder: (context, index) {
          final cat = _categorias[index];
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(cat[0].toUpperCase() + cat.substring(1)),
              selected: _categoriaSeleccionada == cat,
              onSelected: (_) {
                setState(() => _categoriaSeleccionada = cat);
                _loadData();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildTalleresSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Talleres proximos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        SizedBox(
          height: 160,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: _talleres.length,
            itemBuilder: (context, index) => _buildTallerCard(_talleres[index]),
          ),
        ),
      ],
    );
  }

  Widget _buildTallerCard(Map<String, dynamic> taller) {
    return Card(
      margin: const EdgeInsets.only(right: 12),
      clipBehavior: Clip.antiAlias,
      child: Container(
        width: 200,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              height: 80,
              color: Colors.brown.shade100,
              child: Center(child: Icon(Icons.auto_stories, size: 40, color: Colors.brown.shade400)),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(taller['titulo'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Text(taller['fecha'] ?? '', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSaberesSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Saberes documentados', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_saberes.isEmpty)
          Center(
            child: Column(
              children: [
                Icon(Icons.auto_stories, size: 64, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                const Text('No hay saberes en esta categoria'),
              ],
            ),
          )
        else
          ..._saberes.map((s) => _buildSaberCard(s)),
      ],
    );
  }

  Widget _buildSaberCard(Map<String, dynamic> saber) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.brown.shade100,
          child: Icon(Icons.elderly, color: Colors.brown.shade600),
        ),
        title: Text(saber['titulo'] ?? ''),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(saber['portador'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
            if ((saber['categoria'] ?? '').isNotEmpty)
              Chip(
                label: Text(saber['categoria']),
                labelStyle: const TextStyle(fontSize: 10),
                padding: EdgeInsets.zero,
                visualDensity: VisualDensity.compact,
              ),
          ],
        ),
        isThreeLine: true,
        onTap: () => _verDetalle(saber),
      ),
    );
  }

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
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('El titulo es obligatorio')),
                        );
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Saber guardado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al guardar');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
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
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Enlace copiado al portapapeles'),
                      backgroundColor: Colors.green,
                    ),
                  );
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
                final subject = Uri.encodeComponent('Saber ancestral: $titulo');
                final body = Uri.encodeComponent(
                  'Te comparto este saber ancestral:\n\n'
                  'Título: $titulo\n'
                  '${categoria.isNotEmpty ? 'Categoría: $categoria\n' : ''}'
                  '${portador.isNotEmpty ? 'Portador: $portador\n' : ''}',
                );
                final uri = Uri.parse('mailto:?subject=$subject&body=$body');
                if (await canLaunchUrl(uri)) {
                  await launchUrl(uri);
                } else if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('No se puede abrir el cliente de email'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
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
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Compartido en la comunidad: $titulo'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al compartir'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Mensaje enviado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al enviar mensaje'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
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
              decoration: InputDecoration(
                labelText: 'Tu mensaje',
                prefixIcon: const Icon(Icons.message),
                border: const OutlineInputBorder(),
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
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Escribe un mensaje')),
                    );
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Mensaje enviado al portador. Te contactara pronto.'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al enviar mensaje'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
