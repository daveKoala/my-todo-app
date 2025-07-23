<?php

require_once '../../../vendor/autoload.php';

use DaveKoala\RoutesExplorer\Explorer\patterns\ComprehensiveDependencies;

echo "=== NOTE CONTROLLER PATTERN DETECTION DEMO ===\n\n";

// Sample from your NoteController
$noteControllerCode = '
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $note = $user->notes()->create([
            "title" => $validated["title"],
        ]);
        return redirect()->route("notes.index");
    }

    public function show(Note $note): View
    {
        return view("notes.show", compact("note"));
    }

    public function update(Request $request, Note $note)
    {
        $note->update($validated);
        return response()->json(["success" => true]);
    }
}
';

echo "=== TESTING COMPREHENSIVE DEPENDENCY DETECTION ===\n";
$dependencies = ComprehensiveDependencies::detect($noteControllerCode);

foreach ($dependencies as $dependency) {
    echo "✅ Found: {$dependency['class']}\n";
    echo "   Pattern: {$dependency['pattern']}\n";
    echo "   Usage: {$dependency['usage']}\n\n";
}

echo "=== SUMMARY ===\n";
echo sprintf("Total dependencies detected: %d\n", count($dependencies));

$usageTypes = array_count_values(array_column($dependencies, 'usage'));
foreach ($usageTypes as $usage => $count) {
    echo sprintf("- %s: %d\n", $usage, $count);
}

echo "\n✅ This should now detect the Note model through:\n";
echo "   1. use App\\Models\\Note; (import)\n";
echo "   2. function show(Note \$note) (method parameter)\n";
echo "   3. \$user->notes()->create() (relationship call)\n";