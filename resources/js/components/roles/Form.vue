<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    role: {
        type: Object,
        default: null,
    },
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const isEdit = computed(() => props.mode === 'edit');
const isProtected = computed(() => props.role?.is_protected ?? false);
const initialPermissions = props.role?.permissions?.map((permission) => permission.name).sort() ?? [];

const form = useForm({
    name: props.role?.name ?? '',
    permissions: initialPermissions,
});

function togglePermission(permissionName, checked) {
    if (checked) {
        if (!form.permissions.includes(permissionName)) {
            form.permissions = [...form.permissions, permissionName];
        }

        return;
    }

    form.permissions = form.permissions.filter((permission) => permission !== permissionName);
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin-roles.update', props.role));

        return;
    }

    form.post(route('admin-roles.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? '编辑管理员角色' : '创建管理员角色' }}</UiCardTitle>
                <UiCardDescription>
                    为不同岗位配置可访问的功能范围。
                </UiCardDescription>
            </UiCardHeader>

            <UiCardContent class="space-y-6">
                <div class="space-y-2">
                    <UiLabel for="role-name">管理员角色名称</UiLabel>
                    <UiInput
                        id="role-name"
                        v-model="form.name"
                        :disabled="isProtected"
                        :aria-invalid="Boolean(form.errors.name)"
                        placeholder="例如：运营管理员"
                    />
                    <p v-if="isProtected" class="app-copy-muted text-sm">
                        系统管理员角色名称固定，避免影响现有权限分配。
                    </p>
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <UiLabel>权限配置</UiLabel>
                        <p class="app-copy-muted text-sm">
                            可按模块勾选允许查看和维护的功能。
                        </p>
                    </div>

                    <RolesPermissionMatrix
                        :groups="permissionGroups"
                        :selected-permissions="form.permissions"
                        :disabled="isProtected"
                        @toggle="togglePermission"
                    />

                    <p v-if="form.errors.permissions" class="text-sm text-red-600">{{ form.errors.permissions }}</p>
                    <p v-if="form.errors['permissions.0']" class="text-sm text-red-600">{{ form.errors['permissions.0'] }}</p>
                </div>
            </UiCardContent>

            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link :href="route('admin-roles.index')">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-28 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存管理员角色' : '创建管理员角色' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
