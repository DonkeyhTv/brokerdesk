<?php

namespace App\Http\Requests;

use App\Enums\ContractStatus;
use App\Models\Claim;
use App\Models\Contract;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Claim::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'contract_id' => ['required', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($user) {
                $contract = is_numeric($value) ? Contract::query()->visibleTo($user)->find($value) : null;

                if ($contract === null) {
                    $fail('The selected contract is invalid.');
                } elseif ($contract->status !== ContractStatus::Active) {
                    $fail('A claim can only be declared on an active contract.');
                }
            }],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:5000'],
            'estimated_amount_cents' => $user->isStaff() ? ['nullable', 'integer', 'min:0', 'max:2000000000'] : ['prohibited'],
        ];
    }
}
