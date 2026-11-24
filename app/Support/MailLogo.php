<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Mail\TextMessage;
use WeakMap;

/**
 * Resolves the logo shown in the mail header.
 *
 * By default the email links to the hosted logo (config mail.logo_url, from
 * MAIL_LOGO_URL, defaulting to self::PATH in public/) and nothing is
 * attached. It takes a full URL, or a path inside public/ that is resolved
 * against APP_URL. Some clients hide remote images until the reader allows
 * them.
 *
 * With MAIL_LOGO_URL set to an empty value, the PNG is embedded inline (CID)
 * when the email is actually sent, so it shows even when APP_URL isn't
 * publicly reachable. Previews and tests have no message to embed into, so
 * they get the public URL instead. The plain-text part gets no logo, and
 * MAIL_LOGO_ENABLED=false (config mail.logo_enabled) leaves it out entirely.
 */
final class MailLogo
{
    /** PNG, not favicon.ico: most email clients can't display ICO files. */
    public const PATH = 'apple-touch-icon.png';

    /**
     * CID per message. Blade evaluates a component's bound attributes more
     * than once, and Message::embed() attaches the file again on every call.
     *
     * @var WeakMap<Message, string>|null
     */
    private static ?WeakMap $embedded = null;

    public static function src(mixed $message = null): ?string
    {
        if ($message instanceof TextMessage || ! config('mail.logo_enabled', true)) {
            return null;
        }

        $hosted = config('mail.logo_url');

        if (is_string($hosted) && $hosted !== '') {
            // A path inside public/ is resolved against APP_URL (ASSET_URL).
            return filter_var($hosted, FILTER_VALIDATE_URL) ? $hosted : asset($hosted);
        }

        if (! $message instanceof Message) {
            return asset(self::PATH);
        }

        self::$embedded ??= new WeakMap;

        return self::$embedded[$message] ??= $message->embed(public_path(self::PATH));
    }
}
