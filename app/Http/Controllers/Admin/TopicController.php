<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreTopicRequest;
use App\Http\Requests\Admin\UpdateTopicRequest;
use App\Http\Requests\Admin\UploadMaterialRequest;
use App\Http\Requests\Admin\ReorderTopicsRequest;
use Illuminate\Support\Facades\Storage;

class TopicController extends Controller
{
    public function store(StoreTopicRequest $request, Block $block = null)
    {
        $blockId = ($block && $block->id) ? $block->id : $request->block_id;

        if (!$blockId) {
            return redirect()->back()->with('error', 'Falta vincular el ID del bloque.');
        }

        $targetBlock = Block::findOrFail($blockId);

        $targetBlock->topics()->create([
            'title' => $request->title,
            'content' => $request->content,
            'available_at' => $request->available_at,
            'order' => ($targetBlock->topics()->max('order') ?? 0) + 1,
        ]);

        return redirect()->back()->with('success', 'Tema añadido correctamente.');
    }

    public function update(UpdateTopicRequest $request, Topic $topic)
    {
        $topic->update($request->validated());

        return redirect()->back()->with('success', 'Tema actualizado.');
    }

    public function reorder(ReorderTopicsRequest $request)
    {
        foreach ($request->items as $item) {
            Topic::where('id', $item['id'])->update([
                'block_id' => $item['block_id'],
                'order' => $item['order']
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Topic $topic)
    {
        try {
            $topic->delete();
            return redirect()->back()->with('success', 'Tema eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar el tema: está vinculado a otras actividades.');
        }
    }

    public function edit(Topic $topic)
    {
        $topic->load(['block.course', 'pdfVersions', 'resources']);
        return view('admin.topics.edit', compact('topic'));
    }

    public function versions(Topic $topic)
    {
        $topic->load('pdfVersions.uploader');
        $versions = $topic->pdfVersions()->orderBy('version_number', 'desc')->get();
        return view('admin.topics.versions', compact('topic', 'versions'));
    }

    public function storeVersion(Request $request, Topic $topic)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:20480',
            'title' => 'required|string|max:255',
            'version_number' => 'required|integer',
            'change_notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('topics/' . $topic->id . '/versions', 'public');

        $version = $topic->pdfVersions()->create([
            'version_number' => $request->version_number,
            'title' => $request->title,
            'file_path' => $path,
            'upload_date' => now(),
            'is_current' => $request->has('is_current'),
            'change_notes' => $request->change_notes,
            'uploaded_by' => auth()->id(),
        ]);

        if ($version->is_current) {
            $version->setAsCurrent();
            $topic->update(['pdf_url' => $path]);
        }

        return redirect()->back()->with('success', 'Versión de PDF subida correctamente.');
    }

    public function uploadMaterial(Request $request, Topic $topic)
    {
        if ($request->type === 'video') {
            if ($request->filled('external_video_url')) {
                $topic->update([
                    'video_url' => $request->external_video_url,
                    'video_type' => 'explicativa' // default type
                ]);
                return redirect()->back()->with('success', 'Enlace de video actualizado correctamente.');
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                if (!str_starts_with($file->getMimeType(), 'video/')) {
                    return redirect()->back()->with('error', 'El archivo debe ser un video válido.');
                }
                $path = $file->store('topics/' . $topic->id, 'public');
                $topic->update(['video_url' => $path]);
                return redirect()->back()->with('success', 'Video subido correctamente.');
            }
            
            return redirect()->back()->with('error', 'Debes subir un archivo o proporcionar un enlace.');
        }

        // Handle PDF and resources (delegating to validated request if possible or manual check)
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('topics/' . $topic->id, 'public');

            if ($request->type === 'main_pdf') {
                $version = $topic->pdfVersions()->create([
                    'version_number' => \App\Models\TopicPdfVersion::getNextVersionNumber($topic->id),
                    'title' => $request->title ?? 'Versión ' . now()->format('d/m/Y'),
                    'file_path' => $path,
                    'upload_date' => now(),
                    'is_current' => true,
                    'change_notes' => $request->change_notes,
                    'uploaded_by' => auth()->id(),
                ]);
                $version->setAsCurrent();
                $topic->update(['pdf_url' => $path]);
            } elseif ($request->type === 'resource') {
                $topic->resources()->create([
                    'title' => $request->title,
                    'file_path' => $path,
                    'resource_type' => $request->resource_type,
                    'file_size' => $file->getSize(),
                    'is_active' => true,
                    'display_order' => ($topic->resources()->max('display_order') ?? 0) + 1,
                ]);
            }
            return redirect()->back()->with('success', 'Archivo subido correctamente.');
        }

        return redirect()->back()->with('error', 'Falta el archivo o datos requeridos.');
    }

    public function deleteResource(\App\Models\TopicResource $resource)
    {
        Storage::disk('public')->delete($resource->file_path);
        $resource->delete();
        return redirect()->back()->with('success', 'Recurso eliminado.');
    }

    public function deleteVersion(\App\Models\TopicPdfVersion $version)
    {
        if ($version->is_current) {
            return redirect()->back()->with('error', 'No puedes eliminar la versión actual. Marca otra como actual primero.');
        }
        Storage::disk('public')->delete($version->file_path);
        $version->delete();
        return redirect()->back()->with('success', 'Versión eliminada.');
    }

    public function setVersionCurrent(\App\Models\TopicPdfVersion $version)
    {
        $version->setAsCurrent();
        $version->topic->update(['pdf_url' => $version->file_path]);
        return redirect()->back()->with('success', 'Versión actualizada como actual.');
    }
}
