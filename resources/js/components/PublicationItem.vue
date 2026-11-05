<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import PublicationLinks from './PublicationLinks.vue';

defineProps({
    number: { type: Number, required: true },
    publication: { type: Object, required: true },
});
defineEmits(['view-more']);

const description = ref(null);
const isCut = ref(false);

// Line count depends on screen width and fonts, so it can only be measured in the browser
const measure = () => {
    if (description.value) {
        isCut.value = description.value.scrollHeight > description.value.clientHeight + 1;
    }
};

let observer;

onMounted(() => {
    measure();
    observer = new ResizeObserver(measure);
    observer.observe(description.value);
    document.fonts?.ready.then(measure);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <li>
        <span class="pub-number">{{ number }}</span>
        <div class="pub-body">
            <h3>{{ publication.title }}</h3>
            <div ref="description" class="pub-description rich-text" v-html="publication.description"></div>
            <PublicationLinks :publication="publication" />
            <button v-if="isCut" type="button" class="pub-more" @click="$emit('view-more', publication)">View More &#8599;</button>
        </div>
    </li>
</template>
