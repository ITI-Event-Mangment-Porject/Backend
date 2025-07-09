<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


use App\Models\NotificationsAndMessaging\BulkMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\FirestoreService;

class BulkMessageController extends Controller
{

    protected $firebase;
      public function __construct(FirestoreService $firebase)
    {
        $this->firebase = $firebase;
    }
    use AuthorizesRequests;

    /**
     * List all bulk messages (Admin only)
     * GET /bulk-messages
     */
    public function index(Request $request)
    {

        $messages = BulkMessage::with('sentBy')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($messages);
    }

    /**
     * Create a new bulk message (Admin only)
     * POST /bulk-messages
     */
    public function store(Request $request)
    {
        $this->authorize('create', BulkMessage::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_criteria' => 'required|array',
            'target_criteria.roles' => 'sometimes|array',
            'target_criteria.events' => 'sometimes|array',
            'scheduled_at' => 'nullable|date'
        ]);

        $message = BulkMessage::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'target_criteria' => $validated['target_criteria'],
            'sent_by' => Auth::id(),
            'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
            'scheduled_at' => $validated['scheduled_at'] ?? null
        ]);

        return response()->json($message, 201);
    }

    /**
     * Send a bulk message (Admin only)
     * POST /bulk-messages/{id}/send
     */
    public function send($id)
    {
        $message = BulkMessage::findOrFail($id);
        $this->authorize('send', $message);

        // Prevent re-sending
        if ($message->status === 'completed') {
            return response()->json([
                'message' => 'This message has already been sent'
            ], 400);
        }

        // Dispatch job to send messages
        dispatch(new \App\Jobs\SendBulkMessages($message));



        $message->update(['status' => 'sending']);
      
      
//         $this->firebase->sendToAllUsers([
//         'title' => 'Testing Message',
//         'body' => 'An important message has been sent to you by the administration',
//         'type' => 'bulk_message',
// ]);



        return response()->json([
            'message' => 'Bulk message is being sent',
            'status' => $message->status
        ]);
    }

    /**
     * Get sending status (Admin only)
     * GET /bulk-messages/{id}/status
     */
    public function status($id)
    {
        $message = BulkMessage::findOrFail($id);
        $this->authorize('view', $message);

        return response()->json([
            'status' => $message->status,
            'progress' => $message->total_recipients > 0 
                ? ($message->sent_count / $message->total_recipients) * 100 
                : 0
        ]);
    }
}