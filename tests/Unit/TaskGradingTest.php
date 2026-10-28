<?php

namespace Tests\Unit;

use App\Models\Course\Activity\Task;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Services\Course\Activity\TaskService;
use App\Services\Learning\LearnService;
use Mockery;
use Tests\TestCase;

class TaskGradingTest extends TestCase
{
    public function test_creating_a_task_cannot_enable_automatic_grading(): void
    {
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $task = new Task;
        $tasks->shouldReceive('create')->once()
            ->with(['description' => 'Tugas uji', 'is_calculated' => false])
            ->andReturn($task);
        $service = new TaskService(Mockery::mock(LearnService::class), $tasks);

        $this->assertSame($task, $service->createTask([
            'description' => 'Tugas uji', 'is_calculated' => true,
        ]));
    }

    public function test_updating_a_legacy_task_disables_automatic_grading(): void
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('update')->once()
            ->with(['description' => 'Tugas diperbarui', 'is_calculated' => false])
            ->andReturn(true);
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $tasks->shouldReceive('findByActivity')->with('task-id')->once()->andReturn($task);
        $service = new TaskService(Mockery::mock(LearnService::class), $tasks);

        $this->assertTrue($service->updateTask([
            'activity_id' => 'task-id', 'description' => 'Tugas diperbarui', 'is_calculated' => true,
        ]));
    }
}
