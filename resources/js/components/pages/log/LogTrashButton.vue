<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { trash as logTrash } from '@/routes/team/log';

type Props = {
    teamSlug: string;
};

defineProps<Props>();
</script>

<template>
    <Button
        variant="outline"
        size="icon"
        title="Trash"
        aria-label="Trash"
        as-child
    >
        <Link :href="logTrash(teamSlug).url">
            <Trash2 class="h-4 w-4" />
        </Link>
    </Button>
</template>
