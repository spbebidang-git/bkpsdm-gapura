const parseMajorVersion = (userAgent, pattern) => {
    const match = userAgent.match(pattern);

    if (!match) {
        return null;
    }

    return Number.parseInt(match[1], 10);
};

const isLegacyIosVersion = (userAgent) => {
    const iosVersion = parseMajorVersion(userAgent, /OS (\d+)_/i);

    return iosVersion !== null && iosVersion < 13;
};

export const getBrowserSupportStatus = (
    userAgent = typeof navigator !== "undefined" ? navigator.userAgent : "",
) => {
    if (!userAgent) {
        return { supported: true, reason: "" };
    }

    if (/MSIE|Trident/i.test(userAgent)) {
        return {
            supported: false,
            reason: "Internet Explorer tidak lagi didukung",
        };
    }

    const legacyEdgeVersion = parseMajorVersion(userAgent, /Edge\/(\d+)/i);
    if (legacyEdgeVersion !== null) {
        return {
            supported: false,
            reason: "Microsoft Edge versi lama tidak didukung",
        };
    }

    if (isLegacyIosVersion(userAgent)) {
        return {
            supported: false,
            reason: "iOS versi lama tidak didukung",
        };
    }

    if (
        /Android/i.test(userAgent) &&
        /Version\//i.test(userAgent) &&
        /Mobile Safari/i.test(userAgent) &&
        !/Chrome|Chromium|EdgA|SamsungBrowser/i.test(userAgent)
    ) {
        return {
            supported: false,
            reason: "browser bawaan Android versi lama tidak didukung",
        };
    }

    const chromeVersion = parseMajorVersion(
        userAgent,
        /(?:Chrome|CriOS)\/(\d+)/i,
    );
    if (
        chromeVersion !== null &&
        !/Edg\//i.test(userAgent) &&
        !/OPR\//i.test(userAgent) &&
        !/SamsungBrowser/i.test(userAgent) &&
        chromeVersion < 80
    ) {
        return {
            supported: false,
            reason: "Google Chrome versi lama tidak didukung",
        };
    }

    const edgeVersion = parseMajorVersion(userAgent, /Edg\/(\d+)/i);
    if (edgeVersion !== null && edgeVersion < 80) {
        return {
            supported: false,
            reason: "Microsoft Edge versi lama tidak didukung",
        };
    }

    const firefoxVersion = parseMajorVersion(
        userAgent,
        /(?:Firefox|FxiOS)\/(\d+)/i,
    );
    if (firefoxVersion !== null && firefoxVersion < 78) {
        return {
            supported: false,
            reason: "Mozilla Firefox versi lama tidak didukung",
        };
    }

    const samsungBrowserVersion = parseMajorVersion(
        userAgent,
        /SamsungBrowser\/(\d+)/i,
    );
    if (samsungBrowserVersion !== null && samsungBrowserVersion < 13) {
        return {
            supported: false,
            reason: "Samsung Internet versi lama tidak didukung",
        };
    }

    const isSafari =
        /Safari/i.test(userAgent) &&
        !/Chrome|CriOS|Chromium|Android|Edg\//i.test(userAgent);
    if (isSafari) {
        const safariVersion = parseMajorVersion(userAgent, /Version\/(\d+)/i);

        if (safariVersion !== null && safariVersion < 13) {
            return {
                supported: false,
                reason: "Safari versi lama tidak didukung",
            };
        }
    }

    return { supported: true, reason: "" };
};

export const getUnsupportedBrowserMessage = (reason) => {
    const detail = reason ? ` (${reason}).` : ".";

    return `Browser pada perangkat Anda terlalu lama untuk mengikuti kelas ini dengan stabil${detail} Silakan perbarui Chrome, Edge, Firefox, atau Safari ke versi yang lebih baru, atau gunakan perangkat lain.`;
};
