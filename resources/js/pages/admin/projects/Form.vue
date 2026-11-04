<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, ref } from 'vue';
import AdminLayout from '../../../layouts/AdminLayout.vue';
import { url } from '../../../url';

// Loaded on demand so the editor isn't part of the public site's bundle
const RichEditor = defineAsyncComponent(() => import('../../../components/RichEditor.vue'));

const props = defineProps({ project: { type: Object, default: null } });

const isEditing = computed(() => props.project !== null);
const pageTitle = computed(() => (isEditing.value ? 'Edit project' : 'New project'));
const preview = ref(null);

const form = useForm({
    title: props.project?.title ?? '',
    url: props.project?.url ?? '',
    body: props.project?.body ?? '',
    image: null,
});

const pickImage = (event) => {
    form.image = event.target.files[0] ?? null;
    preview.value = form.image ? URL.createObjectURL(form.image) : null;
};

const submit = () => {
    // Browsers can't send files with PUT, so the update is posted with a method override
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(url(`/admin/projects/${props.project.id}`), { forceFormData: true });
    } else {
        form.post(url('/admin/projects'), { forceFormData: true });
    }
};
</script>

<template>
    <Head :title="`${pageTitle} - Admin`" />
    <AdminLayout :title="pageTitle">
        <form class="card" @submit.prevent="submit">
            <label for="title">Title</label>
            <input id="title" v-model="form.title" type="text" required />
            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>

            <label for="image">Image</label>
            <img v-if="preview || project" class="image-preview" :src="preview ?? $url(project.image)" alt="" />
            <input id="image" type="file" accept="image/jpeg,image/png,image/webp" :required="!isEditing" @change="pickImage" />
            <p class="field-hint">JPG, PNG or WebP, up to 4 MB.</p>
            <p v-if="form.errors.image" class="field-error">{{ form.errors.image }}</p>

            <label for="url">Project link (optional)</label>
            <input id="url" v-model="form.url" type="url" placeholder="https://" />
            <p v-if="form.errors.url" class="field-error">{{ form.errors.url }}</p>

            <label for="body">Description</label>
            <RichEditor id="body" v-model="form.body" min-height="260px" />
            <p v-if="form.errors.body" class="field-error">{{ form.errors.body }}</p>

            <div class="actions">
                <button type="submit" class="btn" :disabled="form.processing">Save</button>
                <Link :href="$url('/admin/projects')" class="btn btn-secondary">Cancel</Link>
            </div>
        </form>
    </AdminLayout>
</template>
