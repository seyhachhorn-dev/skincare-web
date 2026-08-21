<template>
  <section class="orders">
    <header class="orders__head">
      <div>
        <p class="eyebrow">Admin</p>
        <h1 class="orders__title">Orders</h1>
        <p class="orders__meta">{{ filteredOrders.length }} order{{ filteredOrders.length === 1 ? "" : "s" }}</p>
      </div>
    </header>

    <div class="toolbar">
      <input
        v-model.trim="searchQuery"
        type="text"
        class="search"
        placeholder="Search by order # or customer…"
      />
      <select v-model="filterStatus" class="filter">
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="panel">
      <p v-if="loading" class="state-msg">Loading orders…</p>
      <p v-else-if="error" class="state-msg state-msg--error">{{ error }}</p>
      <p v-else-if="filteredOrders.length === 0" class="state-msg">No orders match your search.</p>

      <table v-else class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Placed</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th class="table__actions-head"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in filteredOrders"
            :key="order.id"
            class="row"
            @click="viewOrder(order)"
          >
            <td class="mono">#{{ order.number }}</td>
            <td>
              <p class="row__name">{{ order.customer.name }}</p>
              <p class="row__sub">{{ order.customer.email }}</p>
            </td>
            <td class="mono">{{ formatDate(order.placedAt) }}</td>
            <td class="mono">{{ order.items.length }}</td>
            <td class="mono">${{ order.total.toFixed(2) }}</td>
            <td>
              <span class="status-badge" :class="`status-badge--${order.status}`">
                {{ statusLabel(order.status) }}
              </span>
            </td>
            <td class="table__actions">
              <button type="button" class="btn-ghost" @click.stop="viewOrder(order)">View</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/lib/api";

const router = useRouter();

const orders = ref([]);
const loading = ref(true);
const error = ref("");
const searchQuery = ref("");
const filterStatus = ref("");

async function loadOrders() {
  loading.value = true;
  error.value = "";
  try {
    const response = await api.get("/admin/orders");
    orders.value = response.data.map(normalizeOrder);
  } catch (err) {
    console.error("Failed to fetch orders:", err);
    error.value = "Unable to load orders. Please try again.";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadOrders();
});

const filteredOrders = computed(() => {
  const q = searchQuery.value.toLowerCase();
  return orders.value.filter((order) => {
    const matchesSearch =
      !q ||
      order.number.toLowerCase().includes(q) ||
      order.customer.name.toLowerCase().includes(q) ||
      order.customer.email.toLowerCase().includes(q);
    const matchesStatus = !filterStatus.value || order.status === filterStatus.value;
    return matchesSearch && matchesStatus;
  });
});

function viewOrder(order) {
  router.push({ name: "OrderDetail", params: { id: order.id } });
}

function normalizeOrder(order) {
  const items = (order.items ?? []).map((item) => ({
    id: item.id,
    name: item.product?.name ?? "Product",
    category: item.product?.brand ?? "Skincare",
    activeIngredient: item.product?.description ?? "",
    price: Number(item.unit_price ?? item.product?.price ?? 0),
    qty: Number(item.quantity ?? 0),
    image: item.product?.image ?? "",
  }));
  const total = Number(order.total ?? 0);
  return {
    ...order,
    number: String(order.order_number ?? order.id),
    placedAt: order.date,
    trackingNumber: order.tracking_number ?? "",
    shippedAt: order.shipped_at ?? null,
    deliveredAt: order.delivered_at ?? null,
    cancelledAt: order.cancelled_at ?? null,
    items,
    subtotal: total,
    shipping: 0,
    tax: 0,
    discount: 0,
    total,
    customer: order.customer ?? { name: "", email: "", phone: "" },
    shippingAddress: order.address ?? { line1: "", line2: "", city: "", state: "", zip: "", country: "" },
    payment: { method: order.payment_method ?? "", last4: "" },
  };
}

function statusLabel(status) {
  return {
    pending: "Pending",
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
</script>

<style scoped>
@import "@/assets/tokens.css";

.orders {
  background: var(--bg, #faf9f6);
  color: var(--ink, #1f2937);
  font-family: var(--font-body, inherit);
  padding: 2rem 1.5rem 4rem;
  min-height: 100%;
}

.orders__head {
  max-width: 1080px;
  margin: 0 auto 1.5rem;
}

.eyebrow {
  font-family: var(--font-mono, monospace);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--amber, #b45309);
  margin: 0 0 0.5rem;
}

.orders__title {
  font-family: var(--font-display, inherit);
  font-weight: 600;
  font-size: clamp(1.9rem, 3.5vw, 2.4rem);
  margin: 0 0 0.4rem;
  color: var(--moss-dark, #047857);
}

.orders__meta {
  color: var(--ink-soft, #6b7280);
  margin: 0;
  font-size: 0.9rem;
}

.toolbar {
  max-width: 1080px;
  margin: 0 auto 1.25rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.search {
  flex: 1;
  min-width: 220px;
  font-family: var(--font-body, inherit);
  font-size: 0.9rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--line, #e5e7eb);
  border-radius: var(--radius-sm, 0.5rem);
  background: var(--surface, #fff);
  color: var(--ink, #1f2937);
}

.filter {
  font-family: var(--font-body, inherit);
  font-size: 0.9rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--line, #e5e7eb);
  border-radius: var(--radius-sm, 0.5rem);
  background: var(--surface, #fff);
  color: var(--ink, #1f2937);
}

.search:focus,
.filter:focus {
  outline: 2px solid var(--moss, #059669);
  outline-offset: 1px;
}

.panel {
  max-width: 1080px;
  margin: 0 auto;
  background: var(--surface, #fff);
  border: 1px solid var(--line, #e5e7eb);
  border-radius: var(--radius-md, 0.75rem);
  padding: 0.5rem 1.4rem;
  overflow-x: auto;
}

.state-msg {
  padding: 2rem 0;
  text-align: center;
  color: var(--ink-soft, #6b7280);
  font-size: 0.9rem;
}

.state-msg--error {
  color: var(--danger, #b91c1c);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  text-align: left;
  font-family: var(--font-mono, monospace);
  font-size: 0.68rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--ink-faint, #9ca3af);
  padding: 0.9rem 0.6rem 0.6rem;
  border-bottom: 1px solid var(--line, #e5e7eb);
  white-space: nowrap;
}

.table__actions-head {
  width: 1%;
}

.row {
  cursor: pointer;
  transition: background 0.1s var(--ease, ease);
}

.row:hover {
  background: var(--surface-alt, #f4f4f0);
}

.row td {
  padding: 0.85rem 0.6rem;
  border-bottom: 1px solid var(--line, #e5e7eb);
  vertical-align: middle;
  font-size: 0.88rem;
}

.row:last-child td {
  border-bottom: none;
}

.row__name {
  margin: 0;
  font-size: 0.9rem;
  color: var(--ink, #1f2937);
}

.row__sub {
  margin: 0.1rem 0 0;
  font-size: 0.78rem;
  color: var(--ink-faint, #9ca3af);
}

.mono {
  font-family: var(--font-mono, monospace);
  font-size: 0.85rem;
  white-space: nowrap;
}

.table__actions {
  text-align: right;
  white-space: nowrap;
}

.btn-ghost {
  background: var(--surface, #fff);
  border: 1px solid var(--line, #e5e7eb);
  border-radius: var(--radius-sm, 0.5rem);
  color: var(--ink, #1f2937);
  font-family: var(--font-body, inherit);
  font-size: 0.8rem;
  padding: 0.4rem 0.8rem;
  cursor: pointer;
  transition: background 0.15s var(--ease, ease), color 0.15s var(--ease, ease);
}

.btn-ghost:hover {
  background: var(--moss, #059669);
  color: #fff;
  border-color: var(--moss, #059669);
}

.status-badge {
  font-family: var(--font-mono, monospace);
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  white-space: nowrap;
  display: inline-block;
}

.status-badge--pending {
  background: var(--surface-alt, #f4f4f0);
  color: var(--ink-soft, #6b7280);
}

.status-badge--processing {
  background: var(--amber-soft, #fef3c7);
  color: var(--amber, #b45309);
}

.status-badge--shipped {
  background: #dfe6da;
  color: var(--moss-dark, #047857);
}

.status-badge--delivered {
  background: var(--moss, #059669);
  color: #fff;
}

.status-badge--cancelled {
  background: #f0dcd8;
  color: var(--danger, #b91c1c);
}
</style>