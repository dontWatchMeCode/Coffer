export type ContactEntry = {
    label: string;
    value: string;
};

export type ContactItem = {
    id: number;
    name: string;
    phoneNumbers?: ContactEntry[] | null;
    emailAddresses?: ContactEntry[] | null;
    address?: string | null;
    additionalInfo?: string | null;
    createdAt?: string | null;
    updatedAt?: string | null;
};
