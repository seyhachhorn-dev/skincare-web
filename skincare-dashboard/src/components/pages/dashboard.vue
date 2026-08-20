<script setup lang="ts">
import { computed, inject, onMounted, ref, type Ref } from 'vue'
import {
  ArrowDownRight,
  ArrowUpRight,
  BarChart3,
  MoreHorizontal,
  ShoppingBag,
  Sparkles,
  UsersRound,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import api from '@/lib/api'

const search = inject<Ref<string>>('search', ref(''))
const period = inject<Ref<string>>('period', ref('This month'))

const orders = ref<any[]>([])
const products = ref<any[]>([])
const users = ref<any[]>([])
const totalRevenue = computed(() => orders.value.reduce((sum, order) => sum + Number(order.total ?? 0), 0))
const totalOrders = computed(() => orders.value.length)
const totalCustomers = computed(() => users.value.length)

onMounted(async () => {
  try {
    const [orderResponse, productResponse, userResponse] = await Promise.all([
      api.get('/admin/orders'),
      api.get('/products'),
      api.get('/users'),
    ])
    orders.value = orderResponse.data
    products.value = productResponse.data
    users.value = userResponse.data
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  }
})

const filteredOrders = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return orders.value

  return orders.value.filter((order) =>
    [order.order_number, order.customer?.name, order.status].some((value) => String(value ?? '').toLowerCase().includes(query)),
  )
})
</script>

<template>
  <div class="mx-auto max-w-[1540px] px-5 py-7 lg:px-9 lg:py-9">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div><p class="text-sm font-medium text-emerald-700">Good morning, Maya</p><h1 class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-slate-900 sm:text-3xl">Store overview</h1><p class="mt-1.5 text-sm text-slate-500">Here’s what’s happening with your skincare store today.</p></div>
      <Button variant="outline" class="border-slate-200 bg-white text-slate-700"><BarChart3 /> View reports</Button>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Card class="gap-3 border-0 bg-white py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80"><CardHeader class="flex-row items-center justify-between px-5 py-0"><CardTitle class="text-sm font-medium text-slate-500">Total revenue</CardTitle><span class="grid size-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><BarChart3 class="size-4" /></span></CardHeader><CardContent class="px-5 pt-0"><p class="text-2xl font-semibold tracking-[-0.04em]">${{ totalRevenue.toFixed(2) }}</p><p class="mt-2 text-xs text-slate-400">From all recorded orders</p></CardContent></Card>
      <Card class="gap-3 border-0 bg-white py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80"><CardHeader class="flex-row items-center justify-between px-5 py-0"><CardTitle class="text-sm font-medium text-slate-500">Orders</CardTitle><span class="grid size-8 place-items-center rounded-lg bg-violet-50 text-violet-700"><ShoppingBag class="size-4" /></span></CardHeader><CardContent class="px-5 pt-0"><p class="text-2xl font-semibold tracking-[-0.04em]">{{ totalOrders }}</p><p class="mt-2 text-xs text-slate-400">All statuses</p></CardContent></Card>
      <Card class="gap-3 border-0 bg-white py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80"><CardHeader class="flex-row items-center justify-between px-5 py-0"><CardTitle class="text-sm font-medium text-slate-500">Customers</CardTitle><span class="grid size-8 place-items-center rounded-lg bg-amber-50 text-amber-700"><UsersRound class="size-4" /></span></CardHeader><CardContent class="px-5 pt-0"><p class="text-2xl font-semibold tracking-[-0.04em]">{{ totalCustomers }}</p><p class="mt-2 text-xs text-slate-400">Registered accounts</p></CardContent></Card>
      <Card class="gap-3 border-0 bg-white py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80"><CardHeader class="flex-row items-center justify-between px-5 py-0"><CardTitle class="text-sm font-medium text-slate-500">Conversion rate</CardTitle><span class="grid size-8 place-items-center rounded-lg bg-rose-50 text-rose-700"><Sparkles class="size-4" /></span></CardHeader><CardContent class="px-5 pt-0"><p class="text-2xl font-semibold tracking-[-0.04em]">4.86%</p><p class="mt-2 flex items-center gap-1 text-xs font-medium text-rose-600"><ArrowDownRight class="size-3.5" /> 0.8% <span class="font-normal text-slate-400">vs. last month</span></p></CardContent></Card>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.9fr)]">
      <Card class="border-0 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80">
        <CardHeader class="flex flex-row items-start justify-between px-5 pt-5 sm:px-6"><div><CardTitle class="text-base font-semibold">Revenue overview</CardTitle><p class="mt-1 text-sm text-slate-500">Daily revenue for {{ period.toLowerCase() }}</p></div><Button variant="ghost" size="icon-sm" aria-label="Chart options"><MoreHorizontal /></Button></CardHeader>
        <CardContent class="px-3 pb-4 pt-1 sm:px-6">
          <div class="flex h-[255px] gap-3 sm:gap-5">
            <div class="flex flex-col justify-between pb-7 pt-2 text-right text-[11px] text-slate-400"><span>$8k</span><span>$6k</span><span>$4k</span><span>$2k</span><span>$0</span></div>
            <div class="relative flex flex-1 flex-col">
              <div class="absolute inset-x-0 top-2 h-px bg-slate-100" /><div class="absolute inset-x-0 top-[31%] h-px bg-slate-100" /><div class="absolute inset-x-0 top-[56%] h-px bg-slate-100" /><div class="absolute inset-x-0 top-[81%] h-px bg-slate-100" />
              <svg class="relative h-full w-full overflow-visible" viewBox="0 0 760 220" preserveAspectRatio="none" aria-label="Revenue chart" role="img"><defs><linearGradient id="revenue-area" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#059669" stop-opacity="0.2" /><stop offset="100%" stop-color="#059669" stop-opacity="0" /></linearGradient></defs><path d="M0 182 C42 160 57 164 90 148 S137 162 172 132 S222 140 254 111 S300 132 337 91 S391 108 423 73 S462 89 497 61 S546 75 580 38 S638 67 671 31 S720 41 760 12 L760 220 L0 220 Z" fill="url(#revenue-area)" /><path d="M0 182 C42 160 57 164 90 148 S137 162 172 132 S222 140 254 111 S300 132 337 91 S391 108 423 73 S462 89 497 61 S546 75 580 38 S638 67 671 31 S720 41 760 12" fill="none" stroke="#059669" stroke-linecap="round" stroke-width="3" vector-effect="non-scaling-stroke" /><circle cx="580" cy="38" r="5" fill="white" stroke="#059669" stroke-width="3" vector-effect="non-scaling-stroke" /></svg>
              <div class="flex justify-between pt-1 text-[11px] text-slate-400"><span>May 01</span><span>May 06</span><span>May 11</span><span>May 16</span><span>May 21</span><span>May 26</span><span>May 31</span></div>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card class="border-0 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80">
        <CardHeader class="flex flex-row items-center justify-between px-5 pt-5 sm:px-6"><div><CardTitle class="text-base font-semibold">Top products</CardTitle><p class="mt-1 text-sm text-slate-500">By revenue this month</p></div><Button variant="ghost" size="sm" class="text-emerald-700">View all</Button></CardHeader>
        <CardContent class="space-y-5 px-5 pb-5 pt-3 sm:px-6"><div v-for="product in products.slice(0, 4)" :key="product.id" class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-emerald-50 text-slate-600"><Sparkles class="size-4" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ product.name }}</p><p class="text-xs text-slate-500">{{ product.brand || 'Product' }}</p></div><span class="text-sm font-semibold">${{ Number(product.price).toFixed(2) }}</span></div></CardContent>
      </Card>
    </section>

    <section class="mt-5"><Card class="border-0 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)] ring-slate-200/80"><CardHeader class="flex flex-row items-center justify-between px-5 pt-5 sm:px-6"><div><CardTitle class="text-base font-semibold">Recent orders</CardTitle><p class="mt-1 text-sm text-slate-500">Your latest customer purchases</p></div><Button variant="outline" size="sm" class="border-slate-200 bg-white">View all orders</Button></CardHeader><CardContent class="px-0 pb-1 pt-3"><div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead class="border-y border-slate-100 bg-slate-50/70 text-xs font-medium text-slate-500"><tr><th class="px-5 py-3 font-medium sm:px-6">Order</th><th class="px-5 py-3 font-medium">Customer</th><th class="px-5 py-3 font-medium">Product</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 text-right font-medium sm:px-6">Amount</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="order in filteredOrders" :key="order.id" class="transition-colors hover:bg-slate-50/70"><td class="px-5 py-3.5 font-medium text-slate-700 sm:px-6">{{ order.id }}</td><td class="px-5 py-3.5"><div class="flex items-center gap-2.5"><span class="grid size-7 place-items-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-600">{{ order.initials }}</span>{{ order.customer }}</div></td><td class="px-5 py-3.5 text-slate-600">{{ order.product }}</td><td class="px-5 py-3.5"><span class="rounded-full px-2 py-1 text-[11px] font-medium" :class="{ 'bg-emerald-50 text-emerald-700': order.tone === 'emerald', 'bg-amber-50 text-amber-700': order.tone === 'amber', 'bg-blue-50 text-blue-700': order.tone === 'blue' }">{{ order.status }}</span></td><td class="px-5 py-3.5 text-right font-semibold sm:px-6">{{ order.amount }}</td></tr><tr v-if="filteredOrders.length === 0"><td colspan="5" class="px-6 py-10 text-center text-slate-500">No matching orders found.</td></tr></tbody></table></div></CardContent></Card></section>
  </div>
</template>
