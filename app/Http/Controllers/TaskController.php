<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="AssigneeSchema",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="User"),
 *     @OA\Property(property="email", type="string", example="user@gmail.com"),
 *     @OA\Property(property="role", type="string", example="user"),
 *     @OA\Property(property="created_at", type="string", example="2025-05-30 16:46:08"),
 *     @OA\Property(property="updated_at", type="string", example="2025-05-30 16:46:08")
 * )
 *
 * @OA\Schema(
 *     schema="TaskSchema",
 *     type="object",
 *     title="Task",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Task Title"),
 *     @OA\Property(property="description", type="string", example="Task description"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2025-06-15"),
 *     @OA\Property(property="assignee", ref="#/components/schemas/AssigneeSchema"),
 *     @OA\Property(property="created_at", type="string", example="2025-05-30 16:46:26"),
 *     @OA\Property(property="updated_at", type="string", example="2025-05-30 16:46:26")
 * )
 *
 * @OA\Schema(
 *     schema="TaskListResponse",
 *     type="object",
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TaskSchema")
 *     ),
 *     @OA\Property(property="message", type="string", example="Tasks retrieved successfully."),
 *     @OA\Property(property="pagination", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="SingleTaskResponse",
 *     type="object",
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="data", ref="#/components/schemas/TaskSchema"),
 *     @OA\Property(property="message", type="string", example="Task details retrieved successfully."),
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 */

use App\Models\Task;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Repositories\Interfaces\TaskRepositoryInterface;

class TaskController extends Controller
{
    use ResponseTrait;

    protected $taskRepo;

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    /**
     * @OA\Get(
     *     path="/tasks",
     *     summary="Get all tasks",
     *     operationId="getTasks",
     *     tags={"Tasks"},
     *     security={{"Bearer":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by task status (e.g., pending, in_progress, completed)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="assignee_id",
     *         in="query",
     *         description="Filter by user assigned to task",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_date_from",
     *         in="query",
     *         description="Filter tasks with due date from this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-06-01")
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_date_to",
     *         in="query",
     *         description="Filter tasks with due date up to this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-06-30")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tasks list",
     *         @OA\JsonContent(ref="#/components/schemas/TaskListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['status', 'assignee_id', 'due_date_from', 'due_date_to']);
        if ($user->role === 'user') {
            $filters['assignee_id'] = $user->id;
        }
        $tasks = $this->taskRepo->all($filters);
        return $this->success(TaskResource::collection($tasks), 'Tasks retrieved successfully.');
    }

    /**
     * @OA\Get(
     *     path="/tasks/{id}",
     *     summary="Get task details",
     *     tags={"Tasks"},
     *     security={{"Bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Task details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SingleTaskResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $task = $this->taskRepo->find($id);
            if (!Gate::allows('view', $task)) {
                return $this->error(null, 'Unauthorized', 403);
            }
            return $this->success(new TaskResource($task), 'Task details retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error(null, 'Task not found.', 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/tasks",
     *     summary="Create a new task",
     *     tags={"Tasks"},
     *     security={{"Bearer":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "assignee_id", "due_date"},
     *             @OA\Property(property="title", type="string", example="Task Title"),
     *             @OA\Property(property="description", type="string", example="Task description"),
     *             @OA\Property(property="assignee_id", type="integer", example=2),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-06-10"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="dependency_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TaskSchema"),
     *             @OA\Property(property="message", type="string", example="Task created successfully.")
     *         )
     *     )
     * )
     */
    public function store(CreateTaskRequest $request)
    {
        try {
            if (!Gate::allows('create', Task::class)) {
                return $this->error(null, 'Unauthorized', 403);
            }
            $task = $this->taskRepo->create($request->validated());
            return $this->success(new TaskResource($task), 'Task created successfully.', 201);
        } catch (\Exception $e) {
            return $this->error(null, 'Failed to create task. ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/tasks/{id}",
     *     summary="Update an existing task",
     *     tags={"Tasks"},
     *     security={{"Bearer":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Task Title"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="status", type="string", example="in_progress"),
     *             @OA\Property(property="assignee_id", type="integer", example=2),
     *             @OA\Property(property="due_date", type="string", format="date", example="2025-06-15"),
     *             @OA\Property(property="dependency_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/TaskSchema"),
     *             @OA\Property(property="message", type="string", example="Task updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Cannot complete task due to uncompleted dependencies",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="data", type="string", nullable=true, example=null),
     *             @OA\Property(property="message", type="string", example="Cannot complete task until all dependencies are completed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateTaskRequest $request, $id)
    {
        try {
            $task = $this->taskRepo->find($id);

            if (!Gate::allows('update', [$task, $request->validated()])) {
                return $this->error(null, 'Unauthorized', 403);
            }

            $updatedTask = $this->taskRepo->update($id, $request->validated());
            return $this->success(new TaskResource($updatedTask), 'Task updated successfully.');
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error(null, 'Task not found.', 404);
        } catch (\Exception $e) {
            return $this->error(null, 'An unexpected error occurred.', 500);
        }
    }
}
