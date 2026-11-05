<script setup>
defineProps({
    publication: { type: Object, required: true },
});
</script>

<template>
    <p v-if="publication.has_pdf || publication.link_url" class="pub-links">
        <a v-if="publication.has_pdf" :href="$url(`/publications/${publication.id}/pdf`)" target="_blank" rel="noopener" title="Open PDF" aria-label="Open PDF">
            <svg viewBox="0 0 32 32" width="32" height="32" aria-hidden="true">
                <circle cx="16" cy="16" r="15" fill="#D32F2F" />
                <path d="M10 6h9l5 5v15a1 1 0 0 1-1 1H10a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1z" fill="#FFFFFF" />
                <path d="M19 6v4a1 1 0 0 0 1 1h4z" fill="#F0B4B4" />
                <path d="M12 14.5h9M12 17.5h9" stroke="#D32F2F" stroke-width="1.2" stroke-linecap="round" />
                <text x="16.5" y="24.5" font-size="6.5" font-weight="700" text-anchor="middle" fill="#D32F2F" font-family="Arial, sans-serif">PDF</text>
            </svg>
        </a>
        <a v-if="publication.link_url" :href="publication.link_url" target="_blank" rel="noopener noreferrer" title="Open link" aria-label="Open link">
            <svg viewBox="0 0 32 32" width="32" height="32" aria-hidden="true">
                <circle cx="16" cy="16" r="15" fill="#0B5394" />
                <g transform="translate(4 4)" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </g>
            </svg>
        </a>
    </p>
</template>
