<template>
  <main class="login-page">
    <form class="login-card" @submit.prevent="submit">
      <p class="eyebrow">Skincare Dashboard</p>
      <h1>Admin sign in</h1>
      <p class="intro">Sign in to manage products, categories, customers, and orders.</p>

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
  background: #eef0e6;
}

.login-card {
  width: min(100%, 26rem);
  display: grid;
  gap: 0.65rem;
  padding: 2rem;
  border-radius: 1rem;
  background: #fff;
  box-shadow: 0 20px 40px rgba(35, 40, 33, 0.12);
}

h1 { margin: 0; color: #3a4a35; font-size: 2rem; }
.intro { margin: 0 0 1rem; color: #5c6357; }
label { color: #5c6357; font-size: 0.8rem; font-weight: 600; }
input { padding: 0.75rem; border: 1px solid #d3d6c6; border-radius: 0.35rem; }
button { margin-top: 0.5rem; padding: 0.75rem; border: 0; border-radius: 0.35rem; background: #566b4f; color: #fff; cursor: pointer; }
button:disabled { opacity: 0.6; cursor: wait; }
.eyebrow { margin: 0; color: #b9863e; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.error { margin: 0.5rem 0 0; color: #a6493a; }
</style>
