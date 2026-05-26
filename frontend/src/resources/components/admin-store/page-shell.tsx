import type { ReactNode } from 'react'
import { StoreDashboardLayout, type StoreSection } from '@/components/layout/StoreDashboardLayout'
import { useStoreContext } from './use-store-context'
import { cn } from '@/js/lib/utils'

type AdminStorePageShellProps = {
  section: StoreSection
  children: ReactNode
  className?: string
}

/**
 * Wraps any admin-store SPA page with the shared topbar+sidebar layout
 * and standardized content padding. Always use this — never call
 * StoreDashboardLayout directly from a page.
 */
export function AdminStorePageShell({ section, children, className }: AdminStorePageShellProps) {
  const { slug, companyName, companyLogo, storeIsOpen, ifoodIsActive, storeHours, settingsUrl, palette } = useStoreContext()

  return (
    <StoreDashboardLayout
      companyName={companyName}
      companyLogo={companyLogo}
      storeIsOpen={storeIsOpen}
      ifoodIsActive={ifoodIsActive}
      storeHours={storeHours}
      settingsUrl={settingsUrl}
      activeSlug={slug}
      currentSection={section}
      palette={palette}
    >
      <div className={cn('p-5 space-y-5', className)}>{children}</div>
    </StoreDashboardLayout>
  )
}

type PageHeaderProps = {
  title: string
  description?: string
  icon?: ReactNode
  actions?: ReactNode
}

export function AdminPageHeader({ title, description, icon, actions }: PageHeaderProps) {
  return (
    <header className="flex flex-wrap items-center gap-3">
      {icon && (
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 flex-shrink-0">
          {icon}
        </span>
      )}
      <div className="flex-1 min-w-0">
        <h1 className="text-2xl font-semibold text-zinc-800 truncate">{title}</h1>
        {description && <p className="text-sm text-zinc-500">{description}</p>}
      </div>
      {actions && <div className="ml-auto flex flex-wrap items-center gap-2">{actions}</div>}
    </header>
  )
}
