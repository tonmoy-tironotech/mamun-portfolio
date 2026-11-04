<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const form = useForm({ email: '', password: '' });

const submit = () => form.post(url('/login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <AuthPanel title="Log in">
        <form @submit.prevent="submit">
            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <label for="password">Password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="current-password" required />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

            <p class="auth-forgot"><Link :href="$url('/forgot-password')">Forgot password?</Link></p>

            <button type="submit" class="auth-submit" :disabled="form.processing">Log in</button>
        </form>
        <p class="auth-switch">Don't have an account? <Link :href="$url('/register')">Sign up</Link></p>
    </AuthPanel>
</template>
