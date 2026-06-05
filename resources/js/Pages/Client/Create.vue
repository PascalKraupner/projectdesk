<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    currencies: Array,
});

const form = useForm({
    name: '',
    email: '',
    hourly_rate: '',
    currency: props.currencies[0]?.value ?? 'EUR',
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            hourly_rate: data.hourly_rate === '' ? null : data.hourly_rate,
        }))
        .post(route('clients.store'));
};
</script>

<template>
    <Head title="New Client" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                New Client
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
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
                            Create
                        </Button>
                        <Link
                            :href="route('clients.index')"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
