<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = ref(false);

onMounted(() => {
    const url = new URL(window.location.href);

    if (url.searchParams.has('verified')) {
        open.value = true;

        setTimeout(() => {
            url.searchParams.delete('verified');
            window.history.replaceState({}, '', url);
        }, 100);
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Email verified</DialogTitle>
                <DialogDescription>
                    Your email address has been verified successfully.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="open = false">Close</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
