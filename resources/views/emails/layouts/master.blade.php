<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notification')</title>
    <style>
        /* Base styles */
        body {
            font-family: 'Inter', 'Roboto', 'Montserrat', Arial, sans-serif;
            color: #374151;
            /* neutral-700 */
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #F9FAFB;
            /* neutral-50 */
        }

        /* Container styles */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        /* Header styles */
        .email-header {
            background-color: #1E3A8A;
            /* primary */
            padding: 20px;
            text-align: center;
            color: white;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: white;
        }

        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }

        /* Content styles */
        .email-content {
            padding: 30px;
            background-color: #ffffff;
        }

        /* Button styles */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #14B8A6;
            /* secondary */
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 20px;
            text-align: center;
        }

        .btn-primary {
            background-color: #1E3A8A;
            /* primary */
        }

        .btn-accent {
            background-color: #F59E0B;
            /* accent */
        }

        /* Footer styles */
        .email-footer {
            padding: 20px;
            text-align: center;
            background-color: #F3F4F6;
            /* neutral-100 */
            font-size: 14px;
            color: #6B7280;
            /* tertiary/neutral-500 */
        }

        .email-footer a {
            color: #1E3A8A;
            /* primary */
            text-decoration: none;
        }

        /* Typography */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: #1F2937;
            /* neutral-800 */
            margin-top: 0;
        }

        p {
            margin-top: 0;
            margin-bottom: 16px;
        }

        a {
            color: #14B8A6;
            /* secondary */
            text-decoration: underline;
        }

        .text-sm {
            font-size: 14px;
        }

        .text-xs {
            font-size: 12px;
        }

        /* Utilities */
        .mt-4 {
            margin-top: 16px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            @hasSection('header')
            @yield('header')
            @else
            <h1 class="title-header">@yield('header_title', 'Notification')</h1>
            @endif
        </div>

        <div class="email-content">
            @yield('content')
        </div>

        <div class="email-footer">
            @hasSection('footer')
            @yield('footer')
            @else
            <p>© {{ date('Y') }} ModelHub. All rights reserved.</p>
            <p class="text-sm">If you have any questions, please <a href="mailto:support@yourcompany.com">contact
                    support</a>.</p>
            <p class="text-xs">You're receiving this email because you're a registered user.</p>
            @endif
        </div>
    </div>
</body>

</html>