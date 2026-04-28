<script setup lang="ts">
import { Save, Trash2 } from 'lucide-vue-next';
import { computed, useSlots } from 'vue';
import RecordLinksPanel from '@/components/record-links/RecordLinksPanel.vue';
import RecordTagsPanel from '@/components/record-tags/RecordTagsPanel.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type {
    LinkContext,
    LinkEndpoints,
    LinkRecord,
} from '@/types/record-links';
import type { RecordTag, TagContext, TagEndpoints } from '@/types/record-tags';

type Props = {
    variant?: 'default' | 'compact';
    createdBy?: string | null;
    updatedAt?: string | null;
    onDelete?: (() => void) | null;
    onSave?: (() => void) | null;
    deleteLabel?: string;
    saveLabel?: string;
    deleteDisabled?: boolean;
    saveDisabled?: boolean;
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

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    createdBy: null,
    updatedAt: null,
    onDelete: null,
    onSave: null,
    deleteLabel: 'Delete',
    saveLabel: 'Save',
    deleteDisabled: false,
    saveDisabled: false,
    recordLinks: null,
    recordTags: null,
});

const slots = useSlots();

const hasSidebarTop = computed(() => Boolean(slots['sidebar-top']));
const hasMeta = computed(() => Boolean(props.createdBy || props.updatedAt));
const hasActions = computed(() => Boolean(props.onDelete || props.onSave));
const hasRecordLinks = computed(() => props.recordLinks !== null);
const hasRecordTags = computed(() => props.recordTags !== null);

function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
}
</script>

<template>
    <div
        class="mx-auto flex flex-col xl:flex-row"
        :class="[
            props.variant === 'compact' ? 'max-w-5xl gap-6' : 'max-w-7xl gap-8',
        ]"
    >
        <div class="order-2 min-w-0 flex-1 space-y-6 xl:order-1">
            <slot name="main" />
        </div>

        <div
            v-if="
                hasSidebarTop ||
                hasMeta ||
                hasActions ||
                hasRecordLinks ||
                hasRecordTags
            "
            class="order-1 h-fit w-full shrink-0 space-y-4 select-none xl:sticky xl:top-4 xl:order-2 xl:w-[280px]"
        >
            <RecordTagsPanel
                v-if="hasRecordTags && recordTags"
                :tags="recordTags.tags"
                :context="recordTags.context"
                :endpoints="recordTags.endpoints"
            />

            <RecordLinksPanel
                v-if="hasRecordLinks && recordLinks"
                :links="recordLinks.links"
                :context="recordLinks.context"
                :endpoints="recordLinks.endpoints"
            />

            <Separator
                v-if="
                    hasRecordLinks && (hasSidebarTop || hasMeta || hasActions)
                "
            />

            <slot name="sidebar-top" />

            <Separator v-if="hasSidebarTop && (hasMeta || hasActions)" />

            <div v-if="hasMeta" class="space-y-3">
                <div
                    v-if="createdBy"
                    class="flex justify-between gap-4 text-sm"
                >
                    <span class="text-muted-foreground">Created by</span>
                    <span class="text-right">{{ createdBy }}</span>
                </div>

                <div
                    v-if="updatedAt"
                    class="flex justify-between gap-4 text-sm"
                >
                    <span class="text-muted-foreground">Updated at</span>
                    <span class="text-right">{{
                        formatDateTime(updatedAt)
                    }}</span>
                </div>
            </div>

            <Separator v-if="hasMeta && hasActions" />

            <div v-if="hasActions" class="space-y-2">
                <Button
                    v-if="onSave"
                    size="sm"
                    class="w-full cursor-pointer justify-start gap-2"
                    :disabled="saveDisabled"
                    @click="onSave()"
                >
                    <Save class="h-4 w-4" />
                    {{ saveLabel }}
                </Button>

                <Button
                    v-if="onDelete"
                    variant="outline"
                    size="sm"
                    class="w-full cursor-pointer justify-start gap-2 text-destructive hover:bg-destructive hover:text-destructive-foreground"
                    :disabled="deleteDisabled"
                    @click="onDelete()"
                >
                    <Trash2 class="h-4 w-4" />
                    {{ deleteLabel }}
                </Button>
            </div>
        </div>
    </div>
</template>
