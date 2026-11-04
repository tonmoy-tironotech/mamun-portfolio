<script setup>
import { Link } from '@inertiajs/vue3';
import SiteLayout from '../layouts/SiteLayout.vue';

defineProps({
    project: { type: Object, required: true },
});
</script>

<template>
    <SiteLayout wide>
        <div class="post project-detail">
            <Link :href="$url('/projects')" class="project-back">&larr; Back to Projects</Link>
            <img :src="$url(project.image)" :alt="project.title" />
            <h2>
                <a v-if="project.url" :href="project.url">{{ project.title }}</a>
                <template v-else>{{ project.title }}</template>
            </h2>
            <div class="rich-text" v-html="project.body"></div>
        </div>
    </SiteLayout>
</template>
