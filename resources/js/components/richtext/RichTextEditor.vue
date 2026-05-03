<script setup lang="ts">
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';
import {
    normalizeStoredRichText,
    renderStoredRichTextAsHtml,
} from '@/components/richtext/storage';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import './rich-text-editor.css';

const editorShellClass =
    'min-h-32 overflow-hidden rounded-2xl border bg-card shadow-sm';

const toolbarClass =
    'flex flex-wrap items-center gap-1 rounded-t-2xl border-b bg-muted/80 px-2 py-1';

const props = withDefaults(
    defineProps<{
        editable?: boolean;
        modelValue?: string | null;
        placeholder?: string;
        onActivate?: () => void;
    }>(),
    {
        editable: true,
        modelValue: null,
        placeholder: undefined,
        onActivate: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const rootEl = ref<HTMLDivElement | null>(null);
const editor = shallowRef<Editor | null>(null);
const isReady = ref(!props.editable);
const shouldScrollIntoView = ref(false);
const lastSerialized = ref<string>(normalizeStoredRichText(props.modelValue));
const editorInstance = computed<Editor | undefined>(
    () => editor.value ?? undefined,
);
const toolbarRevision = ref(0);

const normalizedValue = computed<string>(() =>
    normalizeStoredRichText(props.modelValue),
);
const hasContent = computed<boolean>(
    () => normalizedValue.value.trim().length > 0,
);
const readonlyHtml = computed<string>(() =>
    renderStoredRichTextAsHtml(props.modelValue),
);
const canActivate = computed(() => !props.editable && !!props.onActivate);

function updateReadyState(): void {
    isReady.value = true;

    if (!shouldScrollIntoView.value) {
        return;
    }

    shouldScrollIntoView.value = false;
    rootEl.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest',
    });
}

function handleDblclick(): void {
    if (canActivate.value) {
        props.onActivate?.();
    }
}

function bumpToolbarRevision(): void {
    toolbarRevision.value += 1;
}

function isActive(name: string, attributes?: Record<string, unknown>): boolean {
    void toolbarRevision.value;

    return editor.value?.isActive(name, attributes) ?? false;
}

function currentBlockType():
    | 'paragraph'
    | 'heading-1'
    | 'heading-2'
    | 'heading-3' {
    if (isActive('heading', { level: 1 })) {
        return 'heading-1';
    }

    if (isActive('heading', { level: 2 })) {
        return 'heading-2';
    }

    if (isActive('heading', { level: 3 })) {
        return 'heading-3';
    }

    return 'paragraph';
}

function setParagraph(): void {
    editor.value?.chain().focus().setParagraph().run();
}

function toggleHeading(level: 1 | 2 | 3): void {
    editor.value?.chain().focus().toggleHeading({ level }).run();
}

function updateBlockType(value: unknown): void {
    if (
        value === null ||
        (typeof value !== 'string' &&
            typeof value !== 'number' &&
            typeof value !== 'bigint')
    ) {
        return;
    }

    const nextValue = value.toString();

    if (nextValue === 'heading-1') {
        toggleHeading(1);

        return;
    }

    if (nextValue === 'heading-2') {
        toggleHeading(2);

        return;
    }

    if (nextValue === 'heading-3') {
        toggleHeading(3);

        return;
    }

    setParagraph();
}

function setLink(): void {
    if (!editor.value) {
        return;
    }

    const previousUrl = editor.value.getAttributes('link').href as
        | string
        | undefined;
    const url = window.prompt('URL', previousUrl ?? 'https://');

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().unsetLink().run();

        return;
    }

    editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
}

onMounted(() => {
    editor.value = new Editor({
        content: normalizedValue.value,
        contentType: 'markdown',
        editable: props.editable,
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
                link: false,
            }),
            Link.configure({
                autolink: true,
                openOnClick: false,
                protocols: ['http', 'https', 'mailto'],
            }),
            Markdown,
            Placeholder.configure({
                placeholder: props.placeholder ?? '',
            }),
        ],
        editorProps: {
            attributes: {
                class: 'tiptap prose prose-sm max-w-none focus:outline-none',
            },
        },
        onCreate: () => {
            bumpToolbarRevision();
            updateReadyState();
        },
        onSelectionUpdate: () => {
            bumpToolbarRevision();
        },
        onUpdate: () => {
            bumpToolbarRevision();
            const serialized = editor.value?.getMarkdown() ?? '';

            if (serialized === lastSerialized.value) {
                return;
            }

            lastSerialized.value = serialized;
            emit('update:modelValue', serialized);
        },
    });

    if (props.editable) {
        shouldScrollIntoView.value = true;
        nextTick().then(() => updateReadyState());
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
    editor.value = null;
});

watch(
    () => props.editable,
    async (editable, wasEditable) => {
        isReady.value = !editable;
        editor.value?.setEditable(editable);

        if (!editable || wasEditable) {
            return;
        }

        shouldScrollIntoView.value = true;
        await nextTick();
        updateReadyState();
    },
);

watch(
    () => normalizedValue.value,
    (value) => {
        lastSerialized.value = value;

        if (!editor.value) {
            return;
        }

        const currentMarkdown = editor.value.getMarkdown();

        if (currentMarkdown === value) {
            return;
        }

        editor.value.commands.setContent(value, { contentType: 'markdown' });
    },
);
</script>

<template>
    <div
        ref="rootEl"
        :class="[
            'rich-text-editor w-full min-w-0',
            isReady ? '' : 'opacity-0',
            canActivate
                ? 'rich-text-editor--readonly cursor-pointer'
                : !editable
                  ? 'rich-text-editor--readonly'
                  : '',
        ]"
        @dblclick="handleDblclick"
    >
        <div v-if="editable" :class="editorShellClass">
            <div :class="toolbarClass">
                <Select
                    :model-value="currentBlockType()"
                    @update:model-value="updateBlockType"
                >
                    <SelectTrigger
                        size="sm"
                        class="h-7 w-30 shrink-0 gap-1.5 border-0 bg-transparent text-sm shadow-none"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="paragraph">Text</SelectItem>
                        <SelectItem value="heading-1">Heading 1</SelectItem>
                        <SelectItem value="heading-2">Heading 2</SelectItem>
                        <SelectItem value="heading-3">Heading 3</SelectItem>
                    </SelectContent>
                </Select>
                <div class="rich-text-editor__toolbar-group">
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button',
                            isActive('bold')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="editor?.chain().focus().toggleBold().run()"
                    >
                        B
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button italic',
                            isActive('italic')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="editor?.chain().focus().toggleItalic().run()"
                    >
                        I
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button line-through',
                            isActive('strike')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="editor?.chain().focus().toggleStrike().run()"
                    >
                        S
                    </Button>
                </div>

                <div class="rich-text-editor__toolbar-group">
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button',
                            isActive('bulletList')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="
                            editor?.chain().focus().toggleBulletList().run()
                        "
                    >
                        • List
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button',
                            isActive('orderedList')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="
                            editor?.chain().focus().toggleOrderedList().run()
                        "
                    >
                        1. List
                    </Button>
                </div>

                <div class="rich-text-editor__toolbar-group">
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button',
                            isActive('blockquote')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="
                            editor?.chain().focus().toggleBlockquote().run()
                        "
                    >
                        Quote
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        :class="[
                            'rich-text-editor__bar-button',
                            isActive('link')
                                ? 'rich-text-editor__button--active'
                                : '',
                        ]"
                        @click="setLink"
                    >
                        Link
                    </Button>
                </div>
            </div>
            <EditorContent :editor="editorInstance" />
        </div>

        <div
            v-else-if="hasContent"
            class="rich-text-editor__html prose prose-sm max-w-none rounded-lg"
            v-html="readonlyHtml"
        />
    </div>
</template>
