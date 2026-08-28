import { usePage } from "@inertiajs/vue3";

export function can(permission : string): boolean {
    const page = usePage();

    const raw = (page.props.auth as any)?.permissions;
    const permissions : string[] = Array.isArray(raw) ? raw : [];

    return permissions.includes(permission);
}