<?php

require_once '../../../vendor/autoload.php';

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;

echo "=== ROBUST PATTERN MATCHING DEMO ===\n\n";

// Test source code with various formatting styles
$testCode = '
public function messyExample() {
    // Different Auth styles that now work
    $user1 = Auth::user();           // Standard
    $user2 = Auth :: user();         // Extra spaces  
    $user3 = auth()->user();         // Helper function
    
    // Guard variations  
    $admin = Auth::guard("admin")->user();
    $api = Auth::guard($guardName)->user();
    
    // Job dispatching variations
    dispatch(new SendEmail());
    dispatch(new SendEmail($user));
    SendEmailJob::dispatch();
    
    // Model calls with flexible spacing
    $posts = Post::where("status", "published");
    $user = User :: find($id);
    
    return response()->json($data);
}
';

echo "=== TESTING AUTH PATTERNS ===\n";
$authMatches = RobustPatternMatcher::matchAuthUser($testCode);
foreach ($authMatches as $match) {
    echo "✅ Found: " . trim($match['full_match']) . "\n";
}

echo "\n=== TESTING GUARD PATTERNS ===\n";
$guardMatches = RobustPatternMatcher::matchAuthGuard($testCode);
foreach ($guardMatches as $match) {
    echo "✅ Found: " . trim($match['full_match']) . "\n";
    if ($match['guard_name']) {
        echo "   Guard: {$match['guard_name']}\n";
    }
}

echo "\n=== TESTING JOB PATTERNS ===\n";
$jobMatches = RobustPatternMatcher::matchJobDispatch($testCode);
foreach ($jobMatches as $match) {
    echo "✅ Found: " . trim($match['full_match']) . "\n";
    echo "   Class: {$match['class_name']}\n";
}

echo "\n=== TESTING MODEL PATTERNS ===\n";
$modelMatches = RobustPatternMatcher::matchModelStatic($testCode);
foreach ($modelMatches as $match) {
    echo "✅ Found: " . trim($match['full_match']) . "\n";
    echo "   Class: {$match['class_name']}, Method: {$match['method_name']}\n";
}

echo "\n=== SUMMARY ===\n";
echo sprintf("Auth calls: %d\n", count($authMatches));
echo sprintf("Guard calls: %d\n", count($guardMatches));  
echo sprintf("Job dispatches: %d\n", count($jobMatches));
echo sprintf("Model calls: %d\n", count($modelMatches));
echo sprintf("Total patterns detected: %d\n", 
    count($authMatches) + count($guardMatches) + count($jobMatches) + count($modelMatches)
);

echo "\n✅ All patterns detected successfully with flexible formatting!\n";