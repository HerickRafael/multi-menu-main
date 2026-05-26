import { memo } from 'react'
import { MoreHorizontal, Store } from 'lucide-react'
import { cn, formatNumber, formatRelativeTime } from '@/js/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuSeparator,
  ContextMenuTrigger,
} from '@/components/ui/context-menu'
import type { StoreItem } from '@/js/types/phase3'

interface StoreActionHandlers {
  onSelect: (store: StoreItem) => void
  onOpenDetails: (store: StoreItem) => void
  onOpenDashboard: (store: StoreItem) => void
  onOpenAnalytics: (store: StoreItem) => void
  onOpenConfig: (store: StoreItem) => void
  onOpenLogs: (store: StoreItem) => void
  onOpenWebhooks: (store: StoreItem) => void
  onResetCache: (store: StoreItem) => void
  onToggleActive: (store: StoreItem) => void
  onDelete: (store: StoreItem) => void
}

interface StoreGridCardProps {
  store: StoreItem
  isLoading?: boolean
  actions: StoreActionHandlers
}

/**
 * StoreGridCard - Individual tenant/store card for grid layout
 * Displays as a workspace selector similar to Shopify/Slack/Vercel
 */
export const StoreGridCard = memo(function StoreGridCard({
  store,
  isLoading,
  actions,
}: StoreGridCardProps) {
  const primaryActionLabel = store.active ? 'Entrar' : 'Ativar'
  const primaryAction = store.active ? () => actions.onSelect(store) : () => actions.onToggleActive(store)
  const lastActivity = store.created_at ? formatRelativeTime(store.created_at) : 'Sem dados'
  const healthBadges = [
    {
      label: `API: ${store.active ? 'Online' : 'Offline'}`,
      variant: store.active ? 'success' : 'destructive',
    },
  ] as const

  return (
    <ContextMenu>
      <ContextMenuTrigger asChild>
        <Card
          className={cn(
            'group border-border/70 bg-card/80 transition-all',
            'hover:border-primary/50 hover:bg-accent/30 hover:shadow-sm',
          )}
        >
          <CardContent className="p-4 space-y-3">
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-center gap-3 min-w-0">
                <Avatar className="h-10 w-10 border border-border/70">
                  <AvatarFallback className="bg-primary/10">
                    <Store className="h-5 w-5 text-primary" />
                  </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                  <button
                    type="button"
                    onClick={() => actions.onOpenDetails(store)}
                    className="text-left"
                  >
                    <p className="text-sm font-semibold text-foreground truncate group-hover:text-primary">
                      {store.name}
                    </p>
                    <p className="text-xs text-muted-foreground">/{store.slug}</p>
                  </button>
                </div>
              </div>
              <Badge variant={store.active ? 'success' : 'destructive'} className="text-[11px]">
                {store.active ? 'Ativa' : 'Inativa'}
              </Badge>
            </div>

            <div className="grid grid-cols-3 gap-2 text-xs">
              <div className="rounded-md border border-border/60 bg-muted/30 px-2 py-1">
                <p className="text-muted-foreground">Pedidos</p>
                <p className="font-semibold text-foreground">{formatNumber(store.orders_count)}</p>
              </div>
              <div className="rounded-md border border-border/60 bg-muted/30 px-2 py-1">
                <p className="text-muted-foreground">Usuários</p>
                <p className="font-semibold text-foreground">{formatNumber(store.users_count)}</p>
              </div>
              <div className="rounded-md border border-border/60 bg-muted/30 px-2 py-1">
                <p className="text-muted-foreground">Atividade</p>
                <p className="font-semibold text-foreground">{lastActivity}</p>
              </div>
            </div>

            <div className="flex flex-wrap gap-1">
              {healthBadges.map((badge) => (
                <Badge key={badge.label} variant={badge.variant} className="text-[10px]">
                  {badge.label}
                </Badge>
              ))}
            </div>

            <div className="flex items-center justify-between gap-2 pt-2 border-t border-border/60">
              <div className="flex flex-wrap items-center gap-2">
                <Button
                  size="sm"
                  variant={store.active ? 'ghost' : 'outline'}
                  className="h-7 px-2 text-xs"
                  disabled={store.active ? isLoading : false}
                  onClick={primaryAction}
                >
                  {isLoading ? 'Processando...' : primaryActionLabel}
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 px-2 text-xs"
                  onClick={() => actions.onOpenConfig(store)}
                >
                  Config
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 px-2 text-xs"
                  onClick={() => actions.onOpenAnalytics(store)}
                >
                  Analytics
                </Button>
              </div>

              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon-sm">
                    <MoreHorizontal className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-44">
                  <DropdownMenuItem onClick={() => actions.onOpenDashboard(store)}>
                    Abrir dashboard
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => actions.onOpenLogs(store)}>
                    Ver logs
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => actions.onOpenWebhooks(store)}>
                    Webhooks
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={() => actions.onResetCache(store)}>
                    Resetar cache
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => actions.onToggleActive(store)}>
                    {store.active ? 'Suspender' : 'Ativar'}
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    className="text-destructive focus:text-destructive"
                    onClick={() => actions.onDelete(store)}
                  >
                    Excluir
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </CardContent>
        </Card>
      </ContextMenuTrigger>
      <ContextMenuContent className="w-44">
        <ContextMenuItem onClick={() => actions.onOpenDetails(store)}>Detalhes</ContextMenuItem>
        <ContextMenuItem onClick={() => actions.onOpenDashboard(store)}>Abrir dashboard</ContextMenuItem>
        <ContextMenuItem onClick={() => actions.onOpenLogs(store)}>Ver logs</ContextMenuItem>
        <ContextMenuSeparator />
        <ContextMenuItem onClick={() => actions.onResetCache(store)}>Resetar cache</ContextMenuItem>
        <ContextMenuItem onClick={() => actions.onToggleActive(store)}>{store.active ? 'Suspender' : 'Ativar'}</ContextMenuItem>
        <ContextMenuSeparator />
        <ContextMenuItem
          className="text-destructive focus:text-destructive"
          onClick={() => actions.onDelete(store)}
        >
          Excluir
        </ContextMenuItem>
      </ContextMenuContent>
    </ContextMenu>
  )
})
