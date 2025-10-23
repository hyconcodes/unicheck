<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report - {{ $class->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 18px;
        }
        .class-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .class-info h3 {
            margin-top: 0;
            color: #007bff;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        .attendance-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .attendance-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .distance {
            font-size: 12px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        .no-attendance {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <h2>{{ $class->title }}</h2>
    </div>

    <div class="class-info">
        <h3>Class Information</h3>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Lecturer:</span> {{ $class->lecturer->name }}
                </div>
                <div class="info-item">
                    <span class="info-label">Department:</span> {{ $class->department->name }}
                </div>
                <div class="info-item">
                    <span class="info-label">Level:</span> {{ $class->level }}
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span> {{ $class->formattedCoordinates }}
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Start Time:</span> {{ \Carbon\Carbon::parse($class->starts_at)->format('M d, Y h:i A') }}
                </div>
                <div class="info-item">
                    <span class="info-label">End Time:</span> {{ \Carbon\Carbon::parse($class->ends_at)->format('M d, Y h:i A') }}
                </div>
                <div class="info-item">
                    <span class="info-label">Radius:</span> {{ $class->radius }}m
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span> 
                    <span style="text-transform: capitalize; color: {{ $class->status === 'active' ? '#28a745' : ($class->status === 'paused' ? '#ffc107' : '#dc3545') }};">
                        {{ $class->status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number">{{ $attendances->count() }}</div>
            <div class="stat-label">Total Present</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format($attendances->avg('distance'), 1) }}m</div>
            <div class="stat-label">Avg Distance</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ number_format(($attendances->count() / $totalStudents) * 100, 1) }}%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>

    @if($attendances->count() > 0)
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Matric Number</th>
                    <th>Time Marked</th>
                    <th>Distance</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $index => $attendance)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $attendance->full_name }}</td>
                        <td>{{ $attendance->matric_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($attendance->marked_at)->format('M d, Y h:i A') }}</td>
                        <td>
                            <span class="distance">{{ $attendance->distance }}m</span>
                        </td>
                        <td>
                            <span class="distance">{{ $attendance->formattedCoordinates }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-attendance">
            No attendance records found for this class.
        </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('M d, Y h:i A') }} | UniCheck Attendance System</p>
    </div>
</body>
</html>