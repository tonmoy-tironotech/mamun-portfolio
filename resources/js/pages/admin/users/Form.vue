<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    user: { type: Object, required: true },
    memberStatuses: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    student_id: props.user.student_id ?? '',
    education_level: props.user.education_level ?? '',
    mobile: props.user.mobile ?? '',
    member_status: props.user.member_status,
    password: '',
    password_confirmation: '',
});

const removePhoto = () => {
    if (confirm(`Remove the photo of ${props.user.name}?`)) {
        router.delete(url(`/admin/users/${props.user.id}/photo`), { preserveScroll: true });
    }
};

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

            <template v-if="!user.is_admin">
                <div v-if="user.profile_photo" class="photo-row">
                    <img :src="$url(user.profile_photo)" alt="" />
                    <button type="button" class="btn btn-danger" @click="removePhoto">Remove photo</button>
                </div>

                <label for="student_id">Student ID</label>
                <input id="student_id" v-model="form.student_id" type="text" maxlength="50" />
                <p v-if="form.errors.student_id" class="field-error">{{ form.errors.student_id }}</p>

                <label for="education_level">Education level</label>
                <input id="education_level" v-model="form.education_level" type="text" maxlength="100" />
                <p v-if="form.errors.education_level" class="field-error">{{ form.errors.education_level }}</p>

                <label for="mobile">Mobile</label>
                <input id="mobile" v-model="form.mobile" type="tel" />
                <p v-if="form.errors.mobile" class="field-error">{{ form.errors.mobile }}</p>

                <label for="member_status">Member status</label>
                <select id="member_status" v-model="form.member_status">
                    <option v-for="(label, key) in memberStatuses" :key="key" :value="key">{{ label }}</option>
                </select>
                <p class="field-hint">Old students move to the Old Students tab on the public Members page.</p>
                <p v-if="form.errors.member_status" class="field-error">{{ form.errors.member_status }}</p>
            </template>

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
