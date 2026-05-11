import { memo } from 'react'
import { ArrowLeft, Clock, CheckCircle, AlertCircle } from 'lucide-react'
import { PageContainer, PageHeader } from '@/components/shared/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatCurrency, formatDate } from '@/lib/utils'
import { useTenant } from '@/contexts/TenantContext'
import { Link } from 'react-router-dom'

interface TimelineEvent {
  id: number
  event_type: string
  description: string
  created_at: string
  actor_name?: string
}

function timelineIcon(eventType: string) {
  if (eventType === 'completed' || eventType === 'paid') return <CheckCircle className="h-5 w-5 text-emerald-600" />
  if (eventType === 'cancelled' || eventType === 'error') return <AlertCircle className="h-5 w-5 text-red-600" />
  return <Clock className="h-5 w-5 text-blue-600" />
}

const TimelineItem = memo(function TimelineItem({ event, isLast }: { event: TimelineEvent; isLast: boolean }) {
  return (
    <div className="relative flex gap-4 pb-8">
      <div className="flex flex-col items-center">
        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
          {timelineIcon(event.event_type)}
        </div>
        {!isLast && <div className="absolute left-5 top-10 h-8 w-0.5 bg-muted"></div>}
      </div>
      <div className="flex-1 pt-1">
        <p className="font-medium">{event.description}</p>
        <p className="text-xs text-muted-foreground">
          {formatDate(event.created_at)} {event.actor_name && `• ${event.actor_name}`}
        </p>
      </div>
    </div>
  )
})

// Mock order data for demonstration
const mockOrder = {
  id: 12345,
  customer_name: 'João Silva',
  customer_email: 'joao@example.com',
  customer_phone: '(11) 99999-9999',
  company_name: 'Loja do Centro',
  status: 'completed',
  mode: 'delivery',
  total: 99.90,
  items_count: 3,
  created_at: new Date(Date.now() - 2*3600000).toISOString(),
  address: 'Rua Principal, 123 - Apto 456 - São Paulo, SP 01234-567',
}

const mockTimeline: TimelineEvent[] = [
  { id: 1, event_type: 'created', description: 'Pedido criado', created_at: new Date(Date.now() - 2*3600000).toISOString() },
  { id: 2, event_type: 'paid', description: 'Pagamento aprovado', created_at: new Date(Date.now() - 110*60000).toISOString(), actor_name: 'Sistema' },
  { id: 3, event_type: 'confirmed', description: 'Pedido confirmado pela loja', created_at: new Date(Date.now() - 100*60000).toISOString(), actor_name: 'Admin Loja' },
  { id: 4, event_type: 'preparing', description: 'Iniciado preparo do pedido', created_at: new Date(Date.now() - 85*60000).toISOString(), actor_name: 'Cozinha' },
  { id: 5, event_type: 'ready', description: 'Pedido pronto para entrega', created_at: new Date(Date.now() - 15*60000).toISOString(), actor_name: 'Cozinha' },
  { id: 6, event_type: 'shipped', description: 'Em rota de entrega', created_at: new Date(Date.now() - 5*60000).toISOString(), actor_name: 'Entregador' },
  { id: 7, event_type: 'completed', description: 'Entregue ao cliente', created_at: new Date().toISOString(), actor_name: 'Entregador' },
]

export default function OrderDetailPage() {
  const { selectedTenantSlug } = useTenant()
  const tenantSlug = selectedTenantSlug || 'select-tenant'

  function statusVariant(status: string): 'default' | 'success' | 'warning' | 'destructive' {
    if (status === 'completed' || status === 'paid') return 'success'
    if (status === 'pending') return 'warning'
    if (status === 'canceled') return 'destructive'
    return 'default'
  }

  return (
    <PageContainer>
      <PageHeader title={`Pedido #${mockOrder.id}`} description={`Detalhes e timeline`}>
        <Badge variant={statusVariant(mockOrder.status)} className="gap-1">
          {mockOrder.status.toUpperCase()}
        </Badge>
      </PageHeader>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Order Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Informações do Pedido</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs text-muted-foreground">Status</p>
                  <Badge variant={statusVariant(mockOrder.status)}>{mockOrder.status.toUpperCase()}</Badge>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Modo</p>
                  <p className="font-medium">{mockOrder.mode === 'delivery' ? 'Entrega' : 'Retirada'}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Data</p>
                  <p className="font-medium">{formatDate(mockOrder.created_at)}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Total</p>
                  <p className="text-lg font-bold">{formatCurrency(mockOrder.total)}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Customer Info */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Dados do Cliente</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <p className="text-xs text-muted-foreground">Nome</p>
                <p className="font-medium">{mockOrder.customer_name}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Email</p>
                <p className="text-sm">{mockOrder.customer_email}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Telefone</p>
                <p className="text-sm">{mockOrder.customer_phone}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Endereço de Entrega</p>
                <p className="text-sm">{mockOrder.address}</p>
              </div>
            </CardContent>
          </Card>

          {/* Timeline */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex gap-2 items-center">
                <Clock className="h-4 w-4" />
                Timeline do Pedido
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="mt-4">
                {mockTimeline.map((event, idx) => (
                  <TimelineItem key={event.id} event={event} isLast={idx === mockTimeline.length - 1} />
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Store Info */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Loja</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="font-medium">{mockOrder.company_name}</p>
            </CardContent>
          </Card>

          {/* Items */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Itens</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">{mockOrder.items_count}</p>
              <p className="text-xs text-muted-foreground">itens no pedido</p>
            </CardContent>
          </Card>

          {/* Actions */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Ações</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button variant="outline" className="w-full">Reimpressão</Button>
              <Button variant="outline" className="w-full">Comunicado ao Cliente</Button>
              <Button variant="outline" className="w-full text-destructive">Cancelar Pedido</Button>
            </CardContent>
          </Card>

          {/* Back Button */}
          <Button asChild variant="ghost" className="w-full gap-2">
            <Link to={`/superadmin/tenant/${tenantSlug}/orders`}>
              <ArrowLeft className="h-4 w-4" />
              Voltar para Pedidos
            </Link>
          </Button>
        </div>
      </div>
    </PageContainer>
  )
}
