<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    account: {
        type: Object,
        required: true,
    },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    user_name: props.account.user_name ?? '',
    password: '',
    clientid: props.account.clientid ?? '',
    product_key: props.account.product_key ?? '',
    device_name: props.account.device_name ?? '',
    certificate: props.account.certificate ?? '',
    // 编辑页传入的是模型序列化结果，这里先统一归一成布尔值，避免勾选框受 0/1 影响。
    is_superuser: Boolean(props.account.is_superuser),
    enabled: Boolean(props.account.enabled ?? true),
});

function submit() {
    if (isEdit.value) {
        form.put(`/mqtt-accounts/${props.account.act_id}`, {
            // 提交后只清理密码字段，其他输入保持原状，方便用户继续修正或重复保存。
            onFinish: () => form.reset('password'),
        });

        return;
    }

    form.post('/mqtt-accounts', {
        // 创建失败时保留其余输入，只清理密码，避免回填明文密码到页面。
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? '编辑MQTT账号' : '创建MQTT账号' }}</UiCardTitle>
                <UiCardDescription>
                    维护连接账号、设备标识和启用状态，供 EMQX 鉴权使用。
                </UiCardDescription>
            </UiCardHeader>
            <UiCardContent class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <UiLabel for="mqtt-user-name">账号名</UiLabel>
                        <UiInput id="mqtt-user-name" v-model="form.user_name" :aria-invalid="Boolean(form.errors.user_name)" />
                        <p v-if="form.errors.user_name" class="text-sm text-red-600">{{ form.errors.user_name }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="mqtt-password">{{ isEdit ? '新密码' : '密码' }}</UiLabel>
                        <UiInput
                            id="mqtt-password"
                            v-model="form.password"
                            type="password"
                            :placeholder="isEdit ? '留空则不修改密码' : ''"
                            :aria-invalid="Boolean(form.errors.password)"
                        />
                        <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="mqtt-clientid">客户端标识</UiLabel>
                        <UiInput id="mqtt-clientid" v-model="form.clientid" :aria-invalid="Boolean(form.errors.clientid)" />
                        <p v-if="form.errors.clientid" class="text-sm text-red-600">{{ form.errors.clientid }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="mqtt-product-key">产品标识</UiLabel>
                        <UiInput id="mqtt-product-key" v-model="form.product_key" :aria-invalid="Boolean(form.errors.product_key)" />
                        <p v-if="form.errors.product_key" class="text-sm text-red-600">{{ form.errors.product_key }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="mqtt-device-name">设备名称</UiLabel>
                        <UiInput id="mqtt-device-name" v-model="form.device_name" :aria-invalid="Boolean(form.errors.device_name)" />
                        <p v-if="form.errors.device_name" class="text-sm text-red-600">{{ form.errors.device_name }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <UiLabel for="mqtt-certificate">证书内容</UiLabel>
                    <textarea
                        id="mqtt-certificate"
                        v-model="form.certificate"
                        :aria-invalid="Boolean(form.errors.certificate)"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                    />
                    <p class="app-copy-muted text-sm">如未启用证书校验，可留空。</p>
                    <p v-if="form.errors.certificate" class="text-sm text-red-600">{{ form.errors.certificate }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-3 rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <UiCheckbox v-model="form.is_superuser" @update:model-value="form.clearErrors('is_superuser')" />
                            <div class="space-y-1">
                                <UiLabel>超级用户</UiLabel>
                                <p class="app-copy-muted text-sm">启用后，该账号可在 EMQX 中使用超级用户权限。</p>
                            </div>
                        </div>
                        <p v-if="form.errors.is_superuser" class="text-sm text-red-600">{{ form.errors.is_superuser }}</p>
                    </div>

                    <div class="space-y-3 rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <UiCheckbox v-model="form.enabled" @update:model-value="form.clearErrors('enabled')" />
                            <div class="space-y-1">
                                <UiLabel>启用账号</UiLabel>
                                <p class="app-copy-muted text-sm">停用后，该账号将无法通过 EMQX 密码鉴权。</p>
                            </div>
                        </div>
                        <p v-if="form.errors.enabled" class="text-sm text-red-600">{{ form.errors.enabled }}</p>
                    </div>
                </div>
            </UiCardContent>
            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link href="/mqtt-accounts">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-32 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存修改' : '创建账号' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
