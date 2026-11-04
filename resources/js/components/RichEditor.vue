<script setup>
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, required: true },
    minHeight: { type: String, default: '180px' },
});
const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            code: false,
            codeBlock: false,
            horizontalRule: false,
            strike: false,
            link: { openOnClick: false, protocols: ['http', 'https', 'mailto'] },
        }),
    ],
    editorProps: {
        attributes: { id: props.id, role: 'textbox', 'aria-multiline': 'true', style: `min-height: ${props.minHeight}` },
    },
    onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value, { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const setLink = () => {
    const current = editor.value.getAttributes('link').href ?? '';
    const href = window.prompt('Link URL (leave empty to remove)', current);

    if (href === null) {
        return;
    }

    const chain = editor.value.chain().focus().extendMarkRange('link');

    if (href.trim() === '') {
        chain.unsetLink().run();
    } else {
        chain.setLink({ href: href.trim() }).run();
    }
};

const buttons = computed(() => [
    { label: 'B', title: 'Bold', name: 'bold', run: (c) => c.toggleBold() },
    { label: 'I', title: 'Italic', name: 'italic', run: (c) => c.toggleItalic() },
    { label: 'U', title: 'Underline', name: 'underline', run: (c) => c.toggleUnderline() },
    { label: 'H2', title: 'Heading', name: 'heading', attrs: { level: 2 }, run: (c) => c.toggleHeading({ level: 2 }) },
    { label: 'H3', title: 'Subheading', name: 'heading', attrs: { level: 3 }, run: (c) => c.toggleHeading({ level: 3 }) },
    { label: 'List', title: 'Bullet list', name: 'bulletList', run: (c) => c.toggleBulletList() },
    { label: '1. List', title: 'Numbered list', name: 'orderedList', run: (c) => c.toggleOrderedList() },
    { label: 'Quote', title: 'Quote', name: 'blockquote', run: (c) => c.toggleBlockquote() },
]);
</script>

<template>
    <div class="rich-editor">
        <div v-if="editor" class="rich-toolbar">
            <button
                v-for="button in buttons"
                :key="button.title"
                type="button"
                :title="button.title"
                :class="{ active: editor.isActive(button.name, button.attrs) }"
                @click="button.run(editor.chain().focus()).run()"
            >{{ button.label }}</button>
            <button type="button" title="Link" :class="{ active: editor.isActive('link') }" @click="setLink">Link</button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
