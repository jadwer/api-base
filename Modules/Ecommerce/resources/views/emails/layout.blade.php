<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Order Notification')</title>
    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

        /* Body styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        /* Header */
        .email-header {
            background-color: #4f46e5;
            padding: 30px 40px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        /* Content */
        .email-content {
            padding: 40px;
            color: #51545e;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-content h2 {
            color: #333333;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .email-content p {
            margin: 0 0 15px;
        }

        /* Button */
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }

        .button:hover {
            background-color: #4338ca;
        }

        /* Order summary box */
        .order-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .order-box h3 {
            color: #333333;
            font-size: 18px;
            margin: 0 0 15px;
        }

        /* Order items table */
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .order-items th {
            background-color: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        .order-items td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .order-items tr:last-child td {
            border-bottom: none;
        }

        /* Totals */
        .order-totals {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .total-row.grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #e5e7eb;
        }

        /* Info box */
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .success-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        /* Shipping address */
        .shipping-address {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .shipping-address strong {
            display: block;
            color: #111827;
            margin-bottom: 8px;
            font-size: 16px;
        }

        /* Footer */
        .email-footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .email-footer p {
            color: #6b7280;
            font-size: 14px;
            margin: 5px 0;
        }

        .email-footer a {
            color: #4f46e5;
            text-decoration: none;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }

            .email-header, .email-content, .email-footer {
                padding: 20px !important;
            }

            .order-items {
                font-size: 12px;
            }

            .order-items th, .order-items td {
                padding: 8px;
            }

            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding: 40px 20px;">
                <table class="email-container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <!-- Header -->
                    <tr>
                        <td class="email-header">
                            <h1>{{ config('app.name', 'ERP System') }}</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="email-content">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="email-footer">
                            <p><strong>{{ config('app.name', 'ERP System') }}</strong></p>
                            <p>
                                If you have any questions, please contact us at
                                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                            </p>
                            <p style="margin-top: 20px; color: #9ca3af; font-size: 12px;">
                                This email was sent to you because you placed an order on our platform.
                                <br>
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
