<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, UserPlus, X } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import { update as updateMember } from '@/routes/teams/members';
import type { RoleOption, Team, TeamMember, TeamPermissions } from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
    availableRoles: RoleOption[];
    permissions: TeamPermissions;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    inviteMember: [];
    removeMember: [member: TeamMember];
}>();

const { getInitials } = useInitials();

function updateMemberRole(member: TeamMember, newRole: string): void {
    router.visit(updateMember([props.team.slug, member.id]), {
        data: { role: newRole },
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Team members"
                :description="
                    permissions.canCreateInvitation
                        ? 'Manage who belongs to this team'
                        : ''
                "
            />

            <Button
                v-if="permissions.canCreateInvitation"
                data-test="invite-member-button"
                @click="emit('inviteMember')"
            >
                <UserPlus /> Invite member
            </Button>
        </div>

        <div class="space-y-3">
            <div
                v-for="member in members"
                :key="member.id"
                data-test="member-row"
                class="flex items-center justify-between rounded-lg border p-4"
            >
                <div class="flex items-center gap-4">
                    <Avatar class="h-10 w-10">
                        <AvatarImage
                            v-if="member.avatar"
                            :src="member.avatar"
                            :alt="member.name"
                        />
                        <AvatarFallback>{{
                            getInitials(member.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <div class="font-medium">
                            {{ member.name }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ member.email }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <DropdownMenu
                        v-if="
                            member.role !== 'owner' &&
                            permissions.canUpdateMember
                        "
                    >
                        <DropdownMenuTrigger as-child>
                            <Button
                                data-test="member-role-trigger"
                                variant="outline"
                                size="sm"
                            >
                                {{ member.role_label }}
                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem
                                v-for="role in availableRoles"
                                :key="role.value"
                                data-test="member-role-option"
                                @click="updateMemberRole(member, role.value)"
                            >
                                {{ role.label }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Badge v-else variant="secondary">
                        {{ member.role_label }}
                    </Badge>

                    <TooltipProvider
                        v-if="
                            member.role !== 'owner' &&
                            permissions.canRemoveMember
                        "
                    >
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    data-test="member-remove-button"
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('removeMember', member)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Remove member</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>
        </div>
    </div>
</template>
