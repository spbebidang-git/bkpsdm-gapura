<?php

namespace App\Providers;

use App\Repositories\Course\Activity\ActivityRepository;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionOptionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\QuestionItemRepository;
use App\Repositories\Course\Activity\Assessment\QuestionOptionRepository;
use App\Repositories\Course\Activity\Assessment\QuestionRepository;
use App\Repositories\Course\Activity\Assessment\QuestionVariantRepository;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\InteractiveRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\LiteratureRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Repositories\Course\Activity\InteractiveRepository;
use App\Repositories\Course\Activity\LiteratureRepository;
use App\Repositories\Course\Activity\TaskRepository;
use App\Repositories\Course\CategoryRepository;
use App\Repositories\Course\TrainerRepository;
use App\Repositories\Course\Contracts\CategoryRepositoryInterface;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Course\Contracts\ReviewRepositoryInterface;
use App\Repositories\Course\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Course\ReviewRepository;
use App\Repositories\Course\ScheduleRepository;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;
use App\Repositories\Identity\Contracts\UserMetaRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Repositories\Identity\EmployeeStatusRepository;
use App\Repositories\Identity\UserMetaRepository;
use App\Repositories\Identity\UserRepository;
use App\Repositories\Learning\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnItemRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Repositories\Learning\EnrollmentRepository;
use App\Repositories\Learning\LearnItemRepository;
use App\Repositories\Learning\LearnRepository;
use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use App\Repositories\Reporting\Contracts\ResultRepositoryInterface;
use App\Repositories\Reporting\LearningReportRepository;
use App\Repositories\Reporting\ResultRepository;
use App\Repositories\Support\Contracts\HelpRepositoryInterface;
use App\Repositories\Support\HelpRepository;
use App\Repositories\System\Contracts\SettingRepositoryInterface;
use App\Repositories\System\SettingRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CategoryRepositoryInterface::class => CategoryRepository::class,
        CourseRepositoryInterface::class => CourseRepository::class,
        ReviewRepositoryInterface::class => ReviewRepository::class,
        ScheduleRepositoryInterface::class => ScheduleRepository::class,
        TrainerRepositoryInterface::class => TrainerRepository::class,
        ActivityRepositoryInterface::class => ActivityRepository::class,
        InteractiveRepositoryInterface::class => InteractiveRepository::class,
        LiteratureRepositoryInterface::class => LiteratureRepository::class,
        TaskRepositoryInterface::class => TaskRepository::class,
        QuestionRepositoryInterface::class => QuestionRepository::class,
        QuestionVariantRepositoryInterface::class => QuestionVariantRepository::class,
        QuestionItemRepositoryInterface::class => QuestionItemRepository::class,
        QuestionOptionRepositoryInterface::class => QuestionOptionRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        EmployeeStatusRepositoryInterface::class => EmployeeStatusRepository::class,
        UserMetaRepositoryInterface::class => UserMetaRepository::class,
        EnrollmentRepositoryInterface::class => EnrollmentRepository::class,
        LearnRepositoryInterface::class => LearnRepository::class,
        LearnItemRepositoryInterface::class => LearnItemRepository::class,
        HelpRepositoryInterface::class => HelpRepository::class,
        SettingRepositoryInterface::class => SettingRepository::class,
        LearningReportRepositoryInterface::class => LearningReportRepository::class,
        ResultRepositoryInterface::class => ResultRepository::class,
    ];
}
