<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Job Fair Participation Approved</title>
</head>
<body>
    <div style="background:#ebebeb;padding:32px 0;">
        <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(32,57,71,0.08);">
            <div style="background:#901b20;padding:24px;text-align:center;">
                <h1 style="color:#fff;font-family:sans-serif;font-size:28px;margin:0;">Job Fair Details Available!</h1>
            </div>
            <div style="padding:32px 24px 24px 24px;font-family:sans-serif;">
                <p style="color:#203947;font-size:18px;margin-top:0;">
                    Dear <strong>{{ $recipientName }}</strong>,
                </p>
                <p style="color:#203947;font-size:16px;">
                    We are excited to announce that the details for the upcoming Job Fair: <strong style="color:#901b20;">{{ $event->title }}</strong> are now available!
                </p>
                <div style="background:#ebebeb;border-radius:6px;padding:16px;margin:20px 0;">
                    <p style="margin:0 0 8px 0;color:#203947;"><strong>Job Fair Overview:</strong></p>
                    <ul style="padding-left:20px;margin:0;">
                        <li><strong>Title:</strong> {{ $event->title }}</li>
                        <li><strong>Date:</strong> {{ $event->start_date ? $event->start_date->format('F j, Y') : 'TBD' }}</li>
                        <li><strong>Time:</strong> {{ $event->start_time ?? 'TBD' }} {{ $event->end_time ? '- ' . $event->end_time : '' }}</li>
                        <li><strong>Location:</strong> {{ $event->location ?? 'TBD' }}</li>
                    </ul>
                </div>
                <p style="color:#203947;font-size:16px;">
                    Discover participating companies, available job profiles, and interview opportunities by visiting the Job Fair details page.
                </p>
                <div style="text-align:center;margin:32px 0;">
                    <a href="{{ $jobFairDetailsUrl }}" style="background:#901b20;color:#fff;text-decoration:none;padding:14px 32px;border-radius:5px;font-size:18px;display:inline-block;">
                        View Job Fair Details
                    </a>
                </div>
                <p style="color:#203947;font-size:15px;">
                    We encourage you to explore these opportunities and prepare for a successful Job Fair!
                </p>
                <p style="color:#203947;font-size:15px;">
                    If you have any questions or require assistance, please do not hesitate to contact our team.
                </p>
                <p style="color:#ad565a;font-size:15px;margin-bottom:0;">
                    Best regards,<br>
                    <span style="color:#901b20;font-weight:bold;">Communiti Team</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
