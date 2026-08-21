<template>
  <section class="checkout">
    <header class="checkout__head">
      <button type="button" class="back" @click="$emit('back')">← Back to bag</button>
      <p class="eyebrow">Field Apothecary — Checkout</p>
      <h1 class="checkout__title">Complete Your Order</h1>
    </header>

    <div class="grid">
      <form class="main" @submit.prevent="placeOrder">
        <fieldset class="section">
          <legend><span class="step-num">1</span> Contact</legend>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" v-model.trim="form.email" type="email" placeholder="you@example.com" required />
          </div>
        </fieldset>

        <fieldset class="section">
          <legend><span class="step-num">2</span> Shipping address</legend>
          <div class="row row--2">
            <div class="field">
              <label for="firstName">First name</label>
              <input id="firstName" v-model.trim="form.firstName" type="text" required />
            </div>
            <div class="field">
              <label for="lastName">Last name</label>
              <input id="lastName" v-model.trim="form.lastName" type="text" required />
            </div>
          </div>
          <div class="field">
            <label for="address1">Address</label>
            <input id="address1" v-model.trim="form.address1" type="text" placeholder="Street address" required />
          </div>
          <div class="field">
            <label for="address2">Apartment, suite, etc. (optional)</label>
            <input id="address2" v-model.trim="form.address2" type="text" />
          </div>
          <div class="row row--3">
            <div class="field">
              <label for="city">City</label>
              <input id="city" v-model.trim="form.city" type="text" required />
            </div>
            <div class="field">
              <label for="state">State</label>
              <input id="state" v-model.trim="form.state" type="text" required />
            </div>
            <div class="field">
              <label for="zip">ZIP code</label>
              <input id="zip" v-model.trim="form.zip" type="text" required />
            </div>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" v-model.trim="form.phone" type="tel" placeholder="For delivery updates" required />
          </div>
        </fieldset>

        <fieldset class="section">
          <legend><span class="step-num">3</span> Shipping method</legend>
          <label v-for="m in shippingMethods" :key="m.id" class="option-row">
            <input type="radio" name="shipping" :value="m.id" v-model="form.shippingMethodId" />
            <span class="option-row__body">
              <span class="option-row__name">{{ m.name }}</span>
              <span class="option-row__sub">{{ m.eta }}</span>
            </span>
            <span class="option-row__price mono">{{ m.price === 0 ? "Free" : `$${m.price.toFixed(2)}` }}</span>
          </label>
        </fieldset>

        <fieldset class="section">
          <legend><span class="step-num">4</span> Payment</legend>
          <p class="section__hint">This is a demo form — no real payment is processed.</p>
          <div class="field">
            <label for="cardName">Name on card</label>
            <input id="cardName" v-model.trim="form.cardName" type="text" required />
          </div>
          <div class="field">
            <label for="cardNumber">Card number</label>
            <input
              id="cardNumber"
              v-model.trim="form.cardNumber"
              type="text"
              inputmode="numeric"
              placeholder="1234 1234 1234 1234"
              maxlength="19"
              required
            />
          </div>
          <div class="row row--2">
            <div class="field">
              <label for="cardExpiry">Expiry</label>
              <input id="cardExpiry" v-model.trim="form.cardExpiry" type="text" placeholder="MM / YY" maxlength="7" required />
            </div>
            <div class="field">
              <label for="cardCvc">CVC</label>
              <input id="cardCvc" v-model.trim="form.cardCvc" type="text" inputmode="numeric" placeholder="123" maxlength="4" required />
            </div>
          </div>
        </fieldset>

        <p v-if="errorMsg" class="error">{{ errorMsg }}</p>

        <button type="submit" class="btn-primary btn-primary--mobile" :disabled="submitting">
          {{ submitting ? "Placing order…" : `Place order — $${total.toFixed(2)}` }}
        </button>
      </form>

      <aside class="summary">
        <h2 class="summary__title">Order summary</h2>

        <ul class="summary__items">
          <li v-for="item in items" :key="item.id" class="summary__item">
            <div class="summary__thumb">
              <img :src="item.image" :alt="item.name" />
              <span class="summary__qty">{{ item.qty }}</span>
            </div>
            <div class="summary__info">
              <p class="summary__name">{{ item.name }}</p>
              <p class="summary__sub">{{ item.category }}</p>
            </div>
            <span class="mono">${{ (item.price * item.qty).toFixed(2) }}</span>
          </li>
        </ul>

        <div class="promo">
          <input v-model.trim="promoCode" type="text" placeholder="Promo code" />
          <button type="button" class="btn-ghost" @click="applyPromo">Apply</button>
        </div>
        <p v-if="promoMessage" class="promo__msg" :class="{ 'is-error': !promoApplied }">{{ promoMessage }}</p>

        <div class="totals">
          <div class="totals__row">
            <span>Subtotal</span>
            <span class="mono">${{ subtotal.toFixed(2) }}</span>
          </div>
          <div class="totals__row">
            <span>Shipping</span>
            <span class="mono">{{ selectedShipping.price === 0 ? "Free" : `$${selectedShipping.price.toFixed(2)}` }}</span>
          </div>
          <div class="totals__row">
            <span>Tax</span>
            <span class="mono">${{ tax.toFixed(2) }}</span>
          </div>
          <div v-if="discount" class="totals__row totals__row--discount">
            <span>Discount</span>
            <span class="mono">−${{ discount.toFixed(2) }}</span>
          </div>
          <div class="totals__row totals__row--total">
            <span>Total</span>
            <span class="mono">${{ total.toFixed(2) }}</span>
          </div>
        </div>

        <button type="button" class="btn-primary btn-primary--desktop" :disabled="submitting" @click="placeOrder">
          {{ submitting ? "Placing order…" : `Place order — $${total.toFixed(2)}` }}
        </button>

        <p class="secure-note">🔒 Secure checkout · your details are encrypted</p>
      </aside>
    </div>
  </section>
</template>

<script>
// Module-scope fallback data — kept separate from <script setup> so
// defineProps() default-factories (hoisted outside setup()) can reference it.
const defaultItems = [
  {
    id: "sp-02",
    name: "Clarity Drops",
    category: "Serum",
    price: 29,
    qty: 1,
    image: "https://images.unsplash.com/photo-1620917669809-3cddf03fbbf0?w=200&q=80",
  },
  {
    id: "sp-04",
    name: "Quiet Cream",
    category: "Moisturizer",
    price: 34,
    qty: 2,
    image: "https://images.unsplash.com/photo-1556228720-195a672e8a03?w=200&q=80",
  },
];

const defaultShippingMethods = [
  { id: "dhl", name: "DHL", eta: "5–7 business days", price: 0 },
  { id: "inpost", name: "InPost", eta: "2–3 business days", price: 8.5 },
];
</script>

<script setup>
import { reactive, ref, computed } from "vue";
import api from "@/lib/api";

const props = defineProps({
  items: {
    type: Array,
    default: () => defaultItems,
  },
  shippingMethods: {
    type: Array,
    default: () => defaultShippingMethods,
  },
  taxRate: {
    type: Number,
    default: 0.08,
  },
});

const emit = defineEmits(["back", "place-order"]);

const form = reactive({
  email: "",
  firstName: "",
  lastName: "",
  address1: "",
  address2: "",
  city: "",
  state: "",
  zip: "",
  phone: "",
  shippingMethodId: props.shippingMethods[0]?.id ?? "",
  cardName: "",
  cardNumber: "",
  cardExpiry: "",
  cardCvc: "",
});

const errorMsg = ref("");
const submitting = ref(false);
const promoCode = ref("");
const promoMessage = ref("");
const promoApplied = ref(false);
const discount = ref(0);

const subtotal = computed(() =>
  props.items.reduce((sum, i) => sum + i.price * i.qty, 0)
);

const selectedShipping = computed(
  () =>
    props.shippingMethods.find((m) => m.id === form.shippingMethodId) ??
    props.shippingMethods[0] ?? { price: 0 }
);

const tax = computed(() => (subtotal.value - discount.value) * props.taxRate);

const total = computed(
  () => subtotal.value - discount.value + selectedShipping.value.price + tax.value
);

function applyPromo() {
  const code = promoCode.value.trim().toUpperCase();
  if (!code) return;
  // Demo logic — replace with a real promo lookup.
  if (code === "APOTHECARY10") {
    discount.value = subtotal.value * 0.1;
    promoApplied.value = true;
    promoMessage.value = "10% off applied.";
  } else {
    discount.value = 0;
    promoApplied.value = false;
    promoMessage.value = "That code isn't valid.";
  }
}

async function placeOrder() {
  errorMsg.value = "";

  if (!props.items.length) {
    errorMsg.value = "Your bag is empty.";
    return;
  }

  submitting.value = true;

  try {
    await Promise.all(props.items.map((item) => api.post("/cart/items", {
      product_id: item.id,
      quantity: item.qty,
    })));

    const addressResponse = await api.post("/addresses", {
      province: form.state,
      district: form.city,
      commune: form.city,
      house_no: `${form.firstName} ${form.lastName}, ${form.address1}`,
      pickup_point: form.zip,
      location: form.address2 || null,
      type: "home",
      is_default: true,
    });

    const response = await api.post("/orders", {
      address_id: addressResponse.data.id,
      payment_method: "bakong_khqr",
      shipping_method: form.shippingMethodId,
    });
    emit("place-order", response.data);
    submitting.value = false;
  } catch (error) {
    console.error("Failed to place order:", error);
    errorMsg.value = "Failed to place order. Please try again.";
    submitting.value = false;
  }
}
</script>

<style scoped>
@import "@/assets/tokens.css";

.checkout {
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-body);
  padding: 2rem 1.5rem 4rem;
  min-height: 100%;
}

.checkout__head {
  max-width: 1080px;
  margin: 0 auto 2rem;
}

.back {
  display: block;
  background: none;
  border: none;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  color: var(--ink-soft);
  cursor: pointer;
  padding: 0;
  margin-bottom: 1.25rem;
}

.back:hover {
  color: var(--moss-dark);
}

.eyebrow {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.6rem;
}

.checkout__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: clamp(1.9rem, 3.5vw, 2.5rem);
  margin: 0;
  color: var(--moss-dark);
}

.grid {
  max-width: 1080px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 860px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.section {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.4rem 1.5rem;
  margin: 0 0 1.25rem;
}

.section legend {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--moss-dark);
  padding: 0 0.3rem;
}

.step-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.4rem;
  height: 1.4rem;
  border-radius: 50%;
  background: var(--moss);
  color: #fff;
  font-family: var(--font-mono);
  font-size: 0.75rem;
}

.section__hint {
  font-size: 0.8rem;
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
  gap: 0.9rem;
}

.row--2 {
  grid-template-columns: 1fr 1fr;
}

.row--3 {
  grid-template-columns: 1.4fr 1fr 1fr;
}

.row .field {
  margin-bottom: 1rem;
}

@media (max-width: 480px) {
  .row--2,
  .row--3 {
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

input {
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

input:focus {
  outline: 2px solid var(--moss);
  outline-offset: 1px;
}

.option-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  padding: 0.75rem 0.9rem;
  margin-bottom: 0.6rem;
  cursor: pointer;
  transition: border-color 0.15s var(--ease);
}

.option-row:last-child {
  margin-bottom: 0;
}

.option-row:has(input:checked) {
  border-color: var(--moss);
  background: var(--surface-alt);
}

.option-row input {
  width: auto;
}

.option-row__body {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.option-row__name {
  font-size: 0.9rem;
  color: var(--ink);
}

.option-row__sub {
  font-size: 0.78rem;
  color: var(--ink-faint);
}

.option-row__price {
  font-size: 0.88rem;
}

.mono {
  font-family: var(--font-mono);
}

.error {
  color: var(--danger);
  font-size: 0.85rem;
  margin: 0 0 1rem;
}

.btn-primary {
  width: 100%;
  background: var(--moss);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.9rem 1.25rem;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background 0.15s var(--ease);
}

.btn-primary:hover:not(:disabled) {
  background: var(--moss-dark);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary--mobile {
  display: none;
}

@media (max-width: 860px) {
  .btn-primary--mobile {
    display: block;
  }
  .btn-primary--desktop {
    display: none;
  }
}

.summary {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.4rem 1.5rem;
  position: sticky;
  top: 1.5rem;
}

.summary__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--moss-dark);
  margin: 0 0 1rem;
}

.summary__items {
  list-style: none;
  padding: 0;
  margin: 0 0 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.summary__item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.summary__thumb {
  position: relative;
  flex-shrink: 0;
}

.summary__thumb img {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  background: var(--surface-alt);
  display: block;
}

.summary__qty {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--moss-dark);
  color: #fff;
  font-family: var(--font-mono);
  font-size: 0.65rem;
  width: 1.1rem;
  height: 1.1rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.summary__info {
  flex: 1;
  min-width: 0;
}

.summary__name {
  margin: 0;
  font-size: 0.85rem;
  color: var(--ink);
}

.summary__sub {
  margin: 0.1rem 0 0;
  font-size: 0.74rem;
  color: var(--ink-faint);
}

.promo {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.promo input {
  flex: 1;
}

.btn-ghost {
  background: none;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  color: var(--moss-dark);
  font-family: var(--font-body);
  font-size: 0.85rem;
  padding: 0 0.9rem;
  cursor: pointer;
}

.btn-ghost:hover {
  border-color: var(--moss);
  background: var(--surface-alt);
}

.promo__msg {
  font-size: 0.76rem;
  color: var(--moss-dark);
  margin: 0 0 0.9rem;
}

.promo__msg.is-error {
  color: var(--danger);
}

.totals {
  border-top: 1px solid var(--line);
  padding-top: 0.9rem;
  margin-bottom: 1.1rem;
}

.totals__row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: var(--ink-soft);
  padding: 0.3rem 0;
}

.totals__row--discount {
  color: var(--moss-dark);
}

.totals__row--total {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--ink);
  padding-top: 0.6rem;
  margin-top: 0.3rem;
  border-top: 1px solid var(--line);
}

.secure-note {
  text-align: center;
  font-size: 0.75rem;
  color: var(--ink-faint);
  margin: 0.9rem 0 0;
}
</style>