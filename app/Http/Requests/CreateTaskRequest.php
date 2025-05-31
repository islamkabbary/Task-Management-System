<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends AbstractFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'due_date'      => 'required|date|after_or_equal:today',
            'status'        => 'required|in:pending,completed,canceled',
            'assignee_id'   => 'nullable|exists:users,id',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => 'exists:tasks,id|distinct',
        ];
    }
}