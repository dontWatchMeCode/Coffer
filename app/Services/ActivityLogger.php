<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RecordLink;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log a tag attachment on a model.
     */
    public static function logTagAttached(Model $model, Tag $tag, ?Model $causer = null): void
    {
        activity()
            ->performedOn($model)
            ->causedBy($causer)
            ->withProperties([
                'relation_changes' => [
                    'type' => 'tag',
                    'action' => 'added',
                    'target' => [
                        'id' => (int) $tag->id,
                        'name' => $tag->name,
                    ],
                ],
            ])
            ->event('tagged')
            ->log('Added tag '.$tag->name);
    }

    /**
     * Log a tag detachment from a model.
     */
    public static function logTagDetached(Model $model, Tag $tag, ?Model $causer = null): void
    {
        activity()
            ->performedOn($model)
            ->causedBy($causer)
            ->withProperties([
                'relation_changes' => [
                    'type' => 'tag',
                    'action' => 'removed',
                    'target' => [
                        'id' => (int) $tag->id,
                        'name' => $tag->name,
                    ],
                ],
            ])
            ->event('untagged')
            ->log('Removed tag '.$tag->name);
    }

    /**
     * Log a bulk tag sync on a model.
     *
     * @param  array<int, string>  $added
     * @param  array<int, string>  $removed
     */
    public static function logTagsSynced(Model $model, array $added, array $removed, ?Model $causer = null): void
    {
        $parts = [];

        if ($added !== []) {
            $parts[] = 'added '.implode(', ', $added);
        }

        if ($removed !== []) {
            $parts[] = 'removed '.implode(', ', $removed);
        }

        if ($parts === []) {
            return;
        }

        activity()
            ->performedOn($model)
            ->causedBy($causer)
            ->withProperties([
                'relation_changes' => [
                    'type' => 'tag',
                    'action' => 'sync',
                    'added' => $added,
                    'removed' => $removed,
                ],
            ])
            ->event('tags_updated')
            ->log('Tags updated: '.implode(', ', $parts));
    }

    /**
     * Log a record link creation on both sides.
     */
    public static function logLinkCreated(RecordLink $link, ?Model $causer = null): void
    {
        $left = static::resolveLinkSide($link->left_type, $link->left_id);
        $right = static::resolveLinkSide($link->right_type, $link->right_id);
        $team = Team::find($link->team_id);

        if ($left === null || $right === null || ! $team instanceof Team) {
            return;
        }

        static::logLinkOnModel($left, $right, 'linked', 'Linked to', $team, $causer);
        static::logLinkOnModel($right, $left, 'linked', 'Linked to', $team, $causer);
    }

    /**
     * Log a record link destruction on both sides.
     */
    public static function logLinkDestroyed(RecordLink $link, ?Model $causer = null): void
    {
        $left = static::resolveLinkSide($link->left_type, $link->left_id);
        $right = static::resolveLinkSide($link->right_type, $link->right_id);
        $team = Team::find($link->team_id);

        if ($left === null || $right === null || ! $team instanceof Team) {
            return;
        }

        static::logLinkOnModel($left, $right, 'unlinked', 'Unlinked from', $team, $causer);
        static::logLinkOnModel($right, $left, 'unlinked', 'Unlinked from', $team, $causer);
    }

    /**
     * Log a link cleanup when a record is deleted.
     */
    public static function logLinkCleanup(Model $deletedModel, RecordLink $link, ?Model $causer = null): void
    {
        $other = static::resolveOtherLinkSide($deletedModel, $link);

        if (! $other instanceof Model) {
            return;
        }

        $deletedTitle = RecordLinkHelper::titleForModel($deletedModel);
        $deletedType = array_flip(RecordLink::linkableMap())[$deletedModel::class] ?? 'unknown';

        activity()
            ->performedOn($other)
            ->causedBy($causer)
            ->withProperties([
                'relation_changes' => [
                    'type' => 'link',
                    'action' => 'removed',
                    'target' => [
                        'type' => $deletedType,
                        'id' => (int) $deletedModel->getKey(),
                        'title' => $deletedTitle,
                    ],
                ],
            ])
            ->event('unlinked')
            ->log(sprintf('Unlinked from %s: %s', $deletedType, $deletedTitle));
    }

    /**
     * @return array{model: Model, type: string, title: string}|null
     */
    protected static function resolveLinkSide(string $type, int $id): ?array
    {
        $class = class_exists($type) ? $type : (RecordLink::linkableMap()[$type] ?? null);

        if ($class === null) {
            return null;
        }

        $model = $class::query()->find($id);

        if (! $model instanceof Model) {
            return null;
        }

        $alias = array_flip(RecordLink::linkableMap())[$class] ?? 'unknown';

        return [
            'model' => $model,
            'type' => $alias,
            'title' => RecordLinkHelper::titleForModel($model),
        ];
    }

    protected static function resolveOtherLinkSide(Model $deletedModel, RecordLink $link): ?Model
    {
        $left = static::resolveModel($link->left_type, (int) $link->left_id);
        $right = static::resolveModel($link->right_type, (int) $link->right_id);

        if ($left instanceof Model && (int) $left->getKey() === (int) $deletedModel->getKey() && $left::class === $deletedModel::class) {
            return $right;
        }

        if ($right instanceof Model && (int) $right->getKey() === (int) $deletedModel->getKey() && $right::class === $deletedModel::class) {
            return $left;
        }

        return null;
    }

    protected static function resolveModel(string $type, int $id): ?Model
    {
        $class = class_exists($type) ? $type : (RecordLink::linkableMap()[$type] ?? null);

        if ($class === null) {
            return null;
        }

        return $class::query()->find($id);
    }

    /**
     * @param  array{model: Model, type: string, title: string}  $source
     * @param  array{model: Model, type: string, title: string}  $target
     */
    protected static function logLinkOnModel(
        array $source,
        array $target,
        string $event,
        string $verb,
        Team $team,
        ?Model $causer = null,
    ): void {
        $targetUrl = RecordLinkHelper::urlForModel($target['model'], $team);

        activity()
            ->performedOn($source['model'])
            ->causedBy($causer)
            ->withProperties([
                'relation_changes' => [
                    'type' => 'link',
                    'action' => $event === 'linked' ? 'added' : 'removed',
                    'target' => [
                        'type' => $target['type'],
                        'id' => (int) $target['model']->getKey(),
                        'title' => $target['title'],
                        'url' => $targetUrl,
                    ],
                ],
            ])
            ->event($event)
            ->log(sprintf('%s %s: %s', $verb, $target['type'], $target['title']));
    }
}
