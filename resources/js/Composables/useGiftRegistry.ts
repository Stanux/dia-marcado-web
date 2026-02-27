/**
 * useGiftRegistry Composable
 * 
 * Provides centralized error handling and retry logic for gift registry operations.
 * Manages API calls with proper error handling and user feedback.
 * 
 * @Requirements: 13.2, 13.5
 */

import { ref } from 'vue';

interface GiftItem {
  id: string;
  name: string;
  description: string;
  photo_url: string | null;
  display_price: number;
  quantity_available: number;
  is_sold_out: boolean;
  registry_mode: 'quantity' | 'quota';
  quota_total: number | null;
  quota_sold: number | null;
  quota_progress_percent: number | null;
  is_fallback_donation: boolean;
  minimum_custom_amount: number | null;
  allows_custom_amount: boolean;
}

interface PurchaseResponse {
  success: boolean;
  transaction?: any;
  qr_code?: string;
  qr_code_text?: string;
  qr_code_base64?: string;
  message?: string;
}

interface UseGiftRegistryReturn {
  loading: Readonly<typeof loading>;
  error: Readonly<typeof error>;
  fetchGifts: (eventId: string) => Promise<GiftItem[]>;
  purchaseGift: (eventId: string, giftId: string, paymentData: any) => Promise<PurchaseResponse>;
  clearError: () => void;
  retryLastOperation: () => Promise<any>;
}

export function useGiftRegistry(): UseGiftRegistryReturn {
  const loading = ref(false);
  const error = ref<string | null>(null);
  const lastOperation = ref<(() => Promise<any>) | null>(null);

  function getCookieValue(name: string): string | null {
    const cookies = document.cookie.split('; ').find((entry) => entry.startsWith(`${name}=`));
    if (!cookies) {
      return null;
    }

    const [, value = ''] = cookies.split('=');
    return value ? decodeURIComponent(value) : null;
  }

  function buildApiHeaders(baseHeaders: Record<string, string> = {}): Record<string, string> {
    const headers: Record<string, string> = {
      'X-Requested-With': 'XMLHttpRequest',
      ...baseHeaders,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const xsrfToken = getCookieValue('XSRF-TOKEN');
    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    return headers;
  }

  /**
   * Fetch available gifts for an event
   */
  async function fetchGifts(eventId: string): Promise<GiftItem[]> {
    const operation = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await fetch(`/api/events/${eventId}/gifts`, {
          method: 'GET',
          headers: buildApiHeaders({
            'Accept': 'application/json',
          }),
        });

        if (!response.ok) {
          const data = await response.json().catch(() => ({}));
          throw new Error(data.message || 'Falha ao carregar presentes');
        }

        const data = await response.json();
        return data.data || [];
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Erro ao carregar presentes';
        error.value = errorMessage;
        throw err;
      } finally {
        loading.value = false;
      }
    };

    lastOperation.value = operation;
    return operation();
  }

  /**
   * Purchase a gift item
   */
  async function purchaseGift(
    eventId: string,
    giftId: string,
    paymentData: any
  ): Promise<PurchaseResponse> {
    const operation = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await fetch(`/api/events/${eventId}/gifts/${giftId}/purchase`, {
          method: 'POST',
          headers: buildApiHeaders({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          }),
          body: JSON.stringify(paymentData),
        });

        const data = await response.json();

        if (!response.ok) {
          // Handle specific error cases
          if (response.status === 422) {
            // Validation error
            const validationErrors = data.errors || {};
            const firstError = Object.values(validationErrors)[0];
            throw new Error(
              Array.isArray(firstError) ? firstError[0] : data.message || 'Dados inválidos'
            );
          } else if (response.status === 409) {
            // Conflict (e.g., item sold out)
            throw new Error(data.message || 'Este presente não está mais disponível');
          } else if (response.status === 400) {
            // Bad request (e.g., payment failed)
            throw new Error(data.message || 'Falha ao processar pagamento');
          } else {
            throw new Error(data.message || 'Erro ao processar compra');
          }
        }

        const payload = data.data ?? data;
        const transaction =
          payload.transaction ??
          (payload.transaction_id
            ? { internal_id: payload.transaction_id, status: payload.status }
            : null);
        return {
          success: true,
          transaction,
          qr_code: payload.qr_code,
          qr_code_text: payload.qr_code_text,
          qr_code_base64: payload.qr_code_base64,
          message: data.message,
        };
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Erro ao processar compra';
        error.value = errorMessage;
        throw err;
      } finally {
        loading.value = false;
      }
    };

    lastOperation.value = operation;
    return operation();
  }

  /**
   * Clear the current error
   */
  function clearError(): void {
    error.value = null;
  }

  /**
   * Retry the last failed operation
   */
  async function retryLastOperation(): Promise<any> {
    if (!lastOperation.value) {
      throw new Error('Nenhuma operação para repetir');
    }

    return lastOperation.value();
  }

  return {
    loading,
    error,
    fetchGifts,
    purchaseGift,
    clearError,
    retryLastOperation,
  };
}
