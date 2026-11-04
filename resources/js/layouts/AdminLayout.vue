<script setup>
import { Link, usePage } from '@inertiajs/vue3';

defineProps({ title: { type: String, required: true } });

const page = usePage();
</script>

<template>
    <div class="admin">
        <aside class="admin-sidebar">
            <div class="admin-brand">Admin Panel</div>
            <nav class="admin-nav">
                <Link :href="$url('/admin')" :class="{ active: page.component === 'admin/Dashboard' }">Dashboard</Link>
                <Link :href="$url('/admin/profile')" :class="{ active: page.component === 'admin/Profile' }">Profile</Link>
                <a :href="$url('/')" target="_blank" rel="noopener">View site</a>
            </nav>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar">
                <h1>{{ title }}</h1>
                <div class="admin-user">
                    <span>{{ page.props.auth.user.name }}</span>
                    <Link :href="$url('/admin/logout')" method="post" as="button" class="btn btn-secondary">Log out</Link>
                </div>
            </header>
            <main class="admin-content">
                <p v-if="page.props.flash.status" class="alert" role="status">{{ page.props.flash.status }}</p>
                <slot />
            </main>
        </div>
    </div>
</template>
