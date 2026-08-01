<?php

namespace Database\Seeders\Data;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CodeSproutCurriculumSeedData
{
    public static function build(): array
    {
        $publishedAt = Carbon::now()->toIso8601String();
        $worlds = self::worldBlueprints($publishedAt);

        return [
            'schema_version' => 1,
            'skills' => collect($worlds)
                ->flatMap(static fn (array $world): array => collect($world['units'])->map(static fn (array $unit): array => self::skillFromUnit($unit))->all())
                ->unique('slug')
                ->values()
                ->all(),
            'curriculum' => [
                'title' => 'CodeSprout One-Year Programme',
                'slug' => 'codesprout-one-year-programme',
                'description' => 'A one-year, game-based computer readiness, typing and early coding programme for children aged 6 to 7.',
                'target_min_age' => 6,
                'target_max_age' => 7,
                'duration_weeks' => 48,
                'lessons_per_week' => 3,
                'version' => '1.0.0',
                'status' => 'published',
                'published_at' => $publishedAt,
                'worlds' => array_map(static fn (array $world): array => self::buildWorld($world, $publishedAt), $worlds),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function worldBlueprints(string $publishedAt): array
    {
        return [
            [
                'number' => 1,
                'name' => 'Computer Discovery',
                'slug' => 'computer-discovery',
                'short_description' => 'Children discover what a computer is and learn the names of the main parts.',
                'story_description' => 'A gentle first world that introduces children to the computer, its parts and the idea of using it safely and carefully.',
                'theme_colour' => '#2fb37b',
                'accent_colour' => '#f7b53b',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Describe what a computer is.',
                    'Name the monitor, keyboard, mouse and system unit.',
                    'Use the computer carefully and sit correctly.',
                ],
                'units' => [
                    self::unit('what is a computer', 1, 'What is a computer?', 'Children learn that a computer helps people work, play and create.', [
                        'Understand that a computer is a helpful machine.',
                        'Recognise where computers are used.',
                        'Say what a computer can help people do.',
                    ], 'Recognise computer purpose', 'computer-purpose', 'Computer literacy', 'Children identify a computer as a helpful tool.', 'Recognise and describe how a computer helps people.'),
                    self::unit('computer parts and shapes', 2, 'Computer parts', 'Children learn the names and jobs of the main computer parts.', [
                        'Point to the monitor, keyboard, mouse and system unit.',
                        'Match each part to its name.',
                        'Hear and repeat the parts of a computer.',
                    ], 'Recognise computer parts', 'computer-parts', 'Computer literacy', 'Children match the monitor, keyboard, mouse and system unit to their names.', 'Name the main parts of a computer with confidence.'),
                    self::unit('cursor and pointer', 3, 'Cursor and pointer', 'Children learn what the cursor and pointer do on the screen.', [
                        'See how the pointer moves.',
                        'Follow the cursor with eyes and hand.',
                        'Use the mouse to guide the pointer.',
                    ], 'Follow the pointer', 'cursor-pointer', 'Mouse control', 'Children guide the pointer on screen.', 'Control the pointer to follow simple instructions.'),
                    self::unit('computer care and safety', 4, 'Computer care', 'Children practise safe and careful computer habits.', [
                        'Sit correctly at the computer.',
                        'Open and close activities carefully.',
                        'Treat the computer gently and safely.',
                    ], 'Care for the computer', 'computer-care', 'Safety and habits', 'Children care for devices and follow safe computer habits.', 'Show safe and respectful computer care.'),
                ],
            ],
            [
                'number' => 2,
                'name' => 'Mouse Adventure',
                'slug' => 'mouse-adventure',
                'short_description' => 'Children build mouse control skills through simple pointing, clicking and dragging games.',
                'story_description' => 'A playful world for learning how to move the mouse, click, drag, scroll and make the pointer do exactly what the child wants.',
                'theme_colour' => '#18a7b8',
                'accent_colour' => '#f57b5d',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Move the mouse with control.',
                    'Click, double click and right click.',
                    'Drag and drop objects with accuracy.',
                ],
                'units' => [
                    self::unit('moving the mouse pointer', 5, 'Move the mouse', 'Children practise moving the pointer in short guided paths.', [
                        'Move the mouse in the right direction.',
                        'Follow simple pointer paths.',
                        'Stop the pointer in the right place.',
                    ], 'Move the pointer', 'mouse-movement', 'Mouse control', 'Children move the mouse with growing confidence.', 'Guide the mouse pointer to the target.'),
                    self::unit('clicking and double clicking', 6, 'Click and double click', 'Children learn when to click once and when to click twice.', [
                        'Use a single click to choose an item.',
                        'Use a double click to open a simple item.',
                        'Listen for the click instruction carefully.',
                    ], 'Use clicks correctly', 'mouse-clicks', 'Mouse control', 'Children use clicks for the correct action.', 'Choose the right click action for each task.'),
                    self::unit('dragging and dropping', 7, 'Drag and drop', 'Children drag objects to the correct place and let go at the right moment.', [
                        'Click and hold an object.',
                        'Drag the object to a target.',
                        'Release the object neatly.',
                    ], 'Drag objects carefully', 'drag-drop', 'Mouse control', 'Children move objects from one place to another.', 'Complete drag-and-drop actions with control.'),
                    self::unit('scrolling and drawing', 8, 'Scrolling and drawing', 'Children explore scrolling and simple mouse drawing.', [
                        'Scroll up and down a small page.',
                        'Draw simple shapes with the mouse.',
                        'Improve mouse accuracy through play.',
                    ], 'Scroll and draw', 'scroll-draw', 'Mouse control', 'Children use the mouse wheel and draw simple shapes.', 'Use the mouse wheel and draw simple shapes.'),
                ],
            ],
            [
                'number' => 3,
                'name' => 'Keyboard Island',
                'slug' => 'keyboard-island',
                'short_description' => 'Children discover the keyboard and learn to locate important keys by sight and sound.',
                'story_description' => 'An island of giant keys where children hear a key name, find it on the keyboard and use it in a tiny mission.',
                'theme_colour' => '#1fa7a0',
                'accent_colour' => '#f2c14e',
                'icon_path' => null,
                'cover_image_path' => 'assets/codesprout/original/CodeSprout-Dashboard-Keyboard-Island-Banner.png',
                'learning_outcomes' => [
                    'Find letter, number and control keys.',
                    'Use Enter, Backspace, Delete, Shift, Caps Lock, Tab and Escape.',
                    'Follow spoken keyboard instructions.',
                ],
                'units' => [
                    self::unit('letter keys', 9, 'Letter keys', 'Children learn to identify and use the letter keys on the keyboard.', [
                        'Find groups of letter keys.',
                        'Hear the names of letter keys.',
                        'Press the right letter when asked.',
                    ], 'Find the letter keys', 'letter-keys', 'Keyboard', 'Children find and name letter keys quickly.', 'Find the correct letter key from a spoken instruction.'),
                    self::unit('number keys and spacebar', 10, 'Number keys and spacebar', 'Children use number keys and the spacebar in short guided tasks.', [
                        'Find the number row.',
                        'Use the spacebar between words.',
                        'Listen for number instructions.',
                    ], 'Use numbers and space', 'number-spacebar', 'Keyboard', 'Children use number keys and the spacebar.', 'Type numbers and spaces in the correct places.'),
                    self::unit('enter backspace and delete', 11, 'Enter, Backspace and Delete', 'Children learn three important editing keys.', [
                        'Find Enter, Backspace and Delete.',
                        'Use Enter to start a new line.',
                        'Use Backspace or Delete to repair a mistake.',
                    ], 'Edit with important keys', 'enter-backspace-delete', 'Keyboard', 'Children use the editing keys to fix text.', 'Edit text with Enter, Backspace and Delete.'),
                    self::unit('shift caps lock tab and arrows', 12, 'Shift, Caps Lock, Tab and arrow keys', 'Children use the final group of important keyboard keys.', [
                        'Use Shift for one capital letter.',
                        'Use Caps Lock for repeated capitals.',
                        'Find Tab, Escape and the arrow keys.',
                    ], 'Use the special keys', 'shift-caps-tab-arrows', 'Keyboard', 'Children use the special keys for control and movement.', 'Use the special keys for typing and movement.'),
                ],
            ],
            [
                'number' => 4,
                'name' => 'Typing Jungle',
                'slug' => 'typing-jungle',
                'short_description' => 'Children build typing fluency from letters to short words and simple sentences.',
                'story_description' => 'A calm typing world that moves from individual letters to familiar words, names and short sentences.',
                'theme_colour' => '#69a84f',
                'accent_colour' => '#ffd166',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Type lowercase letters and short words.',
                    'Type names and short sentences with spaces.',
                    'Practise accuracy before speed.',
                ],
                'units' => [
                    self::unit('lowercase letters', 13, 'Lowercase letters', 'Children type lowercase letters in simple rows and short sequences.', [
                        'Type a row of lowercase letters.',
                        'Listen and repeat the letter names.',
                        'Press the correct key with growing confidence.',
                    ], 'Type lowercase letters', 'typing-lowercase', 'Typing', 'Children type lowercase letters accurately.', 'Type lowercase letters from memory and with sound guidance.'),
                    self::unit('two and three letter words', 14, 'Two and three letter words', 'Children type very short words using the letter keys they know.', [
                        'Type tiny two-letter words.',
                        'Build three-letter words slowly.',
                        'Use the spacebar when needed.',
                    ], 'Type short words', 'typing-short-words', 'Typing', 'Children type short words carefully.', 'Type short familiar words with accuracy.'),
                    self::unit('names and sentences', 15, 'Names and sentences', 'Children type their name and simple child-friendly sentences.', [
                        'Type a child’s name.',
                        'Type short sentences with a space between words.',
                        'Press full stops at the end.',
                    ], 'Type names and sentences', 'typing-names-sentences', 'Typing', 'Children type their name and short sentences.', 'Type names and short sentences with spaces and full stops.'),
                    self::unit('typing accuracy', 16, 'Typing accuracy', 'Children focus on accuracy, rhythm and simple finger awareness.', [
                        'Pause before pressing the next key.',
                        'Check accuracy before typing faster.',
                        'Notice how fingers move across the keyboard.',
                    ], 'Improve typing accuracy', 'typing-accuracy', 'Typing', 'Children improve accuracy before speed.', 'Type carefully and focus on accuracy before speed.'),
                ],
            ],
            [
                'number' => 5,
                'name' => 'Capital City',
                'slug' => 'capital-city',
                'short_description' => 'Children learn uppercase letters and how capitals work in names and sentences.',
                'story_description' => 'A bright city where children practise capital letters, shift, caps lock and combining letters with numbers.',
                'theme_colour' => '#ef476f',
                'accent_colour' => '#ffd166',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Use capital letters in names and sentences.',
                    'Use Shift and Caps Lock safely.',
                    'Combine letters and numbers when typing simple information.',
                ],
                'units' => [
                    self::unit('capital letters in names', 17, 'Capital letters in names', 'Children learn why names begin with capitals.', [
                        'See capital letters in child names.',
                        'Type the first letter of a name as a capital.',
                        'Match names to the correct capital letter.',
                    ], 'Use capitals in names', 'capital-names', 'Typing', 'Children use capital letters in names.', 'Type names with the correct capital letters.'),
                    self::unit('capital letters in sentences', 18, 'Capital letters in sentences', 'Children learn that sentences begin with capital letters.', [
                        'See the capital letter at the start of a sentence.',
                        'Type a capital letter at the beginning of a sentence.',
                        'Check that the sentence begins correctly.',
                    ], 'Start sentences with capitals', 'capital-sentences', 'Typing', 'Children use capitals at the start of sentences.', 'Start sentences with capital letters.'),
                    self::unit('shift and caps lock', 19, 'Shift and Caps Lock', 'Children practise using Shift for one capital and Caps Lock for several.', [
                        'Use Shift for a single capital letter.',
                        'Use Caps Lock for a series of capitals.',
                        'Turn Caps Lock off when finished.',
                    ], 'Use Shift and Caps Lock', 'shift-caps-lock', 'Typing', 'Children use Shift and Caps Lock safely.', 'Use Shift and Caps Lock to create capitals.'),
                    self::unit('numbers with letters', 20, 'Numbers with letters', 'Children type ages, scores and levels that combine letters and numbers.', [
                        'Type an age or score.',
                        'Combine letters and numbers in one line.',
                        'Check the keyboard for the correct number key.',
                    ], 'Type letters and numbers', 'letters-numbers', 'Typing', 'Children combine letters and numbers.', 'Type simple information that uses letters and numbers together.'),
                ],
            ],
            [
                'number' => 6,
                'name' => 'Symbol Mountain',
                'slug' => 'symbol-mountain',
                'short_description' => 'Children learn punctuation and coding symbols in small groups.',
                'story_description' => 'A mountain of symbols where children see each symbol, hear its name, find it on the keyboard and use it in simple coding examples.',
                'theme_colour' => '#7c5cff',
                'accent_colour' => '#ffb703',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Recognise familiar punctuation symbols.',
                    'Use coding symbols such as brackets and angle brackets.',
                    'Type symbols and use them in simple examples.',
                ],
                'units' => [
                    self::unit('familiar punctuation', 21, 'Familiar punctuation', 'Children explore symbols that are already familiar in reading and writing.', [
                        'See and say +, -, =, !, ?, ., , @ and _.',
                        'Match symbol names to the correct sign.',
                        'Type simple punctuation marks from memory.',
                    ], 'Use familiar symbols', 'familiar-punctuation', 'Symbols', 'Children use familiar punctuation symbols.', 'Recognise and type common punctuation symbols.'),
                    self::unit('coding symbols one', 22, 'Coding symbols one', 'Children learn the angle brackets and slashes that appear in simple code.', [
                        'Find <, > and /.',
                        'Hear the name of each coding symbol.',
                        'Type the symbols in the correct order.',
                    ], 'Use coding symbols', 'coding-symbols-one', 'HTML and code', 'Children find coding symbols on the keyboard.', 'Locate and type angle brackets and slashes.'),
                    self::unit('coding symbols two', 23, 'Coding symbols two', 'Children explore quotation marks, colons, semicolons, hashes and asterisks.', [
                        'Recognise ", \', :, ;, # and *.',
                        'Type the symbol when given its name.',
                        'Use the symbol in a tiny code example.',
                    ], 'Use more coding symbols', 'coding-symbols-two', 'HTML and code', 'Children use additional coding symbols.', 'Type the symbols used in simple code examples.'),
                    self::unit('brackets and examples', 24, 'Brackets and examples', 'Children work with brackets and short coding examples.', [
                        'Recognise ( ), [ ] and { }.',
                        'Put brackets into the correct order.',
                        'Use symbols in a simple coding example.',
                    ], 'Use brackets in code', 'brackets-examples', 'HTML and code', 'Children use brackets in simple code examples.', 'Use brackets confidently in short code examples.'),
                ],
            ],
            [
                'number' => 7,
                'name' => 'Logic Land',
                'slug' => 'logic-land',
                'short_description' => 'Children practise instructions, sequences, patterns and problem solving.',
                'story_description' => 'A thinking world where children learn to follow instructions, order events and spot mistakes in a sequence.',
                'theme_colour' => '#ff9f1c',
                'accent_colour' => '#2ec4b6',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Follow instructions in the correct order.',
                    'Recognise patterns and sequences.',
                    'Find mistakes in a simple instruction sequence.',
                ],
                'units' => [
                    self::unit('instructions and order', 25, 'Instructions and order', 'Children follow short instructions step by step.', [
                        'Hear an instruction.',
                        'Place steps in the correct order.',
                        'Follow a short sequence carefully.',
                    ], 'Follow instructions', 'instructions-order', 'Logic', 'Children follow instructions in order.', 'Follow a short sequence of instructions.'),
                    self::unit('patterns and sequences', 26, 'Patterns and sequences', 'Children recognise repeating patterns and simple series of actions.', [
                        'See a repeating pattern.',
                        'Continue the pattern correctly.',
                        'Explain what comes next.',
                    ], 'Complete a sequence', 'patterns-sequences', 'Logic', 'Children recognise patterns and sequences.', 'Continue simple patterns and sequences.'),
                    self::unit('choices and repetition', 27, 'Choices and repetition', 'Children make simple choices and repeat an action when asked.', [
                        'Choose the correct path.',
                        'Repeat an instruction several times.',
                        'Use if-this-then-that thinking.',
                    ], 'Make a choice', 'choices-repetition', 'Logic', 'Children make a choice and repeat actions.', 'Use choices and repetition in a short task.'),
                    self::unit('finding mistakes', 28, 'Finding mistakes', 'Children spot and repair mistakes in an instruction sequence.', [
                        'Spot the incorrect step.',
                        'Move the mistake into the right place.',
                        'Explain why the sequence now works.',
                    ], 'Debug the sequence', 'finding-mistakes', 'Logic and debugging', 'Children spot and fix mistakes in a sequence.', 'Repair a simple instruction sequence.'),
                ],
            ],
            [
                'number' => 8,
                'name' => 'Block Coding Village',
                'slug' => 'block-coding-village',
                'short_description' => 'Children learn events, movement, repetition and conditions using block-style thinking.',
                'story_description' => 'A tiny village where children arrange blocks to make characters move, repeat and respond to simple conditions.',
                'theme_colour' => '#2d9bf0',
                'accent_colour' => '#7c5cff',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Understand events and movement in block coding.',
                    'Arrange blocks into a correct sequence.',
                    'Use simple repetition and conditions in a small maze challenge.',
                ],
                'units' => [
                    self::unit('events and movement', 29, 'Events and movement', 'Children see how an event can start a movement action.', [
                        'Notice when an event starts.',
                        'Move a character one step at a time.',
                        'Match the event to the action.',
                    ], 'Start with an event', 'events-movement', 'Block coding', 'Children use events to start movement.', 'Connect an event to a movement action.'),
                    self::unit('direction sequences', 30, 'Direction sequences', 'Children place direction blocks in the right order.', [
                        'Follow arrows and directions.',
                        'Build a short sequence of blocks.',
                        'Check the character reaches the target.',
                    ], 'Arrange direction blocks', 'direction-sequences', 'Block coding', 'Children arrange direction blocks in sequence.', 'Order direction blocks to reach a target.'),
                    self::unit('repetition and conditions', 31, 'Repetition and conditions', 'Children learn that repeating an action or checking a condition can save time.', [
                        'Repeat a movement block.',
                        'See what happens when a condition is true.',
                        'Choose the right block for the job.',
                    ], 'Use repeat and if', 'repetition-conditions', 'Block coding', 'Children use repeat and if blocks.', 'Use repeating and conditional block logic.'),
                    self::unit('maze challenges', 32, 'Maze challenges', 'Children use blocks to complete a simple maze.', [
                        'Plan a route through the maze.',
                        'Test the block sequence.',
                        'Fix the route if the character gets stuck.',
                    ], 'Solve a maze', 'maze-challenges', 'Block coding', 'Children solve a simple maze challenge.', 'Complete a small maze with block coding.'),
                ],
            ],
            [
                'number' => 9,
                'name' => 'HTML Builder Bay',
                'slug' => 'html-builder-bay',
                'short_description' => 'Children explore webpages, tags and simple HTML structures.',
                'story_description' => 'A shoreline of code where children build headings, paragraphs, buttons, images and lists with opening and closing tags.',
                'theme_colour' => '#ff7f50',
                'accent_colour' => '#2d9bf0',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Explain what a webpage is.',
                    'Use opening and closing tags correctly.',
                    'Build a small personal webpage with headings, paragraphs, images and lists.',
                ],
                'units' => [
                    self::unit('webpages and tags', 33, 'Webpages and tags', 'Children discover that webpages are built with HTML tags.', [
                        'See what a webpage does.',
                        'Recognise opening and closing tags.',
                        'Match tag names to examples.',
                    ], 'Understand tags', 'webpages-tags', 'HTML', 'Children recognise HTML tags.', 'Recognise opening and closing HTML tags.'),
                    self::unit('headings and paragraphs', 34, 'Headings and paragraphs', 'Children build headings and paragraphs with matching tags.', [
                        'Use h1, h2 and p tags.',
                        'Find the missing opening or closing tag.',
                        'Type a simple heading and paragraph.',
                    ], 'Build headings and paragraphs', 'headings-paragraphs', 'HTML', 'Children build headings and paragraphs.', 'Create headings and paragraphs with matching tags.'),
                    self::unit('buttons images and lists', 35, 'Buttons, images and lists', 'Children add buttons, images and lists to a webpage.', [
                        'Recognise the button, image and list tags.',
                        'Complete a missing tag challenge.',
                        'Build a small personal webpage section.',
                    ], 'Add page elements', 'buttons-images-lists', 'HTML', 'Children add buttons, images and lists.', 'Add common webpage elements with HTML.'),
                    self::unit('missing and broken tags', 36, 'Missing and broken tags', 'Children repair simple HTML by finding missing tags.', [
                        'Spot the missing tag.',
                        'Repair a broken HTML example.',
                        'Explain how the page now works.',
                    ], 'Repair HTML', 'missing-broken-tags', 'HTML debugging', 'Children repair broken HTML tags.', 'Repair simple HTML and explain the fix.'),
                ],
            ],
            [
                'number' => 10,
                'name' => 'CSS Colour Kingdom',
                'slug' => 'css-colour-kingdom',
                'short_description' => 'Children explore colours, spacing and styling rules with CSS.',
                'story_description' => 'A colourful kingdom where children style text, backgrounds, borders and profile cards with CSS.',
                'theme_colour' => '#7b61ff',
                'accent_colour' => '#ff9f1c',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Describe what CSS does.',
                    'Change text colour, background colour and font size.',
                    'Style a simple profile card or webpage section.',
                ],
                'units' => [
                    self::unit('what css does', 37, 'What CSS does', 'Children learn that CSS changes the look of a webpage.', [
                        'See the difference between content and style.',
                        'Hear that CSS changes colours and layout.',
                        'Match a style rule to its result.',
                    ], 'Understand CSS', 'what-css-does', 'CSS', 'Children describe what CSS does.', 'Explain that CSS styles how a page looks.'),
                    self::unit('colour and background', 38, 'Colour and background', 'Children change text and background colours.', [
                        'Choose a text colour.',
                        'Choose a background colour.',
                        'Compare two colour choices.',
                    ], 'Style with colour', 'colour-background', 'CSS', 'Children change colours on a page.', 'Change text and background colours with CSS.'),
                    self::unit('size borders and spacing', 39, 'Size, borders and spacing', 'Children adjust font size, borders, radius, width and height.', [
                        'Change font size.',
                        'Add a border and border radius.',
                        'Notice spacing around an element.',
                    ], 'Style with size and space', 'size-borders-spacing', 'CSS', 'Children style size, borders and spacing.', 'Adjust size, borders and spacing in CSS.'),
                    self::unit('styling a profile card', 40, 'Styling a profile card', 'Children style a simple profile card or webpage section.', [
                        'Arrange content on a card.',
                        'Choose colours for a friendly profile card.',
                        'Explain the style choices made.',
                    ], 'Build a profile card', 'styling-profile-card', 'CSS', 'Children style a simple profile card.', 'Style a simple profile card with CSS.'),
                ],
            ],
            [
                'number' => 11,
                'name' => 'JavaScript Action City',
                'slug' => 'javascript-action-city',
                'short_description' => 'Children discover JavaScript as the part of code that makes things happen.',
                'story_description' => 'A lively city where children use alert messages, variables, button clicks, functions and simple page changes.',
                'theme_colour' => '#00a8e8',
                'accent_colour' => '#ffd166',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Describe what JavaScript does.',
                    'Use alerts, strings, variables and simple functions.',
                    'Trigger an action with a button click.',
                ],
                'units' => [
                    self::unit('alerts and strings', 41, 'Alerts and strings', 'Children use alert() and simple strings.', [
                        'See an alert message.',
                        'Type a short string.',
                        'Explain what JavaScript does when it runs.',
                    ], 'Use alerts', 'alerts-strings', 'JavaScript', 'Children use alert messages and strings.', 'Use alert() and short string values.'),
                    self::unit('variables and functions', 42, 'Variables and functions', 'Children learn simple variables and functions.', [
                        'Store a simple value in a variable.',
                        'Run a basic function.',
                        'Change a value and see the result.',
                    ], 'Use variables and functions', 'variables-functions', 'JavaScript', 'Children use variables and functions.', 'Use simple variables and functions in code.'),
                    self::unit('button click actions', 43, 'Button click actions', 'Children make a button do something when clicked.', [
                        'Click a button to trigger a change.',
                        'Link a button to a small action.',
                        'Test the click and see what happens.',
                    ], 'React to button clicks', 'button-click-actions', 'JavaScript', 'Children trigger an action with a button click.', 'Use a button click to start a small action.'),
                    self::unit('hide show and move', 44, 'Hide, show and move', 'Children show or hide an object and move a character.', [
                        'Hide an object.',
                        'Show an object again.',
                        'Move a character a short distance.',
                    ], 'Show and move things', 'hide-show-move', 'JavaScript', 'Children hide, show and move objects.', 'Change visibility and move a simple object.'),
                ],
            ],
            [
                'number' => 12,
                'name' => 'Young Creator Studio',
                'slug' => 'young-creator-studio',
                'short_description' => 'Children combine their skills to plan, build, debug and present a final project.',
                'story_description' => 'A final studio where children plan a small project, mix HTML, CSS and JavaScript, debug it and celebrate the result.',
                'theme_colour' => '#14b8a6',
                'accent_colour' => '#f97316',
                'icon_path' => null,
                'cover_image_path' => null,
                'learning_outcomes' => [
                    'Plan a simple webpage or mini-game.',
                    'Combine HTML, CSS and JavaScript in a safe sandbox.',
                    'Review, improve and explain a final project.',
                ],
                'units' => [
                    self::unit('revision and planning', 45, 'Revision and planning', 'Children review what they have learned and plan a simple project.', [
                        'Choose a project idea.',
                        'List the parts needed for the project.',
                        'Talk about the project plan.',
                    ], 'Plan a project', 'revision-planning', 'Creative project', 'Children plan a small final project.', 'Plan a simple project before building it.'),
                    self::unit('combine html and css', 46, 'Combine HTML and CSS', 'Children combine structure and style in a small project.', [
                        'Build a small webpage structure.',
                        'Style the page with CSS.',
                        'Check that the design looks friendly.',
                    ], 'Build with HTML and CSS', 'combine-html-css', 'Creative project', 'Children combine HTML and CSS.', 'Combine HTML structure with CSS styling.'),
                    self::unit('add simple javascript', 47, 'Add simple JavaScript', 'Children add one safe JavaScript action to their project.', [
                        'Add a button or action.',
                        'Use a simple variable or function.',
                        'Test the action in the sandbox.',
                    ], 'Add a JavaScript action', 'add-simple-javascript', 'Creative project', 'Children add a simple JavaScript action.', 'Add a small JavaScript action to the project.'),
                    self::unit('test debug and present', 48, 'Test, debug and present', 'Children test, improve and present their project.', [
                        'Find a simple bug.',
                        'Improve the project with feedback.',
                        'Celebrate the finished creation.',
                    ], 'Present the final project', 'test-debug-present', 'Creative project', 'Children test, debug and present their work.', 'Test, debug and present a final project.'),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $world
     */
    private static function buildWorld(array $world, string $publishedAt): array
    {
        return [
            'name' => $world['name'],
            'slug' => $world['slug'],
            'world_number' => $world['number'],
            'short_description' => $world['short_description'],
            'story_description' => $world['story_description'],
            'learning_outcomes' => $world['learning_outcomes'],
            'theme_colour' => $world['theme_colour'],
            'accent_colour' => $world['accent_colour'],
            'icon_path' => $world['icon_path'],
            'cover_image_path' => $world['cover_image_path'],
            'estimated_weeks' => 4,
            'display_order' => $world['number'],
            'status' => 'published',
            'published_at' => $publishedAt,
            'prerequisite_slugs' => $world['number'] > 1 ? [self::worldSlug($world['number'] - 1)] : [],
            'units' => array_map(
                static fn (array $unit, int $index): array => self::buildUnit($world, $unit, $index + 1, $publishedAt),
                $world['units'],
                array_keys($world['units']),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $world
     * @param  array<string, mixed>  $unit
     */
    private static function buildUnit(array $world, array $unit, int $displayOrder, string $publishedAt): array
    {
        $lessonTemplates = [
            ['title_prefix' => 'Discover', 'lesson_number' => 1, 'style' => 'discover'],
            ['title_prefix' => 'Practise', 'lesson_number' => 2, 'style' => 'guided'],
            ['title_prefix' => 'Use', 'lesson_number' => 3, 'style' => 'apply'],
        ];

        return [
            'title' => $unit['title'],
            'slug' => self::slugify($unit['title']),
            'week_number' => $unit['week_number'],
            'description' => $unit['description'],
            'learning_outcomes' => $unit['learning_outcomes'],
            'display_order' => $displayOrder,
            'status' => 'published',
            'published_at' => $publishedAt,
            'skill' => [
                'name' => $unit['skill_name'],
                'slug' => $unit['skill_slug'],
                'category' => $unit['skill_category'],
                'description' => $unit['skill_description'],
                'mastery_description' => $unit['skill_mastery_description'],
                'status' => 'published',
                'published_at' => $publishedAt,
            ],
            'lessons' => array_map(
                static fn (array $template, int $index): array => self::buildLesson($world, $unit, $template, $index + 1, $publishedAt),
                $lessonTemplates,
                array_keys($lessonTemplates),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $world
     * @param  array<string, mixed>  $unit
     * @param  array<string, mixed>  $template
     */
    private static function buildLesson(array $world, array $unit, array $template, int $displayOrder, string $publishedAt): array
    {
        $theme = $unit['title'];
        $lessonTitle = match ($template['style']) {
            'discover' => "{$template['title_prefix']} {$theme}",
            'guided' => "{$template['title_prefix']} {$theme}",
            default => "{$template['title_prefix']} {$theme}",
        };
        $slug = self::slugify($lessonTitle);
        $skillSlug = $unit['skill_slug'];

        return [
            'title' => $lessonTitle,
            'slug' => $slug,
            'lesson_number' => $template['lesson_number'],
            'description' => self::lessonDescription($template['style'], $unit['title']),
            'teacher_notes' => self::teacherNotes($template['style'], $unit['title']),
            'learner_objective' => self::learnerObjective($template['style'], $unit['title']),
            'estimated_minutes' => match ($template['style']) {
                'discover' => 8,
                'guided' => 10,
                default => 12,
            },
            'difficulty_level' => match ($template['style']) {
                'discover' => 'introductory',
                'guided' => 'developing',
                default => 'independent',
            },
            'display_order' => $displayOrder,
            'status' => 'published',
            'published_at' => $publishedAt,
            'skill_slugs' => [$skillSlug],
            'stages' => self::buildStages($template['style'], $lessonTitle, $skillSlug, $publishedAt),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildStages(string $style, string $lessonTitle, string $skillSlug, string $publishedAt): array
    {
        $templates = match ($style) {
            'discover' => [
                ['title' => 'Watch it', 'stage_type' => 'introduction', 'interaction_type' => 'watch', 'minutes' => 2, 'stars' => 5, 'required' => true],
                ['title' => 'Hear it', 'stage_type' => 'demonstration', 'interaction_type' => 'listen', 'minutes' => 2, 'stars' => 5, 'required' => true],
                ['title' => 'Find it', 'stage_type' => 'guided_practice', 'interaction_type' => 'find', 'minutes' => 3, 'stars' => 10, 'required' => true],
                ['title' => 'Explain it', 'stage_type' => 'review', 'interaction_type' => 'explain', 'minutes' => 2, 'stars' => 5, 'required' => false],
            ],
            'guided' => [
                ['title' => 'Hear it', 'stage_type' => 'demonstration', 'interaction_type' => 'listen', 'minutes' => 2, 'stars' => 5, 'required' => true],
                ['title' => 'Find it', 'stage_type' => 'guided_practice', 'interaction_type' => 'find', 'minutes' => 3, 'stars' => 5, 'required' => true],
                ['title' => 'Drag it', 'stage_type' => 'game_mission', 'interaction_type' => 'drag_drop', 'minutes' => 3, 'stars' => 10, 'required' => true],
                ['title' => 'Type it', 'stage_type' => 'independent_practice', 'interaction_type' => 'type', 'minutes' => 2, 'stars' => 10, 'required' => false],
            ],
            default => [
                ['title' => 'Watch it', 'stage_type' => 'introduction', 'interaction_type' => 'watch', 'minutes' => 2, 'stars' => 5, 'required' => true],
                ['title' => 'Type it', 'stage_type' => 'independent_practice', 'interaction_type' => 'type', 'minutes' => 3, 'stars' => 10, 'required' => true],
                ['title' => 'Build it', 'stage_type' => 'project', 'interaction_type' => 'build', 'minutes' => 4, 'stars' => 15, 'required' => true],
                ['title' => 'Explain it', 'stage_type' => 'assessment', 'interaction_type' => 'explain', 'minutes' => 3, 'stars' => 10, 'required' => false],
            ],
        };

        return array_map(
            static fn (array $stage, int $index): array => [
                'title' => $stage['title'],
                'slug' => self::slugify($lessonTitle.' '.$stage['title']),
                'stage_type' => $stage['stage_type'],
                'interaction_type' => $stage['interaction_type'],
                'instruction_text' => self::instructionText($lessonTitle, $stage['title']),
                'encouragement_text' => self::encouragementText($lessonTitle, $stage['title']),
                'teacher_guidance' => self::teacherGuidance($lessonTitle, $stage['title']),
                'audio_instruction_path' => null,
                'estimated_minutes' => $stage['minutes'],
                'difficulty_level' => match ($stage['stage_type']) {
                    'introduction', 'demonstration' => 'introductory',
                    'guided_practice' => 'developing',
                    'game_mission', 'independent_practice' => 'independent',
                    default => 'easy',
                },
                'star_value' => $stage['stars'],
                'is_required' => $stage['required'],
                'display_order' => $index + 1,
                'status' => 'published',
                'published_at' => $publishedAt,
                'configuration' => [
                    'challenge_mode' => $stage['stage_type'] === 'game_mission',
                    'hint_level' => $stage['stage_type'] === 'independent_practice' ? 'low' : 'medium',
                    'skill_slug' => $skillSlug,
                ],
                'skill_slugs' => [$skillSlug],
            ],
            $templates,
            array_keys($templates),
        );
    }

    private static function lessonDescription(string $style, string $unitTitle): string
    {
        return match ($style) {
            'discover' => "Children watch and hear the key idea in {$unitTitle}.",
            'guided' => "Children practise {$unitTitle} with guided support.",
            default => "Children use {$unitTitle} more independently.",
        };
    }

    private static function teacherNotes(string $style, string $unitTitle): string
    {
        return match ($style) {
            'discover' => "Use gentle modelling and talk through {$unitTitle} slowly.",
            'guided' => "Pause often, give prompts and encourage the child to try {$unitTitle}.",
            default => "Invite children to explain how they used {$unitTitle} and what they noticed.",
        };
    }

    private static function learnerObjective(string $style, string $unitTitle): string
    {
        return match ($style) {
            'discover' => "I can watch and name {$unitTitle}.",
            'guided' => "I can practise {$unitTitle} with help.",
            default => "I can use {$unitTitle} by myself.",
        };
    }

    private static function instructionText(string $lessonTitle, string $stageTitle): string
    {
        return "{$stageTitle} for {$lessonTitle}. Follow the friendly instructions and try the next step.";
    }

    private static function encouragementText(string $lessonTitle, string $stageTitle): string
    {
        return "Great job finishing {$stageTitle} in {$lessonTitle}. Keep going, little coder!";
    }

    private static function teacherGuidance(string $lessonTitle, string $stageTitle): string
    {
        return "Support children during {$stageTitle} in {$lessonTitle} with clear spoken directions and praise.";
    }

    private static function slugify(string $value): string
    {
        return Str::slug($value);
    }

    private static function worldSlug(int $number): string
    {
        return match ($number) {
            1 => 'computer-discovery',
            2 => 'mouse-adventure',
            3 => 'keyboard-island',
            4 => 'typing-jungle',
            5 => 'capital-city',
            6 => 'symbol-mountain',
            7 => 'logic-land',
            8 => 'block-coding-village',
            9 => 'html-builder-bay',
            10 => 'css-colour-kingdom',
            11 => 'javascript-action-city',
            12 => 'young-creator-studio',
            default => 'world-'.$number,
        };
    }

    private static function unit(
        string $theme,
        int $weekNumber,
        string $title,
        string $description,
        array $learningOutcomes,
        string $skillName,
        string $skillSlug,
        string $skillCategory,
        string $skillDescription,
        string $skillMasteryDescription,
    ): array {
        return [
            'theme' => $theme,
            'week_number' => $weekNumber,
            'title' => $title,
            'description' => $description,
            'learning_outcomes' => $learningOutcomes,
            'skill_name' => $skillName,
            'skill_slug' => $skillSlug,
            'skill_category' => $skillCategory,
            'skill_description' => $skillDescription,
            'skill_mastery_description' => $skillMasteryDescription,
        ];
    }

    /**
     * @param  array<string, mixed>  $unit
     * @return array<string, mixed>
     */
    private static function skillFromUnit(array $unit): array
    {
        return [
            'name' => $unit['skill_name'],
            'slug' => $unit['skill_slug'],
            'category' => $unit['skill_category'],
            'description' => $unit['skill_description'],
            'mastery_description' => $unit['skill_mastery_description'],
            'status' => 'published',
            'published_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
