<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div v-if="modelValue" class="backdrop" @mousedown.self="close">
        <Transition name="panel" appear>
          <div
            v-if="modelValue"
            class="intake"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cat-intake-title"
          >
            <button type="button" class="close-btn" aria-label="Close" @click="close">×</button>

            <header class="intake__head">
              <h1 id="cat-intake-title" class="intake__title">New Category</h1>
            </header>

            <form class="form" @submit.prevent="handleSubmit">
              <fieldset class="section">
                <legend>Details</legend>

                <div class="field">
                  <label for="cname">Category Name</label>
                  <input
                    id="cname"
                    v-model.trim="form.name"
                    type="text"
                    placeholder="Serums"
                    required
                  />
                </div>

                <div class="field">
                  <label for="cicon">Category Image</label>
                  <input
                    id="cicon"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    @change="selectIcon"
                  />
                  <img v-if="previewUrl" :src="previewUrl" alt="Category image preview" class="image-preview" />
                  <span class="hint">PNG, JPG, WEBP, or SVG up to 4 MB.</span>
                </div>
              </fieldset>

              <div class="actions">
                <p v-if="errorMsg" class="error" role="alert">{{ errorMsg }}</p>
                <div class="actions__footer">
                  <button type="submit" class="btn-primary">Save Category</button>
                </div>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { reactive, ref, watch, onUnmounted } from "vue";
import api from "@/lib/api";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["create-category", "update:modelValue", "close"]);

const errorMsg = ref("");
const iconFile = ref(null);
const previewUrl = ref("");

const form = reactive({
  name: "",
  icon: "",
});

function close() {
  emit("update:modelValue", false);
  emit("close");
}

function onKeydown(e) {
  if (e.key === "Escape" && props.modelValue) close();
}

watch(
  () => props.modelValue,
  (isOpen) => {
    document.documentElement.style.overflow = isOpen ? "hidden" : "";
    if (isOpen) {
      window.addEventListener("keydown", onKeydown);
    } else {
      window.removeEventListener("keydown", onKeydown);
    }
  },
  { immediate: true }
);

onUnmounted(() => {
  window.removeEventListener("keydown", onKeydown);
  document.documentElement.style.overflow = "";
});

function resetForm() {
  Object.assign(form, {
    name: "",
    icon: "",
  });
  errorMsg.value = "";
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  iconFile.value = null;
  previewUrl.value = "";
}

function selectIcon(event) {
  const file = event.target.files?.[0] ?? null;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  iconFile.value = file;
  previewUrl.value = file ? URL.createObjectURL(file) : "";
}

async function handleSubmit() {
  errorMsg.value = "";

  const payload = new FormData();
  payload.append("name", form.name);
  if (iconFile.value) payload.append("icon", iconFile.value);

  try {
    const response = await api.post("/categories", payload, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    emit("create-category", response.data);
    resetForm();
    close();
  } catch (error) {
    console.error("Failed to create category:", error);
    const response = error?.response?.data;
    const validationMessage = response?.errors
      ? Object.values(response.errors).flat()[0]
      : null;
    errorMsg.value = validationMessage
      || response?.message
      || "An error occurred while saving. Please try again.";
  }
}
</script>

<style scoped>
@import "@/assets/tokens.css";

.backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-y: auto;
  padding: 4vh 1rem;
  background: rgba(17, 24, 39, 0.5);
  backdrop-filter: blur(4px);
}

.intake {
  position: relative;
  width: 100%;
  max-width: 42rem;
  margin: auto;
  padding: 1.5rem;
  border-radius: 1rem;
  background: #fff;
  color: #1f2937;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: grid;
  width: 2.2rem;
  height: 2.2rem;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 50%;
  background: var(--surface);
  color: var(--ink-soft);
  font-size: 1.2rem;
  cursor: pointer;
}

.close-btn:hover {
  background: var(--moss);
  color: #fff;
}

.intake__head {
  margin: 0 auto 2rem;
  padding-right: 2rem;
}

.intake__title {
  margin: 0;
  color: var(--moss-dark);
  font-family: var(--font-display);
  font-size: 2.5rem;
}

.form {
  max-width: 640px;
  margin: 0 auto;
}

.section {
  margin: 0 0 1.25rem;
  padding: 1.25rem 1.4rem 1.5rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  background: var(--surface);
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

.field:last-child {
  margin-bottom: 0;
}

label {
  color: var(--ink-faint);
  font-family: var(--font-mono);
  font-size: 0.7rem;
  text-transform: uppercase;
}

input {
  box-sizing: border-box;
  width: 100%;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--ink);
}

.image-preview {
  width: 4rem;
  height: 4rem;
  margin-top: 0.5rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  object-fit: cover;
}

.hint {
  color: var(--ink-faint);
  font-size: 0.75rem;
}

.actions {
  display: block;
  margin-top: 1rem;
}

.actions__footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.75rem;
  border-top: 1px solid var(--line);
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 10rem;
  padding: 0.7rem 1rem;
  border: 0;
  border-radius: var(--radius-sm);
  background: var(--moss);
  color: #fff;
  cursor: pointer;
}

.error {
  margin: 0 0 0.75rem;
  color: var(--danger);
  font-size: 0.85rem;
}

.backdrop-enter-active,
.backdrop-leave-active {
  transition: opacity 0.15s var(--ease);
}

.backdrop-enter-from,
.backdrop-leave-to {
  opacity: 0;
}

.panel-enter-active,
.panel-leave-active {
  transition: transform 0.16s var(--ease), opacity 0.16s var(--ease);
}

.panel-enter-from,
.panel-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>