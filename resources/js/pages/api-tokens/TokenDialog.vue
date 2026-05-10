<script setup lang="ts">
import { onClickOutside } from '@vueuse/core';
import { CheckIcon, Plus } from 'lucide-vue-next';
import {
    ListboxContent,
    ListboxFilter,
    ListboxItem,
    ListboxItemIndicator,
    ListboxRoot,
} from 'reka-ui';
import type { AcceptableInputValue, AcceptableValue } from 'reka-ui';
import { computed, reactive, ref, watch } from 'vue';
import InputError from '@/components/form/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    TagsInput,
    TagsInputInput,
    TagsInputItem,
    TagsInputItemDelete,
    TagsInputItemText,
} from '@/components/ui/tags-input';
import { apiTokenResourceLabels } from '@/types';
import type {
    ApiTokenAbilities,
    ApiTokenPermission,
    ApiTokenProject,
} from '@/types';

type FormAbilities = {
    collections: ApiTokenPermission;
    notes: ApiTokenPermission;
    bookmarks: ApiTokenPermission;
    subscriptions: ApiTokenPermission;
    contacts: ApiTokenPermission;
    calendar: ApiTokenPermission;
    tasks: ApiTokenPermission;
    task_projects: {
        mode: 'all' | 'only';
        ids: number[];
    };
};

type FormData = {
    name: string;
    expires_at: string;
    abilities: FormAbilities;
};

type Props = {
    open: boolean;
    mode: 'create' | 'edit';
    initialForm: FormData;
    projects: ApiTokenProject[];
    permissionLevels: ApiTokenPermission[];
    errors: Record<string, string>;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [form: FormData];
}>();

const localForm = reactive<FormData>({
    name: '',
    expires_at: '',
    abilities: {
        collections: 'none',
        notes: 'none',
        bookmarks: 'none',
        subscriptions: 'none',
        contacts: 'none',
        calendar: 'none',
        tasks: 'none',
        task_projects: { mode: 'all', ids: [] },
    },
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            Object.assign(
                localForm,
                JSON.parse(JSON.stringify(props.initialForm)),
            );
        }
    },
    { immediate: true },
);

const resourceKeys = Object.keys(apiTokenResourceLabels) as Array<
    keyof Omit<ApiTokenAbilities, 'task_projects'>
>;

const projectSearchTerm = ref('');
const projectPickerOpen = ref(false);
const projectPickerRef = ref<HTMLDivElement | null>(null);

onClickOutside(projectPickerRef, () => {
    projectPickerOpen.value = false;
});

const availableProjects = computed(() =>
    props.projects.filter((project) => {
        if (localForm.abilities.task_projects.ids.includes(project.id)) {
            return false;
        }

        const search = projectSearchTerm.value.trim().toLowerCase();

        return search === '' || project.name.toLowerCase().includes(search);
    }),
);

const selectedProjects = computed(() =>
    props.projects.filter((project) =>
        localForm.abilities.task_projects.ids.includes(project.id),
    ),
);

const selectedProjectNames = computed(() =>
    selectedProjects.value.map((project) => project.name),
);

function addProject(project: ApiTokenProject | undefined): void {
    if (!project) {
        return;
    }

    if (!localForm.abilities.task_projects.ids.includes(project.id)) {
        localForm.abilities.task_projects.ids = [
            ...localForm.abilities.task_projects.ids,
            project.id,
        ];
    }

    projectSearchTerm.value = '';
    projectPickerOpen.value = false;
}

function removeProject(projectId: number): void {
    localForm.abilities.task_projects.ids =
        localForm.abilities.task_projects.ids.filter((id) => id !== projectId);
}

function handleProjectModelValueUpdate(
    nextValues: AcceptableInputValue[],
): void {
    const nextNames = nextValues.map((value) => String(value));
    const removed = selectedProjects.value.find(
        (project) => !nextNames.includes(project.name),
    );

    if (removed) {
        removeProject(removed.id);
    }
}

function handleSelectedProjects(nextValues: AcceptableValue): void {
    if (!Array.isArray(nextValues)) {
        return;
    }

    const nextNames = nextValues.map((value) => String(value));
    const addedName = nextNames.find(
        (name) => !selectedProjectNames.value.includes(name),
    );

    if (!addedName) {
        return;
    }

    addProject(props.projects.find((project) => project.name === addedName));
}

function handleSubmit(): void {
    emit('submit', {
        name: localForm.name,
        expires_at: localForm.expires_at,
        abilities: JSON.parse(JSON.stringify(localForm.abilities)),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-xl">
            <form class="space-y-5" @submit.prevent="handleSubmit">
                <DialogHeader>
                    <DialogTitle>
                        {{ mode === 'create' ? 'Create MCP' : 'Edit MCP' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            mode === 'create'
                                ? 'Generate a team-scoped MCP credential with resource permissions.'
                                : 'Update the MCP name, expiry, and permissions.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="token-name">Name</Label>
                    <Input
                        id="token-name"
                        v-model="localForm.name"
                        placeholder="OpenCode"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="token-expiry">Expiry</Label>
                    <Input
                        id="token-expiry"
                        v-model="localForm.expires_at"
                        type="date"
                    />
                    <InputError :message="errors.expires_at" />
                </div>

                <div class="space-y-3">
                    <Label>Permissions</Label>
                    <div
                        v-for="resource in resourceKeys"
                        :key="resource"
                        class="grid grid-cols-[1fr_150px] items-center gap-3"
                    >
                        <span class="text-sm">{{
                            apiTokenResourceLabels[resource]
                        }}</span>
                        <Select v-model="localForm.abilities[resource]">
                            <SelectTrigger class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="level in permissionLevels"
                                    :key="level"
                                    :value="level"
                                >
                                    {{
                                        level === 'write'
                                            ? 'Read+Write'
                                            : level.charAt(0).toUpperCase() +
                                              level.slice(1)
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    v-if="localForm.abilities.tasks !== 'none'"
                    class="space-y-3"
                >
                    <Label>Task project scope</Label>
                    <Select v-model="localForm.abilities.task_projects.mode">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All projects</SelectItem>
                            <SelectItem value="only"
                                >Selected projects</SelectItem
                            >
                        </SelectContent>
                    </Select>

                    <div
                        v-if="localForm.abilities.task_projects.mode === 'only'"
                        class="space-y-3"
                    >
                        <ListboxRoot
                            :model-value="selectedProjectNames"
                            highlight-on-hover
                            multiple
                            @update:model-value="handleSelectedProjects"
                        >
                            <div ref="projectPickerRef" class="relative">
                                <TagsInput
                                    :model-value="selectedProjectNames"
                                    class="min-h-9 w-full border-muted bg-background/60 shadow-none"
                                    @update:model-value="
                                        handleProjectModelValueUpdate
                                    "
                                >
                                    <TagsInputItem
                                        v-for="project in selectedProjects"
                                        :key="project.id"
                                        :value="project.name"
                                    >
                                        <TagsInputItemText />
                                        <TagsInputItemDelete />
                                    </TagsInputItem>
                                    <ListboxFilter
                                        v-model="projectSearchTerm"
                                        as-child
                                    >
                                        <TagsInputInput
                                            placeholder="Search projects..."
                                            @focus="projectPickerOpen = true"
                                            @keydown.down="
                                                projectPickerOpen = true
                                            "
                                        />
                                    </ListboxFilter>
                                </TagsInput>

                                <div
                                    v-if="projectPickerOpen"
                                    class="absolute bottom-full left-0 z-50 mb-1 w-full rounded-lg border bg-popover p-1 text-popover-foreground shadow-md"
                                >
                                    <ListboxContent
                                        class="max-h-[220px] scroll-py-1 overflow-x-hidden overflow-y-auto"
                                        tabindex="0"
                                    >
                                        <ListboxItem
                                            v-for="project in availableProjects"
                                            :key="project.id"
                                            class="relative flex cursor-default items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-hidden select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                            :value="project.name"
                                        >
                                            <span class="truncate">{{
                                                project.name
                                            }}</span>
                                            <ListboxItemIndicator
                                                class="ml-auto inline-flex items-center justify-center"
                                            >
                                                <CheckIcon class="h-4 w-4" />
                                            </ListboxItemIndicator>
                                        </ListboxItem>

                                        <div
                                            v-if="
                                                availableProjects.length === 0
                                            "
                                            class="px-2 py-1.5 text-xs text-muted-foreground"
                                        >
                                            No projects found.
                                        </div>
                                    </ListboxContent>
                                </div>
                            </div>
                        </ListboxRoot>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit">
                        <Plus v-if="mode === 'create'" class="mr-2 h-4 w-4" />
                        {{ mode === 'create' ? 'Create MCP' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
