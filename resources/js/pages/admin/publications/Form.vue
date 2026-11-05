<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import { fileSize } from '../../../format';
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
    files: [],
    remove_files: [],
});

const existingFiles = computed(() => props.publication?.files ?? []);
const fileErrors = computed(() => Object.entries(form.errors).filter(([key]) => key === 'files' || key.startsWith('files.')).map(([, message]) => message));

// Each pick adds to the list, so PDFs can be chosen one at a time or several at once
const addFiles = (event) => {
    form.files = [...form.files, ...event.target.files];
    event.target.value = '';
};

const removeNew = (index) => {
    form.files = form.files.filter((_, i) => i !== index);
};

const toggleRemove = (id) => {
    form.remove_files = form.remove_files.includes(id) ? form.remove_files.filter((i) => i !== id) : [...form.remove_files, id];
};

const submit = () => {
    // Browsers can't send files with PUT, so the update is posted with a method override
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(url(`/admin/publications/${props.publication.id}`), { forceFormData: true });
    } else {
        form.post(url('/admin/publications'), { forceFormData: true });
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

            <label for="files">PDFs</label>
            <ul v-if="existingFiles.length" class="file-list">
                <li v-for="file in existingFiles" :key="file.id" :class="{ removed: form.remove_files.includes(file.id) }">
                    <a :href="$url(`/publications/files/${file.id}`)" target="_blank" rel="noopener">[{{ file.label }}]</a>
                    {{ file.file_name }} ({{ fileSize(file.file_size) }})
                    <button type="button" class="btn btn-secondary" @click="toggleRemove(file.id)">
                        {{ form.remove_files.includes(file.id) ? 'Undo' : 'Remove' }}
                    </button>
                </li>
            </ul>
            <ul v-if="form.files.length" class="file-list">
                <li v-for="(file, index) in form.files" :key="`${file.name}-${index}`">
                    New: {{ file.name }} ({{ fileSize(file.size) }})
                    <button type="button" class="btn btn-secondary" @click="removeNew(index)">Remove</button>
                </li>
            </ul>
            <input id="files" type="file" accept="application/pdf,.pdf" multiple @change="addFiles" />
            <p class="field-hint">You can add several PDFs. They show on the site as [pdf1] [pdf2] ... after the description. Up to 20 MB each.</p>
            <p v-for="message in fileErrors" :key="message" class="field-error">{{ message }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/publications')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
