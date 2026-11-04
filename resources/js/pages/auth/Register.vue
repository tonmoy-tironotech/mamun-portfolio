<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () => form.post(url('/register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AuthPanel title="Sign up">
        <form @submit.prevent="submit">
            <label for="name">Name</label>
            <input id="name" v-model="form.name" type="text" autocomplete="name" required autofocus />
            <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>

            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" autocomplete="username" required />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <label for="password">Password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password" required />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" class="auth-submit" :disabled="form.processing">Sign up</button>
        </form>
        <p class="auth-switch">Already have an account? <Link :href="$url('/login')">Log in</Link></p>
    </AuthPanel>
</template>
