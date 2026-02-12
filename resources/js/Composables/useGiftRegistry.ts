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
  id: number;
  name: string;
  description: string;
  photo_url: string;
  display_price: number;
  quantity_available: number;
  is_enabled: boolean;
  is_sold_out: boolean;
}

interface PurchaseResponse {
  success: boolean;
  transaction?: any;
  qr_code?: string;
  qr_code_base64?: string;
  message?: string;
}

interface UseGiftRegistryReturn {
  loading: Readonly<typeof loading>;
  error: Readonly<typeof error>;
  fetchGifts: (eventId: number) => Promise<GiftItem[]>;
  purchaseGift: (eventId: number, giftId: number, paymentData: any) => Promise<PurchaseResponse>;
  clearError: () => void;
  retryLastOperation: () => Promise<any>;
}

export function useGiftRegistry(): UseGiftRegistryReturn {
  const loading = ref(false);
  const error = ref<string | null>(null);
  const lastOperation = ref<(() => Promise<any>) | null>(null);

  /**
   * Fetch available gifts for an event
   */
  async function fetchGifts(eventId: number): Promise<GiftItem[]> {
    const operation = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await fetch(`/api/events/${eventId}/gifts`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
          },
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
    eventId: number,
    giftId: number,
    paymentData: any
  ): Promise<PurchaseResponse> {
    const operation = async () => {
      loading.value = true;
      error.value = null;

      try {
        const response = await fetch(`/api/events/${eventId}/gifts/${giftId}/purchase`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
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
