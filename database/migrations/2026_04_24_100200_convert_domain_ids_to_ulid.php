<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Convert domain PK/FK from bigint → ULID char(26).
 * Spatie roles/permissions PK stay bigint. Morph model_id → char(26).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'users',
        'categories',
        'employee_status',
        'courses',
        'activities',
        'questions',
        'question_variants',
        'question_items',
        'question_options',
        'tasks',
        'interactives',
        'literatures',
        'schedules',
        'enrollments',
        'learns',
        'learn_items',
        'reviews',
        'vouchers',
        'settings',
        'helps',
        'user_meta',
    ];

    /**
     * child_table => [fk_column => parent_table]
     *
     * @var array<string, array<string, string>>
     */
    private array $foreignKeys = [
        'courses' => [
            'category_id' => 'categories',
            'instructor_id' => 'users',
        ],
        'activities' => [
            'course_id' => 'courses',
        ],
        'questions' => [
            'activity_id' => 'activities',
        ],
        'question_variants' => [
            'question_id' => 'questions',
        ],
        'question_items' => [
            'question_variant_id' => 'question_variants',
        ],
        'question_options' => [
            'question_item_id' => 'question_items',
        ],
        'tasks' => [
            'activity_id' => 'activities',
        ],
        'interactives' => [
            'activity_id' => 'activities',
        ],
        'literatures' => [
            'activity_id' => 'activities',
        ],
        'schedules' => [
            'course_id' => 'courses',
        ],
        'enrollments' => [
            'participant_id' => 'users',
            'course_id' => 'courses',
            'schedule_id' => 'schedules',
        ],
        'learns' => [
            'enrollment_id' => 'enrollments',
            'participant_id' => 'users',
            'course_id' => 'courses',
        ],
        'learn_items' => [
            'learn_id' => 'learns',
            'activity_id' => 'activities',
        ],
        'reviews' => [
            'course_id' => 'courses',
            'user_id' => 'users',
        ],
        'vouchers' => [
            'course_id' => 'courses',
        ],
        'user_meta' => [
            'user_id' => 'users',
        ],
        'course_employee_status' => [
            'course_id' => 'courses',
            'employee_status_id' => 'employee_status',
        ],
        'schedule_user' => [
            'schedule_id' => 'schedules',
            'participant_id' => 'users',
        ],
    ];

    public function up(): void
    {
        // already converted?
        $type = DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'");
        if ($type && str_contains(strtolower($type->Type), 'char(26)')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->addAndFillUlidColumns();
            $this->addAndFillFkUlidColumns();
            $this->convertMorphBeforeSwap(); // users still has bigint id + _ulid
            $this->dropAllDomainForeignKeys();
            $this->swapPrimaryKeys();
            $this->swapForeignKeyColumns();
            $this->readdForeignKeys();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // irreversible without full backup — noop
    }

    private function addAndFillUlidColumns(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, '_ulid')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `_ulid` CHAR(26) NULL");

            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        '_ulid' => (string) Str::ulid(),
                    ]);
                }
            });

            DB::statement("ALTER TABLE `{$table}` MODIFY `_ulid` CHAR(26) NOT NULL");
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$table}_ulid_unique` (`_ulid`)");
        }
    }

    private function addAndFillFkUlidColumns(): void
    {
        foreach ($this->foreignKeys as $table => $fks) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($fks as $column => $parent) {
                if (! Schema::hasColumn($table, $column) || ! Schema::hasTable($parent)) {
                    continue;
                }

                $newCol = "{$column}_ulid";
                if (! Schema::hasColumn($table, $newCol)) {
                    DB::statement("ALTER TABLE `{$table}` ADD COLUMN `{$newCol}` CHAR(26) NULL");
                }

                // map via join
                DB::statement("
                    UPDATE `{$table}` AS c
                    INNER JOIN `{$parent}` AS p ON c.`{$column}` = p.`id`
                    SET c.`{$newCol}` = p.`_ulid`
                    WHERE c.`{$column}` IS NOT NULL
                ");
            }
        }
    }

    private function dropAllDomainForeignKeys(): void
    {
        $db = Schema::getConnection()->getDatabaseName();
        $fks = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND TABLE_NAME NOT IN ('role_has_permissions', 'model_has_roles', 'model_has_permissions')
        ", [$db]);

        // also drop FKs that point TO domain tables from pivots already included
        foreach ($fks as $fk) {
            // keep permission package internal FKs on role_has_permissions etc — filtered above partially
            if (in_array($fk->TABLE_NAME, ['role_has_permissions', 'model_has_roles', 'model_has_permissions'], true)) {
                continue;
            }
            try {
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable) {
                // already dropped
            }
        }
    }

    private function swapPrimaryKeys(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, '_ulid')) {
                continue;
            }

            // drop AI primary
            try {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            } catch (\Throwable) {
            }

            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `id`");
            DB::statement("ALTER TABLE `{$table}` CHANGE `_ulid` `id` CHAR(26) NOT NULL");
            DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");

            // drop helper unique if name still exists
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$table}_ulid_unique`");
            } catch (\Throwable) {
            }
        }
    }

    private function swapForeignKeyColumns(): void
    {
        // pivots: drop composite PK before column swap
        foreach (['course_employee_status', 'schedule_user'] as $pivot) {
            if (Schema::hasTable($pivot)) {
                try {
                    DB::statement("ALTER TABLE `{$pivot}` DROP PRIMARY KEY");
                } catch (\Throwable) {
                }
            }
        }

        foreach ($this->foreignKeys as $table => $fks) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($fks as $column => $parent) {
                $newCol = "{$column}_ulid";
                if (! Schema::hasColumn($table, $newCol)) {
                    continue;
                }

                // drop old indexes on column first
                $this->dropIndexesForColumn($table, $column);

                if (Schema::hasColumn($table, $column)) {
                    DB::statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
                }

                DB::statement("ALTER TABLE `{$table}` CHANGE `{$newCol}` `{$column}` CHAR(26) NULL");
            }
        }

        // restore NOT NULL where required
        $notNull = [
            'courses' => ['category_id', 'instructor_id'],
            'activities' => ['course_id'],
            'questions' => ['activity_id'],
            'question_variants' => ['question_id'],
            'question_items' => ['question_variant_id'],
            'question_options' => ['question_item_id'],
            'tasks' => ['activity_id'],
            'interactives' => ['activity_id'],
            'literatures' => ['activity_id'],
            'schedules' => ['course_id'],
            'enrollments' => ['participant_id', 'course_id'],
            'learns' => ['participant_id', 'course_id'],
            'learn_items' => ['learn_id', 'activity_id'],
            'reviews' => ['course_id', 'user_id'],
            'vouchers' => ['course_id'],
            'user_meta' => ['user_id'],
            'course_employee_status' => ['course_id', 'employee_status_id'],
            'schedule_user' => ['schedule_id', 'participant_id'],
        ];

        foreach ($notNull as $table => $cols) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($cols as $col) {
                if (Schema::hasColumn($table, $col)) {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` CHAR(26) NOT NULL");
                }
            }
        }

        // composite PKs for pivots
        if (Schema::hasTable('course_employee_status')) {
            try {
                DB::statement('ALTER TABLE course_employee_status DROP PRIMARY KEY');
            } catch (\Throwable) {
            }
            DB::statement('ALTER TABLE course_employee_status ADD PRIMARY KEY (course_id, employee_status_id)');
        }
        if (Schema::hasTable('schedule_user')) {
            try {
                DB::statement('ALTER TABLE schedule_user DROP PRIMARY KEY');
            } catch (\Throwable) {
            }
            DB::statement('ALTER TABLE schedule_user ADD PRIMARY KEY (schedule_id, participant_id)');
        }
    }

    private function convertMorphBeforeSwap(): void
    {
        // users still has id (bigint) + _ulid
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // already converted?
            $modelId = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = 'model_id'");
            if ($modelId && str_contains(strtolower($modelId->Type), 'char(26)')) {
                continue;
            }

            try {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            } catch (\Throwable) {
            }

            if (! Schema::hasColumn($table, 'model_id_ulid')) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `model_id_ulid` CHAR(26) NULL");
            }

            // map while model_id (bigint) still exists
            if (Schema::hasColumn($table, 'model_id')) {
                DB::statement("
                    UPDATE `{$table}` AS m
                    INNER JOIN `users` AS u ON m.model_id = u.id
                    SET m.model_id_ulid = u._ulid
                ");
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `model_id`");
            }

            // orphan roles (user deleted) — drop, cannot map
            DB::table($table)->whereNull('model_id_ulid')->delete();

            DB::statement("ALTER TABLE `{$table}` CHANGE `model_id_ulid` `model_id` CHAR(26) NOT NULL");

            if ($table === 'model_has_roles') {
                DB::statement('ALTER TABLE model_has_roles ADD PRIMARY KEY (role_id, model_id, model_type)');
            } else {
                DB::statement('ALTER TABLE model_has_permissions ADD PRIMARY KEY (permission_id, model_id, model_type)');
            }
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $col = DB::selectOne("SHOW COLUMNS FROM personal_access_tokens WHERE Field = 'tokenable_id'");
            if ($col && ! str_contains(strtolower($col->Type), 'char(26)')) {
                if (! Schema::hasColumn('personal_access_tokens', 'tokenable_id_ulid')) {
                    DB::statement('ALTER TABLE personal_access_tokens ADD COLUMN tokenable_id_ulid CHAR(26) NULL');
                }
                // join via bigint id while users._ulid exists; if users already swapped use users.id
                if (Schema::hasColumn('users', '_ulid')) {
                    DB::statement('
                        UPDATE personal_access_tokens AS t
                        INNER JOIN users AS u ON t.tokenable_id = u.id
                        SET t.tokenable_id_ulid = u._ulid
                    ');
                }
                DB::table('personal_access_tokens')->whereNull('tokenable_id_ulid')->delete();
                DB::statement('ALTER TABLE personal_access_tokens DROP COLUMN tokenable_id');
                DB::statement('ALTER TABLE personal_access_tokens CHANGE tokenable_id_ulid tokenable_id CHAR(26) NOT NULL');
            }
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            $col = DB::selectOne("SHOW COLUMNS FROM sessions WHERE Field = 'user_id'");
            if ($col && ! str_contains(strtolower($col->Type), 'char(26)')) {
                if (! Schema::hasColumn('sessions', 'user_id_ulid')) {
                    DB::statement('ALTER TABLE sessions ADD COLUMN user_id_ulid CHAR(26) NULL');
                }
                if (Schema::hasColumn('users', '_ulid')) {
                    DB::statement('
                        UPDATE sessions AS s
                        INNER JOIN users AS u ON s.user_id = u.id
                        SET s.user_id_ulid = u._ulid
                    ');
                }
                DB::statement('ALTER TABLE sessions DROP COLUMN user_id');
                DB::statement('ALTER TABLE sessions CHANGE user_id_ulid user_id CHAR(26) NULL');
            }
        }
    }

    private function readdForeignKeys(): void
    {
        $defs = [
            ['courses', 'category_id', 'categories', 'id', 'CASCADE'],
            ['courses', 'instructor_id', 'users', 'id', 'CASCADE'],
            ['activities', 'course_id', 'courses', 'id', 'CASCADE'],
            ['questions', 'activity_id', 'activities', 'id', 'CASCADE'],
            ['question_variants', 'question_id', 'questions', 'id', 'CASCADE'],
            ['question_items', 'question_variant_id', 'question_variants', 'id', 'CASCADE'],
            ['question_options', 'question_item_id', 'question_items', 'id', 'CASCADE'],
            ['tasks', 'activity_id', 'activities', 'id', 'CASCADE'],
            ['interactives', 'activity_id', 'activities', 'id', 'CASCADE'],
            ['literatures', 'activity_id', 'activities', 'id', 'CASCADE'],
            ['schedules', 'course_id', 'courses', 'id', 'CASCADE'],
            ['enrollments', 'participant_id', 'users', 'id', 'CASCADE'],
            ['enrollments', 'course_id', 'courses', 'id', 'CASCADE'],
            ['enrollments', 'schedule_id', 'schedules', 'id', 'SET NULL'],
            ['learns', 'enrollment_id', 'enrollments', 'id', 'SET NULL'],
            ['learns', 'participant_id', 'users', 'id', 'CASCADE'],
            ['learns', 'course_id', 'courses', 'id', 'CASCADE'],
            ['learn_items', 'learn_id', 'learns', 'id', 'CASCADE'],
            ['learn_items', 'activity_id', 'activities', 'id', 'CASCADE'],
            ['reviews', 'course_id', 'courses', 'id', 'CASCADE'],
            ['reviews', 'user_id', 'users', 'id', 'CASCADE'],
            ['vouchers', 'course_id', 'courses', 'id', 'CASCADE'],
            ['user_meta', 'user_id', 'users', 'id', 'CASCADE'],
            ['course_employee_status', 'course_id', 'courses', 'id', 'CASCADE'],
            ['course_employee_status', 'employee_status_id', 'employee_status', 'id', 'CASCADE'],
            ['schedule_user', 'schedule_id', 'schedules', 'id', 'CASCADE'],
            ['schedule_user', 'participant_id', 'users', 'id', 'CASCADE'],
        ];

        foreach ($defs as [$table, $col, $refTable, $refCol, $onDelete]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
                continue;
            }
            $name = "{$table}_{$col}_foreign";
            try {
                DB::statement("
                    ALTER TABLE `{$table}`
                    ADD CONSTRAINT `{$name}`
                    FOREIGN KEY (`{$col}`) REFERENCES `{$refTable}` (`{$refCol}`)
                    ON DELETE {$onDelete}
                ");
            } catch (\Throwable $e) {
                // index may be needed first
                try {
                    DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$table}_{$col}_index` (`{$col}`)");
                    DB::statement("
                        ALTER TABLE `{$table}`
                        ADD CONSTRAINT `{$name}`
                        FOREIGN KEY (`{$col}`) REFERENCES `{$refTable}` (`{$refCol}`)
                        ON DELETE {$onDelete}
                    ");
                } catch (\Throwable) {
                    // leave without FK rather than fail whole migrate
                }
            }
        }

        // re-unique constraints that were dropped with indexes
        $uniques = [
            ['questions', 'questions_activity_id_unique', ['activity_id']],
            ['tasks', 'tasks_activity_id_unique', ['activity_id']],
            ['interactives', 'interactives_activity_id_unique', ['activity_id']],
            ['literatures', 'literatures_activity_id_unique', ['activity_id']],
            ['question_variants', 'question_variants_question_id_variant_unique', ['question_id', 'variant']],
            ['learn_items', 'learn_items_learn_id_activity_id_unique', ['learn_id', 'activity_id']],
            ['learns', 'learns_enrollment_id_unique', ['enrollment_id']],
        ];

        foreach ($uniques as [$table, $name, $cols]) {
            if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
                continue;
            }
            $colList = implode('`,`', $cols);
            try {
                DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$name}` (`{$colList}`)");
            } catch (\Throwable) {
            }
        }
    }

    private function dropIndexesForColumn(string $table, string $column): void
    {
        $db = Schema::getConnection()->getDatabaseName();
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
              AND INDEX_NAME != 'PRIMARY'
        ", [$db, $table, $column]);

        foreach ($indexes as $idx) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$idx->INDEX_NAME}`");
            } catch (\Throwable) {
            }
        }
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
