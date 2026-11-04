<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SiteLayout from '../layouts/SiteLayout.vue';
import { url } from '../url';

const page = usePage();
const user = page.props.auth.user;

const profile = useForm({ name: user.name, email: user.email });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });

const saveProfile = () => profile.put(url('/student-corner/profile'), { preserveScroll: true });

const savePassword = () => password.put(url('/student-corner/profile/password'), {
    preserveScroll: true,
    onSuccess: () => password.reset(),
    onError: () => password.reset('current_password'),
});
</script>

<template>
    <SiteLayout wide>
        <div class="post student-profile">
            <Link :href="$url('/student-corner')" class="project-back">&larr; Back to Student Corner</Link>
            <h2>My Profile</h2>
            <p v-if="page.props.flash.status" class="profile-alert" role="status">{{ page.props.flash.status }}</p>

            <div class="profile-grid">
                <form class="auth-form profile-card" @submit.prevent="saveProfile">
                    <h3>Account details</h3>

                    <label for="name">Name</label>
                    <input id="name" v-model="profile.name" type="text" autocomplete="name" required />
                    <p v-if="profile.errors.name" class="field-error">{{ profile.errors.name }}</p>

                    <label for="email">Email</label>
                    <input id="email" v-model="profile.email" type="email" autocomplete="username" required />
                    <p v-if="profile.errors.email" class="field-error">{{ profile.errors.email }}</p>

                    <button type="submit" class="auth-submit" :disabled="profile.processing">Save</button>
                </form>

                <form class="auth-form profile-card" @submit.prevent="savePassword">
                    <h3>Change password</h3>

                    <label for="current_password">Current password</label>
                    <input id="current_password" v-model="password.current_password" type="password" autocomplete="current-password" required />
                    <p v-if="password.errors.current_password" class="field-error">{{ password.errors.current_password }}</p>

                    <label for="password">New password</label>
                    <input id="password" v-model="password.password" type="password" autocomplete="new-password" required />
                    <p v-if="password.errors.password" class="field-error">{{ password.errors.password }}</p>

                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" v-model="password.password_confirmation" type="password" autocomplete="new-password" required />

                    <button type="submit" class="auth-submit" :disabled="password.processing">Change password</button>
                </form>
            </div>
        </div>
    </SiteLayout>
</template>
