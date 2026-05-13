import { ref } from 'vue';

export function useCopyAsMarkdown() {
    const copied = ref(false);
    const copyError = ref(false);

    async function copyAsMarkdown(markdown: string): Promise<void> {
        copied.value = false;
        copyError.value = false;

        try {
            await navigator.clipboard.writeText(markdown);
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } catch {
            copyError.value = true;
            setTimeout(() => {
                copyError.value = false;
            }, 3000);
        }
    }

    return { copied, copyError, copyAsMarkdown };
}
