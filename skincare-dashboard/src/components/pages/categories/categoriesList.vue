<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Categories</h1>
        <p class="text-sm text-gray-500">Manage categories</p>
      </div>
      <button 
        @click="showCreate = true"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
      >
        <span>+</span> Add New Category
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
      <input 
        v-model="query" 
        type="text" 
        placeholder="Search categories..." 
        class="border border-gray-200 rounded-lg px-4 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-emerald-400"
      />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-emerald-50 text-emerald-900 font-semibold border-b border-emerald-100">
          <tr>
            <th class="p-4">ID</th>
            <th class="p-4">Name</th>
            <th class="p-4">Icon</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtered" :key="c.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="p-4 font-mono text-xs text-gray-500">#{{ c.id }}</td>
            <td class="p-4 font-bold text-gray-900 text-base">{{ c.name }}</td>
            <td class="p-4 text-gray-600">
              <img v-if="c.icon?.startsWith('http')" :src="c.icon" :alt="`${c.name} icon`" class="category-icon" />
              <span v-else>{{ c.icon || "—" }}</span>
            </td>
            <td class="p-4 text-right space-x-3">
              <button @click="$emit('edit-category', c)" class="text-emerald-600 hover:text-emerald-800 text-xs font-medium">Edit</button>
              <button @click="confirmDelete(c)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
            </td>
          </tr>
          <tr v-if="filtered.length === 0">
            <td colspan="4" class="p-8 text-center text-gray-500">
              No categories found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Category Create Modal -->
    <CategoryCreate
      v-model="showCreate"
      @create-category="handleCreate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import CategoryCreate from "./categoriesCreate.vue";
import api from "@/lib/api";

const categories = ref([]);

const emit = defineEmits(["edit-category", "delete-category", "create-category"]);

const query = ref("");
const showCreate = ref(false);

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return categories.value;
  return categories.value.filter((c) => c.name.toLowerCase().includes(q));
});

onMounted(async () => {
  try {
    const response = await api.get("/categories");
    categories.value = response.data;
  } catch (error) {
    console.error("Failed to fetch categories:", error);
  }
});

function confirmDelete(category) {
  if (window.confirm(`Delete "${category.name}"? This can't be undone.`)) {
    emit("delete-category", category);
  }
}

function handleCreate(category) {
  categories.value.unshift(category);
  emit("create-category", category);
}
</script>

<style scoped>
.category-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  object-fit: cover;
}
</style>