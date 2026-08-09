<!doctype html>
<html lang="{{ app()->getLocale() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>@yield('email_title', 'Antikvarijat Biblos')</title>
    <style>
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #f3f0e8;
        }

        table,
        td {
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
            border-collapse: collapse !important;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        .ag-mail-tableset {
            padding: 9px 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 22px;
            color: #4f5e54;
        }

        .ag-mail-tableset h1,
        .ag-mail-tableset h2,
        .ag-mail-tableset h3 {
            margin-top: 0;
            color: #193827;
        }

        .ag-right {
            text-align: right !important;
        }

        .ag-btn {
            display: inline-block;
            padding: 13px 23px;
            background-color: #193827;
            border-radius: 6px;
            color: #ffffff !important;
            font-weight: bold;
        }

        @media screen and (max-width: 640px) {
            .mail-shell {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .mail-gutter {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .mail-product-image,
            .mail-product-details {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
            }

            .mail-product-details {
                padding: 20px 0 0 !important;
            }

            .mail-button {
                display: block !important;
                box-sizing: border-box !important;
                width: 100% !important;
            }
        }
    </style>
    @stack('css')
</head>
<body style="margin:0;padding:0;background-color:#f3f0e8;color:#25342b;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    @yield('preheader')
</div>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f0e8">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" class="mail-shell" style="width:100%;max-width:620px;background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(25,56,39,.08);">
                <tr>
                    <td height="5" style="height:5px;background-color:#bd9456;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
                <tr>
                    <td align="center" bgcolor="#193827" style="padding:26px 32px 24px;background-color:#193827;">
                        <a href="{{ config('app.url') }}" target="_blank" style="display:inline-block;">
                            <img src="{{ config('settings.images_domain') . 'media/img/logo-biblos.png' }}" width="180" alt="Antikvarijat Biblos" style="display:block;width:180px;max-width:100%;height:auto;">
                        </a>
                        <div style="margin-top:10px;font-family:Arial,sans-serif;font-size:11px;line-height:16px;letter-spacing:1.8px;text-transform:uppercase;color:#dfc79f;">
                            Zagreb · Palmotićeva 28
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="mail-gutter" style="padding:44px 48px 42px;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="mail-gutter" align="center" style="padding:26px 48px 28px;background-color:#f8f6f0;border-top:1px solid #e8e1d5;font-family:Arial,Helvetica,sans-serif;">
                        <p style="margin:0 0 12px;font-size:12px;line-height:19px;color:#6f776f;">
                            {{ __('front.email.notification_reason') }}
                        </p>
                        <p style="margin:0 0 12px;font-size:12px;line-height:18px;">
                            <a href="{{ config('app.url') }}" style="color:#193827;font-weight:bold;">{{ __('front.email.visit_shop') }}</a>
                            <span style="padding:0 7px;color:#bd9456;">•</span>
                            <a href="mailto:info@antikvarijat-biblos.hr" style="color:#193827;font-weight:bold;">info@antikvarijat-biblos.hr</a>
                        </p>
                        <p style="margin:0;font-size:11px;line-height:17px;color:#8a8f89;">
                            Antikvarijat Biblos © {{ now()->year }}. {{ __('front.email.footer_rights') }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
