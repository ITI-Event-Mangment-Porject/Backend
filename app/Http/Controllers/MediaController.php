<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media\MediaFile;
use Auth;
// use Dotenv\Exception\ValidationException;
use Illuminate\Validation\ValidationException;
use Exception;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class MediaController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $media = QueryBuilder::for(MediaFile::class)
                ->allowedFilters([
                    AllowedFilter::exact('id'),
                    AllowedFilter::exact('uploaded_by'),
                    AllowedFilter::exact('is_public'),
                    AllowedFilter::partial('filename'),
                    AllowedFilter::partial('original_name'),
                    AllowedFilter::partial('mime_type'),
                    AllowedFilter::partial('file_type'),
                    AllowedFilter::exact('related_type'),
                    AllowedFilter::exact('related_id'),
                    AllowedFilter::scope('uploaded_between'),
                ])
                ->allowedSorts(['uploaded_at', 'file_size', 'filename'])
                ->defaultSort('-uploaded_at')
                ->paginate($request->get('per_page', 15))
                ->appends($request->query());

            return $this->sendResponse($media, 'Media list fetched successfully');

        } catch (Exception $e) {
            return $this->sendError('Failed to fetch media list', [$e->getMessage()], 500);
        }
    }

    public function upload(StoreMediaRequest $request)
    {
        try {
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
            return $this->sendResponse([
                'media' => $media,
                'url' => $media->url,
            ], 'File uploaded successfully', 201);

        } catch (Exception $e) {
            return $this->sendError('Upload Failed', ['error' => $e->getMessage()], 500);
        }
    }
    public function download($id)
    {
        try {
            $media = MediaFile::findOrFail($id);

            if (!Storage::disk('public')->exists($media->file_path)) {
                return $this->sendError('File not found', [], 404);
            }

            return Storage::disk('public')->download($media->file_path, $media->original_name);

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Media not found', [], 404);
        } catch (Exception $e) {
            return $this->sendError('Download failed', ['error' => $e->getMessage()], 500);
        }
    }

    public function publicAccess($id)
    {
        try {
            $media = MediaFile::findOrFail($id);

            if (!$media->is_public) {
                return $this->sendError('Access Denied', ['This file is private'], 403);
            }

            if (!Storage::disk('public')->exists($media->file_path)) {
                return $this->sendError('File Not Found', [], 404);
            }

            return $this->sendResponse(['url' => $media->url], 'Public file access granted');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Media not found', [], 404);
        } catch (Exception $e) {
            return $this->sendError('Failed to access file', ['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $media = MediaFile::findOrFail($id);
            // until we make auth 
            // $user = Auth::user();

            // if (!$user) {
            //     return $this->sendError('Unauthorized access', [], 401);
            // }

            // if ($media->uploaded_by !== $user->id && !$user->hasRole('admin')) {
            //     return $this->sendError('Unauthorized access', [], 403);
            // }

            $filePath = $media->file_path;
            $exists = Storage::disk('public')->exists($filePath);

            if ($exists) {
                $deleted = Storage::disk('public')->delete($filePath);
                if (!$deleted) {
                    return $this->sendError('File exists but could not be deleted', [], 500);
                }
            }

            $media->delete();

            return $this->sendResponse([
                'file_path' => $filePath,
                'file_existed' => $exists
            ], 'File deleted successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Media not found', [], 404);
        } catch (Exception $e) {
            \Log::error('File deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $media->file_path ?? null,
            ]);

            return $this->sendError('Failed to delete file', ['error' => $e->getMessage()], 500);
        }
    }


}
