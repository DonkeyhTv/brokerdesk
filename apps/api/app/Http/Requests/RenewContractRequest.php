<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RenewContractRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var Contract $contract */
        $contract = $this->route('contract');

        return Gate::inspect('update', $contract);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // Optional: the premium of the new term, when the insurer indexes it.
            'annual_premium_cents' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
        ];
    }
}
