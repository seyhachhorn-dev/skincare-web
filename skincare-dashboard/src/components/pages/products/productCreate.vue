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
            <select id="category" v-model="form.category" required>
              <option value="" disabled>Select one</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="field">
            <label for="sku">SKU</label>
            <input id="sku" v-model.trim="form.sku" type="text" placeholder="CD-10-030" required />
          </div>
        </div>

        <div class="field">
          <label for="tagline">Tagline</label>
          <input id="tagline" v-model.trim="form.tagline" type="text" maxlength="90" placeholder="One line, under 90 characters" />
          <span class="hint">{{ form.tagline.length }}/90</span>
        </div>

        <div class="field">
          <label for="desc">Description</label>
          <textarea id="desc" v-model.trim="form.description" rows="4" placeholder="What it does, and who it's for." required />
        </div>

        <div class="field">
          <label>Skin types</label>
          <div class="checks">
            <label v-for="t in skinTypeOptions" :key="t" class="check">
              <input type="checkbox" :value="t" v-model="form.skinTypes" />
              {{ t }}
            </label>
          </div>
        </div>
      </fieldset>

      <fieldset class="section">
        <legend>Lead actives</legend>
        <p class="section__hint">Add each active ingredient and the concentration it's dosed at.</p>

        <div v-for="(a, i) in form.actives" :key="i" class="active-row">
          <input v-model.trim="a.name" type="text" placeholder="Niacinamide" class="active-row__name" />
          <div class="active-row__pct">
            <input v-model.number="a.concentration" type="number" min="0" max="100" step="0.1" />
            <span>%</span>
          </div>
          <button type="button" class="icon-btn" @click="removeActive(i)" aria-label="Remove active">×</button>
        </div>
        <button type="button" class="btn-ghost" @click="addActive">+ Add active</button>
      </fieldset>

      <fieldset class="section">
        <legend>Pricing &amp; inventory</legend>
        <div class="row">
          <div class="field">
            <label for="price">Price (USD)</label>
            <input id="price" v-model.number="form.price" type="number" min="0" step="0.01" required />
          </div>
          <div class="field">
            <label for="size">Size</label>
            <input id="size" v-model.trim="form.size" type="text" placeholder="30 mL / 1 fl oz" required />
          </div>
          <div class="field">
            <label for="stock">Stock quantity</label>
            <input id="stock" v-model.number="form.stock" type="number" min="0" step="1" required />
          </div>
        </div>
      </fieldset>

      <fieldset class="section">
        <legend>How to use</legend>
        <div v-for="(step, i) in form.howToUse" :key="i" class="step-row">
          <span class="step-row__num">{{ i + 1 }}</span>
          <input v-model.trim="form.howToUse[i]" type="text" placeholder="Apply to clean, dry skin…" />
          <button type="button" class="icon-btn" @click="removeStep(i)" aria-label="Remove step">×</button>
        </div>
        <button type="button" class="btn-ghost" @click="addStep">+ Add step</button>
      </fieldset>

      <fieldset class="section">
        <legend>Full ingredient list (INCI)</legend>
        <textarea v-model.trim="form.ingredients" rows="3" placeholder="Aqua, Niacinamide, Pentylene Glycol…" required />
      </fieldset>

      <fieldset class="section">
        <legend>Image URL</legend>
        <div class="field">
          <input v-model.trim="form.image" type="url" placeholder="https://…" required />
        </div>
        <div v-if="form.image" class="preview">
          <img :src="form.image" alt="Product preview" @error="imgError = true" @load="imgError = false" />
          <span v-if="imgError" class="preview__error">Couldn't load that image.</span>
        </div>
      </fieldset>

      <div class="actions">
        <p v-if="errorMsg" class="error">{{ errorMsg }}</p>
        <button type="submit" class="btn-primary">Publish formula</button>
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

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
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

const categories = ["Serum", "Moisturizer", "Cleanser", "Treatment", "Sun Care", "Mask"];
const skinTypeOptions = ["Oily", "Dry", "Combination", "Sensitive", "Normal"];

const imgError = ref(false);
const errorMsg = ref("");

const form = reactive({
  name: "",
  category: "",
  sku: "",
  tagline: "",
  description: "",
  skinTypes: [],
  actives: [{ name: "", concentration: null }],
  price: null,
  size: "",
  stock: null,
  howToUse: [""],
  ingredients: "",
  image: "",
});

function addActive() {
  form.actives.push({ name: "", concentration: null });
}
function removeActive(i) {
  form.actives.splice(i, 1);
}
function addStep() {
  form.howToUse.push("");
}
function removeStep(i) {
  form.howToUse.splice(i, 1);
}

function handleSubmit() {
  errorMsg.value = "";

  if (!form.actives.some((a) => a.name && a.concentration != null)) {
    errorMsg.value = "Add at least one active ingredient with a concentration.";
    return;
  }
  if (!form.howToUse.some((s) => s.trim())) {
    errorMsg.value = "Add at least one usage step.";
    return;
  }

  const payload = {
    ...form,
    id: form.sku.toLowerCase() || crypto.randomUUID(),
    actives: form.actives.filter((a) => a.name && a.concentration != null),
    howToUse: form.howToUse.filter((s) => s.trim()),
  };

  emit("create-product", payload);
  resetForm();
  close();
}

function resetForm() {
  Object.assign(form, {
    name: "",
    category: "",
    sku: "",
    tagline: "",
    description: "",
    skinTypes: [],
    actives: [{ name: "", concentration: null }],
    price: null,
    size: "",
    stock: null,
    howToUse: [""],
    ingredients: "",
    image: "",
  });
  imgError.value = false;
  errorMsg.value = "";
}
</script>

<style scoped>
@import "./tokens.css";

.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(35, 40, 33, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 4vh 1rem;
  overflow-y: auto;
  z-index: 1000;
}

.intake {
  position: relative;
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-body);
  padding: 2.25rem 1.75rem 2.5rem;
  width: 100%;
  max-width: 680px;
  border-radius: var(--radius-md);
  box-shadow: 0 24px 60px -12px rgba(35, 40, 33, 0.45);
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
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.9rem;
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

.hint {
  font-size: 0.72rem;
  color: var(--ink-faint);
  align-self: flex-end;
}

.checks {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.check {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-body);
  text-transform: none;
  letter-spacing: normal;
  font-size: 0.85rem;
  color: var(--ink);
}

.check input {
  width: auto;
}

.active-row,
.step-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.6rem;
}

.active-row__name {
  flex: 1;
}

.active-row__pct {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  width: 90px;
}

.active-row__pct input {
  width: 100%;
}

.step-row__num {
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--ink-faint);
  width: 1.2rem;
  text-align: right;
}

.step-row input {
  flex: 1;
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
</style>