import * as React from 'react'
import * as ProgressPrimitive from '@radix-ui/react-progress'
import { cn } from '@/js/lib/utils'

const progressWidths = [
  { max: 0, className: 'w-0' },
  { max: 10, className: 'w-[10%]' },
  { max: 20, className: 'w-[20%]' },
  { max: 30, className: 'w-[30%]' },
  { max: 40, className: 'w-[40%]' },
  { max: 50, className: 'w-1/2' },
  { max: 60, className: 'w-[60%]' },
  { max: 70, className: 'w-[70%]' },
  { max: 80, className: 'w-[80%]' },
  { max: 90, className: 'w-[90%]' },
  { max: 100, className: 'w-full' },
]

function resolveProgressWidth(value: number | null | undefined) {
  if (value === undefined || value === null || Number.isNaN(value)) return 'w-0'
  const normalized = Math.min(100, Math.max(0, value))
  return progressWidths.find((step) => normalized <= step.max)?.className ?? 'w-0'
}

const Progress = React.forwardRef<
  React.ElementRef<typeof ProgressPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof ProgressPrimitive.Root>
>(({ className, value, ...props }, ref) => (
  <ProgressPrimitive.Root
    ref={ref}
    className={cn('relative h-2 w-full overflow-hidden rounded-full bg-secondary', className)}
    {...props}
  >
    <ProgressPrimitive.Indicator
      className={cn(
        'h-full flex-1 bg-primary transition-[width] duration-300 ease-out',
        resolveProgressWidth(value),
      )}
    />
  </ProgressPrimitive.Root>
))
Progress.displayName = ProgressPrimitive.Root.displayName

export { Progress }
