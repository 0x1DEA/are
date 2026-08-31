import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const formatterPrice = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

const formatterPriceShort = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    compactDisplay: 'short',
});

export const listingAddress = (l: any) => {
    let street = [
        l.address_number,
        l.address_direction,
        l.address_street,
        l.address_street_suffix,
    ].join(' ');

    return `${street}, ${l.address_city}, ${l.address_state} ${l.address_postal}`;
};

export const fmtPrice = (l: any, compact: boolean = false) => {
    return l.sales_price ? (compact ? fmtPriceCmp(l.sales_price) : fmtPriceLng(l.sales_price)) : (compact ? 'N/A' : 'No Price Data');
}

export const fmtPriceLng = (n: number) => formatterPrice.format(n).replace('.00', '');
export const fmtPriceCmp = (n: number) => formatterPriceShort.format(n).replace('.00', '');
