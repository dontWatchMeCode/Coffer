<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CancelInvitationModal from '@/components/modals/CancelInvitationModal.vue';
import DeleteTeamModal from '@/components/modals/DeleteTeamModal.vue';
import InviteMemberModal from '@/components/modals/InviteMemberModal.vue';
import RemoveMemberModal from '@/components/modals/RemoveMemberModal.vue';
import Heading from '@/components/page/Heading.vue';
import DangerZone from '@/components/teams/DangerZone.vue';
import InvitationsList from '@/components/teams/InvitationsList.vue';
import MembersList from '@/components/teams/MembersList.vue';
import TeamSettingsForm from '@/components/teams/TeamSettingsForm.vue';
import { edit, index } from '@/routes/teams';
import type {
    RoleOption,
    Team,
    TeamFeatureOption,
    TeamInvitation,
    TeamMember,
    TeamPermissions,
} from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
    teamFeatures: TeamFeatureOption[];
};

const props = defineProps<Props>();

defineOptions({
    layout: (props: { team: Team }) => ({
        breadcrumbs: [
            {
                title: 'Teams',
                href: index(),
            },
            {
                title: props.team.name,
                href: edit(props.team.slug),
            },
        ],
    }),
});

const inviteDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const removeMemberDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const cancelInvitationDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);

const pageTitle = computed(() =>
    props.permissions.canUpdateTeam
        ? `Edit ${props.team.name}`
        : `View ${props.team.name}`,
);
</script>

<template>
    <Head :title="pageTitle" />

    <h1 class="sr-only">{{ pageTitle }}</h1>

    <div class="flex flex-col space-y-10">
        <div v-if="permissions.canUpdateTeam" class="space-y-6">
            <Heading
                variant="small"
                title="Team settings"
                description="Update your team name and settings"
            />

            <TeamSettingsForm :team="team" :team-features="teamFeatures" />
        </div>

        <div v-else class="space-y-6">
            <Heading variant="small" :title="team.name" />
        </div>

        <MembersList
            :team="team"
            :members="members"
            :available-roles="availableRoles"
            :permissions="permissions"
            @invite-member="inviteDialogOpen = true"
            @remove-member="
                (member) => {
                    memberToRemove = member;
                    removeMemberDialogOpen = true;
                }
            "
        />

        <InvitationsList
            :team="team"
            :invitations="invitations"
            :permissions="permissions"
            @cancel-invitation="
                (invitation) => {
                    invitationToCancel = invitation;
                    cancelInvitationDialogOpen = true;
                }
            "
        />

        <DangerZone
            v-if="permissions.canDeleteTeam"
            :team="team"
            @delete-team="deleteDialogOpen = true"
        />
    </div>

    <InviteMemberModal
        v-if="permissions.canCreateInvitation"
        :team="team"
        :available-roles="availableRoles"
        :open="inviteDialogOpen"
        @update:open="inviteDialogOpen = $event"
    />

    <RemoveMemberModal
        :team="team"
        :member="memberToRemove"
        :open="removeMemberDialogOpen"
        @update:open="removeMemberDialogOpen = $event"
    />

    <CancelInvitationModal
        :team="team"
        :invitation="invitationToCancel"
        :open="cancelInvitationDialogOpen"
        @update:open="cancelInvitationDialogOpen = $event"
    />

    <DeleteTeamModal
        v-if="permissions.canDeleteTeam && !team.isPersonal"
        :team="team"
        :open="deleteDialogOpen"
        @update:open="deleteDialogOpen = $event"
    />
</template>
