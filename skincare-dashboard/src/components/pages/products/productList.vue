<template>
  <section class="catalog">
    <header class="catalog__head">
      <p class="eyebrow">Field Apothecary — Formulary No. 04</p>
      <h1 class="catalog__title">Shop the Range</h1>
      <p class="catalog__sub">
        Every formula listed with its lead active and the concentration it's
        dosed at, the way a dispensary label would read it.
      </p>
    </header>

    <div class="ticket">
      <div class="ticket__field">
        <label for="q">Search</label>
        <input
          id="q"
          v-model="query"
          type="text"
          placeholder="serum, retinal, barrier…"
          autocomplete="off"
        />
      </div>

      <div class="ticket__field">
        <label for="cat">Category</label>
        <select id="cat" v-model="category">
          <option value="all">All categories</option>
          <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>

      <div class="ticket__field">
        <label for="sort">Sort</label>
        <select id="sort" v-model="sort">
          <option value="name">Name, A–Z</option>
          <option value="price-asc">Price, low–high</option>
          <option value="price-desc">Price, high–low</option>
          <option value="conc">Active %, high–low</option>
        </select>
      </div>

      <p class="ticket__count">{{ filtered.length }} formulas</p>
    </div>

    <TransitionGroup name="card" tag="div" class="grid">
      <article
        v-for="p in filtered"
        :key="p.id"
        class="card"
        @click="$emit('select-product', p.id)"
      >
        <div class="card__image">
          <img :src="p.image" :alt="p.name" loading="lazy" />
          <span v-if="p.newArrival" class="card__tag">New</span>
        </div>

        <div class="card__body">
          <p class="card__eyebrow">{{ p.category }} · {{ p.activeIngredient }}</p>
          <h2 class="card__name">{{ p.name }}</h2>

          <div class="meter" :aria-label="`${p.concentration}% ${p.activeIngredient}`">
            <div class="meter__ticks">
              <span v-for="n in 5" :key="n" class="meter__tick" />
            </div>
            <div class="meter__fill" :style="{ width: meterWidth(p.concentration) }" />
            <span class="meter__value">{{ p.concentration }}%</span>
          </div>

          <div class="card__foot">
            <span class="price">${{ p.price.toFixed(2) }}</span>
            <button
              type="button"
              class="btn-add"
              @click.stop="$emit('add-to-cart', p)"
            >
              Add
            </button>
          </div>
        </div>
      </article>
    </TransitionGroup>

    <p v-if="filtered.length === 0" class="empty">
      Nothing matches that search. Try a different active or category.
    </p>
  </section>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  products: {
    type: Array,
    default: () => defaultProducts,
  },
});

defineEmits(["select-product", "add-to-cart"]);

const query = ref("");
const category = ref("all");
const sort = ref("name");

const categories = computed(() =>
  [...new Set(props.products.map((p) => p.category))].sort()
);

const meterWidth = (pct) => `${Math.min(pct, 30) / 30 * 100}%`;

const filtered = computed(() => {
  let list = props.products.filter((p) => {
    const q = query.value.trim().toLowerCase();
    const matchesQuery =
      !q ||
      p.name.toLowerCase().includes(q) ||
      p.activeIngredient.toLowerCase().includes(q);
    const matchesCategory = category.value === "all" || p.category === category.value;
    return matchesQuery && matchesCategory;
  });

  switch (sort.value) {
    case "price-asc":
      list = [...list].sort((a, b) => a.price - b.price);
      break;
    case "price-desc":
      list = [...list].sort((a, b) => b.price - a.price);
      break;
    case "conc":
      list = [...list].sort((a, b) => b.concentration - a.concentration);
      break;
    default:
      list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  }
  return list;
});

// Fallback mock data so the component renders standalone.
// Replace with a real fetch from your product API / store.
const defaultProducts = [
  {
    id: "sp-01",
    name: "Barrier Balm",
    category: "Moisturizer",
    activeIngredient: "Ceramide NP",
    concentration: 5,
    price: 38,
    image: "https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600&q=80",
    newArrival: true,
  },
  {
    id: "sp-02",
    name: "Clarity Drops",
    category: "Serum",
    activeIngredient: "Niacinamide",
    concentration: 10,
    price: 29,
    image: "https://images.unsplash.com/photo-1620917669809-3cddf03fbbf0?w=600&q=80",
  },
  {
    id: "sp-03",
    name: "Night Retinal",
    category: "Treatment",
    activeIngredient: "Retinal",
    concentration: 0.2,
    price: 54,
    image: "https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd?w=600&q=80",
  },
  {
    id: "sp-04",
    name: "Quiet Cream",
    category: "Moisturizer",
    activeIngredient: "Centella Asiatica",
    concentration: 8,
    price: 34,
    image: "https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&q=80",
  },
  {
    id: "sp-05",
    name: "Vitamin C Fluid",
    category: "Serum",
    activeIngredient: "Ascorbic Acid",
    concentration: 15,
    price: 42,
    image: "https://images.unsplash.com/photo-1571875257727-256c39da42af?w=600&q=80",
    newArrival: true,
  },
  {
    id: "sp-06",
    name: "Mineral Veil SPF",
    category: "Sun Care",
    activeIngredient: "Zinc Oxide",
    concentration: 18,
    price: 26,
    image: "https://images.unsplash.com/photo-1600428853876-fb5a850b444c?w=600&q=80",
  },
];
</script>

<style scoped>
@import "./tokens.css";

.catalog {
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-body);
  padding: 3rem 1.5rem 4rem;
  min-height: 100%;
}

.catalog__head {
  max-width: 640px;
  margin: 0 auto 2.5rem;
  text-align: center;
}

.eyebrow {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.75rem;
}

.catalog__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: clamp(2rem, 4vw, 2.75rem);
  margin: 0 0 0.75rem;
  color: var(--moss-dark);
}

.catalog__sub {
  color: var(--ink-soft);
  line-height: 1.55;
  margin: 0;
}

.ticket {
  max-width: 1080px;
  margin: 0 auto 2rem;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.1rem 1.25rem;
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 1.25rem;
}

.ticket__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1 1 160px;
}

.ticket__field label {
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-faint);
}

.ticket__field input,
.ticket__field select {
  font-family: var(--font-body);
  font-size: 0.9rem;
  padding: 0.5rem 0.6rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--ink);
}

.ticket__field input:focus,
.ticket__field select:focus {
  outline: 2px solid var(--moss);
  outline-offset: 1px;
}

.ticket__count {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  color: var(--ink-faint);
  margin: 0 0 0.55rem;
  white-space: nowrap;
}

.grid {
  max-width: 1080px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 1.25rem;
}

.card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.18s var(--ease), box-shadow 0.18s var(--ease);
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px -12px rgba(35, 40, 33, 0.25);
}

.card:focus-visible {
  outline: 2px solid var(--moss);
  outline-offset: 2px;
}

.card__image {
  position: relative;
  aspect-ratio: 4 / 3;
  background: var(--surface-alt);
}

.card__image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.card__tag {
  position: absolute;
  top: 0.6rem;
  left: 0.6rem;
  font-family: var(--font-mono);
  font-size: 0.65rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: var(--moss-dark);
  color: var(--bg);
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
}

.card__body {
  padding: 0.9rem 1rem 1.1rem;
}

.card__eyebrow {
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.3rem;
}

.card__name {
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0 0 0.6rem;
  color: var(--moss-dark);
}

.meter {
  position: relative;
  height: 6px;
  background: var(--surface-alt);
  border-radius: 999px;
  margin-bottom: 0.85rem;
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

.meter__value {
  position: absolute;
  top: -1.3rem;
  right: 0;
  font-family: var(--font-mono);
  font-size: 0.68rem;
  color: var(--ink-faint);
}

.card__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.price {
  font-family: var(--font-mono);
  font-size: 0.95rem;
  color: var(--ink);
}

.btn-add {
  font-family: var(--font-body);
  font-size: 0.8rem;
  font-weight: 600;
  background: var(--moss);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  transition: background 0.15s var(--ease);
}

.btn-add:hover {
  background: var(--moss-dark);
}

.empty {
  text-align: center;
  color: var(--ink-faint);
  padding: 3rem 0;
}

.card-move,
.card-enter-active,
.card-leave-active {
  transition: all 0.25s var(--ease);
}

.card-enter-from,
.card-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>