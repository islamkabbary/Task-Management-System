<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['manager', 'user']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->role === 'manager') {
            return true;
        }
        return $task->assignee_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task, array $input = []): bool
    {
        if ($user->role === 'manager') {
            return true;
        }

        if ($user->role === 'user' && $task->assignee_id === $user->id) {
            $allowedFields = ['status'];
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    return false;
                }
            }

            if (isset($input['status']) && $input['status'] === 'completed') {
                foreach ($task->dependencies as $dependency) {
                    if ($dependency->status !== 'completed') {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->role === 'manager';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
