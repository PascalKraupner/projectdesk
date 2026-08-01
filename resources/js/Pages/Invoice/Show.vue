<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Separator } from '@/Components/ui/separator';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Download, Plus, Trash2 } from 'lucide-vue-next';
import { formatMoney } from '@/lib/money';
import { statusClass } from '@/lib/invoiceStatus';
import { InvoiceStatus } from '@/Enums/InvoiceStatus';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    invoice: { type: Object, required: true },
});

const page = usePage();
const warning = computed(() => page.props.flash?.warning ?? null);

const editable = computed(() => props.invoice.editable);
const isDraft = computed(() => props.invoice.status === InvoiceStatus.Draft);
const isIssued = computed(() => props.invoice.status === InvoiceStatus.Issued);
const isCancelled = computed(() => props.invoice.status === InvoiceStatus.Cancelled);

const currency = computed(() => props.invoice.currency);

const recipientLines = computed(() => {
    const r = props.invoice.recipient;
    return [
        r.name,
        r.contact_person,
        r.street,
        [r.postal_code, r.city].filter(Boolean).join(' '),
        r.country,
    ].filter(Boolean);
});

const editing = ref({ id: null, field: null });
const draft = ref('');

const startEdit = (item, field) => {
    if (!editable.value) return;
    editing.value = { id: item.id, field };
    draft.value = String(item[field] ?? '');
};

const cancelEdit = () => {
    editing.value = { id: null, field: null };
    draft.value = '';
};

const saveEdit = (item) => {
    const field = editing.value.field;
    if (editing.value.id !== item.id || !field) return;

    const next = field === 'description' ? draft.value : Number(draft.value);
    cancelEdit();

    if (next === item[field] || (field !== 'description' && Number.isNaN(next))) return;

    router.patch(route('invoice-items.update', item.id), { [field]: next }, {
        preserveScroll: true,
    });
};

const removeItem = (item) => {
    router.delete(route('invoice-items.destroy', item.id), { preserveScroll: true });
};

const newItem = useForm({ description: '', quantity: 1, unit: 'h', unit_price: 0 });
const addingItem = ref(false);

const addItem = () => {
    newItem.post(route('invoice-items.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            newItem.reset();
            addingItem.value = false;
        },
    });
};

const transition = (action) => {
    router.patch(route(`invoices.${action}`, props.invoice.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Invoice ${invoice.number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-mono text-xl font-semibold leading-tight text-foreground">
                        {{ invoice.number }}
                    </h2>
                    <Badge variant="outline" :class="statusClass(invoice.status)" class="capitalize">
                        {{ invoice.status }}
                    </Badge>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <a :href="route('invoices.pdf', invoice.id)">
                            <Download class="mr-1 h-4 w-4" />
                            PDF
                        </a>
                    </Button>
                    <Button v-if="isDraft" size="sm" @click="transition('issue')">Issue</Button>
                    <Button v-if="isIssued" size="sm" @click="transition('pay')">Mark paid</Button>
                    <AlertDialog v-if="!isCancelled">
                        <AlertDialogTrigger as-child>
                            <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive dark:text-red-400 dark:hover:text-red-300">
                                Cancel invoice
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Cancel {{ invoice.number }}?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Invoices are cancelled rather than deleted, so the number
                                    sequence stays gapless. This cannot be undone.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Keep it</AlertDialogCancel>
                                <AlertDialogAction @click="transition('cancel')">
                                    Cancel invoice
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <p v-if="warning" class="rounded-md border border-yellow-500/30 bg-yellow-500/10 px-4 py-3 text-sm text-yellow-700 dark:text-yellow-400">
                    {{ warning }}
                </p>

                <div class="grid gap-6 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-muted-foreground">Recipient</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-for="line in recipientLines" :key="line" class="text-sm text-foreground">
                                {{ line }}
                            </div>
                            <div v-if="invoice.recipient.vat_id" class="mt-2 text-xs text-muted-foreground">
                                VAT ID {{ invoice.recipient.vat_id }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium text-muted-foreground">Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Issue date</span>
                                <span class="text-foreground">{{ invoice.issue_date }}</span>
                            </div>
                            <Separator />
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Payment terms</span>
                                <span class="text-foreground">{{ invoice.payment_terms_days }} days</span>
                            </div>
                            <Separator />
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Due date</span>
                                <span class="text-foreground">{{ invoice.due_date }}</span>
                            </div>
                            <template v-if="invoice.period_start">
                                <Separator />
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Service period</span>
                                    <span class="text-foreground">
                                        {{ invoice.period_start }} to {{ invoice.period_end }}
                                    </span>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            Items
                            <span v-if="!editable" class="ml-2 font-normal">
                                (read only, invoice is {{ invoice.status }})
                            </span>
                        </CardTitle>
                        <Button
                            v-if="editable && !addingItem"
                            variant="outline"
                            size="sm"
                            @click="addingItem = true"
                        >
                            <Plus class="mr-1 h-4 w-4" />
                            Add item
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right">Quantity</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead class="text-right">Unit price</TableHead>
                                    <TableHead class="text-right">Amount</TableHead>
                                    <TableHead class="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in invoice.items" :key="item.id">
                                    <TableCell>
                                        <Input
                                            v-if="editing.id === item.id && editing.field === 'description'"
                                            v-model="draft"
                                            class="h-8"
                                            @keydown.enter.prevent="saveEdit(item)"
                                            @keydown.esc.prevent="cancelEdit"
                                            @blur="saveEdit(item)"
                                        />
                                        <button
                                            v-else
                                            type="button"
                                            class="w-full text-left text-foreground"
                                            :class="editable ? 'hover:underline' : 'cursor-default'"
                                            @click="startEdit(item, 'description')"
                                        >
                                            {{ item.description }}
                                        </button>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">
                                        <Input
                                            v-if="editing.id === item.id && editing.field === 'quantity'"
                                            v-model="draft"
                                            type="number"
                                            step="0.01"
                                            class="h-8 text-right"
                                            @keydown.enter.prevent="saveEdit(item)"
                                            @keydown.esc.prevent="cancelEdit"
                                            @blur="saveEdit(item)"
                                        />
                                        <button
                                            v-else
                                            type="button"
                                            class="w-full text-right"
                                            :class="editable ? 'hover:underline' : 'cursor-default'"
                                            @click="startEdit(item, 'quantity')"
                                        >
                                            {{ item.quantity.toFixed(2) }}
                                        </button>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ item.unit }}</TableCell>
                                    <TableCell class="text-right tabular-nums">
                                        {{ formatMoney(item.unit_price, currency) }}
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">
                                        <Input
                                            v-if="editing.id === item.id && editing.field === 'amount'"
                                            v-model="draft"
                                            type="number"
                                            step="0.01"
                                            class="h-8 text-right"
                                            @keydown.enter.prevent="saveEdit(item)"
                                            @keydown.esc.prevent="cancelEdit"
                                            @blur="saveEdit(item)"
                                        />
                                        <button
                                            v-else
                                            type="button"
                                            class="w-full text-right font-medium"
                                            :class="editable ? 'hover:underline' : 'cursor-default'"
                                            @click="startEdit(item, 'amount')"
                                        >
                                            {{ formatMoney(item.amount, currency) }}
                                        </button>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            v-if="editable"
                                            variant="ghost"
                                            size="icon-sm"
                                            @click="removeItem(item)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="addingItem">
                                    <TableCell>
                                        <Input v-model="newItem.description" class="h-8" placeholder="Description" />
                                    </TableCell>
                                    <TableCell>
                                        <Input v-model="newItem.quantity" type="number" step="0.01" class="h-8 text-right" />
                                    </TableCell>
                                    <TableCell>
                                        <Input v-model="newItem.unit" class="h-8 w-16" />
                                    </TableCell>
                                    <TableCell>
                                        <Input v-model="newItem.unit_price" type="number" step="0.01" class="h-8 text-right" />
                                    </TableCell>
                                    <TableCell colspan="2">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                size="sm"
                                                :disabled="!newItem.description || newItem.processing"
                                                @click="addItem"
                                            >
                                                Add
                                            </Button>
                                            <Button variant="ghost" size="sm" @click="addingItem = false">
                                                Cancel
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="!invoice.items.length && !addingItem">
                                    <TableCell colspan="6" class="text-center text-muted-foreground">
                                        No items.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div class="mt-4 flex justify-end border-t border-border pt-4">
                            <div class="flex items-center gap-6">
                                <span class="font-medium text-foreground">Total</span>
                                <span class="text-lg font-semibold tabular-nums text-foreground">
                                    {{ formatMoney(invoice.total_amount, currency) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
