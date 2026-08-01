import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote?: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface UserProfile {
    first_name: string | null;
    last_name: string | null;
    preferred_name: string | null;
    full_name: string | null;
    display_name: string | null;
    age: number | null;
    date_of_birth: string | null;
    avatar_url: string | null;
}

export interface ResponsiveImageAsset {
    name?: string;
    alt: string;
    width: number;
    height: number;
    fit?: 'cover' | 'contain';
    priority?: boolean;
    png: string;
    webp?: string | null;
    avif?: string | null;
}

export interface ChildProfileData {
    learner_id: string;
    pin_mode: string;
    last_pin_verified_at: string | null;
}

export interface TeacherProfileData {
    staff_code: string;
    job_title: string | null;
    subject_focus: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string | null;
    avatar_url: string | null;
    dashboard_route: string;
    primary_role: string | null;
    is_active: boolean;
    roles: string[];
    profile: UserProfile | null;
    child_profile: ChildProfileData | null;
    teacher_profile: TeacherProfileData | null;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown;
}
