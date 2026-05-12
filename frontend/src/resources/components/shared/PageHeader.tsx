import type { ReactNode } from 'react'
import { cn } from '@/js/lib/utils'

interface PageHeaderProps {
  title: string
  description?: string
  children?: ReactNode
  className?: string
}

export function PageHeader({ title, description, children, className }: PageHeaderProps) {
  return (
    <div className={cn('flex items-start justify-between gap-4', className)}>
      <div>
        <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
        {description && <p className="text-sm text-muted-foreground mt-0.5">{description}</p>}
      </div>
      {children && <div className="flex items-center gap-2 shrink-0">{children}</div>}
    </div>
  )
}

interface PageContainerProps {
  children: ReactNode
  className?: string
}

export function PageContainer({ children, className }: PageContainerProps) {
  return (
    <div className={cn('p-6 space-y-6 animate-fade-in', className)}>
      {children}
    </div>
  )
}
