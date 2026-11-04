<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';

const page = usePage();
</script>

<template>
    <Head title="Dashboard - Admin" />
    <AdminLayout title="Dashboard">
        <section class="card">
            <h2>Welcome, {{ page.props.auth.user.name }}</h2>
            <p>You are signed in as {{ page.props.auth.user.email }}.</p>
            <div class="actions">
                <Link :href="$url('/admin/projects')" class="btn">Manage projects</Link>
                <Link :href="$url('/admin/documents')" class="btn">Manage documents</Link>
                <Link :href="$url('/admin/publications')" class="btn">Manage publications</Link>
                <Link :href="$url('/admin/messages')" class="btn">Messages</Link>
                <Link :href="$url('/admin/users')" class="btn">Manage users</Link>
                <Link :href="$url('/admin/profile')" class="btn btn-secondary">Edit profile</Link>
                <a :href="$url('/')" target="_blank" rel="noopener" class="btn btn-secondary">View site</a>
            </div>
        </section>
    </AdminLayout>
</template>
