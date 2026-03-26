<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/routes';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    product: {
        type: Object,
        required: true,
    },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    product_key: props.product.product_key ?? '',
    product_name: props.product.product_name ?? '',
    description: props.product.description ?? '',
    manufacturer: props.product.manufacturer ?? '',
    protocol: props.product.protocol ?? '',
    category: props.product.category ?? '',
});

function submit() {
    if (isEdit.value) {
        form.put(route('device-products.update', props.product));

        return;
    }

    form.post(route('device-products.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? '编辑设备产品' : '创建设备产品' }}</UiCardTitle>
                <UiCardDescription>
                    维护产品标识、名称以及协议分类，供设备和分组建立稳定关联。
                </UiCardDescription>
            </UiCardHeader>

            <UiCardContent class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <UiLabel for="device-product-key">产品标识</UiLabel>
                        <UiInput
                            id="device-product-key"
                            v-model="form.product_key"
                            :disabled="isEdit"
                            :aria-invalid="Boolean(form.errors.product_key)"
                        />
                        <p v-if="isEdit" class="app-copy-muted text-sm">
                            产品标识创建后会被设备和分组引用，因此不可修改。
                        </p>
                        <p v-if="form.errors.product_key" class="text-sm text-red-600">{{ form.errors.product_key }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="device-product-name">产品名称</UiLabel>
                        <UiInput
                            id="device-product-name"
                            v-model="form.product_name"
                            :aria-invalid="Boolean(form.errors.product_name)"
                        />
                        <p v-if="form.errors.product_name" class="text-sm text-red-600">{{ form.errors.product_name }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="device-product-manufacturer">厂商</UiLabel>
                        <UiInput
                            id="device-product-manufacturer"
                            v-model="form.manufacturer"
                            :aria-invalid="Boolean(form.errors.manufacturer)"
                        />
                        <p v-if="form.errors.manufacturer" class="text-sm text-red-600">{{ form.errors.manufacturer }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="device-product-protocol">协议</UiLabel>
                        <UiInput
                            id="device-product-protocol"
                            v-model="form.protocol"
                            :aria-invalid="Boolean(form.errors.protocol)"
                        />
                        <p v-if="form.errors.protocol" class="text-sm text-red-600">{{ form.errors.protocol }}</p>
                    </div>

                    <div class="space-y-2">
                        <UiLabel for="device-product-category">分类</UiLabel>
                        <UiInput
                            id="device-product-category"
                            v-model="form.category"
                            :aria-invalid="Boolean(form.errors.category)"
                        />
                        <p v-if="form.errors.category" class="text-sm text-red-600">{{ form.errors.category }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <UiLabel for="device-product-description">描述</UiLabel>
                    <textarea
                        id="device-product-description"
                        v-model="form.description"
                        :aria-invalid="Boolean(form.errors.description)"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                    />
                    <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                </div>
            </UiCardContent>

            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link :href="route('device-products.index')">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-32 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存修改' : '创建设备产品' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
