<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        // Completed tasks move to /tasks/history — the active list only
        // ever shows what's still outstanding.
        $tasks = Task::where('user_id', $request->user()->id)
            ->where('is_done', false)
            ->orderBy('date')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'date' => $data['date'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_done' => false,
        ]);

        return response()->json(['message' => 'Task created', 'task' => $task]);
    }

    public function complete(Request $request, Task $task)
    {
        abort_if($task->user_id !== $request->user()->id, 403);

        $task->update(['is_done' => true]);

        return response()->json(['message' => 'Task completed, sir.', 'task' => $task]);
    }

    public function history(Request $request)
    {
        $query = Task::where('user_id', $request->user()->id)
            ->where('is_done', true)
            ->orderByDesc('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        $paginator = $query->paginate(
            self::PER_PAGE,
            ['*'],
            'page',
            max(1, (int) $request->query('page', 1))
        );

        return response()->json([
            'tasks' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
