<script setup>
defineProps({
    publication: { type: Object, required: true },
});
</script>

<template>
    <p v-if="publication.has_pdf || publication.link_url" class="pub-links">
        <a v-if="publication.has_pdf" :href="$url(`/publications/${publication.id}/pdf`)" target="_blank" rel="noopener" title="Open PDF" aria-label="Open PDF">
            <svg viewBox="0 0 32 32" width="40" height="40" aria-hidden="true">
                <circle cx="16" cy="16" r="15" fill="#D32F2F" />
                <path d="M9 5h10l5.5 5.5V26a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" fill="#FFFFFF" />
                <path d="M19 5v4.5a1 1 0 0 0 1 1h4.5z" fill="#F0B4B4" />
                <path d="M16.25 11.5v6M13.25 14.75l3 3 3-3" fill="none" stroke="#D32F2F" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                <text x="16.25" y="24.2" font-size="7" font-weight="700" text-anchor="middle" fill="#D32F2F" font-family="Arial, sans-serif">PDF</text>
            </svg>
        </a>
        <a v-if="publication.link_url" :href="publication.link_url" target="_blank" rel="noopener noreferrer" title="Open link" aria-label="Open link">
            <svg viewBox="0 0 32 32" width="40" height="40" aria-hidden="true">
                <circle cx="16" cy="16" r="15" fill="#0B5394" />
                <g transform="translate(4 4)" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </g>
            </svg>
        </a>
    </p>
</template>
