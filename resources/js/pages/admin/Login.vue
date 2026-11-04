<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { url } from '../../url';

const form = useForm({ email: '', password: '' });

const submit = () => form.post(url('/admin/login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Log in - Admin" />
    <div class="login">
        <form class="card login-card" @submit.prevent="submit">
            <h1>Admin Panel</h1>

            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <label for="password">Password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="current-password" required />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

            <button type="submit" class="btn" :disabled="form.processing">Log in</button>
        </form>
    </div>
</template>
