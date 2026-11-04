<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const form = useForm({ email: '' });

const submit = () => form.post(url('/forgot-password'), { onSuccess: () => form.reset() });
</script>

<template>
    <AuthPanel title="Forgot password">
        <p class="auth-hint">Enter your email and we will send you a link to reset your password.</p>
        <form @submit.prevent="submit">
            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <button type="submit" class="auth-submit" :disabled="form.processing">Send reset link</button>
        </form>
        <p class="auth-switch"><Link :href="$url('/login')">Back to log in</Link></p>
    </AuthPanel>
</template>
