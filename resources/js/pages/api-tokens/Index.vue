<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Copy, KeyRound, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, index, store, update } from '@/routes/team/mcp';
import { apiTokenResourceLabels } from '@/types';
import type {
    ApiTokenAbilities,
    ApiTokenItem,
    ApiTokenPermission,
    ApiTokenProject,
    Team,
} from '@/types';
import TokenDialog from './TokenDialog.vue';

type Props = {
    tokens: ApiTokenItem[];
    projects: ApiTokenProject[];
    permissionLevels: ApiTokenPermission[];
};

defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const resourceKeys = Object.keys(apiTokenResourceLabels) as Array<
    keyof Omit<ApiTokenAbilities, 'task_projects'>
>;

const form = reactive({
    name: '',
    expires_at: '',
    abilities: {
        collections: 'none' as ApiTokenPermission,
        notes: 'none' as ApiTokenPermission,
        bookmarks: 'none' as ApiTokenPermission,
        contacts: 'none' as ApiTokenPermission,
        calendar: 'none' as ApiTokenPermission,
        tasks: 'none' as ApiTokenPermission,
        task_projects: {
            mode: 'all' as 'all' | 'only',
            ids: [] as number[],
        },
    },
});

const copiedId = ref<number | null>(null);
const tokenDialogOpen = ref(false);
const tokenDialogMode = ref<'create' | 'edit'>('create');
const editingTokenId = ref<number | null>(null);

function resetForm(): void {
    form.name = '';
    form.expires_at = '';

    for (const resource of resourceKeys) {
        form.abilities[resource] = 'none';
    }

    form.abilities.task_projects.mode = 'all';
    form.abilities.task_projects.ids = [];
    editingTokenId.value = null;
}

function openCreateModal(): void {
    resetForm();
    tokenDialogMode.value = 'create';
    tokenDialogOpen.value = true;
}

function openEditModal(token: ApiTokenItem): void {
    form.name = token.name;
    form.expires_at = token.expires_at ?? '';

    for (const resource of resourceKeys) {
        form.abilities[resource] = token.abilities[resource];
    }

    form.abilities.task_projects.mode = token.abilities.task_projects.mode;
    form.abilities.task_projects.ids = [...token.abilities.task_projects.ids];
    editingTokenId.value = token.id;
    tokenDialogMode.value = 'edit';
    tokenDialogOpen.value = true;
}

function submit(data: {
    name: string;
    expires_at: string;
    abilities: Record<string, unknown>;
}): void {
    form.name = data.name;
    form.expires_at = data.expires_at;
    Object.assign(form.abilities, data.abilities);

    if (tokenDialogMode.value === 'edit' && editingTokenId.value !== null) {
        router.patch(
            update({
                current_team: currentTeamSlug.value,
                mcpToken: editingTokenId.value,
            }).url,
            form,
            {
                preserveScroll: true,
                onError: () => {
                    tokenDialogOpen.value = true;
                },
                onSuccess: () => {
                    resetForm();
                    tokenDialogOpen.value = false;
                },
            },
        );

        return;
    }

    router.post(store(currentTeamSlug.value).url, form, {
        preserveScroll: true,
        onError: () => {
            tokenDialogOpen.value = true;
        },
        onSuccess: () => {
            resetForm();
            tokenDialogOpen.value = false;
        },
    });
}

function revoke(token: ApiTokenItem): void {
    router.delete(
        destroy({ current_team: currentTeamSlug.value, mcpToken: token.id })
            .url,
        { preserveScroll: true },
    );
}

async function copyToken(tokenId: number, token: string): Promise<void> {
    await navigator.clipboard.writeText(token);
    copiedId.value = tokenId;
    window.setTimeout(() => (copiedId.value = null), 2000);
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : 'Never';
}

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'MCP',
                href: index(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="MCP" />

    <PageHeader
        title="MCP"
        description="Create team-scoped MCP bearer tokens for external clients."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex justify-end">
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Create MCP
                </Button>

                <TokenDialog
                    v-model:open="tokenDialogOpen"
                    :mode="tokenDialogMode"
                    :initial-form="form"
                    :projects="projects"
                    :permission-levels="permissionLevels"
                    :errors="errors"
                    @submit="submit"
                />
            </div>

            <ListContainer v-if="tokens.length > 0" layout="list">
                <ListItem
                    v-for="token in tokens"
                    :key="token.id"
                    :clickable="false"
                >
                    <div class="flex items-center gap-4">
                        <ListItemIcon size="sm">
                            <KeyRound class="h-4 w-4 text-muted-foreground" />
                        </ListItemIcon>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ token.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                Created by {{ token.created_by ?? 'Unknown' }}
                                · Last used
                                {{ formatDate(token.last_used_at) }}
                                · Expires {{ formatDate(token.expires_at) }}
                            </p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <Badge
                                    v-for="resource in resourceKeys.filter(
                                        (r) => token.abilities[r] !== 'none',
                                    )"
                                    :key="resource"
                                    variant="secondary"
                                    class="text-[10px]"
                                >
                                    {{ apiTokenResourceLabels[resource] }}
                                </Badge>
                                <Badge
                                    v-if="token.abilities.tasks !== 'none'"
                                    variant="outline"
                                    class="px-1.5 py-0 text-[10px]"
                                >
                                    <template
                                        v-if="
                                            token.abilities.task_projects
                                                .mode === 'all'
                                        "
                                    >
                                        All projects
                                    </template>
                                    <template v-else>
                                        {{
                                            token.abilities.task_projects.ids
                                                .length
                                        }}
                                        projects
                                    </template>
                                </Badge>
                            </div>
                        </div>

                        <ListItemActions>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                :title="
                                    copiedId === token.id
                                        ? 'Copied'
                                        : 'Copy token'
                                "
                                @click="copyToken(token.id, token.token)"
                            >
                                <Copy class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                title="Edit token"
                                @click="openEditModal(token)"
                            >
                                <Pencil class="h-4 w-4 text-muted-foreground" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                title="Revoke token"
                                @click="revoke(token)"
                            >
                                <Trash2 class="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </ListItemActions>
                    </div>
                </ListItem>
            </ListContainer>

            <EmptyState
                v-else
                title="No MCP yet."
                description="Create your first MCP credential to authenticate external clients."
                :show-action="true"
                action-label="Create MCP"
                @action="openCreateModal"
            >
                <template #icon>
                    <KeyRound class="h-12 w-12" />
                </template>
                <template #action-icon>
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                </template>
            </EmptyState>
        </div>
    </div>
</template>
