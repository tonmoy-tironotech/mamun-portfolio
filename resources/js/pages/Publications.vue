<script setup>
import { Link } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import PublicationItem from '../components/PublicationItem.vue';
import PublicationLinks from '../components/PublicationLinks.vue';
import TablePager from '../components/TablePager.vue';
import { useSearch } from '../composables/useSearch';
import SiteLayout from '../layouts/SiteLayout.vue';

const props = defineProps({
    publications: { type: Object, required: true },
    types: { type: Array, required: true },
    activeType: { type: String, required: true },
    search: { type: String, default: '' },
});

const term = useSearch(props.search, '/publications', () => ({ type: props.activeType }));

const modal = ref(null);
const selected = ref(null);

const openModal = async (publication) => {
    selected.value = publication;
    await nextTick();
    modal.value.showModal();
};

// A click on the dialog element itself, not its contents, is a click on the backdrop
const closeOnBackdrop = (event) => {
    if (event.target === modal.value) {
        modal.value.close();
    }
};

const tabHref = (type) => {
    const query = new URLSearchParams({ type });

    if (props.search) {
        query.set('search', props.search);
    }

    return `/publications?${query}`;
};
</script>

<template>
    <SiteLayout wide>
        <div class="post publications">
            <div class="pub-header">
                <h2 class="title"><a href="#"><b>Publications</b></a></h2>
                <input v-model="term" type="search" placeholder="Search publications" aria-label="Search publications" />
            </div>

            <nav class="pub-tabs" aria-label="Publication types">
                <Link
                    v-for="type in types"
                    :key="type.key"
                    :href="$url(tabHref(type.key))"
                    :class="{ active: type.key === activeType }"
                    :aria-current="type.key === activeType ? 'page' : undefined"
                    preserve-scroll
                >
                    <span>{{ type.label }}</span>
                    <span class="pub-count">{{ type.count }}</span>
                </Link>
            </nav>

            <ol class="pub-list">
                <PublicationItem
                    v-for="(publication, index) in publications.data"
                    :key="publication.id"
                    :number="publications.from + index"
                    :publication="publication"
                    @view-more="openModal"
                />
            </ol>

            <p v-if="!publications.data.length" class="pub-empty">
                {{ search ? 'No publications match your search.' : 'No publications yet.' }}
            </p>

            <TablePager :pagination="publications" />

            <dialog ref="modal" class="pub-modal" aria-labelledby="pub-modal-title" @click="closeOnBackdrop" @close="selected = null">
                <template v-if="selected">
                    <div class="pub-modal-header">
                        <h3 id="pub-modal-title">{{ selected.title }}</h3>
                        <button type="button" class="pub-modal-close" aria-label="Close" @click="modal.close()">&times;</button>
                    </div>
                    <div class="pub-modal-body rich-text" tabindex="0" v-html="selected.description"></div>
                    <PublicationLinks :publication="selected" class="pub-modal-links" />
                </template>
            </dialog>
        </div>
    </SiteLayout>
</template>
