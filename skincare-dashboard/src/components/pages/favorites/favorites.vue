<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Saved Formulas</h1>
        <p class="text-sm text-gray-500">Manage products you have bookmarked or saved for later</p>
      </div>
      <button 
        @click="$emit('browse')"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
      >
        <span>🔍</span> Browse Catalog
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
      <div class="flex gap-3 items-center">
        <span class="text-sm font-medium text-gray-700">{{ items.length }} saved items</span>
      </div>
      <div class="flex gap-3 items-center">
        <span class="text-sm text-gray-500">Sort by:</span>
        <select v-model="sort" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
          <option value="recent">Recently Added</option>
          <option value="name">Name (A-Z)</option>
          <option value="price-asc">Price (Low to High)</option>
          <option value="price-desc">Price (High to Low)</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-emerald-50 text-emerald-900 font-semibold border-b border-emerald-100">
          <tr>
            <th class="p-4">Product</th>
            <th class="p-4">Category</th>
            <th class="p-4">Active Ingredient</th>
            <th class="p-4">Price</th>
            <th class="p-4">Added</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in sorted" :key="p.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="p-4 flex items-center gap-4 cursor-pointer" @click="$emit('select-product', p.id)">
              <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 shadow-sm border border-gray-100 relative group">
                <img :src="p.image" :alt="p.name" class="w-full h-full object-cover group-hover:opacity-75 transition-opacity" />
              </div>
              <div>
                <p class="font-bold text-gray-900 text-base">{{ p.name }}</p>
                <p class="text-xs text-gray-400">ID: {{ p.id }}</p>
              </div>
            </td>
            <td class="p-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ p.category }}
              </span>
            </td>
            <td class="p-4 font-medium text-gray-700">
              {{ p.activeIngredient }}
            </td>
            <td class="p-4 font-semibold text-gray-900">
              ${{ p.price.toFixed(2) }}
            </td>
            <td class="p-4 text-gray-500 text-sm">
              {{ relativeTime(p.addedAt) }}
            </td>
            <td class="p-4 text-right space-x-3">
              <button @click.stop="$emit('add-to-cart', p)" class="text-emerald-600 hover:text-emerald-800 text-xs font-medium bg-emerald-50 px-3 py-1.5 rounded-md transition-colors hover:bg-emerald-100">
                Add to Cart
              </button>
              <button @click.stop="remove(p)" class="text-red-500 hover:text-red-700 text-xs font-medium hover:bg-red-50 px-3 py-1.5 rounded-md transition-colors" title="Remove from Favorites">
                Remove
              </button>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="6" class="p-12 text-center">
              <div class="text-gray-300 text-4xl mb-4">♡</div>
              <p class="text-lg font-medium text-gray-800 mb-2">Nothing saved yet</p>
              <p class="text-sm text-gray-500 mb-6">Tap the heart on any formula to keep it here for later.</p>
              <button @click="$emit('browse')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
                Browse the Shop
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
// Module-scope fallback data — kept separate from <script setup> to avoid compiler issues.
const defaultFavorites = [
  {
    id: "sp-02",
    name: "Clarity Drops",
    category: "Serum",
    activeIngredient: "Niacinamide",
    price: 29,
    image: "https://images.unsplash.com/photo-1620917669809-3cddf03fbbf0?w=600&q=80",
    addedAt: "2026-08-14T09:00:00Z",
  },
  {
    id: "sp-03",
    name: "Night Retinal",
    category: "Treatment",
    activeIngredient: "Retinal",
    price: 54,
    image: "https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd?w=600&q=80",
    addedAt: "2026-08-10T09:00:00Z",
  },
  {
    id: "sp-06",
    name: "Mineral Veil SPF",
    category: "Sun Care",
    activeIngredient: "Zinc Oxide",
    price: 26,
    image: "https://images.unsplash.com/photo-1600428853876-fb5a850b444c?w=600&q=80",
    addedAt: "2026-08-01T09:00:00Z",
  },
];
</script>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/lib/api";

const props = defineProps({
  favorites: {
    type: Array,
    default: () => defaultFavorites,
  },
});

const emit = defineEmits(["remove-favorite", "add-to-cart", "select-product", "browse"]);

const sort = ref("recent");

const items = ref([]);

const normalizeFavorite = (product) => ({
  ...product,
  category: product.category ?? "Uncategorized",
  activeIngredient: product.brand ?? "Not specified",
  price: Number(product.price),
  addedAt: product.added_at ?? new Date().toISOString(),
  image: product.image || "https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600&q=80",
});

onMounted(async () => {
  try {
    items.value = (await api.get("/favorites")).data.map(normalizeFavorite);
  } catch (error) {
    console.error("Failed to fetch favorites:", error);
  }
});

async function remove(product) {
  try {
    await api.delete(`/favorites/${product.id}`);
    items.value = items.value.filter((p) => p.id !== product.id);
    emit("remove-favorite", product);
  } catch (error) {
    console.error("Failed to remove favorite:", error);
  }
}

const sorted = computed(() => {
  const list = [...items.value];
  switch (sort.value) {
    case "name":
      return list.sort((a, b) => a.name.localeCompare(b.name));
    case "price-asc":
      return list.sort((a, b) => a.price - b.price);
    case "price-desc":
      return list.sort((a, b) => b.price - a.price);
    default:
      return list.sort((a, b) => new Date(b.addedAt) - new Date(a.addedAt));
  }
});

function relativeTime(iso) {
  const diffMs = Date.now() - new Date(iso).getTime();
  const days = Math.floor(diffMs / 86400000);
  if (days <= 0) return "today";
  if (days === 1) return "yesterday";
  if (days < 30) return `${days} days ago`;
  const months = Math.floor(days / 30);
  return `${months} month${months > 1 ? "s" : ""} ago`;
}
</script>