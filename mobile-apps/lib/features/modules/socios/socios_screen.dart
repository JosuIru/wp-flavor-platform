import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';

/// Pantalla de perfil de socio (usuario)
class SociosScreen extends ConsumerStatefulWidget {
  const SociosScreen({super.key});

  @override
  ConsumerState<SociosScreen> createState() => _SociosScreenState();
}

class _SociosScreenState extends ConsumerState<SociosScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _emailController = TextEditingController();

  bool _isLoading = true;
  Map<String, dynamic>? _userData;

  @override
  void initState() {
    super.initState();
    _loadUserProfile();
  }

  Future<void> _loadUserProfile() async {
    setState(() => _isLoading = true);
    final user_id = ref.read(clientAuthProvider).userId;
    if (user_id == null) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/wp/v2/users/$user_id');

      if (response.success && response.data != null) {
        setState(() {
          _userData = response.data!;
          _nombreController.text = _userData!['name'] ?? '';
          _emailController.text = _userData!['email'] ?? '';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: Text('Mi Perfil')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      appBar: AppBar(title: Text('Mi Perfil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: CircleAvatar(
              radius: 60,
              child: Text(
                (_nombreController.text.isNotEmpty ? _nombreController.text[0] : 'U').toUpperCase(),
                style: TextStyle(fontSize: 40),
              ),
            ),
          ),
          SizedBox(height: 32),
          TextFormField(
            controller: _nombreController,
            decoration: InputDecoration(
              labelText: 'Nombre',
              border: OutlineInputBorder(),
              enabled: false,
            ),
          ),
          SizedBox(height: 16),
          TextFormField(
            controller: _emailController,
            decoration: InputDecoration(
              labelText: 'Email',
              border: OutlineInputBorder(),
              enabled: false,
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _emailController.dispose();
    super.dispose();
  }
}
