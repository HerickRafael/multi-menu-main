import { useState, type FormEvent } from 'react'
import { ArrowLeft, Save, Tag } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type Category = {
  id: number | null
  name: string
  sort_order: number
  active: boolean
}

type CategoryFormPayload = {
  category: Category
  urls: {
    list: string
    submit: string
    destroy?: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CATEGORY_FORM__?: CategoryFormPayload
  }
}

export default function AdminStoreCategoryFormPage() {
  const ctx = useStoreContext()
  const payload = (typeof window !== 'undefined' && window.__ADMIN_STORE_CATEGORY_FORM__) || ({} as CategoryFormPayload)
  const { urls } = payload

  const [name, setName] = useState(payload.category?.name ?? '')
  const [sortOrder, setSortOrder] = useState<number>(payload.category?.sort_order ?? 0)
  const [active, setActive] = useState<boolean>(payload.category?.active ?? true)
  const [errors, setErrors] = useState<Record<string, string>>({})

  const isEdit = !!payload.category?.id

  function handleSubmit(e: FormEvent) {
    const trimmed = name.trim()
    if (!trimmed) {
      e.preventDefault()
      setErrors({ name: 'Informe o nome da categoria.' })
      return
    }
    setErrors({})
    // Let the regular form POST proceed — PHP controller redirects back to list
  }

  return (
    <AdminStorePageShell section="categories">
      <AdminPageHeader
        title={isEdit ? 'Editar categoria' : 'Nova categoria'}
        description={isEdit ? 'Atualize os dados da categoria.' : 'Crie uma nova categoria para agrupar seus produtos.'}
        icon={<Tag className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button asChild variant="outline">
            <a href={urls.list} className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              Voltar
            </a>
          </Button>
        }
      />

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-2xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />

        <FormSection
          title="Dados da categoria"
          description="Defina o nome exibido aos clientes e a ordem de aparição no cardápio."
        >
          <FormField label="Nome" htmlFor="cat-name" required error={errors.name}>
            <Input
              id="cat-name"
              name="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              maxLength={120}
              placeholder="Ex.: Lanches, Bebidas, Sobremesas..."
              autoFocus
            />
          </FormField>

          <FormField
            label="Ordem de exibição"
            htmlFor="cat-sort"
            hint="Categorias com menor número aparecem primeiro no cardápio."
          >
            <Input
              id="cat-sort"
              name="sort_order"
              type="number"
              value={sortOrder}
              onChange={(e) => setSortOrder(Number(e.target.value) || 0)}
              min={0}
              max={9999}
              className="w-32"
            />
          </FormField>

          <FormField label="Status">
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                name="active"
                value="1"
                checked={active}
                onChange={(e) => setActive(e.target.checked)}
                className="peer sr-only"
              />
              <span className="relative inline-flex h-5 w-9 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
                <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
              </span>
              <span className="text-sm text-zinc-700">
                {active ? 'Categoria ativa (visível no cardápio)' : 'Categoria inativa (oculta no cardápio)'}
              </span>
            </label>
          </FormField>
        </FormSection>

        <div className="flex flex-wrap items-center gap-2">
          <Button type="submit" className="gap-2">
            <Save className="h-4 w-4" />
            {isEdit ? 'Salvar alterações' : 'Criar categoria'}
          </Button>
          <Button asChild type="button" variant="outline">
            <a href={urls.list}>Cancelar</a>
          </Button>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
