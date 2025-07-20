<?php

namespace App\Http\Controllers\API;

use App\Models\ChatMessage;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ChatMessageController extends BaseController
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }


        $messages = ChatMessage::where('group_id', $request->group_id)
            ->with([
                'user:id,name,profile_image',
                'replies.user:id,name,profile_image', // Load user for replies too
                'replyTo:id,message,user_id', // Load basic info of message being replied to
                'replyTo.user:id,name', // Load user of message being replied to
                'resource:id,title,format'
            ])
            ->latest()
            ->paginate($request->get('per_page', 50));

        return $this->sendResponse($messages, 'Chat messages retrieved successfully.');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id'    => 'required|exists:groups,id',
            'message'     => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
            'resource_id' => 'nullable|exists:resources,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $message = ChatMessage::create([
            'user_id'     => Auth::id(),
            'group_id'    => $request->group_id,
            'message'     => $request->message,
            'reply_to_id' => $request->reply_to_id,
            'resource_id' => $request->resource_id,
        ]);


        $message->load([
            'user:id,name,profile_image',
            'resource:id,title,format',
            'replyTo:id,message,user_id',
            'replyTo.user:id,name'
        ]);


        return $this->sendResponse($message, 'Message sent successfully.', 201);
    }


    public function update(Request $request, ChatMessage $chatMessage)
    {

        if (Auth::id() !== $chatMessage->user_id) {
            return $this->sendError('Forbidden. You can only edit your own messages.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'message'     => 'required|string|max:5000',
            'resource_id' => 'nullable|exists:resources,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $chatMessage->update($validator->validated());
        $chatMessage->load(['user:id,name,profile_image', 'resource:id,title,format']);


        return $this->sendResponse($chatMessage, 'Message updated successfully.');
    }


    public function destroy(ChatMessage $chatMessage)
    {
        $user = Auth::user();
        $group = $chatMessage->group;


        if ($user->id === $chatMessage->user_id || $user->id === $group->owner_id || $user->role === 'admin') {

            $chatMessage->delete();
            return $this->sendResponse(null, 'Message deleted successfully.');
        }

        return $this->sendError('Forbidden. You do not have permission to delete this message.', [], 403);
    }
}
