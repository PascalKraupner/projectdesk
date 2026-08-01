import { InvoiceStatus } from '@/Enums/InvoiceStatus';

export const statusClass = (status) => ({
    [InvoiceStatus.Draft]: 'border-zinc-500/30 bg-zinc-500/10 text-zinc-600 dark:text-zinc-400',
    [InvoiceStatus.Issued]: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    [InvoiceStatus.Paid]: 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
    [InvoiceStatus.Cancelled]: 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400',
}[status] || '');
