import React, { useEffect } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/js/lib/utils'

interface DialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  children: React.ReactNode
}

interface DialogContentProps {
  children: React.ReactNode
  className?: string
}

interface DialogHeaderProps {
  children: React.ReactNode
  className?: string
}

interface DialogTitleProps {
  children: React.ReactNode
  className?: string
}

/**
 * Simple Dialog component using native HTML dialog element
 * Provides accessible modal functionality with backdrop
 */
export const Dialog = function Dialog({
  open,
  onOpenChange,
  children,
}: DialogProps) {
  const dialogRef = React.useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!dialogRef.current) return

    if (open) {
      dialogRef.current.style.display = 'flex'
      document.body.style.overflow = 'hidden'
    } else {
      dialogRef.current.style.display = 'none'
      document.body.style.overflow = 'auto'
    }

    return () => {
      document.body.style.overflow = 'auto'
    }
  }, [open])

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === dialogRef.current) {
      onOpenChange(false)
    }
  }

  const handleEscape = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      onOpenChange(false)
    }
  }

  return (
    <div
      ref={dialogRef}
      className="fixed inset-0 z-50 hidden bg-black/50 flex-col items-center justify-center p-4"
      onClick={handleBackdropClick}
      onKeyDown={handleEscape}
    >
      {children}
    </div>
  )
}

export const DialogContent = React.forwardRef<HTMLDivElement, DialogContentProps>(
  ({ children, className }, ref) => (
    <div
      ref={ref}
      className={cn(
        'relative w-full max-h-[90vh] rounded-lg border bg-background shadow-lg',
        'max-w-3xl flex flex-col',
        className,
      )}
      onClick={(e) => e.stopPropagation()}
    >
      {children}
    </div>
  ),
)
DialogContent.displayName = 'DialogContent'

export const DialogHeader = React.forwardRef<HTMLDivElement, DialogHeaderProps>(
  ({ children, className }, ref) => (
    <div
      ref={ref}
      className={cn('flex items-center justify-between border-b p-4', className)}
    >
      {children}
    </div>
  ),
)
DialogHeader.displayName = 'DialogHeader'

export const DialogTitle = React.forwardRef<HTMLHeadingElement, DialogTitleProps>(
  ({ children, className }, ref) => (
    <h2
      ref={ref}
      className={cn('text-lg font-semibold leading-none tracking-tight', className)}
    >
      {children}
    </h2>
  ),
)
DialogTitle.displayName = 'DialogTitle'

interface DialogCloseButtonProps {
  onClick: () => void
}

export const DialogCloseButton = React.forwardRef<HTMLButtonElement, DialogCloseButtonProps>(
  ({ onClick }, ref) => (
    <Button
      ref={ref}
      variant="ghost"
      size="icon-sm"
      onClick={onClick}
      aria-label="Fechar"
      className="absolute top-4 right-4"
    >
      <X className="h-4 w-4" />
    </Button>
  ),
)
DialogCloseButton.displayName = 'DialogCloseButton'
