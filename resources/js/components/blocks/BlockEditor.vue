<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    FileText,
    Paintbrush,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';
import ExcalidrawBlock from '@/components/blocks/ExcalidrawBlock.vue';
import TextBlock from '@/components/blocks/TextBlock.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type {
    ExcalidrawPayload,
    RteBlock,
    RteBlockType,
    TextPayload,
} from '@/types/notes';

const props = withDefaults(
    defineProps<{
        blocks: RteBlock[];
        editable?: boolean;
        name?: string;
    }>(),
    {
        editable: true,
        name: 'Drawing',
    },
);

const emit = defineEmits<{
    'update:blocks': [value: RteBlock[]];
}>();

const sortedBlocks = computed<RteBlock[]>(() =>
    [...props.blocks].sort((a, b) => a.position - b.position),
);

function updateBlocks(blocks: RteBlock[]): void {
    emit('update:blocks', blocks);
}

function updateBlockPayload(
    blockId: number,
    payload: TextPayload | ExcalidrawPayload,
): void {
    updateBlocks(
        props.blocks.map((b) => (b.id === blockId ? { ...b, payload } : b)),
    );
}

function removeBlock(blockId: number): void {
    updateBlocks(props.blocks.filter((b) => b.id !== blockId));
}

function moveBlock(blockId: number, direction: -1 | 1): void {
    const sorted = [...sortedBlocks.value];
    const index = sorted.findIndex((b) => b.id === blockId);

    if (index === -1) {
        return;
    }

    const targetIndex = index + direction;

    if (targetIndex < 0 || targetIndex >= sorted.length) {
        return;
    }

    const updated = [...props.blocks];
    const blockA = updated.find((b) => b.id === sorted[index].id);
    const blockB = updated.find((b) => b.id === sorted[targetIndex].id);

    if (!blockA || !blockB) {
        return;
    }

    const tempPos = blockA.position;
    blockA.position = blockB.position;
    blockB.position = tempPos;

    updateBlocks(updated);
}

function addBlock(type: RteBlockType, afterPosition: number = -1): void {
    let newBlocks: RteBlock[];

    if (afterPosition >= 0) {
        const updated = props.blocks.map((b) =>
            b.position > afterPosition ? { ...b, position: b.position + 1 } : b,
        );

        newBlocks = [
            ...updated,
            {
                id: Date.now() * -1,
                type,
                position: afterPosition + 1,
                payload: type === 'text' ? { content: '' } : null,
            },
        ];
    } else if (props.blocks.length > 0) {
        const updated = props.blocks.map((b) => ({
            ...b,
            position: b.position + 1,
        }));

        newBlocks = [
            ...updated,
            {
                id: Date.now() * -1,
                type,
                position: 0,
                payload: type === 'text' ? { content: '' } : null,
            },
        ];
    } else {
        newBlocks = [
            {
                id: Date.now() * -1,
                type,
                position: 0,
                payload: type === 'text' ? { content: '' } : null,
            },
        ];
    }

    updateBlocks(newBlocks);
}

function canMoveUp(blockId: number): boolean {
    const sorted = sortedBlocks.value;
    const index = sorted.findIndex((b) => b.id === blockId);

    return index > 0;
}

function canMoveDown(blockId: number): boolean {
    const sorted = sortedBlocks.value;
    const index = sorted.findIndex((b) => b.id === blockId);

    return index < sorted.length - 1;
}

function textPayload(block: RteBlock): TextPayload | null {
    return block.type === 'text' ? (block.payload as TextPayload | null) : null;
}

function excalidrawPayload(block: RteBlock): ExcalidrawPayload | null {
    return block.type === 'excalidraw'
        ? (block.payload as ExcalidrawPayload | null)
        : null;
}
</script>

<template>
    <div>
        <div
            v-if="editable && sortedBlocks.length > 0"
            class="flex justify-center py-3"
        >
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 rounded-full text-foreground"
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center">
                    <DropdownMenuItem @click="addBlock('text', -1)">
                        <FileText class="mr-2 h-4 w-4" />
                        Text block
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="addBlock('excalidraw', -1)">
                        <Paintbrush class="mr-2 h-4 w-4" />
                        Drawing block
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <template v-for="(block, index) in sortedBlocks" :key="block.id">
            <div :class="index > 0 ? 'mt-4' : ''" class="mb-4">
                <div v-if="editable" class="mb-2 flex items-center gap-1">
                    <span
                        class="mr-auto text-xs font-medium text-muted-foreground"
                    >
                        {{ block.type === 'text' ? 'Text' : 'Drawing' }}
                    </span>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="!canMoveUp(block.id)"
                        title="Move up"
                        @click="moveBlock(block.id, -1)"
                    >
                        <ArrowUp class="h-3 w-3" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="!canMoveDown(block.id)"
                        title="Move down"
                        @click="moveBlock(block.id, 1)"
                    >
                        <ArrowDown class="h-3 w-3" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 text-destructive hover:text-destructive"
                        title="Remove block"
                        @click="removeBlock(block.id)"
                    >
                        <Trash2 class="h-3 w-3" />
                    </Button>
                </div>

                <TextBlock
                    v-if="block.type === 'text'"
                    :payload="textPayload(block)"
                    :editable="editable"
                    @update:payload="(p) => updateBlockPayload(block.id, p)"
                />
                <ExcalidrawBlock
                    v-else-if="block.type === 'excalidraw'"
                    :payload="excalidrawPayload(block)"
                    :editable="editable"
                    :name="name"
                    @update:payload="(p) => updateBlockPayload(block.id, p)"
                />
            </div>

            <div v-if="editable" class="flex justify-center py-3">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-6 w-6 rounded-full text-foreground"
                        >
                            <Plus class="h-3.5 w-3.5" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="center">
                        <DropdownMenuItem
                            @click="addBlock('text', block.position)"
                        >
                            <FileText class="mr-2 h-4 w-4" />
                            Text block
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            @click="addBlock('excalidraw', block.position)"
                        >
                            <Paintbrush class="mr-2 h-4 w-4" />
                            Drawing block
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </template>

        <div
            v-if="editable && sortedBlocks.length === 0"
            class="flex justify-center"
        >
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 rounded-full text-foreground"
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center">
                    <DropdownMenuItem @click="addBlock('text')">
                        <FileText class="mr-2 h-4 w-4" />
                        Text block
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="addBlock('excalidraw')">
                        <Paintbrush class="mr-2 h-4 w-4" />
                        Drawing block
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div
            v-if="!editable && sortedBlocks.length === 0"
            class="py-4 text-muted-foreground italic"
        >
            No content yet.
        </div>
    </div>
</template>
