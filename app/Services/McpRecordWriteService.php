<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FileItem;
use App\Models\Note;
use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Throwable;

class McpRecordWriteService
{
    public function __construct(
        private readonly McpRequestContext $requestContext,
        private readonly McpTokenPermissionService $permissions,
        private readonly McpFileContent $fileContent,
    ) {}

    public function create(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTypes())],
            'data' => ['required', 'array'],
        ]);
        $class = McpRecordResolver::classFor($validated['type']);

        if ($class === null) {
            return Response::error('Invalid record type.');
        }

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type'])) {
            return Response::error('Record not found.');
        }

        Gate::forUser($user)->authorize('create', $class);

        $validator = Validator::make(
            $validated['data'],
            McpRecordValidator::rulesFor($validated['type'], false, $team, $validated['data']),
            McpRecordValidator::messagesFor($validated['type']),
        );

        McpRecordValidator::applyConditionalRules($validator, $validated['type']);
        $data = $validator->validate();

        if (! $this->permissions->can($validated['type'], 'write', data: $data)) {
            return Response::error('Permission denied.');
        }

        $data['team_id'] = $team->id;
        $blocks = [];
        $subscriptionCategoryId = null;

        if ($validated['type'] === 'note') {
            $blocks = $data['blocks'] ?? [];
            unset($data['blocks']);
        }

        if ($validated['type'] === 'task') {
            $data['created_by'] ??= $user->id;
            $data['progress'] ??= 0;
            $data['position'] ??= 0;
        }

        if ($validated['type'] === 'subscription' && isset($data['category'])) {
            $subscriptionCategoryId = SubscriptionCategory::resolveIdForTeam($data['category'], $team);
            unset($data['category']);
        }

        $storedPath = null;

        if ($validated['type'] === 'file' && filled($data['content'] ?? null)) {
            $fileAttributes = $this->fileContent->storeForTeam(
                $team,
                (string) $data['content'],
                $data['original_name'] ?? null,
            );
            unset($data['content']);
            $data = [...$data, ...$fileAttributes];
            $storedPath = $fileAttributes['path'];
        }

        try {
            /** @var Model $model */
            $model = $class::create($data);
        } catch (Throwable $throwable) {
            if ($storedPath !== null) {
                Storage::disk(McpFileContent::DISK)->delete($storedPath);
            }

            throw $throwable;
        }

        if ($validated['type'] === 'note' && $blocks !== []) {
            assert($model instanceof Note);
            $model->syncBlocks($blocks);
        }

        if ($validated['type'] === 'subscription' && $subscriptionCategoryId !== null) {
            assert($model instanceof Subscription);
            $model->subscription_category_id = $subscriptionCategoryId;
            $model->save();
        }

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function update(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(RecordTypeRegistry::mcpTypes())],
            'id' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'array'],
        ]);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type']) || ! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        $validator = Validator::make(
            $validated['data'],
            McpRecordValidator::rulesFor($validated['type'], true, $team, $validated['data'], $model),
            McpRecordValidator::messagesFor($validated['type']),
        );
        McpRecordValidator::applyConditionalRules($validator, $validated['type']);
        $data = $validator->validate();

        if (! $this->permissions->can($validated['type'], 'write', $model, $data)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('update', $model);
        $blocks = null;

        if ($validated['type'] === 'note') {
            $blocks = $data['blocks'] ?? null;
            unset($data['blocks']);
        }

        $newPath = null;
        $oldDisk = null;
        $oldPath = null;

        if ($validated['type'] === 'file' && filled($data['content'] ?? null)) {
            assert($model instanceof FileItem);
            $oldDisk = $model->disk;
            $oldPath = $model->path;
            $fileAttributes = $this->fileContent->storeForTeam(
                $team,
                (string) $data['content'],
                $data['original_name'] ?? $model->original_name,
            );
            unset($data['content']);
            $data = [...$data, ...$fileAttributes];
            $newPath = $fileAttributes['path'];
        }

        $model->fill($data);
        $oldCategoryId = null;

        if ($validated['type'] === 'subscription' && array_key_exists('category', $data)) {
            assert($model instanceof Subscription);
            $oldCategoryId = $model->subscription_category_id;
            $model->subscription_category_id = SubscriptionCategory::resolveIdForTeam($data['category'], $team);
            unset($data['category']);
        }

        try {
            $model->save();
        } catch (Throwable $throwable) {
            if ($newPath !== null) {
                Storage::disk(McpFileContent::DISK)->delete($newPath);
            }

            throw $throwable;
        }

        if ($newPath !== null && $oldDisk !== null && $oldPath !== null) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        if ($validated['type'] === 'note' && $blocks !== null) {
            assert($model instanceof Note);
            $model->syncBlocks($blocks);
        }

        if ($validated['type'] === 'subscription' && $model instanceof Subscription && $oldCategoryId !== null && $oldCategoryId !== $model->subscription_category_id) {
            SubscriptionCategory::deleteUnused($team->id);
        }

        return Response::structured(['record' => McpRecordPayload::forModel($model->fresh(), $team)]);
    }

    public function delete(Request $request): Response|ResponseFactory
    {
        $context = $this->requestContext->resolve($request);

        if ($context instanceof Response) {
            return $context;
        }

        [$user, $team] = $context;
        $validated = McpRecordResolver::validateTypeAndId($request);
        $model = McpRecordResolver::resolveRecord($team, $validated['type'], (int) $validated['id']);

        if (! RecordTypeRegistry::teamAllowsType($team, $validated['type']) || ! $model instanceof Model) {
            return Response::error('Record not found.');
        }

        if (! $this->permissions->can($validated['type'], 'write', $model)) {
            return Response::error('Permission denied.');
        }

        Gate::forUser($user)->authorize('delete', $model);
        $model->delete();

        return Response::structured(['deleted' => ['type' => $validated['type'], 'id' => (int) $validated['id']]]);
    }
}
