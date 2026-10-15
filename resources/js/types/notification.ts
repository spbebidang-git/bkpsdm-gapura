export type ParticipantNotificationType = 'completed' | 'certificate' | 'deadline' | 'expired' | 'grace';

export type ParticipantNotification = {
    id: string;
    type: ParticipantNotificationType;
    title: string;
    message: string;
    read_at: string | null;
    created_at: string;
    deadline_at?: string | null;
    action_url: string | null;
    action_label: string | null;
};

export type NotificationCountResponse = { unread_count: number };
export type NotificationLatestResponse = { notifications: ParticipantNotification[]; unread_count: number };
export type PaginatedNotifications = {
    data: ParticipantNotification[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};
