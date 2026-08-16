<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto"
      @click.self="close"
    >
      <!-- Modal Window -->
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all p-6">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
          <div>
            <h2 class="text-xl font-bold text-gray-800">Add New Skincare Customer</h2>
            <p class="text-xs text-gray-500">Capture customer details and skin preferences</p>
          </div>
          <button 
            @click="close" 
            class="text-gray-400 hover:text-gray-600 rounded-lg p-1 hover:bg-gray-100 text-lg transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6 pt-4">
          
          <!-- Basic Info -->
          <div>
            <h3 class="text-xs font-semibold text-rose-900 uppercase tracking-wider mb-3">Basic Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Full Name *</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  placeholder="e.g., Jane Doe"
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Email Address *</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  placeholder="jane@example.com"
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="+1 (555) 000-0000"
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Account Status</label>
                <select 
                  v-model="form.status" 
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-rose-400"
                >
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Skincare Profile -->
          <div>
            <h3 class="text-xs font-semibold text-rose-900 uppercase tracking-wider mb-3">Skincare Profile</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Skin Type *</label>
                <select 
                  v-model="form.skinType" 
                  required 
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-rose-400"
                >
                  <option value="" disabled>Select Skin Type</option>
                  <option value="Normal">Normal</option>
                  <option value="Dry">Dry</option>
                  <option value="Oily">Oily</option>
                  <option value="Combination">Combination</option>
                  <option value="Sensitive">Sensitive</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Primary Skin Concern *</label>
                <select 
                  v-model="form.primaryConcern" 
                  required 
                  class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-rose-400"
                >
                  <option value="" disabled>Select Primary Concern</option>
                  <option value="Acne & Blemishes">Acne & Blemishes</option>
                  <option value="Anti-Aging & Fine Lines">Anti-Aging & Fine Lines</option>
                  <option value="Dryness & Hydration">Dryness & Hydration</option>
                  <option value="Hyperpigmentation">Hyperpigmentation / Dark Spots</option>
                  <option value="Redness & Rosacea">Redness & Sensitivity</option>
                  <option value="Pores & Texture">Uneven Texture / Large Pores</option>
                </select>
              </div>
            </div>

            <!-- Preferences -->
            <div class="mt-4">
              <label class="block text-xs font-medium text-gray-700 mb-2">Sensitivity & Preferences</label>
              <div class="flex flex-wrap gap-4 text-xs text-gray-600">
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input v-model="form.isSensitive" type="checkbox" class="rounded text-rose-600 focus:ring-rose-400" />
                  Sensitive Skin
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input v-model="form.prefersFragranceFree" type="checkbox" class="rounded text-rose-600 focus:ring-rose-400" />
                  Fragrance-Free
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input v-model="form.prefersVegan" type="checkbox" class="rounded text-rose-600 focus:ring-rose-400" />
                  Vegan Formulas
                </label>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium transition-colors"
            >
              Save Customer
            </button>
          </div>

        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close', 'created'])

const initialForm = () => ({
  name: '',
  email: '',
  phone: '',
  status: 'Active',
  skinType: '',
  primaryConcern: '',
  isSensitive: false,
  prefersFragranceFree: false,
  prefersVegan: false
})

const form = reactive(initialForm())

// Reset form fields when modal closes
watch(() => props.isOpen, (newVal) => {
  if (!newVal) Object.assign(form, initialForm())
})

const close = () => {
  emit('close')
}

const handleSubmit = () => {
  emit('created', { ...form, id: Date.now(), ordersCount: 0, totalSpent: 0.00 })
  close()
}
</script>