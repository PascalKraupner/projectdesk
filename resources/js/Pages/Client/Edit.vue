<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
    AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    client: Object,
    currencies: Array,
});

const form = useForm({
    name: props.client.name,
    email: props.client.email ?? '',
    hourly_rate: props.client.hourly_rate != null ? String(props.client.hourly_rate) : '',
    currency: props.client.currency ?? props.currencies[0]?.value ?? 'EUR',
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            hourly_rate: data.hourly_rate === '' ? null : data.hourly_rate,
        }))
        .patch(route('clients.update', props.client.id));
};

const destroy = () => {
    router.delete(route('clients.destroy', props.client.id));
};
</script>

<template>
    <Head title="Edit Client" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Edit Client
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8 space-y-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-2">
                            <Label for="hourly_rate">Hourly rate</Label>
                            <Input
                                id="hourly_rate"
                                v-model="form.hourly_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                            <InputError :message="form.errors.hourly_rate" />
                        </div>
                        <div class="space-y-2">
                            <Label for="currency">Currency</Label>
                            <Select v-model="form.currency">
                                <SelectTrigger id="currency">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in currencies"
                                        :key="c.value"
                                        :value="c.value"
                                    >
                                        {{ c.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.currency" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="form.processing">
                            Save
                        </Button>
                        <Link
                            :href="route('clients.show', client.id)"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>

                <div class="border-t pt-6">
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button variant="destructive" type="button">
                                Delete client
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Delete {{ client.name }}?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    This will permanently delete the client and all of their projects. This action cannot be undone.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction @click="destroy">Delete</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
