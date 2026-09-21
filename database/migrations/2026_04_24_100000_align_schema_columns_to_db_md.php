<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive column/index changes toward db.md (still bigint PKs).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['employee_status', 'helps', 'settings', 'user_meta', 'course_employee_status'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'code')) {
            $this->ensureUniqueIndex('categories', 'categories_code_unique', 'code');
        }

        // users tokens + nip
        $this->renameColumnRaw('users', 'sso_access_token', 'access_token', 'TEXT NULL');
        $this->renameColumnRaw('users', 'sso_refresh_token', 'refresh_token', 'TEXT NULL');
        $this->renameColumnRaw('users', 'sso_expires_at', 'token_expires_at', 'TIMESTAMP NULL');

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'nip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nip')->nullable()->after('name');
            });
            $this->backfillNip();
            $this->ensureUniqueIndex('users', 'users_nip_unique', 'nip');
        }

        // courses access_mode
        if (Schema::hasTable('courses')) {
            if (! Schema::hasColumn('courses', 'access_mode')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->enum('access_mode', ['open', 'scheduled'])->default('open')->after('instructor_id');
                });

                if (Schema::hasColumn('courses', 'type')) {
                    DB::table('courses')->orderBy('id')->chunkById(100, function ($rows) {
                        foreach ($rows as $row) {
                            DB::table('courses')->where('id', $row->id)->update([
                                'access_mode' => ($row->type === 'schedule') ? 'scheduled' : 'open',
                            ]);
                        }
                    });
                }
            }

            if (! Schema::hasColumn('courses', 'default_access_hours')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->unsignedInteger('default_access_hours')->nullable()->after('access_mode');
                });

                if (Schema::hasColumn('courses', 'expired')) {
                    DB::table('courses')->orderBy('id')->chunkById(100, function ($rows) {
                        foreach ($rows as $row) {
                            $hours = $row->expired;
                            if ($hours !== null && (int) $hours >= 9999) {
                                $hours = null;
                            }
                            DB::table('courses')->where('id', $row->id)->update([
                                'default_access_hours' => $hours,
                            ]);
                        }
                    });
                }
            }

            if (Schema::hasColumn('courses', 'type')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->dropColumn('type');
                });
            }
            if (Schema::hasColumn('courses', 'expired')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->dropColumn('expired');
                });
            }

            $this->ensureIndex('courses', 'courses_status_access_mode_index', ['status', 'access_mode']);
        }

        // activities sort_order
        if (Schema::hasTable('activities') && ! Schema::hasColumn('activities', 'sort_order')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('instruction');
            });

            $courseIds = DB::table('activities')->distinct()->pluck('course_id');
            foreach ($courseIds as $courseId) {
                $i = 0;
                $rows = DB::table('activities')->where('course_id', $courseId)->orderBy('id')->get();
                foreach ($rows as $row) {
                    DB::table('activities')->where('id', $row->id)->update(['sort_order' => $i++]);
                }
            }

            $this->ensureIndex('activities', 'activities_course_id_sort_order_index', ['course_id', 'sort_order']);
        }

        if (Schema::hasTable('question_items') && ! Schema::hasColumn('question_items', 'sort_order')) {
            Schema::table('question_items', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('score');
            });
        }

        if (Schema::hasTable('question_options') && ! Schema::hasColumn('question_options', 'sort_order')) {
            Schema::table('question_options', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_correct');
            });
        }

        foreach (['questions', 'tasks', 'interactives', 'literatures'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'activity_id')) {
                $this->ensureUniqueIndex($table, "{$table}_activity_id_unique", 'activity_id');
            }
        }

        if (Schema::hasTable('question_variants')) {
            $this->ensureUniqueIndex(
                'question_variants',
                'question_variants_question_id_variant_unique',
                ['question_id', 'variant']
            );
        }

        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks', 'attachment_path')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('is_calculated');
            });
        }

        if (Schema::hasTable('literatures')) {
            if (! Schema::hasColumn('literatures', 'document_path')) {
                Schema::table('literatures', function (Blueprint $table) {
                    $table->string('document_path')->nullable()->after('content');
                });
            }
            DB::statement('ALTER TABLE literatures MODIFY duration INT UNSIGNED NULL');
            DB::statement('ALTER TABLE literatures MODIFY sub_title VARCHAR(255) NULL');
            DB::statement('ALTER TABLE literatures MODIFY content LONGTEXT NOT NULL');
        }

        if (Schema::hasTable('interactives')) {
            DB::statement('ALTER TABLE interactives MODIFY description TEXT NULL');
            DB::statement('ALTER TABLE interactives MODIFY duration INT UNSIGNED NULL');
        }

        // schedules cohort windows
        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('schedules', 'title')) {
                    $table->string('title')->nullable()->after('course_id');
                }
                if (! Schema::hasColumn('schedules', 'register_from')) {
                    $table->dateTime('register_from')->nullable()->after('title');
                }
                if (! Schema::hasColumn('schedules', 'register_to')) {
                    $table->dateTime('register_to')->nullable()->after('register_from');
                }
                if (! Schema::hasColumn('schedules', 'access_from')) {
                    $table->dateTime('access_from')->nullable()->after('register_to');
                }
                if (! Schema::hasColumn('schedules', 'access_to')) {
                    $table->dateTime('access_to')->nullable()->after('access_from');
                }
            });

            DB::table('schedules')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->access_from || $row->access_to || ! $row->date) {
                        continue;
                    }
                    $d = substr((string) $row->date, 0, 10);
                    $tf = $row->time_from ? substr((string) $row->time_from, 0, 8) : '00:00:00';
                    $tt = $row->time_to ? substr((string) $row->time_to, 0, 8) : '23:59:59';
                    DB::table('schedules')->where('id', $row->id)->update([
                        'access_from' => "{$d} {$tf}",
                        'access_to' => "{$d} {$tt}",
                    ]);
                }
            });

            $this->ensureIndex('schedules', 'schedules_course_access_index', ['course_id', 'access_from', 'access_to']);
        }

        if (Schema::hasTable('learn_items')) {
            $this->ensureUniqueIndex(
                'learn_items',
                'learn_items_learn_id_activity_id_unique',
                ['learn_id', 'activity_id']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'access_mode') && ! Schema::hasColumn('courses', 'type')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('type', ['schedule', 'unschedule'])->default('unschedule')->after('instructor_id');
                $table->integer('expired')->nullable();
            });

            DB::table('courses')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('courses')->where('id', $row->id)->update([
                        'type' => ($row->access_mode === 'scheduled') ? 'schedule' : 'unschedule',
                        'expired' => $row->default_access_hours,
                    ]);
                }
            });
        }

        $this->renameColumnRaw('users', 'access_token', 'sso_access_token', 'TEXT NULL');
        $this->renameColumnRaw('users', 'refresh_token', 'sso_refresh_token', 'TEXT NULL');
        $this->renameColumnRaw('users', 'token_expires_at', 'sso_expires_at', 'TIMESTAMP NULL');
    }

    private function renameColumnRaw(string $table, string $from, string $to, string $definition): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$definition}");
        }
    }

    private function backfillNip(): void
    {
        if (! Schema::hasTable('user_meta')) {
            return;
        }

        $rows = DB::table('user_meta')->where('key', 'nip')->whereNotNull('value')->get();
        foreach ($rows as $row) {
            $nip = $row->value;
            try {
                $nip = decrypt($nip);
            } catch (\Throwable) {
                // keep raw
            }
            if (! is_string($nip) || $nip === '' || $nip === '-') {
                continue;
            }
            $exists = DB::table('users')->where('nip', $nip)->where('id', '!=', $row->user_id)->exists();
            if ($exists) {
                continue;
            }
            DB::table('users')->where('id', $row->user_id)->whereNull('nip')->update(['nip' => $nip]);
        }
    }

    private function ensureUniqueIndex(string $table, string $name, string|array $columns): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }
        $cols = is_array($columns) ? $columns : [$columns];
        Schema::table($table, function (Blueprint $blueprint) use ($cols, $name) {
            $blueprint->unique($cols, $name);
        });
    }

    private function ensureIndex(string $table, string $name, array $columns): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $name]
        );

        return $row && (int) $row->c > 0;
    }
};
