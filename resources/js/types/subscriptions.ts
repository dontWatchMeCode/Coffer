export type SubscriptionCategory = {
    id: number;
    name: string;
    slug: string;
};

export type SubscriptionItem = {
    id: number;
    name: string;
    price?: string | null;
    currency?: string | null;
    billingCycle?: string | null;
    nextBillingDate?: string | null;
    url?: string | null;
    description?: string | null;
    notes?: string | null;
    isActive: boolean;
    category?: string | null;
    categoryId?: number | null;
    createdAt?: string | null;
    updatedAt?: string | null;
};
