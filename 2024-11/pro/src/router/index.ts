import { createRouter, createWebHistory } from 'vue-router'
import OffersView from '../views/OffersView.vue'
import OfferView from '../views/OfferView.vue'
import UserView from '../views/UserView.vue'
import RegLog from '../views/RegLog.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: OffersView
    },
    {
      path: '/search/',
      name: 'search',
      component: OffersView
    },
    {
      path: '/seller/:sellerid',
      name: 'seller',
      component: OffersView
    },
    {
      path: '/offer/:offerid',
      name: 'offer',
      component: OfferView
    },
    {
      path: '/user/:userid',
      name: 'user',
      component: UserView
    },
    {
      path: '/login',
      name: 'login',
      component: RegLog
    },
    {
      path: '/register',
      name: 'register',
      component: RegLog
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue')
    },
    {
      path: '/error',
      name: 'error',
      component: () => import('../views/ErrorView.vue')
    }
  ]
})

export default router
