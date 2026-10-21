export interface NavItem { label: string; route: string; match: string; icon: string; permission?: string }
export interface NavGroup { label: string; hideLabel?: boolean; items: NavItem[] }

export const navigation: NavGroup[] = [
    { label: 'Ringkasan', hideLabel: true, items: [{ label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard', icon: 'bx-grid-alt' }] },
    { label: 'Master', items: [
        { label: 'Kategori', route: 'admin.category.index', match: 'admin.category.*', icon: 'bx-category', permission: 'category.view' },
        { label: 'OPD', route: 'admin.opd.index', match: 'admin.opd.*', icon: 'bx-buildings', permission: 'opd.manage' },
        { label: 'Status pegawai', route: 'admin.employee-status.index', match: 'admin.employee-status.*', icon: 'bx-id-card', permission: 'employee-status.manage' },
        { label: 'Pemateri', route: 'admin.trainer.index', match: 'admin.trainer.*', icon: 'bx-user-voice', permission: 'trainer.manage' },
        { label: 'Peserta', route: 'admin.participant.index', match: 'admin.participant.*', icon: 'bx-group', permission: 'participant.view' },
        { label: 'Kelas', route: 'admin.course.index', match: 'admin.course.*', icon: 'bx-book-open', permission: 'course.view' },
    ] },
    { label: 'Hasil', items: [
        { label: 'Laporan', route: 'admin.report', match: 'admin.report', icon: 'bx-file', permission: 'result.view' },
        { label: 'Data sertifikat', route: 'admin.certificate.index', match: 'admin.certificate.*', icon: 'bx-award', permission: 'certificate.manage' },
        { label: 'Ulasan', route: 'admin.review.index', match: 'admin.review.*', icon: 'bx-star', permission: 'review.manage' },
    ] },
    { label: 'Manajemen', items: [
        { label: 'Bantuan', route: 'admin.help.index', match: 'admin.help.*', icon: 'bx-help-circle', permission: 'help.manage' },
        { label: 'Pesan notifikasi', route: 'admin.management.notification-messages.index', match: 'admin.management.notification-messages.*', icon: 'bx-bell', permission: 'notification-template.manage' },
        { label: 'Hak akses', route: 'admin.management.permission.index', match: 'admin.management.permission.*', icon: 'bx-key', permission: 'permission.manage' },
        { label: 'Peran', route: 'admin.management.role.index', match: 'admin.management.role.*', icon: 'bx-shield-alt-2', permission: 'role.manage' },
        { label: 'Pengguna', route: 'admin.management.user.index', match: 'admin.management.user.*', icon: 'bx-user', permission: 'user.manage' },
        { label: 'Pengaturan', route: 'admin.management.setting.index', match: 'admin.management.setting.*', icon: 'bx-cog', permission: 'setting.manage' },
        { label: 'Log & backup', route: 'admin.management.log.index', match: 'admin.management.log.*', icon: 'bx-receipt', permission: 'log.view' },
    ] },
] ;

export const items = navigation.flatMap((section) => section.items);

export const trainerRoutes = new Set(['admin.dashboard', 'admin.course.index', 'admin.course.create', 'admin.participant.index', 'admin.result.index', 'admin.course-performance.index']);

export function roleLabel(roles?: string[], fallback?: string): string {
    const labels = { super_admin: 'Super Admin', administrator: 'Administrator', trainer: 'Pemateri', participant: 'Peserta' };
    const role = Object.keys(labels).find((name) => roles?.includes(name));
    return role ? labels[role] : fallback || 'Administrator';
}

export function isActive(match: string) {
    try { return Boolean(route().current(match)); } catch { return false; }
}

export function currentItem(): NavItem | null {
    try {
        const name = route().current();
        return name ? items.find((item) => name.startsWith(item.match.replace('.*', ''))) ?? null : null;
    } catch { return null; }
}
