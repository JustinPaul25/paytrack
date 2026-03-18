/**
 * Product/purchase record unit options for search select.
 */
export const PRODUCT_UNITS = [
    'Bottle',
    'Box',
    'Pcs',
    'Unit',
    'Pack',
    'Set',
    'Kg',
    'Gal',
    'Roll',
    'Can',
    'Dozen',
    'Pot',
    'Ream',
    'Pad',
    'Meter',
    'Bundle',
    'Pair',
    'Tube',
    'Bar',
] as const;

export type ProductUnit = (typeof PRODUCT_UNITS)[number];

export function productUnitOptions(): { value: string; label: string }[] {
    return PRODUCT_UNITS.map((u) => ({ value: u, label: u }));
}

/**
 * Options for a row, including current unit if it's not in the standard list (e.g. legacy data).
 * Uses case-insensitive match so we don't show both "Pcs" and "pcs" when DB stores lowercase.
 */
export function unitOptionsForRow(currentUnit: string): { value: string; label: string }[] {
    const options = productUnitOptions();
    if (currentUnit && !options.some((o) => o.value.toLowerCase() === currentUnit.toLowerCase())) {
        return [...options, { value: currentUnit, label: currentUnit }];
    }
    return options;
}
