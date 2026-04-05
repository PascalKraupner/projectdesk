<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <Card>
            <CardHeader>
                <CardTitle>Log in</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="remember"
                            :checked="form.remember"
                            @update:checked="form.remember = $event"
                        />
                        <Label for="remember" class="text-sm text-muted-foreground">Remember me</Label>
                    </div>

                    <div class="flex items-center justify-between">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-muted-foreground underline hover:text-foreground"
                        >
                            Forgot your password?
                        </Link>

                        <Button type="submit" :disabled="form.processing">
                            Log in
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
