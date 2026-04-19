import { ProjectStatus } from '@/Enums/ProjectStatus';

export const statusClass = (status) => ({
    [ProjectStatus.Active]: 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
    [ProjectStatus.Paused]: 'border-yellow-500/30 bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
    [ProjectStatus.Completed]: 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
}[status] || '');
