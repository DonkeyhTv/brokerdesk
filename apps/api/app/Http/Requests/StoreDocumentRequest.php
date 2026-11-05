<?php

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Document;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var Contract $contract */
        $contract = $this->route('contract');

        return Gate::inspect('create', [Document::class, $contract]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['pdf'])->max(10 * 1024)],
            'visible_to_client' => ['sometimes', 'boolean'],
        ];
    }
}
