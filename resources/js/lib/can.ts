import { usePage } from "@inertiajs/vue3";

export function can(permission : string): boolean {
    const page = usePage();

    const raw = (page.props.auth as any)?.permissions;
    const permissions : string[] = Array.isArray(raw) ? raw : [];

    return permissions.includes(permission);
}

/** Safely get permissions as an array, handling cases where Laravel Collections
 *  are serialized as JSON objects in production. */
export function getPermissionsArray(raw: any): string[] {
    if (Array.isArray(raw)) return raw;
    if (raw && typeof raw === 'object') return Object.values(raw).filter((v): v is string => typeof v === 'string');
    return [];
}