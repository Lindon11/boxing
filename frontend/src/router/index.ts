import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import type { RouteMeta } from '@/types/router'
import '@/types/router' // Import for route meta type augmentation
import { usePluginsStore } from '@/stores/plugins'
import { registerPluginRoutes } from '@/composables/usePluginRoutes'

// Re-export RouteMeta for convenience
export type { RouteMeta } from '@/types/router'

// Plugin slug to route mapping - used for static route definitions
// Dynamic routes are loaded from backend via PluginManifestService
// All gaming/utility plugins are now loaded dynamically from bundles
const pluginRoutes: Record<string, string[]> = {}

// Reverse mapping: route name -> plugin slug
const routeToPlugin: Record<string, string> = {}
Object.entries(pluginRoutes).forEach(([plugin, routes]) => {
  routes.forEach(route => {
    routeToPlugin[route] = plugin
  })
})

/**
 * Route definitions with lazy loading for optimal bundle size
 * Core routes only - gaming routes are provided by plugins
 */
const routes: RouteRecordRaw[] = [
  // Public BoxingDB routes
  {
    path: '/',
    component: () => import('@/layouts/BoxingLayout.vue'),
    children: [
      {
        path: '',
        name: 'boxing-home',
        component: () => import('@/views/boxing/HomePage.vue'),
        meta: { title: 'BoxingDB' } satisfies RouteMeta
      },
      {
        path: 'fighters',
        name: 'boxing-fighters',
        component: () => import('@/views/boxing/FightersPage.vue'),
        meta: { title: 'Fighters' } satisfies RouteMeta
      },
      {
        path: 'fighters/:slug',
        name: 'boxing-fighter-profile',
        component: () => import('@/views/boxing/FighterProfilePage.vue'),
        meta: { title: 'Fighter Profile' } satisfies RouteMeta
      },
      {
        path: 'events',
        name: 'boxing-events',
        component: () => import('@/views/boxing/EventsPage.vue'),
        meta: { title: 'Events' } satisfies RouteMeta
      },
      {
        path: 'events/:slug',
        name: 'boxing-event-detail',
        component: () => import('@/views/boxing/EventDetailPage.vue'),
        meta: { title: 'Event' } satisfies RouteMeta
      },
      {
        path: 'events/:slug/fight-card',
        name: 'boxing-fight-card',
        component: () => import('@/views/boxing/FightCardPage.vue'),
        meta: { title: 'Fight Card' } satisfies RouteMeta
      },
      {
        path: 'rankings',
        name: 'boxing-rankings',
        component: () => import('@/views/boxing/RankingsPage.vue'),
        meta: { title: 'Rankings' } satisfies RouteMeta
      },
      {
        path: 'titles',
        name: 'boxing-titles',
        component: () => import('@/views/boxing/TitlesPage.vue'),
        meta: { title: 'Titles' } satisfies RouteMeta
      },
      {
        path: 'search',
        name: 'boxing-search',
        component: () => import('@/views/boxing/SearchPage.vue'),
        meta: { title: 'Search' } satisfies RouteMeta
      },
      {
        path: 'promotions',
        name: 'boxing-promotions',
        component: () => import('@/views/boxing/StaticPage.vue'),
        props: {
          title: 'Promotions',
          message: 'Promoter profiles and event catalogues will live here as the BoxingDB promoter database grows.'
        },
        meta: { title: 'Promotions' } satisfies RouteMeta
      },
      {
        path: 'news',
        name: 'boxing-news',
        component: () => import('@/views/boxing/StaticPage.vue'),
        props: {
          title: 'News',
          message: 'Editorial feeds can be connected here. The homepage already includes a latest-news placeholder.'
        },
        meta: { title: 'News' } satisfies RouteMeta
      },
      {
        path: 'venues',
        name: 'boxing-venues',
        component: () => import('@/views/boxing/StaticPage.vue'),
        props: {
          title: 'Venues',
          message: 'Venue pages will collect arenas, cities, capacities, and event histories.'
        },
        meta: { title: 'Venues' } satisfies RouteMeta
      },
      {
        path: 'watch',
        name: 'boxing-watch',
        component: () => import('@/views/boxing/StaticPage.vue'),
        props: {
          title: 'Watch',
          message: 'Broadcast pages will show upcoming TV and streaming availability by region.'
        },
        meta: { title: 'Watch' } satisfies RouteMeta
      },
      {
        path: 'api-access',
        name: 'boxing-api-access',
        component: () => import('@/views/boxing/StaticPage.vue'),
        props: {
          title: 'API Access',
          message: 'Public API plans can be described here once the commercial access model is ready.'
        },
        meta: { title: 'API Access' } satisfies RouteMeta
      },
    ]
  },

  // Guest-only routes (authentication)
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresGuest: true, title: 'Login' } satisfies RouteMeta
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { requiresGuest: true, title: 'Register' } satisfies RouteMeta
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordView.vue'),
    meta: { requiresGuest: true, title: 'Forgot Password' } satisfies RouteMeta
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPasswordView.vue'),
    meta: { requiresGuest: true, title: 'Reset Password' } satisfies RouteMeta
  },

  // Authenticated routes with CoreLayout
  {
    path: '/app',
    component: () => import('@/layouts/CoreLayout.vue'),
    meta: { requiresAuth: true } satisfies RouteMeta,
    children: [
      // Dashboard & Home (Core)
      {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/HomeView.vue'),
        meta: { title: 'Dashboard' } satisfies RouteMeta
      },
      {
        path: 'home',
        name: 'home',
        component: () => import('@/views/HomeView.vue'),
        meta: { title: 'Home' } satisfies RouteMeta
      },

      // Core Profile (Core)
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/views/ProfileView.vue'),
        meta: { title: 'Profile' } satisfies RouteMeta
      },

      // Activity Log (Core)
      {
        path: 'activity',
        name: 'activity',
        component: () => import('@/views/ActivityView.vue'),
        meta: { title: 'Activity' } satisfies RouteMeta
      },

      // User Settings (Core)
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/SettingsView.vue'),
        meta: { title: 'Settings' } satisfies RouteMeta
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('@/views/NotificationsView.vue'),
        meta: { title: 'Notifications' } satisfies RouteMeta
      },

      // NOTE: Gaming routes (crimes, gym, hospital, bank, drugs, theft, racing,
      // jail, properties, bounty, detective, bullets, gang, organized-crime,
      // chat, messaging, achievements, leaderboards, employment, education,
      // quests, alliances, shop, market, stocks, casino, explore, hunting,
      // events, tournament, inventory, missions, combat, scavenge, skills,
      // forums, announcements, daily-rewards) are now provided by plugins.
      // Install the gaming bundle to restore these features.
    ]
  },

  // 404 Catch-all route
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page Not Found' } satisfies RouteMeta
  }
]

/**
 * Create router instance
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  }
})

/**
 * Initialize dynamic plugin routes
 * Called after plugins are loaded from the backend
 */
export async function initializePluginRoutes(): Promise<void> {
  const pluginsStore = usePluginsStore()

  // Fetch enabled plugins if not already loaded
  if (!pluginsStore.loaded) {
    await pluginsStore.fetchPlugins()
  }

  // Register dynamic routes from plugins
  if (pluginsStore.routes.length > 0) {
    registerPluginRoutes(router, pluginsStore.routes)
  }
}

/**
 * Navigation guard for authentication and plugin routes
 */
router.beforeEach(async (to, _from, next) => {
  const user = localStorage.getItem('user')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  // Update document title if route has a title
  const title = to.meta?.title as string | undefined
  if (title) {
    const appName = import.meta.env.VITE_APP_NAME || 'Core Web App'
    document.title = `${title} | ${appName}`
  }

  // Initialize plugin routes on first authenticated navigation
  const pluginsStore = usePluginsStore()
  if (user && !pluginsStore.loaded) {
    await initializePluginRoutes()
  }

  if (requiresAuth && !user) {
    // Redirect to login if auth required but not authenticated
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  if (requiresGuest && user) {
    // Redirect to dashboard if guest route but already authenticated
    next({ name: 'dashboard' })
    return
  }

  // Check if route requires an enabled plugin
  const pluginSlug = to.meta?.plugin as string | undefined
  if (pluginSlug && !pluginsStore.isEnabled(pluginSlug)) {
    // Plugin not enabled, redirect to dashboard
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
