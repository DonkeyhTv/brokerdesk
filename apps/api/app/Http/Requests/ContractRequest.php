<?php

namespace App\Http\Requests;

use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\PaymentFrequency;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function authorize(): Response
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract
            ? Gate::inspect('update', $contract)
            : Gate::inspect('create', Contract::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $contract = $this->route('contract');

        $rules = [
            'insurer_id' => ['required', 'integer', 'exists:insurers,id'],
            'policy_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('contracts')->where('brokerage_id', $user->brokerage_id)->ignore($contract instanceof Contract ? $contract->id : null),
            ],
            'type' => ['required', Rule::enum(ContractType::class)],
            'status' => ['required', Rule::enum(ContractStatus::class)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'expires_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'annual_premium_cents' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'payment_frequency' => ['required', Rule::enum(PaymentFrequency::class)],
        ];

        if (! $contract instanceof Contract) {
            $rules['client_id'] = ['required', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($user) {
                if (! is_numeric($value) || ! Client::query()->visibleTo($user)->whereKey($value)->exists()) {
                    $fail(__('The selected client is invalid.'));
                }
            }];
        }

        return $rules;
    }
}
