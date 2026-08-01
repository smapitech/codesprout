<?php

namespace App\Enums;

enum RewardType: string
{
    case Stars = 'stars';
    case Experience = 'experience_points';
    case Badge = 'badge';
    case LevelAdvancement = 'level_advancement';
    case MissionCompletion = 'mission_completion';
    case LessonCompletion = 'lesson_completion';
    case UnitCompletion = 'unit_completion';
    case WorldCompletion = 'world_completion';
    case LearningStreak = 'learning_streak';
    case TeacherRecognition = 'teacher_recognition';
}
