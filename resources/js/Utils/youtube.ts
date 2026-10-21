export function getYouTubeEmbedUrl(value?: string | null): string | null {
    if (!value) return null;

    try {
        const url = new URL(value.trim());
        if (url.protocol !== 'https:') return null;

        const host = url.hostname.toLowerCase().replace(/^www\./, '');
        const parts = url.pathname.split('/').filter(Boolean);
        let videoId: string | null = null;

        if (host === 'youtu.be') {
            videoId = parts[0] ?? null;
        } else if (host === 'youtube.com' || host === 'm.youtube.com' || host === 'youtube-nocookie.com') {
            if (parts[0] === 'embed') videoId = parts[1] ?? null;
            else if (parts[0] === 'watch') videoId = url.searchParams.get('v');
            else if (['shorts', 'live'].includes(parts[0])) videoId = parts[1] ?? null;
        }

        if (!videoId || !/^[\w-]{11}$/.test(videoId)) return null;
        return `https://www.youtube.com/embed/${videoId}`;
    } catch {
        return null;
    }
}
