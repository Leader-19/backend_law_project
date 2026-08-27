<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DLmsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin@12345'),
                'status' => User::STATUS_APPROVED,
                'email_verified_at' => now(),
            ]);
            $allRoles = Role::pluck('name')->all();
            $admin->syncRoles($allRoles);
        }

        // --- Categories ---
        $catLaw = Category::create(['title' => 'Legal Studies', 'description' => 'Documents and quizzes related to legal education and law studies.', 'user_id' => $admin->id]);
        $catCriminal = Category::create(['title' => 'Criminal Law', 'description' => 'Criminal law fundamentals, procedures, and case studies.', 'user_id' => $admin->id, 'parent_id' => $catLaw->id]);
        $catCivil = Category::create(['title' => 'Civil Law', 'description' => 'Civil law principles, contracts, and tort law.', 'user_id' => $admin->id, 'parent_id' => $catLaw->id]);
        $catTech = Category::create(['title' => 'Technology & IT', 'description' => 'Technology, programming, and IT management topics.', 'user_id' => $admin->id]);
        $catCyber = Category::create(['title' => 'Cybersecurity', 'description' => 'Cybersecurity principles, threats, and defense strategies.', 'user_id' => $admin->id, 'parent_id' => $catTech->id]);
        $catBusiness = Category::create(['title' => 'Business Management', 'description' => 'Business administration, management, and leadership.', 'user_id' => $admin->id]);
        $catFinance = Category::create(['title' => 'Finance & Accounting', 'description' => 'Financial management, accounting principles, and economics.', 'user_id' => $admin->id, 'parent_id' => $catBusiness->id]);
        $catHR = Category::create(['title' => 'Human Resources', 'description' => 'HR management, recruitment, and employee relations.', 'user_id' => $admin->id, 'parent_id' => $catBusiness->id]);

        // --- Users ---
        $users = [];
        $userNames = [
            ['name' => 'Chamroeun Kosal', 'email' => 'kosal@example.com'],
            ['name' => 'Sophea Chan', 'email' => 'sophea@example.com'],
            ['name' => 'Dara Pich', 'email' => 'dara@example.com'],
            ['name' => 'Mey Ling', 'email' => 'mey@example.com'],
            ['name' => 'Bopha Om', 'email' => 'bopha@example.com'],
            ['name' => 'Rotha San', 'email' => 'rotha@example.com'],
        ];

        $statuses = ['approved', 'approved', 'approved', 'approved', 'pending', 'rejected'];

        foreach ($userNames as $i => $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'status' => $statuses[$i],
                'email_verified_at' => now(),
                'approved_at' => in_array($statuses[$i], ['approved']) ? now() : null,
            ]);
            $user->assignRole('Normal');
            $users[] = $user;
        }

        // --- Documents (sample) ---
        $docs = [];
        $docData = [
            ['doc_name' => 'Introduction to Criminal Law', 'doc_title' => 'Criminal Law Basics', 'category_id' => $catCriminal->id, 'description' => 'A comprehensive introduction to criminal law principles and the justice system.'],
            ['doc_name' => 'Contract Law Fundamentals', 'doc_title' => 'Contract Law', 'category_id' => $catCivil->id, 'description' => 'Understanding contract formation, breach, and remedies.'],
            ['doc_name' => 'Network Security Guide', 'doc_title' => 'Cybersecurity 101', 'category_id' => $catCyber->id, 'description' => 'Essential cybersecurity concepts and best practices.'],
            ['doc_name' => 'Management Principles', 'doc_title' => 'Business Management', 'category_id' => $catBusiness->id, 'description' => 'Core principles of effective business management.'],
            ['doc_name' => 'Financial Statements Analysis', 'doc_title' => 'Finance Basics', 'category_id' => $catFinance->id, 'description' => 'How to read and analyze financial statements.'],
            ['doc_name' => 'Employee Recruitment Handbook', 'doc_title' => 'HR Recruitment Guide', 'category_id' => $catHR->id, 'description' => 'Best practices for recruiting and hiring talent.'],
        ];

        foreach ($docData as $data) {
            $docs[] = Document::create(array_merge($data, ['user_id' => $admin->id, 'doc_upload' => 'documents/sample.pdf']));
        }

        // --- Quizzes with Questions ---
        $quizData = [
            [
                'title' => 'Criminal Law Fundamentals',
                'description' => 'Test your knowledge of basic criminal law principles, offenses, and legal procedures.',
                'category_id' => $catCriminal->id,
                'passing_score' => 70,
                'time_limit_minutes' => 15,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'question' => 'What is the primary purpose of criminal law?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'To protect individual rights only', 'is_correct' => false],
                            ['option_text' => 'To maintain public order and safety', 'is_correct' => true],
                            ['option_text' => 'To generate revenue for the government', 'is_correct' => false],
                            ['option_text' => 'To resolve private disputes', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is "mens rea" in criminal law?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'The guilty mind or criminal intent', 'is_correct' => true],
                            ['option_text' => 'The criminal act itself', 'is_correct' => false],
                            ['option_text' => 'The punishment for a crime', 'is_correct' => false],
                            ['option_text' => 'A legal defense', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is the standard of proof in criminal cases?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Preponderance of evidence', 'is_correct' => false],
                            ['option_text' => 'Beyond a reasonable doubt', 'is_correct' => true],
                            ['option_text' => 'Clear and convincing evidence', 'is_correct' => false],
                            ['option_text' => 'Balance of probabilities', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'Is self-defense a valid legal defense?',
                        'type' => 'true_false',
                        'options' => [
                            ['option_text' => 'True', 'is_correct' => true],
                            ['option_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What does "actus reus" refer to?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'The physical act of committing a crime', 'is_correct' => true],
                            ['option_text' => 'The mental state of the offender', 'is_correct' => false],
                            ['option_text' => 'The verdict of the court', 'is_correct' => false],
                            ['option_text' => 'The sentencing phase', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Cybersecurity Essentials',
                'description' => 'Assess your understanding of cybersecurity threats, vulnerabilities, and protection methods.',
                'category_id' => $catCyber->id,
                'passing_score' => 60,
                'time_limit_minutes' => 20,
                'max_attempts' => 0,
                'questions' => [
                    [
                        'question' => 'What is phishing?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'A type of malware', 'is_correct' => false],
                            ['option_text' => 'A social engineering attack to steal information', 'is_correct' => true],
                            ['option_text' => 'A network protocol', 'is_correct' => false],
                            ['option_text' => 'A firewall configuration', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What does a firewall do?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Encrypts all data on a computer', 'is_correct' => false],
                            ['option_text' => 'Monitors and controls network traffic', 'is_correct' => true],
                            ['option_text' => 'Generates passwords', 'is_correct' => false],
                            ['option_text' => 'Backs up files automatically', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is two-factor authentication (2FA)?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Using two passwords', 'is_correct' => false],
                            ['option_text' => 'Using two different verification methods to log in', 'is_correct' => true],
                            ['option_text' => 'Logging in from two devices', 'is_correct' => false],
                            ['option_text' => 'Having two user accounts', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'Ransomware encrypts your files and demands payment.',
                        'type' => 'true_false',
                        'options' => [
                            ['option_text' => 'True', 'is_correct' => true],
                            ['option_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is the strongest type of password?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Your birthday', 'is_correct' => false],
                            ['option_text' => 'A short word', 'is_correct' => false],
                            ['option_text' => 'A long mix of letters, numbers, and symbols', 'is_correct' => true],
                            ['option_text' => 'Your name', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is a VPN?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Virtual Private Network - encrypts internet traffic', 'is_correct' => true],
                            ['option_text' => 'Very Private Network - hides files', 'is_correct' => false],
                            ['option_text' => 'Virtual Public Network - shares bandwidth', 'is_correct' => false],
                            ['option_text' => 'Video Processing Node - edits media', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Business Management Basics',
                'description' => 'Evaluate your understanding of core business management and leadership concepts.',
                'category_id' => $catBusiness->id,
                'passing_score' => 70,
                'time_limit_minutes' => null,
                'max_attempts' => 5,
                'questions' => [
                    [
                        'question' => 'What are the four functions of management?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Planning, Organizing, Leading, Controlling', 'is_correct' => true],
                            ['option_text' => 'Hiring, Firing, Training, Promoting', 'is_correct' => false],
                            ['option_text' => 'Buying, Selling, Trading, Investing', 'is_correct' => false],
                            ['option_text' => 'Designing, Building, Testing, Deploying', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is delegation in management?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Doing all work yourself', 'is_correct' => false],
                            ['option_text' => 'Assigning tasks and authority to others', 'is_correct' => true],
                            ['option_text' => 'Firing employees', 'is_correct' => false],
                            ['option_text' => 'Attending meetings', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'SWOT stands for Strengths, Weaknesses, Opportunities, and Threats.',
                        'type' => 'true_false',
                        'options' => [
                            ['option_text' => 'True', 'is_correct' => true],
                            ['option_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is the purpose of a mission statement?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'To list employee salaries', 'is_correct' => false],
                            ['option_text' => 'To define the organization\'s purpose and goals', 'is_correct' => true],
                            ['option_text' => 'To track financial performance', 'is_correct' => false],
                            ['option_text' => 'To manage inventory', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Financial Accounting Fundamentals',
                'description' => 'Test your knowledge of basic accounting principles and financial concepts.',
                'category_id' => $catFinance->id,
                'passing_score' => 65,
                'time_limit_minutes' => 25,
                'max_attempts' => 0,
                'questions' => [
                    [
                        'question' => 'What is the accounting equation?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'Revenue - Expenses = Profit', 'is_correct' => false],
                            ['option_text' => 'Assets = Liabilities + Equity', 'is_correct' => true],
                            ['option_text' => 'Cash In - Cash Out = Balance', 'is_correct' => false],
                            ['option_text' => 'Income = Expenses + Savings', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'What is a balance sheet?',
                        'type' => 'multiple_choice',
                        'options' => [
                            ['option_text' => 'A report showing revenues over time', 'is_correct' => false],
                            ['option_text' => 'A snapshot of assets, liabilities, and equity at a point in time', 'is_correct' => true],
                            ['option_text' => 'A list of all employees', 'is_correct' => false],
                            ['option_text' => 'A cash flow projection', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'Debit entries increase expense accounts.',
                        'type' => 'true_false',
                        'options' => [
                            ['option_text' => 'True', 'is_correct' => true],
                            ['option_text' => 'False', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ];

        $quizzes = [];
        foreach ($quizData as $qData) {
            $questions = $qData['questions'];
            unset($qData['questions']);

            $quiz = Quiz::create(array_merge($qData, ['created_by' => $admin->id, 'is_active' => true]));
            $quizzes[] = $quiz;

            $sortOrder = 1;
            foreach ($questions as $qInfo) {
                $options = $qInfo['options'];
                unset($qInfo['options']);

                $question = QuizQuestion::create(array_merge($qInfo, [
                    'quiz_id' => $quiz->id,
                    'sort_order' => $sortOrder++,
                ]));

                $optOrder = 0;
                foreach ($options as $oInfo) {
                    QuizOption::create(array_merge($oInfo, [
                        'question_id' => $question->id,
                        'sort_order' => $optOrder++,
                    ]));
                }
            }
        }

        // --- Quiz Attempts & Certificates ---
        foreach ($users as $i => $user) {
            if ($user->status !== 'approved') {
                continue;
            }

            // Each user attempts 1-2 quizzes
            $quizzesToTake = array_slice($quizzes, $i % count($quizzes), 2);

            foreach ($quizzesToTake as $quiz) {
                $questions = $quiz->questions()->with('options')->get();
                $totalQ = $questions->count();
                $correctCount = random_int((int) ceil($totalQ * 0.5), $totalQ);

                $attempt = QuizAttempt::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->id,
                    'total_questions' => $totalQ,
                    'correct_answers' => $correctCount,
                    'score' => $totalQ > 0 ? (int) round(($correctCount / $totalQ) * 100) : 0,
                    'passed' => ($totalQ > 0 ? (int) round(($correctCount / $totalQ) * 100) : 0) >= $quiz->passing_score,
                    'started_at' => now()->subDays(random_int(1, 14)),
                    'completed_at' => now()->subDays(random_int(0, 13)),
                ]);

                foreach ($questions as $question) {
                    $options = $question->options;
                    $correctOption = $options->firstWhere('is_correct', true);
                    $selectedOption = $correctOption && $attempt->correct_answers > 0
                        ? $correctOption
                        : $options->where('is_correct', false)->random();

                    UserAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'option_id' => $selectedOption->id,
                        'is_correct' => (bool) $selectedOption->is_correct,
                    ]);

                    if ((bool) $selectedOption->is_correct && $attempt->correct_answers > 0) {
                        $attempt->correct_answers--;
                    }
                }

                // Generate certificate if passed
                if ($attempt->passed) {
                    Certificate::create([
                        'user_id' => $user->id,
                        'quiz_id' => $quiz->id,
                        'attempt_id' => $attempt->id,
                        'certificate_number' => Certificate::generateCertificateNumber(),
                        'score' => $attempt->score,
                    ]);
                }
            }
        }

        // --- User Library ---
        foreach ($users as $i => $user) {
            if ($user->status !== 'approved') {
                continue;
            }
            $savedDocs = array_slice($docs, $i % count($docs), 2);
            foreach ($savedDocs as $doc) {
                if (! $user->library()->where('document_id', $doc->id)->exists()) {
                    $user->library()->attach($doc->id);
                }
            }
        }

        // --- Reading History ---
        foreach ($users as $i => $user) {
            if ($user->status !== 'approved') {
                continue;
            }
            $docsToRead = array_slice($docs, $i % count($docs), 3);
            foreach ($docsToRead as $doc) {
                $lastPage = random_int(1, 20);
                $totalPages = 20;
                ReadingHistory::create([
                    'user_id' => $user->id,
                    'document_id' => $doc->id,
                    'last_page' => $lastPage,
                    'total_pages' => $totalPages,
                    'progress_percent' => round(($lastPage / $totalPages) * 100, 2),
                    'last_opened_at' => now()->subHours(random_int(1, 72)),
                ]);
            }
        }

        // --- Contact Messages ---
        foreach ($users as $i => $user) {
            if ($user->status !== 'approved') {
                continue;
            }

            ContactMessage::create([
                'user_id' => $user->id,
                'subject' => 'Question about document access',
                'message' => 'Hello admin, I would like to request access to additional documents in the ' . $catLaw->title . ' category. Thank you.',
                'status' => $i < 2 ? 'replied' : 'open',
                'admin_reply' => $i < 2 ? 'Access has been granted. Please check your library.' : null,
                'replied_at' => $i < 2 ? now()->subDays(1) : null,
            ]);
        }
    }
}
