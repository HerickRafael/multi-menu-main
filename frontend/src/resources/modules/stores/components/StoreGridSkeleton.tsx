import { memo } from 'react'
import { cn } from '@/js/lib/utils'

interface StoreGridSkeletonProps {
  count?: number
  className?: string
}

/**
 * Individual skeleton card
 */
function SkeletonCard() {
  return (
    <div className="rounded-md border border-border/70 bg-card p-4 space-y-3">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <div className="h-10 w-10 rounded-md bg-muted animate-pulse" />
          <div className="space-y-1">
            <div className="h-4 w-32 rounded bg-muted animate-pulse" />
            <div className="h-3 w-16 rounded bg-muted animate-pulse" />
          </div>
        </div>
        <div className="h-5 w-12 rounded bg-muted animate-pulse" />
      </div>

      <div className="grid grid-cols-3 gap-2">
        <div className="h-10 rounded bg-muted animate-pulse" />
        <div className="h-10 rounded bg-muted animate-pulse" />
        <div className="h-10 rounded bg-muted animate-pulse" />
      </div>

      <div className="flex gap-1">
        <div className="h-4 w-14 rounded bg-muted animate-pulse" />
        <div className="h-4 w-12 rounded bg-muted animate-pulse" />
        <div className="h-4 w-12 rounded bg-muted animate-pulse" />
      </div>

      <div className="flex items-center justify-between gap-2 pt-2 border-t border-border/60">
        <div className="flex gap-2">
          <div className="h-7 w-16 rounded bg-muted animate-pulse" />
          <div className="h-7 w-14 rounded bg-muted animate-pulse" />
          <div className="h-7 w-16 rounded bg-muted animate-pulse" />
        </div>
        <div className="h-7 w-7 rounded bg-muted animate-pulse" />
      </div>
    </div>
  )
}

export const StoreGridSkeleton = memo(function StoreGridSkeleton({
  count = 8,
  className,
}: StoreGridSkeletonProps) {
  return (
    <div className={cn(
      'grid gap-4',
      'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
      className,
    )}>
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonCard key={i} />
      ))}
    </div>
  )
})
