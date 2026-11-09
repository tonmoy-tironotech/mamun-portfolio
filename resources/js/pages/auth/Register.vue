<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const form = useForm({ name: '', email: '', student_id: '', education_level: '', mobile: '', password: '', password_confirmation: '' });

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

            <label for="student_id">Student ID</label>
            <input id="student_id" v-model="form.student_id" type="text" maxlength="50" autocomplete="off" required />
            <p v-if="form.errors.student_id" class="field-error">{{ form.errors.student_id }}</p>

            <label for="education_level">Education level</label>
            <input id="education_level" v-model="form.education_level" type="text" maxlength="100" placeholder="e.g. B.Sc 3rd year, M.Sc, PhD" required />
            <p v-if="form.errors.education_level" class="field-error">{{ form.errors.education_level }}</p>

            <label for="mobile">Mobile</label>
            <input id="mobile" v-model="form.mobile" type="tel" autocomplete="tel" placeholder="01XXXXXXXXX" required />
            <p v-if="form.errors.mobile" class="field-error">{{ form.errors.mobile }}</p>

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
