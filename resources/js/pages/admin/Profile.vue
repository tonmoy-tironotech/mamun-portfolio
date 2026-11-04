<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import { url } from '../../url';

const user = usePage().props.auth.user;

const profile = useForm({ name: user.name, email: user.email });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });

const saveProfile = () => profile.put(url('/admin/profile'), { preserveScroll: true });

const savePassword = () => password.put(url('/admin/profile/password'), {
    preserveScroll: true,
    onSuccess: () => password.reset(),
    onError: () => password.reset('current_password'),
});
</script>

<template>
    <Head title="Profile - Admin" />
    <AdminLayout title="Profile">
        <form class="card" @submit.prevent="saveProfile">
            <h2>Account details</h2>

            <label for="name">Name</label>
            <input id="name" v-model="profile.name" type="text" autocomplete="name" required />
            <p v-if="profile.errors.name" class="field-error">{{ profile.errors.name }}</p>

            <label for="email">Email</label>
            <input id="email" v-model="profile.email" type="email" autocomplete="username" required />
            <p v-if="profile.errors.email" class="field-error">{{ profile.errors.email }}</p>

            <button type="submit" class="btn" :disabled="profile.processing">Save</button>
        </form>

        <form class="card" @submit.prevent="savePassword">
            <h2>Change password</h2>

            <label for="current_password">Current password</label>
            <input id="current_password" v-model="password.current_password" type="password" autocomplete="current-password" required />
            <p v-if="password.errors.current_password" class="field-error">{{ password.errors.current_password }}</p>

            <label for="password">New password</label>
            <input id="password" v-model="password.password" type="password" autocomplete="new-password" required />
            <p v-if="password.errors.password" class="field-error">{{ password.errors.password }}</p>

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" v-model="password.password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" class="btn" :disabled="password.processing">Change password</button>
        </form>
    </AdminLayout>
</template>
