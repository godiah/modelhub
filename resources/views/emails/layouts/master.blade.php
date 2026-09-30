{{--
    THE email template. Every email in the app renders through this layout, so the look only ever changes here.

    Sections:
      title        <title> (and the fallback heading)
      header_title the heading at the top of the card (falls back to title)
      preheader    the grey preview line mail clients show beside the subject (optional)
      content      the body — build it from the <x-mail.*> components (button, details/detail, callout, code)
      footer       replaces the default footer text (rarely needed)

    Table-based with inline styles so it survives Outlook and webmail. The `data-embed` <style> is the only
    one kept as a real <style> (media queries cannot be inlined); everything else is inlined on send by
    EmailCssInlinerHelper (see AppServiceProvider).
--}}
@php
    $brand = config('app.name', 'ModelHub');
    $heading = trim($__env->yieldContent('header_title')) ?: trim($__env->yieldContent('title'));
    $support = config('mail.support_address');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', $brand)</title>
    <style data-embed>
        @media only screen and (max-width: 620px) {
            .mail-card { border-radius: 0 !important; border-left: 0 !important; border-right: 0 !important; }
            .mail-pad { padding-left: 24px !important; padding-right: 24px !important; }
            .mail-outer { padding: 0 !important; }
        }
    </style>
    <style>
        body { margin: 0; padding: 0; background-color: #F7F3ED; }
        p { margin: 0 0 16px; }
        a { color: #0F766E; }
        ul, ol { margin: 0 0 16px; padding-left: 20px; }
        li { margin-bottom: 6px; }
        strong { color: #111827; }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#F7F3ED; -webkit-text-size-adjust:100%;">
    @hasSection('preheader')
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">@yield('preheader')</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F3ED;">
        <tr>
            <td align="center" class="mail-outer" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="mail-card"
                    style="width:100%; max-width:600px; background-color:#ffffff; border:1px solid #E5E7EB; border-radius:16px;">
                    <!-- Brand -->
                    <tr>
                        <td class="mail-pad" style="padding:22px 40px; border-bottom:1px solid #F3F4F6;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-right:12px; vertical-align:middle;">
                                        <img src="{{ asset('images/brand/logo-mark.png') }}" alt="" width="40" height="33" style="display:block; border:0; height:33px; width:40px;">
                                    </td>
                                    <td style="vertical-align:middle; font-family:'Plus Jakarta Sans', Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; font-size:15px; font-weight:700; letter-spacing:0.14em; color:#111827;">
                                        {{ strtoupper($brand) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Heading -->
                    <tr>
                        <td class="mail-pad" style="padding:32px 40px 4px;">
                            <h1 style="margin:0; font-family:'Plus Jakarta Sans', Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; font-size:24px; line-height:1.3; font-weight:700; color:#111827;">{{ $heading }}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="mail-pad" style="padding:16px 40px 36px; font-family:Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; font-size:15px; line-height:1.65; color:#374151;">
                            @yield('content')
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">
                    <tr>
                        <td class="mail-pad" align="center" style="padding:24px 40px 8px; font-family:Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; font-size:12px; line-height:1.6; color:#6B7280; text-align:center;">
                            @hasSection('footer')
                                @yield('footer')
                            @else
                                <p style="margin:0 0 6px;">You are receiving this email because you have a {{ $brand }} account.</p>
                                @if ($support)
                                    <p style="margin:0 0 6px;">Questions? <a href="mailto:{{ $support }}" style="color:#0F766E;">Contact support</a></p>
                                @endif
                                <p style="margin:0;">&copy; {{ date('Y') }} {{ $brand }}. All rights reserved.</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
