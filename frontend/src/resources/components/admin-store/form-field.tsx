import type { ReactNode } from 'react'
import { Label } from '@/components/ui/label'
import { cn } from '@/js/lib/utils'

type FormFieldProps = {
  label?: ReactNode
  hint?: ReactNode
  error?: ReactNode
  required?: boolean
  htmlFor?: string
  children: ReactNode
  className?: string
}

export function FormField({ label, hint, error, required, htmlFor, children, className }: FormFieldProps) {
  return (
    <div className={cn('space-y-1.5', className)}>
      {label && (
        <Label htmlFor={htmlFor} className="text-sm font-medium text-zinc-700">
          {label}
          {required && <span className="ml-1 text-red-600">*</span>}
        </Label>
      )}
      {children}
      {hint && !error && <p className="text-xs text-zinc-500">{hint}</p>}
      {error && <p className="text-xs text-red-600">{error}</p>}
    </div>
  )
}

type FormSectionProps = {
  title?: ReactNode
  description?: ReactNode
  children: ReactNode
  className?: string
}

export function FormSection({ title, description, children, className }: FormSectionProps) {
  return (
    <section className={cn('rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm', className)}>
      {(title || description) && (
        <div className="mb-4 space-y-1">
          {title && <h2 className="text-base font-semibold text-zinc-800">{title}</h2>}
          {description && <p className="text-sm text-zinc-500">{description}</p>}
        </div>
      )}
      <div className="space-y-4">{children}</div>
    </section>
  )
}
