import type { ReactNode } from 'react';
import type { router } from '@inertiajs/react';
import type { FormDataConvertible } from '@inertiajs/core';

export type LearnStage = 'instruction' | 'material' | 'result';
export type LearnActivityType = 'pre-test' | 'post-test' | 'task' | 'meeting' | 'video' | 'literature';
export type LearnStatus = 'done' | 'in_progress' | number | boolean | null;
export type LearnId = string | number;

export interface LearnAgendaItem {
    locked?: boolean;
    id: LearnId;
    title: string;
    type: LearnActivityType;
    status?: LearnStatus;
    value?: string | number | null;
}

export interface LearnActivity {
    activity_id: LearnId;
    title: string;
    type: LearnActivityType;
    status?: LearnStatus;
    timer?: number;
    data?: Record<string, unknown>;
}

export interface LearnLayoutProps {
    type: LearnStage;
    course: { slug: string; title: string };
    progress: string | number;
    activity: LearnActivity;
    activities?: LearnAgendaItem[];
    schedule: { id: LearnId };
    children?: ReactNode;
    timer?: number;
    displayTimerSeconds?: number;
    activity_id?: LearnId;
    formattedAnswers?: Parameters<typeof router.post>[1] | Record<string, FormDataConvertible>[];
}
