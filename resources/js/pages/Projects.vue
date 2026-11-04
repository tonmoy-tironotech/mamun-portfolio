<script setup>
import { Link } from '@inertiajs/vue3';
import SiteLayout from '../layouts/SiteLayout.vue';

defineProps({
    projects: { type: Array, required: true },
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
});
</script>

<template>
    <SiteLayout wide>
        <div class="post">
            <h2 class="title"><a href="#"><b>Projects</b></a></h2>

            <p v-if="!projects.length">No projects yet.</p>

            <article v-for="project in projects" :key="project.slug" class="project-row">
                <Link :href="$url(`/projects/${project.slug}`)" class="project-image">
                    <img :src="$url(project.image)" :alt="project.title" />
                </Link>
                <div class="project-text">
                    <h3><Link :href="$url(`/projects/${project.slug}`)">{{ project.title }}</Link></h3>
                    <div class="project-summary rich-text" v-html="project.body"></div>
                    <Link :href="$url(`/projects/${project.slug}`)" class="project-more">View More &#8599;</Link>
                </div>
            </article>

            <nav v-if="lastPage > 1" class="pagination" aria-label="Projects pages">
                <Link v-if="currentPage > 1" :href="$url(`/projects?page=${currentPage - 1}`)" aria-label="Previous page">&lsaquo;</Link>
                <Link
                    v-for="number in lastPage"
                    :key="number"
                    :href="$url(`/projects?page=${number}`)"
                    :class="{ active: number === currentPage }"
                    :aria-current="number === currentPage ? 'page' : undefined"
                >{{ number }}</Link>
                <Link v-if="currentPage < lastPage" :href="$url(`/projects?page=${currentPage + 1}`)" aria-label="Next page">&rsaquo;</Link>
            </nav>
        </div>
    </SiteLayout>
</template>
