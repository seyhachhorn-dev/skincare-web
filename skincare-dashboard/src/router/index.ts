import { createRouter, createWebHistory } from "vue-router";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Dashboard from "@/components/pages/dashboard.vue";
import OrderDetail from "@/components/pages/order/orderDetail.vue";
import ProductList from "@/components/pages/products/productList.vue";
import ProductDetail from "@/components/pages/products/productDetail.vue";
import CategoryList from "@/components/pages/categories/categoriesList.vue";
import categoriesCreate from "@/components/pages/categories/categoriesCreate.vue";
import UserCreate from "@/components/pages/users/userCreate.vue";
import UserList from "@/components/pages/users/userList.vue";
import Checkout from "@/components/pages/order/checkout.vue";
import ProductCreate from "@/components/pages/products/productCreate.vue";
import FavoriteList from "@/components/pages/favorites/favorites.vue";
import Login from "@/components/auth/login.vue";
import Profile from "@/components/auth/profile.vue";
import OrderList from "@/components/pages/order/orderList.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { public: true },
  },
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
        component: OrderList,
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
        path: "products/create",
        name: "ProductCreate",
        component: ProductCreate,
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
      {
        path: "categories/create",
        name: "CategoriesCreate",
        component: categoriesCreate,
      },
      {
        path: "users",
        name: "UserList",
        component: UserList,
      },
      {
        path: "users/create",
        name: "UsersCreate",
        component: UserCreate,
      },
      {
        path: "checkout",
        name: "Checkout",
        component: Checkout,
      },
      {
        path: "favorites",
        name: "Favorites",
        component: FavoriteList,
      },
      {
        path: "profile",
        name: "Profile",
        component: Profile,
      },
    ],
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  if (to.meta.public || localStorage.getItem("token")) return true;
  return { name: "Login", query: { redirect: to.fullPath } };
});
