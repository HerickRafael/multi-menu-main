import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { useTenant } from '@/contexts/TenantContext'

type ConnectionState = 'disabled' | 'connecting' | 'connected' | 'reconnecting' | 'disconnected' | 'error'

interface RealtimeContextValue {
  connectionState: ConnectionState
  scope: string
  lastConnectedAt: number | null
  reconnect: () => void
  send: (payload: unknown) => boolean
}

const RealtimeContext = createContext<RealtimeContextValue | undefined>(undefined)

const MAX_RECONNECT_ATTEMPTS = 8
const BASE_RECONNECT_DELAY_MS = 500

function getScope(mode: 'platform' | 'tenant', tenantId: number | null): string {
  if (mode === 'tenant' && tenantId) {
    return `tenant:${tenantId}`
  }
  return 'platform'
}

function buildWsUrl(baseUrl: string, scope: string, tenantId: number | null): string {
  const url = new URL(baseUrl)
  url.searchParams.set('scope', scope)
  if (tenantId) {
    url.searchParams.set('tenant_id', String(tenantId))
  }
  return url.toString()
}

export function RealtimeContextProvider({ children }: { children: ReactNode }) {
  const { mode, selectedTenantId } = useTenant()

  const scope = useMemo(
    () => getScope(mode, selectedTenantId),
    [mode, selectedTenantId],
  )

  const wsBaseUrl = (import.meta.env.VITE_SUPERADMIN_WS_URL as string | undefined)?.trim() || ''

  const socketRef = useRef<WebSocket | null>(null)
  const reconnectTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const reconnectAttemptsRef = useRef(0)
  const closedByAppRef = useRef(false)

  const [connectionState, setConnectionState] = useState<ConnectionState>(
    wsBaseUrl ? 'disconnected' : 'disabled',
  )
  const [lastConnectedAt, setLastConnectedAt] = useState<number | null>(null)

  const clearReconnectTimer = useCallback(() => {
    if (reconnectTimerRef.current) {
      clearTimeout(reconnectTimerRef.current)
      reconnectTimerRef.current = null
    }
  }, [])

  const closeSocket = useCallback(() => {
    clearReconnectTimer()

    if (socketRef.current) {
      closedByAppRef.current = true
      socketRef.current.close()
      socketRef.current = null
    }
  }, [clearReconnectTimer])

  const connect = useCallback(() => {
    if (!wsBaseUrl) {
      setConnectionState('disabled')
      return
    }

    closeSocket()

    try {
      const wsUrl = buildWsUrl(wsBaseUrl, scope, selectedTenantId)
      const socket = new WebSocket(wsUrl)

      socketRef.current = socket
      setConnectionState(reconnectAttemptsRef.current > 0 ? 'reconnecting' : 'connecting')

      socket.onopen = () => {
        reconnectAttemptsRef.current = 0
        setConnectionState('connected')
        setLastConnectedAt(Date.now())
      }

      socket.onerror = () => {
        setConnectionState('error')
      }

      socket.onclose = () => {
        socketRef.current = null

        if (closedByAppRef.current) {
          closedByAppRef.current = false
          setConnectionState('disconnected')
          return
        }

        if (!wsBaseUrl) {
          setConnectionState('disabled')
          return
        }

        if (reconnectAttemptsRef.current >= MAX_RECONNECT_ATTEMPTS) {
          setConnectionState('error')
          return
        }

        reconnectAttemptsRef.current += 1
        const delay = Math.min(
          BASE_RECONNECT_DELAY_MS * 2 ** (reconnectAttemptsRef.current - 1),
          8000,
        )

        setConnectionState('reconnecting')

        reconnectTimerRef.current = setTimeout(() => {
          connect()
        }, delay)
      }
    } catch {
      setConnectionState('error')
    }
  }, [closeSocket, scope, selectedTenantId, wsBaseUrl])

  useEffect(() => {
    reconnectAttemptsRef.current = 0
    connect()

    return () => {
      closeSocket()
    }
  }, [connect, closeSocket])

  const reconnect = useCallback(() => {
    reconnectAttemptsRef.current = 0
    connect()
  }, [connect])

  const send = useCallback((payload: unknown): boolean => {
    const socket = socketRef.current

    if (!socket || socket.readyState !== WebSocket.OPEN) {
      return false
    }

    try {
      socket.send(typeof payload === 'string' ? payload : JSON.stringify(payload))
      return true
    } catch {
      return false
    }
  }, [])

  const value = useMemo<RealtimeContextValue>(
    () => ({
      connectionState,
      scope,
      lastConnectedAt,
      reconnect,
      send,
    }),
    [connectionState, lastConnectedAt, reconnect, scope, send],
  )

  return <RealtimeContext.Provider value={value}>{children}</RealtimeContext.Provider>
}

export function useRealtime(): RealtimeContextValue {
  const context = useContext(RealtimeContext)
  if (!context) {
    throw new Error('useRealtime must be used within RealtimeContextProvider')
  }
  return context
}
