import { Skeleton } from '@/components/ui/skeleton'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { cn } from '@/js/lib/utils'

const headerWidths = ['w-20', 'w-24', 'w-28', 'w-32', 'w-24', 'w-36']
const rowWidths = ['w-16', 'w-20', 'w-24', 'w-28', 'w-32']
const rowOpacities = ['opacity-100', 'opacity-95', 'opacity-90', 'opacity-85', 'opacity-80', 'opacity-75', 'opacity-70', 'opacity-65']
const chartBarHeights = ['h-6', 'h-8', 'h-10', 'h-12', 'h-14', 'h-16', 'h-20', 'h-24', 'h-28', 'h-32', 'h-36', 'h-40']

function resolveChartHeight(height: number) {
  if (height >= 320) return 'h-80'
  if (height >= 280) return 'h-72'
  if (height >= 240) return 'h-64'
  return 'h-52'
}

export function CardSkeleton() {
  return (
    <Card>
      <CardHeader className="pb-2">
        <Skeleton className="h-4 w-32" />
        <Skeleton className="h-3 w-20" />
      </CardHeader>
      <CardContent>
        <Skeleton className="h-8 w-16" />
      </CardContent>
    </Card>
  )
}

export function KpiCardsSkeleton() {
  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      {Array.from({ length: 6 }).map((_, i) => (
        <CardSkeleton key={i} />
      ))}
    </div>
  )
}

export function TableSkeleton({ rows = 8, cols = 5 }: { rows?: number; cols?: number }) {
  return (
    <div className="rounded-md border">
      {/* Header */}
      <div className="border-b bg-muted/50 px-4 py-3">
        <div className="flex gap-4">
          {Array.from({ length: cols }).map((_, i) => (
            <Skeleton
              key={i}
              className={cn('h-4', headerWidths[i % headerWidths.length])}
            />
          ))}
        </div>
      </div>
      {/* Rows */}
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="border-b px-4 py-3 last:border-0">
          <div className="flex items-center gap-4">
            {Array.from({ length: cols }).map((_, j) => (
              <Skeleton
                key={j}
                className={cn(
                  'h-4',
                  rowWidths[(j + i) % rowWidths.length],
                  rowOpacities[i % rowOpacities.length],
                )}
              />
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}

export function PageSkeleton() {
  return (
    <div className="p-6 space-y-6 animate-fade-in">
      {/* Page header */}
      <div className="space-y-1">
        <Skeleton className="h-6 w-[200px]" />
        <Skeleton className="h-4 w-[300px]" />
      </div>
      {/* KPI cards */}
      <KpiCardsSkeleton />
      {/* Table */}
      <TableSkeleton />
    </div>
  )
}

export function ChartSkeleton({ height = 200 }: { height?: number }) {
  const containerHeight = resolveChartHeight(height)
  return (
    <div className={cn('rounded-md border bg-muted/20 flex items-center justify-center', containerHeight)}>
      <div className="flex gap-1 items-end h-24 px-4">
        {Array.from({ length: 12 }).map((_, i) => (
          <Skeleton
            key={i}
            className={cn('w-5 rounded-none rounded-t', chartBarHeights[i % chartBarHeights.length])}
          />
        ))}
      </div>
    </div>
  )
}
