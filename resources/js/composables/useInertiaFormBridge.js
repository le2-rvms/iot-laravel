import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useInertiaFormBridge(formContext, options = {}) {
    const processing = ref(false);
    const wasSuccessful = ref(false);

    function normalizeErrors(errors = {}) {
        return Object.fromEntries(
            Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
        );
    }

    function setServerErrors(errors = {}) {
        formContext.setErrors(normalizeErrors(errors));
    }

    function submitWithInertia(config = {}) {
        const {
            method = 'post',
            url,
            data,
            onBefore,
            onSuccess,
            onError,
            onFinish,
            ...rest
        } = config;

        processing.value = true;
        wasSuccessful.value = false;
        formContext.setErrors({});
        onBefore?.();

        router[method](url, data, {
            preserveScroll: true,
            ...rest,
            onError: (errors) => {
                setServerErrors(errors);
                onError?.(errors);
            },
            onSuccess: (page) => {
                wasSuccessful.value = true;
                onSuccess?.(page);
            },
            onFinish: (...args) => {
                processing.value = false;
                onFinish?.(...args);
            },
        });
    }

    return {
        processing: computed(() => processing.value),
        wasSuccessful: computed(() => wasSuccessful.value),
        setServerErrors,
        submitWithInertia,
    };
}
