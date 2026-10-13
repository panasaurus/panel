import { Moon } from '@gravity-ui/icons';

import { ServerContext } from '@/state/server';

const HibernationStatus = () => {
    const hibernation = ServerContext.useStoreState((state) => state.hibernation.value);
    const connected = ServerContext.useStoreState((state) => state.socket.connected);
    const status = ServerContext.useStoreState((state) => state.status.value);
    const phase = hibernation?.phase || (hibernation?.sleeping ? 'sleeping' : 'awake');
    const idleMinutes = Math.ceil((hibernation?.idle_timeout_seconds || 600) / 60);
    let label = 'Checking';
    let message = 'Connecting to Wings to check hibernation status.';
    let color = 'text-zinc-400';

    if (hibernation && !connected) {
        label = 'Reconnecting';
        message = 'Waiting for Wings to confirm the current hibernation status.';
    } else if (hibernation && !hibernation.enabled) {
        label = 'Disabled';
        message = 'Hibernation is not enabled for this server.';
    } else if (hibernation?.enabled) {
        color = 'text-emerald-300';
        if (hibernation.error) {
            label = 'Needs attention';
            message = hibernation.error;
            color = 'text-amber-300';
        } else if (phase === 'stopping') {
            label = 'Saving';
            message = 'No players are online. Saving the world before going to sleep…';
        } else if (phase === 'waking') {
            label = 'Waking up';
            message = 'Oops! Panasaurus was asleep. Please wait while we boot the server.';
        } else if (phase === 'sleeping') {
            label = 'Sleeping';
            message = 'Panasaurus is asleep. Connecting to the server will wake it up automatically.';
        } else if (status === 'offline') {
            label = 'Offline';
            message = 'The server was stopped manually. Start it from the panel to resume automatic hibernation.';
            color = 'text-zinc-400';
        } else {
            label = 'Enabled';
            message = `Sleeps after ${idleMinutes} ${idleMinutes === 1 ? 'minute' : 'minutes'} with no players online. Connect to wake it up.`;
        }
    }

    return (
        <div role='status' aria-live='polite' data-hibernation-status={label}
            className='flex items-start gap-3 border-b border-[#ffffff12] bg-[#111111] px-3 py-3 sm:px-4'>
            <Moon width={18} height={18} className={`mt-0.5 shrink-0 ${color}`} aria-hidden />
            <div className='min-w-0'>
                <div className='flex flex-wrap items-center gap-x-2 gap-y-1 text-sm'>
                    <span className='font-medium text-zinc-200'>Hibernation</span>
                    <span className={`rounded-full border border-current/20 px-2 py-0.5 text-xs ${color}`}>{label}</span>
                </div>
                <p className='mt-1 text-xs leading-relaxed text-zinc-400'>{message}</p>
            </div>
        </div>
    );
};

export default HibernationStatus;
