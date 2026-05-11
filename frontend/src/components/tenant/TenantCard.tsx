import React, { memo, useState } from 'react'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Building2,
  Users,
  ShoppingCart,
  MessageCircle,
  Clock,
  ChevronRight,
} from 'lucide-react'
import { cn } from '@/lib/utils'

/**
 * Tenant metadata for card display
 */
export interface TenantCardData {
  id: number
  slug: string
  name: string
  logo_url?: string | null
  active: boolean
  plan?: string
  orders_count?: number
  users_count?: number
  whatsapp_connected?: boolean
  last_activity_at?: string
}

interface TenantCardProps {
  tenant: TenantCardData
  isSelected?: boolean
  isLoading?: boolean
  onSelect: (tenantId: number) => void
}

/**
 * TenantCard - Card component for displaying a tenant in grid view
 * 
 * Features:
 * - Displays tenant logo, name, status
 * - Shows quick stats (orders, users, WhatsApp connection)
 * - Hover effects (scale, shadow elevation)
 * - Memoized for performance in virtualized grids
 * - Accessibility: keyboard navigation ready
 */
export const TenantCard = memo(function TenantCard({
  tenant,
  isSelected = false,
  isLoading = false,
  onSelect,
}: TenantCardProps) {
  const [isHovering, setIsHovering] = useState(false)

  const handleClick = () => {
    if (!isLoading) {
      onSelect(tenant.id)
    }
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      handleClick()
    }
  }

  const lastActivityDate = tenant.last_activity_at
    ? new Date(tenant.last_activity_at)
    : null

  const formattedLastActivity = lastActivityDate
    ? lastActivityDate.toLocaleDateString('pt-BR', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : 'N/A'

  const statusColor = tenant.active ? 'bg-green-500' : 'bg-gray-400'
  const statusLabel = tenant.active ? 'Ativo' : 'Inativo'

  return (
    <Card
      className={cn(
        'relative overflow-hidden transition-all duration-200 cursor-pointer',
        'hover:shadow-lg hover:scale-105',
        isSelected && 'ring-2 ring-blue-500 shadow-lg',
        isLoading && 'opacity-50 cursor-not-allowed',
        'p-4',
      )}
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      role="button"
      tabIndex={isLoading ? -1 : 0}
      aria-selected={isSelected}
      aria-disabled={isLoading}
    >
      {/* Selected indicator */}
      {isSelected && (
        <div className="absolute top-2 right-2">
          <Badge variant="default" className="bg-blue-500">
            Selecionado
          </Badge>
        </div>
      )}

      {/* Tenant Logo / Avatar */}
      <div className="flex items-center gap-3 mb-3">
        <div className="relative">
          {tenant.logo_url ? (
            <img
              src={tenant.logo_url}
              alt={tenant.name}
              className="w-12 h-12 rounded-lg object-cover"
            />
          ) : (
            <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white">
              <Building2 className="w-6 h-6" />
            </div>
          )}
          {/* Status dot */}
          <div className={cn('absolute bottom-0 right-0 w-3 h-3 rounded-full border border-white', statusColor)} />
        </div>

        <div className="flex-1 min-w-0">
          <h3 className="font-semibold text-sm truncate text-foreground">
            {tenant.name}
          </h3>
          <p className="text-xs text-muted-foreground truncate">
            @{tenant.slug}
          </p>
        </div>
      </div>

      {/* Status and Plan */}
      <div className="flex items-center gap-2 mb-3 flex-wrap">
        <Badge variant="outline" className="text-xs">
          {statusLabel}
        </Badge>
        {tenant.plan && (
          <Badge variant="secondary" className="text-xs">
            {tenant.plan}
          </Badge>
        )}
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-2 gap-2 mb-3">
        {/* Orders */}
        <div className="flex items-center gap-1 p-2 rounded bg-secondary/50">
          <ShoppingCart className="w-3 h-3 text-muted-foreground" />
          <span className="text-xs font-medium">
            {tenant.orders_count ?? 0}
          </span>
        </div>

        {/* Users */}
        <div className="flex items-center gap-1 p-2 rounded bg-secondary/50">
          <Users className="w-3 h-3 text-muted-foreground" />
          <span className="text-xs font-medium">
            {tenant.users_count ?? 0}
          </span>
        </div>
      </div>

      {/* WhatsApp & Last Activity */}
      <div className="flex items-center justify-between mb-3 text-xs">
        <div className="flex items-center gap-1">
          {tenant.whatsapp_connected ? (
            <div className="flex items-center gap-1 text-green-600">
              <MessageCircle className="w-3 h-3" />
              <span>WhatsApp</span>
            </div>
          ) : (
            <div className="flex items-center gap-1 text-muted-foreground">
              <MessageCircle className="w-3 h-3" />
              <span>Inativo</span>
            </div>
          )}
        </div>
        <div className="flex items-center gap-1 text-muted-foreground">
          <Clock className="w-3 h-3" />
          <span>{formattedLastActivity}</span>
        </div>
      </div>

      {/* Action Button */}
      <Button
        variant={isSelected ? 'default' : 'outline'}
        size="sm"
        className="w-full justify-between"
        disabled={isLoading}
      >
        <span>{isSelected ? 'Conectado' : 'Conectar'}</span>
        <ChevronRight className={cn(
          'w-4 h-4 transition-transform',
          isHovering && 'translate-x-1',
        )} />
      </Button>
    </Card>
  )
})

TenantCard.displayName = 'TenantCard'
