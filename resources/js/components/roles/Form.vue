<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

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

const form = useForm({
    name: props.role?.name ?? '',
    permissions: props.role?.permissions ?? [],
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
        form.put(`/roles/${props.role.id}`);

        return;
    }

    form.post('/roles');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="rounded-[1.75rem] border-slate-200 shadow-sm">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? '编辑角色' : '创建角色' }}</UiCardTitle>
                <UiCardDescription>
                    角色是权限集合。首版统一采用 `module.read` 与 `module.write` 两层权限模型。
                </UiCardDescription>
            </UiCardHeader>

            <UiCardContent class="space-y-6">
                <div class="space-y-2">
                    <UiLabel for="role-name">角色名称</UiLabel>
                    <UiInput
                        id="role-name"
                        v-model="form.name"
                        :disabled="isProtected"
                        :aria-invalid="Boolean(form.errors.name)"
                        placeholder="例如：运营管理员"
                    />
                    <p v-if="isProtected" class="text-sm text-slate-500">
                        Super Admin 角色名称固定，避免破坏全局放行逻辑。
                    </p>
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <UiLabel>权限配置</UiLabel>
                        <p class="text-sm text-slate-500">
                            删除、创建、编辑等修改型操作统一归入 `write`。
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

            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-slate-200 sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link href="/roles">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:w-auto" :disabled="form.processing">
                    {{ form.processing ? '保存中...' : isEdit ? '保存角色' : '创建角色' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
