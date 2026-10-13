import { useEffect, useState } from 'react';

import Spinner from '@/components/elements/Spinner';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

import getWebsocketToken from '@/api/server/getWebsocketToken';

import { HibernationStatus, ServerContext } from '@/state/server';

import { Websocket } from '@/plugins/Websocket';

const reconnectErrors = ['jwt: exp claim is invalid', 'jwt: created too far in past (denylist)'];

function WebsocketHandler() {
    const [error, setError] = useState<'connecting' | string>('');
    const connected = ServerContext.useStoreState((state) => state.socket.connected);
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    const setServerStatus = ServerContext.useStoreActions((actions) => actions.status.setServerStatus);
    const setHibernation = ServerContext.useStoreActions((actions) => actions.hibernation.setHibernation);
    const { setInstance, setConnectionState } = ServerContext.useStoreActions((actions) => actions.socket);

    useEffect(() => {
        if (connected) setError('');
    }, [connected]);

    useEffect(() => {
        if (!uuid) return;
        setHibernation(null);
        let active = true;
        let updatingToken = false;
        let current: Websocket | null = null;

        const updateToken = (socket: Websocket) => {
            if (updatingToken || !active || current !== socket) return;
            updatingToken = true;
            getWebsocketToken(uuid)
                .then((data) => {
                    if (active && current === socket) socket.setToken(data.token, true);
                })
                .catch((error) => console.error(error))
                .finally(() => { updatingToken = false; });
        };

        const connect = () => {
            if (!active) return;
            if (current) {
                current.removeAllListeners();
                current.close();
            }
            updatingToken = false;
            const socket = new Websocket();
            current = socket;
            setConnectionState(false);
            setInstance(null);
            setError('connecting');

            socket.on('auth success', () => setConnectionState(true));
            socket.on('SOCKET_CLOSE', () => setConnectionState(false));
            socket.on('SOCKET_RECONNECT', () => setError('connecting'));
            socket.on('SOCKET_ERROR', () => {
                setError('connecting');
                setConnectionState(false);
            });
            socket.on('status', (status) => setServerStatus(status));
            const updateHibernation = (data: string, stats = false) => {
                try {
                    const parsed = JSON.parse(data);
                    const value = stats ? parsed.hibernation : parsed;
                    if (value && typeof value.enabled === 'boolean') setHibernation(value as HibernationStatus);
                } catch { return; }
            };
            socket.on('hibernation', (data) => updateHibernation(data));
            socket.on('stats', (data) => updateHibernation(data, true));
            socket.on('daemon error', (message) => {
                console.warn('Got error message from daemon socket:', message);
            });
            socket.on('token expiring', () => updateToken(socket));
            socket.on('token expired', () => updateToken(socket));
            socket.on('jwt error', (error: string) => {
                setConnectionState(false);
                console.warn('JWT validation error from wings:', error);
                if (reconnectErrors.some((value) => error.toLowerCase().includes(value))) {
                    updateToken(socket);
                } else {
                    socket.close();
                    setError('There was an error validating the credentials provided for the websocket. Please refresh the page.');
                }
            });
            socket.on('transfer status', (status: string) => {
                if (status !== 'starting' && status !== 'success') connect();
            });

            getWebsocketToken(uuid)
                .then((data) => {
                    if (!active || current !== socket) return;
                    socket.setToken(data.token).connect(data.socket);
                    setInstance(socket);
                })
                .catch((error) => {
                    if (!active || current !== socket) return;
                    console.error(error);
                    setError('Could not connect to your server. Please refresh the page to retry.');
                });
        };

        connect();
        return () => {
            active = false;
            current?.removeAllListeners();
            current?.close();
            setInstance(null);
            setConnectionState(false);
            setHibernation(null);
        };
    }, [uuid, setInstance, setConnectionState, setServerStatus, setHibernation]);

    return error ? (
        <FadeTransition duration='duration-150' show>
            <div
                className={`flex items-center px-4 rounded-full fixed w-fit mx-auto left-0 right-0 top-4 bg-red-500 py-2 z-9999`}
            >
                {error === 'connecting' ? (
                    <>
                        <Spinner size={'small'} />
                        <p className={`ml-2 text-sm text-red-100`}>
                            We&apos;re having some trouble connecting to your server, please wait...
                        </p>
                    </>
                ) : (
                    <p className={`ml-2 text-sm text-white`}>{error}</p>
                )}
            </div>
        </FadeTransition>
    ) : null;
}

export default WebsocketHandler;
