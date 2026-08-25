<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/admin/login';
</script>

<template>
    <Head title="Вход в админку" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6"
    >
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-semibold">Al Jar Coffee</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Панель управления
                </p>
            </div>

            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-2">
                    <Label for="email">Электронная почта</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Пароль</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Button type="submit" class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Войти
                </Button>
            </Form>

            <p class="mt-6 text-center text-xs text-muted-foreground">
                Доступ выдаёт администратор. Регистрации здесь нет.
            </p>
        </div>
    </div>
</template>
