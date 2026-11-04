<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TablePager from '../../../components/TablePager.vue';
import { useSearch } from '../../../composables/useSearch';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    publications: { type: Object, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/admin/publications');

const destroy = (publication) => {
    if (confirm(`Delete "${publication.title}"?`)) {
        router.delete(url(`/admin/publications/${publication.id}`), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Publications - Admin" />
    <AdminLayout title="Publications">
        <div class="toolbar">
            <input v-model="term" type="search" class="search" placeholder="Search publications" aria-label="Search publications" />
            <Link :href="$url('/admin/publications/create')" class="btn">New publication</Link>
        </div>

        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Updated</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="publication in publications.data" :key="publication.id">
                        <td class="col-type">{{ publication.type }}</td>
                        <td class="col-title">{{ publication.title }}</td>
                        <td class="col-text">{{ publication.description }}</td>
                        <td class="col-date">{{ publication.updated }}</td>
                        <td class="col-actions">
                            <Link :href="$url(`/admin/publications/${publication.id}/edit`)" class="btn btn-secondary">Edit</Link>
                            <button type="button" class="btn btn-danger" @click="destroy(publication)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!publications.data.length">
                        <td colspan="5" class="empty">{{ search ? 'No publications match your search.' : 'No publications yet.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="muted">{{ publications.total }} {{ publications.total === 1 ? 'publication' : 'publications' }}</span>
            <TablePager :pagination="publications" />
        </div>
    </AdminLayout>
</template>
