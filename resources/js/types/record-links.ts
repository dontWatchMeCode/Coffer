export type LinkRecord = {
    id: number;
    type: string;
    title: string;
    url: string;
};

export type LinkContext = {
    type: string;
    id: number;
    title: string;
};

export type LinkEndpoints = {
    candidates: string;
    store: string;
    destroy: string;
};
