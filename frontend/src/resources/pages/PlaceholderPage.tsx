import { Construction } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import type { LucideIcon } from 'lucide-react'

interface PlaceholderPageProps {
  title: string
  description: string
  plan: string
  icon?: LucideIcon
}

export function PlaceholderPage({ title, description, plan, icon: Icon = Construction }: PlaceholderPageProps) {
  return (
    <PageContainer>
      <PageHeader title={title} description={description}>
        <Badge variant="secondary">{plan}</Badge>
      </PageHeader>
      <Card>
        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
          <div className="mb-4 rounded-full border-2 border-dashed border-muted-foreground/25 p-6">
            <Icon className="h-10 w-10 text-muted-foreground/40" />
          </div>
          <h3 className="text-base font-medium">Módulo em desenvolvimento</h3>
          <p className="mt-1 text-sm text-muted-foreground max-w-sm">
            Esta seção será implementada no <strong>{plan}</strong>. A estrutura de navegação e rotas estão prontas.
          </p>
        </CardContent>
      </Card>
    </PageContainer>
  )
}
