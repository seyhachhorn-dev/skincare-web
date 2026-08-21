<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Products Catalog</h1>
        <p class="text-sm text-gray-500">Manage your skincare formulas, pricing, and inventory</p>
      </div>
      <button 
        @click="isCreateModalOpen = true"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
      >
        <span>+</span> Add New Product
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Search by product name or active..." 
        class="border border-gray-200 rounded-lg px-4 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-emerald-400"
      />
      <div class="flex gap-3">
        <select v-model="filterCategory" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
          <option value="">All Categories</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
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
            <th class="p-4">Brand</th>
            <th class="p-4">Size</th>
            <th class="p-4">Price</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in filteredProducts" :key="product.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="p-4 flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                <img :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ product.name }}</p>
                <p class="text-xs text-gray-400">ID: {{ product.id }}</p>
              </div>
            </td>
            <td class="p-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ product.category }}
              </span>
            </td>
            <td class="p-4 text-gray-700">{{ product.brand || "—" }}</td>
            <td class="p-4 text-gray-700">{{ product.size || "—" }}</td>
            <td class="p-4 font-semibold text-gray-900">${{ product.price.toFixed(2) }}</td>
            <td class="p-4 text-right">
              <button @click="deleteProduct(product.id)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
            </td>
          </tr>
          <tr v-if="filteredProducts.length === 0">
            <td colspan="6" class="p-8 text-center text-gray-500">
              No products found matching your search.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Product Create Modal Component Popup -->
    <ProductCreateModal 
      v-model="isCreateModalOpen" 
      :categories="categoryRecords"
      @create-product="handleProductCreated"
      @close="isCreateModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import ProductCreateModal from './productCreate.vue'
import api from '@/lib/api'

const isCreateModalOpen = ref(false)
const searchQuery = ref('')
const filterCategory = ref('')

const products = ref([])
const categoryRecords = ref([])
const imageUrl = (image) => image && !image.startsWith('http') && !image.startsWith('/')
  ? `${import.meta.env.VITE_API_BASE_URL?.replace(/\/api$/, '') || 'http://localhost:8000'}/storage/${image}`
  : image

const normalizeProduct = (product) => ({
  ...product,
  category: categoryRecords.value.find((category) => category.id === product.category_id)?.name ?? 'Uncategorized',
  image: imageUrl(product.image) || 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600&q=80',
  price: Number(product.price),
})

async function loadProducts() {
  const [productResponse, categoryResponse] = await Promise.all([
    api.get('/products'),
    api.get('/categories'),
  ])
  categoryRecords.value = categoryResponse.data
  products.value = productResponse.data.map(normalizeProduct)
}

onMounted(() => {
  loadProducts().catch((error) => console.error('Failed to fetch products:', error))
})

const categories = computed(() =>
  [...new Set(products.value.map((p) => p.category))].sort()
)

const filteredProducts = computed(() => {
  return products.value.filter(product => {
    const q = searchQuery.value.trim().toLowerCase()
    const matchesSearch = product.name.toLowerCase().includes(q) ||
                          (product.brand ?? '').toLowerCase().includes(q)
    const matchesCategory = filterCategory.value ? product.category === filterCategory.value : true
    return matchesSearch && matchesCategory
  })
})

const handleProductCreated = (newProduct) => {
  products.value.unshift(normalizeProduct(newProduct))
}

const deleteProduct = async (id) => {
  if (confirm('Delete product?')) {
    try {
      await api.delete(`/products/${id}`)
      products.value = products.value.filter(p => p.id !== id)
    } catch (error) {
      console.error('Failed to delete product:', error)
      alert('Error deleting product. Please try again.')
    }
  }
}
</script>