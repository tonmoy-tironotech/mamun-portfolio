<script setup>
defineProps({
    publication: { type: Object, required: true },
});
</script>

<template>
    <p v-if="publication.has_pdf || publication.link_url" class="pub-links">
        <a v-if="publication.has_pdf" :href="$url(`/publications/${publication.id}/pdf`)" target="_blank" rel="noopener" title="Open PDF" aria-label="Open PDF">
            <img class="pub-pdf-icon" :src="$url('/images/pdf_icon_small.png')" width="37" height="38" alt="" />
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
