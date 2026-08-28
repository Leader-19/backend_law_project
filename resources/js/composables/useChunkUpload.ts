import { ref, computed } from 'vue'

// 5 MB per chunk – safe for most PHP/nginx configs
const DEFAULT_CHUNK_SIZE = 5 * 1024 * 1024

// Files smaller than this threshold use the normal single-request upload
export const CHUNK_UPLOAD_THRESHOLD = 10 * 1024 * 1024 // 10 MB

interface UploadMeta {
  upload_id: string
  filename: string
  total_size: number
  total_chunks: number
  created_at: string
}

interface InitResponse {
  upload_id: string
  chunk_size: number
  total_chunks: number
}

interface ChunkResponse {
  message: string
  chunk_index: number
  uploaded_chunks: number
  total_chunks: number
}

interface CompleteResponse {
  message: string
  document: Record<string, unknown>
}

interface StatusResponse {
  upload_id: string
  filename: string
  total_size: number
  uploaded_chunks: number
  total_chunks: number
  is_complete: boolean
}

export interface UploadProgress {
  uploadedBytes: number
  totalBytes: number
  percent: number
  uploadedChunks: number
  totalChunks: number
  currentChunk: number
  status: 'idle' | 'initializing' | 'uploading' | 'reassembling' | 'completed' | 'error' | 'cancelled'
  error: string | null
  speed: number          // bytes per second
  estimatedTimeLeft: number // seconds
}

function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]')
  return meta?.getAttribute('content') ?? ''
}

function getApiBase(): string {
  // The Inertia admin panel runs on the same origin
  return ''
}

/**
 * Build the correct API URL for the chunked-upload endpoints.
 * When the Inertia admin panel is behind a reverse-proxy the base may differ
 * from the default `/api`, so we detect it from the page URL.
 */
function apiUrl(path: string): string {
  return `${getApiBase()}/api${path}`
}

async function apiFetch<T>(
  url: string,
  options: RequestInit = {},
): Promise<T> {
  const defaults: RequestInit = {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': decodeURIComponent(
        (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      ),
      'Accept': 'application/json',
      ...options.headers as Record<string, string>,
    },
    credentials: 'same-origin',
  }

  const res = await fetch(url, { ...defaults, ...options })

  if (!res.ok) {
    const body = await res.json().catch(() => ({}))
    const msg =
      body.error ||
      body.message ||
      `Upload failed with status ${res.status}`
    throw new Error(msg)
  }

  return res.json()
}

export function useChunkUpload() {
  const progress = ref<UploadProgress>({
    uploadedBytes: 0,
    totalBytes: 0,
    percent: 0,
    uploadedChunks: 0,
    totalChunks: 0,
    currentChunk: 0,
    status: 'idle',
    error: null,
    speed: 0,
    estimatedTimeLeft: 0,
  })

  const isUploading = computed(() =>
    ['initializing', 'uploading', 'reassembling'].includes(progress.value.status),
  )

  let abortController: AbortController | null = null
  let speedSamples: number[] = []

  function reset() {
    progress.value = {
      uploadedBytes: 0,
      totalBytes: 0,
      percent: 0,
      uploadedChunks: 0,
      totalChunks: 0,
      currentChunk: 0,
      status: 'idle',
      error: null,
      speed: 0,
      estimatedTimeLeft: 0,
    }
    speedSamples = []
  }

  function cancel() {
    abortController?.abort()
    progress.value.status = 'cancelled'
  }

  /**
   * Upload a file using chunked upload.
   *
   * @param file        The File object to upload
   * @param metadata    Form fields: doc_name, doc_title, category_id, description
   * @param onProgress  Optional callback fired on every chunk with current progress
   * @returns           The created document record
   */
  async function upload(
    file: File,
    metadata: {
      doc_name: string
      doc_title: string
      category_id: string | number
      description?: string
    },
    onProgress?: (p: UploadProgress) => void,
  ): Promise<Record<string, unknown>> {
    reset()
    abortController = new AbortController()

    const totalSize = file.size
    const chunkSize = DEFAULT_CHUNK_SIZE
    const totalChunks = Math.ceil(totalSize / chunkSize)

    progress.value.totalBytes = totalSize
    progress.value.totalChunks = totalChunks

    try {
      // ── 1. Initialize ──
      progress.value.status = 'initializing'
      onProgress?.(progress.value)

      const initRes = await apiFetch<InitResponse>(apiUrl('/upload/init'), {
        method: 'POST',
        body: JSON.stringify({
          filename: file.name,
          total_size: totalSize,
          total_chunks: totalChunks,
        }),
      })

      const uploadId = initRes.upload_id

      // ── 2. Upload chunks ──
      progress.value.status = 'uploading'
      let uploadedBytes = 0
      speedSamples = []
      let lastTime = Date.now()

      for (let i = 0; i < totalChunks; i++) {
        if (abortController.signal.aborted) {
          // Clean up on server
          await apiFetch(apiUrl(`/upload/${uploadId}/cancel`), {
            method: 'DELETE',
          }).catch(() => {})
          throw new Error('Upload cancelled')
        }

        const start = i * chunkSize
        const end = Math.min(start + chunkSize, totalSize)
        const chunk = file.slice(start, end)

        progress.value.currentChunk = i + 1

        const res = await fetch(apiUrl(`/upload/${uploadId}/chunk`), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/octet-stream',
            'X-Chunk-Index': String(i),
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': decodeURIComponent(
              (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            ),
          },
          credentials: 'same-origin',
          body: chunk,
          signal: abortController.signal,
        })

        if (!res.ok) {
          const body = await res.json().catch(() => ({}))
          throw new Error(body.error || `Chunk ${i} upload failed (${res.status})`)
        }

        uploadedBytes += end - start
        const chunkData: ChunkResponse = await res.json()
        progress.value.uploadedChunks = chunkData.uploaded_chunks

        // Calculate speed (exponential moving average)
        const now = Date.now()
        const elapsed = (now - lastTime) / 1000
        lastTime = now
        const chunkSpeed = elapsed > 0 ? (end - start) / elapsed : 0
        speedSamples.push(chunkSpeed)
        if (speedSamples.length > 5) speedSamples.shift()
        const avgSpeed =
          speedSamples.reduce((a, b) => a + b, 0) / speedSamples.length

        progress.value.uploadedBytes = uploadedBytes
        progress.value.percent = Math.round((uploadedBytes / totalSize) * 100)
        progress.value.speed = avgSpeed
        progress.value.estimatedTimeLeft =
          avgSpeed > 0 ? (totalSize - uploadedBytes) / avgSpeed : 0

        onProgress?.(progress.value)
      }

      // ── 3. Complete ──
      progress.value.status = 'reassembling'
      onProgress?.(progress.value)

      const completeRes = await apiFetch<CompleteResponse>(
        apiUrl(`/upload/${uploadId}/complete`),
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': decodeURIComponent(
              (document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            ),
            'Accept': 'application/json',
          },
          credentials: 'same-origin',
          body: JSON.stringify(metadata),
        },
      )

      progress.value.status = 'completed'
      progress.value.percent = 100
      onProgress?.(progress.value)

      return completeRes.document
    } catch (err: any) {
      if (err.name === 'AbortError' || progress.value.status === 'cancelled') {
        progress.value.status = 'cancelled'
        progress.value.error = 'Upload cancelled by user.'
      } else {
        progress.value.status = 'error'
        progress.value.error = err.message || 'Upload failed'
      }
      onProgress?.(progress.value)
      throw err
    } finally {
      abortController = null
    }
  }

  /**
   * Check the status of an existing upload (for resume support).
   */
  async function getStatus(uploadId: string): Promise<StatusResponse> {
    return apiFetch<StatusResponse>(apiUrl(`/upload/${uploadId}/status`))
  }

  /**
   * Cancel an in-progress upload and clean up server-side chunks.
   */
  async function cancelUpload(uploadId: string): Promise<void> {
    cancel()
    await apiFetch(apiUrl(`/upload/${uploadId}/cancel`), {
      method: 'DELETE',
    }).catch(() => {})
  }

  return {
    progress,
    isUploading,
    upload,
    cancel,
    getStatus,
    cancelUpload,
    reset,
  }
}
