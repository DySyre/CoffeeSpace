<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f3ece4;color:#35271f;font-family:'DM Sans Variable','DM Sans',Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:40px 16px;background:#f3ece4;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e8e0d8;border-collapse:separate;border-spacing:0;">
                @include('emails.partials.Header')
                <tr><td style="padding:36px;">
                    <p style="margin:0 0 12px;color:#805437;font-family:'DM Sans Variable','DM Sans',Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:.12em;">COFFEESPACE &nbsp;/&nbsp; {{ $category }}</p>
                    <h1 style="margin:0 0 16px;font-family:'Bricolage Grotesque Variable','Bricolage Grotesque','DM Sans',Arial,sans-serif;font-size:28px;line-height:1.2;font-weight:600;letter-spacing:-.025em;">{{ $heading }}</h1>
                    <p style="margin:0 0 24px;color:#35271f;font-size:16px;line-height:1.7;">Hi {{ $user->name }}, here is an update from your CoffeeSpace community.</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#f3ece4;border-left:3px solid #805437;">
                        <tr><td style="padding:18px 20px;">
                            <p style="margin:0;color:#35271f;font-size:15px;line-height:1.6;"><strong>{{ $actorName }}</strong>
                                @if($category === 'MESSAGE') sent you a direct message.
                                @elseif($category === 'COMMENT') commented on your coffee story.
                                @else saved your coffee story.
                                @endif
                            </p>
                            @if($category === 'MESSAGE')
                                <p style="margin:8px 0 0;color:#79685c;font-size:13px;line-height:1.6;">Open your conversation to read the message.</p>
                            @else
                                <p style="margin:12px 0 0;color:#79685c;font-size:11px;font-weight:700;letter-spacing:.08em;">YOUR POST</p>
                                <p style="margin:4px 0 0;color:#35271f;font-size:15px;font-weight:600;line-height:1.5;">{{ $itemTitle }}</p>
                            @endif
                        </td></tr>
                    </table>
                    <p style="margin:0 0 24px;"><a href="{{ $url }}" style="display:inline-block;background:#805437;color:#fffaf5;text-decoration:none;padding:13px 20px;border-radius:4px;font-size:15px;font-weight:600;">{{ $actionLabel }}</a></p>
                    <p style="margin:0;color:#79685c;font-size:13px;line-height:1.65;">If the button does not work, copy this link into your browser:<br><a href="{{ $url }}" style="color:#805437;word-break:break-all;">{{ $url }}</a></p>
                </td></tr>
                @include('emails.partials.Footer')
            </table>
        </td></tr>
    </table>
</body>
</html>
