part of 'clientes_screen.dart';

class ClienteDetalleScreen extends StatelessWidget {
  final Map<String, dynamic> cliente;

  const ClienteDetalleScreen({super.key, required this.cliente});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final nombreCliente = cliente['nombre'] ?? cliente['name'] ?? 'Sin nombre';
    final emailCliente = cliente['email'] ?? '';
    final telefonoCliente = cliente['telefono'] ?? cliente['phone'] ?? '';
    final direccionCliente = cliente['direccion'] ?? cliente['address'] ?? '';
    final tipoCliente = cliente['tipo'] ?? cliente['type'] ?? '';
    final estadoCliente = cliente['estado'] ?? cliente['status'] ?? 'activo';
    final notasCliente = cliente['notas'] ?? cliente['notes'] ?? '';
    final fechaCreacion = cliente['fecha_creacion'] ?? cliente['created_at'] ?? '';
    final empresaCliente = cliente['empresa'] ?? cliente['company'] ?? '';
    final cifCliente = cliente['cif'] ?? cliente['nif'] ?? cliente['tax_id'] ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle del Cliente'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit),
            onPressed: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(
                  builder: (context) => ClienteFormScreen(cliente: cliente),
                ),
              );
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: Column(
              children: [
                FlavorInitialsAvatar(
                  name: nombreCliente,
                  radius: 50,
                  backgroundColor: Colors.blue.shade100,
                  textStyle: TextStyle(
                    fontSize: 36,
                    color: Colors.blue.shade700,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  nombreCliente,
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (empresaCliente.isNotEmpty)
                  Text(
                    empresaCliente,
                    style: theme.textTheme.bodyLarge?.copyWith(color: Colors.grey),
                  ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (tipoCliente.isNotEmpty)
                      Chip(
                        label: Text(tipoCliente),
                        backgroundColor: theme.colorScheme.primaryContainer,
                      ),
                    const SizedBox(width: 8),
                    Chip(
                      avatar: Icon(
                        estadoCliente.toString().toLowerCase() == 'activo'
                            ? Icons.check_circle
                            : Icons.pause_circle,
                        size: 16,
                        color: estadoCliente.toString().toLowerCase() == 'activo'
                            ? Colors.green
                            : Colors.grey,
                      ),
                      label: Text(estadoCliente.toString()),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              if (telefonoCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.phone,
                  label: 'Llamar',
                  color: Colors.green,
                  onTap: () => _realizarLlamada(context, telefonoCliente),
                ),
              if (emailCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.email,
                  label: 'Email',
                  color: Colors.blue,
                  onTap: () => _enviarEmail(context, emailCliente, nombreCliente),
                ),
              if (telefonoCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.message,
                  label: 'Mensaje',
                  color: Colors.orange,
                  onTap: () => _enviarSMS(context, telefonoCliente),
                ),
            ],
          ),
          const SizedBox(height: 24),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Información de contacto',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Divider(),
                  if (emailCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.email),
                      title: const Text('Email'),
                      subtitle: Text(emailCliente),
                    ),
                  if (telefonoCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.phone),
                      title: const Text('Teléfono'),
                      subtitle: Text(telefonoCliente),
                    ),
                  if (direccionCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.location_on),
                      title: const Text('Dirección'),
                      subtitle: Text(direccionCliente),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (cifCliente.isNotEmpty || empresaCliente.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Información fiscal',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Divider(),
                    if (empresaCliente.isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.business),
                        title: const Text('Empresa'),
                        subtitle: Text(empresaCliente),
                      ),
                    if (cifCliente.isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.badge),
                        title: const Text('CIF/NIF'),
                        subtitle: Text(cifCliente),
                      ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),
          if (notasCliente.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Notas',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Divider(),
                    Text(notasCliente),
                  ],
                ),
              ),
            ),
          if (fechaCreacion.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Text(
                'Cliente desde: ${_formatDate(fechaCreacion)}',
                style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey),
                textAlign: TextAlign.center,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildAccionRapida(
    BuildContext context, {
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 4),
            Text(label, style: TextStyle(color: color, fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Future<void> _realizarLlamada(BuildContext context, String telefono) async {
    await FlavorContactLauncher.call(context, telefono);
  }

  Future<void> _enviarEmail(BuildContext context, String email, String nombre) async {
    await FlavorContactLauncher.email(
      context,
      email,
      subject: 'Contacto desde CRM',
      body: 'Hola $nombre,\n\n',
      errorMessage: 'No se puede abrir el email',
    );
  }

  Future<void> _enviarSMS(BuildContext context, String telefono) async {
    await FlavorContactLauncher.sms(context, telefono);
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}

class ClienteFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? cliente;

  const ClienteFormScreen({super.key, this.cliente});

  @override
  ConsumerState<ClienteFormScreen> createState() => _ClienteFormScreenState();
}

class _ClienteFormScreenState extends ConsumerState<ClienteFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _emailController = TextEditingController();
  final _telefonoController = TextEditingController();
  final _direccionController = TextEditingController();
  final _empresaController = TextEditingController();
  final _cifController = TextEditingController();
  final _notasController = TextEditingController();
  String _tipoSeleccionado = '';
  bool _guardando = false;

  bool get _esEdicion => widget.cliente != null;

  final List<String> _tiposCliente = [
    'Particular',
    'Empresa',
    'Autónomo',
    'Administración',
    'Otro',
  ];

  @override
  void initState() {
    super.initState();
    if (_esEdicion) {
      _nombreController.text = widget.cliente!['nombre'] ?? widget.cliente!['name'] ?? '';
      _emailController.text = widget.cliente!['email'] ?? '';
      _telefonoController.text = widget.cliente!['telefono'] ?? widget.cliente!['phone'] ?? '';
      _direccionController.text = widget.cliente!['direccion'] ?? widget.cliente!['address'] ?? '';
      _empresaController.text = widget.cliente!['empresa'] ?? widget.cliente!['company'] ?? '';
      _cifController.text = widget.cliente!['cif'] ?? widget.cliente!['nif'] ?? '';
      _notasController.text = widget.cliente!['notas'] ?? widget.cliente!['notes'] ?? '';
      _tipoSeleccionado = widget.cliente!['tipo'] ?? widget.cliente!['type'] ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar Cliente' : 'Nuevo Cliente'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _nombreController,
              decoration: const InputDecoration(
                labelText: 'Nombre *',
                prefixIcon: Icon(Icons.person),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.email),
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _telefonoController,
              decoration: const InputDecoration(
                labelText: 'Teléfono',
                prefixIcon: Icon(Icons.phone),
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _tipoSeleccionado.isEmpty ? null : _tipoSeleccionado,
              decoration: const InputDecoration(
                labelText: 'Tipo de cliente',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _tiposCliente
                  .map((tipo) => DropdownMenuItem(
                        value: tipo,
                        child: Text(tipo),
                      ))
                  .toList(),
              onChanged: (value) {
                setState(() => _tipoSeleccionado = value ?? '');
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _empresaController,
              decoration: const InputDecoration(
                labelText: 'Empresa',
                prefixIcon: Icon(Icons.business),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _cifController,
              decoration: const InputDecoration(
                labelText: 'CIF/NIF',
                prefixIcon: Icon(Icons.badge),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _direccionController,
              decoration: const InputDecoration(
                labelText: 'Dirección',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(),
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _notasController,
              decoration: const InputDecoration(
                labelText: 'Notas',
                prefixIcon: Icon(Icons.note),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: _guardando ? null : _guardarCliente,
              icon: _guardando ? const FlavorInlineSpinner() : const Icon(Icons.check),
              label: Text(
                _guardando
                    ? 'Guardando...'
                    : (_esEdicion ? 'Guardar Cambios' : 'Crear Cliente'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _guardarCliente() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _guardando = true);

    try {
      final api = ref.read(apiClientProvider);
      final datos = {
        'nombre': _nombreController.text.trim(),
        'email': _emailController.text.trim(),
        'telefono': _telefonoController.text.trim(),
        'tipo': _tipoSeleccionado,
        'empresa': _empresaController.text.trim(),
        'cif': _cifController.text.trim(),
        'direccion': _direccionController.text.trim(),
        'notas': _notasController.text.trim(),
      };

      final ApiResponse<Map<String, dynamic>> response;
      if (_esEdicion) {
        final idCliente = widget.cliente!['id'] ?? widget.cliente!['ID'];
        response = await api.put('/clientes/$idCliente', data: datos);
      } else {
        response = await api.post('/clientes', data: datos);
      }

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(
            context,
            _esEdicion ? 'Cliente actualizado correctamente' : 'Cliente creado correctamente',
          );
          Navigator.of(context).pop(true);
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al guardar');
        }
      }
    } finally {
      if (mounted) {
        setState(() => _guardando = false);
      }
    }
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _emailController.dispose();
    _telefonoController.dispose();
    _direccionController.dispose();
    _empresaController.dispose();
    _cifController.dispose();
    _notasController.dispose();
    super.dispose();
  }
}
