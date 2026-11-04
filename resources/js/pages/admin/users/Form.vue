<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.put(url(`/admin/users/${props.user.id}`), {
    onError: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Edit user - Admin" />
    <AdminLayout title="Edit user">
        <form class="card" autocomplete="off" @submit.prevent="submit">
            <label for="name">Name</label>
            <input id="name" v-model="form.name" type="text" required />
            <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>

            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" required />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <label for="password">New password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password" />
            <p class="field-hint">Leave empty to keep the current password.</p>
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/users')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
