<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Copy, KeyRound, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { destroy, index, store, update } from '@/routes/team/api-tokens';
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
                token: editingTokenId.value,
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
        destroy({ current_team: currentTeamSlug.value, token: token.id }).url,
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
                title: 'API Tokens',
                href: index(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});
</script>

<template>
    <Head title="API Tokens" />

    <PageHeader
        title="API Tokens"
        description="Create team-scoped MCP bearer tokens for external clients."
    />

    <div class="flex-1 px-4 py-6">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex justify-end">
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Token
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

            <Card v-for="token in tokens" :key="token.id">
                <CardHeader
                    class="flex flex-row items-start justify-between gap-4"
                >
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <KeyRound class="h-4 w-4" />
                            {{ token.name }}
                        </CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Created by {{ token.created_by ?? 'Unknown' }} ·
                            Last used
                            {{ formatDate(token.last_used_at) }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            variant="secondary"
                            @click="copyToken(token.id, token.token)"
                        >
                            <Copy class="mr-2 h-4 w-4" />
                            {{ copiedId === token.id ? 'Copied' : 'Copy' }}
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openEditModal(token)"
                        >
                            Edit
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="revoke(token)"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Revoke
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="resource in resourceKeys"
                            :key="resource"
                            variant="secondary"
                        >
                            {{ apiTokenResourceLabels[resource] }}:
                            {{
                                token.abilities[resource] === 'write'
                                    ? 'read+write'
                                    : token.abilities[resource]
                            }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Task projects:
                        <span
                            v-if="token.abilities.task_projects.mode === 'all'"
                            >all</span
                        >
                        <span v-else
                            >{{
                                token.abilities.task_projects.ids.length
                            }}
                            selected</span
                        >
                        · Expires {{ token.expires_at ?? 'never' }}
                    </p>
                </CardContent>
            </Card>

            <Card v-if="tokens.length === 0">
                <CardContent
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    No API tokens yet.
                </CardContent>
            </Card>
        </div>
    </div>
</template>
