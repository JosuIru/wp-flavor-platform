<template>
  <Modal
    :title="`${componentLabel} - Variantes`"
    size="large"
    @close="handleClose"
  >
    <div class="variant-modal-content">
      <!-- Variantes -->
      <div v-if="variants.length > 0" class="variants-section">
        <h4 class="section-title">Variantes</h4>
        <div class="variants-grid">
          <div
            v-for="variant in variants"
            :key="variant.key"
            class="variant-card"
            :class="{ 'is-selected': selectedVariant === variant.key }"
            @click="selectVariant(variant.key)"
          >
            <div class="variant-preview" v-if="variant.preview">
              <img :src="variant.preview" :alt="variant.label" />
            </div>
            <div v-else class="variant-placeholder">
              <span class="dashicons" :class="componentIcon"></span>
            </div>
            <div class="variant-info">
              <span class="variant-name">{{ variant.label }}</span>
              <span class="variant-description" v-if="variant.description">
                {{ variant.description }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Presets -->
      <div v-if="presets.length > 0" class="presets-section">
        <h4 class="section-title">Presets</h4>
        <div class="presets-list">
          <button
            v-for="preset in presets"
            :key="preset.key"
            class="preset-btn"
            :class="{ 'is-selected': selectedPreset === preset.key }"
            @click="selectPreset(preset.key)"
          >
            <span class="preset-name">{{ preset.label }}</span>
          </button>
        </div>
      </div>

      <!-- Opción en blanco -->
      <div class="blank-option">
        <button
          class="blank-btn"
          :class="{ 'is-selected': !selectedVariant && !selectedPreset }"
          @click="selectBlank"
        >
          <span class="dashicons dashicons-plus-alt"></span>
          <span>Añadir en blanco</span>
        </button>
      </div>
    </div>

    <template #footer>
      <button class="button" @click="handleClose">Cancelar</button>
      <button class="button button-primary" @click="handleConfirm">
        Añadir componente
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useComponentDefs } from '../../composables/useComponentDefs';
import Modal from '../ui/Modal.vue';

const props = defineProps({
  componentId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['select', 'close']);

const builderStore = useBuilderStore();
const { getComponentDef, getComponentVariants, getComponentPresets, getDefaultValues, getPresetValues } = useComponentDefs();

// Local state
const selectedVariant = ref(null);
const selectedPreset = ref(null);

// Computed
const componentDef = computed(() => getComponentDef(props.componentId));
const componentLabel = computed(() => componentDef.value?.label || props.componentId);
const componentIcon = computed(() => componentDef.value?.icon || 'dashicons-layout');
const variants = computed(() => getComponentVariants(props.componentId));
const presets = computed(() => getComponentPresets(props.componentId));

// Methods
function selectVariant(variantKey) {
  selectedVariant.value = variantKey;
  selectedPreset.value = null;
}

function selectPreset(presetKey) {
  selectedPreset.value = presetKey;
  selectedVariant.value = null;
}

function selectBlank() {
  selectedVariant.value = null;
  selectedPreset.value = null;
}

function handleClose() {
  emit('close');
}

function handleConfirm() {
  let values = getDefaultValues(props.componentId);

  if (selectedPreset.value) {
    values = { ...values, ...getPresetValues(props.componentId, selectedPreset.value) };
  }

  emit('select', {
    componentId: props.componentId,
    variant: selectedVariant.value,
    values,
  });
}
</script>

<style scoped>
.variant-modal-content {
  padding: 20px;
}

.section-title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: var(--pb-text);
}

/* Variants grid */
.variants-section {
  margin-bottom: 24px;
}

.variants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
}

.variant-card {
  padding: 12px;
  border: 2px solid var(--pb-border-light);
  border-radius: var(--pb-radius-lg);
  cursor: pointer;
  transition: all 0.2s;
}

.variant-card:hover {
  border-color: var(--pb-border);
}

.variant-card.is-selected {
  border-color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
}

.variant-preview {
  aspect-ratio: 16 / 10;
  border-radius: var(--pb-radius);
  overflow: hidden;
  margin-bottom: 10px;
  background: var(--pb-bg);
}

.variant-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.variant-placeholder {
  aspect-ratio: 16 / 10;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--pb-bg);
  border-radius: var(--pb-radius);
  margin-bottom: 10px;
}

.variant-placeholder .dashicons {
  font-size: 32px;
  width: 32px;
  height: 32px;
  color: var(--pb-text-muted);
}

.variant-info {
  text-align: center;
}

.variant-name {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--pb-text);
}

.variant-description {
  display: block;
  font-size: 11px;
  color: var(--pb-text-muted);
  margin-top: 4px;
}

/* Presets */
.presets-section {
  margin-bottom: 24px;
}

.presets-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.preset-btn {
  padding: 8px 16px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  transition: all 0.2s;
}

.preset-btn:hover {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
}

.preset-btn.is-selected {
  background: var(--pb-primary);
  border-color: var(--pb-primary);
  color: white;
}

.preset-name {
  font-size: 13px;
}

/* Blank option */
.blank-option {
  border-top: 1px solid var(--pb-border-light);
  padding-top: 20px;
}

.blank-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: transparent;
  border: 2px dashed var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  color: var(--pb-text-light);
  transition: all 0.2s;
}

.blank-btn:hover {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
}

.blank-btn.is-selected {
  border-color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
  color: var(--pb-primary);
}
</style>
