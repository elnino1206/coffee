<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes/admin';
</script>

<template>
    <Head title="Панель управления" />

    <div class="min-h-svh bg-background p-6">
        <header class="mx-auto flex max-w-4xl items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">Панель управления</h1>
                <p class="text-sm text-muted-foreground">
                    {{ $page.props.auth.admin?.name }}
                </p>
            </div>

            <Form v-bind="logout.form()" v-slot="{ processing }">
                <Button type="submit" variant="outline" :disabled="processing">
                    Выйти
                </Button>
            </Form>
        </header>

        <main class="mx-auto mt-10 max-w-4xl">
            <p class="text-sm text-muted-foreground">
                Разделы админки появятся здесь — заказы, подписки, каталог,
                журнал и заявки опта.
            </p>
        </main>
    </div>
</template>
