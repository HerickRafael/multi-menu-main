import { useEffect, useState, type FormEvent } from 'react'
import {
  ArrowLeft,
  Calculator,
  CircleDollarSign,
  Percent,
  RefreshCw,
  Save,
  Settings as SettingsIcon,
  Target,
  Wallet,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type Settings = {
  default_tax_percentage: number
  ifood_fee_percentage: number
  rappi_fee_percentage: number
  ubereats_fee_percentage: number
  own_delivery_fee_percentage: number
  hourly_labor_cost: number
  target_profit_margin: number
  monthly_revenue_goal: number
  monthly_profit_goal: number
}

type Payload = {
  settings: Settings
  flash: { success: string | null }
  urls: { submit: string; dashboard: string; recalculate: string }
}

declare global {
  interface Window {
    __ADMIN_STORE_FINANCIAL_SETTINGS__?: Payload
  }
}

function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function formatBR(n: number, decimals = 2): string {
  return n.toFixed(decimals).replace('.', ',')
}

export default function AdminStoreFinancialSettingsPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_FINANCIAL_SETTINGS__) || ({} as Payload)
  const urls = payload.urls
  const s = payload.settings ?? ({} as Settings)

  const [defaultTax, setDefaultTax] = useState(formatBR(s.default_tax_percentage ?? 0))
  const [ifoodFee, setIfoodFee] = useState(formatBR(s.ifood_fee_percentage ?? 0))
  const [rappiFee, setRappiFee] = useState(formatBR(s.rappi_fee_percentage ?? 0))
  const [ubereatsFee, setUbereatsFee] = useState(formatBR(s.ubereats_fee_percentage ?? 0))
  const [ownDeliveryFee, setOwnDeliveryFee] = useState(formatBR(s.own_delivery_fee_percentage ?? 0))
  const [hourlyLabor, setHourlyLabor] = useState(formatBR(s.hourly_labor_cost ?? 0))
  const [targetMargin, setTargetMargin] = useState(formatBR(s.target_profit_margin ?? 30))
  const [revenueGoal, setRevenueGoal] = useState(formatBR(s.monthly_revenue_goal ?? 0))
  const [profitGoal, setProfitGoal] = useState(formatBR(s.monthly_profit_goal ?? 0))
  const [recalculating, setRecalculating] = useState(false)

  useEffect(() => {
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleSubmit(e: FormEvent) {
    const margin = Number.parseFloat(targetMargin.replace(',', '.'))
    if (!Number.isFinite(margin) || margin < 0 || margin > 100) {
      e.preventDefault()
      showToast('Margem de lucro alvo deve estar entre 0 e 100%.', 'error')
    }
  }

  async function recalculateCosts() {
    if (!window.confirm('Recalcular custos de todos os produtos? Isso pode levar alguns segundos.')) return
    setRecalculating(true)
    try {
      const fd = new FormData()
      fd.append('csrf_token', getCsrfToken())
      const res = await fetch(urls.recalculate, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; updated?: number; message?: string } | null
      if (data?.success) {
        showToast(data.message || `Custos recalculados (${data.updated ?? 0} produtos).`, 'success')
      } else {
        showToast(data?.message || 'Falha ao recalcular custos.', 'error')
      }
    } catch {
      showToast('Falha de rede ao recalcular.', 'error')
    } finally {
      setRecalculating(false)
    }
  }

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Configurações financeiras"
        description="Taxas das plataformas, custo de mão-de-obra e metas mensais para análise de margem."
        icon={<SettingsIcon className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={recalculateCosts}
              disabled={recalculating}
              className="gap-1.5"
            >
              <RefreshCw className={`h-3.5 w-3.5 ${recalculating ? 'animate-spin' : ''}`} />
              {recalculating ? 'Recalculando...' : 'Recalcular custos'}
            </Button>
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.dashboard}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
          </div>
        }
      />

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-4xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection
          title="Impostos e taxas padrão"
          description="Aplicado no cálculo de margem de lucro dos produtos quando não há override específico."
        >
          <FormField label="Imposto padrão (%)" htmlFor="fs-tax">
            <div className="relative max-w-xs">
              <Input
                id="fs-tax"
                name="default_tax_percentage"
                value={defaultTax}
                onChange={(e) => setDefaultTax(moneyMask(e.target.value))}
                inputMode="decimal"
                placeholder="0,00"
                className="pr-10"
              />
              <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
            </div>
          </FormField>
        </FormSection>

        <FormSection
          title="Taxas de plataformas"
          description="Percentual cobrado por cada canal sobre o valor do pedido. Usado para calcular margem real por canal."
        >
          <div className="grid gap-3 md:grid-cols-2">
            <FormField label="iFood (%)" htmlFor="fs-ifood">
              <div className="relative">
                <Input
                  id="fs-ifood"
                  name="ifood_fee_percentage"
                  value={ifoodFee}
                  onChange={(e) => setIfoodFee(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pr-10"
                />
                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
              </div>
            </FormField>

            <FormField label="Rappi (%)" htmlFor="fs-rappi">
              <div className="relative">
                <Input
                  id="fs-rappi"
                  name="rappi_fee_percentage"
                  value={rappiFee}
                  onChange={(e) => setRappiFee(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pr-10"
                />
                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
              </div>
            </FormField>

            <FormField label="Uber Eats (%)" htmlFor="fs-uber">
              <div className="relative">
                <Input
                  id="fs-uber"
                  name="ubereats_fee_percentage"
                  value={ubereatsFee}
                  onChange={(e) => setUbereatsFee(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pr-10"
                />
                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
              </div>
            </FormField>

            <FormField label="Entrega própria (%)" htmlFor="fs-own" hint="Custo médio interno de entrega como % do pedido.">
              <div className="relative">
                <Input
                  id="fs-own"
                  name="own_delivery_fee_percentage"
                  value={ownDeliveryFee}
                  onChange={(e) => setOwnDeliveryFee(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pr-10"
                />
                <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
              </div>
            </FormField>
          </div>
        </FormSection>

        <FormSection
          title="Custo de mão-de-obra"
          description="Custo hora médio considerando todos os funcionários — usado no CMV dos produtos com tempo de preparo."
        >
          <FormField label="Custo por hora" htmlFor="fs-labor">
            <div className="relative max-w-xs">
              <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
              <Input
                id="fs-labor"
                name="hourly_labor_cost"
                value={hourlyLabor}
                onChange={(e) => setHourlyLabor(moneyMask(e.target.value))}
                inputMode="decimal"
                placeholder="0,00"
                className="pl-9"
              />
            </div>
          </FormField>
        </FormSection>

        <FormSection
          title="Metas do negócio"
          description="Margem alvo, faturamento e lucro mensais. Usados nos dashboards e indicadores."
        >
          <FormField
            label="Margem de lucro alvo (%)"
            htmlFor="fs-margin"
            hint="Margem que você quer atingir em cada produto. Usado para destacar produtos com margem baixa."
          >
            <div className="relative max-w-xs">
              <Input
                id="fs-margin"
                name="target_profit_margin"
                value={targetMargin}
                onChange={(e) => setTargetMargin(moneyMask(e.target.value))}
                inputMode="decimal"
                placeholder="30,00"
                className="pr-10"
              />
              <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">%</span>
            </div>
          </FormField>

          <div className="grid gap-3 md:grid-cols-2">
            <FormField label="Meta de faturamento mensal" htmlFor="fs-rev-goal">
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="fs-rev-goal"
                  name="monthly_revenue_goal"
                  value={revenueGoal}
                  onChange={(e) => setRevenueGoal(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pl-9"
                />
              </div>
            </FormField>

            <FormField label="Meta de lucro mensal" htmlFor="fs-profit-goal">
              <div className="relative">
                <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">R$</span>
                <Input
                  id="fs-profit-goal"
                  name="monthly_profit_goal"
                  value={profitGoal}
                  onChange={(e) => setProfitGoal(moneyMask(e.target.value))}
                  inputMode="decimal"
                  placeholder="0,00"
                  className="pl-9"
                />
              </div>
            </FormField>
          </div>
        </FormSection>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-end gap-2">
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              Salvar configurações
            </Button>
          </div>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
