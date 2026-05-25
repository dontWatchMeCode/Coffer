<script setup lang="ts">
import { Head, InfiniteScroll } from '@inertiajs/vue3';
import SearchInput from '@/components/list/SearchInput.vue';
import PageHeader from '@/components/page/PageHeader.vue';
import TrashRecordList from '@/components/pages/trash/TrashRecordList.vue';
import type { TrashRecord } from '@/components/pages/trash/TrashRecordList.vue';

type Props = {
    title: string;
    description: string;
    backHref: string;
    backLabel: string;
    scrollData: string;
    records: TrashRecord[];
    moduleName: string;
    restoreUrl: (record: TrashRecord) => string;
    forceDeleteUrl: (record: TrashRecord) => string;
};

defineProps<Props>();

const searchQuery = defineModel<string>('searchQuery', { required: true });
</script>

<template>
    <Head :title="title" />

    <PageHeader
        :title="title"
        :description="description"
        :back-href="backHref"
        :back-label="backLabel"
    />

    <div class="min-w-0 flex-1 px-4 py-6">
        <div class="mx-auto w-full max-w-7xl space-y-4">
            <div class="flex items-center justify-end">
                <SearchInput
                    v-model="searchQuery"
                    placeholder="Search trash..."
                />
            </div>

            <InfiniteScroll :data="scrollData">
                <TrashRecordList
                    :records="records"
                    :search-query="searchQuery"
                    :module-name="moduleName"
                    :restore-url="restoreUrl"
                    :force-delete-url="forceDeleteUrl"
                />
            </InfiniteScroll>
        </div>
    </div>
</template>
