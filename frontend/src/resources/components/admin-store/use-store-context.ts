import { useMemo } from 'react'
import { useParams } from 'react-router-dom'

export type StoreHour = {
  is_open: boolean
  open1: string | null
  close1: string | null
  open2: string | null
  close2: string | null
}

export type StoreContext = {
  slug?: string
  company_name?: string
  company_logo?: string
  company_banner?: string
  min_order?: number | null
  system_logo?: string
  store_is_open?: boolean
  ifood_is_active?: boolean
  store_hours?: Record<string, StoreHour>
  settings_url?: string
  theme?: {
    primaryColor?: string
    primaryGradient?: string
  }
}

function normalizeHexColor(color?: string, fallback = '#4F46E5') {
  if (!color) return fallback
  const value = color.trim().replace(/^#/, '')
  if (/^[0-9a-fA-F]{3}$/.test(value)) {
    return `#${value.split('').map((char) => char + char).join('')}`
  }
  if (/^[0-9a-fA-F]{6}$/.test(value)) return `#${value}`
  return fallback
}

function hexToRgb(color: string) {
  const hex = normalizeHexColor(color).slice(1)
  return {
    r: Number.parseInt(hex.slice(0, 2), 16),
    g: Number.parseInt(hex.slice(2, 4), 16),
    b: Number.parseInt(hex.slice(4, 6), 16),
  }
}

function rgba(color: string, alpha: number) {
  const { r, g, b } = hexToRgb(color)
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

function mix(color: string, target: string, amount: number) {
  const source = hexToRgb(color)
  const destination = hexToRgb(target)
  const blend = (start: number, end: number) => Math.round(start + (end - start) * amount)
  return `rgb(${blend(source.r, destination.r)}, ${blend(source.g, destination.g)}, ${blend(source.b, destination.b)})`
}

function resolveMediaUrl(path?: string) {
  if (!path) return ''
  if (/^(https?:)?\/\//i.test(path) || path.startsWith('data:')) return path
  return path.startsWith('/') ? path : `/${path}`
}

function luminance(color: string) {
  const { r, g, b } = hexToRgb(color)
  const transform = (value: number) => {
    const normalized = value / 255
    return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4
  }
  return 0.2126 * transform(r) + 0.7152 * transform(g) + 0.0722 * transform(b)
}

export function getPalette(primaryColor?: string, primaryGradient?: string) {
  const color = normalizeHexColor(primaryColor)
  const dark = '#0f172a'
  const light = '#ffffff'
  const isLight = luminance(color) > 0.72
  const accent = isLight ? mix(color, dark, 0.45) : color
  const accentStrong = isLight ? mix(color, dark, 0.65) : mix(color, light, 0.18)
  const primarySoft = rgba(accent, 0.12)
  const primarySoftStrong = rgba(accent, 0.18)
  const accentFg = isLight ? dark : light

  return {
    primaryColor: color,
    primaryGradient: primaryGradient ?? `linear-gradient(135deg, ${accent} 0%, ${accentStrong} 100%)`,
    primaryForeground: accentFg,
    primarySoft,
    primarySoftStrong,
    accent,
    accentStrong,
    accentFg,
  }
}

export function useStoreContext() {
  const { slug: routeSlug } = useParams<{ slug: string }>()
  const ctx = ((typeof window !== 'undefined' && window.__ADMIN_STORE_CONTEXT__) || {}) as StoreContext
  return useMemo(() => {
    const slug = ctx.slug || routeSlug || ''
    const companyName = ctx.company_name || 'Loja'
    const palette = getPalette(ctx.theme?.primaryColor, ctx.theme?.primaryGradient)
    return {
      slug,
      companyName,
      companyLogo: resolveMediaUrl(ctx.company_logo),
      companyBanner: resolveMediaUrl(ctx.company_banner),
      minOrder: typeof ctx.min_order === 'number' ? ctx.min_order : null,
      systemLogo: ctx.system_logo || '/assets/icons/admin/logo-multimenu.png',
      storeIsOpen: ctx.store_is_open ?? false,
      ifoodIsActive: ctx.ifood_is_active ?? false,
      storeHours: ctx.store_hours ?? {},
      settingsUrl: ctx.settings_url || '',
      palette,
    }
  }, [ctx.slug, ctx.company_name, ctx.company_logo, ctx.company_banner, ctx.min_order, ctx.system_logo, ctx.store_is_open, ctx.ifood_is_active, ctx.store_hours, ctx.settings_url, ctx.theme?.primaryColor, ctx.theme?.primaryGradient, routeSlug])
}

export function getCsrfToken(): string {
  if (typeof document === 'undefined') return ''
  const meta = document.querySelector('meta[name="csrf-token"]')
  return meta?.getAttribute('content') || ''
}

export function formatCurrency(value: number | string | null | undefined): string {
  const num = typeof value === 'string' ? Number.parseFloat(value) : (value ?? 0)
  if (!Number.isFinite(num)) return 'R$ 0,00'
  return `R$ ${num.toFixed(2).replace('.', ',')}`
}
