<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { url } from '../url';

const page = usePage();

const form = useForm({ name: '', email: '', subject: '', message: '', website: '' });

const submit = () => form.post(url('/contact'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});
</script>

<template>
    <form class="auth-form contact-form" @submit.prevent="submit">
        <h3>Send a Message</h3>
        <p v-if="page.props.flash.status" class="form-success" role="status">{{ page.props.flash.status }}</p>

        <div class="contact-fields">
            <div>
                <label for="contact-name">Name</label>
                <input id="contact-name" v-model="form.name" type="text" maxlength="255" autocomplete="name" required />
                <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label for="contact-email">Email</label>
                <input id="contact-email" v-model="form.email" type="email" maxlength="255" autocomplete="email" required />
                <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
            </div>
        </div>

        <label for="contact-subject">Subject</label>
        <input id="contact-subject" v-model="form.subject" type="text" maxlength="255" required />
        <p v-if="form.errors.subject" class="field-error">{{ form.errors.subject }}</p>

        <label for="contact-message">Message</label>
        <textarea id="contact-message" v-model="form.message" rows="6" maxlength="5000" required></textarea>
        <p v-if="form.errors.message" class="field-error">{{ form.errors.message }}</p>

        <!-- Left empty by people; bots tend to fill every field -->
        <div class="contact-trap" aria-hidden="true">
            <label for="contact-website">Website</label>
            <input id="contact-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
        </div>

        <button type="submit" class="auth-submit" :disabled="form.processing">Send message</button>
    </form>
</template>
