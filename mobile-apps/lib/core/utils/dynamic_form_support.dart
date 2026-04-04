import 'package:flutter/material.dart';

class DynamicPickedValue {
  final String displayValue;
  final String submitValue;

  const DynamicPickedValue({
    required this.displayValue,
    required this.submitValue,
  });
}

class DynamicFormSupport {
  const DynamicFormSupport._();

  static bool parseBool(dynamic value) {
    if (value is bool) return value;
    final normalized = value?.toString().toLowerCase().trim();
    return normalized == '1' ||
        normalized == 'true' ||
        normalized == 'yes' ||
        normalized == 'on';
  }

  static Future<DynamicPickedValue?> pickDate(BuildContext context) async {
    final pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );

    if (pickedDate == null) return null;

    final yyyy = pickedDate.year.toString().padLeft(4, '0');
    final mm = pickedDate.month.toString().padLeft(2, '0');
    final dd = pickedDate.day.toString().padLeft(2, '0');

    return DynamicPickedValue(
      displayValue: '$dd/$mm/$yyyy',
      submitValue: '$yyyy-$mm-$dd',
    );
  }

  static Future<DynamicPickedValue?> pickTime(BuildContext context) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked == null) return null;

    final value =
        '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';

    return DynamicPickedValue(
      displayValue: value,
      submitValue: value,
    );
  }

  static Future<DynamicPickedValue?> pickDateTime(BuildContext context) async {
    final pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );

    if (pickedDate == null || !context.mounted) return null;

    final pickedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );

    if (pickedTime == null) return null;

    final yyyy = pickedDate.year.toString().padLeft(4, '0');
    final mm = pickedDate.month.toString().padLeft(2, '0');
    final dd = pickedDate.day.toString().padLeft(2, '0');
    final hh = pickedTime.hour.toString().padLeft(2, '0');
    final min = pickedTime.minute.toString().padLeft(2, '0');
    final value = '$yyyy-$mm-$dd $hh:$min';

    return DynamicPickedValue(
      displayValue: value,
      submitValue: value,
    );
  }
}
