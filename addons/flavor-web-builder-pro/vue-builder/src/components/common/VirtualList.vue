<template>
  <div
    ref="containerRef"
    class="virtual-list-container"
    :style="containerStyle"
    @scroll="onScroll"
  >
    <div class="virtual-list-spacer" :style="spacerStyle">
      <div
        v-for="item in visibleItems"
        :key="item._virtualKey"
        class="virtual-list-item"
        :style="{ height: itemHeight + 'px' }"
      >
        <slot :item="item._originalItem" :index="item._virtualIndex" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  /**
   * Array de items a renderizar
   */
  items: {
    type: Array,
    required: true,
  },
  /**
   * Altura de cada item en píxeles
   */
  itemHeight: {
    type: Number,
    default: 60,
  },
  /**
   * Altura del contenedor (CSS)
   */
  height: {
    type: String,
    default: '400px',
  },
  /**
   * Número de items extra a renderizar fuera del viewport (buffer)
   */
  buffer: {
    type: Number,
    default: 5,
  },
  /**
   * Key única para cada item (función o nombre de propiedad)
   */
  itemKey: {
    type: [String, Function],
    default: 'id',
  },
});

const containerRef = ref(null);
const scrollTop = ref(0);

// Calcular qué items son visibles
const visibleItems = computed(() => {
  if (!props.items.length) return [];

  const containerHeight = containerRef.value?.clientHeight || parseInt(props.height);
  const totalItems = props.items.length;

  // Calcular rango visible
  const startIndex = Math.max(0, Math.floor(scrollTop.value / props.itemHeight) - props.buffer);
  const visibleCount = Math.ceil(containerHeight / props.itemHeight) + props.buffer * 2;
  const endIndex = Math.min(totalItems, startIndex + visibleCount);

  // Crear array de items visibles con metadata
  const visible = [];
  for (let i = startIndex; i < endIndex; i++) {
    const item = props.items[i];
    const key = typeof props.itemKey === 'function'
      ? props.itemKey(item)
      : item[props.itemKey];

    visible.push({
      _virtualKey: key || i,
      _virtualIndex: i,
      _originalItem: item,
    });
  }

  return visible;
});

// Estilo del contenedor
const containerStyle = computed(() => ({
  height: props.height,
  overflow: 'auto',
  position: 'relative',
}));

// Estilo del espaciador (para scroll correcto)
const spacerStyle = computed(() => {
  const totalHeight = props.items.length * props.itemHeight;
  const offsetTop = visibleItems.value.length > 0
    ? visibleItems.value[0]._virtualIndex * props.itemHeight
    : 0;

  return {
    height: `${totalHeight}px`,
    paddingTop: `${offsetTop}px`,
    boxSizing: 'border-box',
  };
});

// Handler de scroll con throttle
let scrollThrottleTimer = null;
function onScroll(event) {
  if (scrollThrottleTimer) return;

  scrollThrottleTimer = setTimeout(() => {
    scrollTop.value = event.target.scrollTop;
    scrollThrottleTimer = null;
  }, 16); // ~60fps
}

// Scroll programático a un índice
function scrollToIndex(index) {
  if (containerRef.value) {
    containerRef.value.scrollTop = index * props.itemHeight;
  }
}

// Exponer métodos
defineExpose({
  scrollToIndex,
  getScrollTop: () => scrollTop.value,
});

// Limpiar timer al desmontar
onUnmounted(() => {
  if (scrollThrottleTimer) {
    clearTimeout(scrollThrottleTimer);
  }
});
</script>

<style scoped>
.virtual-list-container {
  will-change: scroll-position;
}

.virtual-list-spacer {
  position: relative;
}

.virtual-list-item {
  position: relative;
  overflow: hidden;
}
</style>
