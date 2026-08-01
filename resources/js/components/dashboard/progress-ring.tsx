interface ProgressRingProps {
    value: number;
    size?: number;
    strokeWidth?: number;
    label: string;
    sublabel?: string;
    color?: string;
}

export function ProgressRing({ value, size = 164, strokeWidth = 14, label, sublabel, color = '#15a37d' }: ProgressRingProps) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (value / 100) * circumference;

    return (
        <div className="flex flex-col items-center justify-center">
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} className="-rotate-90">
                <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="rgba(21, 163, 125, 0.12)" strokeWidth={strokeWidth} />
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    fill="none"
                    stroke={color}
                    strokeLinecap="round"
                    strokeWidth={strokeWidth}
                    strokeDasharray={circumference}
                    strokeDashoffset={strokeDashoffset}
                />
            </svg>
            <div className="-mt-[calc(50%+1rem)] text-center">
                <div className="text-4xl font-black tracking-tight text-[var(--foreground)]">{value}%</div>
                <div className="text-muted-foreground mt-1 text-sm font-semibold">{label}</div>
                {sublabel && <div className="text-muted-foreground mt-1 text-xs">{sublabel}</div>}
            </div>
        </div>
    );
}
