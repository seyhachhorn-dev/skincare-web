<script setup lang="ts">
import { computed, ref, type Component } from 'vue'
import { useRoute } from 'vue-router'
import {
  LayoutDashboard,
  ShoppingBag,
  UsersRound,
  Package,
  BarChart3,
  LayoutGrid,
  Star,
} from '@lucide/vue'

import Sidebar from '@/components/includes/sidebar.vue'
import Navbar from '@/components/includes/navbar.vue'

type NavigationItem = {
  label: string
  icon: Component
  path: string
}

const navigation: NavigationItem[] = [
  { label: 'Dashboard', icon: LayoutDashboard, path: '/' },
  { label: 'Orders', icon: ShoppingBag, path: '/orders' },
  { label: 'Products', icon: Package, path: '/products' },
  { label: 'Categories', icon: LayoutGrid, path: '/categories' },
  { label: 'Customers', icon: UsersRound, path: '/users' },
  { label: 'Favorites', icon: Star, path: '/favorites' },
  // { label: 'Analytics', icon: BarChart3, path: '/analytics' },
]

const route = useRoute()
const activeNav = computed(() => {
  const currentRoute = navigation.find(item => item.path === route.path)
  return currentRoute ? currentRoute.label : ''
})

const sidebarOpen = ref(false)
const search = ref('')
const period = ref('This month')

function selectNav(label: string) {
  sidebarOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-[#f7f8f7] text-slate-900">
    <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden" @click="sidebarOpen = false" />

    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-[270px] -translate-x-full flex-col border-r border-slate-200 bg-white px-4 py-5 transition-transform duration-200 lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : ''"
    >
      <Sidebar
        :navigation="navigation"
        :active-nav="activeNav"
        :sidebar-open="sidebarOpen"
        @select-nav="selectNav"
        @close-sidebar="sidebarOpen = false"
      />
    </aside>

    <main class="min-h-screen lg:pl-[270px]">
      <Navbar v-model:search="search" v-model:period="period" @open-sidebar="sidebarOpen = true" />
      <router-view />
    </main>
  </div>
</template>