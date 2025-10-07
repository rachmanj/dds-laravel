<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display inbox (received messages).
     */
    public function index()
    {
        $user = Auth::user();

        $messages = Message::where('receiver_id', $user->id)
            ->notDeletedByReceiver()
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.inbox', compact('messages'));
    }

    /**
     * Display sent messages.
     */
    public function sent()
    {
        $user = Auth::user();

        $messages = Message::where('sender_id', $user->id)
            ->notDeletedBySender()
            ->with(['receiver', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.sent', compact('messages'));
    }

    /**
     * Show the form for creating a new message.
     */
    public function create(Request $request)
    {
        $users = User::active()
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();

        $replyTo = null;
        if ($request->has('reply_to')) {
            $replyTo = Message::with(['sender', 'receiver'])
                ->where('id', $request->reply_to)
                ->where(function ($query) {
                    $query->where('sender_id', Auth::id())
                        ->orWhere('receiver_id', Auth::id());
                })
                ->first();
        }

        return view('messages.create', compact('users', 'replyTo'));
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|array|min:1',
            'receiver_id.*' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();
        $receiverIds = $request->receiver_id;
        $createdMessages = [];
        $attachmentFiles = $request->hasFile('attachments') ? $request->file('attachments') : [];

        // Create a message for each recipient
        foreach ($receiverIds as $receiverId) {
            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'subject' => $request->subject,
                'body' => $request->body,
                'parent_id' => $request->parent_id,
            ]);

            $createdMessages[] = $message;

            // Handle file attachments for each message
            foreach ($attachmentFiles as $file) {
                $path = $file->store('message-attachments', 'public');

                $message->attachments()->create([
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'file_original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $recipientCount = count($receiverIds);
        $successMessage = $recipientCount === 1
            ? 'Message sent successfully!'
            : "Message sent successfully to {$recipientCount} recipients!";

        // Handle AJAX requests with animation response
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect' => route('messages.index'),
                'recipient_count' => $recipientCount
            ]);
        }

        return redirect()->route('messages.index')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified message.
     */
    public function show(Message $message)
    {
        $user = Auth::user();

        // Check if user has access to this message
        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            abort(403, 'Unauthorized access to message.');
        }

        // Mark as read if user is the receiver
        if ($message->receiver_id === $user->id && !$message->isRead()) {
            $message->markAsRead();
        }

        $message->load(['sender', 'receiver', 'attachments', 'replies.sender', 'replies.receiver']);

        return view('messages.show', compact('message'));
    }

    /**
     * Remove the specified message from storage.
     */
    public function destroy(Message $message)
    {
        $user = Auth::user();

        // Check if user has access to this message
        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            abort(403, 'Unauthorized access to message.');
        }

        // Soft delete based on user role
        if ($message->sender_id === $user->id) {
            $message->update(['deleted_by_sender' => true]);
        } else {
            $message->update(['deleted_by_receiver' => true]);
        }

        // If both users have deleted the message, delete attachments and remove from database
        if ($message->deleted_by_sender && $message->deleted_by_receiver) {
            // Delete file attachments
            foreach ($message->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            // Delete the message and its attachments
            $message->attachments()->delete();
            $message->delete();
        }

        return redirect()->back()
            ->with('success', 'Message deleted successfully.');
    }

    /**
     * Get unread messages count for AJAX requests.
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $user->unread_messages_count;

        return response()->json(['count' => $count]);
    }

    /**
     * Mark message as read via AJAX.
     */
    public function markAsRead(Message $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->receiver_id === $user->id) {
            $message->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Search users for message composition.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $users = User::active()
            ->where('id', '!=', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'username']);

        return response()->json($users);
    }
}
