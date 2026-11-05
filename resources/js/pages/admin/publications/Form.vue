<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, ref } from 'vue';
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

// Each row is one PDF to upload; "Add another PDF" adds a row
let rowId = 0;
const rows = ref([{ id: rowId++, file: null, label: '' }]);

const addRow = () => rows.value.push({ id: rowId++, file: null, label: '' });

const removeRow = (id) => {
    rows.value = rows.value.filter((row) => row.id !== id);

    // Always keep one empty row to pick a file with
    if (!rows.value.length) {
        addRow();
    }
};

const toggleRemove = (id) => {
    form.remove_files = form.remove_files.includes(id) ? form.remove_files.filter((i) => i !== id) : [...form.remove_files, id];
};

const submit = () => {
    // Rows without a chosen file are ignored
    const files = rows.value.filter((row) => row.file).map(({ file, label }) => ({ file, label }));

    // Browsers can't send files with PUT, so the update is posted with a method override
    if (isEditing.value) {
        form.transform((data) => ({ ...data, files, _method: 'put' })).post(url(`/admin/publications/${props.publication.id}`), { forceFormData: true });
    } else {
        form.transform((data) => ({ ...data, files })).post(url('/admin/publications'), { forceFormData: true });
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

            <label>PDFs</label>
            <ul v-if="existingFiles.length" class="file-list">
                <li v-for="file in existingFiles" :key="file.id" :class="{ removed: form.remove_files.includes(file.id) }">
                    <a :href="$url(`/publications/files/${file.id}`)" target="_blank" rel="noopener">[{{ file.label }}]</a>
                    {{ file.file_name }} ({{ fileSize(file.file_size) }})
                    <button type="button" class="btn btn-secondary" @click="toggleRemove(file.id)">
                        {{ form.remove_files.includes(file.id) ? 'Undo' : 'Remove' }}
                    </button>
                </li>
            </ul>
            <div v-for="(row, index) in rows" :key="row.id" class="file-row">
                <input
                    type="file"
                    accept="application/pdf,.pdf"
                    :aria-label="`PDF ${index + 1}`"
                    @change="row.file = $event.target.files[0] ?? null"
                />
                <input v-model="row.label" type="text" maxlength="50" :placeholder="`Name (optional, default pdf${existingFiles.length + index + 1})`" />
                <button type="button" class="btn btn-secondary" @click="removeRow(row.id)">Remove</button>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-secondary" @click="addRow">+ Add another PDF</button>
            </div>
            <p class="field-hint">Each PDF shows on the site as a link after the description. Leave the name empty to get [pdf1] [pdf2] ... Up to 20 MB each.</p>
            <p v-for="message in fileErrors" :key="message" class="field-error">{{ message }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/publications')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
