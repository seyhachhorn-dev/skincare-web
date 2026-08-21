<template>
  <main class="profile-page">
    <section class="profile-card">
      <header>
        <p class="eyebrow">Account</p>
        <h1>Profile</h1>
        <p class="intro">Update the account details used across your dashboard.</p>
      </header>

      <form @submit.prevent="saveProfile">
        <div class="avatar-row">
          <img v-if="previewUrl || form.avatar" :src="previewUrl || form.avatar" alt="Profile avatar" class="avatar" />
          <div v-else class="avatar avatar-fallback">{{ initials }}</div>
          <div>
            <label for="avatar">Profile image</label>
            <input id="avatar" type="file" accept="image/png,image/jpeg,image/webp" @change="selectAvatar" />
            <small>PNG, JPG, or WEBP up to 4 MB.</small>
          </div>
        </div>

        <label for="name">Name</label>
        <input id="name" v-model.trim="form.name" type="text" required />

        <label for="email">Email</label>
        <input id="email" v-model.trim="form.email" type="email" required />

        <p v-if="message" class="success" role="status">{{ message }}</p>
        <p v-if="errorMsg" class="error" role="alert">{{ errorMsg }}</p>
        <button type="submit" :disabled="saving">{{ saving ? "Saving..." : "Save profile" }}</button>
      </form>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, onUnmounted } from "vue";
import api from "@/lib/api";

const form = reactive({ name: "", email: "", avatar: "" });
const avatarFile = ref(null);
const previewUrl = ref("");
const saving = ref(false);
const message = ref("");
const errorMsg = ref("");
const initials = computed(() => form.name.split(" ").map((part) => part[0]).join("").slice(0, 2).toUpperCase());
const assetUrl = (path) => path && !path.startsWith("http") && !path.startsWith("/")
  ? `${import.meta.env.VITE_API_BASE_URL?.replace(/\/api$/, '') || 'http://localhost:8000'}/storage/${path}`
  : path;

onMounted(async () => {
  try {
    const response = await api.get("/auth/me");
    Object.assign(form, { ...response.data, avatar: assetUrl(response.data.avatar) });
  } catch (error) {
    errorMsg.value = error?.response?.data?.message || "Unable to load your profile.";
  }
});

function selectAvatar(event) {
  const file = event.target.files?.[0] ?? null;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  avatarFile.value = file;
  previewUrl.value = file ? URL.createObjectURL(file) : "";
}

async function saveProfile() {
  saving.value = true;
  message.value = "";
  errorMsg.value = "";

  try {
    const payload = new FormData();
    payload.append("_method", "PATCH");
    payload.append("name", form.name);
    payload.append("email", form.email);
    if (avatarFile.value) payload.append("avatar", avatarFile.value);
    const response = await api.post("/profile", payload, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    Object.assign(form, { ...response.data, avatar: assetUrl(response.data.avatar) });
    message.value = "Profile saved successfully.";
  } catch (error) {
    const response = error?.response?.data;
    errorMsg.value = response?.message || "Unable to save your profile.";
  } finally {
    saving.value = false;
  }
}

onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});
</script>

<style scoped>
.profile-page { min-height: calc(100vh - 73px); padding: 2rem; background: #f7f8f7; }
.profile-card { width: min(100%, 42rem); margin: 0 auto; padding: 2rem; border-radius: 1rem; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
header { margin-bottom: 1.5rem; }
h1 { margin: 0; color: #3a4a35; font-size: 2rem; }
.eyebrow { margin: 0 0 0.4rem; color: #b9863e; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.intro { margin: 0.5rem 0 0; color: #5c6357; }
form { display: grid; gap: 0.65rem; }
label { color: #5c6357; font-size: 0.8rem; font-weight: 600; }
input { box-sizing: border-box; width: 100%; padding: 0.75rem; border: 1px solid #d3d6c6; border-radius: 0.35rem; }
.avatar-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.avatar { width: 4.5rem; height: 4.5rem; border-radius: 50%; object-fit: cover; }
.avatar-fallback { display: grid; place-items: center; background: #d9cabd; color: #755d4c; font-weight: 700; }
small { display: block; margin-top: 0.35rem; color: #8b9182; }
button { margin-top: 0.75rem; padding: 0.75rem 1rem; border: 0; border-radius: 0.35rem; background: #566b4f; color: #fff; cursor: pointer; }
button:disabled { opacity: 0.6; cursor: wait; }
.success { color: #16734b; }
.error { color: #a6493a; }
</style>
