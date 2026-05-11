import { useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
  CommandShortcut,
} from '@/components/ui/command'
import { useUIStore } from '@/stores/uiStore'
import { PLATFORM_NAV_GROUPS, TENANT_NAV_GROUPS } from '@/lib/constants'
import { useTenant } from '@/contexts/TenantContext'

export function CommandMenu() {
  const commandMenuOpen = useUIStore((state) => state.commandMenuOpen)
  const setCommandMenuOpen = useUIStore((state) => state.setCommandMenuOpen)
  const navigate = useNavigate()
  const location = useLocation()
  const { selectedTenantSlug } = useTenant()
  const tenantSlug = selectedTenantSlug || 'select-tenant'
  const navMode = location.pathname.startsWith('/superadmin/tenant/') ? 'tenant' : 'platform'

  const quickActions = navMode === 'tenant'
    ? [
        { label: 'Dashboard', href: `/superadmin/tenant/${tenantSlug}/dashboard`, shortcut: '⌘D' },
        { label: 'Pedidos', href: `/superadmin/tenant/${tenantSlug}/orders` },
        { label: 'Usuários', href: `/superadmin/tenant/${tenantSlug}/users` },
        { label: 'WhatsApp', href: `/superadmin/tenant/${tenantSlug}/whatsapp` },
        { label: 'Filas', href: `/superadmin/tenant/${tenantSlug}/queues` },
      ]
    : [
        { label: 'Lojas', href: '/superadmin/platform/stores', shortcut: '⌘D' },
        { label: 'Monitoramento', href: '/superadmin/platform/monitoring' },
        { label: 'Logs do Sistema', href: '/superadmin/platform/logs' },
        { label: 'Auditoria', href: '/superadmin/platform/audit' },
        { label: 'Feature Flags', href: '/superadmin/platform/feature-flags' },
        { label: 'Sistema', href: '/superadmin/platform/system' },
      ]

  const navGroups = (navMode === 'tenant' ? TENANT_NAV_GROUPS : PLATFORM_NAV_GROUPS).map((group) => ({
    ...group,
    items: group.items.map((item) => ({
      ...item,
      href: item.href.replace(':slug', tenantSlug),
    })),
  }))

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        useUIStore.setState({ commandMenuOpen: true })
      }
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [])

  const handleSelect = (href: string) => {
    navigate(href)
    setCommandMenuOpen(false)
  }

  return (
    <CommandDialog open={commandMenuOpen} onOpenChange={setCommandMenuOpen}>
      <CommandInput placeholder="Buscar página, loja, pedido…" />
      <CommandList>
        <CommandEmpty>Nenhum resultado encontrado.</CommandEmpty>

        <CommandGroup heading="Ações Rápidas">
          {quickActions.map((action) => (
            <CommandItem
              key={action.href}
              onSelect={() => handleSelect(action.href)}
            >
              {action.label}
              {action.shortcut && <CommandShortcut>{action.shortcut}</CommandShortcut>}
            </CommandItem>
          ))}
        </CommandGroup>

        <CommandSeparator />

        {navGroups.map(group => (
          <CommandGroup key={group.label} heading={group.label}>
            {group.items.map(item => {
              const Icon = item.icon
              return (
                <CommandItem
                  key={item.href}
                  onSelect={() => handleSelect(item.href)}
                  value={item.title + ' ' + (item.description ?? '')}
                >
                  <Icon className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-sm">{item.title}</p>
                    {item.description && (
                      <p className="text-xs text-muted-foreground">{item.description}</p>
                    )}
                  </div>
                </CommandItem>
              )
            })}
          </CommandGroup>
        ))}
      </CommandList>
    </CommandDialog>
  )
}
