/** Shared frontend contracts for data returned by Laravel/Inertia. */
export type Id = number | string;
export type ActivityType = 'pre-test' | 'post-test' | 'task' | 'meeting' | 'video' | 'literature' | 'variant' | 'question-item' | string;

export interface Category { id: Id; name: string; slug?: string; [key: string]: unknown; }
export interface Course { id: Id; title: string; slug: string; short_description?: string | null; description?: string | null; thumbnail?: string | null; category?: string | Category | null; employee_status?: string | null; star?: number | string | null; total_participant?: number | string | null; total_passed?: number | string | null; [key: string]: unknown; }
export interface Schedule { id: Id; start_at?: string | null; end_at?: string | null; date?: string | null; [key: string]: unknown; }
export interface Activity { id: Id; type: ActivityType; title?: string | null; description?: string | null; order?: number; [key: string]: unknown; }
export interface Question { id: Id; question?: string | null; option?: Record<string, string | null> | null; option1?: string | null; option2?: string | null; option3?: string | null; option4?: string | null; answer?: string | null; is_correct?: boolean | number; score?: number | string | null; [key: string]: unknown; }
export interface Variant { id: Id; title?: string | null; questions?: Question[]; [key: string]: unknown; }
export interface Trainer { id: Id; name: string; email?: string | null; [key: string]: unknown; }
export interface Result { id?: Id; user?: { id: Id; name: string; [key: string]: unknown }; score?: number | string | null; [key: string]: unknown; }
export interface Paginated<T> { data: T[]; current_page?: number; last_page?: number; total?: number; per_page?: number; [key: string]: unknown; }
export interface FormErrors { [field: string]: string | string[] | undefined; }
export interface ActivityProgress { [activityId: string]: number | boolean | string | null | undefined; }
export type SelectOption = { value: string | number; label: string };
