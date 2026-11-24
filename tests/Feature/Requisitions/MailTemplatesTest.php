<?php

use App\Enums\RequisitionStatus;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Notifications\RequisitionReceived;
use App\Notifications\RequisitionStatusUpdated;
use App\Support\MailLogo;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Pin the logo settings so local .env values (MAIL_LOGO_*) cannot change the results.
    config(['mail.logo_enabled' => true, 'mail.logo_url' => MailLogo::PATH]);

    InventoryCategories::factory()->create();

    $this->requisition = Requisitions::create([
        'request_number' => 'REQ-TPL-0001',
        'requester_name' => 'Juan Dela Cruz',
        'requester_email' => 'req@gmail.com',
        'status' => RequisitionStatus::Approved,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now(),
    ]);

    $this->requisition->items()->create([
        'inventory_item_id' => InventoryItems::factory()->create(['name' => 'A4 Copy Paper'])->id,
        'requested_qty' => 10,
        'available_qty' => 10,
        'approved_qty' => 8,
        'released_qty' => 0,
        'outstanding_qty' => 8,
    ]);

    $this->notifiable = (new AnonymousNotifiable)->route('mail', 'req@gmail.com');
});

/**
 * CSS and attributes with poor email-client support (Mailtrap HTML Check).
 * The compat theme and published components must not bring them back.
 */
function unsupportedMailCss(): array
{
    return ['position: relative', 'box-sizing', 'box-shadow', '-premailer', 'word-break', '100vw', 'rgba(', '-webkit-text-size-adjust', 'target='];
}

test('the status email renders with client-safe markup', function () {
    $html = (string) (new RequisitionStatusUpdated($this->requisition))->toMail($this->notifiable)->render();

    expect($html)
        ->toContain('<!doctype html>')
        ->toContain('REQ-TPL-0001')
        ->toContain('Approved')
        ->toContain('A4 Copy Paper')
        ->toContain('Your request REQ-TPL-0001 is now Approved.')
        ->toContain('?code='.urlencode($this->requisition->tracking_code))
        ->toContain('v:roundrect')
        ->toContain('bgcolor="#18181b"');

    foreach (unsupportedMailCss() as $css) {
        expect($html)->not->toContain($css);
    }
});

test('the received email renders with client-safe markup', function () {
    $html = (string) (new RequisitionReceived($this->requisition))->toMail($this->notifiable)->render();

    expect($html)
        ->toContain('REQ-TPL-0001')
        ->toContain($this->requisition->tracking_code)
        ->toContain('We received request REQ-TPL-0001.');

    foreach (unsupportedMailCss() as $css) {
        expect($html)->not->toContain($css);
    }
});

test('the plain-text part has the details and a raw tracking link', function () {
    $mail = (new RequisitionStatusUpdated($this->requisition))->toMail($this->notifiable);
    $text = (string) app(Markdown::class)->renderText($mail->markdown, $mail->data());

    expect($text)
        ->toContain('REQ-TPL-0001')
        ->toContain('A4 Copy Paper')
        ->toContain(url('/').'?code='.urlencode($this->requisition->tracking_code))
        ->not->toContain('<table');
});

test('the logo falls back to its public URL when there is no message to embed into', function () {
    $html = (string) (new RequisitionReceived($this->requisition))->toMail($this->notifiable)->render();

    expect($html)
        ->toContain('src="'.asset(MailLogo::PATH).'"')
        ->toContain(config('app.name').' logo');
});

test('by default a sent email links the public icon and attaches nothing', function () {
    config(['mail.default' => 'array', 'mail.logo_url' => MailLogo::PATH]);

    Notification::route('mail', 'req@gmail.com')->notifyNow(new RequisitionReceived($this->requisition));

    $sent = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();

    expect($sent->getHtmlBody())->toContain('src="'.asset(MailLogo::PATH).'"')
        ->and($sent->getAttachments())->toBeEmpty();
});

test('with an empty logo url a sent email embeds the logo inline and keeps it out of the text part', function () {
    config(['mail.default' => 'array', 'mail.logo_url' => '']);

    Notification::route('mail', 'req@gmail.com')->notifyNow(new RequisitionStatusUpdated($this->requisition));

    $sent = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    $inline = collect($sent->getAttachments())->filter(fn ($part) => $part->getDisposition() === 'inline');

    expect($sent->getHtmlBody())->toMatch('/<img src="cid:[^"]+"[^>]*class="logo"/')
        ->and($inline)->toHaveCount(1)
        ->and($sent->getTextBody())->not->toContain('cid:')->not->toContain('<img');
});

test('a configured logo url is linked instead of embedded', function () {
    config(['mail.default' => 'array', 'mail.logo_url' => 'https://inventory.example.org/logo.png']);

    Notification::route('mail', 'req@gmail.com')->notifyNow(new RequisitionReceived($this->requisition));

    $sent = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();

    expect($sent->getHtmlBody())
        ->toContain('src="https://inventory.example.org/logo.png"')
        ->not->toContain('cid:')
        ->and($sent->getAttachments())->toBeEmpty();
});

test('a logo path in the public folder is linked from APP_URL', function () {
    config(['mail.default' => 'array', 'mail.logo_url' => 'apple-touch-icon.png']);

    Notification::route('mail', 'req@gmail.com')->notifyNow(new RequisitionReceived($this->requisition));

    $sent = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();

    expect($sent->getHtmlBody())->toContain('src="'.asset('apple-touch-icon.png').'"')
        ->and($sent->getAttachments())->toBeEmpty();
});

test('the logo can be turned off entirely', function () {
    config(['mail.default' => 'array', 'mail.logo_enabled' => false]);

    Notification::route('mail', 'req@gmail.com')->notifyNow(new RequisitionReceived($this->requisition));

    $sent = app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();

    expect($sent->getHtmlBody())
        ->not->toContain('<img')
        ->toContain(config('app.name'))
        ->and($sent->getAttachments())->toBeEmpty();
});
