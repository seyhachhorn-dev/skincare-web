<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Skincare Customers</h1>
        <p class="text-sm text-gray-500">Manage registered users and their skin profiles</p>
      </div>
      <!-- Instant Popup Trigger -->
      <button 
        @click="isCreateModalOpen = true"
        class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
      >
        <span>+</span> Add New Customer
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Search by name or email..." 
        class="border border-gray-200 rounded-lg px-4 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-rose-400"
      />
      <div class="flex gap-3">
        <select v-model="filterSkinType" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-rose-400">
          <option value="">All Skin Types</option>
          <option value="Oily">Oily</option>
          <option value="Dry">Dry</option>
          <option value="Combination">Combination</option>
          <option value="Sensitive">Sensitive</option>
          <option value="Normal">Normal</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-rose-50 text-rose-900 font-semibold border-b border-rose-100">
          <tr>
            <th class="p-4">Customer</th>
            <th class="p-4">Skin Type</th>
            <th class="p-4">Primary Concern</th>
            <th class="p-4">Orders</th>
            <th class="p-4">Total Spent</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="p-4 flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-sm">
                {{ user.name.split(' ').map(n => n[0]).join('') }}
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ user.name }}</p>
                <p class="text-xs text-gray-400">{{ user.email }}</p>
              </div>
            </td>
            <td class="p-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                {{ user.skinType }}
              </span>
            </td>
            <td class="p-4 text-gray-700">{{ user.primaryConcern }}</td>
            <td class="p-4 font-medium text-gray-800">{{ user.ordersCount }}</td>
            <td class="p-4 font-semibold text-gray-900">${{ user.totalSpent.toFixed(2) }}</td>
            <td class="p-4">
              <span 
                :class="user.status === 'Active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                class="px-2.5 py-1 rounded-full text-xs font-medium border"
              >
                {{ user.status }}
              </span>
            </td>
            <td class="p-4 text-right">
              <button @click="deleteUser(user.id)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Component Popup -->
    <UserCreateModal 
      :is-open="isCreateModalOpen" 
      @close="isCreateModalOpen = false" 
      @created="handleCustomerCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import UserCreateModal from './userCreate.vue'
import api from '@/lib/api'

const isCreateModalOpen = ref(false)
const searchQuery = ref('')
const filterSkinType = ref('')

const users = ref([])
const normalizeUser = (user) => ({
  ...user,
  ordersCount: user.orders_count ?? 0,
  totalSpent: Number(user.total_spent ?? 0),
  status: user.status ?? 'Active',
  skinType: user.skin_type ?? 'Not specified',
  primaryConcern: user.primary_concern ?? 'Not specified',
})

onMounted(async () => {
  try {
    const response = await api.get('/users')
    users.value = response.data.map(normalizeUser)
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
})

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesSkin = filterSkinType.value ? user.skinType === filterSkinType.value : true
    return matchesSearch && matchesSkin
  })
})

const handleCustomerCreated = (newCustomer) => {
  users.value.unshift(normalizeUser(newCustomer))
}

const deleteUser = async (id) => {
  if (confirm('Delete customer?')) {
    try {
      await api.delete(`/users/${id}`)
      users.value = users.value.filter(u => u.id !== id)
    } catch (error) {
      console.error('Failed to delete user:', error)
      alert('Error deleting user. Please try again.')
    }
  }
}
</script>