<template>
    <input
        ref="inputRef"
        type="text"
        @input="handleInput"
    />
</template>

<script setup>
import { useCurrencyInput } from 'vue-currency-input';
import { watch } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    options: Object,
});

const emit = defineEmits(['update:modelValue']);

// Use options if provided, otherwise default to a highly permissive number format
const { inputRef, setValue, numberValue } = useCurrencyInput(props.options || { 
    currency: 'USD', // use USD just to get 2-3 decimal support easily
    currencyDisplay: 'hidden', 
    hideCurrencySymbolOnFocus: true, 
    hideGroupingSeparatorOnFocus: false, 
    hideNegligibleDecimalDigitsOnFocus: false,
    autoDecimalDigits: false,
    useGrouping: true,
    precision: { min: 0, max: 3 }, // allow up to 3 decimals (for quantities)
    accountingSign: false
});

const handleInput = () => {
    // Emits the unformatted, raw number (e.g. 1000.5) to the parent v-model
    emit('update:modelValue', numberValue.value);
};

watch(
    () => props.modelValue,
    (value) => {
        setValue(value);
    }
);
</script>
