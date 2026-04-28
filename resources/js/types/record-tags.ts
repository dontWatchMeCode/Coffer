export type RecordTag = {
    id: number;
    name: string;
    slug: string;
};

export type TagContext = {
    type: string;
    id: number;
    title: string;
};

export type TagEndpoints = {
    candidates: string;
    store: string;
    destroy: string;
};
