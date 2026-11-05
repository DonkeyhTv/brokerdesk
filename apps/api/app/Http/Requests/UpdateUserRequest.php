<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var User $target */
        $target = $this->route('user');

        return Gate::inspect('update', $target);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($target->id)],
            'password' => ['nullable', 'string', Password::min(10)],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Broker->value]), function (string $attribute, mixed $value, Closure $fail) use ($target) {
                if ($target->id === $this->user()?->id && $value !== UserRole::Admin->value) {
                    $fail('You cannot remove your own admin role.');
                }
            }],
        ];
    }
}
