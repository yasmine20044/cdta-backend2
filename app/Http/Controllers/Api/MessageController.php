<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Store a newly created message in storage. (Public)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = Message::create($validator->validated());

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => $message
        ], 201);
    }

    /**
     * Display a listing of messages. (Admin only)
     */
    public function index()
    {
        $messages = Message::orderBy('created_at', 'desc')->get();
        return response()->json($messages);
    }

    /**
     * Mark a message as read. (Admin only)
     */
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update(['read_at' => now()]);

        return response()->json(['message' => 'Message marked as read.']);
    }

    /**
     * Remove the specified message from storage. (Admin only)
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully.']);
    }
}
