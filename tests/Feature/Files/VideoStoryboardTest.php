<?php

use App\Models\FileItem;
use App\Models\User;
use App\Services\VideoThumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;

        if ($command[0] === 'ffprobe') {
            return Process::result(output: '{"format":{"duration":"20.0"}}');
        }

        file_put_contents($command[array_key_last($command)], UploadedFile::fake()->image('sprite.jpg', 1280, 180)->getContent());

        return Process::result();
    });
});

function storyboardVideoFile(User $user): FileItem
{
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id, 'mime_type' => 'video/mp4']);
    Storage::disk('local')->put($file->path, 'video bytes');

    return $file;
}

test('private storyboards generate cached sprite cues spanning the whole video', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = storyboardVideoFile($user);
    $vttUrl = route('team.files.storyboard', ['current_team' => $team, 'file' => $file]);
    $imageUrl = route('team.files.storyboard-image', ['current_team' => $team, 'file' => $file]);
    $response = $this->actingAs($user)->get($vttUrl)->assertOk()
        ->assertHeader('content-type', 'text/vtt; charset=UTF-8')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->getContent())->toStartWith("WEBVTT\n")
        ->toContain('00:00:00.000 --> 00:00:05.000')
        ->toContain('00:00:15.000 --> 00:00:20.000')
        ->toContain('#xywh=960,0,320,180')
        ->not->toContain('storage/')
        ->not->toContain($file->path);
    expect($response->headers->get('cache-control'))->toContain('private')->not->toContain('public');

    $this->get($imageUrl)->assertOk()->assertHeader('content-type', 'image/jpeg')
        ->assertHeader('x-content-type-options', 'nosniff');
    expect($this->get($imageUrl)->headers->get('cache-control'))->toContain('private')->not->toContain('public');
    $this->get($vttUrl)->assertOk();
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffprobe' && $process->timeout === 5, 1);
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg' && $process->timeout === 20, 1);
    Storage::disk('local')->assertExists(VideoThumbnail::storyboardPathFor($file->path));
    Storage::disk('local')->assertExists(VideoThumbnail::metadataPathFor($file->path));
    expect(Storage::disk('local')->allFiles())->toHaveCount(3);
    expect(json_decode(Storage::disk('local')->get(VideoThumbnail::metadataPathFor($file->path)), true))
        ->toMatchArray(['count' => 4, 'columns' => 4, 'width' => 320, 'height' => 180, 'v' => 2]);
});

test('storyboard URLs are advertised on video list and detail payloads', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = storyboardVideoFile($user);
    $this->actingAs($user)->get(route('team.files.show', ['current_team' => $team, 'file' => $file]))
        ->assertInertia(fn (Assert $page) => $page->where('file.storyboardUrl', fn (string $url): bool => str_contains($url, '/'.$team->slug.'/files/'.$file->id.'/storyboard.vtt?v=')));
    $this->get(route('team.files.index', ['current_team' => $team]))
        ->assertInertia(fn (Assert $page) => $page->where('files.data.0.storyboardUrl', fn (string $url): bool => str_contains($url, '/'.$team->slug.'/files/'.$file->id.'/storyboard.vtt?v=')));
    Process::assertNothingRan();

    $image = FileItem::factory()->create(['team_id' => $team->id]);
    Storage::disk('local')->put($image->path, 'image bytes');
    $this->get(route('team.files.show', ['current_team' => $team, 'file' => $image]))
        ->assertInertia(fn (Assert $page) => $page->where('file.storyboardUrl', null));
});

test('storyboard assets remain authenticated team scoped and unavailable in trash', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = storyboardVideoFile($user);
    $urls = [route('team.files.storyboard', ['current_team' => $team, 'file' => $file]), route('team.files.storyboard-image', ['current_team' => $team, 'file' => $file])];

    foreach ($urls as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->actingAs(User::factory()->create());

    foreach ($urls as $url) {
        $this->get($url)->assertForbidden();
    }

    Process::assertNothingRan();
    $this->actingAs($user)->get($urls[0])->assertOk();
    $file->delete();

    foreach ($urls as $url) {
        $this->get($url)->assertNotFound();
    }

    Storage::disk('local')->assertExists(VideoThumbnail::storyboardPathFor($file->path));
    $file->forceDelete();
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('non video files have no storyboard without probing', function () {
    $user = User::factory()->create();
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id]);
    Storage::disk('local')->put($file->path, 'image bytes');
    $urls = [route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]), route('team.files.storyboard-image', ['current_team' => $user->currentTeam, 'file' => $file])];

    foreach ($urls as $url) {
        $this->actingAs($user)->get($url)->assertNotFound();
    }

    Process::assertNothingRan();
});

test('invalid duration data fails safely without running extraction or repeatedly probing', function (string $duration) {
    Process::fake(fn () => Process::result(output: $duration));
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $url = route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]);
    $this->actingAs($user)->get($url)->assertNotFound();
    $this->get($url)->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([$file->path]);
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffprobe', 1);
    Process::assertNotRan(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg');
})->with(['{}', '{"format":{"duration":"NaN"}}', '{"format":{"duration":"0"}}', '{"format":{"duration":"9999999"}}', 'invalid JSON']);

test('failed sprite generation leaves no temporary or metadata assets', function () {
    Process::fake(function (PendingProcess $process) {
        if ($process->command[0] === 'ffprobe') {
            return Process::result(output: '{"format":{"duration":"20"}}');
        }

        return Process::result(exitCode: 1);
    });
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $this->actingAs($user)->get(route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]))->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBe([$file->path]);
});

test('webm storyboards probe and extract with the matroska format', function () {
    $user = User::factory()->create();
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id, 'mime_type' => 'video/webm']);
    Storage::disk('local')->put($file->path, 'video bytes');
    $this->actingAs($user)->get(route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[0] === 'ffprobe' && in_array('matroska', $process->command, true));
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg' && in_array('matroska', $process->command, true));
});

test('corrupt storyboard metadata is cleaned up and heals on first hit', function () {
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    Storage::disk('local')->put(VideoThumbnail::storyboardPathFor($file->path), 'stale sprite');
    Storage::disk('local')->put(VideoThumbnail::metadataPathFor($file->path), 'not json');
    $url = route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]);
    $this->actingAs($user)->get($url)->assertOk();
    expect(json_decode(Storage::disk('local')->get(VideoThumbnail::metadataPathFor($file->path)), true))
        ->toMatchArray(['count' => 4, 'columns' => 4]);
});

test('pre-upgrade storyboards without a layout version heal on first hit', function () {
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    Storage::disk('local')->put(VideoThumbnail::storyboardPathFor($file->path), 'stale sprite');
    Storage::disk('local')->put(VideoThumbnail::metadataPathFor($file->path), json_encode(['duration' => 20.0, 'count' => 4, 'columns' => 4, 'width' => 160, 'height' => 90]));
    $url = route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]);
    $response = $this->actingAs($user)->get($url)->assertOk();
    expect($response->getContent())->toContain('#xywh=960,0,320,180');
    expect(json_decode(Storage::disk('local')->get(VideoThumbnail::metadataPathFor($file->path)), true))
        ->toMatchArray(['count' => 4, 'columns' => 4, 'width' => 320, 'height' => 180, 'v' => 2]);
});

test('plain thumbnails never create storyboard assets', function () {
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $this->actingAs($user)->get(route('team.files.thumbnail', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    Storage::disk('local')->assertMissing(VideoThumbnail::storyboardPathFor($file->path));
    Storage::disk('local')->assertMissing(VideoThumbnail::metadataPathFor($file->path));
});

test('storyboard image requests also generate the shared sprite', function () {
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $this->actingAs($user)->get(route('team.files.storyboard-image', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    $this->get(route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffprobe', 1);
    Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg', 1);
});

test('partial sprite rows address the second row start', function () {
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;

        if ($command[0] === 'ffprobe') {
            return Process::result(output: '{"format":{"duration":"30"}}');
        }

        file_put_contents($command[array_key_last($command)], UploadedFile::fake()->image('sprite.jpg', 1600, 360)->getContent());

        return Process::result();
    });
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $response = $this->actingAs($user)->get(route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    expect(substr_count($response->getContent(), ' --> '))->toBe(6);
    expect($response->getContent())->toContain('#xywh=0,180,320,180');
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg' && str_contains(implode(' ', $process->command), 'tile=5x2'));
});

test('storyboard extraction is bounded to thirty tiles for longer videos', function () {
    Process::fake(function (PendingProcess $process) {
        $command = $process->command;

        if ($command[0] === 'ffprobe') {
            return Process::result(output: '{"format":{"duration":"7200"}}');
        }

        file_put_contents($command[array_key_last($command)], UploadedFile::fake()->image('sprite.jpg', 1600, 1080)->getContent());

        return Process::result();
    });
    $user = User::factory()->create();
    $file = storyboardVideoFile($user);
    $response = $this->actingAs($user)->get(route('team.files.storyboard', ['current_team' => $user->currentTeam, 'file' => $file]))->assertOk();
    expect(substr_count($response->getContent(), ' --> '))->toBe(30);
    expect($response->getContent())->toContain('02:00:00.000');
    Process::assertRan(fn (PendingProcess $process): bool => $process->command[0] === 'ffmpeg' && in_array('fps=30/7200.000000,scale=320:180:force_original_aspect_ratio=decrease,pad=320:180:(ow-iw)/2:(oh-ih)/2,tile=5x6', $process->command, true));
});
