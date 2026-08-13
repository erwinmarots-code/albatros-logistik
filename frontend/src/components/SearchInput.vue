<template>
  <div class="search-container">
    <i class="fas fa-search search-icon"></i>
    <input
      type="text"
      :placeholder="placeholder || 'Cari...'"
      v-model="searchQuery"
      @input="onSearch"
      class="search-input"
    />
    <button v-if="searchQuery" @click="clearSearch" class="search-clear">
      <i class="fas fa-times-circle"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue'

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Cari...'
  }
})

const emit = defineEmits(['search', 'clear'])

const searchQuery = ref('')

const onSearch = () => {
  emit('search', searchQuery.value)
}

const clearSearch = () => {
  searchQuery.value = ''
  emit('clear')
  emit('search', '')
}
</script>

<style scoped>
.search-container {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 250px;
}
.search-icon {
  position: absolute;
  left: 12px;
  color: #9ca3af;
  font-size: 14px;
}
.search-input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}
.search-input:focus {
  outline: none;
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26, 74, 122, 0.12);
}
.search-clear {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  font-size: 16px;
}
.search-clear:hover {
  color: #ef4444;
}
@media (max-width: 640px) {
  .search-container { width: 100%; }
}
</style>