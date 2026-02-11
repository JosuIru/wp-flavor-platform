import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Estado de las notificaciones
class NotificationSettings {
  final bool newReservations;
  final bool checkins;
  final bool cancellations;
  final bool dailySummary;
  final bool lowAvailability;
  final bool chatEscalations;

  NotificationSettings({
    this.newReservations = true,
    this.checkins = false,
    this.cancellations = true,
    this.dailySummary = true,
    this.lowAvailability = true,
    this.chatEscalations = true,
  });

  NotificationSettings copyWith({
    bool? newReservations,
    bool? checkins,
    bool? cancellations,
    bool? dailySummary,
    bool? lowAvailability,
    bool? chatEscalations,
  }) {
    return NotificationSettings(
      newReservations: newReservations ?? this.newReservations,
      checkins: checkins ?? this.checkins,
      cancellations: cancellations ?? this.cancellations,
      dailySummary: dailySummary ?? this.dailySummary,
      lowAvailability: lowAvailability ?? this.lowAvailability,
      chatEscalations: chatEscalations ?? this.chatEscalations,
    );
  }

  Map<String, bool> toMap() => {
        'newReservations': newReservations,
        'checkins': checkins,
        'cancellations': cancellations,
        'dailySummary': dailySummary,
        'lowAvailability': lowAvailability,
        'chatEscalations': chatEscalations,
      };

  factory NotificationSettings.fromMap(Map<String, bool> map) {
    return NotificationSettings(
      newReservations: map['newReservations'] ?? true,
      checkins: map['checkins'] ?? false,
      cancellations: map['cancellations'] ?? true,
      dailySummary: map['dailySummary'] ?? true,
      lowAvailability: map['lowAvailability'] ?? true,
      chatEscalations: map['chatEscalations'] ?? true,
    );
  }
}

/// Provider para las configuraciones de notificaciones
final notificationSettingsProvider =
    StateNotifierProvider<NotificationSettingsNotifier, NotificationSettings>((ref) {
  return NotificationSettingsNotifier();
});

class NotificationSettingsNotifier extends StateNotifier<NotificationSettings> {
  NotificationSettingsNotifier() : super(NotificationSettings()) {
    _loadSettings();
  }

  static const String _prefix = 'notification_';

  Future<void> _loadSettings() async {
    final prefs = await SharedPreferences.getInstance();
    final Map<String, bool> loadedSettings = {};

    for (final key in [
      'newReservations',
      'checkins',
      'cancellations',
      'dailySummary',
      'lowAvailability',
      'chatEscalations',
    ]) {
      loadedSettings[key] = prefs.getBool('$_prefix$key') ?? true;
    }

    state = NotificationSettings.fromMap(loadedSettings);
  }

  Future<void> _saveSettings() async {
    final prefs = await SharedPreferences.getInstance();
    final settings = state.toMap();

    for (final entry in settings.entries) {
      await prefs.setBool('$_prefix${entry.key}', entry.value);
    }
  }

  void setNewReservations(bool value) {
    state = state.copyWith(newReservations: value);
    _saveSettings();
  }

  void setCheckins(bool value) {
    state = state.copyWith(checkins: value);
    _saveSettings();
  }

  void setCancellations(bool value) {
    state = state.copyWith(cancellations: value);
    _saveSettings();
  }

  void setDailySummary(bool value) {
    state = state.copyWith(dailySummary: value);
    _saveSettings();
  }

  void setLowAvailability(bool value) {
    state = state.copyWith(lowAvailability: value);
    _saveSettings();
  }

  void setChatEscalations(bool value) {
    state = state.copyWith(chatEscalations: value);
    _saveSettings();
  }
}

/// Pantalla de configuracion de notificaciones
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(notificationSettingsProvider);
    final notifier = ref.read(notificationSettingsProvider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocalizations.of(context)!.notificationsTitle),
      ),
      body: ListView(
        children: [
          // Info sobre notificaciones push
          Container(
            padding: const EdgeInsets.all(16),
            child: Card(
              color: Theme.of(context).colorScheme.secondaryContainer,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(
                      Icons.notifications_active,
                      color: Theme.of(context).colorScheme.secondary,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppLocalizations.of(context)!.notificationsPushTitle,
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            AppLocalizations.of(context)!.notificationsPushDesc,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Seccion: Reservas
          _SectionHeader(title: AppLocalizations.of(context)!.notificationsSectionReservations),
          _NotificationTile(
            icon: Icons.event_available,
            title: AppLocalizations.of(context)!.notificationsNewReservationsTitle,
            subtitle: AppLocalizations.of(context)!.notificationsNewReservationsDesc,
            value: settings.newReservations,
            onChanged: notifier.setNewReservations,
          ),
          _NotificationTile(
            icon: Icons.check_circle,
            title: AppLocalizations.of(context)!.notificationsCheckinsTitle,
            subtitle: AppLocalizations.of(context)!.notificationsCheckinsDesc,
            value: settings.checkins,
            onChanged: notifier.setCheckins,
          ),
          _NotificationTile(
            icon: Icons.cancel,
            title: AppLocalizations.of(context)!.notificationsCancellationsTitle,
            subtitle: AppLocalizations.of(context)!.notificationsCancellationsDesc,
            value: settings.cancellations,
            onChanged: notifier.setCancellations,
          ),

          const Divider(height: 32),

          // Seccion: Resumenes
          _SectionHeader(title: AppLocalizations.of(context)!.notificationsSectionSummaries),
          _NotificationTile(
            icon: Icons.summarize,
            title: AppLocalizations.of(context)!.notificationsDailySummaryTitle,
            subtitle: AppLocalizations.of(context)!.notificationsDailySummaryDesc,
            value: settings.dailySummary,
            onChanged: notifier.setDailySummary,
          ),

          const Divider(height: 32),

          // Seccion: Alertas
          _SectionHeader(title: AppLocalizations.of(context)!.notificationsSectionAlerts),
          _NotificationTile(
            icon: Icons.warning_amber,
            title: AppLocalizations.of(context)!.notificationsLowAvailabilityTitle,
            subtitle: AppLocalizations.of(context)!.notificationsLowAvailabilityDesc,
            value: settings.lowAvailability,
            onChanged: notifier.setLowAvailability,
          ),
          _NotificationTile(
            icon: Icons.support_agent,
            title: AppLocalizations.of(context)!.notificationsChatEscalationsTitle,
            subtitle: AppLocalizations.of(context)!.notificationsChatEscalationsDesc,
            value: settings.chatEscalations,
            onChanged: notifier.setChatEscalations,
          ),

          const SizedBox(height: 32),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;

  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Text(
        title,
        style: Theme.of(context).textTheme.titleSmall?.copyWith(
              color: Theme.of(context).colorScheme.primary,
              fontWeight: FontWeight.bold,
            ),
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final Function(bool) onChanged;

  const _NotificationTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return SwitchListTile(
      secondary: Icon(
        icon,
        color: value
            ? Theme.of(context).colorScheme.primary
            : Theme.of(context).colorScheme.onSurface.withOpacity(0.5),
      ),
      title: Text(title),
      subtitle: Text(subtitle),
      value: value,
      onChanged: onChanged,
    );
  }
}
