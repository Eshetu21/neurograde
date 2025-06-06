<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
// use Inertia\Inertia;
// use Inertia\Response; 
// use App\Models\Test;
// use App\Models\AiGradingResult;
// use Illuminate\Auth\Events\Registered;
// use Illuminate\Http\RedirectResponse;
// use Illuminate\Support\Facades\Validator;
// use App\Models\Submission;
// use App\Services\AiGradingService;
// use App\Jobs\GradeSubmission;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;
// use App\Traits\SanitizesMarkdown;

// class StudentController extends Controller
// {
//     use SanitizesMarkdown;

//     protected $aiGradingService;

//     public function __construct(AiGradingService $aiGradingService)
//     {
//         $this->aiGradingService = $aiGradingService;
//     }

//     /**
//      * Show the student results page.
//      */
//     public function getResults(): Response
//     {
//         $user = auth()->user()->load('student');
//         $student = $user->student;

//         // Fetch submissions with their related data
//         $submissions = Submission::where('student_id', $student->id)
//             ->with([
//                 'test',
//                 'aiGradingResults' => function($query) {
//                     $query->latest();
//                 }
//             ])
//             ->orderBy('created_at', 'desc')
//             ->get();

//         // Format the results for the frontend
//         $formattedResults = $submissions->map(function($submission) {
//             $latestAiResult = $submission->aiGradingResults->first();
            
//             return [
//                 'id' => $submission->id,
//                 'test' => [
//                     'id' => $submission->test->id,
//                     'title' => $submission->test->title,
//                 ],
//                 'score' => $submission->status === 'published' ? $submission->final_grade : null,
//                 'comment' => $submission->status === 'published' ? $submission->teacher_feedback : null,
//                 'metrics' => $latestAiResult ? json_decode($latestAiResult->metrics, true) : null,
//                 'submission_date' => $submission->created_at->format('Y-m-d H:i:s'),
//                 'status' => $submission->status,
//             ];
//         })->toArray();

//         return Inertia::render('dashboard/studentDashboard/Results', [
//             'results' => $formattedResults
//         ]);
//     }

//     /**
//      * Show the student tests list page.
//      */
//     public function getTests(): Response
//     {
//         $user = auth()->user()->load('student');
//         $student = $user->student;

//         // Get tests assigned to this student that are published
//         $tests = $student->tests()
//             ->where('published', true)
//             ->where('due_date', '>', now())
//             ->with(['class.department', 'teacher.user'])
//             ->orderBy('due_date', 'asc')
//             ->get()
//             ->map(function($test) {
//                 return [
//                     'id' => $test->id,
//                     'title' => $test->title,
//                     'problemStatement' => $test->problem_statement,
//                     'dueDate' => $test->due_date ? $test->due_date->format('Y-m-d H:i:s') : null,
//                     'status' => $test->status,
//                     'class' => $test->class ? [
//                         'id' => $test->class->id,
//                         'name' => $test->class->name,
//                         'department' => $test->class->department->name
//                     ] : null,
//                     'teacher' => $test->teacher ? [
//                         'id' => $test->teacher->id,
//                         'name' => $test->teacher->user->first_name . ' ' . $test->teacher->user->last_name
//                     ] : null
//                 ];
//             });

//         return Inertia::render('dashboard/studentDashboard/Tests/Index', [
//             'tests' => $tests
//         ]);
//     }

//     /**
//      * Show a specific test page for a student.
//      */
//     public function showTest($id): Response
//     {
//         $test = Test::findOrFail($id);
//         $student = auth()->user()->student;
        
//         // Verify student has access to this test
//         if ($test->department_id !== $student->department_id) {
//             abort(403, 'Unauthorized');
//         }

//         // Load necessary relationships
//         $test->load(['class.department', 'teacher.user']);

//         // Get student's submission if exists
//         $submission = $test->submissions()
//             ->where('student_id', $student->id)
//             ->first();

//         return Inertia::render('dashboard/studentDashboard/Tests/TestDetail', [
//             'test' => [
//                 'id' => $test->id,
//                 'title' => $test->title,
//                 'problemStatement' => $test->problem_statement,
//                 'dueDate' => $test->due_date ? $test->due_date->format('Y-m-d H:i:s') : null,
//                 'status' => $test->status,
//                 'metrics' => $test->metrics,
//                 'gradingCriteria' => $test->grading_criteria,
//                 'class_id' => $test->class_id,
//                 'department_id' => $test->department_id,
//                 'class' => $test->class ? [
//                     'id' => $test->class->id,
//                     'name' => $test->class->name,
//                     'department' => $test->class->department->name ?? 'N/A'
//                 ] : null,
//                 'department' => $test->department ? [
//                     'id' => $test->department->id,
//                     'name' => $test->department->name
//                 ] : null,
//                 'teacher' => $test->teacher ? [
//                     'id' => $test->teacher->id,
//                     'name' => $test->teacher->user->first_name . ' ' . $test->teacher->user->last_name
//                 ] : null,
//             ],
//             'submission' => $submission ? [
//                 'id' => $submission->id,
//                 'submitted_code' => $submission->submitted_code,
//                 'submitted_file_path' => $submission->submitted_file_path,
//                 'status' => $submission->status,
//                 'created_at' => $submission->created_at ? $submission->created_at->format('Y-m-d H:i:s') : null,
//                 'grade' => $submission->grade ? [
//                     'graded_value' => $submission->grade->graded_value,
//                     'adjusted_grade' => $submission->grade->adjusted_grade,
//                     'comments' => $submission->grade->comments
//                 ] : null,
//                 'feedback' => $submission->feedback ? [
//                     'feedback_text' => $submission->feedback->feedback_text
//                 ] : null,
//             ] : null,
//             'submissionWarning' => !$submission ? 'Note: You can only submit once. Please make sure your solution is correct before submitting.' : null
//         ]);
//     }

//     /**
//      * Handle student code file submission.
//      */
//     public function submitFile(Request $request, $id): RedirectResponse // Specify return type
//     {
//         // Validation and file handling logic here
//         // ...

//         // Redirect after successful submission
//         return redirect()->back()->with('success', 'File submitted successfully!');
//     }

//     /**
//      * Handle student code text submission.
//      */
//     public function submitCode(Request $request, $id): RedirectResponse // Specify return type
//     {
//         // Validation and code handling logic here
//         // ...

//         // Redirect after successful submission
//         return redirect()->back()->with('success', 'Code submitted successfully!');
//     }

//     public function showTests()
//     {
//         $student = auth()->user()->student;
        
//         // Get the student's first class
//         $class = $student->classes->first();
        
//         if (!$class) {
//             return Inertia::render('dashboard/studentDashboard/Tests/Index', [
//                 'tests' => []
//             ]);
//         }
        
//         // Get tests for student's class that are not past due date
//         $tests = Test::where('class_id', $class->id)
//             ->where('due_date', '>', now())
//             ->with([
//                 'class.department',
//                 'teacher.user',
//                 'submissions' => function($query) use ($student) {
//                     $query->where('student_id', $student->id);
//                 }
//             ])
//             ->get();

//         return Inertia::render('dashboard/studentDashboard/Tests/Index', [
//             'tests' => $tests
//         ]);
//     }

//     public function submitTest(Request $request, $id)
//     {
//         try {
//             $student = auth()->user()->student;
            
//             // Load the student's class and department relationships
//             $student->load(['classes', 'department']);
            
//             // Get the test
//             $test = Test::findOrFail($id);
            
//             // Validate student's access to the test
//             if (!$student->classes->contains('id', $test->class_id)) {
//                 return back()->with('error', 'You are not enrolled in the class for this test.');
//             }
            
//             if ($student->department_id !== $test->department_id) {
//                 return back()->with('error', 'You are not in the department for this test.');
//             }

//             // Check if student has already submitted
//             $existingSubmission = Submission::where('student_id', $student->id)
//                 ->where('test_id', $test->id)
//                 ->first();

//             if ($existingSubmission) {
//                 return back()->with('error', 'You have already submitted this test.');
//             }

//             // Validate the submission based on type
//             $validator = Validator::make($request->all(), [
//                 'submission_type' => 'required|in:file,editor',
//                 'code_editor_text' => 'required_if:submission_type,editor|string',
//                 'code_file' => 'required_if:submission_type,file|nullable|file|mimes:cpp,h,hpp,c,py|max:1024',
//                 'language' => 'required|in:cpp,python',
//             ]);

//             if ($validator->fails()) {
//                 Log::error('Validation failed', [
//                     'errors' => $validator->errors()->toArray(),
//                     'request_data' => $request->all()
//                 ]);
//                 return back()->withErrors($validator->errors());
//             }

//             // Create the submission
//             $submission = Submission::create([
//                 'student_id' => $student->id,
//                 'test_id' => $test->id,
//                 'submission_type' => $request->submission_type,
//                 'code_editor_text' => null,
//                 'code_file_path' => null,
//                 'language' => $request->language,
//                 'submission_date' => now(),
//                 'status' => 'pending'
//             ]);

//             // Handle the submission based on type
//             if ($request->submission_type === 'editor') {
//                 $submission->update([
//                     'code_editor_text' => $this->sanitizeMarkdown($request->code_editor_text)
//                 ]);
//             } else if ($request->submission_type === 'file') {
//                 if (!$request->hasFile('code_file')) {
//                     return back()->with('error', 'Please upload a code file for file submission.');
//                 }
//                 $file = $request->file('code_file');
//                 $path = $file->store('submissions/' . $submission->id);
//                 $submission->update([
//                     'code_file_path' => $path,
//                     'code_editor_text' => $this->sanitizeMarkdown(file_get_contents($file->getRealPath()))
//                 ]);
//             }

//             // Dispatch the grading job
//             GradeSubmission::dispatch($submission);

//             Log::info('Test submitted successfully', [
//                 'submission_id' => $submission->id,
//                 'test_id' => $test->id,
//                 'student_id' => $student->id,
//                 'submission_type' => $request->submission_type,
//                 'language' => $request->language,
//                 'has_file' => $request->hasFile('code_file'),
//                 'has_editor_text' => !empty($request->code_editor_text)
//             ]);

//             return back()->with('success', 'Test submitted successfully! Your submission is being graded.');

//         } catch (\Exception $e) {
//             Log::error('Failed to submit test', [
//                 'error' => $e->getMessage(),
//                 'test_id' => $id,
//                 'student_id' => auth()->user()->student->id,
//                 'trace' => $e->getTraceAsString()
//             ]);

//             return back()->with('error', 'Failed to submit test. Please try again.');
//         }
//     }

//     public function checkStatus()
//     {
//         $student = auth()->user()->student;
        
//         if ($student->status === 'assigned') {
//             return Inertia::render('WaitingScreen', [
//                 'status' => 'success',
//                 'message' => 'You have been assigned to a class! Redirecting to dashboard...'
//             ]);
//         }
        
//         return Inertia::render('WaitingScreen', [
//             'status' => 'info',
//             'message' => 'Your account is still pending assignment. Please wait while an administrator assigns you to a class.'
//         ]);
//     }

//     public function dashboard(): Response
//     {
//         $student = auth()->user()->student;
        
//         \Log::info('Student data', [
//             'student_id' => $student->id,
//             'class_id' => $student->class_id,
//             'department_id' => $student->department_id
//         ]);

//         // Get tests for student's class that are not past due date
//         $tests = Test::where('class_id', $student->class_id)
//             ->where('published', true)
//             ->where('due_date', '>', now())
//             ->with([
//                 'class.department',
//                 'teacher.user',
//                 'submissions' => function($query) use ($student) {
//                     $query->where('student_id', $student->id);
//                 }
//             ])
//             ->get();

//         \Log::info('Initial tests query', [
//             'count' => $tests->count(),
//             'tests' => $tests->map(function($test) {
//                 return [
//                     'id' => $test->id,
//                     'title' => $test->title,
//                     'class_id' => $test->class_id,
//                     'due_date' => $test->due_date,
//                     'published' => $test->published,
//                     'submissions_count' => $test->submissions->count()
//                 ];
//             })->toArray()
//         ]);

//         // Filter out tests that the student has already submitted
//         $upcomingTests = $tests->filter(function($test) {
//             $isEmpty = $test->submissions->isEmpty();
//             \Log::info('Test submission check', [
//                 'test_id' => $test->id,
//                 'has_submissions' => !$isEmpty,
//                 'submissions_count' => $test->submissions->count()
//             ]);
//             return $isEmpty;
//         })->map(function($test) {
//             return [
//                 'id' => $test->id,
//                 'title' => $test->title,
//                 'due_date' => $test->due_date->format('Y-m-d H:i:s'),
//                 'class_name' => $test->class->name,
//             ];
//         })->values();

//         \Log::info('Filtered upcoming tests', [
//             'count' => $upcomingTests->count(),
//             'tests' => $upcomingTests->toArray()
//         ]);

//         // Get all submissions for this student
//         $submissions = Submission::where('student_id', $student->id)
//             ->with(['test', 'aiGradingResults'])
//             ->get();

//         \Log::info('Student submissions', [
//             'count' => $submissions->count(),
//             'submissions' => $submissions->map(function($submission) {
//                 return [
//                     'id' => $submission->id,
//                     'test_id' => $submission->test_id,
//                     'status' => $submission->status
//                 ];
//             })->toArray()
//         ]);

//         // Calculate statistics with default values
//         $statistics = [
//             'total_tests' => $tests->count() ?? 0,
//             'completed_tests' => $submissions->where('status', '!=', 'pending')->count() ?? 0,
//             'pending_submissions' => $submissions->where('status', 'pending')->count() ?? 0,
//             'average_score' => round($submissions
//                 ->where('status', 'published')
//                 ->avg('final_grade') ?? 0, 1),
//         ];

//         // Get recent results (last 5 submissions)
//         $recentResults = $submissions
//             ->sortByDesc('created_at')
//             ->take(5)
//             ->map(function($submission) {
//                 return [
//                     'id' => $submission->id,
//                     'test' => [
//                         'title' => $submission->test->title,
//                     ],
//                     'score' => $submission->final_grade,
//                     'status' => $submission->status,
//                     'submission_date' => $submission->created_at->format('Y-m-d H:i:s'),
//                 ];
//             })
//             ->values();

//         return Inertia::render('dashboard/studentDashboard/Home', [
//             'user' => [
//                 'id' => $student->id,
//                 'name' => $student->user->first_name . ' ' . $student->user->last_name,
//                 'email' => $student->user->email,
//                 'student' => [
//                     'id_number' => $student->id_number,
//                     'academic_year' => $student->academic_year,
//                     'department' => $student->department->name,
//                     'class' => $student->class ? [
//                         'name' => $student->class->name,
//                         'department' => $student->class->department->name,
//                     ] : null,
//                 ],
//             ],
//             'upcomingTests' => $upcomingTests,
//             'recentResults' => $recentResults,
//             'statistics' => $statistics,
//         ]);
//     }

//     public function showWaitingScreen()
//     {
//         $student = auth()->user()->student;
        
//         if ($student->status === 'assigned') {
//             return redirect()->route('student.dashboard');
//         }

//         return Inertia::render('WaitingScreen', [
//             'status' => 'info',
//             'message' => 'Your account is still pending assignment. Please wait while an administrator assigns you to a class.'
//         ]);
//     }
// }
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response; 
use App\Models\Test;
use App\Models\AiGradingResult;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Submission;
use App\Services\AiGradingService;
use App\Jobs\GradeSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\SanitizesMarkdown;

class StudentController extends Controller
{
    use SanitizesMarkdown;

    protected $aiGradingService;

    public function __construct(AiGradingService $aiGradingService)
    {
        $this->aiGradingService = $aiGradingService;
    }

    /**
     * Show the student results page.
     */
    public function getResults(): Response
    {
        $user = auth()->user()->load('student');
        $student = $user->student;

        // Fetch submissions with their related data
        $submissions = Submission::where('student_id', $student->id)
            ->with([
                'test',
                'grades' => function($query) {
                    $query->latest();
                },
                'aiGradingResults' => function($query) {
                    $query->latest();
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Format the results for the frontend
        $formattedResults = $submissions->map(function($submission) {
            $latestAiResult = $submission->aiGradingResults->first();
            $latestGrade = $submission->grades->first();
            
            return [
                'id' => $submission->id,
                'test' => [
                    'id' => $submission->test->id,
                    'title' => $submission->test->title,
                ],
                'score' => $submission->status === 'published' ? $submission->final_grade : null,
                'comment' => $latestGrade ? $latestGrade->comments : null,
                'metrics' => $latestAiResult ? json_decode($latestAiResult->metrics, true) : null,
                'submission_date' => $submission->created_at->format('Y-m-d H:i:s'),
                'status' => $submission->status,
            ];
        })->toArray();

        return Inertia::render('dashboard/studentDashboard/Results', [
            'results' => $formattedResults
        ]);
    }

    /**
     * Show the student tests list page.
     */
    public function getTests(): Response
    {
        $user = auth()->user()->load('student');
        $student = $user->student;

        // Get tests assigned to this student that are published
        $tests = $student->tests()
            ->where('published', true)
            ->where('due_date', '>', now())
            ->with(['class.department', 'teacher.user'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'problemStatement' => $test->problem_statement,
                    'dueDate' => $test->due_date ? $test->due_date->format('Y-m-d H:i:s') : null,
                    'status' => $test->status,
                    'class' => $test->class ? [
                        'id' => $test->class->id,
                        'name' => $test->class->name,
                        'department' => $test->class->department->name
                    ] : null,
                    'teacher' => $test->teacher ? [
                        'id' => $test->teacher->id,
                        'name' => $test->teacher->user->first_name . ' ' . $test->teacher->user->last_name
                    ] : null
                ];
            });

        return Inertia::render('dashboard/studentDashboard/Tests/Index', [
            'tests' => $tests
        ]);
    }

    /**
     * Show a specific test page for a student.
     */
    public function showTest($testId)
    {
        $student = auth()->user()->student;
        
        $test = Test::whereHas('students', function($query) use ($student) {
            $query->where('students.id', $student->id);
        })
        ->with(['class.department', 'teacher'])
        ->findOrFail($testId);

        $submission = $test->submissions()
            ->where('student_id', $student->id)
            ->first();

        return Inertia::render('dashboard/studentDashboard/Tests/TestDetail', [
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'problemStatement' => $test->problem_statement,
                'input_spec' => $test->input_spec,
                'output_spec' => $test->output_spec,
                'dueDate' => $test->due_date,
                'status' => $test->status,
                'questionId' => $test->id,
                'initialCode' => $test->initial_code ?? '',
                'class_id' => $test->class_id,
                'department_id' => $test->department_id,
                'class' => [
                    'id' => $test->class->id,
                    'name' => $test->class->name,
                    'department' => $test->class->department->name,
                ],
                'department' => [
                    'id' => $test->department->id,
                    'name' => $test->department->name,
                ],
                'teacher' => [
                    'name' => $test->teacher->user->first_name . ' ' . $test->teacher->user->last_name,
                ],
            ],
            'submission' => $submission ? [
                'id' => $submission->id,
                'status' => $submission->status,
                'created_at' => $submission->created_at,
            ] : null,
        ]);
    }

    /**
     * Handle student code file submission.
     */
    public function submitFile(Request $request, $id): RedirectResponse // Specify return type
    {
        // Validation and file handling logic here
        // ...

        // Redirect after successful submission
        return redirect()->back()->with('success', 'File submitted successfully!');
    }

    /**
     * Handle student code text submission.
     */
    public function submitCode(Request $request, $id): RedirectResponse // Specify return type
    {
        // Validation and code handling logic here
        // ...

        // Redirect after successful submission
        return redirect()->back()->with('success', 'Code submitted successfully!');
    }

    public function showTests()
    {
        $student = auth()->user()->student;
        
        // Get the student's first class
        $class = $student->classes->first();
        
        if (!$class) {
            return Inertia::render('dashboard/studentDashboard/Tests/Index', [
                'tests' => []
            ]);
        }
        
        // Get tests for student's class that are not past due date
        $tests = Test::where('class_id', $class->id)
            ->where('due_date', '>', now())
            ->with([
                'class.department',
                'teacher.user',
                'submissions' => function($query) use ($student) {
                    $query->where('student_id', $student->id);
                }
            ])
            ->get();

        return Inertia::render('dashboard/studentDashboard/Tests/Index', [
            'tests' => $tests
        ]);
    }

    public function submitTest(Request $request, $id)
    {
        try {
            $student = auth()->user()->student;
            
            // Load the student's class and department relationships
            $student->load(['classes', 'department']);
            
            // Get the test
            $test = Test::findOrFail($id);
            
            // Validate student's access to the test
            if (!$student->classes->contains('id', $test->class_id)) {
                return back()->with('error', 'You are not enrolled in the class for this test.');
            }
            
            if ($student->department_id !== $test->department_id) {
                return back()->with('error', 'You are not in the department for this test.');
            }

            // Check if student has already submitted
            $existingSubmission = Submission::where('student_id', $student->id)
                ->where('test_id', $test->id)
                ->first();

            if ($existingSubmission) {
                return back()->with('error', 'You have already submitted this test.');
            }

            // Validate the submission based on type
            $validator = Validator::make($request->all(), [
                'submission_type' => 'required|in:file,editor',
                'code_editor_text' => 'required_if:submission_type,editor|string',
                'code_file' => 'required_if:submission_type,file|nullable|file|mimes:cpp,h,hpp,c,py|max:1024',
                'language' => 'required|in:cpp,python',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                return back()->withErrors($validator->errors());
            }

            // Create the submission
            $submission = Submission::create([
                'student_id' => $student->id,
                'test_id' => $test->id,
                'submission_type' => $request->submission_type,
                'code_editor_text' => null,
                'code_file_path' => null,
                'language' => $request->language,
                'submission_date' => now(),
                'status' => 'pending'
            ]);

            // Handle the submission based on type
            if ($request->submission_type === 'editor') {
                $submission->update([
                    'code_editor_text' => $this->sanitizeMarkdown($request->code_editor_text)
                ]);
            } else if ($request->submission_type === 'file') {
                if (!$request->hasFile('code_file')) {
                    return back()->with('error', 'Please upload a code file for file submission.');
                }
                $file = $request->file('code_file');
                $path = $file->store('submissions/' . $submission->id);
                $submission->update([
                    'code_file_path' => $path,
                    'code_editor_text' => $this->sanitizeMarkdown(file_get_contents($file->getRealPath()))
                ]);
            }

            // Dispatch the grading job
            GradeSubmission::dispatch($submission);

            Log::info('Test submitted successfully', [
                'submission_id' => $submission->id,
                'test_id' => $test->id,
                'student_id' => $student->id,
                'submission_type' => $request->submission_type,
                'language' => $request->language,
                'has_file' => $request->hasFile('code_file'),
                'has_editor_text' => !empty($request->code_editor_text)
            ]);

            return back()->with('success', 'Test submitted successfully! Your submission is being graded.');

        } catch (\Exception $e) {
            Log::error('Failed to submit test', [
                'error' => $e->getMessage(),
                'test_id' => $id,
                'student_id' => auth()->user()->student->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to submit test. Please try again.');
        }
    }

    public function checkStatus()
    {
        $student = auth()->user()->student;
        
        if ($student->status === 'assigned') {
            return Inertia::render('WaitingScreen', [
                'status' => 'success',
                'message' => 'You have been assigned to a class! Redirecting to dashboard...'
            ]);
        }
        
        return Inertia::render('WaitingScreen', [
            'status' => 'info',
            'message' => 'Your account is still pending assignment. Please wait while an administrator assigns you to a class.'
        ]);
    }

    public function dashboard(): Response
    {
        $student = auth()->user()->student;
        
        \Log::info('Student data', [
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'department_id' => $student->department_id
        ]);

        // Get tests for student's class that are not past due date
        $tests = Test::where('class_id', $student->class_id)
            ->where('published', true)
            ->where('due_date', '>', now())
            ->with([
                'class.department',
                'teacher.user',
                'submissions' => function($query) use ($student) {
                    $query->where('student_id', $student->id);
                }
            ])
            ->get();

        \Log::info('Initial tests query', [
            'count' => $tests->count(),
            'tests' => $tests->map(function($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'class_id' => $test->class_id,
                    'due_date' => $test->due_date,
                    'published' => $test->published,
                    'submissions_count' => $test->submissions->count()
                ];
            })->toArray()
        ]);

        // Filter out tests that the student has already submitted
        $upcomingTests = $tests->filter(function($test) {
            $isEmpty = $test->submissions->isEmpty();
            \Log::info('Test submission check', [
                'test_id' => $test->id,
                'has_submissions' => !$isEmpty,
                'submissions_count' => $test->submissions->count()
            ]);
            return $isEmpty;
        })->map(function($test) {
            return [
                'id' => $test->id,
                'title' => $test->title,
                'due_date' => $test->due_date->format('Y-m-d H:i:s'),
                'class_name' => $test->class->name,
            ];
        })->values();

        \Log::info('Filtered upcoming tests', [
            'count' => $upcomingTests->count(),
            'tests' => $upcomingTests->toArray()
        ]);

        // Get all submissions for this student
        $submissions = Submission::where('student_id', $student->id)
            ->with(['test', 'aiGradingResults'])
            ->get();

        \Log::info('Student submissions', [
            'count' => $submissions->count(),
            'submissions' => $submissions->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'test_id' => $submission->test_id,
                    'status' => $submission->status
                ];
            })->toArray()
        ]);

        // Calculate statistics with default values
        $statistics = [
            'total_tests' => $tests->count() ?? 0,
            'completed_tests' => $submissions->where('status', '!=', 'pending')->count() ?? 0,
            'pending_submissions' => $submissions->where('status', 'pending')->count() ?? 0,
            'average_score' => round($submissions
                ->where('status', 'published')
                ->avg('final_grade') ?? 0, 1),
        ];

        // Get recent results (last 5 submissions)
        $recentResults = $submissions
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'test' => [
                        'title' => $submission->test->title,
                    ],
                    'score' => $submission->final_grade,
                    'status' => $submission->status,
                    'submission_date' => $submission->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        return Inertia::render('dashboard/studentDashboard/Home', [
            'user' => [
                'id' => $student->id,
                'name' => $student->user->first_name . ' ' . $student->user->last_name,
                'email' => $student->user->email,
                'student' => [
                    'id_number' => $student->id_number,
                    'academic_year' => $student->academic_year,
                    'department' => $student->department->name,
                    'class' => $student->class ? [
                        'name' => $student->class->name,
                        'department' => $student->class->department->name,
                    ] : null,
                ],
            ],
            'upcomingTests' => $upcomingTests,
            'recentResults' => $recentResults,
            'statistics' => $statistics,
        ]);
    }

    public function showWaitingScreen()
    {
        $student = auth()->user()->student;
        
        if ($student->status === 'assigned') {
            return redirect()->route('student.dashboard');
        }

        return Inertia::render('WaitingScreen', [
            'status' => 'info',
            'message' => 'Your account is still pending assignment. Please wait while an administrator assigns you to a class.'
        ]);
    }

    public function index()
    {
        $student = auth()->user()->student;
        
        // Get upcoming tests with submission status
        $upcomingTests = Test::whereHas('class', function($query) use ($student) {
            $query->whereHas('students', function($q) use ($student) {
                $q->where('students.id', $student->id);
            });
        })
        ->where('published', true)
        ->where('due_date', '>', now())
        ->with([
            'class.department',
            'teacher.user',
            'submissions' => function($query) use ($student) {
                $query->where('student_id', $student->id);
            }
        ])
        ->orderBy('due_date', 'asc')
        ->get()
        ->map(function($test) {
            return [
                'id' => $test->id,
                'title' => $test->title,
                'due_date' => $test->due_date->format('Y-m-d H:i:s'),
                'class_name' => $test->class->name,
                'department' => $test->class->department->name,
                'has_submitted' => $test->submissions->isNotEmpty()
            ];
        });

        // Get recent results
        $recentResults = $student->submissions()
            ->with(['test', 'grades' => function($query) {
                $query->latest();
            }])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($submission) {
                $latestGrade = $submission->grades->first();
                return [
                    'id' => $submission->id,
                    'test' => [
                        'id' => $submission->test->id,
                        'title' => $submission->test->title
                    ],
                    'status' => $submission->status,
                    'score' => $latestGrade ? $latestGrade->graded_value : null,
                    'submission_date' => $submission->submission_date,
                    'comment' => $latestGrade ? $latestGrade->comments : null
                ];
            });

        // Calculate statistics
        $statistics = [
            'total_tests' => $student->tests()->count(),
            'completed_tests' => $student->submissions()->count(),
            'pending_submissions' => $student->tests()->whereDoesntHave('submissions', function($query) use ($student) {
                $query->where('student_id', $student->id);
            })->count(),
            'average_score' => $student->submissions()
                ->whereHas('grades')
                ->with('grades')
                ->get()
                ->avg(function($submission) {
                    return $submission->grades->first()?->graded_value ?? 0;
                })
        ];

        // Add debug logging
        \Log::info('Student class data', [
            'student_id' => $student->id,
            'classes' => $student->classes->pluck('id'),
            'upcoming_tests_count' => $upcomingTests->count(),
            'upcoming_tests' => $upcomingTests->toArray()
        ]);

        return Inertia::render('dashboard/studentDashboard/Home', [
            'user' => [
                'name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                'student' => [
                    'id_number' => $student->id_number
                ]
            ],
            'upcomingTests' => $upcomingTests,
            'recentResults' => $recentResults,
            'statistics' => $statistics
        ]);
    }
}