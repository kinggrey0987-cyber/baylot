<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BayLot — New {{ $inquiryType === 'inquiry' ? 'Inquiry' : 'Lead' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0d6efd;color:#ffffff;padding:20px 24px;font-size:20px;font-weight:700;">
                            BayLot — New {{ $inquiryType === 'inquiry' ? 'Inquiry' : 'Site Visit Request' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px 0;font-size:16px;">Hi {{ $recipientName }},</p>
                            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;">
                                @if($inquiryType === 'inquiry')
                                    A buyer has sent a general inquiry for <strong>{{ $listingTitle }}</strong>.
                                @else
                                    A buyer has requested a site visit for <strong>{{ $listingTitle }}</strong>.
                                @endif
                            </p>

                            <table role="presentation" style="border:1px solid #e5e7eb;border-radius:8px;width:100%;margin-bottom:24px;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                                        <strong>Buyer:</strong> {{ $buyerName }}
                                    </td>
                                </tr>
                                @if($projectName)
                                    <tr>
                                        <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                                            <strong>Project:</strong> {{ $projectName }}
                                        </td>
                                    </tr>
                                @endif
                                @if($requestedStart)
                                    <tr>
                                        <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;">
                                            <strong>Requested Visit:</strong> {{ $requestedStart->format('M d, Y h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                                @if($buyerMessage)
                                    <tr>
                                        <td style="padding:10px 16px;">
                                            <strong>Message:</strong> {{ $buyerMessage }}
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <div style="text-align:center;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:600;">
                                    {{ $inquiryType === 'inquiry' ? 'View Dashboard' : 'Open Lead Queue' }}
                                </a>
                            </div>

                            <p style="margin:20px 0 0 0;font-size:13px;color:#6b7280;text-align:center;">
                                You are receiving this because you are a BayLot {{ $listingType === 'exclusive' ? 'agent' : 'broker or agent' }} for this listing.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
