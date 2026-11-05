<script setup>
defineProps({
    files: { type: Array, default: () => [] },
});
</script>

<template>
    <p v-if="files.length" class="pub-files">
        <template v-for="file in files" :key="file.id">
            <a :href="$url(`/publications/files/${file.id}`)" :title="file.file_name" target="_blank" rel="noopener">[{{ file.label }}]</a>{{ ' ' }}
        </template>
    </p>
</template>
