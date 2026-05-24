<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TeamInvitationController from '@/actions/App/Http/Controllers/Teams/TeamInvitationController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    invitation: {
        code: string;
        teamName: string;
        inviterName: string;
        role: string;
        roleLabel: string;
    };
};

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
    layout: {
        title: 'Team invitation',
        description: 'You have been invited to join a team.',
    },
});
</script>

<template>
    <Head title="Team invitation" />

    <div class="space-y-6">
        <p class="text-sm text-muted-foreground">
            <strong class="text-foreground">{{
                invitation.inviterName
            }}</strong>
            has invited you to join
            <strong class="text-foreground">{{ invitation.teamName }}</strong>
            as a
            <strong class="text-foreground">{{ invitation.roleLabel }}</strong
            >.
        </p>

        <Form
            v-bind="
                TeamInvitationController.accept.form({
                    invitation: invitation.code,
                })
            "
            class="w-full"
            v-slot="{ errors, processing }"
        >
            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Accept invitation
            </Button>

            <div
                v-if="errors.invitation"
                class="mt-2 text-center text-sm font-medium text-red-600"
            >
                {{ errors.invitation }}
            </div>
        </Form>
    </div>
</template>
