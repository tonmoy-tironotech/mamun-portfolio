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
    link_url: props.publication?.link_url ?? '',
    pdf: null,
    remove_pdf: false,
});

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

            <label for="pdf">PDF (optional)</label>
            <p v-if="publication?.has_pdf" class="field-hint">
                Current: <a :href="$url(`/publications/${publication.id}/pdf`)" target="_blank" rel="noopener">{{ publication.pdf_name }}</a> ({{ fileSize(publication.pdf_size) }}).
                Choose a file only to replace it.
            </p>
            <input id="pdf" type="file" accept="application/pdf,.pdf" @change="form.pdf = $event.target.files[0] ?? null" />
            <p v-if="publication?.has_pdf" class="field-hint">
                <label class="checkbox-label"><input v-model="form.remove_pdf" type="checkbox" /> Remove the current PDF</label>
            </p>
            <p class="field-hint">One PDF, up to 20 MB. It shows on the site as a PDF icon.</p>
            <p v-if="form.errors.pdf" class="field-error">{{ form.errors.pdf }}</p>

            <label for="link_url">Link (optional)</label>
            <input id="link_url" v-model="form.link_url" type="url" maxlength="2000" placeholder="https://" />
            <p class="field-hint">One web link. It shows on the site as a link icon.</p>
            <p v-if="form.errors.link_url" class="field-error">{{ form.errors.link_url }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/publications')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
