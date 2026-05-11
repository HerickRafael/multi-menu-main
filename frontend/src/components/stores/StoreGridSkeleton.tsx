import { memo } from 'react'
import { cn } from '@/lib/utils'

interface StoreGridSkeletonProps {
  count?: number
  className?: string
}

/**
 * Individual skeleton card
 */
function SkeletonCard() {
  return (
    <div className="rounded-xl border-2 border-border bg-card p-6 space-y-4">
      {/* Header */}
      <div className="flex items-start justify-between gap-3">
        <div className="h-12 w-12 rounded-lg bg-muted animate-pulse" />
        <div className="flex flex-col gap-1 items-end flex-1">
          <div className="h-5 w-16 rounded bg-muted animate-pulse" />
          <div className="h-4 w-12 rounded bg-muted animate-pulse" />
        </div>
      </div>

      {/* Title */}
      <div className="space-y-2">
        <div className="h-6 w-3/4 rounded bg-muted animate-pulse" />
        <div className="h-4 w-1/4 rounded bg-muted animate-pulse" />
      </div>

      {/* Divider */}
      <div className="h-px bg-border/50" />

      {/* Stats Grid */}
      <div className="grid grid-cols-2 gap-3">
        <div className="h-12 rounded-lg bg-muted animate-pulse" />
        <div className="h-12 rounded-lg bg-muted animate-pulse" />
      </div>

      {/* Last Activity */}
      <div className="h-4 w-1/2 rounded bg-muted animate-pulse mt-auto pt-2" />

      {/* Button */}
      <div className="h-10 w-full rounded-lg bg-muted animate-pulse" />
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
      'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
      className,
    )}>
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonCard key={i} />
      ))}
    </div>
  )
})
