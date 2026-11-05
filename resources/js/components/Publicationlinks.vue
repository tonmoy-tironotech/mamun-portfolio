<script setup>
defineProps({
    publication: { type: Object, required: true },
});
</script>

<template>
    <p v-if="publication.has_pdf || publication.link_url" class="pub-links">
        <a v-if="publication.has_pdf" :href="$url(`/publications/${publication.id}/pdf`)" target="_blank" rel="noopener" title="Open PDF" aria-label="Open PDF">
            <svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true">
                <path d="M6 3a1 1 0 0 1 1-1h13l7 7v20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1z" fill="#D32F2F" />
                <path d="M20 2v6a1 1 0 0 0 1 1h6z" fill="#FFFFFF" opacity="0.4" />
                <text x="16.5" y="24" font-size="8" font-weight="700" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif">PDF</text>
            </svg>
        </a>
        <a v-if="publication.link_url" :href="publication.link_url" target="_blank" rel="noopener noreferrer" title="Open link" aria-label="Open link">
            <svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true">
                <circle cx="16" cy="16" r="15" fill="#0B5394" />
                <g transform="translate(4 4)" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </g>
            </svg>
        </a>
    </p>
</template>
