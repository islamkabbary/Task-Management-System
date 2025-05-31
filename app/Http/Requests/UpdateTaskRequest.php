<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends AbstractFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = request()->route('id');
        return [
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'status'        => 'in:pending,completed,canceled',
            'due_date'      => 'nullable|date',
            'assignee_id'   => 'nullable|exists:users,id',
            'dependency_ids' => 'nullable|array',
            'dependency_ids.*' => "exists:tasks,id|distinct|not_in:$id",
        ];
    }
}
