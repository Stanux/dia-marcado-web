<template>
  <div class="grid-size-control">
    <label class="control-label">
      <svg 
        xmlns="http://www.w3.org/2000/svg" 
        fill="none" 
        viewBox="0 0 24 24" 
        stroke-width="1.5" 
        stroke="currentColor" 
        class="icon"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" 
        />
      </svg>
      Colunas
    </label>
    
    <div class="slider-container">
      <input
        type="range"
        min="4"
        max="8"
        step="1"
        :value="modelValue"
        @input="handleInput"
        class="slider"
      />
      <span class="value-display">{{ modelValue }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * GridSizeControl Component
 * 
 * Controle deslizante para ajustar o número de colunas do grid de fotos.
 * Permite escolher entre 4 e 8 colunas.
 */

interface Props {
  modelValue: number;
}

interface Emits {
  (e: 'update:modelValue', value: number): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

function handleInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', parseInt(target.value));
}
</script>

<style scoped>
.grid-size-control {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 1rem;
  background-color: white;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.control-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
}

.slider-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.slider {
  width: 120px;
  height: 6px;
  border-radius: 3px;
  background: linear-gradient(to right, #3b82f6 0%, #3b82f6 var(--value), #e5e7eb var(--value), #e5e7eb 100%);
  outline: none;
  -webkit-appearance: none;
  appearance: none;
  cursor: pointer;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease-in-out;
}

.slider::-webkit-slider-thumb:hover {
  background: #2563eb;
  transform: scale(1.1);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease-in-out;
}

.slider::-moz-range-thumb:hover {
  background: #2563eb;
  transform: scale(1.1);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.value-display {
  font-size: 0.875rem;
  font-weight: 600;
  color: #3b82f6;
  min-width: 1.5rem;
  text-align: center;
}

/* Mobile adjustments */
@media (max-width: 640px) {
  .grid-size-control {
    padding: 0.375rem 0.75rem;
    gap: 0.75rem;
  }

  .control-label {
    font-size: 0.8125rem;
  }

  .icon {
    width: 1rem;
    height: 1rem;
  }

  .slider {
    width: 80px;
  }

  .value-display {
    font-size: 0.8125rem;
  }
}
</style>
