<template>
  <main class="login-page">
    <form class="login-form" @submit.prevent="submit">
      <h1>Admin sign in</h1>

      <label for="email">Email</label>
      <input id="email" v-model.trim="form.email" type="email" autocomplete="email" required />

      <label for="password">Password</label>
      <input id="password" v-model="form.password" type="password" autocomplete="current-password" required />

      <p v-if="errorMsg" class="error" role="alert">{{ errorMsg }}</p>
      <button type="submit" :disabled="submitting">
        {{ submitting ? "Signing in..." : "Sign in" }}
      </button>
    </form>
  </main>
</template>

<script setup>
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import api from "@/lib/api";

const router = useRouter();
const form = reactive({ email: "", password: "" });
const submitting = ref(false);
const errorMsg = ref("");

async function submit() {
  submitting.value = true;
  errorMsg.value = "";

  try {
    const response = await api.post("/auth/login", form);
    localStorage.setItem("token", response.data.token);
    await router.replace("/");
  } catch (error) {
    errorMsg.value = error?.response?.data?.message || "Unable to sign in.";
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  background: #fff;
}

.login-form {
  width: min(100%, 22rem);
  display: grid;
  gap: 0.65rem;
}

h1 { margin: 0 0 0.75rem; color: #232821; font-size: 1.5rem; font-weight: 600; }
label { color: #5c6357; font-size: 0.8rem; font-weight: 600; }
input { padding: 0.75rem; border: 1px solid #d3d6c6; border-radius: 0.35rem; }
input:focus { outline: 2px solid #566b4f; outline-offset: 1px; }
button { margin-top: 0.5rem; padding: 0.75rem; border: 0; border-radius: 0.35rem; background: #566b4f; color: #fff; cursor: pointer; }
button:disabled { opacity: 0.6; cursor: wait; }
.error { margin: 0.5rem 0 0; color: #a6493a; font-size: 0.85rem; }
</style>
