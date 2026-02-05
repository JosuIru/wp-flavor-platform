import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';

/// Pantalla de formulario de inscripción a un campamento
class CampInscriptionFormScreen extends ConsumerStatefulWidget {
  final Camp camp;

  const CampInscriptionFormScreen({
    super.key,
    required this.camp,
  });

  @override
  ConsumerState<CampInscriptionFormScreen> createState() =>
      _CampInscriptionFormScreenState();
}

class _CampInscriptionFormScreenState
    extends ConsumerState<CampInscriptionFormScreen> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;

  // Datos del participante
  final _participantNameController = TextEditingController();
  final _participantAgeController = TextEditingController();
  final _participantAllergiesController = TextEditingController();

  // Datos del responsable
  final _guardianNameController = TextEditingController();
  final _guardianEmailController = TextEditingController();
  final _guardianPhoneController = TextEditingController();

  bool _acceptTerms = false;

  Future<void> _submitInscription() async {
    if (!_formKey.currentState!.validate()) return;

    if (!_acceptTerms) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debes aceptar los términos y condiciones'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);

    final participantData = {
      'name': _participantNameController.text.trim(),
      'age': int.tryParse(_participantAgeController.text) ?? 0,
      'allergies': _participantAllergiesController.text.trim(),
    };

    final guardianData = {
      'name': _guardianNameController.text.trim(),
      'email': _guardianEmailController.text.trim(),
      'phone': _guardianPhoneController.text.trim(),
    };

    final response = await api.createCampInscription(
      campId: widget.camp.id,
      participant: participantData,
      guardian: guardianData,
      paymentMethod: 'pending', // Método de pago por defecto
    );

    setState(() => _isLoading = false);

    if (response.success && mounted) {
      // Mostrar diálogo de éxito
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => AlertDialog(
          icon: const Icon(Icons.check_circle, color: Colors.green, size: 64),
          title: const Text('¡Inscripción enviada!'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Tu inscripción ha sido registrada correctamente.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              const Text(
                'Recibirás un correo electrónico con los detalles del pago y confirmación.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12),
              ),
            ],
          ),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.of(context).pop(); // Cerrar diálogo
                Navigator.of(context).pop(); // Volver a detalle
                Navigator.of(context).pop(); // Volver a lista
              },
              child: const Text('Aceptar'),
            ),
          ],
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.error ?? 'Error al crear inscripción'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Inscripción'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Resumen del campamento
            Card(
              color: Theme.of(context).primaryColor.withOpacity(0.1),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.camp.title,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(widget.camp.duration),
                        Text(
                          widget.camp.formattedPriceTotal,
                          style: Theme.of(context)
                              .textTheme
                              .titleLarge
                              ?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).primaryColor,
                              ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Datos del participante
            Text(
              'Datos del participante',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _participantNameController,
              decoration: const InputDecoration(
                labelText: 'Nombre completo *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.person),
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
              controller: _participantAgeController,
              decoration: const InputDecoration(
                labelText: 'Edad *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.cake),
                suffixText: 'años',
              ),
              keyboardType: TextInputType.number,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La edad es obligatoria';
                }
                final age = int.tryParse(value);
                if (age == null || age <= 0) {
                  return 'Edad inválida';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _participantAllergiesController,
              decoration: const InputDecoration(
                labelText: 'Alergias o condiciones médicas',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.medical_services),
                helperText: 'Opcional. Indica si tiene alguna alergia importante.',
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 32),

            // Datos del responsable
            Text(
              'Datos del responsable',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianNameController,
              decoration: const InputDecoration(
                labelText: 'Nombre completo *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.person_outline),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre del responsable es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianEmailController,
              decoration: const InputDecoration(
                labelText: 'Email *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.email),
              ),
              keyboardType: TextInputType.emailAddress,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El email es obligatorio';
                }
                if (!value.contains('@')) {
                  return 'Email inválido';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianPhoneController,
              decoration: const InputDecoration(
                labelText: 'Teléfono *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.phone),
                helperText: 'Incluye el prefijo del país (ej: +34)',
              ),
              keyboardType: TextInputType.phone,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El teléfono es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 32),

            // Términos y condiciones
            Card(
              child: CheckboxListTile(
                value: _acceptTerms,
                onChanged: (value) {
                  setState(() => _acceptTerms = value ?? false);
                },
                title: const Text('Acepto los términos y condiciones'),
                subtitle: const Text(
                  'He leído y acepto las condiciones de inscripción y la política de privacidad.',
                  style: TextStyle(fontSize: 12),
                ),
                controlAffinity: ListTileControlAffinity.leading,
              ),
            ),
            const SizedBox(height: 24),

            // Información de pago
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.blue[200]!),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info, color: Colors.blue[700]),
                      const SizedBox(width: 8),
                      Text(
                        'Información de pago',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue[900],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Recibirás un correo electrónico con las instrucciones de pago. La inscripción quedará confirmada una vez se reciba el pago.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.blue[900],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Botón de enviar
            FilledButton.icon(
              onPressed: _isLoading ? null : _submitInscription,
              icon: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.send),
              label: Text(_isLoading ? 'Enviando...' : 'Enviar inscripción'),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.all(16),
              ),
            ),
            const SizedBox(height: 8),

            // Nota de campos obligatorios
            const Text(
              '* Campos obligatorios',
              style: TextStyle(fontSize: 12, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _participantNameController.dispose();
    _participantAgeController.dispose();
    _participantAllergiesController.dispose();
    _guardianNameController.dispose();
    _guardianEmailController.dispose();
    _guardianPhoneController.dispose();
    super.dispose();
  }
}
