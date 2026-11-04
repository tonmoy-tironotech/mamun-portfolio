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
            <p>You are signed in as the superadmin ({{ page.props.auth.user.email }}).</p>
            <div class="actions">
                <Link :href="$url('/admin/profile')" class="btn">Edit profile</Link>
                <a :href="$url('/')" target="_blank" rel="noopener" class="btn btn-secondary">View site</a>
            </div>
        </section>
    </AdminLayout>
</template>
