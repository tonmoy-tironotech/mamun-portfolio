<script setup>
import { Link } from '@inertiajs/vue3';
import { fileSize } from '../format';
import SiteLayout from '../layouts/SiteLayout.vue';

defineProps({ document: { type: Object, required: true } });
</script>

<template>
    <SiteLayout wide>
        <div class="post doc-detail">
            <Link :href="$url('/student-corner')" class="project-back">&larr; Back to Documents</Link>
            <h2>{{ document.title }}</h2>
            <div class="doc-short rich-text" v-html="document.short_description"></div>
            <div class="doc-long rich-text" v-html="document.long_description"></div>
            <a :href="$url(`/student-corner/documents/${document.id}/download`)" class="doc-download">
                Download {{ document.file_name }} ({{ fileSize(document.file_size) }})
            </a>
        </div>
    </SiteLayout>
</template>
