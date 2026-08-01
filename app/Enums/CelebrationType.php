<?php

namespace App\Enums;

enum CelebrationType: string
{
    case MissionCompleted = 'mission_completed';
    case LessonCompleted = 'lesson_completed';
    case UnitCompleted = 'unit_completed';
    case BadgeEarned = 'badge_earned';
    case LevelAdvanced = 'level_advanced';
    case StreakMilestone = 'streak_milestone';
    case WorldCompleted = 'world_completed';
    case ProjectApproved = 'project_approved';
    case TeacherRecognition = 'teacher_recognition';
}
