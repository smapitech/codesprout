import { cn } from '@/lib/utils';
import { type ResponsiveImageAsset } from '@/types';

interface OptimizedPictureProps {
    asset: ResponsiveImageAsset;
    alt?: string;
    className?: string;
    imgClassName?: string;
    loading?: 'eager' | 'lazy';
    sizes?: string;
    decorative?: boolean;
}

export function OptimizedPicture({
    asset,
    alt = asset.alt,
    className,
    imgClassName,
    loading,
    sizes = '100vw',
    decorative = false,
}: OptimizedPictureProps) {
    const resolvedAlt = decorative ? '' : alt;
    const priority = asset.priority ?? false;

    return (
        <picture className={cn('block overflow-hidden', className)} style={{ aspectRatio: `${asset.width} / ${asset.height}` }}>
            {asset.avif && <source type="image/avif" srcSet={asset.avif} sizes={sizes} />}
            {asset.webp && <source type="image/webp" srcSet={asset.webp} sizes={sizes} />}
            <img
                src={asset.png}
                alt={resolvedAlt}
                width={asset.width}
                height={asset.height}
                loading={loading ?? (priority ? 'eager' : 'lazy')}
                decoding="async"
                fetchPriority={priority ? 'high' : 'auto'}
                className={cn('h-full w-full', imgClassName)}
                style={{ objectFit: asset.fit ?? 'cover' }}
            />
        </picture>
    );
}
