import { createElement as h } from 'react';
import htm from 'htm';
import { cn } from '@/components/ui/utils';

const html = htm.bind(h);

export function Button({ variant = 'default', size = 'default', className = '', children, ...rest }) {
  const v =
    variant === 'outline'
      ? 'border border-slate-200 bg-white hover:bg-slate-50 text-slate-900'
      : variant === 'destructive'
        ? 'bg-red-600 hover:bg-red-700 text-white'
        : variant === 'ghost'
          ? 'hover:bg-slate-100 text-slate-700'
          : 'bg-slate-900 hover:bg-slate-800 text-white';
  const s = size === 'sm' ? 'h-8 px-3 text-xs' : size === 'lg' ? 'h-11 px-6' : 'h-9 px-4 text-sm';
  return html`<button
    type=${rest.type || 'button'}
    class=${cn('inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors disabled:opacity-50', v, s, className)}
    ...${rest}
  >
    ${children}
  </button>`;
}
