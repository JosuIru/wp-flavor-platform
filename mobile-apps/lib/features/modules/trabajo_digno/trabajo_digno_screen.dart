import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class TrabajoDignoScreen extends ConsumerStatefulWidget {
  const TrabajoDignoScreen({super.key});

  @override
  ConsumerState<TrabajoDignoScreen> createState() => _TrabajoDignoScreenState();
}

class _TrabajoDignoScreenState extends ConsumerState<TrabajoDignoScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _ofertas = [];
  List<Map<String, dynamic>> _empresas = [];
  Map<String, dynamic>? _miPerfil;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/trabajo-digno/ofertas');
      if (response.success && response.data != null) {
        setState(() {
          _ofertas = (response.data!['ofertas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _empresas = (response.data!['empresas_comprometidas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _miPerfil = response.data!['mi_perfil'];
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
        title: const Text('Trabajo Digno'),
        actions: [
          IconButton(icon: const Icon(Icons.person), onPressed: _verMiPerfil),
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
                    const SizedBox(height: 24),
                    _buildOfertasSection(),
                    const SizedBox(height: 24),
                    _buildEmpresasSection(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildBanner() {
    return Card(
      color: Colors.blue.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(Icons.work, size: 48, color: Colors.blue.shade400),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Empleos con condiciones justas', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text('Ofertas de empresas comprometidas con el trabajo digno'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOfertasSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Ofertas de empleo', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            TextButton.icon(
              onPressed: _mostrarFiltros,
              icon: const Icon(Icons.filter_list, size: 18),
              label: const Text('Filtrar'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (_ofertas.isEmpty)
          Center(
            child: Column(
              children: [
                Icon(Icons.work_off, size: 64, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                const Text('No hay ofertas disponibles'),
              ],
            ),
          )
        else
          ..._ofertas.map((o) => _buildOfertaCard(o)),
      ],
    );
  }

  Widget _buildOfertaCard(Map<String, dynamic> oferta) {
    final badges = <Widget>[];
    if (oferta['teletrabajo'] == true) {
      badges.add(_buildBadge('Remoto', Colors.purple));
    }
    if (oferta['conciliacion'] == true) {
      badges.add(_buildBadge('Conciliacion', Colors.teal));
    }
    if (oferta['salario_justo'] == true) {
      badges.add(_buildBadge('Salario justo', Colors.green));
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue.shade100,
                  child: Icon(Icons.business, color: Colors.blue.shade600),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(oferta['titulo'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(oferta['empresa'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
                    ],
                  ),
                ),
              ],
            ),
            if (badges.isNotEmpty) ...[
              const SizedBox(height: 12),
              Wrap(spacing: 8, runSpacing: 4, children: badges),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.location_on, size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(oferta['ubicacion'] ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                const SizedBox(width: 16),
                Icon(Icons.euro, size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(oferta['salario'] ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                OutlinedButton(onPressed: () => _verDetalle(oferta), child: const Text('Ver mas')),
                const SizedBox(width: 8),
                FilledButton(onPressed: () => _aplicar(oferta), child: const Text('Aplicar')),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text, style: TextStyle(fontSize: 11, color: color, fontWeight: FontWeight.w500)),
    );
  }

  Widget _buildEmpresasSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Empresas comprometidas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_empresas.isEmpty)
          const Text('No hay empresas registradas')
        else
          SizedBox(
            height: 100,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _empresas.length,
              itemBuilder: (context, index) {
                final empresa = _empresas[index];
                return Card(
                  margin: const EdgeInsets.only(right: 12),
                  child: Container(
                    width: 120,
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.verified, color: Colors.blue.shade400),
                        const SizedBox(height: 8),
                        Text(
                          empresa['nombre'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 12),
                          textAlign: TextAlign.center,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

  void _verMiPerfil() {
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
                    radius: 32,
                    backgroundColor: Colors.blue.shade100,
                    child: Icon(Icons.person, size: 32, color: Colors.blue.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _miPerfil?['nombre'] ?? 'Tu perfil',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          _miPerfil?['profesion'] ?? 'Profesion no especificada',
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
              const SizedBox(height: 24),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildPerfilRow(
                        Icons.email,
                        'Email',
                        _miPerfil?['email'] ?? 'No especificado',
                      ),
                      const Divider(),
                      _buildPerfilRow(
                        Icons.phone,
                        'Telefono',
                        _miPerfil?['telefono'] ?? 'No especificado',
                      ),
                      const Divider(),
                      _buildPerfilRow(
                        Icons.location_on,
                        'Ubicacion',
                        _miPerfil?['ubicacion'] ?? 'No especificada',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Habilidades',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 12),
              if (_miPerfil?['habilidades'] != null &&
                  (_miPerfil!['habilidades'] as List).isNotEmpty)
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: (_miPerfil!['habilidades'] as List)
                      .map((h) => Chip(label: Text(h.toString())))
                      .toList(),
                )
              else
                Text(
                  'Anade tus habilidades para mejorar tu perfil',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _editarPerfil();
                  },
                  icon: const Icon(Icons.edit),
                  label: const Text('Editar perfil'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPerfilRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Flexible(child: Text(value, textAlign: TextAlign.end)),
        ],
      ),
    );
  }

  void _editarPerfil() {
    final nombreController = TextEditingController(text: _miPerfil?['nombre'] ?? '');
    final profesionController = TextEditingController(text: _miPerfil?['profesion'] ?? '');
    final telefonoController = TextEditingController(text: _miPerfil?['telefono'] ?? '');
    final ubicacionController = TextEditingController(text: _miPerfil?['ubicacion'] ?? '');
    final habilidadesController = TextEditingController(
      text: (_miPerfil?['habilidades'] as List?)?.join(', ') ?? '',
    );
    final experienciaController = TextEditingController(text: _miPerfil?['experiencia'] ?? '');

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
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.edit),
                  const SizedBox(width: 12),
                  const Text(
                    'Editar perfil',
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
                'Actualiza tu informacion para mejorar tus oportunidades.',
                style: TextStyle(color: Colors.grey.shade600),
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre completo',
                  prefixIcon: Icon(Icons.person),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: profesionController,
                decoration: const InputDecoration(
                  labelText: 'Profesion',
                  prefixIcon: Icon(Icons.work),
                  border: OutlineInputBorder(),
                  hintText: 'Ej: Desarrollador/a, Disenador/a...',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: telefonoController,
                decoration: const InputDecoration(
                  labelText: 'Telefono',
                  prefixIcon: Icon(Icons.phone),
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: ubicacionController,
                decoration: const InputDecoration(
                  labelText: 'Ubicacion',
                  prefixIcon: Icon(Icons.location_on),
                  border: OutlineInputBorder(),
                  hintText: 'Ciudad, Provincia',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: habilidadesController,
                decoration: const InputDecoration(
                  labelText: 'Habilidades',
                  prefixIcon: Icon(Icons.psychology),
                  border: OutlineInputBorder(),
                  hintText: 'Separadas por comas',
                  helperText: 'Ej: Python, React, Gestion de proyectos',
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: experienciaController,
                decoration: const InputDecoration(
                  labelText: 'Experiencia laboral',
                  prefixIcon: Icon(Icons.history),
                  border: OutlineInputBorder(),
                  hintText: 'Describe tu experiencia brevemente...',
                ),
                maxLines: 4,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () async {
                    Navigator.pop(context);
                    await _guardarPerfil(
                      nombre: nombreController.text,
                      profesion: profesionController.text,
                      telefono: telefonoController.text,
                      ubicacion: ubicacionController.text,
                      habilidades: habilidadesController.text,
                      experiencia: experienciaController.text,
                    );
                  },
                  icon: const Icon(Icons.save),
                  label: const Text('Guardar cambios'),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _guardarPerfil({
    required String nombre,
    required String profesion,
    required String telefono,
    required String ubicacion,
    required String habilidades,
    required String experiencia,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final habilidadesList = habilidades
          .split(',')
          .map((h) => h.trim())
          .where((h) => h.isNotEmpty)
          .toList();

      final response = await api.post('/trabajo-digno/perfil', data: {
        'nombre': nombre,
        'profesion': profesion,
        'telefono': telefono,
        'ubicacion': ubicacion,
        'habilidades': habilidadesList,
        'experiencia': experiencia,
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Perfil actualizado correctamente'),
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

  void _mostrarFiltros() {
    String ubicacionFiltro = '';
    bool soloTeletrabajo = false;
    bool soloConciliacion = false;
    bool soloSalarioJusto = false;
    String tipoContrato = 'todos';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.filter_list),
                  const SizedBox(width: 12),
                  const Text(
                    'Filtrar ofertas',
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
                initialValue: ubicacionFiltro,
                decoration: const InputDecoration(
                  labelText: 'Ubicacion',
                  prefixIcon: Icon(Icons.location_on),
                  border: OutlineInputBorder(),
                  hintText: 'Ej: Madrid, Barcelona...',
                ),
                onChanged: (value) => ubicacionFiltro = value,
              ),
              const SizedBox(height: 16),
              const Text('Tipo de contrato', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: tipoContrato,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.work),
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(value: 'todos', child: Text('Todos')),
                  DropdownMenuItem(value: 'indefinido', child: Text('Indefinido')),
                  DropdownMenuItem(value: 'temporal', child: Text('Temporal')),
                  DropdownMenuItem(value: 'practicas', child: Text('Practicas')),
                  DropdownMenuItem(value: 'autonomo', child: Text('Autonomo')),
                ],
                onChanged: (value) {
                  setModalState(() => tipoContrato = value ?? 'todos');
                },
              ),
              const SizedBox(height: 16),
              const Text('Caracteristicas', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              CheckboxListTile(
                value: soloTeletrabajo,
                onChanged: (value) => setModalState(() => soloTeletrabajo = value ?? false),
                title: const Text('Teletrabajo'),
                secondary: const Icon(Icons.home_work, color: Colors.purple),
                contentPadding: EdgeInsets.zero,
              ),
              CheckboxListTile(
                value: soloConciliacion,
                onChanged: (value) => setModalState(() => soloConciliacion = value ?? false),
                title: const Text('Conciliacion'),
                secondary: const Icon(Icons.family_restroom, color: Colors.teal),
                contentPadding: EdgeInsets.zero,
              ),
              CheckboxListTile(
                value: soloSalarioJusto,
                onChanged: (value) => setModalState(() => soloSalarioJusto = value ?? false),
                title: const Text('Salario justo'),
                secondary: const Icon(Icons.euro, color: Colors.green),
                contentPadding: EdgeInsets.zero,
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Filtros limpiados')),
                        );
                        _loadData();
                      },
                      child: const Text('Limpiar'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () async {
                        Navigator.pop(context);
                        await _aplicarFiltros(
                          ubicacion: ubicacionFiltro,
                          tipoContrato: tipoContrato,
                          teletrabajo: soloTeletrabajo,
                          conciliacion: soloConciliacion,
                          salarioJusto: soloSalarioJusto,
                        );
                      },
                      icon: const Icon(Icons.check),
                      label: const Text('Aplicar'),
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

  Future<void> _aplicarFiltros({
    required String ubicacion,
    required String tipoContrato,
    required bool teletrabajo,
    required bool conciliacion,
    required bool salarioJusto,
  }) async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/trabajo-digno/ofertas', queryParameters: {
        if (ubicacion.isNotEmpty) 'ubicacion': ubicacion,
        if (tipoContrato != 'todos') 'tipo_contrato': tipoContrato,
        if (teletrabajo) 'teletrabajo': '1',
        if (conciliacion) 'conciliacion': '1',
        if (salarioJusto) 'salario_justo': '1',
      });

      if (response.success && response.data != null) {
        setState(() {
          _ofertas = (response.data!['ofertas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('${_ofertas.length} ofertas encontradas')),
          );
        }
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _verDetalle(Map<String, dynamic> oferta) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.8,
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
                    radius: 24,
                    backgroundColor: Colors.blue.shade100,
                    child: Icon(Icons.business, color: Colors.blue.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          oferta['titulo'] ?? '',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          oferta['empresa'] ?? '',
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
              const SizedBox(height: 16),
              // Badges
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  if (oferta['teletrabajo'] == true)
                    _buildBadge('Remoto', Colors.purple),
                  if (oferta['conciliacion'] == true)
                    _buildBadge('Conciliacion', Colors.teal),
                  if (oferta['salario_justo'] == true)
                    _buildBadge('Salario justo', Colors.green),
                ],
              ),
              const SizedBox(height: 20),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildDetalleRow(Icons.location_on, 'Ubicacion', oferta['ubicacion'] ?? ''),
                      const Divider(),
                      _buildDetalleRow(Icons.euro, 'Salario', oferta['salario'] ?? ''),
                      const Divider(),
                      _buildDetalleRow(Icons.work, 'Tipo', oferta['tipo_contrato'] ?? 'No especificado'),
                      const Divider(),
                      _buildDetalleRow(Icons.schedule, 'Jornada', oferta['jornada'] ?? 'No especificada'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              if ((oferta['descripcion'] ?? '').isNotEmpty) ...[
                const Text(
                  'Descripcion',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['descripcion']),
                const SizedBox(height: 20),
              ],
              if ((oferta['requisitos'] ?? '').isNotEmpty) ...[
                const Text(
                  'Requisitos',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['requisitos']),
                const SizedBox(height: 20),
              ],
              if ((oferta['beneficios'] ?? '').isNotEmpty) ...[
                const Text(
                  'Beneficios',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['beneficios']),
                const SizedBox(height: 20),
              ],
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _aplicar(oferta);
                  },
                  icon: const Icon(Icons.send),
                  label: const Text('Aplicar a esta oferta'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetalleRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  void _aplicar(Map<String, dynamic> oferta) async {
    final cartaPresentacionController = TextEditingController();

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Aplicar a ${oferta['titulo']}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Tu perfil sera enviado a ${oferta['empresa']}.',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: cartaPresentacionController,
              decoration: const InputDecoration(
                labelText: 'Carta de presentacion (opcional)',
                border: OutlineInputBorder(),
                hintText: 'Presentate brevemente...',
              ),
              maxLines: 3,
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
            child: const Text('Enviar aplicacion'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/trabajo-digno/aplicar', data: {
        'oferta_id': oferta['id'],
        'carta_presentacion': cartaPresentacionController.text,
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Aplicacion enviada a ${oferta['empresa']}'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        throw Exception(response.error ?? 'Error al aplicar');
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
