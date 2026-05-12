/**
 * FASE 4 Types - Monitoring & Webhooks
 */

export interface ApiEnvelope<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface TimeSeriesPoint {
  time: string;
  value: number;
}

export interface CpuMetrics {
  percent: number;
  history: TimeSeriesPoint[];
}

export interface MemoryMetrics {
  used_mb: number;
  total_mb: number;
  percent: number;
  history: TimeSeriesPoint[];
}

export interface DatabaseMetrics {
  connections: number;
  max_connections: number;
}

export interface SystemMetricsData {
  cpu: CpuMetrics;
  memory: MemoryMetrics;
  database: DatabaseMetrics;
}

export interface WorkersStatus {
  online: number;
  jobs_queued: number;
}

export interface MonitoringData {
  system: SystemMetricsData;
  workers: WorkersStatus;
  updated_at: string;
}

// ─── Webhooks ───────────────────────────────────────────

export interface WebhookItem {
  id: number;
  webhook_url: string;
  event_type: string;
  status: 'pending' | 'success' | 'failed';
  status_code: number | null;
  retry_count: number;
  created_at: string;
  updated_at: string;
}

export interface WebhookStats {
  total: number;
  pending: number;
  success: number;
  failed: number;
}

export interface WebhookData {
  items: WebhookItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
    has_next: boolean;
    has_prev: boolean;
  };
  stats: WebhookStats;
}

export type WebhookListFilters = {
  page?: number;
  per_page?: number;
  status?: 'pending' | 'success' | 'failed' | '';
};

// ─── Queues ────────────────────────────────────────────

export type QueueStatus = 'pending' | 'processing' | 'done' | 'failed' | 'retrying' | 'dead';

export interface QueueJobItem {
  id: number;
  company_id: number | null;
  job_type: string;
  payload_json: string | null;
  status: QueueStatus;
  priority: number;
  attempts: number;
  max_attempts: number;
  available_at: string | null;
  reserved_at: string | null;
  last_error: string | null;
  created_at: string;
  updated_at: string;
}

export interface QueueStats {
  total: number;
  pending: number;
  processing: number;
  done: number;
  failed: number;
  dead: number;
}

export interface QueueData {
  items: QueueJobItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
    has_next: boolean;
    has_prev: boolean;
  };
  stats: QueueStats;
}

export type QueueListFilters = {
  page?: number;
  per_page?: number;
  status?: QueueStatus | '';
  job_type?: string;
  company_id?: string;
};

// ─── WhatsApp ───────────────────────────────────────────

export type WhatsAppStatus = 'connected' | 'awaiting_pairing' | 'not_configured';

export interface WhatsAppInstanceItem {
  id: number;
  company_id: number;
  company_name: string;
  label: string;
  number: string;
  instance_identifier: string;
  status: WhatsAppStatus;
  created_at: string;
}

export interface WhatsAppStats {
  total: number;
  connected: number;
  awaiting_pairing: number;
  not_configured: number;
  companies_covered: number;
}

export interface WhatsAppData {
  items: WhatsAppInstanceItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
    has_next: boolean;
    has_prev: boolean;
  };
  stats: WhatsAppStats;
}

export type WhatsAppFilters = {
  page?: number;
  per_page?: number;
  search?: string;
  status?: WhatsAppStatus | '';
  company_id?: number;
};
