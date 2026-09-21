<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

abstract class EloquentRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): LengthAwarePaginator {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model|int|string $model, array $attributes): Model
    {
        $instance = $this->resolveModel($model);
        $instance->fill($attributes);
        $instance->save();

        return $instance->refresh();
    }

    public function delete(Model|int|string $model): bool
    {
        return (bool) $this->resolveModel($model)->delete();
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    protected function resolveModel(Model|int|string $model): Model
    {
        if (! $model instanceof Model) {
            return $this->findOrFail($model);
        }

        if (! $model instanceof $this->model) {
            throw new InvalidArgumentException(sprintf(
                'Repository %s expects an instance of %s.',
                static::class,
                $this->model::class,
            ));
        }

        return $model;
    }
}
