<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Layers3, Pencil, Save, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import {
    index as collectionsIndex,
    update as updateCollection,
} from '@/routes/team/collections';
import type { CollectionItem, Team } from '@/types';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    collection: CollectionItem;
    recordLinks?: {
        links: LinkRecord[];
        context: LinkContext;
        endpoints: LinkEndpoints;
    } | null;
    recordTags?: {
        tags: RecordTag[];
        context: TagContext;
        endpoints: TagEndpoints;
    } | null;
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const errors = computed(() => page.props.errors ?? {});
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const isEditing = ref(false);
const editTitle = ref(props.collection.title);
const editDescription = ref(props.collection.description ?? '');
const isSubmitting = ref(false);
const deleteDialogRef = ref<InstanceType<typeof DeleteCollectionDialog> | null>(
    null,
);

watch(
    () => props.collection,
    (collection) => {
        if (!isEditing.value) {
            editTitle.value = collection.title;
            editDescription.value = collection.description ?? '';
        }
    },
);

function cancelEdit(): void {
    editTitle.value = props.collection.title;
    editDescription.value = props.collection.description ?? '';
    isEditing.value = false;
}

function submitEdit(): void {
    isSubmitting.value = true;

    router.patch(
        updateCollection({
            current_team: currentTeamSlug.value,
            collection: props.collection.id,
        }).url,
        {
            title: editTitle.value,
            description: editDescription.value || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function formatType(type: string): string {
    return type.replaceAll('_', ' ');
}

function formatDateTime(value?: string | null): string {
    return value ? new Date(value).toLocaleString() : '';
}

defineOptions({
    layout: (layoutProps: {
        currentTeam?: Team | null;
        collection?: { id: number; title: string };
    }) => ({
        breadcrumbs: [
            {
                title: 'Collections',
                href: collectionsIndex(layoutProps.currentTeam?.slug).url,
            },
            {
                title: layoutProps.collection?.title ?? 'Collection',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="collection.title" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            :title="collection.title"
            description="Review and maintain a collection of linked records."
            :back-href="collectionsIndex(currentTeamSlug).url"
            back-label="Back to collections"
        />

        <div class="flex-1 px-4 py-6">
            <EditorSidebarLayout
                variant="compact"
                :updated-at="collection.updatedAt"
                :on-save="isEditing ? submitEdit : null"
                :on-delete="() => deleteDialogRef?.openDeleteDialog(collection)"
                save-label="Save changes"
                delete-label="Delete collection"
                :save-disabled="isSubmitting"
                :delete-disabled="isSubmitting"
                :record-links="recordLinks"
                :record-tags="recordTags"
            >
                <template #main>
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                                    >
                                        <Layers3 class="h-5 w-5" />
                                    </div>
                                    <Badge variant="secondary">
                                        Collection
                                    </Badge>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <template v-if="isEditing">
                                        <Button
                                            variant="outline"
                                            :disabled="isSubmitting"
                                            @click="cancelEdit"
                                        >
                                            <X class="mr-1.5 h-4 w-4" />
                                            Cancel
                                        </Button>
                                        <Button
                                            :disabled="isSubmitting"
                                            @click="submitEdit"
                                        >
                                            <Save class="mr-1.5 h-4 w-4" />
                                            Save
                                        </Button>
                                    </template>
                                    <Button
                                        v-else
                                        variant="outline"
                                        @click="isEditing = true"
                                    >
                                        <Pencil class="mr-1.5 h-4 w-4" />
                                        Edit
                                    </Button>
                                </div>
                            </div>

                            <div v-if="!isEditing" class="space-y-2">
                                <h1
                                    class="text-2xl font-semibold tracking-tight"
                                >
                                    {{ collection.title }}
                                </h1>
                                <p
                                    v-if="collection.description"
                                    class="max-w-3xl text-muted-foreground"
                                >
                                    {{ collection.description }}
                                </p>
                                <p
                                    v-else
                                    class="max-w-3xl text-muted-foreground italic"
                                >
                                    No description yet.
                                </p>
                            </div>

                            <form
                                v-else
                                class="w-full space-y-4"
                                @submit.prevent="submitEdit"
                            >
                                <div class="grid gap-2">
                                    <Label for="collection-title">Title</Label>
                                    <Input
                                        id="collection-title"
                                        v-model="editTitle"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="collection-description">
                                        Description
                                    </Label>
                                    <textarea
                                        id="collection-description"
                                        v-model="editDescription"
                                        :class="taskInputLikeClass"
                                        rows="5"
                                    />
                                    <InputError :message="errors.description" />
                                </div>
                            </form>
                        </div>

                        <section class="space-y-4 border-t pt-6">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        Linked Records
                                    </h2>
                                    <p class="text-sm text-muted-foreground">
                                        {{ recordLinks?.links.length ?? 0 }}
                                        records · Updated
                                        {{
                                            formatDateTime(collection.updatedAt)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="recordLinks?.links.length"
                                class="divide-y rounded-lg border"
                            >
                                <div
                                    v-for="link in recordLinks.links"
                                    :key="`${link.type}-${link.id}`"
                                    class="group flex gap-4 p-4 transition-colors hover:bg-muted/50"
                                >
                                    <Badge
                                        variant="secondary"
                                        class="mt-0.5 h-fit shrink-0 capitalize"
                                    >
                                        {{ formatType(link.type) }}
                                    </Badge>

                                    <div class="min-w-0 flex-1 space-y-1">
                                        <h3 class="truncate font-medium">
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                class="hover:underline"
                                            >
                                                {{ link.title }}
                                            </Link>
                                            <span v-else>{{ link.title }}</span>
                                        </h3>
                                        <p
                                            v-if="link.preview"
                                            class="line-clamp-2 text-sm text-muted-foreground"
                                        >
                                            {{ link.preview }}
                                        </p>
                                        <p
                                            v-else
                                            class="text-sm text-muted-foreground italic"
                                        >
                                            No preview available.
                                        </p>
                                    </div>

                                    <a
                                        v-if="link.url"
                                        :href="link.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-muted-foreground opacity-70 group-hover:opacity-100 hover:bg-accent hover:text-foreground"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed py-12 text-center"
                            >
                                <Layers3
                                    class="mx-auto mb-3 h-12 w-12 text-muted-foreground/50"
                                />
                                <p class="font-medium">
                                    No linked records yet.
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Use the link search panel to add records to
                                    this collection.
                                </p>
                            </div>
                        </section>
                    </div>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
