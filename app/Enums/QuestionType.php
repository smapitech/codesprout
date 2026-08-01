<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case ImageChoice = 'image_choice';
    case TrueFalse = 'true_false';
    case MatchItems = 'match_items';
    case DragAndDrop = 'drag_and_drop';
    case OrderSequence = 'order_sequence';
    case FindComputerPart = 'find_computer_part';
    case FindKeyboardKey = 'find_keyboard_key';
    case PressRequestedKey = 'press_requested_key';
    case TypeLetter = 'type_letter';
    case TypeWord = 'type_word';
    case TypeSentence = 'type_sentence';
    case MatchSymbolToName = 'match_symbol_to_name';
    case DragSymbolIntoPosition = 'drag_symbol_into_position';
    case FillMissingSymbol = 'fill_missing_symbol';
    case BuildHtmlTag = 'build_html_tag';
    case MatchOpeningAndClosingHtmlTags = 'match_opening_and_closing_html_tags';
    case FillMissingHtml = 'fill_missing_html';
    case ArrangeCodeIntoCorrectOrder = 'arrange_code_into_correct_order';
    case FillMissingJavascript = 'fill_missing_javascript';
    case RepairSimpleCodeError = 'repair_simple_code_error';
    case ShortChildResponse = 'short_child_response';
    case CreativeProject = 'creative_project';
    case TeacherObservation = 'teacher_observation';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple choice',
            self::ImageChoice => 'Image choice',
            self::TrueFalse => 'True or false',
            self::MatchItems => 'Match items',
            self::DragAndDrop => 'Drag and drop',
            self::OrderSequence => 'Order a sequence',
            self::FindComputerPart => 'Find a computer part',
            self::FindKeyboardKey => 'Find a keyboard key',
            self::PressRequestedKey => 'Press the requested key',
            self::TypeLetter => 'Type a letter',
            self::TypeWord => 'Type a word',
            self::TypeSentence => 'Type a short sentence',
            self::MatchSymbolToName => 'Match a symbol to its name',
            self::DragSymbolIntoPosition => 'Drag a symbol into position',
            self::FillMissingSymbol => 'Fill in a missing symbol',
            self::BuildHtmlTag => 'Build an HTML tag',
            self::MatchOpeningAndClosingHtmlTags => 'Match opening and closing HTML tags',
            self::FillMissingHtml => 'Fill in missing HTML',
            self::ArrangeCodeIntoCorrectOrder => 'Arrange code into the correct order',
            self::FillMissingJavascript => 'Fill in missing JavaScript',
            self::RepairSimpleCodeError => 'Repair a simple code error',
            self::ShortChildResponse => 'Short child response',
            self::CreativeProject => 'Creative project',
            self::TeacherObservation => 'Teacher observation',
        };
    }

    public function isManualReview(): bool
    {
        return in_array($this, [
            self::ShortChildResponse,
            self::CreativeProject,
            self::TeacherObservation,
        ], true);
    }

    public function isAutoGradable(): bool
    {
        return ! $this->isManualReview();
    }

    public function interactionType(): InteractionType
    {
        return match ($this) {
            self::MultipleChoice,
            self::ImageChoice,
            self::TrueFalse,
            self::FindComputerPart,
            self::FindKeyboardKey,
            self::PressRequestedKey,
            self::FillMissingSymbol,
            self::BuildHtmlTag => InteractionType::Select,
            self::MatchItems,
            self::MatchSymbolToName,
            self::DragSymbolIntoPosition,
            self::MatchOpeningAndClosingHtmlTags => InteractionType::Match,
            self::DragAndDrop => InteractionType::DragDrop,
            self::OrderSequence,
            self::ArrangeCodeIntoCorrectOrder => InteractionType::OrderSequence,
            self::TypeLetter,
            self::TypeWord,
            self::TypeSentence,
            self::FillMissingHtml,
            self::FillMissingJavascript => InteractionType::Type,
            self::RepairSimpleCodeError => InteractionType::Debug,
            self::ShortChildResponse => InteractionType::Explain,
            self::CreativeProject => InteractionType::Build,
            self::TeacherObservation => InteractionType::Listen,
        };
    }
}
