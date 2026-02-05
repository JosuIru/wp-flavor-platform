<template>
  <div class="tooltip" :style="positionStyles">
    <div class="tooltip-content">
      {{ content }}
    </div>
    <div class="tooltip-arrow"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  content: {
    type: String,
    required: true,
  },
  x: {
    type: Number,
    default: 0,
  },
  y: {
    type: Number,
    default: 0,
  },
});

const positionStyles = computed(() => ({
  left: `${props.x}px`,
  top: `${props.y}px`,
}));
</script>

<style scoped>
.tooltip {
  position: fixed;
  z-index: 100003;
  pointer-events: none;
  animation: tooltipFadeIn 0.15s ease;
}

@keyframes tooltipFadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tooltip-content {
  padding: 6px 10px;
  background: #1d2327;
  color: white;
  font-size: 12px;
  border-radius: 4px;
  white-space: nowrap;
  max-width: 250px;
}

.tooltip-arrow {
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #1d2327;
}
</style>
