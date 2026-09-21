<?php

namespace Tests\Unit;

use App\Services\Auth\KedatonOAuthService;
use App\Services\Auth\SsoAuthService;
use App\Services\Auth\TokenService;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\InteractiveService;
use App\Services\Course\Activity\LiteratureService;
use App\Services\Course\Activity\Question\QuestionItemService;
use App\Services\Course\Activity\Question\VariantService;
use App\Services\Course\Activity\QuestionService;
use App\Services\Course\Activity\TaskService;
use App\Services\Course\CoursePerformanceService;
use App\Services\Course\CourseService;
use App\Services\Course\Review\ReviewService;
use App\Services\Course\ScheduleUserService;
use App\Services\Identity\UserService;
use App\Services\Integration\CategorySyncService;
use App\Services\Integration\CertificateSyncService;
use App\Services\Learning\LearnService;
use App\Services\Reporting\ResultService;
use Tests\TestCase;

class ServiceResolutionTest extends TestCase
{
    public function test_services_resolve_with_repository_dependencies(): void
    {
        $services = [
            KedatonOAuthService::class,
            SsoAuthService::class,
            TokenService::class,
            ActivityService::class,
            InteractiveService::class,
            LiteratureService::class,
            QuestionItemService::class,
            QuestionService::class,
            TaskService::class,
            VariantService::class,
            CoursePerformanceService::class,
            CourseService::class,
            ReviewService::class,
            ScheduleUserService::class,
            UserService::class,
            CategorySyncService::class,
            CertificateSyncService::class,
            LearnService::class,
            ResultService::class,
        ];

        foreach ($services as $service) {
            $this->assertInstanceOf($service, $this->app->make($service));
        }
    }
}
