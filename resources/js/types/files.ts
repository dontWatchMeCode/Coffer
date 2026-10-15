export type FileItem = {
    id: number;
    title: string;
    description?: string | null;
    originalName: string;
    mimeType: string;
    size: number;
    width?: number | null;
    height?: number | null;
    previewKind: 'image' | 'video' | 'text' | null;
    previewUrl: string | null;
    thumbnailUrl: string | null;
    // Detail payloads only; absent on lists.
    storyboardUrl?: string | null;
    textPreview?: { content: string; truncated: boolean } | null;
    downloadUrl: string;
    createdAt?: string | null;
    updatedAt?: string | null;
    deletedAt?: string | null;
};

export type FileUploadConstraints = {
    acceptedMimeTypes: string[];
    acceptedExtensions: string[];
    maxKilobytes: number;
    maxMegabytes: number;
};
