<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TablePager from '../../../components/TablePager.vue';
import { useSearch } from '../../../composables/useSearch';
import AdminLayout from '../../../layouts/AdminLayout.vue';

const props = defineProps({
    messages: { type: Object, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/admin/messages');
</script>

<template>
    <Head title="Messages - Admin" />
    <AdminLayout title="Messages">
        <div class="toolbar">
            <input v-model="term" type="search" class="search" placeholder="Search messages" aria-label="Search messages" />
        </div>

        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Received</th>
                        <th>Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="message in messages.data" :key="message.id" :class="{ unread: !message.is_read }">
                        <td>
                            <div class="col-name">{{ message.name }}</div>
                            <div class="muted col-email">{{ message.email }}</div>
                        </td>
                        <td class="col-text">
                            <div class="col-subject">{{ message.subject }}</div>
                            <div>{{ message.preview }}</div>
                        </td>
                        <td class="col-date">{{ message.received }}</td>
                        <td>
                            <span class="badge" :class="{ 'badge-admin': !message.is_read, 'badge-replied': message.is_replied }">
                                {{ message.is_replied ? 'Replied' : message.is_read ? 'Read' : 'New' }}
                            </span>
                        </td>
                        <td class="col-actions">
                            <Link :href="$url(`/admin/messages/${message.id}`)" class="btn btn-secondary">View</Link>
                        </td>
                    </tr>
                    <tr v-if="!messages.data.length">
                        <td colspan="5" class="empty">{{ search ? 'No messages match your search.' : 'No messages yet.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="muted">{{ messages.total }} {{ messages.total === 1 ? 'message' : 'messages' }}</span>
            <TablePager :pagination="messages" />
        </div>
    </AdminLayout>
</template>
