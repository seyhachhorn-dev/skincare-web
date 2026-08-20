<template>
  <section class="detail">
    <button type="button" class="back" @click="$emit('back')">← Back to shop</button>

    <div class="detail__grid">
      <div class="gallery">
        <div class="gallery__main">
          <img :src="activeImage" :alt="product.name" />
        </div>
        <div class="gallery__thumbs" v-if="product.images?.length > 1">
          <button
            v-for="(img, i) in product.images"
            :key="i"
            type="button"
            class="gallery__thumb"
            :class="{ 'is-active': img === activeImage }"
            @click="activeImage = img"
          >
            <img :src="img" :alt="`${product.name} view ${i + 1}`" />
          </button>
        </div>
      </div>

      <div class="label">
        <p class="eyebrow">{{ product.category }} · SKU {{ product.sku }}</p>
        <h1 class="label__name">{{ product.name }}</h1>
        <p class="label__tagline">{{ product.tagline }}</p>

        <div class="tags">
          <span v-for="t in product.skinTypes" :key="t" class="tag">{{ t }}</span>
        </div>

        <p class="label__desc">{{ product.description }}</p>

        <div class="actives">
          <h2 class="section-title">Lead actives</h2>
          <div v-for="a in product.actives" :key="a.name" class="active-row">
            <div class="active-row__head">
              <span class="active-row__name">{{ a.name }}</span>
              <span class="active-row__pct">{{ a.concentration }}%</span>
            </div>
            <div class="meter">
              <div class="meter__ticks">
                <span v-for="n in 5" :key="n" class="meter__tick" />
              </div>
              <div
                class="meter__fill"
                :style="{ width: `${Math.min(a.concentration, 30) / 30 * 100}%` }"
              />
            </div>
          </div>
        </div>

        <div class="purchase">
          <span class="price">${{ product.price.toFixed(2) }}</span>
          <span class="size">{{ product.size }}</span>

          <div class="qty">
            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity">−</button>
            <span>{{ qty }}</span>
            <button type="button" @click="qty += 1" aria-label="Increase quantity">+</button>
          </div>

          <button type="button" class="btn-primary" @click="$emit('add-to-cart', { product, qty })">
            Add to bag — ${{ (product.price * qty).toFixed(2) }}
          </button>
        </div>

        <details class="disclosure" open>
          <summary>How to use</summary>
          <ol class="ritual">
            <li v-for="(step, i) in product.howToUse" :key="i">{{ step }}</li>
          </ol>
        </details>

        <details class="disclosure">
          <summary>Full ingredient list</summary>
          <p class="inci">{{ product.ingredients }}</p>
        </details>
      </div>
    </div>
  </section>
</template>

<script>
// Plain module-scope script, separate from <script setup>.
// defineProps() default-factories are hoisted outside setup(), so they can
// only see module-scope bindings like this — not consts declared in
// <script setup> itself.
const defaultProduct = {
  id: "sp-02",
  sku: "CD-10-030",
  name: "Clarity Drops",
  category: "Serum",
  tagline: "A weightless niacinamide fluid for tired, congested skin.",
  description:
    "Clarity Drops pairs 10% niacinamide with zinc PCA to calm visible redness and refine the look of pores, in a fluid light enough to layer under sunscreen. Formulated without fragrance for daily, year-round use.",
  price: 29,
  size: "30 mL / 1 fl oz",
  skinTypes: ["Oily", "Combination", "Sensitive"],
  actives: [
    { name: "Niacinamide", concentration: 10 },
    { name: "Zinc PCA", concentration: 1 },
  ],
  images: [
    "https://images.unsplash.com/photo-1620917669809-3cddf03fbbf0?w=800&q=80",
    "https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd?w=800&q=80",
    "https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&q=80",
  ],
  howToUse: [
    "Apply to clean, dry skin morning and night.",
    "Dispense two to three drops and press across face and neck.",
    "Follow with moisturizer, and SPF in the morning.",
  ],
  ingredients:
    "Aqua, Niacinamide, Pentylene Glycol, Zinc PCA, Tamarindus Indica Seed Gum, Xanthan Gum, Ethylhexylglycerin, Phenoxyethanol.",
};
</script>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import api from "@/lib/api";

const props = defineProps({
  product: {
    type: Object,
    default: () => defaultProduct,
  },
});

defineEmits(["add-to-cart", "back"]);

const qty = ref(1);
const activeImage = ref(props.product.images?.[0] ?? props.product.image);
const route = useRoute();

onMounted(async () => {
  if (!route.params.id) return;
  try {
    const response = await api.get(`/products/${route.params.id}`);
    Object.assign(props.product, response.data);
    activeImage.value = props.product.images?.[0] ?? props.product.image;
  } catch (error) {
    console.error("Failed to fetch product:", error);
  }
});
</script>

<style scoped>
@import "@/assets/tokens.css";

.detail {
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-body);
  padding: 2.5rem 1.5rem 4rem;
  min-height: 100%;
}

.back {
  display: inline-block;
  background: none;
  border: none;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  color: var(--ink-soft);
  cursor: pointer;
  padding: 0;
  margin: 0 auto 1.5rem;
  max-width: 1080px;
  width: 100%;
  display: block;
}

.back:hover {
  color: var(--moss-dark);
}

.detail__grid {
  max-width: 1080px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: start;
}

@media (max-width: 800px) {
  .detail__grid {
    grid-template-columns: 1fr;
  }
}

.gallery {
  position: sticky;
  top: 1.5rem;
}

.gallery__main {
  aspect-ratio: 4 / 5;
  background: var(--surface-alt);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.gallery__main img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.gallery__thumbs {
  display: flex;
  gap: 0.6rem;
  margin-top: 0.75rem;
}

.gallery__thumb {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 2px solid transparent;
  padding: 0;
  cursor: pointer;
  background: var(--surface-alt);
}

.gallery__thumb.is-active {
  border-color: var(--moss);
}

.gallery__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.eyebrow {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.5rem;
}

.label__name {
  font-family: var(--font-display);
  font-size: clamp(1.9rem, 3vw, 2.4rem);
  font-weight: 600;
  color: var(--moss-dark);
  margin: 0 0 0.4rem;
}

.label__tagline {
  color: var(--ink-soft);
  margin: 0 0 1rem;
  line-height: 1.5;
}

.tags {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}

.tag {
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border: 1px solid var(--line);
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  color: var(--ink-soft);
}

.label__desc {
  line-height: 1.6;
  color: var(--ink);
  margin: 0 0 1.75rem;
}

.section-title {
  font-family: var(--font-mono);
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-faint);
  margin: 0 0 0.75rem;
}

.active-row {
  margin-bottom: 0.85rem;
}

.active-row__head {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.3rem;
}

.active-row__name {
  color: var(--ink);
}

.active-row__pct {
  font-family: var(--font-mono);
  color: var(--ink-faint);
}

.meter {
  position: relative;
  height: 6px;
  background: var(--surface-alt);
  border-radius: 999px;
}

.meter__ticks {
  position: absolute;
  inset: 0;
  display: flex;
  justify-content: space-between;
}

.meter__tick {
  width: 1px;
  height: 100%;
  background: var(--bg);
}

.meter__fill {
  position: absolute;
  inset: 0 auto 0 0;
  background: var(--moss);
  border-radius: 999px;
}

.purchase {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 0.5rem 1rem;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  padding: 1.25rem 0;
  margin: 1.75rem 0;
}

.price {
  font-family: var(--font-mono);
  font-size: 1.4rem;
  color: var(--ink);
}

.size {
  font-size: 0.8rem;
  color: var(--ink-faint);
  justify-self: start;
}

.qty {
  grid-column: 1 / -1;
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  width: fit-content;
}

.qty button {
  background: none;
  border: none;
  width: 2.1rem;
  height: 2.1rem;
  font-size: 1rem;
  cursor: pointer;
  color: var(--ink);
}

.qty span {
  min-width: 2rem;
  text-align: center;
  font-family: var(--font-mono);
}

.btn-primary {
  grid-column: 1 / -1;
  background: var(--moss);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.85rem 1.25rem;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.15s var(--ease);
}

.btn-primary:hover {
  background: var(--moss-dark);
}

.disclosure {
  border-bottom: 1px solid var(--line);
  padding: 0.9rem 0;
}

.disclosure summary {
  cursor: pointer;
  font-family: var(--font-display);
  font-weight: 600;
  color: var(--moss-dark);
  font-size: 1.05rem;
}

.ritual {
  margin: 0.85rem 0 0;
  padding-left: 1.2rem;
  line-height: 1.7;
  color: var(--ink-soft);
}

.inci {
  margin: 0.85rem 0 0;
  line-height: 1.7;
  color: var(--ink-soft);
  font-size: 0.85rem;
}
</style>