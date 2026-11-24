<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Mail\TextMessage;
use WeakMap;

/**
 * Resolves the logo shown in the mail header.
 *
 * When the email is actually sent, the PNG is embedded inline (CID) so it
 * shows even when APP_URL isn't publicly reachable and remote images are
 * blocked. Previews and tests have no message to embed into, so they get the
 * public URL instead. The plain-text part gets no logo.
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
        if ($message instanceof TextMessage) {
            return null;
        }

        if (! $message instanceof Message) {
            return asset(self::PATH);
        }

        self::$embedded ??= new WeakMap;

        return self::$embedded[$message] ??= $message->embed(public_path(self::PATH));
    }
}
