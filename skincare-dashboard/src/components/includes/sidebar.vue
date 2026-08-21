<script setup lang="ts">
import type { Component } from 'vue'
import { Sparkles, X, Settings, CircleHelp, ChevronDown } from '@lucide/vue'
import { Button } from '@/components/ui/button'

type NavigationItem = {
  label: string
  icon: Component
  path: string
}

defineProps<{
  navigation: NavigationItem[]
  activeNav: string
  sidebarOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'selectNav', label: string): void
  (e: 'closeSidebar'): void
}>()
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <router-link to="/" class="flex items-center gap-3" @click="emit('closeSidebar')">
            <span class="grid size-9 place-items-center rounded-xl bg-emerald-700 text-white shadow-sm">
                <Sparkles class="size-4" />
            </span>
            <span class="text-lg font-semibold tracking-[-0.04em]">purely.</span>
        </router-link>
        <Button variant="ghost" size="icon-sm" class="lg:hidden" aria-label="Close menu" @click="emit('closeSidebar')">
            <X />
        </Button>
    </div>

    <div class="mt-9">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Workspace</p>
        <nav class="mt-3 space-y-1">
            <router-link v-for="item in navigation" :key="item.label" :to="item.path"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                :class="activeNav === item.label ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'"
                @click="emit('selectNav', item.label)">
                <component :is="item.icon" class="size-4" /> {{ item.label }}
            </router-link>
        </nav>
    </div>

    <div class="mt-8 border-t border-slate-100 pt-6">
        <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Account</p>
        <nav class="mt-3 space-y-1">
            <router-link
                to="/profile"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900">
                <Settings class="size-4" /> Settings
            </router-link>
            <button
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900">
                <CircleHelp class="size-4" /> Help center
            </button>
        </nav>
    </div>

    <div class="mt-auto rounded-xl bg-slate-50 p-3">
        <div class="flex items-center gap-3">
            <span
                class="grid size-9 place-items-center rounded-full bg-[#d9cabd] text-xs font-semibold text-[#755d4c]">MC</span>
            <div class="min-w-0 flex-1">
                <router-link to="/profile" class="block truncate text-sm font-semibold hover:text-emerald-700">Admin profile</router-link>
                <p class="truncate text-xs text-slate-500">Manage account</p>
            </div>
            <ChevronDown class="size-4 text-slate-400" />
        </div>
    </div>
</template>