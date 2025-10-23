<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;

class AttendanceLogService
{
    /**
     * Log attendance success events
     */
    public static function logSuccess(ClassModel $class, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'event' => 'attendance_marked_success',
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'matric_number' => $user?->matric_number,
            'class_id' => $class->id,
            'class_title' => $class->title,
            'lecturer' => $class->lecturer->name,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => $data
        ];
        
        Log::channel('attendance')->info('Attendance marked successfully', $logData);
    }
    
    /**
     * Log attendance error events
     */
    public static function logError(string $error, ClassModel $class = null, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'event' => 'attendance_error',
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'matric_number' => $user?->matric_number,
            'error_message' => $error,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => $data
        ];
        
        if ($class) {
            $logData['class_id'] = $class->id;
            $logData['class_title'] = $class->title;
            $logData['lecturer'] = $class->lecturer->name;
        }
        
        Log::channel('attendance')->error('Attendance error occurred', $logData);
    }
    
    /**
     * Log security violations
     */
    public static function logSecurityViolation(string $violation, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = [
            'event' => 'security_violation',
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'matric_number' => $user?->matric_number,
            'violation_type' => $violation,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => $data
        ];
        
        Log::channel('attendance')->critical('Security violation detected', $logData);
    }
}