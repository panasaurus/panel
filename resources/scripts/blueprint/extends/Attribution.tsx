import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';

/**
 * Blueprint framework attribution — rendered in the dashboard sidebar.
 * Administrators can hide this from Blueprint's own admin settings page.
 */
export default () => {
    const disable_attribution = useStoreState(
        (state: ApplicationStore) => state.settings.data?.blueprint?.disable_attribution ?? false,
    );

    return (
        <>
            {!disable_attribution && (
                <div className='px-4 py-2 text-[11px] leading-4 text-zinc-600 select-none'>
                    <a
                        rel={'noopener nofollow noreferrer'}
                        href={'https://blueprint.zip'}
                        target={'_blank'}
                        className={'no-underline text-zinc-500 transition-colors hover:text-zinc-300'}
                    >
                        Powered by Blueprint
                    </a>
                    &nbsp;&copy; 2023 - {new Date().getFullYear()}
                </div>
            )}
        </>
    );
};
