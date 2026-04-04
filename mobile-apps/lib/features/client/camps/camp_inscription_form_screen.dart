import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

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
  AppLocalizations get i18n => AppLocalizations.of(context);
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
      FlavorSnackbar.showInfo(context, i18n.campInscriptionAcceptTermsError);
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
          title: Text(i18n.campInscriptionSuccessTitle),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                i18n.campInscriptionSuccessMessage,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                i18n.campInscriptionSuccessEmailNote,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 12),
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
              child: Text(i18n.commonAccept),
            ),
          ],
        ),
      );
    } else if (mounted) {
      FlavorSnackbar.showError(context, response.error ?? i18n.campInscriptionCreateError);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.campInscriptionTitle),
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
              i18n.campInscriptionParticipantSection,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _participantNameController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionFullNameRequired,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.person),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campInscriptionNameRequiredError;
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _participantAgeController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionAgeRequired,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.cake),
                suffixText: i18n.campInscriptionYearsSuffix,
              ),
              keyboardType: TextInputType.number,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campInscriptionAgeRequiredError;
                }
                final age = int.tryParse(value);
                if (age == null || age <= 0) {
                  return i18n.campInscriptionAgeInvalidError;
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _participantAllergiesController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionAllergiesLabel,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.medical_services),
                helperText: i18n.campInscriptionAllergiesHelper,
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 32),

            // Datos del responsable
            Text(
              i18n.campInscriptionGuardianSection,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianNameController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionFullNameRequired,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.person_outline),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campInscriptionGuardianNameRequiredError;
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianEmailController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionEmailRequired,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.email),
              ),
              keyboardType: TextInputType.emailAddress,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campInscriptionEmailRequiredError;
                }
                if (!value.contains('@')) {
                  return i18n.campInscriptionEmailInvalidError;
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _guardianPhoneController,
              decoration: InputDecoration(
                labelText: i18n.campInscriptionPhoneRequired,
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.phone),
                helperText: i18n.campInscriptionPhoneHelper,
              ),
              keyboardType: TextInputType.phone,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campInscriptionPhoneRequiredError;
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
                title: Text(i18n.campInscriptionTermsTitle),
                subtitle: Text(
                  i18n.campInscriptionTermsSubtitle,
                  style: const TextStyle(fontSize: 12),
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
                        i18n.campInscriptionPaymentInfoTitle,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    i18n.campInscriptionPaymentInfoBody,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Colors.blue,
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
                  ? const FlavorInlineSpinner(color: Colors.white)
                  : const Icon(Icons.send),
              label: Text(_isLoading ? i18n.commonSending : i18n.campInscriptionSubmit),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.all(16),
              ),
            ),
            const SizedBox(height: 8),

            // Nota de campos obligatorios
            Text(
              i18n.campInscriptionRequiredNote,
              style: const TextStyle(fontSize: 12, color: Colors.grey),
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
