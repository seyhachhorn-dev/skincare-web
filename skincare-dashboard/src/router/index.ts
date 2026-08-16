import { createRouter, createWebHistory } from "vue-router";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Dashboard from "@/components/pages/dashboard.vue";
import OrderDetail from "@/components/pages/order/orderDetail.vue";
import ProductList from "@/components/pages/products/productList.vue";
import ProductDetail from "@/components/pages/products/productDetail.vue";
import CategoryList from "@/components/pages/categories/categoryList.vue";

const routes = [
  {
    path: "/",
    component: DashboardLayout,
    children: [
      {
        path: "",
        name: "Dashboard",
        component: Dashboard,
      },
      {
        path: "orders",
        name: "Orders",
        component: OrderDetail,
      },
      {
        path: "orders/:id",
        name: "OrderDetail",
        component: OrderDetail,
        props: true,
      },
      {
        path: "products",
        name: "Products",
        component: ProductList,
      },
      {
        path: "products/:id",
        name: "ProductDetail",
        component: ProductDetail,
        props: true,
      },
      {
        path: "categories",
        name: "Categories",
        component: CategoryList,
      },
    ],
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
