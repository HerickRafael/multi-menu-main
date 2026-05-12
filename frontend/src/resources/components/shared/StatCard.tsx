import type { ReactNode } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/js/lib/utils'

interface StatCardProps {
  title: string
  value: ReactNode
  description?: ReactNode
  className?: string
  valueClassName?: string
}

export function StatCard({ title, value, description, className, valueClassName }: StatCardProps) {
  return (
    <Card className={className}>
      <CardHeader className="pb-2">
        <CardTitle className="text-sm text-muted-foreground">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className={cn('text-3xl font-bold', valueClassName)}>{value}</div>
        {description && <div className="text-xs text-muted-foreground mt-1">{description}</div>}
      </CardContent>
    </Card>
  )
}
