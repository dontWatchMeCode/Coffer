<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Copy,
    Info,
    KeyRound,
    ListPlus,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/list/EmptyState.vue';
import ListContainer from '@/components/list/ListContainer.vue';
import ListItem from '@/components/list/ListItem.vue';
import ListItemActions from '@/components/list/ListItemActions.vue';
import ListItemIcon from '@/components/list/ListItemIcon.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy, index, store, update } from '@/routes/team/mcp';
import { apiTokenResourceKeys, createApiTokenAbilities } from '@/types';
import type {
    ApiTokenFormData,
    ApiTokenItem,
    ApiTokenPermission,
    ApiTokenProject,
    ApiTokenResourceLabels,
    Team,
} from '@/types';
import TokenDialog from './TokenDialog.vue';

type Props = {
    tokens: ApiTokenItem[];
    projects: ApiTokenProject[];
    permissionLevels: ApiTokenPermission[];
    resourceLabels: ApiTokenResourceLabels;
    mcpEndpointUrl: string;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const errors = computed(() => page.props.errors ?? {});

const resourceKeys = computed(() => apiTokenResourceKeys(props.resourceLabels));

const form = reactive<ApiTokenFormData>({
    name: '',
    expires_at: '',
    abilities: createApiTokenAbilities(resourceKeys.value),
});

const copiedId = ref<number | null>(null);
const infoDialogOpen = ref(false);
const tokenDialogOpen = ref(false);
const tokenDialogMode = ref<'create' | 'edit'>('create');
const editingTokenId = ref<number | null>(null);
const revokeDialogOpen = ref(false);
const selectedToken = ref<ApiTokenItem | null>(null);

function resetForm(): void {
    form.name = '';
    form.expires_at = '';

    for (const resource of resourceKeys.value) {
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

    for (const resource of resourceKeys.value) {
        form.abilities[resource] = token.abilities[resource] ?? 'none';
    }

    form.abilities.task_projects.mode = token.abilities.task_projects.mode;
    form.abilities.task_projects.ids = [...token.abilities.task_projects.ids];
    editingTokenId.value = token.id;
    tokenDialogMode.value = 'edit';
    tokenDialogOpen.value = true;
}

function submit(data: ApiTokenFormData): void {
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

function openRevokeDialog(token: ApiTokenItem): void {
    selectedToken.value = token;
    revokeDialogOpen.value = true;
}

function confirmRevoke(): void {
    if (!selectedToken.value) {
        return;
    }

    const token = selectedToken.value;

    router.delete(
        destroy({ current_team: currentTeamSlug.value, mcpToken: token.id })
            .url,
        {
            preserveScroll: true,
            onSuccess: () => {
                revokeDialogOpen.value = false;
                selectedToken.value = null;
            },
        },
    );
}

async function copyToken(tokenId: number, token: string | null): Promise<void> {
    if (token === null) {
        return;
    }

    await navigator.clipboard.writeText(token);
    copiedId.value = tokenId;
    window.setTimeout(() => (copiedId.value = null), 2000);
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : 'Never';
}

defineOptions({
    inheritAttrs: false,
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

    <div class="min-w-0 flex-1 px-4 py-6">
        <div class="mx-auto w-full max-w-7xl space-y-4">
            <div class="flex justify-end gap-2">
                <Button
                    variant="outline"
                    size="icon"
                    title="How to connect MCP"
                    class="cursor-pointer"
                    @click="infoDialogOpen = true"
                >
                    <Info class="h-4 w-4" />
                </Button>

                <Button
                    size="icon"
                    title="Create MCP"
                    class="cursor-pointer"
                    @click="openCreateModal"
                >
                    <ListPlus class="h-4 w-4" />
                </Button>

                <TokenDialog
                    v-model:open="tokenDialogOpen"
                    :mode="tokenDialogMode"
                    :initial-form="form"
                    :projects="projects"
                    :permission-levels="permissionLevels"
                    :resource-labels="resourceLabels"
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
                    <div
                        class="flex min-w-0 items-center gap-4 overflow-hidden"
                    >
                        <ListItemIcon size="sm">
                            <KeyRound class="h-4 w-4 text-muted-foreground" />
                        </ListItemIcon>

                        <div class="min-w-0 flex-1">
                            <p class="font-medium [overflow-wrap:anywhere]">
                                {{ token.name }}
                            </p>
                            <p
                                class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
                            >
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
                                    {{ resourceLabels[resource] }}
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
                                    token.token === null
                                        ? 'Token unavailable. Create a new MCP token to copy it.'
                                        : copiedId === token.id
                                          ? 'Copied'
                                          : 'Copy token'
                                "
                                :disabled="token.token === null"
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
                                @click="openRevokeDialog(token)"
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

    <Dialog v-model:open="infoDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Connect MCP</DialogTitle>
                <DialogDescription>
                    Use a generated MCP token to connect external clients to
                    your records.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 text-sm">
                <div class="space-y-2">
                    <p class="font-medium">Endpoint</p>
                    <code
                        class="block rounded-md border bg-muted px-3 py-2 text-xs break-all text-muted-foreground"
                    >
                        {{ mcpEndpointUrl }}
                    </code>
                </div>

                <div class="space-y-2">
                    <p class="font-medium">Authentication</p>
                    <p class="text-muted-foreground">
                        Add an Authorization header using the token copied from
                        this page.
                    </p>
                    <code
                        class="block rounded-md border bg-muted px-3 py-2 text-xs break-all text-muted-foreground"
                    >
                        Authorization: Bearer YOUR_MCP_TOKEN
                    </code>
                </div>

                <div class="space-y-2">
                    <p class="font-medium">Client setup</p>
                    <ol
                        class="list-decimal space-y-1 pl-5 text-muted-foreground"
                    >
                        <li>Create an MCP token.</li>
                        <li>Copy the token before closing the page.</li>
                        <li>
                            Add an HTTP/streamable MCP server in your client
                            using the endpoint above.
                        </li>
                        <li>Send the bearer token with every request.</li>
                    </ol>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <ConfirmDeleteDialog
        v-model:open="revokeDialogOpen"
        title="Revoke MCP Token"
        description="This token will stop working immediately for any connected MCP clients."
        confirm-label="Revoke"
        :confirm-icon="Trash2"
        @confirm="confirmRevoke"
    >
        <p v-if="selectedToken" class="text-sm">
            Revoke "{{ selectedToken.name }}"?
        </p>
    </ConfirmDeleteDialog>
</template>
