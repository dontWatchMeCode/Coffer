<?php

use App\Models\FileItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('files page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    actingAs($user)
        ->get(route('team.files.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('files/Index')
            ->has('files')
            ->where('uploadConstraints.acceptedMimeTypes', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->where('uploadConstraints.acceptedExtensions', ['jpg', 'jpeg', 'png', 'gif', 'webp'])
            ->where('uploadConstraints.maxMegabytes', 100));
});

test('files page shows files for current team without public storage urls', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    FileItem::factory()->create([
        'team_id' => $team->id,
        'title' => 'Moodboard',
        'original_name' => 'moodboard.jpg',
    ]);

    FileItem::factory()->create([
        'title' => 'Other Team File',
    ]);

    actingAs($user)
        ->get(route('team.files.index', ['current_team' => $team]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('files/Index')
            ->has('files.data', 1)
            ->where('files.data.0.title', 'Moodboard')
            ->where('files.data.0.originalName', 'moodboard.jpg')
            ->where('files.data.0.previewUrl', route('team.files.inline', ['current_team' => $team, 'file' => FileItem::where('team_id', $team->id)->first()->id]))
            ->where('files.data.0.downloadUrl', route('team.files.download', ['current_team' => $team, 'file' => FileItem::where('team_id', $team->id)->first()->id])));
});

test('team members can upload image files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $upload = UploadedFile::fake()->image('launch.jpg', 800, 600)->size(1024);

    actingAs($user)
        ->post(route('team.files.store', ['current_team' => $team]), [
            'title' => 'Launch Image',
            'description' => 'Private launch visual',
            'file' => $upload,
        ])
        ->assertRedirect();

    $file = FileItem::where('team_id', $team->id)->first();

    expect($file)->not->toBeNull();
    expect($file->title)->toBe('Launch Image');
    expect($file->description)->toBe('Private launch visual');
    expect($file->disk)->toBe('local');
    expect($file->path)->toStartWith('files/'.$team->id.'/');
    expect($file->original_name)->toBe('launch.jpg');
    expect($file->mime_type)->toBe('image/jpeg');
    expect($file->width)->toBe(800);
    expect($file->height)->toBe(600);

    Storage::disk('local')->assertExists($file->path);
});

test('upload rejects svg files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $upload = UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml');

    actingAs($user)
        ->post(route('team.files.store', ['current_team' => $team]), [
            'title' => 'Bad SVG',
            'file' => $upload,
        ])
        ->assertSessionHasErrors(['file']);
});

test('patch rejects attempts to replace the file bytes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/locked.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/locked.jpg',
        'original_name' => 'locked.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $replacement = UploadedFile::fake()->image('replacement.png', 100, 100);

    actingAs($user)
        ->patch(route('team.files.update', ['current_team' => $team, 'file' => $file]), [
            'title' => 'Locked',
            'file' => $replacement,
        ])
        ->assertSessionHasErrors(['file']);

    $file->refresh();

    expect($file->original_name)->toBe('locked.jpg');
    expect($file->mime_type)->toBe('image/jpeg');
    Storage::disk('local')->assertExists('files/locked.jpg');
});

test('team members can update file details without replacing bytes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/update.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/update.jpg',
        'title' => 'Old title',
        'description' => 'Old description',
    ]);

    actingAs($user)
        ->patch(route('team.files.update', ['current_team' => $team, 'file' => $file]), [
            'title' => 'Updated title',
            'description' => 'Updated description',
        ])
        ->assertRedirect(route('team.files.show', ['current_team' => $team, 'file' => $file]));

    $file->refresh();

    expect($file->title)->toBe('Updated title');
    expect($file->description)->toBe('Updated description');
    expect($file->path)->toBe('files/update.jpg');
    Storage::disk('local')->assertExists('files/update.jpg');
});

test('team members can restore deleted files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/restore.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/restore.jpg',
    ]);
    $file->delete();

    actingAs($user)
        ->patch(route('team.files.restore', ['current_team' => $team, 'file' => $file->id]))
        ->assertRedirect(route('team.files.trash', ['current_team' => $team]));

    $this->assertNotSoftDeleted('file_items', ['id' => $file->id]);
    Storage::disk('local')->assertExists('files/restore.jpg');
});

test('team members can upload png image files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $upload = UploadedFile::fake()->image('reaction.png', 800, 600)->size(2048);

    actingAs($user)
        ->post(route('team.files.store', ['current_team' => $team]), [
            'title' => 'Reaction Image',
            'file' => $upload,
        ])
        ->assertRedirect();

    $file = FileItem::where('team_id', $team->id)->first();

    expect($file)->not->toBeNull();
    expect($file->original_name)->toBe('reaction.png');
    expect($file->mime_type)->toBe('image/png');

    Storage::disk('local')->assertExists($file->path);
});

test('inline and download routes serve private file bytes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/private.jpg', 'private-image');

    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/private.jpg',
        'original_name' => 'private.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $inlineResponse = actingAs($user)
        ->get(route('team.files.inline', ['current_team' => $team, 'file' => $file]))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($inlineResponse->headers->get('cache-control'))
        ->toContain('private')
        ->toContain('no-cache')
        ->not->toContain('public');

    $downloadResponse = actingAs($user)
        ->get(route('team.files.download', ['current_team' => $team, 'file' => $file]))
        ->assertOk()
        ->assertDownload('private.jpg')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($downloadResponse->headers->get('cache-control'))
        ->toContain('private')
        ->toContain('no-cache')
        ->not->toContain('public');
});

test('guests and non-members cannot access file pages or bytes', function () {
    Storage::fake('local');

    $team = Team::factory()->create();
    Storage::disk('local')->put('files/private.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/private.jpg',
    ]);

    $this
        ->get(route('team.files.show', ['current_team' => $team, 'file' => $file]))
        ->assertRedirect(route('login'));

    $this
        ->get(route('team.files.inline', ['current_team' => $team, 'file' => $file]))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();

    actingAs($user)
        ->get(route('team.files.show', ['current_team' => $team, 'file' => $file]))
        ->assertForbidden();

    actingAs($user)
        ->get(route('team.files.inline', ['current_team' => $team, 'file' => $file]))
        ->assertForbidden();
});

test('team members can soft delete a file while retaining its bytes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/soft.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/soft.jpg',
    ]);

    actingAs($user)
        ->delete(route('team.files.destroy', ['current_team' => $team, 'file' => $file]))
        ->assertRedirect(route('team.files.index', ['current_team' => $team]));

    $this->assertSoftDeleted('file_items', ['id' => $file->id]);
    Storage::disk('local')->assertExists('files/soft.jpg');
});

test('soft deleted files do not expose bytes through normal routes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/deleted.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/deleted.jpg',
    ]);
    $file->delete();

    actingAs($user)
        ->get(route('team.files.inline', ['current_team' => $team, 'file' => $file->id]))
        ->assertNotFound();

    Storage::disk('local')->assertExists('files/deleted.jpg');
});

test('force deleting files removes the private uploaded file', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    Storage::disk('local')->put('files/remove.jpg', 'private-image');
    $file = FileItem::factory()->create([
        'team_id' => $team->id,
        'path' => 'files/remove.jpg',
    ]);
    $file->delete();

    actingAs($user)
        ->delete(route('team.files.force-destroy', ['current_team' => $team, 'file' => $file->id]))
        ->assertRedirect(route('team.files.trash', ['current_team' => $team]));

    Storage::disk('local')->assertMissing('files/remove.jpg');
    $this->assertDatabaseMissing('file_items', ['id' => $file->id]);
});
