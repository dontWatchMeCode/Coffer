<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Layers3, Pencil } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import EditorSidebarLayout from '@/components/layouts/EditorSidebarLayout.vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import DeleteCollectionDialog from '@/components/pages/collections/DeleteCollectionDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { formatDateTime } from '@/lib/utils';
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
                        <div v-if="!isEditing" class="space-y-4">
                            <div class="space-y-2">
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

                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="isEditing = true"
                                >
                                    <Pencil class="mr-1.5 h-4 w-4" />
                                    Edit
                                </Button>
                            </div>
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
                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="cancelEdit"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="isSubmitting">
                                    Save changes
                                </Button>
                            </div>
                        </form>

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

                            <ListContainer v-if="recordLinks?.links.length">
                                <ListItem
                                    v-for="link in recordLinks.links"
                                    :key="`${link.type}-${link.id}`"
                                    :clickable="!!link.url"
                                    :aria-label="
                                        link.url
                                            ? `Open ${formatType(link.type)}: ${link.title}`
                                            : undefined
                                    "
                                    @click="link.url && router.visit(link.url)"
                                >
                                    <div class="flex items-center gap-4">
                                        <ListItemIcon size="sm" rounded="lg">
                                            <span
                                                class="text-[10px] font-medium text-muted-foreground uppercase"
                                            >
                                                {{ formatType(link.type) }}
                                            </span>
                                        </ListItemIcon>

                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium">
                                                {{ link.title }}
                                            </p>
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

                                        <ListItemActions>
                                            <Button
                                                v-if="link.url"
                                                as="a"
                                                :href="link.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8"
                                                aria-label="Open link"
                                                @click.stop
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                            </Button>
                                        </ListItemActions>
                                    </div>
                                </ListItem>
                            </ListContainer>

                            <EmptyState
                                v-else
                                title="No linked records yet."
                                description="Use the link search panel to add records to this collection."
                            >
                                <template #icon>
                                    <Layers3 class="h-12 w-12" />
                                </template>
                            </EmptyState>
                        </section>
                    </div>
                </template>
            </EditorSidebarLayout>
        </div>
    </div>

    <DeleteCollectionDialog ref="deleteDialogRef" />
</template>
