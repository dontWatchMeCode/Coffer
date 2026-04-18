<script setup lang="ts">
import { Mail, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Team, TeamInvitation, TeamPermissions } from '@/types';

defineProps<{
    team: Team;
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
}>();

const emit = defineEmits<{
    cancelInvitation: [invitation: TeamInvitation];
}>();
</script>

<template>
    <div v-if="invitations.length > 0" class="space-y-6">
        <h3 class="text-sm font-medium">Pending invitations</h3>

        <div class="space-y-3">
            <div
                v-for="invitation in invitations"
                :key="invitation.code"
                data-test="invitation-row"
                class="flex items-center justify-between rounded-lg border p-4"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"
                    >
                        <Mail class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div>
                        <div class="font-medium">
                            {{ invitation.email }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ invitation.role_label }}
                        </div>
                    </div>
                </div>

                <TooltipProvider v-if="permissions.canCancelInvitation">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                data-test="invitation-cancel-button"
                                variant="ghost"
                                size="sm"
                                @click="emit('cancelInvitation', invitation)"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Cancel invitation</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </div>
    </div>
</template>
