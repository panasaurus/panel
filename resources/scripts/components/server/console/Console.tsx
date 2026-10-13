import { FitAddon } from '@xterm/addon-fit';
import { SearchAddon } from '@xterm/addon-search';
import { WebLinksAddon } from '@xterm/addon-web-links';
import { ITerminalOptions, Terminal } from '@xterm/xterm';
import '@xterm/xterm/css/xterm.css';
import debounce from 'debounce';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import HibernationStatus from '@/components/server/console/HibernationStatus';
import { SocketEvent, SocketRequest } from '@/components/server/events';

import { ServerContext } from '@/state/server';

import { ConsoleOutputBuffer } from '@/plugins/ConsoleOutputBuffer';
import useEventListener from '@/plugins/useEventListener';
import { usePermissions } from '@/plugins/usePermissions';
import { usePersistedState } from '@/plugins/usePersistedState';

const theme = {
    background: '#131313',
    cursor: 'transparent',
    black: '#000000',
    red: '#E54B4B',
    green: '#9ECE58',
    yellow: '#FAED70',
    blue: '#396FE2',
    magenta: '#BB80B3',
    cyan: '#2DDAFD',
    white: '#d0d0d0',
    brightBlack: 'rgba(255, 255, 255, 0.2)',
    brightRed: '#FF5370',
    brightGreen: '#C3E88D',
    brightYellow: '#FFCB6B',
    brightBlue: '#82AAFF',
    brightMagenta: '#C792EA',
    brightCyan: '#89DDFF',
    brightWhite: '#ffffff',
    selection: '#FAF089',
};

const terminalProps: ITerminalOptions = {
    disableStdin: true,
    cursorStyle: 'underline',
    allowTransparency: true,
    fontSize: window.innerWidth < 640 ? 11 : 12,
    fontFamily: 'ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace',
    theme: theme,
};

const Console = () => {
    const TERMINAL_PRELUDE = '\u001b[1m\u001b[33mcontainer@panasaurus~ \u001b[0m';
    const ref = useRef<HTMLDivElement>(null);
    const terminal = useMemo(
        () =>
            new Terminal({
                ...terminalProps,
                rows: window.innerWidth < 640 ? 20 : 25,
            }),
        [],
    );
    const output = useMemo(() => new ConsoleOutputBuffer((data, callback) => terminal.write(data, callback)), [terminal]);
    const fitAddon = useMemo(() => new FitAddon(), []);
    const searchAddon = useMemo(() => new SearchAddon(), []);
    const webLinksAddon = useMemo(() => new WebLinksAddon(), []);
    const { connected, instance } = ServerContext.useStoreState((state) => state.socket);
    const [canSendCommands] = usePermissions(['control.console']);
    const serverId = ServerContext.useStoreState((state) => state.server.data!.id);
    const isTransferring = ServerContext.useStoreState((state) => state.server.data!.isTransferring);
    const [history, setHistory] = usePersistedState<string[]>(`${serverId}:command_history`, []);
    const [historyIndex, setHistoryIndex] = useState(-1);

    const handleConsoleOutput = useCallback((line: string, prelude = false) =>
        output.append((prelude ? TERMINAL_PRELUDE : '') + line.replace(/(?:\r\n|\r|\n)$/, '') + '\u001b[0m'), [output]);

    const handleTransferStatus = useCallback((status: string) => {
        switch (status) {
            case 'failure':
                output.append(TERMINAL_PRELUDE + 'Transfer has failed.\u001b[0m');
                return;
        }
    }, [output]);

    const handleDaemonErrorOutput = useCallback((line: string) =>
        output.append(
            TERMINAL_PRELUDE + '\u001b[1m\u001b[41m' + line.replace(/(?:\r\n|\r|\n)$/, '') + '\u001b[0m',
        ), [output]);

    const handlePowerChangeEvent = useCallback((state: string) =>
        output.append(TERMINAL_PRELUDE + 'Server marked as ' + state + '...\u001b[0m'), [output]);

    const handleCommandKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowUp') {
            const newIndex = Math.min(historyIndex + 1, history!.length - 1);

            setHistoryIndex(newIndex);
            e.currentTarget.value = history![newIndex] || '';

            e.preventDefault();
        }

        if (e.key === 'ArrowDown') {
            const newIndex = Math.max(historyIndex - 1, -1);

            setHistoryIndex(newIndex);
            e.currentTarget.value = history![newIndex] || '';
        }

        const command = e.currentTarget.value;
        if (e.key === 'Enter' && command.length > 0) {
            setHistory((prevHistory) => [command, ...prevHistory!].slice(0, 32));
            setHistoryIndex(-1);

            if (instance?.send('send command', command)) e.currentTarget.value = '';
        }
    };

    useEffect(() => {
        return () => {
            output.dispose();
            terminal.dispose();
        };
    }, [output, terminal]);

    useEffect(() => {
        if (connected && ref.current && !terminal.element) {
            terminal.loadAddon(fitAddon);
            terminal.loadAddon(searchAddon);
            terminal.loadAddon(webLinksAddon);

            terminal.open(ref.current);
            fitAddon.fit();

            terminal.attachCustomKeyEventHandler((e: KeyboardEvent) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
                    document.execCommand('copy');
                    return false;
                }
                return true;
            });
        }
    }, [terminal, connected, fitAddon, searchAddon, webLinksAddon]);

    useEventListener(
        'resize',
        debounce(() => {
            if (terminal.element) {
                const newFontSize = window.innerWidth < 640 ? 11 : 12;
                terminal.options.fontSize = newFontSize;
                fitAddon.fit();
            }
        }, 100),
    );

    useEffect(() => {
        const listeners: Record<string, (s: string) => void> = {
            [SocketEvent.STATUS]: handlePowerChangeEvent,
            [SocketEvent.CONSOLE_OUTPUT]: handleConsoleOutput,
            [SocketEvent.INSTALL_OUTPUT]: handleConsoleOutput,
            [SocketEvent.TRANSFER_LOGS]: handleConsoleOutput,
            [SocketEvent.TRANSFER_STATUS]: handleTransferStatus,
            [SocketEvent.DAEMON_MESSAGE]: (line) => handleConsoleOutput(line, true),
            [SocketEvent.DAEMON_ERROR]: handleDaemonErrorOutput,
        };

        if (connected && instance) {
            if (!isTransferring) {
                output.clear();
                terminal.clear();
            }

            Object.keys(listeners).forEach((key: string) => {
                const listener = listeners[key];
                if (listener === undefined) {
                    return;
                }

                instance.addListener(key, listener);
            });
            instance.send(SocketRequest.SEND_LOGS);
        }

        return () => {
            if (instance) {
                Object.keys(listeners).forEach((key: string) => {
                    const listener = listeners[key];
                    if (listener === undefined) {
                        return;
                    }

                    instance.removeListener(key, listener);
                });
            }
        };
    }, [connected, instance, isTransferring, output, terminal, handleConsoleOutput, handleDaemonErrorOutput, handlePowerChangeEvent, handleTransferStatus]);

    return (
        <div className='bg-gradient-to-b from-[#ffffff08] to-[#ffffff05] border-[1px] border-[#ffffff12] rounded-xl hover:border-[#ffffff20] transition-all duration-150 overflow-hidden shadow-sm'>
            <HibernationStatus />
            <div className='relative'>
                <SpinnerOverlay visible={!connected} size={'large'} />
                <div className='bg-[#131313] min-h-[280px] sm:min-h-[380px] p-3 sm:p-4 font-mono overflow-hidden'>
                    <div className='h-full w-full'>
                        <div ref={ref} className='h-full w-full' />
                    </div>
                </div>
                {canSendCommands && (
                    <div className='relative border-t-[1px] border-[#ffffff11] bg-[#0f0f0f]'>
                        <input
                            className='w-full bg-transparent px-3 py-2.5 sm:px-4 sm:py-3 font-mono text-xs sm:text-sm text-zinc-100 placeholder-zinc-500 border-0 outline-none focus:ring-0 focus:outline-none focus:bg-[#1a1a1a] transition-colors duration-150'
                            type='text'
                            placeholder='Enter a command...'
                            aria-label='Console command input.'
                            disabled={!instance || !connected}
                            onKeyDown={handleCommandKeyDown}
                            autoCorrect='off'
                            autoCapitalize='none'
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default Console;
