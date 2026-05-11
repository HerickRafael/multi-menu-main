import { memo } from 'react'
import { Store, Users, MessageCircle, Clock, Zap } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { formatDate, formatNumber } from '@/lib/utils'
import type { StoreItem } from '@/types/phase3'

interface StoreGridCardProps {
  store: StoreItem
  isSelected?: boolean
  isLoading?: boolean
  onSelect: (storeId: number, slug: string, name: string) => void
}

/**
 * StoreGridCard - Individual tenant/store card for grid layout
 * Displays as a workspace selector similar to Shopify/Slack/Vercel
 */
export const StoreGridCard = memo(function StoreGridCard({
  store,
  isSelected,
  isLoading,
  onSelect,
}: StoreGridCardProps) {

  return (
    <button
      onClick={() => onSelect(store.id, store.slug, store.name)}
      disabled={isLoading || !store.active}
      className={cn(
        'group relative overflow-hidden rounded-xl border-2 bg-card',
        'transition-all duration-200 ease-out',
        'hover:shadow-lg hover:border-primary/50',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2',
        isSelected
          ? 'border-primary shadow-lg ring-2 ring-primary/20'
          : 'border-border hover:scale-[1.02] hover:-translate-y-1',
      )}
    >
      {/* Background gradient accent */}
      <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200" />

      {/* Content wrapper */}
      <div className="relative p-6 space-y-4 h-full flex flex-col">
        {/* Header: Logo + Status Badge */}
        <div className="flex items-start justify-between gap-3">
          {/* Logo/Avatar */}
          <Avatar className="h-12 w-12 border-2 border-border">
            <AvatarFallback className="bg-primary/10">
              <Store className="h-6 w-6 text-primary" />
            </AvatarFallback>
          </Avatar>

          {/* Status Badges */}
          <div className="flex flex-col gap-1 items-end">
            <Badge
              variant={store.active ? 'success' : 'destructive'}
              className="text-xs"
            >
              {store.active ? 'Ativa' : 'Inativa'}
            </Badge>
          </div>
        </div>

        {/* Title + Slug */}
        <div className="min-h-[52px]">
          <h3 className="font-semibold text-lg text-foreground line-clamp-2 group-hover:text-primary transition-colors">
            {store.name}
          </h3>
          <p className="text-xs text-muted-foreground mt-1">/{store.slug}</p>
        </div>

        {/* Divider */}
        <div className="h-px bg-border/50" />

        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-3 text-xs">
          {/* Pedidos */}
          <div className="flex items-center gap-2 p-2 rounded-lg bg-muted/30">
            <Zap className="h-3.5 w-3.5 text-amber-500 flex-shrink-0" />
            <div className="min-w-0">
              <p className="text-muted-foreground">Pedidos</p>
              <p className="font-semibold text-foreground">{formatNumber(store.orders_count)}</p>
            </div>
          </div>

          {/* Usuários */}
          <div className="flex items-center gap-2 p-2 rounded-lg bg-muted/30">
            <Users className="h-3.5 w-3.5 text-blue-500 flex-shrink-0" />
            <div className="min-w-0">
              <p className="text-muted-foreground">Usuários</p>
              <p className="font-semibold text-foreground">{formatNumber(store.users_count)}</p>
            </div>
          </div>
        </div>

        {/* Action Button */}
        <button
          onClick={(e) => {
            e.preventDefault()
            onSelect(store.id, store.slug, store.name)
          }}
          disabled={isLoading || !store.active}
          className={cn(
            'w-full mt-3 py-2 px-3 rounded-lg font-medium text-sm',
            'transition-all duration-150',
            store.active
              ? 'bg-primary text-primary-foreground hover:bg-primary/90 active:scale-95'
              : 'bg-muted text-muted-foreground cursor-not-allowed',
          )}
        >
          {isLoading ? 'Acessando...' : 'Acessar'}
        </button>

        {/* Selected indicator */}
        {isSelected && (
          <div className="absolute top-2 right-2 w-3 h-3 rounded-full bg-primary animate-pulse" />
        )}
      </div>
    </button>
  )
})
