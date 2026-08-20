<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div
        v-if="modelValue"
        class="backdrop"
        @mousedown.self="close"
      >
        <Transition name="panel" appear>
          <div
            v-if="modelValue"
            class="intake"
            role="dialog"
            aria-modal="true"
            aria-labelledby="intake-title"
          >
            <button type="button" class="close-btn" aria-label="Close" @click="close">×</button>

            <header class="intake__head">
              <p class="eyebrow">Field Apothecary — Intake Ticket</p>
              <h1 id="intake-title" class="intake__title">List a New Formula</h1>
              <p class="intake__sub">Fill in the label exactly as it should read to a customer.</p>
            </header>

            <form class="form" @submit.prevent="handleSubmit">
      <fieldset class="section">
        <legend>General</legend>

        <div class="field">
          <label for="name">Product name</label>
          <input id="name" v-model.trim="form.name" type="text" placeholder="Clarity Drops" required />
        </div>

        <div class="row">
          <div class="field">
            <label for="category">Category</label>
            <select id="category" v-model="form.categoryId">
              <option value="">Uncategorized</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="field">
            <label for="brand">Brand</label>
            <input id="brand" v-model.trim="form.brand" type="text" placeholder="Field Apothecary" />
          </div>
        </div>

        <div class="field">
          <label for="desc">Description</label>
          <textarea id="desc" v-model.trim="form.description" rows="4" placeholder="What it does, and who it's for." required />
        </div>
      </fieldset>

      <fieldset class="section">
        <legend>Pricing &amp; size</legend>
        <div class="row row--2">
          <div class="field">
            <label for="price">Price (USD, whole numbers)</label>
            <input id="price" v-model.number="form.price" type="number" min="0" step="1" required />
          </div>
          <div class="field">
            <label for="size">Size</label>
            <input id="size" v-model.trim="form.size" type="text" placeholder="30 mL / 1 fl oz" />
          </div>
        </div>
      </fieldset>

      <fieldset class="section">
        <legend>Product Image</legend>
        <div class="field">
          <label for="imageFile">Choose an image</label>
          <input
            id="imageFile"
            type="file"
            accept="image/png,image/jpeg,image/webp,image/svg+xml"
            @change="selectImage"
          />
          <span class="hint">PNG, JPG, WEBP, or SVG up to 4 MB.</span>
        </div>
        <div class="field">
          <label for="imageUrl">Or use an image URL</label>
          <input id="imageUrl" v-model.trim="form.image" type="url" placeholder="https://…" />
        </div>
        <div v-if="previewUrl || form.image" class="preview">
          <img :src="previewUrl || form.image" alt="Product preview" @error="imgError = true" @load="imgError = false" />
          <span v-if="imgError" class="preview__error">Couldn't load that image.</span>
        </div>
      </fieldset>

      <div class="actions">
        <p v-if="errorMsg" class="error">{{ errorMsg }}</p>
        <button type="submit" class="btn-primary" :disabled="submitting">
          {{ submitting ? "Publishing…" : "Publish product" }}
        </button>
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
  categories: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["create-product", "update:modelValue", "close"]);

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

const imgError = ref(false);
const errorMsg = ref("");
const submitting = ref(false);
const imageFile = ref(null);
const previewUrl = ref("");

const form = reactive({
  name: "",
  categoryId: "",
  description: "",
  price: null,
  image: "",
  brand: "",
  size: "",
});

async function handleSubmit() {
  errorMsg.value = "";

  if (form.price == null || form.price < 0) {
    errorMsg.value = "Enter a valid price.";
    return;
  }
  if (!imageFile.value && !form.image) {
    errorMsg.value = "Choose an image or enter an image URL.";
    return;
  }

  submitting.value = true;
  try {
    const data = {
      name: form.name,
      category_id: form.categoryId || "",
      description: form.description,
      price: String(Math.round(form.price)),
      brand: form.brand || "",
      size: form.size || "",
    };
    let payload = data;
    let config = undefined;

    if (imageFile.value) {
      const multipart = new FormData();
      Object.entries(data).forEach(([key, value]) => multipart.append(key, value));
      multipart.append("image", imageFile.value);
      payload = multipart;
      config = { headers: { "Content-Type": "multipart/form-data" } };
    } else {
      payload = { ...data, category_id: form.categoryId || null, price: Math.round(form.price), image: form.image };
    }

    const response = await api.post("/products", payload, config);
    emit("create-product", response.data);
    resetForm();
    close();
  } catch (error) {
    console.error("Failed to create product:", error);
    const response = error?.response?.data;
    const validationMessage = response?.errors
      ? Object.values(response.errors).flat()[0]
      : null;
    errorMsg.value = validationMessage || response?.message || "An error occurred while saving. Please try again.";
  } finally {
    submitting.value = false;
  }
}

function selectImage(event) {
  const file = event.target.files?.[0] ?? null;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  imageFile.value = file;
  previewUrl.value = file ? URL.createObjectURL(file) : "";
  imgError.value = false;
}

function resetForm() {
  Object.assign(form, {
    name: "",
    categoryId: "",
    description: "",
    price: null,
    image: "",
    brand: "",
    size: "",
  });
  imgError.value = false;
  errorMsg.value = "";
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  imageFile.value = null;
  previewUrl.value = "";
}
</script>

<style scoped>
@import "@/assets/tokens.css";

.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.5); /* gray-900/50 */
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 4vh 1rem;
  overflow-y: auto;
  z-index: 50;
}

.intake {
  position: relative;
  background: #ffffff;
  color: #1f2937; /* gray-800 */
  font-family: inherit;
  padding: 1.5rem;
  width: 100%;
  max-width: 42rem; /* 2xl */
  border-radius: 1rem; /* 2xl */
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  margin: auto;
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 50%;
  border: 1px solid var(--line);
  background: var(--surface);
  color: var(--ink-soft);
  font-size: 1.2rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s var(--ease), color 0.15s var(--ease);
}

.close-btn:hover {
  background: var(--moss);
  color: #fff;
  border-color: var(--moss);
}

.intake__head {
  max-width: 640px;
  margin: 0 auto 2rem;
  padding-right: 2rem;
}

/* Instant-feeling open/close */
.backdrop-enter-active {
  transition: opacity 0.15s var(--ease);
}
.backdrop-leave-active {
  transition: opacity 0.12s var(--ease);
}
.backdrop-enter-from,
.backdrop-leave-to {
  opacity: 0;
}

.panel-enter-active {
  transition: transform 0.16s var(--ease), opacity 0.16s var(--ease);
}
.panel-leave-active {
  transition: transform 0.1s var(--ease), opacity 0.1s var(--ease);
}
.panel-enter-from,
.panel-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

.eyebrow {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.6rem;
}

.intake__title {
  font-family: var(--font-display);
  font-size: clamp(1.9rem, 3.5vw, 2.5rem);
  font-weight: 600;
  color: var(--moss-dark);
  margin: 0 0 0.5rem;
}

.intake__sub {
  color: var(--ink-soft);
  margin: 0;
}

.form {
  max-width: 640px;
  margin: 0 auto;
}

.section {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.4rem 1.5rem;
  margin: 0 0 1.25rem;
}

.section legend {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--moss-dark);
  padding: 0 0.4rem;
}

.section__hint {
  font-size: 0.82rem;
  color: var(--ink-faint);
  margin: -0.3rem 0 1rem;
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

.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.9rem;
}

.row--2 {
  grid-template-columns: 1fr 1fr;
}

.row .field {
  margin-bottom: 1rem;
}

@media (max-width: 560px) {
  .row {
    grid-template-columns: 1fr;
  }
}

label {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint);
}

input,
select,
textarea {
  font-family: var(--font-body);
  font-size: 0.9rem;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--ink);
  width: 100%;
  box-sizing: border-box;
}

textarea {
  resize: vertical;
}

input:focus,
select:focus,
textarea:focus {
  outline: 2px solid var(--moss);
  outline-offset: 1px;
}

.icon-btn {
  background: none;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  width: 1.9rem;
  height: 1.9rem;
  color: var(--danger);
  cursor: pointer;
  flex-shrink: 0;
}

.btn-ghost {
  background: none;
  border: 1px dashed var(--line);
  border-radius: var(--radius-sm);
  color: var(--moss-dark);
  font-family: var(--font-body);
  font-size: 0.85rem;
  padding: 0.45rem 0.8rem;
  cursor: pointer;
}

.btn-ghost:hover {
  border-color: var(--moss);
  background: var(--surface-alt);
}

.preview {
  margin-top: 0.85rem;
}

.preview img {
  width: 100%;
  max-width: 220px;
  aspect-ratio: 4 / 5;
  object-fit: cover;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
}

.preview__error {
  display: block;
  font-size: 0.78rem;
  color: var(--danger);
  margin-top: 0.4rem;
}

.actions {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.75rem;
}

.error {
  color: var(--danger);
  font-size: 0.85rem;
  margin: 0;
}

.btn-primary {
  background: var(--moss);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.85rem 1.5rem;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.15s var(--ease);
}

.btn-primary:hover {
  background: var(--moss-dark);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>