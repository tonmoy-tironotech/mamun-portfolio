<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

const props = defineProps({
    contactMessage: { type: Object, required: true },
    replies: { type: Array, required: true },
});

const form = useForm({ body: '' });

const send = () => form.post(url(`/admin/messages/${props.contactMessage.id}/reply`), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});

const destroy = () => {
    if (confirm('Delete this message and its replies?')) {
        router.delete(url(`/admin/messages/${props.contactMessage.id}`));
    }
};
</script>

<template>
    <Head title="Message - Admin" />
    <AdminLayout title="Message">
        <section class="card">
            <h2>{{ contactMessage.subject }}</h2>
            <p class="muted">
                {{ contactMessage.name }} &lt;{{ contactMessage.email }}&gt; &middot; {{ contactMessage.received }}
            </p>
            <p class="message-body">{{ contactMessage.message }}</p>
        </section>

        <section v-if="replies.length" class="card">
            <h2>Replies</h2>
            <div v-for="reply in replies" :key="reply.id" class="reply-item">
                <p class="muted">Sent {{ reply.sent }}</p>
                <p class="message-body">{{ reply.body }}</p>
            </div>
        </section>

        <form class="card" @submit.prevent="send">
            <h2>Reply</h2>
            <p class="muted">Goes to {{ contactMessage.email }} with the subject "Re: {{ contactMessage.subject }}".</p>
            <label for="reply-body">Your reply</label>
            <textarea id="reply-body" v-model="form.body" rows="6" maxlength="10000" required></textarea>
            <p v-if="form.errors.body" class="field-error">{{ form.errors.body }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Send reply</button>
                <Link :href="$url('/admin/messages')" class="btn btn-secondary">Back</Link>
                <button type="button" class="btn btn-danger" @click="destroy">Delete</button>
            </div>
        </form>
    </AdminLayout>
</template>
