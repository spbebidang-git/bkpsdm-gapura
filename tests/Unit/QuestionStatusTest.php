<?php

namespace Tests\Unit;

use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Course\Activity\QuestionService;
use App\Services\Learning\LearnService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class QuestionStatusTest extends TestCase
{
    private function service(?Learn $learn): QuestionService
    {
        $repository = $this->createMock(LearnRepositoryInterface::class);
        $repository->expects($this->never())->method('findByParticipant');
        $repository->expects($this->once())->method('findByParticipantAndCourse')
            ->with('participant', 'current-course', $this->callback(function ($relations) {
                $query = new class {
                    public function where($column, $value)
                    {
                        TestCase::assertSame('activity_id', $column);
                        TestCase::assertSame('activity', $value);
                        return $this;
                    }
                };
                $relations['learnItems']($query);
                return true;
            }))->willReturn($learn);

        return new QuestionService(
            $this->createMock(LearnService::class),
            $this->createMock(QuestionRepositoryInterface::class),
            $this->createMock(QuestionItemRepositoryInterface::class),
            $this->createMock(QuestionVariantRepositoryInterface::class),
            $repository,
        );
    }

    public function test_unstarted_activity_has_zero_status(): void
    {
        $learn = new Learn;
        $learn->setRelation('learnItems', new Collection);
        $this->assertSame(0, $this->service($learn)->getStatus('participant', 'activity', 'current-course'));
    }

    public function test_missing_learning_record_has_zero_status(): void
    {
        $this->assertSame(0, $this->service(null)->getStatus('participant', 'activity', 'current-course'));
    }

    public function test_existing_status_is_read_from_current_course(): void
    {
        $learn = new Learn;
        $learn->setRelation('learnItems', new Collection([new LearnItem(['status' => 'done'])]));
        $this->assertSame('done', $this->service($learn)->getStatus('participant', 'activity', 'current-course'));
    }
}
