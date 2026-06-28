export type FileItem = {
    id: number;
    title: string;
    description?: string | null;
    originalName: string;
    mimeType: string;
    size: number;
    width?: number | null;
    height?: number | null;
    isImage: boolean;
    previewUrl: string;
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
