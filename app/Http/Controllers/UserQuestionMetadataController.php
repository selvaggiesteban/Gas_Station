<?php

namespace App\Http\Controllers;

use App\Models\UserQuestionMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserQuestionMetadataController extends Controller
{
    /**
     * Toggle the doubt status of a question for the authenticated user.
     */
    public function toggleDoubt(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $metadata = UserQuestionMetadata::updateOrCreate(
            ['user_id' => Auth::id(), 'question_id' => $request->question_id]
        );

        $metadata->is_doubt = !$metadata->is_doubt;
        $metadata->save();

        return response()->json([
            'success' => true,
            'is_doubt' => $metadata->is_doubt,
        ]);
    }

    /**
     * Toggle the favorite status of a question.
     */
    public function toggleFavorite(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $metadata = UserQuestionMetadata::updateOrCreate(
            ['user_id' => Auth::id(), 'question_id' => $request->question_id]
        );

        $metadata->is_favorite = !$metadata->is_favorite;
        $metadata->save();

        return response()->json([
            'success' => true,
            'is_favorite' => $metadata->is_favorite,
        ]);
    }
}
