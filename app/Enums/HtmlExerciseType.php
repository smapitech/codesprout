<?php

namespace App\Enums;

enum HtmlExerciseType: string
{
    case SymbolRecognition = 'symbol_recognition';
    case SymbolMatching = 'symbol_matching';
    case SymbolDragOrder = 'symbol_drag_order';
    case TagRecognition = 'tag_recognition';
    case TagPurposeMatching = 'tag_purpose_matching';
    case OpeningClosingMatch = 'opening_closing_match';
    case MissingSymbol = 'missing_symbol';
    case MissingTag = 'missing_tag';
    case TagOrdering = 'tag_ordering';
    case NestingOrder = 'nesting_order';
    case ContentBetweenTags = 'content_between_tags';
    case AttributeMatching = 'attribute_matching';
    case AttributeValueCompletion = 'attribute_value_completion';
    case GuidedCodeCompletion = 'guided_code_completion';
    case CodeRepair = 'code_repair';
    case CodePrediction = 'code_prediction';
    case PreviewPrediction = 'preview_prediction';
    case HeadingBuilder = 'heading_builder';
    case ParagraphBuilder = 'paragraph_builder';
    case ListBuilder = 'list_builder';
    case LinkBuilder = 'link_builder';
    case ImageBuilder = 'image_builder';
    case DocumentStructureBuilder = 'document_structure_builder';
    case VisualBlockBuilder = 'visual_block_builder';
    case CopyCode = 'copy_code';
    case StructuredFreeCode = 'structured_free_code';
    case ProjectCheckpoint = 'project_checkpoint';
    case ProjectSubmission = 'project_submission';
    case TeacherReviewedProject = 'teacher_reviewed_project';
    case FormalHtmlAssessment = 'formal_html_assessment';

    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public static function options(): array
    {
        return array_map(static fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
