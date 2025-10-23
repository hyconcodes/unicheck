<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Class Available</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e2e8f0;
        }
        .class-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .info-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #374151;
        }
        .value {
            color: #6b7280;
        }
        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📚 New Class Available</h1>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $student->name }}</strong>,</p>
        
        <p>A new class has been created for your level in the {{ $department->name }} department. You can now join and mark your attendance!</p>
        
        <div class="class-info">
            <h3 style="margin-top: 0; color: #1f2937;">{{ $class->title }}</h3>
            
            @if($class->description)
                <p style="color: #6b7280; margin: 10px 0;">{{ $class->description }}</p>
            @endif
            
            <div class="info-row">
                <span class="label">Lecturer:</span>
                <span class="value">{{ $lecturer->name }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Department:</span>
                <span class="value">{{ $department->name }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Level:</span>
                <span class="value">{{ $class->level }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Started:</span>
                <span class="value">{{ $class->starts_at->format('M j, Y g:i A') }}</span>
            </div>
            
            @if($class->ends_at)
                <div class="info-row">
                    <span class="label">Ends:</span>
                    <span class="value">{{ $class->ends_at->format('M j, Y g:i A') }}</span>
                </div>
            @endif
            
            <div class="info-row">
                <span class="label">Attendance Radius:</span>
                <span class="value">{{ $class->radius }} meters</span>
            </div>
        </div>
        
        <p><strong>Important:</strong> You must be within {{ $class->radius }} meters of the class location to mark your attendance. Make sure to bring your device and be physically present in the classroom.</p>
        
        <a href="{{ route('student.dashboard') }}" class="cta-button">
            View Class on Dashboard
        </a>
        
        <p>If you have any questions, please contact your lecturer or the system administrator.</p>
        
        <p>Best regards,<br>
        <strong>{{ config('app.name') }} Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>