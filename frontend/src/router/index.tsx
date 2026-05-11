import { lazy, Suspense } from 'react'
import { BrowserRouter, Routes, Route, Navigate, useParams } from 'react-router-dom'
import { AppShell } from '@/layouts/AppShell'
import { ProtectedRoute } from '@/components/shared/ProtectedRoute'
import { PageSkeleton } from '@/components/shared/Skeletons'
import { ErrorBoundary } from '@/components/shared/ErrorBoundary'
import { useTenant } from '@/contexts/TenantContext'

// Auth pages (not lazy — needed immediately)
import LoginPage from '@/pages/auth/LoginPage'

// Lazy loaded pages - Operational (Tenant-scoped)
const DashboardPage = lazy(() => import('@/pages/dashboard/DashboardPage'))
const OrdersPage = lazy(() => import('@/pages/orders/OrdersPage'))
const OrderDetailPage = lazy(() => import('@/pages/orders/OrderDetailPage'))
const UsersPage = lazy(() => import('@/pages/users/UsersPage'))
const WhatsAppPage = lazy(() => import('@/pages/whatsapp/WhatsAppPage'))
const QueuesPage = lazy(() => import('@/pages/queues/QueuesPage'))

// Lazy loaded pages - Platform (Global)
const MonitoringPage = lazy(() => import('@/pages/monitoring/MonitoringPage'))
const StoresPage = lazy(() => import('@/pages/stores/StoresPage'))
const LogsPage = lazy(() => import('@/pages/logs/LogsPage'))
const AuditPage = lazy(() => import('@/pages/audit/AuditPage'))
const WebhooksPage = lazy(() => import('@/pages/webhooks/WebhooksPage'))
const PermissionsPage = lazy(() => import('@/pages/permissions/PermissionsPage'))
const FeatureFlagsPage = lazy(() => import('@/pages/feature-flags/FeatureFlagsPage'))
const AnalyticsPage = lazy(() => import('@/pages/analytics/AnalyticsPage'))
const SettingsPage = lazy(() => import('@/pages/settings/SettingsPage'))
const SystemPage = lazy(() => import('@/pages/system/SystemPage'))

function SuspenseWrapper({ children }: { children: React.ReactNode }) {
  return (
    <ErrorBoundary>
      <Suspense fallback={<PageSkeleton />}>
        {children}
      </Suspense>
    </ErrorBoundary>
  )
}

/**
 * Guard to ensure tenant is selected before accessing tenant routes
 */
function TenantGuard({ children }: { children: React.ReactNode }) {
  const { tenantSlug } = useParams<{ tenantSlug: string }>()
  const { selectedTenantId, selectedTenantSlug, mode } = useTenant()

  // If not in tenant mode or no tenant selected, redirect to platform
  if (mode !== 'tenant' || !selectedTenantId) {
    return <Navigate to="/superadmin/platform/stores" replace />
  }

  // Canonicalize tenant route to the currently selected tenant context.
  // Prevents cross-tenant URL tampering via manual slug editing.
  if (selectedTenantSlug && tenantSlug && tenantSlug !== selectedTenantSlug) {
    return <Navigate to={`/superadmin/tenant/${selectedTenantSlug}/dashboard`} replace />
  }

  return <>{children}</>
}

export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Auth */}
        <Route path="/superadmin/login" element={<LoginPage />} />

        {/* ─── Platform Routes (Global Admin) ─── */}
        <Route
          path="/superadmin/platform"
          element={
            <ProtectedRoute>
              <AppShell />
            </ProtectedRoute>
          }
        >
          <Route index element={<Navigate to="/superadmin/platform/stores" replace />} />
          
          {/* Tenant Management */}
          <Route path="stores" element={<SuspenseWrapper><StoresPage /></SuspenseWrapper>} />
          <Route path="stores/:id" element={<SuspenseWrapper><StoresPage /></SuspenseWrapper>} />
          
          {/* Monitoring */}
          <Route path="monitoring" element={<SuspenseWrapper><MonitoringPage /></SuspenseWrapper>} />
          
          {/* Logs & Audit */}
          <Route path="logs" element={<SuspenseWrapper><LogsPage /></SuspenseWrapper>} />
          <Route path="audit" element={<SuspenseWrapper><AuditPage /></SuspenseWrapper>} />
          
          {/* Platform Configuration */}
          <Route path="webhooks" element={<SuspenseWrapper><WebhooksPage /></SuspenseWrapper>} />
          <Route path="permissions" element={<SuspenseWrapper><PermissionsPage /></SuspenseWrapper>} />
          <Route path="feature-flags" element={<SuspenseWrapper><FeatureFlagsPage /></SuspenseWrapper>} />
          <Route path="analytics" element={<SuspenseWrapper><AnalyticsPage /></SuspenseWrapper>} />
          <Route path="settings" element={<SuspenseWrapper><SettingsPage /></SuspenseWrapper>} />
          <Route path="system" element={<SuspenseWrapper><SystemPage /></SuspenseWrapper>} />
        </Route>

        {/* ─── Tenant Routes (Operational) ─── */}
        {/* These routes require selected tenant context */}
        <Route
          path="/superadmin/tenant/:tenantSlug"
          element={
            <ProtectedRoute>
              <TenantGuard>
                <AppShell />
              </TenantGuard>
            </ProtectedRoute>
          }
        >
          <Route index element={<Navigate to="dashboard" replace />} />
          
          {/* Operational */}
          <Route path="dashboard" element={<SuspenseWrapper><DashboardPage /></SuspenseWrapper>} />
          <Route path="orders" element={<SuspenseWrapper><OrdersPage /></SuspenseWrapper>} />
          <Route path="orders/:id" element={<SuspenseWrapper><OrderDetailPage /></SuspenseWrapper>} />
          <Route path="users" element={<SuspenseWrapper><UsersPage /></SuspenseWrapper>} />
          <Route path="whatsapp" element={<SuspenseWrapper><WhatsAppPage /></SuspenseWrapper>} />
          <Route path="queues" element={<SuspenseWrapper><QueuesPage /></SuspenseWrapper>} />
        </Route>

        {/* ─── Backward Compatibility & Default Routes ─── */}
        {/* Redirect old /superadmin/* routes to /superadmin/platform/* for backward compat */}
        <Route path="/superadmin/dashboard" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/monitoring" element={<Navigate to="/superadmin/platform/monitoring" replace />} />
        <Route path="/superadmin/stores" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/stores/:id" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/orders" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/users" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/whatsapp" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/queues" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin/logs" element={<Navigate to="/superadmin/platform/logs" replace />} />
        <Route path="/superadmin/audit" element={<Navigate to="/superadmin/platform/audit" replace />} />
        <Route path="/superadmin/webhooks" element={<Navigate to="/superadmin/platform/webhooks" replace />} />
        <Route path="/superadmin/permissions" element={<Navigate to="/superadmin/platform/permissions" replace />} />
        <Route path="/superadmin/feature-flags" element={<Navigate to="/superadmin/platform/feature-flags" replace />} />
        <Route path="/superadmin/analytics" element={<Navigate to="/superadmin/platform/analytics" replace />} />
        <Route path="/superadmin/settings" element={<Navigate to="/superadmin/platform/settings" replace />} />
        <Route path="/superadmin/system" element={<Navigate to="/superadmin/platform/system" replace />} />

        {/* Root redirect to platform stores (tenant selection) */}
        <Route path="/" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="/superadmin" element={<Navigate to="/superadmin/platform/stores" replace />} />
        <Route path="*" element={<Navigate to="/superadmin/platform/stores" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
