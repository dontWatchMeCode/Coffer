<script setup lang="ts">
import { Form, Head, usePage, Link } from '@inertiajs/vue3';
import { Archive, FolderPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/Tasks/ProjectController';
import InputError from '@/components/form/InputError.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskInputLikeClass } from '@/lib/tasks';
import { index, show } from '@/routes/team/tasks/index';
import type { TaskProject, TaskStats, Team } from '@/types';

type Props = {
    projects: TaskProject[];
    stats: TaskStats;
};

const props = defineProps<Props>();

const page = usePage();
const currentTeamSlug = computed(() => page.props.currentTeam?.slug ?? '');
const createProjectOpen = ref(false);
const createProjectFormKey = ref(0);
const showArchived = ref(false);
const emptyProjectStateCardClass =
    'flex h-full min-h-[180px] cursor-pointer items-center justify-center border-dashed transition-colors hover:border-primary hover:bg-accent/20';

const hasArchivedProjects = computed(() =>
    props.projects.some((p) => p.isArchived),
);

const visibleProjects = computed(() =>
    showArchived.value
        ? props.projects
        : props.projects.filter((p) => !p.isArchived),
);

defineOptions({
    layout: (pageProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Tasks',
                href: index(pageProps.currentTeam?.slug).url,
            },
        ],
    }),
});

function handleCreateProjectModal(value: boolean): void {
    createProjectOpen.value = value;

    if (!value) {
        createProjectFormKey.value++;
    }
}
</script>

<template>
    <Head title="Tasks" />

    <div class="flex min-h-screen flex-col">
        <PageHeader
            title="Tasks"
            description="Choose a project to work on or create a new one."
        />

        <div class="flex-1 px-4 py-6">
            <div class="mx-auto max-w-7xl space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <Button
                        v-if="hasArchivedProjects"
                        variant="outline"
                        size="icon"
                        title="Show archived"
                        class="cursor-pointer"
                        :class="
                            showArchived
                                ? 'bg-black text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90'
                                : 'bg-muted hover:bg-muted/80'
                        "
                        @click="showArchived = !showArchived"
                    >
                        <Archive class="h-4 w-4" />
                    </Button>

                    <Button
                        size="icon"
                        title="New project"
                        class="cursor-pointer"
                        @click="createProjectOpen = true"
                    >
                        <FolderPlus class="h-4 w-4" />
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <template v-if="visibleProjects.length > 0">
                        <Link
                            v-for="project in visibleProjects"
                            :key="project.id"
                            :href="
                                show({
                                    current_team: currentTeamSlug,
                                    project: project.id,
                                }).url
                            "
                            class="block"
                        >
                            <Card
                                class="h-full transition-colors hover:border-primary hover:bg-accent/20"
                            >
                                <CardHeader>
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <CardTitle>{{
                                            project.name
                                        }}</CardTitle>
                                        <Badge
                                            variant="secondary"
                                            :class="
                                                project.isArchived
                                                    ? ''
                                                    : 'invisible'
                                            "
                                        >
                                            Archived
                                        </Badge>
                                    </div>
                                    <CardDescription>
                                        {{
                                            project.description ||
                                            'No description yet.'
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-muted-foreground"
                                            >Open tasks</span
                                        >
                                        <span class="font-medium">{{
                                            project.openTasksCount
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-muted-foreground"
                                            >Total tasks</span
                                        >
                                        <span class="font-medium">{{
                                            project.tasksCount
                                        }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    </template>

                    <Card
                        v-else-if="projects.length === 0"
                        :class="emptyProjectStateCardClass"
                        @click="createProjectOpen = true"
                    >
                        <div class="text-center">
                            <FolderPlus
                                class="mx-auto mb-3 h-8 w-8 text-muted-foreground"
                            />
                            <CardTitle class="text-base"
                                >Create your first project</CardTitle
                            >
                            <CardDescription
                                >Get started by creating a new project for your
                                team.</CardDescription
                            >
                        </div>
                    </Card>

                    <template v-else>
                        <Card
                            :class="emptyProjectStateCardClass"
                            @click="showArchived = !showArchived"
                        >
                            <div class="text-center">
                                <Archive
                                    class="mx-auto mb-3 h-8 w-8 text-muted-foreground"
                                />
                                <CardTitle class="text-base"
                                    >All projects are archived</CardTitle
                                >
                                <CardDescription
                                    >Toggle the archive filter to view your
                                    existing projects.</CardDescription
                                >
                            </div>
                        </Card>

                        <Card
                            :class="emptyProjectStateCardClass"
                            @click="createProjectOpen = true"
                        >
                            <div class="text-center">
                                <FolderPlus
                                    class="mx-auto mb-3 h-8 w-8 text-muted-foreground"
                                />
                                <CardTitle class="text-base"
                                    >Create a new project</CardTitle
                                >
                                <CardDescription
                                    >Add a new project to your team
                                    workspace.</CardDescription
                                >
                            </div>
                        </Card>
                    </template>
                </div>
            </div>
        </div>

        <Dialog
            :open="createProjectOpen"
            @update:open="handleCreateProjectModal"
        >
            <DialogContent>
                <Form
                    :key="createProjectFormKey"
                    v-bind="ProjectController.store.form(currentTeamSlug)"
                    reset-on-success
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="handleCreateProjectModal(false)"
                >
                    <DialogHeader>
                        <DialogTitle>Create a project</DialogTitle>
                        <DialogDescription>
                            Add a new project to this team workspace.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="project-name">Name</Label>
                        <Input
                            id="project-name"
                            name="name"
                            placeholder="Client portal"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="project-description">Description</Label>
                        <textarea
                            id="project-description"
                            name="description"
                            :class="taskInputLikeClass"
                            rows="4"
                            placeholder="What is this project for?"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="processing">
                            Create project
                        </Button>
                    </div>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
