<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for purchasing a gift.
 * 
 * @Requirements: 2.7, 5.1, 5.2, 5.3, 12.1, 12.2
 */
class PurchaseGiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, no authentication required
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'payment_method' => ['required', 'string', 'in:credit_card,pix'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:100'],
            
            // Payer information (required for both methods)
            'payer.name' => ['required', 'string', 'max:255'],
            'payer.email' => ['required', 'email', 'max:255'],
            'payer.document' => ['required', 'string', 'regex:/^\d{11}$|^\d{14}$/'], // CPF or CNPJ
            'payer.phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],
            
            // Credit card specific fields (required only for credit_card)
            'card_token' => ['required_if:payment_method,credit_card', 'string'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            
            // Billing address (optional but recommended)
            'billing.street' => ['nullable', 'string', 'max:255'],
            'billing.number' => ['nullable', 'string', 'max:20'],
            'billing.complement' => ['nullable', 'string', 'max:100'],
            'billing.district' => ['nullable', 'string', 'max:100'],
            'billing.city' => ['nullable', 'string', 'max:100'],
            'billing.state' => ['nullable', 'string', 'size:2'],
            'billing.postal_code' => ['nullable', 'string', 'regex:/^\d{5}-?\d{3}$/'],
        ];

        $merchantEmail = config('services.pagseguro.merchant_email');
        if (is_string($merchantEmail) && $merchantEmail !== '') {
            $rules['payer.email'][] = 'not_in:' . $merchantEmail;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'O método de pagamento é obrigatório.',
            'payment_method.in' => 'Método de pagamento inválido. Use "credit_card" ou "pix".',
            'idempotency_key.required' => 'A chave de idempotência é obrigatória.',
            'idempotency_key.min' => 'A chave de idempotência deve ter no mínimo 16 caracteres.',
            'idempotency_key.max' => 'A chave de idempotência deve ter no máximo 100 caracteres.',
            
            'payer.name.required' => 'O nome do pagador é obrigatório.',
            'payer.email.required' => 'O email do pagador é obrigatório.',
            'payer.email.email' => 'O email do pagador deve ser válido.',
            'payer.email.not_in' => 'O email do pagador não pode ser o mesmo do recebedor.',
            'payer.document.required' => 'O CPF/CNPJ do pagador é obrigatório.',
            'payer.document.regex' => 'O CPF/CNPJ deve conter apenas números (11 ou 14 dígitos).',
            'payer.phone.regex' => 'O telefone deve conter apenas números (10 ou 11 dígitos).',
            
            'card_token.required_if' => 'O token do cartão é obrigatório para pagamento com cartão de crédito.',
            'installments.integer' => 'O número de parcelas deve ser um número inteiro.',
            'installments.min' => 'O número mínimo de parcelas é 1.',
            'installments.max' => 'O número máximo de parcelas é 12.',
            
            'billing.state.size' => 'O estado deve ter 2 caracteres (sigla).',
            'billing.postal_code.regex' => 'O CEP deve estar no formato 00000-000.',
        ];
    }
}
