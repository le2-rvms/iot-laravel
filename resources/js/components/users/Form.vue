<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const isEdit = computed(() => props.mode === 'edit');
const initialRoles = props.user?.roles?.map((role) => role.name).sort() ?? [];

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    roles: initialRoles,
});

function toggleRole(roleName, checked) {
    if (checked) {
        if (!form.roles.includes(roleName)) {
            form.roles = [...form.roles, roleName];
        }

        return;
    }

    form.roles = form.roles.filter((role) => role !== roleName);
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin-users.update', props.user), {
            onFinish: () => form.reset('password'),
        });

        return;
    }

    form.post(route('admin-users.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? '编辑管理员用户' : '创建管理员用户' }}</UiCardTitle>
                <UiCardDescription>
                    填写管理员用户资料，并为账号分配可用角色。
                </UiCardDescription>
            </UiCardHeader>
            <UiCardContent class="space-y-5">
                <div class="space-y-2">
                    <UiLabel for="user-name">姓名</UiLabel>
                    <UiInput id="user-name" v-model="form.name" :aria-invalid="Boolean(form.errors.name)" />
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="user-email">邮箱</UiLabel>
                    <UiInput
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        :aria-invalid="Boolean(form.errors.email)"
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="user-password">{{ isEdit ? '新密码' : '密码' }}</UiLabel>
                    <UiInput
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        :placeholder="isEdit ? '留空则不修改密码' : ''"
                        :aria-invalid="Boolean(form.errors.password)"
                    />
                    <p class="app-copy-muted text-sm">
                        {{ isEdit ? '若修改邮箱，系统会重置验证状态并重新发送验证邮件。' : '创建后将立即发送邮箱验证邮件。' }}
                    </p>
                    <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <UiLabel>管理员角色分配</UiLabel>
                        <p class="app-copy-muted text-sm">
                            当前管理员用户支持多角色，权限全部来源于角色，不直接赋权给用户。
                        </p>
                    </div>

                    <div v-if="availableRoles.length" class="grid gap-3 rounded-xl border border-app-subtle-border bg-app-subtle/28 p-4 md:grid-cols-2">
                        <label
                            v-for="role in availableRoles"
                            :key="role"
                            class="app-option-card flex items-center gap-3 rounded-xl border px-4 py-3"
                        >
                            <UiCheckbox
                                :model-value="form.roles.includes(role)"
                                @update:model-value="(checked) => toggleRole(role, checked)"
                            />
                            <span class="app-copy-strong text-sm font-medium">{{ role }}</span>
                        </label>
                    </div>

                    <UiAlert v-else>
                        <UiAlertTitle>暂无可分配角色</UiAlertTitle>
                        <UiAlertDescription>
                            请先在管理员角色模块中创建管理员角色，再为管理员用户分配访问权限。
                        </UiAlertDescription>
                    </UiAlert>

                    <p v-if="form.errors.roles" class="text-sm text-red-600">{{ form.errors.roles }}</p>
                    <p v-if="form.errors['roles.0']" class="text-sm text-red-600">{{ form.errors['roles.0'] }}</p>
                </div>
            </UiCardContent>
            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-lg sm:w-auto">
                    <Link :href="route('admin-users.index')">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-lg sm:min-w-28 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存修改' : '创建管理员用户' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
