<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post(url('/reset-password'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <AuthPanel title="Reset password">
        <form @submit.prevent="submit">
            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" autocomplete="username" required />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

            <label for="password">New password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password" required autofocus />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" class="auth-submit" :disabled="form.processing">Reset password</button>
        </form>
    </AuthPanel>
</template>
