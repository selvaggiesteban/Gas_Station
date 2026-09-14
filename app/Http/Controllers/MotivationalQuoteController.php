<?php

namespace App\Http\Controllers;

use App\Models\MotivationalQuote;
use Illuminate\Http\Request;

class MotivationalQuoteController extends Controller
{
    /**
     * Get a random motivational quote for the study session.
     */
    public function getRandom()
    {
        $quote = MotivationalQuote::where('is_active', true)
            ->inRandomOrder()
            ->first();

        return response()->json([
            'success' => $quote ? true : false,
            'quote' => $quote ? $quote->text : '¡Sigue adelante, tú puedes!',
            'author' => $quote ? $quote->author : 'AcademicUS',
        ]);
    }
}
