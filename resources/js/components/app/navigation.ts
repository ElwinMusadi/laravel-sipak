import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Archive,
    ArrowLeftRight,
    BarChart3,
    BadgeCheck,
    BookOpenCheck,
    CircleCheckBig,
    CircleX,
    FileText,
    FileWarning,
    Hash,
    LayoutDashboard,
    MapPin,
    MessagesSquare,
    Package,
    ScrollText,
    Shield,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';

type NavigationHref = NonNullable<InertiaLinkProps['href']>;

export type ApplicationRole =
    | 'Administrator'
    | 'Bendahara Barang'
    | 'Kepala UPTD'
    | 'Kasie Penetapan'
    | 'Kasie Verifikasi'
    | 'Petugas Loket'
    | 'Petugas Penetapan'
    | 'Petugas Verifikasi';

export type ApplicationNavigationItem = {
    title: string;
    icon: LucideIcon;
    href?: NavigationHref;
    availability: 'available' | 'planned';
    roles: readonly ApplicationRole[];
};

export type ApplicationNavigationGroup = {
    title: string;
    items: readonly ApplicationNavigationItem[];
};

const allRoles: readonly ApplicationRole[] = [
    'Administrator',
    'Bendahara Barang',
    'Kepala UPTD',
    'Kasie Penetapan',
    'Kasie Verifikasi',
    'Petugas Loket',
    'Petugas Penetapan',
    'Petugas Verifikasi',
];

export const applicationNavigation: readonly ApplicationNavigationGroup[] = [
    {
        title: 'Dashboard',
        items: [
            {
                title: 'Dashboard',
                icon: LayoutDashboard,
                href: dashboard(),
                availability: 'available',
                roles: allRoles,
            },
        ],
    },
    {
        title: 'Operasional',
        items: [
            {
                title: 'BAP Pemakaian',
                icon: FileText,
                availability: 'planned',
                roles: ['Petugas Loket'],
            },
            {
                title: 'BAP Batal/Rusak',
                icon: FileWarning,
                availability: 'planned',
                roles: ['Petugas Loket'],
            },
            {
                title: 'Klarifikasi',
                icon: MessagesSquare,
                availability: 'planned',
                roles: [
                    'Bendahara Barang',
                    'Kasie Penetapan',
                    'Kasie Verifikasi',
                    'Petugas Loket',
                    'Petugas Penetapan',
                    'Petugas Verifikasi',
                ],
            },
        ],
    },
    {
        title: 'SKPD',
        items: [
            {
                title: 'Persediaan Nomeratur',
                icon: Hash,
                availability: 'planned',
                roles: ['Bendahara Barang'],
            },
            {
                title: 'Box SKPD',
                icon: Archive,
                availability: 'planned',
                roles: ['Bendahara Barang'],
            },
            {
                title: 'Distribusi / Alokasi',
                icon: ArrowLeftRight,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Petugas Loket'],
            },
            {
                title: 'Buku Kendali',
                icon: BookOpenCheck,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Petugas Loket'],
            },
        ],
    },
    {
        title: 'Verifikasi',
        items: [
            {
                title: 'Verifikasi Tahap 1',
                icon: BadgeCheck,
                availability: 'planned',
                roles: ['Petugas Penetapan'],
            },
            {
                title: 'Approval Tahap 1',
                icon: CircleCheckBig,
                availability: 'planned',
                roles: ['Kasie Penetapan'],
            },
            {
                title: 'Verifikasi Tahap 2',
                icon: BadgeCheck,
                availability: 'planned',
                roles: ['Petugas Verifikasi'],
            },
            {
                title: 'Approval Tahap 2',
                icon: CircleCheckBig,
                availability: 'planned',
                roles: ['Kasie Verifikasi'],
            },
        ],
    },
    {
        title: 'Laporan',
        items: [
            {
                title: 'Pemakaian',
                icon: BarChart3,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Kepala UPTD'],
            },
            {
                title: 'Batal/Rusak',
                icon: CircleX,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Kepala UPTD'],
            },
            {
                title: 'Distribusi',
                icon: ArrowLeftRight,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Kepala UPTD'],
            },
            {
                title: 'Persediaan',
                icon: Package,
                availability: 'planned',
                roles: ['Bendahara Barang', 'Kepala UPTD'],
            },
        ],
    },
    {
        title: 'Administrasi',
        items: [
            {
                title: 'Pengguna',
                icon: Users,
                availability: 'planned',
                roles: ['Administrator'],
            },
            {
                title: 'Role',
                icon: Shield,
                availability: 'planned',
                roles: ['Administrator'],
            },
            {
                title: 'Loket',
                icon: MapPin,
                availability: 'planned',
                roles: ['Administrator'],
            },
            {
                title: 'Alasan Batal/Rusak',
                icon: CircleX,
                availability: 'planned',
                roles: ['Administrator'],
            },
            {
                title: 'Audit Log',
                icon: ScrollText,
                availability: 'planned',
                roles: ['Administrator'],
            },
        ],
    },
] as const;
