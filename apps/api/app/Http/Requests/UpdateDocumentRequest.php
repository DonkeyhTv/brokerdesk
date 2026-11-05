<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): Response
    {
        /** @var Document $document */
        $document = $this->route('document');

        return Gate::inspect('update', $document);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'original_name' => ['sometimes', 'required', 'string', 'max:255'],
            'visible_to_client' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
