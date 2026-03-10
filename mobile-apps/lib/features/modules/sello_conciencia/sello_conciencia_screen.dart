import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';

class SelloConcienciaScreen extends ConsumerStatefulWidget {
  const SelloConcienciaScreen({super.key});

  @override
  ConsumerState<SelloConcienciaScreen> createState() => _SelloConcienciaScreenState();
}

class _SelloConcienciaScreenState extends ConsumerState<SelloConcienciaScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _negocios = [];
  List<Map<String, dynamic>> _criterios = [];
  Map<String, dynamic>? _miSolicitud;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/sello-conciencia/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _negocios = (response.data!['negocios_certificados'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _criterios = (response.data!['criterios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _miSolicitud = response.data!['mi_solicitud'];
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sello Conciencia'),
        actions: [
          IconButton(icon: const Icon(Icons.info_outline), onPressed: _mostrarInfo),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildBanner(),
                    if (_miSolicitud != null) ...[
                      const SizedBox(height: 16),
                      _buildMiSolicitud(),
                    ],
                    const SizedBox(height: 24),
                    _buildNegociosCertificados(),
                    const SizedBox(height: 24),
                    _buildCriterios(),
                  ],
                ),
              ),
            ),
      floatingActionButton: _miSolicitud == null
          ? FloatingActionButton.extended(
              onPressed: _solicitarSello,
              icon: const Icon(Icons.verified),
              label: const Text('Solicitar sello'),
            )
          : null,
    );
  }

  Widget _buildBanner() {
    return Card(
      color: Colors.green.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.green.shade100, shape: BoxShape.circle),
              child: Icon(Icons.verified, size: 32, color: Colors.green.shade700),
            ),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Sello Conciencia', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text('Certificacion para negocios sostenibles y responsables'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMiSolicitud() {
    final estado = _miSolicitud!['estado'] ?? '';
    final Color estadoColor;
    switch (estado.toLowerCase()) {
      case 'aprobada':
        estadoColor = Colors.green;
        break;
      case 'pendiente':
        estadoColor = Colors.orange;
        break;
      case 'rechazada':
        estadoColor = Colors.red;
        break;
      default:
        estadoColor = Colors.grey;
    }

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.2),
          child: Icon(Icons.assignment, color: estadoColor),
        ),
        title: const Text('Mi solicitud'),
        subtitle: Text('Estado: $estado'),
        trailing: Chip(
          label: Text(estado),
          backgroundColor: estadoColor.withOpacity(0.2),
          labelStyle: TextStyle(color: estadoColor),
        ),
      ),
    );
  }

  Widget _buildNegociosCertificados() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Negocios certificados', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            TextButton(onPressed: _verTodosNegocios, child: const Text('Ver todos')),
          ],
        ),
        const SizedBox(height: 12),
        if (_negocios.isEmpty)
          const Text('No hay negocios certificados aun')
        else
          ..._negocios.take(5).map((n) => _buildNegocioCard(n)),
      ],
    );
  }

  Widget _buildNegocioCard(Map<String, dynamic> negocio) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.green.shade100,
          child: Icon(Icons.store, color: Colors.green.shade600),
        ),
        title: Text(negocio['nombre'] ?? ''),
        subtitle: Text(negocio['categoria'] ?? ''),
        trailing: const Icon(Icons.verified, color: Colors.green),
        onTap: () => _verDetalleNegocio(negocio),
      ),
    );
  }

  void _verTodosNegocios() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(Icons.verified, color: Colors.green.shade600),
                  const SizedBox(width: 12),
                  const Text(
                    'Negocios Certificados',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: _negocios.isEmpty
                  ? const Center(child: Text('No hay negocios certificados aun'))
                  : ListView.builder(
                      controller: scrollController,
                      padding: const EdgeInsets.all(16),
                      itemCount: _negocios.length,
                      itemBuilder: (context, index) {
                        final negocio = _negocios[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: Colors.green.shade100,
                              child: Icon(Icons.store, color: Colors.green.shade600),
                            ),
                            title: Text(negocio['nombre'] ?? ''),
                            subtitle: Text(negocio['categoria'] ?? ''),
                            trailing: const Icon(Icons.verified, color: Colors.green),
                            onTap: () {
                              Navigator.pop(context);
                              _verDetalleNegocio(negocio);
                            },
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

  void _verDetalleNegocio(Map<String, dynamic> negocio) {
    final nombre = negocio['nombre'] ?? 'Negocio';
    final categoria = negocio['categoria'] ?? '';
    final descripcion = negocio['descripcion'] ?? '';
    final direccion = negocio['direccion'] ?? '';
    final telefono = negocio['telefono'] ?? '';
    final email = negocio['email'] ?? '';
    final web = negocio['web'] ?? '';
    final fechaCertificacion = negocio['fecha_certificacion'] ?? '';
    final criteriosCumplidos = (negocio['criterios_cumplidos'] as List<dynamic>? ?? [])
        .whereType<String>()
        .toList();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
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
                    radius: 30,
                    backgroundColor: Colors.green.shade100,
                    child: Icon(Icons.store, size: 32, color: Colors.green.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombre,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        if (categoria.isNotEmpty)
                          Chip(
                            label: Text(categoria),
                            visualDensity: VisualDensity.compact,
                          ),
                      ],
                    ),
                  ),
                  const Icon(Icons.verified, color: Colors.green, size: 32),
                ],
              ),
              const SizedBox(height: 20),
              if (descripcion.isNotEmpty) ...[
                const Text('Descripcion', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Text(descripcion),
                const SizedBox(height: 16),
              ],
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      if (direccion.isNotEmpty)
                        _buildContactoRow(Icons.location_on, 'Direccion', direccion),
                      if (telefono.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _buildContactoRow(Icons.phone, 'Telefono', telefono),
                      ],
                      if (email.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _buildContactoRow(Icons.email, 'Email', email),
                      ],
                      if (web.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _buildContactoRow(Icons.language, 'Web', web),
                      ],
                      if (fechaCertificacion.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _buildContactoRow(Icons.calendar_today, 'Certificado desde', fechaCertificacion),
                      ],
                    ],
                  ),
                ),
              ),
              if (criteriosCumplidos.isNotEmpty) ...[
                const SizedBox(height: 16),
                const Text('Criterios certificados', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: criteriosCumplidos.map((criterio) {
                    return Chip(
                      avatar: Icon(Icons.check_circle, size: 16, color: Colors.green.shade600),
                      label: Text(criterio),
                      backgroundColor: Colors.green.shade50,
                    );
                  }).toList(),
                ),
              ],
              const SizedBox(height: 24),
              if (telefono.isNotEmpty || email.isNotEmpty)
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () {
                      Navigator.pop(context);
                      _mostrarOpcionesContacto(nombre, telefono, email);
                    },
                    icon: const Icon(Icons.message),
                    label: const Text('Contactar'),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildContactoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: Colors.grey),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
              Text(value),
            ],
          ),
        ),
      ],
    );
  }

  void _mostrarOpcionesContacto(String nombre, String telefono, String email) {
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
                'Contactar con $nombre',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              if (telefono.isNotEmpty)
                ListTile(
                  leading: CircleAvatar(
                    backgroundColor: Colors.green.shade100,
                    child: Icon(Icons.phone, color: Colors.green.shade600),
                  ),
                  title: const Text('Llamar'),
                  subtitle: Text(telefono),
                  onTap: () async {
                    Navigator.pop(context);
                    final uri = Uri.parse('tel:$telefono');
                    if (await canLaunchUrl(uri)) {
                      await launchUrl(uri);
                    } else if (mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('No se puede realizar la llamada')),
                      );
                    }
                  },
                ),
              if (email.isNotEmpty)
                ListTile(
                  leading: CircleAvatar(
                    backgroundColor: Colors.blue.shade100,
                    child: Icon(Icons.email, color: Colors.blue.shade600),
                  ),
                  title: const Text('Enviar email'),
                  subtitle: Text(email),
                  onTap: () async {
                    Navigator.pop(context);
                    final uri = Uri.parse('mailto:$email?subject=Consulta sobre $nombre');
                    if (await canLaunchUrl(uri)) {
                      await launchUrl(uri);
                    } else if (mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('No se puede abrir el cliente de email')),
                      );
                    }
                  },
                ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCriterios() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Criterios de certificacion', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_criterios.isEmpty)
          const Text('Cargando criterios...')
        else
          ..._criterios.map((c) => ListTile(
                leading: Icon(Icons.check_circle, color: Colors.green.shade400),
                title: Text(c['titulo'] ?? ''),
                subtitle: Text(c['descripcion'] ?? ''),
                contentPadding: EdgeInsets.zero,
              )),
      ],
    );
  }

  void _mostrarInfo() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sello Conciencia'),
        content: const Text(
          'El Sello Conciencia certifica a negocios que cumplen con criterios de '
          'sostenibilidad ambiental, responsabilidad social y comercio justo.',
        ),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Entendido'))],
      ),
    );
  }

  void _solicitarSello() {
    final nombreNegocioController = TextEditingController();
    final descripcionController = TextEditingController();
    final direccionController = TextEditingController();
    String categoriaSeleccionada = 'alimentacion';
    final criteriosCumplidos = <String, bool>{};

    // Inicializar criterios
    for (final criterio in _criterios) {
      final id = criterio['id']?.toString() ?? '';
      if (id.isNotEmpty) {
        criteriosCumplidos[id] = false;
      }
    }

    final categorias = [
      {'id': 'alimentacion', 'nombre': 'Alimentacion'},
      {'id': 'comercio', 'nombre': 'Comercio'},
      {'id': 'servicios', 'nombre': 'Servicios'},
      {'id': 'artesania', 'nombre': 'Artesania'},
      {'id': 'hosteleria', 'nombre': 'Hosteleria'},
      {'id': 'otro', 'nombre': 'Otro'},
    ];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => DraggableScrollableSheet(
          initialChildSize: 0.85,
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
                    Icon(Icons.verified, color: Colors.green.shade600),
                    const SizedBox(width: 12),
                    const Text(
                      'Solicitar Sello Conciencia',
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
                  'Certifica tu negocio como sostenible y responsable.',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nombreNegocioController,
                  decoration: const InputDecoration(
                    labelText: 'Nombre del negocio',
                    prefixIcon: Icon(Icons.store),
                    border: OutlineInputBorder(),
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
                  items: categorias.map((cat) {
                    return DropdownMenuItem<String>(
                      value: cat['id'],
                      child: Text(cat['nombre']!),
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
                  controller: direccionController,
                  decoration: const InputDecoration(
                    labelText: 'Direccion',
                    prefixIcon: Icon(Icons.location_on),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: const InputDecoration(
                    labelText: 'Descripcion del negocio',
                    prefixIcon: Icon(Icons.description),
                    border: OutlineInputBorder(),
                    hintText: 'Describe tu actividad y valores...',
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),
                const Text(
                  'Criterios de certificacion',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(
                  'Indica los criterios que cumple tu negocio:',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                ),
                const SizedBox(height: 12),
                if (_criterios.isNotEmpty)
                  ..._criterios.map((criterio) {
                    final id = criterio['id']?.toString() ?? '';
                    return CheckboxListTile(
                      value: criteriosCumplidos[id] ?? false,
                      onChanged: (value) {
                        setModalState(() => criteriosCumplidos[id] = value ?? false);
                      },
                      title: Text(criterio['titulo'] ?? ''),
                      subtitle: Text(
                        criterio['descripcion'] ?? '',
                        style: const TextStyle(fontSize: 12),
                      ),
                      controlAffinity: ListTileControlAffinity.leading,
                      contentPadding: EdgeInsets.zero,
                    );
                  })
                else
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Icon(Icons.info, color: Colors.blue.shade400),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Text('Los criterios seran evaluados tras la solicitud.'),
                          ),
                        ],
                      ),
                    ),
                  ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      if (nombreNegocioController.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('El nombre del negocio es obligatorio')),
                        );
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarSolicitudSello(
                        nombre: nombreNegocioController.text,
                        categoria: categoriaSeleccionada,
                        direccion: direccionController.text,
                        descripcion: descripcionController.text,
                        criterios: criteriosCumplidos,
                      );
                    },
                    icon: const Icon(Icons.send),
                    label: const Text('Enviar solicitud'),
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

  Future<void> _enviarSolicitudSello({
    required String nombre,
    required String categoria,
    required String direccion,
    required String descripcion,
    required Map<String, bool> criterios,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/sello-conciencia/solicitar', data: {
        'nombre_negocio': nombre,
        'categoria': categoria,
        'direccion': direccion,
        'descripcion': descripcion,
        'criterios_cumplidos': criterios.entries
            .where((e) => e.value)
            .map((e) => e.key)
            .toList(),
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Solicitud enviada. Te contactaremos pronto.'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al enviar');
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
