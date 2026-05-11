import { createElement as h } from 'react';
import htm from 'htm';
import { cn } from '@/components/ui/utils';

const html = htm.bind(h);

export function Badge({ children, variant = 'default' }) {
  const c =
    variant === 'success'
      ? 'bg-emerald-100 text-emerald-800'
      : variant === 'neutral'
        ? 'bg-slate-100 text-slate-600'
        : 'bg-slate-900 text-white';
  return html`<span class=${cn('inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', c)}>${children}</span>`;
}
