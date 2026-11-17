<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the Add/Edit project form (student and admin).
 * "Save as draft" only needs the basics; "Submit for review" also needs adviser, abstract and a document.
 */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // who may save is checked in the controller with the ProjectPolicy
    }

    public function rules(): array
    {
        $submitting = $this->input('intent') === 'submit';
        $repo = config('repository');

        return [
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'title' => ['required', 'string', 'max:255'],
            'proponents' => ['required', 'string', 'max:500'],
            'program' => ['required', Rule::in($repo['programs'])],
            'academic_year' => ['required', Rule::in($repo['academic_years'])],
            'category' => ['required', Rule::in($repo['categories'])],
            'adviser' => [$submitting ? 'required' : 'nullable', 'string', 'max:255'],
            'technology' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'abstract' => [$submitting ? 'required' : 'nullable', 'string', 'max:10000'],
            'source_url' => ['nullable', 'url:http,https', 'max:255'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'max:51200', 'mimes:'.$repo['file_types']],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'documents.*.mimes' => 'Only PDF, Word, PowerPoint or ZIP files are allowed.',
            'documents.*.max' => 'Each file must be 50 MB or smaller.',
            'abstract.required' => 'Add the abstract before submitting for review.',
            'adviser.required' => 'Add the adviser before submitting for review.',
        ];
    }
}
