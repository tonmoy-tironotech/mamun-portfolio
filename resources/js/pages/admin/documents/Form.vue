<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import { fileSize } from '../../../format';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

// Loaded on demand so the editor isn't part of the public site's bundle
const RichEditor = defineAsyncComponent(() => import('../../../components/RichEditor.vue'));

const props = defineProps({ document: { type: Object, default: null } });

const isEditing = computed(() => props.document !== null);
const pageTitle = computed(() => (isEditing.value ? 'Edit document' : 'New document'));

const form = useForm({
    title: props.document?.title ?? '',
    short_description: props.document?.short_description ?? '',
    long_description: props.document?.long_description ?? '',
    file: null,
});

const submit = () => {
    // Browsers can't send files with PUT, so the update is posted with a method override
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(url(`/admin/documents/${props.document.id}`), { forceFormData: true });
    } else {
        form.post(url('/admin/documents'), { forceFormData: true });
    }
};
</script>

<template>
    <Head :title="`${pageTitle} - Admin`" />
    <AdminLayout :title="pageTitle">
        <form class="card" @submit.prevent="submit">
            <label for="title">Title</label>
            <input id="title" v-model="form.title" type="text" maxlength="255" required />
            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>

            <label for="short_description">Short description</label>
            <RichEditor id="short_description" v-model="form.short_description" min-height="90px" />
            <p v-if="form.errors.short_description" class="field-error">{{ form.errors.short_description }}</p>

            <label for="long_description">Long description</label>
            <RichEditor id="long_description" v-model="form.long_description" min-height="260px" />
            <p v-if="form.errors.long_description" class="field-error">{{ form.errors.long_description }}</p>

            <label for="file">File</label>
            <p v-if="document" class="field-hint">
                Current: <a :href="$url(`/student-corner/documents/${document.id}/download`)">{{ document.file_name }}</a> ({{ fileSize(document.file_size) }}).
                Choose a file only to replace it.
            </p>
            <input id="file" type="file" :required="!isEditing" @change="form.file = $event.target.files[0] ?? null" />
            <p class="field-hint">PDF, Word, PowerPoint, Excel, TXT, CSV, ZIP, JPG or PNG, up to 20 MB.</p>
            <p v-if="form.errors.file" class="field-error">{{ form.errors.file }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/documents')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
