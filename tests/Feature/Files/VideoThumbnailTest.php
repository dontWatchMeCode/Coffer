<?php

use App\Models\FileItem;
use App\Models\User;
use App\Services\VideoThumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process as SymfonyProcess;

beforeEach(function () {
    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;
        file_put_contents($command[array_key_last($command)], UploadedFile::fake()->image('frame.jpg', 32, 24)->getContent());

        return Process::result();
    });
});

function videoThumbnailFile(User $user): FileItem
{
    $file = FileItem::factory()->create([
        'team_id' => $user->currentTeam->id,
        'mime_type' => 'video/webm',
        'path' => 'files/'.$user->currentTeam->id.'/clip.webm',
    ]);
    Storage::disk('local')->put($file->path, 'video bytes');

    return $file;
}

test('video thumbnails are generated once as private cached JPEGs', function () {
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    $url = route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]);

    $response = $this->actingAs($user)->get($url)
        ->assertOk()->assertHeader('content-type', 'image/jpeg')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->headers->get('cache-control'))->toContain('private')->toContain('no-cache')->not->toContain('public');
    Storage::disk('local')->assertExists(VideoThumbnail::pathFor($file->path));
    expect(Storage::disk('local')->allFiles())->toHaveCount(2);

    $this->get($url)->assertOk();
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg' && $process->timeout === 10, 1);
});

test('thumbnail access enforces membership and excludes trash even when cached', function () {
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    $url = route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]);

    $this->get($url)->assertRedirect(route('login'));
    $this->head($url)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    Process::assertNothingRan();

    $this->actingAs($user)->get($url)->assertOk();
    $file->delete();
    $this->get($url)->assertNotFound();
    $this->head($url)->assertNotFound();
    Storage::disk('local')->assertExists(VideoThumbnail::pathFor($file->path));

    $file->forceDelete();
    Storage::disk('local')->assertMissing($file->path);
    Storage::disk('local')->assertMissing(VideoThumbnail::pathFor($file->path));
});

test('missing sources and non videos do not invoke ffmpeg', function () {
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    $url = route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]);
    $this->actingAs($user);
    Storage::disk('local')->delete($file->path);
    $this->get($url)->assertNotFound();
    $file->update(['mime_type' => 'application/pdf']);
    Storage::disk('local')->put($file->path, 'document');
    $this->get($url)->assertNotFound();
    Process::assertNothingRan();
});

test('failed thumbnail generation leaves no partial image and is briefly backed off', function () {
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;
        file_put_contents($command[array_key_last($command)], 'incomplete image');

        return Process::result(exitCode: 1);
    });
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    $url = route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]);

    $this->actingAs($user)->get($url)->assertNotFound();
    $this->get($url)->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([$file->path]);
    Process::assertRanTimes(fn (): bool => true, 1);
});

test('successful processes must still produce a valid JPEG', function () {
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;
        file_put_contents($command[array_key_last($command)], '<html>not a JPEG</html>');

        return Process::result();
    });
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);

    $this->actingAs($user)->get(route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]))->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([$file->path]);
});

test('ffmpeg startup failures use the fallback without repeated spawn attempts', function () {
    $attempts = 0;
    Process::fake(function () use (&$attempts): never {
        $attempts++;

        throw new ProcessStartFailedException(new SymfonyProcess(['ffmpeg']), 'Unable to launch ffmpeg');
    });
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    $url = route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]);

    $this->actingAs($user)->get($url)->assertNotFound();
    $this->get($url)->assertNotFound();

    expect($attempts)->toBe(1);
    expect(Storage::disk('local')->allFiles())->toBe([$file->path]);
});

test('deleting a source during generation does not publish an orphan thumbnail', function () {
    $user = User::factory()->create();
    $file = videoThumbnailFile($user);
    Process::fake(function (PendingProcess $process) use ($file) {
        $command = $process->command;
        file_put_contents($command[array_key_last($command)], UploadedFile::fake()->image('frame.jpg')->getContent());
        $file->forceDelete();

        return Process::result();
    });

    $this->actingAs($user)->get(route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]))->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([]);
});
