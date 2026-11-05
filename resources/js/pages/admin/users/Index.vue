<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TablePager from '../../../components/TablePager.vue';
import { useSearch } from '../../../composables/useSearch';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    users: { type: Object, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/admin/users');

const approve = (user) => router.post(url(`/admin/users/${user.id}/approve`), {}, { preserveScroll: true });

const revoke = (user) => {
    if (confirm(`Remove document access for ${user.name}?`)) {
        router.delete(url(`/admin/users/${user.id}/approve`), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Users - Admin" />
    <AdminLayout title="Users">
        <div class="toolbar">
            <input v-model="term" type="search" class="search" placeholder="Search by name or email" aria-label="Search users" />
        </div>

        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="col-name">{{ user.name }}</td>
                        <td class="col-email">{{ user.email }}</td>
                        <td>
                            <span v-if="user.is_admin" class="badge badge-admin">Admin</span>
                            <span v-else-if="user.is_pending" class="badge badge-pending">Pending</span>
                            <span v-else class="badge badge-replied">Approved</span>
                        </td>
                        <td class="col-date">{{ user.joined }}</td>
                        <td class="col-actions">
                            <button v-if="user.is_pending" type="button" class="btn" @click="approve(user)">Approve</button>
                            <button v-else-if="!user.is_admin" type="button" class="btn btn-danger" @click="revoke(user)">Revoke</button>
                            <Link :href="$url(`/admin/users/${user.id}/edit`)" class="btn btn-secondary">Edit</Link>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td colspan="5" class="empty">{{ search ? 'No users match your search.' : 'No users yet.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="muted">{{ users.total }} {{ users.total === 1 ? 'user' : 'users' }}</span>
            <TablePager :pagination="users" />
        </div>
    </AdminLayout>
</template>
