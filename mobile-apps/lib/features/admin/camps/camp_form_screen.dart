import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';

/// Pantalla para crear o editar un campamento
class CampFormScreen extends ConsumerStatefulWidget {
  final Camp? camp; // null = crear nuevo, no null = editar

  const CampFormScreen({
    super.key,
    this.camp,
  });

  @override
  ConsumerState<CampFormScreen> createState() => _CampFormScreenState();
}

class _CampFormScreenState extends ConsumerState<CampFormScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _excerptController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController();
  final _priceTotalController = TextEditingController();
  final _durationController = TextEditingController();
  final _labelController = TextEditingController();
  final _scheduleController = TextEditingController();
  final _locationController = TextEditingController();
  final _includesController = TextEditingController();
  final _requirementsController = TextEditingController();

  bool _isLoading = false;
  bool _inscriptionClosed = false;
  String? _startDate;
  String? _endDate;

  // Taxonomías
  List<CampTerm> _availableCategories = [];
  List<CampTerm> _availableAges = [];
  List<CampTerm> _availableLanguages = [];

  List<int> _selectedCategoryIds = [];
  List<int> _selectedAgeIds = [];
  List<int> _selectedLanguageIds = [];

  @override
  void initState() {
    super.initState();
    _loadTaxonomies();

    // Si estamos editando, cargar datos
    if (widget.camp != null) {
      _titleController.text = widget.camp!.title;
      _excerptController.text = widget.camp!.excerpt;
      _descriptionController.text = widget.camp!.description ?? '';
      _priceController.text = widget.camp!.price.toString();
      _priceTotalController.text = widget.camp!.priceTotal.toString();
      _durationController.text = widget.camp!.duration;
      _labelController.text = widget.camp!.label ?? '';
      _inscriptionClosed = widget.camp!.isClosed;

      if (widget.camp!.dates != null) {
        _startDate = widget.camp!.dates!.start;
        _endDate = widget.camp!.dates!.end;
      }

      // Precargamos IDs de taxonomías seleccionadas
      // (los obtendremos de la API de taxonomías después)
    }
  }

  Future<void> _loadTaxonomies() async {
    final api = ref.read(apiClientProvider);
    final response = await api.getCampTaxonomies();

    if (response.success && response.data != null) {
      final taxonomies = response.data!['taxonomies'];

      setState(() {
        _availableCategories = (taxonomies['categories'] as List?)
                ?.map((t) => CampTerm(
                      slug: t['slug'],
                      name: t['name'],
                    ))
                .toList() ??
            [];

        _availableAges = (taxonomies['ages'] as List?)
                ?.map((t) => CampTerm(
                      slug: t['slug'],
                      name: t['name'],
                    ))
                .toList() ??
            [];

        _availableLanguages = (taxonomies['languages'] as List?)
                ?.map((t) => CampTerm(
                      slug: t['slug'],
                      name: t['name'],
                    ))
                .toList() ??
            [];

        // Si estamos editando, marcar taxonomías seleccionadas
        if (widget.camp != null) {
          // Matching por slug
          for (final cat in widget.camp!.categories) {
            final match = _availableCategories
                .indexWhere((c) => c.slug == cat.slug);
            if (match != -1) {
              _selectedCategoryIds.add(match); // Usamos índice como ID temporal
            }
          }
          for (final age in widget.camp!.ages) {
            final match = _availableAges.indexWhere((a) => a.slug == age.slug);
            if (match != -1) {
              _selectedAgeIds.add(match);
            }
          }
          for (final lang in widget.camp!.languages) {
            final match =
                _availableLanguages.indexWhere((l) => l.slug == lang.slug);
            if (match != -1) {
              _selectedLanguageIds.add(match);
            }
          }
        }
      });
    }
  }

  Future<void> _saveCamp() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);

    final data = {
      'title': _titleController.text.trim(),
      'excerpt': _excerptController.text.trim(),
      'description': _descriptionController.text.trim(),
      'price': double.tryParse(_priceController.text) ?? 0.0,
      'price_total': double.tryParse(_priceTotalController.text) ?? 0.0,
      'duration': _durationController.text.trim(),
      'label': _labelController.text.trim(),
      'inscription_closed': _inscriptionClosed,
      'start_date': _startDate,
      'end_date': _endDate,
      'schedule': _scheduleController.text.trim(),
      'location': _locationController.text.trim(),
      'includes': _includesController.text.trim(),
      'requirements': _requirementsController.text.trim(),
      'category_ids': _selectedCategoryIds,
      'age_ids': _selectedAgeIds,
      'language_ids': _selectedLanguageIds,
    };

    final ApiResponse<Map<String, dynamic>> response;

    if (widget.camp == null) {
      // Crear nuevo
      response = await api.createCamp(
        title: data['title'] as String,
        description: data['description'] as String?,
        excerpt: data['excerpt'] as String?,
        price: data['price'] as double?,
        priceTotal: data['price_total'] as double?,
        duration: data['duration'] as String?,
        label: data['label'] as String?,
        inscriptionClosed: data['inscription_closed'] as bool,
        startDate: data['start_date'] as String?,
        endDate: data['end_date'] as String?,
        schedule: data['schedule'] as String?,
        location: data['location'] as String?,
        includes: data['includes'] as String?,
        requirements: data['requirements'] as String?,
        categoryIds: data['category_ids'] as List<int>?,
        ageIds: data['age_ids'] as List<int>?,
        languageIds: data['language_ids'] as List<int>?,
      );
    } else {
      // Actualizar existente
      response = await api.updateCamp(
        campId: widget.camp!.id,
        title: data['title'] as String?,
        description: data['description'] as String?,
        excerpt: data['excerpt'] as String?,
        price: data['price'] as double?,
        priceTotal: data['price_total'] as double?,
        duration: data['duration'] as String?,
        label: data['label'] as String?,
        inscriptionClosed: data['inscription_closed'] as bool?,
        startDate: data['start_date'] as String?,
        endDate: data['end_date'] as String?,
        schedule: data['schedule'] as String?,
        location: data['location'] as String?,
        includes: data['includes'] as String?,
        requirements: data['requirements'] as String?,
        categoryIds: data['category_ids'] as List<int>?,
        ageIds: data['age_ids'] as List<int>?,
        languageIds: data['language_ids'] as List<int>?,
      );
    }

    setState(() => _isLoading = false);

    if (response.success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(widget.camp == null
              ? i18n.campFormCreatedSuccess
              : i18n.campFormUpdatedSuccess),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.of(context).pop(true); // Volver con resultado exitoso
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response.error ?? i18n.campFormSaveError),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEditing = widget.camp != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(isEditing ? i18n.campFormEditTitle : i18n.campFormNewTitle),
        actions: [
          if (_isLoading)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16.0),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else
            IconButton(
              icon: const Icon(Icons.save),
              onPressed: _saveCamp,
              tooltip: i18n.commonSave,
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Título
            TextFormField(
              controller: _titleController,
              decoration: InputDecoration(
                labelText: i18n.campFormTitleLabel,
                border: const OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return i18n.campFormTitleRequired;
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Extracto
            TextFormField(
              controller: _excerptController,
              decoration: InputDecoration(
                labelText: i18n.campFormExcerptLabel,
                border: const OutlineInputBorder(),
                helperText: i18n.campFormExcerptHelper,
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descriptionController,
              decoration: InputDecoration(
                labelText: i18n.campFormDescriptionLabel,
                border: const OutlineInputBorder(),
              ),
              maxLines: 5,
            ),
            const SizedBox(height: 24),

            // Sección de precios
            Text(
              i18n.campFormPricesSection,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _priceController,
                    decoration: InputDecoration(
                      labelText: i18n.campFormPriceInscriptionLabel,
                      border: const OutlineInputBorder(),
                      suffixText: '€',
                    ),
                    keyboardType: TextInputType.number,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _priceTotalController,
                    decoration: InputDecoration(
                      labelText: i18n.campFormPriceTotalLabel,
                      border: const OutlineInputBorder(),
                      suffixText: '€',
                    ),
                    keyboardType: TextInputType.number,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Duración y etiqueta
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _durationController,
                    decoration: InputDecoration(
                      labelText: i18n.campFormDurationLabel,
                      border: const OutlineInputBorder(),
                      helperText: i18n.campFormDurationHelper,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _labelController,
                    decoration: InputDecoration(
                      labelText: i18n.campFormLabelLabel,
                      border: const OutlineInputBorder(),
                      helperText: i18n.campFormLabelHelper,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Estado de inscripción
            SwitchListTile(
              title: Text(i18n.campFormInscriptionClosedTitle),
              subtitle: Text(i18n.campFormInscriptionClosedSubtitle),
              value: _inscriptionClosed,
              onChanged: (value) {
                setState(() => _inscriptionClosed = value);
              },
            ),
            const SizedBox(height: 24),

            // Fechas
            Text(
              i18n.campFormDatesSection,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: ListTile(
                    title: Text(i18n.campFormStartDateLabel),
                    subtitle: Text(_startDate ?? i18n.campFormNotSelected),
                    trailing: const Icon(Icons.calendar_today),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime.now(),
                        lastDate: DateTime.now().add(const Duration(days: 365)),
                      );
                      if (date != null) {
                        setState(() {
                          _startDate = date.toIso8601String().split('T')[0];
                        });
                      }
                    },
                  ),
                ),
                Expanded(
                  child: ListTile(
                    title: Text(i18n.campFormEndDateLabel),
                    subtitle: Text(_endDate ?? i18n.campFormNotSelected),
                    trailing: const Icon(Icons.calendar_today),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime.now(),
                        lastDate: DateTime.now().add(const Duration(days: 365)),
                      );
                      if (date != null) {
                        setState(() {
                          _endDate = date.toIso8601String().split('T')[0];
                        });
                      }
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Información adicional
            Text(
              i18n.campFormAdditionalInfoSection,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            TextFormField(
              controller: _scheduleController,
              decoration: InputDecoration(
                labelText: i18n.campFormScheduleLabel,
                border: const OutlineInputBorder(),
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _locationController,
              decoration: InputDecoration(
                labelText: i18n.campFormLocationLabel,
                border: const OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _includesController,
              decoration: InputDecoration(
                labelText: i18n.campFormIncludesLabel,
                border: const OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _requirementsController,
              decoration: InputDecoration(
                labelText: i18n.campFormRequirementsLabel,
                border: const OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 24),

            // Taxonomías
            Text(
              i18n.campFormCategorizationSection,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            _buildTaxonomySection(
              i18n.campFormCategories,
              _availableCategories,
              _selectedCategoryIds,
            ),
            const SizedBox(height: 16),
            _buildTaxonomySection(
              i18n.campFormAges,
              _availableAges,
              _selectedAgeIds,
            ),
            const SizedBox(height: 16),
            _buildTaxonomySection(
              i18n.campFormLanguages,
              _availableLanguages,
              _selectedLanguageIds,
            ),
            const SizedBox(height: 32),

            // Botón guardar
            FilledButton.icon(
              onPressed: _isLoading ? null : _saveCamp,
              icon: const Icon(Icons.save),
              label: Text(isEditing ? i18n.actualizar2e7be1 : i18n.commonCreate),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.all(16),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTaxonomySection(
    String title,
    List<CampTerm> terms,
    List<int> selectedIds,
  ) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: terms.asMap().entries.map((entry) {
                final index = entry.key;
                final term = entry.value;
                final isSelected = selectedIds.contains(index);

                return FilterChip(
                  label: Text(term.name),
                  selected: isSelected,
                  onSelected: (selected) {
                    setState(() {
                      if (selected) {
                        selectedIds.add(index);
                      } else {
                        selectedIds.remove(index);
                      }
                    });
                  },
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _titleController.dispose();
    _excerptController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    _priceTotalController.dispose();
    _durationController.dispose();
    _labelController.dispose();
    _scheduleController.dispose();
    _locationController.dispose();
    _includesController.dispose();
    _requirementsController.dispose();
    super.dispose();
  }
}
