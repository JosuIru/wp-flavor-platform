<template>
  <div class="flavor-pb-modal-overlay" @click.self="$emit('close')">
    <div class="flavor-pb-modal section-settings-modal">
      <div class="modal-header">
        <h3>Configuracion de Seccion</h3>
        <button class="modal-close" @click="$emit('close')" title="Cerrar">
          <span class="dashicons dashicons-no-alt"></span>
        </button>
      </div>

      <div class="modal-body" v-if="section">
        <!-- Ancho completo -->
        <div class="settings-field">
          <label class="field-label">
            <input
              type="checkbox"
              v-model="localSettings.fullWidth"
            />
            Ancho completo
          </label>
          <p class="field-description">
            Extender la seccion al ancho completo de la pantalla
          </p>
        </div>

        <!-- Color de fondo -->
        <div class="settings-field">
          <label class="field-label">Color de fondo</label>
          <div class="color-field">
            <input
              type="color"
              v-model="localSettings.backgroundColor"
              class="color-picker"
            />
            <input
              type="text"
              v-model="localSettings.backgroundColor"
              placeholder="#ffffff o transparent"
              class="color-input"
            />
            <button
              v-if="localSettings.backgroundColor"
              class="color-clear"
              @click="localSettings.backgroundColor = ''"
              title="Quitar color"
            >
              <span class="dashicons dashicons-no-alt"></span>
            </button>
          </div>
        </div>

        <!-- Padding superior -->
        <div class="settings-field">
          <label class="field-label">Espaciado superior</label>
          <div class="spacing-field">
            <input
              type="text"
              v-model="localSettings.paddingTop"
              placeholder="ej: 40px, 2rem"
              class="spacing-input"
            />
            <div class="spacing-presets">
              <button
                v-for="preset in spacingPresets"
                :key="preset.value"
                class="preset-btn"
                :class="{ active: localSettings.paddingTop === preset.value }"
                @click="localSettings.paddingTop = preset.value"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Padding inferior -->
        <div class="settings-field">
          <label class="field-label">Espaciado inferior</label>
          <div class="spacing-field">
            <input
              type="text"
              v-model="localSettings.paddingBottom"
              placeholder="ej: 40px, 2rem"
              class="spacing-input"
            />
            <div class="spacing-presets">
              <button
                v-for="preset in spacingPresets"
                :key="preset.value"
                class="preset-btn"
                :class="{ active: localSettings.paddingBottom === preset.value }"
                @click="localSettings.paddingBottom = preset.value"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Clase CSS personalizada -->
        <div class="settings-field">
          <label class="field-label">Clase CSS personalizada</label>
          <input
            type="text"
            v-model="localSettings.customClass"
            placeholder="mi-seccion-especial"
            class="text-input"
          />
          <p class="field-description">
            Agregar clases CSS personalizadas a la seccion
          </p>
        </div>

        <!-- ID de seccion -->
        <div class="settings-field">
          <label class="field-label">ID de seccion (ancla)</label>
          <input
            type="text"
            v-model="localSettings.sectionId"
            placeholder="mi-seccion"
            class="text-input"
          />
          <p class="field-description">
            ID para enlaces ancla (ej: #mi-seccion)
          </p>
        </div>
      </div>

      <div class="modal-footer">
        <button class="button" @click="$emit('close')">Cancelar</button>
        <button class="button button-primary" @click="saveSettings">
          Guardar cambios
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';

const props = defineProps({
  sectionId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['close']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();

// Presets de espaciado
const spacingPresets = [
  { label: 'Ninguno', value: '' },
  { label: 'S', value: '20px' },
  { label: 'M', value: '40px' },
  { label: 'L', value: '60px' },
  { label: 'XL', value: '80px' },
];

// Obtener seccion
const section = computed(() => builderStore.getSectionById(props.sectionId));

// Settings locales para edicion
const localSettings = ref({
  fullWidth: false,
  backgroundColor: '',
  paddingTop: '',
  paddingBottom: '',
  customClass: '',
  sectionId: '',
});

// Cargar settings de la seccion al montar
onMounted(() => {
  if (section.value?.settings) {
    localSettings.value = {
      fullWidth: section.value.settings.fullWidth || false,
      backgroundColor: section.value.settings.backgroundColor || '',
      paddingTop: section.value.settings.paddingTop || '',
      paddingBottom: section.value.settings.paddingBottom || '',
      customClass: section.value.settings.customClass || '',
      sectionId: section.value.settings.sectionId || '',
    };
  }
});

function saveSettings() {
  if (!section.value) return;

  builderStore.updateSectionSettings(props.sectionId, {
    fullWidth: localSettings.value.fullWidth,
    backgroundColor: localSettings.value.backgroundColor,
    paddingTop: localSettings.value.paddingTop,
    paddingBottom: localSettings.value.paddingBottom,
    customClass: localSettings.value.customClass,
    sectionId: localSettings.value.sectionId,
  });

  uiStore.showSuccess('Configuracion de seccion guardada');
  emit('close');
}
</script>

<style scoped>
.flavor-pb-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100000;
}

.flavor-pb-modal {
  background: var(--pb-bg-light, #fff);
  border-radius: var(--pb-radius-lg, 8px);
  box-shadow: var(--pb-shadow-lg, 0 4px 12px rgba(0,0,0,0.15));
  max-width: 90vw;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.section-settings-modal {
  width: 480px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--pb-border, #c3c4c7);
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: var(--pb-text-muted, #787c82);
  border-radius: 4px;
}

.modal-close:hover {
  background: var(--pb-bg, #f0f0f1);
  color: var(--pb-text, #1d2327);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

/* Settings fields */
.settings-field {
  margin-bottom: 20px;
}

.settings-field:last-child {
  margin-bottom: 0;
}

.field-label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  font-size: 13px;
}

.field-description {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--pb-text-muted, #787c82);
}

/* Color field */
.color-field {
  display: flex;
  gap: 8px;
  align-items: center;
}

.color-picker {
  width: 40px;
  height: 32px;
  padding: 2px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  cursor: pointer;
}

.color-input {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  font-size: 13px;
}

.color-clear {
  padding: 4px 8px;
  background: none;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  cursor: pointer;
  color: var(--pb-text-muted, #787c82);
}

.color-clear:hover {
  background: var(--pb-bg, #f0f0f1);
  color: var(--pb-error, #dc3232);
}

/* Spacing field */
.spacing-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.spacing-input {
  padding: 8px 10px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  font-size: 13px;
}

.spacing-presets {
  display: flex;
  gap: 4px;
}

.preset-btn {
  padding: 4px 12px;
  font-size: 12px;
  background: var(--pb-bg, #f0f0f1);
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  cursor: pointer;
  transition: all 0.2s;
}

.preset-btn:hover {
  background: var(--pb-bg-light, #fff);
  border-color: var(--pb-primary, #0073aa);
}

.preset-btn.active {
  background: var(--pb-primary, #0073aa);
  color: white;
  border-color: var(--pb-primary, #0073aa);
}

/* Text input */
.text-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  font-size: 13px;
}

.text-input:focus,
.spacing-input:focus,
.color-input:focus {
  outline: none;
  border-color: var(--pb-primary, #0073aa);
  box-shadow: 0 0 0 1px var(--pb-primary, #0073aa);
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--pb-border, #c3c4c7);
  background: var(--pb-bg, #f0f0f1);
}

.modal-footer .button {
  padding: 8px 16px;
}
</style>
