<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import InvoiceCreateDialog from '@/Components/InvoiceCreateDialog.vue';
import { Plus } from 'lucide-vue-next';
import { formatMoney } from '@/lib/money';
import { statusClass } from '@/lib/invoiceStatus';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filter: { type: String, default: null },
    clients: { type: Array, default: () => [] },
});

const page = usePage();
const warning = computed(() => page.props.flash?.warning ?? null);

const createDialogOpen = ref(false);

const setFilter = (status) => {
    router.get(route('invoices.index'), status ? { status } : {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const formatDate = (iso) => new Date(iso).toLocaleDateString();
</script>

<template>
    <Head title="Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-foreground">Invoices</h2>
                <Button variant="outline" size="sm" @click="createDialogOpen = true">
                    <Plus class="mr-1 h-4 w-4" />
                    New invoice
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <p v-if="warning" class="rounded-md border border-yellow-500/30 bg-yellow-500/10 px-4 py-3 text-sm text-yellow-700 dark:text-yellow-400">
                    {{ warning }}
                </p>

                <div class="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="!filter ? 'bg-accent' : ''"
                        @click="setFilter(null)"
                    >
                        All
                    </Button>
                    <Button
                        v-for="status in statuses"
                        :key="status"
                        variant="ghost"
                        size="sm"
                        class="capitalize"
                        :class="filter === status ? 'bg-accent' : ''"
                        @click="setFilter(status)"
                    >
                        {{ status }}
                    </Button>
                </div>

                <Card>
                    <CardContent class="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Number</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Issued</TableHead>
                                    <TableHead>Due</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="invoice in invoices" :key="invoice.id">
                                    <TableCell class="font-mono font-medium">
                                        <Link
                                            :href="route('invoices.show', invoice.id)"
                                            class="text-foreground hover:underline"
                                        >
                                            {{ invoice.number }}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{{ invoice.client_name }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(invoice.issue_date) }}
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(invoice.due_date) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="statusClass(invoice.status)" class="capitalize">
                                            {{ invoice.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right font-medium tabular-nums">
                                        {{ formatMoney(invoice.total_amount, invoice.currency) }}
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!invoices.length">
                                    <TableCell colspan="6" class="text-center text-muted-foreground">
                                        No invoices yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <InvoiceCreateDialog v-model:open="createDialogOpen" :clients="clients" />
    </AuthenticatedLayout>
</template>
