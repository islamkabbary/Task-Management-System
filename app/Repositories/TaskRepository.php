<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Task::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (isset($filters['due_date_from']) && isset($filters['due_date_to'])) {
            $query->whereBetween('due_date', [$filters['due_date_from'], $filters['due_date_to']]);
        } elseif (isset($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        } elseif (isset($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        return $query->get();
    }

    public function find($id)
    {
        return Task::with(['assignee', 'dependencies'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $dependencyIds = $data['dependency_ids'] ?? null;
        unset($data['dependency_ids']);

        $task = Task::create($data);

        if ($dependencyIds !== null) {
            $task->dependencies()->sync($dependencyIds);
        }

        return $task->load(['assignee', 'dependencies']);
    }

    public function update($id, array $data)
    {
        $task = Task::find($id);

        if (($data['status'] ?? null) === 'completed') {
            $incompleteDependenciesCount = $task->dependencies()->where('status', '!=', 'completed')->count();
            if ($incompleteDependenciesCount > 0) {
                throw new \RuntimeException('Cannot complete task until all dependencies are completed.');
            }
        }

        $dependencyIds = $data['dependency_ids'] ?? null;
        unset($data['dependency_ids']);

        $task->update($data);

        if ($dependencyIds !== null) {
            $task->dependencies()->sync($dependencyIds);
        }

        return $task->load(['assignee', 'dependencies']);
    }
}
