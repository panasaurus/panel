import { forwardRef } from 'react';

interface ActionButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'secondary' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    children: React.ReactNode;
}

const ActionButton = forwardRef<HTMLButtonElement, ActionButtonProps>(
    ({ variant = 'primary', size = 'md', className = '', children, ...props }, ref) => {
        const baseClasses =
            'inline-flex cursor-pointer items-center justify-center whitespace-nowrap rounded-lg font-medium transition-all focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand/60 focus-visible:ring-offset-2 focus-visible:ring-offset-black disabled:opacity-50 disabled:cursor-not-allowed';

        const variantClasses = {
            primary: 'bg-brand text-[#002317] hover:bg-[#45dda4] active:bg-brand/90 border border-brand',
            secondary:
                'bg-[#18181b] text-[#d4d4d8] hover:bg-[#303034] hover:text-[#f4f4f5] border border-[#3f3f46]',
            danger: 'bg-[#34191e] text-[#fca5a5] hover:bg-[#54222b] hover:text-[#fecaca] border border-[#71333e] hover:border-[#f87171]',
        };

        const sizeClasses = {
            sm: 'h-8 px-3 py-1.5 text-xs',
            md: 'h-10 px-4 py-2 text-sm',
            lg: 'h-12 px-6 py-3 text-base',
        };

        return (
            <button
                ref={ref}
                className={`${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`}
                {...props}
            >
                {children}
            </button>
        );
    },
);

ActionButton.displayName = 'ActionButton';

export default ActionButton;
