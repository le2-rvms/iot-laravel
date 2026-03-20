<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    resource: {
        type: Object,
        required: true,
    },
    config: {
        type: Object,
        required: true,
    },
});

const isEdit = computed(() => props.mode === 'edit');
const submitMethod = isEdit.value ? 'put' : 'post';
const submitHref = isEdit.value ? `${props.resource.index_href}/${props.config.id}` : props.resource.index_href;

const form = useForm({
    key: props.config.key ?? '',
    value: props.config.value ?? '',
    is_masked: Boolean(props.config.is_masked),
    remark: props.config.remark ?? '',
}).withPrecognition(submitMethod, submitHref);

form.setValidationTimeout(350);

function validateField(field) {
    form.validate(field);
}

function submit() {
    if (isEdit.value) {
        form.put(submitHref);

        return;
    }

    form.post(submitHref);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <UiCard class="app-panel-card rounded-[1.5rem] shadow-sm">
            <UiCardHeader>
                <UiCardTitle>{{ isEdit ? `编辑${resource.title}` : `创建${resource.title}` }}</UiCardTitle>
                <UiCardDescription>
                    使用统一的配置项结构维护键值、打码显示与后台备注说明。
                </UiCardDescription>
            </UiCardHeader>
            <UiCardContent class="space-y-5">
                <div class="space-y-2">
                    <UiLabel for="config-key">配置键</UiLabel>
                    <UiInput
                        id="config-key"
                        v-model="form.key"
                        :aria-invalid="Boolean(form.errors.key)"
                        @input="form.clearErrors('key')"
                        @blur="validateField('key')"
                    />
                    <p v-if="form.errors.key" class="text-sm text-red-600">{{ form.errors.key }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="config-value">配置值</UiLabel>
                    <textarea
                        id="config-value"
                        v-model="form.value"
                        :aria-invalid="Boolean(form.errors.value)"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                        @input="form.clearErrors('value')"
                        @blur="validateField('value')"
                    />
                    <p v-if="form.errors.value" class="text-sm text-red-600">{{ form.errors.value }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel>配置分类</UiLabel>
                    <div class="rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-3 text-sm font-medium text-app-panel-foreground">
                        {{ config.category_label }}
                    </div>
                </div>

                <div class="space-y-3 rounded-xl border border-app-subtle-border bg-app-subtle/28 px-4 py-4">
                    <div class="flex items-start gap-3">
                        <UiCheckbox v-model="form.is_masked" @update:model-value="form.clearErrors('is_masked')" />
                        <div class="space-y-1">
                            <UiLabel>是否打码</UiLabel>
                            <p class="app-copy-muted text-sm">开启后，列表页中的配置值统一显示为 `*****`。</p>
                        </div>
                    </div>
                    <p v-if="form.errors.is_masked" class="text-sm text-red-600">{{ form.errors.is_masked }}</p>
                </div>

                <div class="space-y-2">
                    <UiLabel for="config-remark">备注</UiLabel>
                    <textarea
                        id="config-remark"
                        v-model="form.remark"
                        :aria-invalid="Boolean(form.errors.remark)"
                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                        @input="form.clearErrors('remark')"
                        @blur="validateField('remark')"
                    />
                    <p v-if="form.errors.remark" class="text-sm text-red-600">{{ form.errors.remark }}</p>
                </div>
            </UiCardContent>
            <UiCardFooter class="flex flex-col-reverse gap-3 border-t border-app-panel-border sm:flex-row sm:justify-end">
                <UiButton as-child variant="outline" class="w-full rounded-xl sm:w-auto">
                    <Link :href="resource.index_href">返回列表</Link>
                </UiButton>
                <UiButton type="submit" class="w-full rounded-xl sm:min-w-28 sm:w-auto sm:justify-center" :disabled="form.processing">
                    {{ form.processing ? '保存中' : isEdit ? '保存修改' : '创建配置项' }}
                </UiButton>
            </UiCardFooter>
        </UiCard>
    </form>
</template>
