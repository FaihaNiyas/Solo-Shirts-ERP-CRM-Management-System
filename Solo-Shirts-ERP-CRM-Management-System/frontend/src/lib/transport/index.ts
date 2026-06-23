export interface NotificationTransport {
  subscribe(channel: string, callback: (data: unknown) => void): () => void
}

class PollingTransport implements NotificationTransport {
  private readonly intervalMs: number

  constructor(intervalMs = 30_000) {
    this.intervalMs = intervalMs
  }

  subscribe(_channel: string, callback: (data: unknown) => void): () => void {
    const id = setInterval(() => {
      callback({ channel: _channel, ts: Date.now() })
    }, this.intervalMs)
    return () => clearInterval(id)
  }
}

export function createTransport(intervalMs?: number): NotificationTransport {
  return new PollingTransport(intervalMs)
}
