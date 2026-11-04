<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TablePager from '../../../components/TablePager.vue';
import { useSearch } from '../../../composables/useSearch';
import { fileSize } from '../../../format';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    documents: { type: Object, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/admin/documents');

const destroy = (document) => {
    if (confirm(`Delete "${document.title}"?`)) {
        router.delete(url(`/admin/documents/${document.id}`), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Documents - Admin" />
    <AdminLayout title="Documents">
        <div class="toolbar">
            <input v-model="term" type="search" class="search" placeholder="Search documents" aria-label="Search documents" />
            <Link :href="$url('/admin/documents/create')" class="btn">New document</Link>
        </div>

        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Short description</th>
                        <th>File</th>
                        <th>Updated</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="document in documents.data" :key="document.id">
                        <td class="col-title">
                            <a :href="$url(`/student-corner/documents/${document.id}`)" target="_blank" rel="noopener">{{ document.title }}</a>
                        </td>
                        <td class="col-text">{{ document.short_description }}</td>
                        <td class="col-file">
                            <a :href="$url(`/student-corner/documents/${document.id}/download`)">{{ document.file_name }}</a>
                            <div class="muted">{{ fileSize(document.file_size) }}</div>
                        </td>
                        <td class="col-date">{{ document.updated }}</td>
                        <td class="col-actions">
                            <Link :href="$url(`/admin/documents/${document.id}/edit`)" class="btn btn-secondary">Edit</Link>
                            <button type="button" class="btn btn-danger" @click="destroy(document)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!documents.data.length">
                        <td colspan="5" class="empty">{{ search ? 'No documents match your search.' : 'No documents yet.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="muted">{{ documents.total }} {{ documents.total === 1 ? 'document' : 'documents' }}</span>
            <TablePager :pagination="documents" />
        </div>
    </AdminLayout>
</template>
