<?php

namespace App\Http\Requests;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateClaimRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var Claim $claim */
        $claim = $this->route('claim');

        return Gate::inspect('update', $claim);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ClaimStatus::class)],
            'estimated_amount_cents' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'settled_amount_cents' => ['required_if:status,settled', 'prohibited_unless:status,settled', 'nullable', 'integer', 'min:0', 'max:2000000000'],
        ];
    }
}
