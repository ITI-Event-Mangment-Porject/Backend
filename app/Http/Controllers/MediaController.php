<?php

namespace App\Http\Controllers;

use App\Models\Media\MediaFile;
use Auth;
// use Dotenv\Exception\ValidationException;
use Illuminate\Validation\ValidationException;
use Exception;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $data = $request->validate([
                'file' => 'file|required|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,mp4,avi,mov,mp3,wav',
                'file_type' => 'nullable|string|max:50',
                'related_type' => 'nullable|string',
                'related_id' => 'nullable|integer',
                'is_public' => 'sometimes|boolean'
            ]);
            $file = $request->file('file');
            $path = $file->store('media', 'public');
            $media = MediaFile::create([
                'filename' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_type' => explode('/', $file->getMimeType())[0],
                'uploaded_by' => Auth::id() ?? 2,
                'related_type' => $request->related_type,
                'related_id' => $request->related_id,
                'is_public' => $request->boolean('is_public', false),
                'uploaded_at' => now(),
            ]);
            return response()->json([
                'message' => 'file uploaded successfully',
                'media' => $media,
                'url' => $media->url,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function download($id)
    {
        try {
            $media = MediaFile::findOrFail($id);
            if (!Storage::disk('public')->exists($media->file_path)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            return Storage::disk('public')->download($media->file_path, $media->original_name);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Media not found'], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Download failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function publicAccess($id)
    {
        try {
            $media = MediaFile::findOrFail($id);
            if (!$media->is_public) {
                return response()->json(['message' => 'sorry this file is private'], 403);
            }
            if (!Storage::disk('public')->exists($media->file_path)) {
                return response()->json(['message' => 'File is Not Found'], 404);
            }
            return response()->json(['url' => $media->url], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Media not found'], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to access file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $media = MediaFile::findOrFail($id);
            $user = Auth::user()??2;

            if ($media->uploaded_by !== $user->id && !$user->hasRole('admin')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $filePath = $media->file_path;
            $exists = Storage::disk('public')->exists($filePath);

            if ($exists) {
                $deleted = Storage::disk('public')->delete($filePath);
                if (!$deleted) {
                    return response()->json(['message' => 'File exists but could not be deleted'], 500);
                }
            }

            $media->delete();

            return response()->json([
                'message' => 'File deleted successfully',
                'file_path' => $filePath,
                'file_existed' => $exists
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Media not found'], 404);
        } catch (Exception $e) {
            \Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $media->file_path ?? null,
            ]);
        
            return response()->json([
                'message' => 'Failed to delete file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
