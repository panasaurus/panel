import clsx from 'clsx';
import { JSX } from 'react';
import styled from 'styled-components';

const HeaderWrapper = styled.div``;

interface MainPageHeaderProps {
    children?: React.ReactNode;
    direction?: 'row' | 'column';
    titleChildren?: JSX.Element;
    title?: string;
    headChildren?: JSX.Element;
    hideTitle?: boolean;
}

export const MainPageHeader: React.FC<MainPageHeaderProps> = ({
    children,
    headChildren,
    titleChildren,
    title,
    direction = 'row',
    hideTitle = false,
}) => {
    if (hideTitle) {
        if (!headChildren && !children && !titleChildren) {
            return <h1 className='sr-only'>{title}</h1>;
        }

        return (
            <HeaderWrapper className='main-page-header-compact mb-8 mt-8 md:mt-0 select-none'>
                <h1 className='sr-only'>{title}</h1>
                <div
                    className={clsx(
                        'flex flex-col gap-4 sm:flex-row sm:items-start',
                        headChildren || children ? 'sm:justify-between' : 'sm:justify-end',
                    )}
                >
                    {(headChildren || (direction === 'column' && children)) && (
                        <div className='min-w-0 flex-1'>
                            {headChildren}
                            {direction === 'column' && children}
                        </div>
                    )}
                    {titleChildren && <div className='w-full sm:w-auto sm:shrink-0'>{titleChildren}</div>}
                </div>
            </HeaderWrapper>
        );
    }

    return (
        <HeaderWrapper className={clsx('flex flex-col', 'mb-8 gap-4 md:gap-8 mt-8 md:mt-0 select-none')}>
            <div
                className={clsx(
                    'flex',
                    direction === 'row'
                        ? 'flex-col sm:flex-row sm:items-center'
                        : 'flex-col sm:flex-row sm:items-start',
                    'justify-between',
                    'gap-4',
                )}
            >
                <div className='flex items-center gap-4 min-w-0 flex-wrap flex-1'>
                    <h1 className=' text-2xl sm:text-3xl md:text-4xl lg:text-[52px] font-extrabold leading-[98%] tracking-[-0.02em] sm:tracking-[-0.06em] md:tracking-[-0.14rem] break-words'>
                        {title}
                    </h1>
                    <div className=''>{headChildren}</div>
                </div>
                {titleChildren && <div className='flex-shrink-0 w-full sm:w-auto'>{titleChildren}</div>}
            </div>
            {direction === 'column' && children && <div className='-mt-2 md:-mt-4'>{children}</div>}
        </HeaderWrapper>
    );
};
