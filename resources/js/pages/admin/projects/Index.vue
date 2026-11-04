<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TablePager from '../../../components/TablePager.vue';
import { useSearch } from '../../../composables/useSearch';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    projects: { type: Object, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/admin/projects');

const destroy = (project) => {
    if (confirm(`Delete "${project.title}"?`)) {
        router.delete(url(`/admin/projects/${project.id}`), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Projects - Admin" />
    <AdminLayout title="Projects">
        <div class="toolbar">
            <input v-model="term" type="search" class="search" placeholder="Search projects" aria-label="Search projects" />
            <Link :href="$url('/admin/projects/create')" class="btn">New project</Link>
        </div>

        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Link</th>
                        <th>Updated</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="project in projects.data" :key="project.id">
                        <td><img :src="$url(project.image)" alt="" /></td>
                        <td class="col-title">
                            <a :href="$url(`/projects/${project.slug}`)" target="_blank" rel="noopener">{{ project.title }}</a>
                        </td>
                        <td>
                            <a v-if="project.url" :href="project.url" target="_blank" rel="noopener noreferrer">Open</a>
                            <span v-else class="muted">-</span>
                        </td>
                        <td class="col-date">{{ project.updated }}</td>
                        <td class="col-actions">
                            <Link :href="$url(`/admin/projects/${project.id}/edit`)" class="btn btn-secondary">Edit</Link>
                            <button type="button" class="btn btn-danger" @click="destroy(project)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!projects.data.length">
                        <td colspan="5" class="empty">{{ search ? 'No projects match your search.' : 'No projects yet.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="muted">{{ projects.total }} {{ projects.total === 1 ? 'project' : 'projects' }}</span>
            <TablePager :pagination="projects" />
        </div>
    </AdminLayout>
</template>
