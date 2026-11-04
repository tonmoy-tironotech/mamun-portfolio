<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ pagination: { type: Object, required: true } });

// Laravel's list starts with "previous" and ends with "next"
const pageLinks = computed(() => props.pagination.links.slice(1, -1));
</script>

<template>
    <nav v-if="pagination.last_page > 1" class="pager" aria-label="Pages">
        <Link v-if="pagination.prev_page_url" :href="pagination.prev_page_url" aria-label="Previous page" preserve-scroll>&lsaquo;</Link>
        <template v-for="link in pageLinks" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                :class="{ active: link.active }"
                :aria-current="link.active ? 'page' : undefined"
                preserve-scroll
            >{{ link.label }}</Link>
            <span v-else class="muted">{{ link.label }}</span>
        </template>
        <Link v-if="pagination.next_page_url" :href="pagination.next_page_url" aria-label="Next page" preserve-scroll>&rsaquo;</Link>
    </nav>
</template>
