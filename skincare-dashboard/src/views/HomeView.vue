<script setup lang="ts">
import TheWelcome from '../components/TheWelcome.vue'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
</script>

<template>
  <main>
    <Card class="m-8 max-w-md">
      <CardHeader>
        <CardTitle>shadcn-vue smoke test</CardTitle>
      </CardHeader>
      <CardContent class="flex flex-col gap-4">
        <Input placeholder="Type something..." />
        <Button>Click me</Button>
      </CardContent>
    </Card>
    <TheWelcome />
  </main>
</template>
