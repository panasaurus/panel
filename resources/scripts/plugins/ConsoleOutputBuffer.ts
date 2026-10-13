export class ConsoleOutputBuffer {
    private pending: string[] = [];
    private size = 0;
    private timer: ReturnType<typeof setTimeout> | null = null;
    private writing = false;
    private dropped = false;
    private disposed = false;

    constructor(private readonly write: (data: string, callback: () => void) => void) {}

    append(line: string) {
        if (this.disposed) return;
        this.pending.push(line + '\r\n');
        this.size += line.length + 2;
        while (this.size > 1024 * 1024 && this.pending.length > 1) {
            this.size -= this.pending.shift()!.length;
            this.dropped = true;
        }
        this.schedule();
    }

    private schedule() {
        if (this.disposed || this.writing || this.timer !== null || !this.pending.length) return;
        this.timer = setTimeout(() => {
            this.timer = null;
            this.flush();
        }, 16);
    }

    private flush() {
        if (this.disposed || this.writing || !this.pending.length) return;
        let data = this.dropped ? '\u001b[0mSome console output was skipped while rendering caught up.\r\n' : '';
        this.dropped = false;
        while (this.pending.length && data.length < 64 * 1024) {
            const line = this.pending.shift()!;
            data += line;
            this.size -= line.length;
        }
        this.writing = true;
        this.write(data, () => {
            this.writing = false;
            this.schedule();
        });
    }

    clear() {
        if (this.timer !== null) clearTimeout(this.timer);
        this.timer = null;
        this.pending = [];
        this.size = 0;
        this.dropped = false;
    }

    dispose() {
        this.disposed = true;
        this.clear();
    }
}
