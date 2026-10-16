const LOGO_URL = '/assets/branding/panasaurus-logo.png?v=20261016';

interface MarkProps {
  dead?: boolean;
  className?: string;
  uniqueId?: string;
}

export const SaurusMark = ({ className }: MarkProps) => (
  <img
    src={LOGO_URL}
    alt=""
    aria-hidden="true"
    className={className || 'h-full w-full'}
    style={{ objectFit: 'contain' }}
    draggable={false}
  />
);

interface LogoProps {
  className?: string;
  uniqueId?: string;
  markOnly?: boolean;
}

const Logo = ({ className, markOnly }: LogoProps) => (
  <span className={`inline-flex items-center gap-2 ${className || ''}`}>
    <img
      src={LOGO_URL}
      alt={markOnly ? 'Panasaurus' : ''}
      className="inline-block shrink-0 object-contain"
      style={{ width: markOnly ? '100%' : '2.25rem', height: '1.75rem' }}
      draggable={false}
    />
    {!markOnly && (
      <span className="text-[1.35rem] font-bold leading-none tracking-tight text-white select-none">
        Panasaurus
      </span>
    )}
  </span>
);

export default Logo;
