<?php

use App\Models\FileItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('plays and seeks private video and preserves document download fallbacks', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $bytes = base64_decode(file_get_contents(__DIR__.'/fixtures/black.webm.base64'), true);
    $video = FileItem::factory()->create([
        'team_id' => $team->id,
        'title' => 'Private video',
        'path' => 'files/clip.webm',
        'original_name' => 'clip.webm',
        'mime_type' => 'video/webm',
        'size' => strlen($bytes),
        'width' => null,
        'height' => null,
    ]);
    Storage::disk('local')->put($video->path, $bytes);
    $document = FileItem::factory()->create([
        'team_id' => $team->id,
        'title' => 'Private document',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    $this->actingAs($user);
    $page = visit('/'.$team->slug.'/files')->assertSee('Private video');
    $thumbnailReady = false;
    $deadline = microtime(true) + 10;

    while (microtime(true) < $deadline) {
        $thumbnailReady = $page->script('Array.from(document.querySelectorAll("[data-testid=file-board-item] img")).some((image) => image.src.includes("/thumbnail") && image.complete && image.naturalWidth > 0)');

        if ($thumbnailReady === true) {
            break;
        }

        usleep(100_000);
    }

    expect($thumbnailReady)->toBeTrue('Expected a private JPEG video thumbnail on the card.');
    $page->assertNotPresent('[data-testid="file-board-item"] video');
    $page->click('text=Private video')
        ->assertSee('Video playback depends on browser and codec support.')
        ->assertPresent('[data-testid="file-preview"] video');
    $ready = false;
    $deadline = microtime(true) + 5;

    while (microtime(true) < $deadline) {
        $ready = $page->script('document.querySelector("video").readyState >= 1');

        if ($ready === true) {
            break;
        }

        usleep(100_000);
    }

    expect($ready)->toBeTrue('Expected private WebM metadata to load.');
    expect($page->script('document.querySelector("video").duration'))->toBeGreaterThan(1);

    $page->script('document.querySelector("video").currentTime = 1');
    $seeked = false;
    $deadline = microtime(true) + 5;

    while (microtime(true) < $deadline) {
        $seeked = $page->script('!document.querySelector("video").seeking && document.querySelector("video").currentTime >= 1');

        if ($seeked === true) {
            break;
        }

        usleep(100_000);
    }

    expect($seeked)->toBeTrue('Expected private WebM seeking to finish.');
    expect($page->script(<<<'JS'
        (async () => {
            const video = document.querySelector('video');
            video.muted = true;
            await video.play();
            return !video.paused;
        })()
    JS))->toBeTrue();
    $page->assertNoJavaScriptErrors();

    visit('/'.$team->slug.'/files/'.$document->id)
        ->assertSee('Download it to open locally.')
        ->assertPresent('a[href$="/download"]')
        ->assertNoJavaScriptErrors();
});

it('renders mixed file kinds on the board and escapes text previews', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    FileItem::factory()->create(['team_id' => $team->id, 'title' => 'Board video', 'mime_type' => 'video/mp4']);
    FileItem::factory()->create(['team_id' => $team->id, 'title' => 'Board document', 'mime_type' => 'application/pdf']);
    $text = FileItem::factory()->create(['team_id' => $team->id, 'title' => 'Board text', 'mime_type' => 'text/plain']);
    Storage::disk('local')->put($text->path, '<script>window.untrustedFileExecuted = true</script>');
    $this->actingAs($user);

    $page = visit('/'.$team->slug.'/files')
        ->assertSee('Board video')
        ->assertSee('Board document')
        ->assertSee('Board text')
        ->assertNoJavaScriptErrors();

    $page->click('text=Board text')->assertPresent('[data-testid="file-preview"] pre');

    expect($page->script('document.querySelector("[data-testid=file-preview] pre").textContent'))->toContain('<script>');
    expect($page->script('window.untrustedFileExecuted === true'))->toBeFalse();
    $page->assertNoJavaScriptErrors();
});

it('keeps a consistent masonry gap with variable title and description heights', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $imageBytes = UploadedFile::fake()->image('pixel.png', 1, 1)->getContent();

    foreach (range(1, 16) as $number) {
        $isImage = $number % 2 === 0;
        $file = FileItem::factory()->create([
            'team_id' => $team->id,
            'title' => $number % 3 === 0 ? 'Short '.$number : 'A much longer file title that wraps across more than one line '.$number,
            'description' => $number % 3 === 0 ? null : 'A description long enough to wrap onto two lines and change the measured card height.',
            'mime_type' => $isImage ? 'image/png' : 'text/plain',
            'width' => $isImage ? 400 : null,
            'height' => $isImage ? ($number % 3 === 0 ? 600 : 300) : null,
        ]);

        if ($isImage) {
            Storage::disk('local')->put($file->path, $imageBytes);
        }
    }

    $this->actingAs($user);
    $page = visit('/'.$team->slug.'/files')->assertSee('A much longer file title');
    $gaps = $page->script(<<<'JS'
        (async () => {
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            const columns = new Map();
            document.querySelectorAll('[data-testid=file-board-item]').forEach((element) => {
                const rect = element.getBoundingClientRect();
                const key = Math.round(rect.left);
                if (!columns.has(key)) columns.set(key, []);
                columns.get(key).push({ top: rect.top, bottom: rect.bottom });
            });
            return Array.from(columns.values()).flatMap((items) => {
                items.sort((a, b) => a.top - b.top);
                return items.slice(1).map((item, index) => item.top - items[index].bottom);
            });
        })()
    JS);

    expect($gaps)->not->toBeEmpty();

    foreach ($gaps as $gap) {
        expect($gap)->toBeGreaterThanOrEqual(15.5)->toBeLessThanOrEqual(16.5);
    }

    $page->assertNoJavaScriptErrors();
});
