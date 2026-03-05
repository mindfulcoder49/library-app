<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm font-medium text-rose-600">
            {{ message }}
        </p>
    </div>
</template>
