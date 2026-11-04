<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

// Loaded on demand so the editor isn't part of the public site's bundle
const RichEditor = defineAsyncComponent(() => import('../../../components/RichEditor.vue'));

const props = defineProps({
    types: { type: Object, required: true },
    publication: { type: Object, default: null },
});

const isEditing = computed(() => props.publication !== null);
const pageTitle = computed(() => (isEditing.value ? 'Edit publication' : 'New publication'));

const form = useForm({
    type: props.publication?.type ?? Object.keys(props.types)[0],
    title: props.publication?.title ?? '',
    description: props.publication?.description ?? '',
});

const submit = () => {
    if (isEditing.value) {
        form.put(url(`/admin/publications/${props.publication.id}`));
    } else {
        form.post(url('/admin/publications'));
    }
};
</script>

<template>
    <Head :title="`${pageTitle} - Admin`" />
    <AdminLayout :title="pageTitle">
        <form class="card" @submit.prevent="submit">
            <label for="type">Type</label>
            <select id="type" v-model="form.type">
                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
            </select>
            <p v-if="form.errors.type" class="field-error">{{ form.errors.type }}</p>

            <label for="title">Title</label>
            <input id="title" v-model="form.title" type="text" maxlength="500" required />
            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>

            <label for="description">Description</label>
            <RichEditor id="description" v-model="form.description" min-height="180px" />
            <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/publications')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
