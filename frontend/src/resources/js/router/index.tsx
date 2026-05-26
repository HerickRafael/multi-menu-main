import { lazy, Suspense } from 'react'
import { BrowserRouter, Routes, Route, Navigate, useParams } from 'react-router-dom'
import { AppShell } from '@/layouts/AppShell'
import { ProtectedRoute } from '@/components/shared/ProtectedRoute'
import { PageSkeleton } from '@/components/shared/Skeletons'
import { ErrorBoundary } from '@/components/shared/ErrorBoundary'
import { useTenant } from '@/js/contexts/TenantContext'

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

// Admin Store App (Fase 1)
const AdminStoreDashboardPage = lazy(() => import('@/pages/admin-store/AdminStoreDashboardPage'))
const AdminStoreOrdersPage = lazy(() => import('@/pages/admin-store/AdminStoreOrdersPage'))
const AdminStoreCategoriesPage = lazy(() => import('@/pages/admin-store/AdminStoreCategoriesPage'))
const AdminStoreCategoryFormPage = lazy(() => import('@/pages/admin-store/AdminStoreCategoryFormPage'))
const AdminStoreIngredientsPage = lazy(() => import('@/pages/admin-store/AdminStoreIngredientsPage'))
const AdminStoreIngredientFormPage = lazy(() => import('@/pages/admin-store/AdminStoreIngredientFormPage'))
const AdminStoreCouponFormPage = lazy(() => import('@/pages/admin-store/AdminStoreCouponFormPage'))
const AdminStoreCustomersPage = lazy(() => import('@/pages/admin-store/AdminStoreCustomersPage'))
const AdminStoreCustomerFormPage = lazy(() => import('@/pages/admin-store/AdminStoreCustomerFormPage'))
const AdminStoreSettingsPage = lazy(() => import('@/pages/admin-store/AdminStoreSettingsPage'))
const AdminStoreProductsPage = lazy(() => import('@/pages/admin-store/AdminStoreProductsPage'))
const AdminStoreOrderCreatePage = lazy(() => import('@/pages/admin-store/AdminStoreOrderCreatePage'))
const AdminStoreOrderDetailPage = lazy(() => import('@/pages/admin-store/AdminStoreOrderDetailPage'))
const AdminStoreLoyaltyDiscountPage = lazy(() => import('@/pages/admin-store/AdminStoreLoyaltyDiscountPage'))
const AdminStoreAnalyticsPage = lazy(() => import('@/pages/admin-store/AdminStoreAnalyticsPage'))
const AdminStoreFinancialPage = lazy(() => import('@/pages/admin-store/AdminStoreFinancialPage'))
const AdminStoreProductFormPage = lazy(() => import('@/pages/admin-store/AdminStoreProductFormPage'))
const AdminStoreLoyaltyProgramPage = lazy(() => import('@/pages/admin-store/AdminStoreLoyaltyProgramPage'))
const AdminStoreIFoodConfigPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodConfigPage'))
const AdminStoreIFoodLogsPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodLogsPage'))
const AdminStoreIFoodReviewsPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodReviewsPage'))
const AdminStoreIFoodOrdersPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodOrdersPage'))
const AdminStoreIFoodOrderDetailPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodOrderDetailPage'))
const AdminStoreIFoodLogisticsPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodLogisticsPage'))
const AdminStoreIFoodObservabilityPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodObservabilityPage'))
const AdminStoreIFoodWidgetPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodWidgetPage'))
const AdminStoreIFoodShippingListPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodShippingListPage'))
const AdminStoreIFoodShippingCreatePage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodShippingCreatePage'))
const AdminStoreIFoodShippingDetailPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodShippingDetailPage'))
const AdminStoreIFoodStockPage = lazy(() => import('@/pages/admin-store/AdminStoreIFoodStockPage'))
const AdminStoreApiPage = lazy(() => import('@/pages/admin-store/AdminStoreApiPage'))
const AdminStoreGuidePage = lazy(() => import('@/pages/admin-store/AdminStoreGuidePage'))
const AdminStoreKdsPage = lazy(() => import('@/pages/admin-store/AdminStoreKdsPage'))
const AdminStoreEvolutionPage = lazy(() => import('@/pages/admin-store/AdminStoreEvolutionPage'))
const AdminStoreCrossSellPage = lazy(() => import('@/pages/admin-store/AdminStoreCrossSellPage'))
const AdminStoreCustomizationTemplatesPage = lazy(() => import('@/pages/admin-store/AdminStoreCustomizationTemplatesPage'))
const AdminStoreCustomizationTemplateFormPage = lazy(() => import('@/pages/admin-store/AdminStoreCustomizationTemplateFormPage'))
const AdminStorePaymentMethodsPage = lazy(() => import('@/pages/admin-store/AdminStorePaymentMethodsPage'))
const AdminStoreDeliveryFeesPage = lazy(() => import('@/pages/admin-store/AdminStoreDeliveryFeesPage'))
const AdminStoreFinancialMonthlyPage = lazy(() => import('@/pages/admin-store/AdminStoreFinancialMonthlyPage'))
const AdminStoreFinancialYearlyPage = lazy(() => import('@/pages/admin-store/AdminStoreFinancialYearlyPage'))
const AdminStoreFinancialSettingsPage = lazy(() => import('@/pages/admin-store/AdminStoreFinancialSettingsPage'))
const AdminStoreExpensesPage = lazy(() => import('@/pages/admin-store/AdminStoreExpensesPage'))
const AdminStoreExpenseFormPage = lazy(() => import('@/pages/admin-store/AdminStoreExpenseFormPage'))
const AdminStoreExpenseCategoriesPage = lazy(() => import('@/pages/admin-store/AdminStoreExpenseCategoriesPage'))
const AdminStoreProductCostsPage = lazy(() => import('@/pages/admin-store/AdminStoreProductCostsPage'))
const AdminStoreProductCostEditPage = lazy(() => import('@/pages/admin-store/AdminStoreProductCostEditPage'))
const AdminStorePackagingPage = lazy(() => import('@/pages/admin-store/AdminStorePackagingPage'))
const AdminStorePackagingFormPage = lazy(() => import('@/pages/admin-store/AdminStorePackagingFormPage'))
const AdminStoreEvolutionInstanceConfigPage = lazy(() => import('@/pages/admin-store/AdminStoreEvolutionInstanceConfigPage'))

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

        {/* ─── Admin Loja (Dashboard no caminho original) ─── */}
        <Route path="/admin/:slug/dashboard" element={<SuspenseWrapper><AdminStoreDashboardPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/orders" element={<SuspenseWrapper><AdminStoreOrdersPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/categories" element={<SuspenseWrapper><AdminStoreCategoriesPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/categories/create" element={<SuspenseWrapper><AdminStoreCategoryFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/categories/:id/edit" element={<SuspenseWrapper><AdminStoreCategoryFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ingredients" element={<SuspenseWrapper><AdminStoreIngredientsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ingredients/create" element={<SuspenseWrapper><AdminStoreIngredientFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ingredients/:id/edit" element={<SuspenseWrapper><AdminStoreIngredientFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/coupons/create" element={<SuspenseWrapper><AdminStoreCouponFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/coupons/:id/edit" element={<SuspenseWrapper><AdminStoreCouponFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customers" element={<SuspenseWrapper><AdminStoreCustomersPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customers/create" element={<SuspenseWrapper><AdminStoreCustomerFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customers/:id/edit" element={<SuspenseWrapper><AdminStoreCustomerFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/settings" element={<SuspenseWrapper><AdminStoreSettingsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/products" element={<SuspenseWrapper><AdminStoreProductsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/products/create" element={<SuspenseWrapper><AdminStoreProductFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/products/:id/edit" element={<SuspenseWrapper><AdminStoreProductFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/orders/create" element={<SuspenseWrapper><AdminStoreOrderCreatePage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/orders/show" element={<SuspenseWrapper><AdminStoreOrderDetailPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/orders/:id/edit" element={<SuspenseWrapper><AdminStoreOrderCreatePage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/loyalty-discount" element={<SuspenseWrapper><AdminStoreLoyaltyDiscountPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/analytics" element={<SuspenseWrapper><AdminStoreAnalyticsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/financial" element={<SuspenseWrapper><AdminStoreFinancialPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/loyalty-program" element={<SuspenseWrapper><AdminStoreLoyaltyProgramPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/config" element={<SuspenseWrapper><AdminStoreIFoodConfigPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/logs" element={<SuspenseWrapper><AdminStoreIFoodLogsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/reviews" element={<SuspenseWrapper><AdminStoreIFoodReviewsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/orders" element={<SuspenseWrapper><AdminStoreIFoodOrdersPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/orders/:id" element={<SuspenseWrapper><AdminStoreIFoodOrderDetailPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/logistics" element={<SuspenseWrapper><AdminStoreIFoodLogisticsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/observability" element={<SuspenseWrapper><AdminStoreIFoodObservabilityPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/widget" element={<SuspenseWrapper><AdminStoreIFoodWidgetPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/shipping" element={<SuspenseWrapper><AdminStoreIFoodShippingListPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/shipping/new" element={<SuspenseWrapper><AdminStoreIFoodShippingCreatePage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/shipping/r/:ref" element={<SuspenseWrapper><AdminStoreIFoodShippingDetailPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/ifood/stock" element={<SuspenseWrapper><AdminStoreIFoodStockPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/api" element={<SuspenseWrapper><AdminStoreApiPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/guide/:topic" element={<SuspenseWrapper><AdminStoreGuidePage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/kds" element={<SuspenseWrapper><AdminStoreKdsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/evolution" element={<SuspenseWrapper><AdminStoreEvolutionPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/cross-sell-groups" element={<SuspenseWrapper><AdminStoreCrossSellPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customization-templates" element={<SuspenseWrapper><AdminStoreCustomizationTemplatesPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customization-templates/create" element={<SuspenseWrapper><AdminStoreCustomizationTemplateFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/customization-templates/:id/edit" element={<SuspenseWrapper><AdminStoreCustomizationTemplateFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/payment-methods" element={<SuspenseWrapper><AdminStorePaymentMethodsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/delivery-fees" element={<SuspenseWrapper><AdminStoreDeliveryFeesPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/financial/monthly" element={<SuspenseWrapper><AdminStoreFinancialMonthlyPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/financial/yearly" element={<SuspenseWrapper><AdminStoreFinancialYearlyPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/financial/settings" element={<SuspenseWrapper><AdminStoreFinancialSettingsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/expenses" element={<SuspenseWrapper><AdminStoreExpensesPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/expenses/create" element={<SuspenseWrapper><AdminStoreExpenseFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/expenses/:id/edit" element={<SuspenseWrapper><AdminStoreExpenseFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/expenses/categories" element={<SuspenseWrapper><AdminStoreExpenseCategoriesPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/product-costs" element={<SuspenseWrapper><AdminStoreProductCostsPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/product-costs/:id/edit" element={<SuspenseWrapper><AdminStoreProductCostEditPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/packaging" element={<SuspenseWrapper><AdminStorePackagingPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/packaging/create" element={<SuspenseWrapper><AdminStorePackagingFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/packaging/:id/edit" element={<SuspenseWrapper><AdminStorePackagingFormPage /></SuspenseWrapper>} />
        <Route path="/admin/:slug/evolution/instance/:instanceName" element={<SuspenseWrapper><AdminStoreEvolutionInstanceConfigPage /></SuspenseWrapper>} />

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
