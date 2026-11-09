<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { squarePhoto } from '../image';
import SiteLayout from '../layouts/SiteLayout.vue';
import { url } from '../url';

const page = usePage();
const user = page.props.auth.user;

const profile = useForm({ name: user.name, email: user.email });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });

const authUser = computed(() => page.props.auth.user);
const photoForm = useForm({ photo: null });
const photoPreview = ref(null);
const photoProblem = ref('');

const clearPreview = () => {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }

    photoPreview.value = null;
    photoForm.photo = null;
};

// The photo is cropped to a square and shrunk here, before it is uploaded
const pickPhoto = async (event) => {
    const file = event.target.files[0];

    event.target.value = '';
    photoProblem.value = '';
    photoForm.clearErrors();

    if (!file) {
        return;
    }

    try {
        clearPreview();
        photoForm.photo = await squarePhoto(file);
        photoPreview.value = URL.createObjectURL(photoForm.photo);
    } catch {
        photoProblem.value = "That image couldn't be read. Please choose a JPG, PNG or WebP photo.";
    }
};

const savePhoto = () => photoForm.post(url('/student-corner/profile/photo'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: clearPreview,
});

const removePhoto = () => {
    if (confirm('Remove your profile photo?')) {
        router.delete(url('/student-corner/profile/photo'), { preserveScroll: true });
    }
};

onBeforeUnmount(clearPreview);

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

            <section v-if="authUser.email_verified" class="profile-card profile-photo-card">
                <img class="profile-avatar" :src="photoPreview ?? $url(authUser.profile_photo ?? '/images/default_avatar.svg')" alt="" />
                <div class="profile-photo-body">
                    <h3>Profile photo</h3>
                    <p v-if="!authUser.profile_photo && !photoPreview" class="photo-warning-text">
                        You haven't uploaded a profile photo yet. Please add one. It is shown next to your name on the Members page.
                    </p>
                    <p v-else>It is shown next to your name on the Members page.</p>

                    <div class="photo-actions">
                        <label class="photo-pick">
                            Choose photo
                            <input type="file" accept="image/jpeg,image/png,image/webp" @change="pickPhoto" />
                        </label>
                        <button v-if="photoForm.photo" type="button" class="photo-button" :disabled="photoForm.processing" @click="savePhoto">Save photo</button>
                        <button v-else-if="authUser.profile_photo" type="button" class="photo-remove" @click="removePhoto">Remove photo</button>
                    </div>
                    <p v-if="photoProblem || photoForm.errors.photo" class="field-error">{{ photoProblem || photoForm.errors.photo }}</p>
                </div>
            </section>

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
