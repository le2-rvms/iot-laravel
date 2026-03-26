<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    device: {
        type: Object,
        required: true,
    },
});

const isEdit = computed(() => props.mode === 'edit');

function normalizeDateTime(value) {
    if (!value) {
        return '';
    }

    const normalized = String(value).replace(' ', 'T');

    return normalized.length >= 16 ? normalized.slice(0, 16) : normalized;
}

function normalizeNullableNumber(value) {
    return value === null || value === undefined ? '' : String(value);
}

const form = useForm({
    terminal_id: props.device.terminal_id ?? '',
    dev_name: props.device.dev_name ?? '',
    company_id: props.device.company_id ?? '',
    manufacturer_id: props.device.manufacturer_id ?? '',
    product_key: props.device.product_key ?? '',
    sim_number: props.device.sim_number ?? '',
    device_status: props.device.device_status ?? '',
    review_status: props.device.review_status ?? '',
    auth_code_seed: props.device.auth_code_seed ?? '',
    auth_code_issued_at: normalizeDateTime(props.device.auth_code_issued_at),
    auth_code_expires_at: normalizeDateTime(props.device.auth_code_expires_at),
    auth_failures: normalizeNullableNumber(props.device.auth_failures),
    auth_block_until: normalizeDateTime(props.device.auth_block_until),
    city_relation_id: normalizeNullableNumber(props.device.city_relation_id),
});

function submit() {
    if (isEdit.value) {
        form.put(route('devices.update', props.device));

        return;
    }

    form.post(route('devices.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
        <UiCardHeader>
            <UiCardTitle>{{ isEdit ? '编辑设备' : '创建设备' }}</UiCardTitle>
            <UiCardDescription>
                    维护设备基础信息和业务状态，供后台统一检索与维护。
                </UiCardDescription>
            </UiCardHeader>

            <UiCardContent class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold">基础信息</h3>
                        <p class="app-copy-muted text-sm">终端标识、产品标识和基础归属信息。</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div class="space-y-2">
                            <UiLabel for="device-terminal-id">终端ID</UiLabel>
                            <UiInput
                                id="device-terminal-id"
                                v-model="form.terminal_id"
                                :disabled="isEdit"
                                :aria-invalid="Boolean(form.errors.terminal_id)"
                            />
                            <p v-if="isEdit" class="app-copy-muted text-sm">终端 ID 创建后不可修改。</p>
                            <p v-if="form.errors.terminal_id" class="text-sm text-red-600">{{ form.errors.terminal_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-dev-name">设备名称</UiLabel>
                            <UiInput id="device-dev-name" v-model="form.dev_name" :aria-invalid="Boolean(form.errors.dev_name)" />
                            <p v-if="form.errors.dev_name" class="text-sm text-red-600">{{ form.errors.dev_name }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-product-key">产品标识</UiLabel>
                            <UiInput id="device-product-key" v-model="form.product_key" :aria-invalid="Boolean(form.errors.product_key)" />
                            <p class="app-copy-muted text-sm">
                                {{ device.device_product?.product_name ? `当前关联产品：${device.device_product.product_name}` : '可填写已存在的产品标识，或留空。' }}
                            </p>
                            <p v-if="form.errors.product_key" class="text-sm text-red-600">{{ form.errors.product_key }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-company-id">公司ID</UiLabel>
                            <UiInput id="device-company-id" v-model="form.company_id" :aria-invalid="Boolean(form.errors.company_id)" />
                            <p v-if="form.errors.company_id" class="text-sm text-red-600">{{ form.errors.company_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-manufacturer-id">厂商ID</UiLabel>
                            <UiInput
                                id="device-manufacturer-id"
                                v-model="form.manufacturer_id"
                                :aria-invalid="Boolean(form.errors.manufacturer_id)"
                            />
                            <p v-if="form.errors.manufacturer_id" class="text-sm text-red-600">{{ form.errors.manufacturer_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-sim-number">SIM号</UiLabel>
                            <UiInput id="device-sim-number" v-model="form.sim_number" :aria-invalid="Boolean(form.errors.sim_number)" />
                            <p v-if="form.errors.sim_number" class="text-sm text-red-600">{{ form.errors.sim_number }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-city-relation-id">城市关联ID</UiLabel>
                            <UiInput
                                id="device-city-relation-id"
                                v-model="form.city_relation_id"
                                type="number"
                                :aria-invalid="Boolean(form.errors.city_relation_id)"
                            />
                            <p v-if="form.errors.city_relation_id" class="text-sm text-red-600">{{ form.errors.city_relation_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold">状态信息</h3>
                        <p class="app-copy-muted text-sm">用于维护设备状态和审核状态。</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div class="space-y-2">
                            <UiLabel for="device-status">设备状态</UiLabel>
                            <UiInput id="device-status" v-model="form.device_status" :aria-invalid="Boolean(form.errors.device_status)" />
                            <p v-if="form.errors.device_status" class="text-sm text-red-600">{{ form.errors.device_status }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-review-status">审核状态</UiLabel>
                            <UiInput id="device-review-status" v-model="form.review_status" :aria-invalid="Boolean(form.errors.review_status)" />
                            <p v-if="form.errors.review_status" class="text-sm text-red-600">{{ form.errors.review_status }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold">鉴权信息</h3>
                        <p class="app-copy-muted text-sm">仅在确需人工维护时填写，敏感值不会出现在导出和审计明文中。</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div class="space-y-2 md:col-span-2 xl:col-span-3">
                            <UiLabel for="device-auth-code-seed">鉴权种子</UiLabel>
                            <UiInput
                                id="device-auth-code-seed"
                                v-model="form.auth_code_seed"
                                :aria-invalid="Boolean(form.errors.auth_code_seed)"
                            />
                            <p v-if="form.errors.auth_code_seed" class="text-sm text-red-600">{{ form.errors.auth_code_seed }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-auth-issued-at">鉴权码签发时间</UiLabel>
                            <UiInput
                                id="device-auth-issued-at"
                                v-model="form.auth_code_issued_at"
                                type="datetime-local"
                                :aria-invalid="Boolean(form.errors.auth_code_issued_at)"
                            />
                            <p v-if="form.errors.auth_code_issued_at" class="text-sm text-red-600">{{ form.errors.auth_code_issued_at }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-auth-expires-at">鉴权码过期时间</UiLabel>
                            <UiInput
                                id="device-auth-expires-at"
                                v-model="form.auth_code_expires_at"
                                type="datetime-local"
                                :aria-invalid="Boolean(form.errors.auth_code_expires_at)"
                            />
                            <p v-if="form.errors.auth_code_expires_at" class="text-sm text-red-600">{{ form.errors.auth_code_expires_at }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-auth-failures">鉴权失败次数</UiLabel>
                            <UiInput
                                id="device-auth-failures"
                                v-model="form.auth_failures"
                                type="number"
                                min="0"
                                :aria-invalid="Boolean(form.errors.auth_failures)"
                            />
                            <p v-if="form.errors.auth_failures" class="text-sm text-red-600">{{ form.errors.auth_failures }}</p>
                        </div>

                        <div class="space-y-2">
                            <UiLabel for="device-auth-block-until">鉴权封禁截止时间</UiLabel>
                            <UiInput
                                id="device-auth-block-until"
                                v-model="form.auth_block_until"
                                type="datetime-local"
                                :aria-invalid="Boolean(form.errors.auth_block_until)"
                            />
                            <p v-if="form.errors.auth_block_until" class="text-sm text-red-600">{{ form.errors.auth_block_until }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-4">
                        <div class="space-y-1">
                            <div class="text-sm font-semibold">系统字段</div>
                            <p class="app-copy-muted text-sm">内部标识与创建时间仅展示，不开放编辑。</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-4 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="app-copy-muted">设备ID</span>
                            <span class="font-medium">{{ device.dev_id ?? '-' }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="app-copy-muted">创建时间</span>
                            <span class="font-medium">{{ device.created_at?.slice(0, 19).replace('T', ' ') || '-' }}</span>
                        </div>
                    </div>
                </div>
            </UiCardContent>

            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link :href="route('devices.index')">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-32 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存修改' : '创建设备' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
