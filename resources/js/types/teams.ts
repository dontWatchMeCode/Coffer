export type TeamRole = 'owner' | 'admin' | 'member';

/** Keep in sync with App\Enums\TeamFeature. */
export type TeamFeatureKey =
    | 'tasks'
    | 'calendar'
    | 'contacts'
    | 'bookmarks'
    | 'subscriptions'
    | 'notes'
    | 'files'
    | 'log'
    | 'collections'
    | 'spreadsheets';

export type Team = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: TeamRole;
    roleLabel?: string;
    isCurrent?: boolean;
    defaultTaskStatusOptions?: { value: string; label: string }[];
    featureSettings?: Partial<Record<TeamFeatureKey, boolean>>;
};

export type TeamFeatureOption = {
    value: TeamFeatureKey;
    label: string;
};

export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
