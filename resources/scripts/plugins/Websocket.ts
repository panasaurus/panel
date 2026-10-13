import { EventEmitter } from 'events';

export class Websocket extends EventEmitter {
    private timer: ReturnType<typeof setTimeout> | null = null;
    private connectionTimer: ReturnType<typeof setTimeout> | null = null;
    private backoff = 500;
    private socket: WebSocket | null = null;
    private url: string | null = null;
    private token = '';

    connect(url: string): this {
        this.disconnect();
        this.url = url;
        this.open();
        return this;
    }

    setToken(token: string, isUpdate = false): this {
        this.token = token;
        if (isUpdate) this.authenticate();
        return this;
    }

    authenticate() {
        if (this.url && this.token) this.send('auth', this.token);
    }

    private clearTimers() {
        if (this.timer !== null) clearTimeout(this.timer);
        if (this.connectionTimer !== null) clearTimeout(this.connectionTimer);
        this.timer = null;
        this.connectionTimer = null;
    }

    private disconnect(code = 1000, reason?: string) {
        this.clearTimers();
        const socket = this.socket;
        this.socket = null;
        if (socket) {
            socket.onopen = null;
            socket.onmessage = null;
            socket.onclose = null;
            socket.onerror = null;
            socket.close(code, reason);
        }
    }

    private scheduleReconnect() {
        if (!this.url || this.timer !== null) return;
        const delay = this.backoff * (0.8 + Math.random() * 0.4);
        this.backoff = Math.min(this.backoff * 2, 10000);
        this.timer = setTimeout(() => {
            this.timer = null;
            if (!this.url) return;
            this.emit('SOCKET_RECONNECT');
            this.open();
        }, delay);
    }

    close(code?: number, reason?: string) {
        this.url = null;
        this.token = '';
        this.backoff = 500;
        this.disconnect(code, reason);
        this.emit('SOCKET_CLOSE');
    }

    open() {
        if (!this.url || this.socket) return;
        this.clearTimers();
        const socket = new WebSocket(this.url);
        this.socket = socket;

        this.connectionTimer = setTimeout(() => {
            if (this.socket !== socket) return;
            this.disconnect();
            this.emit('SOCKET_CLOSE');
            this.scheduleReconnect();
        }, 10000);

        socket.onmessage = (message) => {
            if (this.socket !== socket) return;
            let packet: { event: string; args?: unknown[] };
            try {
                packet = JSON.parse(message.data);
                if (!packet || typeof packet.event !== 'string' || (packet.args !== undefined && !Array.isArray(packet.args))) return;
            } catch {
                console.warn('Failed to parse incoming websocket message.');
                return;
            }
            if (packet.event === 'auth success') this.backoff = 500;
            this.emit(packet.event, ...(packet.args || []));
        };
        socket.onopen = () => {
            if (this.socket !== socket) return;
            this.clearTimers();
            this.emit('SOCKET_OPEN');
            this.authenticate();
        };
        socket.onclose = () => {
            if (this.socket !== socket) return;
            this.socket = null;
            this.clearTimers();
            this.emit('SOCKET_CLOSE');
            this.scheduleReconnect();
        };
        socket.onerror = (error) => {
            if (this.socket === socket) this.emit('SOCKET_ERROR', error);
        };
    }

    reconnect() {
        if (!this.url) return;
        this.disconnect();
        this.emit('SOCKET_CLOSE');
        this.scheduleReconnect();
    }

    send(event: string, payload?: string | string[]): boolean {
        if (!this.socket || this.socket.readyState !== WebSocket.OPEN) return false;
        this.socket.send(JSON.stringify({ event, args: Array.isArray(payload) ? payload : payload === undefined ? [] : [payload] }));
        return true;
    }
}
