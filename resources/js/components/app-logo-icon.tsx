import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
            <path d="M14 18c-4.4 0-8 3.6-8 8v12c0 4.4 3.6 8 8 8" fill="none" stroke="currentColor" strokeLinecap="round" strokeWidth="4" />
            <path d="M50 18c4.4 0 8 3.6 8 8v12c0 4.4-3.6 8-8 8" fill="none" stroke="currentColor" strokeLinecap="round" strokeWidth="4" />
            <path
                d="M25 34c0-7 5.4-12 12-12h2c2.8 0 5 2.2 5 5 0 8.3-6.7 15-15 15h-4"
                fill="none"
                stroke="currentColor"
                strokeLinecap="round"
                strokeWidth="4"
            />
            <path d="M30 31c-2.7-3.1-2.2-7.8 1.2-10.8 3.4-3 8.3-2.8 11.1.3-1 5.6-5.7 10.1-12.3 10.5Z" fill="#63D47D" />
            <path d="M31.5 22v18" fill="none" stroke="#1F8A52" strokeLinecap="round" strokeWidth="3" />
        </svg>
    );
}
