<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const OLD_MODEL = 'App\\Models\\User';

    private const NEW_MODEL = 'App\\Models\\Identity\\User';

    public function up(): void
    {
        $this->replaceModelType(self::OLD_MODEL, self::NEW_MODEL);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $this->replaceModelType(self::NEW_MODEL, self::OLD_MODEL);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function replaceModelType(string $from, string $to): void
    {
        foreach ($this->morphColumns() as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (in_array($table, ['model_has_roles', 'model_has_permissions'], true)) {
                $rows = DB::table($table)->where($column, $from)->get();

                foreach ($rows->chunk(500) as $chunk) {
                    DB::table($table)->insertOrIgnore(
                        $chunk->map(function ($row) use ($column, $to) {
                            $values = (array) $row;
                            $values[$column] = $to;

                            return $values;
                        })->all(),
                    );
                }

                DB::table($table)->where($column, $from)->delete();

                continue;
            }

            DB::table($table)->where($column, $from)->update([$column => $to]);
        }
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function morphColumns(): array
    {
        return [
            ['model_has_roles', 'model_type'],
            ['model_has_permissions', 'model_type'],
            ['personal_access_tokens', 'tokenable_type'],
        ];
    }
};
