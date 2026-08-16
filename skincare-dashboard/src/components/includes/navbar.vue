<script setup lang="ts">
import { Menu, Search, Bell, CalendarDays, ChevronDown, Plus } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const search = defineModel<string>('search')
const period = defineModel<string>('period')

const emit = defineEmits<{
  (e: 'openSidebar'): void
}>()
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-[73px] items-center gap-3 border-b border-slate-200/80 bg-[#f7f8f7]/90 px-5 backdrop-blur lg:px-9">
        <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Open menu" @click="emit('openSidebar')">
            <Menu />
        </Button>
        <div class="relative hidden max-w-sm flex-1 md:block">
            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <Input v-model="search" class="h-9 border-slate-200 bg-white pl-9 shadow-none"
                placeholder="Search orders, customers..." />
        </div>
        <div class="ml-auto flex items-center gap-2">
            <Button variant="ghost" size="icon" class="relative text-slate-600" aria-label="Notifications">
                <Bell /><span
                    class="absolute right-2 top-2 size-1.5 rounded-full bg-emerald-600 ring-2 ring-[#f7f8f7]" />
            </Button>
            <Button variant="outline" class="hidden h-9 border-slate-200 bg-white text-slate-700 sm:inline-flex">
                <CalendarDays /> {{ period }}
                <ChevronDown class="ml-1 text-slate-400" />
            </Button>
            <Button class="h-9 bg-emerald-700 px-3.5 shadow-sm hover:bg-emerald-800"
                @click="period = period === 'This month' ? 'Last 30 days' : 'This month'">
                <Plus /><span class="hidden sm:inline">Create order</span><span class="sm:hidden">Create</span>
            </Button>
        </div>
    </header>
</template>