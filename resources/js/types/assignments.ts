export interface SelectOption {
    value: string;
    label: string;
}

export interface AssignmentItemOption extends Record<string, FormDataConvertible> {
    id?: number;
    option_text: string | null;
    image_path?: string | null;
    option_value: string | null;
    matching_key?: string | null;
    is_correct?: boolean;
    display_order: number;
    text?: string | null;
    value?: string | null;
    image?: string | null;
}

export interface AssignmentItem extends Record<string, FormDataConvertible> {
    id?: number;
    html_exercise_version_id?: number | null;
    project_template_version_id?: number | null;
    title: string;
    prompt_text: string | null;
    audio_prompt_path?: string | null;
    image_path?: string | null;
    question_type: string;
    interaction_type: string;
    points: number;
    is_required: boolean;
    hint_text?: string | null;
    hint_audio_path?: string | null;
    explanation_text?: string | null;
    display_order: number;
    configuration?: Record<string, FormDataConvertible>;
    grading_configuration?: Record<string, FormDataConvertible>;
    options?: AssignmentItemOption[];
    left_items?: AssignmentItemOption[];
    right_items?: AssignmentItemOption[];
    items?: AssignmentItemOption[];
    placeholder?: string;
    answer_mode?: 'single' | 'multiple';
}

export interface AssignmentVersion {
    id: number;
    assignment_id: number;
    version_number: number;
    title: string;
    short_description: string | null;
    child_instructions: string | null;
    teacher_instructions: string | null;
    audio_instruction_path: string | null;
    estimated_minutes: number;
    difficulty_level: string;
    total_points: number;
    default_attempt_limit: number;
    feedback_mode: string;
    scoring_method: string;
    status: string;
    published_at: string | null;
    settings: Record<string, FormDataConvertible>;
    assignment_type?: string | null;
    items: AssignmentItem[];
    curriculum_links: Array<Record<string, number | null>>;
    skills: Array<{ id: number; name: string; slug?: string; category?: string; emphasis_level?: number }>;
}

export interface AssignmentRecord {
    id: number;
    assignment_type: string;
    assignment_type_label: string;
    status: string;
    current_version_id: number | null;
    versions_count: number;
    owner_name: string | null;
    creator_name: string | null;
    current_version: AssignmentVersion | null;
}

export interface AssignmentAttempt {
    id: number;
    assignment_allocation_id: number;
    assignment_version_id: number;
    child_id: number;
    attempt_number: number;
    status: string;
    submitted_at: string | null;
    auto_score: number;
    manual_score: number;
    final_score: number;
    maximum_score: number;
    is_late: boolean;
    assignment_title: string | null;
    child_name: string | null;
    responses: Array<{
        id: number;
        assignment_item_id: number;
        text_response: string | null;
        response_data: Record<string, FormDataConvertible> | null;
        is_correct: boolean | null;
        auto_score: number;
        manual_score: number;
        teacher_comment: string | null;
    }>;
    feedback: Array<{
        id: number;
        feedback_text: string;
        feedback_type: string;
        returned_for_retry: boolean;
        visible_to_child: boolean;
        visible_to_parent: boolean;
        teacher_name?: string | null;
    }>;
}

export interface AssignmentAllocation {
    id: number;
    assignment_version_id: number;
    class_id: number | null;
    group_id: number | null;
    child_id: number | null;
    available_from: string | null;
    due_at: string | null;
    closes_at: string | null;
    attempt_limit: number | null;
    scoring_method: string | null;
    show_score_to_child: boolean;
    show_correct_answers: boolean;
    allow_late_submission: boolean;
    late_submission_policy: string | null;
    status: string;
    target_label: string;
    assignment_title: string | null;
    attempts_count: number;
}
import type { FormDataConvertible } from '@inertiajs/core';
