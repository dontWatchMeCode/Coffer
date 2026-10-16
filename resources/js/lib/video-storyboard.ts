export type StoryboardCue = {
    end: number;
    h: number;
    start: number;
    w: number;
    x: number;
    y: number;
};

export type VideoStoryboard = {
    cues: StoryboardCue[];
    duration: number;
    imageUrl: string;
    spriteHeight: number;
    spriteWidth: number;
};

export function parseStoryboardTime(value: string): number | null {
    const parts = value.trim().split(':');

    if (parts.length !== 3 || parts.some((part) => part.trim() === '')) {
        return null;
    }

    const [hours, minutes, rest] = parts;
    const subparts = rest.split('.');

    if (subparts.length > 2) {
        return null;
    }

    const [seconds, millis = '0'] = subparts;
    const parsed = [
        Number(hours),
        Number(minutes),
        Number(seconds),
        Number(millis),
    ];

    if (
        parsed.some((part) => !Number.isFinite(part) || part < 0) ||
        parsed[1] >= 60 ||
        parsed[2] >= 60 ||
        parsed[3] >= 1000
    ) {
        return null;
    }

    return parsed[0] * 3600 + parsed[1] * 60 + parsed[2] + parsed[3] / 1000;
}

export function parseStoryboardVtt(text: string): VideoStoryboard | null {
    const lines = text.split(/\r?\n/);
    const cues: StoryboardCue[] = [];
    let imageUrl: string | null = null;

    for (let index = 0; index < lines.length; index++) {
        if (!lines[index].includes('-->')) {
            continue;
        }

        const [rawStart, rawEnd] = lines[index].split('-->');
        const start = parseStoryboardTime(rawStart ?? '');
        const end = parseStoryboardTime(rawEnd ?? '');

        const payload = lines
            .slice(index + 1)
            .find((line) => line.trim() !== '');

        if (start === null || end === null || end <= start || !payload) {
            continue;
        }

        const fragmentIndex = payload.lastIndexOf('#xywh=');

        if (fragmentIndex === -1) {
            continue;
        }

        const url = payload.slice(0, fragmentIndex).trim();
        const coordParts = payload
            .slice(fragmentIndex + '#xywh='.length)
            .split(',');

        if (url === '' || coordParts.length !== 4) {
            continue;
        }

        const coords: number[] = [];

        for (const part of coordParts) {
            const trimmed = part.trim();

            if (!/^\d+$/.test(trimmed)) {
                coords.length = 0;
                break;
            }

            coords.push(Number(trimmed));
        }

        if (coords.length !== 4) {
            continue;
        }

        const [x, y, w, h] = coords;

        if (w <= 0 || h <= 0) {
            continue;
        }

        imageUrl ??= url;

        if (url !== imageUrl) {
            continue;
        }

        cues.push({ end, h, start, w, x, y });
    }

    if (cues.length === 0 || imageUrl === null) {
        return null;
    }

    cues.sort((a, b) => a.start - b.start);

    return {
        cues,
        duration: Math.max(...cues.map((cue) => cue.end)),
        imageUrl,
        spriteHeight: Math.max(...cues.map((cue) => cue.y + cue.h)),
        spriteWidth: Math.max(...cues.map((cue) => cue.x + cue.w)),
    };
}

export function storyboardCueForRatio(
    storyboard: VideoStoryboard,
    ratio: number,
): { cue: StoryboardCue; index: number } | null {
    if (storyboard.cues.length === 0) {
        return null;
    }

    const clamped = Math.min(1, Math.max(0, ratio));
    const time = clamped * storyboard.duration;
    const index = storyboard.cues.findIndex(
        (cue) => time >= cue.start && time < cue.end,
    );

    if (index !== -1) {
        return { cue: storyboard.cues[index], index };
    }

    const fallback = Math.min(
        storyboard.cues.length - 1,
        Math.floor(clamped * storyboard.cues.length),
    );

    return { cue: storyboard.cues[fallback], index: fallback };
}

export function formatStoryboardTime(seconds: number): string {
    const total = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const rest = total % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
    }

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

export type StoryboardTileStyle = {
    backgroundPosition: string;
    backgroundSize: string;
};

/**
 * Cover-fit tile style for a container: the tile overflows one axis, so the
 * background is shifted by half the overflow to stay centered.
 */
export function storyboardTileStyle(
    storyboard: VideoStoryboard,
    cue: StoryboardCue,
    rectWidth: number,
    rectHeight: number,
): StoryboardTileStyle | null {
    if (rectWidth <= 0 || rectHeight <= 0 || cue.w <= 0 || cue.h <= 0) {
        return null;
    }

    const scale = Math.max(rectWidth / cue.w, rectHeight / cue.h);
    const overflowX = Math.max(0, cue.w * scale - rectWidth);
    const overflowY = Math.max(0, cue.h * scale - rectHeight);

    return {
        backgroundPosition: `-${Math.round(cue.x * scale + overflowX / 2)}px -${Math.round(cue.y * scale + overflowY / 2)}px`,
        backgroundSize: `${storyboard.spriteWidth * scale}px ${storyboard.spriteHeight * scale}px`,
    };
}
