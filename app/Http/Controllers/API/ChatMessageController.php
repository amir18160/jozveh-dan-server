<?php

namespace App\Http\Controllers\API;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ChatMessageController extends BaseController
{
    /**
     * GET /chat-messages
     * Optional filters:
     *   - ?group_id=...
     *   - ?resource_id=...
     */
    public function index(Request $request)
    {
        $query = ChatMessage::with(['user', 'replies', 'resource']);

        if ($request->has('group_id')) {
            $query->where('group_id', $request->get('group_id'));
        }
        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->get('resource_id'));
        }

        $messages = $query->latest()->paginate(20);
        return $this->sendResponse($messages, 'Chat messages retrieved successfully.');
    }

    /**
     * POST /chat-messages
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'group_id'    => 'required|exists:groups,id',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
            'resource_id' => 'nullable|exists:resources,id',
            'message'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $msg = ChatMessage::create($validator->validated());

        return $this->sendResponse(
            $msg->load(['user', 'replies', 'resource']),
            'Chat message created successfully.',
            201
        );
    }

    /**
     * GET /chat-messages/{id}
     */
    public function show($id)
    {
        $msg = ChatMessage::with(['user', 'replies', 'resource'])->find($id);
        if (! $msg) {
            return $this->sendError('Chat message not found.', [], 404);
        }
        return $this->sendResponse($msg, 'Chat message retrieved successfully.');
    }

    /**
     * PUT/PATCH /chat-messages/{id}
     */
    public function update(Request $request, $id)
    {
        $msg = ChatMessage::find($id);
        if (! $msg) {
            return $this->sendError('Chat message not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'message'     => 'sometimes|required|string',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
            'resource_id' => 'nullable|exists:resources,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $msg->fill($validator->validated());
        $msg->save();

        return $this->sendResponse(
            $msg->load(['user', 'replies', 'resource']),
            'Chat message updated successfully.'
        );
    }

    /**
     * DELETE /chat-messages/{id}
     */
    public function destroy($id)
    {
        $msg = ChatMessage::find($id);
        if (! $msg) {
            return $this->sendError('Chat message not found.', [], 404);
        }
        $msg->delete();
        return $this->sendResponse(null, 'Chat message deleted successfully.');
    }
}
