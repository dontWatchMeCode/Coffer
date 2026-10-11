<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import CreateTeamModal from '@/components/modals/CreateTeamModal.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { switchMethod } from '@/routes/teams';
import type { Team } from '@/types';

const page = usePage();

const currentTeam = computed(() => page.props.currentTeam);
const teams = computed(() => page.props.teams ?? []);

const switchTeam = (team: Team) => {
    const previousTeamSlug = currentTeam.value?.slug;

    router.visit(switchMethod(team.slug), {
        onFinish: () => {
            if (!previousTeamSlug || typeof window === 'undefined') {
                router.reload();

                return;
            }

            const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
            const segment = `/${previousTeamSlug}`;

            if (currentUrl.includes(segment)) {
                router.visit(currentUrl.replace(segment, `/${team.slug}`), {
                    replace: true,
                });

                return;
            }

            router.reload();
        },
    });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                data-test="team-switcher-trigger"
                variant="outline"
                class="h-8 gap-1 px-2"
            >
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="max-w-[120px] truncate font-medium">
                        {{ currentTeam?.name ?? 'Select team' }}
                    </span>
                </div>
                <ChevronsUpDown class="size-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent class="w-56" align="end">
            <DropdownMenuLabel class="text-xs text-muted-foreground">
                Teams
            </DropdownMenuLabel>
            <DropdownMenuItem
                v-for="team in teams"
                :key="team.id"
                data-test="team-switcher-item"
                class="cursor-pointer gap-2"
                @click="switchTeam(team)"
            >
                {{ team.name }}
                <Check
                    v-if="currentTeam?.id === team.id"
                    class="ml-auto size-4"
                />
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <CreateTeamModal>
                <DropdownMenuItem
                    data-test="team-switcher-new-team"
                    class="cursor-pointer gap-2"
                    @select.prevent
                >
                    <Plus class="size-4" />
                    <span class="text-muted-foreground">New team</span>
                </DropdownMenuItem>
            </CreateTeamModal>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
