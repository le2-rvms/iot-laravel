import { reactive } from 'vue';

const defaults = () => ({
    open: false,
    title: '请确认操作',
    description: '',
    confirmLabel: '确认',
    cancelLabel: '取消',
    variant: 'default',
    onConfirm: null,
});

const state = reactive(defaults());

function reset() {
    Object.assign(state, defaults());
}

export function useConfirmDialog() {
    function open(options = {}) {
        Object.assign(state, defaults(), options, {
            open: true,
        });
    }

    function close() {
        reset();
    }

    function confirm() {
        const callback = state.onConfirm;

        reset();
        callback?.();
    }

    return {
        state,
        open,
        close,
        confirm,
    };
}
