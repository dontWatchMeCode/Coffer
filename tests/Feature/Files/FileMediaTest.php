<?php

use App\Mcp\Servers\RecordsServer;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Models\FileItem;
use App\Models\Team;
use App\Models\User;
use App\Support\FileTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function fileMediaBytes(string $extension): string
{
    if (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {
        $parts = [
            'docx' => ['word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'],
            'xlsx' => ['xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml'],
            'pptx' => ['ppt/presentation.xml', 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml'],
        ];
        [$part, $contentType] = $parts[$extension];
        $temporary = tmpfile();
        $path = stream_get_meta_data($temporary)['uri'];
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/'.$part.'" ContentType="'.$contentType.'"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
        $zip->addFromString($part, '<?xml version="1.0"?><document/>');
        $zip->close();
        $bytes = file_get_contents($path);
        fclose($temporary);

        return $bytes;
    }

    return match ($extension) {
        'mp4' => pack('N', 24).'ftypisom'.pack('N', 512).'isomiso2'.pack('N', 16).'mdat'.str_repeat("\0", 8),
        'webm' => base64_decode('GkXfo59ChoEBQveBAULygQRC84EIQoKEd2VibUKHgQJChYEC', true),
        'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
        'txt' => "Private plain text document.\n",
    };
}

dataset('file media formats', [
    ['mp4', 'video/mp4', 'video'],
    ['webm', 'video/webm', 'video'],
    ['pdf', 'application/pdf', null],
    ['txt', 'text/plain', 'text'],
    ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null],
    ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null],
    ['pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', null],
]);

test('video and document uploads use server detected types and private storage', function (string $extension, string $mimeType, ?string $previewKind) {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $temporary = UploadedFile::fake()->createWithContent('sample.'.$extension, fileMediaBytes($extension));
    $upload = new UploadedFile($temporary->getPathname(), 'sample.'.$extension, test: true);

    $this->actingAs($user)->post(route('team.files.store', ['current_team' => $team]), [
        'title' => 'Private '.$extension,
        'file' => $upload,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $file = FileItem::whereBelongsTo($team)->firstOrFail();

    expect($file->mime_type)->toBe($mimeType)
        ->and($file->previewKind())->toBe($previewKind)
        ->and($file->disk)->toBe('local')
        ->and($file->width)->toBeNull()
        ->and($file->height)->toBeNull();
    Storage::disk('local')->assertExists($file->path);

    $this->get(route('team.files.show', ['current_team' => $team, 'file' => $file]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('file.previewKind', $previewKind)
            ->where('file.previewUrl', $previewKind === 'video' ? route('team.files.inline', ['current_team' => $team, 'file' => $file]) : null));

    $this->get(route('team.files.download', ['current_team' => $team, 'file' => $file]))
        ->assertOk()->assertDownload('sample.'.$extension);
})->with('file media formats');

test('mcp accepts the same video and document bytes without trusting declared mime type', function (string $extension, string $mimeType, ?string $previewKind) {
    Storage::fake('local');
    $user = User::factory()->create();

    RecordsServer::actingAs($user)->tool(CreateRecordTool::class, [
        'type' => 'file',
        'data' => [
            'title' => 'Private '.$extension,
            'content' => base64_encode(fileMediaBytes($extension)),
            'mime_type' => 'text/html',
        ],
    ])->assertOk();

    $file = FileItem::query()->firstOrFail();
    expect($file->mime_type)->toBe($mimeType)
        ->and($file->previewKind())->toBe($previewKind)
        ->and($file->original_name)->toBe('file.'.$extension)
        ->and($file->width)->toBeNull()
        ->and($file->height)->toBeNull();
    Storage::disk('local')->assertExists($file->path);
})->with('file media formats');

test('download only or active file types cannot be served inline', function (string $mimeType) {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = FileItem::factory()->create(['team_id' => $team->id, 'mime_type' => $mimeType]);
    Storage::disk('local')->put($file->path, '<script>alert(1)</script>');

    $this->actingAs($user)->get(route('team.files.inline', ['current_team' => $team, 'file' => $file]))->assertNotFound();
    $this->get(route('team.files.download', ['current_team' => $team, 'file' => $file]))->assertDownload();
})->with(['text/html', 'image/svg+xml', 'application/xml', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']);

test('file validation rejects disguised active content', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $temporary = UploadedFile::fake()->createWithContent('report.pdf', '<html><script>alert(1)</script></html>');
    $upload = new UploadedFile($temporary->getPathname(), 'report.pdf', test: true);

    $this->actingAs($user)->post(route('team.files.store', ['current_team' => $user->currentTeam]), [
        'title' => 'Disguised HTML',
        'file' => $upload,
    ])->assertSessionHasErrors('file');

    expect(FileItem::query()->count())->toBe(0);
});

test('mcp cannot rewrite stored file types without replacing the validated bytes', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id, 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    Storage::disk('local')->put($file->path, fileMediaBytes('docx'));

    RecordsServer::actingAs($user)->tool(UpdateRecordTool::class, [
        'type' => 'file',
        'id' => $file->id,
        'data' => ['mime_type' => 'application/pdf'],
    ])->assertHasErrors();

    expect($file->refresh()->mime_type)->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

test('text previews are bounded utf8 and only included on detail pages', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = FileItem::factory()->create(['team_id' => $team->id, 'mime_type' => 'text/plain']);
    $prefix = '<script>alert(1)</script>!';
    Storage::disk('local')->put($file->path, $prefix.str_repeat('é', FileTypes::TEXT_PREVIEW_BYTES));

    $this->actingAs($user)->get(route('team.files.show', ['current_team' => $team, 'file' => $file]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('file.textPreview.truncated', true)
            ->where('file.textPreview.content', fn (string $content): bool => strlen($content) <= FileTypes::TEXT_PREVIEW_BYTES && mb_check_encoding($content, 'UTF-8') && str_starts_with($content, $prefix)));

    $this->get(route('team.files.index', ['current_team' => $team]))
        ->assertInertia(fn (Assert $page) => $page->missing('files.data.0.textPreview'));
});

test('invalid utf8 text has a download fallback', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id, 'mime_type' => 'text/plain']);
    Storage::disk('local')->put($file->path, "Invalid \xFF text");

    $this->actingAs($user)->get(route('team.files.show', ['current_team' => $user->currentTeam, 'file' => $file]))
        ->assertInertia(fn (Assert $page) => $page->where('file.textPreview', null));
});

test('private video responses support seeking and unsatisfiable byte ranges', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $bytes = fileMediaBytes('mp4');
    $file = FileItem::factory()->create(['team_id' => $team->id, 'mime_type' => 'video/mp4']);
    Storage::disk('local')->put($file->path, $bytes);
    $url = route('team.files.inline', ['current_team' => $team, 'file' => $file]);

    $response = $this->actingAs($user)->get($url, ['Range' => 'bytes=4-11'])
        ->assertStatus(206)
        ->assertHeader('content-range', 'bytes 4-11/'.strlen($bytes))
        ->assertHeader('content-length', '8')
        ->assertHeader('content-type', 'video/mp4')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->headers->get('cache-control'))->toContain('private')->not->toContain('public');

    ob_start();
    $response->baseResponse->sendContent();
    $content = ob_get_clean();
    expect($content)->toBe(substr($bytes, 4, 8));

    $this->get($url, ['Range' => 'bytes=9999-10000'])->assertStatus(416)->assertHeader('content-range', 'bytes */'.strlen($bytes));
    $this->get($url, ['Range' => 'bytes=-4'])->assertStatus(206)->assertHeader('content-range', 'bytes '.(strlen($bytes) - 4).'-'.(strlen($bytes) - 1).'/'.strlen($bytes));
    $this->get($url, ['Range' => 'bytes=4-'])->assertStatus(206)->assertHeader('content-length', (string) (strlen($bytes) - 4));
    $this->get($url, ['Range' => 'bytes=4-11', 'If-Range' => 'Thu, 01 Jan 1970 00:00:00 GMT'])->assertOk()->assertHeader('content-length', (string) strlen($bytes));
    $this->head($url)->assertOk()->assertHeader('accept-ranges', 'bytes');
});

test('PDF files are download only with private headers and no inline preview', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = FileItem::factory()->create(['team_id' => $user->currentTeam->id, 'mime_type' => 'application/pdf']);
    Storage::disk('local')->put($file->path, fileMediaBytes('pdf'));

    $this->actingAs($user)->get(route('team.files.inline', ['current_team' => $user->currentTeam, 'file' => $file]))->assertNotFound();
    $response = $this->get(route('team.files.download', ['current_team' => $user->currentTeam, 'file' => $file]))
        ->assertDownload()
        ->assertOk()->assertHeader('content-type', 'application/pdf')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->headers->get('content-security-policy'))->toContain('sandbox;')->not->toContain('allow-same-origin');
    expect($response->headers->get('cache-control'))->toContain('private');
});

test('videos and documents retain the 100 MB upload limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('team.files.store', ['current_team' => $user->currentTeam]), [
        'title' => 'Oversized video',
        'file' => UploadedFile::fake()->create('large.mp4', FileTypes::MAX_UPLOAD_KILOBYTES + 1, 'video/mp4'),
    ])->assertSessionHasErrors('file');
});

test('video and document byte access remains team scoped and excludes trash', function (string $mimeType) {
    Storage::fake('local');
    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $file = FileItem::factory()->create(['team_id' => $team->id, 'mime_type' => $mimeType]);
    Storage::disk('local')->put($file->path, 'private content');
    $url = route('team.files.inline', ['current_team' => $team, 'file' => $file]);
    $download = route('team.files.download', ['current_team' => $team, 'file' => $file]);

    $this->get($url, ['Range' => 'bytes=0-3'])->assertRedirect(route('login'));
    $this->head($url)->assertRedirect(route('login'));
    $this->get($download)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get($url, ['Range' => 'bytes=0-3'])->assertForbidden();
    $this->head($url)->assertForbidden();
    $this->get($download)->assertForbidden();

    $this->actingAs($owner);
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($owner, ['role' => 'member']);
    $this->get(route('team.files.inline', ['current_team' => $otherTeam, 'file' => $file]))->assertNotFound();

    $file->delete();
    $this->get($url, ['Range' => 'bytes=0-3'])->assertNotFound();
    $this->head($url)->assertNotFound();
    $this->get($download)->assertNotFound();
})->with(['video/mp4', 'application/pdf']);
