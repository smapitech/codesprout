<?php

namespace App\Enums;

enum BadgeCategory: string
{
    case ComputerSkills = 'computer_skills';
    case MouseSkills = 'mouse_skills';
    case KeyboardSkills = 'keyboard_skills';
    case TypingSkills = 'typing_skills';
    case CodingSkills = 'coding_skills';
    case Creativity = 'creativity';
    case Persistence = 'persistence';
    case Accuracy = 'accuracy';
    case LearningJourney = 'learning_journey';
    case WorldCompletion = 'world_completion';
    case TeacherRecognition = 'teacher_recognition';
}
