import { type ResponsiveImageAsset } from '@/types';

export interface LandingWorldCardData {
    number: number;
    slug: string;
    title: string;
    description: string;
    skills: string[];
    themeColour: string;
    accentColour: string;
    href: string;
    image: ResponsiveImageAsset;
}

export interface LandingPathWorld {
    number: number;
    slug: string;
    title: string;
    shortDescription: string | null;
    themeColour: string;
    accentColour: string;
}
