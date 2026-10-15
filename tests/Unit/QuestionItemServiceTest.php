<?php

namespace Tests\Unit;

use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionOption;
use App\Services\Course\Activity\Question\QuestionItemService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class QuestionItemServiceTest extends TestCase
{
    public function test_it_formats_imported_questions_with_fewer_than_four_options(): void
    {
        $question = new QuestionItem([
            'description' => 'Benar atau salah?',
            'score' => 1,
        ]);
        $question->id = 10;

        $question->setRelation('questionOptions', new Collection([
            $this->option(21, 'Benar', true),
            $this->option(22, 'Salah', false),
        ]));

        $method = new ReflectionMethod(QuestionItemService::class, 'formatQuestionItem');
        $result = $method->invoke(new QuestionItemService(), $question);

        $this->assertSame(10, $result['id']);
        $this->assertCount(2, $result['options']);
        $this->assertSame('Benar', $result['options'][0]['text']);
        $this->assertTrue($result['options'][0]['is_correct']);
        $this->assertSame('Salah', $result['options'][1]['text']);
        $this->assertFalse($result['options'][1]['is_correct']);
    }

    private function option(int $id, string $text, bool $isCorrect): QuestionOption
    {
        $option = new QuestionOption([
            'option' => $text,
            'is_correct' => $isCorrect,
        ]);
        $option->id = $id;

        return $option;
    }
}
