import React from 'react'
import { Card } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

/**
 * TenantCardSkeleton - Loading skeleton for TenantCard
 */
export function TenantCardSkeleton() {
  return (
    <Card className="p-4">
      {/* Header with avatar and text */}
      <div className="flex items-center gap-3 mb-3">
        <Skeleton className="w-12 h-12 rounded-lg flex-shrink-0" />
        <div className="flex-1 min-w-0">
          <Skeleton className="h-4 w-3/4 mb-1" />
          <Skeleton className="h-3 w-1/2" />
        </div>
      </div>

      {/* Badges */}
      <div className="flex items-center gap-2 mb-3">
        <Skeleton className="h-6 w-16" />
        <Skeleton className="h-6 w-16" />
      </div>

      {/* Stats grid */}
      <div className="grid grid-cols-2 gap-2 mb-3">
        <Skeleton className="h-8 rounded" />
        <Skeleton className="h-8 rounded" />
      </div>

      {/* Footer with time and button */}
      <div className="space-y-2">
        <Skeleton className="h-3 w-full" />
        <Skeleton className="h-9 w-full rounded" />
      </div>
    </Card>
  )
}
