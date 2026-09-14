<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudyTechniquesController extends Controller
{
    /**
     * Display the study techniques page.
     */
    public function index()
    {
        return view('study-techniques.index');
    }
}
