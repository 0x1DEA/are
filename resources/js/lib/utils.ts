import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';



export const $$ = {
    tel: '+17737354400',
    tel_s: '+1 (773) 735-4400',
    addr: '5744 S Pulaski Rd, Chicago, IL 60629',
    email: 'info@americarealestateinc.com',
    social_facebook: 'https://www.facebook.com/AmericaRealEstateInc/',
    social_instagram: 'https://www.instagram.com/americarealestateinc/',
};

export function routeIsURL(url = '') {
    return window.location.pathname === url;
}

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
        l?.address_number,
        l?.address_direction,
        l?.address_street,
        l?.address_street_suffix,
    ].filter(f => f !== null).join(' ');

    return `${street}, ${l.address_city}, ${l.address_state} ${l.address_postal}`;
};

export const listingThumb = (l: any) => {
    if (l.thumbnail) {
        return '/storage/' + l.thumbnail.url;
    } else {
        return false;
    }
};

export const listingRooms = (l: any) => {
    let out: string[] = [];

    if (l.bedrooms) out.push(`${l.bedrooms} bed`)
    if (l.total_bathrooms) out.push(`${l.total_bathrooms} bath`)
    if (l.living_area_sq_ft) out.push(`${l.living_area_sq_ft} sqft.`)

    return out.join(' · ');
};

export const listingOverview = (l: any) => {
    let out: string[] = [];

    const date = (d: string) => new Date(d).toLocaleDateString()
    const datetime = (d: string) => new Date(d).toLocaleString()

    if (l.bedrooms) out.push(`${l.status}`)
    if (l.total_bathrooms) out.push(`Listed ${date(l.mls_data['OriginalEntryTimestamp'])}`)
    if (l.living_area_sq_ft) out.push(`Updated ${datetime(l.mls_data['StatusChangeTimestamp'])}`)

    return out.join(' · ');
};

export const listingMLSID = (l: any, long: boolean = true) => {
    return long ? `MRED: #${l.mls_id.replace('MRD', '')}` : `#${l.mls_id.replace('MRD', '')}`;
};

export const fmtPrice = (l: any, compact: boolean = false) => {
    let na = compact ? 'N/A' : 'No Price Data';
    let func = compact ? fmtPriceCmp : fmtPriceLng;
    let price = l.sale_price ?? l.rent_price ?? null;

    return price ? func(price) : na;
}

export const fmtPriceLng = (n: number) => formatterPrice.format(n).replace('.00', '');
export const fmtPriceCmp = (n: number) => formatterPriceShort.format(n).replace('.00', '');
