<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BayLot Login OTP</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0d6efd;color:#ffffff;padding:20px 24px;font-size:20px;font-weight:700;">BayLot Security Verification</td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px 0;font-size:16px;">Hi {{ $name }},</p>
                            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;">Use this one-time code to complete your sign in. The code expires in {{ $expiresInMinutes }} minutes.</p>
                            <div style="text-align:center;margin:24px 0;">
                                <span style="display:inline-block;font-size:32px;letter-spacing:8px;font-weight:700;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px 22px;">{{ $otp }}</span>
                            </div>
                            <p style="margin:0 0 10px 0;font-size:14px;color:#6b7280;">If you did not try to sign in, you can safely ignore this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
