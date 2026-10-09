// Panasaurus brand mark — a chunky geometric dinosaur built from primitives.
// million-ignore

interface MarkProps {
    className?: string;
    uniqueId?: string;
}

/**
 * The Panasaurus dinosaur mark. Rendered as a single flat silhouette so it
 * stays crisp at any size, from favicons to the sidebar.
 */
export const SaurusMark = ({ className }: MarkProps) => {
    return (
        <svg
            xmlns='http://www.w3.org/2000/svg'
            className={className || 'h-full w-full'}
            viewBox='0 0 48 48'
            fill='none'
            aria-hidden='true'
        >
            {/* tail */}
            <path
                d='M2 40 L12 30 L18 36 L10 44 Q5 46 2 40 Z'
                fill='currentColor'
            />
            {/* body */}
            <rect x='10' y='20' width='24' height='17' rx='8.5' fill='currentColor' />
            {/* back haunch */}
            <circle cx='17' cy='31' r='6.5' fill='currentColor' />
            {/* neck */}
            <rect x='27' y='10' width='11' height='18' rx='5.5' fill='currentColor' />
            {/* head */}
            <rect x='30' y='9' width='15' height='10' rx='4.5' fill='currentColor' />
            {/* snout step */}
            <rect x='38' y='15' width='7' height='5' rx='2.5' fill='currentColor' />
            {/* eye */}
            <circle cx='38.5' cy='13' r='1.4' fill='#0B1220' />
            {/* front leg */}
            <rect x='13' y='33' width='5.5' height='11' rx='2.75' fill='currentColor' />
            {/* back leg */}
            <rect x='24' y='33' width='5.5' height='11' rx='2.75' fill='currentColor' />
        </svg>
    );
};

interface LogoProps {
    className?: string;
    uniqueId?: string;
    /** Hide the wordmark and only show the mark. */
    markOnly?: boolean;
}

/**
 * Full Panasaurus logo: dinosaur mark + wordmark.
 */
const Logo = ({ className, markOnly }: LogoProps) => {
    return (
        <span className={`inline-flex items-center gap-2 ${className || ''}`}>
            <span
                className='inline-block shrink-0'
                style={{
                    width: markOnly ? '100%' : '1.75rem',
                    height: '1.75rem',
                    background: 'linear-gradient(135deg, #3EE6A0 0%, #0E9F6E 100%)',
                    WebkitMask: 'url("data:image/svg+xml;utf8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 48 48\'%3E%3Cpath d=\'M2 40 L12 30 L18 36 L10 44 Q5 46 2 40 Z\' fill=\'black\'/%3E%3Crect x=\'10\' y=\'20\' width=\'24\' height=\'17\' rx=\'8.5\' fill=\'black\'/%3E%3Ccircle cx=\'17\' cy=\'31\' r=\'6.5\' fill=\'black\'/%3E%3Crect x=\'27\' y=\'10\' width=\'11\' height=\'18\' rx=\'5.5\' fill=\'black\'/%3E%3Crect x=\'30\' y=\'9\' width=\'15\' height=\'10\' rx=\'4.5\' fill=\'black\'/%3E%3Crect x=\'38\' y=\'15\' width=\'7\' height=\'5\' rx=\'2.5\' fill=\'black\'/%3E%3Crect x=\'13\' y=\'33\' width=\'5.5\' height=\'11\' rx=\'2.75\' fill=\'black\'/%3E%3Crect x=\'24\' y=\'33\' width=\'5.5\' height=\'11\' rx=\'2.75\' fill=\'black\'/%3E%3C/svg%3E") center / contain no-repeat',
                    mask: 'url("data:image/svg+xml;utf8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 48 48\'%3E%3Cpath d=\'M2 40 L12 30 L18 36 L10 44 Q5 46 2 40 Z\' fill=\'black\'/%3E%3Crect x=\'10\' y=\'20\' width=\'24\' height=\'17\' rx=\'8.5\' fill=\'black\'/%3E%3Ccircle cx=\'17\' cy=\'31\' r=\'6.5\' fill=\'black\'/%3E%3Crect x=\'27\' y=\'10\' width=\'11\' height=\'18\' rx=\'5.5\' fill=\'black\'/%3E%3Crect x=\'30\' y=\'9\' width=\'15\' height=\'10\' rx=\'4.5\' fill=\'black\'/%3E%3Crect x=\'38\' y=\'15\' width=\'7\' height=\'5\' rx=\'2.5\' fill=\'black\'/%3E%3Crect x=\'13\' y=\'33\' width=\'5.5\' height=\'11\' rx=\'2.75\' fill=\'black\'/%3E%3Crect x=\'24\' y=\'33\' width=\'5.5\' height=\'11\' rx=\'2.75\' fill=\'black\'/%3E%3C/svg%3E") center / contain no-repeat',
                }}
            />
            {!markOnly && (
                <span className='text-[1.35rem] font-bold leading-none tracking-tight text-white select-none'>
                    Panasaurus
                </span>
            )}
        </span>
    );
};

export default Logo;
