import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export const formatDateTime = (isoDate: string | null): string =>
    isoDate === null
        ? ''
        : new Date(isoDate).toLocaleString('en-GB', {
              dateStyle: 'medium',
              timeStyle: 'short',
          });
