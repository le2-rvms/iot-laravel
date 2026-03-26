<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('security-password.update'), {
        preserveScroll: true,
        onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardHeader>
                <UiCardTitle>修改密码</UiCardTitle>
                <UiCardDescription>
                    请先输入当前密码，再设置新的登录密码。
                </UiCardDescription>
            </UiCardHeader>
            <UiCardContent class="space-y-5">
                <div class="space-y-2">
                    <UiLabel for="current-password">当前密码</UiLabel>
                    <UiInput
                        id="current-password"
                        v-model="form.current_password"
                        type="password"
                        autocomplete="current-password"
                        :aria-invalid="Boolean(form.errors.current_password)"
                    />
                    <p v-if="form.errors.current_password" class="text-sm text-red-600">
                        {{ form.errors.current_password }}
                    </p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="new-password">新密码</UiLabel>
                    <UiInput
                        id="new-password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :aria-invalid="Boolean(form.errors.password)"
                    />
                    <p class="app-copy-muted text-sm">
                        请设置安全且便于记忆的新密码，并再次确认。
                    </p>
                    <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="password-confirmation">确认新密码</UiLabel>
                    <UiInput
                        id="password-confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :aria-invalid="Boolean(form.errors.password_confirmation)"
                    />
                    <p v-if="form.errors.password_confirmation" class="text-sm text-red-600">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>
            </UiCardContent>
            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link :href="route('dashboard')">返回仪表盘</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-28 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : '保存新密码' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
