<?php

namespace App\Http\Requests;

use App\Enums\ClientType;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Rules\ValidEnterpriseNumber;
use App\Support\EnterpriseNumber;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ClientRequest extends FormRequest
{
    public function authorize(): Response
    {
        $client = $this->route('client');

        return $client instanceof Client
            ? Gate::inspect('update', $client)
            : Gate::inspect('create', Client::class);
    }

    protected function prepareForValidation(): void
    {
        try {
            $this->merge(['enterprise_number' => EnterpriseNumber::parse($this->string('enterprise_number')->toString())->digits]);
        } catch (InvalidArgumentException) {
            // Left as sent: the validation rule reports it.
        }
    }

    /**
     * @return array<string, array<int, string|ValidationRule|object>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $client = $this->route('client');

        return [
            'type' => ['required', Rule::enum(ClientType::class)],
            'broker_id' => $user->role === UserRole::Admin
                ? ['required', 'integer', Rule::exists('users', 'id')->where('brokerage_id', $user->brokerage_id)->whereIn('role', [UserRole::Admin->value, UserRole::Broker->value])]
                : ['prohibited'],
            'first_name' => ['required_if:type,individual', 'prohibited_if:type,company', 'nullable', 'string', 'max:100'],
            'last_name' => ['required_if:type,individual', 'prohibited_if:type,company', 'nullable', 'string', 'max:100'],
            'company_name' => ['required_if:type,company', 'prohibited_if:type,individual', 'nullable', 'string', 'max:200'],
            'enterprise_number' => [
                'required_if:type,company',
                'prohibited_if:type,individual',
                'nullable',
                'string',
                new ValidEnterpriseNumber,
                Rule::unique('clients')->where('brokerage_id', $user->brokerage_id)->whereNull('deleted_at')->ignore($client instanceof Client ? $client->id : null),
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'regex:/^[1-9]\d{3}$/'],
            'city' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Identity fields of the other client type are cleared, so a type change stays consistent.
     *
     * @return array<string, mixed>
     */
    public function clientAttributes(): array
    {
        return [
            'first_name' => null,
            'last_name' => null,
            'company_name' => null,
            'enterprise_number' => null,
            'phone' => null,
            ...$this->safe()->except('broker_id'),
        ];
    }
}
