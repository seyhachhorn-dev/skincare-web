<template>
  <section class="detail">
    <div class="topbar">
      <button type="button" class="back" @click="goBack">← Back to orders</button>
      <div class="topbar__actions">
        <button type="button" class="btn-ghost" @click="$emit('print-invoice', order)">
          Print invoice
        </button>
        <button
          v-if="order.status !== 'cancelled' && order.status !== 'delivered'"
          type="button"
          class="btn-ghost btn-ghost--danger"
          @click="confirmCancel"
        >
          Cancel order
        </button>
      </div>
    </div>

    <header class="head">
      <div>
        <p class="eyebrow">Order</p>
        <h1 class="head__title">#{{ order.number }}</h1>
        <p class="head__meta">
          Placed {{ formatDate(order.placedAt) }} · {{ order.items.length }} item{{ order.items.length === 1 ? "" : "s" }}
        </p>
      </div>

      <div class="head__status">
        <label for="status" class="head__status-label">Status</label>
        <select
          id="status"
          v-model="localStatus"
          class="head__status-select"
          :class="`head__status-select--${localStatus}`"
          :disabled="savingStatus"
          @change="saveStatus"
        >
          <option value="awaiting_payment">Awaiting payment</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <span v-if="savingStatus" class="head__status-saving">Saving…</span>
      </div>
    </header>

    <div class="tracking-inline" v-if="localStatus === 'shipped' || order.trackingNumber">
      <label for="tracking">Tracking number</label>
      <input id="tracking" v-model.trim="trackingInput" type="text" placeholder="1Z999AA10123456784" @blur="saveTracking" />
    </div>

    <ol class="timeline" v-if="order.status !== 'cancelled'">
      <li
        v-for="(step, i) in timelineSteps"
        :key="step.key"
        class="timeline__step"
        :class="{
          'is-done': i < currentStepIndex,
          'is-current': i === currentStepIndex,
        }"
      >
        <span class="timeline__dot" />
        <span class="timeline__label">{{ step.label }}</span>
        <span class="timeline__date">{{ step.date ? formatDate(step.date) : "" }}</span>
      </li>
    </ol>
    <p v-else class="cancelled-note">This order was cancelled{{ order.cancelledAt ? " on " + formatDate(order.cancelledAt) : "" }}.</p>

    <div class="grid">
      <div class="main">
        <div class="panel">
          <h2 class="panel__title">Items</h2>
          <table class="items">
            <thead>
              <tr>
                <th>Formula</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in order.items" :key="item.id">
                <td class="items__product">
                  <img :src="item.image" :alt="item.name" />
                  <div>
                    <p class="items__name">{{ item.name }}</p>
                    <p class="items__sub">{{ item.category }} · {{ item.activeIngredient }}</p>
                  </div>
                </td>
                <td class="mono">{{ item.qty }}</td>
                <td class="mono">${{ item.price.toFixed(2) }}</td>
                <td class="mono">${{ (item.price * item.qty).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="totals">
            <div class="totals__row">
              <span>Subtotal</span>
              <span class="mono">${{ order.subtotal.toFixed(2) }}</span>
            </div>
            <div class="totals__row">
              <span>Shipping</span>
              <span class="mono">${{ order.shipping.toFixed(2) }}</span>
            </div>
            <div class="totals__row">
              <span>Tax</span>
              <span class="mono">${{ order.tax.toFixed(2) }}</span>
            </div>
            <div v-if="order.discount" class="totals__row totals__row--discount">
              <span>Discount</span>
              <span class="mono">−${{ order.discount.toFixed(2) }}</span>
            </div>
            <div class="totals__row totals__row--total">
              <span>Total</span>
              <span class="mono">${{ order.total.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <div class="panel" v-if="order.notes">
          <h2 class="panel__title">Notes</h2>
          <p class="notes">{{ order.notes }}</p>
        </div>
      </div>

      <aside class="side">
        <div class="panel">
          <h2 class="panel__title">Customer</h2>
          <p class="customer__name">{{ order.customer.name }}</p>
          <p class="customer__line">{{ order.customer.email }}</p>
          <p class="customer__line">{{ order.customer.phone }}</p>
        </div>

        <div class="panel">
          <h2 class="panel__title">Shipping address</h2>
          <p class="address">
            {{ order.shippingAddress.line1 }}<br />
            <template v-if="order.shippingAddress.line2">{{ order.shippingAddress.line2 }}<br /></template>
            {{ order.shippingAddress.city }}, {{ order.shippingAddress.state }} {{ order.shippingAddress.zip }}<br />
            {{ order.shippingAddress.country }}
          </p>
        </div>

        <div class="panel">
          <h2 class="panel__title">Payment</h2>
          <p class="customer__line">{{ order.payment.method }}</p>
          <p class="customer__line">{{ order.payment.last4 ? `Ending in ${order.payment.last4}` : "" }}</p>
        </div>
      </aside>
    </div>
  </section>
</template>

<script>
// Module-scope fallback data — kept separate from <script setup> so
// defineProps() default-factories (hoisted outside setup()) can reference it.
const defaultOrder = {
  id: "ord-1042",
  number: "1042",
  status: "processing",
  placedAt: "2026-08-15T14:30:00Z",
  shippedAt: null,
  deliveredAt: null,
  cancelledAt: null,
  trackingNumber: "",
  notes: "Customer asked for gift wrapping and a handwritten note.",
  customer: {
    name: "Elena Marsh",
    email: "elena.marsh@example.com",
    phone: "+1 (555) 019-2244",
  },
  shippingAddress: {
    line1: "482 Willow Creek Rd",
    line2: "Apt 3B",
    city: "Asheville",
    state: "NC",
    zip: "28801",
    country: "United States",
  },
  payment: {
    method: "Visa",
    last4: "4242",
  },
  items: [
    {
      id: "sp-02",
      name: "Clarity Drops",
      category: "Serum",
      activeIngredient: "Niacinamide",
      price: 29,
      qty: 2,
      image: "https://images.unsplash.com/photo-1620917669809-3cddf03fbbf0?w=200&q=80",
    },
    {
      id: "sp-04",
      name: "Quiet Cream",
      category: "Moisturizer",
      activeIngredient: "Centella Asiatica",
      price: 34,
      qty: 1,
      image: "https://images.unsplash.com/photo-1556228720-195a672e8a03?w=200&q=80",
    },
  ],
  subtotal: 92,
  shipping: 6.5,
  tax: 7.36,
  discount: 0,
  total: 105.86,
};
</script>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import api from "@/lib/api";

const props = defineProps({
  id: {
    type: [String, Number],
    default: "",
  },
  order: {
    type: Object,
    default: () => defaultOrder,
  },
});

const emit = defineEmits(["back", "update-status", "cancel-order", "print-invoice"]);
const router = useRouter();

const order = ref(props.order);
const localStatus = ref(props.order.status);
const trackingInput = ref(props.order.trackingNumber || "");

onMounted(async () => {
  if (!props.id) return;
  try {
    const response = await api.get("/admin/orders");
    const found = response.data.find((item) => String(item.id) === String(props.id));
    if (found) order.value = normalizeOrder(found);
  } catch (error) {
    console.error("Failed to fetch order:", error);
  }
});

watch(
  () => order.value,
  (o) => {
    localStatus.value = o.status;
    trackingInput.value = o.trackingNumber || "";
  },
  { immediate: true }
);

const timelineSteps = computed(() => [
  { key: "pending", label: "Placed", date: order.value.placedAt },
  { key: "processing", label: "Processing", date: order.value.placedAt },
  { key: "shipped", label: "Shipped", date: order.value.shippedAt },
  { key: "delivered", label: "Delivered", date: order.value.deliveredAt },
]);

const statusOrder = ["pending", "processing", "shipped", "delivered"];

const currentStepIndex = computed(() => {
  const idx = statusOrder.indexOf(order.value.status);
  return idx === -1 ? 0 : idx;
});

function statusLabel(status) {
  return {
    awaiting_payment: "Awaiting payment",
    processing: "Processing",
    shipped: "Shipped",
    delivered: "Delivered",
    cancelled: "Cancelled",
  }[status] ?? status;
}

function formatDate(iso) {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function normalizeOrder(rawOrder) {
  const items = (rawOrder.items ?? []).map((item) => ({
    id: item.id,
    name: item.product?.name ?? "Product",
    category: item.product?.brand ?? "Skincare",
    activeIngredient: item.product?.description ?? "",
    price: Number(item.unit_price ?? item.product?.price ?? 0),
    qty: Number(item.quantity ?? 0),
    image: item.product?.image ?? "",
  }));
  const total = Number(rawOrder.total ?? 0);
  return {
    ...rawOrder,
    number: String(rawOrder.order_number ?? rawOrder.id),
    placedAt: rawOrder.date,
    trackingNumber: rawOrder.tracking_number ?? "",
    shippedAt: rawOrder.shipped_at ?? null,
    deliveredAt: rawOrder.delivered_at ?? null,
    cancelledAt: rawOrder.cancelled_at ?? null,
    items,
    subtotal: total,
    shipping: 0,
    tax: 0,
    discount: 0,
    total,
    customer: rawOrder.customer ?? { name: "", email: "", phone: "" },
    shippingAddress: rawOrder.address ?? { line1: "", line2: "", city: "", state: "", zip: "", country: "" },
    payment: { method: rawOrder.payment_method ?? "", last4: "" },
  };
}

const savingStatus = ref(false);

function goBack() {
  router.push({ name: "Orders" });
}

async function saveStatus() {
  const previousStatus = order.value.status;
  savingStatus.value = true;
  try {
    const response = await api.patch(`/admin/orders/${order.value.id}/status`, {
      status: localStatus.value,
    });
    order.value = normalizeOrder(response.data);
    emit("update-status", order.value);
  } catch (error) {
    console.error("Failed to update order status:", error);
    window.alert("Unable to update this order. Please try again.");
    localStatus.value = previousStatus;
  } finally {
    savingStatus.value = false;
  }
}

async function saveTracking() {
  if (trackingInput.value === (order.value.trackingNumber || "")) return;
  try {
    const response = await api.patch(`/admin/orders/${order.value.id}/status`, {
      status: localStatus.value,
      tracking_number: trackingInput.value,
    });
    order.value = normalizeOrder(response.data);
    emit("update-status", order.value);
  } catch (error) {
    console.error("Failed to update tracking number:", error);
    window.alert("Unable to save tracking number. Please try again.");
  }
}

async function confirmCancel() {
  if (!window.confirm(`Cancel order #${order.value.number}? This can't be undone.`)) return;
  localStatus.value = "cancelled";
  await saveStatus();
  emit("cancel-order", order.value);
}
</script>

<style scoped>
@import "@/assets/tokens.css";

.detail {
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-body);
  padding: 2rem 1.5rem 4rem;
  min-height: 100%;
}

.topbar {
  max-width: 1080px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.back {
  background: none;
  border: none;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  color: var(--ink-soft);
  cursor: pointer;
  padding: 0;
}

.back:hover {
  color: var(--moss-dark);
}

.topbar__actions {
  display: flex;
  gap: 0.6rem;
}

.btn-ghost {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  color: var(--ink);
  font-family: var(--font-body);
  font-size: 0.82rem;
  padding: 0.5rem 0.9rem;
  cursor: pointer;
  transition: border-color 0.15s var(--ease);
}

.btn-ghost:hover {
  border-color: var(--moss);
}

.btn-ghost--danger {
  color: var(--danger);
}

.btn-ghost--danger:hover {
  border-color: var(--danger);
}

.head {
  max-width: 1080px;
  margin: 0 auto 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.eyebrow {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--amber);
  margin: 0 0 0.5rem;
}

.head__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: clamp(1.9rem, 3.5vw, 2.4rem);
  margin: 0 0 0.4rem;
  color: var(--moss-dark);
}

.head__meta {
  color: var(--ink-soft);
  margin: 0;
  font-size: 0.9rem;
}

.status-badge {
  font-family: var(--font-mono);
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 0.4rem 0.85rem;
  border-radius: 999px;
  white-space: nowrap;
}

.status-badge--pending {
  background: var(--surface-alt);
  color: var(--ink-soft);
}

.status-badge--processing {
  background: var(--amber-soft);
  color: var(--amber);
}

.status-badge--shipped {
  background: #dfe6da;
  color: var(--moss-dark);
}

.status-badge--delivered {
  background: var(--moss);
  color: #fff;
}

.status-badge--cancelled {
  background: #f0dcd8;
  color: var(--danger);
}

.head__status {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-shrink: 0;
}

.head__status-label {
  font-family: var(--font-mono, monospace);
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint);
}

.head__status-select {
  font-family: var(--font-mono, monospace);
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 600;
  padding: 0.45rem 2rem 0.45rem 0.85rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  cursor: pointer;
  appearance: none;
  background-color: var(--surface-alt);
  color: var(--ink-soft);
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>");
  background-repeat: no-repeat;
  background-position: right 0.8rem center;
}

.head__status-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.head__status-select--processing {
  background-color: var(--amber-soft);
  color: var(--amber);
}

.head__status-select--shipped {
  background-color: #dfe6da;
  color: var(--moss-dark);
}

.head__status-select--delivered {
  background-color: var(--moss);
  color: #fff;
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' stroke='%23ffffff' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>");
}

.head__status-select--cancelled {
  background-color: #f0dcd8;
  color: var(--danger);
}

.head__status-saving {
  font-size: 0.78rem;
  color: var(--ink-faint);
}

.tracking-inline {
  max-width: 1080px;
  margin: -1rem auto 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.tracking-inline label {
  font-family: var(--font-mono, monospace);
  font-size: 0.7rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint);
}

.tracking-inline input {
  font-family: var(--font-body);
  font-size: 0.85rem;
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--ink);
  width: 220px;
}

.timeline {
  max-width: 1080px;
  margin: 0 auto 2.5rem;
  list-style: none;
  display: flex;
  padding: 0;
}

.timeline__step {
  flex: 1;
  position: relative;
  text-align: center;
  padding-top: 1.2rem;
}

.timeline__step::before {
  content: "";
  position: absolute;
  top: 0.3rem;
  left: -50%;
  width: 100%;
  height: 2px;
  background: var(--line);
  z-index: 0;
}

.timeline__step:first-child::before {
  display: none;
}

.timeline__step.is-done::before {
  background: var(--moss);
}

.timeline__dot {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--surface);
  border: 2px solid var(--line);
  z-index: 1;
}

.timeline__step.is-done .timeline__dot {
  background: var(--moss);
  border-color: var(--moss);
}

.timeline__step.is-current .timeline__dot {
  background: var(--amber);
  border-color: var(--amber);
}

.timeline__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--ink-soft);
  margin-top: 0.6rem;
}

.timeline__step.is-done .timeline__label,
.timeline__step.is-current .timeline__label {
  color: var(--moss-dark);
}

.timeline__date {
  display: block;
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--ink-faint);
  margin-top: 0.2rem;
}

.cancelled-note {
  max-width: 1080px;
  margin: 0 auto 2.5rem;
  background: #f0dcd8;
  color: var(--danger);
  border-radius: var(--radius-sm);
  padding: 0.85rem 1rem;
  font-size: 0.88rem;
}

.grid {
  max-width: 1080px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 820px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.main,
.side {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.panel {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.4rem 1.5rem;
}

.panel__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--moss-dark);
  margin: 0 0 1rem;
}

.items {
  width: 100%;
  border-collapse: collapse;
}

.items th {
  text-align: left;
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint);
  padding-bottom: 0.6rem;
  border-bottom: 1px solid var(--line);
}

.items td {
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--line);
  vertical-align: middle;
}

.items tr:last-child td {
  border-bottom: none;
}

.items__product {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.items__product img {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  background: var(--surface-alt);
  flex-shrink: 0;
}

.items__name {
  margin: 0;
  font-size: 0.9rem;
  color: var(--ink);
}

.items__sub {
  margin: 0.15rem 0 0;
  font-size: 0.76rem;
  color: var(--ink-faint);
}

.mono {
  font-family: var(--font-mono);
  font-size: 0.85rem;
}

.totals {
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--line);
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
  font-size: 1rem;
  font-weight: 600;
  color: var(--ink);
  padding-top: 0.6rem;
  margin-top: 0.3rem;
  border-top: 1px solid var(--line);
}

.notes {
  font-size: 0.88rem;
  line-height: 1.6;
  color: var(--ink-soft);
  margin: 0;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}

.field label {
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint);
}

.field input,
.field select {
  font-family: var(--font-body);
  font-size: 0.88rem;
  padding: 0.5rem 0.6rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--ink);
}

.field input:focus,
.field select:focus {
  outline: 2px solid var(--moss);
  outline-offset: 1px;
}

.btn-primary {
  width: 100%;
  background: var(--moss);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.65rem 1rem;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
  transition: background 0.15s var(--ease);
}

.btn-primary:hover {
  background: var(--moss-dark);
}

.customer__name {
  font-weight: 600;
  margin: 0 0 0.3rem;
}

.customer__line {
  font-size: 0.86rem;
  color: var(--ink-soft);
  margin: 0 0 0.2rem;
}

.address {
  font-size: 0.86rem;
  line-height: 1.6;
  color: var(--ink-soft);
  margin: 0;
}
</style>