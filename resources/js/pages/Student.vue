<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import TablePager from '../components/TablePager.vue';
import { useSearch } from '../composables/useSearch';
import { fileSize } from '../format';
import SiteLayout from '../layouts/SiteLayout.vue';

const props = defineProps({
    documents: { type: Object, required: true },
    search: { type: String, default: '' },
});

const page = usePage();
const term = useSearch(props.search, '/student-corner');
</script>

<template>
    <SiteLayout wide>
        <div class="post student">
            <div class="student-header">
                <h2>Welcome, {{ page.props.auth.user.name }}</h2>
                <div class="student-actions">
                    <a v-if="page.props.auth.user.is_admin" :href="$url('/admin')" class="student-admin">Admin Panel</a>
                    <Link :href="$url('/student-corner/profile')" class="student-logout">Profile</Link>
                    <Link :href="$url('/logout')" method="post" as="button" class="student-logout">Log out</Link>
                </div>
            </div>

            <div class="doc-toolbar">
                <h3>Documents</h3>
                <input v-model="term" type="search" placeholder="Search documents" aria-label="Search documents" />
            </div>

            <div class="doc-table">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>File</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="document in documents.data" :key="document.id">
                            <td class="doc-title">{{ document.title }}</td>
                            <td>{{ document.short_description }}</td>
                            <td class="doc-file">{{ fileSize(document.file_size) }}</td>
                            <td class="doc-more">
                                <Link :href="$url(`/student-corner/documents/${document.id}`)">View More &#8599;</Link>
                            </td>
                        </tr>
                        <tr v-if="!documents.data.length">
                            <td colspan="4" class="doc-empty">{{ search ? 'No documents match your search.' : 'No documents yet.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <TablePager :pagination="documents" />
        </div>
    </SiteLayout>
</template>
